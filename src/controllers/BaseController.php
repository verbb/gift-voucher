<?php

namespace verbb\giftvoucher\controllers;

use Craft;
use craft\db\Table;
use craft\web\Controller;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\GiftVoucher;
use yii\web\Response;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = GiftVoucher::$plugin->getSettings();

        $this->renderTemplate(
            'gift-voucher/settings',
            [
                'settings' => $settings,
            ]
        );
    }

    /**
     * Saves a plugin’s settings.
     *
     * This method is required here now because of the field layout ID
     * I could have used some events to populate the field layout by POST request
     * in beforeSaveSettings event but it seemed a little bit nasty to me ¯\_(ツ)_/¯
     *
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     * @return Response|null
     */
    public function actionSavePluginSettings()
    {
        $this->requirePostRequest();
        $plugin = GiftVoucher::getInstance();
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);

        // set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Code::class;
        Craft::$app->getFields()->saveLayout($fieldLayout);
        $settings['fieldLayoutId'] = $fieldLayout->id;

        $plugin->getSettings()->setAttributes($settings, false);

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save plugin settings.'));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams(
                [
                    'plugin' => $plugin
                ]
            );

            return null;
        }

        // re-save all codes to insert their new field layout
        // maybe you want to move that in a service as well
        /** @var \verbb\giftvoucher\models\Settings $existingSettings */
        $existingSettings = $plugin->getSettings();
        if((int)$existingSettings->fieldLayoutId !== (int)$settings['fieldLayoutId']){
            // field layout has changed
            // update all code field layouts
            $db = Craft::$app->getDb();
            $transaction = $db->beginTransaction();

            try {
                $db->createCommand()
                    ->update(
                        Table::ELEMENTS,
                        [
                            'fieldLayoutId' => $settings['fieldLayoutId']
                        ],
                        [
                            'type' => Code::class
                        ]
                    )->getRawSql();
            } catch (\Throwable $e) {
                $transaction->rollBack();

                throw $e;
            }
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
