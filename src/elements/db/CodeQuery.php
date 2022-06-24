<?php
namespace verbb\giftvoucher\elements\db;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\models\VoucherTypeModel;

use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use DateTime;
use yii\db\Connection;

class CodeQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $voucherId;
    public $typeId;
    public $lineItemId;
    public $orderId;
    public $codeKey;
    public $originalAmount;
    public $currentAmount;
    public $expiryDate;

    protected $defaultOrderBy = ['giftvoucher_codes.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    public function __construct(string $elementType, array $config = [])
    {
        // Default status
        if (!isset($config['status'])) {
            $config['status'] = Element::STATUS_ENABLED;
        }

        parent::__construct($elementType, $config);
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'voucher':
                $this->voucher($value);
                break;
            case 'type':
                $this->type($value);
                break;
            case 'before':
                $this->before($value);
                break;
            case 'after':
                $this->after($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    public function voucher($value)
    {
        if ($value instanceof Voucher) {
            $this->voucherId = $value->id;
        } else if ($value !== null) {
            $this->voucherId = (new Query())
                ->select(['id'])
                ->from(['{{%giftvoucher_vouchers}}'])
                ->where(Db::parseParam('sku', $value))
                ->column();
        } else {
            $this->voucherId = null;
        }

        return $this;
    }

    public function type($value)
    {
        if ($value instanceof VoucherTypeModel) {
            $this->typeId = $value->id;
        } else if ($value !== null) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(['{{%giftvoucher_vouchertypes}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->typeId = null;
        }

        return $this;
    }

    public function before($value)
    {
        if ($value instanceof DateTime) {
            $value = $value->format(DateTime::W3C);
        }

        $this->dateCreated = ArrayHelper::toArray($this->dateCreated);
        $this->dateCreated[] = '<' . $value;

        return $this;
    }

    public function after($value)
    {
        if ($value instanceof DateTime) {
            $value = $value->format(DateTime::W3C);
        }

        $this->dateCreated = ArrayHelper::toArray($this->dateCreated);
        $this->dateCreated[] = '>=' . $value;

        return $this;
    }

    public function typeId($value)
    {
        $this->typeId = $value;

        return $this;
    }

    public function voucherId($value)
    {
        $this->voucherId = $value;

        return $this;
    }

    public function lineItemId($value)
    {
        $this->lineItemId = $value;

        return $this;
    }

    public function orderId($value)
    {
        $this->orderId = $value;

        return $this;
    }

    public function codeKey($value)
    {
        $this->codeKey = $value;

        return $this;
    }

    public function expiryDate($value)
    {
        $this->expiryDate = $value;

        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        // See if 'type' were set to invalid handles
        if ($this->typeId === []) {
            return false;
        }

        $this->joinElementTable('giftvoucher_codes');
        $this->subQuery->innerJoin('{{%giftvoucher_vouchers}} giftvoucher_vouchers', '[[giftvoucher_codes.voucherId]] = [[giftvoucher_vouchers.id]]');

        $this->query->select([
            'giftvoucher_codes.id',
            'giftvoucher_codes.voucherId',
            'giftvoucher_codes.codeKey',
            'giftvoucher_codes.lineItemId',
            'giftvoucher_codes.orderId',
            'giftvoucher_codes.originalAmount',
            'giftvoucher_codes.currentAmount',
            'giftvoucher_codes.expiryDate',
        ]);

        if ($this->voucherId) {
            $this->subQuery->andWhere(Db::parseParam('giftvoucher_vouchers.id', $this->voucherId));
        }

        if ($this->typeId) {
            $this->subQuery->andWhere(Db::parseParam('giftvoucher_vouchers.typeId', $this->typeId));
        }

        if ($this->lineItemId) {
            $this->subQuery->andWhere(Db::parseParam('giftvoucher_codes.lineItemId', $this->lineItemId));
        }

        if ($this->orderId) {
            $this->subQuery->andWhere(Db::parseParam('giftvoucher_codes.orderId', $this->orderId));
        }

        if ($this->codeKey) {
            // Prevent fuzzy-matching. The codeKey must match exactly
            $this->subQuery->andWhere(['giftvoucher_codes.codeKey' => $this->codeKey]);
        }

        if ($this->expiryDate) {
            $this->subQuery->andWhere(Db::parseDateParam('giftvoucher_codes.expiryDate', $this->expiryDate));
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status)
    {
        switch ($status) {
            case Code::STATUS_ENABLED:
                return [
                    'elements.enabled' => true,
                ];
            case Code::STATUS_DISABLED:
                return [
                    'elements.disabled' => true,
                ];
            default:
                return parent::statusCondition($status);
        }
    }
}
