# SEO Library Enhancement Roadmap

## Current Status

The SEO library core is complete.

Completed:

* Core Shared layer
* Admin layer
* Web layer
* Bootstrap/DI wiring
* Final compliance audit
* Phase 7 usability and rendering layer
* HTML renderers
* Output DTOs
* Fluent SEO builder
* Optional Spatie schema adapter
* Sitemap XML string renderer
* Output showcase example

The next work should be treated as optional enhancement phases, not required core completion.

---

# Phase 8: Developer Experience & Usage Documentation

## Goal

Make the library easier to understand, test manually, and integrate into real projects.

## 8A: Usage Guide

Add:

* `docs/USAGE_GUIDE.md`
* Basic SEO head rendering example
* Product page SEO example
* Category page SEO example
* Homepage SEO example
* JSON-LD example
* Sitemap example
* Fluent builder example

## 8B: Integration Guide

Add:

* `docs/INTEGRATION_GUIDE.md`

Cover:

* Plain PHP usage
* Slim usage without coupling
* Laravel usage without adding Laravel dependency
* Template usage examples
* How host apps should call renderers
* How host apps should return HTTP responses themselves

## 8C: More Examples

Add:

* `examples/basic-head-render.php`
* `examples/product-page-seo.php`
* `examples/category-page-seo.php`
* `examples/sitemap-output.php`
* `examples/schema-output.php`

---

# Phase 9: Robots.txt Output Helpers

## Goal

Add framework-neutral helpers for generating `robots.txt` content as plain strings.

## Suggested Classes

* `Web/Robots/RobotsTxtRenderer.php`
* `Web/Robots/DTO/RobotsRuleDTO.php`
* `Web/Robots/DTO/RobotsTxtDTO.php`

## Features

Support:

* user-agent
* allow
* disallow
* sitemap URLs
* crawl-delay
* comments
* multiple user-agent sections

## Example Output

```txt
User-agent: *
Allow: /
Disallow: /admin
Sitemap: https://example.com/sitemap.xml
```

## Constraints

* Return string only
* No HTTP response
* No filesystem write
* No framework coupling

---

# Phase 10: Sitemap Enhancements

## Goal

Expand sitemap support beyond basic URL sitemap rendering.

## 10A: Sitemap Index String Renderer

Add:

* `Web/Sitemap/SitemapIndexXmlStringRenderer.php`

Support:

* sitemap index XML
* `loc`
* `lastmod`
* multiple sitemap files

Example:

```xml
<sitemapindex>
  <sitemap>
    <loc>https://example.com/sitemap-products.xml</loc>
    <lastmod>2026-07-01</lastmod>
  </sitemap>
</sitemapindex>
```

## 10B: Hreflang / Alternate URL Support in Web Renderer

Current core generator supports alternates.

Enhance Web string helper to support:

* `xhtml:link`
* `hreflang`
* alternate URLs
* `x-default`

## 10C: Image Sitemap Support

Optional later.

Support:

* image URL
* image title
* image caption
* image geo location
* image license

## 10D: Video Sitemap Support (Complete)

Support:

* video thumbnail
* title
* description
* duration
* publication date
* content URL
* embed URL

## 10E: News Sitemap Support (Complete)

Support:

* Google News sitemap tags
* publication name, language
* title, date
* access, genres, keywords, stock tickers

---

# Phase 11: SEO Validation Helpers (Complete)

## Goal

Add validation/audit helpers that inspect SEO data and return warnings/errors, and score helpers to easily compute actionable scores from validation results.

This is one of the most useful real-world enhancements.

## Suggested Classes

* `Shared/Validation/SeoMetaValidator.php`
* `Shared/Validation/SeoSchemaValidator.php`
* `Shared/Validation/SitemapValidator.php`
* `Shared/DTO/Validation/SeoValidationIssueDTO.php`
* `Shared/DTO/Validation/SeoValidationResultDTO.php`

## Checks

Validate:

* title missing
* title too short
* title too long
* description missing
* description too short
* description too long
* canonical missing
* canonical invalid URL
* robots invalid value
* OpenGraph title missing
* OpenGraph description missing
* OpenGraph image missing
* Twitter card missing
* JSON-LD missing `@type`
* sitemap URL invalid
* sitemap priority outside range
* sitemap changefreq invalid

## Output

Return structured DTO, not strings only.

Example:

```php
SeoValidationResultDTO {
    passed: false,
    warnings: [...],
    errors: [...]
}
```

## Phase 11B: SEO Validation Score Helpers (Complete)

Provide a way to calculate a simple score directly from validation results without introducing heavy frameworks.

* `Web/Validation/SeoValidationScoreCalculator.php`
* `Web/Validation/DTO/SeoValidationScoreDTO.php`

Return:

* score from 0 to 100
* grade
* error count
* warning count
* info count
* point deductions
* isHealthy flag

Useful for admin dashboard checks, automated QA, and continuous integration workflows before publishing pages.

## Phase 11C: SEO Validation Report Helpers (Complete)

Provide a comprehensive reporting mechanism that combines both the `SeoMetaValidator` and `SeoValidationScoreCalculator` into a single DTO.

* `Web/Validation/SeoValidationReportBuilder.php`
* `Web/Validation/DTO/SeoValidationReportDTO.php`

Return fields:

* isValid
* isHealthy
* score
* grade
* errorCount
* warningCount
* infoCount
* issues
* errors
* warnings
* info
* deductions
* context
* summary

Summary status rules:

* fail if validation has errors
* warning if no errors but warnings exist or score is not healthy
* pass if valid, healthy, and no warnings

Useful for admin dashboard checks, automated QA, and continuous integration workflows before publishing pages.

## Phase 11D: SEO Validation Presets (Complete)

Provides pre-configured validation option arrays (`strict`, `minimal`, `standard`) via `SeoValidationPreset` to streamline common workflows when using the validator, calculator, and report builder.

## Phase 11E: SEO Validation Report Exporter (Complete)

Provide a framework-neutral helper to export validation reports into multiple formats:

*   `Web/Validation/SeoValidationReportExporter.php`

Return formats:

*   Complete array
*   JSON string
*   Compact summary array
*   Markdown string

Useful for logging, dashboards, CI output, and PR issue comments.

## Phase 11F: SEO Validation Batch Report Helpers (Complete)

Provide a framework-neutral builder to batch validate multiple pages/products/entities in a single run:

*   `Web/Validation/SeoValidationBatchReportBuilder.php`
*   `Web/Validation/DTO/SeoValidationBatchReportDTO.php`

Features:
*   Requires a non-empty list of items. Each item requires a `meta` array/object, and accepts an optional `context` array.
*   Supports a `sharedContext` merge, where item context overrides shared context.
*   Provides aggregate counts and score stats: `totalCount`, `validCount`, `invalidCount`, `healthyCount`, `unhealthyCount`, `errorCount`, `warningCount`, `infoCount`, `averageScore`, `minScore`, `maxScore`.
*   Summary rules:
    *   Fail if any report is invalid.
    *   Warning if all valid but any report is unhealthy or has warnings.
    *   Pass if all valid, healthy, and warning-free.

## Phase 11G: SEO Validation Batch Report Exporter (Complete)

Provide a framework-neutral helper to export batch validation reports into multiple formats:

*   `Web/Validation/SeoValidationBatchReportExporter.php`

Formats:
*   Array (full batch DTO data)
*   JSON (supports custom flags)
*   Compact summary array
*   Markdown string

Useful for logging, dashboards, CI output, and PR issue comments.

---

# Phase 13: JSON-LD Schema Builders

## Goal

Make schema creation easier without forcing users to write raw arrays.

## Suggested Builders

* `Shared/Schema/Builder/WebSiteSchemaBuilder.php`
* `Shared/Schema/Builder/OrganizationSchemaBuilder.php`
* `Shared/Schema/Builder/ProductSchemaBuilder.php`
* `Shared/Schema/Builder/BreadcrumbSchemaBuilder.php`
* `Shared/Schema/Builder/ArticleSchemaBuilder.php`
* `Shared/Schema/Builder/FAQSchemaBuilder.php`

## Output

Each builder should return:

* `JsonLdSchemaDTO`

## Constraints

* No Spatie dependency
* No framework coupling
* No static global state
* No HTTP handling

---

# Phase 14: SEO Factories / Page Presets (Complete via Batch 1B & 1C)

## Goal

Reduce repetitive creation of `MetaTagsDTO`.

## Suggested Factories

* `Shared/Factory/HomePageSeoFactory.php`
* `Shared/Factory/ProductPageSeoFactory.php`
* `Shared/Factory/CategoryPageSeoFactory.php`
* `Shared/Factory/ArticlePageSeoFactory.php`

## Suggested Input DTOs

* `ProductSeoInputDTO`
* `CategorySeoInputDTO`
* `ArticleSeoInputDTO`
* `HomePageSeoInputDTO`

## Example

A product input can include:

* product name
* short description
* canonical URL
* image URL
* site name
* price
* currency
* availability

Factory returns:

* `MetaTagsDTO`
* optional JSON-LD schema DTOs

---

# Phase 15: Canonical / URL / Hreflang Helpers (Complete via Phase 15A & Batch 3)

## Goal

Centralize common URL SEO helpers.

## Suggested Classes

* `Shared/Url/CanonicalUrlResolver.php`
* `Shared/Url/HreflangUrlBuilder.php`
* `Shared/Url/SeoUrlNormalizer.php`

## Features

Support:

* canonical URL normalization
* query parameter allowlist
* remove tracking params
* language URL generation
* hreflang mapping
* x-default generation

## Example

Input:

```php
https://example.com/product?id=10&utm_source=x
```

Output:

```php
https://example.com/product?id=10
```

---

# Phase 16: Meta Robots Helpers (Complete via Batch 1A)

## Goal

Make robots meta values safer and less manual.

## Suggested Classes

* `Shared/Robots/MetaRobotsBuilder.php`
* `Shared/Robots/MetaRobotsDirective.php`

## Support

* index
* noindex
* follow
* nofollow
* noarchive
* nosnippet
* max-snippet
* max-image-preview
* max-video-preview

## Output

```txt
index,follow,max-image-preview:large
```

---

# Phase 17: OpenGraph / Twitter Presets (Complete via Phase 14)

## Goal

Make social metadata easier to create.

## Suggested Classes

* `Shared/Social/OpenGraphPresetFactory.php`
* `Shared/Social/TwitterCardPresetFactory.php`

## Presets

* website
* product
* article
* profile
* video
* image

---

# Phase 18: Admin Preview DTOs (Complete via Batch 2)

## Goal

Prepare data for admin preview screens without adding UI.

## Suggested DTOs

* `Admin/DTO/SeoPreviewDTO.php`
* `Admin/DTO/SocialPreviewDTO.php`
* `Admin/DTO/SearchResultPreviewDTO.php`

## Features

Return structured preview data for:

* Google result preview
* Facebook/OpenGraph preview
* Twitter/X card preview
* raw HTML head preview
* validation issues

No UI should be implemented inside the library.

---

# Phase 19: Import / Export Helpers

## Goal

Allow host apps to export/import SEO metadata safely.

## Suggested Classes

* `Shared/Export/SeoMetadataExporter.php`
* `Shared/Import/SeoMetadataImporter.php`

## Formats

* array
* JSON
* CSV later if needed

## Use Cases

* migration
* backup
* admin tools
* bulk editing

---

# Phase 20: CLI-Friendly Examples

## Goal

Add example scripts for maintainers and developers.

## Examples

* generate sample sitemap
* generate sample robots.txt
* validate sample page SEO
* print JSON-LD schema
* audit product SEO

No actual CLI package is required unless needed later.

---

# Phase 21: Quality / CI / Release Readiness

## Goal

Protect the library from regressions.

## Add GitHub Actions

Run:

* composer validate
* composer install
* php -l for src/tests/examples
* phpstan analyse
* all manual PHP tests
* examples syntax check

## Optional

Add:

* release checklist
* tag checklist
* package usage checklist

---

# Recommended Implementation Order

## Best practical order

1. Phase 8: Developer Experience & Docs
2. Phase 11: SEO Validation Helpers
3. Phase 9: Robots.txt Output Helpers
4. Phase 10A: Sitemap Index Renderer
6. Phase 15: Canonical / URL / Hreflang Helpers
7. Phase 13: JSON-LD Schema Builders
8. Phase 14: SEO Factories / Page Presets
9. Phase 16: Meta Robots Helpers
10. Phase 18: Admin Preview DTOs
11. Phase 21: CI / Release Readiness

## Later / optional

* Image sitemap
* Video sitemap
* Import/export helpers
* CLI tooling
* advanced social previews

---

# Priority Recommendation

The strongest next real-world enhancement is:

## SEO Validation Helpers

Because it lets the host application know whether a page/product/category has good SEO or not.

After that:

1. Robots.txt renderer
2. Sitemap index renderer
3. JSON-LD schema builders
4. Usage docs and examples polish
