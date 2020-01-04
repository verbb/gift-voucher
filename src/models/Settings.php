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
    public $fieldsPath = 'fields';
    public $codeStorage = Session::class;

    private $fieldLayout;


    // Public Methods
    // =========================================================================

    public function getFieldLayout(): FieldLayout
    {
        if ($this->fieldLayout !== null) {
            return $this->fieldLayout;
        }

        // if no field layout ID was set yet, return an empty FieldLayout
        if ($this->fieldLayoutId === null) {
            return new FieldLayout(['type' => Code::class]);
        }

        if (($fieldLayout = Craft::$app->getFields()->getLayoutById($this->fieldLayoutId)) === null) {
            throw new InvalidConfigException('Invalid field layout ID: ' . $this->fieldLayoutId);
        }

        return $this->fieldLayout = $fieldLayout;
    }
}
