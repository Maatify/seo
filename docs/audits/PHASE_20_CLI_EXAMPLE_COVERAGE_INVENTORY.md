# Phase 20 — CLI Example Coverage Inventory

## Purpose and Evidence Basis

This inventory records major public capability families exposed by the current
repository and identifies which of them have a runnable CLI example. It groups
related services, builders, renderers, commands, DTOs, and adapters into
user-facing capability families; individual classes and DTOs are not counted as
separate capabilities.

The inventory was reviewed against:

- `src/` public service, builder, renderer, command, and contract families;
- the feature and practical-example lists in `README.md`; and
- the architecture, service, Web-layer, and host-responsibility descriptions in
  `docs/SEO_LIBRARY_REFERENCE.md`.

The Phase 20 Draft baseline for this inventory is
`5d1770d86e47b5528affd878901a41b8914fe129`.

## Classification Rules

- `covered`: an existing runnable example demonstrates the family at the
  user-facing level and is linked below.
- `partial`: an example covers an adjacent part of the family, but a major
  public service workflow or orchestration contract is not demonstrated yet.
- `missing`: the public capability exists, but no current standalone example
  demonstrates its user-facing workflow.
- `not-applicable-for-dedicated-cli-example`: the family is infrastructure,
  host wiring, a reusable contract/value shape, or a diagnostic mechanism; it
  is intentionally not a standalone CLI capability.

## Capability Inventory

| # | Capability family | Repository evidence | Current example evidence | Classification | Phase 20 action |
| ---: | --- | --- | --- | --- | --- |
| 1 | Core metadata and HTML head output | `MetaTagsDTO`, `Web/Render/*`, `SeoHeadHtmlDTO`, `SeoHeadHtmlRenderer` | `examples/basic-head-render.php`, `examples/product-page-seo.php`, `examples/phase7-output-showcase.php` | `covered` | Keep as existing coverage |
| 2 | Fluent SEO page construction and domain presets | `Web/Builder/FluentSeoBuilder`, `Web/Page/SeoPagePresetFactory`, domain preset factories | `examples/category-page-seo.php`, `examples/seo-page-presets.php` | `covered` | Keep as existing coverage |
| 3 | Social metadata generation and social preview composition | `Web/Social/*` builders and collection/output types | `examples/social-builders.php` | `covered` | Keep as existing coverage |
| 4 | Admin SERP and social previews | `Admin/Preview/*`, `SerpPreviewDTO`, `SocialPreviewDTO` | `examples/admin-previews.php` | `covered` | Keep as existing coverage |
| 5 | Hreflang head-link generation | `Web/Hreflang/*` | `examples/hreflang-generation.php` | `covered` | Keep as existing coverage |
| 6 | Canonical URL and meta-robots helpers | `Web/Indexing/CanonicalUrlBuilder`, `Web/Robots/MetaRobotsBuilder` | `examples/meta-robots-canonical.php` | `covered` | Keep as existing coverage |
| 7 | Structured-data construction, graph generation, and JSON-LD output | `Shared/Service/SchemaGeneratorService`, schema DTOs, `Web/JsonLd/Builder/*`, `Web/Render/JsonLdScriptRenderer`, `Web/Schema/SpatieSchemaAdapter` | `examples/schema-output.php`, `examples/phase13-jsonld-builders.php`, `examples/phase13o-product-advanced.php` | `partial` | WU6 will demonstrate the high-level generation/orchestration path; existing builder and output coverage remains valid |
| 8 | Sitemap XML generation and extended sitemap data | `SitemapGeneratorService`, `Web/Sitemap/*`, shared sitemap DTOs for alternates, images, videos, and news | `examples/sitemap-output.php` | `covered` | Keep as existing coverage |
| 9 | `robots.txt` generation | `Web/Robots/RobotsTxtRenderer` and robots DTOs | `examples/robots-output.php` | `covered` | WU1 is complete |
| 10 | Page SEO validation, scoring, and report export | `SeoMetaValidator`, score/report builders, DTOs, and exporters | `examples/seo-validation.php` | `covered` | WU2 is complete |
| 11 | Product SEO audit with structured-data validation | Product metadata/schema contracts plus `SeoMetaValidator` JSON-LD semantic path | `examples/product-seo-audit.php` | `covered` | WU3 is complete |
| 12 | Metadata import/export | `Admin/Export/*`, `Admin/Import/*`, import/export DTOs | `examples/import-export.php` | `covered` | Keep as existing coverage |
| 13 | Override-aware metadata generation and canonical fallback | `MetaGeneratorService`, `GenerateMetaTagsCommand`, `SeoOverrideQueryService`, `HostUrlGeneratorInterface`, `MetaTagsDTO` | Direct DTO/rendering examples cover output, but no example demonstrates override lookup, default fallback, and canonical resolution together | `partial` | WU5 adds the missing service workflow |
| 14 | Redirect and slug-history workflow | `SlugHistoryService`, `RedirectManagerService`, shared commands/decisions, and Admin/Shared redirect and slug services | No current standalone workflow example | `missing` | WU4 adds the in-memory host-style workflow example |
| 15 | High-level SEO page render orchestration | `RenderSeoPageCommand`, `SeoPageRenderService`, `SeoPagePayloadDTO` | No current standalone orchestration example | `missing` | WU6 adds the payload orchestration example |
| 16 | Persistence, bootstrap/container wiring, host interfaces, exceptions, and raw DTO/value contracts | `Shared/Infrastructure/Persistence/*`, `Bootstrap/SeoBindings`, host contracts, exception classes, and reusable DTO families | Integration Guide and reference documentation describe these boundaries; they are not user-facing CLI capabilities | `not-applicable-for-dedicated-cli-example` | No Work Unit; do not add a dedicated example |

## Approved Phase 20 Coverage Result

WU1–WU3 are covered in the current Draft:

- WU1 covers `robots.txt` generation through `examples/robots-output.php`.
- WU2 covers page SEO validation and scoring through
  `examples/seo-validation.php`.
- WU3 covers a real Product SEO audit, including Product JSON-LD evaluation,
  through `examples/product-seo-audit.php`.

The remaining example coverage is intentionally limited to three additional
Work Units:

- WU4 — Redirect + Slug History Workflow Example
  (`examples/redirect-slug-history.php`)
- WU5 — SEO Override + Meta Generation Example
  (`examples/seo-override-meta-generation.php`)
- WU6 — `SeoPageRenderService` Orchestration Example
  (`examples/seo-page-render.php`)

No Work Unit is created for `SeoBindings`, PDO repositories, exceptions, raw
DTO families, or framework/container integration. These are wiring,
infrastructure, or reusable contracts documented in the Integration Guide and
reference, not independent user-facing CLI capabilities.

## Classification Counts

| Classification | Count |
| --- | ---: |
| `covered` | 11 |
| `partial` | 2 |
| `missing` | 2 |
| `not-applicable-for-dedicated-cli-example` | 1 |
| **Total capability families** | **16** |

These counts describe capability families, not files, classes, DTOs, or Work
Units.
