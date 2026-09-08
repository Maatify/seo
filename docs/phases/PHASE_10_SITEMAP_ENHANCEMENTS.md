# Phase 10 — Sitemap Enhancements

## Phase status

The Phase 10 implementation is complete, the Verification Gate passed, the
Documentation Sweep is complete, and Final Review against the latest `main`
passed. Phase 10 satisfies its technical Definition of Done and is
**Complete**.

PR #191 is the Phase 10 Integration PR and final integration path to `main`.
Ready and squash-merge are separate maintainer integration actions.

Current lifecycle state:

- Blueprint: complete.
- WU-10-1 — Strict sitemap date validation: complete.
- WU-10-2 — Raw-array URL contract parity: complete.
- Verification: complete / `PASS`.
- Runtime gaps: `0`.
- Implementation Work Units remaining: `0`.
- Documentation Sweep: complete.
- Final Review: `PASS`.

Lifecycle:

`Draft Integration PR → Blueprint → Work Units → Verification → Documentation Sweep → Final Review vs latest main → Ready → Merge`

## Verified runtime contracts

The current Web sitemap renderers remain framework-neutral and return XML
strings generated in memory. The host application owns routes, HTTP headers,
HTTP responses, filesystem or cache policy, and delivery.

- `SitemapUrlDTO::isValidLastmod()` accepts valid `YYYY-MM-DD` and valid ATOM
  timestamps and rejects invalid calendar dates and parser warnings/errors.
- The shared strict date helper is used by the relevant URL, Web sitemap-index,
  and video publication-date contracts.
- `SitemapNewsDTO::$publicationDate` remains non-empty and is emitted as
  provided; it is intentionally not strict shared date validation.
- `SitemapXmlStringRenderer` supports typed DTO and raw associative URL inputs.
  Raw top-level `loc`, `lastmod`, `changefreq`, and `priority` follow the typed
  contract.
- Hreflang alternates, `x-default`, image, video, and news child collections
  are supported with conditional XML namespaces and native XML escaping.
- The two sitemap-index DTOs remain separate public contracts: the Shared DTO
  belongs to `SitemapGeneratorService`, while the Web DTO belongs to
  `SitemapIndexXmlStringRenderer`.

## Documentation Sweep evidence

- Exact Draft baseline reviewed for this sweep:
  `53089b5fa98c7b0c8e7a1f727e898b920cde8c58`.
- Latest accepted `main` at sweep start:
  `c4b0a60adf29fa104f8911e253d02fa35bb19e83`.
- Current Verification evidence:
  `docs/verification/PHASE_10_SITEMAP_ENHANCEMENTS_VERIFICATION_REPORT.md`.
- The sweep changes documentation and the readable sitemap example only. It
  does not change runtime code, DTOs, renderers, tests, dependencies, Composer,
  CI workflows, or verified contracts.

## Documentation layer classification

Each required documentation layer has one final classification from the Phase
Execution Standard.

| Path | Status | Synchronization result |
| --- | --- | --- |
| `README.md` | `updated` | The feature summary now names URL-set/index XML, typed/raw inputs, strict validation, hreflang, image, video, and news support while keeping the overview concise. |
| `docs/SEO_LIBRARY_REFERENCE.md` | `updated` | The Web renderers, raw validation parity, strict date behavior, News date contract, and separate Shared/Web index DTO roles are explicit. |
| `docs/guides/USAGE_GUIDE.md` | `updated` | The sitemap usage section now states strict URL/date/frequency/priority validation and the intentional News date behavior. |
| `docs/guides/INTEGRATION_GUIDE.md` | `updated` | Host-owned HTTP behavior and the typed/raw sitemap validation contract are synchronized. |
| `docs/SEO/**` | `reviewed-no-change` | `docs/SEO/library/**` is the active engineering handbook and currently contains structured-data-specific material with no Phase 10 sitemap claim requiring synchronization; `docs/SEO/v1/**` is historical and remains preserved. |
| `docs/phases/**` | `updated` | This current Phase 10 lifecycle record was added following the repository convention. |
| `docs/verification/**` | `reviewed-no-change` | The accepted Verification report remains the current evidence record and was not rewritten during this sweep. |
| `examples/**` | `updated` | `examples/sitemap-output.php` now demonstrates extended typed URL data, `x-default`, and sitemap-index rendering. |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | `updated` | Phase 10 component statuses now match verified runtime while overall completion remains pending Final Review. |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | `updated` | Stale Optional-later wording was synchronized; lifecycle state, strict contracts, and the intentional News date behavior are recorded. |
| `docs/blueprints/**` | `reviewed-no-change` | The accepted Blueprint is the implementation contract and did not require modification. |

No Google eligibility, Search Console submission, indexing guarantee, network
integration, filesystem ownership, HTTP response ownership, or framework
coupling is claimed by these updates.

## Final Review evidence

Final Review passed against the exact reviewed references below:

- Latest actual `main`: `c4b0a60adf29fa104f8911e253d02fa35bb19e83`.
- Reviewed Draft baseline: `a84bb1293504638b0e5ec13de75544c842894d97`.
- Merge-base: `c4b0a60adf29fa104f8911e253d02fa35bb19e83`.
- Reviewed topology: Draft was `6` commits ahead and `0` commits behind.
- Runtime gaps: `0`.
- Implementation Work Units remaining: `0`.
- Verification: `PASS`.
- Documentation Sweep: complete.

The accumulated Phase 10 stack contains exactly 15 files:

1. `README.md`
2. `docs/SEO_LIBRARY_REFERENCE.md`
3. `docs/blueprints/PHASE_10_SITEMAP_ENHANCEMENTS_BLUEPRINT.md`
4. `docs/guides/INTEGRATION_GUIDE.md`
5. `docs/guides/USAGE_GUIDE.md`
6. `docs/phases/PHASE_10_SITEMAP_ENHANCEMENTS.md`
7. `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md`
8. `docs/roadmap/SEO_LIBRARY_ROADMAP.md`
9. `docs/verification/PHASE_10_SITEMAP_ENHANCEMENTS_VERIFICATION_REPORT.md`
10. `examples/sitemap-output.php`
11. `src/Shared/DTO/Sitemap/SitemapUrlDTO.php`
12. `src/Web/Sitemap/SitemapXmlStringRenderer.php`
13. `tests/Phase10ASitemapIndexXmlStringRendererTest.php`
14. `tests/Phase10DVideoSitemapXmlStringRendererTest.php`
15. `tests/Phase7ESitemapXmlStringRendererTest.php`

The review confirmed that the Phase 10 technical Definition of Done is
satisfied without changing runtime code, tests, examples, dependencies, or
CI workflows in this Final Review record.

## Integration boundary

PR #191 is the Phase 10 Integration PR and final integration path to `main`.
Ready and squash-merge are separate maintainer integration actions.
