# Phase 23 — Merchant Center Eligibility Diagnostics Blueprint

## 1. Goal

The goal of Phase 23 is to design an optional external diagnostics boundary for reading and diagnosing Google Merchant Center product eligibility, status, and issues via the Merchant API v1. This integration is strictly isolated and its results must not be merged into or alter the core SEO validation processes or taxonomy.

## 2. Scope Decision

**Included in Scope:**
1. **Product-level eligibility diagnostics:** Retrieving product status, reporting contexts, and item issues using the `productStatus` resource.
2. **Typed status/issues:** Mapping provider states (e.g., `ELIGIBLE`, `PENDING`, `DISAPPROVED`) and issue severities into strict, read-only DTOs.
3. **Account-level aggregate diagnostics:** Utilizing `aggregateProductStatuses.list` to provide an optional account health overview (approved, pending, disapproved counts by reporting context). This adds significant diagnostic value without polluting the core contracts.

**Deferred from Scope:**
* **Issue Resolution (`renderproductissue`, `renderaccountissue`):** Deferred. These endpoints are primarily designed for displaying human-readable remediation UI and actionable steps. They are not essential for the core backend eligibility diagnostic contract and would unnecessarily expand the scope.
* **Reports API (`product_view` filtering):** Deferred. The complex filtering and search capabilities of the Reports API are out of scope. Providing product-level exact lookups and aggregate account health fulfills the library's diagnostic purpose without requiring a complex querying contract.

## 3. Strict Boundaries

Phase 23 must strictly preserve core validation isolation. The Merchant Center results **must not** modify, merge into, or be referenced by:
* `SeoMetaValidator`
* `JsonLdSemanticValidator`
* `SeoValidationResultDTO`
* `SeoValidationReportDTO`
* SEO scoring algorithms.
* Validation summaries or batch validation reports.
* Existing exporters.
* The current SEO issue taxonomy.

Google Merchant issues (e.g., missing GTIN in the Merchant Center) are provider-specific external diagnostics, not core SEO library validation issues.

## 4. Host Ownership

The library defines contracts and mappings but pushes execution to the host. The host application strictly owns:
* **HTTP Client & Network Execution:** (Guzzle, PSR-18, cURL, etc.). The library will not include HTTP dependencies.
* **OAuth Flow & Credentials:** Client ID, Client Secret, credential storage, and token lifecycles.
* **JSON Decoding:** The host decodes the JSON response and passes raw arrays to the library's mapper.
* **Scheduling & Persistence:** Retries, quotas, backoff strategy, queueing, and database storage.

*Note: The required OAuth scope for the Merchant API must be managed by the host. The recommended minimum scope is `https://www.googleapis.com/auth/content`.*

## 5. Provider Semantics

To ensure resilience against external API changes, the blueprint mandates:
* **Raw Value Preservation:** Provider enum values (status, severity) must be preserved in their raw string format.
* **Unknown Value Resilience:** Future unknown statuses, enum values, or severities must not break parsing or throw mapping exceptions.
* **No Default Success:** Unknown statuses must not automatically be treated as "eligible" or "success".
* **Missing Data:** Missing provider sections or missing issue arrays do not implicitly mean a "PASS". They must be represented as empty collections or nullable properties.
* **No Automatic Remediation:** The library provides read-only diagnostics without attempting to automatically resolve or mutate issues.
* **Freshness Warning:** The documentation must clearly state that Merchant Center status updates are subject to provider delay and are not real-time.
* **Identifiers:** Product and resource identifiers must be accepted and passed without inventing custom parsing contracts.

## 6. Error Model

A dedicated exception family (e.g., `MerchantCenterException`) will enforce error boundaries, distinguishing between:
1. **Invalid Local Request:** Missing required IDs or invalid parameters before attempting transport.
2. **Transport/Provider HTTP Failure:** Non-2xx responses from the provider.
3. **Malformed Decoded Response:** Missing required structural fields in the parsed array that prevent successful DTO hydration.

The exception model must **never** leak:
* OAuth tokens.
* Credentials or Authorization headers.
* Secrets.
* Raw, unsafe provider payloads containing sensitive account data.

Additionally, standard HTTP statuses (like 401/403) should not be over-classified as specific authorization errors, as they are transport-level concerns.

## 7. Namespace and File Plan

All Phase 23 components will be strictly locked under the following namespace:
`Maatify\Seo\Web\MerchantCenter`

**Planned Files:**
* `src/Web/MerchantCenter/MerchantCenterTransportInterface.php`
* `src/Web/MerchantCenter/DTO/MerchantCenterProductRequestDTO.php`
* `src/Web/MerchantCenter/DTO/MerchantCenterAggregateRequestDTO.php`
* `src/Web/MerchantCenter/DTO/MerchantCenterTransportResponseDTO.php`
* `src/Web/MerchantCenter/DTO/MerchantCenterProductStatusResultDTO.php`
* `src/Web/MerchantCenter/DTO/MerchantCenterItemIssueDTO.php`
* `src/Web/MerchantCenter/DTO/MerchantCenterAggregateStatusResultDTO.php`
* `src/Web/MerchantCenter/Exception/MerchantCenterException.php`
* `src/Web/MerchantCenter/Mapper/MerchantCenterResponseMapper.php`
* `src/Web/MerchantCenter/MerchantCenterDiagnosticsService.php`

*Generic arrays will only be used to hold raw decoded transport bodies or explicit arrays of typed DTOs (with proper PHPStan annotations).*

## 8. Work Units

The implementation will be completed in a single PR, structured into three logical work units.

### WU1 — Merchant Contracts & Typed DTOs
**Scope:** Establish the transport interface, exception foundation, and strictly typed request/response DTO hierarchy.
* Define `MerchantCenterTransportInterface`.
* Define Product and Aggregate Request DTOs.
* Define immutable Result DTOs (`MerchantCenterProductStatusResultDTO`, `MerchantCenterItemIssueDTO`, `MerchantCenterAggregateStatusResultDTO`).
* Establish `MerchantCenterException`.

### WU2 — Provider Response Mapping
**Scope:** Implement the deterministic response mapper to convert decoded transport arrays into the typed DTO hierarchy.
* Build `MerchantCenterResponseMapper`.
* Implement safe parsing for unknown statuses and severities.
* Handle missing optional fields and malformed response structures.

### WU3 — Merchant Diagnostics Service
**Scope:** Implement the orchestration layer connecting the DTOs, Transport, and Mapper.
* Build `MerchantCenterDiagnosticsService`.
* Orchestrate request validation, transport execution, and mapping.
* Handle provider failure propagation and ensure strict decoupling from core SEO validation.

## 9. Tests Required

Standalone, no-network tests are mandatory and must cover:
1. Fully eligible product.
2. `ELIGIBLE_LIMITED` product.
3. `PENDING` product.
4. `NOT_ELIGIBLE_OR_DISAPPROVED` product.
5. Multiple reporting contexts.
6. Item issues with varying severities.
7. Optional/missing provider fields.
8. Unknown future statuses and severities (verifying parsing does not break).
9. Malformed decoded provider response.
10. Non-2xx transport/provider response.
11. Invalid local request.
12. Transport failure propagation.
13. Verification that no automatic retries are executed.
14. Verification of complete decoupling from core SEO validation.
15. Aggregate diagnostics: approved/pending/disapproved counts.
16. Aggregate diagnostics: aggregate issue mapping.
17. Aggregate diagnostics: reporting-context/country mapping.

## 10. Explicit Non-Goals

The following features and integrations are explicitly out of scope for Phase 23:
* Product creation, updates, or deletions.
* Feed submission or inventory mutation.
* Merchant Center account configuration.
* Automatic issue remediation or mutation logic.
* Campaign management.
* Performance or pricing analytics.
* OAuth flow, credential storage, or HTTP client implementation.
* Issue Resolution UI or UI actions.
* Search Console integration changes.
* Any integration with or changes to core SEO structural/semantic validation pipelines.
