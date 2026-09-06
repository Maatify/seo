# Phase 21 — Quality / CI / Release Readiness

## WU3 — Deferred external-verification boundary

This document records the Phase 21 WU3 boundary decision. WU3 is
documentation-only. It does not add an external provider, call an external API,
change runtime validation, or introduce a CI workflow for an external service.

## Core structured-data validation

The library's core structured-data validation remains the existing, framework-
neutral validation pipeline:

- `SeoMetaValidator::validate()` is the public entry point.
- JSON-LD is read through the existing `jsonLd`, `json_ld`, `schema`, and `schemas`
  aliases.
- The existing structural layer validates JSON-LD node shape, numeric node lists,
  `@graph`, recursive nodes, deterministic field paths, and well-formed `@type`
  values.
- The existing deep semantic scope is limited to `Product`, `Offer`,
  `AggregateOffer`, and `ProductGroup` under the Phase 13P contracts.
- Results continue to use `SeoValidationResultDTO` and the existing report, score,
  batch, and exporter contracts.

Core validation describes the structure and declared semantic contracts that the
library implements. It is not a Google Rich Results eligibility check and is not a
Merchant eligibility check.

## Deferred external verification

Google Rich Results, Merchant, Search Console, and similar provider-specific checks
are external verification concerns. They are distinct from the library's core
structured-data validation and are not implemented by Phase 21.

Phase 21 makes the following decisions explicit:

1. Phase 21 does not choose an external provider or Google/Merchant API.
2. Phase 21 does not add provider SDKs, Composer dependencies, credentials, network
   calls, external-service workflows, or `tools/` implementations.
3. WU3 documents the boundary only. The absence of provider integration is
   intentional and is not a gap in Core Phase 21.
4. Any actual Google, Rich Results, Merchant, or other external verification
   integration requires a separate maintainer decision, explicit scope
   authorization, and a follow-up implementation plan after Phase 21.
5. No future provider-specific result may be interpreted as a result produced by
   the core library unless a separately approved contract says so.

This deferred external integration is a documented future boundary, not unfinished
core validation work for Phase 21.

## Separation of future external results

If a future, separately approved integration produces an external result, that result
must remain separate from the library's existing validation contracts. In particular,
it must not be inserted into or silently alter:

- `SeoValidationResultDTO`;
- validation scoring;
- validation summaries;
- batch reports; or
- array, JSON, Markdown, or other existing exporters.

The separation also preserves the existing issue taxonomy, warnings behavior,
public entry points, and runtime validation semantics. A future external result must
identify its external source and status independently rather than appearing as a
core Schema.org validation issue.

## Limitations and non-claims

The library does not guarantee Google Rich Results eligibility, Merchant eligibility,
Search Console acceptance, ranking outcomes, or complete validation of every
Schema.org type. Phase 21 does not select or implement a provider that could make
such a determination.

The documented core boundary is therefore:

`Library structural/semantic validation → separate future external verification`

Any work beyond this boundary is deferred until a separate decision confirms the
provider, authorization, scope, input/output contract, security handling, and
verification lifecycle.

## Documentation Sweep

This Documentation Sweep reviewed the required documentation layers against the
Phase 21 implementation and Verification report. Each path has exactly one status
from the Phase Execution Standard.

| Path | Status | Reason / synchronization result |
| --- | --- | --- |
| `README.md` | `reviewed-no-change` | The README's package requirements, examples, and public feature summary remain accurate; Phase 21 adds no public usage API. |
| `docs/SEO_LIBRARY_REFERENCE.md` | `updated` | Added the current PHP matrix, syntax and structured-data CI gates, existing validation entry point and aliases, scope limitations, external-verification boundary, and release checklist reference. |
| `docs/guides/USAGE_GUIDE.md` | `reviewed-no-change` | No usage or runtime contract changed; existing JSON-LD and validation guidance remains applicable. |
| `docs/guides/INTEGRATION_GUIDE.md` | `reviewed-no-change` | Phase 21 adds no framework or host integration requirement and no external-service dependency. |
| `docs/SEO/**` | `reviewed-no-change` | The structured-data architecture guidance already describes the four-type semantic boundary and the absence of Google/Merchant eligibility guarantees; no CI-specific user workflow requires insertion there. |
| `docs/phases/**` | `updated` | This Phase 21 record now includes the completed sweep and its path-by-path decisions; other phase records were not changed. |
| `docs/verification/**` | `updated` | Added the Phase 21 Verification Gate report recording PASS, commands, versions, counts, and CI evidence; existing verification reports remain historical records. |
| `examples/**` | `reviewed-no-change` | Existing examples were syntax-checked and executed during verification; no new example or API usage is required by Phase 21. |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | `reviewed-no-change` | This roadmap has no Phase 21-specific status or claim requiring synchronization. |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | `updated` | Corrected the Phase 21 wording to describe the implemented CI/release scope, documented external verification as separately approved Future Work, and then recorded the successful Final Review without changing other phases. |
| `docs/blueprints/**` | `reviewed-no-change` | The merged Phase 21 Blueprint matches the implemented WU1–WU4 scope; no Blueprint defect was found. |

The Phase 21 release checklist remains at
`docs/release/PHASE_21_RELEASE_READINESS_CHECKLIST.md`. It was reviewed as part
of this sweep and still preserves Git tags as the release source of truth without
requiring lightweight or annotated tags, adding a version file, or creating
automatic tag/release/publish behavior.

## Final Review

**Final Review Result: `PASS`**

The complete Phase 21 diff was reviewed against the latest approved `main`:

- Reviewed `main` SHA: `228ad2cdc026e0148e934526cc35068b50bf5948`
- Reviewed Draft SHA: `726c84ce8cf30e26f87f2bc125bd278b26b9092a`
- Verification Gate: **PASS**.
- Documentation Sweep: **complete**, with all 11 required path statuses recorded
  above.
- WU1–WU4 and the merged Blueprint are present with no scope bypass.
- No `src/**` changes, runtime changes, unintended public-contract changes, or
  changes to DTOs, scoring, issue taxonomy, exporters, builders, or renderers
  were found.
- The deep semantic scope remains limited to `Product`, `Offer`,
  `AggregateOffer`, and `ProductGroup`; the four JSON-LD aliases remain
  `jsonLd`, `json_ld`, `schema`, and `schemas`.
- The external-verification boundary remains documentation-only: no provider,
  SDK, `tools/**`, network call, credentials, external workflow, Google Rich
  Results eligibility, or Merchant eligibility logic was added.
- Git tags remain the release source of truth without imposing lightweight or
  annotated tags. No version file, tag, release, package publish, or automation
  was added.

No unintended scope or behavior mismatch was found. The integration PR remains
Draft as required for the current review step; no Ready transition or merge into
`main` was performed by this Final Review branch.
