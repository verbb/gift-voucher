// craft-screenshots: gift-voucher-type
/** Create the project-config-backed voucher type before seeding content. */

use craft\fieldlayoutelements\TitleField;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\models\VoucherType;
use verbb\giftvoucher\models\VoucherTypeSite;

$site = Craft::$app->getSites()->getPrimarySite();
$voucherTypes = GiftVoucher::$plugin->getVoucherTypes();

if (!$voucherTypes->getVoucherTypeByHandle('giftCards')) {
    $layout = new FieldLayout(['type' => Voucher::class]);
    $tab = new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $layout]);
    $tab->setElements([new TitleField()]);
    $layout->setTabs([$tab]);

    $voucherType = new VoucherType([
        'name' => 'Gift Cards',
        'handle' => 'giftCards',
        'skuFormat' => 'GIFT-{id}',
        'descriptionFormat' => '{title}',
    ]);
    $voucherType->getBehavior('voucherFieldLayout')->setFieldLayout($layout);
    $voucherType->setSiteSettings([
        $site->id => new VoucherTypeSite([
            'siteId' => $site->id,
            'hasUrls' => false,
            'uriFormatIsRequired' => false,
        ]),
    ]);

    if (!$voucherTypes->saveVoucherType($voucherType)) {
        throw new RuntimeException('Unable to save the Gift Voucher screenshot type: ' . Json::encode($voucherType->getErrors()));
    }
}

echo Json::encode(['voucherTypeHandle' => 'giftCards'], JSON_THROW_ON_ERROR);
