<?php
namespace verbb\giftvoucher\storage;

use verbb\giftvoucher\helpers\CodeHelper;

use Craft;
use craft\base\Component;

use craft\commerce\elements\Order;

use yii\helpers\ArrayHelper;

class Order extends Component implements CodeStorageInterface
{
    // Properties
    // =========================================================================

    public $fieldHandle;


    // Public Methods
    // =========================================================================

    /**
     * Add a code
     *
     * @param                                $code
     * @param \craft\commerce\elements\Order $order
     *
     * @return bool
     *
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws \Throwable
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function add($code, Order $order): bool
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
     * @param \craft\commerce\elements\Order $order
     *
     * @return bool
     *
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws \Throwable
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function remove($code, Order $order): bool
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
     * @param \craft\commerce\elements\Order $order
     *
     * @return \verbb\giftvoucher\elements\Code[]
     *
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function getCodes(Order $order): array
    {
        if ($this->fieldHandle !== null) {
            return $order->getFieldValue($this->fieldHandle)->all();
        }

        return [];
    }

    /**
     * Get all stored code keys
     *
     * @param \craft\commerce\elements\Order $order
     *
     * @return string[]
     *
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function getCodeKeys(Order $order): array
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
     * @param \verbb\giftvoucher\elements\Code[]|int[]|string[] $codes
     * @param \craft\commerce\elements\Order                    $order
     *
     * @return bool
     *
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws \Throwable
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function setCodes(array $codes, Order $order): bool
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