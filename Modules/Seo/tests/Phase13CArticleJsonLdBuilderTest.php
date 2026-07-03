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

$schema = $builder
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

assertSameValue13C('full article schema', [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => 'Maatify Demo Article',
    'description' => 'A demo article for JSON-LD output.',
    'url' => 'https://example.com/article/demo',
    'image' => [
        'https://example.com/images/article-front.jpg',
        'https://example.com/images/article-side.jpg',
    ],
    'author' => [
        '@type' => 'Person',
        'name' => 'Jane Doe',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Maatify Organization',
    ],
    'datePublished' => '2023-07-03T08:00:00+00:00',
    'dateModified' => '2023-07-03T10:00:00+00:00',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => 'https://example.com/article/demo',
    ],
    'articleSection' => 'Technology',
    'keywords' => ['SEO', 'JSON-LD', 'PHP'],
], $schema);

assertSameValue13C('single image remains a string', 'https://example.com/image.jpg', (new ArticleJsonLdBuilder())->setImage('https://example.com/image.jpg')->get('image'));
assertSameValue13C('asBlogPosting works', 'BlogPosting', (new ArticleJsonLdBuilder())->asBlogPosting()->get('@type'));
assertSameValue13C('asNewsArticle works', 'NewsArticle', (new ArticleJsonLdBuilder())->asNewsArticle()->get('@type'));
assertSameValue13C('asArticle works', 'Article', (new ArticleJsonLdBuilder())->asNewsArticle()->asArticle()->get('@type'));

assertSameValue13C('static article factory', 'Article', ArticleJsonLdBuilder::article()->get('@type'));
assertSameValue13C('static blogPosting factory', 'BlogPosting', ArticleJsonLdBuilder::blogPosting()->get('@type'));
assertSameValue13C('static newsArticle factory', 'NewsArticle', ArticleJsonLdBuilder::newsArticle()->get('@type'));


echo "Phase 13C article JSON-LD builder tests passed.\n";
