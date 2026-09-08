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
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationReportExporter;

$metaTags = new MetaTagsDTO(
    title: 'Super Widget Pro - Product SEO Audit',
    description: 'A representative product page used to demonstrate the Product SEO audit pipeline.',
    canonicalUrl: 'https://example.com/products/super-widget-pro',
    robots: 'index,follow',
    openGraphTitle: 'Super Widget Pro',
    openGraphDescription: 'The Super Widget Pro is a representative product for this audit.',
    openGraphUrl: 'https://example.com/products/super-widget-pro',
    openGraphType: 'product',
    openGraphImage: 'https://cdn.example.com/images/super-widget-pro.jpg',
    twitterCard: 'summary_large_image',
    twitterTitle: 'Super Widget Pro',
    twitterDescription: 'Product SEO audit example for the Super Widget Pro.',
    twitterImage: 'https://cdn.example.com/images/super-widget-pro-twitter.jpg',
);

$productSchema = new ProductSchemaDTO(
    name: 'Super Widget Pro',
    description: 'The Super Widget Pro is a representative product for SEO auditing.',
    sku: 'WIDGET-PRO-100',
    brandName: 'WidgetCorp',
    additionalProperties: [
        'image' => 'https://cdn.example.com/images/super-widget-pro.jpg',
        'category' => 'Widgets',
        'offers' => [
            '@type' => 'Offer',
            // Deliberately malformed so the audit demonstrates a real finding.
            'price' => ['unexpected' => 'shape'],
            'priceCurrency' => 'USD',
            'availability' => 'https://schema.org/InStock',
        ],
    ],
);

$auditInput = $metaTags->jsonSerialize();
$auditInput['jsonLd'] = [$productSchema->jsonSerialize()];

$report = SeoValidationReportBuilder::build(
    meta: $auditInput,
    context: [
        'page' => 'https://example.com/products/super-widget-pro',
        'audit' => 'Product SEO',
        'structuredData' => 'Product JSON-LD',
        'validationPipeline' => 'SeoValidationReportBuilder -> SeoMetaValidator',
    ],
);

$reportData = SeoValidationReportExporter::toArray($report);
$structuredDataFindings = array_filter(
    $reportData['issues'],
    static fn (array $issue): bool => is_string($issue['field']) && str_starts_with($issue['field'], 'jsonLd'),
);

echo "\n==============================\n";
echo "Product SEO Audit\n";
echo "==============================\n";
echo 'Product structured data evaluated by SeoMetaValidator: ' . ($structuredDataFindings === [] ? 'yes (no findings)' : 'yes') . "\n";
echo 'Product structured-data findings: ' . count($structuredDataFindings) . "\n\n";
echo SeoValidationReportExporter::toMarkdown($report);
