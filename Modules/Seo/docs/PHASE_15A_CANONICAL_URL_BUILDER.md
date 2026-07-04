# Phase 15A Canonical URL Builder

This document outlines the usage of the Phase 15A Canonical URL Builder in the Maatify SEO Library.

## Features

- Constructs complete canonical URLs with paths and query strings.
- Automatically normalizing slashes between the base URL and the path.
- Handles query parameters addition, replacement, or removal while safely omitting `null` values.
- Converts boolean parameters to `1` and `0`.
- Encodes URLs according to RFC3986.
- Provides string-based `build()` to fetch the raw URL, and `toHtml()` to get an HTML `<link rel="canonical">` element escaped correctly (via `ENT_QUOTES | ENT_SUBSTITUTE` UTF-8 escaping).
- Pure logic class that generates URLs explicitly passed.

## Strict Guarantees

As per Maatify's module standards:
- **No URL Validation:** The builder does not do URL validation beyond string typing. If you need validation, it must happen prior to usage.
- **No auto-detection:** Does not detect the current host, path, or request parameters from server variables/globals or frameworks.
- **No current request calculations:** Does not read current request data dynamically.
- **Pure Output:** Always returns strings (`build()`, `toHtml()`).
- **No HTTP dependency:** No PSR-7 dependency. No controllers, routes, framework coupling or static global state.

## Basic Usage

### Instantiation
```php
use Maatify\Seo\Web\Indexing\CanonicalUrlBuilder;

// Instantiate with a Base URL
$builder = new CanonicalUrlBuilder('https://example.com');

// Set Path later
$builder->setPath('about-us');

// Result: https://example.com/about-us
echo $builder->build();
```

### Managing Query Parameters

```php
// Sets a brand new set of query parameters
$builder->setQueryParams(['sort' => 'desc', 'page' => 1]);

// Adds or overrides single query parameters
$builder->addQueryParam('category', 'tech');

// Removing a parameter
$builder->removeQueryParam('page');

// Result: https://example.com/about-us?sort=desc&category=tech
echo $builder->build();

// Preserving only allowed keys
$builder->preserveQueryParams(['sort']);

// Result: https://example.com/about-us?sort=desc
echo $builder->build();
```

### Output HTML

```php
// Build the HTML link element natively
$builder->setQueryParams(['q' => 'shoes"']);

// Result: <link rel="canonical" href="https://example.com/about-us?q=shoes%22">
echo $builder->toHtml();
```
