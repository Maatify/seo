# Stack 0 — Contract Characterization Inventory

Status: complete for the reviewed Draft HEAD.

Repository: `Maatify/seo`  
Source/umbrella branch: `integration/seo-architecture-remediation`  
Stack branch: `stack/0-contract-characterization`  
Source HEAD at branch creation: `979f2d7fff0e63cf8d53a6d49f58576261fb82c9`

## Purpose and boundary

This inventory records observable contracts before the later remediation stacks. Stack 0 adds characterization tests and this permanent inventory only. It does not refactor production code, change `src/`, unify serializers, alter robots or OGP policy, add companion diagnostics, change public signatures, or implement any later-stack class.

The classifications used here are intentionally limited to:

- `preserve` — current behavior is a compatibility contract for the later work.
- `intentionally change` — the audit already fixes a later correction; Stack 0 records the current behavior without applying that correction.
- `unknown / needs decision` — the behavior is material, but the audit does not authorize a production outcome. This is a gate, not permission to change it. It may affect API behavior, serialized output, validation, score, standards/provider semantics, compatibility, or scope.

An existing test is evidence; the new Stack 0 test is added only where the existing suite did not provide one focused characterization of the requested contract.

## Required coverage

The table below is the complete Stack 0 Required Coverage inventory. Every row describes one observable behavior and has exactly one classification. Existing historical tests are referenced where they already provide sufficient evidence; the additive Stack 0 test covers only the remaining gaps.

| Surface / observable behavior | Classification | Current evidence | Audit decision / later boundary |
|---|---|---|---|
| Base `SitemapUrlDTO` XML: exact XML declaration, base namespace, element order, date/frequency/priority formatting | `preserve` | [Phase 7E tests](../../tests/Phase7ESitemapXmlStringRendererTest.php#L68-L112) | F-01 requires equivalent core-only output while public entry points remain callable. |
| Extended `SitemapUrlDTO` through `SitemapXmlStringRenderer`: alternates, images, videos, News, conditional namespaces, exact order | `preserve` | [Phase 10C](../../tests/Phase10CImageSitemapXmlStringRendererTest.php#L55-L124), [Phase 10D](../../tests/Phase10DVideoSitemapXmlStringRendererTest.php#L55-L125), [Phase 10E](../../tests/Phase10ENewsSitemapXmlStringRendererTest.php#L55-L150), plus the [Stack 0 cross-surface test](../../tests/Stack0ContractCharacterizationTest.php#L148-L166) | F-01 lines 236–249 establish the later canonical full-element output and namespace behavior. |
| Extended `SitemapUrlDTO` through `SitemapGeneratorService`: `images`, `videos`, and `news` are silently omitted while alternates remain | `intentionally change` | [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L168-L172) | F-01 lines 236–243 explicitly identify the drop as the intentional correction for later remediation. It is not changed in Stack 0. |
| `SitemapXmlStringRenderer` raw-array normalization, list-shaped child rejection, optional-field normalization, and exception behavior | `preserve` | [Phase 7E](../../tests/Phase7ESitemapXmlStringRendererTest.php#L76-L126), [Phase 10A](../../tests/Phase10ASitemapIndexXmlStringRendererTest.php#L60-L167), [Phase 10C](../../tests/Phase10CImageSitemapXmlStringRendererTest.php#L82-L124), [Phase 10D](../../tests/Phase10DVideoSitemapXmlStringRendererTest.php#L82-L125), [Phase 10E](../../tests/Phase10ENewsSitemapXmlStringRendererTest.php#L82-L150) | F-01 lines 244–247 preserve raw-array support and the typed-only service input boundary, except for explicitly audited corrections. |
| `SitemapIndexXmlStringRenderer`: typed Web index DTO and raw associative entry support, exact XML, normalization, and current exceptions | `preserve` | [Phase 10A](../../tests/Phase10ASitemapIndexXmlStringRendererTest.php#L60-L167), [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L174-L205) | F-01 preserves the public renderer and its raw-array input boundary; F-02 preserves the current strict DTO boundary while later validation is additive. |
| Two `SitemapIndexEntryDTO` namespace contracts: valid serialization and distinct invalid-URL exception messages | `preserve` | [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L174-L184); renderer baseline in [Phase 10A](../../tests/Phase10ASitemapIndexXmlStringRendererTest.php#L60-L167) | F-01 records both public DTOs and forbids destructive namespace/caller changes. |
| Fractional-second Sitemap `lastmod` is currently rejected by the URL DTO, both index DTOs, and raw index rendering | `intentionally change` | [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L200-L205) | F-02 lines 284–315 fix the later accepted lexical forms, including fractional seconds. Current rejection is recorded as an implementation limitation only. |
| Multiple News entries remain representable and serialize in input order | `preserve` | [Phase 10E](../../tests/Phase10ENewsSitemapXmlStringRendererTest.php#L99-L150) | F-05 lines 600–670 preserve the public list API; provider cardinality is a later companion diagnostic, not a Stack 0 constructor change. |
| `RobotsRuleDTO` rejects empty Allow/Disallow patterns but accepts non-empty leading-wildcard paths such as `*` and `*/private` | `preserve` | [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L207-L214); renderer behavior in [Phase 9A](../../tests/Phase9ARobotsTxtRendererTest.php#L50-L125) | F-06 lines 686–694 and its compatibility policy preserve the strict constructor boundary and fixed leading-wildcard compatibility behavior. |
| `RobotsTxtDTO` accepts ASCII Sitemap URLs and currently rejects Unicode host/path values through `FILTER_VALIDATE_URL` | `preserve` | [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L212-L214) | The strict DTO contract, including ASCII-only rejection, remains unchanged. Stack 2 adds separate raw candidate/provider validation for the wider Google Sitemap profile; that later boundary does not change this strict DTO contract. |
| `RobotsTxtRenderer`: comments, rule grouping, Crawl-delay placement, Allow-before-Disallow order, and Sitemap output | `preserve` | [Phase 9A](../../tests/Phase9ARobotsTxtRendererTest.php#L50-L199) | F-06 preserves existing rendering compatibility while RFC/provider behavior is separated into later validation/profile surfaces. |
| Robots directive order, duplicate removal, exclusivity, and prefix replacement | `preserve` | [Batch 1A](../../tests/Batch1AMetaRobotsBuilderTest.php#L44-L160) | Audit non-regression requirements at lines 3947–3966 preserve order, replacement, duplicate removal, and escaping. |
| OpenGraph scalar order; multiple `og:image` roots; each root immediately followed by its structured properties; first tag preference | `preserve` | [Phase 14B](../../tests/Phase14BOpenGraphBuilderTest.php#L69-L153) | F-13 lines 2435–2442 and non-regression lines 3960–3966 make ordering and root/property attachment explicit. |
| Legacy `missing_og_title`, `missing_og_description`, and `missing_og_image` warnings: codes, warning severity, and five-point default score participation | `preserve` | [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L216-L225); baseline issue/score coverage in [Phase 11A](../../tests/Phase11ASeoValidationHelpersTest.php#L93-L165) and [Phase 11B](../../tests/Phase11BSeoValidationScoreHelpersTest.php#L63-L153) | F-12 and F-13 explicitly keep these issues in the legacy result and score; only new OGP `type`/`url` diagnostics are companion-only later. |
| Relative and absolute canonical output | `preserve` | [Phase 15A](../../tests/Phase15ACanonicalUrlBuilderTest.php#L34-L108) | F-14 lines 2540–2560 and validation acceptance lines 3843–3846 keep relative builder output generic-compatible; any provider warning is later and off the legacy score. |
| `SeoValidationPreset`: minimal/standard/strict option payloads, independent copies, named lookup, invalid-name rejection, and composition with validator/report builder | `preserve` | [Phase 11D](../../tests/Phase11DSeoValidationPresetsTest.php#L55-L127) | F-12 preserves legacy validation/score options; presets remain compatibility helpers and do not authorize new provider diagnostics. |
| `SeoValidationScoreCalculator`: default/custom penalties, deduction records, score clamping, grades, counts, and health threshold | `preserve` | [Phase 11B](../../tests/Phase11BSeoValidationScoreHelpersTest.php#L63-L155), [Stack 0 OGP score characterization](../../tests/Stack0ContractCharacterizationTest.php#L216-L225) | F-12/GDC-01 preserve legacy score propagation; companion diagnostics remain off-score. |
| `SeoValidationReportBuilder`: validation plus score composition, pass/warning/fail summaries, context preservation, non-mutation, and option errors | `preserve` | [Phase 11C](../../tests/Phase11CSeoValidationReportHelpersTest.php#L50-L137) | F-12 preserves the legacy report/result/score boundary and report serialization. |
| `SeoValidationBatchReportBuilder`: batch counts, aggregate score/health, item/shared context merge, report serialization, input immutability, and invalid-item rejection | `preserve` | [Phase 11F](../../tests/Phase11FSeoValidationBatchReportHelpersTest.php#L55-L187) | F-12 preserves legacy batch aggregation and serialized output; new companion diagnostics do not enter legacy aggregates. |
| Legacy validation issue codes, severity partitioning, and structured-data scoped property-range issue | `preserve` | [Phase 11A](../../tests/Phase11ASeoValidationHelpersTest.php#L93-L165), [Phase 13P](../../tests/Phase13PJsonLdStructuralValidationTest.php), [Phase 21 gate](../../tests/Phase21StructuredDataCiValidationTest.php#L219-L245) | F-12, F-16, and F-18 preserve the legacy result boundary and scoped semantic validation. |
| `SeoValidationIssueDTO` and `SeoValidationResultDTO` serialized shapes, including `errors`, `warnings`, `info`, and `issues` | `preserve` | [Stack 0 full-shape characterization](../../tests/Stack0ContractCharacterizationTest.php#L240-L258); existing shape checks in [Phase 11A](../../tests/Phase11ASeoValidationHelpersTest.php#L140-L165) | F-12/GDC-01 and non-regression lines 3964–3968 require byte-stable legacy payloads. |
| `strlen()` byte-length behavior at ASCII and Arabic/Unicode title/description boundaries; length issues remain warnings with five-point deductions | `preserve` | [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L227-L238) | F-12 and the measurement policy at lines 3962–3970 and 4078 preserve byte thresholds, warning codes/severity, score deductions, and legacy payloads. |
| Exact public signature `SeoMetaValidator::validate(array|object $meta, array $options = []): SeoValidationResultDTO`; `MetaTagsDTO` object branch | `preserve` | [Stack 0 reflection/object characterization](../../tests/Stack0ContractCharacterizationTest.php#L260-L269); object coverage also in [Phase 11A](../../tests/Phase11ASeoValidationHelpersTest.php#L146-L158) | Fixed signature is stated at audit lines 3848–3853 and 4071–4073. |
| `MetaGeneratorService` default trimming, missing-override fallback, override precedence, canonical precedence, and OG/Twitter field copying | `unknown / needs decision` | [Stack 0 characterization](../../tests/Stack0ContractCharacterizationTest.php#L271-L315) | No explicit remediation decision in the audit fixes these material API/serialized-output semantics. No production change is authorized until an approved contract amendment resolves them. |
| `SocialImage` optional fields and OpenGraph builder’s exposed image surface | `preserve` | [Phase 14A](../../tests/Phase14ASocialMetaFoundationTest.php#L65-L97), [Phase 14B](../../tests/Phase14BOpenGraphBuilderTest.php#L69-L153) | F-13 lines 2403–2442 fixes the exposed surface and preserves structured-property attachment/order; `og:image:alt` remains optional/recommendation-level. |
| Hreflang link normalization/strict absolute URL behavior and builder duplicate/replace semantics | `preserve` | [Batch 3](../../tests/Batch3HreflangHeadLinkBuilderTest.php#L41-L92) | F-15 keeps existing strict/build APIs, adds a separate candidate/profile layer later, and explicitly defers a versioned ISO membership registry. |

## Existing versus added evidence

Existing evidence already covered the broad XML, robots directive, OGP image ordering, canonical, hreflang, validation, scoring, and structured-data behavior. The new Stack 0 test adds the cross-surface and uncovered evidence required for this stack:

- exact extended output comparison between the renderer and generator, including the known generator drop;
- both Sitemap Index DTO namespace/exception contracts;
- fractional-second rejection at every current Sitemap Index/URL entry point;
- leading-wildcard robots paths and ASCII-only Sitemap URL rejection;
- all three legacy OGP warning codes, severity, and score deductions;
- ASCII versus Arabic byte-length boundaries;
- complete legacy issue/result serialized shapes;
- reflection evidence for the exact validator signature and the `MetaTagsDTO` object branch;
- direct `MetaGeneratorService` characterization using repository and host URL fakes.

The test is additive and standalone, matching the repository’s existing direct-PHP test style. It does not duplicate the already clear multiple-News, MetaRobots order/replacement, OGP image ordering, canonical, hreflang, or structured-data tests beyond referencing them here.

## Unknown-decision gate

The only material `unknown / needs decision` recorded in this Stack 0 inventory is the current `MetaGeneratorService` output contract. It affects public API behavior and the serialized values copied into `MetaTagsDTO`, including canonical and social fields. Later production work must not reinterpret or refactor this behavior until the audit is amended or an approved contract decision is recorded.

The Unicode `RobotsTxtDTO` result is intentionally split by layer: the strict existing DTO rejection is preserved, while the audit has already fixed a later additive provider-profile correction. This is not an unresolved Stack 0 decision.

## Verification snapshot

Baseline gates passed before Stack 0 changes:

- `composer validate --strict` — pass (`./composer.json is valid`)
- `vendor/bin/phpstan analyse` — pass (`[OK] No errors`)
- `php tests/Phase21StructuredDataCiValidationTest.php` — pass
- `find tests -name '*Test.php' -print0 | xargs -0 -n1 php` — pass for the baseline suite

Post-review gates passed after the characterization corrections:

- `composer validate --strict` — pass (`./composer.json is valid`)
- `vendor/bin/phpstan analyse` — pass (`[OK] No errors`)
- `php tests/Phase21StructuredDataCiValidationTest.php` — pass (`Phase 21 WU2 structured-data validation gate passed.`)
- `php tests/Stack0ContractCharacterizationTest.php` — pass (`Stack 0 contract characterization tests passed.`)
- `find tests -name '*Test.php' -print0 | xargs -0 -n1 php` — pass; all repository tests passed, including Stack 0
- `php -l tests/Stack0ContractCharacterizationTest.php` — pass
- Markdown line-anchor audit — pass; every inventory anchor is in range for its referenced test file
- `git diff --check` — pass

## Scope guard

Stack 0 changes are limited to this inventory and [the additive characterization test](../../tests/Stack0ContractCharacterizationTest.php). `src/` is unchanged. No later remediation, release/tag/version change, merge, or production behavior correction is included.
