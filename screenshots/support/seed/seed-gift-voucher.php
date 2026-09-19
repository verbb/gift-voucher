// craft-screenshots: gift-voucher-feature-tour
/** Seed realistic Gift Voucher content and an illustrative voucher template. */

use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\commerce\Plugin as Commerce;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\GiftVoucher;

$elements = Craft::$app->getElements();
$site = Craft::$app->getSites()->getPrimarySite();
$voucherType = GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeByHandle('giftCards');

if (!$voucherType) {
    throw new RuntimeException('Unable to resolve the persisted Gift Voucher screenshot type.');
}

$store = Commerce::getInstance()->getStores()->getPrimaryStore();
$taxCategory = Commerce::getInstance()->getTaxCategories()->getDefaultTaxCategory();
$shippingCategory = Commerce::getInstance()->getShippingCategories()->getDefaultShippingCategory($store->id);

if (!$store || !$taxCategory || !$shippingCategory) {
    throw new RuntimeException('Unable to resolve the default Commerce store categories for Gift Voucher screenshots.');
}

$voucherData = [
    ['A little something', 'GIFT-150', 150],
    ['Weekend away', 'GIFT-250', 250],
    ['Dinner for two', 'GIFT-120', 120],
];
$vouchers = [];

foreach ($voucherData as [$title, $sku, $price]) {
    $voucher = Voucher::find()
        ->typeId($voucherType->id)
        ->sku($sku)
        ->siteId($site->id)
        ->status(null)
        ->one();

    if (!$voucher) {
        $voucher = new Voucher([
            'typeId' => $voucherType->id,
            'siteId' => $site->id,
            'title' => $title,
            'slug' => strtolower(str_replace(' ', '-', $title)),
            'sku' => $sku,
            'price' => $price,
            'customAmount' => false,
            'promotable' => true,
            'availableForPurchase' => true,
            'taxCategoryId' => $taxCategory->id,
            'shippingCategoryId' => $shippingCategory->id,
            'postDate' => new DateTime('2026-09-01 09:00:00', new DateTimeZone('America/Los_Angeles')),
            'enabled' => true,
        ]);

        if (!$elements->saveElement($voucher)) {
            throw new RuntimeException('Unable to save a Gift Voucher screenshot voucher: ' . Json::encode($voucher->getErrors()));
        }
    }

    $vouchers[$sku] = $voucher;
}

$codeData = [
    ['WARM-WISHES-26', 'GIFT-150', 150, 95, '2027-09-30 23:59:00'],
    ['WEEKEND-AWAY', 'GIFT-250', 250, 250, '2027-12-31 23:59:00'],
    ['DINNER-FOR-TWO', 'GIFT-120', 120, 72, '2027-06-30 23:59:00'],
    ['CELEBRATE-150', 'GIFT-150', 150, 150, null],
    ['THANK-YOU-120', 'GIFT-120', 120, 120, '2027-03-31 23:59:00'],
    ['JUST-BECAUSE', 'GIFT-250', 250, 180, null],
];

foreach ($codeData as [$codeKey, $sku, $originalAmount, $currentAmount, $expiry]) {
    $code = Code::find()->codeKey($codeKey)->status(null)->one();

    if (!$code) {
        $code = new Code([
            'voucherId' => $vouchers[$sku]->id,
            'codeKey' => $codeKey,
            'originalAmount' => $originalAmount,
            'currentAmount' => $currentAmount,
            'expiryDate' => $expiry ? new DateTime($expiry, new DateTimeZone('America/Los_Angeles')) : null,
            'enabled' => true,
        ]);

        if (!$elements->saveElement($code)) {
            throw new RuntimeException('Unable to save a Gift Voucher screenshot code: ' . Json::encode($code->getErrors()));
        }
    }
}

$featuredCode = Code::find()->codeKey('WARM-WISHES-26')->status(null)->one();
$codeCount = (int)Code::find()->status(null)->count();

if (!$featuredCode) {
    throw new RuntimeException('Unable to resolve the featured Gift Voucher screenshot code.');
}

$templateDir = Craft::getAlias('@templates') . '/shop/_pdf';
FileHelper::createDirectory($templateDir);
$voucherTemplate = <<<'TWIG'
{# craft-screenshots: sample-frontend #}
{% set code = codes|first %}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>A little something for you</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 72px 48px; background: #f3f1ec; color: #173a32; font-family: Arial, Helvetica, sans-serif; }
        .voucher-shell { width: min(1080px, 100%); margin: 0 auto; }
        .voucher { position: relative; display: grid; grid-template-columns: minmax(0, 1fr) 340px; min-height: 520px; overflow: hidden; border-radius: 28px; background: #fbfaf7; box-shadow: 0 28px 70px rgba(23, 58, 50, .16); }
        .voucher::before { position: absolute; inset: 0; background: radial-gradient(circle at 8% 18%, rgba(236, 178, 95, .28) 0 14%, transparent 14.2%), radial-gradient(circle at 74% 92%, rgba(85, 139, 119, .16) 0 24%, transparent 24.2%); content: ''; pointer-events: none; }
        .message { position: relative; display: flex; flex-direction: column; justify-content: space-between; padding: 64px 68px 58px; }
        .brand { display: flex; align-items: center; gap: 12px; color: #58776d; font-size: 13px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        .mark { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 50%; background: #173a32; color: #fff; font-size: 17px; }
        h1 { max-width: 620px; margin: 46px 0 18px; font-family: Georgia, 'Times New Roman', serif; font-size: 68px; font-weight: 500; letter-spacing: -.045em; line-height: .98; }
        .note { max-width: 560px; margin: 0; color: #526861; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; line-height: 1.5; }
        .from { margin-top: 54px; color: #173a32; font-size: 15px; font-weight: 700; }
        .details { position: relative; display: flex; flex-direction: column; justify-content: center; padding: 54px 48px; background: #173a32; color: #fff; }
        .label { display: block; margin-bottom: 8px; color: #b9d0c7; font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .amount { margin-bottom: 48px; font-family: Georgia, 'Times New Roman', serif; font-size: 76px; line-height: 1; }
        .code { padding: 17px 18px; border: 1px solid rgba(255, 255, 255, .28); border-radius: 10px; background: rgba(255, 255, 255, .08); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 19px; font-weight: 700; letter-spacing: .045em; text-align: center; }
        .expiry { margin-top: 30px; color: #dbe7e2; font-size: 14px; line-height: 1.5; }
        .fine-print { margin-top: 54px; color: #9fbbb0; font-size: 11px; line-height: 1.5; }
    </style>
</head>
<body>
    <main class="voucher-shell">
        <article class="voucher" aria-label="Illustrative gift voucher">
            <section class="message">
                <div>
                    <div class="brand"><span class="mark">V</span> Verbb &amp; Co.</div>
                    <h1>A little something for you.</h1>
                    <p class="note">Choose something you love, plan a brilliant day out, or save it for later. This one’s entirely yours.</p>
                </div>
                <div class="from">With love, from Alex</div>
            </section>
            <aside class="details">
                <span class="label">Gift value</span>
                <div class="amount">${{ code.originalAmount|number_format(0) }}</div>
                <span class="label">Voucher code</span>
                <div class="code">{{ code.codeKey }}</div>
                <div class="expiry"><span class="label">Valid until</span>30 September 2027</div>
                <div class="fine-print">Redeem online at checkout. Any remaining balance stays on this code for your next order.</div>
            </aside>
        </article>
    </main>
</body>
</html>
TWIG;
file_put_contents($templateDir . '/voucher.twig', $voucherTemplate);

$cartTemplate = <<<'TWIG'
{# craft-screenshots: sample-frontend #}
{% set code = craft.giftVoucher.codes({ codeKey: 'WARM-WISHES-26', status: null }).one() %}
{% set subtotal = 184 %}
{% set redemption = min(code.currentAmount, subtotal) %}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gift voucher redemption</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 54px; background: #f5f2ec; color: #173a32; font-family: Arial, Helvetica, sans-serif; }
        .cart { width: min(980px, 100%); margin: 0 auto; overflow: hidden; border: 1px solid #dedbd3; border-radius: 18px; background: #fff; box-shadow: 0 22px 55px rgba(23, 58, 50, .12); }
        .header { padding: 30px 36px; border-bottom: 1px solid #e8e5de; font-family: Georgia, 'Times New Roman', serif; font-size: 32px; }
        .item { display: grid; grid-template-columns: 92px 1fr auto; align-items: center; gap: 24px; padding: 32px 36px; border-bottom: 1px solid #e8e5de; }
        .thumb { width: 92px; height: 92px; border-radius: 12px; background: linear-gradient(135deg, #d5e4dc, #739b89); }
        .title { margin-bottom: 8px; font-size: 20px; font-weight: 700; }
        .meta { color: #74817c; font-size: 14px; }
        .price { font-size: 20px; font-weight: 700; }
        .redeem { display: grid; grid-template-columns: 1fr auto; gap: 12px; padding: 28px 36px; background: #faf9f6; }
        input { min-width: 0; padding: 14px 16px; border: 1px solid #c9cec9; border-radius: 8px; color: #173a32; font: 700 15px ui-monospace, SFMono-Regular, Menlo, monospace; }
        button { padding: 0 24px; border: 0; border-radius: 8px; background: #173a32; color: white; font-weight: 700; }
        .summary { margin-left: auto; width: 430px; padding: 26px 36px 36px; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; }
        .voucher { color: #32715b; }
        .voucher small { display: block; margin-top: 4px; color: #769084; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .total { margin-top: 10px; padding-top: 20px; border-top: 1px solid #d9ddd9; font-size: 24px; font-weight: 800; }
    </style>
</head>
<body>
    <main class="cart">
        <div class="header">Your cart</div>
        <section class="item">
            <div class="thumb"></div>
            <div><div class="title">Handmade weekend bag</div><div class="meta">Natural canvas · Quantity 1</div></div>
            <div class="price">${{ subtotal|number_format(2) }}</div>
        </section>
        <form class="redeem"><input value="{{ code.codeKey }}" aria-label="Gift voucher code"><button type="button">Apply gift voucher</button></form>
        <section class="summary">
            <div class="row"><span>Subtotal</span><strong>${{ subtotal|number_format(2) }}</strong></div>
            <div class="row voucher"><span>Gift voucher<small>{{ code.codeKey }}</small></span><strong>−${{ redemption|number_format(2) }}</strong></div>
            <div class="row total"><span>Total</span><span>${{ (subtotal - redemption)|number_format(2) }}</span></div>
        </section>
    </main>
</body>
</html>
TWIG;
file_put_contents(Craft::getAlias('@templates') . '/screenshot-gift-voucher-cart.twig', $cartTemplate);

echo Json::encode([
    'cartRoute' => '/screenshot-gift-voucher-cart',
    'codeIndexRoute' => '/admin/gift-voucher/codes',
    'codeRoute' => '/admin/gift-voucher/codes/' . $featuredCode->id,
    'voucherRoute' => '/actions/gift-voucher/downloads/pdf?codeUid=' . $featuredCode->uid . '&site=' . $site->handle . '&format=plain',
    'codeCount' => $codeCount,
], JSON_THROW_ON_ERROR);
