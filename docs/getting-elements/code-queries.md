# Code Queries

You can fetch codes in your templates or PHP code using **code queries**.

::: code
```twig Twig
{# Create a new code query #}
{% set myQuery = craft.giftVoucher.codes() %}
```

```php PHP
// Create a new code query
$myQuery = \verbb\giftvoucher\elements\Code::find();
```
:::

Once you’ve created a code query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Code](docs:reference/code) objects will be returned.

See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn how element queries work.

## Example

You can display the codes bought in an order by doing the following.

1. Create a code query with `craft.giftVoucher.codes()`.
2. Set the [orderId](#orderid) parameter on it.
3. Fetch the codes with `.all()`.
4. Loop through the codes using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their contents.

This example assumes an `order` variable, such as on an order confirmation template.

```twig
{# Create a code query with the 'orderId' parameter #}
{% set codes = craft.giftVoucher.codes()
    .orderId(order.id)
    .all() %}

{# Display their contents #}
{% for code in codes %}
    <p>{{ code.codeKey }}, {{ code.currentAmount|commerceCurrency(order.currency) }}</p>
{% endfor %}
```

## Parameters

Code queries support the following parameters.

::: reference
### `after`

Narrows the query results to codes created on or after a date.

| Value | Fetches codes… |
| --- | --- |
| `'2026-04-01'` | created on or after 2026-04-01. |
| a `DateTime` object | created on or after the date represented by the object. |

::: code
```twig Twig
{# Fetch codes created this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set codes = craft.giftVoucher.codes()
    .after(firstDayOfMonth)
    .all() %}
```

```php PHP
// Fetch codes created this month
$firstDayOfMonth = new \DateTime('first day of this month');

$codes = \verbb\giftvoucher\elements\Code::find()
    ->after($firstDayOfMonth)
    ->all();
```
:::
:::

::: reference
### `asArray`

Causes the query to return matching codes as arrays of data, rather than [Code](docs:reference/code) objects.

::: code
```twig Twig
{# Fetch codes as arrays #}
{% set codes = craft.giftVoucher.codes()
    .asArray()
    .all() %}
```

```php PHP
// Fetch codes as arrays
$codes = \verbb\giftvoucher\elements\Code::find()
    ->asArray()
    ->all();
```
:::
:::

::: reference
### `before`

Narrows the query results to codes created before a date.

| Value | Fetches codes… |
| --- | --- |
| `'2026-04-01'` | created before 2026-04-01. |
| a `DateTime` object | created before the date represented by the object. |

::: code
```twig Twig
{# Fetch codes created before this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set codes = craft.giftVoucher.codes()
    .before(firstDayOfMonth)
    .all() %}
```

```php PHP
// Fetch codes created before this month
$firstDayOfMonth = new \DateTime('first day of this month');

$codes = \verbb\giftvoucher\elements\Code::find()
    ->before($firstDayOfMonth)
    ->all();
```
:::
:::

::: reference
### `codeKey`

Narrows the query results to the code with this code key, which is the code a customer enters at checkout. The key must match exactly, so wildcards such as `*` aren’t supported.

::: code
```twig Twig
{# Fetch the code a customer entered #}
{% set enteredCode = craft.app.request.getParam('voucherCode') %}

{% set code = craft.giftVoucher.codes()
    .codeKey(enteredCode)
    .one() %}
```

```php PHP
// Fetch the code a customer entered
$enteredCode = \Craft::$app->getRequest()->getParam('voucherCode');

$code = \verbb\giftvoucher\elements\Code::find()
    ->codeKey($enteredCode)
    ->one();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the codes’ creation dates.

| Value | Fetches codes… |
| --- | --- |
| `'>= 2026-04-01'` | that were created on or after 2026-04-01. |
| `'< 2026-05-01'` | that were created before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were created between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch codes created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set codes = craft.giftVoucher.codes()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch codes created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$codes = \verbb\giftvoucher\elements\Code::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the codes’ last-updated dates.

| Value | Fetches codes… |
| --- | --- |
| `'>= 2026-04-01'` | that were updated on or after 2026-04-01. |
| `'< 2026-05-01'` | that were updated before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were updated between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch codes updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set codes = craft.giftVoucher.codes()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch codes updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$codes = \verbb\giftvoucher\elements\Code::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `expiryDate`

Narrows the query results based on the codes’ expiry dates.

| Value | Fetches codes… |
| --- | --- |
| `'>= 2026-04-01'` | that expire on or after 2026-04-01. |
| `'< 2026-05-01'` | that expire before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that expire between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch codes expiring this month #}
{% set nextMonth = date('first day of next month')|atom %}

{% set codes = craft.giftVoucher.codes()
    .expiryDate("< #{nextMonth}")
    .all() %}
```

```php PHP
// Fetch codes expiring this month
$nextMonth = (new \DateTime('first day of next month'))->format(\DateTime::ATOM);

$codes = \verbb\giftvoucher\elements\Code::find()
    ->expiryDate("< {$nextMonth}")
    ->all();
```
:::
:::

::: reference
### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch codes in a specific order #}
{% set codes = craft.giftVoucher.codes()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch codes in a specific order
$codes = \verbb\giftvoucher\elements\Code::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the codes’ IDs.

| Value | Fetches codes… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the code by its ID #}
{% set code = craft.giftVoucher.codes()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the code by its ID
$code = \verbb\giftvoucher\elements\Code::find()
    ->id(1)
    ->one();
```
:::

::: tip
This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::
:::

::: reference
### `inReverse`

Causes the query results to be returned in reverse order.

::: code
```twig Twig
{# Fetch codes in reverse #}
{% set codes = craft.giftVoucher.codes()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch codes in reverse
$codes = \verbb\giftvoucher\elements\Code::find()
    ->inReverse()
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of codes that should be returned.

::: code
```twig Twig
{# Fetch up to 10 codes #}
{% set codes = craft.giftVoucher.codes()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 codes
$codes = \verbb\giftvoucher\elements\Code::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `lineItemId`

Narrows the query results based on the Commerce line items the codes were bought in, per the line items’ IDs.

| Value | Fetches codes… |
| --- | --- |
| `1` | bought in a line item with an ID of 1. |
| `'not 1'` | not bought in a line item with an ID of 1. |
| `[1, 2]` | bought in a line item with an ID of 1 or 2. |
| `['not', 1, 2]` | not bought in a line item with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch codes for a line item #}
{% set codes = craft.giftVoucher.codes()
    .lineItemId(lineItem.id)
    .all() %}
```

```php PHP
// Fetch codes for a line item
$codes = \verbb\giftvoucher\elements\Code::find()
    ->lineItemId($lineItem->id)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many codes should be skipped in the results.

::: code
```twig Twig
{# Fetch all codes except for the first 3 #}
{% set codes = craft.giftVoucher.codes()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all codes except for the first 3
$codes = \verbb\giftvoucher\elements\Code::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the codes should be returned in. Codes are ordered by their creation date, newest first, by default.

::: code
```twig Twig
{# Fetch all codes in order of date created #}
{% set codes = craft.giftVoucher.codes()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all codes in order of date created
$codes = \verbb\giftvoucher\elements\Code::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `orderId`

Narrows the query results based on the Commerce orders the codes were bought in, per the orders’ IDs.

| Value | Fetches codes… |
| --- | --- |
| `1` | bought in an order with an ID of 1. |
| `'not 1'` | not bought in an order with an ID of 1. |
| `[1, 2]` | bought in an order with an ID of 1 or 2. |
| `['not', 1, 2]` | not bought in an order with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch codes for an order #}
{% set codes = craft.giftVoucher.codes()
    .orderId(order.id)
    .all() %}
```

```php PHP
// Fetch codes for an order
$codes = \verbb\giftvoucher\elements\Code::find()
    ->orderId($order->id)
    ->all();
```
:::
:::

::: reference
### `relatedTo`

Narrows the query results to only codes that are related to certain other elements. See [Relations](https://craftcms.com/docs/5.x/system/relations.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all codes that are related to myCategory #}
{% set codes = craft.giftVoucher.codes()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all codes that are related to myCategory
$codes = \verbb\giftvoucher\elements\Code::find()
    ->relatedTo($myCategory)
    ->all();
```
:::
:::

::: reference
### `search`

Narrows the query results to only codes that match a search query. See [Searching](https://craftcms.com/docs/5.x/system/searching.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all codes that match the search query #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{% set codes = craft.giftVoucher.codes()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Fetch all codes that match the search query
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

$codes = \verbb\giftvoucher\elements\Code::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the codes’ statuses. Pass `null` to fetch codes regardless of their status.

| Value | Fetches codes… |
| --- | --- |
| `'enabled'` _(default)_ | that are enabled. |
| `'disabled'` | that are disabled. |
| `['enabled', 'disabled']` | that are enabled or disabled. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled codes #}
{% set codes = craft.giftVoucher.codes()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled codes
$codes = \verbb\giftvoucher\elements\Code::find()
    ->status('disabled')
    ->all();
```
:::
:::

::: reference
### `type`

Narrows the query results based on the voucher types of the codes’ vouchers, per the voucher types’ handles.

| Value | Fetches codes… |
| --- | --- |
| `'foo'` | for a voucher type with a handle of `foo`. |
| `['foo', 'bar']` | for a voucher type with a handle of `foo` or `bar`. |
| a `VoucherType` object | for the voucher type represented by the object. |

::: code
```twig Twig
{# Fetch codes for vouchers with a Gift Cards voucher type #}
{% set codes = craft.giftVoucher.codes()
    .type('giftCards')
    .all() %}
```

```php PHP
// Fetch codes for vouchers with a Gift Cards voucher type
$codes = \verbb\giftvoucher\elements\Code::find()
    ->type('giftCards')
    ->all();
```
:::
:::

::: reference
### `typeId`

Narrows the query results based on the voucher types of the codes’ vouchers, per the voucher types’ IDs.

| Value | Fetches codes… |
| --- | --- |
| `1` | for a voucher type with an ID of 1. |
| `'not 1'` | not for a voucher type with an ID of 1. |
| `[1, 2]` | for a voucher type with an ID of 1 or 2. |
| `['not', 1, 2]` | not for a voucher type with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch codes for the voucher type with an ID of 1 #}
{% set codes = craft.giftVoucher.codes()
    .typeId(1)
    .all() %}
```

```php PHP
// Fetch codes for the voucher type with an ID of 1
$codes = \verbb\giftvoucher\elements\Code::find()
    ->typeId(1)
    ->all();
```
:::
:::

::: reference
### `uid`

Narrows the query results based on the codes’ UIDs.

::: code
```twig Twig
{# Fetch the code by its UID #}
{% set code = craft.giftVoucher.codes()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the code by its UID
$code = \verbb\giftvoucher\elements\Code::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `voucher`

Narrows the query results to codes for a voucher, per the voucher or its SKU.

| Value | Fetches codes… |
| --- | --- |
| `'GIFT-50'` | for a voucher with a SKU of `GIFT-50`. |
| a `Voucher` object | for the voucher represented by the object. |

::: code
```twig Twig
{# Fetch codes for the voucher with a SKU of GIFT-50 #}
{% set codes = craft.giftVoucher.codes()
    .voucher('GIFT-50')
    .all() %}
```

```php PHP
// Fetch codes for the voucher with a SKU of GIFT-50
$codes = \verbb\giftvoucher\elements\Code::find()
    ->voucher('GIFT-50')
    ->all();
```
:::
:::

::: reference
### `voucherId`

Narrows the query results based on the codes’ vouchers, per the vouchers’ IDs.

| Value | Fetches codes… |
| --- | --- |
| `1` | for a voucher with an ID of 1. |
| `'not 1'` | not for a voucher with an ID of 1. |
| `[1, 2]` | for a voucher with an ID of 1 or 2. |
| `['not', 1, 2]` | not for a voucher with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch codes for the voucher with an ID of 1 #}
{% set codes = craft.giftVoucher.codes()
    .voucherId(1)
    .all() %}
```

```php PHP
// Fetch codes for the voucher with an ID of 1
$codes = \verbb\giftvoucher\elements\Code::find()
    ->voucherId(1)
    ->all();
```
:::
:::

::: reference
### `with`

Causes the query to return matching codes eager-loaded with related elements. See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch codes eager-loaded with the “Related” field’s relations #}
{% set codes = craft.giftVoucher.codes()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch codes eager-loaded with the “Related” field’s relations
$codes = \verbb\giftvoucher\elements\Code::find()
    ->with(['related'])
    ->all();
```
:::
:::
