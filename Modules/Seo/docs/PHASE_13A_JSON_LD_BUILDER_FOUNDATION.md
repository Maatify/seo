# Phase 13A — JSON-LD Schema Builder Foundation

## Architecture overview

Phase 13A adds a framework-neutral JSON-LD builder foundation under `src/Web/JsonLd/Builder/`.
The foundation is intentionally small and does not replace any existing JSON-LD DTOs, renderers, schema generators, or Spatie adapter integration.

The builder foundation stores schema data as an associative PHP array and exposes a fluent API for setting, removing, checking, reading, array-exporting, and JSON-exporting fields. Future typed builders can extend the abstract base class and add domain-specific methods while reusing the same storage and serialization behavior.

Design constraints:

- No framework dependencies.
- No static global state.
- No external packages.
- No hard dependency on Spatie.
- Mutable fluent API by design.
- Compatible with existing services that already consume associative JSON-LD arrays.
- JSON serialization always uses `JSON_THROW_ON_ERROR`.

## Class responsibilities

### `JsonLdBuilderInterface`

Defines the public builder contract:

```php
set(string $key, mixed $value): static
remove(string $key): static
has(string $key): bool
get(string $key): mixed
toArray(): array
toJson(int $flags = 0): string
```

Future schema builders should implement this interface directly or extend `AbstractJsonLdBuilder`.

### `JsonLdBuilderTrait`

Provides the reusable mutable implementation for the builder contract:

- Stores schema fields in `protected array $schema`.
- Implements fluent `set()` and `remove()` methods.
- Uses `array_key_exists()` for `has()` so keys with `null` values are still considered present.
- Exports the current schema with `toArray()`.
- Encodes the current schema with `toJson()` using caller-provided flags plus `JSON_THROW_ON_ERROR`.

### `AbstractJsonLdBuilder`

Provides a simple extensible base class for future typed builders. It accepts an optional associative schema seed in the constructor, which allows future builders to initialize defaults such as `@context` and `@type` without duplicating storage logic.

### `JsonLdBuildException`

Wraps JSON encoding failures in a library exception that implements `SeoExceptionInterface`. This keeps JSON-LD encoding failures inside the SEO module exception boundary instead of leaking raw `JsonException` instances from `toJson()`.

## Usage examples

### Anonymous or local builder subclass

```php
use Maatify\Seo\Web\JsonLd\Builder\AbstractJsonLdBuilder;

final class ThingBuilder extends AbstractJsonLdBuilder
{
}

$json = (new ThingBuilder(['@context' => 'https://schema.org']))
    ->set('@type', 'Thing')
    ->set('name', 'Example Thing')
    ->toJson(JSON_UNESCAPED_SLASHES);
```

### Array output for existing renderers or DTO flows

```php
$schema = (new ThingBuilder())
    ->set('@context', 'https://schema.org')
    ->set('@type', 'Thing')
    ->set('name', 'Example Thing')
    ->toArray();
```

The resulting array can be passed to existing JSON-LD-compatible services that already accept associative schema arrays.

### Removing or checking fields

```php
$builder = (new ThingBuilder())
    ->set('@context', 'https://schema.org')
    ->set('@type', 'Thing')
    ->set('description', null);

$hasDescription = $builder->has('description'); // true, because the key exists.

$builder->remove('description');
```

## Future builder roadmap

Phase 13A intentionally does not add typed domain builders. The foundation is prepared for later phases to add classes such as:

- `ProductBuilder`
- `BreadcrumbBuilder`
- `OrganizationBuilder`
- `WebSiteBuilder`
- `FAQBuilder`
- `ArticleBuilder`

Expected future pattern:

```php
final class ProductBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
        ]);
    }

    public function name(string $name): static
    {
        return $this->set('name', $name);
    }
}
```

This roadmap keeps Phase 13A limited to foundation work and avoids changing production JSON-LD generation behavior before typed builders are introduced.
