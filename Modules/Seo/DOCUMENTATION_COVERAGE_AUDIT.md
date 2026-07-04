# Documentation Coverage Audit

This audit evaluates the documentation completeness and developer experience of the Maatify SEO module, focusing on discovering APIs, validating the CHANGELOG, and ensuring proper practical examples exist.

## 1. Missing README Sections

The `README.md` is generally comprehensive but lacks explicit sections and examples for several major recently added features (Phases 10-15 and Batches).

**Recommended Additions:**
- **SeoPagePresetFactory:** Documentation on how to use `SeoPagePresetFactory` and its specialized factories (`ContentSeoPresetFactory`, `EcommerceSeoPresetFactory`) to quickly bootstrap SEO configurations without manually calling multiple builders.
- **Hreflang Generation:** Missing examples on how to use `HreflangLinkBuilder` to generate `<link rel="alternate" hreflang="...">` tags for multi-language sites.
- **Admin Previews (`SerpPreviewDTO`, `SocialPreviewDTO`):** Documentation on how host applications can use these factories to build preview panels in their admin dashboards.
- **Import/Export Helpers:** Documentation on using `SeoMetadataImporter` and `SeoMetadataExporter` for data migration.
- **Social Builders (`OpenGraphBuilder`, `TwitterCardBuilder`, `SocialPreviewBuilder`):** Missing explicit examples on how to use these highly structured composite builders independently of the fluent builder.
- **JSON-LD Builders (Phases 13):** Need a section documenting the extensive array of specialized JSON-LD builders (e.g., `ArticleJsonLdBuilder`, `ProductJsonLdBuilder`, `OrganizationJsonLdBuilder`) and how they ensure correct normalization natively without array manipulation.
- **MetaRobotsBuilder:** Missing documentation for the `MetaRobotsBuilder` which manages `<meta name="robots">` independently.
- **CanonicalUrlBuilder:** Missing documentation for managing canonical URLs cleanly.

## 2. Missing CHANGELOG Entries

The `CHANGELOG.md` accurately reflects most phases up to Phase 11 and 13I/13F. However, several critical phases are completely absent from the log.

**Missing Entries:**
- **Phase 12 (CI & Stability Audits):** Missing entries for `PHASE_12A_FINAL_STABILITY_AND_TEST_COVERAGE_AUDIT_REPORT.md` and `PHASE_12B_GITHUB_ACTIONS_CI_VERIFICATION_REPORT.md`.
- **Phase 13 (Remaining Builders):** Missing entries for specific JSON-LD phases like Phase 13A (Foundation), 13B (Product), 13C (Article), 13E (Organization), 13G (Person), 13H (Content), 13J (Media), 13K (Page Type), 13L (Rich Results), 13M (Extra Specialized).
- **Phase 14 (Social Meta Builders):** Missing entries for `OpenGraphBuilder`, `TwitterCardBuilder`, `SocialPreviewBuilder`, and `SocialImageFactory` (Phases 14A to 14F).
- **Phase 15 (Canonical URL Builder):** Missing entry for `CanonicalUrlBuilder` (Phase 15A).
- **Batches 1-3:** Missing entries for `MetaRobotsBuilder` (Batch 1), Admin Previews (Batch 2), and Hreflang Link Builder (Batch 3).

## 3. Missing Examples

The `examples/` directory demonstrates foundational rendering (`basic-head-render.php`), fluent builders (`category-page-seo.php`), validation, and sitemaps. However, it lacks practical examples for many decoupled domain builders.

**Recommended Example Files:**
- `examples/seo-page-presets.php`: Demonstrating `SeoPagePresetFactory` and its specialized presets.
- `examples/hreflang-generation.php`: Demonstrating `HreflangLinkBuilder` and renderer.
- `examples/admin-previews.php`: Demonstrating `SerpPreviewFactory` and `SocialPreviewFactory`.
- `examples/import-export.php`: Demonstrating `SeoMetadataExporter` and `SeoMetadataImporter`.
- `examples/social-builders.php`: Demonstrating `OpenGraphBuilder`, `TwitterCardBuilder`, and `SocialPreviewBuilder` orchestration.
- `examples/meta-robots-canonical.php`: Demonstrating `MetaRobotsBuilder` and `CanonicalUrlBuilder`.

## 4. Discoverability Gaps Ranked

From a developer experience perspective, without reading the source code, several powerful APIs remain hidden due to the lack of entry points in the README.

**Critical Gaps (High Priority):**
1. **`SeoPagePresetFactory`**: This is the primary orchestration point for most consumers (pages, products, articles). Without README documentation, developers will manually rebuild this logic.
2. **Social Builders (`OpenGraphBuilder`, `TwitterCardBuilder`)**: Without docs, users will rely on array-based configuration in the fluent builder instead of utilizing the strict type-safety and automatic tag ordering provided by these builders.
3. **Admin Previews**: Host applications building CMS systems need to know `SerpPreviewDTO` and `SocialPreviewDTO` exist to prevent rewriting complex preview extraction logic.

**Medium Gaps:**
1. **JSON-LD Builders List**: While Phase 13 is mentioned in the changelog partially, a list or table in the README mapping available schemas (e.g., `FAQPage`, `JobPosting`, `LocalBusiness`) to their respective builder classes is needed.
2. **Hreflang Builder**: Multilingual SEO is a common requirement; not documenting this explicitly will cause developers to build their own `<link>` tag generators.

**Nice-to-Have Gaps:**
1. **Import/Export Helpers**: Useful for migrations, but less critical for daily rendering usage.
2. **MetaRobotsBuilder / CanonicalUrlBuilder**: These are often handled by higher-level presets or the fluent builder, but having explicit documentation for standalone usage is beneficial.
