<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

class CodesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requirePermission('giftVoucher-manageCodes');

        parent::init();
    }

    public function actionEdit(int $codeId = null, Code $code = null): Response
    {
        if ($code === null) {
            if ($codeId === null) {
                $code = new Code();
            } else {
                $code = Craft::$app->getElements()->getElementById($codeId, Code::class);

                if (!$code) {
                    $code = new Code();
                }
            }
        }

        $variables['tabs'] = [];
        $tabs = $code->getFieldLayout()->getTabs();

        // prepend the first tab as general setting in case there are no field layout tabs
        if (empty($tabs) === false) {
            $variables['tabs'][] = [
                // TODO: Maybe There is a better wording for it ¯\_(ツ)_/¯
                'label' => Craft::t('app', 'Settings'),
                'url' => '#general',
                'class' => $code->getErrors('voucherId') ? 'error' : null
            ];
        }

        // include each field layout tab
        foreach ($code->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            // check if there are any errors for this tab
            if ($code->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($hasErrors = $code->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#' . $tab->getHtmlId(),
                'class' => $hasErrors ? 'error' : null
            ];
        }

        $variables['title'] = $code->id ? (string) $code : Craft::t('gift-voucher', 'Create a new Code');
        $variables['code'] = $code;
        $variables['voucherElementType'] = Voucher::class;
        $variables['continueEditingUrl'] = 'gift-voucher/codes/{id}';

        return $this->renderTemplate('gift-voucher/codes/_edit', $variables);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $codeId = $request->getBodyParam('codeId');

        if ($codeId) {
            $code = Craft::$app->getElements()->getElementById($codeId, Code::class);

            if (!$code) {
                throw new Exception('No code with the ID “{id}”', ['id' => $codeId]);
            }
        } else {
            $code = new Code();
        }

        $voucherIds = $request->getBodyParam('voucher');

        if (is_array($voucherIds) && !empty($voucherIds)) {
            $code->voucherId = reset($voucherIds);
        }

        $code->id = $request->getBodyParam('codeId');
        $code->enabled = (bool)$request->getBodyParam('enabled');
        $code->originalAmount = $request->getBodyParam('originalAmount');
        $code->currentAmount = $request->getBodyParam('currentAmount');
        $code->expiryDate = (($date = $request->getParam('expiryDate')) !== false ? (DateTimeHelper::toDateTime($date) ?: null) : $code->expiryDate);

        if (!$code->originalAmount) {
            $code->originalAmount = $code->currentAmount;
        }

        // Sanity checks
        if ($code->currentAmount === '') {
            $code->addError('currentAmount', Craft::t('gift-voucher', 'Amount is required.'));

            Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t save code.'));
            Craft::$app->getUrlManager()->setRouteParams(['code' => $code]);

            return null;
        }

        // populate fields
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $code->setFieldValuesFromRequest($fieldsLocation);

        // validate fields
        $code->setScenario(Element::SCENARIO_LIVE);

        // Save it
        if (!Craft::$app->getElements()->saveElement($code)) {
            Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t save code.'));
            Craft::$app->getUrlManager()->setRouteParams(['code' => $code]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('gift-voucher', 'Code saved.'));

        return $this->redirectToPostedUrl($code);
    }

    public function actionDelete()
    {
        $this->requirePostRequest();

        $codeId = Craft::$app->getRequest()->getRequiredBodyParam('codeId');
        $code = Craft::$app->getElements()->getElementById($codeId, Code::class);

        if (!$code) {
            throw new Exception('No code with the ID “{id}”', ['id' => $codeId]);
        }

        if (Craft::$app->getElements()->deleteElement($code)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => true]);
            }

            Craft::$app->getSession()->setNotice(Craft::t('gift-voucher', 'Code deleted.'));
            return $this->redirectToPostedUrl($code);
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => false]);
        }

        Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t delete code.'));
        Craft::$app->getUrlManager()->setRouteParams(['code' => $code]);

        return null;
    }

    public function actionBulkGenerate()
    {
        $variables = Craft::$app->getUrlManager()->getRouteParams();
        $variables['voucherElementType'] = Voucher::class;

        return $this->renderTemplate('gift-voucher/codes/_bulk-generate', $variables);
    }

    public function actionBulkGenerateSubmit()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $errors = [];
        $amount = (int)$request->getBodyParam('amount');
        $voucherAmount = (float)$request->getBodyParam('voucherAmount');
        $voucher = null;

        $voucherIds = $request->getBodyParam('voucher');
        
        if (!empty($voucherIds) && is_array($voucherIds)) {
            $voucherId = reset($voucherIds);
            $voucher = GiftVoucher::$plugin->getVouchers()->getVoucherById($voucherId);
        }

        $expiryDate = $request->getBodyParam('expiryDate') ? (DateTimeHelper::toDateTime($request->getBodyParam('expiryDate')) ?: null) : null;

        if (!($amount > 0)) {
            $errors['amount'][] = Craft::t('gift-voucher', 'You should at least generate one voucher code.');
        }

        if (!($voucherAmount > 0)) {
            $errors['voucherAmount'][] = Craft::t('gift-voucher', 'Choose the amount the voucher code is worth.');
        }

        if (!$voucher) {
            $errors['voucher'][] = Craft::t('gift-voucher', 'Select a voucher to assign the codes to.');
        }

        if (!empty($errors)) {
            Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Failed generating voucher codes.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'errors' => $errors,
            ]);

            return null;
        }

        $baseCode = new Code();
        $baseCode->voucherId = $voucherId;
        $baseCode->enabled = true;
        $baseCode->currentAmount = $voucherAmount;
        $baseCode->originalAmount = $baseCode->currentAmount;
        $baseCode->expiryDate = $expiryDate;

        $savedCodes = [];

        for ($i = 0; $i <= $amount; $i++) {
            $code = clone $baseCode;

            if (!Craft::$app->getElements()->saveElement($code)) {
                Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t save code.'));

                return null;
            }

            $savedCodes[] = $code->id;
        }

        Craft::$app->getSession()->setNotice(Craft::t('gift-voucher', 'Voucher codes generated.'));

        return $this->redirect(UrlHelper::url('gift-voucher/codes/bulk-generate-success', [
            'savedCodes' => $savedCodes,
        ]));
    }
}
