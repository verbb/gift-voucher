<?php

namespace Craft;

class GiftVoucher_RedemptionService extends BaseApplicationComponent
{

    // Public Methods
    // =========================================================================

    /**
     * Get a Redemption by it's ID.
     *
     * @param int $id
     *
     * @return GiftVoucher_RedemptionModel
     * @throws Exception
     */
    public function getRedemptionById($id)
    {
        $record = GiftVoucher_RedemptionRecord::model()->findById($id);

        if (!$record) {
            throw new Exception(Craft::t('No redemption exists for the ID “{id}”',
                ['id' => $id]));
        }

        return GiftVoucher_RedemptionModel::populateModel($record);
    }

    /**
     * Get all Redemption Models by it's code id.
     *
     * @param int $codeId
     *
     * @return GiftVoucher_RedemptionModel[]
     */
    public function getRedemptionsForCode($codeId)
    {
        $records = GiftVoucher_RedemptionRecord::model()->findAllByAttributes(['codeId' => $codeId]);
        $models = [];

        foreach ($records as $record) {
            $models[] = GiftVoucher_RedemptionModel::populateModel($record);
        }

        return $models;
    }

    /**
     * Save a Redemption.
     *
     * @param GiftVoucher_RedemptionModel $redemption
     *
     * @return bool
     * @throws Exception
     */
    public function saveRedemption(GiftVoucher_RedemptionModel $redemption)
    {
        $code = GiftVoucherHelper::getCodesService()->getCodeById($redemption->codeId);

        if (!$code) {
            throw new Exception(Craft::t('No voucher code exists with the ID “{id}”', ['id' => $redemption->codeId]));
        }

        $order = craft()->commerce_orders->getOrderById($redemption->orderId);

        if (!$order) {
            throw new Exception(Craft::t('No order exists with the ID “{id}”', ['id' => $redemption->orderId]));
        }

        // See if we already have issues with provided data.
        if ($redemption->hasErrors()) {
            return false;
        }

        $record = new GiftVoucher_RedemptionRecord();
        $record->codeId = $redemption->codeId;
        $record->orderId = $redemption->orderId;
        $record->amount = $redemption->amount;

        $record->validate();
        $redemption->addErrors($record->getErrors());

        if ($redemption->hasErrors()) {
            return false;
        }

        if (!$record->save()) {
            return false;
        }

        return true;
    }
}
