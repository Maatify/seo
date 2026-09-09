# Phase 22 — Search Console External Verification

## Goal

Provide an optional, framework-neutral boundary for Google Search Console URL
Inspection API evidence while keeping external provider results separate from the
library's core SEO validation and scoring contracts.

## Blueprint

The design and scope are recorded in
[`PHASE_22_SEARCH_CONSOLE_EXTERNAL_VERIFICATION_BLUEPRINT.md`](../blueprints/PHASE_22_SEARCH_CONSOLE_EXTERNAL_VERIFICATION_BLUEPRINT.md).

## Implementation status

- WU1 — typed request, transport, and result contracts: complete
- WU2 — response mapping and provider exception boundary: complete
- WU3 — inspection service validation and orchestration: complete
- Verification Gate: `PASS`
- Verified Draft SHA: `4e0fc5c93d746290e8b0b83ad60bdda761959232`
- Documentation Sweep: complete
- Final Review: pending

The Phase is not marked complete until Final Review has passed.

## Architecture boundary

The library provides typed contracts under
`Maatify\Seo\Web\Indexing\SearchConsole` and accepts a host-owned transport.
The host owns HTTP, OAuth, credentials, token lifecycle, scope selection, quotas,
and scheduling. The intended provider is Google's URL Inspection API, which
evaluates the version available in Google's index; it is not a live URL fetch or
a Rich Results Test replacement.

Provider results are independent of `SeoMetaValidator`, JSON-LD semantic
validation, `SeoValidationResultDTO`, `SeoValidationReportDTO`, scoring,
summaries, batch reports, and existing exporters. Missing `richResultsResult`
maps to `null`, unknown provider values remain raw, and non-2xx or malformed
responses remain in the provider-specific exception boundary.

No Google SDK, network client, credentials, retry, cache, persistence, queue, or
scheduled job is introduced by the library.

## Limitations and out of scope

- Merchant API and Merchant Center product eligibility
- Search Analytics
- sitemap submission and indexing requests
- Rich Results Test scraping, browser automation, and unofficial endpoints
- changes to core validation semantics or existing DTO, scoring, and exporter contracts

## Lifecycle

Documentation Sweep is recorded separately from implementation and Verification.
Final Review remains pending, and Ready or merge are separate maintainer actions.
