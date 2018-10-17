<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\elements\Code;

use Craft;
use craft\helpers\FileHelper;
use craft\web\View;

use craft\commerce\elements\Order;
use craft\commerce\events\PdfEvent;
use craft\commerce\Plugin as Commerce;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\Component;
use yii\base\Exception;

class PdfService extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_RENDER_PDF = 'beforeRenderPdf';
    const EVENT_AFTER_RENDER_PDF = 'afterRenderPdf';

    // Public Methods
    // =========================================================================

    public function renderPdf(Order $order, $option = '', $templatePath = null): string
    {
        if (null === $templatePath){
            $templatePath = GiftVoucher::getInstance()->getSettings()->voucherCodesPdfPath;
        }

        $codes = Code::find()
            ->orderId($order->id)
            ->all();

        // Trigger a 'beforeRenderPdf' event
        $event = new PdfEvent([
            'order' => $order,
            'option' => $option,
            'template' => $templatePath,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PDF, $event);

        if ($event->pdf !== null) {
            return $event->pdf;
        }

        // Set Craft to the site template mode
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        if (!$templatePath || !$view->doesTemplateExist($templatePath)) {
            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            throw new Exception('PDF template file does not exist.');
        }

        try {
            $html = $view->renderTemplate($templatePath, compact('order', 'codes', 'option'));
        } catch (\Exception $e) {
            // Set the pdf html to the render error.
            Craft::error('Voucher PDF render error. Order number: ' . $order->getShortNumber() . '. ' . $e->getMessage());
            Craft::$app->getErrorHandler()->logException($e);
            $html = Craft::t('gift-voucher', 'An error occurred while generating this PDF.');
        }

        // Restore the original template mode
        $view->setTemplateMode($oldTemplateMode);

        $dompdf = new Dompdf();

        // Set the config options
        $pathService = Craft::$app->getPath();
        $dompdfTempDir = $pathService->getTempPath() . DIRECTORY_SEPARATOR . 'gift_voucher_dompdf';
        $dompdfFontCache = $pathService->getCachePath() . DIRECTORY_SEPARATOR . 'gift_voucher_dompdf';
        $dompdfLogFile = $pathService->getLogPath() . DIRECTORY_SEPARATOR . 'gift_voucher_dompdf.htm';

        // Should throw an error if not writable
        FileHelper::isWritable($dompdfTempDir);
        FileHelper::isWritable($dompdfLogFile);

        $isRemoteEnabled = Commerce::getInstance()->getSettings()->pdfAllowRemoteImages;

        $options = new Options();
        $options->setTempDir($dompdfTempDir);
        $options->setFontCache($dompdfFontCache);
        $options->setLogOutputFile($dompdfLogFile);
        $options->setIsRemoteEnabled($isRemoteEnabled);

        // Paper Size and Orientation
        $pdfPaperSize = Commerce::getInstance()->getSettings()->pdfPaperSize;
        $pdfPaperOrientation = Commerce::getInstance()->getSettings()->pdfPaperOrientation;
        $options->setDefaultPaperOrientation($pdfPaperOrientation);
        $options->setDefaultPaperSize($pdfPaperSize);

        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);
        $dompdf->render();

        // Trigger an 'afterRenderPdf' event
        $event = new PdfEvent([
            'order' => $order,
            'option' => $option,
            'template' => $templatePath,
            'pdf' => $dompdf->output(),
        ]);
        $this->trigger(self::EVENT_AFTER_RENDER_PDF, $event);

        return $event->pdf;
    }
}