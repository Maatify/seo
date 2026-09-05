# Blueprint: Phase 13O — Advanced Product Structured Data

## 1. Current State Analysis

### 1.1 Existing Classes & Traits
*   **`JsonLdBuilderInterface` & `JsonLdBuilderTrait`**: Provide foundational array manipulation (`set`, `get`, `remove`, `toArray`, `toJson`). Currently lacking native support for typed builder composition (builders cannot cleanly absorb other builders without manual array conversion and `@context` stripping).
*   **`ProductJsonLdBuilder`**: Supports core fields (`name`, `description`, `sku`, `brand`, `image`, etc.).
    *   *Limitation:* Pricing and availability are handled implicitly via `setOfferField()`, which forces the creation of a single unstructured `Offer` array.
    *   *Gap:* Missing `GTIN`, `MPN`, and product variant properties (`color`, `size`, `material`, `pattern`). Lacks explicit relational properties (`isVariantOf`). Missing a clean way to inject typed `OfferJsonLdBuilder` or `AggregateOfferJsonLdBuilder`.
*   **`OfferJsonLdBuilder`**: Supports basic properties (`price`, `priceCurrency`, `availability`, `validFrom`, `priceValidUntil`, `seller`).
    *   *Limitation:* `setSeller` accepts only `string` or raw `array`, missing `OrganizationJsonLdBuilder` typed support.

### 1.2 The Gaps
1.  **Product Fields**: Missing Global Trade Item Numbers (GTIN/MPN) and physical variant descriptors.
2.  **ProductGroup**: Missing `ProductGroupJsonLdBuilder` entirely (needed for parent-child variant structures).
3.  **AggregateOffer**: Missing `AggregateOfferJsonLdBuilder` (needed for price ranges and collections of offers).
4.  **Composition**: Forcing developers to pass raw arrays instead of strongly typed builder objects for nested structures (e.g., `Product` -> `offers` -> `AggregateOffer` -> `offers` -> `Offer`).

---

## 2. Architectural Decisions

1.  **Composition Strategy via Builder Contracts**:
    We will modify the core `JsonLdBuilderTrait` to seamlessly handle instances of `JsonLdBuilderInterface`. A new helper `resolveNode()` will be introduced. When a builder is passed as a value, `resolveNode()` will call `toArray()` and cleanly remove the nested `@context` property to prevent invalid Schema.org nesting.
2.  **Backward Compatibility (BC)**:
    *   The legacy `setOfferField` and methods relying on it (`setPrice`, `setCurrency`, etc.) inside `ProductJsonLdBuilder` will remain intact.
    *   New composition methods like `setOffers()` will simply overwrite the `offers` key. If a user mixes legacy scalar price methods and the new `setOffers()`, the explicitly set array/builder takes precedence.
3.  **Framework-Agnostic Paradigm**:
    Everything remains strictly standard PHP. No external packages, no framework DI, and serialization uses native `json_encode` with strict security flags.

---

## 3. Public API Proposals

### 3.1 Core Trait Updates (`JsonLdBuilderTrait.php`)
```php
/**
 * Recursively resolves JsonLdBuilderInterface instances into arrays,
 * stripping nested '@context' keys.
 */
protected function resolveNode(mixed $node): mixed
```

### 3.2 Product Builder Enhancements (`ProductJsonLdBuilder.php`)
**New Methods:**
```php
public function setGtin(string $gtin): static
public function setMpn(string $mpn): static
public function setColor(string $color): static
public function setSize(string $size): static
public function setMaterial(string $material): static
public function setPattern(string $pattern): static

// Relationships
public function setIsVariantOf(string|array|JsonLdBuilderInterface $productGroup): static
public function setInProductGroupWithID(string $productGroupID): static

// Typed Composition (Overwrites implicit offer array if used)
public function setOffers(array|JsonLdBuilderInterface ...$offers): static
public function addOffer(array|JsonLdBuilderInterface $offer): static
```

### 3.3 New: AggregateOffer Builder (`AggregateOfferJsonLdBuilder.php`)
**Design:** Extends `AbstractJsonLdBuilder`, initializes `@type` to `AggregateOffer`.
```php
public function setLowPrice(int|float|string $price): static
public function setHighPrice(int|float|string $price): static
public function setPriceCurrency(string $currency): static
public function setOfferCount(int $count): static
public function setOffers(array|JsonLdBuilderInterface ...$offers): static
public function addOffer(array|JsonLdBuilderInterface $offer): static
```

### 3.4 New: ProductGroup Builder (`ProductGroupJsonLdBuilder.php`)
**Design:** Extends `AbstractJsonLdBuilder`, initializes `@type` to `ProductGroup`.
```php
// Standard product properties mirrored (name, description, brand, etc.)
public function setName(string $name): static
public function setDescription(string $description): static
public function setBrand(string|array|JsonLdBuilderInterface $brand): static

// Group-specific properties
public function setProductGroupID(string $id): static
public function setVariesBy(array $properties): static // e.g. ['color', 'size']
public function setHasVariant(array|JsonLdBuilderInterface ...$variants): static
public function addVariant(array|JsonLdBuilderInterface $variant): static
```

### 3.5 Offer Builder Updates (`OfferJsonLdBuilder.php`)
```php
// Update signature to support OrganizationJsonLdBuilder composition
public function setSeller(string|array|JsonLdBuilderInterface $seller): static
```

---

## 4. JSON-LD Examples (Composition Scenarios)

### Scenario A: Product with a Typed Offer
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Super Widget",
  "offers": {
    "@type": "Offer",
    "price": "19.99",
    "priceCurrency": "USD",
    "priceValidUntil": "2024-12-31"
  }
}
```

### Scenario B: Product with AggregateOffer
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Widget Collection",
  "offers": {
    "@type": "AggregateOffer",
    "lowPrice": "10.00",
    "highPrice": "50.00",
    "priceCurrency": "USD",
    "offerCount": 5
  }
}
```

### Scenario C: ProductGroup containing Product Variants
```json
{
  "@context": "https://schema.org",
  "@type": "ProductGroup",
  "name": "T-Shirt Line",
  "productGroupID": "TSHIRT-BASE",
  "variesBy": ["color", "size"],
  "hasVariant": [
    {
      "@type": "Product",
      "sku": "TSHIRT-RED-L",
      "color": "Red",
      "size": "L"
    },
    {
      "@type": "Product",
      "sku": "TSHIRT-BLU-M",
      "color": "Blue",
      "size": "M"
    }
  ]
}
```

### Scenario D: Product Variant linked to ProductGroup (Inverse Relation)
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "sku": "TSHIRT-RED-L",
  "color": "Red",
  "isVariantOf": {
    "@type": "ProductGroup",
    "productGroupID": "TSHIRT-BASE"
  }
}
```

---

## 5. Validation Semantic Preparedness (Phase 13P Considerations)
The design avoids runtime validation inside builders to keep them fast and pure. The outputs of `toArray()` will serve as the exact `array` contracts that Phase 13P (Semantic Validation) will ingest.
*   By preserving exactly formatted property names (e.g., `priceValidUntil`, `productGroupID`) and strict Schema.org `@type` declarations, future validators can reliably traverse the AST.
*   Missing data will simply mean missing keys in the array (no `null` keys output), which the validator will flag against Google's documentation.

---

## 6. Testing Strategy

1.  **Backward Compatibility Tests**: Validate that `(new ProductJsonLdBuilder())->setPrice(10)->setCurrency('USD')` yields the exact legacy JSON.
2.  **Unit Tests**: Comprehensive standalone PHP scripts (`tests/Web/JsonLd/Builder/ProductGroupJsonLdBuilderTest.php` etc.) covering new builder methods.
3.  **Composition Tests**: Validate that nested `@context` properties are accurately stripped without affecting root context.
4.  **Edge Cases**: Passing mixed data types (arrays, builders) into variadic methods like `setOffers()`.

---

## 7. Recommended Execution Plan / PR Splitting

*   **PR 1: Foundation & Composition Tools**
    *   Update `JsonLdBuilderTrait` with `resolveNode`.
    *   Update `OfferJsonLdBuilder` to accept typed builders.
    *   Add tests for basic trait composition.
*   **PR 2: AggregateOffer Implementation**
    *   Create `AggregateOfferJsonLdBuilder`.
    *   Include tests ensuring proper output structure.
*   **PR 3: Product Builder Completeness**
    *   Add GTIN, MPN, and variant properties to `ProductJsonLdBuilder`.
    *   Implement `setOffers` and `addOffer` composition methods.
    *   Include BC tests.
*   **PR 4: ProductGroup & Variants**
    *   Create `ProductGroupJsonLdBuilder`.
    *   Implement variant relationship methods.
    *   Add complex composition tests (ProductGroup -> Variants).
*   **PR 5: Final Documentation & Verification**
    *   Sync `SEO_LIBRARY_REFERENCE.md`.
    *   Add a verification report to `docs/verification/`.