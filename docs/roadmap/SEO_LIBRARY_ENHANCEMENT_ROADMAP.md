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

## Phase Execution Standard (Mandatory for Future Phases)

Every future Phase must follow the lifecycle below. A Phase must not move directly from
an idea to implementation, and an existing Phase status is not changed by this standard
unless that Phase is explicitly reviewed and updated.

`Blueprint → Draft Integration PR → Work Units → Verification → Documentation Sweep → Final Review vs main → Ready → Merge`

### Blueprint / Draft Gate

Before any implementation begins, the Phase must have a Blueprint / Draft that records:

* Current State
* Gaps
* Decisions / Contracts
* Scope and Out of Scope
* Work Units
* Test Matrix
* Documentation Impact
* Definition of Done

The Blueprint for each Phase must be stored under `docs/blueprints/**`.

### Integration Workflow

At the beginning of the Phase, create an Integration branch and an Integration PR as a
Draft. Each Work Unit PR must target that Phase's Integration branch, never `main`.
After a Work Unit is accepted, merge it into the Integration branch. A Work Unit must
not be merged directly into `main`.

The Integration PR is the only final path for merging the complete Phase into `main`.
It must remain Draft until Verification, the Documentation Sweep, and the Final Review
against the latest `main` are complete. Only then may it be converted to Ready and
merged into `main`.

### Work Unit Contract

Each Work Unit must define all of the following before implementation:

* Clear Scope
* Expected Files
* Required Tests
* Dependencies, if any
* Done Criteria

Work Units must remain within their declared Scope and must not silently expand the
Phase's contracts or Out of Scope items.

### Documentation Impact Review

Before a Phase can be completed, a Documentation Impact Review is mandatory. The review
must explicitly classify each applicable documentation layer as either requiring an
update, reviewed with no update required, or deferred with a documented reason:

* `README.md`
* `docs/SEO_LIBRARY_REFERENCE.md`
* `docs/guides/USAGE_GUIDE.md`
* `docs/guides/INTEGRATION_GUIDE.md`, when integration or usage changes
* `docs/SEO/**`
* `docs/phases/**`
* `docs/verification/**`
* `examples/**`
* `docs/roadmap/SEO_LIBRARY_ROADMAP.md`
* `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md`

Not every path must change for every Phase, but every applicable path must be reviewed
and recorded as `updated`, `reviewed-no-change`, or `deferred-with-reason`.

### Verification and Readiness Gates

After implementation is complete, the Phase must proceed through these gates in order:

1. Verification
2. Documentation Sweep
3. Final Review of the complete result against the latest `main`
4. Convert the Integration PR from Draft to Ready
5. Merge only after the Ready review is complete

The Integration PR must not be marked Ready before Verification, the Documentation
Sweep, and the final review against the latest `main` are complete.

### Phase Completion Criteria

A Phase may be marked `Complete` in this roadmap only after all of the following are
true:

* Implementation is complete.
* Tests are complete and passing for the declared Test Matrix.
* Verification is complete.
* Documentation is synchronized with the actual behavior.
* Required examples are added or updated.
* The roadmap status is updated.
* Limitations and deferred work are documented.
* The complete result has passed final review against the latest `main`.

---

# Phase 8: Developer Experience & Usage Documentation

## Goal

Make the library easier to understand, test manually, and integrate into real projects.

## 8A: Usage Guide

Add:

* `docs/guides/USAGE_GUIDE.md`
* Basic SEO head rendering example
* Product page SEO example
* Category page SEO example
* Homepage SEO example
* JSON-LD example
* Sitemap example
* Fluent builder example

## 8B: Integration Guide

Add:

* `docs/guides/INTEGRATION_GUIDE.md`

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

# Phase 13O — Advanced Product & Variant Structured Data (Complete)

## Goal

Fully reflect the structured-data gaps that previously existed in the library. This section addressed deferred requirements (ProductGroup, product variant structured-data support, richer schema / CI validation direction) and newly identified enhancements (first-class AggregateOffer support) while completing partially implemented features (Product typed-field completeness). Deep Schema.org semantic validation remains outstanding in Phase 13P.

## 13O-1 Product Builder Completeness (Complete)

*   missing Product fields (GTIN, MPN)
*   richer Offer integration (seller, priceValidUntil)
*   variant relationships / variant properties
*   preserve backward compatibility

## 13O-2 ProductGroup / Product Variants (Complete)

*   **Note:** This was a historically planned requirement that is now completed.
*   ProductGroup builder
*   productGroupID
*   variesBy
*   hasVariant
*   Product variant relationships (isVariantOf, inProductGroupWithID)
*   variant properties (color, size, material, pattern)

## 13O-3 AggregateOffer (Complete)

*   **Note:** This is a newly identified enhancement.
*   dedicated builder (`AggregateOfferJsonLdBuilder`)
*   pricing range fields (lowPrice, highPrice)
*   offer count
*   nested offers where appropriate
*   Product integration

## 13O-4 Typed Structured Data Composition (Complete)

*   Product + Offer
*   Product + AggregateOffer
*   ProductGroup + variants
*   avoid raw-array requirement for supported schemas

## 13O-5 Tests / Examples / Documentation (Complete)

*   unit/manual tests following repository convention
*   ProductGroup example
*   multi-variant example
*   AggregateOffer example
*   migration/backward-compatibility notes

---

# Phase 13P — Structured Data Semantic Validation (Complete)

## Goal

Provide deep semantic validation for the in-scope Schema.org types, distinguishing
Schema.org correctness from Google eligibility. Phase 13P now includes the completed
validation foundation, generic structural and graph validation, scoped semantic
validation for Product, Offer, AggregateOffer, and ProductGroup, validation-pipeline
compatibility, Verification, Documentation Sweep, and Final Review against the latest
`main`. All other JSON-LD schema types remain outside the scope of Phase 13P. Google
Rich Results / Merchant eligibility remains a separate layer and Future Work.

## Checks

This phase must clearly distinguish four validation layers:

1. **Current validation foundation (Complete)**
   * basic JSON-LD array shape
   * non-empty schema entries
   * existing generic/meta validation

2. **Generic structural validation (Complete)**
   * `@type` presence
   * root, list, nested, and `@graph` traversal
   * deterministic field paths and well-formed type identity

3. **In-scope semantic validation (Complete)**
   * Product
   * Offer
   * AggregateOffer
   * ProductGroup
   * schema-type relationships/properties within the in-scope types
   * specific validators (Product, Offer, AggregateOffer, ProductGroup)
   * all other JSON-LD schema types are outside the scope of Phase 13P

4. **Google-specific eligibility / Rich Results / Merchant validation (separate Future Work)**
   * Google eligibility rules are not identical to Schema.org validity
   * the library must not claim to perfectly reproduce Google's validators
   * CI-friendly output

## Completion Status

Phase 13P is **Complete**. Work Units 1–4, the Verification Gate, the Documentation
Sweep, and Final Review against the latest `main` all passed.

Final Review compared Draft SHA `2692f66e12a62da0c8e4579c4796dcb94942af78` against
`main` SHA `ce087cf6682f411b8884ee4e3a1c0f56f9fb5f9b`. The Phase remains limited to
Product, Offer, AggregateOffer, and ProductGroup deep semantic validation; Google Rich
Results and Merchant eligibility remain Future Work.
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

* `Admin/DTO/SerpPreviewDTO.php`
* `Admin/DTO/SocialPreviewDTO.php`

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

# Phase 21: Quality / CI / Release Readiness (Extended)

## Goal

Protect the library from regressions and expand CI/CD pipelines to include structured-data and Rich Results validation.

## Add GitHub Actions

Run:

* composer validate
* composer install
* php -l for src/tests/examples
* phpstan analyse
* all manual PHP tests
* examples syntax check

## Optional Release-Readiness Items

Add:

* release checklist
* tag checklist
* package usage checklist

## Google / CI Validation Enhancements

Extend Phase 21 with structured-data verification:

* CI/CD schema validation
* CI-friendly structured-data validation output
* optional Google Rich Results verification workflow
* Rich Results monitoring direction
* Merchant/Product eligibility verification where applicable

## Constraints

* framework-neutral
* host-agnostic
* no mandatory Google/Search Console/external-service runtime dependency
* external verification/monitoring belongs in optional tooling/integration workflows

---

# Recommended Implementation Order

## Best practical order

1. Phase 13P: Structured-data semantic validation
2. Phase 21: Optional CI / Rich Results verification enhancements
3. Phase 8: Developer Experience & Docs

## Later / optional

* Image sitemap
* Video sitemap
* Import/export helpers
* CLI tooling
* advanced social previews
* [Optional Admin SEO Control Layer (RFC)](proposals/OPTIONAL_ADMIN_SEO_CONTROL_LAYER_RFC.md)

---

# Priority Recommendation

The strongest next real-world enhancement is:

## Semantic structured-data validation (Phase 13P)

After that:

1. CI / Rich Results verification enhancements (Phase 21)
