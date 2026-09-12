# MetaGeneratorService Contract

## Status and Authority

This document records the successor normative contract for `MetaGeneratorService` established by Phase 24. It resolves the Stack 0 classification `unknown / needs decision` for the service's material output semantics without changing runtime behavior.

Within Phase 24, after this document is reviewed, accepted, and merged into `integration/phase-24-meta-generator-contract`, it is the execution authority for this decision, closes its AP-11 gate, and permits WU2 to begin. This statement does not claim that this contract is part of current `main`; that is true only after the final Phase 24 integration is merged into `main`.

The Stack 0 inventory and architecture audit remain historical records of the evidence and decision state at the time they were written. This contract does not rewrite those records or supersede unrelated audit decisions.

## Defaults

- `defaultTitle` is processed with PHP `trim()`. The trimmed value is the fallback title. `GenerateMetaTagsCommand` remains responsible for rejecting a blank default title; this contract adds no validation.
- If `defaultDescription` is `null`, the resulting fallback description is `null`. Otherwise PHP `trim()` is applied; a blank trimmed result becomes `null`, and every other result is the trimmed value.
- `robots` is processed with PHP `trim()` only inside `MetaGeneratorService`. No additional normalization is performed.

## Override Semantics

Active overrides are applied independently for each field:

- For `metaTitle`, `null` or a value that is blank after PHP `trim()` means no title override. The default title remains. A non-blank value is trimmed and replaces the default title.
- For `metaDescription`, `null` or a value that is blank after PHP `trim()` means no description override. The default description remains. A non-blank value is trimmed and replaces the default description.
- A blank override value does not clear or erase its corresponding default.
- `SeoNotFoundException` from active override lookup means there is no applicable override; the defaults remain in use.
- Any other exception from active override lookup is not swallowed and propagates to the caller.

## Canonical Precedence

The canonical value is selected in this order:

1. A non-blank explicit `GenerateMetaTagsCommand::$canonicalUrl`.
2. The value returned by `HostUrlGeneratorInterface::generateEntityUrl(...)`, when a host URL generator is supplied.
3. `null`, when neither a usable explicit canonical nor a host URL generator is available.

The explicit canonical is processed with PHP `trim()`. A blank trimmed value is absent; a non-blank value is returned trimmed. When a non-blank explicit canonical exists, the host URL generator is not called.

The host owns the URL returned by `generateEntityUrl(...)`. `MetaGeneratorService` returns that value as supplied, without trimming, URL validation, or provider validation. This contract introduces no canonical validity policy.

Internal execution order is not part of the public contract, except for the observable short-circuit that prevents a host URL generator call when a non-blank explicit canonical is present.

## Social-Field Copying

Once the final values are resolved, `MetaGeneratorService` populates `MetaTagsDTO` as follows:

| `MetaTagsDTO` field | Value |
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

## Public DTO Shape

The public `MetaTagsDTO` constructor and serialized shape do not change. Its current serialized keys are:

- `title`
- `description`
- `canonical_url`
- `robots`
- `open_graph_title`
- `open_graph_description`
- `open_graph_url`
- `twitter_title`
- `twitter_description`
- `open_graph_type`
- `open_graph_image`
- `twitter_card`
- `twitter_image`

## Explicit Non-Goals

This contract does not add:

- URL validation or canonical provider rules.
- Open Graph provider validation or Twitter/X provider validation.
- Scoring or companion diagnostics.
- Network or DNS behavior.
- Repository behavior.
- Override fields, social fields, or `MetaTagsDTO` fields.
- Identifier or slug normalization.
- New exceptions.
