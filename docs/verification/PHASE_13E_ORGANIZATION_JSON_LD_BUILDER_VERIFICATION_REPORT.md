# Phase 13E — Organization JSON-LD Builder Verification Report

## Files added

- `tests/Phase13EOrganizationJsonLdBuilderTest.php`
- `docs/phases/PHASE_13E_ORGANIZATION_JSON_LD_BUILDER.md`
- `docs/verification/PHASE_13E_ORGANIZATION_JSON_LD_BUILDER_VERIFICATION_REPORT.md`

## Public API summary

The concrete builder is `Maatify\Seo\Web\JsonLd\Builder\OrganizationJsonLdBuilder`.

It extends `AbstractJsonLdBuilder`, implements the existing `JsonLdBuilderInterface` through the base class, and automatically initializes every schema with:

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
]
```

Organization-specific static factories:

```php
public static function organization(): self
public static function localBusiness(): self
public static function corporation(): self
public static function store(): self
```

Organization-specific fluent methods:

```php
public function setType(string $type): static
public function asOrganization(): static
public function asLocalBusiness(): static
public function asCorporation(): static
public function asStore(): static
public function setName(string $name): static
public function setUrl(string $url): static
public function setLogo(string $logo): static
public function setDescription(string $description): static
public function setSameAs(array $sameAs): static
public function addSameAs(string $url): static
public function setContactPoint(array $contactPoint): static
public function addContactPoint(array $contactPoint): static
public function setAddress(array $address): static
public function setPostalAddress(?string $streetAddress = null, ?string $addressLocality = null, ?string $addressRegion = null, ?string $postalCode = null, ?string $addressCountry = null): static
```

The inherited generic builder methods remain available:

```php
set(string $key, mixed $value): static
remove(string $key): static
has(string $key): bool
get(string $key): mixed
toArray(): array
toJson(int $flags = 0): string
```

## Example output

```json
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Maatify Demo Org",
    "url": "https://example.com",
    "logo": "https://example.com/logo.png",
    "description": "A demo organization for JSON-LD output.",
    "sameAs": [
        "https://twitter.com/example",
        "https://github.com/example"
    ],
    "contactPoint": [
        {
            "telephone": "+1-800-555-1212",
            "contactType": "customer service",
            "@type": "ContactPoint"
        }
    ],
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "123 Demo St",
        "addressLocality": "Demo City",
        "addressRegion": "CA",
        "postalCode": "90210",
        "addressCountry": "US"
    }
}
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

## vendor/bin/phpstan analyse result

Command:

```bash
vendor/bin/phpstan analyse
```

Result:

```text
 [OK] No errors
```

Status: Passed.

## Test results

Command:

```bash
find tests -name '*Test.php' -print0 | xargs -0 -n1 php
```

Result:

```text
Phase 11G SEO validation batch report exporter tests passed.
Phase 7E sitemap XML string renderer tests passed.
Phase 10D video sitemap XML string renderer tests passed.
Phase 11C SEO validation report helpers tests passed.
Phase 10C image sitemap XML string renderer tests passed.
Phase 7D Spatie schema adapter tests passed.
Phase 13A JSON-LD builder foundation tests passed.
Phase 7C fluent SEO builder tests passed.
Phase 11F SEO validation batch report helpers tests passed.
Phase 11D SEO validation presets tests passed.
Phase 7A renderer tests passed.
BreadcrumbJsonLdBuilderTest passed successfully.
Phase 10B sitemap hreflang XML string renderer tests passed.
Phase 10A sitemap index XML string renderer tests passed.
Phase 13E organization JSON-LD builder tests passed.
Phase 10E news sitemap XML string renderer tests passed.
Phase 11E SEO validation report exporter tests passed.
Phase 11A SEO validation helpers tests passed.
Phase 11B SEO validation score helpers tests passed.
Phase 13C article JSON-LD builder tests passed.
Phase 9A RobotsTxtRenderer tests passed.
Phase 13B product JSON-LD builder tests passed.
```

Status: Passed.

Additional syntax check:

```bash
php -l src/Web/JsonLd/Builder/OrganizationJsonLdBuilder.php
php -l tests/Phase13EOrganizationJsonLdBuilderTest.php
```

Result:

```text
No syntax errors detected in src/Web/JsonLd/Builder/OrganizationJsonLdBuilder.php
No syntax errors detected in tests/Phase13EOrganizationJsonLdBuilderTest.php
```

Status: Passed.

## CI compatibility statement

The implementation is additive and framework-neutral. It does not modify existing generators, renderers, DTOs, services, composer dependencies, or Composer autoload configuration. It uses only PHP language features already required by the package (`php >=8.2`) and remains compatible with CI jobs that install Composer dev dependencies before running PHPStan.

## BC verification

- No existing public API was removed or renamed.
- No existing generator was modified.
- No new package dependency was added.
- `composer.lock` was not created or modified.
- The new builder exports plain arrays and JSON strings through the existing Phase 13A builder contract.

## Final verdict

Phase 13E is complete. The Organization JSON-LD builder is verified and documented. No blockers found.
