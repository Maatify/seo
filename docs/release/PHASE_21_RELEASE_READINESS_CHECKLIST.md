# Phase 21 — Release, Tag, and Package Readiness Checklist

## Purpose and boundaries

This checklist is the WU4 release-readiness aid for `maatify/seo`. It collects
repeatable evidence for a maintainer; it does not create a release, publish a
package, change versioning policy, or replace the Verification, Documentation
Sweep, or Final Review gates.

Git tags are the release source of truth. The repository must not introduce a
second version file, infer a release version from generated metadata, or move an
existing published tag. No item below authorizes creating a tag, GitHub Release,
package publication, release automation, provider integration, or external
network verification.

Record the commit, command, tool version, date, and result for every executed
item. An unchecked or failed required item blocks tagging and publication until
the responsible maintainer resolves it in an explicitly scoped change.

## Current repository facts

These facts are the baseline this checklist must continue to match:

- Composer package: `maatify/seo` (`type: library`, MIT license).
- Runtime requirement: PHP `>=8.2` and `ext-xmlwriter`.
- Development dependency: PHPStan `^2.2`; PHPUnit is not a required Composer
  dependency.
- Static-analysis configuration: `phpstan.neon`, level `max`, path `src`.
- Supported CI matrix: PHP `8.2`, `8.3`, and `8.4`.
- Existing CI workflow: `.github/workflows/ci.yml`; it has no release or publish
  workflow.
- Existing release history includes Git tag `v1.0.0-rc.1`.
- No committed `composer.lock` is present at this baseline; clean installation
  checks must therefore record the dependency resolution performed by Composer.
- The structured-data CI gate uses the existing validation pipeline and does not
  add Google Rich Results or Merchant eligibility findings.

If any fact changes, update the checklist evidence or the appropriate
documentation during the Documentation Sweep; do not silently change this WU4
scope.

## 1. Preflight and scope safety

- [ ] Confirm the release candidate is the intended commit on the approved
  integration branch, and record `git rev-parse HEAD`.
- [ ] Fetch the relevant remotes and confirm the candidate is based on the
  reviewed integration branch and the latest approved `main`.
- [ ] Confirm `git status --short` is empty before tagging or publishing.
- [ ] Run `git diff --check`.
- [ ] Review the final diff for accidental changes to runtime code, public
  contracts, CI behavior outside the approved Phase 21 gates, or versioning
  policy.
- [ ] Confirm Verification, Documentation Sweep, and Final Review have passed
  separately. This checklist does not substitute for those gates.

## 2. Composer metadata and package requirements

- [ ] Run `composer validate --strict` from the candidate checkout.
- [ ] Review `composer.json` and record the package name, package type, license,
  autoload mapping, PHP requirement, `ext-xmlwriter` requirement, development
  dependencies, and optional suggestions.
- [ ] Confirm the package has no new version file and that Git tags remain the
  only release-version source of truth.
- [ ] Confirm no dependency was added solely for release automation, external
  verification, Google, Merchant, Search Console, or Rich Results checks.
- [ ] Confirm the package remains framework-agnostic and has no mandatory
  external-service or credential requirement.

## 3. Clean Composer installation and usage

- [ ] In a clean checkout or isolated temporary copy, run:

  ```sh
  composer install --prefer-dist --no-progress --no-interaction
  ```

  Record the PHP version, Composer version, resolved dependencies, and result.
- [ ] Confirm installation succeeds using the committed `composer.json` and
  does not depend on an existing local `vendor/` directory.
- [ ] Confirm the PSR-4 autoload mapping resolves `Maatify\\Seo\\` from `src/`.
- [ ] Confirm a consumer can require the package as a Composer library without a
  framework or an external verification service.
- [ ] Do not commit a generated lockfile or other generated installation output
  unless a separate, explicit repository policy requires it.

## 4. PHP compatibility matrix

- [ ] Verify the complete GitHub Actions matrix on PHP `8.2`.
- [ ] Verify the complete GitHub Actions matrix on PHP `8.3`.
- [ ] Verify the complete GitHub Actions matrix on PHP `8.4`.
- [ ] Record the workflow run URL and commit SHA for each matrix result.
- [ ] Confirm the existing triggers, `xmlwriter` setup, dependency installation,
  conditional PHPUnit behavior, PHPStan step, and standalone test step remain
  present.

## 5. Static analysis, syntax, and tests

- [ ] Run PHP syntax checks over every PHP file under all three required paths:

  ```sh
  find src tests examples -type f -name '*.php' -print0 \
    | sort -z \
    | while IFS= read -r -d '' file; do php -l "$file" || exit 1; done
  ```

  A failure must be non-zero and identify the file reported by `php -l`.
- [ ] Run `vendor/bin/phpstan analyse`.
- [ ] Run every standalone test using the repository convention:

  ```sh
  find tests -name '*Test.php' -print0 | xargs -0 -n1 php
  ```

- [ ] Record the exact number of standalone tests discovered and the result of
  each invocation.
- [ ] Confirm the conditional `vendor/bin/phpunit` behavior remains unchanged:
  PHPUnit runs when an executable binary is installed and is otherwise skipped
  with the existing message.

## 6. Structured-data CI gate and examples

- [ ] Run the focused structured-data gate:

  ```sh
  php tests/Phase21StructuredDataCiValidationTest.php
  ```

  Success must exit `0`. Failure must exit non-zero and identify the existing
  issue code and deterministic field path.
- [ ] Confirm the gate exercises the current contracts for Product, Offer,
  AggregateOffer, ProductGroup, root nodes, numeric node lists, `@graph`,
  recursive graph nodes, invalid structure/type/property/relationship cases,
  valid out-of-scope relationship targets, and the supported aliases
  `jsonLd`, `json_ld`, `schema`, and `schemas`.
- [ ] Confirm structured-data output uses existing DTO/report/exporter contracts
  and does not add Google, Rich Results, or Merchant eligibility findings.
- [ ] Run the syntax gate for every file under `examples/` as part of the command
  in Section 5 and record that result.
- [ ] Run an example end-to-end smoke check only when its dependencies and
  side-effect profile are understood. Do not claim runtime example coverage
  when only syntax was verified; record `reviewed-no-change` or a limitation in
  the later Documentation Sweep instead.

## 7. Documentation synchronization

- [ ] During the Documentation Sweep, review the repository paths required by
  the Phase Execution Standard and record exactly one status per path:
  `updated`, `reviewed-no-change`, or `deferred-with-reason`.
- [ ] Confirm user-facing documentation describes the existing validation entry
  points and CI gates without claiming complete Schema.org validation, Google
  Rich Results eligibility, or Merchant eligibility.
- [ ] Confirm phase and verification documentation records actual commands,
  versions, counts, limitations, and deferred work rather than planned results.
- [ ] Confirm examples and release instructions match the package metadata and
  supported PHP versions.
- [ ] Do not mark the Phase complete, or the integration PR Ready, until the
  Documentation Sweep and Final Review have passed.

## 8. Git tag preparation (maintainer action after all gates)

- [ ] Confirm all implementation, Verification, Documentation Sweep, and Final
  Review approvals are complete on the intended integration commit.
- [ ] Confirm the working tree is clean and `git diff --check` passes.
- [ ] Confirm the intended tag name follows the existing versioning policy and
  does not reuse or move an existing tag.
- [ ] Confirm `composer validate --strict`, clean installation, the PHP matrix,
  syntax gates, PHPStan, standalone tests, structured-data gate, examples review,
  and documentation synchronization are all recorded as passing or explicitly
  accepted according to repository policy.
- [ ] Confirm no tag, GitHub Release, package publication, or release automation
  is created by WU4 itself.
- [ ] When a separately authorized release action is performed, create the
  annotated Git tag on the approved commit. The tag, not a new version file, is
  the release source of truth.

## 9. Post-tag verification (separate authorized release action)

- [ ] Verify the tag resolves to the approved release commit with
  `git show --no-patch <tag>`.
- [ ] Verify the remote tag points to the same commit with
  `git ls-remote --tags <remote> <tag>`.
- [ ] Verify a clean checkout at the tag can install the package with Composer
  and passes the applicable syntax, PHPStan, standalone-test, and structured-data
  checks.
- [ ] Verify package metadata and documented requirements at the tag match the
  release candidate.
- [ ] If a GitHub Release or package publication is separately authorized,
  record its external result independently; it is not a Phase 21 library
  validation result and must not be added to the library DTOs or scores.

## 10. Rollback and recovery

- [ ] Before publishing a tag, preserve the approved commit SHA, branch state,
  CI run URLs, and verification evidence.
- [ ] If a pre-publication check fails, stop the release action and restore the
  workspace to the last known-good commit without rewriting shared history.
- [ ] After a tag has been published, never move or force-update that tag. Use
  the repository's separately approved corrective release policy and a new tag
  when correction is required.
- [ ] For a failed package or external release action, record the provider-side
  recovery result separately; do not represent it as a core `SeoValidationResultDTO`
  finding or alter library scoring to model it.
- [ ] Confirm the last known-good Git tag and commit can be checked out and
  revalidated with the applicable Composer and test gates.

## WU4 completion record

- [ ] The checklist is the only WU4 file change unless the Blueprint explicitly
  requires an additional documentation update.
- [ ] No runtime, CI, public-contract, dependency, versioning, tag, release, or
  publish behavior was added by WU4.
- [ ] Verification, Documentation Sweep, and Final Review remain separate,
  subsequent gates.
