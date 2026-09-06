# Phase 8 — Developer Experience & Usage Documentation

## Phase status

Documentation Sweep is complete for the current Phase 8 Draft. Final Review against
the latest `main` is still pending, so Phase 8 is not marked `Complete` in the
roadmap and the Integration PR remains under the maintainer's review lifecycle.

Lifecycle:

`Draft Integration PR → Blueprint → Work Units → Verification → Documentation Sweep → Final Review vs latest main → Ready → Merge`

Verified Draft HEAD:

`e527672747642ed06d7295f38512326e89b642cb`

Verified `main`:

`2989683e3609bcc843d0ec25ead3799a3b5d2d39`

## Final implemented scope

Phase 8's actual implementation scope is documentation-only:

- A dedicated end-to-end Homepage SEO example was added to
  `docs/guides/USAGE_GUIDE.md`.
- The Usage Guide's JSON-LD validation wording was synchronized with the current
  Phase 13P implementation.
- The Usage Guide explicitly documents structural node/list and `@graph` handling,
  recursive graph nodes, the four JSON-LD aliases, and the limited deep semantic
  scope.
- The host application's responsibility for placing output in `<head>` and sending
  the HTTP response remains explicit.

The original 8B Integration Guide and the five 8C examples were already present and
compatible with the current APIs. They were reviewed and were not rebuilt or
rewritten.

### Work Unit disposition

WU1 — Usage Guide completion and evidence-backed synchronization — was the only
implementation gap identified by the Blueprint:

- Homepage example added using existing `FluentSeoBuilder`,
  `WebSiteJsonLdBuilder`, and `OrganizationJsonLdBuilder` APIs.
- The stale JSON-LD description limited to non-empty arrays was replaced with the
  current structural and semantic boundary.
- No contradiction was found in `docs/guides/INTEGRATION_GUIDE.md`, so it was not
  changed.

8B and 8C were existing repository capabilities, not missing Work Units in this
Phase execution.

## Validation and documentation boundaries

The synchronized documentation reflects the current contracts:

- Structural JSON-LD validation covers nodes, numeric node lists, `@graph`, and
  recursive nested graph nodes.
- JSON-LD is accepted through `jsonLd`, `json_ld`, `schema`, and `schemas` aliases.
- Deep semantic validation is limited to `Product`, `Offer`, `AggregateOffer`, and
  `ProductGroup`.
- Complete Schema.org coverage is not claimed.
- Google Rich Results eligibility is not implemented or guaranteed.
- Merchant eligibility is not implemented or guaranteed.
- The library remains framework-neutral and does not add provider, network, or
  framework integration.

## Verification Gate

The accepted Verification report is:

`docs/verification/PHASE_8_DEVELOPER_EXPERIENCE_USAGE_DOCUMENTATION_VERIFICATION_REPORT.md`

Verification result: `PASS`.

Recorded results:

- PHP syntax gate: `229` PHP files passed.
- `vendor/bin/phpstan analyse`: PASS.
- `php tests/Phase21StructuredDataCiValidationTest.php`: PASS.
- Standalone tests: `49/49` passed.
- Examples: `14/14` passed.
- Homepage snippet: PASS, exit status `0`, output `1247` bytes, with title,
  OpenGraph, Twitter, and two JSON-LD script blocks.
- GitHub Actions: PASS on PHP `8.2`, `8.3`, and `8.4`, including syntax, PHPStan,
  structured-data, conditional PHPUnit, and standalone-test steps.
- `composer validate --strict`: PASS.
- `git diff --check`: PASS.

## Compatibility and scope review

- No `src/**` changes.
- No runtime or public API changes.
- No `tests/**` changes.
- No `.github/**` changes.
- No Composer dependency changes.
- No framework or provider implementation.
- No external network calls or credentials.
- Existing examples remain unchanged and executable.
- The Integration Guide remains compatible with the current public APIs.

## Documentation Impact Review

Every required path was reviewed and assigned exactly one status from the Phase
Execution Standard: `updated`, `reviewed-no-change`, or `deferred-with-reason`.

| Path | Status | Reason / recorded action |
| --- | --- | --- |
| `README.md` | `reviewed-no-change` | Existing guide links, example links, and library claims remain accurate. |
| `docs/SEO_LIBRARY_REFERENCE.md` | `reviewed-no-change` | Phase 13P boundaries, builder contracts, aliases, and validation limitations already match the current code. |
| `docs/guides/USAGE_GUIDE.md` | `updated` | Added the Homepage example and synchronized JSON-LD validation wording in WU1. |
| `docs/guides/INTEGRATION_GUIDE.md` | `reviewed-no-change` | Plain PHP, Slim, Laravel, template, renderer, and host-owned response guidance remains compatible. |
| `docs/SEO/**` | `reviewed-no-change` | Active architecture documentation matches current behavior; `docs/SEO/v1/**` remains historical and was not rewritten. |
| `docs/phases/**` | `updated` | This Phase 8 record was added as the Documentation Sweep record. |
| `docs/verification/**` | `updated` | The accepted Phase 8 Verification report records the PASS gate and actual counts. |
| `examples/**` | `reviewed-no-change` | All 14 examples, including the five Phase 8 examples, execute successfully; no mismatch was found. |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | `reviewed-no-change` | No claim in the main roadmap required synchronization for this documentation-only Phase. |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | `updated` | The Phase Execution Standard was synchronized with the actual Stack lifecycle; Phase 8 completion status itself remains deferred pending Final Review. |
| `docs/blueprints/**` | `updated` | The accepted Phase 8 Blueprint is present and is the source for this scope and gate record. |

No other documentation path was changed during this sweep.

## Limitations and deferred work

- Final Review against the latest `main` has not been executed in this branch.
- Phase 8 must not be marked `Complete` until Final Review passes.
- The library does not provide complete Schema.org semantic coverage, Google Rich
  Results eligibility, or Merchant eligibility validation.
- Historical documents under `docs/SEO/v1/**` remain historical by design.
- Any future runtime, framework, provider, or external-service work requires a
  separately scoped Phase or Work Unit.

## Next gate

The next and final Phase 8 gate is Final Review of the complete Draft diff against
the latest `main`. Only after that review passes may the Integration PR move to
Ready and proceed toward merge. This record does not perform Final Review and does
not change roadmap status.
