<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\helpers\Locale;

use Craft;
use craft\db\Query;
use craft\helpers\Json;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\db\Table;
use craft\commerce\models\LineItem;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DownloadsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionPdf(): Response|string
    {
        $codes = [];
        $order = null;
        $lineItem = null;

        $number = $this->request->getParam('number');
        $option = $this->request->getParam('option', '');
        $lineItemUid = $this->request->getParam('lineItemUid');
        $codeUid = $this->request->getParam('codeUid');

        $format = $this->request->getParam('format');
        $attach = $this->request->getParam('attach');

        if (
            ($number !== null && !is_string($number)) ||
            ($lineItemUid !== null && !is_string($lineItemUid)) ||
            ($codeUid !== null && !is_string($codeUid))
        ) {
            throw new BadRequestHttpException('Voucher PDF identifiers must be strings.');
        }

        $hasNumber = $number !== null && $number !== '';
        $hasLineItemUid = $lineItemUid !== null && $lineItemUid !== '';
        $hasCodeUid = $codeUid !== null && $codeUid !== '';

        if ($hasNumber === $hasCodeUid) {
            throw new BadRequestHttpException('Supply either an order number or a voucher code UID.');
        }

        if ($hasLineItemUid && !$hasNumber) {
            throw new BadRequestHttpException('A line item UID must be accompanied by an order number.');
        }

        $siteHandle = $this->request->getParam('site');
        $site = Craft::$app->getSites()->getPrimarySite();

        if ($siteHandle) {
            if ($requestedSite = Craft::$app->getSites()->getSiteByHandle($siteHandle)) {
                $site = $requestedSite;
            }
        }

        if ($hasNumber) {
            $order = Commerce::getInstance()->getOrders()->getOrderByNumber($number);

            if (!$order) {
                throw new NotFoundHttpException('Order not found.');
            }
        }

        if ($hasLineItemUid) {
            $lineItem = $this->_getLineItemByUid($lineItemUid);

            if (!$lineItem || $lineItem->orderId !== $order->id) {
                throw new NotFoundHttpException('Line item not found.');
            }
        }

        if ($hasCodeUid) {
            $code = Craft::$app->getElements()->getElementByUid($codeUid, Code::class);

            if (!$code || $code->uid !== $codeUid) {
                throw new NotFoundHttpException('Voucher code not found.');
            }

            $codes = [$code];
            $order = $code->getOrder();
        }

        // Switch to use the correct site/language
        $originalLanguage = Craft::$app->language;
        $originalFormattingLocale = Craft::$app->formattingLocale;

        Locale::switchAppLanguage($site->language);

        $pdf = GiftVoucher::$plugin->getPdf()->renderPdf($codes, $order, $lineItem, $option);

        // Set previous language back
        Locale::switchAppLanguage($originalLanguage, $originalFormattingLocale);

        $filenameFormat = GiftVoucher::$plugin->getSettings()->voucherCodesPdfFilenameFormat;

        $fileName = $this->getView()->renderObjectTemplate($filenameFormat, $order, [
            'codeKey' => $codes[0]->codeKey ?? null,
        ]);

        if (!$fileName) {
            if ($order) {
                $fileName = 'Voucher-' . $order->number;
            } elseif ($codes) {
                $fileName = 'Voucher-' . $codes[0]->codeKey;
            }
        }

        $options = [
            'mimeType' => 'application/pdf',
        ];

        if ($attach) {
            $options['inline'] = true;
        }

        if ($format === 'plain') {
            return $pdf;
        }

        return Craft::$app->getResponse()->sendContentAsFile($pdf, $fileName . '.pdf', $options);
    }


    // Private Methods
    // =========================================================================

    private function _getLineItemByUid(string $uid): ?LineItem
    {
        $result = $this->_createLineItemQuery()
            ->where(['uid' => $uid])
            ->one();

        if ($result) {
            // Unpack the snapshot
            $result['snapshot'] = Json::decodeIfJson($result['snapshot']);
        }

        return $result ? new LineItem($result) : null;
    }

    private function _createLineItemQuery(): Query
    {
        return (new Query())
            ->select([
                'dateCreated',
                'dateUpdated',
                'description',
                'hasFreeShipping',
                'height',
                'id',
                'isPromotable',
                'isShippable',
                'isTaxable',
                'length',
                'lineItemStatusId',
                'note',
                'options',
                'orderId',
                'price',
                'promotionalPrice',
                'privateNote',
                'purchasableId',
                'qty',
                'shippingCategoryId',
                'sku',
                'snapshot',
                'taxCategoryId',
                'type',
                'uid',
                'weight',
                'width',
            ])
            ->from([Table::LINEITEMS . ' lineItems'])
            ->orderBy('dateCreated DESC');
    }
}
