# Phase 23 — Merchant Center Eligibility Diagnostics

## Goal

Provide an optional, framework-neutral boundary for Google Merchant Center Eligibility Diagnostics via the Merchant API v1 while keeping external provider results separate from the library's core SEO validation and taxonomy.

## Blueprint

The design and scope are recorded in
[`PHASE_23_MERCHANT_CENTER_ELIGIBILITY_DIAGNOSTICS_BLUEPRINT.md`](../blueprints/PHASE_23_MERCHANT_CENTER_ELIGIBILITY_DIAGNOSTICS_BLUEPRINT.md).

## Implementation status

- WU1 — Merchant Contracts & Typed DTOs: complete
- WU2 — Provider Response Mapping: complete
- WU3 — Merchant Diagnostics Service: complete
- Verification Gate: `PASS`
- Verified Draft SHA: `042d0f26066bc368da3518c9148dc1acddf7c5b7`
- Documentation Sweep: complete
- Final Review: pending

The Phase is not marked complete until Final Review has passed.

## Architecture boundary

The library provides typed contracts under `Maatify\Seo\Web\MerchantCenter` and accepts a host-owned transport. The host owns HTTP, OAuth (`https://www.googleapis.com/auth/content`), credentials, JSON decoding, retry/backoff, pagination loops, cache, and scheduling. The library handles request validation, transport execution orchestration, and deterministic response mapping into a typed DTO hierarchy.

Provider results are completely independent from core SEO validation. Missing optional provider data remains null or empty, unknown statuses are preserved as raw strings, and no synthetic overall eligibility is derived. The host consumes `nextPageToken` for pagination without library interference. Aggregate status updates may be delayed by the provider by more than 30 minutes and are not real-time.

No Google SDK, concrete HTTP client, or auto-remediation logic is introduced by the library.

## Limitations and out of scope

- Reports API `product_view`
- Issue Resolution
- account issue workflows
- Product creation, updates, or deletions
- Feed submission or inventory mutation
- Automatic issue remediation or mutation logic
- Deriving synthetic overall eligibility enums (e.g., `ELIGIBLE`, `PENDING`) not strictly present in the Product API.
- Search Console changes
- Changes to core validation semantics

## Lifecycle

Documentation Sweep is recorded separately from implementation and Verification.
Final Review remains pending, and Ready or merge are separate maintainer actions.
