<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\models\Redemption;
use verbb\giftvoucher\records\Redemption as RedemptionRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

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

    public function getRedemptionById(int $id): ?Redemption
    {
        $result = $this->_createRedemptionsQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new Redemption($result) : null;
    }

    public function getRedemptionsByCodeId(int $codeId): array
    {
        $redemptions = [];

        $results = $this->_createRedemptionsQuery()
            ->where(['codeId' => $codeId])
            ->all();

        foreach ($results as $result) {
            $redemptions[] = new Redemption($result);
        }

        return $redemptions;
    }

    public function saveRedemption(Redemption $redemption, bool $runValidation = true): bool
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

        $redemptionRecord = $this->_getRedemptionRecord($redemption->id);
        $redemptionRecord->codeId = $redemption->codeId;
        $redemptionRecord->orderId = $redemption->orderId;
        $redemptionRecord->amount = $redemption->amount;

        // Save the record
        $redemptionRecord->save(false);

        // Now that we have an ID, save it on the model
        if ($isNewRedemption) {
            $redemption->id = $redemptionRecord->id;
        }

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

    public function deleteRedemption(Redemption $redemption): bool
    {
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_REDEMPTION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_REDEMPTION, new RedemptionEvent([
                'redemption' => $redemption,
            ]));
        }

        $affectedRows = Db::delete('{{%giftvoucher_redemptions}}', [
            'id' => $redemption->id,
        ]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_REDEMPTION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_REDEMPTION, new RedemptionEvent([
                'redemption' => $redemption,
            ]));
        }

        return (bool)$affectedRows;
    }


    // Private Methods
    // =========================================================================

    private function _createRedemptionsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'codeId',
                'orderId',
                'amount',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from(['{{%giftvoucher_redemptions}}']);
    }

    private function _getRedemptionRecord(int|string|null $id): RedemptionRecord
    {
        /** @var RedemptionRecord $redemption */
        if ($id && $redemption = RedemptionRecord::find()->where(['id' => $id])->one()) {
            return $redemption;
        }

        return new RedemptionRecord();
    }
}
