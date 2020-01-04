<?php
namespace verbb\giftvoucher\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m200104_000000_include_code_content extends Migration
{
    public function safeUp()
    {
        // grab all existing Voucher Codes
        $rows = (new Query())
            ->select(['code.id as id'])
            ->from('{{%giftvoucher_codes}} code')
            ->all();

        // grab the primary site
        // I use normal queries here because the Craft API my change as well over time
        // ¯\_(ツ)_/¯
        $primarySiteId = (new Query())
            ->select('id')
            ->where(['primary' => 1])
            ->from(Table::SITES)
            ->scalar();

        if (empty($primarySiteId) === false) {
            foreach ($rows as $row) {
                // find their elements
                // create a content record for those elements, otherwise they can't be found
                // in the CP since Craft won't find elements with `null` rows in their content table

                // only add a record for the primary site id
                // `Code::isLocalized is false so Code::SupportedSites will return
                // `[Craft::$app->getSites()->getPrimarySite()->id];` -> only the primary site
                $id = $row['id'] ?? null;

                if ($id !== null) {
                    Craft::$app->getDb()->createCommand()
                        ->insert(Table::CONTENT, [
                            'elementId' => $id,
                            'siteId' => $primarySiteId,
                        ])
                        ->execute();
                }
            }
        }


    }

    public function safeDown()
    {
        echo "m200104_000000_include_code_content cannot be reverted.\n";

        return false;
    }
}
