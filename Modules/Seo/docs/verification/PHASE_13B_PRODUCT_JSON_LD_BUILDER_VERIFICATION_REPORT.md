# Phase 13B — Product JSON-LD Builder Verification Report

## Files added

- `src/Web/JsonLd/Builder/ProductJsonLdBuilder.php`
- `tests/Phase13BProductJsonLdBuilderTest.php`
- `docs/PHASE_13B_PRODUCT_JSON_LD_BUILDER.md`
- `docs/verification/PHASE_13B_PRODUCT_JSON_LD_BUILDER_VERIFICATION_REPORT.md`

## Public API summary

The new concrete builder is `Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder`.

It extends `AbstractJsonLdBuilder`, implements the existing `JsonLdBuilderInterface` through the base class, and automatically initializes every schema with:

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'Product',
]
```

Product-specific fluent methods:

```php
setName(string $name): static
setDescription(string $description): static
setSku(string $sku): static
setBrand(string $brand): static
setImage(string|array $image): static
setCategory(string $category): static
setUrl(string $url): static
setCurrency(string $currency): static
setPrice(int|float|string $price): static
setAvailability(string $schemaAvailability): static
setCondition(string $schemaCondition): static
setOfferUrl(string $url): static
setAggregateRating(float $ratingValue, int $reviewCount): static
addReview(string $author, int|float $rating, string $reviewBody): static
```

The inherited generic builder methods remain available:

```php
set(string $key, mixed $value): static
remove(string $key): static
has(string $key): bool
get(string $key): mixed
toArray(): array
toJson(int $flags = 0): string
```

## Example output

```json
{
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "Maatify Demo Product",
    "description": "A demo product for JSON-LD output.",
    "sku": "SKU-13B",
    "brand": {
        "@type": "Brand",
        "name": "Maatify"
    },
    "offers": {
        "@type": "Offer",
        "priceCurrency": "USD",
        "price": "19.99",
        "availability": "https://schema.org/InStock",
        "itemCondition": "https://schema.org/NewCondition",
        "url": "https://example.com/products/demo?purchase=1"
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": 4.8,
        "reviewCount": 27
    },
    "review": [
        {
            "@type": "Review",
            "author": {
                "@type": "Person",
                "name": "Jane Doe"
            },
            "reviewRating": {
                "@type": "Rating",
                "ratingValue": 5
            },
            "reviewBody": "Excellent product."
        }
    ]
}
```

## composer validate result

Command:

```bash
composer validate --strict
```

Result:

```text
./composer.json is valid
```

Status: Passed.

## vendor/bin/phpstan analyse result

Command:

```bash
vendor/bin/phpstan analyse
```

Result:

```text
 [OK] No errors
```

Status: Passed.

## Test results

Command:

```bash
find tests -name '*Test.php' -print0 | xargs -0 -n1 php
```

Result:

```text
Phase 11G SEO validation batch report exporter tests passed.
Phase 7E sitemap XML string renderer tests passed.
Phase 10D video sitemap XML string renderer tests passed.
Phase 11C SEO validation report helpers tests passed.
Phase 10C image sitemap XML string renderer tests passed.
Phase 7D Spatie schema adapter tests passed.
Phase 13A JSON-LD builder foundation tests passed.
Phase 7C fluent SEO builder tests passed.
Phase 11F SEO validation batch report helpers tests passed.
Phase 11D SEO validation presets tests passed.
Phase 7A renderer tests passed.
Phase 10B sitemap hreflang XML string renderer tests passed.
Phase 10A sitemap index XML string renderer tests passed.
Phase 10E news sitemap XML string renderer tests passed.
Phase 11E SEO validation report exporter tests passed.
Phase 11A SEO validation helpers tests passed.
Phase 11B SEO validation score helpers tests passed.
Phase 9A RobotsTxtRenderer tests passed.
Phase 13B product JSON-LD builder tests passed.
```

Status: Passed.

Additional syntax check:

```bash
php -l src/Web/JsonLd/Builder/ProductJsonLdBuilder.php
```

Result:

```text
No syntax errors detected in src/Web/JsonLd/Builder/ProductJsonLdBuilder.php
```

Status: Passed.

## CI compatibility statement

The implementation is additive and framework-neutral. It does not modify existing generators, renderers, DTOs, services, composer dependencies, or Composer autoload configuration. It uses only PHP language features already required by the package (`php >=8.2`) and remains compatible with CI jobs that install Composer dev dependencies before running PHPStan.

## BC verification

- No existing public API was removed or renamed.
- No existing generator was modified.
- No new package dependency was added.
- `composer.lock` was not created or modified.
- The new builder exports plain arrays and JSON strings through the existing Phase 13A builder contract.

## Final verdict

Phase 13B is complete. The Product JSON-LD builder is implemented, covered by a focused test, documented, and compatible with the existing JSON-LD builder foundation.
