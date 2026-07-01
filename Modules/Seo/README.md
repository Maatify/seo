# Maatify SEO Module

This is the standalone Maatify SEO library. It provides host-agnostic tools to manage SEO metadata, schema generation (JSON-LD), redirects, slug history, and sitemaps.

> **Note**: This package is intentionally framework-agnostic and host-agnostic. It contains zero coupling to frameworks (like Laravel or Symfony) and zero foreign-key relationships to host database tables. It relies on standard host interfaces (contracts).

## Installation
```bash
composer require maatify/seo
```

## Practical Examples

To see how the library functions in real-world scenarios, you can run the following standalone examples from the command line:

* `php examples/basic-head-render.php`: Demonstrates simple rendering of standard meta tags and array-based JSON-LD.
* `php examples/product-page-seo.php`: Shows how to construct and output open graph tags and schema specifically for product pages.
* `php examples/category-page-seo.php`: Uses the `FluentSeoBuilder` to construct schema and metadata for category pages.
* `php examples/schema-output.php`: Outputs structured data schemas from arrays, DTOs, and adapted optional spatie objects.
* `php examples/sitemap-output.php`: Render sitemap XML outputs natively using provided DTOs and renderers.

## Implemented Layers
The module is complete and release-ready. It has the following foundational layers implemented for the **Core/Shared SEO library** (Phases 1-5) the **Admin Layer** (Phase 6A), the **Web Layer** (Phase 6B), **Bootstrap/DI Full Wiring** (Phase 6C), and **Final Module Compliance Audit** (Phase 6D).

- **Phase 1 (Foundation - Core/Shared):** Base DTOs, Exceptions, Host Contracts.
- **Phase 2A (Schema - Core/Shared):** Standalone SQL tables for slug history, redirects, and manual SEO overrides.
- **Phase 2B (Repositories - Core/Shared):** PDO implementations for persistence layers without ORMs.
- **Phase 2C (Services - Core/Shared):** Core domain logic orchestration, utilizing constructor injection and strict module exceptions.
- **Phase 3A (Meta Generator - Core/Shared):** Logic to assemble and orchestrate standard HTML Meta tags, merging host-provided defaults with manual database overrides in a framework-agnostic way.
- **Phase 3B (JSON-LD Schema Generator - Core/Shared):** Standalone service providing host-agnostic and framework-agnostic structured data generation for SEO (e.g., Breadcrumbs, Products) via strictly typed DTOs.
- **Phase 3C (Redirect & Slug Services - Core/Shared):** Core logic for resolving SEO redirects and managing slug histories, maintaining framework independence by returning DTOs rather than HTTP responses.
- **Phase 4 (Sitemap Generation - Core/Shared):** In-memory XML sitemap generation stream (URL sets and Sitemap Indexes) dynamically powered by strict DTOs.
- **Phase 5 (Documentation & Polish - Core/Shared):** Final package documentation polish, validation, and release readiness verification.
- **Phase 6A (Admin Layer):** Admin-specific command, query, and service classes for managing SEO overrides, redirects, and slug history.
- **Phase 6B (Web Layer):** Implementation of host website consumption services and DTOs for frontend SEO data structures, operating entirely framework-agnostic.
- **Phase 6C (Bootstrap/DI Full Wiring):** Single shared binding entry point providing framework-neutral dependency definitions for all layers.
- **Phase 6D (Final Module Compliance Audit):** Full compliance audit against Maatify module standards, confirming the entire module is complete and release-ready.
- **Phase 7A (Usability & Rendering):** Optional HTML rendering helpers to easily output raw PHP strings for SEO head blocks without template engine coupling.
- **Phase 7B (Flattened Usability DTO):** Output DTO mapping all metadata cleanly into individual string sections or array access.
- **Phase 7C (Fluent SEO Builder):** Fluent interface for dynamically building and rendering SEO output.
- **Phase 7D (Optional Spatie Schema Integration):** Provides an optional adapter for converting Spatie schema objects to native JSON-LD DTOs.
- **Phase 7E (Sitemap String Output):** Helper to optionally generate plain string output directly from sitemap commands. The Phase 7 usability and rendering layer is now completely implemented.
- **Phase 9A (Robots.txt Output Helpers):** Framework-neutral helpers for generating `robots.txt` plain strings.
- **Phase 10A (Sitemap Index String Renderer):** Helper to optionally generate plain string output directly for sitemap indexes.
- **Phase 10B (Hreflang / Alternate URL Support):** Web string helpers for sitemap multi-language indexing (`xhtml:link`).
- **Phase 10C (Image Sitemap Support):** Web string helpers for image sitemap standard integration (`image:image`).
- **Phase 10D (Video Sitemap Support):** Web string helpers for video sitemap standard integration (`video:video`).
- **Phase 11A (SEO Validation Helpers):** Framework-neutral helpers for auditing and validating generated SEO metadata arrays or objects to warn about missing fields or tag conflicts.

## Rendering Robots.txt

The `RobotsTxtRenderer` generates a complete `robots.txt` string dynamically.

```php
use Maatify\Seo\Web\Robots\RobotsTxtRenderer;
use Maatify\Seo\Web\Robots\DTO\RobotsTxtDTO;
use Maatify\Seo\Web\Robots\DTO\RobotsRuleDTO;

$renderer = new RobotsTxtRenderer();

$txt = new RobotsTxtDTO(
    rules: [
        new RobotsRuleDTO(
            userAgent: '*',
            allow: ['/'],
            disallow: ['/admin/'],
            crawlDelay: 10
        )
    ],
    sitemaps: ['https://example.com/sitemap.xml']
);

echo $renderer->render($txt);
```
