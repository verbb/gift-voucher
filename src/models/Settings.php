<?php
namespace verbb\giftvoucher\models;

use verbb\giftvoucher\storage\Session;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public int $expiry = 0;
    public int $codeKeyLength = 10;
    public string $codeKeyCharacters = 'ACDEFGHJKLMNPQRTUVWXYZ234679';
    public string $voucherCodesPdfPath = 'shop/_pdf/voucher';
    public string $voucherCodesPdfFilenameFormat = 'Voucher-{number}';
    public bool $stopProcessing = true;
    public bool $pdfAllowRemoteImages = false;
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';
    public mixed $codeStorage = Session::class;
    public string $registerAdjuster = 'beforeTax';
    public array $attachPdfToEmails = [];

    // TODO: Remove at next breakpoint
    private mixed $fieldLayout = null;
    public mixed $fieldLayoutId = null;
    public string $fieldsPath = 'fields';
}
