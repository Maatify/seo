# Phase 13B — Product JSON-LD Builder

## Overview

Phase 13B adds `ProductJsonLdBuilder`, the first concrete typed builder on top of the JSON-LD builder foundation introduced in Phase 13A.

The builder is framework-neutral, has no external dependencies, and exports plain associative arrays or JSON strings that can be used by existing renderers, DTOs, controllers, templates, or API responses.

Every new instance automatically starts with schema.org Product defaults:

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'Product',
]
```

## Supported methods

`ProductJsonLdBuilder` extends the base builder API (`set`, `remove`, `has`, `get`, `toArray`, `toJson`) and adds Product-specific fluent methods:

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

## Nested schema structures

### Offer

The offer-related methods build one nested `offers` object with `@type` set to `Offer`:

- `setPrice()` writes `offers.price`.
- `setCurrency()` writes `offers.priceCurrency`.
- `setAvailability()` writes `offers.availability`.
- `setOfferUrl()` writes `offers.url`.
- `setCondition()` writes `offers.itemCondition`.

### AggregateRating

`setAggregateRating()` writes an `aggregateRating` object with `@type` set to `AggregateRating`:

- `ratingValue`
- `reviewCount`

### Review

`addReview()` appends to the `review` array. Each review includes:

- `@type` = `Review`
- `author` as a schema.org `Person`
- `reviewRating` as a schema.org `Rating`
- `reviewBody`

## Usage examples

### Minimal Product JSON-LD

```php
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;

$schema = (new ProductJsonLdBuilder())
    ->setName('Maatify Demo Product')
    ->setDescription('A demo product for JSON-LD output.')
    ->setSku('SKU-13B')
    ->toArray();
```

Generated array:

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => 'Maatify Demo Product',
    'description' => 'A demo product for JSON-LD output.',
    'sku' => 'SKU-13B',
]
```

### Product with offer, rating, and reviews

```php
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;

$json = (new ProductJsonLdBuilder())
    ->setName('Maatify Demo Product')
    ->setDescription('A demo product for JSON-LD output.')
    ->setSku('SKU-13B')
    ->setBrand('Maatify')
    ->setImage([
        'https://example.com/images/product-front.jpg',
        'https://example.com/images/product-side.jpg',
    ])
    ->setCategory('Software')
    ->setUrl('https://example.com/products/demo')
    ->setCurrency('USD')
    ->setPrice('19.99')
    ->setAvailability('https://schema.org/InStock')
    ->setCondition('https://schema.org/NewCondition')
    ->setOfferUrl('https://example.com/products/demo?purchase=1')
    ->setAggregateRating(4.8, 27)
    ->addReview('Jane Doe', 5, 'Excellent product.')
    ->toJson(JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
```

Generated JSON example:

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
    "image": [
        "https://example.com/images/product-front.jpg",
        "https://example.com/images/product-side.jpg"
    ],
    "category": "Software",
    "url": "https://example.com/products/demo",
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

## Integration examples

### Rendering a JSON-LD script tag manually

```php
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;

$json = (new ProductJsonLdBuilder())
    ->setName('Maatify Demo Product')
    ->setCurrency('USD')
    ->setPrice('19.99')
    ->toJson(JSON_UNESCAPED_SLASHES);

echo '<script type="application/ld+json">' . $json . '</script>';
```

### Passing arrays to existing application layers

```php
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;

$productSchema = (new ProductJsonLdBuilder())
    ->setName($productName)
    ->setDescription($productDescription)
    ->setUrl($canonicalUrl)
    ->setCurrency($currency)
    ->setPrice($price)
    ->toArray();

// Pass $productSchema to a renderer, view model, DTO, or API response.
```

## Compatibility notes

- No existing generators were modified.
- No new dependency was added.
- `composer.lock` is not required and was not introduced.
- The builder remains compatible with the Phase 13A foundation and existing JSON-LD array consumers.
