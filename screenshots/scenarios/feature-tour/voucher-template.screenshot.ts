import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedGiftVoucherFixture } from '../../support/fixtures';

let voucherRoute = '/';

export default defineScreenshotScenario({
    id: 'gift-voucher-feature-tour-voucher-template',
    output: 'feature-tour/voucher-template.png',
    route: () => voucherRoute,
    viewport: { width: 1320, height: 720, deviceScaleFactor: 2 },
    async setup(context) {
        voucherRoute = (await seedGiftVoucherFixture(context)).voucherRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'A little something for you.' },
        { type: 'text', text: 'WARM-WISHES-26' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                document.body.style.padding = '0';
                document.body.style.background = 'transparent';
                document.querySelector('.voucher').style.boxShadow = 'none';
            `,
        },
    ],
    target: { type: 'clip', x: 120, y: 0, width: 1080, height: 520, omitBackground: true },
    caption: 'A polished project-owned voucher template rendered with a real Gift Voucher code.',
    intent: 'Illustrate the complete design freedom available through Gift Voucher PDF templates without implying a bundled theme.',
});
