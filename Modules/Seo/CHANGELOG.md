# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - Unreleased
- **Added:** Phase 5 (Documentation & Polish) implementation including final validations and verification reports.
- **Added:** Phase 4 (Sitemap Generation) implementation including `SitemapGeneratorService` and heavily-validated DTOs (`SitemapUrlDTO`, `SitemapIndexEntryDTO`, etc.) to stream valid XML (see `docs/verification/PHASE_4_SITEMAP_GENERATION_VERIFICATION_REPORT.md`).
- **Added:** Phase 3C (Redirect & Slug Services) implementation including `RedirectManagerService`, `SlugHistoryService`, and corresponding DTOs (see `docs/verification/PHASE_3C_REDIRECT_AND_SLUG_SERVICES_VERIFICATION_REPORT.md`).
- **Added:** Phase 3B (JSON-LD Schema Generator) implementation including `SchemaGeneratorService` and various strictly typed schema DTOs (see `docs/verification/PHASE_3B_JSON_LD_SCHEMA_GENERATOR_VERIFICATION_REPORT.md`).
- **Added:** Phase 3A (Meta Generator) implementation including `GenerateMetaTagsCommand`, `MetaTagsDTO`, and `MetaGeneratorService` (see `docs/verification/PHASE_3A_META_GENERATOR_VERIFICATION_REPORT.md`).
- **Added:** Phase 2C (Service Layer) implementations for redirect, slug history, and SEO overrides (see `docs/verification/PHASE_2C_SERVICE_LAYER_VERIFICATION_REPORT.md`).
- **Added:** Phase 2B (Repository Layer) containing PDO implementations for standard CRUD.
- **Added:** Phase 2A (Schema) including `maa_seo_slug_history`, `maa_seo_redirects`, and `maa_seo_overrides` tables.
- Initial foundational release (Phase 1).
