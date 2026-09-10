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

It is also the **execution authority for the remediation scope defined in this document**. Its job is not merely to identify findings: every material architecture, compatibility, standards, provider, evidence-boundary, and current-vs-future scope decision needed by Stacks 0 through 8 must be fixed here before production remediation proceeds. An implementation stack may choose ordinary internal coding details that do not alter these contracts, but it must not invent policy, reinterpret an unresolved standard, widen scope, or decide a public/observable behavior that this audit leaves materially open.

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
- **RECLASSIFY** — a capability remains, but its meaning or layer is currently wrong and must be corrected.
- **DEPRECATE** — preserve compatibility but clearly move away from the capability over time.
- **DOC-FIX** — documentation, example, or changelog claim is incorrect or stale.
- **CORRECTION** — a recorded fact, example, or earlier classification (in the repository, prior review, or this audit's drafting) is demonstrably wrong and must be corrected with the authoritative evidence recorded here.
- **EVIDENCE BOUNDARY** — the authoritative source is intentionally open-ended or context-dependent, so the library must **not** convert the gap into a fabricated local pass/fail. Behavior is limited to deterministic local rules plus explicit caller/provider-supplied evidence states, and an `unknown`/evidence-gap state must never be promoted into a pass or failure.
- **DEFER** — a capability or contract is explicitly moved to a **separately approved future contract**. A `DEFER` decision grants an implementation stack **no authority** to implement, approximate, or expand the deferred capability during this remediation. The table uses the scoped forms **DEFER MEMBERSHIP**, **DEFER PROVIDER PROFILE**, and **DEFER EXPANSION** to name the exact deferred surface.

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

Existing public classes must initially remain façades over that path.

Before refactoring:

1. Capture current outputs with characterization tests.
2. Capture DTO-only service behavior.
3. Capture raw-array renderer behavior.
4. Capture namespace behavior and exception types.
5. Make the internal engine produce equivalent output for all behavior not intentionally changed.

### Canonical extended-DTO serialization decision — fixed by this audit

The extended-DTO divergence is a **known behavior** with a fixed remediation outcome. It is **not** `unknown / needs decision` and must not be left to Stack 3 or to characterization:

- The canonical serialization path emits the **full sitemap element set** for a URL entry: `loc`, `lastmod`, `changefreq`, `priority`, and all extended children carried by `SitemapUrlDTO` — `alternates` (hreflang), `images`, `videos`, and `news`.
- The canonical path conditionally declares the `xhtml`, `image`, `video`, and `news` namespaces on the `<urlset>` root/entry **exactly when** the corresponding extended data is present, mirroring `SitemapXmlStringRenderer`'s current behavior. The `SitemapGeneratorService` must obtain the same behavior after delegation.
- Both public entry points (`SitemapGeneratorService` and `SitemapXmlStringRenderer`) delegate to this one canonical engine, so **both produce identical extended-DTO output** for equivalent input.
- `SitemapGeneratorService` dropping `images`, `videos`, and `news` is the **intentional correction**. It is the observable defect this finding fixes; it is not a documented limitation to preserve.
- **Compatibility is preserved** for every input whose meaning is not intentionally corrected:
  - DTO-only service input that contains no extended data continues to produce equivalent core-only XML.
  - Raw-array input normalization and exception types of `SitemapXmlStringRenderer` remain characterized and stable, except for bugs the audit explicitly corrects (for example namespace/extension handling inconsistencies).
  - `SitemapGeneratorService` continues to accept typed `SitemapUrlDTO` input only; accepting raw arrays there is **not** introduced by this remediation.
- `SitemapGenerationResultDTO`'s serialized shape (`xml`, `entry_count`, `type`) is unchanged. `entry_count` refers to the number of URL entries, not to a count of extended children.
- After the canonical path exists, one XML-writing implementation must render every sitemap structure, and no duplicated validation rule may remain between the two public façades.

### Acceptance criteria for later remediation

- One XML-writing implementation for equivalent sitemap structures.
- No duplicated validation rules between public façades.
- Existing public APIs remain callable.
- Intentional differences in accepted input shapes are documented, not accidental.
- **Extended DTO data (`images`, `videos`, `news`, `alternates`) never silently disappears through any entry point after the canonical path is in place.** The Stack 3 stop condition in this audit forbids shipping an entry point whose extended data is dropped.
- `SitemapGeneratorService` and `SitemapXmlStringRenderer` produce identical extended-child XML for equivalent `SitemapUrlDTO` input.

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
- In a URL sitemap, `<url><lastmod>` identifies the time the page content was last modified.
- In a Sitemap Index, `<sitemap><lastmod>` identifies the time the linked sitemap file itself was last modified.

### Exact Sitemap `lastmod` lexical contract

For this remediation, the accepted lexical contract is fixed here and must **not** be re-derived by the implementer.

Sitemaps.org requires W3C Datetime and publishes Sitemap/Sitemap-Index XML schemas. The implementation contract is the intersection of that W3C profile with the schema date/dateTime forms:

- `YYYY-MM-DD`
- `YYYY-MM-DDThh:mm:ssTZD`
- `YYYY-MM-DDThh:mm:ss.sTZD`, where the fractional-second part contains one or more digits

For the dateTime forms, `TZD` is required and is one of:

- `Z`
- `+hh:mm`
- `-hh:mm`

Therefore the Sitemap protocol profile used by this library does **not** accept year-only, year-month, hour/minute-only dateTime, or zone-less dateTime forms. The same lexical policy applies to URL-Sitemap and Sitemap-Index `lastmod`; their semantic meaning differs by context as stated above.

**Current implementation limitation/mismatch:**
- `SitemapUrlDTO::isValidLastmod()`, `Shared\DTO\Sitemap\SitemapIndexEntryDTO`, and `Web\Sitemap\DTO\SitemapIndexEntryDTO` explicitly enforce regex `Y-m-d` or PHP `DateTimeInterface::ATOM` parsing.
- `DateTimeInterface::ATOM` does not include fractional seconds, so the current implementation rejects the valid fractional-second dateTime form above. This is a **current implementation limitation**, not evidence of W3C/Sitemap conformance.
- Remediation must implement exactly the three accepted forms defined above and reject malformed or out-of-contract lexical forms; this decision is part of the audit authority and is not left to implementation-time standards interpretation.

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
- Google uses `lastmod` only when it is consistently and verifiably accurate and represents a significant page update.

### Safe target

The architecture needs separate levels:

1. **Entry-level**
   - URL shape
   - page URL `<loc>` length below 2,048 characters
   - URL values must follow the applicable URI/IRI escaping requirements documented by the protocol.
   - `lastmod` lexical validation uses exactly the three forms fixed by this audit.
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

### Exact measurement contract for numeric textual boundaries

The two authoritative sources state "less than 2,048 characters" (Sitemaps.org, for the page `<loc>`) and "a maximum of 2,048 characters" (Google Video, for `video:description`). **Neither source defines the unit** as UTF-8 bytes, Unicode code points, or grapheme clusters, and neither defines preprocessing/normalization for the length check. This unit is therefore an **EVIDENCE BOUNDARY**: the library must not silently pick a unit as if the source backed it. To keep the boundary deterministic and authorization-complete (no implementation-time choice), the audit fixes a library measurement policy:

- **Unit: UTF-8 bytes**, measured with the library-native `strlen()` on the value **as supplied** to the validating entry point.
  - Rationale: this matches the library's existing fixed byte-measurement compatibility contract for text-length boundaries (F-12 preserves `strlen()` byte measurement for title/description heuristics), keeps the measurement idiom uniform across the library, and requires no `mbstring`/PCRE-unicode dependency. It is also the conservative deterministic upper-bound check: any value accepted on bytes is necessarily accepted on code points or graphemes for the same threshold, so the byte check never under-enforces the source's "characters" reading.
  - The byte policy applies until an authoritative source defines a different unit or a separate contract amendment replaces it. It must not be re-derived during Stack 4.

- **Measurement point — `<loc>`:** the length is measured **before** URI/IRI normalization and before percent-encoding. The authored `loc` value as supplied is the input; percent-encoding is a serialization/output operation applied later and is not expanded for length counting.
  - Rationale: a URL length limit that Sitemaps.org ties to the `loc` value the publisher authors is most deterministically verified at input time, whereas percent-encoding expansion depends on output-time mechanics. URI/IRI escaping correctness is still validated separately as its own serialization guarantee; it does not re-trigger the length boundary.
  - Boundary: `strlen(loc) < 2048` — 2,047 bytes valid, 2,048 bytes invalid (protocol requires *less than* 2,048).

- **Measurement point — `video:description`:** the length is measured on the description value **as supplied** to the video validator/entry point, before any XML escaping/CDATA wrapping.
  - Boundary: `strlen(description) <= 2048` — 2,048 bytes valid, 2,049 bytes invalid.

- These policies are part of the audit authority. Stack 4 must implement exactly these boundaries with the corresponding tests and must not substitute `mb_strlen`, grapheme counting, post-encoding measurement, or a different unit. (The title/description heuristic measurement is covered separately by F-12.)

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
- They remain for backward compatibility in this remediation.
- They must not be documented as current Google indexing enhancements.
- New examples must not encourage them as recommended Google output.
- The Google Image profile must model the 1,000-images-per-URL limit and cross-domain verification context.
- Any future deprecation is **explicitly deferred** out of this remediation: it is not a Stack 4 implementer decision and requires its own approved compatibility/migration contract before the public fields are removed, renamed, or re-stricted.

### What must not be done

Do not remove constructor parameters or output support in a compatibility-breaking cleanup without a deliberate migration plan.

---

## F-04 — Google Video sitemap validation is materially incomplete

**Decision:** `ADD` (Provider Layer)  
**Risk:** High  
**Area:** Google Video sitemap

### Repository evidence

At the reviewed integration snapshot:

- `src/Shared/DTO/Sitemap/SitemapVideoDTO.php` validates:
  - `thumbnailLoc` as a non-empty `FILTER_VALIDATE_URL` URL;
  - non-empty title and description;
  - `contentLoc` / `playerLoc` URL shape when supplied;
  - presence of at least one of `contentLoc` or `playerLoc`;
  - duration only as greater than zero;
  - publication date through the shared Sitemap `lastmod` helper.
- `src/Web/Sitemap/SitemapXmlStringRenderer.php` independently applies substantially the same subset when normalizing raw-array video input.

Neither path proves the complete Google Video provider contract.

### Current Google Video Sitemap contract

The current official Google Video Sitemap documentation establishes, for the fields represented by the library:

- `video:thumbnail_loc`, `video:title`, and `video:description` are required for each video entry, along with at least one of `video:content_loc` or `video:player_loc`.
- `video:title` is recommended to match the video title displayed on the host page.
- `video:description`:
  - has a maximum of 2,048 characters;
  - must match the description displayed on the host page, but does not have to be word-for-word identical.
- `video:duration`, when present, must be from 1 through 28,800 seconds.
- `video:publication_date`, when present, supports:
  - `YYYY-MM-DD`;
  - `YYYY-MM-DDThh:mm:ssTZD`.
- `video:content_loc`:
  - points to the actual video media file;
  - must refer to a supported video file type;
  - is recommended when available because it is the most effective way for Google to fetch the video;
  - must not equal the parent page `<loc>`.
- `video:player_loc` points to a player for the specific video and must not equal the parent page `<loc>`.
- Google currently documents these supported video file types: 3GP, 3G2, ASF, AVI, DivX, M2V, M3U, M3U8, M4V, MKV, MOV, MP4, MPEG, OGV, QVT, RAM, RM, VOB, WebM, WMV, XAP.
- Google Video best-practice documentation explicitly states that Data URLs are unsupported for video URLs.
- Do not list a video in a Video Sitemap when it is unrelated to the content of the host page.
- All files referenced by the Video Sitemap must be accessible to Googlebot: they must not be blocked by robots.txt, login/metafile requirements, firewalls, or similar barriers.

### Supported-protocol wording must not be overclaimed

The current Video Sitemap page literally says referenced files must be accessible on a supported protocol, naming **HTTP and FTP**, and says streaming protocols are unsupported. The same authoritative page also uses **HTTPS** URLs in its own Video Sitemap examples.

Therefore the audit records this source evidence without inventing a stricter statement than Google publishes:

- HTTP and FTP are explicitly named in the prose.
- HTTPS is demonstrably used by Google's own examples and must not be rejected.
- Streaming protocols are explicitly unsupported by the Video Sitemap page.
- A provider validator may safely support HTTP, HTTPS, and FTP based on the combined source evidence, but documentation must not falsely quote Google as literally enumerating `HTTP/HTTPS/FTP only`.
- Do not infer a comprehensive closed list of every possible streaming scheme from the phrase "streaming protocols" unless an authoritative source enumerates it.

### Thumbnail provider contract

Google's current Video SEO Best Practices apply the following thumbnail requirements to `video:thumbnail_loc`:

- supported formats: BMP, GIF, JPEG, PNG, WebP, SVG, AVIF;
- minimum dimensions: 60x30 pixels, with larger preferred;
- the thumbnail must be accessible to Googlebot and Googlebot Images;
- the file must remain consistently available at a stable URL;
- at least 80% of thumbnail pixels must have alpha (transparency) greater than 250.

### Rule classification

1. **Deterministic lexical/local validation**
   - non-empty required values;
   - description maximum length;
   - duration boundaries;
   - exact publication-date lexical forms;
   - presence of `content_loc` or `player_loc`;
   - URL shape;
   - parent-`<loc>` inequality when the parent URL is supplied to the validator;
   - explicit rejection of Data URLs;
   - protocol/scheme handling only to the extent established above, without inventing an unsupported closed scheme taxonomy.

2. **Document/content context**
   - video relevance to the host page;
   - title/description consistency with visible host-page content;
   - these require caller-supplied page/content context when validated offline.

3. **Serialization/output guarantees**
   - title and description must be XML entity-escaped or CDATA-wrapped correctly;
   - values must not be pre-escaped in a way that causes double escaping.

4. **External/provider evidence**
   - actual Googlebot accessibility of referenced resources;
   - actual remote video file type/format;
   - actual remote thumbnail format, dimensions, stability, accessibility, and transparency.

Do not infer an actual remote file type from a URL extension alone and do not convert remote accessibility into fake offline validation.

Watch-page/video indexing eligibility is a separate provider outcome and must not be treated as the validation state of `content_loc` or `player_loc`.

### Safe target architecture

Implement Google Video as an explicit provider profile/decorator over the base Sitemap contract. Preserve existing public DTOs while introducing context-aware diagnostics for rules that cannot live honestly inside a single-entry constructor.

---

## F-05 — Google News sitemap provider contract is incomplete and current cardinality can emit invalid provider output

**Decision:** `ADD` (Provider Layer) + `DOC-FIX`  
**Risk:** High  
**Area:** Google News sitemap

### Repository evidence

At the reviewed integration snapshot:

- `src/Shared/DTO/Sitemap/SitemapNewsDTO.php` only requires non-empty `publicationName`, `publicationLanguage`, `publicationDate`, and `title`; it does not enforce the current Google News lexical/content rules below.
- `src/Shared/DTO/Sitemap/SitemapUrlDTO.php` exposes `news` as `list<SitemapNewsDTO>` and accepts multiple News entries for one URL.
- `src/Web/Sitemap/SitemapXmlStringRenderer.php` iterates that list and can serialize multiple `<news:news>` blocks inside a single `<url>`.
- `examples/sitemap-output.php` uses the actually invalid example `publicationDate: 'as-provided'`.

Do not replace these repository facts with invented examples. The reviewed example's `publicationName` is `Example Daily`; the proven example defect is its publication date.

### Current Google News contract

Current Google News Sitemap documentation states:

- each `<url>` may contain **only one** `<news:news>` tag;
- one News Sitemap may contain up to **1,000** `<news:news>` tags total;
- only include recent article URLs created in the **last two days**; after they are older than two days, either remove the URL from the News Sitemap or remove its `<news:news>` metadata;
- `news:language` uses an ISO 639 language code of two or three letters, with Google's documented exceptions:
  - Simplified Chinese: `zh-cn`;
  - Traditional Chinese: `zh-tw`;
- `news:publication_date` must represent the **original date and time when the article was first published on the site**, not the time it was added to the Sitemap;
- Google accepts these four publication-date forms:
  - `YYYY-MM-DD`;
  - `YYYY-MM-DDThh:mmTZD`;
  - `YYYY-MM-DDThh:mm:ssTZD`;
  - `YYYY-MM-DDThh:mm:ss.sTZD`;
- `news:name` must exactly match the publication name as it appears on articles in Google News, **omitting anything in parentheses**;
- `news:title` is the title as it appears on the site and must not include the author name, publication name, or publication date.

The source says **"last two days"** and does not define an exact `48 hours`, calendar-day, timezone, or date-only boundary algorithm. The library policy for this remediation is therefore fixed as follows:

- **Do not implement hard local freshness arithmetic from `publicationDate` plus a clock/reference time.**
- Treat the provider freshness rule as a **context/evidence diagnostic** with three semantic states: `within_window`, `outside_window`, or `unknown`.
- `publicationDate` lexical validity alone must never be treated as proof that an article is within or outside Google's freshness window.
- A host/caller that possesses authoritative freshness evidence may supply that state; the library must process it deterministically and must not call `now()`, read a global/system clock, or invent the provider boundary.
- `unknown` must remain an evidence gap/diagnostic state, not be converted into a fabricated provider pass or failure.
- The three freshness states are delivered through the GDC-01 companion surface. They are off-legacy-result and off-score exactly like every other new provider diagnostic; they never change `is_valid`, `has_warnings`, `errors`/`warnings`/`info`, or the calculated score.
- If Google later publishes exact boundary semantics, changing this policy requires an explicit contract amendment rather than an implementation-time interpretation.

### Legacy fields

The repository still exposes `news:access`, `news:genres`, `news:keywords`, and `news:stock_tickers`. They are absent from Google's current News Sitemap reference. Absence from the current reference is not sufficient evidence to delete public compatibility fields, but they must be classified as legacy/provider-status fields and must not be presented as current Google News requirements or recommendations without evidence.

### Rule classification

1. **Deterministic lexical / entry-level validation**
   - the four documented publication-date lexical forms;
   - the two/three-letter ISO 639 form plus `zh-cn` / `zh-tw` exceptions;
   - one News entry per URL as the Google provider cardinality contract;
   - parenthetical content in `news:name` can be detected locally, but exact publication-name equivalence cannot.

2. **Document/context validation**
   - maximum 1,000 News entries per Sitemap;
   - the "last two days" rule is represented only through the caller-supplied `within_window` / `outside_window` / `unknown` evidence state defined above;
   - no hard age calculation from the lexical publication date and no hidden `now()` or global/system time.

3. **Content/provider evidence**
   - publication date truly being the original first-publication time;
   - publication name exactly matching the Google News publication identity;
   - title matching the article title and excluding author/publication/date semantics where those facts require caller-supplied content context.

### Compatibility-safe target

The current `SitemapUrlDTO::$news` list is a public contract and must not be broken casually. Characterize it first. A Google News provider profile must classify more than one News entry under a URL as provider-invalid/diagnostic through the GDC-01 companion surface while preserving the existing list API until an explicit migration decision is made.

Correct the canonical example so it uses a valid documented publication date.

---

## F-06 — `robots.txt` DTOs do not conform cleanly to RFC 9309 and Google-specific contracts are not separated

**Decision:** `FIX` + `RECLASSIFY`  
**Risk:** High  
**Area:** Robots Exclusion Protocol / Google robots.txt

### Repository evidence

At the reviewed integration snapshot:

`src/Web/Robots/DTO/RobotsRuleDTO.php`:

- accepts any non-empty `userAgent`;
- rejects empty Allow paths;
- rejects empty Disallow paths;
- **does not** enforce that non-empty rule paths begin with `/`;
- does not implement the RFC product-token grammar;
- does not explicitly reject CR/LF or other line-breaking control content in rule values/comments;
- exposes `crawlDelay` as a normal field.

`src/Web/Robots/DTO/RobotsTxtDTO.php`:

- exposes `list<string> $sitemaps`;
- validates each Sitemap value with PHP `FILTER_VALIDATE_URL`.

PHP documents that `FILTER_VALIDATE_URL` works only on ASCII URLs; this is narrower than Google's published `Sitemap:` examples, which include a fully-qualified URL containing a raw Unicode path.

### RFC 9309 base contract

RFC 9309 defines:

- `product-token = identifier / "*"`;
- `identifier = 1*("-" / A-Z / "_" / a-z)`; digits are not part of this grammar;
- empty Allow/Disallow patterns are valid in the grammar;
- the published ABNF defines non-empty `path-pattern` as beginning with `/`;
- `#` starts comment syntax, so raw `#` is not an ordinary literal path character; literal special-character matching uses percent encoding as required by the RFC matching rules;
- control characters are excluded from `UTF8-char-noctl`, and the line-oriented format makes CR/LF injection a serialization/security concern.

### RFC leading-wildcard inconsistency

RFC 9309's published ABNF requires non-empty `path-pattern` to begin with `/`, while its own Simple Example uses `Disallow: *.gif$` and its wildcard discussion supports `*` matching.

RFC Editor Errata 7995 has status **Reported** and proposes changing the ABNF to:

`path-pattern = ("/" / "*") *UTF8-char-noctl`

The erratum is not an incorporated normative replacement for RFC 9309.

### Fixed library policy for leading `*`

The compatibility decision is fixed by this audit and must not be delegated to Stack 2:

- Existing public `RobotsRuleDTO` / rendering compatibility for a non-empty path beginning with `*` is preserved during this remediation; the constructor must not start throwing solely because the first character is `*`, and the renderer must not silently rewrite the value.
- The generic RFC 9309 conformance profile follows the **published ABNF** for normative conformance: an empty pattern is allowed and an ordinary non-empty conforming `path-pattern` begins with `/`.
- Because RFC 9309's own Simple Example conflicts with that ABNF and Errata 7995 remains only `Reported`, a leading-`*` value is classified as a **non-fatal protocol compatibility diagnostic**, not as proven normative conformance and not as a constructor-level hard failure.
- The diagnostic contract is fixed as origin `protocol`, profile `rfc9309`, warning severity, with stable code `robots_rfc9309_leading_wildcard_compatibility`. The diagnostic is delivered through the GDC-01 companion surface; it never enters the legacy result and never affects scoring.
- The Google robots.txt profile remains separate: when validating against Google's documented present-path rule, a non-empty path that does not begin with `/` receives the provider diagnostic `robots_google_present_path_leading_slash` (`warning`, origin `provider`, profile `google`) via the GDC-01 companion surface; it is still not rewritten by the generic builder/renderer and never enters the legacy result or score.
- A future change in the RFC/errata status may change this classification only through an explicit audit/contract amendment with tests; an implementer must not silently adopt the proposed erratum as normative text.

Therefore do **not**:

- claim that the published RFC normatively allows leading `*`;
- claim that Errata 7995 already changed the RFC;
- reject or rewrite the existing compatibility input merely to make the constructor mirror the published ABNF.

### Google robots.txt profile

Google's current robots.txt documentation states:

- the file is UTF-8 encoded plain text;
- Google parses up to 500 KiB and ignores content after that limit;
- field names are case-insensitive;
- Allow/Disallow values are case-sensitive;
- when a Google Allow/Disallow path is present, it starts with `/`; a missing path means the rule is ignored;
- `crawl-delay` is not supported by Google;
- `Sitemap:` is supported as a separate field:
  - field name is case-insensitive;
  - value is case-sensitive;
  - value is an absolute, fully-qualified URL including protocol and host;
  - it does not have to be URL-encoded;
  - it may point to another host;
  - multiple `Sitemap:` fields are allowed with no documented limit;
  - it is not tied to any specific user-agent group.

Google's own current example includes a Unicode Sitemap path. Therefore `FILTER_VALIDATE_URL` is not sufficient as the Google-profile validator for this field.

### Safe target architecture

1. **RFC 9309 generic layer**
   - product-token grammar;
   - empty Allow/Disallow patterns;
   - RFC path matching/encoding semantics;
   - the fixed leading-`*` compatibility policy above;
   - raw `#` / percent-encoded literal behavior;
   - CR/LF and forbidden-control protection across rule values, rule comments, and top-level comments.

2. **Google robots.txt profile**
   - Google path behavior, including leading `/` for a present path;
   - 500 KiB provider document limit;
   - UTF-8/plain-text output expectations;
   - Google `Sitemap:` absolute/Unicode/cross-host/multiplicity/group-independence semantics.

3. **Compatibility boundary**
   - preserve existing public APIs while characterizing current rejection/acceptance behavior before changing constructors;
   - preserve leading-`*` inputs as specified above while distinguishing compatibility from normative/provider conformance;
   - the Google `Sitemap:` field must accept fully-qualified URLs with raw Unicode/non-URL-encoded paths, cross-host URLs, and multiple fields with no documented count limit. The internal mechanism (preg-based parsing, `parse_url` decomposition, or equivalent) is an ordinary implementation detail; the acceptance/classification behavior above is fixed and is not an implementation-time strategy choice. The generic (non-Google) URL validation contract must not be weakened globally as a side effect.

### What must not be done

Do not "tighten all strings to non-empty" as a generic safety rule. RFC 9309 permits empty rule patterns.

Do not describe current `RobotsRuleDTO` as already enforcing a leading slash; it does not.

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

For this remediation:

- keep the existing public `crawlDelay` field and rendering behavior for compatibility;
- classify and document it as a non-standard crawler extension;
- do not describe it as RFC conformance;
- do not describe it as a Google-effective directive;
- do **not** introduce a new generic crawler-extension framework as part of Stack 2. Such a framework is a separate future contract change if later justified.

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

This remediation does **not** invent a constructor dependency between directives. The fixed provider diagnostic is: when `indexifembedded` is present **without** `noindex`, the Google-profile validator emits the stable-code diagnostic `robots_meta_indexifembedded_without_noindex` with `warning` severity, origin `provider`, profile `google`. The diagnostic goes through the GDC-01 companion surface; it never enters the legacy result or score. The builder itself remains capable of representing the caller's directive set as authored.

---

## F-10 — `unavailable_after` is accepted without date validation

**Decision:** `RECLASSIFY` + explicit evidence-boundary contract  
**Risk:** Medium  
**Area:** Google robots meta

### Repository evidence

`unavailableAfter(string $value)` accepts caller-provided text without validating it.

### Current Google position

Google requires a broadly recognized date/time format, including examples such as RFC 822, RFC 850, and ISO 8601. Invalid values are ignored.

The provider documentation is intentionally open-ended rather than an exhaustive lexical grammar. A local validator therefore cannot truthfully claim that one finite parser proves every Google-recognizable value.

### Fixed remediation contract

The decision is fixed as follows:

- `MetaRobotsBuilder::unavailableAfter(string $value)` remains a **raw compatibility builder** in this remediation. Its current ability to carry caller-provided text is preserved; Stack 2 must not narrow it to one locally invented date grammar.
- The Google provider-validation path **does** locally flag an empty/whitespace-only value as lacking the required date value (stable code `robots_meta_unavailable_after_missing`, `warning`, origin `provider`, profile `google`); it must not claim that a non-empty arbitrary string is recognized merely because it was supplied.
- Recognition of a non-empty value uses explicit caller/provider evidence with exactly three semantic states: `recognized`, `unrecognized`, or `unknown`.
- `recognized` means the caller supplies authoritative evidence that the value is accepted as a broadly recognized format in its context; `unrecognized` means the caller supplies evidence that it is not provider-recognizable; `unknown` means the library does not possess evidence either way.
- `unknown` is an evidence-gap diagnostic, not a fabricated pass or failure.
- All `unavailable_after` diagnostics above (missing value, and the recognized/unrecognized/unknown evidence states) are delivered through the GDC-01 companion surface; none of them enters the legacy result and none affects scoring.
- No hidden network request, global clock, locale-dependent parsing, or "try a few formats and call the remainder invalid" behavior may be used to turn Google's open-ended statement into a closed local grammar.
- If Google later publishes an exhaustive grammar, adopting it requires an explicit contract amendment; it is not an implementation-time choice.

This preserves the generic builder while giving the provider layer deterministic semantics without overclaiming what the library can prove.

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

Length violations produce warnings. `SeoValidationScoreCalculator` currently assigns warnings a default 5-point penalty, so these heuristic warnings participate in score deductions under the current contract.

The current public validation payload is also part of the compatibility surface: `SeoValidationIssueDTO` exposes `code`, `severity`, `message`, and `field`, while `SeoValidationResultDTO` serializes those issue objects into its current `is_valid`, `has_warnings`, `errors`, `warnings`, `info`, and `issues` structure.

### Unicode Measurement Heuristics — compatibility decision

The current validator uses `strlen()` to measure title and description length.

This audit fixes the remediation contract as follows:

- **Preserve `strlen()` byte-length measurement throughout this remediation.** Byte length is the current compatibility contract for title/description heuristic thresholds.
- Arabic and other non-ASCII content therefore continue to trigger length warnings according to UTF-8 byte length, not visible-character count, Unicode code points, or grapheme clusters.
- Reclassifying these issues as `heuristic` must not change their existing issue codes, warning severity, threshold inputs, or byte measurement.
- Do **not** replace `strlen()` with `mb_strlen()`, grapheme counting, or another measurement unit in Stack 5.
- Any future change of measurement unit is a separate public heuristic-contract change and must be designed, documented, and tested independently rather than entering as a remediation side effect.
- Characterization tests must cover ASCII and Unicode/Arabic inputs and lock the current byte-based boundaries before architecture refactoring.

### Scoring compatibility decision

The scoring decision is also fixed for this remediation:

- Existing title/description length warnings continue to participate in scoring exactly as current warning issues do.
- The existing default warning penalty remains 5 points per warning unless the caller supplies the already-supported scoring options.
- Reclassification adds origin/profile semantics; it does not silently remove these warnings from scoring, alter their severity, or introduce a new scoring weight.
- Any future decision to exclude or differently weight heuristic issues is a separate scoring-contract change outside this remediation.

### Issue origin/profile contract — fixed for remediation

The architecture of classification is also fixed; Stack 5 must not redesign the legacy validation payload while adding origin/profile semantics:

- Existing `SeoValidationIssueDTO` constructor/property semantics and its four-field `toArray()` / `jsonSerialize()` payload (`code`, `severity`, `message`, `field`) remain unchanged in this remediation.
- Existing `SeoValidationResultDTO` construction and serialized shape remain unchanged. `SeoMetaValidator::validate()` must continue to return the existing result contract.
- Origin/profile information is introduced **additively as companion classification metadata**, not by silently adding keys to the legacy serialized issue/result payloads.
- The initial origin vocabulary is closed for this remediation to: `protocol`, `provider`, `heuristic`, `content-quality`.
- `profile` is a separate nullable stable machine identifier. At minimum, the remediation uses `rfc9309`, `sitemaps`, `ogp`, `google`, and `seo-default` where those profiles apply. An issue's origin is not inferred from severity.
- Existing title/description length issues are classified as origin `heuristic`, profile `seo-default` while retaining their existing issue codes/severity/score behavior.
- OGP conformance issues use origin `protocol`, profile `ogp`; RFC 9309 conformance issues use origin `protocol`, profile `rfc9309`; Sitemap base-protocol issues use origin `protocol`, profile `sitemaps`; Google-specific diagnostics use origin `provider`, profile `google`.
- The additive classification surface must pair each classification with its underlying `SeoValidationIssueDTO` without requiring consumers of the legacy result to migrate. Internal class naming is not a contract decision, but the separation and compatibility behavior above are mandatory.
- The existing score calculator continues to consume the legacy issue severity contract; origin/profile metadata must not alter scoring in Stack 5.

### Current Google position

Google explicitly states:

- there is no fixed length limit for the HTML `<title>` element; display may be truncated.
- there is no fixed length limit for meta descriptions; snippets may be truncated as needed.

### Correct conclusion

Length heuristics are useful.

They are not invalid.

What is wrong is treating them as if they were part of a generic validity model without an explicit heuristic classification.

### Safe target architecture

The validation pipeline distinguishes issue origins exactly as fixed above while preserving the legacy issue/result/score contracts.

### What must not be done

Do not simply delete title and description recommendations.

Do not change their `strlen()` byte measurement, issue severity/codes, or current score participation as part of semantic reclassification.

Do not add origin/profile keys to legacy issue/result JSON as a side effect of Stack 5.

Do not call title/description heuristics Google limits.

---

## GDC-01 — Global diagnostics contract for all new protocol/provider/context diagnostics (Stacks 2/4/5/6)

**Decision:** fixed contract  
**Risk:** High if violated  
**Area:** Validation / diagnostics architecture

This is the single contract that decides where every **new** diagnostic introduced by Stacks 2, 4, 5, and 6 lands and what it may affect. It applies to provider diagnostics, protocol-conformance diagnostics, and context/evidence diagnostics that did not exist as a `SeoValidationIssueDTO` before this remediation.

### Decision

For each new diagnostic produced by the remediation:

1. **Container.** New diagnostics do **not** enter the legacy `SeoValidationResultDTO` as part of its `errors`, `warnings`, `info`, or `issues` collections. They are emitted through the additive **companion classification surface** introduced by F-12: a companion/profile diagnostics collection distinct from the legacy result, each entry pairing a stable machine identifier (`code`), `severity`, `message`, optional `field`, `origin`, `profile`, and — where applicable — an explicit evidence state (`recognized` / `unrecognized` / `within_window` / `outside_window` / `unknown`, per the contract that defines it).

2. **`is_valid` is never changed by a new diagnostic.** `SeoValidationResultDTO::is_valid` continues to mean "no legacy error-severity issues". New provider/protocol/context diagnostics cannot turn a legacy-valid result into an invalid one.

3. **Legacy collections are never mutated by a new diagnostic.** A new diagnostic must not be appended to the legacy `errors`, `warnings`, `info`, or `issues` series, and must not reuse or shadow an existing legacy issue code.

4. **Scoring is never changed by a new diagnostic.** `SeoValidationScoreCalculator` continues to consume only the legacy issue severity contract exactly as it does today. New companion diagnostics contribute **no** deduction, not even a zero-point placeholder, and must not alter existing `error_count`, `warning_count`, `info_count`, `is_healthy`, grade, or score.

5. **Relationship to legacy issues.** Where a new diagnostic concerns the same subject as an existing legacy issue, the companion record references the legacy issue via its stable code; the legacy issue itself remains in the legacy result unchanged. The two surfaces are correlated, never merged.

6. **Single exception — pre-existing legacy issues.** Diagnostics that already existed before this remediation as `SeoValidationIssueDTO` objects (for example `missing_title`, `missing_og_title`, `missing_og_description`, `missing_og_image`, title/description length warnings) keep their current legacy placement, severity, code, and score participation under F-12. Reclassification adds companion origin/profile metadata only; it does not move them to the companion surface.

### What legally belongs only in the companion surface

Examples fixed by this audit:

- F-06 leading-wildcard `robots_rfc9309_leading_wildcard_compatibility` (protocol/rfc9309 warning).
- F-05 Google News freshness states `within_window`, `outside_window`, `unknown`.
- F-10 `unavailable_after` recognizability states `recognized`, `unrecognized`, `unknown`.
- F-09 `indexifembedded` without `noindex` provider diagnostic.
- F-04 / F-05 / F-02 provider-content diagnostics that require caller-supplied evidence.
- F-13 new `missing_og_type` and `missing_og_url` protocol diagnostics.
- F-14 relative-canonical provider best-practice diagnostic.

None of these may be plumbed into the legacy result or the score.

### Consequence for implementers

An implementation stack that produces, serializes, or scores a new diagnostic through the legacy `SeoValidationResultDTO` or `SeoValidationScoreCalculator` violates this contract, regardless of internal naming. Stack 5 must expose the companion surface additively without altering legacy serialized shapes, F-12 scoring, or `SeoMetaValidator::validate()`.

---

## F-13 — Open Graph required-field model mismatches OGP and the exposed surface needs one explicit protocol contract

**Decision:** `FIX` (Protocol Profiling)  
**Risk:** High  
**Area:** Social Metadata

### Repository evidence

- `src/Web/Social/OpenGraphBuilder.php`
- `src/Web/Social/SocialImage.php`
- related rendering and validation tests

`OpenGraphBuilder` exposes title, description, type, URL, site name, locale, determiner, audio, video, and multiple images. `SocialImage` exposes URL, secure URL, type, width, height, and alt.

### Open Graph Protocol contract

`SeoMetaValidator` currently treats `og:description` as part of its required/basic validity model while omitting `og:type` and `og:url`.

OGP defines four required basic properties:

- `og:title`
- `og:type`
- `og:image`
- `og:url`

`og:description` and `og:site_name` are optional.

For the surface the library already exposes:

- `og:determiner`: enum `(a, an, the, "", auto)`.
- `og:locale`: `language_TERRITORY`.
- OGP URL datatype uses `http://` or `https://`.
- `og:image:secure_url`: alternate URL for use when the webpage requires HTTPS.
- `og:image:type`: image MIME type.
- `og:image:width` / `og:image:height`: pixel dimensions.
- `og:image:alt`: image description; OGP recommends specifying it whenever `og:image` is specified. This is a **protocol-level recommendation / optional structured property**, not a heuristic and not a required validity field.
- `og:video` has the same structured-property family as `og:image`; `og:audio` has the URL, secure URL, and type structured properties. The current builder exposes only scalar `og:video` / `og:audio`, so missing structured video/audio helpers are not automatically a defect in the current public contract.

### Array and structured-property ordering

OGP treats repeated properties as arrays. The first tag from top to bottom has preference on conflicts.

Structured properties must follow their root property. When another root property is parsed, the preceding structured-property group is complete.

Because `OpenGraphBuilder` supports multiple images and currently emits each image root followed by its structured image properties, ordering is part of the serialization contract that must be characterized and preserved during refactoring.

### Fixed OGP migration contract

This subsection fixes the executable outcome, replacing "characterize then migrate" with closed contracts. The OGP protocol profile recognizes four required basics — `og:title`, `og:type`, `og:image`, `og:url` — and treats `og:description` and `og:site_name` as optional. The mapping to the legacy validator and the companion surface is fixed as follows.

**Fate of `missing_og_description`:**

- `og:description` is optional under OGP, so the OGP protocol profile emits **no** issue for a missing description.
- The legacy `SeoValidationIssueDTO` `missing_og_description` warning is a pre-existing legacy issue. Under F-12 and GDC-01 it **remains in the legacy result unchanged**: same code, `warning` severity, same message, and the same default 5-point warning score deduction — exactly as today.
- It is reclassified (companion metadata only) as origin `heuristic`, profile `seo-default`, because it is a publisher recommendation, not an OGP protocol requirement. Reclassification must not move it to the companion surface, change its severity/code, or alter its score participation.

**Issue contracts for missing OGP required basics:**

- `og:title` and `og:image` already produce legacy warnings (`missing_og_title`, `missing_og_image`). Those pre-existing legacy issues stay in the legacy result and score under F-12 and GDC-01. Their companion classification is origin `protocol`, profile `ogp` — the missing property is a genuine OGP required basic even though it was historically reported as a warning. This is documentation/classification only; no legacy behavior changes.
- Missing `og:type` and missing `og:url` are **new** diagnostics introduced by the OGP profile. Their contracts are fixed as:
  - codes: `missing_og_type`, `missing_og_url`;
  - severity: `warning` — matching the severity convention of the existing missing-OGP-basic warnings;
  - origin: `protocol`; profile: `ogp`;
  - container: **companion surface only**. Under GDC-01 they must **not** be appended to the legacy `errors`/`warnings`/`info`/`issues` collections, must **not** change `is_valid` or `has_warnings`, and must **not** produce any score deduction.
- These new codes must not collide with any legacy code and must not be emitted by `SeoMetaValidator::validate()` into the legacy result.

**Relationship to the legacy result and score:** unchanged for all pre-existing legacy issues; off-result and off-score for all new protocol diagnostics. `SeoMetaValidator::validate()` keeps returning the existing `SeoValidationResultDTO` contract; the OGP profile results are delivered through the companion surface (GDC-01) and the dedicated social builders.

**What Stack 5 may not decide:** it may not drop `missing_og_description` from the legacy result, may not promote missing `og:type`/`og:url` to `error`, may not add these to scoring, and may not invent additional required or optional OGP fields beyond the surface documented in this finding.

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

The decision is fixed as follows — no implementer choice between validation and diagnostics:

- keep generic builder compatibility. `CanonicalUrlBuilder::build()` behavior is unchanged.
- add a provider/best-practice **diagnostic** (not a constructor exception and not a new strict-validity failure) for a relative canonical URL: stable code `canonical_relative_provider_best_practice`, `warning` severity, origin `provider`, profile `google`, delivered through the GDC-01 companion surface — never into the legacy result or score.
- classify a relative canonical as provider/best-practice concern, not global protocol invalidity.

### What must not be done

Do not change `CanonicalUrlBuilder::build()` to throw on relative paths.

That would break an intentional, tested public behavior for a recommendation rather than a universal validity rule.

---

## F-15 — Hreflang has multiple normalization paths and lacks provider-aware cluster validation

**Decision:** `RECLASSIFY` + `ADD`, with code-set membership explicitly deferred  
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
- permissive syntax that is not equivalent to provider-supported code membership;
- absence of deterministic cluster-level checks for self-reference and reciprocal relationships.

A single-link DTO cannot validate cluster rules such as reciprocity.

### Fixed source-of-truth and scope decision

This remediation does **not** invent or silently embed an unversioned ISO language/region/script registry.

- Stack 6 must unify parsing and normalization, apply conventional BCP 47 casing when normalizing, preserve `x-default`, enforce fully-qualified URL behavior where the Google profile requires it, and add deterministic cluster checks for self-reference/reciprocity/consistent alternate groups.
- Syntax/casing alone must **not** be labeled proof that a language, region, or script code is an actually assigned/supported ISO member.
- Full membership validation against ISO 639-1, ISO 3166-1 Alpha-2, and ISO 15924 requires a separately audited, versioned standards-data source with provenance, update policy, and licensing/distribution review. That registry capability is explicitly **outside the current remediation contract**.
- Until that separate contract exists, the library must not ship a hand-maintained guessed list and must not classify a regex-shaped code as provider-valid or provider-invalid solely from an incomplete local registry.
- If caller-supplied authoritative membership evidence is added later, it belongs to that separate contract; Stack 6 does not invent such an evidence API.

### Safe target for this remediation

Separate and unify:

1. Shared hreflang parsing / normalization semantics used by Web and Sitemap entry points.
2. Generic language-tag syntax and conventional casing without pretending casing proves provider validity.
3. Google-visible structural rules that are deterministically known from supplied data, including fully-qualified alternate URLs and `x-default` handling.
4. Cluster integrity:
   - self-reference
   - reciprocal relationships
   - complete/consistent alternate groups
   - fully-qualified URLs

### What must not be done

Do not treat capitalization alone as a Google-invalidity error.

Do not claim complete provider-supported code membership validation in Stack 6.

Do not hide network crawling inside the DTO to prove reciprocity.

The host or caller should supply cluster data to a deterministic validator.

---

## F-16 — Structured Data architecture correctly separates Schema.org from Google eligibility, but provider eligibility profiles require a dedicated audited contract

**Decision:** `KEEP` current principle + `DEFER` Google eligibility profiles to a separate contract  
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

### Fixed remediation boundary

A complete Google structured-data eligibility matrix is not present in this audit. Therefore this audit must **not** delegate creation of that provider contract to an implementation stack and must **not** authorize partial Google-eligibility validation based on implementer research.

For the current remediation:

- Preserve generic Schema.org builders and the existing principle that generation does not guarantee Google eligibility.
- Preserve the existing scoped semantic validator behavior except for the documentation/classification corrections explicitly authorized by F-18.
- Do not add Google required/recommended-property eligibility rules, search-feature capability claims, or runtime Google structured-data profiles under this audit.
- Do not delete or deprecate a generic builder because a Google search appearance is absent, changed, conditional, or not yet audited.
- A future Google Structured Data Provider Profiles phase must first produce and approve a **date-stamped capability matrix** covering the library's relevant builders/features, required and recommended properties, composition rules, provider limitations, official sources, and verification dates. Only that approved matrix may become execution authority for runtime Google eligibility profiles.

This is an explicit separate-future-contract decision, not work left for the Stack 7 implementer to decide.

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

These observations protect generic compatibility but do not authorize Stack 7 to implement Course/Book Google eligibility rules; F-16 explicitly defers that provider-profile contract.

---

## F-18 — `JsonLdSemanticValidator` is scoped type/range validation, not complete semantic or lexical validation

**Decision:** `RECLASSIFY`; lexical/provider expansion is a separate future contract  
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

### Fixed remediation scope

For Stacks 0 through 8 under this audit:

- preserve the existing runtime property-range behavior and existing validation/score compatibility;
- correct documentation and classification so the validator is described as scoped structural/property-range validation rather than complete semantic/lexical proof;
- do **not** add new lexical URL validation, Date/DateTime grammars, enumeration membership validation, or Google required-property eligibility checks under F-18;
- each of those would create new validation errors and score/output changes and therefore requires a separately approved contract with exact vocabulary/provider rules and compatibility impact before implementation.

The implementer has no discretion to pick one of those expansions during Stack 7.

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

The current audit reviewed Open Graph Protocol behavior, but did not independently verify Twitter/X Card rules against a current, official Twitter/X documentation source.

### Safe Target

- Twitter/X provider conformance was **not source-verified by this audit**.
- Generic/current library compatibility for Twitter Cards remains preserved as-is.
- Twitter/X provider-rule remediation is out of scope until a dedicated official-source revalidation is completed.
- The documentation should reflect that Open Graph was audited, but Twitter/X Cards remain under historical implementation assumptions pending future review.

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

A provider matrix that does not yet exist is **not** delegated to an implementation stack by default. Provider-specific runtime rules require an approved evidence-backed contract first.

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

## AP-11 — Unknown material behavior is a stop condition, not implementer discretion

Characterization may legitimately discover repository behavior that this point-in-time audit could not observe in advance. That discovery does not transfer architecture authority to the implementer.

If Stack 0 or any later stack records `unknown / needs decision` for a behavior that affects a public API, serialized output, validation result, score, standards/provider classification, compatibility promise, or remediation scope, production remediation for that behavior must stop. The evidence and decision must be added to this audit (or an explicitly succeeding normative contract), reviewed, and accepted before implementation proceeds.

Ordinary internal details that cannot change an observable/contract outcome do not require an audit amendment.

## AP-12 — New diagnostics are additive and never mutate legacy results or scores

Every new protocol/provider/context diagnostic introduced by Stacks 2/4/5/6 is delivered through the GDC-01 companion surface. It must not change `SeoValidationResultDTO::is_valid`, must not be appended to the legacy `errors`/`warnings`/`info`/`issues` collections, and must not affect `SeoValidationScoreCalculator`. Pre-existing legacy issues keep their F-12 placement and score behavior already documented here.

## AP-13 — Numeric textual boundaries use the fixed measurement policy

Sitemap `<loc>` and Google Video `description` boundaries use the F-02 exact measurement contract: UTF-8 bytes via `strlen()` on the value as supplied, before URI/IRI normalization/percent-encoding or XML escaping. Stack 4 must not substitute `mb_strlen`, grapheme counting, or post-encoding measurement, and must not reinterpret the boundary as an implementer choice.

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
- `RobotsTxtDTO`
- `RobotsTxtRenderer`
- `SeoMetaValidator`
- `SeoValidationPreset`
- score/report builders
- `OpenGraphBuilder`
- `SocialImage`
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

### Mandatory decision gate

`unknown / needs decision` is an inventory state, **not permission for the implementer to choose a policy**.

If the unknown affects any material/observable contract listed in AP-11, that behavior is blocked from production remediation until the audit/contract is explicitly amended and accepted. Stack 0 may continue characterizing unrelated behavior, but a later stack must not cross that unresolved decision boundary.

No production refactor should start until the relevant behavior inventory exists and every material behavior needed by that refactor is either `preserve` or `intentionally change` under an approved contract.

---

## Stack 1 — Standards and Provider Taxonomy

### Goal

Create the architecture distinction between:

- protocol/vocabulary
- provider
- heuristic
- content-quality

### Fixed compatibility contract

Use the F-12 classification contract and the GDC-01 global diagnostics contract: keep `SeoValidationIssueDTO`, `SeoValidationResultDTO`, their legacy serialization, `SeoMetaValidator::validate()`, and current scoring behavior compatible. Classification is additive companion metadata with the fixed origin vocabulary and stable profile identifiers defined in F-12; every **new** diagnostic defined by this audit is delivered through the GDC-01 companion surface and never mutates the legacy result or score.

### Important constraint

This stack must avoid changing user-facing behavior.

It establishes the vocabulary that later code uses; it must not use taxonomy work as a reason to reweight scores or silently change legacy serialized payloads.

---

## Stack 2 — Robots

### Why first

Robots has:

- a formal RFC,
- clear current code,
- small surface area,
- several confirmed corrections.

### Order

1. Characterize current `RobotsRuleDTO`, `RobotsTxtDTO`, renderer, ordering, and exception behavior.
2. Implement the RFC 9309 product-token contract (`identifier` or `*`) and valid empty Allow/Disallow patterns.
3. Apply the F-06 leading-`*` policy exactly: preserve existing builder/rendering compatibility; do not call it normative published-ABNF conformance; emit the fixed non-fatal RFC compatibility diagnostic `robots_rfc9309_leading_wildcard_compatibility` (warning, origin `protocol`, profile `rfc9309`) through the GDC-01 companion surface; do not rewrite it; keep the Google-profile path diagnostic `robots_google_present_path_leading_slash` separate and also companion-only.
4. Implement RFC path/comment semantics, including raw `#`, percent-encoded literal special characters, and matching/encoding boundaries relevant to generated output.
5. Prevent CR/LF/control-character directive injection across rule values, rule comments, and top-level comments.
6. Preserve `crawl-delay` as the existing non-standard compatibility extension; do not represent it as RFC or Google behavior and do not invent a new extension framework in this stack.
7. Add an explicit Google robots.txt profile for:
   - UTF-8/plain-text output;
   - 500 KiB document boundary;
   - Google path behavior for present Allow/Disallow values;
   - Google `Sitemap:` fully-qualified URL semantics;
   - Unicode/non-URL-encoded Sitemap paths;
   - multiplicity without a documented limit;
   - cross-host Sitemap URLs;
   - independence from user-agent groups.
8. Replace the Google-profile reliance on ASCII-only `FILTER_VALIDATE_URL` with the F-06 fixed acceptance contract for the `Sitemap:` field; do not weaken unrelated generic URL contracts.
9. Fix `max-snippet:-1` and `max-video-preview:-1`.
10. Add typed `indexifembedded` support while preserving the raw escape hatch; emit `robots_meta_indexifembedded_without_noindex` (warning, origin `provider`, profile `google`, GDC-01 companion-only) when `noindex` is absent — never a builder construction barrier.
11. Correct `noarchive` documentation.
12. Implement F-10 exactly: keep `unavailableAfter()` raw-compatible; emit `robots_meta_unavailable_after_missing` for the provider-path missing/empty value; consume only explicit `recognized` / `unrecognized` / `unknown` evidence for non-empty provider recognizability; do not invent a closed date grammar; all `unavailable_after` diagnostics are GDC-01 companion-only.
13. Update examples/tests/docs.

### Stop condition

Do not move on while RFC 9309, Google robots.txt behavior, non-standard extensions, and Google robots-meta behavior remain conflated or while either the F-06 or F-10 fixed policy is replaced by an implementation-time interpretation. No Stack 2 diagnostic may appear in the legacy result or score; all must use the GDC-01 companion surface.

---

## Stack 3 — Sitemap Core Unification

### Goal

Remove duplicate serialization logic without removing public APIs.

### Order

1. Characterize XML outputs (Stack 0 inventory feeds this).
2. Introduce internal canonical serialization.
3. Delegate both public paths to it.
4. Keep both public `SitemapIndexEntryDTO` namespace contracts callable; adapt each to the canonical internal representation rather than deleting/renaming one as part of this remediation.
5. Implement the fixed F-01 decision for extended DTO data: the canonical path emits `alternates`, `images`, `videos`, and `news` exactly as `SitemapXmlStringRenderer` does today, conditionally declaring the corresponding namespaces, and **both** `SitemapGeneratorService` and `SitemapXmlStringRenderer` produce identical extended-child output for equivalent `SitemapUrlDTO` input. `SitemapGeneratorService` dropping extended children is the intentional correction, not a preserved limitation.
6. `SitemapGenerationResultDTO` (`xml`, `entry_count`, `type`) is unchanged; `entry_count` counts URL entries only.

### Stop condition

There must be one rule implementation for equivalent sitemap output, while both existing public index-entry DTO entry points remain available. No public entry point may silently drop extended DTO data; `SitemapGeneratorService` and `SitemapXmlStringRenderer` must produce identical extended-child XML for equivalent DTO input. If characterization exposes behavior whose preserve/change decision is not fixed by this audit, assert the AP-11 gate and stop that path.

---

## Stack 4 — Sitemap Standards / Google Extensions

### Goal

Add layered validation without collapsing protocol, provider, content-context, and remote-evidence rules into one constructor.

### Required coverage

#### Base sitemap
- URL sitemap count limit: 50,000 URLs.
- Sitemap Index count limit: 50,000 Sitemaps.
- uncompressed byte-size limits: 50 MB (52,428,800 bytes).
- page `<loc>` must be less than 2,048 characters; measured as UTF-8 bytes of the value as supplied (`strlen(loc) < 2048`), before URI/IRI normalization/percent-encoding, per the F-02 exact measurement contract.
- host/site/submission-context rules.
- cross-submission semantics.
- UTF-8 encoding requirements.
- XML entity escaping and URL URI/IRI escaping requirements.
- `lastmod` must implement exactly these library protocol-profile forms: `YYYY-MM-DD`, `YYYY-MM-DDThh:mm:ssTZD`, and `YYYY-MM-DDThh:mm:ss.sTZD` with one-or-more fractional digits; dateTime requires `Z` or `±hh:mm` timezone designator.
- year-only, year-month, hour/minute-only, zone-less dateTime, and malformed forms are out of contract.
- fractional-second characterization and intentional correction of the current helper mismatch.
- URL-sitemap vs Sitemap-Index `lastmod` semantics.
- verification of XMLWriter UTF-8 and serialization guarantees.

#### Google base Sitemap behavior
- `priority` is ignored by Google.
- `changefreq` is ignored by Google.
- `lastmod` is useful to Google only when consistently/verifiably accurate and tied to significant page modification.
- These are Google-provider semantics/diagnostics delivered through the GDC-01 companion surface; they must not redefine generic Sitemap protocol validity and must not change the legacy result or score.

#### Google Image
- current/deprecated field classification.
- 1,000-images-per-URL limit.
- cross-domain Search Console verification context.
- external crawlability context.
- provider diagnostics here are GDC-01 companion-only.

#### Google Video
- characterize current DTO and raw-array behavior.
- required title/description/thumbnail plus content/player presence.
- title host-page match as a provider recommendation.
- description max 2,048 characters, measured as UTF-8 bytes of the value as supplied (`strlen(description) <= 2048`) before XML escaping/CDATA wrapping, per the F-02 exact measurement contract; host-page consistency is a provider/context diagnostic.
- duration 1..28,800.
- exact two publication-date forms represented by the provider documentation.
- parent `<loc>` inequality.
- host-page relevance requirement.
- `content_loc` preference as a provider recommendation, not validity.
- supported file-type list exactly as documented, without invented extension aliases.
- Data URLs unsupported.
- protocol evidence handled exactly as documented: HTTP/FTP explicitly named, HTTPS demonstrated by Google's own examples, streaming protocols unsupported; no false claim that Google literally publishes `HTTP/HTTPS/FTP only`.
- thumbnail formats: BMP, GIF, JPEG, PNG, WebP, SVG, AVIF.
- thumbnail minimum 60x30, stable URL, Googlebot/Googlebot Images accessibility, and transparency requirement.
- deterministic/context rules separated from remote format/accessibility evidence.
- title/description XML escaping/CDATA output semantics.
- watch-page/video indexing eligibility kept outside Sitemap DTO validity.
- all Video provider/context diagnostics are GDC-01 companion-only.

#### Google News
- four exact publication-date forms.
- publication date means original first publication time, not Sitemap-addition time.
- language contract: two/three-letter ISO 639 plus `zh-cn` / `zh-tw` exceptions.
- publication-name exact-match semantics and parenthetical omission rule.
- title semantics.
- one News entry per URL provider cardinality, while preserving the public list contract until migration is deliberate; the multi-entry diagnostic is GDC-01 companion-only.
- 1,000 total News entries per Sitemap.
- "last two days" is a context/evidence diagnostic only: consume caller-supplied `within_window` / `outside_window` / `unknown` evidence and do not derive a hard boundary from `publicationDate` plus a clock/reference time.
- no hidden `now()`/global time and no invented `48 hours` or calendar-day arithmetic.
- legacy optional News-field provider-status classification.
- canonical example correction for `publicationDate: 'as-provided'`.
- all News provider/context diagnostics are GDC-01 companion-only.

### Critical constraint

Document/context and remote-evidence rules must not be forced into single-entry constructors when the required evidence is unavailable. Provider diagnostics must not become fake offline proof. All new Stack 4 diagnostics are delivered through the GDC-01 companion surface; none changes the legacy result or score.

---

## Stack 5 — Validation Architecture + Open Graph

### Goal

Stop mixing heuristic recommendations with protocol validity.

### Order

1. Introduce additive issue origin/profile classification exactly under the F-12 contract, with new diagnostics delivered through the GDC-01 companion surface; do not alter legacy `SeoValidationIssueDTO` / `SeoValidationResultDTO` serialized shapes or `SeoMetaValidator::validate()` return contract.
2. Establish OGP protocol validation under the fixed F-13 OGP migration contract.
3. Preserve legacy issue/score behavior: current title/description length issue codes and warning severity remain unchanged, and warning issues continue to flow through the existing score calculator.
4. Reclassify title/description length warnings as origin `heuristic`, profile `seo-default` without changing their observable compatibility behavior.
5. Preserve `strlen()` byte measurement as the title/description heuristic measurement unit for this remediation; do not substitute code-point or grapheme measurement.
6. Add characterization tests for ASCII and Arabic/Unicode title/description lengths that lock the current byte-based thresholds before refactoring validation architecture.
7. Declare Twitter/X provider conformance out of scope until an official-source revalidation is conducted.
8. Implement the OGP profile for the four required basics and the current exposed optional surface: determiner, locale, site_name, HTTP/HTTPS URL datatype, audio/video root URLs, image structured properties, multiple-image array preference/order, root/structured-property association, and `og:image:alt` as a protocol-level recommendation. Emit the new `missing_og_type` / `missing_og_url` protocol diagnostics (`warning`, origin `protocol`, profile `ogp`) as GDC-01 companion-only; keep the pre-existing `missing_og_title`, `missing_og_image`, and `missing_og_description` legacy issues in the legacy result and score unchanged, with `missing_og_description` reclassified as origin `heuristic`, profile `seo-default`.
9. Keep heuristic warnings participating in scores exactly as current warnings do, including the existing default 5-point warning penalty; any future scoring change requires a separate explicit contract change.
10. Align dedicated social builders and legacy `MetaTagsDTO` path without adding origin/profile keys to legacy JSON payloads.

### Critical constraint

Do not change existing score math, heuristic score participation, issue severity/codes, title/description byte measurement, or the existing validation result serialization while changing semantic categories. Do not route any new OGP diagnostic into the legacy result or score (GDC-01).

---

## Stack 6 — Canonical and Hreflang

### Canonical

- keep generic relative behavior; `CanonicalUrlBuilder::build()` is unchanged.
- add the provider best-practice diagnostic `canonical_relative_provider_best_practice` (`warning`, origin `provider`, profile `google`) for a relative canonical URL, delivered through the GDC-01 companion surface — never into the legacy result or score, and never as a builder exception.

### Hreflang

- unify Web and Sitemap hreflang parsing / normalization semantics.
- use conventional BCP 47 casing when normalization is performed, without treating casing alone as provider invalidity.
- preserve `x-default`.
- validate deterministically knowable Google structural rules from supplied data, including fully-qualified alternate URLs.
- add cluster-level self-reference, reciprocal-link, and alternate-set consistency validation; cluster diagnostics are GDC-01 companion-only.
- **do not implement ISO 639-1 / ISO 3166-1 / ISO 15924 membership tables in this remediation** and do not claim full provider code-membership validation; that requires the separate versioned standards-data contract defined by F-15.
- preserve deterministic behavior and host ownership; no crawling is introduced.

---

## Stack 7 — Structured Data Contract Boundary

### Goal

Preserve the valid Schema.org/provider separation and correct the library's claims without inventing a Google eligibility contract that this audit has not established.

### Required work in this remediation

- Preserve existing generic structured-data builders.
- Preserve current `JsonLdSemanticValidator` runtime/property-range behavior and legacy issue/score compatibility.
- Update normative documentation so the validator is described precisely as scoped structural/property-range semantic validation.
- Ensure documentation continues to state that Schema.org generation/conformance does not prove Google Search eligibility.
- Preserve the Course/Book nuance recorded by F-17 and avoid blanket provider-deprecation claims.
- Explicitly document that Google required/recommended-property eligibility profiles and a complete capability matrix are **not implemented by this remediation**.

### Explicit future-contract boundary

A Google structured-data capability matrix and runtime provider profiles are a separate future phase. Before that phase can implement production behavior, its normative contract must be approved and must contain, for each relevant library builder/feature:

- Schema.org type;
- library builder;
- Google search feature, if applicable;
- required properties;
- recommended properties;
- composition requirements;
- known provider limitations;
- official URL;
- verified date.

Likewise, new lexical URL/Date/DateTime/enumeration checks described in F-18 require their own exact vocabulary/compatibility contract before they can become validation errors.

### Stop condition

Stack 7 must not perform broad Google structured-data research, create an ad-hoc capability matrix, add partial eligibility rules, or tighten lexical validation. If such work is desired, stop and open the dedicated contract/audit first.

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
- document the explicit future-contract boundaries from F-15, F-16, and F-18 so current docs do not imply capabilities that this remediation intentionally does not add.

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

For Stack 5, the contract is already decided: existing heuristic warnings keep their current score participation and default warning penalty. Any future scoring change needs its own explicit contract change.

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

Provider facts must be checked against the recorded authoritative source when freshness matters.

## ADR-11

**Do not let characterization transfer decision authority to the implementer.**

A material `unknown / needs decision` blocks the affected production change until an approved contract amendment resolves it.

## ADR-12

**Do not implement an unaudited external vocabulary/provider data set as if it were a settled local contract.**

This applies in particular to hreflang ISO membership tables and Google structured-data eligibility matrices. Those capabilities require their explicit future contracts before runtime enforcement.

## ADR-13

**Do not let new diagnostics hit the legacy result or score.**

Every new protocol/provider/context diagnostic introduced by Stacks 2/4/5/6 is delivered through the GDC-01 companion surface. It must not change `is_valid`, must not be appended to legacy `errors`/`warnings`/`info`/`issues`, and must not alter `SeoValidationScoreCalculator` output. The single exception is pre-existing legacy issues, which keep their F-12 placement and score behavior.

## ADR-14

**Do not substitute a different unit for the fixed numeric boundaries.**

Sitemap `<loc>` (`< 2048`) and Google Video `description` (`<= 2048`) use UTF-8 byte measurement via `strlen()` on the value as supplied, per the F-02 exact measurement contract. `mb_strlen`, grapheme counting, and post-encoding/post-escaping measurement are prohibited as unit substitutes.

---

# 9. Test Strategy Required for the Remediation

The test strategy must cover the contracts introduced by the findings, not only representative examples.

## 9.1 Characterization tests

Add characterization tests before changing public behavior for:

- exact XML from base Sitemap DTO input.
- exact XML from extended Sitemap DTO input through **both** `SitemapGeneratorService` and `SitemapXmlStringRenderer`, capturing the current image/video/news-drop divergence and namespace behavior before unification.
- raw-array Sitemap normalization.
- both Sitemap Index DTO namespaces.
- current fractional-second `lastmod` rejection.
- current multiple-News-per-URL list/serialization behavior before Google-profile tightening.
- current RobotsRuleDTO acceptance/rejection behavior, including empty patterns and leading-wildcard paths.
- current `RobotsTxtDTO` ASCII-only `FILTER_VALIDATE_URL` Sitemap behavior.
- robots directive order and replacement semantics.
- Open Graph multiple-image root/structured-property order and first-image preference.
- current missing-OGP-basic warning codes and severity (`missing_og_title`, `missing_og_description`, `missing_og_image`) and their legacy score participation, before the OGP migration contract is applied.
- canonical relative and absolute output.
- current validation issue codes and score propagation.
- current `SeoValidationIssueDTO` and `SeoValidationResultDTO` serialized shapes before additive classification work.
- ASCII and Arabic/Unicode title/description strings that lock `strlen()` byte behavior at heuristic boundaries.
- current title/description length warnings retaining warning severity and the existing default 5-point-per-warning score deduction.
- current structured-data scoped property-range issue output before documentation/classification changes.

If characterization discovers a material behavior not already classified `preserve` or `intentionally change`, assert the AP-11 decision gate rather than inventing the expected production behavior inside the test PR.

## 9.2 Formal protocol / vocabulary tests

Purpose: prove locally deterministic protocol behavior without silently importing provider rules.

### Sitemap core

- 50,000 URL entries boundary and 50,001 over-boundary issue.
- 50,000 Sitemap Index entries boundary and 50,001 over-boundary issue.
- uncompressed-size boundary at 52,428,800 bytes and over-boundary case.
- page URL `<loc>` length: measured as UTF-8 bytes of the value as supplied (`strlen`), before URI/IRI normalization/percent-encoding; 2,047 bytes valid, 2,048 bytes invalid because protocol requires less than 2,048.
- a percent-encoded or normalized variant of a `<loc>` is **not** re-measured after escaping; escaping correctness is asserted separately from the length boundary.
- host/submission checks use explicit document context rather than unconditional same-host constructor rejection.
- UTF-8 serialization.
- XML entity escaping and URI/IRI escaping behavior.
- extended DTO parity: equivalent `SitemapUrlDTO` input produces identical `image:*`/`video:*`/`news:*`/`xhtml:link` output through `SitemapGeneratorService` and `SitemapXmlStringRenderer` after Stack 3, with namespaces declared conditionally.
- valid `lastmod`: `YYYY-MM-DD`.
- valid `lastmod`: `YYYY-MM-DDThh:mm:ssZ` and `YYYY-MM-DDThh:mm:ss±hh:mm`.
- valid `lastmod`: fractional-second dateTime with one or more fractional digits and required `Z`/offset.
- invalid `lastmod`: year-only, year-month, hour/minute-only dateTime, zone-less dateTime, and malformed/out-of-range calendar/time input.
- URL-sitemap vs Sitemap-Index `lastmod` semantic context where representable.

### RFC 9309 robots

- valid `*` product-token.
- valid identifier with only RFC identifier characters.
- invalid identifier containing digits.
- empty Allow/Disallow pattern.
- ordinary `/` path cases.
- existing leading-`*` path remains constructible/renderable for compatibility.
- leading-`*` receives `robots_rfc9309_leading_wildcard_compatibility` as a warning with origin `protocol` / profile `rfc9309`, delivered via the GDC-01 companion surface (not the legacy result, not scoring), rather than being represented as published-ABNF conformance or converted into a constructor exception.
- non-empty leading-`*` additionally surfaces `robots_google_present_path_leading_slash` only under the Google profile (origin `provider`, profile `google`, GDC-01 companion-only); no silent rewrite is performed.
- raw `#` comment behavior.
- percent-encoded literal special-character cases such as `%23` where applicable.
- CR/LF/control-character injection cases across values and comments.

### Open Graph Protocol

- four required basics.
- `og:description` remains optional at protocol-validity level; the OGP profile emits **no** missing-description conformance issue.
- legacy `missing_og_description` warning remains a legacy `SeoValidationIssueDTO` (warning, heuristic origin, `seo-default` profile) that keeps its legacy result placement and default 5-point score deduction unchanged.
- pre-existing `missing_og_title` / `missing_og_image` legacy warnings stay in the legacy result and score, with companion origin `protocol` / profile `ogp`.
- new `missing_og_type` and `missing_og_url` are `warning`-severity protocol diagnostics (origin `protocol`, profile `ogp`) delivered only through the GDC-01 companion surface: they must not appear in legacy `errors`/`warnings`/`info`/`issues`, must not change `is_valid`/`has_warnings`, and must not affect the calculated score.
- determiner enum.
- locale shape.
- HTTP/HTTPS URL datatype.
- image width/height integer semantics.
- `og:site_name` optional behavior.
- multiple-image ordering and first-tag preference.
- structured image properties remain attached to the correct root image by output order.
- `og:image:alt` is surfaced as a protocol-level recommendation, not a required validity error.
- OGP issues are classifiable as origin `protocol`, profile `ogp` without changing legacy issue/result JSON shapes.

## 9.3 Provider profile tests

### Google base Sitemap

- `priority` and `changefreq` remain protocol-valid fields but are classified as ignored by Google, not as Google validity failures.
- Google `lastmod` accuracy is represented as a provider/content diagnostic requiring appropriate evidence rather than inferred from lexical validity.

### Google Image

- 1,000 images valid.
- 1,001 images provider-invalid/issue.
- cross-domain verification remains an evidence/context condition rather than an offline network assertion.

### Google Video

Deterministic/provider-input cases:

- description: measured as UTF-8 bytes via `strlen()` on the value as supplied, before XML escaping; 2,048 bytes accepted; 2,049 bytes provider-invalid/issue.
- ASCII and Arabic/Unicode description inputs lock the byte-based 2,048 boundary so that `mb_strlen`/grapheme substitution cannot silently change it.
- duration: 1 accepted; 28,800 accepted; 28,801 provider-invalid/issue.
- both `content_loc` and `player_loc` missing.
- `content_loc == parent <loc>`.
- `player_loc == parent <loc>`.
- both documented publication-date forms plus invalid input.
- Data URL rejection.
- HTTP, HTTPS, and FTP evidence cases must preserve the documented source nuance; do not create a test whose assertion falsely claims Google literally enumerates all three in one normative sentence.
- title match and `content_loc` preference are recommendations/context diagnostics, not generic constructor validity failures.

Do **not** create offline tests that claim a URL extension proves the actual remote video file type, thumbnail format/dimensions/transparency, Googlebot accessibility, resource stability, or watch-page indexing eligibility. If a future context validator accepts caller-supplied evidence for those facts, test the evidence-processing contract, not the network fact itself.

### Google News

- one News entry per URL provider-valid.
- multiple News entries under one URL provider-invalid/diagnostic while legacy list behavior remains characterized.
- 1,000 total News entries boundary valid.
- 1,001 total News entries provider-invalid/issue.
- all four accepted publication-date forms.
- invalid arbitrary date such as `as-provided`.
- valid/invalid language cases including `zh-cn` and `zh-tw` according to the explicit Google News lexical contract recorded in F-05; do not generalize this into the deferred hreflang ISO-membership capability.
- publication-name parenthetical rule where locally decidable.
- freshness state `within_window` is processed as caller-supplied provider evidence without local age arithmetic.
- freshness state `outside_window` is processed deterministically as provider-out-of-window evidence.
- freshness state `unknown` remains an evidence gap/diagnostic and is not fabricated into a pass or failure.
- lexical `publicationDate` plus a reference clock must not trigger an invented `48 hours`, calendar-day, or timezone boundary calculation; no hidden `now()`.
- original-publication-time/name/title truth remains content/context evidence, not fake lexical proof.

### Google robots.txt

- 500 KiB document boundary classification.
- Google Allow/Disallow present-path leading `/` cases.
- non-empty leading-`*` remains generic-compatible but is distinguishable as the Google provider-path diagnostic `robots_google_present_path_leading_slash` (GDC-01 companion-only); no silent rewrite.
- `Sitemap:` fully-qualified URL.
- raw Unicode/non-URL-encoded Sitemap path accepted according to Google's documented contract.
- multiple Sitemap fields.
- cross-host Sitemap URL.
- Sitemap field independence from user-agent groups.

### Google robots meta

- `max-snippet:-1`.
- `max-video-preview:-1`.
- values below `-1` invalid for those helpers.
- `indexifembedded` helper emits `robots_meta_indexifembedded_without_noindex` (warning, origin `provider`, profile `google`, GDC-01 companion-only) when `noindex` is absent; raw representation remains possible and no builder construction error occurs.
- `unavailable_after` empty/missing provider value is locally diagnosable as `robots_meta_unavailable_after_missing` (GDC-01 companion-only).
- non-empty `unavailable_after` with caller evidence `recognized` is processed deterministically as recognized.
- non-empty `unavailable_after` with caller evidence `unrecognized` is processed deterministically as unrecognized.
- non-empty `unavailable_after` with evidence `unknown` remains an evidence gap rather than being parsed through an invented exhaustive grammar.
- no hidden network/clock/locale behavior is used to infer recognizability.
- none of the robots-meta diagnostics above appears in the legacy result or affects scoring (GDC-01).

### Hreflang

- `en`.
- `en-US`.
- `zh-Hant`.
- `zh-Hans-US`.
- `x-default`.
- equivalent conventional-casing normalization through Web and Sitemap entry points.
- casing differences alone do not produce provider-invalidity.
- no test claims that an unversioned local regex/list proves ISO 639-1 / ISO 3166-1 / ISO 15924 membership.
- reciprocal cluster.
- missing self-reference.
- missing return link.
- inconsistent alternate set across supplied localized URLs.

### Canonical

- relative canonical output remains generic-compatible and identical to current behavior.
- a relative canonical produces `canonical_relative_provider_best_practice` (warning, origin `provider`, profile `google`) through the GDC-01 companion surface; it never throws and never changes the legacy result or score.

### Global diagnostics contract (GDC-01)

Tightest contract coverage across Stacks 2/4/5/6 — asserted for every new diagnostic (Robots, Sitemap, OGP, canonical, hreflang):

- the legacy `SeoValidationResultDTO` serialized shape (`is_valid`, `has_warnings`, `errors`, `warnings`, `info`, `issues`) is byte-stable when only new companion diagnostics are present.
- `is_valid` is `true` and `has_warnings` is `false` in the legacy result even when a new `warning`-severity companion diagnostic is present.
- `SeoValidationScoreCalculator` output (score, grade, counts, `is_healthy`) is unchanged by the presence of new companion diagnostics.
- each new diagnostic carries the fixed origin/profile/evidence-state metadata and a stable, non-colliding code.
- no new diagnostic reuses or shadows a legacy issue code.

### Structured Data boundary

- existing generic builders remain callable regardless of whether a Google search feature has been audited.
- existing `JsonLdSemanticValidator` property-range behavior remains compatible.
- no new lexical URL/Date/DateTime/enumeration failures are introduced under this remediation.
- no Google required/recommended-property eligibility failure is introduced without the future approved capability-matrix contract.
- documentation/tests do not equate generic Schema.org support with Google eligibility.

## 9.4 Non-regression tests

Every standards/provider fix must prove that unrelated behavior remains unchanged.

Examples:

Changing `maxSnippet(-1)` must not change:

- directive insertion order.
- prefix replacement.
- duplicate removal.
- HTML escaping.

Unifying Sitemap serialization must not silently drop image/video/news/alternate data from an existing public entry point.

Open Graph validation changes must not reorder multiple images or detach structured image properties from their root image.

Reclassifying title/description length issues as heuristics must not change their `strlen()` byte thresholds, warning severity/codes, existing score deductions, or legacy issue/result serialized payloads.

The OGP migration must not remove or re-severity `missing_og_title`, `missing_og_description`, or `missing_og_image` in the legacy result, and must not add `missing_og_type` / `missing_og_url` to the legacy result or score.

Applying the measurement contract must not change any non-length validation (URL shape, URI/IRI escaping, `lastmod` lexical forms) and must not rebase the boundary to a different unit.

Structured-data documentation/classification work must not introduce new lexical/provider eligibility failures or scoring changes.

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
| F-02 | Base Sitemap and provider policies are not separated; `lastmod` has a lexical implementation mismatch | ADD / RECLASSIFY | High |
| F-03 | Google Image legacy tags and provider constraints need explicit classification | RECLASSIFY | Medium |
| F-04 | Google Video provider contract is materially incomplete and mixes local/context/remote evidence | ADD | High |
| F-05 | Google News validation is incomplete; per-URL cardinality and canonical example are wrong for the provider | ADD / DOC-FIX | High |
| F-06 | robots.txt DTOs mismatch RFC 9309 and Google `Sitemap:`/path contracts need a separate provider profile | FIX / RECLASSIFY | High |
| F-07 | crawl-delay presented like core REP behavior | RECLASSIFY | Medium |
| F-08 | Meta robots rejects valid Google `-1` | FIX | High |
| F-09 | `indexifembedded` missing | ADD | Medium |
| F-10 | `unavailable_after` provider recognizability requires an explicit evidence boundary | RECLASSIFY / EVIDENCE BOUNDARY | Medium |
| F-11 | `noarchive` Google meaning stale | KEEP / DOC-FIX | Low/Medium |
| F-12 | SEO validity and heuristics conflated | RECLASSIFY / ADD | High |
| F-13 | Open Graph required-field model mismatches OGP and needs explicit exposed-surface serialization contracts | FIX | High |
| F-14 | Relative canonical must remain generic-compatible | KEEP / ADD profile | Medium |
| F-15 | Hreflang normalization/cluster logic is fragmented; ISO code-membership registry is a separate future contract | RECLASSIFY / ADD / DEFER MEMBERSHIP | High |
| F-16 | Structured Data provider separation is correct; Google eligibility profiles require a separate audited matrix | KEEP / DEFER PROVIDER PROFILE | High |
| F-17 | Old Course/Book deprecation assumption is unsafe | CORRECTION | High |
| F-18 | JSON-LD validator is scoped property-range validation; lexical/provider expansion is separate future work | RECLASSIFY / DEFER EXPANSION | Medium |
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

The safest remediation is a sequence of narrow contract-preserving stacks, each starting with characterization and then implementing the contracts recorded by this audit.

The target is:

> one implementation source of truth per domain, provider-neutral core contracts, explicit provider profiles where this audit has established them, explicit heuristics, deterministic validation, explicit evidence/future-contract boundaries, and documentation that accurately states what the library can and cannot prove.

### Execution Authority Rule

This audit is the **baseline execution authority** for remediation Stacks 0 through 8. A fresh implementer must not need to reinvent classifications, infer unwritten provider rules, restart a standards audit from scratch, or choose among materially different contract outcomes.

- **Repository freshness:** each implementation stack must inspect its then-current Draft HEAD before modifying production behavior, because repository state can change after this snapshot.
- **Provider freshness:** time-variable provider facts already inside the approved remediation scope receive a targeted freshness check against the authoritative URLs recorded here.
- **No automatic reopening:** a freshness check is not permission to reopen settled architecture or perform broad provider research. If an authoritative source has materially changed after the audit date, record the changed evidence and amend the relevant contract explicitly before implementing against it.
- **Evidence boundaries:** remote/provider facts that the library cannot prove offline remain explicit caller/host/provider evidence boundaries.
- **GDC-01 global diagnostics contract:** every new protocol/provider/context diagnostic introduced by Stacks 2/4/5/6 is delivered through the companion surface and must not mutate the legacy result, `is_valid`, `errors`/`warnings`/`info`/`issues`, or `SeoValidationScoreCalculator`. Pre-existing legacy issues keep their documented F-12 placement and score behavior. This is part of the execution authority, not an implementation preference.
- **Measurement policy:** numeric textual boundaries (Sitemap `<loc>` < 2048, Google Video `description` <= 2048) are UTF-8 bytes via `strlen()` on the value as supplied, per the F-02 exact measurement contract; Stack 4 must not substitute another unit.
- **Unknown-decision gate:** a material `unknown / needs decision` discovered by characterization blocks the affected production change until an approved contract amendment resolves it.
- **Future-contract boundaries:** hreflang ISO membership registries, complete Google structured-data capability/eligibility profiles, Twitter/X provider conformance, and the F-18 lexical/enumeration expansion are not implementation discretion under this audit. They are separate future contracts.

No production behavior should be changed merely because it "looks more strict."

A behavior change is justified only when the audit classifies it, its authoritative source is recorded where applicable, its compatibility impact is understood, and its non-target behavior is protected by tests.

---

# 13. Authoritative External References

The references below are the evidence baseline for this audit. During remediation, perform **targeted freshness verification** of time-variable provider facts already authorized here; do not restart standards/provider research or expand into an explicitly deferred contract unless its own audit is opened and approved.

## Standards / protocols

- W3C Datetime  
  https://www.w3.org/TR/NOTE-datetime

- PHP `strlen` function documentation  
  https://www.php.net/manual/en/function.strlen.php

- PHP Filter Constants (`FILTER_VALIDATE_URL`)  
  https://www.php.net/manual/en/filter.constants.php

- RFC 9309 — Robots Exclusion Protocol  
  https://www.rfc-editor.org/rfc/rfc9309.html

- RFC 9309 Errata 7995 — reported path-pattern leading-wildcard inconsistency  
  https://www.rfc-editor.org/errata/eid7995

- RFC 5646 / BCP 47 — Tags for Identifying Languages  
  https://www.rfc-editor.org/rfc/rfc5646.html

- Sitemaps.org Protocol  
  https://www.sitemaps.org/protocol.html

- Sitemaps.org Sitemap schema (`sitemap.xsd`)  
  https://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd

- Open Graph Protocol  
  https://ogp.me/

- Schema.org  
  https://schema.org/

## Google Search

- Build and Submit a Sitemap  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap

- Video SEO Best Practices  
  https://developers.google.com/search/docs/appearance/video

- Video sitemaps  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/video-sitemaps

- News sitemaps  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap

- Image sitemaps  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/image-sitemaps

- Google sitemap extension deprecation notice  
  https://developers.google.com/search/blog/2022/05/spring-cleaning-sitemap-extensions

- Robots.txt specification  
  https://developers.google.com/crawling/docs/robots-txt/robots-txt-spec

- Robots meta tag specifications  
  https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag

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
- `src/Web/Robots/RobotsTxtRenderer.php`
- `src/Web/Robots/MetaRobotsBuilder.php`
- `src/Web/Validation/SeoMetaValidator.php`
- `src/Web/Validation/SeoValidationPreset.php`
- `src/Web/Validation/JsonLd/JsonLdSemanticValidator.php`
- `src/Web/Validation/DTO/SeoValidationIssueDTO.php`
- `src/Web/Validation/DTO/SeoValidationResultDTO.php`
- `src/Web/Social/OpenGraphBuilder.php`
- `src/Web/Social/SocialImage.php`
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
