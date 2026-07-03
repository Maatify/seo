# Phase 13C — Article JSON-LD Builder

## Overview

Phase 13C introduces the `ArticleJsonLdBuilder`, the second concrete typed builder built upon the Phase 13A foundation. It generates structured data for articles, blog posts, and news articles following the schema.org standards.

The builder is fully framework-neutral, has zero external dependencies, and exports schema data as pure associative arrays or JSON strings.

By default, the builder initializes with the standard schema.org `Article` type:

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'Article',
]
```

## Supported Methods

The builder extends `AbstractJsonLdBuilder` and includes fluent setters for article properties:

```php
setHeadline(string $headline): static
setDescription(string $description): static
setUrl(string $url): static
setImage(string|array $image): static
setImages(array $images): static
setAuthor(string|array $author): static
setPublisher(string|array $publisher): static
setDatePublished(string $datePublished): static
setDateModified(string $dateModified): static
setMainEntityOfPage(string|array $mainEntityOfPage): static
setArticleSection(string $articleSection): static
setKeywords(string|array $keywords): static
```

### Schema Type Factories & Toggling

You can create specific article types using factory methods:

```php
ArticleJsonLdBuilder::article()
ArticleJsonLdBuilder::blogPosting()
ArticleJsonLdBuilder::newsArticle()
```

Or switch types dynamically on an existing builder:

```php
$builder->asArticle();
$builder->asBlogPosting();
$builder->asNewsArticle();
```

### Complex Entities Handling

The `ArticleJsonLdBuilder` is smart enough to handle both simple string inputs and complex array definitions for `author`, `publisher`, and `mainEntityOfPage`.

#### String Inputs
If you pass strings, they are automatically structured into schema entities:

- `setAuthor('Jane Doe')` -> `['@type' => 'Person', 'name' => 'Jane Doe']`
- `setPublisher('Maatify Publishing')` -> `['@type' => 'Organization', 'name' => 'Maatify Publishing']`
- `setMainEntityOfPage('https://example.com/demo')` -> `['@type' => 'WebPage', '@id' => 'https://example.com/demo']`

#### Array Inputs
If you need advanced definitions (like an organization logo), you can pass the full array definition directly, and it will be preserved as-is.

## Usage Examples

### Standard Blog Posting

```php
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;

$schema = ArticleJsonLdBuilder::blogPosting()
    ->setHeadline('Understanding JSON-LD for SEO')
    ->setDescription('A comprehensive guide to implementing JSON-LD.')
    ->setUrl('https://example.com/blog/understanding-json-ld')
    ->setAuthor('Jane Doe')
    ->setPublisher('Maatify Publishing')
    ->setDatePublished('2023-10-27T10:00:00Z')
    ->setImage('https://example.com/images/json-ld-header.jpg')
    ->toArray();
```

Generated array:

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => 'Understanding JSON-LD for SEO',
    'description' => 'A comprehensive guide to implementing JSON-LD.',
    'url' => 'https://example.com/blog/understanding-json-ld',
    'author' => [
        '@type' => 'Person',
        'name' => 'Jane Doe',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Maatify Publishing',
    ],
    'datePublished' => '2023-10-27T10:00:00Z',
    'image' => 'https://example.com/images/json-ld-header.jpg',
]
```

### News Article with Advanced Publisher Definition

```php
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;

$json = ArticleJsonLdBuilder::newsArticle()
    ->setHeadline('Maatify Releases New SEO Module')
    ->setAuthor([
        '@type' => 'Organization',
        'name' => 'Maatify Press Team',
    ])
    ->setPublisher([
        '@type' => 'Organization',
        'name' => 'Maatify Global',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => 'https://example.com/logo.png',
        ],
    ])
    ->setDatePublished('2023-11-01T08:00:00Z')
    ->toJson(JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
```

Generated JSON:

```json
{
    "@context": "https://schema.org",
    "@type": "NewsArticle",
    "headline": "Maatify Releases New SEO Module",
    "author": {
        "@type": "Organization",
        "name": "Maatify Press Team"
    },
    "publisher": {
        "@type": "Organization",
        "name": "Maatify Global",
        "logo": {
            "@type": "ImageObject",
            "url": "https://example.com/logo.png"
        }
    },
    "datePublished": "2023-11-01T08:00:00Z"
}
```

## Integration Examples

### Extracting and Rendering via Script Tag

```php
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;

$json = ArticleJsonLdBuilder::article()
    ->setHeadline('Hello World')
    ->toJson(JSON_UNESCAPED_SLASHES);

echo '<script type="application/ld+json">' . $json . '</script>';
```

## Compatibility Notes

- Follows the foundation laid out in Phase 13A.
- Exists completely independent of the framework ecosystem.
- No `composer.lock` dependencies were introduced.
