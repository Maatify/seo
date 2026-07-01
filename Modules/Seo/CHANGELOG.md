# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - Unreleased
- **Added:** Implementation of Phase 10A Sitemap Index String Renderer (`SitemapIndexXmlStringRenderer`) to directly render XML sitemap index strings (see `docs/verification/PHASE_10A_SITEMAP_INDEX_STRING_RENDERER_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 9A Robots.txt Output Helpers (`RobotsTxtRenderer`) to generate `robots.txt` strings in a framework-neutral way (see `docs/verification/PHASE_9A_ROBOTS_TXT_RENDERER_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 7E Sitemap String Output Helpers (`SitemapXmlStringRenderer`) to directly render XML sitemap strings (see `docs/verification/PHASE_7E_SITEMAP_STRING_OUTPUT_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 7D Optional Spatie Schema Integration to provide a framework-neutral adapter for `spatie/schema-org`.
- **Added:** Implementation of Phase 7C Fluent SEO Builder to provide a framework-neutral fluent interface for dynamic output construction.
- **Added:** Phase 7B (Usability & Rendering) Render Output DTOs (`SeoHeadHtmlDTO`) implemented and verified (see `docs/verification/PHASE_7B_RENDER_OUTPUT_DTOS_VERIFICATION_REPORT.md`).
- **Added:** Phase 7A (Usability & Rendering) HTML rendering helpers (`SeoHeadHtmlRenderer`, etc.) implemented and verified (see `docs/verification/PHASE_7A_HTML_RENDERING_HELPERS_VERIFICATION_REPORT.md`).
- **Added:** Phase 6D (Final Module Compliance Audit) completed and verified (see `docs/verification/PHASE_6D_FINAL_MODULE_COMPLIANCE_AUDIT_REPORT.md`).
- **Added:** Phase 6C (Bootstrap/DI Full Wiring) implementation including `Bootstrap/SeoBindings.php` (see `docs/verification/PHASE_6C_BOOTSTRAP_DI_WIRING_VERIFICATION_REPORT.md`).
- **Added:** Phase 6B (Web Layer) implementation including `Web/SeoRender/Service/SeoPageRenderService`, `Web/SeoRender/Command/RenderSeoPageCommand`, and `Web/SeoRender/DTO/SeoPagePayloadDTO` (see `docs/verification/PHASE_6B_WEB_LAYER_VERIFICATION_REPORT.md`).
- **Added:** Phase 6A (Admin Layer) implementation including `AdminSeoOverride`, `AdminRedirect`, and `AdminSlugHistory` services, DTOs, and commands (see `docs/verification/PHASE_6A_ADMIN_LAYER_VERIFICATION_REPORT.md`).
- **Added:** Phase 5 (Documentation & Polish) implementation including final validations and verification reports.
- **Added:** Phase 4 (Sitemap Generation) implementation including `SitemapGeneratorService` and heavily-validated DTOs (`SitemapUrlDTO`, `SitemapIndexEntryDTO`, etc.) to stream valid XML (see `docs/verification/PHASE_4_SITEMAP_GENERATION_VERIFICATION_REPORT.md`).
- **Added:** Phase 3C (Redirect & Slug Services) implementation including `RedirectManagerService`, `SlugHistoryService`, and corresponding DTOs (see `docs/verification/PHASE_3C_REDIRECT_AND_SLUG_SERVICES_VERIFICATION_REPORT.md`).
- **Added:** Phase 3B (JSON-LD Schema Generator) implementation including `SchemaGeneratorService` and various strictly typed schema DTOs (see `docs/verification/PHASE_3B_JSON_LD_SCHEMA_GENERATOR_VERIFICATION_REPORT.md`).
- **Added:** Phase 3A (Meta Generator) implementation including `GenerateMetaTagsCommand`, `MetaTagsDTO`, and `MetaGeneratorService` (see `docs/verification/PHASE_3A_META_GENERATOR_VERIFICATION_REPORT.md`).
- **Added:** Phase 2C (Service Layer) implementations for redirect, slug history, and SEO overrides (see `docs/verification/PHASE_2C_SERVICE_LAYER_VERIFICATION_REPORT.md`).
- **Added:** Phase 2B (Repository Layer) containing PDO implementations for standard CRUD.
- **Added:** Phase 2A (Schema) including `maa_seo_slug_history`, `maa_seo_redirects`, and `maa_seo_overrides` tables.
- Initial foundational release (Phase 1).
