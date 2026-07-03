<?php

declare(strict_types=1);

// Ensure a local autoloader for standalone execution
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

use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;

// Build the Article JSON-LD schema
$builder = ArticleJsonLdBuilder::blogPosting()
    ->setHeadline('Understanding JSON-LD for SEO')
    ->setDescription('A comprehensive guide to implementing JSON-LD for better search engine visibility.')
    ->setUrl('https://example.com/blog/understanding-json-ld')
    ->setAuthor('Jane Doe')
    ->setPublisher('Maatify Publishing')
    ->setDatePublished('2023-10-27T10:00:00Z')
    ->setDateModified('2023-10-28T14:30:00Z')
    ->setImage([
        'https://example.com/images/json-ld-header.jpg',
        'https://example.com/images/json-ld-diagram.png'
    ])
    ->setArticleSection('SEO Technical Guide')
    ->setKeywords('json-ld, seo, structured data, schema.org')
    ->setMainEntityOfPage('https://example.com/blog/understanding-json-ld');

$schema = $builder->toArray();

echo "=== Generated Article JSON-LD Schema Array ===\n";
print_r($schema);
echo "\n";

echo "=== Rendered JSON-LD Script ===\n";
$renderer = new JsonLdScriptRenderer();
echo $renderer->render($schema);
echo "\n";
