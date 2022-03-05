<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\events\PdfEvent;
use verbb\giftvoucher\events\PdfRenderOptionsEvent;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\web\View;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;

class PdfService extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_RENDER_PDF = 'beforeRenderPdf';
    const EVENT_AFTER_RENDER_PDF = 'afterRenderPdf';
    const EVENT_MODIFY_RENDER_OPTIONS = 'modifyRenderOptions';


    // Public Methods
    // =========================================================================

    public function getPdfUrl(Order $order, LineItem $lineItem = null, $option = null): string
    {
        return UrlHelper::actionUrl('gift-voucher/downloads/pdf', array_filter([
            'number' => $order->number ?? null,
            'option' => $option ?? null,
            'lineItemId' => $lineItem->id ?? null,
        ]));
    }

    public function getPdfUrlForCode($code, $option = null): string
    {
        return UrlHelper::actionUrl('gift-voucher/downloads/pdf', array_filter([
            'codeId' => $code->id ?? null,
            'option' => $option ?? null,
        ]));
    }

    public function renderPdf($codes, Order $order = null, $lineItem = null, $option = '', $templatePath = null): string
    {
        $settings = GiftVoucher::$plugin->getSettings();
        $format = null;

        $request = Craft::$app->getRequest();

        if (!$request->getIsConsoleRequest()) {
            $format = $request->getParam('format');
        }

        if (!$templatePath) {
            $templatePath = $settings->voucherCodesPdfPath;
        }

        if (!$codes && $order) {
            $codesQuery = Code::find()
                ->orderId($order->id);

            if ($lineItem) {
                $codesQuery->lineItemId = $lineItem->id;
            }

            $codes = $codesQuery->all();
        }

        $variables = compact('order', 'codes', 'lineItem', 'option');

        // Trigger a 'beforeRenderPdf' event
        $event = new PdfEvent([
            'order' => $order,
            'option' => $option,
            'template' => $templatePath,
            'variables' => $variables,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PDF, $event);

        if ($event->pdf !== null) {
            return $event->pdf;
        }

        $variables = $event->variables;
        $variables['order'] = $event->order;
        $variables['option'] = $event->option;

        // Set Craft to the site template mode
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        if (!$event->template || !$view->doesTemplateExist($event->template)) {
            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            throw new Exception('PDF template file does not exist.');
        }

        try {
            $html = $view->renderTemplate($templatePath, $variables);
        } catch (\Exception $e) {
            GiftVoucher::error('An error occurred while generating this PDF: ' . $e->getMessage());

            if ($order) {
                GiftVoucher::error('Voucher PDF render error. Order number: ' . $order->getShortNumber() . '. ' . $e->getMessage());
            }

            if ($codes) {
                GiftVoucher::error('Voucher PDF render error. Code key: ' . $codes[0]->codeKey . '. ' . $e->getMessage());
            }

            // Set the pdf html to the render error.
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

        // Ensure directories are created
        FileHelper::createDirectory($dompdfTempDir);
        FileHelper::createDirectory($dompdfFontCache);

        if (!FileHelper::isWritable($dompdfLogFile)) {
            throw new ErrorException("Unable to write to file: $dompdfLogFile");
        }

        if (!FileHelper::isWritable($dompdfFontCache)) {
            throw new ErrorException("Unable to write to folder: $dompdfFontCache");
        }

        if (!FileHelper::isWritable($dompdfTempDir)) {
            throw new ErrorException("Unable to write to folder: $dompdfTempDir");
        }

        $isRemoteEnabled = $settings->pdfAllowRemoteImages;

        $options = new Options();
        $options->setTempDir($dompdfTempDir);
        $options->setFontCache($dompdfFontCache);
        $options->setLogOutputFile($dompdfLogFile);
        $options->setIsRemoteEnabled($isRemoteEnabled);

        // Set additional render options
        if ($this->hasEventHandlers(self::EVENT_MODIFY_RENDER_OPTIONS)) {
            $this->trigger(self::EVENT_MODIFY_RENDER_OPTIONS, new PdfRenderOptionsEvent([
                'options' => $options,
            ]));
        }

        // Set the options
        $dompdf->setOptions($options);

        // Paper Size and Orientation
        $pdfPaperSize = $settings->pdfPaperSize;
        $pdfPaperOrientation = $settings->pdfPaperOrientation;
        $dompdf->setPaper($pdfPaperSize, $pdfPaperOrientation);

        $dompdf->loadHtml($html);

        if ($format === 'plain') {
            return $html;
        }

        $dompdf->render();

        // Trigger an 'afterRenderPdf' event
        $afterEvent = new PdfEvent([
            'order' => $event->order,
            'option' => $event->option,
            'template' => $event->template,
            'variables' => $variables,
            'pdf' => $dompdf->output(),
        ]);
        $this->trigger(self::EVENT_AFTER_RENDER_PDF, $afterEvent);

        return $afterEvent->pdf;
    }
}