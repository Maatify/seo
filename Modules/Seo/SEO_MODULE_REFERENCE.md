# SEO Module Reference

Complete API reference and design rules for the Maatify SEO library.

## Current Module Structure
The module is divided into Shared, Admin (planned), and Web (planned) components, ensuring clean boundaries between persistence, business logic, and presentation.

**Note:** For the SEO library, `src/Web/` is the approved layer name replacing the standard `Customer/` layer. `src/Web/` is strictly for host website consumption services and DTOs. It does not include controllers, routes, HTTP responses, or framework integration.

```text
Modules/Seo/
├── docs/
├── schema/
│   ├── maa_seo_overrides.sql
│   ├── maa_seo_redirects.sql
│   └── maa_seo_slug_history.sql
└── src/
    ├── Admin/
    │   ├── Redirect/
    │   │   ├── Command/
    │   │   ├── Contract/
    │   │   ├── DTO/
    │   │   ├── Infrastructure/Repository/
    │   │   └── Service/
    │   ├── SeoOverride/
    │   │   ├── Command/
    │   │   ├── Contract/
    │   │   ├── DTO/
    │   │   ├── Infrastructure/Repository/
    │   │   └── Service/
    │   └── SlugHistory/
    │       ├── Command/
    │       ├── Contract/
    │       ├── DTO/
    │       ├── Infrastructure/Repository/
    │       └── Service/
    ├── Bootstrap/
    │   └── SeoBindings.php
    ├── Exception/
    │   ├── SeoCodeAlreadyExistsException.php
    │   ├── SeoConflictException.php
    │   ├── SeoErrorCode.php
    │   ├── SeoExceptionInterface.php
    │   ├── SeoInvalidArgumentException.php
    │   └── SeoNotFoundException.php
    ├── Shared/
    │   ├── Command/
    │   ├── Contract/
    │   ├── DTO/
    │   ├── Infrastructure/Persistence/
    │   └── Service/
    └── Web/
        └── SeoRender/
            ├── DTO/
            └── Service/
```

## Schema Tables

### `maa_seo_slug_history`
Records old slugs when an entity's slug changes, to prevent reuse and facilitate automatic redirects.
- **Key Columns:** `entity_type`, `entity_id`, `language_id`, `old_slug`
- **Soft Delete:** `deleted_at DATETIME NULL`

### `maa_seo_redirects`
Fast lookup table for the routing layer to find if a requested URL/slug should 301 to a new one, or 410 (Gone).
- **Key Columns:** `entity_type`, `language_id`, `requested_slug`, `target_entity_type`, `target_entity_id`, `http_status`
- **Soft Delete:** `deleted_at DATETIME NULL`

### `maa_seo_overrides`
Allows marketers/admins to manually override generated Meta Title and Description per entity without polluting the host's primary tables.
- **Key Columns:** `entity_type`, `entity_id`, `language_id`, `meta_title`, `meta_description`
- **Soft Delete:** `deleted_at DATETIME NULL`

## Repository Contracts and PDO Implementations
Repositories handle pure database operations (CRUD) without business logic formatting. All database persistence uses plain PDO implementations.

- **`RedirectRepositoryInterface`**: Implemented by `PdoRedirectRepository`. Manages redirect records (create, update, find by ID, find active by requested slug, soft delete, hard delete).
- **`SlugHistoryRepositoryInterface`**: Implemented by `PdoSlugHistoryRepository`. Manages slug history records (create, find by ID, find active by slug, find active for entity, soft delete, hard delete).
- **`SeoOverrideRepositoryInterface`**: Implemented by `PdoSeoOverrideRepository`. Manages SEO override records (create, update, find by ID, find active for entity, soft delete, hard delete).

## Core Services

### Schema Generator Service
The `SchemaGeneratorService` handles the generation of structured data for SEO by converting Data Transfer Objects (DTOs) into JSON-LD arrays.
- **Responsibility**: It transforms host-provided `JsonSerializable` DTOs into structured JSON-LD output wrapped in `JsonLdSchemaDTO` objects. The service contains **no SQL, no repository access, no host data fetching, and no framework rendering**. It does not generate HTML or `<script>` tags.
- **`generate()` Behavior**: Accepts any `\JsonSerializable` schema DTO and returns a `JsonLdSchemaDTO`.
- **`generateGraph()` Behavior**: Accepts a list of `\JsonSerializable` schema DTOs and returns a `JsonLdSchemaDTO` containing:
  - `@context: https://schema.org`
  - `@graph: [...]` (the serialized schema array)

### Meta Generator Service
The `MetaGeneratorService` orchestrates the assembly of `<title>`, `<meta>` description, canonical, robots, OpenGraph, and Twitter tags per the current language and provided entity data.
- **Responsibility**: It builds host-agnostic meta tags from host-provided defaults by accepting a `GenerateMetaTagsCommand` and returning a `MetaTagsDTO`.
- **Override Fallback Behavior**: The service checks if a manual SEO override exists in `maa_seo_overrides` via the `SeoOverrideQueryService`. If an override exists for the specific entity and language, it replaces the default title and/or description. If the query service throws a `SeoNotFoundException`, it treats this as "no override" and falls back to the host-provided defaults.
- **Canonical URL Resolution**: It determines the canonical URL first by checking the `canonicalUrl` property in the `GenerateMetaTagsCommand`. If that is not provided, it falls back to generating the URL via the `HostUrlGeneratorInterface` using the entity details.

### DTOs and Commands
- **`GenerateMetaTagsCommand`**: Encapsulates all data required to generate meta tags:
  - `entityType` (string)
  - `entityId` (string)
  - `languageId` (int)
  - `defaultTitle` (string)
  - `defaultDescription` (?string)
  - `slug` (?string)
  - `canonicalUrl` (?string)
  - `robots` (string, defaults to 'index,follow')
- **`MetaTagsDTO`**: Aggregates final computed strings for SEO headers:
  - `title` (string)
  - `description` (?string)
  - `canonicalUrl` (?string)
  - `robots` (string)
  - `openGraphTitle` (?string)
  - `openGraphDescription` (?string)
  - `openGraphUrl` (?string)
  - `twitterTitle` (?string)
  - `twitterDescription` (?string)

### Sitemap Generator Service
The `SitemapGeneratorService` generates valid XML strings for sitemap indexes and URL sets using `XMLWriter`.
- **Responsibility**: It is fully host-agnostic and framework-agnostic. It accepts arrays of strict DTOs and returns a generated XML string in memory. The service contains **no SQL, no repository access, no host data fetching, no file writing, and no HTTP response generation**.
- **`generateUrlSitemap()` Behavior**: Accepts a list of `SitemapUrlDTO` instances and returns a `SitemapGenerationResultDTO` containing the URL sitemap XML (`urlset`). It automatically adds the XHTML namespace if any `SitemapUrlDTO` contains alternate hreflang links.
- **`generateSitemapIndex()` Behavior**: Accepts a list of `SitemapIndexEntryDTO` instances and returns a `SitemapGenerationResultDTO` containing the sitemap index XML (`sitemapindex`).

### Sitemap DTOs
All inputs to the sitemap generator are strictly validated `final readonly` DTOs.
- **`SitemapUrlDTO`**: Represents a single `<url>` entry. Contains `loc`, optional `lastmod`, `changefreq`, `priority`, a list of `SitemapAlternateUrlDTO` instances for hreflang links, a list of `SitemapImageDTO` instances for images, and a list of `SitemapVideoDTO` instances for videos.
- **`SitemapAlternateUrlDTO`**: Represents a single `<xhtml:link>` hreflang alternate. Contains `hreflang` and `url`.
- **`SitemapImageDTO`**: Represents a single `<image:image>` entry. Contains `loc`, optional `title`, `caption`, `geoLocation`, and `license`.
- **`SitemapVideoDTO`**: Represents a single `<video:video>` entry. Contains `thumbnailLoc`, `title`, `description`, optional `contentLoc`, `playerLoc`, `duration`, and `publicationDate`.
- **`SitemapIndexEntryDTO`**: Represents a single `<sitemap>` entry in a sitemap index. Contains `loc` and optional `lastmod`.
- **`SitemapGenerationResultDTO`**: Represents the result of a generation operation. Contains the full `xml` string, the `entryCount` (exposed as `entry_count` in JSON serialization), and the `type` (either `urlset` or `sitemapindex`).

### Redirect and Slug Management
- **`RedirectManagerService`**: Orchestrates redirect decisions without directly accessing the database (uses `RedirectQueryService` and `RedirectCommandService`). It accepts a `ResolveRedirectCommand` and returns a `RedirectDecisionDTO`. It does not emit HTTP responses, does not perform framework routing, and generates target URLs exclusively via `HostUrlGeneratorInterface`. Contains no SQL.
- **`SlugHistoryService`**: Manages entity slug history and optional redirect creation. Uses `SlugHistoryQueryService`, `SlugHistoryCommandService`, and optional `RedirectCommandService` exclusively through constructor injection. Redirect creation is optional and only happens when `RecordSlugChangeCommand::createRedirect` is true and `RedirectCommandService` is available. Contains no SQL and no direct repository access.
- **`RedirectDecisionDTO`**: A final readonly DTO containing the redirect decision (none, 301, 410) and target URL. It ensures the routing layer receives a standardized outcome.
- **`ResolveRedirectCommand`**: Command object containing parameters needed to resolve a redirect (`entityType`, `languageId`, `requestedSlug`, `requestedPath`).
- **`RecordSlugChangeCommand`**: Command object representing an entity slug change. Takes constructor values for `entityType`, `entityId`, `languageId`, `oldSlug`, `newSlug`, and `createRedirect`. It validates that `oldSlug` and `newSlug` are non-empty and different.

### Schema DTOs
All schema generation is powered by strict, host-agnostic Data Transfer Objects that implement `\JsonSerializable` or specific interfaces.
- **`JsonLdSchemaDTO`**: A final readonly DTO class implementing `\JsonSerializable` that wraps an `array<string, mixed>` JSON-LD schema and serializes it unchanged.
- **`GenericSchemaDTO`**: A flexible schema implementation that allows passing a raw array mapping for custom or less common schemas while still enforcing standard encoding constraints.
- **`BreadcrumbItemDTO`**: Represents a single node in a breadcrumb trail (name, target URL, position).
- **`BreadcrumbListDTO`**: Collection DTO holding multiple `BreadcrumbItemDTO` elements.
- **`BreadcrumbSchemaDTO`**: The root schema DTO encapsulating a `BreadcrumbListDTO` for standard JSON-LD output.
- **`WebPageSchemaDTO`**: Represents `WebPage` schema properties (name, description, url).
- **`WebsiteSchemaDTO`**: Represents `WebSite` schema (name, url, potential search actions).
- **`OrganizationSchemaDTO`**: Represents `Organization` schema documenting constructor-provided values: `name`, `url`, `logoUrl`, and `sameAsUrls`.
- **`ProductSchemaDTO`**: A robust representation of a `Product` schema documenting constructor-provided values: `name`, `description`, `sku`, `brandName`, and `additionalProperties` (which can be used by the host to supply offers, reviews, or other custom fields).

## Bootstrap Layer
The `Bootstrap/SeoBindings.php` class is the single shared binding entry point for the Shared, Admin, and Web layers.
- **Methods**: It exposes `shared()`, `admin()`, `web()`, and `all()` methods which return associative arrays of class strings to callable dependency definitions (factories).
- **Framework-neutral**: The bindings are entirely framework-neutral. They do not depend on Slim, Laravel, Symfony, PHP-DI, or any framework-specific containers. Host applications can adapt these definitions to any container by resolving dependencies using the same class/interface-string keys.
- **Dependencies**: It keeps host-provided dependencies documented:
  - Required: `PDO::class`
  - Optional: `HostUrlGeneratorInterface::class`

## Service Layer
Services manage the core business orchestration and throw standard `SeoNotFoundException` when entities are missing. They never perform SQL queries directly and strictly use constructor injection.

### Shared Layer
- **`RedirectCommandService`**: Orchestrates `create`, `update`, `softDelete`, and `hardDelete` operations for redirects.
- **`RedirectQueryService`**: Retrieves redirect records, throwing module exceptions on failure.
- **`SlugHistoryCommandService`**: Orchestrates `create`, `softDelete`, and `hardDelete` operations for slug history entries.
- **`SlugHistoryQueryService`**: Retrieves slug history records.
- **`SeoOverrideCommandService`**: Orchestrates `create`, `update`, `softDelete`, and `hardDelete` operations for SEO overrides.
- **`SeoOverrideQueryService`**: Retrieves SEO override records.

### Admin Layer
The Admin layer provides dedicated admin-facing services, DTOs, and commands for managing SEO capabilities in the backend. These classes are framework-agnostic, have no controllers, routes, or HTTP responses, and rely on constructor-injected Shared services for core logic without directly accessing the database.
- **`Admin/SeoOverride/`**: Admin-facing management of SEO overrides via `AdminSeoOverrideCommandService` and `AdminSeoOverrideQueryService`.
- **`Admin/Redirect/`**: Admin-facing management of SEO redirects via `AdminRedirectCommandService` and `AdminRedirectQueryService`.
- **`Admin/SlugHistory/`**: Admin-facing management of slug history via `AdminSlugHistoryCommandService` and `AdminSlugHistoryQueryService`.


## Web Layer
The Web layer provides website/frontend consumption services and DTOs. Following an approved exception, it uses `src/Web/` instead of the standard `src/Customer/` directory. These classes are strictly framework-agnostic and rely on Shared services via constructor injection. They do not access the database (no PDO), do not include controllers or routes, do not emit HTTP or PSR-7 responses, do not render templates, and do not output final HTML tags.
- **`Web/SeoRender/Command/RenderSeoPageCommand`**: A final readonly command object containing data necessary to render an SEO page payload (such as entity details, defaults, robots, schemas, and breadcrumbs). It validates its input in the constructor.
- **`Web/SeoRender/DTO/SeoPagePayloadDTO`**: A final readonly DTO (implementing `\JsonSerializable`) that wraps the computed meta tags, schemas, redirect decisions, and optional sitemap XML. It enforces that all inputs are valid.
- **`Web/SeoRender/Service/SeoPageRenderService`**: Orchestrates the generation of the SEO page payload using Shared services (`MetaGeneratorService`, `SchemaGeneratorService`, etc.). It supports computing redirect decisions via `RedirectManagerService` and generating sitemap strings via `SitemapGeneratorService` if injected.

### HTML Rendering Helpers
The Web layer includes optional HTML Rendering Helpers under `src/Web/Render/`. These renderers are framework-neutral, return pure PHP strings (HTML) or strictly-typed read-only DTOs, and do not emit HTTP responses. They can be manually consumed by any host application or template engine to safely render SEO data.
- **`Web/Render/MetaTagsHtmlRenderer.php`**: Renders `<title>`, `<meta name="description">`, canonical URLs, and robots tags with safely escaped text and attributes.
- **`Web/Render/OpenGraphHtmlRenderer.php`**: Renders `og:` metadata tags (`og:title`, `og:description`, `og:type`, `og:url`, `og:image`) with safely escaped properties and content.
- **`Web/Render/TwitterCardHtmlRenderer.php`**: Renders `twitter:` metadata tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`) with safely escaped names and content.
- **`Web/Render/JsonLdScriptRenderer.php`**: Safely encodes array or DTO structures into valid JSON and wraps them in `<script type="application/ld+json">` tags, mitigating XSS risks.
- **`Web/Render/SeoHeadHtmlRenderer.php`**: A facade that orchestrates the above renderers to combine and return the complete SEO HTML head payload dynamically. Contains `renderDto()` and `renderPayloadDto()` which return a `SeoHeadHtmlDTO`. The existing string rendering API via `render()` and `renderPayload()` remains fully available.

### Fluent Output Builder
The Web layer includes a framework-neutral fluent output builder under `src/Web/Builder/`.
- **`Web/Builder/FluentSeoBuilder.php`**: An instance-based (no static global state) builder that provides a fluent interface for configuring SEO attributes (`title()`, `description()`, OpenGraph, Twitter, and schemas). It validates input natively and can build a `MetaTagsDTO`, render a plain string via `SeoHeadHtmlRenderer`, or render a `SeoHeadHtmlDTO`. It accepts JSON-LD schemas as `JsonLdSchemaDTO` objects or associative arrays. The method `spatieSchema(object $schema)` is available to optionally convert and add Spatie schema objects directly to the builder. Existing Phase 7A/7B APIs remain fully available.

### Optional Spatie Schema Integration
The Web layer provides a dedicated adapter for optionally converting Spatie schema objects to native JSON-LD DTOs.
- **`Web/Schema/SpatieSchemaAdapter.php`**: A framework-neutral bridge that provides `supports(object $schema): bool` and `toJsonLdSchemaDTO(object $schema): JsonLdSchemaDTO` functionality. The integration is completely optional; `spatie/schema-org` is only a suggested dependency. The adapter uses runtime checks (e.g. `method_exists`) so no hard dependencies on Spatie classes are imported or required by production code. Existing Phase 7A/7B/7C APIs remain fully available and unaltered.

### Sitemap String Output
The Web layer includes optional helpers for rendering XML sitemap strings directly.
- **`Web/Sitemap/SitemapXmlStringRenderer.php`**: A framework-neutral helper that returns XML strings only and does not emit HTTP responses. It supports `SitemapUrlDTO` objects and raw array URL entries, correctly handling and validating `loc`, `lastmod`, `changefreq`, `priority`, hreflang `alternates`, `images`, `videos`, and `news` fields. When `alternates` are present, it dynamically adds the `xmlns:xhtml` namespace and renders `<xhtml:link>` elements. When `images` are present, it dynamically adds the `xmlns:image` namespace and renders `<image:image>` elements. When `videos` are present, it dynamically adds the `xmlns:video` namespace and renders `<video:video>` elements. When `news` data is present, it dynamically adds the `xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"` namespace and renders `<news:news>` elements. It safely escapes XML values natively. The existing `SitemapGeneratorService` remains fully available and unchanged.
- **`Web/Sitemap/SitemapIndexXmlStringRenderer.php`**: A framework-neutral helper that returns XML strings only for Sitemap Indexes. It supports `SitemapIndexEntryDTO` objects and raw array URL entries, safely escaping values.
- **`Shared/DTO/Sitemap/SitemapNewsDTO.php`**: A final readonly DTO that encapsulates Google News sitemap tags. It requires `publicationName`, `publicationLanguage`, `publicationDate`, and `title`. It optionally supports `access`, `genres`, `keywords`, and `stockTickers`. The `publicationDate` is accepted as-is and rendered exactly as provided. Required fields are trimmed, and providing empty required values throws a `SeoInvalidArgumentException`. Optional empty strings are normalized to `null` and are entirely omitted from the XML output. All values are safely escaped by `XMLWriter` when rendered.

### Robots.txt String Output
The Web layer includes framework-neutral helpers for generating `robots.txt` string output.
- **`Web/Robots/RobotsTxtRenderer.php`**: Renders a full `robots.txt` plain string from a provided `RobotsTxtDTO`.
- **`Web/Robots/DTO/RobotsTxtDTO.php`**: A DTO representing the complete file, containing global comments, a list of sitemaps, and user-agent rule blocks.
- **`Web/Robots/DTO/RobotsRuleDTO.php`**: A DTO representing a single user-agent block, with fields for `userAgent`, `allow`, `disallow`, `crawlDelay`, and comments.

### Validation Helpers
The Web layer includes framework-neutral helpers for auditing and validating generated SEO metadata arrays or objects.
- **`Web/Validation/SeoValidationPreset.php`**: Provides ready-made options for validation and scoring. Includes presets like `minimal`, `standard`, and `strict`. The helper dynamically loads options using `SeoValidationPreset::for(string $preset)` and returns an array structured with `validationOptions` and `scoreOptions`.
- **`Web/Validation/SeoMetaValidator.php`**: Validates SEO metadata arrays or objects against configurable options. It checks for missing fields, incorrect lengths, robots tag conflicts, schema problems, and missing OpenGraph/Twitter context fields, safely returning an aggregated DTO rather than throwing exceptions.
- **`Web/Validation/SeoValidationScoreCalculator.php`**: A framework-neutral calculator that accepts a `SeoValidationResultDTO` and computes a score from 0 to 100 based on standard SEO issue deductions. It optionally takes configuration to adjust the default deductions. It does not modify the original result or change validator logic.
- **`Web/Validation/SeoValidationReportBuilder.php`**: A framework-neutral builder that combines validation and scoring into a comprehensive report. It preserves optional context (e.g. url, entityType, language) as-is. It does not mutate the original input metadata, validation options, score options, or change the behavior of the underlying validator or calculator. It emits no HTTP output.
- **`Web/Validation/DTO/SeoValidationReportDTO.php`**: An aggregate DTO that stores the final results, including `isValid`, `isHealthy`, `score`, `grade`, arrays of `issues`, `errors`, `warnings`, `info`, `deductions`, `context`, and a `summary` object containing status and message based on validation state (fail, warning, pass).
- **`Web/Validation/DTO/SeoValidationIssueDTO.php`**: A DTO representing a single validation problem (`code`, `severity`, `message`, `field`).
- **`Web/Validation/DTO/SeoValidationResultDTO.php`**: An aggregate DTO that categorizes issues into errors, warnings, and info, and computes boolean flags like `isValid` and `hasWarnings`.
- **`Web/Validation/DTO/SeoValidationScoreDTO.php`**: An aggregate DTO holding the final score, letter grade (A-F), error counts, warning counts, the list of point deductions shapes (`code`, `severity`, `field`, `points`), and an `isHealthy` boolean indicator.
- **`Web/Validation/SeoValidationReportExporter.php`**: A framework-neutral exporter useful for logs, QA reports, dashboards, issue comments, CI output, and admin previews. It converts `SeoValidationReportDTO` objects into arrays (`toArray` returns full DTO data using existing report serialization), JSON strings (`toJson` returns a JSON string, uses readable defaults, respects custom JSON flags, and throws `SeoInvalidArgumentException` if encoding fails), compact summary arrays (`toSummaryArray` returns compact status/score/counts/message data), and human-readable Markdown (`toMarkdown` returns a plain Markdown report with summary, score, grade, valid/healthy flags, counts, errors/warnings/info groups, and optional deductions/context). It does not mutate the report DTO, does not call the validator, score calculator, or report builder internally, and does not emit HTTP headers, routes, controllers, or responses.
- **`Web/Validation/SeoValidationBatchReportBuilder.php`**: A framework-neutral builder that batches SEO validation reports for multiple pages/products/entities in a single run. It does not mutate input, has no HTTP integration, and ensures existing validation and scoring behaviors remain completely unchanged.
- **`Web/Validation/DTO/SeoValidationBatchReportDTO.php`**: An aggregate DTO that stores the final batch results, including `isValid`, `isHealthy`, `totalCount`, `validCount`, `invalidCount`, `healthyCount`, `unhealthyCount`, `errorCount`, `warningCount`, `infoCount`, `averageScore`, `minScore`, `maxScore`, a list of `reports` (`SeoValidationReportDTO`), and a `summary` (rules: fail if invalid, warning if all valid but unhealthy or with warnings, pass if all valid, healthy, and warning-free). It implements `toArray()` and `jsonSerialize()` for easy exporting.
- **`Web/Validation/SeoValidationBatchReportExporter.php`**: A framework-neutral exporter useful for exporting batch validation reports into arrays, JSON, summary arrays, and Markdown. It does not mutate the batch DTO, does not call validator/score/report/batch builder internally, and emits no HTTP output.

### JSON-LD Builders
The Web layer includes builders for generating specific JSON-LD schemas. These builders encapsulate the logic for creating complex schemas, providing a fluent interface for setting properties and ensuring the output matches Schema.org specifications.
- **`Web/JsonLd/Builder/AbstractJsonLdBuilder.php`**: Base class implementing `JsonLdBuilderInterface` and using `JsonLdBuilderTrait`.
- **`Web/JsonLd/Builder/ProductJsonLdBuilder.php`**: A builder for the `Product` JSON-LD schema, allowing easy configuration of product details like name, image, description, SKU, brand, offers, and aggregate ratings.
- **`Web/JsonLd/Builder/ArticleJsonLdBuilder.php`**: A builder for the `Article`, `NewsArticle`, or `BlogPosting` JSON-LD schemas, supporting configuration of headlines, images, authors, publishers, and publication dates.
- **`Web/JsonLd/Builder/BreadcrumbJsonLdBuilder.php`**: A builder for the `BreadcrumbList` JSON-LD schema, providing methods to add breadcrumb items (`addItem`, `addBreadcrumb`, `addItems`) and correctly sequencing them with `ListItem` and `position` properties.

### Web Output DTOs
- **`Web/DTO/SeoHeadHtmlDTO.php`**: A framework-neutral, final read-only DTO that implements `\JsonSerializable`. It separates rendered HTML into individual string sections (`metaHtml`, `openGraphHtml`, `twitterCardHtml`, `jsonLdHtml`) and provides a pre-combined `fullHtml` output, allowing host applications flexibility in rendering without requiring template engine coupling.

## Final Compliance and Audit
The SEO module has successfully completed its final compliance audit, verifying the implementation of the Shared, Admin, Web, and Bootstrap layers.
- The module remains strictly standalone, framework-neutral, and host-agnostic.
- The `src/Web/` layer was approved as an exception to the standard `src/Customer/` directory rule for this module.
- No direct database access occurs outside of the `Shared/Infrastructure/Persistence/` repositories.
- No HTTP responses, templates, routing, or controllers exist within the module.

## Contracts (Host Interfaces)
- `HostUrlGeneratorInterface`
- `HostEntityProviderInterface`
- `HostSearchContextInterface`

## Out of Scope / Host Responsibilities
The SEO module is complete as a standalone library. The following items are intentionally omitted because the module is strictly framework-neutral and host-agnostic. These are not missing phases and do not block module completeness:
- **Redirect resolver logic**: Framework routing decisions (evaluating an HTTP request against redirects and emitting a 301/410 response) belong entirely to the consuming host application or framework router.
- **Controllers/framework integration**: Controllers, routes, and HTTP integration are intentionally excluded to keep the module fully decoupled from any specific framework (like Slim, Laravel, or Symfony).
- **Host-specific product/category logic**: Domain-specific business logic for products, categories, or other entities remains strictly in the host application and is integrated via standard interfaces (contracts).
