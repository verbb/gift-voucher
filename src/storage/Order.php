<?php
/**
 * Craft CMS Plugins for Craft CMS 3.x
 *
 * Created with PhpStorm.
 *
 * @link      https://github.com/Anubarak/
 * @email     anubarak1993@gmail.com
 * @copyright Copyright (c) 2019 Robin Schambach
 */

namespace verbb\giftvoucher\storage;


use Craft;
use craft\base\Component;
use verbb\giftvoucher\helpers\CodeHelper;
use yii\helpers\ArrayHelper;

/**
 * @property \verbb\giftvoucher\elements\Code[]|array|int[]|string[] $codes
 */
class Order extends Component implements CodeStorageInterface
{

    public $fieldHandle;

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
    public function add($code, \craft\commerce\elements\Order $order): bool
    {
        $code = CodeHelper::getCode($code);
        if($code !== null && $this->fieldHandle !== null){
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
    public function remove($code, \craft\commerce\elements\Order $order): bool
    {
        $code = CodeHelper::getCode($code);
        if($code !== null && $this->fieldHandle !== null){
            $codes = $order->getFieldValue($this->fieldHandle)->ids();
            ArrayHelper::removeValue($codes, (int)$code->id);
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
    public function getCodes(\craft\commerce\elements\Order $order): array
    {
        if($this->fieldHandle !== null){
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
    public function getCodeKeys(\craft\commerce\elements\Order $order): array
    {
        $codes = $this->getCodes($order);
        $codeKeys = [];
        foreach ($codes as $code){
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
     * @since  18.12.2019
     */
    public function setCodes(array $codes, \craft\commerce\elements\Order $order): bool
    {
        if($this->fieldHandle === null){
            return false;
        }
        $codeIds = [];
        foreach ($codes as $code){
            $code = CodeHelper::getCode($code);
            if($code !== null){
                $codeIds[] = (int)$code->id;
            }
        }

        $order->setFieldValue($this->fieldHandle, $codeIds);

        return Craft::$app->getElements()->saveElement($order);
    }
}