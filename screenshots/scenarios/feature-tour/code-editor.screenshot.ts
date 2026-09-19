import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedGiftVoucherFixture } from '../../support/fixtures';

let codeRoute = '/admin/gift-voucher/codes';

export default defineScreenshotScenario({
    id: 'gift-voucher-feature-tour-code-editor',
    output: 'feature-tour/code-editor.png',
    route: () => codeRoute,
    viewport: { width: 1240, height: 720, deviceScaleFactor: 2 },
    async setup(context) {
        codeRoute = (await seedGiftVoucherFixture(context)).codeRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'WARM-WISHES-26' },
        { type: 'text', text: 'Original Amount' },
        { type: 'text', text: 'Current Amount' },
        { type: 'text', text: 'A little something' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                document.activeElement?.blur();
                window.scrollTo(0, 0);
                document.querySelector('#main-container').style.paddingTop = '24px';
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: { type: 'anchoredClip', selector: '#main-container', x: 0, y: 0, width: 1010, height: 558 },
    caption: 'A voucher code retaining its original value while showing the remaining balance and expiry date.',
    intent: 'Show the real Gift Voucher code editor and the controls staff use to manage a code.',
});
