# Phase 14A — Social Meta Foundation

## Architecture overview

Phase 14A establishes a framework-neutral, dependency-free foundation for rendering social media meta tags (such as Open Graph or Twitter cards, though specific builders for those are planned for later phases).

The social meta foundation resides in `src/Web/Social/` and provides classes to represent individual meta tags, social images, collections of tags, and a final render output DTO.

Design constraints:

- No framework dependencies.
- No HTTP responses or PSR-7 coupling.
- No static global state.
- Output remains DTOs, arrays, or properly escaped HTML strings.
- Collections preserve insertion order and intentionally do not deduplicate tags (allowing multiple tags with the same name if required by specific social protocols).
- No validation of URLs beyond string typing.

## Class responsibilities

### `SocialMetaTag`

Represents a single social meta tag.

```php
// Constructor
public function __construct(string $name, string $content, string $attribute = 'property')
```

- `getName(): string` - Returns the tag name (e.g., `og:title` or `twitter:card`).
- `getContent(): string` - Returns the tag content.
- `getAttribute(): string` - Returns the HTML attribute used for the name (defaults to `property` for Open Graph, but can be `name` for Twitter or `itemprop` for schema).
- `toArray(): array` - Returns `['name' => string, 'content' => string, 'attribute' => string]`.
- `toHtml(): string` - Renders the tag as a `<meta>` HTML string. The attribute, name, and content are all escaped using `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.

### `SocialImage`

Represents an image asset for social sharing. It does not validate the URL string format.

```php
// Constructor requires the primary URL
public function __construct(string $url)
```

Provides fluent setters for optional image properties:
- `setSecureUrl(string $secureUrl): static`
- `setType(string $type): static`
- `setWidth(int $width): static`
- `setHeight(int $height): static`
- `setAlt(string $alt): static`

Provides corresponding getters:
- `getUrl(): string`
- `getSecureUrl(): ?string`
- `getType(): ?string`
- `getWidth(): ?int`
- `getHeight(): ?int`
- `getAlt(): ?string`

- `toArray(): array` - Returns an array representation. Optional fields are omitted if they are `null`.

### `SocialMetaCollection`

A mutable collection of `SocialMetaTag` instances. It preserves the order of insertion and does not deduplicate tags.

- `add(SocialMetaTag $tag): static` - Adds a tag instance to the collection.
- `addTag(string $name, string $content, string $attribute = 'property'): static` - Helper to create and add a tag in one call.
- `all(): array` - Returns the list of `SocialMetaTag` instances.
- `toArray(): array` - Returns an array of tag array representations.
- `toHtml(string $separator = "\n"): string` - Renders all tags to an HTML string separated by the given separator.
- `isEmpty(): bool` - Returns `true` if the collection has no tags.
- `count(): int` - Returns the number of tags in the collection.

### `SocialMetaRenderOutput`

A strictly read-only DTO that wraps a `SocialMetaCollection` and provides convenient methods for reading or rendering the final output.

```php
public function __construct(SocialMetaCollection $collection)
```

- `getCollection(): SocialMetaCollection` - Returns the underlying collection.
- `getTags(): array` - Delegates to the collection's `all()` method.
- `toArray(): array` - Delegates to the collection's `toArray()` method.
- `toHtml(string $separator = "\n"): string` - Delegates to the collection's `toHtml()` method.
- `isEmpty(): bool` - Delegates to the collection's `isEmpty()` method.

## Usage examples

### Creating and rendering basic tags

```php
use Maatify\Seo\Web\Social\SocialMetaTag;

$tag = new SocialMetaTag('og:title', 'Example Title');
echo $tag->toHtml();
// Output: <meta property="og:title" content="Example Title">

$twitterTag = new SocialMetaTag('twitter:card', 'summary', 'name');
echo $twitterTag->toHtml();
// Output: <meta name="twitter:card" content="summary">
```

### Using the collection

```php
use Maatify\Seo\Web\Social\SocialMetaCollection;

$collection = new SocialMetaCollection();
$collection->addTag('og:type', 'website');
$collection->addTag('twitter:site', '@example', 'name');

echo $collection->toHtml();
// Output:
// <meta property="og:type" content="website">
// <meta name="twitter:site" content="@example">
```

### Working with images

```php
use Maatify\Seo\Web\Social\SocialImage;

$image = (new SocialImage('https://example.com/img.jpg'))
    ->setWidth(1200)
    ->setHeight(630)
    ->setAlt('Example alt text');

$array = $image->toArray();
// [
//     'url' => 'https://example.com/img.jpg',
//     'width' => 1200,
//     'height' => 630,
//     'alt' => 'Example alt text',
// ]
```

## Future implementation

Phase 14A intentionally does not add specialized builders for Open Graph or Twitter. It provides only the foundation. Future phases will introduce typed builders (e.g., `OpenGraphBuilder`, `TwitterCardBuilder`) that utilize this foundation.
