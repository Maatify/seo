# Batch 3: Hreflang Head Link Builder

This document describes the usage of the Hreflang Head Link Builder components in the Maatify SEO library.

## Overview

The `HreflangLinkBuilder` provides a framework-agnostic way to construct and render `<link rel="alternate" hreflang="..." href="...">` tags for the HTML `<head>`.

It operates completely independently of HTTP requests, routing mechanisms, or host framework abstractions (like Laravel or Symfony), making it fully compatible with any application architecture.

## Requirements & Constraints

* **Strict Independence**: Contains no controllers, routes, HTTP responses, or framework coupling.
* **HTML Head Focus**: Designed strictly for HTML head link generation. Does not interact with or modify sitemap XML `hreflang` generation.
* **Pure Output**: Emits only string tags based on provided inputs.

## Components

### HreflangLinkDTO

A Data Transfer Object that represents a single hreflang link. It normalizes the hreflang code and strictly validates that both the hreflang and URL are non-empty strings, with basic format validation for URLs.

It implements `toArray()` and `JsonSerializable`.

### HreflangLinkBuilder

A builder for managing a collection of `HreflangLinkDTO`s.

* **`add(string $hreflang, string $url)`**: Adds a link. If the hreflang already exists, the first added value is retained (duplicates are ignored).
* **`replace(string $hreflang, string $url)`**: Replaces a link, explicitly overriding any existing value for the given hreflang.
* **`addMany(array $links)`**: Adds multiple links at once. Supports DTOs, array payloads (e.g. `['hreflang' => 'en', 'url' => '...']`), or simple key-value pairs (e.g. `'en' => '...'`).
* **`xDefault(string $url)`**: An explicit alias to replace the `x-default` hreflang.
* **`all()`**: Returns the raw `HreflangLinkDTO` array.
* **`toArray()`**: Returns a serialized array of the DTOs.
* **`render()`**: Renders all links into safe HTML via the `HreflangLinkRenderer`.

### HreflangLinkRenderer

Takes a `HreflangLinkDTO` or array of them and securely renders the HTML head tags. All attributes are escaped using `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8')`.

## Example Usage

```php
use Maatify\Seo\Web\Hreflang\HreflangLinkBuilder;

$builder = new HreflangLinkBuilder();

// Add single links
$builder->add('en', 'https://example.com/en')
        ->add('fr', 'https://example.com/fr');

// Explicitly define the fallback
$builder->xDefault('https://example.com/en');

// Render tags directly to HTML for output
echo $builder->render();
```

## Note on Hreflang Normalization

The DTO normalizes the hreflang string by standardizing capitalization (e.g., `en-us` or `EN_US` to `en-US`), based on standard language and region code conventions.

**Important Note on Validation:** The normalization and validation are basic formatting checks. They *do not* perform strict full BCP-47 language/region code validation against an exhaustive dictionary of all possible correct locale codes.
