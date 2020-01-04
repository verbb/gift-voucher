<?php
namespace verbb\giftvoucher\storage;

use craft\commerce\elements\Order;

interface CodeStorageInterface
{
    // Public Methods
    // =========================================================================

    public function add($code, Order $order): bool;

    public function remove($code, Order $order): bool;

    public function getCodes(Order $order): array;

    public function getCodeKeys(Order $order): array;

    public function setCodes(array $codes, Order $order): bool;
}