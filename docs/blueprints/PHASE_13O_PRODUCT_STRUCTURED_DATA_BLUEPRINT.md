# Blueprint: Phase 13O — Advanced Product Structured Data

## 1. Current State Analysis

### 1.1 Existing Classes & Traits
*   **`JsonLdBuilderInterface` & `JsonLdBuilderTrait`**: Provide foundational array manipulation (`set`, `get`, `remove`, `toArray`, `toJson`). Currently, `set()` simply stores values and `toArray()` returns `$this->schema` without resolving nested builders.
*   **`ProductJsonLdBuilder`**: Supports core fields.
    *   *Limitation:* Pricing and availability are handled implicitly via `setOfferField()`, generating a single unstructured `Offer` array.
    *   *Gap:* Missing `GTIN`, `MPN`, variant properties (`color`, `size`, `material`, `pattern`), and relationship tracking (`isVariantOf`). Missing safe typed composition capabilities.
*   **`OfferJsonLdBuilder`**: Supports `price`, `priceCurrency`, `availability`, `validFrom`, `priceValidUntil`, and `seller`.
    *   *Limitation:* `setSeller` accepts only `string` or raw `array`. It requires an upgrade to support `JsonLdBuilderInterface`.

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
*   **Backward Compatibility (BC) Rule:** Destructive normalization (context stripping) MUST apply ONLY to nodes originating from `JsonLdBuilderInterface`. Arbitrary raw arrays provided by the user MUST NOT be destructively modified. This ensures that legacy code explicitly setting nested `@context` arrays remains unaffected.

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

**Argument Flattening:**
*   If a single argument is passed and it is a numeric list (e.g. `setOffers([$offer1, $offer2])`), the list MUST NOT be nested as `[[...]]`. It MUST be flattened to process the items individually.
*   If a single argument is passed and it is an associative array or a single builder, it is treated as a single node.

**Storage Shape Rules:**
*   **One item total:** Stored as an associative array/object (e.g., `"offers": { "@type": "Offer" }`).
*   **Multiple items total:** Stored as a numeric list of objects (e.g., `"offers": [ { "@type": "Offer" }, { "@type": "Offer" } ]`).

**Appending Lifecycle (`addOffer`, `addVariant`):**
*   If `offers` is `null`: stores the new node as an object.
*   If `offers` is an `object`: converts the property to a `list` containing the old object and the new node.
*   If `offers` is a `list`: pushes the new node to the list.

### 2.3 Product BC & Implicit State Machine
`ProductJsonLdBuilder` currently mixes scalar setters (`setPrice`) into a single implicit array. To prevent generating an invalid Schema.org state, we implement a strict internal flag:

*   **State Flag:** `private bool $hasExplicitOffers = false;`

**State Transitions:**
1.  **Initial State:** `$hasExplicitOffers = false`.
2.  **Explicit Typed Setters (`setOffers()`)**:
    *   Wipes out any existing `offers` data (replaces implicit state completely).
    *   Sets `$hasExplicitOffers = true`.
3.  **Explicit Appender (`addOffer()`)**:
    *   If `$hasExplicitOffers == false` (meaning `offers` contains legacy implicit data): Wipe the existing legacy implicit offer, store the new explicit offer, and set `$hasExplicitOffers = true`. (We do not merge structured objects with implicit scalar arrays).
    *   If `$hasExplicitOffers == true`: Append safely as defined in 2.2.
4.  **Generic Remover (`remove('offers')`)**:
    *   Wipes the data and resets `$hasExplicitOffers = false`.
5.  **Legacy Scalar Setters (`setPrice`, `setCurrency`, etc.)**:
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

// Casting: String becomes ['@type' => 'ProductGroup', 'productGroupID' => $productGroup]
public function setIsVariantOf(string|array|JsonLdBuilderInterface $productGroup): static
public function setInProductGroupWithID(string $productGroupID): static

public function setOffers(array|JsonLdBuilderInterface ...$offers): static
public function addOffer(array|JsonLdBuilderInterface $offer): static
```

### 3.2 ProductGroup Builder (`ProductGroupJsonLdBuilder.php`)
```php
// MUST explicitly initialize: ['@context' => 'https://schema.org', '@type' => 'ProductGroup']
public function __construct()
public function setName(string $name): static
public function setDescription(string $description): static

// Casting: String becomes ['@type' => 'Brand', 'name' => $brand]
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
*Note: Using `https://schema.org/*` property URLs in `variesBy` is a Google-oriented representation required for Rich Result technical processing, though base Schema.org permits string literals. This representation does not guarantee Rich Results on its own, as eligibility depends on broader site factors.*
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

1.  **Work Unit 1 (Fulfills Roadmap 13O-4 / Typed Composition Foundation):**
    *   Update `JsonLdBuilderTrait` with `resolveNode()`.
    *   Update `OfferJsonLdBuilder` to accept `JsonLdBuilderInterface` in `setSeller`.
    *   Test: `Phase13OCompositionTest.php`.
2.  **Work Unit 2 (Fulfills Roadmap 13O-1 / Product Completeness):**
    *   Add GTIN/MPN/variant descriptors to `ProductJsonLdBuilder`.
    *   Implement `setOffers` / `addOffer` and the `$hasExplicitOffers` state logic.
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

### 5.2 Deterministic Test Matrix
All edge cases below MUST be verified via PHP tests:

*   **Flattening:**
    *   `setOffers($offerBuilder)` -> single object.
    *   `setOffers(['@type' => 'Offer'])` -> single object.
    *   `setOffers([$offer1, $offer2])` -> numeric list.
    *   `setOffers(['@type' => 'Offer'], ['@type' => 'Offer'])` -> numeric list.
*   **Appending Lifecycle (`addOffer`, `addVariant`):**
    *   Null -> `addOffer($offer1)` -> single object.
    *   Object -> `addOffer($offer2)` -> numeric list (len: 2).
    *   List -> `addOffer($offer3)` -> numeric list (len: 3).
*   **Product State BC:**
    *   `setPrice(10)` -> `setOffers($offer)` -> outputs explicit `$offer` only.
    *   `setPrice(10)` -> `addOffer($offer)` -> outputs explicit `$offer` only.
    *   `setOffers($offer)` -> `setPrice(10)` -> THROWS `JsonLdBuildException`.
    *   `addOffer($offer)` -> `setPrice(10)` -> THROWS `JsonLdBuildException`.
    *   `setOffers($offer)` -> `remove('offers')` -> `setPrice(10)` -> succeeds.
*   **Context Stripping & Preservation:**
    *   Injecting `Product` builder into `ProductGroup` builder strips the nested `@context`.
    *   Injecting a RAW user array `['@context' => 'foo', '@type' => 'Offer']` preserves the nested `@context`.
    *   The root builder always preserves its initialized `@context`.
*   **Regression:** Existing unmodified tests utilizing raw array injections must pass without output alteration.