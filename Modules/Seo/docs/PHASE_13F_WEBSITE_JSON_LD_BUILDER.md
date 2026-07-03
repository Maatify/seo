# Phase 13F: WebSite JSON-LD Builder

## Overview
The `WebSiteJsonLdBuilder` is part of the `Maatify\Seo\Web\JsonLd\Builder` namespace and provides a framework-neutral, fluent interface to build `WebSite` Schema.org JSON-LD arrays.

## Supported Fields
- `name`
- `url`
- `description`
- `publisher`:
  - Handled via `setPublisher(string|array $publisher)`. Passing a string automatically converts it to an `Organization` type array. Passing an array retains the array format.
- `potentialAction` (used for `SearchAction`):
  - Use `setSearchAction(string $targetUrlTemplate, string $searchTermParameter = 'search_term_string')` to easily configure a `SearchAction` pointing to a search endpoint.
  - Use `setPotentialAction(array $potentialAction)` to provide a custom action array. If `@type` is missing, it defaults to `SearchAction`.

## Usage Example

```php
use Maatify\Seo\Web\JsonLd\Builder\WebSiteJsonLdBuilder;

$builder = new WebSiteJsonLdBuilder();

$schemaArray = $builder
    ->setName('Example Site')
    ->setUrl('https://example.com')
    ->setDescription('An example website for testing.')
    ->setPublisher('Example Publisher') // Automatically formats as an Organization type
    ->setSearchAction('https://example.com/search?q={search_term_string}', 'search_term_string')
    ->toArray();

// Or get as JSON string directly
$jsonString = $builder->toJson();
```
