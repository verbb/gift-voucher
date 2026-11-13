# Permissions

Gift Voucher's permissions separate the vouchers you sell from the codes customers redeem. A merchandiser might manage the vouchers in one voucher type, while customer service staff look up and adjust codes when a customer asks about their balance. Set them in **Settings → Users → User Groups**, or on an individual user's **Permissions** tab.

**Access Gift Voucher** is listed under **General**, with Craft's other permissions for reaching each plugin's section. The others are listed under a **Gift Vouchers** heading.

| Permission | What It Allows |
| --- | --- |
| Access Gift Voucher | Reach the plugin's section in the control panel. |
| Manage vouchers | See **Vouchers** in the plugin's navigation, and add voucher codes to an incomplete Commerce order from its edit page, when they can also save that order. |
| Manage “{type}” vouchers | Create, edit, duplicate and delete vouchers of that voucher type, and preview and share them. |
| Manage codes | See **Voucher Codes**, and view, create, edit, delete and export codes. Release or retry a code's reservation on an order. |
| Bulk generate codes | Use **Bulk Generate Codes** to create many codes at once. |

Each voucher type gets its own **Manage “{type}” vouchers** permission, generated from the voucher types you've created in **Gift Voucher → Voucher Types** and nested under **Manage vouchers**. A new voucher type adds a new permission, which nobody but admins holds until you grant it. **Manage vouchers** on its own opens the Vouchers screen, but someone needs a voucher type's permission to see or change its vouchers.

**Bulk generate codes** needs **Manage codes** as well. Without **Manage codes**, codes are hidden everywhere in the control panel, including element indexes, relation fields and search, and are shown only as "Voucher code".

Admins can always do all of this. **Voucher Types** and **Settings** are for admins only, and **Settings** only appears where Craft's `allowAdminChanges` setting is on.

A voucher code is worth money to whoever holds it, so give **Manage codes** to the staff who handle customers' voucher questions, and keep **Bulk generate codes** for the people who run promotions. See [Voucher Codes](voucher-codes.md) for what's stored with each code.
