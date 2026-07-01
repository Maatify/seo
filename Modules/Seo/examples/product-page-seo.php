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
use Maatify\Seo\Shared\DTO\Schema\ProductSchemaDTO;
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
    title: 'Super Widget Pro - Buy Now',
    description: 'The Super Widget Pro is the ultimate widget for professionals. Buy now for only $49.99.',
    canonicalUrl: 'https://example.com/products/super-widget-pro',
    robots: 'index,follow,max-image-preview:large',
    openGraphTitle: 'Super Widget Pro',
    openGraphDescription: 'The Super Widget Pro is the ultimate widget for professionals.',
    openGraphUrl: 'https://example.com/products/super-widget-pro',
    openGraphType: 'product',
    openGraphImage: 'https://cdn.example.com/images/super-widget-pro-og.jpg',
    twitterCard: 'summary_large_image',
    twitterTitle: 'Super Widget Pro',
    twitterDescription: 'The Super Widget Pro is the ultimate widget for professionals. Buy now!',
    twitterImage: 'https://cdn.example.com/images/super-widget-pro-twitter.jpg',
);

$productSchema = new ProductSchemaDTO(
    name: 'Super Widget Pro',
    description: 'The Super Widget Pro is the ultimate widget for professionals.',
    sku: 'WIDGET-PRO-100',
    brandName: 'WidgetCorp',
    additionalProperties: [
        'category' => 'Widgets',
        'offers' => [
            '@type' => 'Offer',
            'price' => '49.99',
            'priceCurrency' => 'USD',
            'availability' => 'https://schema.org/InStock',
            'seller' => [
                '@type' => 'Organization',
                'name' => 'Example.com',
            ],
        ],
    ]
);

$renderer = new SeoHeadHtmlRenderer();
$fullHtml = $renderer->render($metaTags, [$productSchema]);

printSection('Product Page SEO Setup', 'Configuring product page meta tags and schemas.');
printSection('Full Rendered SEO Head HTML', $fullHtml);
