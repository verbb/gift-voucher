<?php

namespace Craft;

class GiftVoucher_CodesController extends BaseController
{

    // Public Methods
    // =========================================================================

    /**
     * Edit a Voucher Code
     *
     * @param array $variables
     *
     * @throws HttpException
     * @throws \Exception
     */
    public function actionEdit(array $variables = [])
    {
        if (empty($variables['voucherCode'])) {
            if (empty($variables['codeId'])) {
                $voucherCode = new GiftVoucher_CodeModel();
            } else {
                $voucherCode = GiftVoucherHelper::getCodesService()->getCodeById($variables['codeId']);
            }
            if (!$voucherCode) {
                $voucherCode = new GiftVoucher_CodeModel();
            }
            $variables['voucherCode'] = $voucherCode;
        }

        $variables['title'] = empty($variables['voucherCode']->id) ? Craft::t('Create a new voucher code') : $variables['voucherCode']->codeKey;

        $variables['voucher'] = [];

        if ($variables['voucherCode']->voucherId) {
            $variables['voucher'] = GiftVoucherHelper::getVouchersService()->getVouchers([
                'id' => $variables['voucherCode']->voucherId,
            ]);
        }

        $this->renderTemplate('giftvoucher/codes/_edit', $variables);
    }

    /**
     * Save a Voucher Code
     *
     * @throws HttpException
     * @throws \Exception
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $voucherCode = New GiftVoucher_CodeModel();

        $voucherCode->id = craft()->request->getPost('codeId');
        $voucherCode->currentAmount = craft()->request->getPost('currentAmount');
        $voucherCode->originAmount = craft()->request->getPost('originAmount');

        if (!$voucherCode->originAmount) {
            $voucherCode->originAmount = $voucherCode->currentAmount;
        }

        $voucherCode->expiryDate = craft()->request->getPost('expiryDate');
        $voucherId = craft()->request->getPost('voucherId');

        if (\is_array($voucherId)) {
            $voucherCode->voucherId = $voucherId[0];
        } else {
            $voucherCode->voucherId = $voucherId;
        }

        if (empty(craft()->request->getPost('codeId')) || craft()->request->getPost('manually')) {
            $voucherCode->manually = true;
        } else {
            $voucherCode->manually = false;
        }

        // Save it
        if (GiftVoucherHelper::getCodesService()->saveCode($voucherCode)) {
            craft()->userSession->setNotice(Craft::t('Code saved.'));
            $this->redirectToPostedUrl($voucherCode);
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save code.'));
        }

        // Send the code back to the template
        craft()->urlManager->setRouteVariables([
            'voucherCode' => $voucherCode,
        ]);
    }

    /**
     * Delete a Voucher Code
     *
     * @throws HttpException
     */
    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        try {
            GiftVoucherHelper::getCodesService()->deleteCodeById($id);
            $this->returnJson(['success' => true]);
        } catch (\Exception $e) {
            $this->returnErrorJson($e->getMessage());
        }
    }
}
