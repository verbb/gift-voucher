<?php
namespace verbb\giftvoucher\models;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public $expiry = 0;
    public $codeKeyLength = 10;
    public $codeKeyCharacters = 'ACDEFGHJKLMNPQRTUVWXYZ234679';
    public $voucherCodesPdfPath = 'shop/_pdf/voucher';
    public $voucherCodesPdfFilenameFormat = 'Voucher-{number}';

    public $pdfAllowRemoteImages = false;
    public $pdfPaperSize = 'letter';
    public $pdfPaperOrientation = 'portrait';

}
