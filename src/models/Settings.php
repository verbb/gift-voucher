<?php

namespace verbb\giftvoucher\models;

use Craft;
use craft\base\Model;
use craft\models\FieldLayout;
use verbb\giftvoucher\elements\Code;
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
    /**
     * The field layout id used for Codes
     *
     * @var int|null $fieldLayoutId
     */
    public $fieldLayoutId;
    /**
     * The field layout of a Code
     *
     * @var \craft\models\FieldLayout|null
     */
    private $fieldLayout;
    /**
     * The path of the custom fields, default 'fields'
     *
     * @var string|null $fieldsPath
     */
    public $fieldsPath = 'fields';

    /**
     * Returns the owner's field layout.
     *
     * @return \craft\models\FieldLayout
     * @throws \yii\base\InvalidConfigException if the configured field layout ID is invalid
     */
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
