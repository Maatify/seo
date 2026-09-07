# Phase 10 Sitemap Enhancements Blueprint

## 1. Current State

This blueprint is based on the latest actual `main` at
`c4b0a60adf29fa104f8911e253d02fa35bb19e83` and the Phase 10 integration
draft marker at `4e1ab299aadb22b47114d21f1923733ccd27dfa5`.

The repository already contains the Phase 10 sitemap feature families. The
audit therefore treats the current implementation, contracts, tests,
documentation, and historical verification reports as evidence; it does not
infer missing work from the phase name or from the existence of a class.

Current runtime layers are:

- `src/Shared/Service/SitemapGeneratorService.php`: core DTO-only URL sitemap
  and sitemap-index generation. It intentionally remains separate from the
  Web string renderer and currently renders core URL fields plus alternates.
- `src/Web/Sitemap/SitemapIndexXmlStringRenderer.php`: Web-layer sitemap-index
  string rendering.
- `src/Web/Sitemap/SitemapXmlStringRenderer.php`: Web-layer URL-set and
  single-entry string rendering, including alternates, images, videos, and
  news.
- `src/Web/Sitemap/DTO/SitemapIndexEntryDTO.php`: Web renderer index-entry
  contract.
- `src/Shared/DTO/Sitemap/`: shared contracts for URL entries, alternates,
  images, videos, news, index entries, and generation results.

The architecture remains framework-neutral: the audited renderers return
plain XML strings and do not own HTTP responses, routes, files, network calls,
databases, or global state.

## 2. Repository Evidence

### Runtime and contract evidence

- `src/Web/Sitemap/SitemapIndexXmlStringRenderer.php`
  - public methods: `renderIndex(array $sitemaps): string` and
    `renderEntry(mixed $sitemap): string`;
  - accepts the Web `SitemapIndexEntryDTO` or an associative array entry;
  - uses in-memory `XMLWriter`, the sitemap namespace, input order, and
    conditional `lastmod` output.
- `src/Web/Sitemap/SitemapXmlStringRenderer.php`
  - public methods: `renderUrlSet(array $urls): string` and
    `renderUrlEntry(mixed $url): string`;
  - accepts `SitemapUrlDTO` or raw associative URL arrays;
  - conditionally emits `xmlns:xhtml`, `xmlns:image`, `xmlns:video`, and
    `xmlns:news` and renders each supported child collection in input order.
- `src/Shared/DTO/Sitemap/SitemapUrlDTO.php`
  - owns the typed URL contract and validates typed `loc`, `lastmod`,
    `changefreq`, `priority`, and typed child DTO lists.
- `src/Shared/DTO/Sitemap/SitemapAlternateUrlDTO.php`
  - validates `hreflang` and URL values; accepts `x-default`.
- `src/Shared/DTO/Sitemap/SitemapImageDTO.php`
  - validates image `loc` and optional license URL; normalizes optional text.
- `src/Shared/DTO/Sitemap/SitemapVideoDTO.php`
  - validates thumbnail, title, description, content/player URL availability,
    positive duration, and optional publication date through the shared date
    helper.
- `src/Shared/DTO/Sitemap/SitemapNewsDTO.php`
  - requires non-empty publication name, language, publication date, and
    title; optional access, genres, keywords, and stock tickers are normalized
    as nullable strings.

### Test evidence

The current sitemap-specific standalone test files are:

- `tests/Phase7ESitemapXmlStringRendererTest.php`
- `tests/Phase10ASitemapIndexXmlStringRendererTest.php`
- `tests/Phase10BSitemapHreflangXmlStringRendererTest.php`
- `tests/Phase10CImageSitemapXmlStringRendererTest.php`
- `tests/Phase10DVideoSitemapXmlStringRendererTest.php`
- `tests/Phase10ENewsSitemapXmlStringRendererTest.php`

They use direct PHP assertions and cover DTO and array inputs, XML escaping,
conditional namespaces, combinations, and module-specific exceptions. They do
not yet cover every contract boundary identified in this audit; those gaps are
listed separately from runtime gaps below.

### Documentation and example evidence

- `README.md` advertises sitemap, hreflang, and the sitemap example.
- `docs/SEO_LIBRARY_REFERENCE.md` documents the Web renderers and all current
  sitemap DTO families.
- `docs/guides/USAGE_GUIDE.md` and `docs/guides/INTEGRATION_GUIDE.md` contain
  current examples for URL sets, alternates, images, videos, news, and index
  output.
- `examples/sitemap-output.php` demonstrates the core generator and basic Web
  URL rendering, but does not exercise every Phase 10 child collection.
- `docs/verification/PHASE_10A_SITEMAP_INDEX_STRING_RENDERER_VERIFICATION_REPORT.md`,
  `docs/verification/PHASE_10B_SITEMAP_HREFLANG_ALTERNATE_URL_VERIFICATION_REPORT.md`,
  `docs/verification/PHASE_10C_IMAGE_SITEMAP_SUPPORT_VERIFICATION_REPORT.md`,
  `docs/verification/PHASE_10D_VIDEO_SITEMAP_SUPPORT_VERIFICATION_REPORT.md`,
  and `docs/verification/PHASE_10E_NEWS_SITEMAP_SUPPORT_VERIFICATION_REPORT.md`
  contain historical verification reports. Their historical “Complete”
  verdicts are evidence of prior work, not a substitute for this latest-main
  audit.
- There is no current Phase 10 document under `docs/phases/`.

### History evidence

The feature history shows the intended layer split:

- `d28d3b0`: introduced shared sitemap DTOs and the core generator.
- `682e1ef`: copied the index-entry contract into the Web namespace and added
  the Web index renderer; the Web copy changed invalid URL reporting to the
  dedicated invalid-URL error.
- `d4db3e2`: added Web hreflang rendering.
- `6d655b1`: added image rendering.
- `5e02c81`: added video rendering.
- `7650c4b`: added news rendering.
- `45b67c6`: flattened the package to the current repository paths.

## 3. Phase 10A Status

**Classification: `partially-present`.** The sitemap-index feature is present,
but one validation contract is not strict enough.

### Present behavior

- `renderIndex()` emits an XML declaration, `<sitemapindex>`, the sitemap
  namespace, one `<sitemap>` per input entry, `loc`, and optional `lastmod`.
- Multiple DTO/array entries are supported and their input order is preserved.
- `renderEntry()` supports one Web index DTO or one associative array and
  returns a plain XML string.
- Array entries validate associative shape, non-empty/valid URL, and
  `lastmod` through `SitemapUrlDTO::isValidLastmod()`.
- Empty `renderIndex([])` is deterministic and produces an empty sitemap-index
  document; the core `SitemapGeneratorService` has a different, existing
  non-empty-input contract and is not changed by this phase.
- No filesystem, HTTP, routing, database, network, or framework ownership is
  present.

### Proven gap

- `SitemapUrlDTO::isValidLastmod()` checks that an ATOM parse returns a
  `DateTimeImmutable` but does not reject parser warnings. For example,
  `2026-02-31T10:00:00+00:00` is accepted by the Web index DTO and is emitted
  by the renderer. Date-only invalid values are rejected, so this is a
  narrower full-ATOM validation gap rather than missing index support.

## 4. Phase 10B Status

**Classification: `partially-present`.** Alternate URL support itself is
complete; the shared raw-array URL contract used by the Web renderer is not
validation-parity-complete.

### Present behavior

- `SitemapAlternateUrlDTO` exposes `hreflang` and `url` and validates both.
- The raw array contract is a list of associative entries with `hreflang` and
  `url`; non-list and malformed entries are rejected.
- Multiple alternates are preserved in deterministic input order.
- `x-default` is accepted and emitted.
- Hreflang values are trimmed and lowercased by the Web renderer before XML
  emission.
- Alternate URLs are URL-validated and XML-escaped.
- `renderUrlSet()` declares `xmlns:xhtml` once on `<urlset>` when any URL has
  alternates; `renderUrlEntry()` declares it locally on `<url>`.
- `<xhtml:link rel="alternate" hreflang="..." href="..."/>` is emitted for
  each alternate.

### Proven shared contract gap

For a raw array URL entry, `SitemapXmlStringRenderer::normalizeUrlEntry()`
checks only that top-level `loc` is non-empty. It does not apply the typed
`SitemapUrlDTO` URL, `lastmod`, `changefreq`, or priority-range rules. Thus a
raw array can render `loc: not-a-url`, an invalid full-ATOM `lastmod`, an
unknown `changefreq`, or a priority outside `0..1`. Alternate child URLs are
validated correctly; this gap concerns the containing URL contract.

## 5. Phase 10C Status

**Classification: `already fully present` for the image sitemap feature.** No
image runtime implementation is required by this blueprint.

The DTO, typed and raw-array list contracts, conditional image namespace,
`image:loc`, title, caption, geo location, license, multiple-image iteration,
XML escaping, and image-specific URL validation are present in the current
renderer. The test suite covers the fields, namespaces, escaping, optional
values, and invalid image inputs. It does not yet prove multiple images inside
one single URL entry; this is a test coverage gap, not a missing runtime
capability.

The enhancement roadmap still labels image sitemap support “Optional later”
and lists image work under a later/optional section, while the main roadmap
labels Phase 10C “Complete” and the current guides document the feature. This
is a documentation synchronization finding; it is not a reason to add image
runtime work here.

## 6. Phase 10D Status

**Classification: `partially-present`.** Video sitemap rendering is present and
the roadmap’s “Complete” claim is substantively correct for XML fields, but
the shared full-ATOM date validation gap also affects video publication dates.

### Present behavior

- DTO and raw-array contracts support thumbnail, title, description, content
  URL, player/embed URL, duration, and publication date.
- At least one of content URL or player URL is required.
- URLs, positive duration, required strings, and optional fields are validated
  or normalized according to the existing contract.
- Multiple video values are iterated, conditional `xmlns:video` is emitted,
  and all text/URL output is XML-escaped.
- Both `renderUrlEntry()` and `renderUrlSet()` support videos and namespace
  combinations with alternates and images.

### Proven gap

An invalid calendar date embedded in an otherwise parseable ATOM string is
accepted as a video `publicationDate` because the shared date helper ignores
parser warnings. This is the same WU as the Phase 10A gap.

## 7. Phase 10E Status

**Classification: `already fully present` under the current documented
contract.** News support is implemented in typed and raw-array forms.

The current contract requires non-empty publication name, language, date, and
title, accepts optional access, genres, keywords, and stock tickers, conditionally
declares the news namespace, emits the required/optional tags, preserves
collection order, and XML-escapes values. The existing documentation explicitly
states that `publicationDate` is accepted as provided, so this blueprint does
not invent a date-format WU for news.

The current test covers required/optional fields, DTO and raw-array inputs,
namespace combinations, escaping, and invalid required values. It does not
yet include multiple news entries in one URL entry; that is a coverage gap.

## 8. Gaps

### Runtime/contract gaps

The actual runtime gap count is **5**:

1. Full-ATOM invalid calendar dates are accepted by
   `SitemapUrlDTO::isValidLastmod()` because parser warnings are ignored.
2. Raw-array top-level `loc` in `SitemapXmlStringRenderer` is not URL-validated.
3. Raw-array top-level `lastmod` in `SitemapXmlStringRenderer` is not validated.
4. Raw-array top-level `changefreq` in `SitemapXmlStringRenderer` is not
   checked against `SitemapUrlDTO::allowedChangefreqValues()`.
5. Raw-array top-level `priority` in `SitemapXmlStringRenderer` is not checked
   for the typed DTO’s `0..1` range.

The five findings were reproduced against the current code: the renderer
accepts `not-a-url`, `2026-02-31T10:00:00+00:00`, `invalid`, and `2.0` in the
respective raw-array fields, and the Web index DTO plus video DTO accept the
invalid full-ATOM date.

### Test coverage gaps

The test coverage gap count is **6 scenario groups / 13 focused cases**:

1. Empty sitemap-index input and direct Web index DTO invalid URL/full-ATOM
   invalid `lastmod` constructor cases.
2. Explicit `x-default` and hreflang case/trim normalization assertions.
3. Multiple images in one URL entry.
4. Multiple videos in one URL entry plus invalid full-ATOM video publication
   date.
5. Multiple news entries in one URL entry.
6. Raw-array top-level validation parity for malformed `loc`, invalid
   `lastmod`, invalid `changefreq`, and out-of-range priority.

These are coverage gaps even where the corresponding feature is already
implemented. They are not counted as additional runtime gaps.

### Documentation gaps

- The main roadmap and enhancement roadmap disagree about 10C and later image
  or video work.
- The README and example script are less complete than the API reference and
  guides for Phase 10 child collections.
- There is no Phase 10 phase document; the only Phase 10 records are the
  historical verification reports.
- The duplicate DTO names are not explained in the public reference as a
  deliberate Core/Shared versus Web-layer split.

## 9. Decisions / Contracts

The following existing contracts are preserved for implementation WUs unless a
separate decision explicitly changes them:

1. `SitemapIndexXmlStringRenderer` uses the Web namespace DTO; the shared
   `SitemapIndexEntryDTO` remains the Core generator DTO. Both are live public
   contracts with different consumers and are not refactored or merged here.
2. `renderIndex()` and `renderUrlSet()` preserve caller order and return a
   complete XML document string. `renderEntry()` and `renderUrlEntry()` return
   one XML element document string with feature namespaces local to that root.
3. Typed DTO validation and raw-array validation must be behaviorally aligned
   for the fields that both contracts expose.
4. The standard date contract accepts `YYYY-MM-DD` and valid ATOM timestamps;
   parser warnings must not make an invalid calendar value valid.
5. `SitemapNewsDTO.publicationDate` remains non-empty and is emitted as
   provided. Tightening its format is out of scope until explicitly decided.
6. XML values continue to be escaped by native `XMLWriter`.
7. No HTTP response, route, filesystem, network, database, framework, or
   provider integration is introduced.

## 10. Scope

The post-blueprint implementation scope is limited to:

- correcting strict full-ATOM date validation shared by index/URL/video date
  contracts;
- making raw-array top-level URL normalization enforce the typed DTO’s
  `loc`, `lastmod`, `changefreq`, and priority rules;
- adding the focused tests listed in the test matrix;
- documenting the duplicate DTO layer split and synchronizing roadmap/API
  references in a later documentation sweep, not in this Blueprint PR.

## 11. Out of Scope

- New image, video, news, alternate, or sitemap-index runtime feature work
  beyond the proven gaps above.
- Refactoring, merging, or deleting either `SitemapIndexEntryDTO`.
- Expanding `SitemapGeneratorService` to render image/video/news collections;
  the Core generator and Web string renderer have separate existing contracts.
- HTTP responses, routes, controllers, filesystem persistence, network calls,
  database persistence, Google APIs, Search Console, or provider integrations.
- Composer, dependency, CI, or roadmap-status changes in this Blueprint PR.
- README, examples, guides, verification reports, or other documentation
  changes in this Blueprint PR.
- Marking Phase 10 Complete or making the integration PR Ready for review.

## 12. Work Units

Implementation Work Units count: **2**.

### WU-10-1 — Strict sitemap date validation

- Update the shared date validation contract so parser warnings/errors make a
  full-ATOM value invalid.
- Preserve valid date-only and valid ATOM values.
- Cover Web index `lastmod`, typed URL `lastmod`, and video `publicationDate`
  through focused tests.
- Do not change the documented news date-as-provided contract.

### WU-10-2 — Raw-array URL contract parity

- Update `SitemapXmlStringRenderer` raw-array normalization to enforce typed
  DTO rules for top-level `loc`, `lastmod`, `changefreq`, and priority.
- Preserve current output for valid inputs and current optional-field omission
  behavior.
- Add focused invalid-input and boundary tests, including the five reproduced
  runtime cases.

No Work Unit is created for 10C or 10E feature implementation, and no separate
feature Work Unit is created for the already-present alternate/image/video/news
XML paths.

## 13. Test Matrix

| Area | Current evidence | Runtime status | Required focused coverage after Blueprint acceptance |
|---|---|---|---|
| 10A index | `tests/Phase10ASitemapIndexXmlStringRendererTest.php`, Web index renderer/DTO | Partial: strict full-ATOM date gap | empty input, DTO constructor invalid URL/date, valid/invalid date boundaries |
| 10B alternates | `tests/Phase10BSitemapHreflangXmlStringRendererTest.php`, alternate DTO and renderer | Alternate feature present; shared raw-array parity gap | `x-default`, normalization, mixed URL set, top-level raw-array validation |
| 10C images | `tests/Phase10CImageSitemapXmlStringRendererTest.php`, image DTO and renderer | Fully present | multiple images in one URL; retain existing field/escaping/namespace cases |
| 10D videos | `tests/Phase10DVideoSitemapXmlStringRendererTest.php`, video DTO and renderer | Partial: strict full-ATOM date gap plus shared raw-array parity gap | multiple videos in one URL, invalid ATOM publication date, raw-array boundaries |
| 10E news | `tests/Phase10ENewsSitemapXmlStringRendererTest.php`, news DTO and renderer | Fully present under current date-as-provided contract | multiple news entries in one URL; retain required/optional/escaping cases |
| Baseline 7E | `tests/Phase7ESitemapXmlStringRendererTest.php` | Existing behavior must remain compatible | assert valid output and the newly defined raw-array validation contract |

Existing test files are not modified in this Blueprint PR.

## 14. Documentation Impact

Expected later documentation sweep, intentionally not performed here:

- `README.md`: add a concise pointer or example coverage note for index and
  extended sitemap rendering if maintainers want README-level discoverability.
- `docs/SEO_LIBRARY_REFERENCE.md`: explain the Core/Shared versus Web index
  DTO split and state raw-array validation parity.
- `docs/guides/USAGE_GUIDE.md`: retain current complete examples and add the
  clarified validation/date behavior only after implementation acceptance.
- `docs/guides/INTEGRATION_GUIDE.md`: retain host-owned HTTP behavior and
  update only if public contracts change.
- `docs/SEO/**`: decide whether the handbook/skeleton needs a current API
  cross-reference; do not duplicate the reference guide by default.
- `docs/phases/**`: optionally add a Phase 10 implementation/closure document
  after Blueprint and verification acceptance.
- `docs/verification/**`: add a new verification record only after the WUs or
  the zero-runtime-gap decision are accepted; historical reports remain
  historical.
- `examples/**`: optionally extend `examples/sitemap-output.php` with one
  representative Web extended entry after implementation.
- `docs/roadmap/SEO_LIBRARY_ROADMAP.md` and
  `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md`: synchronize conflicting
  10C/10D wording in a separate documentation sweep; no status change is made
  by this Blueprint.
- `docs/blueprints/**`: this file is the sole Blueprint PR change.

## 15. Risks / Compatibility

- Tightening date validation can reject previously accepted malformed ATOM
  strings. This is intentional contract correction and must be called out in
  release notes if implemented.
- Tightening raw-array validation can reject inputs that were previously
  serialized despite violating the typed DTO contract. Valid output remains
  unchanged and the behavior becomes consistent with DTO inputs.
- The two index DTOs are public and used by different layers. Merging them
  would be a compatibility change with no Phase 10 requirement; it is
  explicitly avoided.
- The existing renderers materialize normalized arrays before writing a URL
  set. This preserves deterministic namespace detection and ordering; no
  streaming redesign is proposed.
- Historical verification reports claim completion for the feature additions,
  but their commands and dates predate this latest-main audit. They must not be
  treated as proof that the five current gaps do not exist.

## 16. Definition of Done

Blueprint acceptance is complete when:

- this document is the only changed file in the Blueprint PR;
- the Blueprint PR targets `codex/phase-10-draft`, remains separate from
  `main`, and is not squash-merged by this workflow;
- all current feature classifications, five runtime gaps, six coverage groups,
  and two Work Units are reviewed and either accepted or explicitly revised;
- the implementation decision is made after review: implement WU-10-1 and
  WU-10-2, or document an explicit decision to preserve the current behavior;
- the exact Blueprint HEAD passes the required composer, syntax, PHPStan,
  standalone-test, example, sitemap-test, diff, and CI checks;
- no runtime, tests, examples, Composer, dependencies, CI, roadmap statuses,
  or unrelated documentation are changed in this Blueprint PR.

Phase 10 is **not** marked Complete by this document.
