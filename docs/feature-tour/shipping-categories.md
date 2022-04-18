# Shipping Categories
It's a common scenario for your online store to sell both physical products to be shipped, and digital products (like Gift Vouchers) that don't require shipping. Managing shipping options for customers to pick depending on what's in their cart can be handled through Commerce's [Shipping Categories](https://craftcms.com/docs/commerce/3.x/shipping.html#shipping-categories).

Before we begin, let's consider a few scenarios.

**Scenario 1:**
The customer only has physical products in their cart. Shipping options should be shown as normal.

**Scenario 2:**
The customer has a gift voucher and a physical product in their cart. Shipping options are shown as normal, despite the gift voucher not needing actual shipping - shipping is still required for all other physical items in the cart.

**Scenario 3:**
The customer has only gift vouchers in their cart. We should only show a "Download" shipping option for the customer to pick, which would be free.

To deal with the above scenarios, we'll set up two Shipping Categories, "General" and "Download". Go to Commerce → Shipping → Shipping Categories and create these categories. General should be the default for products on your store.

Next, ensure every Gift Voucher element you've created is assigned to the "Download" category, by selecting it as the "Shipping Category" when editing the voucher.

Then, we need to assign logic to the Shipping Methods used in our store to use these categories, within certain circumstances. For our example, let's say we have a "$10 Standard Shipping" and "Download" Shipping Method created, where we charge $10 as a flat-rate to any regular orders, but want to provide free shipping when someone is only purchasing gift vouchers.

Go to Commerce → Shipping → Shipping Methods and select "$10 Standard Delivery" (or whatever your method is called). Click the shipping rule required (if you have multiple, you'll need to apply this to all shipping rules) to edit that rule. In the Conditions tab, ensure the following is set for "Shipping Category Conditions":

| Name | Condition
| - | -
| General | Require
| Download | Allow

Next, go back to Commerce → Shipping → Shipping Methods and select "Download", then create or edit the shipping rule. In the Conditions tab, ensure the following is set for "Shipping Category Conditions":

| Name | Condition
| - | -
| General | Disallow
| Download | Require

We've now successfully setup the logic for showing "Download" as the only shipping option when only Gift Vouchers are in a customers cart.
