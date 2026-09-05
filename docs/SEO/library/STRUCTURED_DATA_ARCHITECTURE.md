# Structured Data Architecture

This document explains the technical architecture, responsibilities, and practical implementation guidance for Structured Data (JSON-LD) within the Maatify SEO library. It reflects the modern capabilities established up to Phase 13O.

## 1. Core Architecture

The Maatify SEO library utilizes a series of framework-agnostic "Builders" implementing `JsonLdBuilderInterface`.
These builders internally manage associative arrays, applying specific logic for common Schema.org types (e.g., `Article`, `Product`, `Organization`).

**Key Architectural Principles:**
1.  **Immutability of Legacy Contracts:** The library strongly preserves backward compatibility. Legacy scalar setters on builders are maintained.
2.  **Output-Time Resolution:** Builders can be composed (nested inside each other). This composition is resolved *only* at the final output stage (`toArray()` or `toJson()`), ensuring the builder state remains completely mutable until rendering.
3.  **Non-Destructive Normalization:** When typed builders are resolved, the root builder retains its `@context` tag, while nested typed builders have their `@context` tags automatically stripped to prevent redundant output. However, if a developer passes a raw associative array instead of a builder, the library will preserve the raw array keys (including any explicit `@context` provided at that level). However, resolution is recursive: if that raw array itself contains nested `JsonLdBuilderInterface` objects, those internal builder objects will still be resolved and have their `@context` tags removed according to the `resolveNode()` contract.

## 2. Product Structured Data

`ProductJsonLdBuilder` is the central class for e-commerce data.

### Legacy vs. Explicit State
Historically, developers used scalar methods like `setPrice()` and `setCurrency()` directly on the `ProductJsonLdBuilder`. This internally maintained an implicit `Offer` array.

To support complex architectures (like `AggregateOffer` or multi-sellers), Phase 13O introduced an **Explicit Offers API**:
-   **`setOffers(array|JsonLdBuilderInterface ...$offers)`**
-   **`addOffer(array|JsonLdBuilderInterface $offer)`**

**The State Contract:**
-   `setOffers()` with no arguments is a no-op and does NOT trigger explicit state.
-   `setOffers([])` (an empty array) is a no-op and does NOT trigger explicit state.
-   A non-empty explicit input to `setOffers(...)` activates explicit state and replaces existing legacy offer data.
-   `addOffer(...)` always activates explicit state.
-   **Strict Boundary:** Once explicit state is activated, calling a legacy scalar offer helper like `setPrice()` will immediately throw a `JsonLdBuildException`. You cannot mix states.
-   **Reversal:** Calling `remove('offers')` deletes the explicit data and resets explicit state back to `false`.

## 3. Product Variants and ProductGroup

E-commerce often requires defining parent products and their variants. The library provides `ProductGroupJsonLdBuilder` and link properties in `ProductJsonLdBuilder`.

### The `ProductGroup`
A `ProductGroup` represents a collection of product variants. It initializes with `@type: ProductGroup`.
-   **`variesBy`:** Accepts an array of property URLs (e.g., `['https://schema.org/color', 'https://schema.org/size']`). This array is stored exactly as provided.
-   **`hasVariant`:** Accepts `ProductJsonLdBuilder` instances or raw arrays representing the child variants.

### Variant Relationships
A child `Product` can link back to its parent:
-   **`setIsVariantOf($productGroup)`:** Links the product to a group. If passed a string, it automatically casts it to `['@type' => 'ProductGroup', 'productGroupID' => $productGroup]`.

## 4. Offers and AggregateOffer

### Offer
`OfferJsonLdBuilder` creates standard offers. It supports typed composition for its properties, most notably `setSeller()`, which accepts an `OrganizationJsonLdBuilder` or a string (auto-cast to an `Organization` array).

### AggregateOffer
`AggregateOfferJsonLdBuilder` is used to represent a range of prices. It supports `setLowPrice()`, `setHighPrice()`, and `setOfferCount()`. It can also recursively accept specific `OfferJsonLdBuilder` instances via its own `setOffers()` and `addOffer()` methods.

## 5. Responsibilities and Validation Boundaries

It is crucial to understand what the library *does* and *does not* do.

### What the Library Does
-   Provides fluent, object-oriented APIs to construct associative arrays.
-   Manages the complex rules of nesting builders and stripping redundant `@context` tags.
-   Ensures type safety in builder arguments and handles list flattening (e.g., converting a variadic input into a standard list).
-   Outputs perfectly formatted JSON strings (via `JsonLdScriptRenderer`).

### What the Library Does NOT Do
-   **No Semantic Validation:** The library does not check if your GTIN is mathematically valid, if your URL is reachable, or if you missed a "required" Schema.org property.
-   **No Google Eligibility Guarantees:** Generating a `ProductGroup` using the library does not mean Google will grant your site Rich Results. You must still comply with Google's specific technical guidelines, content policies, and required property constraints.
-   **No HTTP/Framework Coupling:** The library does not touch `$_SERVER`, does not emit HTTP headers, and does not require a specific framework DI container.

## 6. Practical Implementation Guidance

**Good Patterns:**
-   **Use Explicit Builders for Complexity:** If your e-commerce platform has multiple sellers or price ranges, immediately jump to `setOffers()` with `OfferJsonLdBuilder` or `AggregateOfferJsonLdBuilder`. Do not try to hack the legacy scalar setters.
-   **Rely on Output-Time Resolution:** Construct your builders early in your request lifecycle. You can safely pass the `ProductJsonLdBuilder` instance around your application, adding data (like reviews or ratings) as it becomes available, and only call `toArray()` or `toJson()` in the final rendering view.
-   **Mix and Match:** If you have an existing hardcoded array for an Organization, you can pass that raw array directly into `setSeller()`. You don't have to rewrite everything into builders simultaneously.

**Bad Patterns:**
-   **Mixing States:** `->setOffers($offer)->setPrice('10')` will throw an exception.
-   **Database Queries inside Builders:** Builders are DTO-like structures. Do not pass PDO instances or ORM models directly into builder setters (unless you map them first). Map your database data to the builder setters in a dedicated service/factory class.
-   **Adding Framework Dependencies:** Do not add Laravel's `Config` or Symfony's `Request` directly into the builders.