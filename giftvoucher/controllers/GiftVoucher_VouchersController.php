<?php

namespace Craft;

class GiftVoucher_VouchersController extends BaseController
{

    /**
     * @var bool
     */
    protected $allowAnonymous = ['actionViewSharedVoucher'];

    // Public Methods
    // =========================================================================

    /**
     * Index of gift voucher
     *
     * @param array $variables
     *
     * @throws HttpException
     */
    public function actionIndex(array $variables = [])
    {
        $this->renderTemplate('giftvoucher/vouchers/index', $variables);
    }

    /**
     * Prepare screen to edit a voucher.
     *
     * @param array $variables
     *
     * @throws Exception
     * @throws HttpException in case of lacking permissions or missing/corrupt data
     */
    public function actionEdit(array $variables = [])
    {
        // Make sure a correct voucher type handle was passed so we can check permissions
        if (!empty($variables['voucherTypeHandle'])) {
            $variables['voucherType'] = GiftVoucherHelper::getVoucherTypesService()->getVoucherTypeByHandle($variables['voucherTypeHandle']);
        }

        if (empty($variables['voucherType'])) {
            throw new HttpException(404);
        }

        $this->_prepareVariableArray($variables);
        $this->_maybeEnableLivePreview($variables);

        $this->renderTemplate('giftvoucher/vouchers/_edit', $variables);
    }

    /**
     * Deletes a Voucher.
     *
     * @throws HttpException if no product found
     * @throws \Exception
     */
    public function actionDeleteVoucher()
    {
        $this->requirePostRequest();

        $voucherId = craft()->request->getRequiredPost('voucherId');
        $voucher = GiftVoucherHelper::getVouchersService()->getVoucherById($voucherId);

        if (!$voucher) {
            throw new HttpException(404);
        }

        if (GiftVoucherHelper::getVouchersService()->deleteVoucher($voucher)) {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => true]);
            } else {
                craft()->userSession->setNotice(Craft::t('Voucher deleted.'));
                $this->redirectToPostedUrl($voucher);
            }
        } else {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => false]);
            } else {
                craft()->userSession->setError(Craft::t('Couldn’t delete voucher.'));

                craft()->urlManager->setRouteVariables([
                    'voucher' => $voucher
                ]);
            }
        }
    }

    /**
     * Save a new or existing voucher.
     *
     * @throws HttpException
     * @throws Exception
     * @throws \Exception
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $voucher = $this->_buildVoucherFromPost();

        $products = craft()->request->getPost('products', []);
        if (!$products) {
            $products = [];
        }

        $productTypes = craft()->request->getPost('productTypes', []);
        if (!$productTypes) {
            $productTypes = [];
        }

        $existingVoucher = (bool)$voucher->id;

        if (GiftVoucherHelper::getVouchersService()->saveVoucher($voucher, $productTypes, $products)) {
            craft()->userSession->setNotice(Craft::t('Voucher saved.'));
            $this->redirectToPostedUrl($voucher);
        }

        if (!$existingVoucher) {
            $voucher->id = null;
        }

        craft()->userSession->setError(Craft::t('Couldn’t save voucher.'));
        craft()->urlManager->setRouteVariables([
            'voucher' => $voucher
        ]);
    }

    /**
     * @throws Exception
     * @throws HttpException
     */
    public function actionPreviewVoucher()
    {
        $this->requirePostRequest();

        $voucher = $this->_buildVoucherFromPost();
//        $this->_enforceVoucherPermissionsForVoucherType($voucher->typeId);

        $this->_showVoucher($voucher);
    }

    /**
     * @param int  $voucherId
     * @param null $locale
     *
     * @throws Exception
     * @throws HttpException
     */
    public function actionShareVoucher($voucherId, $locale = null)
    {
        $voucher = GiftVoucherHelper::getVouchersService()->getVoucherById($voucherId, $locale);

        if (!$voucher || !GiftVoucherHelper::getVoucherTypesService()->isVoucherTypeTemplateValid($voucher->getVoucherType())) {
            throw new HttpException(404);
        }

//        $this->_enforceVoucherPermissionsForVoucherType($voucher->typeId);

        // Create the token and redirect to the voucher URL with the token in place
        $token = craft()->tokens->createToken(array(
            'action' => 'giftVoucher/vouchers/viewSharedVoucher',
            'params' => array(
                'voucherId' => $voucherId,
                'locale' => $voucher->locale
            )
        ));

        $url = UrlHelper::getUrlWithToken($voucher->getUrl(), $token);
        craft()->request->redirect($url);
    }

    /**
     * @param int  $voucherId
     * @param null $locale
     *
     * @throws HttpException
     */
    public function actionViewSharedVoucher($voucherId, $locale = null)
    {
        $this->requireToken();

        $voucher = GiftVoucherHelper::getVouchersService()->getVoucherById($voucherId, $locale);

        if (!$voucher) {
            throw new HttpException(404);
        }

        $this->_showVoucher($voucher);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param GiftVoucher_VoucherModel $voucher
     *
     * @throws HttpException
     */
    private function _showVoucher(GiftVoucher_VoucherModel $voucher)
    {
        $voucherType = $voucher->getVoucherType();

        if (!$voucherType) {
            throw new HttpException(404);
        }

        craft()->setLanguage($voucher->locale);

        // Have this voucher override any freshly queried vouchers with the same ID/locale
        craft()->elements->setPlaceholderElement($voucher);

        craft()->templates->getTwig()->disableStrictVariables();

        $this->renderTemplate($voucherType->template, array(
            'voucher' => $voucher
        ));
    }

    /**
     * @param int $voucherTypeId
     *
     * @throws HttpException
     */
//    private function _enforceVoucherPermissionsForVoucherType($voucherTypeId)
//    {
//        // Check for general event commerce access
//        if (!craft()->userSession->checkPermission('vouchers-manageVouchers')) {
//            throw new HttpException(403, Craft::t('This action is not allowed for the current user.'));
//        }
//
//        // Check if the user can edit the voucher in the event type
//        if (!craft()->userSession->getUser()->can('vouchers-manageVoucherType:' . $voucherTypeId)) {
//            throw new HttpException(403, Craft::t('This action is not allowed for the current user.'));
//        }
//    }

    /**
     * Prepare $variable array for editing a Voucher
     *
     * @param array $variables by reference
     *
     * @throws HttpException in case of missing/corrupt data or lacking permissions.
     */
    private function _prepareVariableArray(&$variables)
    {
        // Locale related checks
        $variables['localeIds'] = craft()->i18n->getEditableLocaleIds();

        if (!$variables['localeIds']) {
            throw new HttpException(403, Craft::t('Your account doesn’t have permission to edit any of this site’s locales.'));
        }

        if (empty($variables['localeId'])) {
            $variables['localeId'] = craft()->language;

            if (!in_array($variables['localeId'], $variables['localeIds'])) {
                $variables['localeId'] = $variables['localeIds'][0];
            }
        } else {
            // Make sure they were requesting a valid locale
            if (!in_array($variables['localeId'], $variables['localeIds'])) {
                throw new HttpException(404);
            }
        }

        // Voucher related checks
        if (empty($variables['voucher'])) {
            if (!empty($variables['voucherId'])) {
                $variables['voucher'] = GiftVoucherHelper::getVouchersService()->getVoucherById($variables['voucherId'], $variables['localeId']);

                if (!$variables['voucher']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['voucher'] = new GiftVoucher_VoucherModel();
                $variables['voucher']->typeId = $variables['voucherType']->id;

                // Set default categories
                $taxCategories = craft()->commerce_taxCategories->getDefaultTaxCategory();
                $variables['voucher']->taxCategoryId = $taxCategories->attributes['id'];
                $shippingCategories = craft()->commerce_shippingCategories->getDefaultShippingCategory();
                $variables['voucher']->shippingCategoryId = $shippingCategories->attributes['id'];

                if (!empty($variables['localeId'])) {
                    $variables['voucher']->locale = $variables['localeId'];
                }
            }
        }

        // Enable locales
        if (!empty($variables['voucher']->id)) {
            $variables['enabledLocales'] = craft()->elements->getEnabledLocalesForElement($variables['voucher']->id);
        } else {
            $variables['enabledLocales'] = [];

            foreach (craft()->i18n->getEditableLocaleIds() as $locale) {
                $variables['enabledLocales'][] = $locale;
            }
        }

        // Set up tabs
        $variables['tabs'] = [];

        foreach ($variables['voucherType']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;
            if ($variables['voucher']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($variables['voucher']->getErrors($field->getField()->handle)) {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t($tab->name),
                'url' => '#tab'.($index + 1),
                'class' => ($hasErrors ? 'error' : null)
            ];
        }

        // Set up title and the URL for continuing editing the voucher
        if (!empty($variables['voucher']->id)) {
            $variables['title'] = $variables['voucher']->title;
        } else {
            $variables['title'] = Craft::t('Create a new voucher');
        }

        $variables['continueEditingUrl'] = "giftvoucher/vouchers/".$variables['voucherType']->handle."/{id}".
            (craft()->isLocalized() && !empty($variables['localeId']) && craft()->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');

        //getting product types maps
        $types = craft()->commerce_productTypes->getAllProductTypes();
        $variables['types'] = \CHtml::listData($types, 'id', 'name');

        $variables['products'] = null;
        $products = $productIds = [];
//        if (empty($variables['voucher']->id)) {
//            $productIds = explode('|', craft()->request->getParam('productIds'));
//        } else {
//            $productIds = $variables['voucher']->getProductIds();
//        }
//        foreach ($productIds as $productId) {
//            $product = craft()->commerce_products->getProductById($productId);
//            if($product){
//                $products[] = $product;
//            }
//        }
        $variables['products'] = $products;
    }

    /**
     * @param array $variables
     *
     * @throws Exception
     */
    private function _maybeEnableLivePreview(array &$variables)
    {
        if (!craft()->request->isMobileBrowser(true)
            && !empty($variables['voucherType'])
            && GiftVoucherHelper::getVoucherTypesService()->isVoucherTypeTemplateValid($variables['voucherType'])
        ) {

            craft()->templates->includeJs('Craft.LivePreview.init('.JsonHelper::encode(array(
                    'fields' => '#title-field, #fields > div > div > .field, #sku-field, #price-field',
                    'extraFields' => '#meta-pane .field',
                    'previewUrl' => $variables['voucher']->getUrl(),
                    'previewAction' => 'giftVoucher/vouchers/previewVoucher',
                    'previewParams' => array(
                        'typeId' => $variables['voucherType']->id,
                        'voucherId' => $variables['voucher']->id,
                        'locale' => $variables['voucher']->locale,
                    )
                )).');');

            $variables['showPreviewBtn'] = true;

            // Should we show the Share button too?
            if ($variables['voucher']->id) {
                // If the event is enabled, use its main URL as its share URL.
                if ($variables['voucher']->enabled) {
                    $variables['shareUrl'] = $variables['voucher']->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::getActionUrl('giftVoucher/vouchers/shareVoucher', array(
                        'voucherId' => $variables['voucher']->id,
                        'locale' => $variables['voucher']->locale
                    ));
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }
    }

    /**
     * @return GiftVoucher_VoucherModel
     * @throws Exception
     */
    private function _buildVoucherFromPost()
    {
        $voucherId = craft()->request->getPost('voucherId');
        $locale = craft()->request->getPost('locale');

        if ($voucherId) {
            $voucher = GiftVoucherHelper::getVouchersService()->getVoucherById($voucherId, $locale);

            if (!$voucher) {
                throw new Exception(Craft::t('No voucher with the ID “{id}”',
                    ['id' => $voucherId]));
            }
        } else {
            $voucher = new GiftVoucher_VoucherModel();
        }

        $data = craft()->request->getPost();

        $fields = [
            'expiry',
//            'description',
            'enabled',
//            'purchaseTotal',
//            'purchaseQty',
//            'maxPurchaseQty',
//            'freeShipping',
//            'excludeOnSale',
            'sku',
            'typeId'
        ];

        foreach ($fields as $field) {
            $voucher->$field = craft()->request->getPost($field);
        }

        $voucher->customAmount = $data['customAmount'];

        if ($voucher->customAmount) {
            $voucher->price = 0.00;
        } else {
            $voucher->price = (float)$data['price'];
        }

        $voucher->taxCategoryId = isset($data['taxCategoryId']) ? $data['taxCategoryId'] : $voucher->taxCategoryId;
        $voucher->shippingCategoryId = isset($data['shippingCategoryId']) ? $data['shippingCategoryId'] : $voucher->shippingCategoryId;
        $voucher->slug = $data['slug'] ? $data['slug'] : $voucher->slug;

        $voucher->localeEnabled = (bool)craft()->request->getPost('localeEnabled', $voucher->localeEnabled);
        $voucher->getContent()->title = craft()->request->getPost('title', $voucher->title);
        $voucher->setContentFromPost('fields');

        return $voucher;
    }
}
