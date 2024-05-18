<?php
namespace verbb\giftvoucher\storage;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\helpers\CodeHelper;

use Craft;
use craft\base\Component;
use craft\errors\MissingComponentException;
use craft\helpers\Session as SessionHelper;

use craft\commerce\elements\Order;

class Session extends Component implements CodeStorageInterface
{
    // Constants
    // =========================================================================

    public const CODE_KEY = 'giftVoucher.giftVoucherCodes';


    // Public Methods
    // =========================================================================

    /**
     * Add a code
     *
     * @param                                $code
     *
     * @param Order $order
     * @return bool
     * @throws MissingComponentException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function add($code, Order $order): bool
    {
        $code = CodeHelper::getCode($code);

        if ($code !== null && SessionHelper::exists()) {
            $codeKeys = $this->getCodeKeys($order);
            $codeKeys[] = $code->codeKey;
            $codeKeys = array_unique($codeKeys);

            SessionHelper::set($this->_getCacheKey($order), $codeKeys);

            return true;
        }

        return false;
    }

    /**
     * remove a code
     *
     * @param Code|string|int|null $code
     * @param Order $order
     *
     *
     * @return bool
     * @throws MissingComponentException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function remove($code, Order $order): bool
    {
        $code = CodeHelper::getCode($code);
        $success = false;

        if ($code !== null && SessionHelper::exists()) {
            $codeKeys = $this->getCodeKeys($order);

            foreach ($codeKeys as $key => $codeKey) {
                if ($codeKey === $code->codeKey) {
                    $success = true;
                    unset($codeKeys[$key]);
                }
            }

            SessionHelper::set($this->_getCacheKey($order), $codeKeys);
        }

        return $success;
    }

    /**
     * Get all Codes
     *
     *
     *
     * @throws MissingComponentException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function getCodeKeys(Order $order): array
    {
        if (SessionHelper::exists()) {
            return SessionHelper::get($this->_getCacheKey($order), []) ?? [];
        }

        return [];
    }

    /**
     * Get all Codes
     *
     *
     * @return Code[]
     *
     * @throws MissingComponentException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function getCodes(Order $order): array
    {
        if (SessionHelper::exists() && empty(($codes = $this->getCodeKeys($order))) === false) {
            return Code::find()->codeKey($codes)->all();
        }

        return [];
    }

    /**
     * Set Codes
     *
     *
     *
     * @throws MissingComponentException
     * @author Robin Schambach
     * @since  18.12.2019
     */
    public function setCodes(array $codes, Order $order): bool
    {
        $success = false;

        if (SessionHelper::exists()) {
            $codeKeys = [];
            $success = true;

            foreach ($codes as $code) {
                $codeElement = CodeHelper::getCode($code);

                if ($codeElement !== null) {
                    $codeKeys[] = $codeElement->codeKey;
                } else {
                    $success = false;
                }
            }

            SessionHelper::set($this->_getCacheKey($order), $codeKeys);
        }

        return $success;
    }


    // Private Methods
    // =========================================================================

    private function _getCacheKey($order): string
    {
        return self::CODE_KEY . ':' . $order->id;
    }

}