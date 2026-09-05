# Phase 13O: Advanced Product Structured Data

## 1. Overview and Target

The goal of Phase 13O was to extend the Maatify SEO library's JSON-LD builders to fully support complex e-commerce structured data architectures, specifically typed composition for Product, Offer, AggregateOffer, and ProductGroup.

This phase introduced robust tools for defining explicit product structures and relationships while maintaining strict backward compatibility with existing implicit builders and avoiding framework-coupling or HTTP-specific layers.

### State Before Phase 13O
Prior to Phase 13O, the `ProductJsonLdBuilder` supported single products with implicit "scalar" offer properties (like `$product->setPrice('10.00')->setCurrency('USD')`). Internally, these helpers built an associative array representing an `Offer`. However, it lacked the ability to safely compose multiple offers, typed `AggregateOffer`, nested `Offer` typed builders, or represent complex variant models using `ProductGroup`. The JSON-LD foundation did not natively support safely nesting `JsonLdBuilderInterface` objects and stripping inner `@context` tags on render.

## 2. Technical Implementation

### Typed Nested Composition Foundation
A core enabler of Phase 13O was the enhancement of `JsonLdBuilderTrait`. It now resolves nested nodes at render time via `toArray()` and `toJson()`.

- **Nested `@context` behavior:** When a `JsonLdBuilderInterface` is injected into another builder, the root builder retains its own `@context`. However, the nested builder's `@context` is safely stripped during resolution to prevent invalid, redundant context tags in the final output.
- **Raw Arrays vs. Builders:** Typed composition applies non-destructive normalization. Typed builder nodes (and arrays of them) are recursively resolved. If an arbitrary raw array is provided, its keys (including any raw `@context`) are preserved. However, resolution is recursive: if the raw array contains nested `JsonLdBuilderInterface` nodes, those nested builders will still be resolved and have their inner `@context` stripped according to the `resolveNode` contract.

### Product & Offer Composition

`ProductJsonLdBuilder` received new standard setters (e.g., `setGtin`, `setMpn`, `setColor`, `setSize`, `setMaterial`, `setPattern`). MPN and GTIN are treated as distinct properties.

#### Explicit Offers Behavior and Lifecycle
The core paradigm shift in `ProductJsonLdBuilder` is the introduction of **Explicit Offers State** via `setOffers()` and `addOffer()`.

- **`setOffers(array|JsonLdBuilderInterface ...$offers)`:** Allows injecting typed offers, a mix of typed builders and raw arrays, or flattening a numeric list.
- **`addOffer(array|JsonLdBuilderInterface $offer)`:** Appends a single offer to the list.
- **Lifecycle:**
  - `setOffers()` with no arguments or an empty array `[]` is a no-op and does NOT trigger explicit state.
  - A non-empty explicit input to `setOffers(...)` or using `addOffer(...)` places the builder into "explicit state" (`$hasExplicitOffers = true`).
  - Single nodes are stored as objects; multiple nodes become numeric lists. Lists are flattened.
  - Activating explicit state replaces any previously generated legacy implicit offer data.

#### Backward Compatibility with Legacy Product Helpers
Legacy scalar helpers (e.g., `setPrice()`, `setCurrency()`, `setAvailability()`) are fully maintained for backward compatibility.
- If called *before* explicit state, they build the legacy implicit offer array normally.
- If called *after* explicit state has been activated (e.g., calling `setPrice()` after `setOffers()`), they strictly throw a `JsonLdBuildException` to prevent accidental mixed states and data corruption.
- The generic setter `set('offers', ...)` was intentionally NOT overridden to trigger explicit state, thereby preserving generic legacy behavior.

#### Generic Override (`remove('offers')`)
`ProductJsonLdBuilder` overrides `remove(string $key)`. If `$key === 'offers'`, it calls the parent remove method AND explicitly resets the `$hasExplicitOffers` flag to `false`, allowing developers to safely revert to the legacy helper state if needed.

#### Seller Composition in Offer
`OfferJsonLdBuilder` was updated so `setSeller()` now accepts a `JsonLdBuilderInterface` (like `OrganizationJsonLdBuilder`), string (auto-cast to an `Organization` array), or raw array, enabling deep compositional trees.

### ProductGroup and Product Variants

A new `ProductGroupJsonLdBuilder` handles parent products with multiple variants.

- **Initialization:** Explicitly sets `@context` to `https://schema.org` and `@type` to `ProductGroup`.
- **`productGroupID`:** Defined via `setProductGroupID(string $id)`.
- **`variesBy`:** Defined via `setVariesBy(array $properties)`. It stores the provided properties exactly as provided, preserving order.
- **Variant Relationships (`hasVariant`):** Defined via `setHasVariant()` (accepts single, list, variadic, or mixed builders/arrays) and `addVariant()`.
- **Child-to-Parent Relationships:** `ProductJsonLdBuilder` supports `setIsVariantOf(string|array|JsonLdBuilderInterface)` (strings auto-cast to `ProductGroup` array) and `setInProductGroupWithID(string)`.
- **Variant Properties:** Set directly on the child `ProductJsonLdBuilder` using the new `setColor`, `setSize`, etc.

### AggregateOffer

A new `AggregateOfferJsonLdBuilder` supports price ranges.
- **Initialization:** Explicitly sets `@context` and `@type` to `AggregateOffer`.
- **Properties:** Setters for `setLowPrice()`, `setHighPrice()`, `setPriceCurrency()`, `setOfferCount()`, and `setAvailability()`.
- **Nested Offers:** Supports `setOffers()` and `addOffer()` with the same list-flattening and composition rules as the Product builder, allowing detailed representations of individual offers beneath an `AggregateOffer`.

## 3. Migration Notes

For developers upgrading to Phase 13O:
- Existing uses of `ProductJsonLdBuilder` scalar methods (`setPrice()`, `setCurrency()`) remain perfectly valid and unchanged.
- You can mix raw associative arrays with explicit `OfferJsonLdBuilder` instances in `setOffers()` safely.
- If you were manually overriding the `offers` array using `set('offers', ...)` and manually managing the legacy scalar properties, this behavior is untouched. However, utilizing `setOffers()` is now the recommended approach for custom data.
- **Warning:** Do not mix `setOffers()` / `addOffer()` with legacy scalar methods like `setPrice()`. Doing so will now throw a `JsonLdBuildException`.

## 4. Limitations and Boundaries

- **No Semantic Validation:** The builders structure the data into valid JSON. They **do not** validate the semantic correctness against Schema.org rules (e.g., they will not warn you if a GTIN format is invalid).
- **No Google Rich Results Guarantee:** While properties like `variesBy` align with Google's technical documentation, using these builders does not guarantee eligibility for Google Rich Results. Eligibility relies on satisfying Google's own data guidelines, content policies, and algorithmic factors.
- **Pure Output:** Builders do not interact with databases, perform HTTP requests, or emit HTTP headers.

## 5. References

- **Blueprint:** [PHASE_13O_PRODUCT_STRUCTURED_DATA_BLUEPRINT.md](../blueprints/PHASE_13O_PRODUCT_STRUCTURED_DATA_BLUEPRINT.md)
- **Verification Report:** [PHASE_13O_PRODUCT_STRUCTURED_DATA_VERIFICATION_REPORT.md](../verification/PHASE_13O_PRODUCT_STRUCTURED_DATA_VERIFICATION_REPORT.md)
- **API Reference:** [SEO_LIBRARY_REFERENCE.md](../SEO_LIBRARY_REFERENCE.md)
- **Usage Guide:** [USAGE_GUIDE.md](../guides/USAGE_GUIDE.md)
- **Examples:** `examples/phase13o-product-advanced.php` (Demonstrating composed scenarios).
