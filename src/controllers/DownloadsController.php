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
        $number = Craft::$app->getRequest()->getQueryParam('number');
        $option = Craft::$app->getRequest()->getQueryParam('option', '');
        $order = Commerce::getInstance()->getOrders()->getOrderByNumber($number);

        $format = Craft::$app->getRequest()->getParam('format');
        $attach = Craft::$app->getRequest()->getParam('attach');

        if (!$order) {
            throw new HttpException('No Order Found');
        }

        $pdf = GiftVoucher::getInstance()->getPdf()->renderPdf($order, $option);
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