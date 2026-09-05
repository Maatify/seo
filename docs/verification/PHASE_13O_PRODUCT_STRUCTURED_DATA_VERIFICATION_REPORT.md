# Phase 13O: Product Structured Data Verification Report

## Scope

This report verifies the implemented Phase 13O product structured-data work through Work Units 1–4 on `codex/phase-13o-completion`.

The verified implementation includes:

- Typed JSON-LD composition and nested-node resolution from Work Unit 1.
- Product completeness and explicit-offer state handling from Work Unit 2.
- ProductGroup and product-variant relationships from Work Unit 3.
- AggregateOffer support from Work Unit 4.

This report does not claim semantic Schema.org validation, Google Rich Results eligibility, or external search-engine verification. Those concerns are outside the implemented builder contracts.

## Implementation Verification

### Typed composition foundation

`JsonLdBuilderTrait` resolves nested `JsonLdBuilderInterface` instances only during `toArray()`/`toJson()` output. The verified behavior is:

- The root builder keeps its own `@context`.
- A nested typed builder is converted through `toArray()`.
- The nested builder's `@context` is removed from the nested node.
- Typed builders nested inside numeric lists or raw arrays are resolved recursively.
- Raw arrays are not normalized by the foundation; raw-array values and raw `@context` entries remain preserved.
- `set()` retains the supplied value until output resolution.

### Product builder and legacy compatibility

`ProductJsonLdBuilder` now exposes GTIN, MPN, color, size, material, and pattern setters, plus `setOffers()` and `addOffer()`.

The explicit-offer state contract was verified as follows:

- Empty `setOffers()` calls and `setOffers([])` calls are no-ops.
- A single node is stored as an object; multiple nodes are stored as a numeric list.
- A single numeric-list argument is flattened.
- Explicit offers replace legacy implicit offer data.
- `addOffer()` replaces a legacy implicit offer when explicit state has not started, then follows object-to-list and list-append lifecycle rules.
- Legacy offer setters continue to build the existing implicit offer while explicit state is inactive.
- Legacy offer setters throw `JsonLdBuildException` after `setOffers()` or `addOffer()` starts explicit state.
- Generic `set('offers', ...)` behavior remains unchanged.
- `remove('offers')` removes the property and resets explicit-offer state.

### ProductGroup and variant relationships

`ProductGroupJsonLdBuilder` is initialized with `@context: https://schema.org` and `@type: ProductGroup`. Its verified API includes:

- `setName()`, `setDescription()`, `setUrl()`, and `setProductGroupID()`.
- `setBrand()` with string, raw-array, and typed-builder inputs.
- `setVariesBy()` with order-preserving array storage.
- `setHasVariant()` with empty-input no-ops, single-node object shape, flattened numeric lists, variadic nodes, and mixed raw/builder nodes.
- `addVariant()` with object, object-to-list, and list-append lifecycle behavior.

`ProductJsonLdBuilder::setIsVariantOf()` supports a product-group ID string, raw array, or typed builder. `setInProductGroupWithID()` stores the supplied relationship identifier.

### AggregateOffer

`AggregateOfferJsonLdBuilder` is initialized with `@context: https://schema.org` and `@type: AggregateOffer`. Its verified API includes:

- `setLowPrice()`, `setHighPrice()`, `setPriceCurrency()`, `setOfferCount()`, and `setAvailability()`.
- `setOffers()` with single-node, flattened-list, variadic, mixed, and empty-input behavior.
- `addOffer()` with object, object-to-list, list-append, and typed-builder composition behavior.
- Product composition through `ProductJsonLdBuilder::setOffers()`.

## Test Evidence

The following focused tests cover the Phase 13O contracts:

- `tests/Phase13AJsonLdBuilderFoundationTest.php`
- `tests/Phase13BProductJsonLdBuilderTest.php`
- `tests/Phase13OCompositionTest.php`
- `tests/Phase13OProductGroupJsonLdBuilderTest.php`
- `tests/Phase13OAggregateOfferJsonLdBuilderTest.php`

The focused tests verify the exact ProductGroup multi-variant and Product-to-ProductGroup relationship scenarios from the Phase 13O blueprint, as well as the Product + AggregateOffer scenario and nested-offer composition.

The complete standalone test suite was executed with:

```bash
find tests -maxdepth 1 -name '*.php' -type f -print0 | sort -z | xargs -0 -n1 php
```

Result: all available test scripts passed with zero failures.

Static analysis was executed with:

```bash
vendor/bin/phpstan analyse
```

Result: `[OK] No errors`.

## Compatibility and Boundaries

- Existing Product legacy offer output remains covered by the Phase 13B regression tests.
- Nested typed-builder `@context` stripping is limited to typed builder nodes; raw-array contexts remain caller-controlled.
- Collection setters do not introduce empty `offers` or `hasVariant` values when called with no arguments or a single empty list.
- Builders are framework-neutral and produce arrays or JSON strings; they do not perform database access, HTTP rendering, semantic validation, or Google eligibility checks.
- Work Units 1–4 do not add variant semantic validation or external Schema.org verification.
