<?php

namespace Craft;

class GiftVoucher_CodesService extends BaseApplicationComponent
{
    // Properties
    // =========================================================================

    const CODE_KEY_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';


    // Public Methods
    // =========================================================================

    /**
     * Get a Code by it's ID.
     *
     * @param int $id
     *
     * @return GiftVoucher_CodeModel
     * @throws Exception
     */
    public function getCodeById($id)
    {
        $codeRecord = GiftVoucher_CodeRecord::model()->findById($id);

        if (!$codeRecord) {
            throw new Exception(Craft::t('No code exists for the ID “{id}”',
                ['id' => $id]));
        }

        return GiftVoucher_CodeModel::populateModel($codeRecord);
    }

    /**
     * Get a Code Model by it's code.
     *
     * @param string $codeKey
     *
     * @return GiftVoucher_CodeModel|false
     */
    public function getCodeByCodeKey($codeKey)
    {
        $codeRecord = GiftVoucher_CodeRecord::model()->findByAttributes([
            'codeKey' => $codeKey,
        ]);

        if (!$codeRecord) {
            return false;
        }

        return GiftVoucher_CodeModel::populateModel($codeRecord);
    }

    /**
     * Get Codes for given attributes
     *
     * @param array $attributes
     * @param array $options
     *
     * @return GiftVoucher_CodeModel[]
     */
    public function getCodes(array $attributes = [], array $options = [])
    {
        $codeRecords = GiftVoucher_CodeRecord::model()->findAllByAttributes($attributes, $options);
        $codeModels = [];

        foreach ($codeRecords as $codeRecord) {
            $codeModels[] = GiftVoucher_CodeModel::populateModel($codeRecord);
        }

        return $codeModels;
    }

    /**
     * Get an array of Codes for a single line item because one line item can
     * have more then one voucher
     *
     * @param Commerce_LineItemModel $lineItem
     *
     * @return GiftVoucher_CodeModel[]
     * @throws Exception
     */
    public function getCodesForLineItem(Commerce_LineItemModel $lineItem)
    {
        $codeRecords = GiftVoucher_CodeRecord::model()->findAllByAttributes([
            'orderId'    => $lineItem->order->id,
            'lineItemId' => $lineItem->id,
        ]);

        if (!$codeRecords) {
            throw new Exception(Craft::t('No codes exists for the line Item ID “{id}”',
                ['id' => $lineItem->id]));
        }

        $codes = [];

        foreach ($codeRecords as $codeRecord) {
            $codes[] = GiftVoucher_CodeModel::populateModel($codeRecord)->codeKey;
        }

        return $codes;
    }

    /**
     * Save a Code.
     *
     * @param GiftVoucher_CodeModel $code
     *
     * @return bool
     * @throws Exception in case of invalid data.
     * @throws \Exception if saving of the Element failed causing a failed transaction
     */
    public function saveCode(GiftVoucher_CodeModel $code)
    {
        $isNewCode = false;

        if (!$code->id) {
            $record = new GiftVoucher_CodeRecord();
            $isNewCode = true;
        } else {
            $record = GiftVoucher_CodeRecord::model()->findById($code->id);

            if (!$record) {
                throw new Exception(Craft::t('No code exists with the ID “{id}”',
                    ['id' => $code->id]));
            }
        }

        // Validate model
        $code->validate();

        // See if we got some issues with provided data.
        if ($code->hasErrors()) {
            return false;
        }

        $voucher = GiftVoucherHelper::getVouchersService()->getVoucherById($code->voucherId);

        if (!$voucher) {
            throw new Exception(Craft::t('No voucher exists with the ID “{id}”', ['id' => $code->voucherId]));
        }

        $voucherType = $voucher->getVoucherType();

        if (!$voucherType) {
            throw new Exception(Craft::t('No voucher type exists with the ID “{id}”', ['id' => $voucher->typeId]));
        }


        $record->id = $code->id;
        $record->currentAmount = $code->currentAmount;
        $record->expiryDate = $code->expiryDate;
        $record->voucherId = $code->voucherId;
        $record->manually = $code->manually;

        if (empty($code->voucherId)) {
            $code->addError('voucherId', Craft::t('{attribute} cannot be blank.', ['attribute' => 'Voucher']));
        }

        if (!$record->id) {
            do {
                $codeKey = $this->generateCodeKey();
                $conflict = GiftVoucher_CodeRecord::model()->findAllByAttributes(['codeKey' => $codeKey]);
            } while ($conflict);

            $modifiedCodeKey = craft()->plugins->callFirst('giftVoucher_modifyCodeKey', [
                $codeKey,
                $code,
            ], true);

            // Use the plugin-modified name, if anyone was up to the task.
            $codeKey = $modifiedCodeKey ?: $codeKey;

            $record->codeKey = $codeKey;

            // Set original amount
            $record->originalAmount = $code->originalAmount;
        }

        $record->validate();
        $code->addErrors($record->getErrors());

        if ($code->hasErrors()) {
            return false;
        }

        if (!$record->save()) {
            return false;
        }

        if ($isNewCode) {
            return $record->id;
        }

        return true;
    }

    /**
     * Check for two options:
     *
     * 1. Sort trough the ordered items and check if a voucher item was purchased, then generate Codes for that voucher.
     * 2. If a voucher code was used, update the current amount of that code and remove the code from the session.
     *
     * @param Event $event
     *
     * @throws Exception
     * @throws \Exception
     */
    public static function onOrderCompleteHandler(Event $event)
    {
        if (empty($event->params['order'])) {
            return;
        }

        // Check every single line item
        /**
         * @var Commerce_OrderModel $order
         */
        $order = $event->params['order'];

        foreach ($order->getLineItems() as $lineItem) {
            $itemId = $lineItem->purchasableId;
            $element = craft()->elements->getElementById($itemId);
            $quantity = $lineItem->qty;

            if ($element->getElementType() == "GiftVoucher_Voucher") {
                for ($i = 0; $i < $quantity; $i++) {
                    GiftVoucherHelper::getCodesService()->codeVoucherByOrder($element, $lineItem);
                }
            }
        }

        // Check if a voucher code was used
        $code = craft()->httpSession->get('giftVoucher.giftVoucherCode');

        if ($code != '') {
            $voucherCode = GiftVoucherHelper::getCodesService()->getCodeByCodeKey($code);

            if ($voucherCode) {
                $reducedAmount = $voucherCode->currentAmount;

                // If the voucher discount is higher then the order total price
                // (total price is then calculated to 0), reduce the voucher amount
                // of the item total, tax and shipping costs.
                // Otherwise set the voucher current amount to 0
                if ($order->totalPrice == 0) {
                    $reducedAmount = 0;
                    $reducedAmount += $order->itemTotal;
                    $reducedAmount += $order->getTotalTax();
                    $reducedAmount += $order->getTotalShippingCost();

                    if ($reducedAmount > 0) {
                        $voucherCode->currentAmount -= $reducedAmount;
                    } else {
                        $voucherCode->currentAmount = 0;
                    }
                } else {
                    $voucherCode->currentAmount = 0;
                }

                GiftVoucherHelper::getCodesService()->saveCode($voucherCode);

                // Track code redemption
                $redemption = new GiftVoucher_RedemptionModel();
                $redemption->codeId = $voucherCode->id;
                $redemption->orderId = $order->id;
                $redemption->amount = $reducedAmount;
                GiftVoucherHelper::getRedemptionService()->saveRedemption($redemption);

                // delete session code 'giftVoucher.code'
                craft()->httpSession->add('giftVoucher.giftVoucherCode', '');
            }
        }
    }

    /**
     * Generate a code for a Voucher per Order.
     *
     * @param GiftVoucher_VoucherModel $voucher
     * @param Commerce_LineItemModel   $lineItem
     *
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function codeVoucherByOrder(GiftVoucher_VoucherModel $voucher, Commerce_LineItemModel $lineItem)
    {
        $code = new GiftVoucher_CodeModel();
        $code->voucherId = $voucher->id;

        // Set code amount from line item price. This makes sure, that vouchers
        // with custom amounts get the right amount.
        $code->originalAmount = $lineItem->price;
        $code->currentAmount = $lineItem->price;

        $settingExpiry = GiftVoucherHelper::getPlugin()->getSettings()->expiry;

        if ($settingExpiry == 0) {
            $code->expiryDate = 0;
        } else {
            // Set expiry beginning from order creation date
            $day = $lineItem->order->dateOrdered->day();
            $month = (int)$lineItem->order->dateOrdered->month();
            $year = $lineItem->order->dateOrdered->year();
            $newExpiry = mktime(0, 0, 0, $month + $settingExpiry, $day, $year);

            $code->expiryDate = DateTimeHelper::toIso8601($newExpiry);
        }

        $codeId = $this->saveCode($code);

        if ($codeId !== false) {
            return (bool)craft()->db->createCommand()->update('giftvoucher_codes', [
                'orderId'    => $lineItem->order->id,
                'lineItemId' => $lineItem->id,
            ], [
                'id' => $codeId,
            ]);
        }

        return false;
    }

    /**
     * Generate a code key.
     *
     * @return string
     */
    public function generateCodeKey()
    {
        $codeAlphabet = self::CODE_KEY_CHARACTERS;
        $keyLength = GiftVoucherHelper::getPlugin()->getSettings()->codeKeyLength;

        $codeKey = '';

        for ($i = 0; $i < $keyLength; $i++) {
            $codeKey .= $codeAlphabet[mt_rand(0, strlen($codeAlphabet) - 1)];
        }

        return $codeKey;
    }

    /**
     * Match Voucher Code and check if
     * - is valid
     * - has an amount left
     * - not expired
     *
     * @param string      $code
     * @param string|null $error
     *
     * @return bool
     */
    public function matchCode($code, &$error)
    {
        $voucherCode = $this->getCodeByCodeKey($code);

        // Check if valid
        if (!$voucherCode) {
            $error = Craft::t('Voucher code is not valid');

            return false;
        }

        // Check if has an amount left
        if ($voucherCode->currentAmount <= 0) {
            $error = Craft::t('Voucher code has no amount left');

            return false;
        }

        // Check for expiry date
        $today = new DateTime();
        if ($voucherCode->expiryDate && $voucherCode->expiryDate->format('Ymd') < $today->format('Ymd')) {
            $error = Craft::t('Voucher code is out of date');

            return false;
        }

        return true;
    }

    /**
     * Delete a Voucher Code by it's Id.
     *
     * @param $id
     *
     * @return bool
     * @throws \Exception if failed to delete the Voucher Code.
     */
    public function deleteCodeById($id)
    {
        try {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            $codeRecord = GiftVoucher_CodeRecord::model()->findById($id);
            $affectedRows = $codeRecord->delete();

            if ($transaction !== null) {
                $transaction->commit();
            }

            return (bool)$affectedRows;
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }
            throw $e;
        }
    }
}
