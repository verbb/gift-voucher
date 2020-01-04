<?php
namespace verbb\giftvoucher\storage;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\helpers\CodeHelper;

use Craft;
use craft\base\Component;

use craft\commerce\elements\Order;

class Session extends Component implements CodeStorageInterface
{
    // Properties
    // =========================================================================

    /**
     * The key for code storing
     */
    const CODE_KEY = 'giftVoucher.giftVoucherCodes';
    
    /** @var \craft\web\Request */
    public $request;


    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->request = Craft::$app->getRequest();

        parent::init();
    }

    /**
     * Add a code
     *
     * @param                                $code
     * @param \craft\commerce\elements\Order $order
     *
     * @return bool
     *
     * @throws \craft\errors\MissingComponentException
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

            Craft::$app->getSession()->set(self::CODE_KEY, $codeKeys);

            return true;
        }

        return false;
    }

    /**
     * remove a code
     *
     * @param \verbb\giftvoucher\elements\Code|string|int|null $code
     * @param \craft\commerce\elements\Order                   $order
     *
     * @return bool
     *
     * @throws \craft\errors\MissingComponentException
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

            Craft::$app->getSession()->set(self::CODE_KEY, $codeKeys);
        }

        return $success;
    }

    /**
     * Get all Codes
     *
     * @param \craft\commerce\elements\Order $order
     *
     * @return array
     *
     * @throws \craft\errors\MissingComponentException
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function getCodeKeys(Order $order): array
    {
        if ($this->_isActive() === true) {
            return Craft::$app->getSession()->get(self::CODE_KEY, []);
        }

        return [];
    }

    /**
     * Get all Codes
     *
     * @param \craft\commerce\elements\Order $order
     *
     * @return Code[]
     *
     * @throws \craft\errors\MissingComponentException
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
     * @param array                          $codes
     * @param \craft\commerce\elements\Order $order
     *
     * @return bool
     *
     * @throws \craft\errors\MissingComponentException
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

            Craft::$app->getSession()->set(self::CODE_KEY, $codeKeys);
        }

        return $success;
    }


    // Private Methods
    // =========================================================================

    /**
     * Check if the session is even active
     *
     * @return bool
     *
     * @throws \craft\errors\MissingComponentException
     * @since  18.12.2019
     * @author Robin Schambach
     */
    private function _isActive(): bool
    {
        return $this->request->getIsConsoleRequest() === false && Craft::$app->getSession()->getIsActive();
    }
    
}