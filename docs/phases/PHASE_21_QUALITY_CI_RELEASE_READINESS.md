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
