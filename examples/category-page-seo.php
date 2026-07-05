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

use Maatify\Seo\Web\Builder\FluentSeoBuilder;

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

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => 'https://example.com'
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Widgets',
            'item' => 'https://example.com/widgets'
        ]
    ]
];

$itemListSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'url' => 'https://example.com/products/super-widget-pro'
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'url' => 'https://example.com/products/basic-widget'
        ]
    ]
];

$builder = (new FluentSeoBuilder())
    ->title('Widgets Category - Example.com')
    ->description('Browse our collection of high quality widgets.')
    ->canonical('https://example.com/widgets')
    ->openGraphTitle('Widgets Category')
    ->openGraphType('website')
    ->schemas([$breadcrumbSchema, $itemListSchema]);

printSection('Category Page SEO Setup (Fluent Builder)', 'Building category page meta tags and schemas.');
printSection('Full Rendered SEO Head HTML', $builder->render());
