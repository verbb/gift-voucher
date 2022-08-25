<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\models\Redemption;
use verbb\giftvoucher\records\Redemption as RedemptionRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
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


    // Properties
    // =========================================================================

    private ?MemoizableArray $_redemptions = null;


    // Public Methods
    // =========================================================================

    public function getRedemptionById(int $id): ?Redemption
    {
        return $this->_redemptions()->firstWhere('id', $id);
    }

    public function getRedemptionsByCodeId(int $codeId): array
    {
        return $this->_redemptions()->where('codeId', $codeId)->all();
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

        $this->_redemptions = null;

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

        Db::delete('{{%giftvoucher_redemptions}}', [
            'id' => $redemption->id,
        ]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_REDEMPTION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_REDEMPTION, new RedemptionEvent([
                'redemption' => $redemption,
            ]));
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _redemptions(): MemoizableArray
    {
        if (!isset($this->_redemptions)) {
            $redemptions = [];

            foreach ($this->_createRedemptionsQuery()->all() as $result) {
                $redemptions[] = new Redemption($result);
            }

            $this->_redemptions = new MemoizableArray($redemptions);
        }

        return $this->_redemptions;
    }

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
