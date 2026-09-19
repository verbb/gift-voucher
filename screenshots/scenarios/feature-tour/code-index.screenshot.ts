import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedGiftVoucherFixture } from '../../support/fixtures';

let codeIndexRoute = '/admin/gift-voucher/codes';

export default defineScreenshotScenario({
    id: 'gift-voucher-feature-tour-code-index',
    output: 'feature-tour/code-index.png',
    route: () => codeIndexRoute,
    viewport: { width: 1800, height: 600, deviceScaleFactor: 2 },
    async setup(context) {
        codeIndexRoute = (await seedGiftVoucherFixture(context)).codeIndexRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Voucher Codes' },
        { type: 'text', text: 'WARM-WISHES-26' },
        { type: 'text', text: 'JUST-BECAUSE' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                document.activeElement?.blur();
                window.scrollTo(0, 0);

                const style = document.createElement('style');
                style.textContent = \`
                    #elements .tablepane,
                    #elements table { width: 1076px !important; max-width: 1076px !important; }
                    #elements table { table-layout: fixed !important; }
                    #elements table tr > *:nth-child(9) { display: none !important; }
                    #elements table tr > *:nth-child(1) { width: 72px !important; }
                    #elements table tr > *:nth-child(2) { width: 182px !important; }
                    #elements table tr > *:nth-child(3) { width: 136px !important; }
                    #elements table tr > *:nth-child(4) { width: 150px !important; }
                    #elements table tr > *:nth-child(5) { width: 103px !important; white-space: normal !important; }
                    #elements table tr > *:nth-child(6) { width: 147px !important; }
                    #elements table tr > *:nth-child(7) { width: 98px !important; }
                    #elements table tr > *:nth-child(8) { width: 188px !important; }
                \`;
                document.head.appendChild(style);
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: { type: 'anchoredClip', selector: '#elements table', x: 0, y: 0, width: 1076, height: 378 },
    caption: 'A populated index of active voucher codes, balances and expiry dates in Craft 5.',
    intent: 'Show the genuine current Gift Voucher code index with realistic sample data.',
});
