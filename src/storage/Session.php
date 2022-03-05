<?php
namespace verbb\giftvoucher\storage;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\helpers\CodeHelper;

use Craft;
use craft\base\Component;
use craft\errors\MissingComponentException;
use craft\web\Request;

use craft\commerce\elements\Order;

class Session extends Component implements CodeStorageInterface
{
    // Properties
    // =========================================================================

    /**
     * The key for code storing
     */
    const CODE_KEY = 'giftVoucher.giftVoucherCodes';
    
    /** @var Request */
    public Request $request;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->request = Craft::$app->getRequest();

        parent::init();
    }

    /**
     * Add a code
     *
     * @param                                $code
     *
     *
     * @throws MissingComponentException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function add($code, Order $order): bool
    {
        $code = CodeHelper::getCode($code);

        if ($code !== null && $this->_isActive() === true) {
            $codeKeys = $this->getCodeKeys($order);
            $codeKeys[] = $code->codeKey;
            $codeKeys = array_unique($codeKeys);

            Craft::$app->getSession()->set($this->_getCacheKey($order), $codeKeys);

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
     * @throws MissingComponentException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function remove($code, Order $order): bool
    {
        $code = CodeHelper::getCode($code);
        $success = false;
        
        if ($code !== null && $this->_isActive() === true) {
            $codeKeys = $this->getCodeKeys($order);

            foreach ($codeKeys as $key => $codeKey) {
                if ($codeKey === $code->codeKey) {
                    $success = true;
                    unset($codeKeys[$key]);
                }
            }

            Craft::$app->getSession()->set($this->_getCacheKey($order), $codeKeys);
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
        if ($this->_isActive() === true) {
            return Craft::$app->getSession()->get($this->_getCacheKey($order), []);
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
        if ($this->_isActive() === true && empty(($codes = $this->getCodeKeys($order))) === false) {
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

        if ($this->_isActive() === true) {
            $codeKeys = [];
            $success = true;

            foreach ($codes as $code){
                $codeElement = CodeHelper::getCode($code);
                
                if ($codeElement !== null){
                    $codeKeys[] = $codeElement->codeKey;
                } else {
                    $success = false;
                }
            }

            Craft::$app->getSession()->set($this->_getCacheKey($order), $codeKeys);
        }

        return $success;
    }


    // Private Methods
    // =========================================================================
    /**
     * Check if the session is even active
     *
     *
     * @throws MissingComponentException
     * @since  18.12.2019
     * @author Robin Schambach
     */
    private function _isActive(): bool
    {
        return $this->request->getIsConsoleRequest() === false && Craft::$app->getSession()->getIsActive();
    }

    private function _getCacheKey($order): string
    {
        return self::CODE_KEY . ':' . $order->id;
    }
    
}