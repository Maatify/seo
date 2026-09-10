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

Host/path rules must not become unconditional same-host DTO rejection. Cross-submission/cross-host behavior can depend on ownership/submission context.

Not all of these constraints belong at the same validation level. The page URL `<loc>` lexical/length rule is entry-level; count, byte-size, and location/context rules are document-level or host/submission-context-level.

### Current implementation limitation

`SitemapUrlDTO` validates a single URL entry but does not know:

- where the sitemap will be hosted,
- the final uncompressed XML byte length,
- total URL count,
- site / path ownership context or cross-submission evidence.

Therefore those rules **cannot safely be pushed into the DTO constructor**. Do not turn host/path rules into unconditional same-host constructor rejection.

### Safe target

The architecture needs separate levels:

1. **Entry validation**
   - URL shape
   - page URL `<loc>` length below 2,048 characters
   - lastmod lexical format
   - changefreq vocabulary
   - priority range
   - child DTO shape

2. **Document validation**
   - entry count
   - output size
   - sitemap index count

3. **Host/submission context rules**
   - document context
   - cross-submission / cross-host behavior

4. **Provider extension validation**
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

**Decision:** `ADD`  
**Risk:** High  
**Area:** Google Video sitemap

### Repository evidence

`SitemapVideoDTO` currently validates:

- thumbnail URL
- non-empty title
- non-empty description
- content/player URL presence
- duration greater than zero
- publication date using the shared sitemap date helper

### Missing current Google constraints

Current Google documentation includes constraints such as:

- `video:description`: maximum 2,048 characters.
- `video:duration`: 1..28,800 seconds.
- `video:publication_date` documented forms:
  - `YYYY-MM-DD`
  - `YYYY-MM-DDThh:mm:ssTZD`
- At least one of:
  - `video:content_loc`
  - `video:player_loc`
  must be supplied.
- `video:content_loc` must not be the same URL as the parent page `<loc>`.
- `video:player_loc` must not be the same URL as the parent page `<loc>`.

Also classify provider requirements concerning:

- supported video resource formats,
- crawlability/accessibility,
- thumbnail requirements.

Every rule must be classified as one of:

1. deterministic entry validation,
2. document/context validation,
3. external/provider-evidence condition.

Do not represent crawlability, remote accessibility, indexing state, or similar external facts as something an offline DTO validator can prove.

### Safe target

Do not make every Google provider rule a universal `SitemapVideoDTO` constructor exception unless the DTO is explicitly defined as a Google Video DTO contract.

A safer architecture is to make provider validation explicit and testable. Clearly distinguish:

- locally deterministic validation,
- document/context validation,
- conditions that require external/provider evidence and therefore must not become fake offline validation.

If the existing type remains specifically Google-oriented, then tightening can be justified, but the behavior change must be treated as intentional and covered by regression tests.

---

## F-05 — Google News sitemap data is structurally accepted without enough provider validation

**Decision:** `ADD` + `DOC-FIX`  
**Risk:** High  
**Area:** Google News sitemap

### Repository evidence

`SitemapNewsDTO` only requires non-empty values for:

- publicationName
- publicationLanguage
- publicationDate
- title

It does not validate:

- ISO 639 language-code semantics.
- The exact currently supported publication-date forms from Google's News sitemap documentation.
- publication language rules.
- publication name semantics.
- title semantics where relevant.
- maximum 1,000 News entries.
- two-day News metadata window.
- current status of legacy optional News fields already exposed by the library.

The repository example `examples/sitemap-output.php` currently uses:

```php
publicationDate: 'as-provided',
```

This is accepted by the DTO but is not a valid Google News publication date.

### Current Google position

Google documents:

- publication language follows Google's documented language rules and documented Chinese exceptions.
- publication date must use supported W3C date forms:
  - `YYYY-MM-DD`
  - `YYYY-MM-DDThh:mmTZD`
  - `YYYY-MM-DDThh:mm:ssTZD`
  - `YYYY-MM-DDThh:mm:ss.sTZD`
- a News sitemap may contain at most 1,000 `<news:news>` entries.
- News sitemap metadata applies to articles created within the last 2 days; older URLs may remain in a general sitemap, but their `<news:news>` metadata should be removed.
- `news:publication/news:name` represents the publication name used by Google News.
- `news:title` represents the article title according to the current Google News sitemap contract.

The current Google News sitemap reference lists the currently supported News sitemap tags and no longer lists the repository's optional `news:access`, `news:genres`, `news:keywords`, or `news:stock_tickers` fields. Absence from the current reference is not, by itself, sufficient evidence to delete public compatibility fields, but their provider status must be explicitly classified before remediation.

### Correct conclusion

This is a real provider-validation gap and also an incorrect example.

The remediation needs two levels:

- entry/provider lexical validation for publication language and publication date;
- document/context validation for the 1,000-entry cap and two-day News metadata window.

The four legacy optional fields must be treated as compatibility/provider-status candidates pending explicit source-backed classification; they must not continue to be presented as current Google News requirements or recommendations without evidence.

### What must not be done

Do not silently change a generic date helper into a News-specific parser if that changes unrelated sitemap behavior.

The News policy should be explicit. The two-day rule must remain deterministic: Do not permit a future validator to call hidden `now()` / system time internally. The architecture must require caller-supplied reference time/context for time-relative validation.

---

## F-06 — `robots.txt` DTO does not conform cleanly to RFC 9309 grammar

**Decision:** `FIX`  
**Risk:** High  
**Area:** Robots Exclusion Protocol

### Repository evidence

`RobotsRuleDTO` currently:

- accepts any non-empty `userAgent`.
- rejects empty allow paths.
- rejects empty disallow paths.
- accepts path strings without enforcing RFC path grammar.
- does not explicitly reject control characters / line breaks.

### RFC 9309 requirements

RFC 9309 defines:

- `product-token = identifier / "*"`
- identifier grammar follows RFC 9309.
- Empty Allow/Disallow pattern is valid.
- A non-empty path pattern begins with `/`.
- Raw `#` starts comment semantics.
- Raw `#` is not an ordinary literal path-pattern character.
- If a literal `#` is intended inside the path, use its percent-encoded representation such as `%23` according to protocol encoding rules.
- CR/LF and forbidden control characters must not be allowed to alter rendered robots.txt structure.

The CR/LF protection must explicitly cover:

- Allow/Disallow values,
- rule comments,
- top-level document comments.

### Confirmed mismatch

The current DTO simultaneously:

- **rejects valid RFC input**: empty Allow/Disallow pattern.
- **accepts invalid RFC input**: arbitrary user-agent token.
- can accept strings containing line-breaking control characters that should not be part of the rule value.

### Why the newline issue matters

Because `robots.txt` is a line-oriented protocol, uncontrolled newline content can alter rendered directive structure.

This is not only cosmetic validation.

### Safe target

Introduce RFC-aware validation with explicit rules for:

- valid product-token grammar.
- valid empty Allow/Disallow pattern.
- slash-prefixed path pattern.
- raw `#` comment semantics.
- percent-encoded literal values where applicable.
- CR/LF and other forbidden control characters in rule values.
- CR/LF safety for rule comments.
- CR/LF safety for top-level comments.

The current renderer is line-oriented, so injection prevention must be an explicit contract rather than an implied generic string check.

### What must not be done

Do not "tighten all strings to non-empty" as a generic safety rule. RFC 9309 explicitly permits an empty rule pattern.

---

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

## F-13 — Open Graph validation currently requires the wrong basic property set

**Decision:** `FIX`, preferably through an explicit protocol profile  
**Risk:** High  
**Area:** Open Graph

### Repository evidence

When Open Graph data is detected, `SeoMetaValidator` currently expects:

- `og:title`
- `og:description`
- `og:image`

It does not require:

- `og:type`
- `og:url`

The current Phase 11A tests lock this behavior.

### Open Graph Protocol

The Open Graph Protocol defines four required basic properties:

- `og:title`
- `og:type`
- `og:image`
- `og:url`

`og:description` is optional metadata.

### Confirmed mismatch

The current validator:

- treats an optional OGP property as required/recommended in its basic set.
- omits two protocol-required properties from that set.

### Additional architecture complication

The repository has two social metadata paths:

1. `MetaTagsDTO` / `MetaGeneratorService` / HTML renderers.
2. `OpenGraphBuilder` / `SocialMetaCollection`.

`MetaGeneratorService` populates title, description, and URL but does not automatically provide Open Graph type or image.

`OpenGraphBuilder` is a richer dedicated model.

### Safe remediation

Do not immediately change the legacy validator and thereby alter all existing scores and reports.

First establish an explicit OGP-conformance validator/profile.

Then decide how the legacy `SeoMetaValidator` should migrate to that profile.

---

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

- Schema.org is the vocabulary source.
- Google Search Central documentation is authoritative for Google Search behavior.
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

1. RFC 9309 rule validation.
2. Preserve non-standard extensions separately.
3. Fix `-1` meta robots behavior.
4. Add `indexifembedded`.
5. Correct `noarchive` documentation.
6. Add provider-aware `unavailable_after` validation.
7. Update examples/tests/docs.

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

### Order

1. Base sitemap entry/document policy, including `<loc>` length, URL count, byte size, and hosting context.
2. Image provider status / deprecated-tag handling plus 1,000-image and cross-domain context rules.
3. Video limits.
4. News language/date rules plus 1,000-entry and two-day metadata-window rules.
5. Explicit provider-status classification for legacy News optional fields.
6. Correct examples.
7. Clarify README claims.

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
5. Decide how heuristic warnings participate in scores.
6. Align dedicated social builders and legacy `MetaTagsDTO` path.

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

### Robots

- valid `*`.
- valid identifier.
- invalid product-token case.
- valid empty Allow/Disallow pattern.
- valid slash path.
- raw `#` comment behavior.
- encoded `%23` literal-path case.
- CR/LF in Allow/Disallow.
- CR/LF in rule comments.
- CR/LF in top-level document comments.

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
- external-evidence-only cases.

### Google News

- 1,000 entries valid
- 1,001 invalid/issue
- all four documented publication-date forms
- invalid arbitrary date
- valid/invalid language cases
- exact two-day-window boundary using explicit caller-supplied reference time

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

No production behavior should be changed merely because it "looks more strict."

A behavior change is justified only when the audit classifies it, its authoritative source is recorded, its compatibility impact is understood, and its non-target behavior is protected by tests.

---

# 13. Authoritative External References

All provider facts should be rechecked again when the corresponding remediation stack starts.

## Standards / protocols

- RFC 9309 — Robots Exclusion Protocol  
  https://www.rfc-editor.org/rfc/rfc9309.html

- RFC 5646 / BCP 47 — Tags for Identifying Languages  
  https://www.rfc-editor.org/rfc/rfc5646.html

- Sitemaps.org Protocol  
  https://www.sitemaps.org/protocol.html

- Open Graph Protocol  
  https://ogp.me/

- Schema.org  
  https://schema.org/

## Google Search

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
