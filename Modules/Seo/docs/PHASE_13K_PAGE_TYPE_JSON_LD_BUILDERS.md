# Phase 13K: Page Type JSON-LD Builders

This document covers the batch of page-focused JSON-LD builders implemented in Phase 13K. These builders allow generating standardized schema.org entities for various specific page types, extending the base `WebPage` properties.

The Phase 13K batch includes:
- `AboutPageJsonLdBuilder`
- `ContactPageJsonLdBuilder`
- `CollectionPageJsonLdBuilder`
- `ProfilePageJsonLdBuilder`
- `SearchResultsPageJsonLdBuilder`

All builders are strictly framework-neutral, independent of any HTTP or template engine layer, and are designed to return arrays or JSON strings for rendering.

## 1. AboutPageJsonLdBuilder

Builds `AboutPage` schema.org markup with properties specific to an "About Us" page.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\AboutPageJsonLdBuilder;

$aboutBuilder = new AboutPageJsonLdBuilder();

$schema = $aboutBuilder
    ->setName('About Us')
    ->setUrl('https://example.com/about')
    ->setDescription('Learn more about us')
    ->setIsPartOf('https://example.com')
    ->setBreadcrumb('https://example.com/about#breadcrumb')
    ->setPrimaryImageOfPage('https://example.com/image.jpg')
    ->setDatePublished('2023-01-01')
    ->setDateModified('2023-10-01')
    ->setAbout('Our Company') // auto-normalized to Thing
    ->setMainEntity(['@type' => 'Organization', 'name' => 'Acme Corp'])
    ->toArray();

echo json_encode($schema, JSON_UNESCAPED_SLASHES);
```

### Methods
- `setName(string $name): static`
- `setUrl(string $url): static`
- `setDescription(string $description): static`
- `setIsPartOf(string|array $website): static`
- `setBreadcrumb(string|array $breadcrumb): static`
- `setPrimaryImageOfPage(string|array $image): static`
- `setDatePublished(string $datePublished): static`
- `setDateModified(string $dateModified): static`
- `setAbout(string|array $about): static`
- `setMainEntity(string|array $mainEntity): static`

## 2. ContactPageJsonLdBuilder

Builds `ContactPage` schema.org markup with contact-specific properties.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\ContactPageJsonLdBuilder;

$contactBuilder = new ContactPageJsonLdBuilder();

$schema = $contactBuilder
    ->setName('Contact Us')
    ->setUrl('https://example.com/contact')
    ->setContactPoint('customer support') // auto-normalized to ContactPoint
    ->toArray();
```

### Methods
- *Inherits standard page methods (setName, setUrl, setDescription, etc.)*
- `setContactPoint(string|array $contactPoint): static`

## 3. CollectionPageJsonLdBuilder

Builds `CollectionPage` schema.org markup, suitable for category pages or galleries.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\CollectionPageJsonLdBuilder;

$collectionBuilder = new CollectionPageJsonLdBuilder();

$schema = $collectionBuilder
    ->setName('Our Products')
    ->setHasPart(['https://example.com/p1', 'https://example.com/p2']) // URLs auto-normalized to WebPage
    ->addHasPart(['@type' => 'ItemPage', 'url' => 'https://example.com/p3'])
    ->toArray();
```

### Methods
- *Inherits standard page methods (setName, setUrl, setDescription, etc.)*
- `setHasPart(array $hasPart): static`
- `addHasPart(string|array $part): static`

## 4. ProfilePageJsonLdBuilder

Builds `ProfilePage` schema.org markup, representing a specific person's profile page.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\ProfilePageJsonLdBuilder;

$profileBuilder = new ProfilePageJsonLdBuilder();

$schema = $profileBuilder
    ->setName('Jane Doe Profile')
    ->setMainEntity('Jane Doe') // auto-normalized to Person
    ->toArray();
```

### Methods
- *Inherits standard page methods (setName, setUrl, setDescription, etc.)*
- `setMainEntity(string|array $mainEntity): static`

## 5. SearchResultsPageJsonLdBuilder

Builds `SearchResultsPage` schema.org markup, detailing search results and the query performed.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\SearchResultsPageJsonLdBuilder;

$searchBuilder = new SearchResultsPageJsonLdBuilder();

$schema = $searchBuilder
    ->setName('Search Results for "json-ld"')
    ->setQuery('json-ld')
    ->setItemListElement(['https://example.com/r1', 'https://example.com/r2']) // Auto-positioned ListItem elements
    ->addResult('https://example.com/r3', 'Result 3')
    ->toArray();
```

### Methods
- *Inherits standard page methods (setName, setUrl, setDescription, etc.)*
- `setQuery(string $query): static`
- `setItemListElement(array $items): static`
- `addResult(string|array $item, ?string $name = null): static`
