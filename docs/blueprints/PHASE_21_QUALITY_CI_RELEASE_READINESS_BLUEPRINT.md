# Phase 21 — Quality / CI / Release Readiness Blueprint

## Blueprint status

This document is the pre-implementation Blueprint for Phase 21. It follows the
repository's mandatory Phase Execution Standard and is intentionally limited to
contracts, scope, evidence, Work Units, tests, documentation impact, and Done
Criteria. It does not change runtime code, CI configuration, public contracts, or
release behavior.

The Blueprint was prepared from the latest verified `main` baseline:

`228ad2cdc026e0148e934526cc35068b50bf5948`

Phase lifecycle:

`Draft Integration PR → Blueprint → Work Units → Verification → Documentation Sweep → Final Review vs latest main → Ready → Merge`

Verification and Documentation Sweep are gates after the last implementation Work
Unit. They are not Work Units.

## Current State

### Repository and package baseline

- The package is framework-agnostic PHP and requires PHP `>=8.2` and
  `ext-xmlwriter` according to `composer.json`.
- Development dependencies currently include PHPStan `^2.2`; PHPUnit is not a
  required package dependency.
- `phpstan.neon` runs at level `max` against `src` only.
- The repository contains standalone PHP tests under `tests/` and executable PHP
  examples under `examples/`.
- Release/version history is represented by Git tags; the repository currently
  contains the existing `v1.0.0-rc.1` tag. No automatic tag or package-publish
  workflow is currently present.

### Existing CI

The only current workflow is `.github/workflows/ci.yml` with:

- `push` and `pull_request` triggers for all branches.
- A `build-and-test` job on `ubuntu-latest`.
- A PHP matrix of `8.2`, `8.3`, and `8.4`.
- `xmlwriter` enabled through `shivammathur/setup-php`.
- `composer validate --strict`.
- `composer install --prefer-dist --no-progress --no-interaction`.
- `vendor/bin/phpstan analyse`.
- Conditional `vendor/bin/phpunit` execution when an executable PHPUnit binary is
  available; otherwise the step reports that PHPUnit is skipped.
- Maatify standalone tests through
  `find tests -name '*Test.php' -print0 | xargs -0 -n1 php`.

The current workflow does not explicitly run `php -l` over `src/`, `tests/`, or
`examples/`, does not have a separate examples syntax gate, and does not expose a
dedicated structured-data validation result or artifact.

### Existing structured-data validation

- `SeoMetaValidator::validate()` is the public validation entry point and returns
  the existing `SeoValidationResultDTO`.
- JSON-LD is accepted through the existing `jsonLd`, `json_ld`, `schema`, and
  `schemas` aliases.
- The current validation foundation and Phase 13P pipeline already support
  structural JSON-LD validation, `@graph`, deterministic issue fields, and deep
  semantic validation limited to `Product`, `Offer`, `AggregateOffer`, and
  `ProductGroup`.
- `SeoValidationReportBuilder`, `SeoValidationBatchReportBuilder`, and the array,
  JSON, summary, and Markdown exporters already provide reusable report output.
- Existing public DTO shapes, issue codes, scoring, warnings, summaries, batch
  aggregation, and exporter contracts are part of the compatibility baseline.
- No CI-specific structured-data command, fixture suite, or optional external
  Google/Merchant verification workflow is currently present.

## Gaps

Phase 21 addresses only the following evidence-based gaps:

1. The existing PHP matrix does not have explicit syntax gates for every PHP file
   under `src/`, `tests/`, and `examples/`.
2. CI runs the complete standalone test suite but does not identify a dedicated
   structured-data validation gate with focused, CI-readable results.
3. Structured-data validation output is available through library DTOs/exporters,
   but CI has no stable, documented machine-readable success/failure presentation
   for that gate.
4. Phase 21 requires documentation of a clear boundary between core structured-data
   validation and any future external Google Rich Results or Merchant verification.
   The current absence of provider integration is intentional and is not a gap in
   the Phase 21 core scope.
5. Release, Git tag, and package-usage readiness checks are documented in audits
   and historical reports but are not consolidated into a Phase 21 checklist.

No gap in this section authorizes replacing the existing CI, changing the PHP
matrix, changing runtime validation semantics, or adding a mandatory external
service.

## Decisions / Contracts

### CI compatibility

1. The existing workflow remains the compatibility baseline. `composer validate
   --strict`, dependency installation, PHPStan, conditional PHPUnit behavior, and
   the standalone test command must remain available unless a Work Unit explicitly
   documents an equivalent additive change.
2. The PHP matrix remains `8.2`, `8.3`, and `8.4`. Phase 21 does not raise the
   minimum PHP version, remove a supported matrix version, or add framework-specific
   jobs.
3. CI remains framework-neutral and host-agnostic. Gates operate on repository
   files and public library APIs only.
4. Syntax gates must be deterministic, fail the job on the first syntax failure,
   and cover every `*.php` file under `src/`, `tests/`, and `examples/`. They must
   not replace the standalone test execution.

### Structured-data CI contract

1. The structured-data gate reuses `SeoMetaValidator::validate()` and the existing
   Phase 13P result/report/exporter contracts. It does not add a second validator,
   duplicate semantic catalogs, or change public DTO shapes.
2. The gate must cover the current structural and semantic boundary: JSON-LD root
   nodes, numeric node lists, `@graph` wrappers and recursive nodes, malformed
   structure/type cases, the four deep-validation types (`Product`, `Offer`,
   `AggregateOffer`, `ProductGroup`), and valid out-of-scope relationship targets
   allowed by the existing contracts.
3. The gate must exercise the existing JSON-LD aliases where the current pipeline
   supports them: `jsonLd`, `json_ld`, `schema`, and `schemas`.
4. A passing structured-data gate exits with status `0`. A failed assertion or
   malformed fixture exits non-zero and identifies the deterministic issue code and
   field path. The CI presentation must be stable and readable in logs; when JSON
   output is emitted, it must use existing report/exporter shapes rather than a new
   public result schema.
5. CI structured-data findings remain ordinary library validation findings. No
   Google Rich Results or Merchant eligibility finding may be added to
   `SeoValidationResultDTO`, scores, summaries, batches, or exporters by Phase 21.

### Optional external verification contract

1. Phase 21 does not select, add, vendor, or invoke a Google, Rich Results,
   Merchant, Search Console, or other external provider/API.
2. WU3 is limited to documenting an optional external-verification boundary and
   explicitly deferring any executable integration. WU3 must not create `tools/`,
   add a provider SDK, make network calls, or add a workflow that invokes an
   external service.
3. The deferred boundary is not a missing core Phase 21 capability. It records the
   separation between the library's validation and any future provider-specific
   verification.
4. Any actual Google/Rich Results/Merchant implementation requires a separate
   maintainer decision and follow-up scope after Phase 21. It must not be inferred
   from this Blueprint or implemented under WU3.
5. Future external results, if separately approved, must remain outside the
   library's `SeoValidationResultDTO`, scores, summaries, batches, and exporters,
   and must not be described as Schema.org semantic validation or eligibility
   guarantees.

### Release and package readiness contract

1. Release readiness is a checklist and verification concern, not an automatic
   publish action in this Phase.
2. The checklist must cover package metadata, supported PHP and extension
   requirements, Composer validation/install, PHPStan, standalone tests, syntax
   gates, examples, documentation synchronization, Git tag preparation, and
   rollback/verification notes.
3. Git tags remain the release source of truth. Phase 21 must not introduce a
   second version file or silently change the project's versioning policy.
4. No Work Unit may publish a package, create a release tag, or change public API
   compatibility without an explicitly scoped follow-up request.

## Scope

Phase 21 includes:

- Explicit syntax gates for `src/`, `tests/`, and `examples/` in CI.
- A CI-integrated structured-data validation gate using the existing public
  validation pipeline and Phase 13P contracts.
- Deterministic, CI-friendly structured-data pass/fail output using existing
  DTO/report/exporter contracts where applicable.
- Documentation of a deferred, optional external-verification boundary for Google
  Rich Results and Merchant verification; no provider integration is implemented
  in Phase 21.
- Release, Git tag, and package-usage readiness checklists.
- The tests and documentation necessary to prove these additions without changing
  runtime behavior.

## Out of Scope

- Runtime SEO or JSON-LD behavior changes unrelated to making existing validation
  observable in CI.
- New Schema.org semantic catalogs, new relationship ranges, or deep validation for
  types outside `Product`, `Offer`, `AggregateOffer`, and `ProductGroup`.
- Changes to `SeoValidationResultDTO`, report DTOs, score behavior, warning policy,
  issue taxonomy, aliases, builders, renderers, or existing public entry points.
- Mandatory Google, Search Console, Merchant Center, Rich Results, or other
  external-service dependencies.
- Claims of Google eligibility, Merchant eligibility, ranking impact, or complete
  Schema.org coverage.
- Framework adapters, application-specific deployment logic, hosted CI services
  beyond the existing GitHub Actions workflow, or a replacement CI platform.
- Automatic package publishing, automatic Git tag creation, version-file
  introduction, or release automation beyond explicitly documented optional
  tooling.
- Performance benchmarking, security scanning, database work, unrelated test
  cleanup, or changes to phases outside Phase 21.

## Work Units

Each Work Unit PR must target `codex/phase-21-draft`, never `main`. A Work Unit is
accepted only when its Done Criteria and required tests pass. Verification,
Documentation Sweep, and Final Review are subsequent gates, not Work Units.

### WU1 — CI baseline and syntax gates

**Scope**

Add explicit, additive PHP syntax checks for all `*.php` files under `src/`,
`tests/`, and `examples/`, while preserving the current workflow triggers, PHP
matrix, Composer gates, PHPStan gate, conditional PHPUnit behavior, and standalone
test execution.

**Expected files**

- `.github/workflows/ci.yml`
- Focused CI/configuration test or verification evidence only if required by the
  repository's existing test style; no runtime source file.

**Required tests**

- Run the syntax command over all three directories on each supported PHP matrix
  entry.
- Confirm a deliberate syntax failure returns non-zero and identifies its file in
  the focused gate design, without committing a failing fixture.
- Run the existing PHPStan and standalone test commands unchanged.

**Dependencies**

- Starts from the existing CI workflow and requires no new package dependency.

**Done Criteria**

- Every PHP file in `src/`, `tests/`, and `examples/` is covered explicitly.
- The existing CI gates and PHP matrix remain present and green.
- No runtime/public contract changes are included.

### WU2 — Structured-data CI validation and output

**Scope**

Integrate a focused structured-data gate into CI using the existing
`SeoMetaValidator`, Phase 13P contracts, and existing report/exporter behavior.
Make failures deterministic and useful in CI logs without adding a new public
validation API or changing runtime semantics.

**Expected files**

- `.github/workflows/ci.yml`
- A focused standalone test/fixture under `tests/` for the Phase 21 CI gate, using
  the repository's existing standalone PHP test convention.
- Documentation for the CI output only if needed by the final Documentation
  Impact Review.

**Required tests**

- Valid Product, Offer, AggregateOffer, and ProductGroup payloads.
- Valid graph wrapper and recursive graph nodes.
- Existing invalid structural/type/property/relationship issue codes and
  deterministic field paths.
- Valid out-of-scope relationship targets remain allowed according to Phase 13P.
- All four JSON-LD metadata aliases supported by the pipeline.
- Stable success output, non-zero failure behavior, and report/JSON output where
  the existing exporter contract is used.
- Regression execution of all current standalone tests.

**Dependencies**

- Depends on WU1's additive CI syntax gate.
- Must use the existing Phase 13P implementation; no semantic catalog expansion.

**Done Criteria**

- CI visibly and deterministically validates the structured-data gate.
- Failure output identifies the existing issue code and field path.
- The gate does not add Google/Merchant findings or change DTO, score, report,
  batch, exporter, alias, builder, or renderer contracts.

### WU3 — Deferred external-verification boundary documentation

**Scope**

Document the boundary between core Phase 21 validation and any future optional
Google/Rich Results/Merchant verification. This Work Unit is documentation-only.
It explicitly defers provider selection and executable integration to a separate
future decision.

**Expected files**

- `docs/phases/PHASE_21_QUALITY_CI_RELEASE_READINESS.md`.
- Applicable user-facing documentation only when the Documentation Impact Review
  proves that the deferred boundary changes integration guidance.
- No `.github/**` workflow, `tools/**` entry point, provider SDK, Composer
  dependency, runtime namespace, or network implementation.

**Required tests**

- Documentation review confirms that no provider, SDK, network call, or external
  service workflow was added.
- `git diff --check` and the repository's documentation review pass.
- No external network call or provider credential is used by this Work Unit.

**Dependencies**

- Depends only on the WU2 CI output contract as context for documenting the
  boundary; it has no executable dependency.

**Done Criteria**

- The Phase 21 documentation explicitly records external verification as deferred
  optional integration, not as a core Phase 21 gap.
- The documentation states that Phase 21 chooses no provider and adds no SDK,
  `tools/` code, network implementation, or external-service workflow.
- The documentation requires a separate maintainer decision before any actual
  Google/Rich Results/Merchant implementation.

### WU4 — Release, tag, and package readiness checklists

**Scope**

Consolidate repeatable release-readiness checks for this package without publishing,
tagging, or changing the package's versioning policy.

**Expected files**

- `docs/release/PHASE_21_RELEASE_READINESS_CHECKLIST.md`.
- Applicable README, guide, reference, phase, verification, or roadmap files only
  when the Documentation Impact Review proves an update is needed.

**Required tests**

- Checklist evidence covers `composer validate --strict`, dependency installation,
  PHP matrix compatibility, PHPStan, standalone tests, syntax gates, examples,
  structured-data CI output, documentation review, and package metadata.
- Tag checklist confirms the intended Git tag is the source of truth and records
  pre-tag, post-tag, and rollback verification steps.
- Package-usage checklist confirms installability from Composer metadata without
  requiring a framework or external service.

**Dependencies**

- Depends on the final WU1–WU3 contracts and their verification evidence.

**Done Criteria**

- A maintainer can follow the checklists without making undocumented architectural
  decisions.
- No release or tag is created by the Work Unit itself.
- Checklist claims match the actual repository and CI behavior.

## Test Matrix

The final Phase test matrix must preserve the existing baseline and add only the
declared Phase 21 gates:

| Area | Required coverage | Expected result |
| --- | --- | --- |
| Composer | `composer validate --strict` and clean dependency installation | Pass on PHP 8.2, 8.3, and 8.4 |
| Syntax | `php -l` for every PHP file under `src/`, `tests/`, and `examples/` | No syntax errors; failing file is identified |
| Static analysis | `vendor/bin/phpstan analyse` using `phpstan.neon` | Existing level-max analysis remains clean |
| Standalone tests | Every `tests/*Test.php` script using the existing invocation convention | All current tests pass |
| Existing PHPUnit behavior | Conditional `vendor/bin/phpunit` behavior | Runs when available; otherwise remains safely skipped |
| Structured-data valid cases | Product, Offer, AggregateOffer, ProductGroup, graph, recursion, aliases | Pass with no unexpected issues |
| Structured-data invalid cases | Existing structural/type/property/relationship failures | Non-zero gate with deterministic code and field path |
| Output compatibility | Existing result/report/batch/exporter shapes and JSON/Markdown output | No public contract or scoring regression |
| Deferred external-verification boundary | Documentation-only review; no provider, SDK, network call, or external-service workflow | Deferred explicitly; core CI and runtime remain unchanged |
| Release readiness | Package metadata, requirements, docs, examples, tags, and rollback checklist | Evidence is complete and repository-grounded |

The matrix is executed through the CI matrix where applicable and is supplemented
by the Verification Gate after the last implementation Work Unit. Verification
must record the actual commands, versions, test count, and results; it must not
silently repair failures.

## Documentation Impact

The final Documentation Sweep must review every path below and record exactly one
status per path: `updated`, `reviewed-no-change`, or `deferred-with-reason`. The
Blueprint does not pre-claim a final status; the status must reflect the files and
behavior that actually ship.

| Path | Review decision required for Phase 21 |
| --- | --- |
| `README.md` | Confirm CI, validation, examples, and release claims remain accurate; update only if user-facing behavior changes. |
| `docs/SEO_LIBRARY_REFERENCE.md` | Confirm validation entry points, structured-data scope, and output contracts; update if CI-facing usage is documented there. |
| `docs/guides/USAGE_GUIDE.md` | Review examples and validation usage; update only for changed user-facing commands or workflows. |
| `docs/guides/INTEGRATION_GUIDE.md` | Review only if CI integration or optional external tooling changes host integration/usage. |
| `docs/SEO/**` | Review engineering and structured-data guidance for unsupported claims; update only where Phase 21 changes guidance. |
| `docs/phases/**` | Create/update the Phase 21 implementation record after implementation and verification. |
| `docs/verification/**` | Add the Phase 21 Verification Gate report after implementation; do not use the Blueprint as verification evidence. |
| `examples/**` | Review syntax-gated examples and add/update only if a Phase 21 example is required by the final scope. |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | Review for any status or roadmap wording affected by Phase 21; do not mark Complete before all gates. |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | Review the existing Phase 21 wording and update status only after implementation, verification, sweep, and final review. |
| `docs/blueprints/**` | Preserve this Blueprint and record any approved contract correction in the same Blueprint lineage; do not modify it during implementation without a documented defect. |

The Documentation Sweep is a separate gate and must also record limitations,
deferred optional integrations, and the fact that Google Rich Results/Merchant
eligibility is not a mandatory library capability.

## Definition of Done

Phase 21 may be marked Complete only when all of the following are true:

1. A Draft umbrella branch and Draft Integration PR exist from the verified latest
   `main`; every Work Unit PR targets that umbrella branch.
2. WU1–WU4 are implemented within their declared scopes and merged into the
   umbrella branch. No Work Unit is merged directly into `main`.
3. The existing PHP matrix, Composer gates, PHPStan, conditional PHPUnit behavior,
   standalone tests, public DTOs, validation aliases, and report/exporter contracts
   remain compatible.
4. Explicit syntax gates cover `src/`, `tests/`, and `examples/`.
5. Structured-data CI validation covers the existing Phase 13P boundary and emits
   deterministic CI-friendly results without adding semantic scope or eligibility
   findings.
6. WU3 documents the deferred optional external-verification boundary. No provider,
   SDK, `tools/` implementation, network call, or external-service workflow is
   introduced by Phase 21; any actual integration requires a separate decision.
7. Release, tag, and package-usage readiness checklists are complete and match
   repository reality; no automatic publish/tag action is performed unintentionally.
8. The Verification Gate passes and records the actual PHP matrix, syntax gates,
   structured-data CI gate, PHPStan, standalone tests, and any documentation
   verification required for WU3.
9. The Documentation Sweep reviews every required path with
   `updated`, `reviewed-no-change`, or `deferred-with-reason` and synchronizes any
   affected docs and examples.
10. Final Review compares the complete Phase 21 result against the latest `main` and
    confirms no scope bypass, unsupported claims, or unintended public behavior
    changes.
11. Only after Verification, Documentation Sweep, and Final Review pass is the
    Integration PR converted from Draft to Ready and merged into `main`.

## Explicit non-claims

This Blueprint does not claim that the current repository already has the Phase 21
gates. It does not claim Google Rich Results eligibility, Merchant eligibility,
complete Schema.org validation, automatic package release, or a mandatory external
verification service. Any future provider-specific result requires a separate
decision and remains outside the Phase 21 core library contract.
