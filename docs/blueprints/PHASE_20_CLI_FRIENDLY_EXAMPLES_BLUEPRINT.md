# Phase 20 — CLI-Friendly Examples Blueprint

## Current State

Phase 20 is an examples-only phase. The current integration Draft is
`codex/phase-20-draft` at baseline `3d60cfda406b959e06df34bffa9a798a6543dd16`.
This Blueprint defines the implementation contract; it does not implement any
example or runtime behavior.

The repository already contains the following CLI-oriented examples:

- `examples/sitemap-output.php` constructs and prints sample sitemap output,
  including URL-set and sitemap-index rendering.
- `examples/schema-output.php` constructs and prints JSON-LD schema output,
  including DTO and raw-array inputs.
- `examples/product-page-seo.php` builds Product SEO metadata and renders the
  resulting product-page HTML, but it does not run a Product SEO audit.

The relevant runtime contracts already exist. In particular, the repository
provides `RobotsTxtRenderer` with `RobotsTxtDTO` and `RobotsRuleDTO`, the SEO
validation pipeline (`SeoMetaValidator`, `SeoValidationReportBuilder`,
`SeoValidationScoreCalculator`, and `SeoValidationReportExporter`), and the
structured-data validation path used for Product JSON-LD.

## Gaps

The current examples set has these Phase 20 gaps:

- There is no standalone example dedicated to generating `robots.txt`.
- There is no standalone example dedicated to page SEO validation and a
  human-readable validation result.
- There is no standalone Product SEO audit example. The existing product-page
  example demonstrates rendering only, which is not an audit.

Sitemap generation and JSON-LD schema printing are already represented by
existing examples. They are therefore not new Work Units in Phase 20.

## Decisions / Contracts

- Phase 20 is examples-only. No runtime implementation is included.
- No public API, DTO, validator, renderer, score, report, or structured-data
  contract may be added or changed by Phase 20.
- Examples must use the repository's existing Composer/autoload conventions
  and remain runnable directly from the command line with PHP.
- No framework, application bootstrap, CLI package, network service, database,
  filesystem persistence, or other external dependency may be introduced.
- Output must be deterministic and understandable from a terminal. Each
  example must make the contract it demonstrates visible in its output.
- Every implementation Work Unit targets `codex/phase-20-draft`; no child Work
  Unit targets `main` directly.
- Verification, Documentation Sweep, and Final Review are later lifecycle
  gates. They are not Phase 20 Work Units.

## Scope

Phase 20 adds exactly three standalone examples:

1. a `robots.txt` generation example;
2. a page SEO validation example; and
3. a Product SEO audit example.

The existing sitemap and schema examples remain the source of truth for those
already-covered demonstrations and are not rewritten as part of this scope.

## Out of Scope

- Any change under `src/` or any runtime behavior change.
- New or changed public APIs, DTOs, validators, renderers, score contracts, or
  report contracts.
- A CLI framework, command-line package, or application-specific command.
- Routing, HTTP responses, persistence, network calls, database access, or
  framework integration.
- Replacing or expanding the existing sitemap, schema, or product-page render
  examples.
- Making Phase 20 complete in the same step as this Blueprint.
- Verification, Documentation Sweep, Final Review, or integration into `main`.

## Work Units

### WU1 — Robots.txt CLI-Friendly Example

Expected file: `examples/robots-output.php`

The example must construct a representative `RobotsRuleDTO` and
`RobotsTxtDTO`, pass them to the existing `RobotsTxtRenderer`, and print the
result. The demonstration should make representative user-agent, allow,
disallow, crawl-delay, comment, and sitemap directives visible where those
fields are used.

Acceptance requirements:

- The file runs directly from the command line and exits successfully.
- It uses only the existing `RobotsTxtRenderer`, `RobotsTxtDTO`, and
  `RobotsRuleDTO` contracts.
- The output is valid renderer output and is easy to inspect in a terminal.
- No CLI package, runtime change, or public API change is introduced.

### WU2 — Page SEO Validation Example

Expected file: `examples/seo-validation.php`

The example must provide representative page metadata to the existing
validation/reporting pipeline and print a readable result. It should use
`SeoValidationReportBuilder` together with the existing score and export
contracts, such as `SeoValidationReportExporter`, rather than implementing a
new validator or result object.

Acceptance requirements:

- The file runs directly from the command line and exits successfully.
- The output exposes the validation status, score/grade, and actionable
  findings using the existing report contracts.
- Validation and scoring are performed by the current library pipeline.
- No validator, result contract, public API, runtime, or CLI dependency is
  introduced.

### WU3 — Product SEO Audit Example

Expected file: `examples/product-seo-audit.php`

The example must perform a real audit of Product SEO data. It should compose
page metadata with a Product JSON-LD payload using the existing metadata and
product-schema contracts, then pass that input through the existing
meta-validation/reporting pipeline. Where Product structured data is present,
the existing structured-data validation path must be exercised. The output
must present the resulting report, not merely render product HTML.

Acceptance requirements:

- The file runs directly from the command line and exits successfully.
- It invokes existing validation/reporting and scoring contracts and exposes
  a human-readable audit result.
- Product JSON-LD is audited through the existing structured-data validation
  integration where applicable.
- Rendering HTML may be shown only as optional context; rendering alone does
  not satisfy this Work Unit.
- No Product contract, validator, result contract, runtime behavior, or public
  API is changed.

## Test Matrix

Each Work Unit must be checked as a standalone command-line program on the
supported PHP versions:

| Area | Required check | Expected result |
| --- | --- | --- |
| WU1 | `php examples/robots-output.php` | Exit `0`; visible `User-agent`, rule directives, and sitemap output where configured |
| WU2 | `php examples/seo-validation.php` | Exit `0`; visible validation status, score/grade, and findings |
| WU3 | `php examples/product-seo-audit.php` | Exit `0`; visible Product audit report and score/grade with structured-data findings evaluated where applicable |
| Existing examples | `php examples/sitemap-output.php`, `php examples/schema-output.php`, and `php examples/product-page-seo.php` | Continue to run successfully without modification |
| Syntax | PHP lint for all examples | PASS |
| Regression | Existing standalone tests and relevant validation/structured-data test suites | PASS |
| Static analysis | Repository PHPStan gate | PASS |
| Formatting | `git diff --check` | PASS |

The implementation PRs may add only the expected example files for their Work
Units. Tests, runtime files, and unrelated examples remain outside this
Blueprint's implementation scope unless a later review explicitly identifies
a necessary repository convention that is already supported by the phase
contract.

## Documentation Impact

The three new examples are themselves the Phase 20 user-facing documentation
for command-line usage. Their output and comments must identify the existing
contracts being demonstrated without duplicating or changing runtime API
documentation.

The existing sitemap, schema, and product-page examples require no
synchronization for this Blueprint because their current coverage is already
established. Any broader documentation inventory or synchronization is a
later Documentation Sweep gate, not a Work Unit here.

## Definition of Done

Phase 20's implementation is technically complete only when all three Work
Units are implemented in their expected example files and satisfy the
following conditions:

- Every new example is standalone and command-line runnable.
- WU1 uses the existing robots DTOs and renderer to produce sample
  `robots.txt` output.
- WU2 uses the existing validation, report, score, and export contracts to
  show a page SEO validation result.
- WU3 performs a real Product SEO audit through the existing metadata and
  structured-data validation path rather than only rendering HTML.
- No runtime, public API, framework, CLI dependency, or unrelated file change
  is introduced.
- The Test Matrix passes, including syntax, standalone examples, relevant
  regression suites, static analysis, and `git diff --check`.
- All Work Unit PRs target `codex/phase-20-draft` and are reviewed there before
  integration.
- Verification, Documentation Sweep, and Final Review are completed as later
  lifecycle gates before Phase 20 is declared complete.
