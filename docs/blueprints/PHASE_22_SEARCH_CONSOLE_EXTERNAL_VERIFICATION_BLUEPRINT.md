# Phase 22 — Search Console External Verification Blueprint

## 1. Goal

The goal of Phase 22 is to define the boundary and contracts for optional external verification using the Google Search Console URL Inspection API (`index.inspect`). This phase establishes the typed Request/Response DTOs, provider mapping semantics, transport contract, and orchestration service. It explicitly does not execute HTTP requests, does not handle OAuth or credentials, and does not alter the core SEO validation results.

## 2. Current repository baseline

The repository implements a robust core structural and semantic validation pipeline for JSON-LD and SEO metadata. The current implementation in `SeoMetaValidator` and `JsonLdSemanticValidator` operates independently, offline, and deterministically. There is intentionally no integration with external providers, live URL testing, or Google API services. The core validation pipeline (`SeoValidationResultDTO`, `SeoValidationReportDTO`) and the current issue taxonomy are strictly separated from external provider behavior.

## 3. Official provider contract

Phase 22 exclusively integrates with the Google Search Console URL Inspection API:
* **Operation:** `index.inspect`
* **Endpoint:** `POST searchconsole.googleapis.com/v1/urlInspection/index:inspect`
* **Required Request Fields:** `inspectionUrl`, `siteUrl`
* **Optional Request Fields:** `languageCode`
* **Required OAuth Scopes:** `webmasters.readonly` is the recommended minimum privilege (over `webmasters`).

## 4. Architectural boundaries

### Indexed Version Only
The URL Inspection API evaluates the version of the URL currently in the Google index.
* It is **not** a Live URL Inspection tool.
* It is **not** a Rich Results Test replacement.
* The output result must not be named `liveValidation`.
* The result must not claim to test the latest published HTML.

### Strict Separation From Core Validation
Phase 21 established the CI quality baseline. Phase 22 must not modify or merge Google results into:
* `SeoMetaValidator`
* `JsonLdSemanticValidator`
* `SeoValidationResultDTO`
* `SeoValidationReportDTO`
* Validation scores, summaries, batch reports, existing exporters, or the current issue taxonomy.

Google WARNING or ERROR statuses are not core SEO issues and must remain a **provider-specific external result**.

## 5. Namespace/file plan

Phase 22 is strictly locked under the namespace: `Maatify\Seo\Web\Indexing\SearchConsole`

The file structure expands to include nested typed DTOs required for the provider result, rather than relying on generic arrays:

* `src/Web/Indexing/SearchConsole/SearchConsoleTransportInterface.php`
* `src/Web/Indexing/SearchConsole/DTO/SearchConsoleInspectionRequestDTO.php`
* `src/Web/Indexing/SearchConsole/DTO/SearchConsoleTransportResponseDTO.php`
* `src/Web/Indexing/SearchConsole/DTO/SearchConsoleInspectionResultDTO.php`
* `src/Web/Indexing/SearchConsole/DTO/SearchConsoleIndexStatusResultDTO.php`
* `src/Web/Indexing/SearchConsole/DTO/SearchConsoleRichResultsResultDTO.php`
* `src/Web/Indexing/SearchConsole/DTO/SearchConsoleDetectedItemDTO.php`
* `src/Web/Indexing/SearchConsole/DTO/SearchConsoleRichResultItemDTO.php`
* `src/Web/Indexing/SearchConsole/DTO/SearchConsoleRichResultIssueDTO.php`
* `src/Web/Indexing/SearchConsole/Exception/SearchConsoleException.php`
* `src/Web/Indexing/SearchConsole/Mapper/SearchConsoleResponseMapper.php`
* `src/Web/Indexing/SearchConsole/SearchConsoleInspectionService.php`

## 6. Public contracts

### SearchConsoleTransportInterface
The library relies on a provider-specific injected transport instead of HTTP dependencies.
```php
interface SearchConsoleTransportInterface
{
    public function inspect(SearchConsoleInspectionRequestDTO $request): SearchConsoleTransportResponseDTO;
}
```
* Accepts a typed inspection request.
* Returns a typed transport response containing the HTTP status and decoded provider JSON body.
* The transport handles OAuth and the HTTP stack. The library adds no Google SDK, Guzzle, PSR-18, cURL, OAuth library, or env-variable contracts.

## 7. DTO shapes

### Request: `SearchConsoleInspectionRequestDTO`
Represents the request to Google:
* `inspectionUrl` (string, required)
* `siteUrl` (string, required)
* `languageCode` (string, nullable)

### Transport Response: `SearchConsoleTransportResponseDTO`
Represents the transport's response:
* `httpStatus` (int)
* `decodedBody` (array)
* **No tokens or credentials.**

### Final Result Hierarchy
The mapped results must use a genuinely typed DTO hierarchy. Untyped arrays are strictly prohibited within the mapped result (except for raw decoded transport body preservation if applicable).

`SearchConsoleInspectionResultDTO`
* `providerIdentity` (string) = "Google Search Console"
* `inspectionResultLink` (string|null)
* `indexStatusResult` (`SearchConsoleIndexStatusResultDTO`)
* `richResultsResult` (`SearchConsoleRichResultsResultDTO`|null) - Nullable because Google may omit this object if no rich results exist.

`SearchConsoleIndexStatusResultDTO`
* Covers fields such as: `verdict`, `coverageState`, `robotsTxtState`, `indexingState`, `lastCrawlTime`, `pageFetchState`, `googleCanonical`, `userCanonical`, `crawledAs`

`SearchConsoleRichResultsResultDTO`
* `verdict` (string)
* `detectedItems` (array of `SearchConsoleDetectedItemDTO`)

`SearchConsoleDetectedItemDTO`
* `richResultType` (string)
* `items` (array of `SearchConsoleRichResultItemDTO`)

`SearchConsoleRichResultItemDTO`
* `name` (string|null)
* `issues` (array of `SearchConsoleRichResultIssueDTO`)

`SearchConsoleRichResultIssueDTO`
* `issueMessage` (string)
* `severity` (string)

*Note: The absence of `richResultsResult` is not equivalent to PASS. It must explicitly be represented as `null` or "not reported".*

## 8. Transport/auth ownership

### Library Owns:
* Typed Search Console request contract.
* Typed provider response/result DTOs.
* Deterministic response mapping.
* Provider failure semantics.
* Orchestration service.
* Provider identity and documentation of API limitations.

### Host Application Owns:
* OAuth flow, Client ID, Client Secret.
* Refresh tokens and access-token lifecycle.
* Credential storage and HTTP implementation.
* JSON Decoding (Host parses JSON and creates array).
* Secret logging policy.
* Scheduling, throttling, and caching/persistence of results.

## 9. Mapping semantics

Google provider enum/string values may evolve. Mapping strategy must explicitly handle unknown provider values:
* Provider enum/state fields must retain the raw string received from Google.
* Known values can be interpreted or differentiated, but any future unknown value **must not** break the parsing process completely.
* Unknown values **must not** be automatically converted to PASS or FAIL.
* The raw provider value **must not** be lost.
* Do not use closed PHP enums that throw exceptions upon encountering new Google values unless they are explicitly designed with an unknown/raw preservation fallback.

## 10. Error semantics

A custom exception family (e.g., `SearchConsoleException`) will be introduced. It must architecturally differentiate between:
1. **Invalid local request:** Malformed URL or missing required fields before transport.
2. **Transport/provider HTTP failure:** Non-2xx response from Google (including 403, which shouldn't be over-classified as it can mean many things).
3. **Malformed/unusable provider response shapes:** The host transport is responsible for HTTP, OAuth, and JSON decoding. If JSON decoding fails, the transport must throw a transport-level exception. The `SearchConsoleResponseMapper` exclusively handles decoded arrays and is responsible for detecting malformed or unusable provider shapes (e.g., missing required structural fields or incorrect field types).

Exceptions may retain the HTTP status and safe provider error information, but must absolutely exclude:
* Credentials, Authorization headers, tokens, and secrets.

## 11. Security rules

* The library must never receive or store OAuth secrets.
* The `SearchConsoleTransportResponseDTO` must not contain any sensitive headers or auth tokens.
* Error messages must be scrubbed of any secrets.
* The lowest acceptable OAuth scope (`webmasters.readonly`) must be recommended.

## 12. Quota/retry rules

Google URL Inspection API is quota-restricted on the provider side. Phase 22 strictly forbids:
* Automatic retries or hidden retry logic.
* Throttling schedulers.
* Queues.
* Caching or persistence.

The host application assumes full responsibility for scheduling, backoff, and quota strategy. This must be clearly documented.

## 13. WU1–WU3 exact scope

Phase 22 is divided into precisely three WUs (Work Units). **There is no WU4.**

### WU1 — Contracts & DTO Foundation
**Scope:** Defines the request DTO, transport interface/response contract, typed external result DTO structure, and provider-specific exception foundation.
**No HTTP implementation.**

### WU2 — Search Console Response Mapper
**Scope:** Implements deterministic parsing for index status, rich results, detected types/items, issues/severity, absent optional sections, unknown provider values, and malformed provider decoded arrays.
**Must be testable without network access.**

### WU3 — Inspection Service Orchestration
**Scope:** Implements the service that coordinates the request DTO, injected transport interface, and response mapper to produce the typed external result. Handles local validation, transport failure propagation, provider HTTP failure, and successful mapping.
**No mutation to core validation contracts.**

## 14. Tests

Standalone, deterministic tests (without network or credentials) are required. Must cover at least:
1. Indexed PASS + rich-results PASS.
2. Rich-result ERROR.
3. WARNING.
4. Multiple rich-result types/items.
5. `richResultsResult` absent.
6. Index-status fields present.
7. Optional provider fields absent.
8. Unknown provider verdict/severity/value.
9. Malformed decoded provider response array.
10. Non-2xx provider response.
11. Invalid local request.
12. Verification that no core validation DTO or service is modified or invoked.

Fixtures or fake transports are permitted only in tests. No real Google API calls in CI.

## 15. Explicit non-goals

The following are strictly out of scope for Phase 22:
* Merchant API / Merchant Center.
* Search Analytics, sitemap submission, Indexing API, or indexing requests.
* Rich Results Test scraping or live URL testing.
* AMP verification or deprecated mobile-usability integration.
* Browser automation or unofficial Google endpoints.
* Batch inspection orchestration.
* Persistence/history, queues, or caches.
* OAuth implementation, credential storage, or automatic retries.
* Any modifications to core validation logic.

## 16. Verification gates

After WU3, the following gates must pass:
```bash
composer validate --strict
vendor/bin/phpstan analyse
find tests -name '*Test.php' -print0 | xargs -0 -n1 php
git diff --check
```
The Final Verification Gate reassesses the complete Phase 22 implementation against these standards.

## 17. Documentation impact candidates

* `docs/SEO_LIBRARY_REFERENCE.md`: Update to reflect the new external verification namespace and DTOs.
* `docs/guides/USAGE_GUIDE.md`: Add a section detailing how a host application would implement the transport and inject it.
* `docs/phases/PHASE_22_SEARCH_CONSOLE_EXTERNAL_VERIFICATION.md`: (To be created post-implementation) Summarizing the phase.
* Documentation must clearly emphasize that this is indexed data, not live validation, and that quotas/retries are the host's responsibility.

## 18. Final acceptance criteria

Phase 22 is complete when:
1. WUs 1-3 are fully implemented without adding HTTP libraries or violating core separation.
2. All DTOs, mappers, and orchestration services are strictly typed and deterministically parse the provider API.
3. 100% of the required test cases are covered using mocked transports/fixtures.
4. No core SEO validation logic or DTOs are altered.
5. All verification gates and static analysis pass cleanly.
6. Documentation accurately reflects the architectural boundaries, OAuth ownership, and quota responsibilities.
