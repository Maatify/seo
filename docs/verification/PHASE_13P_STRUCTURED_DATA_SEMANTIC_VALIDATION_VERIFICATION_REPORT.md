# Phase 13P — Structured Data Semantic Validation Verification Report

## Verification scope

This report records the Phase 13P Verification Gate only. Verification was run from
`codex/phase-13p-verification`, created from the following Draft integration HEAD:

`85b8f7f52ddbed58be5fb95180982e341a1a9a8b`

The Draft branch was `codex/phase-13p-draft`. No runtime, test, or documentation
failure was fixed during this gate.

## PHPStan

Command:

```text
vendor/bin/phpstan analyse
```

Result: **PASS** — PHPStan completed with `[OK] No errors`.

## Standalone test suite

Command:

```text
for test_file in tests/*.php; do php "$test_file" || exit 1; done
```

Result: **PASS** — all 48 standalone PHP test scripts completed successfully.

Executed test files:

1. `tests/Batch1AMetaRobotsBuilderTest.php`
2. `tests/Batch1BSeoPagePresetFactoryTest.php`
3. `tests/Batch1CHighLevelDomainSeoPresetFactoriesTest.php`
4. `tests/Batch2AdminPreviewsMigrationsTest.php`
5. `tests/Batch3HreflangHeadLinkBuilderTest.php`
6. `tests/Phase10ASitemapIndexXmlStringRendererTest.php`
7. `tests/Phase10BSitemapHreflangXmlStringRendererTest.php`
8. `tests/Phase10CImageSitemapXmlStringRendererTest.php`
9. `tests/Phase10DVideoSitemapXmlStringRendererTest.php`
10. `tests/Phase10ENewsSitemapXmlStringRendererTest.php`
11. `tests/Phase11ASeoValidationHelpersTest.php`
12. `tests/Phase11BSeoValidationScoreHelpersTest.php`
13. `tests/Phase11CSeoValidationReportHelpersTest.php`
14. `tests/Phase11DSeoValidationPresetsTest.php`
15. `tests/Phase11ESeoValidationReportExporterTest.php`
16. `tests/Phase11FSeoValidationBatchReportHelpersTest.php`
17. `tests/Phase11GSeoValidationBatchReportExporterTest.php`
18. `tests/Phase13AJsonLdBuilderFoundationTest.php`
19. `tests/Phase13BProductJsonLdBuilderTest.php`
20. `tests/Phase13CArticleJsonLdBuilderTest.php`
21. `tests/Phase13DBreadcrumbJsonLdBuilderTest.php`
22. `tests/Phase13EOrganizationJsonLdBuilderTest.php`
23. `tests/Phase13FWebSiteJsonLdBuilderTest.php`
24. `tests/Phase13GPersonJsonLdBuilderTest.php`
25. `tests/Phase13HContentJsonLdBuildersTest.php`
26. `tests/Phase13ICommerceJsonLdBuildersTest.php`
27. `tests/Phase13JMediaJsonLdBuildersTest.php`
28. `tests/Phase13KPageTypeJsonLdBuildersTest.php`
29. `tests/Phase13LSpecializedRichResultsJsonLdBuildersTest.php`
30. `tests/Phase13MExtraSpecializedJsonLdBuildersTest.php`
31. `tests/Phase13OAggregateOfferJsonLdBuilderTest.php`
32. `tests/Phase13OCompositionTest.php`
33. `tests/Phase13OProductGroupJsonLdBuilderTest.php`
34. `tests/Phase13PAggregateOfferProductGroupSemanticValidationTest.php`
35. `tests/Phase13PJsonLdStructuralValidationTest.php`
36. `tests/Phase13PProductOfferSemanticValidationTest.php`
37. `tests/Phase13PValidationPipelineTest.php`
38. `tests/Phase14ASocialMetaFoundationTest.php`
39. `tests/Phase14BOpenGraphBuilderTest.php`
40. `tests/Phase14CTwitterCardBuilderTest.php`
41. `tests/Phase14DSocialImageFactoryTest.php`
42. `tests/Phase14ESocialPreviewBuilderTest.php`
43. `tests/Phase15ACanonicalUrlBuilderTest.php`
44. `tests/Phase7ARenderersTest.php`
45. `tests/Phase7CFluentSeoBuilderTest.php`
46. `tests/Phase7DSpatieSchemaAdapterTest.php`
47. `tests/Phase7ESitemapXmlStringRendererTest.php`
48. `tests/Phase9ARobotsTxtRendererTest.php`

## Work Unit coverage

The verified Draft HEAD includes and passes coverage for all four implementation Work
Units:

| Work Unit | Verified scope | Focused standalone test |
| --- | --- | --- |
| WU1 | Generic JSON-LD structural validation, graph handling, recursive traversal, type contracts, deterministic paths, and read-only behavior | `tests/Phase13PJsonLdStructuralValidationTest.php` |
| WU2 | Product and Offer semantic catalogs, value shapes, relationships, nested validation, and malformed types | `tests/Phase13PProductOfferSemanticValidationTest.php` |
| WU3 | AggregateOffer and ProductGroup semantic catalogs, relationships, recursive validation, and Phase 13O scenarios | `tests/Phase13PAggregateOfferProductGroupSemanticValidationTest.php` |
| WU4 | Existing validation pipeline, result DTOs, warnings, scoring, reports, batches, exporters, and JSON-LD aliases | `tests/Phase13PValidationPipelineTest.php` |

The existing Phase 11 validation, scoring, report, batch, and exporter tests also passed
without regression.

## Limitations and scope boundary

Phase 13P provides structural validation and deep semantic validation only for
`Product`, `Offer`, `AggregateOffer`, and `ProductGroup`, according to the fixed
catalog and relationship contracts. Other JSON-LD types may remain valid structural or
relationship targets where the contracts allow them, but do not receive deep semantic
validation in this Phase.

Phase 13P does **not** implement Google Rich Results eligibility or Merchant eligibility
validation. No Google or Merchant eligibility findings are emitted by the verified
pipeline; these remain separate Future Work.

This report covers Verification only. Documentation Sweep and Final Review against the
latest `main` remain independent post-implementation gates and are not claimed as
complete here.

## Verification conclusion

**PASS** — PHPStan and all 48 standalone tests passed on Draft HEAD
`85b8f7f52ddbed58be5fb95180982e341a1a9a8b`. The Verification Gate evidence is
complete for WU1–WU4 within the stated Phase 13P scope.
