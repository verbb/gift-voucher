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

    public function actionPdf()
    {
        $request = Craft::$app->getRequest();

        $codes = [];
        $order = [];
        $lineItem = null;

        $number = $request->getParam('number');
        $option = $request->getParam('option', '');
        $lineItemId = $request->getParam('lineItemId', '');
        $codeId = $request->getParam('codeId', '');

        $format = $request->getParam('format');
        $attach = $request->getParam('attach');

        if ($number) {
            $order = Commerce::getInstance()->getOrders()->getOrderByNumber($number);

            if (!$order) {
                throw new HttpException('No Order Found');
            }
        }

        if ($lineItemId) {
            $lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($lineItemId);
        }

        if ($codeId) {
            $codes = [Craft::$app->getElements()->getElementById($codeId)];
        }

        $pdf = GiftVoucher::getInstance()->getPdf()->renderPdf($codes, $order, $lineItem, $option);
        $filenameFormat = GiftVoucher::getInstance()->getSettings()->voucherCodesPdfFilenameFormat;

        $fileName = $this->getView()->renderObjectTemplate($filenameFormat, $order);

        if (!$fileName) {
            if ($order) {
                $fileName = 'Voucher-' . $order->number;
            } else if ($codes) {
                $fileName = 'Voucher-' . $code[0]->codeKey;
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
        } else {
            return Craft::$app->getResponse()->sendContentAsFile($pdf, $fileName . '.pdf', $options);
        }
    }
}