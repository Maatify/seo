# Phase 10 — Sitemap Enhancements

## Phase status

The Phase 10 implementation is complete. After the initial Final Review, a later independent review found runtime/coverage correction requirements. Correction PR #198 was accepted and squash-merged, and a fresh post-correction Verification was completed and accepted. Documentation synchronization is complete, and a fresh Final Review is pending.

PR #191 is the Phase 10 Integration PR and final integration path to `main`.
Ready and squash-merge are separate maintainer integration actions.

Current lifecycle state:

- Blueprint: complete.
- WU-10-1 — Strict sitemap date validation: complete.
- WU-10-2 — Raw-array URL contract parity: complete.
- Post-correction Verification: complete / `PASS`.
- Runtime gaps: `0`.
- Implementation Work Units remaining: `0`.
- Post-correction Documentation Synchronization: complete.
- Fresh Final Review: `PASS`.

Lifecycle:

`Draft Integration PR → Blueprint → Work Units → Verification → Documentation Sweep → Final Review vs latest main → Correction → Post-Correction Verification → Documentation Synchronization → Fresh Final Review → Ready → Merge`

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
  contract. Typed and raw priority must be finite and within `0.0..1.0` (rejecting `NAN`, `+INF`, `-INF`).
- Valid calendar ATOM sitemap dates and strict `YYYY-MM-DD` dates are accepted, while malformed/invalid calendar ATOM dates are correctly rejected.
- Persisted Blueprint regression coverage exists for date and priority constraints.
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

## Historical Final Review evidence

The original Final Review passed against the exact reviewed references below:

- Reviewed Draft baseline: `a84bb1293504638b0e5ec13de75544c842894d97`.
- Topology at that historical review: `6 ahead / 0 behind`.
- Original Verification: `PASS`.
- Original Documentation Sweep: complete.
- Original Final Review: `PASS`.

## Final post-correction state

- Fresh Final Review: `PASS`.
- Reviewed Draft baseline: `03eb7965e8ee8cc4fead1a4608ff6041de5e7e24`.
- Latest reviewed `main`: `c4b0a60adf29fa104f8911e253d02fa35bb19e83`.
- Topology: `10 ahead / 0 behind`.
- Accumulated changed files: `19`.
- Runtime gaps: `0`.
- Remaining implementation WUs: `0`.
- Post-correction Verification: `PASS`.
- Post-correction Documentation Synchronization: `complete`.
- Phase 10 technical lifecycle: `Complete`.
## Integration boundary

PR #191 is the Phase 10 Integration PR and final integration path to `main`.
Ready and squash-merge are separate maintainer integration actions.
