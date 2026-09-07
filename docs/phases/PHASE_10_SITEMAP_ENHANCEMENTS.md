# Phase 10 — Sitemap Enhancements

## Phase status

The Phase 10 implementation is complete, the Verification Gate passed, and the
Documentation Sweep is complete through this record. Final Review against the
latest `main` is still pending. Therefore Phase 10 is **not yet globally
Complete**, and umbrella PR #191 remains Draft.

Current lifecycle state:

- Blueprint: complete.
- WU-10-1 — Strict sitemap date validation: complete.
- WU-10-2 — Raw-array URL contract parity: complete.
- Verification: complete / `PASS`.
- Runtime gaps: `0`.
- Implementation Work Units remaining: `0`.
- Documentation Sweep: complete through this record.
- Final Review: pending.

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
| `docs/SEO/**` | `reviewed-no-change` | These files are historical handbook/specification material; no current public Phase 10 claim required rewriting in this sweep. |
| `docs/phases/**` | `updated` | This current Phase 10 lifecycle record was added following the repository convention. |
| `docs/verification/**` | `reviewed-no-change` | The accepted Verification report remains the current evidence record and was not rewritten during this sweep. |
| `examples/**` | `updated` | `examples/sitemap-output.php` now demonstrates extended typed URL data, `x-default`, and sitemap-index rendering. |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | `updated` | Phase 10 component statuses now match verified runtime while overall completion remains pending Final Review. |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | `updated` | Stale Optional-later wording was synchronized; lifecycle state, strict contracts, and the intentional News date behavior are recorded. |
| `docs/blueprints/**` | `reviewed-no-change` | The accepted Blueprint is the implementation contract and did not require modification. |

No Google eligibility, Search Console submission, indexing guarantee, network
integration, filesystem ownership, HTTP response ownership, or framework
coupling is claimed by these updates.

## Deferred lifecycle work

Final Review must still compare the complete Phase 10 stack against the latest
`main`. Only after Final Review acceptance may PR #191 become Ready or proceed
through the maintainer-controlled merge lifecycle. This Documentation Sweep
does not mark Phase 10 globally Complete.
