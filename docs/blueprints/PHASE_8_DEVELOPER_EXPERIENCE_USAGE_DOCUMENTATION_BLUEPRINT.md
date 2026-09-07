# Phase 8 — Developer Experience & Usage Documentation Blueprint

## Blueprint status

This is the pre-implementation Blueprint for Phase 8. It records the repository
evidence, the remaining documentation gap, the Work Unit contract, and the gates
required by the repository's Phase Execution Standard. It does not change runtime
code, tests, examples, guides, or roadmap status.

The inventory was performed against the Phase 8 umbrella baseline:

`main` = `2989683e3609bcc843d0ec25ead3799a3b5d2d39`

The Phase 8 umbrella branch is `codex/phase-8-draft`. Its empty marker commit is
only the GitHub-required difference needed to open the Draft Integration PR; it is
not a Phase 8 implementation change.

The lifecycle is:

`Draft Integration PR → Blueprint → Work Units → Verification → Documentation Sweep → Final Review vs latest main → Ready → Merge`

Verification and Documentation Sweep are post-implementation gates, not Work Units.
The Integration PR remains Draft until those gates and Final Review against the
latest `main` are complete.

## Current State

### Repository and documentation baseline

- The package is a framework-neutral PHP library requiring PHP `>=8.2` and
  `ext-xmlwriter` according to `composer.json`.
- The existing public usage surface is represented by builders, renderers, DTOs,
  services, and host-owned integration boundaries under `src/`; Phase 8 does not
  require a new public API.
- `README.md` links to both guides and lists the currently available examples.
- `docs/SEO_LIBRARY_REFERENCE.md` and
  `docs/SEO/library/STRUCTURED_DATA_ARCHITECTURE.md` describe the current Phase 13P
  validation and JSON-LD builder behavior. They are not blank documentation areas.
- No Phase 8-specific phase record or verification report currently exists under
  `docs/phases/**` or `docs/verification/**`. Those are completion artifacts for the
  later gates, not evidence that the existing guides/examples are absent.

### 8A — Usage Guide

`docs/guides/USAGE_GUIDE.md` already exists and currently contains sections for:

- basic SEO head rendering;
- rendered output DTOs;
- the `FluentSeoBuilder`;
- raw and builder-based JSON-LD;
- Product, Offer, AggregateOffer, and ProductGroup composition;
- sitemap and robots output;
- validation, scoring, presets, reports, batch reports, and exporters; and
- host-application usage notes for plain PHP, Slim, Laravel, and templates.

The requested product, category, JSON-LD, sitemap, and fluent-builder usage topics
are represented by existing sections. There is not, however, a dedicated end-to-end
Homepage SEO example in the guide. The only current `Homepage` reference is the
name value in a generic `WebPage` JSON-LD example.

There is also one evidence-backed stale statement in the validation section: it
describes JSON-LD only as a warning for non-empty array formatting. The current
validator includes the Phase 13P structural and in-scope semantic contracts, so the
guide needs wording synchronization without inventing broader Schema.org or Google
claims.

### 8B — Integration Guide

`docs/guides/INTEGRATION_GUIDE.md` exists and covers:

- plain PHP and Composer autoloading;
- Slim integration without adding Slim to the library;
- Laravel integration without adding Laravel to the library;
- Twig, Blade, and DTO-section template usage; and
- host-owned routing, HTTP responses, headers, persistence, and dependency
  injection boundaries.

The guide uses the current public `FluentSeoBuilder`, renderer, sitemap, robots, and
validation APIs inspected in `src/`. No missing 8B topic was found in the current
tree. Its snippets still require the later Documentation Sweep to be checked as
documentation examples rather than treated as executable library tests.

### 8C — More Examples

All five examples named by the Phase 8 roadmap are present:

- `examples/basic-head-render.php`
- `examples/product-page-seo.php`
- `examples/category-page-seo.php`
- `examples/sitemap-output.php`
- `examples/schema-output.php`

The repository contains 14 standalone PHP examples in total. The current examples
are Composer-autoloaded scripts and the full set was executed successfully during
the baseline inventory. No missing 8C file was found, so Phase 8 must not recreate or
rename these five examples.

### Current CI and test convention

The existing `.github/workflows/ci.yml` runs on PHP `8.2`, `8.3`, and `8.4`, keeps
Composer validation and installation, runs the explicit syntax gate over `src/`,
`tests/`, and `examples/`, runs PHPStan, runs the focused structured-data gate, keeps
conditional PHPUnit behavior, and executes the standalone `tests/*Test.php` scripts.
The Phase 8 work must preserve this behavior.

## Gaps

Only these gaps are supported by the current repository evidence:

1. The Usage Guide lacks a dedicated Homepage SEO example that demonstrates the
   existing page-level metadata/rendering flow for a homepage.
2. The Usage Guide's JSON-LD validation bullet is stale relative to the current
   Phase 13P validator and must be synchronized to describe the actual structural
   and in-scope semantic behavior without claiming complete Schema.org validation or
   Google eligibility.
3. Phase 8 has no phase-specific verification record or completion document yet.
   This is a lifecycle documentation gap to be handled by the Verification and
   Documentation Sweep gates; it is not a reason to recreate the already-present
   8A, 8B, or 8C artifacts in an implementation Work Unit.

The following are explicitly not gaps based on the inventory:

- the Usage Guide file itself;
- the Integration Guide file or its listed plain PHP, Slim, Laravel, and template
  topics;
- the five roadmap-named example files; and
- the framework-neutral HTTP/response boundary.

## Decisions / Contracts

### Documentation and API contract

1. Phase 8 is documentation/example work. It must not change runtime behavior,
   public signatures, DTO shapes, builder contracts, renderers, validation issue
   taxonomy, or Composer dependencies.
2. New or revised snippets must use only APIs verified in the current `src/` tree.
   No method, class, framework adapter, or dependency may be invented for a guide
   example.
3. The library remains framework-neutral. Slim, Laravel, Twig, Blade, routing,
   controllers, HTTP responses, and persistence remain host responsibilities.
4. JSON-LD documentation must distinguish builder construction, structural
   validation, the Phase 13P deep semantic scope, and external Google/Merchant
   eligibility. Phase 8 must not claim semantic coverage outside the implemented
   contracts or eligibility guarantees.
5. Existing raw-array behavior and current example output are preserved. A guide
   correction must not silently prescribe a runtime normalization or migration.

### Example contract

1. Existing examples remain standalone Composer-autoloaded PHP scripts and continue
   to be directly executable with `php examples/<name>.php`.
2. A new Homepage guide snippet must be runnable with the current metadata DTO and
   renderer/builder APIs when extracted to a temporary script; it must not require a
   framework or external service.
3. The five existing Phase 8 examples are verification targets, not files to rewrite
   without a demonstrated mismatch.

### Phase boundary contract

1. Phase 8 does not add semantic validation, Google Rich Results validation, Merchant
   eligibility validation, provider tooling, or network calls.
2. Phase 8 does not update the roadmap status to `Complete` during Blueprint or
   implementation. Completion requires all lifecycle gates below.
3. Verification, Documentation Sweep, and Final Review are independent gates after
   the last implementation Work Unit. They are not implementation Work Units.

## Scope

Phase 8 includes only:

- completing the evidence-backed Homepage usage example in the Usage Guide;
- synchronizing the stale JSON-LD validation wording in the Usage Guide;
- verification that the existing Integration Guide and five named examples match the
  current public API and remain executable;
- the Phase 8 verification, documentation-sweep, and final-review records required
  by the lifecycle; and
- the documentation impact review for all applicable repository documentation
  layers.

## Out of Scope

- Any change under `src/**` or to runtime/public behavior.
- New builders, renderers, DTOs, validators, semantic catalogs, or framework
  integrations.
- Changes to the existing five Phase 8 examples unless a concrete API or execution
  mismatch is demonstrated.
- New Composer packages, framework dependencies, HTTP clients, network calls, or
  external verification providers.
- Google Rich Results, Merchant eligibility, Search Console, or ranking guarantees.
- Rewriting historical documents under `docs/SEO/v1/**` merely to make them match
  current APIs; those documents are historical and must be classified accordingly in
  the Documentation Sweep.
- Roadmap status changes before Final Review against the latest `main`.
- Any Phase 9+ implementation or unrelated documentation cleanup.

## Work Units

Each Work Unit PR must target `codex/phase-8-draft`, never `main`. A Work Unit is
accepted only when its declared scope and Done Criteria pass. Since 8B and 8C are
already present and no other implementation gap was found, one focused Work Unit is
sufficient for the remaining content gap.

### WU1 — Usage Guide completion and evidence-backed synchronization

**Scope**

- Add a dedicated Homepage SEO usage example to
  `docs/guides/USAGE_GUIDE.md` using only current public APIs.
- Correct the stale JSON-LD validation description in that guide to reflect the
  current structural and Phase 13P in-scope semantic behavior, while preserving the
  explicit limitation that this is not complete Schema.org or Google eligibility
  validation.
- Review the existing 8A and 8B guide snippets for direct contradictions with the
  current public API; change only evidence-backed contradictions.

**Expected files**

- `docs/guides/USAGE_GUIDE.md`

No runtime, test, example, Composer, CI, or roadmap file is expected in this Work
Unit. If a direct contradiction requires another documentation path, the PR must
justify that exact path in its description and remain within Phase 8 scope.

**Required verification**

- Execute the extracted Homepage snippet with the repository's current Composer
  autoloader and PHP runtime, without committing a temporary fixture.
- Execute all existing `examples/*.php` scripts.
- Run the repository syntax gate, PHPStan, focused structured-data gate, and all
  standalone tests.
- Check all links and referenced classes/methods in the changed guide against the
  repository.

**Dependencies**

- Depends on the public APIs already present on the Phase 8 umbrella baseline.
- No external service, framework, provider, or new dependency.

**Done Criteria**

- The Usage Guide has a clear Homepage example that can be executed with current
  APIs and states the host-owned rendering responsibility.
- The JSON-LD validation wording matches the actual validator boundary and makes no
  unsupported eligibility or complete-coverage claim.
- The existing Integration Guide and five named examples remain unchanged unless a
  concrete mismatch is proven.
- No runtime/public contract, test behavior, CI behavior, or roadmap status changes
  are included.

## Test Matrix

The following matrix is fixed for the Work Unit and later Verification Gate:

| Area | Required check | Expected contract |
| --- | --- | --- |
| Composer metadata | `composer validate --strict` | Pass with the existing package metadata and requirements |
| PHP syntax | `php -l` for every `*.php` under `src/`, `tests/`, and `examples/` | All files parse successfully; record the actual count |
| Static analysis | `vendor/bin/phpstan analyse` | No PHPStan errors |
| Structured data | `php tests/Phase21StructuredDataCiValidationTest.php` | Existing Phase 13P gate passes without semantic-scope changes |
| Standalone tests | `find tests -name '*Test.php' -print0 \| xargs -0 -n1 php` | Every current standalone test passes; record the actual count |
| Examples | `php examples/<file>.php` for every example | Every current example exits successfully; record the actual count |
| Homepage snippet | Execute the new guide snippet from a temporary file | Uses current APIs and exits successfully without a framework |
| Documentation diff | `git diff --check` and link/API/path review | No whitespace errors, broken local references, or unsupported API claims |
| CI | GitHub Actions on PHP `8.2`, `8.3`, and `8.4` | Existing matrix, syntax gate, PHPStan, focused gate, conditional PHPUnit, and standalone tests remain green |

## Documentation Impact

The final Documentation Sweep must review every path below and record exactly one
status: `updated`, `reviewed-no-change`, or `deferred-with-reason`. The table is the
planned baseline decision from the current inventory; the final sweep must update the
reason if an implementation or verification result changes it.

| Path | Planned status | Reason / expected action |
| --- | --- | --- |
| `README.md` | `reviewed-no-change` | Existing links and example inventory are accurate; no README claim is contradicted by the WU1 gap. |
| `docs/SEO_LIBRARY_REFERENCE.md` | `reviewed-no-change` | Current reference already documents the public APIs and Phase 13P boundaries. |
| `docs/guides/USAGE_GUIDE.md` | `updated` | Add the Homepage example and correct the stale JSON-LD validation wording. |
| `docs/guides/INTEGRATION_GUIDE.md` | `reviewed-no-change` | Current host/framework-neutral integration topics are present and use existing APIs. |
| `docs/SEO/**` | `reviewed-no-change` | Active architecture docs match current behavior; historical `v1` docs remain historical. |
| `docs/phases/**` | `deferred-with-reason` | Phase 8 record is a later Documentation Sweep/completion artifact, not a Blueprint or WU1 edit. |
| `docs/verification/**` | `deferred-with-reason` | Verification report is created only by the Verification Gate after implementation. |
| `examples/**` | `reviewed-no-change` | All five named Phase 8 examples exist and execute successfully; no concrete mismatch was found. |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | `reviewed-no-change` | No Phase 8 claim in this roadmap requires a change from the current evidence. |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | `deferred-with-reason` | Phase status must not change until Verification, Documentation Sweep, and Final Review pass. |
| `docs/blueprints/**` | `updated` | This Blueprint is the Phase 8 pre-implementation contract. |

The final sweep must not mark Phase 8 `Complete` merely because WU1 passes. It must
record limitations and deferred work, including the absence of Google/Merchant
eligibility validation and the intentionally historical status of `docs/SEO/v1/**`.

## Definition of Done

Phase 8 is not complete until all of the following are true:

1. The Draft Integration PR and Blueprint child PR follow the stack workflow, and
   every Work Unit PR targets `codex/phase-8-draft`.
2. WU1 adds the Homepage usage example and corrects only the evidence-backed stale
   validation wording.
3. The existing Integration Guide and five named examples remain accurate and
   executable; no unsupported APIs or framework dependencies are introduced.
4. The declared Test Matrix passes, with actual versions and counts recorded in the
   Verification report.
5. The Verification Gate records the exact Draft HEAD, commands, results, and any
   limitations without claiming checks that were not run.
6. The Documentation Sweep records one required status for every Documentation
   Impact path and synchronizes only paths that are actually stale.
7. A Final Review compares the complete Phase 8 diff against the latest `main` and
   confirms no runtime/public contract or scope bypass.
8. Only after Verification, Documentation Sweep, and Final Review pass may the
   Integration PR become Ready and be merged.
9. The roadmap is updated to `Complete` only after implementation, tests,
   verification, documentation synchronization, required examples, limitations,
   roadmap status, and final review are all complete.
