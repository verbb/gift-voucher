<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\base\Element;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Localization;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class VouchersController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = ['actionViewSharedVoucher'];


    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requirePermission('giftVoucher-manageVouchers');

        parent::init();
    }

    public function actionIndex(): Response
    {
        return $this->renderTemplate('gift-voucher/vouchers/index');
    }

    public function actionEdit(string $voucherTypeHandle, int $voucherId = null, string $siteHandle = null, Voucher $voucher = null): Response
    {
        $voucherType = null;

        $variables = [
            'voucherTypeHandle' => $voucherTypeHandle,
            'voucherId' => $voucherId,
            'voucher' => $voucher
        ];

        // Make sure a correct voucher type handle was passed so we can check permissions
        if ($voucherTypeHandle) {
            $voucherType = GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeByHandle($voucherTypeHandle);
        }

        if (!$voucherType) {
            throw new Exception('The voucher type was not found.');
        }

        $this->requirePermission('giftVoucher-manageVoucherType:' . $voucherType->id);
        $variables['voucherType'] = $voucherType;

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new Exception('Invalid site handle: '.$siteHandle);
            }
        }

        $this->_prepareVariableArray($variables);

        if (!empty($variables['voucher']->id)) {
            $variables['title'] = $variables['voucher']->title;
        } else {
            $variables['title'] = Craft::t('gift-voucher', 'Create a new voucher');
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'gift-voucher/vouchers/' . $variables['voucherTypeHandle'] . '/{id}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] . (Craft::$app->getIsMultiSite() && Craft::$app->getSites()->currentSite->id !== $variables['site']->id ? '/' . $variables['site']->handle : '');

        $this->_maybeEnableLivePreview($variables);

        $variables['tabs'] = [];

        foreach ($variables['voucherType']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($variables['voucher']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($hasErrors = $variables['voucher']->hasErrors($field->handle . '.*')) {
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

        return $this->renderTemplate('gift-voucher/vouchers/_edit', $variables);
    }

    public function actionDeleteVoucher()
    {
        $this->requirePostRequest();

        $voucherId = Craft::$app->getRequest()->getRequiredParam('voucherId');
        $voucher = Voucher::findOne($voucherId);

        if (!$voucher) {
            throw new Exception(Craft::t('gift-voucher', 'No voucher exists with the ID “{id}”.',['id' => $voucherId]));
        }

        $this->enforceVoucherPermissions($voucher);

        if (!Craft::$app->getElements()->deleteElement($voucher)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t delete voucher.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'voucher' => $voucher
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('gift-voucher', 'Voucher deleted.'));

        return $this->redirectToPostedUrl($voucher);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $voucher = $this->_setVoucherFromPost();

        $this->enforceVoucherPermissions($voucher);

        if ($voucher->enabled && $voucher->enabledForSite) {
            $voucher->setScenario(Element::SCENARIO_LIVE);
        }

        if (!Craft::$app->getElements()->saveElement($voucher)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $voucher->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t save voucher.'));

            // Send the category back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'voucher' => $voucher
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $voucher->id,
                'title' => $voucher->title,
                'status' => $voucher->getStatus(),
                'url' => $voucher->getUrl(),
                'cpEditUrl' => $voucher->getCpEditUrl()
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Voucher saved.'));

        return $this->redirectToPostedUrl($voucher);
    }

    public function actionPreviewVoucher(): Response
    {

        $this->requirePostRequest();

        $voucher = $this->_setVoucherFromPost();

        $this->enforceVoucherPermissions($voucher);

        return $this->_showVoucher($voucher);
    }

    public function actionShareVoucher($voucherId, $siteId): Response
    {
        $voucher = GiftVoucher::getInstance()->getVouchers()->getVoucherById($voucherId, $siteId);

        if (!$voucher) {
            throw new HttpException(404);
        }

        $this->enforceVoucherPermissions($voucher);

        if (!GiftVoucher::$plugin->getVoucherTypes()->isVoucherTypeTemplateValid($voucher->getType(), $voucher->siteId)) {
            throw new HttpException(404);
        }

        $this->requirePermission('giftVoucher-manageVoucherType:' . $voucher->typeId);

        // Create the token and redirect to the voucher URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'gift-voucher/vouchers/view-shared-voucher', ['voucherId' => $voucher->id, 'siteId' => $siteId]
        ]);

        $url = UrlHelper::urlWithToken($voucher->getUrl(), $token);

        return $this->redirect($url);
    }

    public function actionViewSharedVoucher($voucherId, $site = null)
    {
        $this->requireToken();

        $voucher = GiftVoucher::getInstance()->getVouchers()->getVoucherById($voucherId, $site);

        if (!$voucher) {
            throw new HttpException(404);
        }

        $this->_showVoucher($voucher);

        return null;
    }


    // Protected Methods
    // =========================================================================

    protected function enforceVoucherPermissions(Voucher $voucher)
    {
        if (!$voucher->getType()) {
            Craft::error('Attempting to access a voucher that doesn’t have a type', __METHOD__);
            throw new HttpException(404);
        }

        $this->requirePermission('giftVoucher-manageVoucherType:' . $voucher->getType()->id);
    }


    // Private Methods
    // =========================================================================

    private function _showVoucher(Voucher $voucher): Response
    {

        $voucherType = $voucher->getType();

        if (!$voucherType) {
            throw new ServerErrorHttpException('Voucher type not found.');
        }

        $siteSettings = $voucherType->getSiteSettings();

        if (!isset($siteSettings[$voucher->siteId]) || !$siteSettings[$voucher->siteId]->hasUrls) {
            throw new ServerErrorHttpException('The voucher ' . $voucher->id . ' doesn\'t have a URL for the site ' . $voucher->siteId . '.');
        }

        $site = Craft::$app->getSites()->getSiteById($voucher->siteId);

        if (!$site) {
            throw new ServerErrorHttpException('Invalid site ID: ' . $voucher->siteId);
        }

        Craft::$app->language = $site->language;

        // Have this voucher override any freshly queried vouchers with the same ID/site
        Craft::$app->getElements()->setPlaceholderElement($voucher);

        $this->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($siteSettings[$voucher->siteId]->template, [
            'voucher' => $voucher
        ]);
    }

    private function _prepareVariableArray(&$variables)
    {
        // Locale related checks
        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites supported by this section');
        }

        if (empty($variables['site'])) {
            $site = $variables['site'] = Craft::$app->getSites()->currentSite;

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $site = $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        // Voucher related checks
        if (empty($variables['voucher'])) {
            if (!empty($variables['voucherId'])) {
                $variables['voucher'] = Craft::$app->getElements()->getElementById($variables['voucherId'], Voucher::class, $site->id);

                if (!$variables['voucher']) {
                    throw new Exception('Missing voucher data.');
                }
            } else {
                $variables['voucher'] = new Voucher();
                $variables['voucher']->typeId = $variables['voucherType']->id;

                if (!empty($variables['siteId'])) {
                    $variables['voucher']->site = $variables['siteId'];
                }
            }
        }

        // Enable locales
        if ($variables['voucher']->id) {
            $variables['enabledSiteIds'] = Craft::$app->getElements()->getEnabledSiteIdsForElement($variables['voucher']->id);
        } else {
            $variables['enabledSiteIds'] = [];

            foreach (Craft::$app->getSites()->getEditableSiteIds() as $site) {
                $variables['enabledSiteIds'][] = $site;
            }
        }
    }

    private function _maybeEnableLivePreview(array &$variables)
    {
        if (!Craft::$app->getRequest()->isMobileBrowser(true) && GiftVoucher::$plugin->getVoucherTypes()->isVoucherTypeTemplateValid($variables['voucherType'], $variables['site']->id)) {
            $this->getView()->registerJs('Craft.LivePreview.init('.Json::encode([
                    'fields' => '#title-field, #fields > div > div > .field',
                    'extraFields' => '#meta-pane',
                    'previewUrl' => $variables['voucher']->getUrl(),
                    'previewAction' => 'gift-voucher/vouchers/preview-voucher',
                    'previewParams' => [
                        'typeId' => $variables['voucherType']->id,
                        'voucherId' => $variables['voucher']->id,
                        'siteId' => $variables['voucher']->siteId,
                    ]
                ]).');');

            $variables['showPreviewBtn'] = true;

            // Should we show the Share button too?
            if ($variables['voucher']->id) {
                // If the voucher is enabled, use its main URL as its share URL.
                if ($variables['voucher']->getStatus() === Voucher::STATUS_LIVE) {
                    $variables['shareUrl'] = $variables['voucher']->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::actionUrl('gift-voucher/vouchers/share-voucher', [
                        'voucherId' => $variables['voucher']->id,
                        'siteId' => $variables['voucher']->siteId
                    ]);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }
    }

    private function _setVoucherFromPost(): Voucher
    {
        $request = Craft::$app->getRequest();
        $voucherId = $request->getBodyParam('voucherId');
        $siteId = $request->getBodyParam('siteId');

        if ($voucherId) {
            $voucher = GiftVoucher::getInstance()->getVouchers()->getVoucherById($voucherId, $siteId);

            if (!$voucher) {
                throw new Exception(Craft::t('gift-voucher', 'No voucher with the ID “{id}”', ['id' => $voucherId]));
            }
        } else {
            $voucher = new Voucher();
        }

        $voucher->typeId = $request->getBodyParam('typeId');
        $voucher->siteId = $siteId ?? $voucher->siteId;
        $voucher->enabled = (bool)$request->getBodyParam('enabled');

        $voucher->price = Localization::normalizeNumber($request->getBodyParam('price'));
        $voucher->sku = $request->getBodyParam('sku');

        $voucher->customAmount = $request->getBodyParam('customAmount');

        if ($voucher->customAmount) {
            $voucher->price = 0;
        }

        if (($postDate = Craft::$app->getRequest()->getBodyParam('postDate')) !== null) {
            $voucher->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        
        if (($expiryDate = Craft::$app->getRequest()->getBodyParam('expiryDate')) !== null) {
            $voucher->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        // $voucher->promotable = (bool)$request->getBodyParam('promotable');
        $voucher->taxCategoryId = $request->getBodyParam('taxCategoryId');
        $voucher->shippingCategoryId = $request->getBodyParam('shippingCategoryId');
        $voucher->slug = $request->getBodyParam('slug');

        $voucher->enabledForSite = (bool)$request->getBodyParam('enabledForSite', $voucher->enabledForSite);
        $voucher->title = $request->getBodyParam('title', $voucher->title);

        $voucher->setFieldValuesFromRequest('fields');

        // Last checks
        if (empty($voucher->sku)) {
            $voucherType = $voucher->getType();
            $voucher->sku = Craft::$app->getView()->renderObjectTemplate($voucherType->skuFormat, $voucher);
        }

        return $voucher;
    }
}
