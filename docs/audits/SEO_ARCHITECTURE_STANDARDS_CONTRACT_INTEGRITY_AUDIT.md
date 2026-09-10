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

### Protocol layer and Google provider layer must not be conflated

F-02 establishes two separate concerns for host/location/context validation:

- **Sitemaps.org protocol context** (profile `sitemaps`, origin `protocol`): sitemap location/scope, applicable scheme/host/port, path scope derived from sitemap location, Sitemap Index same-site restriction, cross-submission authority semantics (evidence key `sitemaps.cross_submission_authority`, FIX 19). These are generic Sitemap protocol rules, not Google Search Console diagnostics.
- **Google provider context** (profile `google`, origin `provider`): `google_sitemap_host_context` emits `verified_host`/`unverified_host`/`unknown` evidence state **only** when the document explicitly concerns Google verified ownership/submission context (Search Console evidence). This provider evidence diagnostic must **not** be used as a substitute for generic Sitemap protocol location-scope validation.

The protocol-level `sitemap_location_scope_violation` diagnostic (fixed in the GDC-01 machine-contract table) handles the generic Sitemaps.org scope rules using deterministic caller-supplied document/location context, without Search Console, network access, or guessed ownership.

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

The two authoritative sources state "less than 2,048 characters" (Sitemaps.org, for the page `<loc>`) and "a maximum of 2,048 characters" (Google Video, for `video:description`). **Neither source defines the unit** as UTF-8 bytes, Unicode code points, or grapheme clusters, and neither defines preprocessing/normalization for the length check. The audit therefore separates, for each limit, what the source proves from what the library does.

**Source-backed protocol/provider rule:**

- `<loc>`: the sitemaps.org protocol rule is "the page URL must be less than 2,048 characters." The unit is a **character** quantity; the source does not fix whether that means UTF-8 bytes, Unicode code points, or grapheme clusters, and does not define measurement relative to percent-encoding/normalization.
- `video:description`: the Google Video rule is "a maximum of 2,048 characters," with the same unit and normalization gap.
- Because the unit is undefined by the authoritative source, exceeding a *byte* threshold cannot be asserted as a proven violation of the source's *character* rule. That would be an overclaim.

**Library deterministic policy (EVIDENCE BOUNDARY):**

- The unit is an **EVIDENCE BOUNDARY** under this audit's vocabulary: the source is intentionally open-ended on the unit, so the library must not fabricate a byte-equals-characters equivalence while claiming source backing.
- To keep the boundary deterministic and authorization-complete (no implementation-time choice), the audit fixes a **library measurement policy**: measure **UTF-8 bytes** with the library-native `strlen()` on the value **as supplied** to the validating entry point.
  - Rationale: this matches the library's existing fixed byte-measurement compatibility contract for text-length boundaries (F-12 preserves `strlen()` byte measurement for title/description heuristics), keeps the measurement idiom uniform across the library, and requires no `mbstring`/PCRE-unicode dependency.
  - Conservativeness: passing the byte boundary is **conservative proof** that the value is also below the same numeric threshold under Unicode code-point or grapheme counting, because a UTF-8 string of `n` bytes contains **at most** `n` Unicode code points and therefore at most `n` graphemes — the UTF-8 byte count is never smaller than those counts for valid UTF-8 text. Failing the byte boundary is **not** proof that the source-defined character limit is exceeded; multi-byte text may exceed the byte threshold while remaining below the same character-count threshold, so the library argues upward from the safe side and represents exactly that shade of uncertainty instead of claiming a proven violation. In particular, the byte measurement does **not** "never reject something the character rule would accept": it can flag a multi-byte value that a character-count rule at the same numeric threshold would accept, which is exactly why the at/above-boundary result is a conservative library-policy warning rather than a proven violation.
  - The byte policy applies until an authoritative source defines a different unit or a separate contract amendment replaces it. It must not be re-derived during Stack 4.
- **Measurement point — `<loc>`:** the length is measured **before** URI/IRI normalization and before percent-encoding, on the authored `loc` value **as supplied**. The source does not define whether the limit applies to the authored or the encoded form; the audit fixes the input-time point as part of the library deterministic policy, **without** claiming source backing for that choice. Percent-encoding is treated as a serialization/output operation checked separately by its own escaping guarantee; it does not re-trigger the length boundary, and no claim is made that the authoritative source intends the encoded or unencoded form.
- **Measurement point — `video:description`:** the length is measured on the description value **as supplied** to the video validator/entry point, before any XML escaping/CDATA wrapping. The same input-time policy and the same no-claim-about-encoded-form caveat apply.
- **Boundaries and emitted diagnostics:**
  - `<loc>`: `strlen(loc) < 2048` — a value of 2,047 bytes is **conservatively below** the numeric character threshold under code-point/grapheme interpretations; a value of 2,048 or more bytes emits `sitemap_loc_length_exceeds_measure_boundary` (warning, GDC-01 companion-only), which is a **library conservative-policy warning**, not a claim of a proven protocol violation.
  - `video:description`: `strlen(description) <= 2048` — a value of 2,048 bytes is **conservatively below** the numeric character threshold under code-point/grapheme interpretations; a value of 2,049 or more bytes emits `google_video_description_length_exceeds_measure_boundary` (warning, GDC-01 companion-only), a **library conservative-policy warning**, not a claim of a proven provider violation.
- These policies are part of the audit authority. Stack 4 must implement exactly these boundaries with the corresponding tests and must not substitute `mb_strlen`, grapheme counting, post-encoding measurement, or a different unit, and must not re-word the emitted diagnostics as proven protocol/provider violations. (The title/description heuristic measurement is covered separately by F-12.)
- **Same principle for other textual limits whose unit is not source-defined:** any numeric textual boundary in this remediation whose unit the authoritative source does not fix must receive the same two-part treatment (source rule as stated + explicit library deterministic policy) with the length-check diagnostic worded as a library policy boundary. GDC-01 machine contracts already encode this for the only two such boundaries in remediation scope.

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
- For pre-existing legacy issues, that additive classification is emitted as the **legacy classification record** fixed in the GDC-01 Normative Legacy Classification Table: a companion entry with `code == related_legacy_code ==` the legacy issue code, `evidence_state = null`, and the fixed origin/profile/field for that code. These records are companion metadata, **not** new diagnostics, and produce no additional legacy, scoring, or validity effect.
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

### Validation candidate layer — strict domain DTOs are not validation inputs

Strict domain/rendering DTOs and validation candidate inputs are **two separate architectural layers**. They must not be conflated:

```text
Raw / Candidate Validation Input
        |
        v
Protocol / Provider Validators
        |
        v
Companion Diagnostics


Strict Valid Domain DTOs
        |
        v
Builders / Renderers / Generators
```

- **Lane A — existing strict domain/rendering DTOs.** The following types remain exactly as they are today; the audit does **not** change their constructors or invariants:
  - `Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO` — rejects invalid `loc` and invalid `lastmod`.
  - `Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO`
  - `Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO` — rejects empty thumbnail, empty title, empty description, missing both `contentLoc` and `playerLoc`, non-positive duration, and invalid publication date.
  - `Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO` — rejects required empty fields.
  - `Maatify\Seo\Shared\DTO\Sitemap\SitemapIndexEntryDTO`
  - `Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO`
  - `Maatify\Seo\Web\Robots\DTO\RobotsTxtDTO`
  - `Maatify\Seo\Web\Robots\DTO\RobotsRuleDTO` — rejects empty Allow/Disallow paths.
  - `Maatify\Seo\Web\Hreflang\HreflangLinkDTO`
  - `Maatify\Seo\Web\Robots\MetaRobotsBuilder`
  
  These are valid domain/rendering DTOs. Any current rejection behavior remains preserved **unless** a specific finding in this audit states an explicit, separate intentional correction (for example F-08 `-1` semantics).
- **Lane B — validation candidate inputs.** This audit adds a dedicated public validation-input model. Its purpose is:
  - to represent candidate data **before** protocol/provider validity is proven;
  - to be able to represent missing and invalid lexical values (which strict DTO constructors reject);
  - to perform **no** rendering;
  - to **not** change, replace, or become a substitute for the existing DTOs;
  - its constructor performs **no** protocol/provider validation.

**Architecture Preservation Rule — fixed:**

> Validation Candidate DTOs are additive validation-only inputs. They do not replace, relax, widen, or redefine the constructors or accepted states of existing strict domain/render DTOs.

The strict DTO is **not** weakened merely so a validator can observe invalid candidate state; the validator uses the candidate input when it must see that state. The candidate model is exactly what makes current machine-contract diagnostics reachable — for example malformed Sitemap `lastmod`, missing Video title/description/thumbnail, both video media locations missing, invalid Video publication date, out-of-range Video duration, invalid News publication date/language, relative Hreflang URLs, and raw robots path cases — **without** weakening any strict constructor (FIX 23). No public candidate-to-domain conversion contract is introduced (`toDomainDto()`, `fromDomainDto()`, public mapper/factory, or automatic normalization service); private/internal adapters from strict DTOs to candidate input are non-observable implementation details.

### Decision

For each new diagnostic produced by the remediation:

1. **Container.** New diagnostics do **not** enter the legacy `SeoValidationResultDTO` as part of its `errors`, `warnings`, `info`, or `issues` collections. They are emitted through the additive **companion classification surface** introduced by F-12: a companion/profile diagnostics collection distinct from the legacy result, each entry being a `SeoCompanionDiagnosticDTO` (the fixed entry type defined in the public access contract below) pairing a stable machine identifier (`code`), `severity`, `message`, optional `field`, `origin`, `profile`, an optional `evidence_state` chosen only from the states its defining contract fixes, and an optional `related_legacy_code`.

2. **`is_valid` is never changed by a new diagnostic.** `SeoValidationResultDTO::is_valid` continues to mean "no legacy error-severity issues". New provider/protocol/context diagnostics cannot turn a legacy-valid result into an invalid one.

3. **Legacy collections are never mutated by a new diagnostic.** A **new diagnostic** must not be appended to the legacy `errors`, `warnings`, `info`, or `issues` series, and must not reuse or shadow an existing legacy issue code. A **legacy classification record** is the sole exception: it intentionally uses the same `code` and `related_legacy_code` as the legacy issue it classifies (see the Normative Legacy Classification Table below) and introduces no additional legacy, scoring, or validity effect.

4. **Scoring is never changed by a new diagnostic.** `SeoValidationScoreCalculator` continues to consume only the legacy issue severity contract exactly as it does today. New companion diagnostics contribute **no** deduction, not even a zero-point placeholder, and must not alter existing `error_count`, `warning_count`, `info_count`, `is_healthy`, grade, or score.

5. **Relationship to legacy issues.** Where a new diagnostic concerns the same subject as an existing legacy issue, the companion record references the legacy issue via its stable code; the legacy issue itself remains in the legacy result unchanged. The two surfaces are correlated, never merged.

6. **Single exception — pre-existing legacy issues.** Diagnostics that already existed before this remediation as `SeoValidationIssueDTO` objects (for example `missing_title`, `missing_og_title`, `missing_og_description`, `missing_og_image`, title/description length warnings) keep their current legacy placement, severity, code, and score participation under F-12. Reclassification adds companion origin/profile metadata only; it does not move them to the companion surface. Those companion metadata entries — where `code == related_legacy_code ==` the existing legacy issue code — are **legacy classification records**, not new diagnostics; they are the sole, intentional exception to the no-reuse rule and are fixed in the Normative Legacy Classification Table below.

### Public access contract — fixed surface (Stack 1 / Stack 5 deliverable)

This subsection fixes the consumer-facing shape of the companion surface so implementation never chooses a method, service, DTO, or result shape. Stack 1 and Stack 5 must implement exactly this contract, no more and no less.

- **Unified result type.** Every profile validator introduced by this audit returns the **same** concrete result type: `Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO`. There is exactly one result type for the companion surface; per-profile result variants, per-profile subclasses, and alternative result containers are **not** introduced.
- **Entry type.** Each companion diagnostic is `Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO` with exactly these fields:
  - `code` — non-empty stable string, fixed per the machine-contract tables below (Stacks 2/4/6) and the fixed F-13 OGP contracts;
  - `severity` — one of `error` / `warning` / `info`. For ordinary diagnostics, severity is fixed per code. For evidence-state diagnostics, severity is fixed by the normative `(code, evidence_state) → severity` mapping defined in the machine-contract table;
  - `message` — non-empty;
  - `field` — `?string`: the sitemap tag, meta field, or attribute the diagnostic concerns, or `null` for document/context-level diagnostics;
  - `origin` — one of the F-12 fixed vocabulary: `protocol` / `provider` / `heuristic` / `content-quality`;
  - `profile` — a stable profile identifier from the fixed set used by this audit (`sitemaps`, `google`, `ogp`, `rfc9309`, `seo-default`);
  - `evidence_state` — `?string`, present **only** for evidence/context diagnostics and matching exactly one of the states its defining contract fixes (for example `recognized`/`unrecognized`/`unknown`, `within_window`/`outside_window`/`unknown`, `accurate`/`inaccurate`/`unknown`, `original`/`not_original`/`unknown`, `matched`/`mismatched`/`unknown`, `accessible`/`inaccessible`/`unknown`, `relevant`/`irrelevant`/`unknown`, `verified`/`unverified`/`unknown`, `verified_host`/`unverified_host`/`unknown`);
  - `related_legacy_code` — `?string`: the stable legacy `SeoValidationIssueDTO` code this companion record concerns, when the same subject already has a legacy issue (correlation metadata only; it never moves the legacy issue);
  - `target` — the **required** `SeoDiagnosticTargetDTO` fixed in "Diagnostic target locator — fixed contract" below. It is **not** nullable and is present on **every** companion entry, including legacy classification records. The human-readable `message` / `field` is **never** used as target identity.
  - Construction rules mirror the legacy DTO: empty `code` or `message` and unknown `severity`, `origin`, `profile`, or `evidence_state` values are construction errors. `evidence_state` values are scoped per code: a state not listed for that code is invalid for it. The construction guard must also reject any severity that does not match the fixed code severity for ordinary diagnostics, or that does not match the normative `(code, evidence_state) → severity` mapping for evidence-state diagnostics. A severity/evidence-state mismatch is never left to implementer interpretation. An invalid `target` shape (see "Target shape guards" below) is also a construction error.
- **Correlation with the legacy result.** `SeoCompanionValidationResultDTO` exposes a nullable `legacy` property holding the `SeoValidationResultDTO` that was paired with the profile run, and `null` when the profile validator was invoked standalone. Correlation is therefore available both per-entry (`related_legacy_code`) and at the container level (`legacy`). The companion result never replaces the legacy result and never borrows its fields. **Single exception:** `OpenGraphProtocolValidator` always embeds the exact legacy result produced by `SeoMetaValidator::validate($meta, $options)` from the same `$meta` and `$options`, because OGP compatibility intentionally reuses existing legacy OGP issues as classification records (FIX 9 / FIX 26). Robots, Sitemap, Canonical, and Hreflang standalone profile runs keep `legacy = null`.
- **Invocation surface.** The legacy `SeoMetaValidator::validate()` is unchanged letter-for-letter and continues to return `SeoValidationResultDTO`:

  ```php
  public static function validate(
      array|object $meta,
      array $options = []
  ): SeoValidationResultDTO
  ```

  The additive `SeoMetaValidator::validateWithCompanion(array|object $meta, array $options = [], ?SeoValidationContextDTO $context = null): SeoCompanionValidationResultDTO` is the single public method that returns both surfaces for the web/meta/OGP path, embedding the identical legacy result under `legacy`. Every profile validator added by the remediation exposes exactly one of the fixed public signatures defined in "Profile validator public signatures — fixed" below, all returning the same unified type `SeoCompanionValidationResultDTO`; Stack 5 does not design an alternative result type, an alternative method contract, an alternative input shape, or an alternative legacy-pairing mechanism.
- **Serialization shape.** `SeoCompanionValidationResultDTO` implements `\JsonSerializable` and its `toArray()`/JSON shape is fixed:

  ```json
  {
    "legacy": null,
    "diagnostics": [
      {
        "code": "sitemap_url_count_exceeds_limit",
        "severity": "error",
        "message": "...",
        "field": "urlset",
        "origin": "protocol",
        "profile": "sitemaps",
        "evidence_state": null,
        "related_legacy_code": null,
        "target": {
          "scope": "sitemap_document",
          "entry_index": null,
          "item_index": null,
          "line": null
        }
      }
    ]
  }
  ```

  Keys are snake_case; the `code`/`severity`/`origin`/`profile`/`evidence_state` vocabularies are preserved verbatim. When a profile run is paired with the legacy validator, `legacy` contains the exact `SeoValidationResultDTO` shape emitted today (`is_valid`, `has_warnings`, `errors`, `warnings`, `info`, `issues`). `SeoCompanionValidationResultDTO` carries **no** `is_valid`, `has_warnings`, score, grade, or health fields. Consumers may derive their own aggregates, but no derived aggregate may be fed back into `SeoValidationScoreCalculator`.
- **Unified profile surface.** Profile validators (Google base Sitemap, Google Image, Google Video, Google News, Google robots.txt, robots meta, RFC 9309, canonical, hreflang cluster, OGP) emit exactly the companion entries fixed in the machine-contract tables below and in the fixed F-13 OGP contracts. A profile validator must not add a code, severity, origin, profile, field, evidence state, or target scope not listed in this audit. Every Sitemap/Robots/Hreflang profile validator consumes the fixed **validation candidate** input types defined in "Validation candidate inputs — fixed public input contract" below, never the existing strict domain/render DTOs.

### Unified immutable context DTO — fixed public input contract

The companion/public validation surface consumes **one** immutable context DTO rather than per-validator invented method arguments. The fixed class is:

```php
final readonly class Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO
{
    public function __construct(
        public ?array $evidence = null,
    );
}
```

It is the **additive public DTO** for all companion/profile validation. It has exactly this one public field and no other public fields. There is **no** `documentContext` field: local deterministic Sitemap document state belongs to `Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO` (defined below), which is the correct home for it (FIX 7). No network, client, or clock dependency is introduced, and no generic extension mechanism exists. The `evidence` array is a **typed semantic map** whose keys are the fixed, Audit-authorized keys defined in the "Evidence input keys — fixed contract" and "Normative evidence shapes" subsections below. It is **not** an arbitrary policy-extension mechanism. Constructor validation is fixed in "Context construction guards" below.

The following invocation contracts are fixed.

**Legacy `SeoMetaValidator::validate()` — preserved letter-for-letter:**

```php
public static function validate(
    array|object $meta,
    array $options = []
): SeoValidationResultDTO
```

This is the actual existing repository contract and stays unchanged: `static`, `array|object $meta`, `$options` with default `[]`, and `SeoValidationResultDTO` return type. `MetaTagsDTO` remains a valid input through the `object` branch but is **not** the only accepted input. The audit's earlier `validate(MetaTagsDTO $meta)` wording is withdrawn; it is not a valid contract.

**Additive `SeoMetaValidator::validateWithCompanion()` — fixed signature:**

```php
public static function validateWithCompanion(
    array|object $meta,
    array $options = [],
    ?SeoValidationContextDTO $context = null
): SeoCompanionValidationResultDTO
```

Parameter order is fixed: `$meta`, then `$options`, then `$context`. No overload variant is used; `$options` is not dropped; `$context` is not moved ahead of `$options`.

**Required behavior of `validateWithCompanion()`:**

1. Produces the exact legacy validation semantics equivalent to `SeoMetaValidator::validate($meta, $options)`.
2. Embeds that identical `SeoValidationResultDTO` unchanged under `SeoCompanionValidationResultDTO::$legacy`.
3. Adds **only** the companion legacy classification records (F-12 / GDC-01 Normative Legacy Classification Table, fixed below) and the fixed F-13 Open Graph companion diagnostics authorized below.
4. Does **not** automatically run the Google robots.txt, Google robots-meta, Sitemap, Google Canonical, or hreflang cluster profiles. Those profiles are independent and are invoked through their own validators.

`validateWithCompanion()` is responsible only for: legacy-result pairing; F-12 legacy classification records; F-13 Open Graph companion behavior. It is **not** a universal validation orchestrator.

**Open Graph delegation — single source of truth (FIX 12):**

`validateWithCompanion()` uses the **same** OGP companion behavior defined by `OpenGraphProtocolValidator`. No two independent implementations of the OGP companion rules may exist. The observable result must satisfy:

```text
validateWithCompanion(meta, options, context)
==
OpenGraphProtocolValidator::validate(meta, options, context)
```

with respect to the embedded legacy result, the legacy classification records, and the OGP companion diagnostics (and any ordering contract this audit fixes). Internally it is permissible for one to delegate to the other; **which** one delegates is an internal non-observable detail, as long as there is no duplicated rule logic and no observable difference.

**Profile validator public signatures — fixed.**

Every profile validator returns the unified `Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO` and takes the optional unified evidence-only context DTO. The signatures below are fixed letter-for-letter. The Sitemap/Robots/Hreflang profile inputs are the fixed **validation candidate** types defined in "Validation candidate inputs — fixed public input contract" below; the previous signatures based on strict domain DTOs (`RobotsTxtDTO`, `MetaRobotsBuilder`, `SitemapUrlDTO` lists, `array<string,list<HreflangLinkDTO>>`) are superseded and withdrawn. No generic placeholder such as `<existing-domain-input>` exists anywhere in the public contract.

**1. RFC 9309 robots**

```php
final class Rfc9309RobotsValidator
{
    public function validate(
        RobotsTxtValidationInputDTO $input,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Input type: `Maatify\Seo\Web\Validation\Input\RobotsTxtValidationInputDTO`.

**2. Google robots.txt**

```php
final class GoogleRobotsTxtValidator
{
    public function validate(
        RobotsTxtValidationInputDTO $input,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Input type: `Maatify\Seo\Web\Validation\Input\RobotsTxtValidationInputDTO`.

**3. Google robots meta**

```php
final class GoogleRobotsMetaValidator
{
    public function validate(
        RobotsMetaValidationInputDTO $input,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Input type: `Maatify\Seo\Web\Validation\Input\RobotsMetaValidationInputDTO`. The validator interprets directives; `MetaRobotsBuilder` remains a generation/domain DTO unchanged. No public adapter/factory contract from the builder to candidate input is added.

**4. Sitemap protocol**

```php
final class SitemapProtocolValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Input type: `Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO`.

**5. Google base Sitemap**

```php
final class GoogleSitemapValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Requirement: `$document->type === 'urlset'`; any other type is invalid invocation (library invalid-argument exception family), not an SEO diagnostic.

**6. Google Image Sitemap**

```php
final class GoogleImageSitemapValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Requirement: `$document->type === 'urlset'`; it inspects the child `images` of each entry.

**7. Google Video Sitemap**

```php
final class GoogleVideoSitemapValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Requirement: `$document->type === 'urlset'`; it inspects the child `videos` of each entry. The parent page loc for a video is `$document->entries[$urlIndex]->loc`; no duplicate parent-loc field exists in any context or document field.

**8. Google News Sitemap**

```php
final class GoogleNewsSitemapValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Requirement: `$document->type === 'urlset'`; it inspects the child `news` of each entry. This allows document-wide 1,000-News-entry validation from the candidate document.

**9. Open Graph**

```php
final class OpenGraphProtocolValidator
{
    public function validate(
        array|object $meta,
        array $options = [],
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

It uses the same broad meta input domain already supported by `SeoMetaValidator` (`array|object`). It is not narrowed to `MetaTagsDTO`. Its activation and legacy-pairing contracts are fixed in F-13 / FIX 9–11 (presence-triggered; always embeds the exact legacy result).

**10. Google Canonical**

```php
final class GoogleCanonicalValidator
{
    public function validate(
        string $canonical,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

It diagnoses the supplied canonical URL string. It does not modify `CanonicalUrlBuilder`.

**11. Google Hreflang Cluster**

```php
final class GoogleHreflangClusterValidator
{
    public function validate(
        HreflangValidationClusterDTO $cluster,
        ?SeoValidationContextDTO $context = null
    ): SeoCompanionValidationResultDTO;
}
```

Input type: `Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationClusterDTO`. The cluster is itself the complete deterministic cluster; hreflang cluster data never goes in any context field.

**Key authorization rule:**

> A profile validator may read only context/evidence keys explicitly authorized by the Audit machine contracts. Unknown keys must not create new diagnostics or new semantics.

### Concrete public profile validator classes — fixed

The following classes/namespaces are fixed public contracts in this Audit. An implementer may not choose alternative names or per-profile result DTOs. Each class uses exactly the public signature fixed in the "Profile validator public signatures — fixed" subsection above.

- `Maatify\Seo\Web\Validation\Profile\Rfc9309RobotsValidator`
- `Maatify\Seo\Web\Validation\Profile\GoogleRobotsTxtValidator`
- `Maatify\Seo\Web\Validation\Profile\GoogleRobotsMetaValidator`
- `Maatify\Seo\Web\Validation\Profile\SitemapProtocolValidator`
- `Maatify\Seo\Web\Validation\Profile\GoogleSitemapValidator`
- `Maatify\Seo\Web\Validation\Profile\GoogleImageSitemapValidator`
- `Maatify\Seo\Web\Validation\Profile\GoogleVideoSitemapValidator`
- `Maatify\Seo\Web\Validation\Profile\GoogleNewsSitemapValidator`
- `Maatify\Seo\Web\Validation\Profile\OpenGraphProtocolValidator`
- `Maatify\Seo\Web\Validation\Profile\GoogleCanonicalValidator`
- `Maatify\Seo\Web\Validation\Profile\GoogleHreflangClusterValidator`

If existing repository namespace evidence proves that one of these names collides with an already-existing class, that collision is recorded as an AP-11 blocker and is resolved by an audit amendment — not by renaming at implementation time.

### Validation candidate inputs — fixed public input contract

This subsection fixes the validation candidate (Lane B) model. The following types are public contracts; the profile validators above consume them. **No** new diagnostic code is added merely because a candidate DTO can carry a malformed state (FIX 23); the candidate layer only makes the already-locked machine-contract diagnostics reachable.

**Candidate representability is not equivalent to diagnostic authorization (fixed).** A Candidate DTO may intentionally carry a malformed/null value even where the current remediation has **no** diagnostic for that field. A validator may emit only diagnostics explicitly present in GDC-01 and in F-13. If a candidate state has been explicitly classified as no-diagnostic/deferred by this Audit, it stays silent. This rule prevents the implementer from using broad Candidate DTO shapes as permission to invent validation rules.

#### Candidate construction rule — fixed (FIX 2)

Every Candidate DTO in this remediation is:

- `final readonly`;
- input-only;
- performs **no** `trim()`, **no** normalization, **no** URL validation, **no** date parsing, **no** provider validation, **no** protocol validation, and **no** numeric-range validation beyond the structural PHP constraints explicitly listed below;
- preserves the string **exactly as supplied by the caller**.

Constructor guards are allowed only for:
- PHP type/container shape;
- list membership/type;
- fixed structural discriminators (for example `urlset` / `sitemapindex`);
- non-negative document byte count;
- the structural location-object shape defined below.

Candidate DTOs are **not** `JsonSerializable` in this remediation: they are input contracts, not output contracts.

**General targeting identity (FIX 18).** Evidence and diagnostic targets index into `SitemapValidationDocumentDTO::$entries` and its child lists; they never use URL strings, titles, or hashes as identity.

#### Robots.txt candidate input (FIX 3)

```php
final readonly class Maatify\Seo\Web\Validation\Input\RobotsTxtValidationInputDTO
{
    public function __construct(
        public string $content,
    );
}
```

Rules:
- `$content` may be empty.
- No `trim()`. Line endings remain exactly as supplied. Byte content remains exactly as supplied.
- `strlen($content)` is the document byte source for the Google 500 KiB diagnostic (`robots_google_document_size_exceeds_parse_limit`).
- The validators interpret lines/rules; the constructor performs no interpretation.
- `RobotsTxtDTO` is **not** used as validation input for the RFC/Google profiles; it remains the generation/domain DTO unchanged.

#### Robots meta candidate input (FIX 4)

```php
final readonly class Maatify\Seo\Web\Validation\Input\RobotsMetaValidationInputDTO
{
    /**
     * @param list<string> $directives
     */
    public function __construct(
        public array $directives,
    );
}
```

Construction guard:
- must be a list;
- every element is a string;
- no `trim()`, no normalization;
- empty strings are allowed as candidate input;
- the constructor does not interpret directives.

`MetaRobotsBuilder` remains unchanged. Implementations may later use an internal/private adapter from `MetaRobotsBuilder::toArray()` to candidate input, but **no public adapter/factory contract** is added in this audit.

#### Sitemap validation candidate model (FIX 5)

**A. `SitemapValidationLocationDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationLocationDTO
{
    public function __construct(
        public string $scheme,
        public string $host,
        public ?int $port,
        public string $path,
    );
}
```

Structural guards only:
- `scheme !== ''`;
- `host !== ''`;
- `port === null || port > 0`;
- `path` starts with `/`.

No Search Console, no ownership, no protocol-authority state.

**B. `SitemapValidationDocumentDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO
{
    /**
     * @param list<SitemapUrlValidationInputDTO>|list<SitemapIndexEntryValidationInputDTO> $entries
     */
    public function __construct(
        public string $type,
        public array $entries,
        public ?SitemapValidationLocationDTO $location = null,
        public ?int $uncompressedSizeBytes = null,
    );
}
```

`type` allowed only: `urlset` | `sitemapindex`.

- For `urlset`: `entries` is a homogeneous `list<SitemapUrlValidationInputDTO>`.
- For `sitemapindex`: `entries` is a homogeneous `list<SitemapIndexEntryValidationInputDTO>`.
- Empty list is allowed.
- Mixed candidate entry types is an invalid invocation shape (library invalid-argument exception family), not an SEO diagnostic.
- `uncompressedSizeBytes`: `null` allowed; otherwise `>= 0`.
- No protocol/provider validation inside the constructor.

**C. `SitemapUrlValidationInputDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Sitemap\SitemapUrlValidationInputDTO
{
    /**
     * @param list<SitemapImageValidationInputDTO> $images
     * @param list<SitemapVideoValidationInputDTO> $videos
     * @param list<SitemapNewsValidationInputDTO> $news
     */
    public function __construct(
        public ?string $loc = null,
        public ?string $lastmod = null,
        public ?string $changefreq = null,
        public int|float|null $priority = null,
        public array $images = [],
        public array $videos = [],
        public array $news = [],
    );
}
```

The constructor checks only that `images`, `videos`, and `news` are lists of the specified types. It does **not** reject:
- null/empty `loc`;
- malformed URL strings;
- malformed `lastmod`;
- unknown `changefreq`;
- out-of-protocol `priority` range.

Only the validators apply the machine contracts fixed in this audit.

**D. `SitemapIndexEntryValidationInputDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Sitemap\SitemapIndexEntryValidationInputDTO
{
    public function __construct(
        public ?string $loc = null,
        public ?string $lastmod = null,
    );
}
```

No URL/date validation in the constructor.

**E. `SitemapImageValidationInputDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Sitemap\SitemapImageValidationInputDTO
{
    public function __construct(
        public ?string $loc = null,
        public ?string $title = null,
        public ?string $caption = null,
        public ?string $geoLocation = null,
        public ?string $license = null,
    );
}
```

No semantic validation.

**F. `SitemapVideoValidationInputDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Sitemap\SitemapVideoValidationInputDTO
{
    public function __construct(
        public ?string $thumbnailLoc = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $contentLoc = null,
        public ?string $playerLoc = null,
        public ?int $duration = null,
        public ?string $publicationDate = null,
    );
}
```

This type **must** be able to represent:
- missing thumbnail;
- missing title;
- missing description;
- both content/player missing;
- negative/zero/out-of-range duration;
- malformed publication date;
- data URL;
- parent-equal media URL.

Therefore **none** of those validations belongs in the constructor.

**G. `SitemapNewsValidationInputDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Sitemap\SitemapNewsValidationInputDTO
{
    public function __construct(
        public ?string $publicationName = null,
        public ?string $publicationLanguage = null,
        public ?string $publicationDate = null,
        public ?string $title = null,
        public ?string $access = null,
        public ?string $genres = null,
        public ?string $keywords = null,
        public ?string $stockTickers = null,
    );
}
```

No required-field, date, language, or provider validation in the constructor.

#### Hreflang validation candidate model (FIX 6)

`HreflangLinkDTO` is strict and performs URL validation/normalization, so it cannot diagnose relative/non-qualified candidate URLs. The candidate model below is the validation input.

**`HreflangValidationLinkDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationLinkDTO
{
    public function __construct(
        public ?string $hreflang = null,
        public ?string $url = null,
    );
}
```

No normalization, no URL validation, no casing normalization.

**`HreflangValidationPageDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationPageDTO
{
    /**
     * @param list<HreflangValidationLinkDTO> $links
     */
    public function __construct(
        public string $pageUrl,
        public array $links,
    );
}
```

Structural rules:
- `pageUrl` is non-empty because it is the cluster identity;
- `links` is a list of `HreflangValidationLinkDTO`;
- no normalization of `pageUrl`;
- no provider validation.

**`HreflangValidationClusterDTO`**

```php
final readonly class Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationClusterDTO
{
    /**
     * @param list<HreflangValidationPageDTO> $pages
     */
    public function __construct(
        public array $pages,
    );
}
```

Structural rules:
- list only;
- every item is an `HreflangValidationPageDTO`;
- `pageUrl` identities must be unique exact strings within the cluster;
- a duplicate page identity is an **invalid invocation shape**, not an SEO diagnostic.

No crawling, no ISO membership lookup.

### Evidence input keys — fixed contract

Because `SeoValidationContextDTO::$evidence` is a public surface, its keys are fixed here and are not arbitrary. The table below is the complete normative list of evidence keys, their consuming profile validator, the diagnostics they feed, and the only allowed states. A state value outside the allowed set is a construction error for that key.

| Evidence key | Consumes | Feeds | Allowed states |
|---|---|---|---|
| `google_sitemap.lastmod_accuracy` | `GoogleSitemapValidator` | `google_sitemap_lastmod_accuracy` | `accurate` / `inaccurate` / `unknown` |
| `google_sitemap.host_verification` | `GoogleSitemapValidator` | `google_sitemap_host_context` | `verified_host` / `unverified_host` / `unknown` |
| `sitemaps.cross_submission_authority` | `SitemapProtocolValidator` | `sitemap_location_scope_violation` | `authorized` / `unauthorized` / `unknown` |
| `google_image.cross_domain_verification` | `GoogleImageSitemapValidator` | `google_image_cross_domain_verification` | `verified` / `unverified` / `unknown` |
| `google_image.crawlability` | `GoogleImageSitemapValidator` | `google_image_crawlability_context` | `accessible` / `inaccessible` / `unknown` |
| `google_video.relevance` | `GoogleVideoSitemapValidator` | `google_video_relevance_context` | `relevant` / `irrelevant` / `unknown` |
| `google_video.title_host_page_match` | `GoogleVideoSitemapValidator` | `google_video_title_host_page_match` | `matches` / `differs` / `unknown` |
| `google_video.description_host_page_match` | `GoogleVideoSitemapValidator` | `google_video_description_host_page_match` | `matches` / `differs` / `unknown` |
| `google_news.original_publication` | `GoogleNewsSitemapValidator` | `google_news_original_publication_evidence` | `original` / `not_original` / `unknown` |
| `google_news.publication_name_match` | `GoogleNewsSitemapValidator` | `google_news_name_exact_match_evidence` | `matched` / `mismatched` / `unknown` |
| `google_news.freshness` | `GoogleNewsSitemapValidator` | `google_news_freshness_evidence` | `within_window` / `outside_window` / `unknown` |
| `google_news.title_content_conformance` | `GoogleNewsSitemapValidator` | `google_news_title_content_evidence` | `conforming` / `nonconforming` / `unknown` |
| `robots_meta.unavailable_after_recognizability` | `GoogleRobotsMetaValidator` | `robots_meta_unavailable_after_recognizability` | `recognized` / `unrecognized` / `unknown` |

**`sitemaps.cross_submission_authority` — fixed (FIX 19).**

This is a new evidence key at the **protocol/sitemaps evidence boundary**. Shape:

```text
array<int, 'authorized'|'unauthorized'|'unknown'>
```

Indexed by the URL entry index inside a `urlset` candidate document. It is:
- consumed **only** by `SitemapProtocolValidator`;
- **not** Google Search Console evidence;
- **not** used for the Sitemap Index same-site rule;
- backed by **no** network lookup and **no** ownership inference.

Sitemap Index documents do **not** consume this evidence key at all (FIX 21).

**`google_news.title_content_conformance` — fixed.**

This is a new evidence key at the **provider/News content-evidence boundary**. Shape:

```text
array<int, array<int, 'conforming'|'nonconforming'|'unknown'>>
```

Target:

```text
[urlIndex][newsIndex]
```

It is:
- consumed **only** by `GoogleNewsSitemapValidator`;
- used **only** to feed the `google_news_title_content_evidence` diagnostic (severity mapping `conforming` → info, `nonconforming` → warning, `unknown` → info; origin `provider`, profile `google`, `field = title`, target `sitemap_news` + `[urlIndex][newsIndex]`);
- caller-supplied evidence covering the existing F-05 title-content requirement, including article-title correspondence and the documented exclusion semantics already stated in F-05 (title must not include author, publication name, or publication date);
- **not** inferable from the title string alone — no local inference is permitted;
- mapped to `unknown` when the evidence is missing;
- **not** emitted for an item whose title itself is missing (that item emits `google_news_title_missing` only).

**Evidence registry closure (fixed).** This key is the **only** new evidence key added by the contract completeness sweep. No evidence key is added for deferred Video remote facts (remote file type, thumbnail format/dimensions/stability/accessibility/transparency, Googlebot accessibility of referenced video resources, watch-page/video indexing eligibility), and no evidence key is added for robots MIME/HTTP transport context. The existing `sitemaps.cross_submission_authority` key remains unchanged.

**General targeting rule.**

Evidence associated with Sitemap child collections is indexed by the exact zero-based positions in the supplied candidate document entries. For nested child data:

```text
$urlIndex   = position in SitemapValidationDocumentDTO::$entries
$childIndex = position inside images/videos/news
```

URL strings, titles, object hashes, and invented IDs are **not** used as identity. This permits duplicate URLs/objects without ambiguity. These indices must match the `SeoDiagnosticTargetDTO` indices fixed below. For `urlset` candidate documents, `$urlIndex` is the position in `SitemapValidationDocumentDTO::$entries`; child indices are `$imageIndex`, `$videoIndex`, `$newsIndex`. Example: evidence `google_video.relevance[2][1] = irrelevant` produces a diagnostic with `target.scope = sitemap_video`, `target.entryIndex = 2`, `target.itemIndex = 1`.

**Normative evidence shapes — targeting and cardinality.**

- `google_sitemap.lastmod_accuracy` → `array<int, 'accurate'|'inaccurate'|'unknown'>` indexed by `$urlIndex`; `evidence[$urlIndex]` describes the candidate URL entry at that position in `SitemapValidationDocumentDTO::$entries`.
- `google_sitemap.host_verification` → document/site-level scalar `'verified_host'|'unverified_host'|'unknown'`; it is not per-URL evidence.
- `sitemaps.cross_submission_authority` → `array<int, 'authorized'|'unauthorized'|'unknown'>` indexed by `$urlIndex` in a `urlset` candidate document; consumed only by `SitemapProtocolValidator`.
- `google_image.cross_domain_verification` → `array<int, array<int, 'verified'|'unverified'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → image `$imageIndex`.
- `google_image.crawlability` → `array<int, array<int, 'accessible'|'inaccessible'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → image `$imageIndex`.
- `google_video.relevance` → `array<int, array<int, 'relevant'|'irrelevant'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → video `$videoIndex`.
- `google_video.title_host_page_match` → `array<int, array<int, 'matches'|'differs'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → video `$videoIndex`.
- `google_video.description_host_page_match` → `array<int, array<int, 'matches'|'differs'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → video `$videoIndex`.
- `google_news.original_publication` → `array<int, array<int, 'original'|'not_original'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → news `$newsIndex`.
- `google_news.publication_name_match` → `array<int, array<int, 'matched'|'mismatched'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → news `$newsIndex`.
- `google_news.freshness` → `array<int, array<int, 'within_window'|'outside_window'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → news `$newsIndex`.
- `google_news.title_content_conformance` → `array<int, array<int, 'conforming'|'nonconforming'|'unknown'>>` mapped to candidate URL entry `$urlIndex` → news `$newsIndex`.
- `robots_meta.unavailable_after_recognizability` → scalar `'recognized'|'unrecognized'|'unknown'`.

**Evidence consumption rules:**

- A profile validator reads only its own key from the table above.
- Missing evidence for a key maps to the `unknown` state **only** when the diagnostic's contract fixes an `unknown` state. A missing key is never converted into a warning or into any fabricated pass/fail.
- No arbitrary evidence state is accepted. A state not listed for the key is invalid.

**Missing / extra evidence rules:**

- For authorized indexed evidence maps: a missing `$urlIndex` maps to `unknown` where `unknown` exists.
- A missing child index maps to `unknown` where `unknown` exists.
- An index that does not correspond to an actual supplied URL/child creates no diagnostic.
- Non-integer or negative indices in an authorized indexed evidence map are invalid context input.
- An invalid state for an authorized key is invalid context input.
- Unknown top-level evidence keys remain ignored and may not create diagnostics or semantics.
- Absent evidence is never converted into a warning.

**Sitemap document state — sourced from the candidate document (FIX 7).**

There is **no** generic `documentContext` field and **no** `SeoValidationContextDTO::$documentContext`. Local deterministic Sitemap document state is carried by `SitemapValidationDocumentDTO` itself, which is the correct and only home for it:

- **Document location** → `SitemapValidationDocumentDTO::$location` (a `SitemapValidationLocationDTO`, or `null`). Consumed only by `SitemapProtocolValidator` for `sitemap_location_scope_violation`. No Search Console data and no ownership claim belong here.
- **Document type** → `SitemapValidationDocumentDTO::$type` (`'urlset'` or `'sitemapindex'`). A contradiction between candidate entry element types and `$type` is invalid invocation/context, not an SEO diagnostic.
- **Document byte size** → `SitemapValidationDocumentDTO::$uncompressedSizeBytes` (`int >= 0` or `null`). Feeds `sitemap_document_size_exceeds_boundary`. It represents the actual uncompressed serialized document byte count supplied by the caller/serialization layer. It is **not** estimated from entry count and not derived from `$type`.
- **Video parent page loc** → the parent candidate `SitemapUrlValidationInputDTO::$loc` at `$document->entries[$urlIndex]`, never a context field.
- **Hreflang cluster** → `HreflangValidationClusterDTO`, the primary input to `GoogleHreflangClusterValidator`, never a context field.
- **Generic document count** → URL/Index/News/Image counts are derived directly from the supplied candidate document arrays.

All references to `documentContext['sitemap.document_location']`, `documentContext['sitemap.document_type']`, `documentContext['sitemap.uncompressed_size_bytes']`, parent loc inside `documentContext`, and hreflang cluster inside `documentContext` are withdrawn.

**Context construction guards — fixed.**

`SeoValidationContextDTO` constructor validation is fixed:

- For recognized `$evidence` keys: enforce the exact value shape (scalar / indexed / nested) and the allowed states defined above.
- Negative and non-integer list indices are invalid for indexed evidence.
- An invalid recognized evidence value (shape or state) is a construction error.
- Unknown top-level keys are ignored by validators: they must not create diagnostics and must not introduce semantics.
- No extension registry is invented.

### Diagnostic target locator — fixed contract (FIX 13)

`SeoCompanionDiagnosticDTO` alone cannot state machine-readably which URL/Image/Video/News item a child-level diagnostic concerns. The public DTO below is added and is required on every companion entry.

```php
final readonly class Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO implements \JsonSerializable
{
    public function __construct(
        public string $scope,
        public ?int $entryIndex = null,
        public ?int $itemIndex = null,
        public ?int $line = null,
    );
}
```

Fixed JSON shape (snake_case keys):

```json
{
  "scope": "sitemap_video",
  "entry_index": 0,
  "item_index": 1,
  "line": null
}
```

### Allowed target scopes — closed set (FIX 14)

The only allowed `scope` values are:

- `robots_document`
- `robots_rule`
- `robots_meta`
- `sitemap_document`
- `sitemap_url`
- `sitemap_index_entry`
- `sitemap_image`
- `sitemap_video`
- `sitemap_news`
- `meta`
- `canonical`
- `hreflang_page`
- `hreflang_link`

There is **no** generic arbitrary scope registry.

### Target shape guards — fixed (FIX 15)

- `robots_document` — `entryIndex` null, `itemIndex` null, `line` null.
- `robots_rule` — `line` **required**, 1-based source line number; `entryIndex` null; `itemIndex` null.
- `robots_meta` — all indexes/line null.
- `sitemap_document` — all indexes/line null.
- `sitemap_url` — `entryIndex` required, zero-based; `itemIndex` null; `line` null.
- `sitemap_index_entry` — same as `sitemap_url` (`entryIndex` = child index, zero-based).
- `sitemap_image` — `entryIndex` = URL index, `itemIndex` = image index, both zero-based; `line` null.
- `sitemap_video` — `entryIndex` = URL index, `itemIndex` = video index, both zero-based; `line` null.
- `sitemap_news` — `entryIndex` = URL index, `itemIndex` = news index, both zero-based; `line` null.
- `meta` — indexes/line null.
- `canonical` — indexes/line null.
- `hreflang_page` — `entryIndex` = page index; `itemIndex` null; `line` null.
- `hreflang_link` — `entryIndex` = page index, `itemIndex` = link index; `line` null.

Any invalid target shape is a construction error. Negative indices are invalid. `line < 1` is invalid.

### Normative target mapping — fixed (FIX 17)

**Robots:**
- `robots_google_document_size_exceeds_parse_limit` → `robots_document`.
- `robots_google_document_invalid_utf8` → `robots_document`.
- Path/RFC/Google robots.txt rule diagnostics → `robots_rule` + exact 1-based source line.
- `robots_rfc9309_product_token_invalid` → `robots_rule` + exact 1-based source line.
- `robots_rfc9309_path_pattern_invalid` → `robots_rule` + exact 1-based source line.
- `robots_rfc9309_control_character_invalid` → `robots_rule` + exact 1-based source line.
- `robots_google_sitemap_url_not_fully_qualified` → `robots_rule` + exact 1-based source line.
- Robots meta diagnostics → `robots_meta`.

**Sitemap protocol:**
- Document count/size: `sitemap_url_count_exceeds_limit`, `sitemap_index_count_exceeds_limit`, `sitemap_document_size_exceeds_boundary` → `sitemap_document`.
- URL-entry lexical/location diagnostics → `sitemap_url` + URL `entryIndex`.
- `sitemap_loc_missing` and `sitemap_loc_invalid_uri_iri` → `sitemap_url` + URL `entryIndex` when the entry is a URL Sitemap entry (`field = loc`), and `sitemap_index_entry` + `entryIndex` when the entry is a Sitemap Index child (`field = sitemap`).
- `sitemap_changefreq_invalid` and `sitemap_priority_out_of_range` → `sitemap_url` + URL `entryIndex`.
- Sitemap Index child location/lastmod diagnostics → `sitemap_index_entry` + `entryIndex`.
- `sitemap_lastmod_invalid_lexical` → `sitemap_url` + `entryIndex` for a URL Sitemap, `sitemap_index_entry` + `entryIndex` for a Sitemap Index.
- For `sitemap_location_scope_violation`: URL-sitemap URL violation → `sitemap_url`; Sitemap Index child violation → `sitemap_index_entry`.

**Google base Sitemap:**
- Per-URL: `priority`, `changefreq`, `lastmod` accuracy → `sitemap_url` + `entryIndex`.
- `google_sitemap_host_context` → `sitemap_document`.

**Google Image:**
- `google_image_count_exceeds_limit` → parent `sitemap_url` + URL `entryIndex`.
- Per-image evidence diagnostic → `sitemap_image` + `[urlIndex][imageIndex]`.

**Google Video:**
- Every child-video diagnostic → `sitemap_video` + `[urlIndex][videoIndex]`, including missing fields, URL-shape diagnostics (`google_video_thumbnail_loc_invalid_url`, `google_video_content_loc_invalid_url`, `google_video_player_loc_invalid_url`), duration, publication date, parent-loc equality, description length, data URL (including both Data-URL diagnostics sharing the same code with `field = content_loc` and `field = player_loc`), relevance, title match, description match, and `content_loc` preference.

**Google News:**
- Per-News-entry diagnostics/evidence → `sitemap_news` + `[urlIndex][newsIndex]`, including the four required-field missing diagnostics (`google_news_publication_name_missing`, `google_news_language_missing`, `google_news_publication_date_missing`, `google_news_title_missing`) and the title-content evidence diagnostic `google_news_title_content_evidence`.
- `google_news_multiple_entries_per_url` → `sitemap_url` + URL index.
- `google_news_document_count_exceeds_limit` → `sitemap_document`.

**Meta / OGP / legacy classifications:**
- → `meta`.

**Canonical:**
- → `canonical`.

**Hreflang:**
- Cluster page-level (self reference, reciprocity, alternate-set consistency) → `hreflang_page` + page index.
- Specific alternate URL not fully qualified → `hreflang_link` + page index + link index.
- `hreflang_tag_invalid_syntax` → `hreflang_link` + page index + link index.

**Evidence targeting uses candidate document indices (FIX 18).** The zero-based evidence indexing binds to `SitemapValidationDocumentDTO::$entries` (not any strict `SitemapUrlDTO` list): `$urlIndex = position in SitemapValidationDocumentDTO::$entries`, and child indices `$imageIndex` / `$videoIndex` / `$newsIndex` must match `SeoDiagnosticTargetDTO`. No URL strings or titles are used as identity.

### Cross-submission authority evidence — exact logic (FIX 19–21)

These rules are fixed and must not be left to implementation interpretation.

**Protocol cross-submission authority evidence (FIX 19):** `sitemaps.cross_submission_authority` is the evidence key at the **protocol/sitemaps evidence boundary** (`array<int, 'authorized'|'unauthorized'|'unknown'>`), indexed by URL entry index inside a `urlset` candidate document. It is consumed only by `SitemapProtocolValidator`; it is **not** Search Console evidence; it is **not** used for the Sitemap Index same-site rule; there is no network lookup and no ownership inference.

**Exact logic (FIX 20), for a URL Sitemap:**
- If a candidate URL is within the normal protocol scope inferred from `SitemapValidationDocumentDTO::$location` → no scope violation.
- If a candidate URL is outside the normal scope, consult `sitemaps.cross_submission_authority[$urlIndex]`:
  - `authorized` → no `sitemap_location_scope_violation` emitted; supplied authority evidence permits the specific cross-submission state described in F-02.
  - `unauthorized` → emit `sitemap_location_scope_violation` (`error`, origin `protocol`, profile `sitemaps`, `field = loc`, target `sitemap_url` + URL index).
  - `unknown` or missing → **no protocol error emitted.** This is an EVIDENCE BOUNDARY: absence of authority proof is not converted into a fabricated failure, and it is not described as a proven pass either. No new diagnostic is added for the `unknown` state in this remediation.

**Sitemap Index rule (FIX 21):** For `type = sitemapindex`, the `sitemaps.cross_submission_authority` evidence is **not applied**. The Sitemap Index same-site restriction remains as fixed: a Sitemap Index child location is checked directly against document location + candidate child location; a proven deterministic violation emits `sitemap_location_scope_violation` with `field = sitemap` and target `sitemap_index_entry` + child index. No Search Console, no `sitemaps.cross_submission_authority`, no Google host verification.

**Google host verification stays separate (FIX 22):** `google_sitemap.host_verification` (states `verified_host` / `unverified_host` / `unknown`) is consumed **only** by `GoogleSitemapValidator`, feeds `google_sitemap_host_context` (origin `provider`, profile `google`, target `sitemap_document`), and is **never** used in `sitemap_location_scope_violation` or in protocol cross-submission authority.

### Fixed machine contracts for all new diagnostics

**Complete Normative Runtime Registry (fixed).** The normative runtime machine registry for Stacks 2/4/5/6 consists **only** of:

- all GDC-01 machine table rows (including every code added by the contract completeness sweep below and above);
- the fixed F-13 OGP new diagnostics (`missing_og_type`, `missing_og_url`);
- the fixed Normative Legacy Classification Table.

Everything else is exactly one of:

- strict invocation/constructor behavior explicitly documented;
- serialization/output guarantee;
- parser semantics;
- explicitly deferred / no-runtime-diagnostic scope.

**No prose rule anywhere else in this Audit implicitly authorizes a new runtime diagnostic.** A rule that is not a machine-table row, an F-13 OGP diagnostic, a legacy classification record, a documented invocation/constructor rule, a serialization/output guarantee, or parser semantics is explicitly deferred / no-runtime-diagnostic scope until a separately approved contract defines otherwise.

**Diagnostic precedence / anti-cascade rule (fixed).** When a field is missing and has a dedicated missing diagnostic, the missing diagnostic is emitted and no invalid lexical/URL diagnostic is also emitted for the same absence. When the field is present but malformed, the malformed/invalid diagnostic is emitted instead. Evidence diagnostics that require a substantive field are not emitted when that required field is missing. Fixed examples:

- Video thumbnail missing → `google_video_thumbnail_loc_missing`, **not** also `google_video_thumbnail_loc_invalid_url`.
- News publication date missing → `google_news_publication_date_missing`, **not** also `google_news_publication_date_invalid`.
- News language missing → `google_news_language_missing`, **not** also `google_news_language_invalid`.
- Sitemap `loc` missing → `sitemap_loc_missing`, **not** also `sitemap_loc_invalid_uri_iri`.

This rule is a hard anti-cascade constraint; the same absence must never yield both a missing diagnostic and a malformed diagnostic of the same field.

**Reachability contract (FIX 23).** Every diagnostic required from the RFC robots, Google robots.txt, Sitemap protocol, Google Video, Google News, and Google Hreflang profiles whose malformed state is rejected by a strict DTO constructor is reachable because the profiles consume the validation candidate input layer — without weakening any strict DTO. Explicitly reachable-in-this-layer examples (no new codes; each maps to its machine code below): malformed Sitemap `lastmod` → `sitemap_lastmod_invalid_lexical`; missing Sitemap `loc` → `sitemap_loc_missing`; malformed non-empty Sitemap `loc` → `sitemap_loc_invalid_uri_iri`; invalid `changefreq` → `sitemap_changefreq_invalid`; out-of-range/non-finite `priority` → `sitemap_priority_out_of_range`; Video missing title/description/thumbnail and both media locations missing → `google_video_title_missing` / `google_video_description_missing` / `google_video_thumbnail_loc_missing` / `google_video_content_or_player_loc_missing`; Video non-empty-but-malformed thumbnail/content/player URL → `google_video_thumbnail_loc_invalid_url` / `google_video_content_loc_invalid_url` / `google_video_player_loc_invalid_url`; invalid Video publication date → `google_video_publication_date_invalid`; out-of-range Video duration → `google_video_duration_out_of_range`; News missing required fields → `google_news_publication_name_missing` / `google_news_language_missing` / `google_news_publication_date_missing` / `google_news_title_missing`; News invalid publication date/language → `google_news_publication_date_invalid` / `google_news_language_invalid`; relative/missing/malformed Hreflang URL → `hreflang_url_not_fully_qualified`; malformed Hreflang tag syntax → `hreflang_tag_invalid_syntax`; raw robots path cases → `robots_google_present_path_leading_slash` / `robots_rfc9309_leading_wildcard_compatibility`; RFC product-token/path-pattern/control-character cases → `robots_rfc9309_product_token_invalid` / `robots_rfc9309_path_pattern_invalid` / `robots_rfc9309_control_character_invalid`; Google Sitemap directive URL → `robots_google_sitemap_url_not_fully_qualified`. None of these requires changing a strict constructor.

Every stable `code`, `severity`, `origin`, `profile`, `field`, and `evidence_state` below is part of GDC-01. A diagnostic absent from these tables and from the fixed F-13 OGP contracts may not be introduced by an implementation stack. All rows are GDC-01 companion-only: none enters the legacy result, `is_valid`, `has_warnings`, or `SeoValidationScoreCalculator`. Severity convention: `error` is reserved for provable protocol-invalid conditions (Stack 4 protocol rules); provider contract violations and conservative library-policy boundaries are `warning`; status/recommendation/evidence-gap entries are `info`. For ordinary diagnostics, severity is fixed per code; for evidence-state diagnostics, severity is fixed by the normative `(code, evidence_state) → severity` mapping in the table (for example `inaccurate` → warning, `accurate` → info). The pre-existing legacy missing-OGP warnings keep their fixed legacy severity as documented in F-13.

#### Stack 2 — Google robots.txt (provider profile `google`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `robots_google_document_size_exceeds_parse_limit` | warning | provider | `google` | `document` | null | Document exceeds 512,000 bytes (500 KiB); Google ignores content after this limit (F-06). Not RFC invalidity; companion-only, not legacy result. |
| `robots_google_document_invalid_utf8` | warning | provider | `google` | `document` | null | Raw `RobotsTxtValidationInputDTO::$content` is not valid UTF-8 (F-06 Google UTF-8/plain-text contract). Independent of the 500 KiB diagnostic; both may coexist for the same document. Companion-only, not legacy result. |
| `robots_google_present_path_leading_slash` | warning | provider | `google` | `path` | null | Google Allow/Disallow present-path leading-`/` rule (F-06): a non-empty path that does not begin with `/` is a provider-profile warning; companion-only, not legacy result. |
| `robots_google_sitemap_url_not_fully_qualified` | warning | provider | `google` | `sitemap` | null | A parsed `Sitemap:` value is present but fails the already-fixed Google fully-qualified URL semantics (F-06): absolute URL including protocol and host required. Raw Unicode/non-URL-encoded paths are **not** rejected merely because `FILTER_VALIDATE_URL` rejects them; cross-host Sitemap URLs and multiple Sitemap directives remain allowed with no count-limit diagnostic. `FILTER_VALIDATE_URL` is never the Google-profile contract for this field. Target `robots_rule` + exact 1-based source line. Companion-only, not legacy result. |

#### Stack 2 — RFC 9309 robots (protocol profile `rfc9309`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `robots_rfc9309_leading_wildcard_compatibility` | warning | protocol | `rfc9309` | `path` | null | Leading-`*` on a non-empty path is a non-fatal RFC compatibility diagnostic (F-06); companion-only, not legacy result. |
| `robots_rfc9309_product_token_invalid` | error | protocol | `rfc9309` | `user_agent` | null | Parsed `User-agent` product-token is neither `*` nor the fixed RFC 9309 identifier grammar already recorded in F-06 (for example an identifier containing a digit). Not an invocation exception: the raw candidate document is valid validator input and the validator diagnoses the protocol-invalid token. Target `robots_rule` + exact 1-based source line. Companion-only, not legacy result. |
| `robots_rfc9309_path_pattern_invalid` | error | protocol | `rfc9309` | `path` | null | Non-empty `path-pattern` satisfies neither the accepted `/`-started form nor the special leading-`*` compatibility case (F-06). Empty Allow/Disallow patterns are valid and emit no diagnostic; ordinary non-empty paths starting `/` emit no path-start diagnostic; a non-empty path starting `*` emits only `robots_rfc9309_leading_wildcard_compatibility` and not this code. The F-06 leading-`*` compatibility policy remains untouched. Target `robots_rule` + exact 1-based source line. Companion-only, not legacy result. |
| `robots_rfc9309_control_character_invalid` | error | protocol | `rfc9309` | `user_agent` or `path` | null | A forbidden non-line control character appears inside a parsed semantic token/value. Normal CRLF/LF document line separators are **not** diagnostics; only a forbidden control character inside a parsed semantic value emits this. `field` is `user_agent` or `path` according to the parsed rule; no arbitrary field strings. Target `robots_rule` + exact 1-based source line. Companion-only, not legacy result. |

#### Stack 2 — Google robots meta (provider profile `google`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `robots_meta_indexifembedded_without_noindex` | warning | provider | `google` | `meta` | null | `indexifembedded` present without `noindex` (F-09); companion-only, not legacy result. |
| `robots_meta_unavailable_after_missing` | warning | provider | `google` | `meta` | null | `unavailable_after` is empty or whitespace-only (F-10); companion-only, not legacy result. |
| `robots_meta_unavailable_after_recognizability` | warning / info | provider | `google` | `meta` | `recognized` / `unrecognized` / `unknown` | Caller-supplied recognizability evidence for non-empty `unavailable_after` (F-10); `recognized` → info, `unrecognized` → warning, `unknown` → info (evidence gap); companion-only, not legacy result. |

#### Stack 4 — Sitemap core (protocol rules profile `sitemaps`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `sitemap_url_count_exceeds_limit` | error | protocol | `sitemaps` | `urlset` | — | URL sitemap exceeds 50,000 URLs (sitemaps.org source-backed limit). |
| `sitemap_index_count_exceeds_limit` | error | protocol | `sitemaps` | `sitemapindex` | — | Sitemap Index exceeds 50,000 sitemap entries (sitemaps.org source-backed limit). |
| `sitemap_document_size_exceeds_boundary` | error | protocol | `sitemaps` | `null` | — | Uncompressed document exceeds 52,428,800 bytes (50 MB), a byte-defined sitemaps.org limit. Document-level rule applies to both URL sitemap and Sitemap Index; `field` is `null`. |
| `sitemap_loc_missing` | error | protocol | `sitemaps` | `loc` (URL) / `sitemap` (Index child) | null | Entry-level missing-`loc` rule: emit only when `loc === null || trim(loc) === ''`. For a URL Sitemap entry: `field = loc`, target `sitemap_url` + URL entry index. For a Sitemap Index entry: `field = sitemap`, target `sitemap_index_entry` + child index. Missing `loc` emits this diagnostic only and never also `sitemap_loc_invalid_uri_iri`. Companion-only, not legacy result. |
| `sitemap_loc_invalid_uri_iri` | error | protocol | `sitemaps` | `loc` (URL) / `sitemap` (Index child) | null | A non-empty supplied `loc` fails the Sitemap URL/URI/IRI lexical contract already fixed by F-02 (malformed URL shape; malformed URI/IRI lexical form relevant to Sitemap location validation). Same field/target mapping as `sitemap_loc_missing`. XML escaping failure is **not** part of this diagnostic: XML escaping remains serialization/output behavior. Emitted only when `loc` is non-empty; missing/empty `loc` uses `sitemap_loc_missing`. Companion-only, not legacy result. |
| `sitemap_lastmod_invalid_lexical` | error | protocol | `sitemaps` | `lastmod` | — | `lastmod` fails the fixed F-02 lexical forms (including year-only, year-month, hour/minute-only, zone-less dateTime). Missing/null `lastmod` is a valid absence and emits no diagnostic. For a URL Sitemap: target `sitemap_url` + `entryIndex`. For a Sitemap Index: target `sitemap_index_entry` + `entryIndex`. Target mapping is fixed per document type. |
| `sitemap_changefreq_invalid` | error | protocol | `sitemaps` | `changefreq` | null | Emitted only when a non-null/non-empty candidate `changefreq` is outside the fixed Sitemap vocabulary already represented by the current strict DTO contract. Null means absent and emits no diagnostic. Target `sitemap_url` + `entryIndex`. Companion-only, not legacy result. |
| `sitemap_priority_out_of_range` | error | protocol | `sitemaps` | `priority` | null | Emitted when a non-null `priority` is non-finite, `< 0.0`, or `> 1.0`. Null means absent. Target `sitemap_url` + `entryIndex`. Companion-only, not legacy result. |

**Sitemap serialization rules are NOT candidate diagnostics (fixed).** UTF-8 XML serialization, XML entity escaping, and canonical percent/URI output escaping are **serialization/output guarantees**, not companion diagnostics in Stack 4. Stack 3 and the serialization tests prove them; the candidate validation layer does **not** re-check XML serialized output. No codes such as `sitemap_xml_not_utf8` or `sitemap_xml_not_escaped` (or any similar code) are introduced by this Audit.
| `sitemap_loc_length_exceeds_measure_boundary` | warning | protocol | `sitemaps` | `loc` | — | EVIDENCE BOUNDARY + conservative library policy (F-02 exact measurement contract): byte measure regards a value ≥ 2,048 bytes as exceed-the-library-boundary. **Not** labeled a proven violation of the source's unit-undefined character limit. |
| `sitemap_location_scope_violation` | error | protocol | `sitemaps` | `loc` (or `sitemap` for Sitemap Index child location) | — | EVIDENCE BOUNDARY + deterministic caller-supplied context: the caller-supplied document/location context (`SitemapValidationDocumentDTO::$location`) proves deterministically that a URL violates the applicable Sitemaps.org location/scope protocol (scheme, host, port, path scope). When the violation concerns a Sitemap Index child sitemap location rather than a page `<loc>`, `field = sitemap`. Cross-submission uses only `sitemaps.cross_submission_authority` (FIX 19–21): `authorized` → no error; `unauthorized` → error; `unknown`/missing → EVIDENCE BOUNDARY, no diagnostic. Sitemap Index never consumes cross-submission authority. No network, no Search Console, no guessed ownership. Targets: `sitemap_url` + URL index when `field = loc`, `sitemap_index_entry` + child index when `field = sitemap`. |

#### Stack 4 — Google base Sitemap (provider profile `google`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `google_sitemap_priority_ignored` | info | provider | `google` | `priority` | — | Google ignores `priority`; protocol-valid field, not a validity failure. |
| `google_sitemap_changefreq_ignored` | info | provider | `google` | `changefreq` | — | Google ignores `changefreq`; protocol-valid field, not a validity failure. |
| `google_sitemap_lastmod_accuracy` | warning / info | provider | `google` | `lastmod` | `accurate` / `inaccurate` / `unknown` | Caller-supplied evidence only; `inaccurate` → warning, `accurate` → info, `unknown` → info (evidence gap). |
| `google_sitemap_host_context` | warning / info | provider | `google` | `null` | `verified_host` / `unverified_host` / `unknown` | Caller-supplied Search Console verification evidence; `unverified_host` → warning, `verified_host` → info, `unknown` → info (evidence gap). |

#### Stack 4 — Google Image (provider profile `google`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `google_image_count_exceeds_limit` | warning | provider | `google` | `image` | — | Exceeds 1,000 `image:image` entries per URL (Google provider limit). |
| `google_image_cross_domain_verification` | warning / info | provider | `google` | `image` | `verified` / `unverified` / `unknown` | Cross-domain hosting requires Search Console verification (F-03); `unverified` → warning, `verified` → info, `unknown` → info (evidence gap). |
| `google_image_crawlability_context` | warning / info | provider | `google` | `image` | `accessible` / `inaccessible` / `unknown` | External crawlability evidence (F-03); `inaccessible` → warning, `accessible` → info, `unknown` → info (evidence gap). |

**Google Image current machine scope — fixed boundary.** This sweep adds **no** Google Image `loc`/`title`/`caption`/`geoLocation`/`license`/URL-shape diagnostics. Although `SitemapImageValidationInputDTO` can represent null/malformed fields, the current F-03 approved machine scope authorizes only: the image count diagnostic, the cross-domain verification evidence diagnostic, the crawlability evidence diagnostic, and the legacy-field compatibility classification. Candidate representability does **not** itself authorize a diagnostic: `GoogleImageSitemapValidator` emits no new missing/URL-shape diagnostic beyond the already-approved GDC-01 table above, and no implementer may invent one. Any broader Image required-field/URL-shape provider contract requires a separate audit amendment.

#### Stack 4 — Google Video (provider profile `google`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `google_video_title_missing` | warning | provider | `google` | `title` | — | Required per Google Video contract (F-04). `missing := value === null || trim(value) === ''`. Target `sitemap_video` + `[urlIndex][videoIndex]`. |
| `google_video_description_missing` | warning | provider | `google` | `description` | — | Required per Google Video contract (F-04). `missing := value === null || trim(value) === ''`. Target `sitemap_video` + `[urlIndex][videoIndex]`. |
| `google_video_thumbnail_loc_missing` | warning | provider | `google` | `thumbnail_loc` | — | Required per Google Video contract (F-04). `missing := value === null || trim(value) === ''`. Missing/whitespace-only uses this code and never also `google_video_thumbnail_loc_invalid_url`. Target `sitemap_video` + `[urlIndex][videoIndex]`. |
| `google_video_thumbnail_loc_invalid_url` | warning | provider | `google` | `thumbnail_loc` | — | `thumbnailLoc` is non-empty but its URL shape is invalid (F-04 deterministic URL shape). Missing/whitespace-only uses `google_video_thumbnail_loc_missing` instead; the two never emit together for the same item. Target `sitemap_video` + `[urlIndex][videoIndex]`. |
| `google_video_content_loc_invalid_url` | warning | provider | `google` | `content_loc` | — | `contentLoc` is present/non-empty and its URL shape is invalid (F-04 deterministic URL shape). Target `sitemap_video` + `[urlIndex][videoIndex]`. |
| `google_video_player_loc_invalid_url` | warning | provider | `google` | `player_loc` | — | `playerLoc` is present/non-empty and its URL shape is invalid (F-04 deterministic URL shape). Target `sitemap_video` + `[urlIndex][videoIndex]`. |
| `google_video_content_or_player_loc_missing` | warning | provider | `google` | `content_loc` | — | At least one of `content_loc`/`player_loc` required (F-04). Emits **only when both** `contentLoc === null || trim(contentLoc) === ''` **and** `playerLoc === null || trim(playerLoc) === ''`. If exactly one media location is present but malformed, its URL-shape diagnostic (`google_video_content_loc_invalid_url` / `google_video_player_loc_invalid_url`) is used instead, not this code. Target `sitemap_video` + `[urlIndex][videoIndex]`. |
| `google_video_description_length_exceeds_measure_boundary` | warning | provider | `google` | `description` | — | EVIDENCE BOUNDARY + conservative library policy (F-02): byte measure regards a value > 2,048 bytes as exceed-the-library-boundary. **Not** labeled a proven violation of the source's unit-undefined character maximum. |
| `google_video_duration_out_of_range` | warning | provider | `google` | `duration` | — | Outside 1..28,800 seconds (F-04). |
| `google_video_publication_date_invalid` | warning | provider | `google` | `publication_date` | — | Fails the two documented date forms (F-04). Emitted only when `publicationDate` is non-null/non-empty (absent value emits no diagnostic). |
| `google_video_media_loc_equals_parent_loc` | warning | provider | `google` | `content_loc` or `player_loc` | — | `content_loc` or `player_loc` equals the parent page `<loc>` (F-04). Uses the same code but separate diagnostic entries per violated field: `field = content_loc` when content_loc is the violator, `field = player_loc` when player_loc is the violator; both may emit independently if both are present. |
| `google_video_data_url_unsupported` | warning | provider | `google` | `content_loc` OR `player_loc` | — | Data URLs are unsupported for video URLs (F-04). A single machine contract covers either offending media field: `field = content_loc` when `contentLoc` is a Data URL, `field = player_loc` when `playerLoc` is a Data URL. If **both** locations are Data URLs, emit **two** diagnostics with the same code and same target but `field = content_loc` and `field = player_loc` respectively. No separate codes are created for content versus player Data URLs. Target `sitemap_video` + `[urlIndex][videoIndex]`. |
| `google_video_relevance_context` | warning / info | provider | `google` | `null` | `relevant` / `irrelevant` / `unknown` | Do-not-list-unrelated-video requirement (F-04); `irrelevant` → warning, `relevant` → info, `unknown` → info (evidence gap). |
| `google_video_title_host_page_match` | warning / info | provider | `google` | `title` | `matches` / `differs` / `unknown` | Title should match host page (recommendation, F-04); `differs` → warning, `matches` → info, `unknown` → info (evidence gap). |
| `google_video_description_host_page_match` | warning / info | provider | `google` | `description` | `matches` / `differs` / `unknown` | Description must match description displayed on host page (F-04); `differs` → warning, `matches` → info, `unknown` → info (evidence gap). Separate from `google_video_relevance_context` which concerns video-to-page topical relevance. |
| `google_video_content_loc_preference` | info | provider | `google` | `content_loc` | — | `content_loc` preferred when available (recommendation, F-04). |

**Video remote-evidence boundary — no additional runtime diagnostic in Stacks 0–8 for:** actual remote video file type; actual thumbnail file format; actual thumbnail dimensions; actual thumbnail stability; actual thumbnail accessibility; actual thumbnail transparency; actual Googlebot accessibility of referenced video resources; watch-page/video indexing eligibility. These facts are explicitly **`DEFER PROVIDER EVIDENCE CONTRACT`** for a later approved contract; no implementer may invent evidence keys or codes for them in Stack 4.

**Video scheme handling — closed outcome.** The current remediation supports only the deterministic rules already fixed: the ordinary URL-shape diagnostics above (`google_video_thumbnail_loc_invalid_url`, `google_video_content_loc_invalid_url`, `google_video_player_loc_invalid_url`) and the explicit Data URL diagnostic (`google_video_data_url_unsupported`). No generic `unsupported_video_scheme` diagnostic is introduced and no closed streaming-protocol taxonomy is invented. The HTTP/HTTPS/FTP/streaming-protocol source evidence recorded in F-04 remains **documentation evidence only** until a separately approved closed machine contract exists. This is an explicit scope decision, not implementer discretion.

#### Stack 4 — Google News (provider profile `google`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `google_news_multiple_entries_per_url` | warning | provider | `google` | `news` | — | More than one `<news:news>` under a single URL (F-05 provider cardinality). |
| `google_news_document_count_exceeds_limit` | warning | provider | `google` | `news` | — | More than 1,000 `<news:news>` entries in one document (F-05 provider limit). |
| `google_news_publication_name_missing` | warning | provider | `google` | `name` | null | Required field missing (F-05). `missing := value === null || trim(value) === ''`. Target `sitemap_news` + `[urlIndex][newsIndex]`. |
| `google_news_language_missing` | warning | provider | `google` | `language` | null | Required field missing (F-05). `missing := value === null || trim(value) === ''`. Target `sitemap_news` + `[urlIndex][newsIndex]`. |
| `google_news_publication_date_missing` | warning | provider | `google` | `publication_date` | null | Required field missing (F-05). `missing := value === null || trim(value) === ''`. Target `sitemap_news` + `[urlIndex][newsIndex]`. |
| `google_news_title_missing` | warning | provider | `google` | `title` | null | Required field missing (F-05). `missing := value === null || trim(value) === ''`. Target `sitemap_news` + `[urlIndex][newsIndex]`. |
| `google_news_publication_date_invalid` | warning | provider | `google` | `publication_date` | — | Fails the four documented date forms (F-05). Emitted **only when** `publicationDate` is present/non-empty but its lexical form is invalid; missing `publicationDate` emits only `google_news_publication_date_missing` and never this code. |
| `google_news_language_invalid` | warning | provider | `google` | `language` | — | Fails the 2/3-letter ISO 639 lexical form including the `zh-cn`/`zh-tw` exceptions (F-05). Emitted **only when** `language` is present/non-empty but invalid; missing `language` emits only `google_news_language_missing` and never this code. |
| `google_news_publication_name_parenthetical` | warning | provider | `google` | `name` | — | Parenthetical content present in `news:name` (locally decidable, F-05). |
| `google_news_title_content_evidence` | warning / info | provider | `google` | `title` | `conforming` / `nonconforming` / `unknown` | Caller-supplied evidence covering the existing F-05 title-content requirement, including article-title correspondence and the documented exclusion semantics already stated in F-05 (`title` must not include the author name, publication name, or publication date). **No local inference from the title string alone.** `missing`/absent evidence maps to `unknown`; severity: `conforming` → info, `nonconforming` → warning, `unknown` → info (evidence gap). Missing title emits `google_news_title_missing` and no title-content evidence diagnostic for that item. Target `sitemap_news` + `[urlIndex][newsIndex]`. |
| `google_news_original_publication_evidence` | warning / info | provider | `google` | `publication_date` | `original` / `not_original` / `unknown` | Publication date claims original first-publication time (F-05); `not_original` → warning, `original` → info, `unknown` → info (evidence gap). |
| `google_news_name_exact_match_evidence` | warning / info | provider | `google` | `name` | `matched` / `mismatched` / `unknown` | Exact Google News publication-name identity (F-05); `mismatched` → warning, `matched` → info, `unknown` → info (evidence gap). |
| `google_news_freshness_evidence` | warning / info | provider | `google` | `null` | `within_window` / `outside_window` / `unknown` | "Last two days" rule (F-05); `outside_window` → warning, `within_window` → info, `unknown` → info (evidence gap); no local clock arithmetic. |

**News missing-vs-invalid precedence (fixed).** For a required News field with a dedicated missing diagnostic, missing and invalid are mutually exclusive outcomes for the same absent/present state:

- `publicationDate` missing → **only** `google_news_publication_date_missing`; do **not** also emit `google_news_publication_date_invalid`.
- `publicationDate` present but lexical form invalid → **only** `google_news_publication_date_invalid`.
- `language` missing → **only** `google_news_language_missing`; do **not** also emit `google_news_language_invalid`.
- `language` present but invalid → **only** `google_news_language_invalid`.

**Legacy Google News fields (fixed).** `access`, `genres`, `keywords`, and `stockTickers` remain compatibility fields. Their status in this remediation is **documentation/provider-status classification only**: no runtime diagnostic, no removal, no deprecation implementation, and no candidate validation failure. Any future runtime provider diagnostic for these fields requires a separately approved contract.

#### Stack 6 — Canonical and Hreflang (provider profile `google`)

| Code | Severity | Origin | Profile | Field | Evidence state | Contract |
|---|---|---|---|---|---|---|
| `canonical_relative_provider_best_practice` | warning | provider | `google` | `href` | — | Relative canonical (F-14); never a builder exception. |
| `hreflang_self_reference_missing` | warning | provider | `google` | `href` | — | A URL's own localized version missing from its supplied alternate set (F-15). |
| `hreflang_reciprocal_link_missing` | warning | provider | `google` | `href` | — | Missing return/reciprocal link in the supplied cluster (F-15). |
| `hreflang_alternate_set_inconsistent` | warning | provider | `google` | `href` | — | Inconsistent alternate sets across supplied localized URLs (F-15). |
| `hreflang_url_not_fully_qualified` | warning | provider | `google` | `href` | null | Alternate URL not fully qualified where the Google profile requires it (F-15). This single code covers **missing, malformed, and relative** candidate URLs for `HreflangValidationLinkDTO::$url`: emit when `url === null OR trim(url) === '' OR` the URL is not fully qualified under the fixed Google-profile structural contract. No separate missing/invalid URL code is introduced and no duplicate URL diagnostic is emitted for one link. Target `hreflang_link` + page index + link index. Companion-only, not legacy result. |
| `hreflang_tag_invalid_syntax` | warning | provider | `google` | `hreflang` | null | Candidate `hreflang` is `null`, whitespace-only, or malformed under the basic language-tag syntax fixed by F-15 (including preservation of `x-default` as valid). This diagnostic covers **syntax only**; it must not claim ISO 639 membership, ISO 3166 membership, or ISO 15924 membership — those registries remain DEFERRED exactly as F-15 states. Casing difference alone is never a diagnostic (`hreflang_noncanonical_case` or equivalent is not introduced). Target `hreflang_link` + page index + link index. Companion-only, not legacy result. |

**Hreflang cluster computation (fixed).** A candidate link that carries either `hreflang_tag_invalid_syntax` or `hreflang_url_not_fully_qualified` is still allowed to produce its own link-level diagnostic, but it is **excluded from semantic cluster-edge computation** for reciprocity, self-reference matching, and alternate-set equality. Invalid structural link data must not fabricate valid/invalid cluster edges; no additional cluster diagnostics are cascaded solely because an already structurally invalid link is excluded, and cluster-level diagnostics are computed from the structurally usable links only. This rule is fixed to prevent diagnostic cascades.

**Hreflang page identity (fixed).** `HreflangValidationPageDTO::$pageUrl` remains a non-empty unique exact-string structural identity and carries no page-URL diagnostic in this remediation. The page identity is invocation structure; fully-qualified alternate-link validation happens through each `HreflangValidationLinkDTO::$url`. `pageUrl` is never reinterpreted as another alternate link.

### Normative Legacy Classification Table — fixed

Two distinct kinds of entries exist inside `SeoCompanionDiagnosticDTO`:

**A. New diagnostics** — genuinely new companion diagnostics (the GDC-01 machine tables above plus the fixed F-13 OGP contracts). They may not reuse or shadow any legacy issue code.

**B. Legacy classification records** — companion metadata records for issues that already exist inside the legacy result. They are **not** new diagnostics. For these records only:

```text
code == related_legacy_code == existing legacy issue code
```

This is an explicit, intentional exception to the non-collision rule. A legacy classification record produces **no** additional legacy issue, **no** additional score contribution, **no** additional warning/error count, and **no** `is_valid` change. It is classification metadata for the legacy issue that already exists.

The complete normative table of legacy classification records for this remediation is:

| Legacy code              | Severity | Origin    | Profile       | Field            |
| ------------------------ | -------- | --------- | ------------- | ---------------- |
| `title_too_short`        | warning  | heuristic | `seo-default` | `title`          |
| `title_too_long`         | warning  | heuristic | `seo-default` | `title`          |
| `description_too_short`  | warning  | heuristic | `seo-default` | `description`    |
| `description_too_long`   | warning  | heuristic | `seo-default` | `description`    |
| `missing_og_title`       | warning  | protocol  | `ogp`         | `og:title`       |
| `missing_og_description` | warning  | heuristic | `seo-default` | `og:description` |
| `missing_og_image`       | warning  | protocol  | `ogp`         | `og:image`       |

For every row:

```text
evidence_state = null
related_legacy_code = same value as code
target = { scope: 'meta', entry_index: null, item_index: null, line: null }
```

Example record:

```json
{
  "code": "missing_og_description",
  "severity": "warning",
  "message": "...",
  "field": "og:description",
  "origin": "heuristic",
  "profile": "seo-default",
  "evidence_state": null,
  "related_legacy_code": "missing_og_description",
  "target": {
    "scope": "meta",
    "entry_index": null,
    "item_index": null,
    "line": null
  }
}
```

The `message` reflects/correlates with the same legacy issue and creates no new semantic rule. No legacy classification rows other than the seven above are added in this remediation (FIX 27).

### What legally belongs only in the companion surface

Examples fixed by this audit (the complete, normative list is the machine-contract tables above plus the fixed F-13 OGP contracts):

- F-06 leading-wildcard `robots_rfc9309_leading_wildcard_compatibility` (protocol/rfc9309 warning).
- F-06 Google robots.txt provider diagnostics `robots_google_document_size_exceeds_parse_limit` and `robots_google_present_path_leading_slash` (provider/google warnings).
- F-05 Google News freshness states `within_window`, `outside_window`, `unknown`.
- F-10 `unavailable_after` recognizability states `recognized`, `unrecognized`, `unknown`, plus `robots_meta_unavailable_after_missing`.
- F-09 `indexifembedded` without `noindex` provider diagnostic.
- F-02 `sitemap_location_scope_violation` (protocol/sitemaps error, deterministic caller-supplied scope context only).
- F-04 / F-05 / F-02 provider-content diagnostics that require caller-supplied evidence.
- F-13 new `missing_og_type` and `missing_og_url` protocol diagnostics.
- F-14 relative-canonical provider best-practice diagnostic.

None of these may be plumbed into the legacy result or the score.

### Consequence for implementers

An implementation stack that produces, serializes, or scores a new diagnostic through the legacy `SeoValidationResultDTO` or `SeoValidationScoreCalculator` violates this contract, regardless of internal naming. Stack 5 must expose the companion surface additively without altering legacy serialized shapes, F-12 scoring, or `SeoMetaValidator::validate()`, and must return the single unified result type `SeoCompanionValidationResultDTO` from every profile validator without introducing per-profile result variants or an alternative legacy-pairing mechanism. New diagnostic codes, severities, origins, profiles, fields, evidence states, or **target scopes** other than those fixed in the tables above (and in F-13) are out of contract. Every companion entry (including every legacy classification record) carries the required `SeoDiagnosticTargetDTO` fixed above; an entry without a valid target violates this contract.

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

**OGP activation — presence-triggered (FIX 10):**

OGP validation is **not** mandatory for every page. OGP profile validation is **presence-triggered**, reusing the existing compatibility activation boundary in `SeoMetaValidator`. The OGP profile is active only when at least one of the following signals is present in the supplied `$meta`:

- nested `openGraph` is array/object;
- nested `og` is array/object;
- `openGraphTitle`;
- `open_graph_title`;
- `openGraphDescription`;
- `open_graph_description`;
- `openGraphImage`;
- `open_graph_image`.

If none of these signals is present:
- no OGP protocol diagnostic is emitted;
- no OGP legacy classification record is emitted;
- a fully absent OGP section is **not** a protocol error in this remediation.

Activation is **not** extended by `og:type` or `og:url` alone in this round. This is an intentional compatibility decision (FIX 10).

**Legacy embedding — single exception (FIX 9 / FIX 26):**

`OpenGraphProtocolValidator` is the **exception** to the standalone-profile `legacy = null` rule. It always produces:

```php
$legacy = SeoMetaValidator::validate($meta, $options);
```

and embeds that result **unchanged** under `SeoCompanionValidationResultDTO::$legacy`. The reason: OGP compatibility intentionally reuses the existing legacy OGP issues as classification records. The exception is explicit, never implicit.

**No duplicate OGP semantics (FIX 11):** when the OGP profile is active:
- the existing legacy `missing_og_title` stays a legacy warning;
- the existing legacy `missing_og_image` stays a legacy warning;
- the existing legacy `missing_og_description` stays a legacy heuristic warning;
- the new `missing_og_type` stays a companion protocol warning;
- the new `missing_og_url` stays a companion protocol warning.

`OpenGraphProtocolValidator` expresses a missing required title/image through the **existing** GDC-01 legacy classification records — `missing_og_title` (protocol, `ogp`, related legacy code `missing_og_title`) and `missing_og_image` (protocol, `ogp`, related legacy code `missing_og_image`). It does **not** add duplicate new codes `ogp_missing_title` or `ogp_missing_image`. `missing_og_description` remains a heuristic (`seo-default`) classification record only — it is **not** an OGP protocol requirement. The only new companion diagnostics for missing OGP basics are `missing_og_type` and `missing_og_url`.

**Fate of `missing_og_description`:**

- `og:description` is optional under OGP, so the OGP protocol profile emits **no** issue for a missing description.
- The legacy `SeoValidationIssueDTO` `missing_og_description` warning is a pre-existing legacy issue. Under F-12 and GDC-01 it **remains in the legacy result unchanged**: same code, `warning` severity, same message, and the same default 5-point warning score deduction — exactly as today.
- Its companion entry is the **legacy classification record** fixed in the GDC-01 Normative Legacy Classification Table: origin `heuristic`, profile `seo-default`, `evidence_state = null`, `related_legacy_code = missing_og_description`, because it is a publisher recommendation, not an OGP protocol requirement. This record is **not** a new OGP diagnostic. Reclassification must not move the legacy issue to the companion surface, change its severity/code, or alter its score participation.

**Issue contracts for missing OGP required basics:**

- `og:title` and `og:image` already produce legacy warnings (`missing_og_title`, `missing_og_image`). Those pre-existing legacy issues stay in the legacy result and score under F-12 and GDC-01. Their companion classification is the legacy classification record fixed in the GDC-01 Normative Legacy Classification Table (origin `protocol`, profile `ogp`) — the missing property is a genuine OGP required basic even though it was historically reported as a warning. This is documentation/classification only; no legacy behavior changes.
- Missing `og:type` and missing `og:url` are **new** diagnostics introduced by the OGP profile (and the only new OGP diagnostics in this remediation). Their contracts are fixed as:
  - codes: `missing_og_type`, `missing_og_url`;
  - severity: `warning` — matching the severity convention of the existing missing-OGP-basic warnings;
  - origin: `protocol`; profile: `ogp`;
  - `field`: `og:type` for `missing_og_type`, `og:url` for `missing_og_url`;
  - `evidence_state`: `null`; `related_legacy_code`: `null`;
  - container: **companion surface only** (`SeoCompanionDiagnosticDTO` in `SeoCompanionValidationResultDTO`). Under GDC-01 they must **not** be appended to the legacy `errors`/`warnings`/`info`/`issues` collections, must **not** change `is_valid` or `has_warnings`, and must **not** produce any score deduction.
- These new codes must not collide with any legacy code and must not be emitted by `SeoMetaValidator::validate()` into the legacy result.
- When invoked through `validateWithCompanion()`, the legacy classification records for `missing_og_title`, `missing_og_image`, and `missing_og_description` appear **only** when the corresponding legacy issue is actually present in the produced legacy result, and the new `missing_og_type` / `missing_og_url` diagnostics are added exactly once by the fixed F-13 OGP companion protocol validation.

**Relationship to the legacy result and score:** unchanged for all pre-existing legacy issues; off-result and off-score for all new protocol diagnostics. `SeoMetaValidator::validate()` keeps returning the existing `SeoValidationResultDTO` contract; the OGP profile results are delivered through the companion surface (GDC-01) and the dedicated social builders. **Single OGP rule source (FIX 12):** `SeoMetaValidator::validateWithCompanion()` must produce the same observable companion result as `OpenGraphProtocolValidator::validate($meta, $options, $context)` for the same inputs — embedded legacy result, legacy classification records, and OGP companion diagnostics. No two independent implementations of the OGP companion rules may exist; internal delegation direction is an implementation detail.

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

The Google hreflang cluster validator consumes the fixed candidate cluster input `Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationClusterDTO` (a list of `HreflangValidationPageDTO` pages, each with a non-empty unique `pageUrl` and a list of `HreflangValidationLinkDTO` links) as its primary input. Because `HreflangLinkDTO` performs strict URL validation/normalization, the validation candidate types carry the raw candidate state needed to diagnose relative/non-qualified alternate URLs. Cluster data is never supplied through any context field, and no new `HreflangClusterDTO` is introduced. The host or caller supplies the deterministic cluster input; the validator performs no crawling.

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

Every new protocol/provider/context diagnostic introduced by Stacks 2/4/5/6 is delivered through the GDC-01 companion surface: a `SeoCompanionDiagnosticDTO` entry inside the unified `SeoCompanionValidationResultDTO` (GDC-01 public access contract). It must not change `SeoValidationResultDTO::is_valid`, must not be appended to the legacy `errors`/`warnings`/`info`/`issues` collections, and must not affect `SeoValidationScoreCalculator`. Pre-existing legacy issues keep their F-12 placement and score behavior already documented here. Legacy classification records (GDC-01 Normative Legacy Classification Table) are companion metadata, **not** new diagnostics: they intentionally reuse the legacy `code` as `related_legacy_code` and produce no additional legacy, scoring, or validity effect.

## AP-13 — Numeric textual boundaries use the fixed measurement policy

Sitemap `<loc>` and Google Video `description` boundaries follow the F-02 exact measurement contract exactly as two separate things: the **source-backed rule** (a unit-undefined character limit in each source) and the **library deterministic policy** (an explicitly labeled EVIDENCE BOUNDARY converting it to UTF-8 bytes via `strlen()` on the value as supplied, before URI/IRI normalization/percent-encoding or XML escaping). A value **below** the byte threshold is conservative proof that it is also below the same numeric threshold under Unicode code-point or grapheme counting, because the UTF-8 byte count is never smaller than those counts for valid UTF-8 text; a value **at/above** it emits `sitemap_loc_length_exceeds_measure_boundary` / `google_video_description_length_exceeds_measure_boundary` as a conservative library-policy warning, never as a claim of a proven protocol/provider violation — failing the byte boundary is not proof that the source-defined character limit is exceeded, since multi-byte text may exceed the byte threshold while remaining below the same character-count threshold. Stack 4 must not substitute `mb_strlen`, grapheme counting, or post-encoding measurement, and must not reinterpret the boundary as an implementer choice. Any other textual limit whose unit the source does not fix must receive the same two-part treatment.

## AP-14 — Strict domain DTOs are not validation candidate containers

A DTO whose constructor guarantees valid rendering/domain state must not be weakened merely so a profile validator can inspect invalid candidate data. Validation uses the dedicated candidate-input layer (`Maatify\Seo\Web\Validation\Input`, plus its `Sitemap` / `Hreflang` sub-namespaces). Adding a candidate type never relaxes, extends, or re-invokes a strict DTO's accepted states.

## AP-15 — Every companion diagnostic has a machine-readable target

`SeoCompanionDiagnosticDTO::$target` is required on every companion entry. The human-readable `message` cannot be used as target identity; item/entry identity is structural (`entry_index` / `item_index` / `line` within a fixed scope).

## AP-16 — Every remediation rule has one observable disposition

Every normative rule required by a Stack must be explicitly classified as **exactly one** of:

- **companion diagnostic** — a machine-contract row in GDC-01, an F-13 OGP diagnostic, or a legacy classification record;
- **legacy classification** — a Normative Legacy Classification Table record (metadata, not a new diagnostic);
- **strict invocation/constructor rule** — documented, deterministic input-safety or structural behavior (for example `SeoInvalidArgumentException` for control-character injection, invalid target shapes, invalid candidate invocation shapes);
- **parser/serialization guarantee** — raw `#` comment semantics, percent-encoded literal path data, UTF-8/entity/URI escaping output guarantees;
- **deferred / no-runtime-diagnostic scope** — explicitly deferred provider/transport/registry facts that produce no runtime diagnostic in this remediation.

No implementation task may infer a new diagnostic merely because a Finding discusses a standard/provider rule, and no prose rule outside the normative registry may be read as authorizing one.

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

### Foundation deliverable (FIX 29)

Stack 1 establishes the foundation types, with **no** provider business rules invented here:

- `Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO`;
- `Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO`;
- `Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO`;
- the evidence-only `Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO`;
- the validation candidate input DTO foundations under `Maatify\Seo\Web\Validation\Input` (and its `Sitemap` / `Hreflang` sub-namespaces), fixed in GDC-01.

The candidate DTOs and the strict domain/rendering DTOs remain two separate layers; Stack 1 must not relax or modify any strict DTO constructor.

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
2. Introduce the raw robots validation candidate inputs `Maatify\Seo\Web\Validation\Input\RobotsTxtValidationInputDTO` and `Maatify\Seo\Web\Validation\Input\RobotsMetaValidationInputDTO`. The RFC/Google robots validators consume candidate inputs only; the strict robots generation DTOs (`RobotsRuleDTO`, `RobotsTxtDTO`, `MetaRobotsBuilder`, renderer) stay unchanged (FIX 29).
3. Implement the RFC 9309 product-token contract (`identifier` or `*`) and valid empty Allow/Disallow patterns. A parsed `User-agent` whose product-token is neither `*` nor the fixed F-06 identifier grammar (for example an identifier containing a digit) emits the protocol diagnostic `robots_rfc9309_product_token_invalid` (error) through the GDC-01 companion surface; the raw candidate document remains valid validator input and this is never an invocation exception.
4. Apply the F-06 leading-`*` policy exactly: preserve existing builder/rendering compatibility; do not call it normative published-ABNF conformance; emit the fixed non-fatal RFC compatibility diagnostic `robots_rfc9309_leading_wildcard_compatibility` (warning, origin `protocol`, profile `rfc9309`) through the GDC-01 companion surface; do not rewrite it; keep the Google-profile path diagnostic `robots_google_present_path_leading_slash` separate and also companion-only. A non-empty path that satisfies neither the accepted `/`-started form nor the special leading-`*` compatibility case emits `robots_rfc9309_path_pattern_invalid` (error, protocol diagnostic); an empty Allow/Disallow pattern and an ordinary non-empty `/`-started path emit no path-start diagnostic.
5. Implement RFC path/comment semantics, including raw `#` as **parser semantics** (comment interpretation per F-06), percent-encoded literal special characters as path data (matching/path semantics), and matching/encoding boundaries relevant to generated output. A raw `#` is **never** a diagnostic — Stack 2 must not invent a `raw_hash_invalid` code.
6. Prevent CR/LF/control-character directive injection across rule values, rule comments, and top-level comments. In the raw candidate validator, a forbidden non-line control character inside a parsed semantic token/value emits `robots_rfc9309_control_character_invalid` (error, field `user_agent` or `path` according to the parsed rule); normal CRLF/LF document line separators are not diagnostics. In the **structured strict DTO/generator lane**, CR/LF/control-character injection through structured values/comments is a **hard input-safety contract**, not a companion diagnostic: structured construction must reject embedded forbidden control content through `Maatify\Seo\Exception\SeoInvalidArgumentException` where the existing structured API accepts user-agent values, Allow/Disallow values, rule comments, top-level robots comments, and Sitemap directive values. No silent sanitization, no silent truncation, and no companion diagnostic in place of the exception. The exact internal exception factory/message is an implementation detail; the exception class is fixed.
7. Preserve `crawl-delay` as the existing non-standard compatibility extension; do not represent it as RFC or Google behavior and do not invent a new extension framework in this stack.
8. Add an explicit Google robots.txt profile for:
   - UTF-8/plain-text output;
   - 500 KiB document boundary (byte source: `strlen(RobotsTxtValidationInputDTO::$content)`);
   - raw document UTF-8 validity → `robots_google_document_invalid_utf8` (warning, provider, GDC-01 companion-only), independent of and co-existing with the 500 KiB diagnostic;
   - Google path behavior for present Allow/Disallow values;
   - Google `Sitemap:` fully-qualified URL semantics → `robots_google_sitemap_url_not_fully_qualified` (warning, provider, field `sitemap`, target `robots_rule` + exact 1-based source line) when a present value fails the fixed fully-qualified contract;
   - Unicode/non-URL-encoded Sitemap paths;
   - multiplicity without a documented limit;
   - cross-host Sitemap URLs;
   - independence from user-agent groups.
9. Replace the Google-profile reliance on ASCII-only `FILTER_VALIDATE_URL` with the F-06 fixed acceptance contract for the `Sitemap:` field; do not weaken unrelated generic URL contracts.
10. Fix `max-snippet:-1` and `max-video-preview:-1`.
11. Add typed `indexifembedded` support while preserving the raw escape hatch; emit `robots_meta_indexifembedded_without_noindex` (warning, origin `provider`, profile `google`, GDC-01 companion-only) when `noindex` is absent — never a builder construction barrier.
12. Correct `noarchive` documentation.
13. Implement F-10 exactly: keep `unavailableAfter()` raw-compatible; emit `robots_meta_unavailable_after_missing` for the provider-path missing/empty value; consume only explicit `recognized` / `unrecognized` / `unknown` evidence for non-empty provider recognizability; do not invent a closed date grammar; all `unavailable_after` diagnostics are GDC-01 companion-only.
14. Update examples/tests/docs.

### Robots outcome ownership — fixed (contract completeness sweep)

Stack 2 must explicitly distinguish every Robots rule outcome as exactly one of:

- **Companion diagnostics** — the Robots machine codes fixed in GDC-01: `robots_rfc9309_leading_wildcard_compatibility`, `robots_rfc9309_product_token_invalid`, `robots_rfc9309_path_pattern_invalid`, `robots_rfc9309_control_character_invalid`, `robots_google_document_size_exceeds_parse_limit`, `robots_google_document_invalid_utf8`, `robots_google_present_path_leading_slash`, `robots_google_sitemap_url_not_fully_qualified`, plus the robots-meta codes `robots_meta_indexifembedded_without_noindex`, `robots_meta_unavailable_after_missing`, and `robots_meta_unavailable_after_recognizability`.
- **Parser semantics** — raw `#` comment interpretation and percent-encoded literal handling; these are parser behavior and are never diagnostics.
- **Hard structured-input safety** — `SeoInvalidArgumentException` for control-character injection through the structured APIs (user-agent values, Allow/Disallow values, rule comments, top-level robots comments, Sitemap directive values); no silent sanitization/truncation and no companion diagnostic instead.
- **Deferred** — HTTP MIME/transport verification (`DEFER TRANSPORT CONTEXT`) is outside Stacks 0–8; no MIME/content-type diagnostic and no transport context/evidence key is added.

No other Robots outcome is permitted.

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

### Critical constraint (FIX 29)

Stack 3 is the **rendering architecture**. It continues to rely on the existing strict rendering/domain DTOs (`SitemapUrlDTO`, `SitemapImageDTO`, `SitemapVideoDTO`, `SitemapNewsDTO`, the two `SitemapIndexEntryDTO` namespaces) as its input surface. The validation candidate DTOs (`SitemapValidationDocumentDTO`, `SitemapUrlValidationInputDTO`, and the rest) **do not** enter the renderer/generator. Stack 3 (rendering) and Stack 4 (validation candidate/profile architecture) use different input layers; mixing them is forbidden.

### Stop condition

There must be one rule implementation for equivalent sitemap output, while both existing public index-entry DTO entry points remain available. No public entry point may silently drop extended DTO data; `SitemapGeneratorService` and `SitemapXmlStringRenderer` must produce identical extended-child XML for equivalent DTO input. If characterization exposes behavior whose preserve/change decision is not fixed by this audit, assert the AP-11 gate and stop that path.

---

## Stack 4 — Sitemap Standards / Google Extensions

### Goal

Add layered validation without collapsing protocol, provider, content-context, and remote-evidence rules into one constructor.

### Input model (FIX 29)

Stack 4 validates through the fixed candidate document/input DTOs (`SitemapValidationDocumentDTO` and its entry/image/video/news candidate types). Protocol and provider validators consume candidate inputs; child-level diagnostics carry deterministic machine targets (`SeoDiagnosticTargetDTO`); protocol authority evidence sits behind the fixed evidence boundary; and **no** strict rendering DTO constructor is relaxed by this stack.

### Required coverage

#### Base sitemap
- URL sitemap count limit: 50,000 URLs; over-limit emits `sitemap_url_count_exceeds_limit` (error, GDC-01 companion-only; target `sitemap_document`).
- Sitemap Index count limit: 50,000 Sitemaps; over-limit emits `sitemap_index_count_exceeds_limit` (error, GDC-01 companion-only; target `sitemap_document`).
- uncompressed byte-size limits: 50 MB (52,428,800 bytes); over-limit emits `sitemap_document_size_exceeds_boundary` (error, GDC-01 companion-only; target `sitemap_document`); byte source is `SitemapValidationDocumentDTO::$uncompressedSizeBytes`, never estimated from entry count.
- page `<loc>`: the sitemaps.org source rule is "less than 2,048 characters" without a defined unit. The F-02 exact measurement contract separates that source rule from the library deterministic policy (EVIDENCE BOUNDARY): measure UTF-8 bytes of the value as supplied (`strlen(loc) < 2048`), before URI/IRI normalization/percent-encoding, and emit `sitemap_loc_length_exceeds_measure_boundary` (warning, GDC-01 companion-only; target `sitemap_url` + URL `entryIndex`) as a conservative library-policy boundary — never worded as a proven protocol violation.
- missing entry-level `loc`: `loc === null || trim(loc) === ''` emits `sitemap_loc_missing` (error; `field = loc`, target `sitemap_url` + URL `entryIndex` for a URL Sitemap entry; `field = sitemap`, target `sitemap_index_entry` + child index for a Sitemap Index child). Missing `loc` never also emits `sitemap_loc_invalid_uri_iri`.
- malformed non-empty `loc`: a non-empty supplied `loc` that fails the F-02 Sitemap URL/URI/IRI lexical contract emits `sitemap_loc_invalid_uri_iri` (error; same field/target mapping as `sitemap_loc_missing`). XML escaping failure is **not** part of this diagnostic.
- `changefreq` vocabulary: a non-null/non-empty candidate outside the fixed Sitemap vocabulary already represented by the strict DTO contract emits `sitemap_changefreq_invalid` (error, GDC-01 companion-only; target `sitemap_url` + URL `entryIndex`); null `changefreq` means absent and emits nothing.
- `priority` limits: a non-null `priority` that is non-finite, `< 0.0`, or `> 1.0` emits `sitemap_priority_out_of_range` (error, GDC-01 companion-only; target `sitemap_url` + URL `entryIndex`); null `priority` means absent.
- Generic Sitemaps.org protocol location/scope: uses **only** `sitemap_location_scope_violation` (error, origin `protocol`, profile `sitemaps`, GDC-01 companion-only), consuming `SitemapValidationDocumentDTO::$location` as its sole document-location input. No Search Console, no Google ownership state, no network, and no guessed ownership; `google_sitemap_host_context` is never used to prove or disprove Sitemaps.org protocol scope. Cross-submission authority uses only the `sitemaps.cross_submission_authority` evidence key per the FIX 19–21 exact logic (authorized → no scope error; unauthorized → scope error; unknown/missing → EVIDENCE BOUNDARY, no fabricated error).
- Google verified ownership/submission evidence: uses **only** `google_sitemap_host_context` (origin `provider`, profile `google`, GDC-01 companion-only; target `sitemap_document`), consuming `evidence['google_sitemap.host_verification']` with states `verified_host` / `unverified_host` / `unknown`. It is independent provider evidence; no offline host verification is fabricated, and it is never a substitute for generic Sitemap protocol location-scope validation and never a cross-submission authority.
- Sitemaps.org location/scope protocol validation: modeled as `sitemap_location_scope_violation` (error, origin `protocol`, profile `sitemaps`, GDC-01 companion-only), emitting with `field = loc` (target `sitemap_url` + URL index) for page-URL scope violations or `field = sitemap` (target `sitemap_index_entry` + child index) for Sitemap Index child location scope violations; emitted only when caller-supplied document/location context deterministically proves the violation; no network, no Search Console, no guessed ownership. URL-sitemap cross-submission follows FIX 20; the Sitemap Index same-site rule never consumes `sitemaps.cross_submission_authority` (FIX 21).
- UTF-8 encoding requirements — **serialization/output guarantee** (Stack 3; not a candidate-validation disposition).
- XML entity escaping and URL URI/IRI escaping requirements — **serialization/output guarantee** (Stack 3; not a candidate-validation disposition). XML escaping failure is not `sitemap_loc_invalid_uri_iri`.
- `lastmod` must implement exactly these library protocol-profile forms: `YYYY-MM-DD`, `YYYY-MM-DDThh:mm:ssTZD`, and `YYYY-MM-DDThh:mm:ss.sTZD` with one-or-more fractional digits; dateTime requires `Z` or `±hh:mm` timezone designator.
- year-only, year-month, hour/minute-only, zone-less dateTime, and malformed forms are out of contract and emit `sitemap_lastmod_invalid_lexical` (error, GDC-01 companion-only). Target mapping is fixed per document type: URL Sitemap entry → `sitemap_url` + `entryIndex`; Sitemap Index child → `sitemap_index_entry` + `entryIndex`. Missing/null `lastmod` is a valid absence and emits no diagnostic.
- fractional-second characterization and intentional correction of the current helper mismatch.
- URL-sitemap vs Sitemap-Index `lastmod` semantics.
- verification of XMLWriter UTF-8 and serialization guarantees.

All Base-sitemap code/severity/origin/profile/field contracts are fixed in the GDC-01 machine-contract tables; Stack 4 must not alter them.

#### Google base Sitemap behavior
- `priority` is ignored by Google → `google_sitemap_priority_ignored` (info, GDC-01 companion-only).
- `changefreq` is ignored by Google → `google_sitemap_changefreq_ignored` (info, GDC-01 companion-only).
- `lastmod` is useful to Google only when consistently/verifiably accurate and tied to significant page modification → `google_sitemap_lastmod_accuracy` evidence diagnostic with `accurate`/`inaccurate`/`unknown` states (GDC-01 companion-only).
- These are Google-provider semantics/diagnostics delivered through the GDC-01 companion surface; they must not redefine generic Sitemap protocol validity and must not change the legacy result or score.

#### Google Image
- current/deprecated field classification.
- 1,000-images-per-URL limit → `google_image_count_exceeds_limit` (warning, GDC-01 companion-only).
- cross-domain Search Console verification context → `google_image_cross_domain_verification` evidence diagnostic (GDC-01 companion-only).
- external crawlability context → `google_image_crawlability_context` evidence diagnostic (GDC-01 companion-only).
- provider diagnostics here are GDC-01 companion-only.
- **Image diagnostic-scope freeze (contract completeness sweep):** no Google Image `loc`/`title`/`caption`/`geoLocation`/`license`/URL-shape missing or malformed diagnostic is added in this remediation. Although `SitemapImageValidationInputDTO` can represent null/malformed fields, the current F-03 approved machine scope remains intentionally limited to the count diagnostic, the two evidence diagnostics, and the legacy-field compatibility classification. Candidate representability does not expand this scope; `GoogleImageSitemapValidator` emits no new missing/URL-shape diagnostic.

#### Google Video
- characterize current DTO and raw-array behavior.
- required title/description/thumbnail plus content/player presence → `google_video_title_missing`, `google_video_description_missing`, `google_video_thumbnail_loc_missing`, `google_video_content_or_player_loc_missing` (warning, GDC-01 companion-only). Missing is `value === null || trim(value) === ''` for the required textual values; `google_video_content_or_player_loc_missing` emits only when **both** `contentLoc` and `playerLoc` are missing (null/whitespace-only), and a single present-but-malformed media location uses its URL-shape diagnostic instead.
- non-empty-but-malformed media/thumbnail URL shapes → `google_video_thumbnail_loc_invalid_url`, `google_video_content_loc_invalid_url`, `google_video_player_loc_invalid_url` (warning, GDC-01 companion-only; target `sitemap_video` + `[urlIndex][videoIndex]`). A missing thumbnail uses `google_video_thumbnail_loc_missing`, never both codes.
- title host-page match as a provider recommendation → `google_video_title_host_page_match` evidence diagnostic.
- description: Google's source rule is "a maximum of 2,048 characters" without a defined unit. The F-02 exact measurement contract separates that rule from the library deterministic policy (EVIDENCE BOUNDARY): measure UTF-8 bytes of the value as supplied (`strlen(description) <= 2048`) before XML escaping/CDATA wrapping, and emit `google_video_description_length_exceeds_measure_boundary` (warning, GDC-01 companion-only) as a conservative library-policy boundary — never worded as a proven provider violation. Host-page consistency is a separate evidence diagnostic `google_video_description_host_page_match` (field `description`, states `matches`/`differs`/`unknown`), distinct from `google_video_relevance_context` (topical relevance) and from `google_video_title_host_page_match` (title consistency).
- duration 1..28,800 → `google_video_duration_out_of_range` (warning, GDC-01 companion-only).
- exact two publication-date forms represented by the provider documentation → `google_video_publication_date_invalid` (warning, GDC-01 companion-only).
- parent `<loc>` inequality → `google_video_media_loc_equals_parent_loc` (warning, GDC-01 companion-only).
- host-page relevance requirement → `google_video_relevance_context` evidence diagnostic.
- `content_loc` preference as a provider recommendation, not validity → `google_video_content_loc_preference` (info, GDC-01 companion-only).
- supported file-type list exactly as documented, without invented extension aliases.
- Data URLs unsupported → `google_video_data_url_unsupported` (warning, GDC-01 companion-only) with `field = content_loc` when `contentLoc` is a Data URL, `field = player_loc` when `playerLoc` is a Data URL, and **two** same-code diagnostics (content and player) when both locations are Data URLs; no separate codes are created.
- protocol evidence handled exactly as documented: HTTP/FTP explicitly named, HTTPS demonstrated by Google's own examples, streaming protocols unsupported; no false claim that Google literally publishes `HTTP/HTTPS/FTP only`. No generic `unsupported_video_scheme` diagnostic and no closed streaming-protocol taxonomy is introduced; HTTP/HTTPS/FTP/streaming source evidence remains **documentation evidence only** until a separately approved closed machine contract exists.
- thumbnail formats: BMP, GIF, JPEG, PNG, WebP, SVG, AVIF.
- thumbnail minimum 60x30, stable URL, Googlebot/Googlebot Images accessibility, and transparency requirement.
- deterministic/context rules separated from remote format/accessibility evidence.
- **Video remote-evidence deferral (contract completeness sweep):** no additional runtime diagnostic is created in Stacks 0–8 for actual remote video file type; actual thumbnail file format, dimensions, stability, accessibility, or transparency; actual Googlebot accessibility of referenced video resources; or watch-page/video indexing eligibility. These are explicitly `DEFER PROVIDER EVIDENCE CONTRACT` pending a separately approved contract; no implementer may invent evidence keys or codes for them in Stack 4.
- title/description XML escaping/CDATA output semantics — **serialization/output guarantee**, not a candidate-validation disposition.
- watch-page/video indexing eligibility kept outside Sitemap DTO validity.
- all Video provider/context diagnostics are GDC-01 companion-only; code/severity/origin/profile/field contracts are fixed in the GDC-01 machine-contract tables.

#### Google News
- four exact publication-date forms → `google_news_publication_date_invalid` (warning, GDC-01 companion-only).
- required-field missing diagnostics (contract completeness sweep): `google_news_publication_name_missing` (field `name`), `google_news_language_missing` (field `language`), `google_news_publication_date_missing` (field `publication_date`), and `google_news_title_missing` (field `title`) — all `warning`, origin `provider`, profile `google`, target `sitemap_news` + `[urlIndex][newsIndex]`. Missing is `value === null || trim(value) === ''`.
- **missing-vs-invalid precedence (fixed):** `publicationDate` missing → `google_news_publication_date_missing` only; present but invalid lexical form → `google_news_publication_date_invalid` only. `language` missing → `google_news_language_missing` only; present but invalid → `google_news_language_invalid` only. The missing and invalid codes never co-emit for the same field.
- publication date means original first publication time, not Sitemap-addition time → `google_news_original_publication_evidence` evidence diagnostic.
- language contract: two/three-letter ISO 639 plus `zh-cn` / `zh-tw` exceptions → `google_news_language_invalid` (warning, GDC-01 companion-only).
- publication-name exact-match semantics and parenthetical omission rule → `google_news_publication_name_parenthetical` (locally decidable) and `google_news_name_exact_match_evidence`.
- title semantics: structurally valid `title` is diagnosed only by `google_news_title_missing` when absent; when present, title-content conformance (article-title correspondence and the documented exclusion semantics of F-05) is supplied by caller evidence under `google_news.title_content_conformance` and emitted as `google_news_title_content_evidence` (`conforming` → info, `nonconforming` → warning, `unknown` → info). No local inference from the title string alone, and no title-content evidence for a missing-title item.
- one News entry per URL provider cardinality, while preserving the public list contract until migration is deliberate → `google_news_multiple_entries_per_url` (warning, GDC-01 companion-only).
- 1,000 total News entries per Sitemap → `google_news_document_count_exceeds_limit` (warning, GDC-01 companion-only).
- "last two days" is a context/evidence diagnostic only → `google_news_freshness_evidence` consuming caller-supplied `within_window` / `outside_window` / `unknown` evidence; do not derive a hard boundary from `publicationDate` plus a clock/reference time.
- no hidden `now()`/global time and no invented `48 hours` or calendar-day arithmetic.
- legacy optional News fields (`access`, `genres`, `keywords`, `stockTickers`) are **documentation/provider-status classification only** in this remediation: no runtime diagnostic, no removal, no deprecation implementation, no candidate validation failure. Any future runtime provider diagnostic requires a separately approved contract.
- canonical example correction for `publicationDate: 'as-provided'`.
- all News provider/context diagnostics are GDC-01 companion-only; code/severity/origin/profile/field contracts are fixed in the GDC-01 machine-contract tables.

### Critical constraint

Document/context and remote-evidence rules must not be forced into single-entry constructors when the required evidence is unavailable. Provider diagnostics must not become fake offline proof. All new Stack 4 diagnostics are delivered through the GDC-01 companion surface; none changes the legacy result or score.

---

## Stack 5 — Validation Architecture + Open Graph

### Goal

Stop mixing heuristic recommendations with protocol validity.

### Order

1. Introduce additive issue origin/profile classification exactly under the F-12 contract, delivering every new diagnostic as a `SeoCompanionDiagnosticDTO` entry in the unified `SeoCompanionValidationResultDTO` and exposing them through the GDC-01 public access contract using exactly the fixed invocation signatures (`SeoMetaValidator::validateWithCompanion(array|object $meta, array $options = [], ?SeoValidationContextDTO $context = null)` and the 11 fixed profile-validator `validate()` signatures), with no alteration to legacy `SeoValidationIssueDTO` / `SeoValidationResultDTO` serialized shapes or to `SeoMetaValidator::validate()` return contract.
2. Establish OGP protocol validation under the fixed F-13 OGP migration contract.
3. Preserve legacy issue/score behavior: current title/description length issue codes and warning severity remain unchanged, and warning issues continue to flow through the existing score calculator.
4. Reclassify title/description length warnings as origin `heuristic`, profile `seo-default` without changing their observable compatibility behavior.
5. Preserve `strlen()` byte measurement as the title/description heuristic measurement unit for this remediation; do not substitute code-point or grapheme measurement.
6. Add characterization tests for ASCII and Arabic/Unicode title/description lengths that lock the current byte-based thresholds before refactoring validation architecture.
7. Declare Twitter/X provider conformance out of scope until an official-source revalidation is conducted.
8. Implement the OGP profile for the four required basics and the current exposed optional surface: determiner, locale, site_name, HTTP/HTTPS URL datatype, audio/video root URLs, image structured properties, multiple-image array preference/order, root/structured-property association, and `og:image:alt` as a protocol-level recommendation. The OGP profile is **presence-triggered** (FIX 10): active only when one of the fixed presence signals exists; a fully absent OGP section is not a protocol error and produces no OGP classification records. Emit the new `missing_og_type` / `missing_og_url` protocol diagnostics (`warning`, origin `protocol`, profile `ogp`, `evidence_state`/`related_legacy_code` null, target `meta`) as GDC-01 companion-only; keep the pre-existing `missing_og_title`, `missing_og_image`, and `missing_og_description` legacy issues in the legacy result and score unchanged; do **not** add duplicate codes such as `ogp_missing_title` / `ogp_missing_image`. Companion classification for those three is exactly the legacy classification record fixed in the GDC-01 Normative Legacy Classification Table (origin `protocol`, profile `ogp` for `missing_og_title`/`missing_og_image`; origin `heuristic`, profile `seo-default` for `missing_og_description`), emitted only when the corresponding legacy issue is actually present. `OpenGraphProtocolValidator::validate(array|object $meta, array $options = [], ...)` always embeds the exact legacy result from `SeoMetaValidator::validate($meta, $options)` under `legacy` (FIX 9 / FIX 26), and `SeoMetaValidator::validateWithCompanion()` shares that single OGP rule implementation with identical observable output (FIX 12).
9. Keep heuristic warnings participating in scores exactly as current warnings do, including the existing default 5-point warning penalty; any future scoring change requires a separate explicit contract change.
10. Align dedicated social builders and legacy `MetaTagsDTO` path without adding origin/profile keys to legacy JSON payloads.

### Critical constraint

Do not change existing score math, heuristic score participation, issue severity/codes, title/description byte measurement, or the existing validation result serialization while changing semantic categories. Do not route any new OGP diagnostic into the legacy result or score (GDC-01).

---

## Stack 6 — Canonical and Hreflang

### Canonical

- keep generic relative behavior; `CanonicalUrlBuilder::build()` is unchanged.
- add the provider best-practice diagnostic `canonical_relative_provider_best_practice` (`warning`, origin `provider`, profile `google`, field `href`) for a relative canonical URL, delivered through the GDC-01 companion surface — never into the legacy result or score, and never as a builder exception.

### Hreflang

- unify Web and Sitemap hreflang parsing / normalization semantics.
- use conventional BCP 47 casing when normalization is performed, without treating casing alone as provider invalidity. **Casing difference alone is never a diagnostic** — no `hreflang_noncanonical_case` (or equivalent) code exists; tests may verify normalization equivalence only.
- preserve `x-default`.
- validate deterministically knowable Google structural rules from supplied data. `hreflang_url_not_fully_qualified` (warning, GDC-01 companion-only) is the single code for alternate URL issues covering missing, malformed, and relative candidate URLs: emit when `HreflangValidationLinkDTO::$url` is `null`, `trim(url) === ''`, or not fully qualified under the fixed Google-profile structural contract. No separate missing/invalid URL code and no duplicate per-link URL diagnostic.
- add the tag-syntax code `hreflang_tag_invalid_syntax` (warning, origin `provider`, profile `google`, field `hreflang`, GDC-01 companion-only): emitted when candidate `hreflang` is `null`, whitespace-only, or malformed under the basic language-tag syntax fixed by F-15. It asserts **syntax only** and must not claim ISO 639 / ISO 3166 / ISO 15924 membership; `x-default` remains valid.
- `GoogleHreflangClusterValidator` accepts the fixed candidate cluster input `Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationClusterDTO` (list of `HreflangValidationPageDTO` pages, each with a non-empty unique `pageUrl` and a list of `HreflangValidationLinkDTO` links). The cluster input is itself the complete deterministic cluster; cluster data is never supplied through any context field, and no `HreflangClusterDTO` is introduced.
- add cluster-level validation with the fixed GDC-01 codes `hreflang_self_reference_missing`, `hreflang_reciprocal_link_missing`, and `hreflang_alternate_set_inconsistent` (all warning, origin `provider`, profile `google`, field `href`, GDC-01 companion-only).
- **structural-invalid link exclusion (fixed):** a candidate link carrying either `hreflang_tag_invalid_syntax` or `hreflang_url_not_fully_qualified` may still produce its own link-level diagnostic, but is excluded from semantic cluster-edge computation (reciprocity, self-reference matching, alternate-set equality). Cluster-level diagnostics are computed from the structurally usable links only; no cascade diagnostic is added because a structurally invalid link was excluded. This prevents synthetic reciprocity/self-reference cascades.
- `HreflangValidationPageDTO::$pageUrl` remains a non-empty unique exact-string structural identity (invocation structure) with no page-URL diagnostic in this remediation; it is never reinterpreted as another alternate link.
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

Every new protocol/provider/context diagnostic introduced by Stacks 2/4/5/6 is delivered through the GDC-01 companion surface. It must not change `is_valid`, must not be appended to legacy `errors`/`warnings`/`info`/`issues`, and must not alter `SeoValidationScoreCalculator` output. The single exception is pre-existing legacy issues, which keep their F-12 placement and score behavior. Legacy classification records (GDC-01 Normative Legacy Classification Table) are companion metadata, not new diagnostics: they intentionally reuse the legacy `code` as `related_legacy_code` and produce no additional legacy, scoring, or validity effect.

## ADR-14

**Do not substitute a different unit for the fixed numeric boundaries, and do not overclaim the source rule.**

Sitemap `<loc>` (`< 2048`) and Google Video `description` (`<= 2048`) are source-defined only as unit-undefined character limits. The audit converts them (F-02) to an explicitly labeled EVIDENCE BOUNDARY with UTF-8 byte measurement via `strlen()` on the value as supplied, as a conservative library policy. `mb_strlen`, grapheme counting, and post-encoding/post-escaping measurement are prohibited as unit substitutes. A value below the byte boundary is conservative proof that it is also below the same numeric threshold under Unicode code-point or grapheme counting, because the UTF-8 byte count is never smaller than those counts for valid UTF-8 text. A value at/above the byte boundary emits the fixed library-policy warning codes and must not be reported as a proven violation of the source's character rule, because the source does not define the unit the rule uses and a multi-byte value may exceed the byte threshold while remaining below the same character-count threshold.

## ADR-15

**One companion result type, one public access contract.**

All new diagnostics flow through the unified `SeoCompanionValidationResultDTO` with `SeoCompanionDiagnosticDTO` entries, correlated to the legacy result via the embedded `legacy` property and per-entry `related_legacy_code`. Every profile validator returns this same type; custom per-profile result variants and alternative public methods are prohibited (GDC-01 public access contract).

## ADR-16

**Separate candidate-validation inputs from strict domain/render DTOs.**

Strict DTO relaxation is rejected because a DTO whose constructor provably guarantees valid rendering/domain state cannot honestly carry invalid candidate data; candidate inputs are additive and do not replace existing DTOs; Stack 3 (rendering) and Stack 4 (validation) consume different input layers by design. Candidate constructors perform representation/shape only, never protocol/provider validation.

## ADR-17

**Companion diagnostics use stable structural target locators.**

`SeoDiagnosticTargetDTO` is required on every companion entry; scopes are the fixed closed set (13 values); entry/item indexes are zero-based against candidate lists; robots path diagnostics carry an exact 1-based source line; URL strings, titles, messages, and fields are never target identity. An invalid target shape is a construction error.

## ADR-18

**Machine contract completeness and anti-cascade policy.**

The Candidate DTO expansion in the validation candidate layer exposed previously unreachable rule gaps (missing `loc`, invalid `changefreq`, out-of-range `priority`, invalid `hreflang` syntax, missing News required fields) — the candidate layer makes the already-locked machine-contract diagnostics reachable and, because Candidate representability does **not** authorize diagnostics, it may not by itself widen the diagnostic scope. A field that is missing and has a dedicated missing diagnostic emits the missing diagnostic and never also a malformed/lexical diagnostic for the same absence (missing-vs-invalid precedence). Invalid hreflang links are excluded from cluster-edge computation so that structurally invalid link data cannot fabricate valid/invalid cluster edges or cascade synthetic cluster diagnostics. Remote/context facts without an approved evidence contract (Video remote-facts, robots MIME/transport) are explicitly deferred rather than guessed, because a deferred fact must never be converted into a locally fabricated pass/fail.

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

**Legacy compatibility:**

- Characterize the exact existing method contract and prove it stays unchanged:

  ```php
  SeoMetaValidator::validate(array|object $meta, array $options = [])
  ```

- Prove `MetaTagsDTO` remains accepted through the `object` branch while the broader public input contract (`array|object`) is preserved; the audit's earlier `validate(MetaTagsDTO $meta)` wording is withdrawn and must not reappear.

**`validateWithCompanion`:**

- `validateWithCompanion($meta, $options, $context)` produces an embedded `legacy` result exactly equal to `validate($meta, $options)` for the same `$meta` and `$options`.
- Custom options supplied through `$options` (for example title/description thresholds) flow identically into the embedded legacy result.

**Legacy classification:**

- Assert every fixed classification row: `title_too_short`, `title_too_long`, `description_too_short`, `description_too_long`, `missing_og_title`, `missing_og_description`, `missing_og_image`.
- For each row: the classification record appears **only** when the underlying legacy issue exists; `code == related_legacy_code`; severity matches the legacy severity; origin/profile match the GDC-01 Normative Legacy Classification Table; the legacy result and score remain unchanged.

**Profile public signatures:**

- Tests/static checks must lock the 11 exact public signatures documented in GDC-01 ("Profile validator public signatures — fixed").
- No generic placeholder such as `<existing-domain-input>` remains anywhere in the public contract.

## 9.2 Formal protocol / vocabulary tests

Purpose: prove locally deterministic protocol behavior without silently importing provider rules.

### Sitemap core

- 50,000 URL entries boundary valid and 50,001 over-boundary emitting `sitemap_url_count_exceeds_limit` (error, GDC-01 companion-only).
- 50,000 Sitemap Index entries boundary valid and 50,001 over-boundary emitting `sitemap_index_count_exceeds_limit` (error, GDC-01 companion-only).
- uncompressed-size boundary at 52,428,800 bytes valid and the over-boundary case emitting `sitemap_document_size_exceeds_boundary` (error, GDC-01 companion-only).
- page URL `<loc>` length under the F-02 two-part contract: the source rule is "less than 2,048 characters" with no defined unit; the library policy measures UTF-8 bytes of the value as supplied (`strlen`), before URI/IRI normalization/percent-encoding. Assert 2,047 bytes are **conservatively below** the numeric character threshold under code-point/grapheme interpretations, and 2,048+ bytes emitting `sitemap_loc_length_exceeds_measure_boundary` as a `warning` GDC-01 companion diagnostic whose message/state is worded as a conservative library-policy boundary, **not** as a proven protocol violation (failing the byte boundary is not proof that the character limit is exceeded).
- a percent-encoded or normalized variant of a `<loc>` is **not** re-measured after escaping; escaping correctness is asserted separately from the length boundary.
- missing URLset `loc` (`loc === null || trim(loc) === ''`) emits only `sitemap_loc_missing` (error, `field = loc`, target `sitemap_url` + URL `entryIndex`), never also `sitemap_loc_invalid_uri_iri`.
- malformed URLset `loc` (non-empty value failing the F-02 URL/URI/IRI lexical contract) emits `sitemap_loc_invalid_uri_iri` (error, target `sitemap_url` + URL `entryIndex`).
- missing Sitemap Index child `loc` emits only `sitemap_loc_missing` (error, `field = sitemap`, target `sitemap_index_entry` + child index).
- malformed Sitemap Index child `loc` emits `sitemap_loc_invalid_uri_iri` (error, target `sitemap_index_entry` + child index).
- invalid `changefreq` (non-null/non-empty candidate outside the fixed Sitemap vocabulary) emits `sitemap_changefreq_invalid` (error, target `sitemap_url` + `entryIndex`); null `changefreq` emits nothing.
- `priority` below `0.0` emits `sitemap_priority_out_of_range` (error, target `sitemap_url` + `entryIndex`).
- `priority` above `1.0` emits `sitemap_priority_out_of_range`.
- non-finite `priority` (for example `NAN`/`INF`) emits `sitemap_priority_out_of_range`; null `priority` emits nothing.
- missing-vs-invalid precedence: absent `loc` produces no invalid-URI/IRI diagnostic and absent/non-finite `lastmod`-less entries produce no fabricated date diagnostic.
- `lastmod` target mapping: URL Sitemap entry → `sitemap_url` + `entryIndex`; Sitemap Index child → `sitemap_index_entry` + `entryIndex`; missing/null `lastmod` is a valid absence with no diagnostic.
- UTF-8/XML/entity/URI escaping remains a serialization/output test (Stack 3), not a companion-diagnostic test: no `sitemap_xml_not_utf8` / `sitemap_xml_not_escaped` code exists and the candidate validation never re-checks XML serialized output.
- invalid `lastmod` forms (year-only, year-month, hour/minute-only, zone-less, malformed) emit `sitemap_lastmod_invalid_lexical` (error, GDC-01 companion-only).
- host/submission checks use explicit document context rather than unconditional same-host constructor rejection.
- Sitemap location/scope protocol validation emits `sitemap_location_scope_violation` (error, origin `protocol`, profile `sitemaps`, GDC-01 companion-only) only when caller-supplied document/location context deterministically proves a page URL violates the applicable scope (`field = loc`), or a Sitemap Index child location violates the same-site restriction (`field = sitemap`); insufficient context emits no diagnostic and no fabricated pass/fail.
- Google verified-ownership/submission context stays a provider evidence diagnostic (`google_sitemap_host_context`) and is never used as a substitute for generic protocol location-scope validation.
- candidate `SitemapValidationDocumentDTO::$type` conflicting with the supplied entry element type is rejected as invalid invocation/context (library invalid-argument exception family), not reported as an SEO diagnostic.
- document byte size drives `sitemap_document_size_exceeds_boundary`, sourced from `SitemapValidationDocumentDTO::$uncompressedSizeBytes` and never estimated from entry count.
- UTF-8 serialization.
- XML entity escaping and URI/IRI escaping behavior.
- extended DTO parity: equivalent `SitemapUrlDTO` input produces identical `image:*`/`video:*`/`news:*`/`xhtml:link` output through `SitemapGeneratorService` and `SitemapXmlStringRenderer` after Stack 3, with namespaces declared conditionally.
- valid `lastmod`: `YYYY-MM-DD`.
- valid `lastmod`: `YYYY-MM-DDThh:mm:ssZ` and `YYYY-MM-DDThh:mm:ss±hh:mm`.
- valid `lastmod`: fractional-second dateTime with one or more fractional digits and required `Z`/offset.
- invalid `lastmod`: year-only, year-month, hour/minute-only dateTime, zone-less dateTime, and malformed/out-of-range calendar/time input.
- URL-sitemap vs Sitemap-Index `lastmod` semantic context where representable.

### RFC 9309 robots

- valid `*` product-token: no `robots_rfc9309_product_token_invalid`.
- valid identifier with only RFC identifier characters: no `robots_rfc9309_product_token_invalid`.
- identifier containing a digit (for example `User-agent: Googlebot-2`) emits `robots_rfc9309_product_token_invalid` (error, origin `protocol`, profile `rfc9309`, field `user_agent`, target `robots_rule` + exact 1-based source line); the raw candidate document remains valid validator input and no invocation exception is thrown.
- empty Allow/Disallow pattern is valid and emits no `robots_rfc9309_path_pattern_invalid`.
- ordinary non-empty `/`-started path is valid and emits no path-start diagnostic.
- non-empty leading-`*` path remains constructible/renderable for compatibility.
- leading-`*` receives `robots_rfc9309_leading_wildcard_compatibility` as a warning with origin `protocol` / profile `rfc9309`, delivered via the GDC-01 companion surface (not the legacy result, not scoring), rather than being represented as published-ABNF conformance or converted into a constructor exception; it does **not** additionally emit `robots_rfc9309_path_pattern_invalid`.
- a non-empty path that satisfies neither the accepted `/`-started case nor the special leading-`*` compatibility case emits `robots_rfc9309_path_pattern_invalid` (error, field `path`, target `robots_rule` + exact 1-based source line).
- non-empty leading-`*` additionally surfaces `robots_google_present_path_leading_slash` only under the Google profile (origin `provider`, profile `google`, GDC-01 companion-only); no silent rewrite is performed.
- raw `#` is parsed as comment semantics with **no** new diagnostic (`raw_hash_invalid` does not exist).
- percent-encoded literal special-character cases such as `%23` survive as path data (matching/path semantics, not comment semantics).
- a forbidden non-line control character inside a parsed semantic token/value emits `robots_rfc9309_control_character_invalid` (error, field `user_agent` or `path` per the parsed rule, target `robots_rule` + exact 1-based source line); normal CRLF/LF line separators are not diagnostics.
- the diagnostic target line is exact and 1-based for every RFC rule diagnostic.

### Open Graph Protocol

- four required basics.
- `og:description` remains optional at protocol-validity level; the OGP profile emits **no** missing-description conformance issue.
- legacy `missing_og_description` warning remains a legacy `SeoValidationIssueDTO` (warning, heuristic origin, `seo-default` profile) that keeps its legacy result placement and default 5-point score deduction unchanged.
- pre-existing `missing_og_title` / `missing_og_image` legacy warnings stay in the legacy result and score, with companion classification fixed as origin `protocol` / profile `ogp` per the GDC-01 Normative Legacy Classification Table (legacy classification records, `code == related_legacy_code`).
- through `validateWithCompanion()`, the classification records for `missing_og_title`, `missing_og_image`, and `missing_og_description` appear **only** when the corresponding legacy issue is actually present in the produced legacy result; no classification record is produced for an absent legacy issue, and no duplicate classification entries occur for the same legacy issue instance.
- new `missing_og_type` and `missing_og_url` are `warning`-severity protocol diagnostics (origin `protocol`, profile `ogp`, `evidence_state`/`related_legacy_code` null) delivered only through the GDC-01 companion surface: they must not appear in legacy `errors`/`warnings`/`info`/`issues`, must not change `is_valid`/`has_warnings`, and must not affect the calculated score.
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

- `priority` and `changefreq` remain protocol-valid fields but emit `google_sitemap_priority_ignored` / `google_sitemap_changefreq_ignored` (info, GDC-01 companion-only), not Google validity failures.
- Google `lastmod` accuracy is represented as `google_sitemap_lastmod_accuracy` (provider/content diagnostic) consuming caller evidence `accurate`/`inaccurate`/`unknown` rather than inferred from lexical validity; `unknown` remains a gap.
- two URL entries can carry independent `lastmod` evidence by `$urlIndex` (for example `[0] => 'inaccurate'`, `[1] => 'accurate'`) producing independent diagnostics per entry.
- `evidence['google_sitemap.host_verification']` is document/site-level scalar evidence and does **not** drive `sitemap_location_scope_violation` or any generic protocol-scope diagnostic.

### Google Image

- 1,000 images valid.
- 1,001 images emit `google_image_count_exceeds_limit` (warning, GDC-01 companion-only).
- cross-domain verification remains the evidence/context diagnostic `google_image_cross_domain_verification` rather than an offline network assertion; `unknown` remains a gap.
- two images under one URL can carry different evidence by `[urlIndex][imageIndex]` (for example `[0][0] => 'verified'`, `[0][1] => 'unverified'`) producing independent diagnostics per image.
- evidence for a non-existent child index creates no diagnostic; a missing child index maps to `unknown` where `unknown` exists.

### Google Video

Deterministic/provider-input cases:

- description under the F-02 two-part contract: measured as UTF-8 bytes via `strlen()` on the value as supplied, before XML escaping. Assert 2,048 bytes are **conservatively below** the numeric character threshold under code-point/grapheme interpretations, and 2,049+ bytes emitting `google_video_description_length_exceeds_measure_boundary` as a `warning` GDC-01 companion diagnostic worded as a conservative library-policy boundary, **not** as a proven provider violation (failing the byte boundary is not proof that the character maximum is exceeded).
- ASCII and Arabic/Unicode description inputs lock the byte-based 2,048 boundary so that `mb_strlen`/grapheme substitution cannot silently change it.
- duration: 1 accepted; 28,800 accepted; 28,801 emits `google_video_duration_out_of_range` (warning, GDC-01 companion-only).
- missing thumbnail (`thumbnailLoc === null || trim(thumbnailLoc) === ''`) emits `google_video_thumbnail_loc_missing` **only**; never also `google_video_thumbnail_loc_invalid_url`.
- malformed (non-empty, URL-shape-invalid) thumbnail emits `google_video_thumbnail_loc_invalid_url` (warning) and not the missing code.
- malformed (non-empty, URL-shape-invalid) `content_loc` emits `google_video_content_loc_invalid_url`.
- malformed (non-empty, URL-shape-invalid) `player_loc` emits `google_video_player_loc_invalid_url`.
- both `content_loc` and `player_loc` missing (null/whitespace-only) emit `google_video_content_or_player_loc_missing` (warning, GDC-01 companion-only).
- one valid media location is sufficient for the presence rule: when `content_loc` is present/valid and `player_loc` is missing, no `google_video_content_or_player_loc_missing` is emitted (and vice versa); a single present-but-malformed media location uses its URL-shape diagnostic instead.
- `content_loc == parent <loc>` / `player_loc == parent <loc>` emit `google_video_media_loc_equals_parent_loc` (warning, GDC-01 companion-only).
- both documented publication-date forms plus invalid input emitting `google_video_publication_date_invalid` (warning, GDC-01 companion-only).
- Data URL in `content_loc` emits `google_video_data_url_unsupported` with `field = content_loc`.
- Data URL in `player_loc` emits `google_video_data_url_unsupported` with `field = player_loc`.
- both `content_loc` and `player_loc` as Data URLs produce **two** `google_video_data_url_unsupported` diagnostics with the same code/target and `field = content_loc` and `field = player_loc` respectively.
- remote-fact silence: no runtime diagnostic is invented for actual remote video file type, actual thumbnail format/dimensions/stability/accessibility/transparency, actual Googlebot accessibility of referenced video resources, or watch-page/video indexing eligibility; no offline test claims a URL extension or local field proves any of those `DEFER PROVIDER EVIDENCE CONTRACT` facts.
- HTTP, HTTPS, and FTP evidence cases must preserve the documented source nuance; do not create a test whose assertion falsely claims Google literally enumerates all three in one normative sentence.
- title match (`google_video_title_host_page_match`) and `content_loc` preference (`google_video_content_loc_preference`) are recommendations/context diagnostics, not generic constructor validity failures.
- description host-page consistency is `google_video_description_host_page_match`: `matches` → info, `differs` → warning, `unknown` → info; it is distinct from `google_video_relevance_context` (topical relevance of video to page).
- missing required fields emit `google_video_title_missing`, `google_video_description_missing`, `google_video_thumbnail_loc_missing` (warning, GDC-01 companion-only).
- two videos under one URL can carry different relevance/title/description states by `[urlIndex][videoIndex]` producing independent diagnostics per video.
- video evidence targeting uses `SitemapValidationDocumentDTO::$entries[$urlIndex]->videos[$videoIndex]`; the parent page `<loc>` for a video is `$document->entries[$urlIndex]->loc`, never a context field.
- evidence for a non-existent video index creates no diagnostic; a missing child index maps to `unknown` where `unknown` exists.

Do **not** create offline tests that claim a URL extension proves the actual remote video file type, thumbnail format/dimensions/transparency, Googlebot accessibility, resource stability, or watch-page indexing eligibility. If a future context validator accepts caller-supplied evidence for those facts, test the evidence-processing contract, not the network fact itself.

### Google News

- one News entry per URL provider-valid.
- multiple News entries under one URL emit `google_news_multiple_entries_per_url` (warning, GDC-01 companion-only) while legacy list behavior remains characterized.
- 1,000 total News entries boundary valid.
- 1,001 total News entries emit `google_news_document_count_exceeds_limit` (warning, GDC-01 companion-only).
- each of the four required-field missing diagnostics is emitted for the corresponding missing value (`value === null || trim(value) === ''`): `google_news_publication_name_missing` (field `name`), `google_news_language_missing` (field `language`), `google_news_publication_date_missing` (field `publication_date`), `google_news_title_missing` (field `title`) — all warning, target `sitemap_news` + `[urlIndex][newsIndex]`.
- missing-vs-invalid date precedence: `publicationDate` missing → `google_news_publication_date_missing` only; `publicationDate` present but lexically invalid → `google_news_publication_date_invalid` only; the two never co-emit for the same item.
- missing-vs-invalid language precedence: `language` missing → `google_news_language_missing` only; `language` present but invalid → `google_news_language_invalid` only; the two never co-emit for the same item.
- all four accepted publication-date forms.
- invalid arbitrary date such as `as-provided` emits `google_news_publication_date_invalid` (warning, GDC-01 companion-only).
- valid/invalid language cases including `zh-cn` and `zh-tw` according to the explicit Google News lexical contract recorded in F-05, emitting `google_news_language_invalid` (warning, GDC-01 companion-only); do not generalize this into the deferred hreflang ISO-membership capability.
- publication-name parenthetical rule where locally decidable emits `google_news_publication_name_parenthetical` (warning, GDC-01 companion-only).
- title-content evidence: caller evidence `conforming` → `google_news_title_content_evidence` with severity `info`; `nonconforming` → `warning`; `unknown`/missing evidence → `info` (gap); no local inference from the title string alone.
- missing title → `google_news_title_missing` **only**; no title-content evidence diagnostic is emitted for that item even when evidence is supplied.
- legacy optional fields (`access`, `genres`, `keywords`, `stockTickers`) produce **no** runtime diagnostic in any state.
- freshness state `within_window` is processed as caller-supplied provider evidence (`google_news_freshness_evidence`, info) without local age arithmetic.
- freshness state `outside_window` is processed deterministically as provider-out-of-window evidence (`google_news_freshness_evidence`, warning).
- freshness state `unknown` remains an evidence gap/diagnostic (`google_news_freshness_evidence`, info) and is not fabricated into a pass or failure.
- lexical `publicationDate` plus a reference clock must not trigger an invented `48 hours`, calendar-day, or timezone boundary calculation; no hidden `now()`.
- original-publication-time/name/title truth remains content/context evidence (`google_news_original_publication_evidence`, `google_news_name_exact_match_evidence`), not fake lexical proof.
- two News entries can carry different freshness/original/name/title-content evidence by `[urlIndex][newsIndex]` producing independent diagnostics per entry.
- evidence for a non-existent News index creates no diagnostic; a missing child index maps to `unknown` where `unknown` exists.

### Google robots.txt

- 500 KiB document boundary classification: a robots.txt of 512,000 bytes or fewer is within the parse limit; a robots.txt exceeding 512,000 bytes emits `robots_google_document_size_exceeds_parse_limit` (warning, GDC-01 companion-only).
- raw document not valid UTF-8 emits `robots_google_document_invalid_utf8` (warning, provider, field `document`, target `robots_document`); the 500 KiB diagnostic remains independent and both may coexist for the same document.
- a fully-qualified `Sitemap:` value containing a raw Unicode path is accepted by the provider validator (no `FILTER_VALIDATE_URL` rejection of Unicode).
- a relative `Sitemap:` value emits `robots_google_sitemap_url_not_fully_qualified` (warning, provider, field `sitemap`, target `robots_rule` + exact 1-based source line); present-but-not-fully-qualified and absent are distinct (absent emits nothing for this code).
- a cross-host fully-qualified `Sitemap:` URL is accepted with no diagnostic.
- multiple `Sitemap:` directives are accepted with no count-limit diagnostic.
- Google Allow/Disallow present-path leading `/` cases.
- non-empty leading-`*` remains generic-compatible but is distinguishable as the Google provider-path diagnostic `robots_google_present_path_leading_slash` (GDC-01 companion-only); no silent rewrite.
- `Sitemap:` fully-qualified URL.
- Sitemap field independence from user-agent groups.
- **no MIME/content-type diagnostic exists** (`DEFER TRANSPORT CONTEXT`, outside Stacks 0–8); no transport context/evidence key is invented.

### Structured Robots Lane (hard input-safety contract)

- embedded CR/LF/control-character injection through structured DTO fields — user-agent values, Allow/Disallow values, rule comments, top-level robots comments, and Sitemap directive values — **throws `Maatify\Seo\Exception\SeoInvalidArgumentException`**.
- no silent sanitization (no trimming/escaping/rewriting of the injected content) and no silent truncation.
- no companion diagnostic is emitted in place of the exception; the hard-safety guarantee is invocation/constructor behavior, not a diagnostic.

### Google robots meta

- `max-snippet:-1`.
- `max-video-preview:-1`.
- values below `-1` invalid for those helpers.
- `indexifembedded` helper emits `robots_meta_indexifembedded_without_noindex` (warning, origin `provider`, profile `google`, GDC-01 companion-only) when `noindex` is absent; raw representation remains possible and no builder construction error occurs.
- `unavailable_after` empty/missing provider value is locally diagnosable as `robots_meta_unavailable_after_missing` (warning, GDC-01 companion-only).
- non-empty `unavailable_after` with caller evidence `recognized` is processed deterministically as recognized, emitting `robots_meta_unavailable_after_recognizability` with severity `info`.
- non-empty `unavailable_after` with caller evidence `unrecognized` is processed deterministically as unrecognized, emitting `robots_meta_unavailable_after_recognizability` with severity `warning`.
- non-empty `unavailable_after` with evidence `unknown` remains an evidence gap emitting `robots_meta_unavailable_after_recognizability` with severity `info`, rather than being parsed through an invented exhaustive grammar.
- no hidden network/clock/locale behavior is used to infer recognizability.
- none of the robots-meta diagnostics above appears in the legacy result or affects scoring (GDC-01).

### Hreflang

- `en`.
- `en-US`.
- `zh-Hant`.
- `zh-Hans-US`.
- `x-default` is valid and emits no `hreflang_tag_invalid_syntax`.
- equivalent conventional-casing normalization through Web and Sitemap entry points.
- casing differences alone (for example `en-US` vs `en-us`) emit **no** diagnostic; no `hreflang_noncanonical_case` (or equivalent) code exists — tests may assert normalization equivalence only.
- no test claims that an unversioned local regex/list proves ISO 639-1 / ISO 3166-1 / ISO 15924 membership; `hreflang_tag_invalid_syntax` asserts syntax only.
- reciprocal cluster (no diagnostics).
- missing self-reference emits `hreflang_self_reference_missing` (warning, GDC-01 companion-only).
- missing return link emits `hreflang_reciprocal_link_missing` (warning, GDC-01 companion-only).
- inconsistent alternate set across supplied localized URLs emits `hreflang_alternate_set_inconsistent` (warning, GDC-01 companion-only).
- alternate URL fully qualified and non-empty → no `hreflang_url_not_fully_qualified`.
- alternate URL `null` → `hreflang_url_not_fully_qualified`.
- alternate URL whitespace/empty → `hreflang_url_not_fully_qualified`.
- alternate URL relative → `hreflang_url_not_fully_qualified`.
- alternate URL malformed (non-empty, not fully qualified under the fixed structural contract) → `hreflang_url_not_fully_qualified`.
- every malformed/missing/relative URL case emits the single code `hreflang_url_not_fully_qualified` with exactly one diagnostic per link (no separate missing/invalid/relative codes).
- candidate `hreflang` `null` → `hreflang_tag_invalid_syntax`.
- candidate `hreflang` whitespace-only → `hreflang_tag_invalid_syntax`.
- candidate `hreflang` malformed language tag → `hreflang_tag_invalid_syntax`.
- structurally invalid links (tag-syntax and/or URL code emitted) are excluded from cluster-edge computation: no reciprocity/self-reference/alternate-set cluster diagnostic is derived from them, and no synthetic cascade cluster diagnostic is added solely because they were excluded.
- cluster tests supply the fixed candidate input `Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationClusterDTO` (list of `HreflangValidationPageDTO` pages with unique non-empty `pageUrl` and `HreflangValidationLinkDTO` links); the cluster is the primary input and is never supplied through any context field.

### Canonical

- relative canonical output remains generic-compatible and identical to current behavior.
- a relative canonical produces `canonical_relative_provider_best_practice` (warning, origin `provider`, profile `google`, field `href`) through the GDC-01 companion surface; it never throws and never changes the legacy result or score.

### Legacy surface and fixed public signatures

- exact legacy signature locked as `SeoMetaValidator::validate(array|object $meta, array $options = []): SeoValidationResultDTO`.
- additive signature locked as `SeoMetaValidator::validateWithCompanion(array|object $meta, array $options = [], ?SeoValidationContextDTO $context = null): SeoCompanionValidationResultDTO`; parameter order `$meta`, `$options`, `$context` is asserted, no overload variant exists, and `$options` is not dropped.
- the 11 profile-validator public signatures (RFC 9309, Google robots.txt, Google robots meta, Sitemap protocol, Google base Sitemap, Google Image, Google Video, Google News, Open Graph, Google Canonical, Google Hreflang Cluster) are locked by tests/static checks; no `<existing-domain-input>` placeholder remains.
- a legacy classification record is emitted exactly once per matching present legacy issue with `code == related_legacy_code`, and changes neither the legacy result, the score, nor the warning/error counts; no record is emitted when the underlying legacy issue is absent.

### Global diagnostics contract (GDC-01)

Tightest contract coverage across Stacks 2/4/5/6 — asserted for every new diagnostic (Robots, Sitemap, OGP, canonical, hreflang):

- the legacy `SeoValidationResultDTO` serialized shape (`is_valid`, `has_warnings`, `errors`, `warnings`, `info`, `issues`) is byte-stable when only new companion diagnostics are present.
- `is_valid` is `true` and `has_warnings` is `false` in the legacy result even when a new `warning`-severity companion diagnostic is present.
- `SeoValidationScoreCalculator` output (score, grade, counts, `is_healthy`) is unchanged by the presence of new companion diagnostics.
- each new diagnostic carries the fixed origin/profile/evidence-state metadata and a stable, non-colliding code.
- **no new diagnostic reuses or shadows a legacy issue code.** A **legacy classification record** is the sole exception: it intentionally uses the same `code` and `related_legacy_code` as the legacy issue it classifies, per the GDC-01 Normative Legacy Classification Table, and produces no additional legacy/scoring effect.
- every profile validator returns the unified `SeoCompanionValidationResultDTO`; no per-profile result type or alternative container exists.
- the unified type's JSON shape matches the GDC-01 serialization contract: snake_case keys, the exact legacy `SeoValidationResultDTO` shape embedded under `legacy` when paired (with `null` for standalone runs except the OGP-paired profile, which always embeds it), and the fixed entry keys `code`/`severity`/`message`/`field`/`origin`/`profile`/`evidence_state`/`related_legacy_code`/`target`.
- correlation assertions: a companion record's `related_legacy_code` references the legacy code for the same subject (for example `missing_og_description`), and the embedded `legacy` object is bit-for-bit equal to the standalone `SeoMetaValidator::validate()` result for the same input.
- `SeoCompanionValidationResultDTO` exposes no `is_valid`/`has_warnings`/score/grade/health fields; derived aggregates never feed `SeoValidationScoreCalculator`.
- construction guards: empty `code`/`message`, unknown `severity`/`origin`/`profile`, and an `evidence_state` not in the code's fixed state vocabulary each throw a construction error; a severity that does not match the fixed code severity (ordinary diagnostics) or the `(code, evidence_state)` mapping (evidence-state diagnostics) also throws.
- the unified evidence-only context DTO (`SeoValidationContextDTO` with `$evidence` only) is the only accepted context surface for companion/profile validation; a validator reads only its authorized evidence keys, missing evidence maps to `unknown` only where the contract fixes an `unknown` state, arbitrary evidence states are rejected, and no diagnostic is produced from unauthorized/unknown keys.
- `SeoValidationContextDTO` constructor guards: recognized `$evidence` keys enforce the fixed value shape and allowed states; negative/non-integer indices in indexed evidence and invalid recognized values are construction errors; there is no `$documentContext` field; unknown top-level keys are ignored and produce no diagnostics; no extension registry exists.
- every companion entry serializer includes the required `SeoDiagnosticTargetDTO` (`scope`, `entry_index`, `item_index`, `line`) matching the fix `target` shapes; an invalid target shape is a construction error.
- every code in the GDC-01 machine-contract tables and the fixed F-13 OGP contracts is asserted at least once on its fixed severity/origin/profile/field and (where applicable) evidence-state contract.

### Validation candidate layer tests (FIX 28)

The test strategy must prove the new architecture, not just its names.

**Strict DTO preservation (characterization):** characterization tests prove the existing strict domain/render DTOs stay strict:
- `SitemapVideoDTO` continues to reject the cases it rejects today (empty thumbnail, empty title, empty description, both media locations missing, non-positive duration, invalid publication date).
- `SitemapUrlDTO` continues to reject malformed `loc` and currently-invalid `lastmod` per its current contract, until the explicit F-02 remediation corrects only what it adopted.
- `HreflangLinkDTO` remains strict (non-empty hreflang, absolute URL, normalization).
- `RobotsRuleDTO` remains strict (empty Allow/Disallow paths rejected).
- Candidate DTOs must not change any of the above; constructing a candidate does not relax the strict DTO contract.

**Candidate representability:** tests prove the candidate inputs can carry each of the following **without a constructor protocol/provider exception**:
- malformed Sitemap `lastmod`;
- empty/missing Video title;
- empty/missing Video description;
- missing thumbnail;
- both content/player missing;
- duration `0`;
- negative duration;
- over-range duration;
- malformed `publicationDate`;
- malformed/relative hreflang URL;
- raw robots path states.

**Validator reachability:** prove that a state that cannot be built through the strict DTO can be built through the candidate input and then produces the machine diagnostic locked in this audit. Mandatory examples:
- Candidate Video `title = null` → `google_video_title_missing` (not a constructor exception).
- Candidate Video `contentLoc = null` and `playerLoc = null` → `google_video_content_or_player_loc_missing` (not a constructor exception).

**Diagnostic target:** tests cover the `SeoDiagnosticTargetDTO` JSON shape, and cases such as URL 0 / Video 0, URL 0 / Video 1, and URL 1 / Video 0. The same code may appear more than once with different targets; item identity is never derived from `message`.

**Robots target:** a raw robots path diagnostic carries a 1-based source `line`; the document-size diagnostic carries `robots_document` with no line.

**Sitemap target:**
- document count/size → `sitemap_document`;
- URL issue → `sitemap_url` + `entryIndex`;
- Index child issue → `sitemap_index_entry` + `entryIndex`;
- Image → parent URL + image index;
- Video → parent URL + video index;
- News → parent URL + news index.

**Hreflang target:**
- cluster/page issue → page index;
- individual alternate issue → page index + link index.

### Open Graph activation and pairing tests (FIX 10–12, 26)

- No OGP presence signal → **no** OGP companion diagnostics and **no** OGP legacy classification records; fully absent OGP is not a protocol error.
- OGP presence + missing title → legacy `missing_og_title` + one matching classification record.
- OGP presence + missing image → legacy issue + one matching classification record.
- Missing description stays a heuristic legacy classification record only.
- Missing type/url → the new companion diagnostics `missing_og_type` / `missing_og_url`, and nothing duplicate such as `ogp_missing_title` / `ogp_missing_image`.
- `OpenGraphProtocolValidator::validate()` embeds the exact legacy result (`legacy === SeoMetaValidator::validate($meta, $options)`).
- `SeoMetaValidator::validateWithCompanion()` produces the same observable companion result as the OGP validator for the same inputs.
- Custom `$options` are preserved into the embedded legacy validation.

### Cross-submission authority tests (FIX 19–22)

- URLset entry outside the normal scope inferred from `SitemapValidationDocumentDTO::$location`:
  - `authorized` → no scope error;
  - `unauthorized` → `sitemap_location_scope_violation` (error, protocol, `sitemaps`, field `loc`, target `sitemap_url` + URL index);
  - `unknown` → no fabricated error;
  - missing evidence → same `unknown` boundary behavior.
- Sitemap Index: authority evidence is ignored; a deterministic same-site violation still emits the protocol error (field `sitemap`, target `sitemap_index_entry` + child index).
- Google verification evidence must not affect any of the above.

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

`validateWithCompanion()` must pair the identical legacy result without altering its serialization or score; legacy classification records must not duplicate, reorder, or change the counts of the underlying legacy issues, and must not change `is_valid` / `has_warnings`.

Applying the measurement contract must not change any non-length validation (URL shape, URI/IRI escaping, `lastmod` lexical forms), must not rebase the boundary to a different unit, and must not re-word the over-boundary diagnostics (`sitemap_loc_length_exceeds_measure_boundary`, `google_video_description_length_exceeds_measure_boundary`) as proven protocol/provider character-limit violations.

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
- **GDC-01 global diagnostics contract:** every new protocol/provider/context diagnostic introduced by Stacks 2/4/5/6 is delivered through the companion surface (`SeoCompanionDiagnosticDTO` entries in the unified `SeoCompanionValidationResultDTO`) and must not mutate the legacy result, `is_valid`, `errors`/`warnings`/`info`/`issues`, or `SeoValidationScoreCalculator`. The public access contract (unified result type, entry fields, legacy correlation, serialization shape, profile surface) and the fixed machine contracts (code/severity/origin/profile/field/evidence state) are part of the execution authority; an implementation may not add or rename codes, severities, origins, profiles, fields, evidence states, or result types. Pre-existing legacy issues keep their documented F-12 placement and score behavior. This is part of the execution authority, not an implementation preference.
- **Fixed public signatures:** the legacy `SeoMetaValidator::validate(array|object $meta, array $options = []): SeoValidationResultDTO` signature is preserved letter-for-letter; the additive `SeoMetaValidator::validateWithCompanion(array|object $meta, array $options = [], ?SeoValidationContextDTO $context = null): SeoCompanionValidationResultDTO` and the fixed `final readonly` evidence-only `SeoValidationContextDTO` (`$evidence`) are part of the execution authority. The 11 profile-validator public signatures fixed in GDC-01 (Sitemap/Robots/Hreflang using the validation candidate types) may not be renamed, reordered, overloaded, or replaced by a generic placeholder (`<existing-domain-input>`).
- **Layered input model:** strict domain/rendering DTOs remain strict and unchanged; validation candidate DTOs are a separate, additive validation-only layer; Sitemap/Robots/Hreflang profile signatures use the candidate types fixed here; no implementation may substitute existing strict DTO inputs when doing so makes a required diagnostic unreachable; candidate constructors perform representation/shape only, never protocol/provider validation; no public candidate-to-domain conversion contract is added; Stack 3 (rendering) never consumes candidate inputs and Stack 4 never relaxes strict constructors.
- **Evidence targeting:** Sitemap child-collection evidence is indexed by zero-based `$urlIndex` / `$childIndex` over `SitemapValidationDocumentDTO::$entries` exactly as fixed in GDC-01 and must match `SeoDiagnosticTargetDTO`; there is **no** `documentContext`; document location/type/byte-size live on `SitemapValidationDocumentDTO`. The legacy classification records fixed in the GDC-01 Normative Legacy Classification Table are metadata records, **not** new diagnostics: they are the sole exception to the no-reuse rule (`code == related_legacy_code`), always target `scope = meta`, and produce no legacy, scoring, or validity effect.
- **Machine targets:** every companion diagnostic carries the required machine-readable `SeoDiagnosticTargetDTO`; the human-readable `message` or `field` is never used as target identity.
- **Cross-submission authority:** protocol cross-submission (`sitemaps.cross_submission_authority`, consumed only by `SitemapProtocolValidator`) and Google Search Console verification (`google_sitemap.host_verification`, consumed only by `GoogleSitemapValidator`) are separate evidence domains that never substitute for each other; Sitemap Index never consumes cross-submission authority evidence.
- **OGP exception:** `OpenGraphProtocolValidator` is the explicit legacy-paired profile exception (always embeds `SeoMetaValidator::validate($meta, $options)`), is presence-triggered, and `validateWithCompanion()` shares the single OGP rule implementation.
- **Measurement policy:** the source-backed rules for Sitemap `<loc>` (< 2,048 characters) and Google Video `description` (<= 2,048 characters) are unit-undefined; the audit converts them under F-02 to an explicit EVIDENCE BOUNDARY with a conservative library policy of UTF-8 bytes via `strlen()` on the value as supplied. A value below the byte boundary is conservative proof that it is also below the same numeric threshold under Unicode code-point or grapheme counting, because the UTF-8 byte count is never smaller than those counts for valid UTF-8 text; a value at/above the byte boundary emits the fixed warning codes and must never be labeled a proven protocol/provider violation, because a multi-byte value may exceed the byte threshold while remaining below the same character-count threshold. Stack 4 must not substitute another unit and must not restate the diagnostic as source-proven.
- **Unknown-decision gate:** a material `unknown / needs decision` discovered by characterization blocks the affected production change until an approved contract amendment resolves it.
- **Future-contract boundaries:** hreflang ISO membership registries, complete Google structured-data capability/eligibility profiles, Twitter/X provider conformance, and the F-18 lexical/enumeration expansion are not implementation discretion under this audit. They are separate future contracts.
- **Complete runtime registry is the scope (AP-16):** the complete normative machine-contract registry for this remediation (all Stack 2/4/6 media codes, Video Video/News codes, robots RFC/Google codes, hreflang codes, OGP codes, plus the Normative Legacy Classification records) is the **exhaustive** runtime diagnostic scope. No validator, evidence consumer, or profile may emit a code outside this registry, and no strict validator/settings flag may substitute a non-companion validity-exception path for a registry diagnostic. A strict robots DTO is never a reason to drop a registry code into a "settings" channel, and no comparable hard-invalidation code exists where the registry fixes a missing/invalid diagnostic. No new evidence key may be introduced beyond `google_news.title_content_conformance`; all other keys remain exactly as fixed.
- **Representability ≠ authorization (AP-16):** candidate DTO representability does not by itself authorize a diagnostic. Every remediation rule required by a Stack has **exactly one** observable disposition — companion diagnostic, legacy classification, strict invocation/constructor rule, parser/serialization guarantee, or deferred/no-runtime-diagnostic — as classified in GDC-01, the Stack dispositions, AP-16, and ADR-18. Implementers and maintainers must not infer a new diagnostic from prose alone.
- **Anti-cascade precedence (ADR-18):** missing-vs-invalid precedence is mandatory: a field with a dedicated missing diagnostic that is missing emits the missing diagnostic and never also a malformed/lexical/URL diagnostic for the same absence. General Sitemap-vs-specific diagnostics remain independent and non-cascading.
- **Hreflang invalid-link exclusion (ADR-18):** a candidate link carrying `hreflang_tag_invalid_syntax` or `hreflang_url_not_fully_qualified` may produce its own link-level diagnostic but is excluded from cluster-edge computation (reciprocity, self-reference matching, alternate-set equality); cluster diagnostics come from structurally usable links only, and no cascade diagnostic is added because an invalid link was excluded.
- **Video remote facts deferred (`DEFER PROVIDER EVIDENCE CONTRACT`):** no runtime diagnostic for actual remote video file type, thumbnail format/dimensions/stability/accessibility/transparency, Googlebot accessibility, or indexing eligibility; those facts may only be processed under a separately approved evidence contract.
- **Robots MIME/transport deferred (`DEFER TRANSPORT CONTEXT`):** no robotstxt MIME/content-type diagnostic exists and no transport context/evidence key is invented; this is outside Stacks 0–8.
- **Structured Robots Lane hard-safety (strict invocation/constructor rule):** CR/LF/control-character injection through structured robots DTO values (user agents, paths, rule comments, top-level comments, Sitemap values) throws `Maatify\Seo\Exception\SeoInvalidArgumentException`; no silent sanitize/truncate and no companion diagnostic in place of the exception.
- **Google Image scope freeze:** no Google Image missing/URL-shape diagnostic is added by this remediation; the F-03 approved Image machine scope remains the count diagnostic, the two evidence diagnostics, and the legacy-field compatibility classification. Candidate representability does not expand it.
- **News title evidence key (fixed):** `google_news.title_content_conformance` is the only new evidence key and is consumed only by `GoogleNewsSitemapValidator`, emitted as `google_news_title_content_evidence` (`conforming` → info, `nonconforming` → warning, `unknown` → info); a missing `title` emits `google_news_title_missing` only, with no title-content evidence for that item.

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
