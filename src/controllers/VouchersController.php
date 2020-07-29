<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidElementException;
use craft\errors\MissingComponentException;
use craft\errors\SiteNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Localization;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class VouchersController extends Controller
{
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
        $variables = compact('voucherTypeHandle', 'voucherId', 'voucher');

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        $this->_prepEditVoucherVariables($variables);

        $voucher = $variables['voucher'];

        if ($voucher->id === null) {
            $variables['title'] = Craft::t('gift-voucher', 'Create a new voucher');
        } else {
            $variables['title'] = $voucher->title;
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'gift-voucher/vouchers/' . $variables['voucherTypeHandle'] . '/{id}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] . (Craft::$app->getIsMultiSite() && Craft::$app->getSites()->currentSite->id !== $variables['site']->id ? '/' . $variables['site']->handle : '');

        $this->_prepVariables($variables);

        // Enable Live Preview?
        if (!Craft::$app->getRequest()->isMobileBrowser(true) && GiftVoucher::$plugin->getVoucherTypes()->isVoucherTypeTemplateValid($variables['voucherType'], $variables['site']->id)) {
            $this->getView()->registerJs('Craft.LivePreview.init(' . Json::encode([
                    'fields' => '#title-field, #fields > div > div > .field',
                    'extraFields' => '#details',
                    'previewUrl' => $voucher->getUrl(),
                    'previewAction' => Craft::$app->getSecurity()->hashData('gift-voucher/vouchers-preview/preview-voucher'),
                    'previewParams' => [
                        'typeId' => $variables['voucherType']->id,
                        'voucherId' => $voucher->id,
                        'siteId' => $voucher->siteId,
                    ]
                ]) . ');');

            $variables['showPreviewBtn'] = true;

            // Should we show the Share button too?
            if ($voucher->id !== null) {
                // If the voucher is enabled, use its main URL as its share URL.
                if ($voucher->getStatus() == Voucher::STATUS_LIVE) {
                    $variables['shareUrl'] = $voucher->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::actionUrl('gift-voucher/vouchers-preview/share-voucher', [
                        'voucherId' => $voucher->id,
                        'siteId' => $voucher->siteId
                    ]);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }

        return $this->renderTemplate('gift-voucher/vouchers/_edit', $variables);
    }

    public function actionDeleteVoucher()
    {
        $this->requirePostRequest();

        $voucherId = Craft::$app->getRequest()->getRequiredParam('voucherId');
        $voucher = GiftVoucher::$plugin->getVouchers()->getVoucherById($voucherId);

        if (!$voucher) {
            throw new Exception(Craft::t('gift-voucher', 'No voucher exists with the ID “{id}”.',['id' => $voucherId]));
        }

        $this->enforceVoucherPermissions($voucher);

        if (!Craft::$app->getElements()->deleteElement($voucher)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t delete voucher.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'voucher' => $voucher
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
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

    public function actionDuplicate()
    {
        return $this->runAction('save', ['duplicate' => true]);
    }


    // Protected Methods
    // =========================================================================

    protected function enforceVoucherPermissions(Voucher $voucher)
    {
        $this->requirePermission('giftVoucher-manageVoucherType:' . $voucher->getType()->uid);
    }


    // Private Methods
    // =========================================================================

    private function _prepVariables(array &$variables)
    {
        $variables['tabs'] = [];

        $voucherType = $variables['voucherType'];
        $voucher = $variables['voucher'];

        foreach ($voucherType->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($voucher->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($hasErrors = $voucher->hasErrors($field->handle . '.*')) {
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
    }

    private function _prepEditVoucherVariables(array &$variables)
    {
        if (!empty($variables['voucherTypeHandle'])) {
            $variables['voucherType'] = GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeByHandle($variables['voucherTypeHandle']);
        } else if (!empty($variables['voucherTypeId'])) {
            $variables['voucherType'] = GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeById($variables['voucherTypeId']);
        }

        if (empty($variables['voucherType'])) {
            throw new NotFoundHttpException('Voucher Type not found');
        }

        // Get the site
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites supported by this voucher type');
        }

        if (empty($variables['site'])) {
            $variables['site'] = Craft::$app->getSites()->currentSite;

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /** @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        if (!empty($variables['voucherTypeHandle'])) {
            $variables['voucherType'] = GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeByHandle($variables['voucherTypeHandle']);
        }

        if (empty($variables['voucherType'])) {
            throw new HttpException(400, Craft::t('gift-voucher', 'Wrong voucher type specified'));
        }

        // Get the voucher
        // ---------------------------------------------------------------------

        if (empty($variables['voucher'])) {
            if (!empty($variables['voucherId'])) {
                $variables['voucher'] = GiftVoucher::$plugin->getVouchers()->getVoucherById($variables['voucherId'], $variables['site']->id);

                if (!$variables['voucher']) {
                    throw new NotFoundHttpException('Voucher not found');
                }
            } else {
                $variables['voucher'] = new Voucher();
                $variables['voucher']->typeId = $variables['voucherType']->id;
                
                $taxCategories = $variables['voucherType']->getTaxCategories();
                $variables['voucher']->taxCategoryId = key($taxCategories);
                
                $shippingCategories = $variables['voucherType']->getShippingCategories();
                $variables['voucher']->shippingCategoryId = key($shippingCategories);

                $variables['voucher']->typeId = $variables['voucherType']->id;
                $variables['voucher']->enabled = true;
                $variables['voucher']->siteId = $site->id;
            }
        }

        if ($variables['voucher']->id) {
            $this->enforceVoucherPermissions($variables['voucher']);

            $variables['enabledSiteIds'] = Craft::$app->getElements()->getEnabledSiteIdsForElement($variables['voucher']->id);
        } else {
            $variables['enabledSiteIds'] = [];

            foreach (Craft::$app->getSites()->getEditableSiteIds() as $site) {
                $variables['enabledSiteIds'][] = $site;
            }
        }
    }
}
