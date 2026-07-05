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

use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;

function printSection(string $title, mixed $output): void
{
    echo "\n==============================\n";
    echo $title . "\n";
    echo "==============================\n";
    if (is_string($output)) {
        echo $output . "\n";
    } elseif (is_array($output)) {
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}

$metaTags = new MetaTagsDTO(
    title: 'My Basic Webpage - Example.com',
    description: 'This is a basic example of rendering SEO head tags.',
    canonicalUrl: 'https://example.com/basic-page',
    robots: 'index,follow',
    openGraphTitle: 'My Basic Webpage',
    openGraphDescription: 'This is a basic example of rendering SEO head tags via OpenGraph.',
    openGraphUrl: 'https://example.com/basic-page',
    openGraphType: 'website',
);

$schemaArray = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'My Basic Webpage',
    'description' => 'This is a basic example of rendering SEO head tags.',
];

$renderer = new SeoHeadHtmlRenderer();
$fullHtml = $renderer->render($metaTags, [$schemaArray]);

printSection('MetaTagsDTO Instance', 'Configured basic meta tags.');
printSection('Basic JSON-LD Schema Array', $schemaArray);
printSection('Full Rendered SEO Head HTML', $fullHtml);
