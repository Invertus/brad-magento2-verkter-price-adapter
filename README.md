# brad-magento-verkter-price-adapter

Verkter-specific bridge between `bradsearch/magento-extension` and `Magenmagic_ProductPrices`.

Registers the Magento module `Magenmagic_BradSearchPrice`, which provides a single
`<preference>` overriding `BradSearch\SearchGraphQl\Api\PriceCalculatorInterface`
with an implementation that delegates to `Magenmagic\ProductPrices\Helper\Price`.

This guarantees that the `calculated_price` BradSearch indexes is the same value
the storefront returns from the `productPrice` GraphQL query — including country
scope, currency, special prices, and tax handling.

It also registers `PriceInputAttributeProvider` on the extension's real-time
sync `ChangeFilter`. The price helper reads the regular price, the tax class and
the free-shipping weight and length (named in `mmfreeshipping/attr_code/*`) from
the default-scope product. The provider makes the sync hash these at default
scope, so a change there is sent even when a store view has its own value, and
a weight or length change is sent although neither attribute is searchable or
filterable. Install this release
together with the extension release that adds `ProductHashAttributeProviderInterface`:
on an older extension, `setup:di:compile` fails on the missing interface.

## Why this exists as its own package

The bridge is client-specific (it depends on Verkter's `Magenmagic_ProductPrices`),
but it is extracted into its own composer package so that when Verkter engineers
refactor `Magenmagic\ProductPrices\Helper\Price`, the consumer shows up in
`vendor/` search and does not silently rot. The alternative — keeping the bridge
inside `app/code/Magenmagic/` — hides this coupling from IDE refactor tools.

## Installation

Add the private composer registry to the consumer `composer.json` (already
configured in Verkter) and require the package:

```bash
composer require bradsearch/magento-verkter-price-adapter
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Development

```bash
composer install              # installs captainhook hooks automatically
composer phpcbf               # auto-fix Magento2 coding standard
composer phpcs                # Magento2 coding standard check
composer phpstan              # static analysis at level 6
composer psalm                # static analysis at error level 4
composer test                 # phpunit unit tests
composer check                # all of the above
```

Pre-commit hooks run `phpcbf` + `phpcs` + `phpstan` on staged PHP files.
Pre-push runs `psalm` + full test suite.

## Module dependency sequence

`Magenmagic_BradSearchPrice` sequences after:

- `BradSearch_SearchGraphQl` — provides `PriceCalculatorInterface` and
  `CalculatedPrice` / `PriceTuple` / `Money` value objects.
- `Magenmagic_ProductPrices` — provides the `Price` helper this bridge wraps.

## Verification after install

1. `bin/magento module:status Magenmagic_BradSearchPrice` → `enabled`
2. Run a BradSearch-routed `products(search: "…")` GraphQL query and compare the
   `price_range.minimum_price.final_price` against the storefront `productPrice`
   response for the same product + store view. They must match to the cent.
