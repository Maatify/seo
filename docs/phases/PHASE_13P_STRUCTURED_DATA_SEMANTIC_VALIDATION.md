# Phase 13P — Structured Data Semantic Validation

## Final implemented scope

Phase 13P adds read-only JSON-LD structural and scoped semantic validation to the
existing SEO metadata validation pipeline. The implementation preserves the existing
public DTOs and entry points while carrying structural and semantic JSON-LD issues into
`SeoValidationResultDTO`, reports, scoring, batch reports, and exporters.

The public entry point remains:

```php
SeoMetaValidator::validate(array|object $meta, array $options = []): SeoValidationResultDTO
```

JSON-LD is read from the existing metadata aliases `jsonLd`, `json_ld`, `schema`, and
`schemas`. No builder or input array is mutated by validation.

## Four validation layers

Phase 13P keeps these layers separate:

1. **Current validation foundation** — existing non-empty JSON-LD array and schema-entry
   checks, including the compatible `invalid_json_ld` and `invalid_json_ld_schema`
   issues.
2. **Generic structural validation** — root nodes, numeric node lists, recursive node
   placement, valid `@type` shape, deterministic field paths, and graph documents.
3. **In-scope semantic validation** — fixed Schema.org property, value-shape,
   collection, and relationship contracts for the four deep-validation types only.
4. **Google Rich Results / Merchant eligibility** — outside Phase 13P and Future Work.

The fourth layer is not inferred from a structural or semantic pass. Phase 13P does
not emit Google or Merchant eligibility findings.

## Deep semantic scope

Deep semantic validation is limited to:

- `Product`
- `Offer`
- `AggregateOffer`
- `ProductGroup`

Other JSON-LD types remain outside deep-validation scope. They may be valid relationship
targets where the fixed Schema.org range allows them, but their internal properties are
not deeply validated merely because they are nested.

The implemented catalog includes the fixed properties from the Blueprint:

- `Product`: product identity and descriptive fields, `brand`, media/category fields,
  `offers`, `aggregateRating`, `review`, `isVariantOf`, and `inProductGroupWithID`.
- `Offer`: price, currency, availability, URL/date fields, condition, and `seller`.
- `AggregateOffer`: `lowPrice`, `highPrice`, `priceCurrency`, `offerCount`,
  `availability`, and `offers`.
- `ProductGroup`: `name`, `description`, `brand`, `url`, `productGroupID`, `variesBy`,
  and `hasVariant`.

The fixed relationship contracts include:

- Product `offers`: `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`, or
  `OfferForPurchase`.
- Product `isVariantOf`: `ProductGroup` or `ProductModel`.
- AggregateOffer `offers`: the same offer-family range, including `Demand`.
- Offer `seller`: `Organization` or `Person`.
- ProductGroup `hasVariant`: `Product`.
- Product and ProductGroup `brand`: `Brand` or `Organization`.

## Generic structural and graph support

An ordinary root is a non-empty associative node with a valid `@type`. A top-level
numeric list contains non-empty associative nodes; nested numeric lists are not treated
as nodes. A graph document is an associative wrapper containing a non-empty numeric
`@graph` list of associative nodes. The graph wrapper may keep `@context` without its
own `@type`, while every node inside the graph follows the ordinary node contract.

Graph nodes are traversed recursively with deterministic paths such as
`jsonLd.@graph.0.offers.price`. The graph wrapper and input `@context` values are not
modified by validation.

`@type` accepts either a non-empty string or a non-empty numeric list of non-empty
strings. Short Schema.org names and `http`/`https` Schema.org IRIs match by canonical
local type identity without rewriting the input. A well-formed type outside the four
deep-validation types is not an error, warning, or info condition by itself.

## Issue codes and general contracts

The stable JSON-LD issue taxonomy is:

- `invalid_json_ld` — existing foundation behavior for null, empty, or non-array JSON-LD.
- `invalid_json_ld_schema` — existing foundation behavior for invalid list entries.
- `json_ld_invalid_node` — malformed node/list placement or malformed/empty `@graph`.
- `json_ld_missing_type` — a structurally validated node has no `@type`.
- `json_ld_invalid_type` — `@type` is empty, non-string, associative, or otherwise
  malformed; it is not used for out-of-scope but well-formed types.
- `json_ld_invalid_property` — an in-scope catalog value or representation is invalid.
- `json_ld_invalid_relationship` — a node relationship target is outside its fixed
  canonical range.

Structural and semantic issues are errors and therefore make `isValid` false. Existing
metadata warnings, scoring penalties, report summaries, batch aggregation, array/JSON/
Markdown exporters, and public DTO shapes remain compatible. Unknown extension
properties are not rejected solely because they are outside the fixed Phase 13P catalog.

## Verification result

Verification was run from Draft HEAD
`85b8f7f52ddbed58be5fb95180982e341a1a9a8b` before this Documentation Sweep.

- `vendor/bin/phpstan analyse`: **PASS** — `[OK] No errors`.
- Standalone tests under `tests/*.php`: **PASS** — 48/48 scripts passed.
- Evidence report: `docs/verification/PHASE_13P_STRUCTURED_DATA_SEMANTIC_VALIDATION_VERIFICATION_REPORT.md`.

The focused tests cover WU1 structural/graph behavior, WU2 Product and Offer
semantics, WU3 AggregateOffer and ProductGroup semantics, and WU4 validation-pipeline
compatibility.

## Limitations and Future Work

Phase 13P does not provide deep semantic coverage for JSON-LD types outside Product,
Offer, AggregateOffer, and ProductGroup. It does not claim complete Schema.org
validation, enforce every required-property policy, perform URL reachability or
lexical/enum validation beyond the declared representation contracts, or mutate/expand
remote JSON-LD contexts.

Google Rich Results validation and Merchant eligibility validation are separate Future
Work. A Phase 13P structural or semantic pass must not be presented as proof of either
eligibility outcome.

Final Review against the latest `main` is still pending; this document records the
implemented scope and Documentation Sweep only.

## Documentation Impact Review

Each Section 8 path was reviewed during this sweep and has one explicit final status.

| Path | Status | Reason |
| --- | --- | --- |
| `README.md` | `reviewed-no-change` | The top-level quick start and feature summary remain accurate and do not need a new validator example for this Phase. |
| `docs/SEO_LIBRARY_REFERENCE.md` | `updated` | Added the public validator entry point, four layers, scoped types, issue taxonomy, relationships, aliases, and limitations. |
| `docs/guides/USAGE_GUIDE.md` | `reviewed-no-change` | Existing validator, score, report, batch, and exporter usage remains correct; no public API or usage flow changed. |
| `docs/guides/INTEGRATION_GUIDE.md` | `reviewed-no-change` | Phase 13P does not change framework, CI, middleware, or host integration contracts. |
| `docs/SEO/**` | `updated` | Updated `docs/SEO/library/STRUCTURED_DATA_ARCHITECTURE.md` to reflect Phase 13P scoped validation and its non-eligibility boundary. |
| `docs/phases/**` | `updated` | Added this Phase 13P implementation and contract summary. |
| `docs/verification/**` | `reviewed-no-change` | The Phase 13P Verification Gate report already records the verified Draft HEAD, PHPStan PASS, and 48/48 test result; no verification evidence changed during this sweep. |
| `examples/**` | `reviewed-no-change` | No runtime or public API changed, and the existing Phase 13O structured-data example remains valid. |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | `reviewed-no-change` | No Phase 13P completion status is maintained in this roadmap; changing unrelated roadmap entries is outside this sweep. |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | `deferred-with-reason` | Phase 13P must not be marked Complete until Final Review against the latest `main` passes. |
| `docs/blueprints/**` | `reviewed-no-change` | The approved Phase 13P Blueprint remains the source contract and was not changed during implementation or this sweep. |

Documentation Sweep is complete for the listed paths. Final Review against the latest
`main` remains a separate required gate before the Integration PR can become Ready or
the Phase can be marked Complete.
