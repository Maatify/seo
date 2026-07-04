# Batch 1B SEO Page Preset Factory

## Overview
The `SeoPagePresetFactory` (Batch 1B) provides high-level preset factory methods to effortlessly generate fully-configured SEO output (`SeoPagePresetOutputDTO`) for standard page types (e.g., Generic, Product, Article, Category, Home, Breadcrumb). It acts as an orchestrator that natively integrates and reuses the library's existing specialized builders without introducing direct framework or HTTP coupling.

The resulting `SeoPagePresetOutputDTO` provides structured access to generated tags, schemas, and pre-rendered HTML ready for host application integration.

## Implemented Page Presets

- `SeoPagePresetFactory::generic(string $title, ?string $description, array $options = []): SeoPagePresetOutputDTO`
- `SeoPagePresetFactory::product(string $title, ?string $description, array $product, array $options = []): SeoPagePresetOutputDTO`
- `SeoPagePresetFactory::category(string $title, ?string $description, array $items, array $options = []): SeoPagePresetOutputDTO`
- `SeoPagePresetFactory::article(string $title, ?string $description, array $article, array $options = []): SeoPagePresetOutputDTO`
- `SeoPagePresetFactory::home(string $title, ?string $description, array $options = []): SeoPagePresetOutputDTO`
- `SeoPagePresetFactory::breadcrumb(string $title, ?string $description, array $breadcrumbs, array $options = []): SeoPagePresetOutputDTO`

## Example Usage

### Generic Page

```php
use Maatify\Seo\Web\Page\SeoPagePresetFactory;

$preset = SeoPagePresetFactory::generic('About Us', 'About our company', [
    'canonicalBaseUrl' => 'https://example.com',
    'canonicalPath' => '/about',
    'robots' => ['index', 'follow'],
    'imageUrl' => 'https://example.com/about.jpg',
    'siteName' => 'Example Site',
]);

echo $preset->html; // Fully rendered HTML tags
```

### Product Page

```php
use Maatify\Seo\Web\Page\SeoPagePresetFactory;

$productData = [
    'name' => 'Blue T-Shirt',
    'sku' => 'TSHIRT-BL-L',
    'brand' => 'Acme Apparel',
    'price' => '29.99',
    'currency' => 'USD',
];

$preset = SeoPagePresetFactory::product(
    'Blue T-Shirt - Acme Apparel',
    'High quality blue t-shirt.',
    $productData,
    [
        'canonicalUrl' => 'https://example.com/products/blue-tshirt',
        'imageUrl' => 'https://example.com/images/blue-tshirt.jpg',
    ]
);

// Get structured data schemas (like JSON-LD Product Schema)
$schemas = $preset->toArray()['schemas'];
```

### Article Page

```php
use Maatify\Seo\Web\Page\SeoPagePresetFactory;

$articleData = [
    'author' => 'Jane Doe',
    'datePublished' => '2025-01-15T08:00:00Z',
    'publisher' => 'Example News',
];

$preset = SeoPagePresetFactory::article(
    'The Future of Tech',
    'An in-depth look at upcoming technology trends.',
    $articleData,
    [
        'canonicalUrl' => 'https://example.com/news/future-of-tech',
        'imageUrl' => 'https://example.com/news/future-of-tech-cover.jpg',
    ]
);
```

## Options Reference
The `$options` array accepted by factory methods can include:
- `canonicalBaseUrl` (string): Base URL for canonical building.
- `canonicalPath` (string): Path for canonical building.
- `canonicalUrl` (string): Direct full canonical URL.
- `queryParams` (array): Query parameters for canonical building.
- `allowedQueryParams` (array): Allowed query parameters to preserve in canonical URLs.
- `robots` (array|MetaRobotsBuilder): Meta robots instructions (e.g., `['index', 'follow']`).
- `imageUrl` (string): Primary image URL for social preview.
- `siteName` (string): Site name for Open Graph.
- `locale` (string): Locale for Open Graph.
- `twitterSite` (string): Twitter site handle (e.g., `@example`).
- `extraSchemas` (array): Array of `JsonLdSchemaDTO` objects to merge into the final output.
- `breadcrumbs` (array): Array of breadcrumb items `[['name' => 'Home', 'url' => '...'], ...]`.

## Output Object: `SeoPagePresetOutputDTO`

The `SeoPagePresetOutputDTO` exposes:
- `$metaTags` (`MetaTagsDTO`): Basic meta tags.
- `$canonicalUrl` (`?string`): Resolved canonical URL.
- `$robots` (`string`): Meta robots string.
- `$socialTags` (`array`): Array of social tag definitions.
- `$socialHtml` (`string`): Rendered social tags HTML.
- `$schemas` (`array` of `JsonLdSchemaDTO`): JSON-LD schemas generated.
- `$html` (`string`): Full combined SEO `<head>` HTML block.
