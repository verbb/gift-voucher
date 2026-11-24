// ==========================================================================

// Gift Voucher Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

// Plugin Kit's components load only when the page uses them, each with the kit's stylesheet for its tokens.

// The landing page's setup checklist until the first code is issued.
if (document.querySelector('pk-setup-checklist')) {
    void Promise.all([import('@verbb/plugin-kit-web/plugin-kit.css'), import('@verbb/plugin-kit-web/components/setup-checklist.js')]);
}

// Empty states, such as Settings › Emails before Commerce has an email.
if (document.querySelector('pk-state-panel')) {
    void Promise.all([import('@verbb/plugin-kit-web/plugin-kit.css'), import('@verbb/plugin-kit-web/components/state-panel.js')]);
}
