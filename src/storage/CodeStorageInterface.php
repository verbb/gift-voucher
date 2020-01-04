<?php
namespace verbb\giftvoucher\storage;

use craft\commerce\elements\Order;

/**
 * Interface CodeStorageInterface
 * @package verbb\giftvoucher\storage
 */
interface CodeStorageInterface
{
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
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function add($code, Order $order): bool;

    /**
     * remove a code
     *
     * @param                                $code
     * @param \craft\commerce\elements\Order $order
     *
     * @return bool
     *
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function remove($code, Order $order): bool;

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
    public function getCodes(Order $order): array;

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
    public function getCodeKeys(Order $order): array;

    /**
     * Set Codes
     *
     * @param \verbb\giftvoucher\elements\Code[]|int[]|string[] $codes
     * @param \craft\commerce\elements\Order $order
     *
     * @return bool
     *
     * @author Robin Schambach
     * @since  18.12.2019
     */
    public function setCodes(array $codes, Order $order): bool;
}