<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\models\RedemptionModel;
use verbb\giftvoucher\records\Redemption as RedemptionRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;

use Exception;

class Redemptions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_REDEMPTION = 'beforeSaveRedemption';
    public const EVENT_AFTER_SAVE_REDEMPTION = 'afterSaveRedemption';
    public const EVENT_BEFORE_DELETE_REDEMPTION = 'beforeDeleteRedemption';
    public const EVENT_AFTER_DELETE_REDEMPTION = 'afterDeleteRedemption';


    // Public Methods
    // =========================================================================

    public function getRedemptionById(int $id): ?RedemptionModel
    {
        $result = $this->_createRedemptionsQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new RedemptionModel($result) : null;
    }

    public function getRedemptionsByCodeId(int $codeId): array
    {
        $results = $this->_createRedemptionsQuery()
            ->where(['codeId' => $codeId])
            ->all();

        $redemptions = [];

        foreach ($results as $result) {
            $redemptions[] = new RedemptionModel($result);
        }

        return $redemptions;
    }

    public function saveRedemption(RedemptionModel $redemption, bool $runValidation = true): bool
    {
        $isNewRedemption = !$redemption->id;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_REDEMPTION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_REDEMPTION, new RedemptionEvent([
                'redemption' => $redemption,
                'isNew' => $isNewRedemption,
            ]));
        }

        if ($runValidation && !$redemption->validate()) {
            Craft::info('Redemption not saved due to validation error.', __METHOD__);
            return false;
        }

        $redemptionRecord = $this->_getRedemptionRecordById($redemption->id);

        $redemptionRecord->codeId = $redemption->codeId;
        $redemptionRecord->orderId = $redemption->orderId;
        $redemptionRecord->amount = $redemption->amount;

        // Save the record
        $redemptionRecord->save(false);

        // Now that we have an ID, save it on the model
        if ($isNewRedemption) {
            $redemption->id = $redemptionRecord->id;
        }

        // Might as well update our cache of the model while we have it.
        $this->_redemptionsById[$redemption->id] = $redemption;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_REDEMPTION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_REDEMPTION, new RedemptionEvent([
                'redemption' => $redemption,
                'isNew' => $isNewRedemption,
            ]));
        }

        return true;
    }

    public function deleteRedemptionById(int $redemptionId): bool
    {
        $redemption = $this->getRedemptionById($redemptionId);

        if (!$redemption) {
            return false;
        }

        return $this->deleteRedemption($redemption);
    }

    public function deleteRedemption(RedemptionModel $redemption): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_REDEMPTION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_REDEMPTION, new RedemptionEvent([
                'redemption' => $redemption,
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->delete('{{%giftvoucher_redemptions}}', ['id' => $redemption->id])
            ->execute();

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_REDEMPTION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_REDEMPTION, new RedemptionEvent([
                'redemption' => $redemption,
            ]));
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _getRedemptionRecordById(int $redemptionId = null): ?RedemptionRecord
    {
        if ($redemptionId !== null) {
            $redemptionRecord = RedemptionRecord::findOne($redemptionId);

            if (!$redemptionRecord) {
                throw new Exception("No redemption exists with the ID '{$redemptionId}'");
            }
        } else {
            $redemptionRecord = new RedemptionRecord();
        }

        return $redemptionRecord;
    }

    private function _createRedemptionsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'codeId',
                'orderId',
                'amount',
            ])
            ->from(['{{%giftvoucher_redemptions}}']);
    }
}
