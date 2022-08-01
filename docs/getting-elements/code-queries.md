# Code Queries
You can fetch codes in your templates or PHP code using **code queries**.

:::code
```twig Twig
{# Create a new code query #}
{% set myQuery = craft.giftVoucher.codes() %}
```

```php PHP
// Create a new code query
$myQuery = \verbb\giftvoucher\elements\Code::find();
```
:::

Once you’ve created a code query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Code](docs:developers/code) objects will be returned.

:::tip
See Introduction to [Element Queries](https://craftcms.com/docs/4.x/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example
We can display codes for a given order by doing the following:

1. Create a code query with `craft.giftVoucher.codes()`.
2. Set the [orderId](#orderId) a [limit](#limit) parameters on it.
3. Fetch all codes with `.all()` and output.
4. Loop through the codes using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to output the contents.

```twig
{# Create a codes query with the 'orderId' and 'limit' parameters #}
{% set codesQuery = craft.giftVoucher.codes()
    .orderId(order.id)
    .limit(10) %}

{# Fetch the Codes #}
{% set codes = codesQuery.all() %}

{# Display their contents #}
{% for code in codes %}
    <p>{{ code.codeKey }}</p>
{% endfor %}
```

## Parameters
Code queries support the following parameters:

<!-- BEGIN PARAMS -->

### `after`

Narrows the query results to only codes that were posted on or after a certain date.

Possible values include:

| Value | Fetches codes…
| - | -
| `'2018-04-01'` | that were posted after 2018-04-01.
| a [DateTime](http://php.net/class.datetime) object | that were posted after the date represented by the object.

::: code
```twig Twig
{# Fetch codes posted this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set codes = craft.giftVoucher.codes()
    .after(firstDayOfMonth)
    .all() %}
```

```php PHP
// Fetch codes posted this month
$firstDayOfMonth = new \DateTime('first day of this month');

$codes = \verbb\giftvoucher\elements\Code::find()
    ->after($firstDayOfMonth)
    ->all();
```
:::



### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v4/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v4/craft-elements-db-elementquery.html#method-enabledforsite) parameters.

::: code
```twig Twig
{# Fetch all codes, regardless of status #}
{% set codes = craft.giftVoucher.codes()
    .anyStatus()
    .all() %}
```

```php PHP
// Fetch all codes, regardless of status
$codes = \verbb\giftvoucher\elements\Code::find()
    ->anyStatus()
    ->all();
```
:::



### `asArray`

Causes the query to return matching codes as arrays of data, rather than [Code](docs:developers/code) objects.

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



### `before`

Narrows the query results to only codes that were posted before a certain date.

Possible values include:

| Value | Fetches codes…
| - | -
| `'2018-04-01'` | that were posted before 2018-04-01.
| a [DateTime](http://php.net/class.datetime) object | that were posted before the date represented by the object.

::: code
```twig Twig
{# Fetch codes posted before this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set codes = craft.giftVoucher.codes()
    .before(firstDayOfMonth)
    .all() %}
```

```php PHP
// Fetch codes posted before this month
$firstDayOfMonth = new \DateTime('first day of this month');

$codes = \verbb\giftvoucher\elements\Code::find()
    ->before($firstDayOfMonth)
    ->all();
```
:::



### `codeKey`

Narrows the query results based on the codes’ code key.

::: code
```twig Twig
{# Fetch the code by its code key #}
{% set code = craft.giftVoucher.codes()
    .codeKey('xxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the code by its code key
$code = \verbb\giftvoucher\elements\Code::find()
    ->codeKey('xxxxxxxx')
    ->one();
```
:::



### `dateCreated`

Narrows the query results based on the codes’ creation dates.

Possible values include:

| Value | Fetches codes…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch codes created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set codes = craft.giftVoucher.codes()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch codes created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$codes = \verbb\giftvoucher\elements\Code::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`

Narrows the query results based on the codes’ last-updated dates.

Possible values include:

| Value | Fetches codes…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

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
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$codes = \verbb\giftvoucher\elements\Code::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



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



### `id`

Narrows the query results based on the codes’ IDs.

Possible values include:

| Value | Fetches codes…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

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



### `limit`

Determines the number of codes that should be returned.

::: code
```twig Twig
{# Fetch up to 10 codes  #}
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



### `lineItemId`

Narrows the query results based on the codes’ Line Item ID.

Possible values include:

| Value | Fetches codes…
| - | -
| `1` | of a line item with an ID of 1.
| `'not 1'` | not of a line item with an ID of 1.
| `[1, 2]` | of a line item with an ID of 1 or 2.
| `['not', 1, 2]` | not of a line item with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch codes for an line item with an ID of 1 #}
{% set codes = craft.giftVoucher.codes()
    .lineItemId(1)
    .all() %}
```

```php PHP
// Fetch codes for a line item with an ID of 1
$codes = \verbb\giftvoucher\elements\Code::find()
    ->lineItemId(1)
    ->all();
```
:::



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



### `orderBy`

Determines the order that the codes should be returned in.

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



### `orderId`

Narrows the query results based on the codes’ Order ID.

Possible values include:

| Value | Fetches codes…
| - | -
| `1` | of a order with an ID of 1.
| `'not 1'` | not of an order with an ID of 1.
| `[1, 2]` | of an order with an ID of 1 or 2.
| `['not', 1, 2]` | not of an order with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch codes for an order with an ID of 1 #}
{% set codes = craft.giftVoucher.codes()
    .orderId(1)
    .all() %}
```

```php PHP
// Fetch codes for an order with an ID of 1
$codes = \verbb\giftvoucher\elements\Code::find()
    ->orderId(1)
    ->all();
```
:::



### `price`

Narrows the query results based on the codes’ price.

Possible values include:

| Value | Fetches codes…
| - | -
| `100` | with a price of 100.
| `'>= 100'` | with a price of at least 100.
| `'< 100'` | with a price of less than 100.



### `relatedTo`

Narrows the query results to only codes that are related to certain other elements.

See [Relations](https://craftcms.com/docs/4.x/relations.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all codes that are related to myCategory #}
{% set codes = craft.giftVoucher.codes()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all codes that are related to $myCategory
$codes = \verbb\giftvoucher\elements\Code::find()
    ->relatedTo($myCategory)
    ->all();
```
:::



### `search`

Narrows the query results to only codes that match a search query.

See [Searching](https://craftcms.com/docs/4.x/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

{# Fetch all codes that match the search query #}
{% set codes = craft.giftVoucher.codes()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

// Fetch all codes that match the search query
$codes = \verbb\giftvoucher\elements\Code::find()
    ->search($searchQuery)
    ->all();
```
:::



### `sku`

Narrows the query results based on the codes’ SKUs.

Possible values include:

| Value | Fetches codes…
| - | -
| `'foo'` | with a SKU of `foo`.
| `'foo*'` | with a SKU that begins with `foo`.
| `'*foo'` | with a SKU that ends with `foo`.
| `'*foo*'` | with a SKU that contains `foo`.
| `'not *foo*'` | with a SKU that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a SKU that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a SKU that doesn’t contain `foo` or `bar`.

::: code
```twig Twig
{# Get the requested code SKU from the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{# Fetch the code with that slug #}
{% set code = craft.giftVoucher.codes()
    .sku(requestedSlug|literal)
    .one() %}
```

```php PHP
// Get the requested code SKU from the URL
$requestedSlug = \Craft::$app->getRequest()->getSegment(3);

// Fetch the code with that slug
$code = \verbb\giftvoucher\elements\Code::find()
    ->sku(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```
:::



### `slug`

Narrows the query results based on the codes’ slugs.

Possible values include:

| Value | Fetches codes…
| - | -
| `'foo'` | with a slug of `foo`.
| `'foo*'` | with a slug that begins with `foo`.
| `'*foo'` | with a slug that ends with `foo`.
| `'*foo*'` | with a slug that contains `foo`.
| `'not *foo*'` | with a slug that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a slug that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a slug that doesn’t contain `foo` or `bar`.

::: code
```twig Twig
{# Get the requested code slug from the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{# Fetch the code with that slug #}
{% set code = craft.giftVoucher.codes()
    .slug(requestedSlug|literal)
    .one() %}
```

```php PHP
// Get the requested code slug from the URL
$requestedSlug = \Craft::$app->getRequest()->getSegment(3);

// Fetch the code with that slug
$code = \verbb\giftvoucher\elements\Code::find()
    ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```
:::



### `status`

Narrows the query results based on the codes’ statuses.

Possible values include:

| Value | Fetches codes…
| - | -
| `'live'` _(default)_ | that are live.
| `'pending'` | that are pending (enabled with a Post Date in the future).
| `'expired'` | that are expired (enabled with an Expiry Date in the past).
| `'disabled'` | that are disabled.
| `['live', 'pending']` | that are live or pending.

::: code
```twig Twig
{# Fetch disabled codes #}
{% set codes = {twig-function}
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



### `title`

Narrows the query results based on the codes’ titles.

Possible values include:

| Value | Fetches codes…
| - | -
| `'Foo'` | with a title of `Foo`.
| `'Foo*'` | with a title that begins with `Foo`.
| `'*Foo'` | with a title that ends with `Foo`.
| `'*Foo*'` | with a title that contains `Foo`.
| `'not *Foo*'` | with a title that doesn’t contain `Foo`.
| `['*Foo*', '*Bar*'` | with a title that contains `Foo` or `Bar`.
| `['not', '*Foo*', '*Bar*']` | with a title that doesn’t contain `Foo` or `Bar`.

::: code
```twig Twig
{# Fetch codes with a title that contains "Foo" #}
{% set codes = craft.giftVoucher.codes()
    .title('*Foo*')
    .all() %}
```

```php PHP
// Fetch codes with a title that contains "Foo"
$codes = \verbb\giftvoucher\elements\Code::find()
    ->title('*Foo*')
    ->all();
```
:::



### `type`

Narrows the query results based on the codes’ types.

Possible values include:

| Value | Fetches codes…
| - | -
| `'foo'` | of a type with a handle of `foo`.
| `'not foo'` | not of a type with a handle of `foo`.
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
| a Code Type object | of a type represented by the object.

::: code
```twig Twig
{# Fetch codes with a Foo code type #}
{% set codes = craft.giftVoucher.codes()
    .type('foo')
    .all() %}
```

```php PHP
// Fetch codes with a Foo code type
$codes = \verbb\giftvoucher\elements\Code::find()
    ->type('foo')
    ->all();
```
:::



### `typeId`

Narrows the query results based on the codes’ types, per the types’ IDs.

Possible values include:

| Value | Fetches codes…
| - | -
| `1` | of a type with an ID of 1.
| `'not 1'` | not of a type with an ID of 1.
| `[1, 2]` | of a type with an ID of 1 or 2.
| `['not', 1, 2]` | not of a type with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch codes of the code type with an ID of 1 #}
{% set codes = craft.giftVoucher.codes()
    .typeId(1)
    .all() %}
```

```php PHP
// Fetch codes of the code type with an ID of 1
$codes = \verbb\giftvoucher\elements\Code::find()
    ->typeId(1)
    ->all();
```
:::



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



### `uri`

Narrows the query results based on the codes’ URIs.

Possible values include:

| Value | Fetches codes…
| - | -
| `'foo'` | with a URI of `foo`.
| `'foo*'` | with a URI that begins with `foo`.
| `'*foo'` | with a URI that ends with `foo`.
| `'*foo*'` | with a URI that contains `foo`.
| `'not *foo*'` | with a URI that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a URI that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a URI that doesn’t contain `foo` or `bar`.

::: code
```twig Twig
{# Get the requested URI #}
{% set requestedUri = craft.app.request.getPathInfo() %}

{# Fetch the code with that URI #}
{% set code = craft.giftVoucher.codes()
    .uri(requestedUri|literal)
    .one() %}
```

```php PHP
// Get the requested URI
$requestedUri = \Craft::$app->getRequest()->getPathInfo();

// Fetch the code with that URI
$code = \verbb\giftvoucher\elements\Code::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```
:::



### `with`

Causes the query to return matching codes eager-loaded with related elements.

See [Eager-Loading Elements](https://craftcms.com/docs/4.x/eager-loading-elements.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch codes eager-loaded with the "Related" field’s relations #}
{% set codes = craft.giftVoucher.codes()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch codes eager-loaded with the "Related" field’s relations
$codes = \verbb\giftvoucher\elements\Code::find()
    ->with(['related'])
    ->all();
```
:::



### `voucherId`

Narrows the query results based on the codes’ voucher types, per the voucher types’ IDs.

Possible values include:

| Value | Fetches codes…
| - | -
| `1` | of a voucher type with an ID of 1.
| `'not 1'` | not of a voucher type with an ID of 1.
| `[1, 2]` | of a voucher type with an ID of 1 or 2.
| `['not', 1, 2]` | not of a voucher type with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch codes of the voucher type with an ID of 1 #}
{% set codes = craft.giftVoucher.codes()
    .voucherId(1)
    .all() %}
```

```php PHP
// Fetch codes of the voucher type with an ID of 1
$codes = \verbb\giftvoucher\elements\Code::find()
    ->voucherId(1)
    ->all();
```
:::


<!-- END PARAMS -->
