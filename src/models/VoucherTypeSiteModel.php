<?php
namespace verbb\giftvoucher\models;

use verbb\giftvoucher\GiftVoucher;

use Craft;
use craft\base\Model;
use craft\models\Site;

use yii\base\InvalidConfigException;

class VoucherTypeSiteModel extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $voucherTypeId = null;
    public ?int $siteId = null;
    public ?bool $hasUrls = null;
    public ?string $uriFormat = null;
    public ?string $template = null;
    public bool $uriFormatIsRequired = true;

    private ?VoucherTypeModel $_voucherType = null;
    private ?Site $_site = null;


    // Public Methods
    // =========================================================================

    public function getVoucherType(): VoucherTypeModel
    {
        if ($this->_voucherType !== null) {
            return $this->_voucherType;
        }

        if (!$this->voucherTypeId) {
            throw new InvalidConfigException('Site is missing its voucher type ID');
        }

        if (($this->_voucherType = GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeById($this->voucherTypeId)) === null) {
            throw new InvalidConfigException('Invalid voucher type ID: ' . $this->voucherTypeId);
        }

        return $this->_voucherType;
    }

    public function setVoucherType(VoucherTypeModel $voucherType): void
    {
        $this->_voucherType = $voucherType;
    }

    public function getSite(): Site
    {
        if (!$this->_site) {
            $this->_site = Craft::$app->getSites()->getSiteById($this->siteId);
        }

        return $this->_site;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        if ($this->uriFormatIsRequired) {
            $rules[] = ['uriFormat', 'required'];
        }

        return $rules;
    }
}
