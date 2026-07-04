# Maatify SEO Module

This is the standalone Maatify SEO library. It provides host-agnostic tools to manage SEO metadata, schema generation (JSON-LD), redirects, slug history, and sitemaps.

> **Note**: This package is intentionally framework-agnostic and host-agnostic. It contains zero coupling to frameworks (like Laravel or Symfony) and zero foreign-key relationships to host database tables. It relies on standard host interfaces (contracts).

## Installation
```bash
composer require maatify/seo
```

## Practical Examples

To see how the library functions in real-world scenarios, you can run the following standalone examples from the command line:

* `php examples/seo-page-presets.php`: Demonstrates simple rendering of standard meta tags and array-based JSON-LD.
* `php examples/hreflang-generation.php`: Demonstrates hreflang generation.
* `php examples/admin-previews.php`: Demonstrates admin SERP and Social previews.
* `php examples/import-export.php`: Shows how to import and export SEO metadata.
* `php examples/social-builders.php`: Demonstrates social builders for Open Graph and Twitter Cards.
* `php examples/meta-robots-canonical.php`: Demonstrates robots and canonical builders.
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
- **Phase 10E (News Sitemap Support):** Web string helpers for news sitemap standard integration (`news:news`).
- **Phase 11A (SEO Validation Helpers):** Framework-neutral helpers for auditing and validating generated SEO metadata arrays or objects to warn about missing fields or tag conflicts.
- **Phase 11B (SEO Validation Score Helpers):** Framework-neutral calculator that computes actionable SEO scores, grades, and deductions from a validation result.
- **Phase 11C (SEO Validation Report Helpers):** Framework-neutral builder that combines validation and scoring into a comprehensive reporting DTO.
- **Phase 11D (SEO Validation Presets):** Framework-neutral helper that provides pre-configured validation and score options (e.g. strict, minimal, standard) for streamlined usage.
- **Phase 11E (SEO Validation Report Exporter):** Framework-neutral helper to export validation reports into arrays, JSON, summary arrays, and Markdown.
- **Phase 11F (SEO Validation Batch Report Helpers):** Framework-neutral builder to build SEO validation reports for multiple pages/products/entities in one batch. It provides aggregate counts, score stats, and summary status, and is useful for QA crawls, admin dashboards, audits, CI reports, and bulk product/category checks.
- **Phase 11G (SEO Validation Batch Report Exporter):** Framework-neutral helper to export batch validation reports into arrays, JSON, summary arrays, and Markdown.

## Validation Report Example

The `SeoValidationReportBuilder` combines validation and scoring into a comprehensive report.

```php
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;

$metaData = [
    'title' => 'Example Page Title',
    'canonical' => 'https://example.com/demo'
];

$report = SeoValidationReportBuilder::build(
    meta: $metaData,
    validationOptions: [
        'requireCanonical' => true,
    ],
    scoreOptions: [
        'healthyMinimumScore' => 90,
    ],
    context: [
        'url' => 'https://example.com/demo',
        'entityType' => 'page',
        'language' => 'en',
    ]
);

// $report->isValid
// $report->isHealthy
// $report->score
// $report->grade
// $report->summary['status']
```

## Validation Report Export Example

The `SeoValidationReportExporter` can export the report DTO into arrays, JSON, summary arrays, and Markdown.

```php
use Maatify\Seo\Web\Validation\SeoValidationPreset;
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationReportExporter;

$preset = SeoValidationPreset::standard();
$report = SeoValidationReportBuilder::build(
    meta: $metaData,
    validationOptions: $preset['validationOptions'],
    scoreOptions: $preset['scoreOptions'],
    context: [
        'url' => 'https://example.com/products/demo',
        'entityType' => 'product',
        'entityId' => 123,
        'language' => 'en',
        'source' => 'qa',
    ],
);

// Full array export
$fullArray = SeoValidationReportExporter::toArray($report);

// JSON string export
$json = SeoValidationReportExporter::toJson($report);

// Compact summary array
$summary = SeoValidationReportExporter::toSummaryArray($report);
/*
[
    'isValid' => true,
    'isHealthy' => true,
    'score' => 100,
    'grade' => 'A',
    'errorCount' => 0,
    'warningCount' => 0,
    'infoCount' => 0,
    'status' => 'pass',
    'message' => 'SEO validation passed.',
]
*/

// Markdown export (useful for issue comments, CI output, etc.)
// Note: Do not hardcode full markdown output if it is too long. Show short representative output only.
$markdown = SeoValidationReportExporter::toMarkdown($report);
/*
# SEO Validation Report

## Summary
- Status: pass
- Message: SEO validation passed.
- Score: 100
- Grade: A
...
*/
```

## Validation Scoring Example

The `SeoValidationScoreCalculator` can assign a score from 0 to 100 based on validation results.

```php
use Maatify\Seo\Web\Validation\SeoMetaValidator;
use Maatify\Seo\Web\Validation\SeoValidationScoreCalculator;

$metaData = [
    'title' => 'Missing Description Example',
];

$result = SeoMetaValidator::validate($metaData);

// Generates a SeoValidationScoreDTO
$scoreDto = SeoValidationScoreCalculator::score($result, [
    'errorPenalty' => 25,
    'warningPenalty' => 5,
]);

echo "Score: {$scoreDto->score}/100\n";
echo "Grade: {$scoreDto->grade}\n";
echo "Healthy: " . ($scoreDto->isHealthy ? 'Yes' : 'No') . "\n";
```

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

## Sitemap Output Examples

The Web layer provides helpers to safely output raw XML strings for sitemaps, including support for news sitemaps, video sitemaps, image sitemaps, and hreflang alternates.

### Generating a News Sitemap with DTOs

```php
use Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

$renderer = new SitemapXmlStringRenderer();

$url = new SitemapUrlDTO(
    loc: 'https://example.com/news/story',
    news: [
        new SitemapNewsDTO(
            publicationName: 'Example Daily',
            publicationLanguage: 'en',
            publicationDate: '2026-07-01T10:00:00+00:00',
            title: 'Breaking News',
            access: 'Subscription',
            genres: 'PressRelease',
            keywords: 'markets, stocks',
            stockTickers: 'NASDAQ:EXM',
        ),
    ],
);

$xml = $renderer->renderUrlSet([$url]);
echo $xml;
```

### Generating a News Sitemap with Arrays

```php
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

$renderer = new SitemapXmlStringRenderer();

$xml = $renderer->renderUrlSet([
    [
        'loc' => 'https://example.com/news/story',
        'news' => [
            [
                'publicationName' => 'Example Daily',
                'publicationLanguage' => 'en',
                'publicationDate' => '2026-07-01T10:00:00+00:00',
                'title' => 'Breaking News',
                'access' => 'Subscription',
                'genres' => 'PressRelease',
                'keywords' => 'markets, stocks',
                'stockTickers' => 'NASDAQ:EXM',
            ],
        ],
    ],
]);
echo $xml;
```

### Combined Sitemap Example

You can seamlessly combine news sitemap tags with hreflang alternate URLs, images, and videos.

```php
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

$renderer = new SitemapXmlStringRenderer();

$url = new SitemapUrlDTO(
    loc: 'https://example.com/en/news-product',
    alternates: [
        new SitemapAlternateUrlDTO('en', 'https://example.com/en/news-product'),
    ],
    images: [
        new SitemapImageDTO('https://cdn.example.com/products/product-1.jpg', 'Product 1'),
    ],
    videos: [
        new SitemapVideoDTO(
            thumbnailLoc: 'https://cdn.example.com/videos/thumb.jpg',
            title: 'Video title',
            description: 'Video description',
            contentLoc: 'https://cdn.example.com/videos/video.mp4',
        ),
    ],
    news: [
        new SitemapNewsDTO(
            publicationName: 'Example Daily',
            publicationLanguage: 'en',
            publicationDate: '2026-07-01',
            title: 'Product Launch',
        ),
    ],
);

$xml = $renderer->renderUrlSet([$url]);
echo $xml;
```
