<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\base\ElementInterface;
use craft\events\SiteEvent;
use craft\helpers\Assets;
use craft\helpers\Queue;
use craft\queue\jobs\ResaveElements;

use craft\commerce\events\MailEvent;

use yii\base\Component;

use Throwable;

class Vouchers extends Component
{
    // Properties
    // =========================================================================

    private array $_pdfPaths = [];


    // Public Methods
    // =========================================================================

    public function getVoucherById(int $id, $siteId = null): ?Voucher
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, Voucher::class, $siteId);
    }

    public function afterSaveSiteHandler(SiteEvent $event): void
    {
        if ($event->isNew) {
            $oldPrimarySiteId = $event->oldPrimarySiteId;

            $elementTypes = [
                Voucher::class,
            ];

            foreach ($elementTypes as $elementType) {
                Queue::push(new ResaveElements([
                    'elementType' => $elementType,
                    'criteria' => [
                        'siteId' => $oldPrimarySiteId,
                        'status' => null,
                    ],
                ]));
            }
        }
    }

    public function onBeforeSendEmail(MailEvent $event): void
    {
        $order = $event->order;
        $commerceEmail = $event->commerceEmail;

        $settings = GiftVoucher::$plugin->getSettings();

        try {
            // Don't proceed further if there's no voucher in this order
            $hasVoucher = false;

            foreach ($order->lineItems as $lineItem) {
                if (is_a($lineItem->purchasable, Voucher::class)) {
                    $hasVoucher = true;

                    break;
                }
            }

            // No voucher in the order?
            if (!$hasVoucher) {
                return;
            }

            // Check this is an email we want to attach the voucher PDF to
            $matchedEmail = $settings->attachPdfToEmails[$commerceEmail->uid] ?? null;

            if (!$matchedEmail) {
                return;
            }

            // Generate the PDF for the order
            $pdf = GiftVoucher::$plugin->getPdf()->renderPdf([], $order, null, null);

            if (!$pdf) {
                return;
            }

            // Save it in a temp location, so we can attach it
            $pdfPath = Assets::tempFilePath('pdf');
            file_put_contents($pdfPath, $pdf);

            // Generate the filename correctly.
            $filenameFormat = $settings->voucherCodesPdfFilenameFormat;
            $fileName = Craft::$app->getView()->renderObjectTemplate($filenameFormat, $order);

            if (!$fileName) {
                if ($order) {
                    $fileName = 'Voucher-' . $order->number;
                } else {
                    $fileName = 'Voucher';
                }
            }

            if (!$pdfPath) {
                return;
            }

            $craftEmail = $event->craftEmail;
            $event->craftEmail->attach($pdfPath, ['fileName' => $fileName . '.pdf', 'contentType' => 'application/pdf']);

            // Store for later
            $this->_pdfPaths[] = $pdfPath;
        } catch (Throwable $e) {
            $error = Craft::t('gift-voucher', 'PDF unable to be attached to “{email}” for order “{order}”. Error: {error} {file}:{line}', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $commerceEmail->name,
                'order' => $order->getShortNumber(),
            ]);

            GiftVoucher::error($error);
        }
    }

    public function onAfterSendEmail(MailEvent $event): void
    {
        // Clear out any generated PDFs
        foreach ($this->_pdfPaths as $pdfPath) {
            unlink($pdfPath);
        }
    }

}
