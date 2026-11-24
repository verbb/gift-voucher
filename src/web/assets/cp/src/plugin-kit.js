// ==========================================================================

// Gift Voucher Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

// Plugin Kit's stylesheet loads with the page, in <head>, so its FOUCE rules hide pk-* elements until their components load.
import '@verbb/plugin-kit-web/plugin-kit.css';

// Plugin Kit's components load only when the page uses them.

// The landing page's setup checklist until the first code is issued.
if (document.querySelector('pk-setup-checklist')) {
    void import('@verbb/plugin-kit-web/components/setup-checklist.js');
}

// Empty states, such as Settings › Emails before Commerce has an email.
if (document.querySelector('pk-state-panel')) {
    void import('@verbb/plugin-kit-web/components/state-panel.js');
}
