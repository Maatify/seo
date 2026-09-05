# Blueprint: Phase 13O — Advanced Product Structured Data

## 1. Current State Analysis

### 1.1 Existing Classes & Traits
*   **`JsonLdBuilderInterface` & `JsonLdBuilderTrait`**: Provide foundational array manipulation (`set`, `get`, `remove`, `toArray`, `toJson`). Currently, `set()` simply stores values and `toArray()` returns `$this->schema` without resolving nested builders.
*   **`ProductJsonLdBuilder`**: Supports core fields.
    *   *Limitation:* Pricing and availability are handled implicitly via `setOfferField()`, generating a single unstructured `Offer` array.
    *   *Gap:* Missing `GTIN`, `MPN`, variant properties (`color`, `size`, `material`, `pattern`), and relationship tracking (`isVariantOf`). Missing safe typed composition capabilities.
*   **`OfferJsonLdBuilder`**: Contains `price`, `priceCurrency`, `availability`, `validFrom`, `priceValidUntil`, and `seller(string|array)`.
    *   *Limitation:* `setSeller` requires an upgrade to support `JsonLdBuilderInterface` without breaking existing `string|array` usage.

### 1.2 The Gaps
1.  **Product Fields**: Missing Global Trade Item Numbers (GTIN/MPN) and physical variant descriptors.
2.  **ProductGroup**: Missing `ProductGroupJsonLdBuilder` entirely.
3.  **AggregateOffer**: Missing `AggregateOfferJsonLdBuilder`.
4.  **Composition Foundation**: Typed builders cannot cleanly absorb other typed builders without destructive manual array manipulation.

---

## 2. Architectural Decisions & Contracts

### 2.1 Typed Composition Contract (`resolveNode`)
To safely embed builders inside other builders without breaking existing implementations, the core `JsonLdBuilderTrait` will be updated.

*   **Signature:** `protected function resolveNode(mixed $node): mixed`
*   **Location:** Invoked *exclusively* inside `toArray()`. This ensures builders retain their object references until final rendering.
*   **Backward Compatibility (BC) Rule:** Destructive normalization (context stripping) MUST apply ONLY to nodes originating from `JsonLdBuilderInterface`. Arbitrary raw arrays provided by the user MUST NOT be destructively modified.

**`toArray()` Algorithm:**
1.  Create a shallow copy of `$this->schema` to serve as the root.
2.  The root `@context` remains intact (if initialized by the builder).
3.  Iterate over every value in the root schema and pass it to `resolveNode()`.

**`resolveNode($node)` Algorithm (Pseudocode):**
```php
if ($node is JsonLdBuilderInterface) {
    $array = $node->toArray();
    unset($array['@context']); // Strip ONLY from injected builders
    return map_recursive($array, resolveNode);
} elseif (is_array($node)) {
    // Preserve raw user arrays exactly as provided, but recurse
    // to catch any builders deeply nested within user arrays.
    return map_recursive($node, resolveNode);
}
return $node;
```

### 2.2 Collection Composition Semantics (`setOffers`, `setHasVariant`)
The behavior for variadic collection setters (`array|JsonLdBuilderInterface ...$nodes`) MUST follow a deterministic state machine:

**Argument Flattening & Empty Checks:**
*   If exactly zero arguments are passed (e.g. `setOffers()`), it MUST NOT alter the schema. It returns early.
*   If exactly one argument is passed and it is an empty array `[]`, it MUST NOT alter the schema. It returns early.
*   If a single argument is passed and that array is a numeric list (e.g. `setOffers([$offer1, $offer2])`), the list MUST NOT be nested as `[[...]]`. It MUST be flattened to process the items individually.
*   If a single argument is passed and it is an associative array or a single builder, it is treated as a single node.

**Storage Shape Rules:**
*   **One item total:** Stored as an associative array/object (e.g., `"offers": { "@type": "Offer" }`).
*   **Multiple items total:** Stored as a numeric list of objects (e.g., `"offers": [ { "@type": "Offer" }, { "@type": "Offer" } ]`).

**Appending Lifecycle (`addOffer`, `addVariant`):**
*   If `offers` is `null`: stores the new node as an object.
*   If `offers` is an `object`: converts the property to a `list` containing the old object and the new node.
*   If `offers` is a `list`: pushes the new node to the list.

### 2.3 Product BC & Implicit State Machine
`ProductJsonLdBuilder` currently mixes scalar setters (`setPrice`) into a single implicit array. To prevent generating an invalid Schema.org state, we implement a strict internal flag.

*   **State Flag:** `private bool $hasExplicitOffers = false;`

**State Transitions:**
1.  **Initial State:** `$hasExplicitOffers = false`.
2.  **Explicit Typed Setters (`setOffers()`)**:
    *   Wipes out any existing `offers` data (replaces implicit state completely).
    *   Sets `$hasExplicitOffers = true`.
3.  **Explicit Appender (`addOffer()`)**:
    *   If `$hasExplicitOffers == false`: Wipe the existing legacy implicit offer, store the new explicit offer, and set `$hasExplicitOffers = true`. (We do not merge structured objects with implicit scalar arrays).
    *   If `$hasExplicitOffers == true`: Append safely as defined in 2.2.
4.  **Generic Override (`remove()`)**:
    *   `ProductJsonLdBuilder` MUST override `remove(string $key): static`.
    *   If `$key === 'offers'`, it calls `parent::remove($key)` AND sets `$hasExplicitOffers = false`.
    *   If `$key !== 'offers'`, it simply calls `parent::remove($key)`.
5.  **Generic Override (`set()`)**:
    *   `ProductJsonLdBuilder` MUST override `set(string $key, mixed $value): static`.
    *   If `$key === 'offers'`, it calls `parent::set($key, $value)` AND sets `$hasExplicitOffers = true` (treating generic manual overrides as explicit state, permanently locking out legacy scalar setters).
    *   If `$key !== 'offers'`, it calls `parent::set($key, $value)`.
6.  **Legacy Scalar Setters (`setPrice`, `setCurrency`, etc.)**:
    *   Checks `$hasExplicitOffers`.
    *   If `true`: MUST throw `JsonLdBuildException` immediately.
    *   If `false`: Proceed with legacy `setOfferField()` behavior.

---

## 3. Public API Proposals

### 3.1 Product Builder Enhancements (`ProductJsonLdBuilder.php`)
```php
public function setGtin(string $gtin): static
public function setMpn(string $mpn): static
public function setColor(string $color): static
public function setSize(string $size): static
public function setMaterial(string $material): static
public function setPattern(string $pattern): static

// String cast rule: string becomes ['@type' => 'ProductGroup', 'productGroupID' => $productGroup]
// Array cast rule: raw arrays are stored EXACTLY as passed. No automatic '@type' injection.
public function setIsVariantOf(string|array|JsonLdBuilderInterface $productGroup): static
public function setInProductGroupWithID(string $productGroupID): static

public function setOffers(array|JsonLdBuilderInterface ...$offers): static
public function addOffer(array|JsonLdBuilderInterface $offer): static
```
*Design Note on Offer Roadmap:* `Product` will NOT receive convenience methods for `seller` or `priceValidUntil`. Developers must use `OfferJsonLdBuilder` combined with `setOffers()` to fulfill richer integration requirements.

### 3.2 ProductGroup Builder (`ProductGroupJsonLdBuilder.php`)
```php
// MUST explicitly initialize: ['@context' => 'https://schema.org', '@type' => 'ProductGroup']
public function __construct()
public function setName(string $name): static
public function setDescription(string $description): static

// String cast rule: string becomes ['@type' => 'Brand', 'name' => $brand]
// Array cast rule: raw arrays are stored EXACTLY as passed. No automatic '@type' injection.
public function setBrand(string|array|JsonLdBuilderInterface $brand): static
public function setUrl(string $url): static
public function setProductGroupID(string $id): static

// Shape: accepts an array of strings (e.g. ['color', 'size']), stored exactly as provided.
public function setVariesBy(array $properties): static
public function setHasVariant(array|JsonLdBuilderInterface ...$variants): static
public function addVariant(array|JsonLdBuilderInterface $variant): static
```

### 3.3 AggregateOffer Builder (`AggregateOfferJsonLdBuilder.php`)
```php
// MUST explicitly initialize: ['@context' => 'https://schema.org', '@type' => 'AggregateOffer']
public function __construct()
public function setLowPrice(int|float|string $price): static
public function setHighPrice(int|float|string $price): static
public function setPriceCurrency(string $currency): static
public function setOfferCount(int $count): static
public function setOffers(array|JsonLdBuilderInterface ...$offers): static
public function addOffer(array|JsonLdBuilderInterface $offer): static
public function setAvailability(string $availability): static
```

### 3.4 Offer Builder Updates (`OfferJsonLdBuilder.php`)
```php
// Existing String BC: string becomes ['@type' => 'Organization', 'name' => $seller]
// Existing Array BC: raw arrays lacking '@type' receive '@type' => 'Organization'. Arrays WITH '@type' are preserved.
// New Builder Behavior: builders are converted via resolveNode.
public function setSeller(string|array|JsonLdBuilderInterface $seller): static
```

---

## 4. JSON-LD Example Scenarios

### Scenario 1: Product with a Typed Offer
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Widget",
  "offers": {
    "@type": "Offer",
    "price": "19.99",
    "priceCurrency": "USD"
  }
}
```

### Scenario 2: Product with AggregateOffer
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Widget Collection",
  "offers": {
    "@type": "AggregateOffer",
    "lowPrice": "10.00",
    "highPrice": "50.00",
    "priceCurrency": "USD"
  }
}
```

### Scenario 3: ProductGroup containing Product Variants
*Note: Using full Schema.org URLs in `variesBy` is a technical recommendation for Google-oriented markup. However, utilizing this representation does not guarantee Rich Results on its own; Google eligibility depends on satisfying all required data guidelines and algorithmic factors.*
```json
{
  "@context": "https://schema.org",
  "@type": "ProductGroup",
  "name": "T-Shirt Line",
  "productGroupID": "TSHIRT-BASE",
  "variesBy": [
    "https://schema.org/color",
    "https://schema.org/size"
  ],
  "hasVariant": [
    {
      "@type": "Product",
      "sku": "TS-RED-L",
      "color": "Red",
      "size": "L"
    },
    {
      "@type": "Product",
      "sku": "TS-BLU-M",
      "color": "Blue",
      "size": "M"
    }
  ]
}
```

### Scenario 4: Product Variant linked to ProductGroup
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "sku": "TS-RED-L",
  "color": "Red",
  "isVariantOf": {
    "@type": "ProductGroup",
    "productGroupID": "TSHIRT-BASE"
  }
}
```

---

## 5. Execution Plan & Test Matrix

### 5.1 Technical Implementation Mapping
To ensure all PRs are independently stable and verifiable without introducing broken APIs, the foundation (`resolveNode`) MUST be built first. We technically map the roadmap requirements as follows:

1.  **Work Unit 1 (Implements the foundation required by Roadmap 13O-4):**
    *   Update `JsonLdBuilderTrait` with `resolveNode()`.
    *   Update `OfferJsonLdBuilder` to accept `JsonLdBuilderInterface` in `setSeller`.
    *   Test: `Phase13OCompositionTest.php`.
2.  **Work Unit 2 (Fulfills Roadmap 13O-1 / Product Completeness):**
    *   Add GTIN/MPN/variant descriptors to `ProductJsonLdBuilder`.
    *   Implement `setOffers` / `addOffer` and the generic `set()`/`remove()` explicit state flag overrides.
    *   Test: Append to `Phase13BProductJsonLdBuilderTest.php`.
3.  **Work Unit 3 (Fulfills Roadmap 13O-2 / ProductGroup):**
    *   Create `ProductGroupJsonLdBuilder`.
    *   Implement `isVariantOf` in Product.
    *   Test: `Phase13OProductGroupJsonLdBuilderTest.php`.
4.  **Work Unit 4 (Fulfills Roadmap 13O-3 / AggregateOffer):**
    *   Create `AggregateOfferJsonLdBuilder`.
    *   Test: `Phase13OAggregateOfferJsonLdBuilderTest.php`.
5.  **Work Unit 5 (Fulfills Roadmap 13O-5 / Documentation):**
    *   Sync `SEO_LIBRARY_REFERENCE.md`.
    *   Add `PHASE_13O_PRODUCT_STRUCTURED_DATA_VERIFICATION_REPORT.md`.

*(Note: Roadmap 13O-4 "Typed Composition" is functionally complete across Work Units 1, 2, 3, and 4).*

### 5.2 Deterministic Test Matrix
All edge cases below MUST be verified via PHP tests:

1.  `setOffers($builder)` -> single builder -> single object.
2.  `setOffers(['@type' => 'Offer'])` -> single raw associative array -> single object.
3.  `setOffers([$builder1, $builder2])` -> numeric list argument -> numeric list.
4.  `setOffers([['@type' => 'Offer'], ['@type' => 'Offer']])` -> numeric list raw arrays -> numeric list.
5.  `setOffers($builder1, $builder2)` -> variadic builders -> numeric list.
6.  `setOffers($builder1, ['@type' => 'Offer'])` -> mix builder/raw -> numeric list.
7.  `setOffers()` -> empty variadic input -> early exit (no changes).
8.  `setOffers([])` -> empty numeric list -> early exit (no changes).
9.  `addOffer`: null -> object.
10. `addOffer`: object -> list (len: 2).
11. `addOffer`: list -> list appended (len: 3).
12. `addVariant`: null -> object.
13. `addVariant`: object -> list (len: 2).
14. `addVariant`: list -> list appended (len: 3).
15. `setPrice(10)` -> `setOffers($offer)` -> outputs explicit `$offer` only.
16. `setPrice(10)` -> `addOffer($offer)` -> outputs explicit `$offer` only.
17. `setOffers($offer)` -> `setPrice(10)` -> THROWS `JsonLdBuildException`.
18. `addOffer($offer)` -> `setPrice(10)` -> THROWS `JsonLdBuildException`.
19. `setOffers($offer)` -> `remove('offers')` -> `setPrice(10)` -> succeeds.
20. `set('offers', ['@type' => 'Offer'])` -> `setPrice(10)` -> THROWS `JsonLdBuildException`.
21. Product + AggregateOffer -> AggregateOffer outputs without interference.
22. AggregateOffer + nested Offer list -> AggregateOffer properties retain stability.
23. Nested builder `@context` stripping -> Builder contexts removed correctly.
24. Raw array nested `@context` preservation -> Explicit nested raw contexts are retained.
25. Root `@context` preservation -> Main builder context is retained.
26. Existing Product output regression -> All scalar tests pass unchanged.
27. Existing Offer `setSeller` regression -> string/array tests pass unchanged.
28. Exact JSON-LD output tests matching the 4 scenarios in Section 4.