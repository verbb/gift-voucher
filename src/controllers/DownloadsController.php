<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\HttpException;
use yii\web\Response;

class DownloadsController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionPdf(): Response
    {
        $number = Craft::$app->getRequest()->getParam('number');
        $option = Craft::$app->getRequest()->getParam('option', '');
        $lineItemId = Craft::$app->getRequest()->getParam('lineItemId', '');
        $format = Craft::$app->getRequest()->getParam('format');
        $attach = Craft::$app->getRequest()->getParam('attach');

        $lineItem = null;
        $order = Commerce::getInstance()->getOrders()->getOrderByNumber($number);

        if (!$order) {
            throw new HttpException('No Order Found');
        }

        if ($lineItemId) {
            $lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($lineItemId);
        }

        $pdf = GiftVoucher::getInstance()->getPdf()->renderPdf($order, $lineItem, $option);
        $filenameFormat = GiftVoucher::getInstance()->getSettings()->voucherCodesPdfFilenameFormat;

        $fileName = $this->getView()->renderObjectTemplate($filenameFormat, $order);

        if (!$fileName) {
            $fileName = 'Voucher-' . $order->number;
        }

        return Craft::$app->getResponse()->sendContentAsFile($pdf, $fileName . '.pdf', [
            'mimeType' => 'application/pdf'
        ]);
    }
}