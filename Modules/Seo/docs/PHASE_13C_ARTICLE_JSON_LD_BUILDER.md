# Phase 13C: Article JSON-LD Builder

## Overview
The `ArticleJsonLdBuilder` is part of the `Maatify\Seo\Web\JsonLd\Builder` namespace and provides a framework-neutral, fluent interface to build `Article`, `BlogPosting`, and `NewsArticle` Schema.org JSON-LD arrays.

## Supported Schema Types
- `Article`
- `BlogPosting`
- `NewsArticle`

## Supported Fields
- `headline`
- `description`
- `url`
- `image` (string or array of strings)
- `author` (string or `Person` array)
- `publisher` (string or `Organization` array)
- `datePublished`
- `dateModified`
- `mainEntityOfPage`
- `articleSection`
- `keywords` (string or array)

## Usage Example

```php
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;

$builder = new ArticleJsonLdBuilder();
// Alternatively: ArticleJsonLdBuilder::blogPosting() or ArticleJsonLdBuilder::newsArticle()

$schemaArray = $builder
    ->setHeadline('Maatify Demo Article')
    ->setDescription('A demo article for JSON-LD output.')
    ->setUrl('https://example.com/article/demo')
    ->setImage([
        'https://example.com/images/article-front.jpg',
        'https://example.com/images/article-side.jpg',
    ])
    ->setAuthor('Jane Doe')
    ->setPublisher('Maatify Organization')
    ->setDatePublished('2023-07-03T08:00:00+00:00')
    ->setDateModified('2023-07-03T10:00:00+00:00')
    ->setMainEntityOfPage('https://example.com/article/demo')
    ->setArticleSection('Technology')
    ->setKeywords(['SEO', 'JSON-LD', 'PHP'])
    ->toArray();

// Or get as JSON string directly
$jsonString = $builder->toJson();
```
