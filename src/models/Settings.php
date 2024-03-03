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


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Config normalization
        if (array_key_exists('fieldLayout', $config)) {
            unset($config['fieldLayout']);
        }

        if (array_key_exists('fieldLayoutId', $config)) {
            unset($config['fieldLayoutId']);
        }

        if (array_key_exists('fieldsPath', $config)) {
            unset($config['fieldsPath']);
        }

        parent::__construct($config);
    }
}
