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
- **Redirect resolver logic**: Routing decisions (evaluating a request against redirects) belong to the consuming framework.
- **Sitemap generation logic**: Streaming XML dynamically will be implemented in a dedicated phase due to memory constraints.
- **Controllers/framework integration**: Kept decoupled to remain framework-agnostic.
- **Host-specific product/category logic**: Domain-specific logic remains in the host module via interfaces.
