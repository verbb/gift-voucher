<?php
/**
 * Gift-Voucher Plugin for Craft CMS 3.x
 *
 * Created with PhpStorm.
 */

namespace verbb\giftvoucher\events;

use yii\base\Event;

/**
 * Class PopulateCodeFromLineItemEvent
 * @package verbb\giftvoucher\events
 */
class PopulateCodeFromLineItemEvent extends Event
{
    /**
     * The LineItem the code is populated from
     *
     * @var \craft\commerce\models\LineItem
     */
    public $lineItem;
    /**
     * The Order
     *
     * @var \craft\commerce\elements\Order $order
     */
    public $order;
    /**
     * The Code that is populated
     *
     * @var \verbb\giftvoucher\elements\Code $code
     */
    public $code;
    /**
     * The fields array in the LineItem
     * this may be empty.
     *
     * @var array $customFields
     */
    public $customFields;
    /**
     * The Voucher
     *
     * @var \verbb\giftvoucher\elements\Voucher
     */
    public $voucher;
}
