import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedGiftVoucherFixture } from '../../../support/fixtures';

let cartRoute = '/';

export default defineScreenshotScenario({
    id: 'gift-voucher-docs-legacy-cart-redemption',
    output: 'docs/legacy/cart-redemption.png',
    route: () => cartRoute,
    viewport: { width: 1100, height: 760, deviceScaleFactor: 2 },
    async setup(context) {
        cartRoute = (await seedGiftVoucherFixture(context)).cartRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'WARM-WISHES-26' },
        { type: 'text', text: 'Apply gift voucher' },
    ],
    target: { type: 'selector', selector: '.cart' },
    caption: 'A project-owned cart showing a real Gift Voucher code applied at checkout.',
    intent: 'Retain the legacy front-end redemption subject while keeping the surrounding storefront explicitly illustrative.',
});
