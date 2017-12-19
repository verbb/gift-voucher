<?php

namespace Craft;

class GiftVoucher_VouchersService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    /**
     * Get a Gift Voucher by it's ID.
     *
     * @param int $id
     * @param int $localeId
     *
     * @return GiftVoucher_VoucherModel
     */
    public function getVoucherById($id, $localeId = null)
    {
        return craft()->elements->getElementById($id, 'GiftVoucher_Voucher', $localeId);
    }

    /**
     * Get Vouchers by criteria.
     *
     * @param array|ElementCriteriaModel $criteria
     *
     * @return GiftVoucher_VoucherModel[]
     */
    public function getVouchers($criteria = [])
    {
        if (!$criteria instanceof ElementCriteriaModel) {
            $criteria = craft()->elements->getCriteria('GiftVoucher_Voucher', $criteria);
        }

        return $criteria->find();
    }


    /**
     * Save a Voucher.
     *
     * @param GiftVoucher_VoucherModel $voucher
     * @param array                    $productTypes
     * @param array                    $products
     *
     * @return bool
     * @throws Exception in case of invalid data
     * @throws \Exception if saving of the Element failed causing a failed transaction
     */
    public function saveVoucher(GiftVoucher_VoucherModel $voucher, array $productTypes = null, array $products = null)
    {
        if (!$voucher->id) {
            $record = new GiftVoucher_VoucherRecord();
        } else {
            $record = GiftVoucher_VoucherRecord::model()->findById($voucher->id);

            if (!$record) {
                throw new Exception(Craft::t('No voucher exists with the ID “{id}”',
                    ['id' => $voucher->id]));
            }
        }

        $voucherType = GiftVoucherHelper::getVoucherTypesService()->getVoucherTypeById($voucher->typeId);

        if (!$voucherType) {
            throw new Exception(Craft::t('No voucher type exists with the ID “{id}”',
                ['id' => $voucher->typeId]));
        }

        if (empty($voucher->sku)) {
            try {
                $voucher->sku = craft()->templates->renderObjectTemplate($voucherType->skuFormat, $voucher);
            } catch (\Exception $e) {
                $voucher->sku = '';
            }
        }

        $fields = [
            'expiry',
//            'description',
//            'purchaseTotal',
//            'purchaseQty',
//            'maxPurchaseQty',
//            'freeShipping',
//            'excludeOnSale',
            'sku',
            'typeId',
            'taxCategoryId',
            'shippingCategoryId',
            'price',
            'customAmount'
        ];

        foreach ($fields as $field) {
            $record->$field = $voucher->$field;
        }

//        $record->allProductTypes = $voucher->allProductTypes = empty($productTypes);
//        $record->allProducts = $voucher->allProducts = empty($products);
        
        $record->validate();
        $voucher->addErrors($record->getErrors());

        if ($voucher->hasErrors()) {
            return false;
        }

        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

        try {
            $event = new Event($this, ['voucher' => $voucher, 'isNewVoucher' => !$voucher->id]);

            $success = false;
            
            if ($event->performAction) {
                $success = craft()->commerce_purchasables->saveElement($voucher);
            }

            if (!$success) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                return false;
            }

            $record->id = $voucher->id;
            $record->save(false);

//            GiftVoucher_VoucherProductRecord::model()->deleteAllByAttributes(['voucherId' => $voucher->id]);
//            GiftVoucher_VoucherProductTypeRecord::model()->deleteAllByAttributes(['voucherId' => $voucher->id]);
//
//            foreach ($productTypes as $productTypeId) {
//                $relation = new GiftVoucher_VoucherProductTypeRecord;
//                $relation->attributes = ['productTypeId' => $productTypeId, 'voucherId' => $voucher->id];
//                $relation->insert();
//            }
//
//            foreach ($products as $productId) {
//                $relation = new GiftVoucher_VoucherProductRecord;
//                $relation->attributes = ['productId' => $productId, 'voucherId' => $voucher->id];
//                $relation->insert();
//            }

            if ($transaction !== null) {
                $transaction->commit();
            }

        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }

            throw $e;
        }

        return true;
    }

    /**
     * Delete a Voucher.
     *
     * @param GiftVoucher_VoucherModel $voucher
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteVoucher(GiftVoucher_VoucherModel $voucher)
    {
        $event = new Event($this, ['voucher' => $voucher]);

        return ($event->performAction && craft()->elements->deleteElementById($voucher->id));
    }

    /**
     * Checks a purchasable for enabled customAmount setting and updates the line item price
     *
     * @param Event $event
     */
    public function onPopulateLineItemHandler(Event $event)
    {
        $lineItem = $event->params['lineItem'];
        $purchasable = $event->params['purchasable'];

        if (!$lineItem || !$purchasable) {
            return;
        }

        if ($purchasable instanceof GiftVoucher_VoucherModel && $purchasable->customAmount) {
            $options = $lineItem->options;

            if (isset($options['amount'])) {
                $lineItem->price = $options['amount'];
            }
        }
    }
}
