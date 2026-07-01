# SEO Library Roadmap

## 1. Executive Summary

This roadmap outlines the design for the `Maatify\Seo` module, a standalone, extractable, and host-agnostic PHP SEO library for the Maatify ecosystem. It strictly adheres to `MODULE_BUILDING_STANDARD.md` and is designed to be fully reusable for any Maatify project. The library provides robust, schema-driven, and multilingual-ready tools to manage dynamic SEO metadata, canonical URLs, hreflang tags, schema generation (JSON-LD), redirects, slug histories, and sitemap indexing. Crucially, the library treats host system details as abstractions via interfaces, ensuring reusability across any future project without direct table joins or tight coupling to a specific framework.

## 2. What Belongs Inside the SEO Library

The `Maatify\Seo` library is responsible for:

- Generating and structuring HTML Meta tags (Title, Description, Robots, OpenGraph, Twitter Cards, Canonical).
- Generating multilingual Hreflang tags.
- Generating valid Schema.org JSON-LD (BreadcrumbList, Product, Organization, Website, SearchAction).
- Sitemap generation logic (Sitemap Index and individual language/category sitemaps).
- Managing URL stability logic: Slug history, detection of slug changes, and automatic 301 redirects.
- Providing SEO overrides (custom meta title/description mapping for specific entities/languages).
- Exception definitions specific to SEO constraints and URL management.

## 3. What Must Remain Host-Provided Via Interfaces

Since the module is host-agnostic and maintains no FK/JOIN dependencies on host tables, the host project must provide implementations for:

- **Routing Engine / URL Generator**: The host resolves the Primary URL from an Entity ID/UUID, Language, and Slug. The SEO library NEVER stores full URLs in its schema, and relies on the host to provide the URL via a contract.
- **Product/Entity Repositories**: Data to populate Product Schema, Breadcrumbs, and Organization/Website details must be supplied by the host through defined DTOs or entity contracts.
- **Current Request Context**: The active language and requested URI.
- **Internal Search Check**: Boolean logic on whether an internal search engine exists (for `SearchAction` schema).

## 4. Proposed Module Directory Structure

Following `MODULE_BUILDING_STANDARD.md`:

```text
Modules/Seo/
├── README.md
├── CHANGELOG.md
├── SEO_MODULE_REFERENCE.md
├── composer.json
├── phpstan.neon
├── schema/
│   ├── maa_seo_slug_history.sql
│   ├── maa_seo_redirects.sql
│   └── maa_seo_overrides.sql
└── src/
    ├── Bootstrap/
    │   └── SeoBindings.php
    ├── Exception/
    │   ├── SeoExceptionInterface.php
    │   ├── SeoNotFoundException.php
    │   ├── SeoInvalidArgumentException.php
    │   ├── SeoCodeAlreadyExistsException.php
    │   └── SeoConflictException.php
    ├── Shared/
    │   ├── Contract/
    │   │   ├── HostUrlGeneratorInterface.php
    │   │   ├── HostEntityProviderInterface.php
    │   │   └── Schema/
    │   ├── DTO/
    │   │   ├── MetaTagsDTO.php
    │   │   ├── Schema/
    │   │   │   ├── ProductSchemaDTO.php
    │   │   │   ├── BreadcrumbSchemaDTO.php
    │   │   │   └── OrganizationSchemaDTO.php
    │   │   └── Sitemap/
    │   │       └── SitemapUrlDTO.php
    │   ├── Infrastructure/
    │   │   └── Persistence/
    │   │       ├── PdoSlugHistoryRepository.php
    │   │       └── PdoRedirectRepository.php
    │   └── Service/
    │       ├── MetaGeneratorService.php
    │       ├── SchemaGeneratorService.php
    │       ├── SitemapGeneratorService.php
    │       ├── RedirectManagerService.php
    │       └── SlugHistoryService.php
    ├── Admin/
    │   └── SeoOverride/
    │       ├── Command/
    │       ├── Contract/
    │       ├── DTO/
    │       ├── Infrastructure/
    │       │   └── Repository/
    │       └── Service/
    └── Web/
        └── SeoRender/
            ├── DTO/
            └── Service/
```

## 5. Proposed Public Services

- **`MetaGeneratorService`**: Orchestrates the assembly of `<title>`, `<meta>`, canonical, and hreflang tags per the current language and provided entity data. Merges host data with any database SEO overrides.
- **`SchemaGeneratorService`**: Outputs fully validated JSON-LD blocks for products, organizations, and breadcrumbs, preventing empty properties or invalid nested schemas (like `OutOfStock` translation).
- **`SitemapGeneratorService`**: Orchestrates valid XML output (index and language-specific sitemaps), fetching URL node arrays from the host.
- **`RedirectManagerService`**: Checks a requested slug against `maa_seo_redirects` to see if a 301/410 response is required.
- **`SlugHistoryService`**: Called by the host when an entity's slug changes to record the old slug and establish an automatic 301 redirect.

## 6. Proposed DTOs

- **`MetaTagsDTO`**: Aggregates final computed strings for title, description, canonical, robots, og:*, and twitter:*.
- **`ProductSchemaDTO`**: Represents localized name, description, category, brand, SKU, price, currency, availability, and seller. (Implements `\JsonSerializable`).
- **`BreadcrumbListDTO`**: Collection DTO containing list of `BreadcrumbItemDTO` (name, target ID).
- **`OrganizationSchemaDTO` / `WebsiteSchemaDTO`**: Static representation of the global schemas.
- **`SitemapUrlDTO`**: DTO containing `loc`, `lastmod`, `priority`, `changefreq`, and alternate links (hreflangs).

## 7. Proposed Contracts/Interfaces for Host Projects

- **`HostUrlGeneratorInterface`**

  ```php
  interface HostUrlGeneratorInterface {
      public function generateEntityUrl(string $entityType, string $entityId, int $languageId, ?string $slug): string;
      public function generateHomeUrl(int $languageId): string;
  }
  ```

- **`HostEntityProviderInterface`**

  ```php
  interface HostEntityProviderInterface {
      /** Return true if entity was discontinued without replacement (410) */
      public function isPermanentlyDiscontinued(string $entityType, string $entityId): bool;
      public function getDiscontinuedReplacementId(string $entityType, string $entityId): ?string;
  }
  ```

- **`HostSearchContextInterface`**

  ```php
  interface HostSearchContextInterface {
      public function getInternalSearchUrlTemplate(): ?string;
  }
  ```

## 8. Database Schema Needs

A PDO-based schema is required for specific sub-systems where persistence provides value beyond just the current state of a host entity. No Foreign Keys will map to host tables.

- **Slug History**: `maa_seo_slug_history`
  Needed. When an entity changes its slug, we must keep a record to avoid reusing old slugs and to trigger redirects.
  Columns: `id`, `entity_type`, `entity_id` (Host ID), `language_id`, `old_slug`, `created_at`.
- **Redirects**: `maa_seo_redirects`
  Needed. Fast lookup table for the routing layer to find if a requested URL/slug should 301 to a new one, or 410 (Gone).
  Redirects should be scoped by `entity_type`, `language_id`, and `requested_slug` or `requested_path`.
  Columns: `id`, `entity_type`, `language_id`, `requested_slug`, `target_entity_type`, `target_entity_id`, `http_status` (301 or 410), `created_at`. (Avoid storing full host URLs unless explicitly justified).
- **SEO Overrides**: `maa_seo_overrides`
  Needed. Allows marketers/admins to manually override generated Meta Title/Description per entity without polluting the host's primary product table.
  Columns: `id`, `entity_type`, `entity_id`, `language_id`, `meta_title`, `meta_description`, `created_at`, `updated_at`.
- **Sitemap Cache/Index**:
  *Not strictly needed as persistence.* Sitemaps should ideally be generated dynamically (or cached at a filesystem/Redis layer by the host). The library will provide the generation stream, but will not store full XML structures or URLs in PDO tables to prevent stale caches and parameter leaks.

## 9. Suggested Dependencies

- `ext-pdo`: For direct database interaction adhering to standard.
- `ext-json`: For JSON-LD encoding (`json_encode`).
- `ext-dom` or `ext-xmlwriter`: Standard PHP extensions for robust, large-scale XML sitemap streaming without memory exhaustion.
- No heavy frameworks (no Symfony/Laravel/Slim).
- `php-di/php-di`: (Optional/Suggested) for resolving `SeoBindings.php` during setup.

## 10. Implementation Phases

*Note: The full SEO module is not completely done yet. Phases 1-5 below represent the completion of the Core/Shared library only. The upcoming layers (Admin and Web) are planned in Phase 6.*

- **Phase 1: Foundation & Schemas (Complete - Core/Shared)**
  Create directory structure, PHPStan config, module exception classes, Base DTOs (Schema generation, Meta tag generation), and define Host Interfaces.
- **Phase 2: Persistence Layer & Services (Complete - Core/Shared)**
  Write SQL schemas (`maa_seo_slug_history`, `maa_seo_redirects`, `maa_seo_overrides`). Implement PDO repositories and Commands for saving/fetching overrides and redirect history. *(Note: Phase 2A Schema, 2B Repositories, 2C Services completed).*
- **Phase 3: Core Services (Complete - Core/Shared)**
  - **Phase 3A: Meta Generator (Complete):** Implement `MetaGeneratorService` with DTOs and database fallback logic.
  - **Phase 3B: JSON-LD Schema Generator (Complete):** Implement `SchemaGeneratorService` for structured data.
  - **Phase 3C: Redirect & Slug Services (Complete):** Implement `RedirectManagerService` and `SlugHistoryService`.
- **Phase 4: Sitemap Generation (Complete - Core/Shared)**
  Implement `SitemapGeneratorService` using `XMLWriter` to support dynamic sitemap index and language-specific XMLs.
- **Phase 5: Documentation & Polish (Complete - Core/Shared)**
  Finalized `README.md`, `SEO_MODULE_REFERENCE.md`, and verified PHPStan level max passes natively.

- **Phase 6: Full Module Completion (Complete)**
  - **Phase 6A: Admin Layer (Complete):** Implement admin-specific command, query, and service classes for managing SEO overrides, redirects, and slug history.
  - **Phase 6B: Web Layer (Complete):** Implement host website consumption services/DTOs. (Replaces standard Customer layer; no controllers or routes).
  - **Phase 6C: Bootstrap/DI Full Wiring (Complete):** Complete dependency injection wiring for all layers.
  - **Phase 6D: Final Module Compliance Audit (Complete):** Ensure full compliance with all Maatify module standards. The full SEO module is complete and release-ready.

- **Phase 7: Usability and Rendering (Complete)**
  - **Phase 7A: HTML Rendering Helpers (Complete):** Extract final HTML generation out of commands to pure PHP string renderers.
  - **Phase 7B: Flattened Usability DTO (Complete):** Create `SeoHeadHtmlDTO` mapping all metadata cleanly for simple array access.
  - **Phase 7C: Fluent Output Builder (Complete):** Provide a fluent interface (`$builder->title()->description()->render()`).
  - **Phase 7D: Spatie Schema Integration (Complete):** Provide a dedicated, optional bridge wrapping the `spatie/schema-org` package.
  - **Phase 7E: Sitemap String Output (Complete):** Helper to optionally generate plain string output directly from sitemap commands.

- **Phase 9: Robots.txt Output Helpers**
  - **Phase 9A: Robots.txt String Renderer (Complete):** Provide framework-neutral helpers for generating `robots.txt` content as plain strings.

- **Phase 10: Sitemap Enhancements**
  - **Phase 10A: Sitemap Index String Renderer (Complete):** Helper to optionally generate plain string output directly from sitemap index commands.
  - **Phase 10B: Hreflang / Alternate URL Support (Complete):** Enhance Web string helper to support `xhtml:link` alternate URLs for multi-language indexing.
  - **Phase 10C: Image Sitemap Support (Complete):** Enhance Web string helper to support `image:image` tags and Google's image sitemap schema.
  - **Phase 10D: Video Sitemap Support (Complete):** Enhance Web string helper to support `video:video` tags and Google's video sitemap schema.

- **Phase 11: Utilities and Diagnostics**
  - **Phase 11A: SEO Validation Helpers (Complete):** Provide a tool to inspect generated output array/DTO structures and warn about common issues (missing title, description too long, etc.).

## 11. Risks / Decisions that Need Approval Before Coding

- **Entity Identifier Type**: Host projects might use `int`, `string` (UUID), or mixed types for `entity_id`. Should `entity_id` in `maa_seo_*` tables be `VARCHAR(36)` to safely support UUIDs, even if the host uses integers? (Recommendation: Yes, `VARCHAR(36)`).
- **Extensibility of `entity_type`**: A `VARCHAR(50)` will be used to differentiate `product`, `category`, `brand`, etc. Is a hardcoded enum string acceptable, or should the host register types?
- **Sitemap Generation Memory Constraints**: Sitemaps for thousands of products can OOM. Approval is needed to enforce pagination via `XMLWriter` stream instead of holding the entire XML in memory.

## 12. Definition of Done

- All required standard module files are present (`README.md`, `CHANGELOG.md`, `SEO_MODULE_REFERENCE.md`, `composer.json`, `phpstan.neon`).
- The library successfully runs independently with zero external host-table FKs.
- `phpstan analyse -c phpstan.neon` returns NO errors at `level: max`.
- Multi-language URL generation and hreflang tag management are fully supported via DTOs and Interfaces.
- `maa_seo_*` schemas are documented, provided in `schema/`, and include clear comments (e.g., `Host-provided ID. No FK.`).
- All module-specific exceptions extend a base `\RuntimeException` and implement `SeoExceptionInterface`.
- Persistence uses plain PDO (no ORMs).
- Roadmap/design makes it obvious how the module drops into a future Maatify framework with zero changes.
