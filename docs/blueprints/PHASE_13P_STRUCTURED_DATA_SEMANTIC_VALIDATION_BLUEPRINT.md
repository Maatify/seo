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
   presence, well-formed type identity, and valid node/list placement. This layer is not
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

Every node that is structurally validated must have an `@type` value that satisfies this
closed contract:

* a non-empty string after trimming; or
* a non-empty numeric list in which every item is a non-empty string after trimming.

An absent `@type` produces `json_ld_missing_type`. An empty, non-string, associative,
or otherwise malformed `@type` produces `json_ld_invalid_type`. A structurally valid
type string or type list does not fail, warn, or emit info merely because none of its
types belongs to the Phase 13P deep-validation set. The validator must retain the
node's location in the `field` value, for example `jsonLd.0.offers.1.price`.

#### Well-formed type identity and matching

Type matching is based on the canonical local Schema.org type identity. The following
forms are equivalent for relationship and deep-validation matching, without rewriting
the input node:

| Input `@type` token | Matched identity |
| --- | --- |
| `Product` | `Product` |
| `https://schema.org/Product` | `Product` |
| `http://schema.org/Product` | `Product` |

The same short-name and `http`/`https` Schema.org IRI equivalence applies to the other
catalog types. Any non-empty string that is not in the Phase 13P catalog can still be a
well-formed type identity; being outside the catalog is not, by itself, an error,
warning, or info condition. A numeric `@type` list matches a relationship when at least
one normalized item is in that relationship's allowed range. Deep validation runs when
the list contains at least one of `Product`, `Offer`, `AggregateOffer`, or
`ProductGroup` after normalization. A list may therefore contain additional valid
out-of-scope types without becoming invalid solely because they are out of scope.

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
foundation behavior. `json_ld_invalid_type` is reserved for a malformed `@type`
contract; it must never be emitted merely because a valid type is outside the Phase
13P catalog.

The severity contract is:

* structural and semantic violations are errors and therefore affect `isValid`;
* non-failing advisory information may be info or warning;
* out-of-scope schema types are never failures, warnings, or info merely because they
  are outside Phase 13P;
* relationship incompatibility is evaluated against the fixed canonical relationship
  range in Section 3.5, not against the four-type deep-validation set;
* Google eligibility findings are not emitted by Phase 13P.

Unknown or extension properties must not be rejected solely because they are not in the
fixed Phase 13P property catalog in Section 3.5. Only the properties and relationships
listed there are semantically checked, preventing an accidental closed-world validator.

### 3.5 Schema.org canonical source and fixed property catalog

The canonical Schema.org type pages are the source of truth for the expected types and
ranges of the properties in this catalog:

* [`Product`](https://schema.org/Product)
* [`Offer`](https://schema.org/Offer)
* [`AggregateOffer`](https://schema.org/AggregateOffer)
* [`ProductGroup`](https://schema.org/ProductGroup)

The following catalog is fixed before implementation. Work Units must not add local
property ranges, narrow a canonical range because of a PHP setter signature, or select
semantic rules from builder output. `Text`, `URL`, `Number`, `Integer`, `Date`,
`DateTime`, and node types below refer to the canonical Schema.org expected types.

| Owner type | Property | Canonical expected type / range |
| --- | --- | --- |
| `Product` | `name` | `Text` |
| `Product` | `description` | `Text` or `TextObject` |
| `Product` | `sku` | `Text` |
| `Product` | `gtin` | `Text` or `URL` |
| `Product` | `mpn` | `Text` |
| `Product` | `brand` | `Brand` or `Organization` |
| `Product` | `image` | `ImageObject` or `URL` |
| `Product` | `category` | `CategoryCode`, `PhysicalActivityCategory`, `Text`, `Thing`, or `URL` |
| `Product` | `url` | `URL` |
| `Product` | `color` | `Text` |
| `Product` | `size` | `DefinedTerm`, `QuantitativeValue`, `SizeSpecification`, or `Text` |
| `Product` | `material` | `Product`, `Text`, or `URL` |
| `Product` | `pattern` | `DefinedTerm` or `Text` |
| `Product` | `offers` | `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`, or `OfferForPurchase` |
| `Product` | `aggregateRating` | `AggregateRating` |
| `Product` | `review` | `Review` |
| `Product` | `isVariantOf` | `ProductGroup` or `ProductModel` |
| `Product` | `inProductGroupWithID` | `Text` |
| `Offer` | `price` | `Number` or `Text` |
| `Offer` | `priceCurrency` | `Text` |
| `Offer` | `availability` | `ItemAvailability` |
| `Offer` | `url` | `URL` |
| `Offer` | `validFrom` | `Date` or `DateTime` |
| `Offer` | `priceValidUntil` | `Date` |
| `Offer` | `itemCondition` | `OfferItemCondition` |
| `Offer` | `seller` | `Organization` or `Person` |
| `AggregateOffer` | `lowPrice` | `Number` or `Text` |
| `AggregateOffer` | `highPrice` | `Number` or `Text` |
| `AggregateOffer` | `priceCurrency` | `Text` |
| `AggregateOffer` | `offerCount` | `Integer` |
| `AggregateOffer` | `availability` | `ItemAvailability` |
| `AggregateOffer` | `offers` | `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`, or `OfferForPurchase` |
| `ProductGroup` | `name` | `Text` |
| `ProductGroup` | `description` | `Text` or `TextObject` |
| `ProductGroup` | `brand` | `Brand` or `Organization` |
| `ProductGroup` | `url` | `URL` |
| `ProductGroup` | `productGroupID` | `Text` |
| `ProductGroup` | `variesBy` | `DefinedTerm` or `Text` |
| `ProductGroup` | `hasVariant` | `Product` |

JSON-LD may encode a repeated property as a numeric list of values from the same
canonical range. A node value must carry its own valid `@type` contract before its
relationship target is evaluated.

The relationship contracts are fixed as follows:

* `Product.isVariantOf` accepts `ProductGroup` or `ProductModel`.
* `Product.offers` accepts `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`, or
  `OfferForPurchase`.
* `AggregateOffer.offers` accepts the same range: `Demand`, `Offer`,
  `AggregateOffer`, `OfferForLease`, or `OfferForPurchase`. A `Demand` target must not
  be rejected merely because `Demand` is outside the Phase 13P deep-validation set.
* `Offer.seller` accepts `Organization` or `Person`.
* `ProductGroup.hasVariant` accepts `Product`.
* `brand` uses the canonical `Brand` or `Organization` range for both `Product` and
  `ProductGroup`. It must not be narrowed because the current builders may emit a
  string-derived Brand node, a raw array, or a typed builder node.

Deep semantic validation is implemented only for `Product`, `Offer`, `AggregateOffer`,
and `ProductGroup`. A valid relationship target outside those four types is allowed
when it is permitted by the canonical relationship range, but the target's internal
properties are not deeply validated. Examples include `ProductModel` for
`Product.isVariantOf`, `Demand` for `offers`, `Organization` or `Person` for
`Offer.seller`, and `OfferForLease` or `OfferForPurchase` for `offers`.

### 3.6 JSON value representation contract

The following materialized JSON-LD value representations are fixed for the Phase 13P
catalog. These are representation contracts, not lexical, enum-membership, or Google
eligibility checks:

* `Text` is a PHP `string`.
* `Number` is a PHP `int` or `float`.
* `Integer` is a PHP `int`.
* `URL`, `Date`, `DateTime`, `ItemAvailability`, and `OfferItemCondition` are
  non-empty PHP strings. Phase 13P does not perform lexical-format or enum-membership
  validation for these values.
* A node type is an associative PHP array with a valid `@type` matching the permitted
  relationship range. Its internals receive deep validation only when its normalized
  type includes `Product`, `Offer`, `AggregateOffer`, or `ProductGroup`.
* A repeated property is a non-empty numeric list, and every item is checked using the
  same value contract as the property in its singular form.
* `variesBy` is either one `Text` value or one `DefinedTerm` node, or a non-empty
  numeric list whose items are each a `Text` value or a `DefinedTerm` node.
* Internals of an out-of-scope node are not deeply validated after the node passes its
  relationship and `@type` checks.

### 3.7 In-scope type contracts

The four deep-validation contracts are:

| Type | In-scope checks | In-scope relationships |
| --- | --- | --- |
| `Product` | `@type`, the fixed Product catalog, canonical value shapes, repeated values, and product-group identifiers | `offers` accepts `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`, or `OfferForPurchase`; `isVariantOf` accepts `ProductGroup` or `ProductModel`; `inProductGroupWithID` is `Text` |
| `Offer` | `@type`, the fixed Offer catalog, canonical value shapes, repeated values, and seller range | `seller` accepts `Organization` or `Person` without deep validation of those target types |
| `AggregateOffer` | `@type`, the fixed AggregateOffer catalog, canonical value shapes, repeated values, and offer count | `offers` accepts `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`, or `OfferForPurchase`, including `Demand` without deep validation |
| `ProductGroup` | `@type`, name/description/URL/group ID, `variesBy` list shape, and brand node shape | `hasVariant` accepts `Product` nodes or lists |

Relationships to out-of-scope node types may be checked only for the minimum shape and
allowed relationship target required by the fixed catalog; the out-of-scope node's own
semantic properties are not validated. Relationship incompatibility is determined by
the canonical relationship range, not by whether the target type is included in the
four-type deep-validation set.

### 3.8 No Google eligibility contract

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

Work Units 1–4 are the only implementation Work Units in Phase 13P. Each Work Unit is
expected to be a separate PR targeting `codex/phase-13p-draft`. No Work Unit may merge
directly into `main`. Verification, Documentation Sweep, and Final Review are
post-implementation gates, not Work Units and not Work Unit PRs.

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
* missing and malformed `@type` diagnostics;
* out-of-scope but structurally valid types produce no warning or info;
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
* Product and Offer nodes with their expected types and malformed `@type` values;
* supported Product fields and invalid value shapes;
* Offer price, currency, availability, URL/date/condition, and seller shapes;
* Product `offers` with `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`, and
  `OfferForPurchase` nodes/lists;
* Product `isVariantOf` with ProductGroup and ProductModel;
* Offer `seller` with Organization and Person;
* Product `brand` values are checked against the canonical range, not narrowed to the
  current builder representation;
* invalid relationship targets and nested field paths;
* valid out-of-scope relationship targets are not deeply validated and produce no
  scope-only warning or info.

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
* AggregateOffer `offers` with `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`,
  and `OfferForPurchase` nodes/lists;
* ProductGroup `hasVariant` with Product nodes/lists;
* ProductGroup `brand` values are checked against the canonical `Brand` or
  `Organization` range, not narrowed to the current builder representation;
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

## 6. Post-Implementation Gates (Not Work Units)

After Work Unit 4 is accepted and merged into `codex/phase-13p-draft`, Phase 13P must
pass these independent gates in this order:

1. **Verification:** run the complete standalone test suite and
   `vendor/bin/phpstan analyse`, then record the evidence and any limitations.
2. **Documentation Sweep:** review every applicable path in Section 8 and record one of
   `updated`, `reviewed-no-change`, or `deferred-with-reason` for each path.
3. **Final Review vs latest `main`:** update the integration branch from the latest
   `main`, review the complete Phase diff and contracts, and confirm that no Work Unit
   or documentation scope was bypassed.

These gates are not Work Units, do not receive Work Unit PRs, and must not be merged or
marked complete independently of the Integration PR. Only after all three gates pass
may the Integration PR become Ready and proceed to merge.

## 7. Test Matrix

The tests must remain standalone PHP scripts following the repository convention. The
matrix below is the minimum required coverage:

| Area | Required coverage | Expected outcome |
| --- | --- | --- |
| Existing foundation | `null`, empty, non-array, associative node, numeric list, and empty list entries | Existing warnings remain compatible; new structural errors are deterministic |
| Generic structure | missing `@type`, malformed `@type`, short/`http`/`https` Schema.org type aliases, lists containing an allowed type, out-of-scope type, empty node, nested node, invalid collection shape | Only malformed/missing type contracts fail; a well-formed out-of-scope type produces no scope-only warning or info |
| Context | root context, nested builder-style omission, raw nested context | Validation is read-only and does not remove or inject context |
| Product | supported fields, `offers`, `isVariantOf`, `inProductGroupWithID`, invalid values | Product rules apply only to the declared catalog |
| Offer | price, currency, availability, URL/date/condition, seller | Offer rules and seller relationship shape are covered |
| AggregateOffer | low/high price, currency, offer count, availability, nested offers | `offers` accepts `Demand`, `Offer`, `AggregateOffer`, `OfferForLease`, and `OfferForPurchase`; Demand is not rejected for being out of deep scope |
| ProductGroup | group ID, `variesBy`, brand, `hasVariant` | Only Product nodes are accepted in `hasVariant` |
| Cross-type composition | Product→Offer, Product→AggregateOffer, Product→ProductGroup, Product→ProductModel, Product→Demand, Offer→Organization/Person, ProductGroup→Product | Valid canonical targets pass; only relationship-incompatible targets fail |
| Out-of-scope types | Article, Organization, WebSite, and at least one other existing builder output | No deep semantic validation or Google claim is produced |
| Extension properties | unknown/custom properties on an in-scope node | Not rejected solely for being outside the fixed Phase 13P catalog |
| Result pipeline | result DTO, score, report, batch, array/JSON/Markdown exporters | Existing DTO shapes and summaries remain compatible |
| Regression | all existing `tests/` scripts, including Phase 11 and Phase 13A–O | No unrelated regression |
| Static analysis | `vendor/bin/phpstan analyse` | No new static-analysis errors |

Exact rule-level cases, issue codes, and expected field paths must be added to the
Work Unit tests before the corresponding implementation is merged.

## 8. Documentation Impact

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

## 9. Definition of Done

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

## 10. Explicit Limitations and Deferred Work

Phase 13P intentionally does not provide:

* semantic validation for other JSON-LD schema types;
* Google Rich Results or Merchant eligibility validation;
* a guarantee of search visibility, indexing, ranking, or rich-result appearance;
* a complete closed-world Schema.org vocabulary validator;
* remote context resolution or third-party validation service integration.

These limitations must remain visible in the final Phase documentation and verification
report.
