# Phase 21 — Quality / CI / Release Readiness Verification Report

## Verification status

**Final result: PASS**

This report records the Verification Gate for the Phase 21 integration branch
after WU1–WU4 were merged into the Draft integration branch. Verification was
read-only: no runtime, CI, public-contract, or test implementation was changed
to make a check pass.

| Item | Recorded value |
| --- | --- |
| Draft HEAD verified | `f4df8a3f30d4a8d0448baef3cad6360176e5bb0d` |
| Verification branch | `codex/phase-21-verification` |
| Verification scope | WU1, WU2, WU3, WU4 and the Phase 21 contracts in the merged Blueprint |
| PHP CLI | `PHP 8.5.9` |
| Composer | `Composer version 2.10.2` |
| Existing release tag observed | `v1.0.0-rc.1` |

The Draft HEAD above is the commit under verification. The later report commit
only adds this report and does not change the implementation being verified.

## Executed verification

### Repository and package checks

- `git rev-parse HEAD` → `f4df8a3f30d4a8d0448baef3cad6360176e5bb0d` — **PASS**.
- `php -v` → PHP `8.5.9` CLI — **PASS** for the local verification host.
- `composer --version` → Composer `2.10.2` — **PASS**.
- `composer validate --strict` → `./composer.json is valid` — **PASS**.
- Clean Composer installation was executed in an isolated temporary checkout
  created from the Draft HEAD with no use of the repository's current `vendor/`:

  ```sh
  composer install --prefer-dist --no-progress --no-interaction
  ```

  Composer resolved and installed `phpstan/phpstan 2.2.13`, generated the
  temporary checkout autoloader, and exited `0` — **PASS**. The generated lock
  file remained only in the temporary checkout; no lock file was added here.

### Syntax, static analysis, and standalone tests

- The existing syntax gate command was run with `php -l` over every `*.php` in
  `src/`, `tests/`, and `examples/` — **PASS**.
- PHP files examined: **229**.
- `vendor/bin/phpstan analyse` using `phpstan.neon` — **PASS**, no errors.
- Focused structured-data gate:

  ```sh
  php tests/Phase21StructuredDataCiValidationTest.php
  ```

  Result: `Phase 21 WU2 structured-data validation gate passed.` — **PASS**.
- Standalone test command, unchanged from the repository workflow:

  ```sh
  find tests -name '*Test.php' -print0 | xargs -0 -n1 php
  ```

  Tests discovered and executed: **49**. Result: **49/49 PASS**.
- `git diff --check` — **PASS**.

### Conditional PHPUnit behavior

The existing workflow still contains the conditional branch:

```sh
if [ -x "vendor/bin/phpunit" ]; then
  vendor/bin/phpunit
else
  echo "phpunit is not installed or not executable, skipping."
fi
```

The local checkout does not have an executable `vendor/bin/phpunit`, so the
existing behavior is to skip PHPUnit with that message. The conditional behavior
and workflow step were not changed — **PASS**.

## Phase 21 contract review

### WU1 — syntax gates

`.github/workflows/ci.yml` contains the additive `php -l` gate over `src/`,
`tests/`, and `examples/`. The existing PHP matrix remains `8.2`, `8.3`, and
`8.4`; the workflow also retains its existing triggers, Composer validation and
installation, PHPStan, conditional PHPUnit, and standalone-test steps — **PASS**.

### WU2 — structured-data CI gate

The workflow runs the focused gate through the existing
`SeoMetaValidator::validate()` pipeline and the existing report/exporter
contracts. The focused test covers:

- Product, Offer, AggregateOffer, and ProductGroup;
- JSON-LD root nodes, numeric node lists, `@graph`, and recursive graph nodes;
- existing invalid structure, type, property, and relationship issue cases;
- valid out-of-scope relationship targets;
- aliases `jsonLd`, `json_ld`, `schema`, and `schemas`; and
- deterministic issue codes and field paths in existing report JSON.

No new validator, DTO, score, issue taxonomy, report, batch, exporter, alias,
builder, or renderer contract was introduced — **PASS**.

### Runtime and public-contract compatibility

The complete Phase 21 diff against `github/main` contains only:

- `.github/workflows/ci.yml`;
- the Phase 21 Blueprint;
- the Phase 21 phase record;
- the WU4 release checklist; and
- `tests/Phase21StructuredDataCiValidationTest.php`.

No `src/` file changed. Therefore no runtime validation semantics, DTO,
scoring, issue taxonomy, exporter, builder, renderer, or other public runtime
contract changed in Phase 21 — **PASS**.

### WU3 — deferred external-verification boundary

WU3 is documentation-only. The Phase 21 record confirms that Phase 21 chooses no
provider and adds no provider SDK, `tools/` implementation, Composer dependency,
credentials, network call, or external-service workflow. Google Rich Results,
Merchant, and similar external verification remain a separate future decision;
their absence is not a Core Phase 21 gap — **PASS**.

### WU4 — release, tag, and package readiness

`docs/release/PHASE_21_RELEASE_READINESS_CHECKLIST.md` is present and covers the
required Composer, PHP matrix, syntax, PHPStan, standalone-test, structured-data,
examples, documentation, package, tag, post-tag, and recovery checks.

No tag, GitHub Release, package publication, release automation, or new version
file was created. The observed tag list still contains `v1.0.0-rc.1`. The
checklist keeps Git tags as the release source of truth and uses neutral wording
that follows the repository's approved tagging policy without imposing
lightweight or annotated tags — **PASS**.

## Limitations and non-claims

- Local verification ran on PHP `8.5.9`; supported Phase 21 CI compatibility is
  verified separately by the GitHub Actions matrix for PHP `8.2`, `8.3`, and
  `8.4`.
- PHPUnit was not installed or executable locally, so the existing conditional
  workflow correctly skipped it. No PHPUnit result is claimed.
- Phase 21 does not provide Google Rich Results eligibility, Merchant eligibility,
  Search Console verification, or any mandatory external-service integration.
- This report does not perform Documentation Sweep or Final Review; those remain
  subsequent Phase gates.

## GitHub Actions verification

This section is completed after the Verification PR is opened and its workflow
checks finish. It must record the actual workflow run URLs and results for PHP
`8.2`, `8.3`, and `8.4`, including the syntax gate, PHPStan, structured-data
gate, conditional PHPUnit behavior, and standalone tests.

**Pending at initial report creation.**
