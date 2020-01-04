<?php
namespace verbb\giftvoucher\controllers;

use craft\base\Element;
use craft\base\Field;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

class CodesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * init
     *
     * @throws \yii\web\ForbiddenHttpException
     */
    public function init()
    {
        $this->requirePermission('giftVoucher-manageCodes');

        parent::init();
    }

    /**
     * Action Edit
     *
     * @param int|null                              $codeId
     * @param \verbb\giftvoucher\elements\Code|\craft\base\ElementInterface|null $code
     *
     * @return \yii\web\Response
     */
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
        if(empty($tabs) === false){
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
                    /** @var Field $field */
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

    /**
     * Action Save
     *
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     * @return \yii\web\Response|null
     */
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

    /**
     * Action Delete
     *
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     * @return \yii\web\Response|null
     */
    public function actionDelete()
    {
        $this->requirePostRequest();

        $codeId = Craft::$app->getRequest()->getRequiredBodyParam('codeId');
        $code = Craft::$app->getElements()->getElementById($codeId, Code::class);

        if(!$code){
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
}
