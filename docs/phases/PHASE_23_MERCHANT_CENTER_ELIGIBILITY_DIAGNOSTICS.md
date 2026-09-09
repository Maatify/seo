# Phase 23 — Merchant Center Eligibility Diagnostics

## Goal

Provide an optional external diagnostics boundary for reading and diagnosing Google Merchant Center product eligibility, status, and issues via the Merchant API v1.

## Blueprint

The design and scope are recorded in
[`PHASE_23_MERCHANT_CENTER_ELIGIBILITY_DIAGNOSTICS_BLUEPRINT.md`](../blueprints/PHASE_23_MERCHANT_CENTER_ELIGIBILITY_DIAGNOSTICS_BLUEPRINT.md).

## Implementation status

- WU1–WU3: complete
- Verification Gate: `PASS`
- Verified Draft SHA: `042d0f26066bc368da3518c9148dc1acddf7c5b7`
- Documentation Sweep: complete
- Final Review: pending

The Phase is not marked complete until Final Review has passed.

## Architecture boundary

The library provides typed contracts under `Maatify\Seo\Web\MerchantCenter` and accepts a host-owned transport. The host owns HTTP, OAuth, credentials, JSON decoding, retry, cache, persistence, and scheduling. The OAuth scope is `https://www.googleapis.com/auth/content`.

Selected surfaces are `products.get` and `aggregateProductStatuses.list`. Reports `product_view`, issue resolution actions, and mutations are deferred.

Provider results are strictly separated from core SEO validation. There is no synthetic overall eligibility status, and unknown provider values are preserved raw. Aggregate counts (active, pending, disapproved, expiring) are preserved as strings. Missing provider data does not equal PASS.

There is no auto-pagination; the host consumes `nextPageToken`. The library does not clamp `pageSize > 250`.

## Limitations

Aggregate provider status can lag; Google warns of >30 minute freshness delays. There is no guarantee of Merchant eligibility, approval, ranking, or provider acceptance. No network verification against a real Merchant account is performed or claimed.
