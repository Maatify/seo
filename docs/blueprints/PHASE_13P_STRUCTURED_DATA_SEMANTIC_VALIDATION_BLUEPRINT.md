# Blueprint: Phase 13P — Structured Data Semantic Validation

## 1. Current State

This Blueprint describes the planned validation work against the current repository
state on the Phase 13P integration branch. It does not implement runtime behavior.

### 1.1 Existing validation entry point

`src/Web/Validation/SeoMetaValidator.php` is the current validation entry point for
metadata arrays or objects. It already validates titles, descriptions, canonical URLs,
robots directives, Open Graph, and Twitter data. Its JSON-LD handling is limited to the
private `validateJsonLd()` path called from `validate()` using the existing aliases:

* `jsonLd`
* `json_ld`
* `schema`
* `schemas`

The current JSON-LD checks are structural and shallow:

* `null` JSON-LD is ignored.
* A non-array or empty array produces the existing `invalid_json_ld` warning.
* A numeric list is checked only for non-empty array entries; invalid entries produce
  the existing `invalid_json_ld_schema` warning.
* An associative array currently exits the JSON-LD check without requiring `@type` or
  checking any schema properties or relationships.
* No Product, Offer, AggregateOffer, or ProductGroup semantic validation exists.
* No Google Rich Results or Merchant eligibility validation exists.

### 1.2 Existing result and reporting contracts

The current validation pipeline already provides reusable result and reporting objects:

* `src/Web/Validation/DTO/SeoValidationIssueDTO.php` stores `code`, `severity`,
  `message`, and an optional `field` path.
* `src/Web/Validation/DTO/SeoValidationResultDTO.php` groups issues into errors,
  warnings, and info and derives `isValid` and `hasWarnings`.
* `src/Web/Validation/SeoValidationScoreCalculator.php` scores an existing result.
* `src/Web/Validation/SeoValidationReportBuilder.php` builds a report from the current
  validator and score calculator.
* `src/Web/Validation/SeoValidationBatchReportBuilder.php` runs the same report path for
  a list of metadata items.
* The report and batch exporters serialize those existing DTO contracts.

The current result contract treats errors as invalid, while warnings and info do not
make `isValid` false. JSON-LD semantic issues must fit this contract instead of
introducing a parallel result shape without a demonstrated need.

### 1.3 Existing JSON-LD construction and rendering

The JSON-LD builders are construction helpers, not validators:

* `src/Web/JsonLd/Builder/JsonLdBuilderInterface.php` exposes `set`, `remove`, `has`,
  `get`, `toArray`, and `toJson`.
* `src/Web/JsonLd/Builder/JsonLdBuilderTrait.php` resolves typed composition at output
  time and preserves the current root/nested `@context` behavior.
* `ProductJsonLdBuilder.php`, `OfferJsonLdBuilder.php`,
  `AggregateOfferJsonLdBuilder.php`, and `ProductGroupJsonLdBuilder.php` construct the
  four in-scope node types, but do not check Schema.org semantics.
* `src/Shared/DTO/Schema/JsonLdSchemaDTO.php` wraps a schema array without semantic
  validation.
* `src/Shared/DTO/Schema/GenericSchemaDTO.php` checks basic property input shape, not
  Schema.org type/property compatibility.
* `src/Web/Render/JsonLdScriptRenderer.php` normalizes and renders JSON-LD output; it
  does not validate the rendered schema.

There must be no runtime changes to the JSON-LD builder composition foundation as part
of this Blueprint or its implementation Work Units unless a separately approved defect
is discovered.

### 1.4 Existing tests and project conventions

Validation and builder coverage is implemented as standalone PHP scripts rather than a
PHPUnit test suite. Relevant existing tests include:

* `tests/Phase11ASeoValidationHelpersTest.php` for current metadata and shallow JSON-LD
  checks.
* `tests/Phase11BSeoValidationScoreHelpersTest.php` through
  `tests/Phase11GSeoValidationBatchReportExporterTest.php` for the reporting pipeline.
* `tests/Phase13AJsonLdBuilderFoundationTest.php` through the Phase 13 builder tests.
* `tests/Phase13OAggregateOfferJsonLdBuilderTest.php` and
  `tests/Phase13OProductGroupJsonLdBuilderTest.php` for the current product structured
  data composition behavior.

The repository uses PHP `>=8.2`, has no hard framework dependency, and has no required
external Schema.org or Google validation service.

## 2. Gaps

Phase 13P addresses the following gaps without claiming complete Schema.org or Google
coverage:

1. There is no explicit generic JSON-LD node contract requiring a usable `@type`.
2. There is no distinction in code between generic structural checks and semantic checks
   for a known Schema.org type.
3. There are no semantic validators for Product, Offer, AggregateOffer, or
   ProductGroup.
4. Known relationships such as Product `offers`, Product `isVariantOf`, ProductGroup
   `hasVariant`, and AggregateOffer `offers` are not checked for compatible node types.
5. Known property value shapes, cardinality, and invalid in-scope relationships are not
   reported with stable field paths.
6. Nested nodes and numeric lists do not receive semantic validation diagnostics.
7. The existing report, score, batch, and exporter pipeline has no documented contract
   for JSON-LD semantic issues.
8. The current documentation must distinguish Schema.org-oriented semantic validation
   from Google eligibility and must state the Phase 13P type boundary.

## 3. Decisions / Contracts

### 3.1 Four validation layers

Phase 13P must keep these layers separate:

1. **Current validation foundation:** existing non-empty JSON-LD array and list-entry
   checks already performed by `SeoMetaValidator`.
2. **Generic structural validation:** node shape, root/list traversal, `@type`
   presence, recognized type identity, and valid node/list placement. This layer is not
   a Google eligibility check.
3. **In-scope semantic validation:** Schema.org-oriented property, value-shape,
   cardinality, and relationship checks for Product, Offer, AggregateOffer, and
   ProductGroup only.
4. **Google Rich Results / Merchant eligibility:** a separate layer that is outside
   Phase 13P and remains Future Work.

The implementation must not merge layers 3 and 4 or describe a Phase 13P pass as proof
of Google eligibility.

### 3.2 Public entry point and backward compatibility

The existing public signature remains stable:

```php
SeoMetaValidator::validate(array|object $meta, array $options = []): SeoValidationResultDTO
```

Its title, description, canonical, robots, Open Graph, Twitter, and existing JSON-LD
shape behavior must remain compatible for valid inputs. Phase 13P may add diagnostics
for invalid JSON-LD structures and in-scope semantics, but must not change the result
DTO shape or the existing report, score, batch, and exporter contracts.

The implementation may introduce a focused JSON-LD validation facade under
`src/Web/Validation/` for direct array validation if that is required by the Work Unit
design. If introduced, its contract must return the existing
`SeoValidationResultDTO`; it must not create a competing result model.

Direct validation input is materialized JSON-LD data: an associative node or a numeric
list of nodes. Builders and DTOs are not implicitly mutated or normalized by the
validator. Callers that start with a builder or `JsonLdSchemaDTO` materialize it first
using its existing output contract.

### 3.3 Read-only validation and context handling

Validation is read-only:

* Raw arrays are never rewritten or auto-fixed.
* Builders are never changed by validation.
* No missing property, `@type`, `@context`, or relationship is injected.
* No remote context is fetched or expanded.

`@context` is not a Google eligibility decision. Root and nested nodes must be handled
without rejecting a valid nested composition solely because a nested builder has no
`@context`; the existing builder foundation intentionally strips nested builder
contexts. An explicitly supplied raw-array `@context` remains input data and must not
be removed by validation.

### 3.4 Node and issue contract

The validator accepts either:

* one non-empty associative node; or
* a numeric list of non-empty associative nodes.

Every node that is structurally validated must have an `@type` identifying the expected
type. A string type is supported; a type list is acceptable only when the expected type
is present. The validator must retain the node's location in the `field` value, for
example `jsonLd.0.offers.1.price`.

New issue codes must use a stable `json_ld_` prefix and be asserted by tests. The
initial code taxonomy is:

* `json_ld_invalid_node`
* `json_ld_missing_type`
* `json_ld_invalid_type`
* `json_ld_invalid_property`
* `json_ld_invalid_relationship`

If implementation discovers a genuinely distinct case that needs another code, the
Work Unit PR must document it and add its test before merging. The existing
`invalid_json_ld` and `invalid_json_ld_schema` codes remain unchanged for their current
foundation behavior.

The severity contract is:

* structural and semantic violations are errors and therefore affect `isValid`;
* non-failing advisory information may be info or warning;
* out-of-scope schema types are never semantic failures merely because they are outside
  Phase 13P; they may be reported as non-failing information if useful;
* Google eligibility findings are not emitted by Phase 13P.

Unknown or extension properties must not be rejected solely because they are not in the
initial rule catalog. Only explicitly supported in-scope properties and relationships
are semantically checked, preventing an accidental closed-world validator.

### 3.5 In-scope type contracts

The semantic rule catalog is limited to these four types:

| Type | In-scope checks | In-scope relationships |
| --- | --- | --- |
| `Product` | `@type`, supported scalar/property value shapes, supported collection shapes, and product-group identifiers | `offers` accepts `Offer` or `AggregateOffer` nodes or lists; `isVariantOf` accepts a `ProductGroup` node; `inProductGroupWithID` is a scalar identifier |
| `Offer` | `@type`, price/currency/availability/URL/date/condition value shapes, and seller node shape | `seller` may reference a supported Organization/Person-shaped node without deep validation of that out-of-scope type |
| `AggregateOffer` | `@type`, low/high price, currency, offer count, availability, and collection value shapes | `offers` accepts `Offer` nodes or lists |
| `ProductGroup` | `@type`, name/description/URL/group ID, `variesBy` list shape, and brand node shape | `hasVariant` accepts `Product` nodes or lists |

The initial property catalog must be based on the existing builders and the Phase 13O
contracts before implementation. It must not silently expand to every Schema.org
property. Other JSON-LD schema types, including their internal properties, remain
outside Phase 13P semantic scope.

Relationships to out-of-scope node types may be checked only for the minimum shape and
allowed relationship target required by an in-scope rule; the out-of-scope node's own
semantic properties are not validated.

### 3.6 No Google eligibility contract

Phase 13P must not implement or imply:

* Google Rich Results eligibility checks;
* Google Merchant listing eligibility checks;
* Google-specific required-field policy as a substitute for Schema.org semantics;
* ranking, indexing, rendering, or algorithmic outcome predictions;
* a guarantee that a semantically valid node produces a Rich Result.

Those concerns are a separate Future Work phase and must remain explicitly documented
as deferred.

## 4. Scope / Out of Scope

### 4.1 Scope

Phase 13P includes:

* completing generic JSON-LD structural validation needed to safely inspect nodes;
* semantic validation for `Product`, `Offer`, `AggregateOffer`, and `ProductGroup`;
* supported property value shapes and collection cardinality for those types;
* the in-scope relationships listed in Section 3.5;
* recursive validation of nested in-scope nodes and numeric lists;
* stable field paths and issue codes through the existing validation result pipeline;
* compatibility with `SeoValidationReportBuilder`, scoring, batch reports, and exporters;
* standalone PHP regression coverage and full existing test-suite compatibility;
* documentation and verification that accurately state the four-layer model and scope.

### 4.2 Out of Scope

The following are explicitly excluded:

* semantic validation for every JSON-LD type other than Product, Offer, AggregateOffer,
  and ProductGroup;
* Google Rich Results, Google Merchant eligibility, or any other Google-specific
  eligibility policy; these remain Future Work;
* live calls to Schema.org, Google, or any external validation service;
* remote JSON-LD context expansion or vocabulary fetching;
* automatic repair, normalization, or mutation of raw arrays and builders;
* changes to `JsonLdBuilderTrait`, builder composition, builder public APIs, or
  renderer behavior unless a separate defect is approved;
* changes to Product, Offer, AggregateOffer, or ProductGroup construction semantics;
* HTTP handling, framework integration, persistence, database work, or UI output;
* replacing the existing `SeoValidationResultDTO`, report DTOs, batch DTOs, scoring, or
  exporter contracts;
* validation of custom/extension properties beyond the explicit in-scope rule catalog;
* claims of complete Schema.org coverage or Google eligibility.

## 5. Work Units

Each Work Unit is expected to be a separate PR targeting `codex/phase-13p-draft`. No
Work Unit may merge directly into `main`. The Integration PR remains Draft until all
Work Units, verification, documentation sweep, and final review are complete.

### Work Unit 1 — Generic JSON-LD Validation Foundation

**Scope**

Implement the read-only root/list traversal and generic node checks required by the
semantic validators, while preserving current non-JSON-LD validation behavior.

**Expected files**

* `src/Web/Validation/SeoMetaValidator.php`
* `src/Web/Validation/JsonLd/JsonLdSemanticValidator.php` or the smallest equivalent
  focused validation boundary under `src/Web/Validation/`
* `tests/Phase13PJsonLdStructuralValidationTest.php`

New internal helper files are allowed only when they preserve the public contract in
Section 3.2 and are documented in the Work Unit PR.

**Required tests**

* associative root and numeric-list input;
* empty/non-array input regression;
* missing or incompatible `@type` diagnostics;
* nested nodes and field paths;
* root versus nested `@context` handling;
* raw input remains unchanged;
* existing Phase 11A JSON-LD behavior remains compatible where the input is valid.

**Dependencies**

None.

**Done Criteria**

Generic structural checks are deterministic, read-only, covered by standalone tests,
and return `SeoValidationResultDTO`-compatible issues without changing unrelated SEO
validation behavior.

### Work Unit 2 — Product and Offer Semantic Validation

**Scope**

Implement the explicit rule catalog for Product and Offer, including supported fields,
value shapes, nested node traversal, and Product `offers` / `isVariantOf` relationships.

**Expected files**

* `src/Web/Validation/JsonLd/ProductJsonLdValidator.php` or an equivalent internal
  Product rule component;
* `src/Web/Validation/JsonLd/OfferJsonLdValidator.php` or an equivalent internal Offer
  rule component;
* `src/Web/Validation/JsonLd/JsonLdSemanticValidator.php` when orchestration changes;
* `tests/Phase13PProductOfferSemanticValidationTest.php`.

**Required tests**

* valid Product and Offer nodes;
* missing/wrong Product and Offer types;
* supported Product fields and invalid value shapes;
* Offer price, currency, availability, URL/date/condition, and seller shapes;
* Product `offers` with Offer and AggregateOffer nodes/lists;
* Product `isVariantOf` with ProductGroup;
* invalid relationship targets and nested field paths;
* out-of-scope nested Organization/Person internals are not deeply validated.

**Dependencies**

Work Unit 1.

**Done Criteria**

Product and Offer semantic violations are reported through the existing result contract,
valid Phase 13O output passes, and no Product/Offer builder behavior changes.

### Work Unit 3 — AggregateOffer and ProductGroup Semantic Validation

**Scope**

Implement the explicit rule catalog for AggregateOffer and ProductGroup, including
AggregateOffer `offers` and ProductGroup `hasVariant` relationship traversal.

**Expected files**

* `src/Web/Validation/JsonLd/AggregateOfferJsonLdValidator.php` or an equivalent
  internal AggregateOffer rule component;
* `src/Web/Validation/JsonLd/ProductGroupJsonLdValidator.php` or an equivalent internal
  ProductGroup rule component;
* `src/Web/Validation/JsonLd/JsonLdSemanticValidator.php` when orchestration changes;
* `tests/Phase13PAggregateOfferProductGroupSemanticValidationTest.php`.

**Required tests**

* valid AggregateOffer and ProductGroup nodes;
* price range, currency, count, availability, group ID, and `variesBy` shapes;
* AggregateOffer `offers` with Offer nodes/lists;
* ProductGroup `hasVariant` with Product nodes/lists;
* invalid relationship targets, empty collection nodes, and order-preserving paths;
* exact Product → AggregateOffer and Product → ProductGroup scenarios from Phase 13O.

**Dependencies**

Work Units 1 and 2.

**Done Criteria**

AggregateOffer and ProductGroup semantic checks are covered, their supported
relationships are recursive and deterministic, and existing Phase 13O tests remain
unchanged and passing.

### Work Unit 4 — Validation Pipeline and Compatibility Integration

**Scope**

Integrate the four-layer JSON-LD result into the existing metadata, report, score, batch,
and exporter flow without changing their public DTO shapes or unrelated validation
rules.

**Expected files**

* `src/Web/Validation/SeoMetaValidator.php`;
* `src/Web/Validation/SeoValidationReportBuilder.php` only if integration requires it;
* `src/Web/Validation/SeoValidationBatchReportBuilder.php` only if integration requires
  it;
* `tests/Phase13PValidationPipelineTest.php`;
* existing Phase 11 validation tests only when a regression assertion must be added.

**Required tests**

* Product/Offer/AggregateOffer/ProductGroup issues flow into `SeoValidationResultDTO`;
* `isValid`, warning behavior, scoring, report summaries, batch aggregation, and JSON/
  Markdown/array exporters remain compatible;
* existing JSON-LD aliases (`jsonLd`, `json_ld`, `schema`, `schemas`) remain supported;
* valid existing metadata and all Phase 11 tests remain passing;
* no Google eligibility result is emitted.

**Dependencies**

Work Units 1, 2, and 3.

**Done Criteria**

The new semantic validation is reachable through the existing validation workflow,
backward-compatible DTO contracts are preserved, and the full existing test suite
passes.

### Work Unit 5 — Documentation, Examples, and Verification

**Scope**

Document the shipped behavior and limitations, add a minimal usage example if the
public entry point requires one, complete the verification report, and perform the
mandatory documentation sweep and final review.

**Expected files**

* `docs/SEO_LIBRARY_REFERENCE.md`;
* `docs/guides/USAGE_GUIDE.md` when the public usage path changes or gains a new example;
* `docs/phases/PHASE_13P_STRUCTURED_DATA_SEMANTIC_VALIDATION.md`;
* `docs/verification/PHASE_13P_STRUCTURED_DATA_SEMANTIC_VALIDATION_VERIFICATION_REPORT.md`;
* `examples/phase-13p-structured-data-validation.php` when an example is required by
  the final API;
* `docs/roadmap/SEO_LIBRARY_ROADMAP.md`;
* `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md`.

**Required tests and checks**

* run all standalone tests under `tests/`;
* run `vendor/bin/phpstan analyse`;
* verify the documented scope excludes other JSON-LD types and Google eligibility;
* review documentation impact decisions for every applicable path.

**Dependencies**

Work Unit 4.

**Done Criteria**

Documentation matches the actual runtime behavior, limitations and deferred work are
explicit, verification evidence is recorded, and the Integration PR is eligible to
move from Draft to Ready only after the final review against the latest `main`.

## 6. Test Matrix

The tests must remain standalone PHP scripts following the repository convention. The
matrix below is the minimum required coverage:

| Area | Required coverage | Expected outcome |
| --- | --- | --- |
| Existing foundation | `null`, empty, non-array, associative node, numeric list, and empty list entries | Existing warnings remain compatible; new structural errors are deterministic |
| Generic structure | missing `@type`, wrong `@type`, supported type list, empty node, nested node, invalid collection shape | Errors include stable codes and precise `field` paths |
| Context | root context, nested builder-style omission, raw nested context | Validation is read-only and does not remove or inject context |
| Product | supported fields, `offers`, `isVariantOf`, `inProductGroupWithID`, invalid values | Product rules apply only to the declared catalog |
| Offer | price, currency, availability, URL/date/condition, seller | Offer rules and seller relationship shape are covered |
| AggregateOffer | low/high price, currency, offer count, availability, nested offers | Only Offer nodes are accepted in `offers` |
| ProductGroup | group ID, `variesBy`, brand, `hasVariant` | Only Product nodes are accepted in `hasVariant` |
| Cross-type composition | Product→Offer, Product→AggregateOffer, Product→ProductGroup, ProductGroup→Product | Valid Phase 13O scenarios pass; invalid targets fail |
| Out-of-scope types | Article, Organization, WebSite, and at least one other existing builder output | No deep semantic validation or Google claim is produced |
| Extension properties | unknown/custom properties on an in-scope node | Not rejected solely for being outside the initial catalog |
| Result pipeline | result DTO, score, report, batch, array/JSON/Markdown exporters | Existing DTO shapes and summaries remain compatible |
| Regression | all existing `tests/` scripts, including Phase 11 and Phase 13A–O | No unrelated regression |
| Static analysis | `vendor/bin/phpstan analyse` | No new static-analysis errors |

Exact rule-level cases, issue codes, and expected field paths must be added to the
Work Unit tests before the corresponding implementation is merged.

## 7. Documentation Impact

The following review is mandatory before Phase 13P can be marked complete. Each path
must receive one final status: `updated`, `reviewed-no-change`, or
`deferred-with-reason`.

| Path | Planned review decision |
| --- | --- |
| `README.md` | `reviewed-no-change` unless the final public entry point is promoted to the top-level quick-start surface; document the reason either way |
| `docs/SEO_LIBRARY_REFERENCE.md` | `updated` with the validator entry point, four layers, supported types, and limitations |
| `docs/guides/USAGE_GUIDE.md` | `updated` if a public JSON-LD validation example is added; otherwise `reviewed-no-change` with rationale |
| `docs/guides/INTEGRATION_GUIDE.md` | `reviewed-no-change` unless integration or CI usage changes; any change requires an explicit update |
| `docs/SEO/**` | `reviewed-no-change` unless schema/validation guidance or claims need correction |
| `docs/phases/**` | `updated` with the final Phase 13P implementation and contract summary |
| `docs/verification/**` | `updated` with `PHASE_13P_STRUCTURED_DATA_SEMANTIC_VALIDATION_VERIFICATION_REPORT.md` |
| `examples/**` | `updated` when the public API needs a runnable semantic-validation example; otherwise `reviewed-no-change` with rationale |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | `reviewed-no-change` or `updated` after checking the main roadmap's Phase 13P entry |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | `updated` only after implementation, tests, verification, documentation sweep, and final review are complete |
| `docs/blueprints/**` | `updated` if the approved contract changes; otherwise this Blueprint remains the source definition |

No documentation layer may claim Google eligibility, Merchant eligibility, or semantic
coverage for JSON-LD types outside Product, Offer, AggregateOffer, and ProductGroup.

## 8. Definition of Done

Phase 13P is complete only when all of the following are true:

1. Generic structural validation and the four in-scope semantic validators are
   implemented and covered by the declared Test Matrix.
2. Product, Offer, AggregateOffer, and ProductGroup semantics and relationships are
   validated without silently expanding the type scope.
3. Other JSON-LD schema types remain outside semantic scope and are not falsely
   reported as Google-eligible or Schema.org-complete.
4. Google Rich Results and Merchant eligibility remain explicitly documented as
   separate Future Work.
5. Existing builder APIs, composition behavior, renderers, result DTOs, report DTOs,
   batch DTOs, scoring, exporters, and unrelated metadata validation remain backward
   compatible.
6. All required standalone tests pass, including all existing tests under `tests/`.
7. `vendor/bin/phpstan analyse` passes at the repository's configured level.
8. Documentation Impact Review is complete for every applicable path, with each path
   marked `updated`, `reviewed-no-change`, or `deferred-with-reason`.
9. Phase documentation, verification evidence, required examples, limitations, and
   deferred work are synchronized with the actual code.
10. The final implementation is reviewed against the latest `main`.
11. Only after the preceding gates pass may the Integration PR leave Draft, become
    Ready, and merge into `main`.

The roadmap status must not be changed to `Complete` before every criterion above is
met.

## 9. Explicit Limitations and Deferred Work

Phase 13P intentionally does not provide:

* semantic validation for other JSON-LD schema types;
* Google Rich Results or Merchant eligibility validation;
* a guarantee of search visibility, indexing, ranking, or rich-result appearance;
* a complete closed-world Schema.org vocabulary validator;
* remote context resolution or third-party validation service integration.

These limitations must remain visible in the final Phase documentation and verification
report.
