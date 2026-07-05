# Phase 13A — JSON-LD Schema Builder Foundation Verification Report

## Files added

- `src/Web/JsonLd/Builder/JsonLdBuilderInterface.php`
- `src/Web/JsonLd/Builder/JsonLdBuilderTrait.php`
- `src/Web/JsonLd/Builder/AbstractJsonLdBuilder.php`
- `src/Web/JsonLd/Builder/JsonLdBuildException.php`
- `tests/Phase13AJsonLdBuilderFoundationTest.php`
- `docs/phases/PHASE_13A_JSON_LD_BUILDER_FOUNDATION.md`
- `docs/verification/PHASE_13A_JSON_LD_BUILDER_FOUNDATION_VERIFICATION_REPORT.md`

## Public API summary

The new foundation exposes the `Maatify\Seo\Web\JsonLd\Builder\JsonLdBuilderInterface` contract:

```php
set(string $key, mixed $value): static
remove(string $key): static
has(string $key): bool
get(string $key): mixed
toArray(): array
toJson(int $flags = 0): string
```

`AbstractJsonLdBuilder` provides a reusable base implementation through `JsonLdBuilderTrait` and accepts an optional associative array seed through its constructor.

`JsonLdBuildException` is thrown when JSON encoding fails and implements the module-level `SeoExceptionInterface`.

## BC verification

- No existing JSON-LD generator, DTO, renderer, schema service, Spatie adapter, or fluent SEO builder file was modified.
- No existing public API was removed or renamed.
- No composer dependency was added.
- `composer.lock` was not created or modified.
- The new namespace is additive: `Maatify\Seo\Web\JsonLd\Builder`.
- Existing JSON-LD consumers remain compatible because builders can export plain associative arrays with `toArray()`.

## Example usage

```php
use Maatify\Seo\Web\JsonLd\Builder\AbstractJsonLdBuilder;

final class ThingBuilder extends AbstractJsonLdBuilder
{
}

$schema = (new ThingBuilder(['@context' => 'https://schema.org']))
    ->set('@type', 'Thing')
    ->set('name', 'Example Thing')
    ->toArray();

$json = (new ThingBuilder($schema))->toJson(JSON_UNESCAPED_SLASHES);
```

## composer validate result

Command:

```bash
composer validate --strict
```

Result:

```text
./composer.json is valid
```

Status: Passed.

## PHPStan result

Command:

```bash
vendor/bin/phpstan analyse
```

Result:

```text
/bin/bash: line 1: vendor/bin/phpstan: No such file or directory
```

Status: Not run in this checkout because dev dependencies are not installed and `vendor/bin/phpstan` is unavailable.

## Test results

Command:

```bash
find src/Web/JsonLd/Builder -name '*.php' -print0 | xargs -0 -n1 php -l
```

Result:

```text
No syntax errors detected in src/Web/JsonLd/Builder/JsonLdBuilderInterface.php
No syntax errors detected in src/Web/JsonLd/Builder/AbstractJsonLdBuilder.php
No syntax errors detected in src/Web/JsonLd/Builder/JsonLdBuilderTrait.php
No syntax errors detected in src/Web/JsonLd/Builder/JsonLdBuildException.php
```

Status: Passed.

Command:

```bash
for f in tests/*Test.php; do php "$f" || exit 1; done
```

Result:

```text
Phase 10A sitemap index XML string renderer tests passed.
Phase 10B sitemap hreflang XML string renderer tests passed.
Phase 10C image sitemap XML string renderer tests passed.
Phase 10D video sitemap XML string renderer tests passed.
Phase 10E news sitemap XML string renderer tests passed.
Phase 11A SEO validation helpers tests passed.
Phase 11B SEO validation score helpers tests passed.
Phase 11C SEO validation report helpers tests passed.
Phase 11D SEO validation presets tests passed.
Phase 11E SEO validation report exporter tests passed.
Phase 11F SEO validation batch report helpers tests passed.
Phase 11G SEO validation batch report exporter tests passed.
Phase 7A renderer tests passed.
Phase 7C fluent SEO builder tests passed.
Phase 7D Spatie schema adapter tests passed.
Phase 7E sitemap XML string renderer tests passed.
Phase 9A RobotsTxtRenderer tests passed.
```

Status: Passed.

## CI compatibility statement

The new foundation uses PHP 8.2-compatible language features already allowed by the package requirement. It depends only on core PHP functions and the existing module exception interface. The implementation does not require framework services, Spatie classes, network access, databases, or new Composer packages, so it is compatible with the existing GitHub Actions CI design once dev dependencies are installed by CI.

## Final verdict

Phase 13A is complete. The JSON-LD builder foundation is additive, framework-neutral, dependency-free, compatible with existing JSON-LD services, and ready for later typed schema builders without requiring major refactoring.
