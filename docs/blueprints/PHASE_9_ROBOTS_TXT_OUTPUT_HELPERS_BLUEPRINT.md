# Phase 9 — Robots.txt Output Helpers Blueprint

## 1. Current State

This Blueprint is based on the exact Phase 9 Draft baseline:

- `main`: `3f3018bfa3656c8c7706a9fe9e4e9ae5c662c7c0`
- Phase 9 umbrella Draft marker: `d6ed8a61cd4cbaa32d69ceb26b702f3458ee92e3`

The repository already contains the Phase 9A runtime surface:

- `src/Web/Robots/RobotsTxtRenderer.php`
- `src/Web/Robots/DTO/RobotsRuleDTO.php`
- `src/Web/Robots/DTO/RobotsTxtDTO.php`
- `tests/Phase9ARobotsTxtRendererTest.php`
- `docs/verification/PHASE_9A_ROBOTS_TXT_RENDERER_VERIFICATION_REPORT.md`

The current Enhancement Roadmap still lists `Phase 9: Robots.txt Output Helpers`
without marking the Phase complete. The historical Phase 9A report records the
original renderer as complete, but it is not treated as the current Stack
Verification Gate. A current Phase 9 verification, Documentation Sweep, and Final
Review are still required.

### 1.1 Existing runtime behavior

`RobotsTxtRenderer::render(RobotsTxtDTO $robotsTxt): string` currently:

- emits global comments first as `# comment` lines;
- inserts a blank line between global comments and rules or sitemaps when both are
  present;
- renders rules in input order;
- renders each rule with the deterministic directive order:
  comments, `User-agent`, optional `Crawl-delay`, `Allow`, then `Disallow`;
- separates rule blocks with one blank line;
- renders sitemap URLs after all rule blocks as `Sitemap: <url>` lines;
- preserves the input order of rules, directives, and sitemap URLs without sorting or
  deduplicating them;
- trims the assembled document and returns exactly one final newline.

`renderRule(RobotsRuleDTO $rule): string` returns one rule block without a trailing
newline. Empty or whitespace-only comments are ignored after trimming. An empty
`RobotsTxtDTO` therefore renders as one newline (`"\\n"`), while comments-only and
sitemap-only DTOs render their content with one final newline.

`RobotsRuleDTO` currently has these public constructor contracts:

- `userAgent: string`, required to be non-empty after trimming;
- `allow: list<string>`, where every path must be non-empty after trimming;
- `disallow: list<string>`, where every path must be non-empty after trimming;
- `crawlDelay: int|float|null`, where a provided value must be greater than or equal
  to zero;
- `comments: list<string>`, rendered after per-comment trimming; blank comments are
  omitted by the renderer.

`RobotsTxtDTO` currently has these public constructor contracts:

- `rules: list<RobotsRuleDTO>`;
- `sitemaps: list<string>`, with every URL accepted only when
  `FILTER_VALIDATE_URL` accepts it;
- `comments: list<string>`.

The DTOs do not add framework, HTTP, filesystem, database, network, or package
dependencies. The renderer returns a plain PHP string only. The host application
owns the `/robots.txt` route, response headers, and HTTP response emission.

### 1.2 Existing test coverage

`tests/Phase9ARobotsTxtRendererTest.php` currently covers:

- one rule with allow and disallow directives;
- multiple user-agent rule blocks and their ordering;
- global and rule comments, including ignored blank comments;
- crawl-delay output;
- empty user-agent rejection;
- empty allow-path rejection;
- empty disallow-path rejection;
- negative crawl-delay rejection;
- invalid sitemap URL rejection;
- sitemap-only output;
- comments-only output.

The existing test does not directly assert every current behavior. In particular, it
does not explicitly assert multiple sitemap lines, empty DTO output, direct
`renderRule()` output, or every blank-line/final-newline boundary. These are evidence
coverage gaps for the modern Verification Matrix, not proof of a runtime
implementation gap. The current Usage Guide already demonstrates multiple sitemaps
and the source behavior is deterministic.

### 1.3 Existing documentation evidence

The following current documentation was inspected:

- `docs/SEO_LIBRARY_REFERENCE.md` already lists the Robots renderer and both DTOs.
- `docs/guides/USAGE_GUIDE.md` already provides a current Robots.txt example with
  rules, allow/disallow, crawl-delay, comments, multiple sitemap URLs, and explicit
  host-owned `Content-Type` output.
- `docs/guides/INTEGRATION_GUIDE.md` already states that the host owns the route,
  headers, and response while the library returns a string.
- No active `docs/SEO/**`, `docs/phases/**`, or `examples/**` document a contradictory
  Robots API. Robots references under `docs/SEO/v1/**` are historical and must not be
  rewritten as part of this Phase.
- The legacy `docs/roadmap/SEO_LIBRARY_ROADMAP.md` already records Phase 9A as
  complete. The Enhancement Roadmap describes the requirements but does not yet
  reflect a current Phase 9 Stack completion record.

### 1.4 Current-state classification

| Phase 9 requirement | Classification | Evidence / reason |
| --- | --- | --- |
| user-agent | `fully-present` | `RobotsRuleDTO::$userAgent` and `User-agent:` rendering exist; empty values are rejected. |
| allow | `fully-present` | `RobotsRuleDTO::$allow` validates non-empty paths and renders ordered `Allow:` directives. |
| disallow | `fully-present` | `RobotsRuleDTO::$disallow` validates non-empty paths and renders ordered `Disallow:` directives. |
| sitemap URLs | `fully-present` | `RobotsTxtDTO::$sitemaps` validates URLs and the renderer emits ordered `Sitemap:` lines. |
| crawl-delay | `fully-present` | `int|float|null` is accepted, negative values are rejected, and `Crawl-delay:` is rendered. |
| comments | `fully-present` | Global and rule comments are rendered and blank comments are ignored. |
| multiple user-agent sections | `fully-present` | Multiple `RobotsRuleDTO` instances are rendered as ordered blocks separated by blank lines. |
| plain string output | `fully-present` | `RobotsTxtRenderer::render()` returns `string` and owns no response behavior. |
| no HTTP response ownership | `fully-present` | No routes, controllers, headers, or response objects exist in the Robots implementation. |
| no filesystem write | `fully-present` | The renderer performs no file I/O and returns content only. |
| no framework coupling | `fully-present` | The implementation uses PHP and library DTO/exception types only. |

## 2. Gaps

No genuine Phase 9 runtime implementation gap was found in the audited baseline.
The only identified gap is current evidence coverage: the historical Phase 9A report
predates the repository's Stack lifecycle and Phase 21 CI gate, and the standalone
Phase 9A test does not directly exercise every behavior required by the modern
Verification Matrix. Those gaps are addressed by the Verification Gate's commands
and smoke cases, not by rewriting correct runtime code.

The historical report is therefore classified as **partially stale**:

- still-current evidence: the listed public classes, core validation behavior, plain
  string output, and framework-neutral boundaries match the current source;
- stale or incomplete evidence: its Phase 9A completion verdict is not a current
  Phase 9 Stack gate, and its command/test/CI record predates Phase 21's current
  matrix and lifecycle.

No stale active API description, HTTP-response claim, filesystem-writing claim, or
contradictory current example was found in the audited guides. Historical material
under `docs/SEO/v1/**` remains outside the synchronization scope.

## 3. Decisions / Contracts

### 3.1 Architectural contract

Phase 9 preserves the existing framework-neutral architecture:

- `RobotsTxtRenderer` returns a plain `string` only.
- The host application owns the route, HTTP response, headers, and controller.
- The library does not write `robots.txt` to the filesystem.
- The library performs no network access or database access.
- The library adds no framework dependency or HTTP-library dependency.
- Existing public DTO and renderer contracts remain unchanged unless a later audit
  proves a concrete gap and records an explicit decision.
- No provider, crawler, search-engine, or external verification integration is part
  of Phase 9.

### 3.2 Serialization contract

The current output contract is preserved exactly:

1. Global non-empty comments, as `# ...` lines.
2. A blank line when global comments are followed by rules or sitemaps.
3. Rule blocks in input order.
4. Within each rule: non-empty comments, `User-agent`, optional `Crawl-delay`, all
   `Allow` entries, then all `Disallow` entries, each in input order.
5. A blank line between rule blocks.
6. Sitemap lines after rules, in input order, with a blank separator when needed.
7. One final newline after trimming the assembled output.

No sorting, deduplication, URL rewriting, response emission, or filesystem behavior
may be inferred from this contract.

### 3.3 Validation contract

Phase 9 documents and verifies the validation already present:

- empty-after-trim `userAgent`, allow path, or disallow path is rejected;
- negative `crawlDelay` is rejected;
- the accepted `crawlDelay` type is `int|float|null`;
- sitemap values must pass the current `FILTER_VALIDATE_URL` check;
- comments are accepted as the current list-of-strings DTO contract and blank
  rendered comments are omitted.

The Phase does not introduce stricter lexical robots.txt validation, URL policy,
directive normalization, type coercion, comment escaping policy, or new exceptions
without a separately approved gap decision.

## 4. Scope / Out of Scope

### In scope

- Evidence-based audit of the existing Robots renderer and DTO contracts.
- Current Verification Gate for the existing implementation.
- Documentation Sweep and Phase 9 completion record after verification.
- Final Review against the latest `main`.
- Synchronization of active documentation only where repository evidence requires it.

### Out of scope

- Any new or changed `src/**` implementation when no genuine gap is proven.
- Rewriting `tests/Phase9ARobotsTxtRendererTest.php` in the Blueprint branch.
- HTTP routes, controllers, response objects, headers, or framework adapters.
- Filesystem persistence, network access, database access, or external providers.
- Search-engine crawling, Google validation, indexing eligibility, or SEO ranking
  guarantees.
- XML sitemap generation or Phase 10 sitemap enhancements.
- Meta robots tags, HTML rendering, JSON-LD validation, or unrelated roadmap phases.
- Dependency, versioning, release, or CI architecture changes.
- Marking Phase 9 `Complete` before Verification, Documentation Sweep, and Final
  Review have passed.

## 5. Work Units

### Implementation Work Units: `0`

The audit proves that the existing runtime implementation already satisfies the
Phase 9 feature inventory and architectural contract. No implementation Work Unit is
created merely to rewrite correct code, rename existing classes, or duplicate the
Phase 9A implementation.

The Stack therefore proceeds after this Blueprint through these independent gates:

`Draft Integration PR → Blueprint → Verification → Documentation Sweep → Final Review vs latest main → Ready → Squash Merge`

Verification and Documentation Sweep are gates, not Work Units. If a future audit
finds a genuine implementation defect, it must stop the gate and create a separately
approved Work Unit with explicit Scope, Expected Files, Required Tests, Dependencies,
Done Criteria, and Out of Scope before implementation begins.

## 6. Test Matrix

The modern Phase 9 Verification Gate must run and record actual results for:

1. `composer validate --strict`.
2. `php -l` for every `*.php` under `src/`, `tests/`, and `examples/`, including the
   actual file count.
3. `vendor/bin/phpstan analyse`.
4. `php tests/Phase9ARobotsTxtRendererTest.php`.
5. `php tests/Phase21StructuredDataCiValidationTest.php`.
6. Every standalone `tests/*.php` script, with the actual count and result.
7. Every `examples/*.php` script, with the actual count and result.
8. A direct Robots renderer smoke execution proving:
   - multiple rules and preserved rule order;
   - allow and disallow directives;
   - integer or float crawl-delay output;
   - global and per-rule comments;
   - multiple sitemap URLs and their placement after rules;
   - exact blank-line and final-newline behavior;
   - empty DTO output;
   - invalid DTO cases for empty user-agent, empty allow/disallow paths, negative
     crawl-delay, and invalid sitemap URL.
9. `git diff --check`.
10. GitHub Actions CI on PHP `8.2`, `8.3`, and `8.4`, including the repository's
    existing syntax, PHPStan, structured-data, conditional PHPUnit, and standalone
    test behavior.

The direct smoke execution is evidence collection for behavior already present; it
does not authorize implementation changes in the Blueprint branch.

## 7. Documentation Impact

These are planned statuses for the later Documentation Sweep. They are not changes
made by this Blueprint PR. The final sweep must re-check every status against the
repository at that time and record the actual reason.

| Path | Planned status | Reason / expected action |
| --- | --- | --- |
| `README.md` | `reviewed-no-change` | No stale Robots API claim or required discovery link was found. |
| `docs/SEO_LIBRARY_REFERENCE.md` | `reviewed-no-change` | The current Robots renderer and DTO surface is already documented accurately. |
| `docs/guides/USAGE_GUIDE.md` | `reviewed-no-change` | The existing example already covers current construction, multiple sitemaps, and host-owned output. |
| `docs/guides/INTEGRATION_GUIDE.md` | `reviewed-no-change` | The existing integration section correctly assigns route, headers, and response ownership to the host. |
| `docs/SEO/**` | `reviewed-no-change` | No active contradictory Robots contract was found; `docs/SEO/v1/**` remains historical. |
| `docs/phases/**` | `updated` | A current Phase 9 record is required after Verification and Documentation Sweep. |
| `docs/verification/**` | `updated` | A current Phase 9 Verification report is required; the Phase 9A report remains historical evidence. |
| `examples/**` | `reviewed-no-change` | No example mismatch or required Phase 9 implementation example was found. |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | `reviewed-no-change` | Its legacy Phase 9A entry is accurate and does not need rewriting in this Phase. |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | `updated` | The Phase 9 Stack outcome and current lifecycle/status need synchronization after all gates. |
| `docs/blueprints/**` | `updated` | This Phase 9 Blueprint is the new source document for the current Stack execution. |

## 8. Definition of Done

Phase 9 may be marked `Complete` only after all of the following are true:

- The Blueprint audit is merged into the Phase 9 Integration Draft.
- The existing implementation is verified without unapproved semantic or API changes.
- The Verification Gate records the actual current commands, versions, counts, smoke
  results, and PHP 8.2/8.3/8.4 CI results.
- The Documentation Sweep records all 11 required path statuses and synchronizes
  only documentation whose current claims require it.
- A Phase 9 record documents the final scope, current contracts, limitations, and
  historical-report classification.
- Final Review passes against the latest `main` and confirms no scope bypass,
  unintended public/runtime change, framework coupling, filesystem/network/database
  behavior, or stale active claim.
- Only after those gates may the Integration PR move to Ready and be squash-merged
  into `main`.

## 9. Evidence and Deferred Work

The existing Phase 9A implementation is retained as the current runtime foundation.
The historical Phase 9A Verification report is retained as historical evidence and
must not be rewritten by this Blueprint PR. A modern Verification report will record
the actual current evidence. Any future implementation or external integration must
be separately scoped; this Phase does not create a provider, network client,
filesystem writer, HTTP integration, or search-engine verification tool.
