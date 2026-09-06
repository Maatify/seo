# Phase 8 — Developer Experience & Usage Documentation Verification Report

## Verification result

`PASS`

This report records the Phase 8 Verification Gate for the exact Draft HEAD below.
No runtime, test, example, guide, roadmap, CI, Composer, or public-contract change
was made on the Verification branch. The only Verification artifact is this report.

## Verified revisions

- Verification branch: `codex/phase-8-verification`
- Exact Draft HEAD under verification:
  `eb4c9427823e531e1b16b2593339ee3cd4df3509`
- Latest `main` at verification time:
  `2989683e3609bcc843d0ec25ead3799a3b5d2d39`
- WU1 squash commit included in the Draft:
  `eb4c9427823e531e1b16b2593339ee3cd4df3509`

The Phase 8 implementation diff against the verified `main` contains the Blueprint
and the Usage Guide synchronization only:

- `docs/blueprints/PHASE_8_DEVELOPER_EXPERIENCE_USAGE_DOCUMENTATION_BLUEPRINT.md`
- `docs/guides/USAGE_GUIDE.md`

No `src/**`, `tests/**`, `.github/**`, Composer, example, or public-contract file
is part of the Draft implementation diff.

## Runtime and package checks

| Check | Command / version | Result |
| --- | --- | --- |
| PHP | `PHP 8.5.9 (cli)` with Zend Engine `4.5.9` | PASS |
| Composer | `Composer version 2.10.2 2026-07-01 11:24:45` | PASS |
| Composer metadata | `composer validate --strict` | PASS — `./composer.json is valid` |
| PHP syntax | `php -l` for every `*.php` under `src/`, `tests/`, and `examples/` | PASS — 229 files |
| Static analysis | `vendor/bin/phpstan analyse` | PASS — no errors |
| Structured-data gate | `php tests/Phase21StructuredDataCiValidationTest.php` | PASS |
| Diff whitespace | `git diff --check` | PASS |

The repository's conditional PHPUnit step was reviewed in the unchanged
`.github/workflows/ci.yml`. It still runs `vendor/bin/phpunit` only when an
executable PHPUnit binary is available and otherwise reports a skip; no PHPUnit
behavior or dependency was changed by Phase 8.

## Standalone tests and examples

### Standalone tests

The current repository convention was executed with:

```text
find tests -name '*Test.php' -print0 | xargs -0 -n1 php
```

Result: `49/49` standalone tests passed.

### Examples

All current examples were executed with `php <example>`: `14/14` passed.

The five Phase 8 examples were present and passed individually:

- `examples/basic-head-render.php`
- `examples/product-page-seo.php`
- `examples/category-page-seo.php`
- `examples/sitemap-output.php`
- `examples/schema-output.php`

## Homepage snippet verification

The Homepage code block was extracted from `docs/guides/USAGE_GUIDE.md` into a
temporary script stream and executed with PHP from the repository root. The
temporary output was not committed.

Result:

- Exit status: `0`
- Output size: `1247` bytes
- `<title>` tags: `1`
- JSON-LD script blocks: `2`
- OpenGraph tags: `5`
- Twitter tags: `4`

This proves that the snippet produces a rendered SEO head containing metadata,
social output, and both WebSite and Organization JSON-LD nodes.

## Documentation and API review

### Homepage example contract

The Homepage example was checked against the current source APIs:

- `FluentSeoBuilder` provides the title, description, canonical, robots, OpenGraph,
  Twitter, `schemas()`, and `render()` methods used by the example.
- `WebSiteJsonLdBuilder` provides `setName()`, `setUrl()`, `setSearchAction()`, and
  `toArray()`.
- `OrganizationJsonLdBuilder` provides `setName()`, `setUrl()`, and `toArray()`.
- The host application remains responsible for placing the rendered string inside
  `<head>` and sending the HTTP response.

No invented class, method, framework dependency, or external service is used.

### JSON-LD validation wording

The Usage Guide wording matches the current implementation and Phase 13P contracts:

- structural node and numeric-list validation is documented;
- `@graph` wrappers and recursive graph-node handling are documented;
- the existing aliases `jsonLd`, `json_ld`, `schema`, and `schemas` are documented;
- deep semantic validation is limited to `Product`, `Offer`, `AggregateOffer`, and
  `ProductGroup`;
- complete Schema.org coverage is not claimed;
- Google Rich Results eligibility is not claimed; and
- Merchant eligibility is not claimed.

### Integration Guide review

`docs/guides/INTEGRATION_GUIDE.md` was reviewed against the current public classes
and methods. Its Plain PHP, Slim, Laravel, template, host-owned HTTP response,
sitemap, robots, and validation examples remain compatible. No proven contradiction
was found, so the file was not changed.

## Scope and compatibility review

- No `src/**` changes.
- No runtime or public API changes.
- No test changes.
- No example changes.
- No `.github/**` changes.
- No Composer dependency or package metadata changes.
- No framework dependency or provider integration.
- No Google, Merchant, network, or external-service implementation.
- Existing Phase 8 examples remain present and executable.
- The Draft remains a documentation-only Phase 8 change set plus its Blueprint.

## GitHub Actions

GitHub Actions completed successfully for the Verification PR. The two workflow
runs triggered for the PR both passed all three PHP matrix jobs.

| Matrix / gate | Result |
| --- | --- |
| PHP 8.2 | PASS |
| PHP 8.3 | PASS |
| PHP 8.4 | PASS |
| Explicit syntax gate | PASS |
| PHPStan | PASS |
| Structured-data gate | PASS |
| Conditional PHPUnit behavior | PASS — existing conditional workflow behavior retained |
| Standalone tests | PASS |

## Final gate judgment

`PASS`

All requested local checks, API/documentation reviews, example executions, and the
Verification PR CI matrix passed. Phase 8 may proceed to Documentation Sweep; this
report does not perform that sweep or change roadmap status.
