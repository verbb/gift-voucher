<?php

namespace Craft;

class GiftVoucher_VoucherTypesController extends BaseController
{

    // Public Methods
    // =========================================================================

    /**
     * Create or edit a Voucher Type
     *
     * @param array $variables
     *
     * @throws HttpException
     */
    public function actionEdit(array $variables = [])
    {
        if (empty($variables['voucherType'])) {
            if (empty($variables['voucherTypeId'])) {
                $voucherType = new GiftVoucher_VoucherTypeModel();
            } else {
                $voucherType = GiftVoucherHelper::getVoucherTypesService()->getVoucherTypeById($variables['voucherTypeId']);
            }
            if (!$voucherType) {
                $voucherType = new GiftVoucher_VoucherTypeModel();
            }
            $variables['voucherType'] = $voucherType;
        }
        
        $variables['title'] = empty($variables['voucherType']->id) ? Craft::t('Create a new voucher type') : $variables['voucherType']->name;

        $this->renderTemplate('giftvoucher/vouchertypes/_edit', $variables);
    }

    /**
     * Save a Voucher Type
     *
     * @throws HttpException
     * @throws Exception
     * @throws \Exception
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $voucherType = new GiftVoucher_VoucherTypeModel();

        $voucherType->id = craft()->request->getPost('voucherTypeId');
        $voucherType->name = craft()->request->getPost('name');
        $voucherType->handle = craft()->request->getPost('handle');
        $voucherType->hasUrls = craft()->request->getPost('hasUrls');
        $voucherType->skuFormat = craft()->request->getPost('skuFormat');
        $voucherType->template = craft()->request->getPost('template');

        $locales = [];

        foreach (craft()->i18n->getSiteLocaleIds() as $localeId) {
            $locales[$localeId] = new GiftVoucher_VoucherTypeLocaleModel([
                'locale' => $localeId,
                'urlFormat' => craft()->request->getPost('urlFormat.'.$localeId)
            ]);
        }

        $voucherType->setLocales($locales);

        $fieldLayout = craft()->fields->assembleLayoutFromPost();
        $fieldLayout->type = 'GiftVoucher_Voucher';
        $voucherType->asa('voucherFieldLayout')->setFieldLayout($fieldLayout);

        // Save it
        if (GiftVoucherHelper::getVoucherTypesService()->saveVoucherType($voucherType)) {
            craft()->userSession->setNotice(Craft::t('Voucher type saved.'));
            $this->redirectToPostedUrl($voucherType);
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save voucher type.'));
        }

        // Send the voucherType back to the template
        craft()->urlManager->setRouteVariables([
            'voucherType' => $voucherType
        ]);
    }

    /**
     * Delete a Voucher Type
     *
     * @throws HttpException
     */
    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        try {
            GiftVoucherHelper::getVoucherTypesService()->deleteVoucherTypeById($id);
            $this->returnJson(['success' => true]);
        } catch (\Exception $e) {
            $this->returnErrorJson($e->getMessage());
        }
    }

}
