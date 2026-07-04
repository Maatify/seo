# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - Unreleased
- **Added:** Documentation Coverage Audit completed to align public APIs with the README and ensure developer accessibility.
- **Added:** Batch 3 Hreflang Head Link Builder for HTML head `<link rel="alternate" hreflang="...">` tag generation.
- **Added:** Batch 2 Admin Previews (`SerpPreviewDTO`, `SocialPreviewDTO`) & Metadata Import/Export helpers.
- **Added:** Batch 1C Domain SEO Preset Factories (`EcommerceSeoPresetFactory`, `ContentSeoPresetFactory`, `LocalBusinessSeoPresetFactory`) for high-level semantic setups.
- **Added:** Batch 1B SeoPagePresetFactory as the primary orchestrator for common page types.
- **Added:** Batch 1A MetaRobotsBuilder for standalone, exclusive management of `<meta name="robots">` tags.
- **Added:** Phase 15A CanonicalUrlBuilder for programmatic construction of canonical links with normalized query strings.
- **Added:** Phase 14 Social Meta Builders (`OpenGraphBuilder`, `TwitterCardBuilder`, `SocialPreviewBuilder`, `SocialImageFactory`).
- **Added:** Phase 13 JSON-LD builder expansion for extensive Schema.org schemas (Foundation, Product, Article, Organization, Content, Media, Specialized entities, etc.).
- **Added:** Phase 12 CI/Stability audits (`PHASE_12A_FINAL_STABILITY_AND_TEST_COVERAGE_AUDIT_REPORT.md`, `PHASE_12B_GITHUB_ACTIONS_CI_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 13I Commerce JSON-LD Builders (`ReviewJsonLdBuilder`, `AggregateRatingJsonLdBuilder`, `OfferJsonLdBuilder`, `ServiceJsonLdBuilder`, `LocalBusinessJsonLdBuilder`) to fluently generate JSON-LD output (see `docs/verification/PHASE_13I_COMMERCE_JSON_LD_BUILDERS_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 13F WebSite JSON-LD Builder (`WebSiteJsonLdBuilder`) to fluently generate WebSite JSON-LD output (see `docs/verification/PHASE_13F_WEBSITE_JSON_LD_BUILDER_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 11G SEO Validation Batch Report Exporter including `SeoValidationBatchReportExporter` to export batch reports to Array, JSON, Summary Array, and Markdown (see `docs/verification/PHASE_11G_SEO_VALIDATION_BATCH_REPORT_EXPORTER_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 11F SEO Validation Batch Report Helpers including `SeoValidationBatchReportBuilder` and `SeoValidationBatchReportDTO` to build SEO validation reports for multiple entities in one batch (see `docs/verification/PHASE_11F_SEO_VALIDATION_BATCH_REPORT_HELPERS_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 11E SEO Validation Report Exporter including `SeoValidationReportExporter` to export reports to Array, JSON, Summary Array, and Markdown (see `docs/verification/PHASE_11E_SEO_VALIDATION_REPORT_EXPORTER_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 10E News Sitemap Support in `SitemapXmlStringRenderer` and addition of `SitemapNewsDTO` for standard Google news sitemap indexing (see `docs/verification/PHASE_10E_NEWS_SITEMAP_SUPPORT_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 11D SEO Validation Presets including `SeoValidationPreset` providing pre-configured validation and score option arrays (see `docs/verification/PHASE_11D_SEO_VALIDATION_PRESETS_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 11C SEO Validation Report Helpers including `SeoValidationReportBuilder` and `SeoValidationReportDTO` for comprehensive combined reporting (see `docs/verification/PHASE_11C_SEO_VALIDATION_REPORT_HELPERS_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 11B SEO Validation Score Helpers including `SeoValidationScoreCalculator` and `SeoValidationScoreDTO` to generate actionable scores and grades (see `docs/verification/PHASE_11B_SEO_VALIDATION_SCORE_HELPERS_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 11A SEO Validation Helpers including `SeoMetaValidator`, `SeoValidationResultDTO`, and `SeoValidationIssueDTO` for framework-agnostic metadata auditing (see `docs/verification/PHASE_11A_SEO_VALIDATION_HELPERS_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 10D Video Sitemap Support in `SitemapXmlStringRenderer` and addition of `SitemapVideoDTO` for standard Google video sitemap indexing (see `docs/verification/PHASE_10D_VIDEO_SITEMAP_SUPPORT_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 10C Image Sitemap Support in `SitemapXmlStringRenderer` and addition of `SitemapImageDTO` for standard Google image sitemap indexing (see `docs/verification/PHASE_10C_IMAGE_SITEMAP_SUPPORT_VERIFICATION_REPORT.md`).
- **Added:** Implementation of Phase 10B Sitemap Hreflang / Alternate URL Support in `SitemapXmlStringRenderer` and addition of `SitemapAlternateUrlDTO` for multi-language indexing (see `docs/verification/PHASE_10B_SITEMAP_HREFLANG_ALTERNATE_URL_VERIFICATION_REPORT.md`).
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
