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


## Page Preset Factories

The library provides high-level preset factories in `SeoPagePresetFactory` and specialized domain factories (`EcommerceSeoPresetFactory`, `ContentSeoPresetFactory`, `LocalBusinessSeoPresetFactory`) to effortlessly generate fully-configured SEO output (`SeoPagePresetOutputDTO`) for standard page types without manually calling multiple builders. They compose existing builders and return DTO/output only.

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

## Hreflang Head Link Builder

The `HreflangLinkBuilder` provides a framework-agnostic way to construct and render `<link rel="alternate" hreflang="..." href="...">` tags for the HTML `<head>`. It does not interact with or modify sitemap XML hreflang generation.

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

## Admin Previews

The `SerpPreviewDTO`, `SocialPreviewDTO`, `SerpPreviewFactory`, and `SocialPreviewFactory` are used to generate mock views of Search Engine Results Pages (SERP) and Social Previews for consuming host applications in an admin dashboard or CMS. They are for host admin/CMS previews and do not render UI.

```php
use Maatify\Seo\Admin\Preview\SerpPreviewFactory;
use Maatify\Seo\Admin\Preview\SocialPreviewFactory;

$serpPreviewDTO = SerpPreviewFactory::fromPreset($presetOutput);
$socialPreviewDTO = SocialPreviewFactory::fromPreset($presetOutput, siteName: 'My Awesome Site');

// Export to JSON array for your frontend
$serpArray = $serpPreviewDTO->toArray();
$socialArray = $socialPreviewDTO->toArray();
```

## Metadata Import/Export Helpers

The `SeoMetadataExporter` aggregates overrides, redirects, and slug history into a versioned format (`SeoMetadataExportDTO`), and the `SeoMetadataImporter` parses an array-based schema payload to validate and save the data (`SeoMetadataImportResultDTO`). The importer is create-only according to current repository contracts.

```php
use Maatify\Seo\Admin\Import\SeoMetadataImporter;

// Perform a Dry Run (no data is persisted to the database)
$dryRunResult = $importer->importArray($payload, dryRun: true);

// Perform the actual Import
$importResult = $importer->importArray($payload, dryRun: false);
```

## Social Builders

The library provides dedicated builders for Open Graph (`OpenGraphBuilder`), Twitter/X Cards (`TwitterCardBuilder`), and a combined orchestration layer (`SocialPreviewBuilder`). Use them directly when you need strict type-safety, explicit ordering, and dedicated image control independently of the fluent builder or presets.

```php
use Maatify\Seo\Web\Social\SocialPreviewBuilder;

$builder = new SocialPreviewBuilder();
$builder->setTitle('Example Title')
        ->setDescription('Example description')
        ->setImage('https://example.com/cover.jpg')
        ->setTwitterCard('summary_large_image');

echo $builder->toHtml();
```

## JSON-LD Builders

A comprehensive suite of specialized JSON-LD builders is available to natively generate arrays or JSON strings for structured data:
- `ProductJsonLdBuilder`
- `ArticleJsonLdBuilder` (also BlogPosting, NewsArticle)
- `OrganizationJsonLdBuilder` (also LocalBusiness, Corporation, Store)
- `FAQPageJsonLdBuilder`
- `HowToJsonLdBuilder`
- `EventJsonLdBuilder`
- `ItemListJsonLdBuilder`
- `WebPageJsonLdBuilder`
- `WebSiteJsonLdBuilder`
- `ReviewJsonLdBuilder`
- `AggregateRatingJsonLdBuilder`
- `OfferJsonLdBuilder`
- `ServiceJsonLdBuilder`
- `LocalBusinessJsonLdBuilder`

```php
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\OrganizationJsonLdBuilder;

$productBuilder = new ProductJsonLdBuilder();
$productBuilder->setName('Example Product')
               ->setPrice('29.99')
               ->setCurrency('USD');
$productJson = $productBuilder->toJson();

$articleBuilder = new ArticleJsonLdBuilder();
$articleBuilder->setHeadline('Example Article')
               ->setAuthor('Jane Doe')
               ->setDatePublished('2023-10-27T10:00:00Z');
$articleJson = $articleBuilder->toJson();

$orgBuilder = new OrganizationJsonLdBuilder();
$orgBuilder->setName('Example Organization')
           ->setUrl('https://example.com')
           ->setLogo('https://example.com/logo.png');
$orgJson = $orgBuilder->toJson();
```

## MetaRobotsBuilder and CanonicalUrlBuilder

These standalone builders provide fluent interfaces to manage `<meta name="robots">` tags and `<link rel="canonical">` tags independently. They are also reused internally by higher-level presets.

```php
use Maatify\Seo\Web\Robots\MetaRobotsBuilder;
use Maatify\Seo\Web\Indexing\CanonicalUrlBuilder;

$robotsBuilder = new MetaRobotsBuilder();
$robotsBuilder->index()->follow()->maxSnippet(50);
echo $robotsBuilder->toHtml();

$canonicalBuilder = new CanonicalUrlBuilder('https://example.com');
$canonicalBuilder->setPath('about-us')->setQueryParams(['sort' => 'desc']);
echo $canonicalBuilder->toHtml();
```
