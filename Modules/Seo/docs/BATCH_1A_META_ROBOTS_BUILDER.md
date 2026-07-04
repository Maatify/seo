# Batch 1A — Meta Robots Builder

The `MetaRobotsBuilder` component provides a fluent interface for building the contents of a `robots` meta tag, specifically used to give instructions to web crawlers and search engines. It strictly manages directives such as `index`, `follow`, and various limitations like `max-snippet`.

**Note:** This builder handles the `<meta name="robots">` tag, not the `robots.txt` file.

## Features

- **Fluent Interface:** Chain methods to build the directive list.
- **Exclusivity Management:** Automatically handles mutually exclusive directives (e.g., calling `noIndex()` will remove an existing `index` directive).
- **Prefix Replacement:** Directives like `max-snippet:*`, `max-image-preview:*`, `max-video-preview:*`, and `unavailable_after:*` are updated instead of appended, maintaining unique prefix constraints.
- **Deduplication:** Ensures no duplicate directives are added to the list.
- **Validation:** Throws `SeoInvalidArgumentException` for invalid values (like negative maximum lengths or unrecognised `max-image-preview` types).
- **HTML Escaping:** Provides a safe `toHtml()` method for rendering the full meta tag properly escaped.

## Class Definition

`Maatify\Seo\Web\Robots\MetaRobotsBuilder`

## Usage

### Basic Usage

```php
use Maatify\Seo\Web\Robots\MetaRobotsBuilder;

$builder = new MetaRobotsBuilder();

$builder->index()
        ->follow()
        ->maxSnippet(50)
        ->maxImagePreview('large');

echo $builder->build();
// Output: index, follow, max-snippet:50, max-image-preview:large
```

### Exclusivity Handling

Adding an exclusive directive removes its counterpart automatically.

```php
$builder = new MetaRobotsBuilder();
$builder->index();

// Later in code:
$builder->noIndex(); // Removes 'index', adds 'noindex'

echo $builder->build();
// Output: noindex
```

### Output Formats

You can output the meta directives in a few different formats:

```php
$builder = new MetaRobotsBuilder();
$builder->index()->follow();

// As string (comma-separated):
$builder->build(); // or (string) $builder

// As array:
$builder->toArray(); // ['index', 'follow']

// As fully formed HTML tag (escaped):
$builder->toHtml();
// Output: <meta name="robots" content="index, follow">
```

### Validation

If an invalid input is passed, the builder throws a `SeoInvalidArgumentException`:

```php
use Maatify\Seo\Exception\SeoInvalidArgumentException;

try {
    $builder->maxSnippet(-10);
} catch (SeoInvalidArgumentException $e) {
    // "Field [max-snippet] is invalid: Value must be greater than or equal to 0."
}
```

## Available Directives

*   `index()`: Allow indexing. Exclusive with `noIndex()`.
*   `noIndex()`: Prevent indexing. Exclusive with `index()`.
*   `follow()`: Allow following links. Exclusive with `noFollow()`.
*   `noFollow()`: Prevent following links. Exclusive with `follow()`.
*   `noArchive()`: Prevent cached copies.
*   `noSnippet()`: Prevent snippets and video previews.
*   `noImageIndex()`: Prevent image indexing.
*   `noTranslate()`: Prevent translated versions.
*   `maxSnippet(int $value)`: Set maximum snippet length.
*   `maxImagePreview(string $value)`: Set max image preview size (`none`, `standard`, `large`).
*   `maxVideoPreview(int $value)`: Set maximum video preview length.
*   `unavailableAfter(string $value)`: Set expiration date/time.
*   `add(string $directive)`: Add a custom directive.
*   `remove(string $directive)`: Remove a specific directive.
*   `clear()`: Clear all directives.
