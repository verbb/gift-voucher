# Voucher Queries
You can fetch vouchers in your templates or PHP code using **voucher queries**.

:::code
```twig Twig
{# Create a new voucher query #}
{% set myQuery = craft.giftVoucher.vouchers() %}
```

```php PHP
// Create a new voucher query
$myQuery = \verbb\giftvoucher\elements\Voucher::find();
```
:::

Once you’ve created a voucher query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Voucher](docs:developers/voucher) objects will be returned.

:::tip
See Introduction to [Element Queries](https://docs.craftcms.com/v3/dev/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example
We can display vouchers for a given type by doing the following:

1. Create a voucher query with `craft.giftVoucher.vouchers()`.
2. Set the [type](#type) a [limit](#limit) parameters on it.
3. Fetch all vouchers with `.all()` and output.
4. Loop through the vouchers using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to output the contents.

```twig
{# Create a vouchers query with the 'type' and 'limit' parameters #}
{% set vouchersQuery = craft.giftVoucher.vouchers()
    .type('giftCards')
    .limit(10) %}

{# Fetch the Vouchers #}
{% set vouchers = vouchersQuery.all() %}

{# Display their contents #}
{% for voucher in vouchers %}
    <p>{{ voucher.name }}</p>
{% endfor %}
```

## Parameters
Voucher queries support the following parameters:

<!-- BEGIN PARAMS -->

### `after`

Narrows the query results to only vouchers that were posted on or after a certain date.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'2018-04-01'` | that were posted after 2018-04-01.
| a [DateTime](http://php.net/class.datetime) object | that were posted after the date represented by the object.

::: code
```twig
{# Fetch vouchers posted this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set vouchers = craft.giftVoucher.vouchers()
    .after(firstDayOfMonth)
    .all() %}
```

```php
// Fetch vouchers posted this month
$firstDayOfMonth = new \DateTime('first day of this month');

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->after($firstDayOfMonth)
    ->all();
```
:::



### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.

::: code
```twig
{# Fetch all vouchers, regardless of status #}
{% set vouchers = craft.giftVoucher.vouchers()
    .anyStatus()
    .all() %}
```

```php
// Fetch all vouchers, regardless of status
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->anyStatus()
    ->all();
```
:::



### `asArray`

Causes the query to return matching vouchers as arrays of data, rather than [Voucher](docs:developers/voucher) objects.

::: code
```twig
{# Fetch vouchers as arrays #}
{% set vouchers = craft.giftVoucher.vouchers()
    .asArray()
    .all() %}
```

```php
// Fetch vouchers as arrays
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->asArray()
    ->all();
```
:::



### `before`

Narrows the query results to only vouchers that were posted before a certain date.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'2018-04-01'` | that were posted before 2018-04-01.
| a [DateTime](http://php.net/class.datetime) object | that were posted before the date represented by the object.

::: code
```twig
{# Fetch vouchers posted before this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set vouchers = craft.giftVoucher.vouchers()
    .before(firstDayOfMonth)
    .all() %}
```

```php
// Fetch vouchers posted before this month
$firstDayOfMonth = new \DateTime('first day of this month');

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->before($firstDayOfMonth)
    ->all();
```
:::



### `dateCreated`

Narrows the query results based on the vouchers’ creation dates.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch vouchers created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set vouchers = craft.giftVoucher.vouchers()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch vouchers created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`

Narrows the query results based on the vouchers’ last-updated dates.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch vouchers updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set vouchers = craft.giftVoucher.vouchers()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch vouchers updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig
{# Fetch vouchers in a specific order #}
{% set vouchers = craft.giftVoucher.vouchers()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch vouchers in a specific order
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::



### `id`

Narrows the query results based on the vouchers’ IDs.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

::: code
```twig
{# Fetch the voucher by its ID #}
{% set voucher = craft.giftVoucher.vouchers()
    .id(1)
    .one() %}
```

```php
// Fetch the voucher by its ID
$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->id(1)
    ->one();
```
:::

::: tip
This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::



### `inReverse`

Causes the query results to be returned in reverse order.

::: code
```twig
{# Fetch vouchers in reverse #}
{% set vouchers = craft.giftVoucher.vouchers()
    .inReverse()
    .all() %}
```

```php
// Fetch vouchers in reverse
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->inReverse()
    ->all();
```
:::



### `limit`

Determines the number of vouchers that should be returned.

::: code
```twig
{# Fetch up to 10 vouchers  #}
{% set vouchers = craft.giftVoucher.vouchers()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 vouchers
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->limit(10)
    ->all();
```
:::



### `offset`

Determines how many vouchers should be skipped in the results.

::: code
```twig
{# Fetch all vouchers except for the first 3 #}
{% set vouchers = craft.giftVoucher.vouchers()
    .offset(3)
    .all() %}
```

```php
// Fetch all vouchers except for the first 3
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->offset(3)
    ->all();
```
:::



### `orderBy`

Determines the order that the vouchers should be returned in.

::: code
```twig
{# Fetch all vouchers in order of date created #}
{% set vouchers = craft.giftVoucher.vouchers()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php
// Fetch all vouchers in order of date created
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::



### `price`

Narrows the query results based on the vouchers’ price.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `100` | with a price of 100.
| `'>= 100'` | with a price of at least 100.
| `'< 100'` | with a price of less than 100.



### `relatedTo`

Narrows the query results to only vouchers that are related to certain other elements.

See [Relations](https://docs.craftcms.com/v3/relations.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Fetch all vouchers that are related to myCategory #}
{% set vouchers = craft.giftVoucher.vouchers()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all vouchers that are related to $myCategory
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->relatedTo($myCategory)
    ->all();
```
:::



### `search`

Narrows the query results to only vouchers that match a search query.

See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

{# Fetch all vouchers that match the search query #}
{% set vouchers = craft.giftVoucher.vouchers()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all vouchers that match the search query
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->search($searchQuery)
    ->all();
```
:::



### `sku`

Narrows the query results based on the vouchers’ SKUs.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'foo'` | with a SKU of `foo`.
| `'foo*'` | with a SKU that begins with `foo`.
| `'*foo'` | with a SKU that ends with `foo`.
| `'*foo*'` | with a SKU that contains `foo`.
| `'not *foo*'` | with a SKU that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a SKU that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a SKU that doesn’t contain `foo` or `bar`.

::: code
```twig
{# Fetch the voucher with an sku #}
{% set voucher = craft.giftVoucher.vouchers()
    .sku('some-sku')
    .one() %}
```

```php
// Fetch the voucher with a sku
$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->sku('some-sku')
    ->one();
```
:::



### `slug`

Narrows the query results based on the vouchers’ slugs.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'foo'` | with a slug of `foo`.
| `'foo*'` | with a slug that begins with `foo`.
| `'*foo'` | with a slug that ends with `foo`.
| `'*foo*'` | with a slug that contains `foo`.
| `'not *foo*'` | with a slug that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a slug that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a slug that doesn’t contain `foo` or `bar`.

::: code
```twig
{# Get the requested voucher slug from the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{# Fetch the voucher with that slug #}
{% set voucher = craft.giftVoucher.vouchers()
    .slug(requestedSlug|literal)
    .one() %}
```

```php
// Get the requested voucher slug from the URL
$requestedSlug = \Craft::$app->request->getSegment(3);

// Fetch the voucher with that slug
$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```
:::



### `status`

Narrows the query results based on the vouchers’ statuses.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'live'` _(default)_ | that are live.
| `'pending'` | that are pending (enabled with a Post Date in the future).
| `'expired'` | that are expired (enabled with an Expiry Date in the past).
| `'disabled'` | that are disabled.
| `['live', 'pending']` | that are live or pending.

::: code
```twig
{# Fetch disabled vouchers #}
{% set vouchers = {twig-function}
    .status('disabled')
    .all() %}
```

```php
// Fetch disabled vouchers
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->status('disabled')
    ->all();
```
:::



### `title`

Narrows the query results based on the vouchers’ titles.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'Foo'` | with a title of `Foo`.
| `'Foo*'` | with a title that begins with `Foo`.
| `'*Foo'` | with a title that ends with `Foo`.
| `'*Foo*'` | with a title that contains `Foo`.
| `'not *Foo*'` | with a title that doesn’t contain `Foo`.
| `['*Foo*', '*Bar*'` | with a title that contains `Foo` or `Bar`.
| `['not', '*Foo*', '*Bar*']` | with a title that doesn’t contain `Foo` or `Bar`.

::: code
```twig
{# Fetch vouchers with a title that contains "Foo" #}
{% set vouchers = craft.giftVoucher.vouchers()
    .title('*Foo*')
    .all() %}
```

```php
// Fetch vouchers with a title that contains "Foo"
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->title('*Foo*')
    ->all();
```
:::



### `type`

Narrows the query results based on the vouchers’ types.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'foo'` | of a type with a handle of `foo`.
| `'not foo'` | not of a type with a handle of `foo`.
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
| a Voucher Type object | of a type represented by the object.

::: code
```twig
{# Fetch vouchers with a Foo voucher type #}
{% set vouchers = craft.giftVoucher.vouchers()
    .type('foo')
    .all() %}
```

```php
// Fetch vouchers with a Foo voucher type
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->type('foo')
    ->all();
```
:::



### `typeId`

Narrows the query results based on the vouchers’ types, per the types’ IDs.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `1` | of a type with an ID of 1.
| `'not 1'` | not of a type with an ID of 1.
| `[1, 2]` | of a type with an ID of 1 or 2.
| `['not', 1, 2]` | not of a type with an ID of 1 or 2.

::: code
```twig
{# Fetch vouchers of the voucher type with an ID of 1 #}
{% set vouchers = craft.giftVoucher.vouchers()
    .typeId(1)
    .all() %}
```

```php
// Fetch vouchers of the voucher type with an ID of 1
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->typeId(1)
    ->all();
```
:::



### `uid`

Narrows the query results based on the vouchers’ UIDs.

::: code
```twig
{# Fetch the voucher by its UID #}
{% set voucher = craft.giftVoucher.vouchers()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the voucher by its UID
$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::



### `uri`

Narrows the query results based on the vouchers’ URIs.

Possible values include:

| Value | Fetches vouchers…
| - | -
| `'foo'` | with a URI of `foo`.
| `'foo*'` | with a URI that begins with `foo`.
| `'*foo'` | with a URI that ends with `foo`.
| `'*foo*'` | with a URI that contains `foo`.
| `'not *foo*'` | with a URI that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a URI that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a URI that doesn’t contain `foo` or `bar`.

::: code
```twig
{# Get the requested URI #}
{% set requestedUri = craft.app.request.getPathInfo() %}

{# Fetch the voucher with that URI #}
{% set voucher = craft.giftVoucher.vouchers()
    .uri(requestedUri|literal)
    .one() %}
```

```php
// Get the requested URI
$requestedUri = \Craft::$app->request->getPathInfo();

// Fetch the voucher with that URI
$voucher = \verbb\giftvoucher\elements\Voucher::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```
:::



### `with`

Causes the query to return matching vouchers eager-loaded with related elements.

See [Eager-Loading Elements](https://docs.craftcms.com/v3/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Fetch vouchers eager-loaded with the "Related" field’s relations #}
{% set vouchers = craft.giftVoucher.vouchers()
    .with(['related'])
    .all() %}
```

```php
// Fetch vouchers eager-loaded with the "Related" field’s relations
$vouchers = \verbb\giftvoucher\elements\Voucher::find()
    ->with(['related'])
    ->all();
```
:::


<!-- END PARAMS -->
