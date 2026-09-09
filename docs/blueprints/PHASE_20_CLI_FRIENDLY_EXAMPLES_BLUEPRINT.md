# Phase 20 — CLI-Friendly Examples Blueprint

## Current State

Phase 20 is an examples-only phase. The current integration Draft is
`codex/phase-20-draft` at baseline `5d1770d86e47b5528affd878901a41b8914fe129`.
WU1, WU2, and WU3 are already present in that Draft. This Blueprint amendment
defines the remaining example coverage contract; it does not implement any
new example or runtime behavior.

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
`SeoValidationScoreCalculator`, and `SeoValidationReportExporter`), the
structured-data validation path used for Product JSON-LD, the metadata override
and generation services, the redirect and slug-history services, and the
`SeoPageRenderService` orchestration contract.

The complete family-level evidence map is recorded in
`docs/audits/PHASE_20_CLI_EXAMPLE_COVERAGE_INVENTORY.md`.

## Gaps

WU1–WU3 resolve the original Phase 20 gaps: standalone `robots.txt`
generation, page SEO validation/scoring, and a real Product SEO audit are now
covered by the current Draft examples.

The remaining gaps are workflow-level coverage gaps:

- No standalone redirect plus slug-history workflow demonstrates recording a
  slug change, optional redirect creation, and redirect resolution/decision.
- No standalone example demonstrates SEO override lookup, override application,
  default fallback, and canonical resolution through `MetaGeneratorService`.
- No standalone example demonstrates the high-level `SeoPageRenderService`
  orchestration from `RenderSeoPageCommand` to `SeoPagePayloadDTO`.

The inventory also records the structured-data generation family as partial:
existing builders and JSON-LD output are covered, while the shared/high-level
generation orchestration still needs a dedicated example.

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
- The Phase 20 Work Unit set is exactly six Work Units. WU1–WU3 remain the
  accepted robots, page-validation, and Product-audit examples; WU4–WU6 are
  the only additional implementation units.
- Every implementation Work Unit targets `codex/phase-20-draft`; no child Work
  Unit targets `main` directly.
- Verification, Documentation Sweep, and Final Review are later lifecycle
  gates. They are not Phase 20 Work Units.

## Scope

Phase 20 adds exactly six standalone examples:

1. a `robots.txt` generation example;
2. a page SEO validation example; and
3. a Product SEO audit example;
4. a redirect and slug-history workflow example;
5. an SEO override and metadata-generation example; and
6. a high-level SEO page-render orchestration example.

The existing sitemap and schema examples remain the source of truth for those
already-covered demonstrations and are not rewritten as part of this scope.
The coverage inventory is the authoritative family-level map for deciding
whether a capability is covered, partial, missing, or not applicable to a
dedicated CLI example.

## Out of Scope

- Any change under `src/` or any runtime behavior change.
- New or changed public APIs, DTOs, validators, renderers, score contracts, or
  report contracts.
- A CLI framework, command-line package, or application-specific command.
- Routing, HTTP responses, persistence, network calls, database access, or
  framework integration.
- Replacing or expanding the existing sitemap, schema, or product-page render
  examples.
- Modifying `src/`, public contracts, persistence infrastructure, bindings,
  exceptions, or reusable DTO families.
- Adding a Work Unit for `SeoBindings`, PDO repositories, exceptions, raw DTO
  families, or framework/container integration.
- Making Phase 20 complete in the same step as this Blueprint amendment.
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

### WU4 — Redirect + Slug History Workflow Example

Expected file: `examples/redirect-slug-history.php`

The example must demonstrate a real slug migration workflow using the existing
slug-history and redirect contracts and services. It should record a slug
change, show the optional redirect creation path, and resolve the legacy slug
into a `RedirectDecisionDTO` using the current redirect decision service.

The example may provide small host-style in-memory implementations of the
repository contracts locally inside the example. Those implementations are
only test/demo wiring; they must not add persistence code to `src/` or require
PDO, a database, a framework, or a container.

Acceptance requirements:

- The file runs directly from the command line and exits successfully.
- It uses the current slug-history and redirect commands, services, DTOs, and
  host URL contract without changing them.
- The terminal output makes the slug change, optional redirect record, and
  resolved redirect decision visible.
- The example demonstrates the dependency contracts without claiming to be a
  production persistence implementation.

### WU5 — SEO Override + Meta Generation Example

Expected file: `examples/seo-override-meta-generation.php`

The example must demonstrate the current override-aware metadata flow. It
should show an override create/query path, apply a manual title and/or
description override through `MetaGeneratorService`, show fallback to the host
defaults when no override exists, and show canonical resolution using the
existing command and `HostUrlGeneratorInterface` contracts.

Acceptance requirements:

- The file runs directly from the command line and exits successfully.
- It uses the current SEO override commands, DTOs, query/command services,
  `GenerateMetaTagsCommand`, `MetaGeneratorService`, and canonical URL
  contracts.
- The output distinguishes the override-present result from the no-override
  default fallback and displays the resolved canonical URL.
- No override, metadata, generator, URL, or public contract is changed.
- No database, PDO, framework, container, or CLI dependency is introduced.

### WU6 — `SeoPageRenderService` Orchestration Example

Expected file: `examples/seo-page-render.php`

The example must demonstrate the high-level Web orchestration contract from
`RenderSeoPageCommand` through `SeoPageRenderService` to
`SeoPagePayloadDTO`. It should provide host-style dependencies in memory,
generate metadata through the existing services, generate schema output, and
print the resulting payload in a terminal-inspectable form.

Acceptance requirements:

- The file runs directly from the command line and exits successfully.
- It uses `RenderSeoPageCommand`, `SeoPageRenderService`, and
  `SeoPagePayloadDTO` as the orchestration path.
- The output makes metadata generation, schema generation, and the payload
  sections visible without duplicating all feature-specific examples.
- It does not become a second sitemap, robots, validation, Product audit, or
  HTML-rendering showcase.
- No runtime, public contract, framework/container integration, or external
  dependency is introduced.

## Test Matrix

Each Work Unit must be checked as a standalone command-line program on the
supported PHP versions:

| Area | Required check | Expected result |
| --- | --- | --- |
| WU1 | `php examples/robots-output.php` | Exit `0`; visible `User-agent`, rule directives, and sitemap output where configured |
| WU2 | `php examples/seo-validation.php` | Exit `0`; visible validation status, score/grade, and findings |
| WU3 | `php examples/product-seo-audit.php` | Exit `0`; visible Product audit report and score/grade with structured-data findings evaluated where applicable |
| WU4 | `php examples/redirect-slug-history.php` | Exit `0`; visible slug-history record, optional redirect creation, and redirect decision/resolution |
| WU5 | `php examples/seo-override-meta-generation.php` | Exit `0`; visible override-present metadata, default fallback metadata, and canonical resolution |
| WU6 | `php examples/seo-page-render.php` | Exit `0`; visible `SeoPagePayloadDTO` metadata, schemas, and orchestration output |
| Existing examples | `php examples/sitemap-output.php`, `php examples/schema-output.php`, and `php examples/product-page-seo.php` | Continue to run successfully without modification |
| Syntax | PHP lint for all examples | PASS |
| Regression | Existing standalone tests and relevant validation/structured-data test suites | PASS |
| Static analysis | Repository PHPStan gate | PASS |
| Formatting | `git diff --check` | PASS |

The implementation PRs may add only the six expected example files for their
Work Units. Tests, runtime files, and unrelated examples remain outside this
Blueprint's implementation scope unless a later review explicitly identifies
a necessary repository convention that is already supported by the phase
contract. The coverage inventory itself must remain consistent with the
family-level classifications and counts recorded in
`docs/audits/PHASE_20_CLI_EXAMPLE_COVERAGE_INVENTORY.md`.

## Documentation Impact

The six examples are themselves the Phase 20 user-facing documentation for
command-line usage. Their output and comments must identify the existing
contracts being demonstrated without duplicating or changing runtime API
documentation.

The coverage inventory at
`docs/audits/PHASE_20_CLI_EXAMPLE_COVERAGE_INVENTORY.md` is the architecture
and documentation record for the family-level coverage decision. It links
covered capabilities to actual examples and explains why infrastructure,
host-wiring, raw DTO, and exception families do not receive dedicated Work
Units.

The existing sitemap, schema, and product-page examples require no
synchronization for this Blueprint because their current coverage is already
established. Any broader documentation inventory or synchronization is a
later Documentation Sweep gate, not a Work Unit here.

## Definition of Done

Phase 20's implementation is technically complete only when all six Work
Units are implemented in their expected example files and satisfy the
following conditions:

- Every new example is standalone and command-line runnable.
- WU1 uses the existing robots DTOs and renderer to produce sample
  `robots.txt` output.
- WU2 uses the existing validation, report, score, and export contracts to
  show a page SEO validation result.
- WU3 performs a real Product SEO audit through the existing metadata and
  structured-data validation path rather than only rendering HTML.
- WU4 demonstrates slug-history recording, optional redirect creation, and
  redirect resolution using host-style in-memory dependency wiring.
- WU5 demonstrates override creation/query, manual metadata override
  application, default fallback, and canonical URL resolution through the
  existing services.
- WU6 demonstrates the `RenderSeoPageCommand` → `SeoPageRenderService` →
  `SeoPagePayloadDTO` orchestration path for metadata and schemas.
- No runtime, public API, framework, CLI dependency, or unrelated file change
  is introduced.
- The capability inventory is present, family-level, linked to actual covered
  examples, and records the approved `covered`/`partial`/`missing`/
  `not-applicable-for-dedicated-cli-example` classifications.
- The Test Matrix passes, including syntax, standalone examples, relevant
  regression suites, static analysis, and `git diff --check`.
- All Work Unit PRs target `codex/phase-20-draft` and are reviewed there before
  integration.
- Verification, Documentation Sweep, and Final Review are completed as later
  lifecycle gates before Phase 20 is declared complete.
