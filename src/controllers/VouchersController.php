<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\helpers\VoucherHelper;

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
use yii\base\Model;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

use craft\commerce\elements\Order;

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
                    ],
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
                        'siteId' => $voucher->siteId,
                    ]);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }

        return $this->renderTemplate('gift-voucher/vouchers/_edit', $variables);
    }

    public function actionDelete()
    {
        $this->requirePostRequest();

        $voucherId = Craft::$app->getRequest()->getRequiredParam('voucherId');
        $voucher = GiftVoucher::$plugin->getVouchers()->getVoucherById($voucherId);

        if (!$voucher) {
            throw new Exception(Craft::t('gift-voucher', 'No voucher exists with the ID “{id}”.', ['id' => $voucherId]));
        }

        $this->enforceVoucherPermissions($voucher);

        if (!Craft::$app->getElements()->deleteElement($voucher)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t delete voucher.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'voucher' => $voucher,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('gift-voucher', 'Voucher deleted.'));

        return $this->redirectToPostedUrl($voucher);
    }

    public function actionSave(bool $duplicate = false)
    {
        $this->requirePostRequest();

        // Get the requested voucher
        $request = Craft::$app->getRequest();
        $oldVoucher = VoucherHelper::voucherFromPost($request);
        $this->enforceVoucherPermissions($oldVoucher);
        $elementsService = Craft::$app->getElements();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // If we're duplicating the voucher, swap $voucher with the duplicate
            if ($duplicate) {
                try {
                    $voucher = $elementsService->duplicateElement($oldVoucher);
                } catch (InvalidElementException $e) {
                    $transaction->rollBack();

                    /** @var Voucher $clone */
                    $clone = $e->element;

                    if ($request->getAcceptsJson()) {
                        return $this->asJson([
                            'success' => false,
                            'errors' => $clone->getErrors(),
                        ]);
                    }

                    Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t duplicate voucher.'));

                    // Send the original voucher back to the template, with any validation errors on the clone
                    $oldVoucher->addErrors($clone->getErrors());

                    Craft::$app->getUrlManager()->setRouteParams([
                        'voucher' => $oldVoucher,
                    ]);

                    return null;
                } catch (\Throwable $e) {
                    throw new ServerErrorHttpException(Craft::t('gift-voucher', 'An error occurred when duplicating the voucher.'), 0, $e);
                }
            } else {
                $voucher = $oldVoucher;
            }

            // Now populate the rest of it from the post data
            VoucherHelper::populateVoucherFromPost($voucher, $request);

            // Save the voucher (finally!)
            if ($voucher->enabled && $voucher->enabledForSite) {
                $voucher->setScenario(Element::SCENARIO_LIVE);
            }

            $success = $elementsService->saveElement($voucher);

            if (!$success && $duplicate && $voucher->getScenario() === Element::SCENARIO_LIVE) {
                // Try again with the voucher disabled
                $voucher->enabled = false;
                $voucher->setScenario(Model::SCENARIO_DEFAULT);
                $success = $elementsService->saveElement($voucher);
            }

            if (!$success) {
                $transaction->rollBack();

                if ($request->getAcceptsJson()) {
                    return $this->asJson([
                        'success' => false,
                        'errors' => $voucher->getErrors(),
                    ]);
                }

                Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t save voucher.'));

                if ($duplicate) {
                    // Add validation errors on the original voucher
                    $oldVoucher->addErrors($voucher->getErrors());
                }

                Craft::$app->getUrlManager()->setRouteParams([
                    'voucher' => $oldVoucher,
                ]);

                return null;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
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

    public function actionDuplicate()
    {
        return $this->runAction('save', ['duplicate' => true]);
    }

    public function actionGetModalBody()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $orderId = $this->request->getParam('orderId');
        $order = Order::find()->id($orderId)->one();

        $codes = GiftVoucher::$plugin->getCodeStorage()->getCodeKeys($order);

        $variables = [
            'order' => $order,
            'codes' => $codes,
        ];

        $html = $this->getView()->renderTemplate('gift-voucher/vouchers/_modal', $variables);

        return $this->asJson([
            'success' => true,
            'html' => $html,
        ]);
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

        $form = $voucherType->getVoucherFieldLayout()->createForm($voucher);
        $variables['tabs'] = $form->getTabMenu();
        $variables['fieldsHtml'] = $form->render();
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
