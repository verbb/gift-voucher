import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type GiftVoucherFixture = {
    cartRoute: string;
    codeIndexRoute: string;
    codeRoute: string;
    voucherRoute: string;
    codeCount: number;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const typeSeedScript = readFileSync(join(supportDir, 'seed', 'seed-voucher-type.php'), 'utf8');
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-gift-voucher.php'), 'utf8');

/** Seed a current voucher catalogue, usable codes and a project-owned voucher template. */
export async function seedGiftVoucherFixture(context: ScreenshotSetupContext): Promise<GiftVoucherFixture> {
    await context.runCraftScript(typeSeedScript, { label: 'seed-gift-voucher-type' });
    const output = await context.runCraftScript(seedScript, { label: 'seed-gift-voucher' });
    const fixture = JSON.parse(output.trim()) as GiftVoucherFixture;

    if (!fixture.cartRoute || !fixture.codeIndexRoute || !fixture.codeRoute || !fixture.voucherRoute || fixture.codeCount < 5) {
        throw new Error(`Invalid Gift Voucher fixture payload: ${output}`);
    }

    return fixture;
}
