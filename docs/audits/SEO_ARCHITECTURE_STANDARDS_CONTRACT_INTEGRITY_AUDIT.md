# SEO Architecture, Standards, and Contract Integrity Audit

**Repository:** `Maatify/seo`  
**Audit evidence baseline branch:** `main`  
**Audit evidence baseline commit:** `1e2cca61fa4259da3cf20c8908b08f413b93c51b`  
**Integration branch reviewed:** `codex/phase-23-draft`  
**Integration snapshot reviewed:** `bbaf7bf641e20fe51261d17903def893e0ee2ecb`  
**Audit date:** 2026-09-10  
**Audit type:** Pre-remediation architecture and SEO standards audit  
**Status:** `NEEDS REMEDIATION`  
**Release scope:** Explicitly out of scope. This audit does **not** evaluate release gating, publishing, tagging, packaging, or version-number readiness.

---

## 1. Purpose

This audit establishes a safe, evidence-backed baseline before any remediation work is started.

The goal is not to "fix everything that looks old" and not to maximize the number of validation rules. The goal is to make the library architecturally trustworthy at a global-quality level while preserving valid public contracts and avoiding accidental breakage.

The audit therefore separates four different questions that must not be conflated:

1. **Protocol / standard conformance**  
   Is the behavior valid according to a protocol or vocabulary such as RFC 9309, Sitemaps.org, Open Graph Protocol, or Schema.org?

2. **Provider-specific behavior**  
   Does a provider such as Google Search support, require, ignore, deprecate, or recommend a particular field or rule?

3. **SEO heuristics / best practices**  
   Is a rule merely an opinionated recommendation such as a preferred title length, rather than a validity rule?

4. **Library contract behavior**  
   What does the current public API already promise through classes, method signatures, examples, tests, and documentation?

A safe remediation must satisfy all four dimensions at once.

---

## 2. Baseline and Evidence Rules

### 2.1 Baseline

The architecture and standards findings were established against the last completed `main` baseline:

`main@1e2cca61fa4259da3cf20c8908b08f413b93c51b`

Before this audit was attached to Phase 23, the current Phase 23 integration Draft was also reviewed at:

`codex/phase-23-draft@bbaf7bf641e20fe51261d17903def893e0ee2ecb`

The Phase 23 Draft is five commits ahead of the audited `main` baseline and adds the Merchant Center boundary plus related tests and documentation. Those changes do not invalidate the pre-remediation findings below, but repository-state claims that can change between `main` and the integration Draft are stated against the Phase 23 snapshot where relevant.

This distinction is intentional:

- **Audit evidence baseline** identifies the completed repository state from which the standards audit was derived.
- **Integration snapshot** identifies the Draft state into which this audit is being incorporated.
- Later remediation stacks must re-check the then-current Draft HEAD before changing production behavior.

The audit does not rely on an older patch baseline.

### 2.2 Evidence hierarchy

When sources disagree or operate at different abstraction levels, the following hierarchy applies:

1. **Formal protocol / standard** for generic conformance:
   - RFC 9309 for Robots Exclusion Protocol.
   - Sitemaps.org for base sitemap protocol.
   - Open Graph Protocol for Open Graph metadata.
   - Schema.org for vocabulary definitions.

2. **Provider documentation** for provider behavior:
   - Google Search Central documentation is authoritative for Google Search requirements and eligibility behavior.
   - Google-specific sitemap namespaces are treated as provider extensions, not universal sitemap rules.

3. **Repository code and tests** for the library's current public contract.

4. **Heuristic recommendations** are never promoted into protocol errors without an explicit profile and documentation.

### 2.3 Critical interpretation rule

The following equivalences are forbidden:

- `Schema.org valid` **does not mean** `Google Rich Result eligible`.
- `Google recommended` **does not mean** `globally invalid when absent`.
- `SEO heuristic violated` **does not mean** `protocol invalid`.
- `Legacy provider field` **does not automatically mean** `delete public API`.
- `Existing test passes` **does not mean** the tested behavior is standards-correct.

---

## 3. Executive Architecture Judgment

The library has a strong foundation and should **not** be rewritten from scratch.

The following foundations are worth preserving:

- Framework-neutral architecture.
- Host-owned HTTP / OAuth / provider lifecycle boundaries.
- Typed DTOs and explicit exception boundaries.
- Search Console results kept separate from core deterministic validation.
- JSON-LD builder composition.
- HTML escaping and JSON-LD rendering safety.
- Repository interfaces separating application services from persistence implementations.
- Explicit acknowledgment in the modern structured-data documentation that Schema.org support and Google eligibility are not the same concern.

However, the library is not yet at the desired global-grade architecture because several domains currently lack one clear source of truth and several validators mix protocol rules, Google-specific rules, and heuristics in the same behavior.

### Required target model

The remediation should converge on this conceptual model:

```text
Domain Model / Public API
        |
        v
Protocol / Vocabulary Conformance
        |
        +----------------------+
        |                      |
        v                      v
Provider Profiles        Heuristic Profiles
(Google, future others)  (SEO recommendations)
        |                      |
        +----------+-----------+
                   |
                   v
      Reports / Scores / Diagnostics
```

Provider logic must never silently redefine the generic protocol layer.

---

## 4. Classification Vocabulary Used by This Audit

Each finding uses one or more of these decisions:

- **KEEP** — current behavior or architecture is valid and should be protected.
- **FIX** — current implementation is incorrect or internally inconsistent.
- **ADD** — capability is missing and should be introduced additively.
- **RECLASSIFY** — capability may remain, but its meaning or layer is currently wrong.
- **DEPRECATE** — preserve compatibility but clearly move away from the capability over time.
- **DOC-FIX** — documentation, example, or changelog claim is incorrect or stale.

Risk levels:

- **High** — likely to create incorrect output, incorrect diagnostics, or architectural divergence.
- **Medium** — meaningful correctness or contract clarity issue, but not generally destructive by itself.
- **Low** — documentation or polish issue with limited runtime risk.

---

# 5. Detailed Findings

---

## F-01 — Sitemap serialization has two independent implementation paths

**Decision:** `FIX`  
**Risk:** High  
**Area:** Architecture / Sitemap

### Repository evidence

Two separate classes serialize sitemap XML:

- `src/Shared/Service/SitemapGeneratorService.php`
- `src/Web/Sitemap/SitemapXmlStringRenderer.php`

`SitemapGeneratorService` writes XML directly with `XMLWriter`.

`SitemapXmlStringRenderer` independently normalizes inputs and writes XML with another `XMLWriter` implementation.

The divergence is already observable:

- `SitemapUrlDTO` contains:
  - alternates
  - images
  - videos
  - news

- `SitemapXmlStringRenderer` renders all those extensions.
- `SitemapGeneratorService` renders:
  - loc
  - lastmod
  - changefreq
  - priority
  - hreflang alternates

but does not serialize image, video, or news children.

There are also two classes named `SitemapIndexEntryDTO`:

- `src/Shared/DTO/Sitemap/SitemapIndexEntryDTO.php`
- `src/Web/Sitemap/DTO/SitemapIndexEntryDTO.php`

They model almost the same concept in different namespaces.

### Why this is an architecture problem

The problem is not that one public API must be deleted.

The problem is that the same sitemap domain has multiple independent normalization and XML-writing implementations. That allows future bug fixes, provider rules, and output behavior to drift.

A global-quality library should not require maintainers to remember to patch two serializers whenever a sitemap rule changes.

### What must not be done

Do **not**:

- Delete a public renderer simply because another service exists.
- Change all callers to one namespace in one destructive refactor.
- Remove raw-array support from `SitemapXmlStringRenderer`.
- Make `SitemapGeneratorService` suddenly accept every input shape without an explicit compatibility decision.
- Change output formatting unintentionally.

### Safe target

Introduce one internal canonical sitemap normalization / serialization path.

Existing public classes should initially remain façades over that path.

Before refactoring:

1. Capture current outputs with characterization tests.
2. Capture DTO-only service behavior.
3. Capture raw-array renderer behavior.
4. Capture namespace behavior and exception types.
5. Make the internal engine produce equivalent output for all behavior not intentionally changed.

### Acceptance criteria for later remediation

- One XML-writing implementation for equivalent sitemap structures.
- No duplicated validation rules between public façades.
- Existing public APIs remain callable.
- Intentional differences in accepted input shapes are documented, not accidental.
- Extended DTO data cannot silently disappear through one entry point unless that limitation is explicitly part of the public contract.

---

## F-02 — Base sitemap protocol rules and provider extension rules are not modeled as separate policies

**Decision:** `ADD` + `RECLASSIFY`  
**Risk:** High  
**Area:** Sitemap standards

### Formal base protocol

Sitemaps.org defines base sitemap constraints including:

- Maximum 50,000 `<url>` entries.
- Maximum uncompressed size: 50 MB (52,428,800 bytes).
- The required page URL `<loc>` inside a `<url>` entry must be less than 2,048 characters.
- Sitemap index maximum 50,000 `<sitemap>` entries.
- Sitemap index maximum uncompressed size: 50 MB (52,428,800 bytes).
- Sitemap XML must be UTF-8 encoded.
- XML data values must be entity-escaped.
- URL values must follow the applicable URI/IRI escaping requirements documented by the protocol.
- The default Sitemap location scope affects allowed URLs by protocol/scheme, host, port where applicable, and path scope derived from the Sitemap location.
- The `lastmod` value must follow the W3C Datetime format (which allows omitting the time portion, e.g. YYYY-MM-DD). Fractional seconds are supported by the protocol.
- In a URL sitemap, `<url><lastmod>` identifies the time the page content was last modified.
- In a Sitemap Index, `<sitemap><lastmod>` identifies the time the linked sitemap file itself was last modified.

**Current implementation limitation/mismatch:**
- `SitemapUrlDTO::isValidLastmod()`, `Shared\DTO\Sitemap\SitemapIndexEntryDTO`, and `Web\Sitemap\DTO\SitemapIndexEntryDTO` explicitly enforce regex `Y-m-d` or `ATOM` strict formats.
- Standard PHP `DateTimeInterface::ATOM` does not natively include fractional seconds. While W3C Datetime officially supports fractional seconds, the current implementation actively rejects them. This is a **current implementation limitation** that must be characterized before any remediation, rather than falsely claiming it guarantees W3C compliance.

Host/path rules must not become unconditional same-host DTO rejection.

- URLs contained inside one URL sitemap must belong to the applicable single-host/site scope defined by the protocol.
- Cross-submission does **not** mean one sitemap may freely mix URLs from unrelated hosts.
- Cross-submission allows a sitemap file to be submitted/hosted in a different location when the required ownership/authority relationship is established.
- A Sitemap Index may reference only Sitemap files on the same site as the Sitemap Index.
- This rule is separate from URL-sitemap cross-submission behavior.
- Cross-submission must not be interpreted as permission for a Sitemap Index to freely reference unrelated external Sitemap hosts.
- Host/submission checks require document/submission context and must not be forced into a single-entry DTO constructor.

Keep these concerns outside single-entry DTO validation because they require document/submission context.

Not all of these constraints belong at the same validation level. The page URL `<loc>` lexical/length rule is entry-level; count, byte-size, and location/context rules are document-level or host/submission-context-level.

### Current implementation limitation

`SitemapUrlDTO` validates a single URL entry but does not know:

- where the sitemap will be hosted,
- the final uncompressed XML byte length,
- total URL count,
- site / path ownership context or cross-submission evidence.

Therefore those rules **cannot safely be pushed into the DTO constructor**. Do not turn host/path rules into unconditional same-host constructor rejection.

Furthermore, while `XMLWriter` declares UTF-8 in the XML declaration (`startDocument(..., 'UTF-8')`), the library must not assume that it automatically validates or transcodes all input to UTF-8 without proof. The actual XML escaping behavior and UTF-8 validity must be characterized and verified. Entity escaping remains a **serialization/output guarantee**, not a DTO pre-escaping requirement.

### Google Provider Behavior

Google-specific behavior must be explicitly separated from the base sitemap protocol:
- Google ignores `priority` and `changefreq`.
- Google uses `lastmod` only if it is consistently and verifiably accurate (e.g., compared to actual page modification).

### Safe target

The architecture needs separate levels:

1. **Entry-level**
   - URL shape
   - page URL `<loc>` length below 2,048 characters
   - URL values must follow the applicable URI/IRI escaping requirements documented by the protocol.
   - `lastmod` lexical format (W3C Datetime).
   - `changefreq` vocabulary.
   - `priority` limits (0.0 to 1.0).

2. **Document-level / Context validation**
   - URL count max 50,000
   - Uncompressed size max 50 MB
   - Sitemap Index count max 50,000
   - Sitemap Index Uncompressed size max 50 MB
   - Content semantic validation: `lastmod` represents the page's actual modification time (in `<url>`) or the linked sitemap file's actual modification time (in `<sitemap>`).

3. **Serialization/output guarantees**
   - Sitemap XML must be UTF-8 encoded.
   - XML data values must be entity-escaped.
   - The library must verify `XMLWriter` provides these guarantees during serialization.

4. **Host/Location/Submission context**
   - default Sitemap location scope affects allowed URLs: protocol/scheme, host, port where applicable, path scope derived from Sitemap location.
   - cross-submission remains a separate authority/submission-context mechanism and must not be confused with freely mixing URL hosts.
   - Sitemap Index same-site restriction remains separate from cross-submission.

5. **Provider extension validation**
   - Google ignores `priority` and `changefreq`.
   - Google `lastmod` accuracy requirements.
   - Google Image
   - Google Video
   - Google News
   - provider-specific deprecations or recommendations

### What must not be done

Do not force document-context or host-context rules into a single-entry DTO.

That would either require hidden global state or create fake validation that cannot actually prove the rule.

---

## F-03 — Deprecated Google Image sitemap fields remain first-class without clear status

**Decision:** `RECLASSIFY`; possible future `DEPRECATE`  
**Risk:** Medium  
**Area:** Google Image sitemap

### Repository evidence

`SitemapImageDTO` exposes:

- `title`
- `caption`
- `geoLocation`
- `license`

and the renderer can emit:

- `image:title`
- `image:caption`
- `image:geo_location`
- `image:license`

### Current Google position

Google officially deprecated these image sitemap tags and states that they have had no effect on indexing and search features since August 6, 2022.

Google also states that leaving them in existing sitemaps has no immediate negative effect.

### Additional current Google Image constraints

The current Google Image sitemap reference also states:

- each `<url>` may contain up to 1,000 `<image:image>` entries;
- an image URL may be hosted on another domain, but Google requires both the main site and the image-hosting domain to be verified in Search Console for that cross-domain setup;
- crawlability of image URLs remains a provider-context concern.

The current `SitemapUrlDTO` accepts an unbounded image list and cannot know Search Console verification state. These rules therefore need an explicit Google Image provider validation/context layer rather than being silently folded into generic URL validation.

### Correct conclusion

This is **not** a justification for deleting the public fields immediately.

The safe conclusion is:

- They are no longer current Google-effective sitemap fields.
- They may remain for backward compatibility.
- They must not be documented as current Google indexing enhancements.
- New examples should not encourage them as recommended Google output.
- The Google Image profile must also model the 1,000-images-per-URL limit and cross-domain verification context.
- A future deprecation path can be considered separately.

### What must not be done

Do not remove constructor parameters or output support in a compatibility-breaking cleanup without a deliberate migration plan.

---

## F-04 — Google Video sitemap validation is materially incomplete

**Decision:** `ADD` (Provider Layer)
**Risk:** High  
**Area:** Google Extensions

### Repository evidence

- `src/Shared/DTO/Sitemap/SitemapVideoDTO.php`

### The problem

The current DTO implements only a subset of the Google Video sitemap provider constraints.

Current Google documentation includes constraints such as:
- `video:duration` must be between 1 and 28,800 seconds.
- `video:description` maximum length is 2,048 characters.
- `video:publication_date` accepted forms are W3C Datetime (YYYY-MM-DD or YYYY-MM-DDThh:mm:ssTZD).
- `video:content_loc` vs `video:player_loc`:
  - At least one of `video:content_loc` or `video:player_loc` must be present. (When `video:content_loc` is available, Google officially recommends using it rather than relying solely on `player_loc`—this is a **provider recommendation**, not a strict validity requirement).
  - `video:content_loc` must point to a supported video file type. Google currently documents support for: 3GP, 3G2, ASF, AVI, DivX, M2V, M3U, M3U8, M4V, MKV, MOV, MP4, MPEG, OGV, QVT, RAM, RM, VOB, WebM, WMV, XAP.
  - Video URLs must use HTTP, HTTPS, or FTP. Streaming protocols are explicitly unsupported.
  - Data URLs are explicitly unsupported for video URLs.
  - Both must not be the same URL as the parent page `<loc>`.
  - The resources must be accessible to Googlebot (Googlebot must not be blocked by robots.txt or login requirements, and must be able to fetch the file).
  - The video must be relevant to the host page content, and not embedded on a page completely unrelated to the video.

The current DTO enforces part of the contract (such as non-empty title/description, minimum duration, basic URL shapes, and requiring at least one of `contentLoc` or `playerLoc`), but does not enforce all additional Google Video constraints documented above.

### Classification of Rules

1. **Deterministic lexical/local validation (Entry Level):**
   - Raw DTO values remain non-pre-escaped.
   - `video:duration` limits.
   - `video:description` maximum length.
   - `video:publication_date` accepted forms.
   - Requirement of either `video:content_loc` or `video:player_loc`.
   - Inequality of `content_loc`/`player_loc` to parent `<loc>`.
   - `video:thumbnail_loc` URL shape.
   - Rejection of explicitly unsupported protocols (e.g. streaming protocols, Data URLs) for `content_loc`. Enforcement of HTTP/HTTPS/FTP where applicable.

2. **Document/Context validation:**
   - (Inequality check is handled deterministically if parent `<loc>` is supplied via context).

3. **Serialization/Output Guarantees:**
   - `video:title` and `video:description` must be properly XML entity-escaped or CDATA wrapped during output rendering.
   - The library must not instruct the implementation to double-escape values.

4. **External/Provider-evidence condition:**
   - Host-page relevance requirement (consistency between video context and the on-page visible content).
   - Actual remote accessibility of `content_loc`, `player_loc`, and `thumbnail_loc` to Googlebot.
   - Actual remote file format of `content_loc` matching the documented supported types.
   - Actual remote image format, transparency, dimensions, and stability of `thumbnail_loc`.
   (These remote/accessibility constraints must not be converted into fake offline validation).

Note: Watch-page or video indexing eligibility is an external outcome entirely separate from the Video Sitemap resource contract and must not be used as a DTO validation rule.

### Safe target architecture

Provider extensions should act as explicit decorators or context-aware validators on top of the base sitemap.

## F-05 — Google News sitemap data is structurally accepted without enough provider validation; cardinality mismatch

**Decision:** `ADD` (Provider Layer) / `DOC-FIX`
**Risk:** High  
**Area:** Google Extensions

### Repository evidence

- `src/Shared/DTO/Sitemap/SitemapNewsDTO.php`
- `src/Web/Sitemap/SitemapXmlStringRenderer.php`
- `src/Shared/DTO/Sitemap/SitemapUrlDTO.php`
- `examples/sitemap-output.php`

### The problem

The current architecture lacks explicit distinction between core Sitemap mechanics and Google News extension rules, leading to several provider mismatches:

1. **Cardinality mismatch:** Google News officially mandates a maximum of **one** `<news:news>` tag per `<url>` entry, and up to 1,000 `<news:news>` tags total per sitemap file. The current `SitemapUrlDTO::$news` is an array/list, and `SitemapXmlStringRenderer` iterates over it to output multiple news entries under a single `<url>`, violating the provider's cardinality contract.
2. The current documentation/examples pass "publication date" without an explicit time-relative rule. Google News requires the article to have been published within the last **two days** (48 hours) to be eligible.
3. The legacy generic documentation provides an invalid example for `SitemapNewsDTO::$publicationName` by retaining the parenthetical `(The)` (e.g. `The Example (The)`). Google News explicitly forbids extra parentheticals or strings in the publication name, stating it must match exactly.
4. Google News requires `news:publication/news:language` to be a 2 or 3 letter ISO 639 code, explicitly including Google's specific documented exceptions for simplified `zh-cn` and traditional `zh-tw` Chinese.

### Safe target architecture

The News policy should be explicit.

- **Two-day metadata window:** This must remain deterministic: Do not permit a future validator to call hidden `now()` / system time internally. The architecture must require caller-supplied reference time/context for time-relative validation.
- **Cardinality:** The current list API (`SitemapUrlDTO::$news`) must not be broken immediately to avoid breaking compatibility. Instead, this requires a compatibility-safe remediation path where multiple entries are explicitly classified as provider-invalid/diagnostic, while maintaining the one-entry-per-URL valid state.
- **Publication Name/Title:** The title semantics must enforce exclusion of author, publication name, and publication date.

### Classification of Rules

1. **Deterministic lexical/local validation:**
   - `news:publication_date` W3C Datetime lexical shape constraints.
   - `news:publication/news:language` must be a 2 or 3 letter ISO 639 code (including Google's documented exceptions for simplified `zh-cn` and traditional `zh-tw` Chinese).
   - Cardinality validation: One News entry per URL is valid. Multiple News entries on one URL is provider-invalid/diagnostic.
   - Title semantics (excluding author, publication, date) where locally possible.
   - Publication-name semantics (exactly matching Google News publication name, avoiding parentheticals).

2. **Document/Context validation:**
   - 1,000 total News entries limit per News sitemap (distinct from per-URL cardinality).
   - The two-day publication window (relative to caller-provided time context).

3. **External/Provider-evidence condition:**
   - Accuracy of the article text versus the reported language.
   - Actual provider ingestion eligibility.

## F-06 — `robots.txt` DTO does not conform cleanly to RFC 9309 grammar and Google provider contracts

**Decision:** `FIX` (RFC Compliance & Provider Profiles)
**Risk:** High  
**Area:** Robots.txt Validation

### Repository evidence

- `src/Web/Robots/DTO/RobotsTxtDTO.php`

### The problem

The current implementation treats RFC 9309 and Google-specific parsing rules as interchangeable and utilizes incomplete offline validators.

1. **Path semantics:** The `RobotsRuleDTO` enforces that rule paths start with `/`. The RFC allows `*`, and Errata 7995 proposes changing the ABNF to allow `/` or `*` at the start. Furthermore, Google parsing behavior has specific rules for leading slashes.
2. **`Sitemap:` URL contract vs current validator:** Google officially states that the `Sitemap:` directive requires a fully-qualified URL (including protocol and host), is case-sensitive, does not have to be URL-encoded (accepting Unicode paths), may be cross-host, may occur multiple times without a documented count limit, and is entirely independent of user-agent groups.
   - The current code uses PHP's `FILTER_VALIDATE_URL`, which operates on ASCII URLs only. This incorrectly flags perfectly valid Unicode sitemap paths as invalid.

### Safe target architecture

1. Separate the RFC 9309 parsing boundary from the Google-specific execution profile.
2. Explicitly map Errata 7995 as a reported/proposed correction, not as absolute RFC text, while handling the difference between standard and Google path semantics.
3. Replace the current `FILTER_VALIDATE_URL` Sitemap check with a compatibility-safe target validation that satisfies the actual Google contract:
   - Must allow Unicode / non-URL-encoded provider compatibility.
   - Must enforce absolute/fully-qualified URLs (protocol + host).
   - Must allow multiplicity and cross-host semantics.
   - Must preserve independence from user-agent groups.

*Characterization tests must capture the current ASCII-only `FILTER_VALIDATE_URL` behavior before migration.*

## F-07 — `crawl-delay` is modeled as if it were core robots behavior

**Decision:** `RECLASSIFY`  
**Risk:** Medium  
**Area:** Robots extensions

### Repository evidence

`RobotsRuleDTO` has:

```php
public int|float|null $crawlDelay = null
```

and examples present crawl delay as part of ordinary robots output.

### Standards position

`crawl-delay` is not part of RFC 9309's standard Allow/Disallow grammar.

Google does not support it as a Google robots directive.

Other crawlers may implement non-standard extensions.

### Correct conclusion

Do not delete it automatically.

Instead:

- mark it as a non-standard crawler extension.
- do not describe it as RFC conformance.
- do not describe it as a Google-effective directive.
- consider an extension mechanism so provider-specific directives do not become core protocol fields over time.

---

## F-08 — `MetaRobotsBuilder` rejects valid Google `-1` semantics

**Decision:** `FIX`  
**Risk:** High  
**Area:** Google robots meta

### Repository evidence

`maxSnippet()` and `maxVideoPreview()` call a non-negative assertion.

Current tests explicitly expect:

```php
maxSnippet(-1)
```

and:

```php
maxVideoPreview(-1)
```

to throw `SeoInvalidArgumentException`.

### Current Google position

Google documents `-1` as a special valid value for:

- `max-snippet:-1`
- `max-video-preview:-1`

meaning no practical publisher-imposed maximum / Google may choose the effective preview length.

### Correct conclusion

This is a confirmed behavior bug relative to the Google-specific helper semantics.

The existing test is therefore a characterization of an incorrect contract and must be intentionally replaced, not preserved blindly.

### Safe change rule

- `-1` becomes valid.
- values `< -1` remain invalid.
- current zero and positive-value behavior remains covered.
- replacement / deduplication behavior remains unchanged.

---

## F-09 — `indexifembedded` is missing from the typed Google robots meta helpers

**Decision:** `ADD`  
**Risk:** Medium  
**Area:** Google robots meta

### Current Google position

Google currently supports `indexifembedded`, which only has an effect together with `noindex`.

### Safe target

Add typed support additively.

Do not remove the existing raw `add()` escape hatch.

A future Google-profile validator may additionally warn if `indexifembedded` is used without `noindex`.

---

## F-10 — `unavailable_after` is accepted without date validation

**Decision:** `ADD` / `RECLASSIFY`  
**Risk:** Medium  
**Area:** Google robots meta

### Repository evidence

`unavailableAfter(string $value)` accepts caller-provided text without validating it.

### Current Google position

Google requires a broadly recognized date/time format, including examples such as RFC 822, RFC 850, and ISO 8601. Invalid values are ignored.

### Correct conclusion

The generic builder can remain permissive if it is intentionally a raw directive builder.

However, a Google-conformance path must be able to distinguish:

- syntactically supplied directive
- provider-recognizable value

Do not overfit validation to one date format when Google explicitly accepts multiple recognized formats.

---

## F-11 — `noarchive` is valid to preserve but its Google meaning is stale

**Decision:** `KEEP` + `RECLASSIFY` + `DOC-FIX`  
**Risk:** Low/Medium  
**Area:** Google robots meta documentation

### Repository evidence

The method documentation says:

> Prevent search engines from showing a cached copy of the page.

### Current Google position

Google Search documents `noarchive` as historical / unused because the cached-link feature no longer exists.

### Correct conclusion

Keep the helper for generic compatibility.

Correct the documentation so it does not imply current Google effectiveness.

This finding is an example of why provider behavior must not be embedded as timeless method semantics.

---

## F-12 — Core SEO validation conflates validity with heuristics

**Decision:** `RECLASSIFY` + architectural `ADD`  
**Risk:** High  
**Area:** Validation architecture

### Repository evidence

`SeoMetaValidator` reads defaults such as:

- title min 10
- title max 60
- description min 50
- description max 160

`SeoValidationPreset::strict()` uses:

- title 20..60
- description 80..155

Length violations produce warnings and those warnings can affect scoring.

### Unicode Measurement Heuristics

The current validator uses `strlen()` to measure title and description length.
- The current behavior relies on a byte-length heuristic, which is not a Unicode-aware character count.
- Arabic and other non-ASCII content may trigger length warnings differently than ASCII text with the same number of visible characters.
- Remediation must explicitly define the unit of measurement (e.g., bytes, Unicode code points, or grapheme clusters).
- Do not assume `mb_strlen()` or grapheme counting is the immediate solution before defining the public heuristic contract.
- Characterization tests must cover both ASCII and Unicode/Arabic content before changing the measurement behavior.
- Any change may impact issue generation and scoring, and thus must not be done as an undocumented side effect.

### Current Google position

Google explicitly states:

- there is no fixed length limit for the HTML `<title>` element; display may be truncated.
- there is no fixed length limit for meta descriptions; snippets may be truncated as needed.

### Correct conclusion

Length heuristics are useful.

They are not invalid.

What is wrong is treating them as if they were part of a generic validity model without an explicit heuristic classification.

### Safe target architecture

The validation pipeline should distinguish issue origins such as:

```text
protocol
provider
heuristic
content-quality
```

The exact API shape can be decided during remediation, but the semantic distinction must become real.

### What must not be done

Do not simply delete title and description recommendations.

Do not silently stop scoring them without deciding compatibility for existing score consumers.

Do not call them Google limits.

---

## F-13 — Open Graph required-field model mismatches OGP and omits full exposed contract details

**Decision:** `FIX` (Protocol Profiling)
**Risk:** High  
**Area:** Social Metadata

### Repository evidence

- `src/Web/Social/OpenGraphBuilder.php`
- `src/Web/Social/SocialImage.php`
- Related rendering and validation tests

### The problem

The `SeoMetaValidator` enforces `og:description` as a required field for 100% validity and generates issues if it is missing or exceeds generic string lengths.

According to the official Open Graph Protocol (OGP), only **four** properties are strictly required for every page:
- `og:title`
- `og:type`
- `og:image`
- `og:url`

`og:description` is optional in the OGP protocol, yet the library currently scores it as a required semantic component.

Furthermore, the existing Open Graph builders expose several other properties (such as `og:site_name` and support for multiple images via arrays) whose semantic rules and serialization ordering guarantees are undocumented and unverified against the provider contract.

### Safe target architecture

First establish an explicit OGP-conformance validator/profile that enforces the 4 true required properties.

Additionally, the audit of the OGP provider contract must fully cover every capability actually exposed by `OpenGraphBuilder` and `SocialImage`, explicitly documenting the behavior according to OGP. This includes at a minimum:
- `og:site_name`: optional string.
- `og:determiner`: enum of (a, an, the, "", auto)
- `og:locale`: format `language_TERRITORY`
- URL datatypes: OGP specifies URLs must use `http://` or `https://` schemes.
- audio/video URL semantics
- `og:image:secure_url`: an alternate URL to use if a webpage requires HTTPS
- `og:image:type`: MIME type
- `og:image:width`: integer
- `og:image:height`: integer
- `og:image:alt`: string. The OGP protocol officially recommends providing this whenever `og:image` is used. It must be explicitly classified as a **protocol-level recommendation / optional structured property**, not a heuristic recommendation and not a required validity rule.

#### Array and Multiplicity Ordering Semantic Contract
The library currently supports multiple images. OGP states that multiple properties of the same name are considered arrays. Crucially, when there are conflicts or structured properties attached to a root property (e.g. `og:image:width` attached to `og:image`), **the protocol dictates that properties are ordered from top to bottom, and structured properties are grouped sequentially after their root property**.
- This represents a strict serialization contract.
- A non-regression requirement must be established to guarantee that the ordering of the first image and its associated structured properties is maintained exactly during serialization.
- Characterization tests must be added to map current multiple-image ordering behavior before refactoring.

Then decide how the legacy `SeoMetaValidator` should migrate to that profile.

## F-14 — Canonical URL behavior is permissive by design; relative canonical must not be mislabeled as universally invalid

**Decision:** `KEEP` generic behavior + `ADD` stricter provider/best-practice profile  
**Risk:** Medium  
**Area:** Canonical

### Repository evidence

`CanonicalUrlBuilder` intentionally supports:

- empty builder.
- absolute path passed without base URL.
- relative path passed without base URL.

Tests explicitly preserve relative output.

### Current Google position

Google supports relative canonical paths but explicitly recommends absolute URLs because relative paths can cause long-term operational mistakes.

### Correct conclusion

Relative canonical is not a universal syntax error.

Therefore:

- keep generic builder compatibility.
- add strict validation or diagnostics for Google best-practice usage.
- classify a relative canonical as provider/best-practice concern, not global protocol invalidity.

### What must not be done

Do not change `CanonicalUrlBuilder::build()` to throw on relative paths without a migration strategy.

That would break an intentional, tested public behavior for a recommendation rather than a universal validity rule.

---

## F-15 — Hreflang has multiple normalization paths and lacks provider-aware cluster validation

**Decision:** `RECLASSIFY` + `ADD`  
**Risk:** High  
**Area:** International SEO

### Repository evidence

Hreflang behavior is implemented through more than one path:

1. `src/Web/Hreflang/HreflangLinkDTO.php`
2. `src/Shared/DTO/Sitemap/SitemapAlternateUrlDTO.php`
3. `src/Web/Sitemap/SitemapXmlStringRenderer.php`

`HreflangLinkDTO` validates one link for non-empty hreflang and absolute URL, then normalizes by lowercasing the first subtag and uppercasing every later subtag.

Examples:

```text
zh-Hant     -> zh-HANT
zh-Hans-US  -> zh-HANS-US
```

The sitemap path uses a different permissive regex and lowercases the full hreflang value for output.

This means equivalent hreflang concepts do not currently share one normalization/validation source of truth.

### Standards and provider interpretation

BCP 47 comparisons are case-insensitive, and case regularization is optional. Therefore `zh-HANT` is not, by casing alone, proof of an invalid language tag.

However, BCP 47's conventional casing distinguishes:

- language subtags: lowercase;
- script subtags: title case, for example `Hant`;
- alphabetic region subtags: uppercase.

Google's current hreflang documentation supports:

- ISO 639-1 language codes;
- optional ISO 3166-1 Alpha 2 regions;
- explicit ISO 15924 scripts such as `zh-Hant` and `zh-Hans`;
- combinations such as `zh-Hans-US`;
- `x-default`.

The current repository paths do not consistently prove that a syntactically accepted subtag is actually in the provider-supported language/region/script sets.

### Current Google cluster requirements

Google also documents:

- each language version should list itself and the relevant alternates;
- alternate URLs must be fully-qualified;
- reciprocal / bidirectional links matter for processing;
- sitemap hreflang annotations require the same alternate set to be represented per localized URL.

### Architecture conclusion

The casing difference is primarily a canonicalization/consistency issue, not sufficient evidence of provider invalidity by itself.

The material architecture gaps are:

- duplicate hreflang normalization/validation implementations;
- permissive syntax that is not equivalent to provider-supported code validation;
- absence of deterministic cluster-level checks for self-reference and reciprocal relationships.

A single-link DTO cannot validate cluster rules such as reciprocity.

### Safe target

Separate and unify:

1. Shared hreflang parsing / normalization semantics used by Web and Sitemap entry points.
2. Generic language-tag syntax where a generic contract is needed.
3. Google provider-supported language/region/script validation.
4. Cluster integrity:
   - self-reference
   - reciprocal relationships
   - complete/consistent alternate groups
   - fully-qualified URLs

### What must not be done

Do not treat capitalization alone as a Google-invalidity error.

Do not hide network crawling inside the DTO to prove reciprocity.

The host or caller should supply cluster data / evidence to a deterministic validator.

---

## F-16 — Structured Data architecture correctly separates Schema.org from Google eligibility, but the implementation needs explicit provider profiles

**Decision:** `KEEP` current principle + `ADD` provider model  
**Risk:** High  
**Area:** Structured Data

### Repository evidence

`docs/SEO/library/STRUCTURED_DATA_ARCHITECTURE.md` correctly states:

- validation is not complete Schema.org validation.
- generation does not guarantee Google Rich Results eligibility.
- deep semantic validation is currently limited to:
  - Product
  - Offer
  - AggregateOffer
  - ProductGroup

This separation is architecturally correct and must be preserved.

### Current Google position

Google's own structured-data documentation states that:

- Schema.org is the primary vocabulary source for most structured data supported in Google Search.
- Google Search Central feature-specific documentation is the authority on Google eligibility, required properties, recommendations, composition rules, and policies.
- Google eligibility must not be inferred from Schema.org validity alone.
- Google requires specific required properties for eligibility.
- Schema.org may contain additional properties not required by Google.

### Missing architectural capability

The library currently lacks an explicit, first-class model for:

```text
Schema.org conformance
vs
Google Search eligibility profile
```

As a result, builders can exist for many Schema.org types while the library cannot clearly answer:

- Is this type valid vocabulary?
- Does Google currently support a related search appearance?
- Which properties are required by Google today?
- Is support conditional on a carousel or page-level structure?
- Is the feature active, changed, or provider-specific?

### Safe target

Maintain a date-stamped provider capability matrix sourced from official documentation.

The runtime architecture should allow provider eligibility rules to evolve without changing generic Schema.org builders.

---

## F-17 — State actual Course / Book provider status

**Decision:** `CORRECTION` / `KEEP` until current provider evidence says otherwise  
**Risk:** High if ignored  
**Area:** Structured Data provider status

### Why this correction exists

A preliminary review previously risked overgeneralizing older Google structured-data removals.

The re-check for this audit found current official evidence that makes blanket removal/deprecation claims unsafe.

### Course

- Google's older **Course Info** search appearance was removed and its documentation was removed because that search appearance no longer appears in Google Search.
- Google's current **Course List** structured-data documentation still exists.
- Therefore a blanket statement that Course structured data is unsupported by Google is incorrect.

### Book

- Current Book / Book Actions documentation still exists.
- Book Actions are not equivalent to a generic normal rich-result contract available to every page.
- They involve provider-specific participation, feed, and eligibility requirements.
- Generic Schema.org Book vocabulary support must remain separate from Google Book Actions eligibility.

Provider-status evidence hierarchy must use, as applicable:

- feature-specific current documentation,
- Google Search documentation updates,
- Search Gallery.

Do not treat Search Gallery as the sole authority for every provider capability.

---

## F-18 — `JsonLdSemanticValidator` is scoped type/range validation, not complete semantic or lexical validation

**Decision:** `RECLASSIFY` + incremental `ADD`  
**Risk:** Medium  
**Area:** Structured Data validation

### Repository evidence

The validator checks selected property ranges for:

- Product
- Offer
- AggregateOffer
- ProductGroup

For scalar classes such as:

- URL
- Date
- DateTime
- ItemAvailability
- OfferItemCondition

some checks currently amount to "non-empty string" representation checks rather than full lexical / vocabulary validation.

### Correct conclusion

The validator is useful and should be preserved.

But documentation and naming must not imply complete Schema.org semantic proof.

A more precise description is:

> scoped structural and property-range semantic validation for selected types.

### Safe remediation choices

Possible future steps include:

- lexical URL validation where appropriate.
- date / datetime lexical validation.
- enumeration validation where the provider/vocabulary contract is explicit.
- provider-specific required-property checks in provider profiles.

These must be introduced incrementally because new errors change validation results and scores.

---

## F-19 — Documentation and CHANGELOG do not accurately describe current repository behavior

**Decision:** `DOC-FIX`  
**Risk:** High  
**Area:** Documentation truth

### A. CHANGELOG is incomplete for the audited post-RC history

At the audited `main@1e2cca61...` baseline, `CHANGELOG.md` under `1.0.0 - Unreleased` listed only three sitemap fixes.

At the reviewed Phase 23 integration snapshot, one Phase 23 Merchant Center entry has also been added, but substantial earlier post-RC work remains absent from the Unreleased history.

The repository history also contains substantial additions such as:

- advanced Product structured data.
- ProductGroup / AggregateOffer composition.
- structured-data semantic validation.
- CLI examples.
- CI / quality work.
- Search Console provider boundary.

The changelog therefore does not represent the actual change history from the previous tagged state through the reviewed Phase 23 integration snapshot.

This is a documentation integrity problem independent of release decisions.

### B. CHANGELOG uses inaccurate "stream" wording

The RC history describes Phase 4 sitemap generation as producing / streaming valid XML.

Actual implementation uses `XMLWriter::openMemory()` and returns complete XML strings.

Documentation should say "generate/render in-memory XML strings" unless true streaming is implemented.

### C. Phase 22 status contradicts repository history

`docs/phases/PHASE_22_SEARCH_CONSOLE_EXTERNAL_VERIFICATION.md` says:

- `Final Review: pending`
- phase is not complete until Final Review passes.

But the current main commit itself records Phase 22 completion and Final Review PASS.

The phase document is stale.

### D. README overstates "strict" validation if read as covering provider extensions

README says sitemap output has strict URL/date/frequency/priority validation.

That is reasonable for the base fields but can be misread as strict provider eligibility validation for Image, Video, and News extensions, which is not currently true.

The documentation should qualify exactly which layer is validated.

### E. Example contains invalid Google News date

`examples/sitemap-output.php` uses:

```php
publicationDate: 'as-provided'
```

for a Google News sitemap DTO.

This must not remain as a canonical usage example if the documented goal is Google News compatibility.

---

## F-20 — The repository needs one normative documentation hierarchy

**Decision:** `ADD` + `RECLASSIFY`  
**Risk:** High  
**Area:** Documentation architecture

### Current state

Repository truth is spread across:

- README
- engineering handbook
- phase documents
- blueprints
- verification reports
- roadmap documents
- usage / integration guides
- changelog

Historical evidence and normative contracts are mixed.

### Safe target

Documentation should distinguish:

1. **Normative current contract**
   - current architecture
   - current public APIs
   - current standards/provider profiles
   - current limitations

2. **Historical implementation evidence**
   - phase reports
   - verification reports
   - old blueprints

3. **Future proposals**
   - roadmap
   - RFC/proposals

Historical verification reports should not silently override current code or current provider documentation.

---

## F-21 — Twitter/X Cards provider contract was not source-verified by this audit

**Decision:** `ADD` (Out of Scope Statement)
**Risk:** Medium
**Area:** Social Metadata

### Repository evidence

The repository contains:
- `src/Web/Social/TwitterCardBuilder.php`
- Twitter validation inside `SeoMetaValidator`
- Tests covering Twitter fields
- README announcing Twitter Card support

### Audit Scope Limitation

The current audit reviewed Open Graph Protocol provider behavior, but did not independently verify Twitter/X Card rules against a current, official Twitter/X documentation source.

### Safe Target

- Twitter/X provider conformance was **not source-verified by this audit**.
- Generic/current library compatibility for Twitter Cards remains preserved as-is.
- Twitter/X provider-rule remediation is out of scope until a dedicated official-source revalidation is completed.
- The documentation should reflect that Open Graph was audited, but Twitter/X Cards remain under historical implementation assumptions pending future review.

# 6. Required Architecture Principles Before Remediation

The following rules should be treated as constraints for all later fixes.

## AP-01 — One source of truth per serialization domain

If two public APIs need the same XML/HTML/JSON semantics, they should delegate to one canonical internal implementation rather than reimplement the same rules.

## AP-02 — Public façades are not implementation engines

A public class may remain for compatibility even after its internals are delegated elsewhere.

Removing duplication does not require deleting public entry points.

## AP-03 — Protocol, provider, and heuristic rules are separate layers

Examples:

- RFC 9309 product-token grammar -> protocol.
- Google `indexifembedded` -> provider.
- title 60-character preference -> heuristic.

They must not share one undifferentiated "invalid SEO" meaning.

## AP-04 — Provider rules must be date-stamped and source-backed

Google Search behavior evolves.

Every provider capability matrix or provider validation profile should record:

- authoritative source.
- verification date.
- supported feature name.
- required properties.
- recommended properties.
- provider limitations.

## AP-05 — Generic builders remain provider-neutral

A Schema.org builder may remain valid even if Google changes a search feature.

A generic canonical builder may allow valid URL forms even if Google recommends a stricter best practice.

## AP-06 — Characterization before refactor

Before replacing an internal engine, capture current public behavior.

Characterization tests are especially mandatory for:

- sitemap rendering.
- canonical rendering.
- robots output.
- validation issue codes.
- score calculations.
- public DTO serialization.

## AP-07 — Intentional standards fixes may change old tests

Not every old behavior must be preserved.

Examples already confirmed:

- rejecting `max-snippet:-1` is wrong for the Google helper contract.
- current OGP required-field logic does not match OGP basic metadata.

The old test should be replaced only when:

1. the mismatch is proven,
2. the source is recorded,
3. the behavior change is isolated,
4. unaffected behavior remains characterized.

## AP-08 — No hidden network behavior in deterministic validators

Cluster/provider validation must operate on caller-provided data.

Do not add HTTP calls, Search Console calls, Rich Results scraping, or live crawling inside deterministic validation services.

## AP-09 — No global-score semantics from provider evidence without an explicit contract

Search Console evidence remains separate from offline validation unless a future architecture explicitly defines a composed report.

The current Phase 22 separation should be preserved.

## AP-10 — Documentation is part of the contract

A fix is incomplete if:

- code says one thing,
- tests say another,
- current normative docs say a third.

---

# 7. Safe Remediation Order

This audit recommends the following order because it minimizes the chance that a later architectural change invalidates earlier work.

This is **not a release gate**. It is a dependency-safe implementation order.

---

## Stack 0 — Contract Characterization

### Goal

Capture existing public behavior before architecture changes.

### Required coverage

- `SitemapGeneratorService`
- `SitemapXmlStringRenderer`
- `SitemapIndexXmlStringRenderer`
- both `SitemapIndexEntryDTO` contracts
- `MetaRobotsBuilder`
- `RobotsRuleDTO`
- `RobotsTxtRenderer`
- `SeoMetaValidator`
- `SeoValidationPreset`
- score/report builders
- `OpenGraphBuilder`
- `MetaGeneratorService`
- `CanonicalUrlBuilder`
- `HreflangLinkDTO`
- `HreflangLinkBuilder`
- structured-data validation issue output

### Deliverable

A behavior inventory that explicitly marks each behavior:

- preserve
- intentionally change
- unknown / needs decision

No production refactor should start until this inventory exists.

---

## Stack 1 — Standards and Provider Taxonomy

### Goal

Create the architecture distinction between:

- protocol/vocabulary
- provider
- heuristic

### Important constraint

This stack should avoid changing user-facing behavior where possible.

It establishes the vocabulary that later code uses.

---

## Stack 2 — Robots

### Why first

Robots has:

- a formal RFC,
- clear current code,
- small surface area,
- several confirmed corrections.

### Order

1. RFC 9309 rule validation (allow `*`, empty paths, correct RFC identifiers).
2. Explicit CR/LF injection prevention.
3. Line-by-line renderer with provider document-level constraints (e.g. 500 KiB boundary warning, UTF-8 encoded plain text output).
4. Preserve non-standard extensions separately.
5. Implement explicit separation between RFC 9309 behavior, Errata 7995 (mapping it explicitly as a proposed correction, not absolute text), and Google provider path parsing rules.
6. Implement Google `Sitemap:` contract validation:
   - Introduce compatibility-safe validation covering Unicode / non-URL-encoded provider compatibility.
   - Enforce absolute/fully-qualified URLs (protocol + host).
   - Support multiplicity (multiple `Sitemap:` fields without limits).
   - Explicitly allow cross-host semantics.
   - Verify independence from user-agent groups.
7. Implement Google-specific path parsing behavior (e.g. leading `/` rules).
8. Fix `-1` meta robots behavior.
9. Add `indexifembedded`.
10. Correct `noarchive` documentation.
11. Add provider-aware `unavailable_after` validation.
12. Update examples/tests/docs.

### Stop condition

Do not move on while generic robots.txt and Google robots-meta concepts are still described as one standard.

---

## Stack 3 — Sitemap Core Unification

### Goal

Remove duplicate serialization logic without removing public APIs.

### Order

1. Characterize XML outputs.
2. Introduce internal canonical serialization.
3. Delegate both public paths.
4. Reconcile duplicate `SitemapIndexEntryDTO` concepts through a compatibility strategy.
5. Confirm extended DTO data has deterministic behavior from each public entry point.

### Stop condition

There must be one rule implementation for equivalent sitemap output.

---

## Stack 4 — Sitemap Standards / Google Extensions

### Goal

Add layered validation.

### Required coverage

#### Base sitemap
- URL sitemap count limit (50,000 URLs).
- Sitemap-index count limit (50,000 Sitemaps).
- uncompressed byte-size limits (50 MB).
- page `<loc>` must be less than 2,048 characters.
- host/site/submission-context rules.
- cross-submission semantics.
- UTF-8 encoding requirements.
- XML entity-escaping and URL URI/IRI escaping requirements.
- sitemap `lastmod` accepted/rejected lexical forms (including W3C Datetime).
- fractional-seconds characterization for current implementation behavior.
- distinction between URL-sitemap and Sitemap-Index `lastmod` semantics where contextually representable.
- Verification of XMLWriter UTF-8 and serialization guarantees.

#### Google Image
- current/deprecated field classification.
- 1,000-images-per-URL limit.
- cross-domain verification context.
- external crawlability context.

#### Google Video
- existing content/player-presence behavior characterization.
- description limit.
- duration range.
- publication-date forms (W3C Datetime).
- parent `<loc>` relationship.
- host-page relevance content-context rule (external evidence).
- document `content_loc` preference as a provider recommendation.
- deterministic versus external/provider-evidence rules (e.g. remote format verification).
- video title/description serialization semantics (XML/CDATA escaping).
- thumbnail contract classification (URL shape vs external image format, dimensions, stability, accessibility, and transparency).
- Implement Google Video explicit profile with entry, document, and serialization levels.
- Document explicitly supported video file types (not MIME types).
- Explicitly reject Data URLs and streaming protocols (HTTP/HTTPS/FTP only).
- Explicitly separate Googlebot resource-accessibility boundaries as external/provider-evidence.

#### Google News
- language contract (ISO 639 formatting, including Google's `zh-cn`/`zh-tw` exceptions).
- exact date forms (W3C Datetime).
- publication-name semantics (exactly matching Google News publication name, avoiding parentheticals).
- title semantics (excluding author, publication, date).
- 1,000-entry limit per News sitemap (document level).
- One News entry per URL valid vs multiple entries provider-invalid/diagnostic (compatibility-safe cardinality remediation).
- two-day metadata window with explicit caller-supplied reference time.
- legacy optional-field provider-status classification.

### Critical constraint

Document-level rules must not be forced into single-entry constructors when the required context is unavailable.

---

## Stack 5 — Validation Architecture + Open Graph

### Goal

Stop mixing heuristic recommendations with protocol validity.

### Order

1. Introduce issue origin/profile semantics.
2. Establish OGP protocol validation.
3. Preserve legacy issue/score behavior until migration is explicitly decided.
4. Reclassify title/description length warnings as heuristics.
5. Make an explicit decision on title/description measurement units (bytes, code points, etc.).
6. Add characterization tests for ASCII and Arabic/Unicode title/description lengths before altering `strlen()` usage.
7. Declare Twitter/X provider conformance out of scope until an official-source revalidation is conducted.
8. Implement strict OGP DTO profile (only 4 required) and formalize remaining supported optional properties (determiner, locale, site_name, HTTP/HTTPS URL datatypes, exact top-to-bottom structured-property ordering semantics for multiple images, and `og:image:alt` as a protocol-level recommendation).
9. Decide how heuristic warnings participate in scores.
10. Align dedicated social builders and legacy `MetaTagsDTO` path.

### Critical constraint

Do not silently change existing score math while changing semantic categories.

---

## Stack 6 — Canonical and Hreflang

### Canonical

- keep generic relative behavior.
- add provider best-practice diagnostics for absolute canonical URLs.

### Hreflang

- unify Web and Sitemap hreflang parsing / normalization semantics.
- use canonical BCP 47 casing when normalization is performed, without treating casing alone as provider invalidity.
- validate provider-supported language/region/script shape.
- add cluster-level validation.
- preserve deterministic behavior and host ownership.

---

## Stack 7 — Structured Data Profiles

### Goal

Preserve generic builders while making provider support explicit.

### Required output

A current Google structured-data capability matrix containing at minimum:

- Schema.org type.
- library builder.
- Google search feature, if applicable.
- required properties.
- recommended properties.
- composition requirements.
- known provider limitations.
- official URL.
- verified date.

### Important correction

Do not use stale assumptions about Course, Book, Dataset, or other features.

Verify each provider feature at implementation time.

---

## Stack 8 — Documentation Truth Synchronization

### Goal

Make the repository tell one coherent story.

### Required work

- complete CHANGELOG history from repository evidence.
- correct Phase 22 status.
- correct sitemap "streaming" wording.
- correct Google News example.
- qualify deprecated provider fields.
- qualify validation depth.
- establish normative-vs-historical docs hierarchy.
- ensure README feature claims match actual code.

---

# 8. Anti-Destruction Rules

These are explicit protections against "fixing" the library into a worse state.

## ADR-01

**Do not delete a public API merely because its current implementation is duplicated.**

First delegate it to one engine.

## ADR-02

**Do not remove Schema.org builders because a Google feature changes.**

Vocabulary support and provider eligibility are separate.

## ADR-03

**Do not convert provider recommendations into generic constructor exceptions.**

Example: relative canonical.

## ADR-04

**Do not preserve an old test when authoritative evidence proves the behavior is wrong.**

Example: `max-snippet:-1`.

## ADR-05

**Do not change scoring as a side effect of refactoring validation layers.**

Scoring changes need their own explicit contract decision.

## ADR-06

**Do not make DTOs depend on hidden runtime context they cannot possess.**

Example: sitemap document host/path/byte-size checks.

## ADR-07

**Do not make deterministic validators perform provider HTTP calls.**

Provider evidence remains an explicit boundary.

## ADR-08

**Do not describe deprecated-but-preserved fields as currently effective provider features.**

Compatibility support must be distinguished from recommendation.

## ADR-09

**Do not trust phase reports over current code.**

Historical verification proves what was checked at that time, not current truth.

## ADR-10

**Do not trust memory for current provider support.**

Provider facts must be rechecked against official current documentation.

---

# 9. Test Strategy Required for the Remediation

The test strategy must cover the contracts introduced by the findings, not only representative examples.

## 9.1 Characterization tests

Add characterization tests for:

- ASCII title and description strings.
- Arabic/Unicode title and description strings (to isolate the `strlen()` byte-length behavior before changing it).

Purpose: preserve behavior before refactoring.

Examples:

- exact XML for base sitemap DTO.
- exact XML for extended sitemap DTO.
- raw array normalization.
- canonical relative and absolute output.
- robots directive order and replacement semantics.
- current validation issue codes and score propagation.

## 9.2 Standards conformance tests

Purpose: prove formal protocol behavior.

Add explicit boundary cases including at minimum:

### Sitemap core

- 50,000 valid / 50,001 invalid/issue URL entries.
- 50,000 valid / 50,001 invalid/issue sitemap-index entries.
- uncompressed-size boundary at 52,428,800 bytes, over-boundary case.
- page URL `<loc>` length boundary: 2,047 characters valid at this protocol length boundary, 2,048 characters invalid because the protocol requires less than 2,048.
- host/submission cases must validate explicit context behavior rather than unconditional same-host rejection.
- UTF-8 encoding requirements.
- XML entity-escaping and URL URI/IRI escaping requirements.
- sitemap `lastmod` accepted/rejected lexical forms (including W3C Datetime).
- fractional-seconds characterization for current implementation behavior.
- distinction between URL-sitemap and Sitemap-Index `lastmod` semantics where contextually representable.

### Robots

- valid `*` product-token.
- valid RFC identifier.
- invalid identifier containing digits.
- empty Allow/Disallow.
- ordinary slash-path cases.
- wildcard path characterization including leading `*`.
- raw `#`.
- `%23`.
- CR/LF injection cases.

### Google Robots.txt and Provider Constraints

- file size limit behavior (500 KiB boundary) as a documented provider document-level constraint.
- characterization tests for current ASCII-only `FILTER_VALIDATE_URL` Sitemap behavior.
- Google robots `Sitemap:` Unicode sitemap paths acceptable to Google.
- Google robots `Sitemap:` deterministic rules (valid absolute URLs, multiplicity, cross-host, group-independence).
- Google-specific robots path cases (leading `/` handling).

### Open Graph Protocol
- supported OGP lexical/structural contracts (e.g., HTTP/HTTPS URL datatypes, `og:locale` format, `og:site_name`, integer constraints for dimensions).
- proper top-to-bottom property grouping for multiple arrays (characterization for multiple-image ordering and non-regression for first image/property ordering).
- explicit distinction for the `og:image:alt` protocol-level recommendation vs validity.

## 9.3 Provider profile tests

Add explicit boundary cases including at minimum:

### Google Image

- 1,000 images valid
- 1,001 images invalid/issue

### Google Video

- Description: 2,048 valid, 2,049 invalid/issue.
- Duration: 1 valid, 28,800 valid, 28,801 invalid/issue.
- both `content_loc` and `player_loc` missing.
- `content_loc == parent <loc>`.
- `player_loc == parent <loc>`.
- deterministic locally provable cases.
- publication-date tests for both documented accepted forms plus invalid input.
- rejection of Data URLs for `content_loc`.

### Google News

- one News entry per URL valid.
- multiple News entries on one URL provider-invalid/diagnostic.
- 1,000 total News entries boundary (valid) distinct from per-URL cardinality.
- 1,001 total News entries boundary (invalid/issue).
- all four documented publication-date forms
- invalid arbitrary date
- valid/invalid language cases (including `zh-cn`/`zh-tw` exceptions).
- exact two-day-window boundary using explicit caller-supplied reference time.

Examples:

Google robots meta:

- `max-snippet:-1`.
- `max-video-preview:-1`.
- `indexifembedded` with `noindex`.
- `unavailable_after` recognized/unrecognized values.

Hreflang:

- `en`
- `en-US`
- `zh-Hant`
- `zh-Hans-US`
- `x-default`
- equivalent normalization through Web and Sitemap entry points
- unsupported-but-regex-shaped language/region/script values
- reciprocal cluster
- missing self-reference
- missing return link

## 9.4 Non-regression tests

Every standards fix must prove that unrelated behavior remains unchanged.

Example:

Changing `maxSnippet(-1)` must not change:

- directive insertion order.
- prefix replacement.
- duplicate removal.
- HTML escaping.

---

# 10. Documentation Model Recommended After Remediation

A global-quality repository should make it obvious which documents are current contracts.

Recommended conceptual hierarchy:

```text
README.md
  -> concise package entry point

docs/architecture/
  -> current normative architecture

docs/standards/
  -> protocol/vocabulary contracts
  -> provider profiles
  -> verified dates and official sources

docs/guides/
  -> current usage

docs/audits/
  -> point-in-time audits such as this file

docs/verification/
  -> historical verification evidence

docs/phases/
  -> historical implementation lifecycle

docs/roadmap/
  -> future work, not current truth
```

The exact paths can be adjusted to repository conventions, but the authority levels must be explicit.

---

# 11. Findings Summary

| ID | Finding | Decision | Risk |
|---|---|---|---|
| F-01 | Two independent sitemap serialization paths | FIX | High |
| F-02 | Base sitemap vs provider policies not separated | ADD / RECLASSIFY | High |
| F-03 | Google Image legacy tags and provider constraints need explicit classification | RECLASSIFY | Medium |
| F-04 | Video sitemap provider constraints incomplete | ADD | High |
| F-05 | News sitemap provider validation/context incomplete; example invalid | ADD / DOC-FIX | High |
| F-06 | robots.txt DTO mismatches RFC 9309 grammar | FIX | High |
| F-07 | crawl-delay presented like core REP behavior | RECLASSIFY | Medium |
| F-08 | Meta robots rejects valid Google `-1` | FIX | High |
| F-09 | `indexifembedded` missing | ADD | Medium |
| F-10 | `unavailable_after` provider date semantics unchecked | ADD / RECLASSIFY | Medium |
| F-11 | `noarchive` Google meaning stale | KEEP / DOC-FIX | Low/Medium |
| F-12 | SEO validity and heuristics conflated | RECLASSIFY / ADD | High |
| F-13 | Open Graph required-field model mismatches OGP | FIX | High |
| F-14 | Relative canonical must remain generic-compatible | KEEP / ADD profile | Medium |
| F-15 | Hreflang has duplicate normalization paths and lacks provider-aware cluster validation | RECLASSIFY / ADD | High |
| F-16 | Structured Data needs explicit provider profiles | KEEP principle / ADD | High |
| F-17 | Old Course/Book deprecation assumption is unsafe | CORRECTION | High |
| F-18 | JSON-LD "semantic" validation is scoped, not complete lexical validation | RECLASSIFY / ADD | Medium |
| F-19 | CHANGELOG/docs/examples do not fully match current behavior | DOC-FIX | High |
| F-20 | No explicit normative documentation hierarchy | ADD / RECLASSIFY | High |
| F-21 | Twitter/X Cards provider contract not source-verified | ADD | Medium |

---

# 12. Final Audit Judgment

The repository is **architecturally recoverable without a rewrite**.

The core problem is not lack of features.

The core problem is that some feature areas accumulated overlapping implementations and validation rules without a sufficiently explicit distinction between:

- formal standard,
- provider behavior,
- heuristic recommendation,
- historical compatibility.

The safest remediation is therefore **not** a broad cleanup PR.

The safest remediation is a sequence of narrow contract-preserving stacks, each starting with characterization and authoritative source verification.

The target is:

> one implementation source of truth per domain, provider-neutral core contracts, explicit provider profiles, explicit heuristics, deterministic validation, and documentation that accurately states what the library can and cannot prove.

### Execution Authority Rule

This Audit document serves as the **baseline execution authority** for all remediation stacks. A future implementer must be able to execute Stacks 0 to 8 entirely based on the rules encoded in this document, referencing external official sources only for verification, without needing to reinvent classifications, deduce unwritten behaviors, or restart standard research from scratch.

- **Freshness verification:** Time-variable provider facts can undergo freshness verification during Stack implementation.
- **Source updates:** Freshness verification does not mean reopening decisions or conducting broad standard research without evidence of source alteration. If the official authoritative provider source has genuinely changed after the date of this audit, the change must be explicitly documented and the contract amended before execution proceeds.

No production behavior should be changed merely because it "looks more strict."

A behavior change is justified only when the audit classifies it, its authoritative source is recorded, its compatibility impact is understood, and its non-target behavior is protected by tests.

---

# 13. Authoritative External References

All provider facts should be rechecked again when the corresponding remediation stack starts.

## Standards / protocols

- W3C Datetime
  https://www.w3.org/TR/NOTE-datetime

- PHP `strlen` function documentation
  https://www.php.net/manual/en/function.strlen.php

- PHP Filter Constants (FILTER_VALIDATE_URL)
  https://www.php.net/manual/en/filter.constants.php

- RFC 9309 — Robots Exclusion Protocol  
  https://www.rfc-editor.org/rfc/rfc9309.html

- RFC 9309 Errata 7995 — path-pattern leading wildcard inconsistency
  https://www.rfc-editor.org/errata/eid7995

- RFC 5646 / BCP 47 — Tags for Identifying Languages  
  https://www.rfc-editor.org/rfc/rfc5646.html

- Sitemaps.org Protocol  
  https://www.sitemaps.org/protocol.html

- Open Graph Protocol  
  https://ogp.me/

- Schema.org  
  https://schema.org/

## Google Search

- Build and Submit a Sitemap
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap

- Video SEO Best Practices
  https://developers.google.com/search/docs/appearance/video

- Robots.txt specification (UTF-8, 500 KiB)
  https://developers.google.com/search/docs/crawling-indexing/robots/robots_txt

- Robots meta tag specifications  
  https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag

- Image sitemaps  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/image-sitemaps

- Google sitemap extension deprecation notice  
  https://developers.google.com/search/blog/2022/05/spring-cleaning-sitemap-extensions

- Video sitemaps  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/video-sitemaps

- News sitemaps  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap

- Title links  
  https://developers.google.com/search/docs/appearance/title-link

- Snippets / meta descriptions  
  https://developers.google.com/search/docs/appearance/snippet

- Canonical URLs  
  https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls

- Localized versions / hreflang  
  https://developers.google.com/search/docs/specialty/international/localized-versions

- Structured Data introduction  
  https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data

- Structured Data supported features gallery  
  https://developers.google.com/search/docs/appearance/structured-data/search-gallery

- General Structured Data guidelines  
  https://developers.google.com/search/docs/appearance/structured-data/sd-policies

- Current Course structured data documentation  
  https://developers.google.com/search/docs/appearance/structured-data/course

- Current Book structured data documentation  
  https://developers.google.com/search/docs/appearance/structured-data/book

- Google Search documentation updates  
  https://developers.google.com/search/updates

---

# 14. Repository Evidence Paths

Primary repository evidence referenced during this audit:

- `src/Shared/Service/SitemapGeneratorService.php`
- `src/Web/Sitemap/SitemapXmlStringRenderer.php`
- `src/Web/Sitemap/SitemapIndexXmlStringRenderer.php`
- `src/Shared/DTO/Sitemap/SitemapUrlDTO.php`
- `src/Shared/DTO/Sitemap/SitemapAlternateUrlDTO.php`
- `src/Shared/DTO/Sitemap/SitemapImageDTO.php`
- `src/Shared/DTO/Sitemap/SitemapVideoDTO.php`
- `src/Shared/DTO/Sitemap/SitemapNewsDTO.php`
- `src/Shared/DTO/Sitemap/SitemapIndexEntryDTO.php`
- `src/Web/Sitemap/DTO/SitemapIndexEntryDTO.php`
- `src/Web/Robots/DTO/RobotsRuleDTO.php`
- `src/Web/Robots/DTO/RobotsTxtDTO.php`
- `src/Web/Robots/MetaRobotsBuilder.php`
- `src/Web/Validation/SeoMetaValidator.php`
- `src/Web/Validation/SeoValidationPreset.php`
- `src/Web/Validation/JsonLd/JsonLdSemanticValidator.php`
- `src/Web/Social/OpenGraphBuilder.php`
- `src/Shared/Service/MetaGeneratorService.php`
- `src/Shared/DTO/MetaTagsDTO.php`
- `src/Web/Indexing/CanonicalUrlBuilder.php`
- `src/Web/Hreflang/HreflangLinkDTO.php`
- `src/Web/Hreflang/HreflangLinkBuilder.php`
- `docs/SEO/library/STRUCTURED_DATA_ARCHITECTURE.md`
- `docs/phases/PHASE_22_SEARCH_CONSOLE_EXTERNAL_VERIFICATION.md`
- `CHANGELOG.md`
- `README.md`
- `examples/sitemap-output.php`
- `tests/Batch1AMetaRobotsBuilderTest.php`
- `tests/Phase11ASeoValidationHelpersTest.php`
- `tests/Phase11DSeoValidationPresetsTest.php`
- `tests/Phase15ACanonicalUrlBuilderTest.php`

---

**End of audit.**
