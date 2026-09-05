# Blueprint: Phase 13O — Advanced Product Structured Data

## 1. Current State Analysis

### 1.1 Existing Classes & Traits
*   **`JsonLdBuilderInterface` & `JsonLdBuilderTrait`**: Provide foundational array manipulation.
*   **`ProductJsonLdBuilder`**: Supports core fields, but implicitly forces a single unstructured `Offer` array via `setOfferField()`.
*   **`OfferJsonLdBuilder`**: Supports basic properties but `setSeller` lacks typed composition.

### 1.2 The Gaps
1.  **Product Fields**: Missing `GTIN`, `MPN`, variant descriptors, and relationship tracking.
2.  **ProductGroup**: Missing `ProductGroupJsonLdBuilder`.
3.  **AggregateOffer**: Missing `AggregateOfferJsonLdBuilder`.
4.  **Composition**: Missing safe typed composition capabilities.

---

## 2. Architectural Decisions & Contracts

### 2.1 Typed Composition Contract (`resolveNode`)
*   **Signature:** `protected function resolveNode(mixed $node): mixed` inside `JsonLdBuilderTrait`.
*   **Location:** Invoked *exclusively* inside `toArray()`, not inside `set()`. This preserves the builder reference until the tree is rendered.
*   **Algorithm Rules:**
    1.  **Base Context Preservation:** The root builder's `@context` is inherently preserved because `toArray()` returns the unmodified `$this->schema` before yielding to recursion.
    2.  **Builder Resolution:** If `$node` is a `JsonLdBuilderInterface`, call its `toArray()`.
    3.  **Context Stripping:** For the resolved array (or if the node was already an associative array), unset the `@context` key.
    4.  **Nested Traversals:** Iterate through associative arrays and numeric lists, applying `resolveNode` to all values, unsetting `@context` at every depth layer (e.g. if a raw array contains `@context`, it gets stripped).

### 2.2 Collection Composition Semantics (`setOffers`, `setHasVariant`)
The behavior for variadic collection setters must follow strict deterministic flattening:
*   **Input:** `(array|JsonLdBuilderInterface ...$nodes)`
*   **Flattening Rule:** If a single array argument is passed, and that array is a numeric list (e.g. `setOffers([$offer1, $offer2])`), it MUST be flattened into the variadic arguments stack. If it is an associative array, it is treated as a single node.
*   **Single Node Storage:** If, after flattening, exactly one node exists, it is stored as a direct associative array/object.
*   **Multiple Node Storage:** If more than one node exists (mix of builders/arrays), they are stored as a numeric list of nodes.
*   **Appending (`add*`):**
    *   If no existing nodes exist, store the new node as a single object.
    *   If a single object exists, convert the property to a list of two objects.
    *   If a list already exists, push the new node to the list.

### 2.3 Product Backward Compatibility & Implicit State
To strictly prevent invalid mixed states (e.g., mixing legacy `setPrice` with typed `setOffers`), `ProductJsonLdBuilder` MUST implement a private state flag, independent of array shape detection.

*   **State Flag:** `private bool $hasExplicitOffers = false;`
*   **Rule 1 (`setOffers` / `addOffer` called):**
    *   Clears any existing `offers` data (wiping legacy scalar offers).
    *   Sets `$hasExplicitOffers = true`.
    *   Injects the new explicit nodes.
*   **Rule 2 (`setPrice`, `setCurrency`, `setAvailability`, `setCondition`, `setOfferUrl` called):**
    *   Checks `$hasExplicitOffers`.
    *   If `true`, MUST throw a `JsonLdBuildException` immediately.
    *   If `false`, proceeds with legacy `setOfferField()` behavior.
*   **Rule 3 (AggregateOffer Composition):** An `AggregateOffer` set via `setOffers()` flags the explicit state, protecting it from accidental `setPrice()` calls.

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
public function setIsVariantOf(string|array|JsonLdBuilderInterface $productGroup): static
public function setInProductGroupWithID(string $productGroupID): static

public function setOffers(array|JsonLdBuilderInterface ...$offers): static
public function addOffer(array|JsonLdBuilderInterface $offer): static
```

### 3.2 ProductGroup Builder (`ProductGroupJsonLdBuilder.php`)
```php
public function __construct() // MUST initialize ['@context' => 'https://schema.org', '@type' => 'ProductGroup']
public function setName(string $name): static
public function setDescription(string $description): static

// String cast rule: string becomes ['@type' => 'Brand', 'name' => $brand]
public function setBrand(string|array|JsonLdBuilderInterface $brand): static
public function setUrl(string $url): static
public function setProductGroupID(string $id): static

// Shape: list of string property names/URLs (stored exactly as provided)
public function setVariesBy(array $properties): static
public function setHasVariant(array|JsonLdBuilderInterface ...$variants): static
public function addVariant(array|JsonLdBuilderInterface $variant): static
```

### 3.3 AggregateOffer Builder (`AggregateOfferJsonLdBuilder.php`)
```php
public function __construct() // MUST initialize ['@context' => 'https://schema.org', '@type' => 'AggregateOffer']
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
*Note on Typed Setters: Allowing `JsonLdBuilderInterface` is a structural composition decision. Semantic correctness (e.g. ensuring it is an `Organization`) is intentionally deferred to Phase 13P runtime validation.*

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
*(Note: Using `https://schema.org/*` properties in `variesBy` is a Google-oriented representation required for Rich Result eligibility, though base Schema.org permits string literals.)*
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
      "sku": "TS-BLU-L",
      "color": "Blue",
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

## 5. Execution Plan & Deterministic Test Matrix

All test additions must be contained within `tests/` matching their phase files (e.g., `tests/Phase13OProductGroupJsonLdBuilderTest.php`).

**Test Matrix Requirements:**
*   **Composition Types:** Validate single builder, single associative array, flat numeric list argument, and variadic stack of builders.
*   **Appending:** Verify `addOffer` transitions safely from `null` -> `object` -> `list`.
*   **Backward Compatibility:**
    *   Verify `setPrice(10)` -> `setOffers(Offer)` correctly outputs ONLY the typed Offer.
    *   Verify `setOffers(Offer)` -> `setPrice(10)` strictly throws `JsonLdBuildException`.
*   **Context Stripping:** Assert that injecting `Product` into `ProductGroup` results in exactly one root `@context`, even if raw nested arrays explicitly declare one.

**Execution Order:**
1.  **Phase 13O-1: Product Builder Completeness** (GTIN/MPN, BC state logic, `setOffers`).
2.  **Phase 13O-2: ProductGroup / Variants** (Builder creation, `setHasVariant`).
3.  **Phase 13O-3: AggregateOffer** (Builder creation, composition rules).
4.  **Phase 13O-4: Typed Composition Implementation** (`resolveNode` logic in Trait).
5.  **Phase 13O-5: Tests / Examples / Docs** (Verification reports and Reference MD).