<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;

use Craft;
// use craft\elements\User;
use craft\helpers\DateTimeHelper;
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
