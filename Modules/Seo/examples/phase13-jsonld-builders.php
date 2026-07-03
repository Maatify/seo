<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
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
}

use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;

function printSection(string $title, string $output): void
{
    echo "\n==============================\n";
    echo $title . "\n";
    echo "==============================\n";
    echo $output . "\n";
}

$renderer = new JsonLdScriptRenderer();

// --- Product JSON-LD Builder ---
$productBuilder = new ProductJsonLdBuilder();
$productBuilder
    ->setName('Maatify Demo Product')
    ->setDescription('A demo product showcasing the JSON-LD Builder.')
    ->setSku('DEMO-PROD-01')
    ->setBrand('Maatify')
    ->setImage('https://example.com/images/product.jpg')
    ->setCategory('Software')
    ->setUrl('https://example.com/products/demo-product')
    ->setCurrency('USD')
    ->setPrice('29.99')
    ->setAvailability('https://schema.org/InStock')
    ->setCondition('https://schema.org/NewCondition')
    ->setAggregateRating(4.9, 150);

printSection('Product Schema Output (rendered via JsonLdScriptRenderer)', $renderer->render($productBuilder->toArray()));

// --- Article JSON-LD Builder ---
$articleBuilder = ArticleJsonLdBuilder::blogPosting();
$articleBuilder
    ->setHeadline('How to Use JSON-LD Builders')
    ->setDescription('A comprehensive guide to structured data in Maatify.')
    ->setUrl('https://example.com/blog/how-to-use-json-ld-builders')
    ->setImage([
        'https://example.com/images/article-1x1.jpg',
        'https://example.com/images/article-16x9.jpg',
    ])
    ->setAuthor('Jane Doe')
    ->setPublisher('Maatify Media')
    ->setDatePublished('2023-10-01T10:00:00+00:00')
    ->setDateModified('2023-10-05T12:30:00+00:00')
    ->setMainEntityOfPage('https://example.com/blog/how-to-use-json-ld-builders')
    ->setKeywords(['SEO', 'JSON-LD', 'PHP']);

printSection('Article/BlogPosting Schema Output (rendered via JsonLdScriptRenderer)', $renderer->render($articleBuilder->toArray()));
