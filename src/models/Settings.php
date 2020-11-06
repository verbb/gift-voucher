<?php
namespace verbb\giftvoucher\models;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\storage\Session;

use Craft;
use craft\base\Model;
use craft\models\FieldLayout;

use yii\base\InvalidConfigException;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public $expiry = 0;
    public $codeKeyLength = 10;
    public $codeKeyCharacters = 'ACDEFGHJKLMNPQRTUVWXYZ234679';
    public $voucherCodesPdfPath = 'shop/_pdf/voucher';
    public $voucherCodesPdfFilenameFormat = 'Voucher-{number}';
    public $stopProcessing = true;
    public $pdfAllowRemoteImages = false;
    public $pdfPaperSize = 'letter';
    public $pdfPaperOrientation = 'portrait';
    public $fieldLayoutId;
    public $codeStorage = Session::class;
    public $registerAdjuster = 'beforeTax';
    public $attachPdfToEmails = [];

    // TODO: Remove at next breakpoint
    private $fieldLayout;
    public $fieldsPath = 'fields';
}
