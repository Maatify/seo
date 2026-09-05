# Blueprint: Phase 13O — Advanced Product Structured Data

## 1. Current State Analysis

### 1.1 Existing Classes & Traits
*   **`JsonLdBuilderInterface` & `JsonLdBuilderTrait`**: Provide foundational array manipulation (`set`, `get`, `remove`, `toArray`, `toJson`).
*   **`ProductJsonLdBuilder`**: Supports core fields (`name`, `description`, `sku`, `brand`, `image`, etc.).
    *   *Limitation:* Pricing and availability are handled implicitly via `setOfferField()`, which forces the creation of a single unstructured `Offer` array.
    *   *Gap:* Missing `GTIN`, `MPN`, and product variant properties (`color`, `size`, `material`, `pattern`). Lacks explicit relational properties (`isVariantOf`). Missing typed composition for `Offer` or `AggregateOffer`.
*   **`OfferJsonLdBuilder`**: Supports basic properties.
    *   *Limitation:* `setSeller` accepts only `string` or raw `array`, missing `OrganizationJsonLdBuilder` typed support.

### 1.2 The Gaps
1.  **Product Fields**: Missing Global Trade Item Numbers (GTIN/MPN) and physical variant descriptors.
2.  **ProductGroup**: Missing `ProductGroupJsonLdBuilder` entirely.
3.  **AggregateOffer**: Missing `AggregateOfferJsonLdBuilder`.
4.  **Composition**: Forcing raw arrays instead of strongly typed builder objects for nested structures.

---

## 2. Architectural Decisions & Contracts

### 2.1 Typed Composition Contract (`resolveNode`)
*   **Location:** The normalization must occur exclusively inside `toArray()` within `JsonLdBuilderTrait`.
*   **Why:** Normalizing inside `set()` destroys the builder reference too early, preventing later modifications to the injected builder before final render. Normalizing at `toArray()` ensures all references are fully built.
*   **Rules for `resolveNode(mixed $node)`:**
    *   If `$node` is a `JsonLdBuilderInterface`, call its `toArray()`.
    *   Once the builder is converted to an array, the helper must recursively strip the `@context` key from the resulting array (nested nodes must not redefine the context).
    *   If `$node` is an associative array with an explicit `@context`, it must also be stripped.
    *   If `$node` is a numeric array (a list), `resolveNode` must iterate over the items and apply the same stripping logic.
    *   The root builder retains its `@context` natively because its `toArray()` simply returns `$this->schema` before any node is passed to `resolveNode`.

### 2.2 Collection Composition Semantics
When using `setOffers()`, `addOffer()`, `setHasVariant()`, or `addVariant()`:
*   **Single Node (Builder or Associative Array):** If a single node is passed (or the array has only one element), it is stored exactly as a single object/associative array (e.g., `"offers": { "@type": "Offer", ... }`).
*   **Multiple Nodes (Mix of Builders/Arrays):** If multiple nodes are passed, they must be stored as a numeric array (list) of objects.
*   **Appending (`add*` methods):**
    *   If the existing value is a single object, calling `addOffer` converts the property into a list containing the original object and the new one.
    *   If the property does not exist, `addOffer` stores the node as a single object.

### 2.3 AggregateOffer Strict Composition
*   An `AggregateOffer` represents a collection of offers, but Schema.org defines it as a distinct node.
*   `Product -> offers -> AggregateOffer` is valid.
*   The `AggregateOffer` itself can contain nested offers via its own `offers` property: `AggregateOffer -> offers -> [Offer, Offer]`.
*   Composition must not accidentally flatten `AggregateOffer` into an array of offers.

### 2.4 Backward Compatibility & Product Implicit Offers
**Current Behavior:** `setPrice(10)->setCurrency('USD')` implicitly creates `["offers" => ["@type" => "Offer", "price" => 10, ...]]`.
**New Rule (Deterministic Overwrite):**
*   If a user calls a typed composition method (`setOffers` or `addOffer`), it **permanently overwrites** any implicitly built scalar offer data.
*   If a user calls a scalar method (`setPrice`, `setCurrency`) **after** calling `setOffers()`, a `JsonLdBuildException` MUST be thrown to prevent generating an invalid, mixed-state schema.
*   **Validation Rule:** `setOfferField()` must check if `$this->get('offers')` is already a structured list, a typed Builder object, or an `AggregateOffer` (by checking `@type`). If so, it must throw an exception.

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
public function setIsVariantOf(string|array|JsonLdBuilderInterface $productGroup): static
public function setInProductGroupWithID(string $productGroupID): static
public function setOffers(array|JsonLdBuilderInterface ...$offers): static
public function addOffer(array|JsonLdBuilderInterface $offer): static
```

### 3.2 ProductGroup Builder (`ProductGroupJsonLdBuilder.php`)
*Properties restricted strictly to this list. No implied methods.*
```php
public function __construct() // initializes @type => ProductGroup
public function setName(string $name): static
public function setDescription(string $description): static
public function setBrand(string|array|JsonLdBuilderInterface $brand): static
public function setUrl(string $url): static
public function setProductGroupID(string $id): static
public function setVariesBy(array $properties): static
public function setHasVariant(array|JsonLdBuilderInterface ...$variants): static
public function addVariant(array|JsonLdBuilderInterface $variant): static
```

### 3.3 AggregateOffer Builder (`AggregateOfferJsonLdBuilder.php`)
```php
public function __construct() // initializes @type => AggregateOffer
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
/**
 * Note: JsonLdBuilderInterface support is a technical composition contract.
 * Semantic validation (e.g. ensuring it is an Organization) is deferred to Phase 13P.
 */
public function setSeller(string|array|JsonLdBuilderInterface $seller): static
```

---

## 4. JSON-LD Examples (Composition Scenarios)

### Scenario A: Google-Oriented ProductGroup with Variants
*(Uses standard Schema.org URLs for `variesBy` to meet Google Rich Results criteria)*
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
      "sku": "TSHIRT-RED-L",
      "color": "Red",
      "size": "L"
    }
  ]
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

---

## 5. Serialization and Validation Preparedness
*   **Serialization:** The library currently uses native `json_encode()` with `JSON_THROW_ON_ERROR` and any flags passed by the caller. No strict security flags are forced internally by default at the trait level.
*   **Validation:** Technical composition (allowing any `JsonLdBuilderInterface`) is purely architectural. Semantic validation of these nodes will be implemented exclusively in Phase 13P.

---

## 6. Execution Plan & PR Splitting (Aligned with Roadmap)

All tests must be placed in the root `tests/` directory following the established convention.

1.  **Phase 13O-1: Product Builder Completeness**
    *   Add GTIN, MPN, color, size, material, pattern, and relationship properties to `ProductJsonLdBuilder`.
    *   Test: Add methods to `tests/Phase13BProductJsonLdBuilderTest.php`.
2.  **Phase 13O-2: ProductGroup / Variants**
    *   Create `ProductGroupJsonLdBuilder`.
    *   Test: Create `tests/Phase13OProductGroupJsonLdBuilderTest.php`.
3.  **Phase 13O-3: AggregateOffer**
    *   Create `AggregateOfferJsonLdBuilder`.
    *   Test: Create `tests/Phase13OAggregateOfferJsonLdBuilderTest.php`.
4.  **Phase 13O-4: Typed Structured Data Composition**
    *   Implement `resolveNode()` inside `JsonLdBuilderTrait::toArray()`.
    *   Implement `setOffers`/`addOffer` in Product and AggregateOffer builders.
    *   Implement `setSeller` builder support in Offer.
    *   Implement deterministic BC exceptions for implicit price mixing.
    *   Test: Create `tests/Phase13OCompositionTest.php`.
5.  **Phase 13O-5: Tests / Examples / Documentation**
    *   Sync `docs/SEO_LIBRARY_REFERENCE.md`.
    *   Add a verification report `docs/verification/PHASE_13O_PRODUCT_STRUCTURED_DATA_VERIFICATION_REPORT.md`.