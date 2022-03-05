<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\records\CodeRecord;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\records\Element;
use craft\records\Element_SiteSettings;

class m190825_000000_migrate_codes_to_elements extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Find any orphaned records with no element
        $codeIds = (new Query())
            ->select(['codes.id AS codeId'])
            ->from('{{%giftvoucher_codes}} codes')
            ->leftJoin('{{%elements}} elements', '[[codes.id]] = [[elements.id]]')
            ->where(['elements.id' => null])
            ->orWhere(['!=', 'elements.type', Code::class])
            ->groupBy('codes.id')
            ->column();

        $queryBuilder = $this->db->getSchema()->getQueryBuilder();
        $this->execute($queryBuilder->checkIntegrity(false));

        if ($codeIds) {
            foreach ($codeIds as $codeId) {
                // Fetch the record
                $codeRecord = CodeRecord::findOne($codeId);

                // Create a low-level element record
                $elementRecord = new Element();
                $elementRecord->type = Code::class;
                $elementRecord->enabled = true;
                $elementRecord->dateCreated = $codeRecord->dateCreated;
                $elementRecord->dateUpdated = $codeRecord->dateUpdated;

                $elementRecord->save(false);

                echo 'Created element ' . $elementRecord->id . PHP_EOL;

                // Create the appropriate site element
                $siteSettingsRecord = new Element_SiteSettings();
                $siteSettingsRecord->elementId = $elementRecord->id;
                $siteSettingsRecord->siteId = Craft::$app->getSites()->getPrimarySite()->id;
                $siteSettingsRecord->enabled = true;

                $siteSettingsRecord->save(false);

                echo 'Created site element ' . $siteSettingsRecord->id . PHP_EOL;

                // Before we proceed, we also have to update any redemptions tied to this code - bah
                $this->update('{{%giftvoucher_redemptions}}', ['codeId' => $elementRecord->id], ['codeId' => $codeId], [], false);

                echo 'Updating ' . $codeRecord->id . ' => ' . $elementRecord->id . PHP_EOL;

                // Update the code
                $this->update('{{%giftvoucher_codes}}', ['id' => $elementRecord->id], ['id' => $codeId], [], false);
            }
        }

        // Re-enable FK checks
        $this->execute($queryBuilder->checkIntegrity(true));

        // Add back FK check that should've been there anyway
        $this->addForeignKey(null, '{{%giftvoucher_codes}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190825_000000_migrate_codes_to_elements cannot be reverted.\n";
        return false;
    }
}
