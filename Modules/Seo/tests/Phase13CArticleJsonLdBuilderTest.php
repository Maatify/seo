<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Maatify\\Seo\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuilderInterface;
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;

function assertSameValue13C(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13C(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

$builder = new ArticleJsonLdBuilder();
assertTrueValue13C('article builder implements builder interface', $builder instanceof JsonLdBuilderInterface);
assertSameValue13C('article builder seeds schema.org article defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
], $builder->toArray());
assertSameValue13C('setHeadline is fluent', $builder, $builder->setHeadline('Maatify Demo Article'));

// Test factory methods
assertSameValue13C('article factory creates article type', 'Article', ArticleJsonLdBuilder::article()->get('@type'));
assertSameValue13C('blogPosting factory creates blog posting type', 'BlogPosting', ArticleJsonLdBuilder::blogPosting()->get('@type'));
assertSameValue13C('newsArticle factory creates news article type', 'NewsArticle', ArticleJsonLdBuilder::newsArticle()->get('@type'));

// Test dynamic type switching
assertSameValue13C('asBlogPosting switches type', 'BlogPosting', $builder->asBlogPosting()->get('@type'));
assertSameValue13C('asNewsArticle switches type', 'NewsArticle', $builder->asNewsArticle()->get('@type'));
assertSameValue13C('asArticle switches type', 'Article', $builder->asArticle()->get('@type'));

// Test fallback to article type on invalid type
assertSameValue13C('fallback on invalid type', 'Article', (new ArticleJsonLdBuilder('InvalidType'))->get('@type'));
assertSameValue13C('fallback on setting invalid type', 'Article', $builder->setType('InvalidType')->get('@type'));

$builder->asArticle(); // Reset

$schema = $builder
    ->setHeadline('Maatify Demo Article')
    ->setDescription('A demo article for JSON-LD output.')
    ->setUrl('https://example.com/articles/demo')
    ->setImage([
        'https://example.com/images/article-1.jpg',
        'https://example.com/images/article-2.jpg',
    ])
    ->setAuthor('Jane Doe')
    ->setPublisher('Maatify Publishing')
    ->setDatePublished('2023-10-27T10:00:00Z')
    ->setDateModified('2023-10-28T14:30:00Z')
    ->setMainEntityOfPage('https://example.com/articles/demo')
    ->setArticleSection('Technology')
    ->setKeywords('maatify, seo, json-ld')
    ->toArray();

assertSameValue13C('full article schema', [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => 'Maatify Demo Article',
    'description' => 'A demo article for JSON-LD output.',
    'url' => 'https://example.com/articles/demo',
    'image' => [
        'https://example.com/images/article-1.jpg',
        'https://example.com/images/article-2.jpg',
    ],
    'author' => [
        '@type' => 'Person',
        'name' => 'Jane Doe',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Maatify Publishing',
    ],
    'datePublished' => '2023-10-27T10:00:00Z',
    'dateModified' => '2023-10-28T14:30:00Z',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => 'https://example.com/articles/demo',
    ],
    'articleSection' => 'Technology',
    'keywords' => 'maatify, seo, json-ld',
], $schema);

assertSameValue13C(
    'single image remains a string',
    'https://example.com/image.jpg',
    (new ArticleJsonLdBuilder())->setImage('https://example.com/image.jpg')->get('image')
);

// Advanced authors, publishers, mainEntity
$schemaAdvanced = (new ArticleJsonLdBuilder())
    ->setAuthor([
        '@type' => 'Organization',
        'name' => 'Maatify Authors',
    ])
    ->setPublisher([
        '@type' => 'Organization',
        'name' => 'Maatify Publishing',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => 'https://example.com/logo.png',
        ],
    ])
    ->setMainEntityOfPage([
        '@type' => 'WebPage',
        '@id' => 'https://example.com/articles/demo',
        'description' => 'Description of main entity',
    ])
    ->toArray();

assertSameValue13C('advanced author, publisher, mainEntity', [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'author' => [
        '@type' => 'Organization',
        'name' => 'Maatify Authors',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Maatify Publishing',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => 'https://example.com/logo.png',
        ],
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => 'https://example.com/articles/demo',
        'description' => 'Description of main entity',
    ],
], $schemaAdvanced);

assertSameValue13C(
    'toJson can encode article schema',
    '{"@context":"https://schema.org","@type":"Article","headline":"Maatify Demo Article"}',
    (new ArticleJsonLdBuilder())->setHeadline('Maatify Demo Article')->toJson(JSON_UNESCAPED_SLASHES)
);

echo "Phase 13C article JSON-LD builder tests passed.\n";
