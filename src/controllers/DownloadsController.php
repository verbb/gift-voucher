<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\helpers\Locale;

use Craft;
use craft\db\Query;
use craft\helpers\Json;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\db\Table;
use craft\commerce\models\LineItem;

use yii\web\HttpException;
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
        $request = Craft::$app->getRequest();

        $codes = [];
        $order = [];
        $lineItem = null;

        $number = $request->getParam('number');
        $option = $request->getParam('option', '');
        $lineItemUid = $request->getParam('lineItemUid', '');
        $codeUid = $request->getParam('codeUid', '');

        $format = $request->getParam('format');
        $attach = $request->getParam('attach');

        $siteHandle = $request->getParam('site');
        $site = Craft::$app->getSites()->getPrimarySite();

        if ($siteHandle) {
            if ($requestedSite = Craft::$app->getSites()->getSiteByHandle($siteHandle)) {
                $site = $requestedSite;
            }
        }

        if ($number) {
            $order = Commerce::getInstance()->getOrders()->getOrderByNumber($number);

            if (!$order) {
                throw new HttpException('No Order Found');
            }
        }

        if ($lineItemUid) {
            $lineItem = $this->_getLineItemByUid($lineItemUid);
        }

        if ($codeUid) {
            $codes = [Craft::$app->getElements()->getElementByUid($codeUid)];
            $order = $codes[0]->order ?? null;
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
            } else if ($codes) {
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
                'height',
                'id',
                'length',
                'lineItemStatusId',
                'note',
                'options',
                'orderId',
                'price',
                'privateNote',
                'purchasableId',
                'qty',
                'salePrice',
                'shippingCategoryId',
                'sku',
                'snapshot',
                'taxCategoryId',
                'uid',
                'weight',
                'width',
            ])
            ->from([Table::LINEITEMS . ' lineItems'])
            ->orderBy('dateCreated DESC');
    }
}
