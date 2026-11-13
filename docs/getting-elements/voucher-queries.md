# Voucher Queries

You can fetch vouchers in your templates or PHP code using **voucher queries**.

::: code
```twig Twig
{# Create a new voucher query #}
{% set myQuery = craft.giftVoucher.vouchers() %}
```

```php PHP
// Create a new voucher query
$myQuery = \verbb\giftvoucher\elements\Voucher::find();
```
:::

Once you’ve created a voucher query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Voucher](docs:reference/voucher) objects will be returned.

See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn how element queries work.

## Example

You can display the vouchers of a given type by doing the following.

1. Create a voucher query with `craft.giftVoucher.vouchers()`.
2. Set the [type](#type) and [limit](#limit) parameters on it.
3. Fetch the vouchers with `.all()`.
4. Loop through the vouchers using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their contents.

```twig
{# Create a voucher query with the 'type' and 'limit' parameters #}
{% set vouchersQuery = craft.giftVoucher.vouchers()
    .type('giftCards')
    .limit(10) %}

{# Fetch the vouchers #}
{% set vouchers = vouchersQuery.all() %}

{# Display their contents #}
{% for voucher in vouchers %}
    <p><a href="{{ voucher.url }}">{{ voucher.title }}</a></p>
{% endfor %}
```

## Parameters

Voucher queries support the following parameters.

::: reference
### `after`

Narrows the query results to vouchers posted on or after a date.

| Value | Fetches vouchers… |
| --- | --- |
| `'2026-04-01'` | posted on or after 2026-04-01. |
| a `DateTime` object | posted on or after the date represented by the object. |

::: code
```twig Twig
{# Fetch vouchers posted this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set vouchers = craft.giftVoucher.vouchers()
    .after(firstDayOfMonth)
    .all() %}
```

```php PHP
// Fetch vouchers posted this month
$firstDayOfMonth = new \DateTime('first day of this month');

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->after($firstDayOfMonth)
    ->all();
```
:::
:::

::: reference
### `asArray`

Causes the query to return matching vouchers as arrays of data, rather than [Voucher](docs:reference/voucher) objects.

::: code
```twig Twig
{# Fetch vouchers as arrays #}
{% set vouchers = craft.giftVoucher.vouchers()
    .asArray()
    .all() %}
```

```php PHP
// Fetch vouchers as arrays
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->asArray()
    ->all();
```
:::
:::

::: reference
### `availableForPurchase`

Narrows the query results to vouchers that are available for purchase.

::: code
```twig Twig
{# Fetch vouchers that are available for purchase #}
{% set vouchers = craft.giftVoucher.vouchers()
    .availableForPurchase()
    .all() %}
```

```php PHP
// Fetch vouchers that are available for purchase
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->availableForPurchase()
    ->all();
```
:::
:::

::: reference
### `before`

Narrows the query results to vouchers posted before a date.

| Value | Fetches vouchers… |
| --- | --- |
| `'2026-04-01'` | posted before 2026-04-01. |
| a `DateTime` object | posted before the date represented by the object. |

::: code
```twig Twig
{# Fetch vouchers posted before this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set vouchers = craft.giftVoucher.vouchers()
    .before(firstDayOfMonth)
    .all() %}
```

```php PHP
// Fetch vouchers posted before this month
$firstDayOfMonth = new \DateTime('first day of this month');

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->before($firstDayOfMonth)
    ->all();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the vouchers’ creation dates.

| Value | Fetches vouchers… |
| --- | --- |
| `'>= 2026-04-01'` | that were created on or after 2026-04-01. |
| `'< 2026-05-01'` | that were created before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were created between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch vouchers created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set vouchers = craft.giftVoucher.vouchers()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch vouchers created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the vouchers’ last-updated dates.

| Value | Fetches vouchers… |
| --- | --- |
| `'>= 2026-04-01'` | that were updated on or after 2026-04-01. |
| `'< 2026-05-01'` | that were updated before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were updated between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch vouchers updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set vouchers = craft.giftVoucher.vouchers()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch vouchers updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `editable`

Narrows the query results to only vouchers in voucher types the current user has permission to edit.

::: code
```twig Twig
{# Fetch vouchers the current user can edit #}
{% set vouchers = craft.giftVoucher.vouchers()
    .editable()
    .all() %}
```

```php PHP
// Fetch vouchers the current user can edit
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->editable()
    ->all();
```
:::
:::

::: reference
### `expiryDate`

Narrows the query results based on the vouchers’ expiry dates.

| Value | Fetches vouchers… |
| --- | --- |
| `'>= 2026-04-01'` | that expire on or after 2026-04-01. |
| `'< 2026-05-01'` | that expire before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that expire between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch vouchers expiring this month #}
{% set nextMonth = date('first day of next month')|atom %}

{% set vouchers = craft.giftVoucher.vouchers()
    .expiryDate("< #{nextMonth}")
    .all() %}
```

```php PHP
// Fetch vouchers expiring this month
$nextMonth = (new \DateTime('first day of next month'))->format(\DateTime::ATOM);

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
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
{# Fetch vouchers in a specific order #}
{% set vouchers = craft.giftVoucher.vouchers()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch vouchers in a specific order
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the vouchers’ IDs.

| Value | Fetches vouchers… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the voucher by its ID #}
{% set voucher = craft.giftVoucher.vouchers()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the voucher by its ID
$voucher = \verbb\giftvoucher\elements\Voucher::find()
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
{# Fetch vouchers in reverse #}
{% set vouchers = craft.giftVoucher.vouchers()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch vouchers in reverse
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->inReverse()
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of vouchers that should be returned.

::: code
```twig Twig
{# Fetch up to 10 vouchers #}
{% set vouchers = craft.giftVoucher.vouchers()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 vouchers
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many vouchers should be skipped in the results.

::: code
```twig Twig
{# Fetch all vouchers except for the first 3 #}
{% set vouchers = craft.giftVoucher.vouchers()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all vouchers except for the first 3
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the vouchers should be returned in. Vouchers are ordered by their creation date, newest first, by default.

::: code
```twig Twig
{# Fetch all vouchers in order of date created #}
{% set vouchers = craft.giftVoucher.vouchers()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all vouchers in order of date created
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `postDate`

Narrows the query results based on the vouchers’ post dates.

| Value | Fetches vouchers… |
| --- | --- |
| `'>= 2026-04-01'` | that were posted on or after 2026-04-01. |
| `'< 2026-05-01'` | that were posted before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were posted between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch vouchers posted last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set vouchers = craft.giftVoucher.vouchers()
    .postDate(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch vouchers posted last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->postDate(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `price`

Narrows the query results based on the vouchers’ prices.

| Value | Fetches vouchers… |
| --- | --- |
| `100` | with a price of 100. |
| `'>= 100'` | with a price of 100 or more. |
| `['and', '>= 50', '<= 200']` | with a price between 50 and 200. |

::: code
```twig Twig
{# Fetch vouchers that cost 100 or more #}
{% set vouchers = craft.giftVoucher.vouchers()
    .price('>= 100')
    .all() %}
```

```php PHP
// Fetch vouchers that cost 100 or more
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->price('>= 100')
    ->all();
```
:::
:::

::: reference
### `relatedTo`

Narrows the query results to only vouchers that are related to certain other elements. See [Relations](https://craftcms.com/docs/5.x/system/relations.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all vouchers that are related to myCategory #}
{% set vouchers = craft.giftVoucher.vouchers()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all vouchers that are related to myCategory
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->relatedTo($myCategory)
    ->all();
```
:::
:::

::: reference
### `search`

Narrows the query results to only vouchers that match a search query. See [Searching](https://craftcms.com/docs/5.x/system/searching.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all vouchers that match the search query #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{% set vouchers = craft.giftVoucher.vouchers()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Fetch all vouchers that match the search query
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `site`

Determines which site the vouchers should be queried in. The current site is used by default.

| Value | Fetches vouchers… |
| --- | --- |
| `'foo'` | from the site with a handle of `foo`. |
| `['foo', 'bar']` | from a site with a handle of `foo` or `bar`. |
| `'*'` | from any site. |
| a `craft\models\Site` object | from the site represented by the object. |

::: code
```twig Twig
{# Fetch vouchers from the Foo site #}
{% set vouchers = craft.giftVoucher.vouchers()
    .site('foo')
    .all() %}
```

```php PHP
// Fetch vouchers from the Foo site
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->site('foo')
    ->all();
```
:::
:::

::: reference
### `siteId`

Determines which site the vouchers should be queried in, per the site’s ID. The current site is used by default.

::: code
```twig Twig
{# Fetch vouchers from the site with an ID of 1 #}
{% set vouchers = craft.giftVoucher.vouchers()
    .siteId(1)
    .all() %}
```

```php PHP
// Fetch vouchers from the site with an ID of 1
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->siteId(1)
    ->all();
```
:::
:::

::: reference
### `sku`

Narrows the query results based on the vouchers’ SKUs.

| Value | Fetches vouchers… |
| --- | --- |
| `'foo'` | with a SKU of `foo`. |
| `'foo*'` | with a SKU that begins with `foo`. |
| `'*foo'` | with a SKU that ends with `foo`. |
| `'*foo*'` | with a SKU that contains `foo`. |
| `'not *foo*'` | with a SKU that doesn’t contain `foo`. |
| `['*foo*', '*bar*']` | with a SKU that contains `foo` or `bar`. |
| `['not', '*foo*', '*bar*']` | with a SKU that doesn’t contain `foo` or `bar`. |

::: code
```twig Twig
{# Fetch the voucher with a SKU of GIFT-50 #}
{% set voucher = craft.giftVoucher.vouchers()
    .sku('GIFT-50')
    .one() %}
```

```php PHP
// Fetch the voucher with a SKU of GIFT-50
$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->sku('GIFT-50')
    ->one();
```
:::
:::

::: reference
### `slug`

Narrows the query results based on the vouchers’ slugs.

| Value | Fetches vouchers… |
| --- | --- |
| `'foo'` | with a slug of `foo`. |
| `'foo*'` | with a slug that begins with `foo`. |
| `'*foo'` | with a slug that ends with `foo`. |
| `'*foo*'` | with a slug that contains `foo`. |
| `'not *foo*'` | with a slug that doesn’t contain `foo`. |
| `['*foo*', '*bar*']` | with a slug that contains `foo` or `bar`. |
| `['not', '*foo*', '*bar*']` | with a slug that doesn’t contain `foo` or `bar`. |

::: code
```twig Twig
{# Fetch the voucher with the slug in the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{% set voucher = craft.giftVoucher.vouchers()
    .slug(requestedSlug|literal)
    .one() %}
```

```php PHP
// Fetch the voucher with the slug in the URL
$requestedSlug = \Craft::$app->getRequest()->getSegment(3);

$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the vouchers’ statuses. Pass `null` to fetch vouchers regardless of their status.

| Value | Fetches vouchers… |
| --- | --- |
| `'live'` _(default)_ | that are live. |
| `'pending'` | that are pending (enabled with a Post Date in the future). |
| `'expired'` | that are expired (enabled with an Expiry Date in the past). |
| `'disabled'` | that are disabled. |
| `['live', 'pending']` | that are live or pending. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled vouchers #}
{% set vouchers = craft.giftVoucher.vouchers()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled vouchers
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->status('disabled')
    ->all();
```
:::
:::

::: reference
### `title`

Narrows the query results based on the vouchers’ titles.

| Value | Fetches vouchers… |
| --- | --- |
| `'foo'` | with a title of `foo`. |
| `'foo*'` | with a title that begins with `foo`. |
| `'*foo'` | with a title that ends with `foo`. |
| `'*foo*'` | with a title that contains `foo`. |
| `'not *foo*'` | with a title that doesn’t contain `foo`. |
| `['*foo*', '*bar*']` | with a title that contains `foo` or `bar`. |
| `['not', '*foo*', '*bar*']` | with a title that doesn’t contain `foo` or `bar`. |

::: code
```twig Twig
{# Fetch vouchers with a title that contains "Foo" #}
{% set vouchers = craft.giftVoucher.vouchers()
    .title('*Foo*')
    .all() %}
```

```php PHP
// Fetch vouchers with a title that contains "Foo"
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->title('*Foo*')
    ->all();
```
:::
:::

::: reference
### `type`

Narrows the query results based on the vouchers’ types, per the voucher types’ handles.

| Value | Fetches vouchers… |
| --- | --- |
| `'foo'` | of a type with a handle of `foo`. |
| `'not foo'` | not of a type with a handle of `foo`. |
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`. |
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`. |
| a `VoucherType` object | of a type represented by the object. |

::: code
```twig Twig
{# Fetch vouchers with a Gift Cards voucher type #}
{% set vouchers = craft.giftVoucher.vouchers()
    .type('giftCards')
    .all() %}
```

```php PHP
// Fetch vouchers with a Gift Cards voucher type
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->type('giftCards')
    ->all();
```
:::
:::

::: reference
### `typeId`

Narrows the query results based on the vouchers’ types, per the voucher types’ IDs.

| Value | Fetches vouchers… |
| --- | --- |
| `1` | of a type with an ID of 1. |
| `'not 1'` | not of a type with an ID of 1. |
| `[1, 2]` | of a type with an ID of 1 or 2. |
| `['not', 1, 2]` | not of a type with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch vouchers of the voucher type with an ID of 1 #}
{% set vouchers = craft.giftVoucher.vouchers()
    .typeId(1)
    .all() %}
```

```php PHP
// Fetch vouchers of the voucher type with an ID of 1
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->typeId(1)
    ->all();
```
:::
:::

::: reference
### `uid`

Narrows the query results based on the vouchers’ UIDs.

::: code
```twig Twig
{# Fetch the voucher by its UID #}
{% set voucher = craft.giftVoucher.vouchers()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the voucher by its UID
$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `uri`

Narrows the query results based on the vouchers’ URIs.

| Value | Fetches vouchers… |
| --- | --- |
| `'foo'` | with a URI of `foo`. |
| `'foo*'` | with a URI that begins with `foo`. |
| `'*foo'` | with a URI that ends with `foo`. |
| `'*foo*'` | with a URI that contains `foo`. |
| `'not *foo*'` | with a URI that doesn’t contain `foo`. |
| `['*foo*', '*bar*']` | with a URI that contains `foo` or `bar`. |
| `['not', '*foo*', '*bar*']` | with a URI that doesn’t contain `foo` or `bar`. |

::: code
```twig Twig
{# Fetch the voucher with the requested URI #}
{% set requestedUri = craft.app.request.getPathInfo() %}

{% set voucher = craft.giftVoucher.vouchers()
    .uri(requestedUri|literal)
    .one() %}
```

```php PHP
// Fetch the voucher with the requested URI
$requestedUri = \Craft::$app->getRequest()->getPathInfo();

$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```
:::
:::

::: reference
### `with`

Causes the query to return matching vouchers eager-loaded with related elements. See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch vouchers eager-loaded with the “Related” field’s relations #}
{% set vouchers = craft.giftVoucher.vouchers()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch vouchers eager-loaded with the “Related” field’s relations
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->with(['related'])
    ->all();
```
:::
:::
