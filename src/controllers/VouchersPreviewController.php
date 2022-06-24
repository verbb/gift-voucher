<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\helpers\VoucherHelper;

use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class VouchersPreviewController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionPreviewVoucher(): Response
    {
        $this->requirePostRequest();

        $voucher = VoucherHelper::populateVoucherFromPost();

        $this->enforceVoucherPermissions($voucher);

        return $this->_showVoucher($voucher);
    }

    public function actionShareVoucher($voucherId, $siteId): Response
    {
        $voucher = GiftVoucher::$plugin->getVouchers()->getVoucherById($voucherId, $siteId);

        if (!$voucher) {
            throw new HttpException(404);
        }

        $this->enforceVoucherPermissions($voucher);

        // Make sure the voucher actually can be viewed
        if (!GiftVoucher::$plugin->getVoucherTypes()->isVoucherTypeTemplateValid($voucher->getType(), $voucher->siteId)) {
            throw new HttpException(404);
        }

        // Create the token and redirect to the voucher URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'gift-voucher/vouchers-preview/view-shared-voucher', ['voucherId' => $voucher->id, 'siteId' => $siteId],
        ]);

        $url = UrlHelper::urlWithToken($voucher->getUrl(), $token);

        return $this->redirect($url);
    }

    public function actionViewSharedVoucher($voucherId, $site = null)
    {
        $this->requireToken();

        $voucher = GiftVoucher::$plugin->getVouchers()->getVoucherById($voucherId, $site);

        if (!$voucher) {
            throw new HttpException(404);
        }

        $this->_showVoucher($voucher);

        return null;
    }

    public function actionSaveVoucher()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $voucher = VoucherHelper::populateVoucherFromPost();

        $this->enforceVoucherPermissions($voucher);

        // Save the entry (finally!)
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
                'voucher' => $voucher,
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
                'cpEditUrl' => $voucher->getCpEditUrl(),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('gift-voucher', 'Voucher saved.'));

        return $this->redirectToPostedUrl($voucher);
    }

    protected function enforceVoucherPermissions(Voucher $voucher)
    {
        $this->requirePermission('giftVoucher-manageVoucherType:' . $voucher->getType()->uid);
    }

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
        if ($voucher->id) {
            Craft::$app->getElements()->setPlaceholderElement($voucher);
        }

        $this->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($siteSettings[$voucher->siteId]->template, [
            'voucher' => $voucher,
        ]);
    }

}
