<?php
namespace verbb\giftvoucher\storage;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\helpers\CodeHelper;

use Craft;
use craft\base\Component;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;

use craft\commerce\elements\Order as OrderElement;

use yii\base\Exception;

use Throwable;

class Order extends Component implements CodeStorageInterface
{
    // Properties
    // =========================================================================

    public ?string $fieldHandle = null;


    // Public Methods
    // =========================================================================
    /**
     * Add a code
     *
     * @param                                $code
     *
     * @param OrderElement $order
     * @return bool
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     * @throws InvalidFieldException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function add($code, OrderElement $order): bool
    {
        $code = CodeHelper::getCode($code);

        if ($code !== null && $this->fieldHandle !== null) {
            $codes = $order->getFieldValue($this->fieldHandle)->ids();
            $codes[] = (int)$code->id;
            $codes = array_unique($codes);

            $order->setFieldValue($this->fieldHandle, $codes);

            return Craft::$app->getElements()->saveElement($order);
        }

        return false;
    }

    /**
     * remove a code
     *
     * @param                                $code
     *
     * @param OrderElement $order
     * @return bool
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     * @throws InvalidFieldException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function remove($code, OrderElement $order): bool
    {
        $code = CodeHelper::getCode($code);

        if ($code !== null && $this->fieldHandle !== null) {
            $codes = $order->getFieldValue($this->fieldHandle)->ids();

            // can't use ArrayHelper due to "id" vs (int)$id ¯\_(ツ)_/¯
            $codeId = (int)$code->id;

            foreach ($codes as $key => $c) {
                if ((int)$c === $codeId) {
                    unset($codes[$key]);
                }
            }

            // remove duplicates if there are any
            $codes = array_unique($codes);
            $order->setFieldValue($this->fieldHandle, $codes);

            return Craft::$app->getElements()->saveElement($order);
        }

        return false;
    }

    /**
     * Get all stored codes
     *
     *
     * @return Code[]
     *
     * @throws InvalidFieldException
     * @throws InvalidFieldException
     * @since  2.0.16
     * @author Robin Schambach
     */
    public function getCodes(OrderElement $order): array
    {
        if ($this->fieldHandle !== null) {
            return $order->getFieldValue($this->fieldHandle)->all();
        }

        return [];
    }

    /**
     * Get all stored code keys
     *
     *
     * @return string[]
     *
     * @throws InvalidFieldException
     * @throws InvalidFieldException
     * @since  2.0.16
     * @author Robin Schambach
     */
    public function getCodeKeys(OrderElement $order): array
    {
        $codes = $this->getCodes($order);
        $codeKeys = [];

        foreach ($codes as $code) {
            $codeKeys[] = $code->codeKey;
        }

        return $codeKeys;
    }

    /**
     * Set Codes
     *
     * @param Code[]|int[]|string[] $codes
     *
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function setCodes(array $codes, OrderElement $order): bool
    {
        if ($this->fieldHandle === null) {
            return false;
        }

        $codeIds = [];
        foreach ($codes as $code){
            $code = CodeHelper::getCode($code);

            if ($code !== null) {
                $codeIds[] = (int)$code->id;
            }
        }

        $order->setFieldValue($this->fieldHandle, $codeIds);

        return Craft::$app->getElements()->saveElement($order);
    }
}
