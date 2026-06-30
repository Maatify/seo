# SEO Module Reference

Complete API reference and design rules for the Maatify SEO library.

## Current Module Structure
The module is divided into Admin, Shared, Customer (pending), and Bootstrap components, ensuring clean boundaries between persistence, business logic, and presentation.

```text
Modules/Seo/
├── docs/
├── schema/
│   ├── maa_seo_overrides.sql
│   ├── maa_seo_redirects.sql
│   └── maa_seo_slug_history.sql
└── src/
    ├── Admin/
    │   └── SeoOverride/
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
    └── Shared/
        ├── Command/
        ├── Contract/
        ├── DTO/
        ├── Infrastructure/Persistence/
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

### Redirect and Slug Management
- **`RedirectManagerService`**: Orchestrates redirect decisions without directly accessing the database (uses `RedirectQueryService` and `RedirectCommandService`). It accepts a `ResolveRedirectCommand` and returns a `RedirectDecisionDTO`. It does not emit HTTP responses, does not perform framework routing, and generates target URLs exclusively via `HostUrlGeneratorInterface`. Contains no SQL.
- **`SlugHistoryService`**: Manages entity slug history and automatic redirect creation without directly accessing the database. It handles `RecordSlugChangeCommand` to log old slugs. Contains no SQL.
- **`RedirectDecisionDTO`**: A final readonly DTO containing the redirect decision (none, 301, 410) and target URL. It ensures the routing layer receives a standardized outcome.
- **`ResolveRedirectCommand`**: Command object containing parameters needed to resolve a redirect (`entityType`, `languageId`, `requestedSlug`, `requestedPath`).
- **`RecordSlugChangeCommand`**: Command object representing an entity slug change, specifying the `oldSlug` and whether to automatically create a redirect.

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

## Service Layer
Services manage the core business orchestration and throw standard `SeoNotFoundException` when entities are missing. They never perform SQL queries directly and strictly use constructor injection.

### Shared Layer
- **`RedirectCommandService`**: Orchestrates `create`, `update`, `softDelete`, and `hardDelete` operations for redirects.
- **`RedirectQueryService`**: Retrieves redirect records, throwing module exceptions on failure.
- **`SlugHistoryCommandService`**: Orchestrates `create`, `softDelete`, and `hardDelete` operations for slug history entries.
- **`SlugHistoryQueryService`**: Retrieves slug history records.

### Admin Layer
- **`SeoOverrideCommandService`**: Orchestrates `create`, `update`, `softDelete`, and `hardDelete` operations for SEO overrides.
- **`SeoOverrideQueryService`**: Retrieves SEO override records.

## Contracts (Host Interfaces)
- `HostUrlGeneratorInterface`
- `HostEntityProviderInterface`
- `HostSearchContextInterface`

## Intentionally Not Implemented (Pending Phases)
- **Redirect resolver logic**: Routing decisions (evaluating a request against redirects) belong to the consuming framework and are not implemented yet.
- **Sitemap generation logic**: Streaming XML dynamically will be implemented in a dedicated phase due to memory constraints and is not implemented yet.
- **Controllers/framework integration**: Kept decoupled to remain framework-agnostic and are not implemented yet.
- **Host-specific product/category logic**: Domain-specific logic remains in the host module via interfaces.
