# Phase 24 — MetaGeneratorService Contract Finalization Blueprint

## 1. Goal and Status

Phase 24 resolves the material `MetaGeneratorService` output semantics that Stack 0 recorded as `unknown / needs decision`. It fixes the decisions below as an explicit architectural contract while preserving the current runtime behavior.

This file is planning material only. It does not itself close the AP-11 decision gate or become current API authority. WU1 must create and obtain acceptance for the successor normative contract at `docs/SEO/library/META_GENERATOR_SERVICE_CONTRACT.md`; that accepted contract will resolve this Stack 0 unknown without changing runtime behavior.

This Blueprint child is based on the Phase 24 integration branch at `10da9b8ff06a6b149e3703979955505af2eb70f3`. Its PR targets `integration/phase-24-meta-generator-contract`, whose bootstrap Draft PR is #245. This child PR is documentation-only and may add only this Blueprint file.

## 2. Current Evidence and State

The following implementation, test, authority, and historical-evidence paths were reviewed before fixing this plan:

- `src/Shared/Service/MetaGeneratorService.php`
- `src/Shared/Command/GenerateMetaTagsCommand.php`
- `src/Shared/DTO/MetaTagsDTO.php`
- `src/Shared/Service/SeoOverrideQueryService.php`
- `src/Shared/Contract/HostUrlGeneratorInterface.php`
- `src/Shared/DTO/SeoOverride/SeoOverrideDTO.php`
- `tests/Stack0ContractCharacterizationTest.php`
- `docs/verification/STACK_0_CONTRACT_CHARACTERIZATION_INVENTORY.md`
- `docs/audits/SEO_ARCHITECTURE_STANDARDS_CONTRACT_INTEGRITY_AUDIT.md`
- `docs/README.md`

Current implementation evidence shows that `MetaGeneratorService` trims its default and override strings, applies override values independently, catches `SeoNotFoundException` for the no-override case, trims an explicit canonical before considering the optional host URL generator, and copies the resulting title, description, and canonical into the current social DTO fields. The host URL generator's string is returned without service-side normalization. `MetaTagsDTO` currently exposes and serializes the same public fields listed in the regression matrix below.

The Stack 0 inventory remains historically correct: its row for these behaviors is `unknown / needs decision`, based on the behavior classified at that time. Its direct characterization test is evidence of current behavior, not an approved policy. AP-11 permits an explicitly succeeding normative contract; the audit and Stack 0 inventory are historical evidence and must not be rewritten to make it appear the decision was already fixed. The documentation authority rules also mean this unstarted Blueprint cannot override current code, tests, or normative documentation.

No unresolved semantic alternative remains within the fixed decisions in this Blueprint. The only authority lifecycle distinction is that AP-11 is closed only after the WU1 successor contract is reviewed and accepted, not by this planning document or by the existing characterization test.

## 3. Locked Contract Decision

The accepted WU1 contract must state the following behavior without alternatives. Phase 24 preserves the current runtime; if WU2 finds a mismatch, it stops and reports it rather than changing production code or weakening the contract.

### 3.1 Defaults

- `defaultTitle` is processed with PHP `trim()`. The trimmed result is the fallback title. The existing command validation still rejects a blank `defaultTitle`; Phase 24 adds no further rule.
- `defaultDescription === null` produces `null`. Otherwise PHP `trim()` is applied; an empty result produces `null`, and any other result is the fallback description.
- `robots` is processed with PHP `trim()` inside `MetaGeneratorService` only. No further normalization is part of this contract.
- No other default normalization is introduced.

### 3.2 Override Semantics

An active override applies independently, field by field:

- `metaTitle === null` or blank after PHP `trim()` means no title override; the default title remains. A non-blank value is trimmed and replaces the default title.
- `metaDescription === null` or blank after PHP `trim()` means no description override; the default description remains. A non-blank value is trimmed and replaces the default description.
- A blank override never clears or erases the corresponding default.
- If active-override lookup has no result and reports `SeoNotFoundException`, that is a normal no-override case and defaults are used.
- `MetaGeneratorService` catches no other lookup exception. Every exception other than `SeoNotFoundException` propagates to the caller.

### 3.3 Canonical Precedence

The resolved canonical value is selected in this order:

1. A non-blank explicit `GenerateMetaTagsCommand::$canonicalUrl`.
2. The value returned by `HostUrlGeneratorInterface::generateEntityUrl(...)`, if a generator is supplied.
3. `null`, if no explicit value is usable and no host URL generator is supplied.

The explicit canonical is processed with PHP `trim()`. If the result is blank, it is treated as absent. A non-blank explicit canonical is returned trimmed and short-circuits the host URL generator: the generator is not called in that case.

The host URL generator owns its output. `MetaGeneratorService` returns that string as supplied, without trimming, URL validation, or provider validation. This contract adds no canonical-URL validity policy. The observable host-generator short-circuit is fixed; other internal implementation sequencing is not promoted into public contract.

### 3.4 Final Social-Field Copying

After defaults and overrides are resolved, the final values populate the DTO as follows:

| `MetaTagsDTO` field | Final value |
| --- | --- |
| `title` | final title |
| `description` | final description |
| `canonicalUrl` | final canonical |
| `robots` | trimmed robots value |
| `openGraphTitle` | final title |
| `openGraphDescription` | final description |
| `openGraphUrl` | final canonical |
| `twitterTitle` | final title |
| `twitterDescription` | final description |
| `openGraphType` | `null` |
| `openGraphImage` | `null` |
| `twitterCard` | `null` |
| `twitterImage` | `null` |

The public `MetaTagsDTO` constructor and serialization shape remain unchanged. The current serialized keys remain: `title`, `description`, `canonical_url`, `robots`, `open_graph_title`, `open_graph_description`, `open_graph_url`, `twitter_title`, `twitter_description`, `open_graph_type`, `open_graph_image`, `twitter_card`, and `twitter_image`.

## 4. Explicit Boundaries

Phase 24 does not add or change:

- URL or canonical validation, Open Graph provider validation, or Twitter/X provider validation.
- Scoring, companion diagnostics, network access, DNS behavior, or repository behavior.
- Override fields, social fields, the public `MetaTagsDTO` shape, or exception semantics.
- An identifier or slug normalization contract.
- Any production behavior, except a separately approved response to a demonstrated mismatch; mismatch remediation is not part of this Phase 24 execution.

Internal implementation order is not a public contract, except that a non-blank explicit canonical prevents a call to the host URL generator.

## 5. Normative Authority and Historical Records

WU1 creates `docs/SEO/library/META_GENERATOR_SERVICE_CONTRACT.md` as the successor normative contract for this service and links it from the current handbook as needed. Once reviewed and accepted, that file is the authority that closes AP-11 for the Stack 0 `MetaGeneratorService` decision; it records the behavior above and explicitly states that it resolves the unknown without changing runtime behavior. It does not supersede unrelated audit remediation decisions.

Do not edit the Stack 0 inventory to remove or recast its historical `unknown / needs decision` classification. Do not erase, rewrite, or otherwise alter the historical finding in `docs/audits/SEO_ARCHITECTURE_STANDARDS_CONTRACT_INTEGRITY_AUDIT.md`. Neither path is part of this Blueprint child PR.

## 6. Work Units

### WU1 — Normative Contract Authority

**Scope:** Documentation-only. Create `docs/SEO/library/META_GENERATOR_SERVICE_CONTRACT.md`, record the locked contract above, identify it as the accepted successor contract resolving Stack 0's unknown without a runtime change, and link it from the current handbook as needed.

**Ownership:** A documentation/review agent, not an implementation agent.

**Gate:** The contract must be reviewed and accepted before it is treated as normative or AP-11 is considered closed for this decision.

### WU2 — Contract Regression Lock

**Scope:** Tests-only. Add `tests/Phase24MetaGeneratorServiceContractTest.php` to lock the complete contract in the test matrix below. Keep the tests standalone and network-free, consistent with the repository's direct PHP characterization-test style.

If a required assertion demonstrates any mismatch between current runtime and the locked contract, stop. Report the exact evidence for architectural review; do not edit `src/`, change the contract expectation, or otherwise fix the mismatch in WU2. If runtime matches the contract, modifying `src/` is forbidden. Verification is not part of WU2.

**Ownership:** Codex only.

## 7. WU2 Test Matrix

The regression test must cover every item below:

1. Default title is trimmed.
2. A non-blank default description is trimmed.
3. A blank default description resolves to `null`.
4. Robots is trimmed, with no additional normalization.
5. No active override falls back to the defaults.
6. A title-only override replaces the title and retains the default description.
7. A description-only override replaces the description and retains the default title.
8. Blank title and description override values do not erase their defaults.
9. `SeoNotFoundException` from override lookup is treated as no override.
10. Any non-`SeoNotFoundException` from override lookup propagates to the caller.
11. A non-blank explicit canonical is trimmed.
12. An explicit canonical takes precedence over a host-generated URL.
13. The host URL generator is not called when an explicit canonical is present.
14. A `null` explicit canonical falls back to the host URL generator.
15. A blank explicit canonical falls back to the host URL generator.
16. With neither usable explicit canonical nor host URL generator, canonical is `null`.
17. A host-generated URL is returned unchanged by the service, with no service-side normalization or validation.
18. The final title is copied to `title`, `openGraphTitle`, and `twitterTitle`.
19. The final description is copied to `description`, `openGraphDescription`, and `twitterDescription`.
20. The final canonical is copied to `canonicalUrl` and `openGraphUrl`.
21. `openGraphType`, `openGraphImage`, `twitterCard`, and `twitterImage` remain `null`.
22. The public `MetaTagsDTO` constructor and serialized shape remain unchanged, including the current serialized key set.

The no-active-row case in item 5 must exercise the real `SeoOverrideQueryService` not-found conversion. Item 9 must also prove that a `SeoNotFoundException` raised during lookup is handled as the documented no-override condition. Item 10 must use a different exception type and assert propagation.

## 8. Subsequent Gates and Definition of Done

After WU1 and WU2:

1. Jules performs verification.
2. Jules completes the Documentation Sweep.
3. Final Review is performed against the latest `main`.
4. Only after those gates pass may Integration PR #245 be changed from Draft to Ready.

Phase 24 is complete only when the successor normative contract is accepted, it resolves the Stack 0 unknown, the regression test covers the contract, `src/` remains unchanged unless a separate mismatch is explicitly approved, the full repository tests pass, PHPStan passes, CI passes on PHP 8.2, 8.3, and 8.4, the Documentation Sweep is complete, and Final Review against latest `main` is PASS. Phase 24 includes no release, tag, or version bump. No merge is authorized by this Blueprint.

## 9. Documentation Impact Review

During the later WU1/documentation sweep, review every path below and record exactly one result for each: `updated`, `reviewed-no-change`, or `deferred-with-reason`. This Blueprint child PR does not modify these current normative documents, the audit, or the example.

| Path | Later review result |
| --- | --- |
| `README.md` | To be classified |
| `CHANGELOG.md` | To be classified |
| `docs/README.md` | To be classified |
| `docs/SEO/library/README.md` | To be classified |
| `docs/SEO/library/META_GENERATOR_SERVICE_CONTRACT.md` | To be classified |
| `docs/SEO_LIBRARY_REFERENCE.md` | To be classified |
| `docs/guides/USAGE_GUIDE.md` | To be classified |
| `docs/guides/INTEGRATION_GUIDE.md` | To be classified |
| `docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md` | To be classified |
| `docs/roadmap/SEO_LIBRARY_ROADMAP.md` | To be classified |
| `examples/seo-override-meta-generation.php` | To be classified |
| Relevant Phase and verification documentation | To be classified |
