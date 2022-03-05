<?php
namespace verbb\giftvoucher\elements\db;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\models\VoucherTypeModel;

use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use DateTime;

class CodeQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $voucherId = null;
    public mixed $typeId = null;
    public mixed $lineItemId = null;
    public mixed $orderId = null;
    public mixed $codeKey = null;
    public mixed $originalAmount = null;
    public mixed $currentAmount = null;
    public mixed $expiryDate = null;


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

    public function voucher($value): static {
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

    public function type($value): static
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

    public function before($value): static
    {
        if ($value instanceof DateTime) {
            $value = $value->format(DateTime::W3C);
        }

        $this->dateCreated = ArrayHelper::toArray($this->dateCreated);
        $this->dateCreated[] = '<'.$value;

        return $this;
    }

    public function after($value): static
    {
        if ($value instanceof DateTime) {
            $value = $value->format(DateTime::W3C);
        }

        $this->dateCreated = ArrayHelper::toArray($this->dateCreated);
        $this->dateCreated[] = '>='.$value;

        return $this;
    }

    public function typeId($value): static
    {
        $this->typeId = $value;

        return $this;
    }

    public function voucherId($value): static
    {
        $this->voucherId = $value;

        return $this;
    }

    public function lineItemId($value): static
    {
        $this->lineItemId = $value;

        return $this;
    }

    public function orderId($value): static
    {
        $this->orderId = $value;

        return $this;
    }

    public function codeKey($value): static
    {
        $this->codeKey = $value;

        return $this;
    }

    public function expiryDate($value): static
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

        if (!$this->orderBy) {
            $this->orderBy = ['giftvoucher_codes.dateCreated' => SORT_DESC];
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            Code::STATUS_ENABLED => [
                'elements.enabled' => true
            ],
            Code::STATUS_DISABLED => [
                'elements.disabled' => true
            ],
            default => parent::statusCondition($status),
        };
    }
}
