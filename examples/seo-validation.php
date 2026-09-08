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

use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationReportExporter;

$pageMetadata = [
    'title' => 'SEO Validation Example Page',
    'description' => 'Short example description.',
    'canonical' => 'https://example.com/guides/seo-validation',
    'robots' => 'index,follow',
    'openGraph' => [
        'title' => 'SEO Validation Example Page',
        'description' => 'A representative OpenGraph description for this example page.',
        'image' => 'https://cdn.example.com/images/seo-validation.jpg',
    ],
    'twitter' => [
        'card' => 'summary_large_image',
        'title' => 'SEO Validation Example Page',
        'description' => 'A representative Twitter description for this example page.',
    ],
];

$report = SeoValidationReportBuilder::build(
    meta: $pageMetadata,
    context: [
        'page' => 'https://example.com/guides/seo-validation',
        'source' => 'Phase 20 WU2 example',
    ],
);

echo "\n==============================\n";
echo "Page SEO Validation\n";
echo "==============================\n";
echo SeoValidationReportExporter::toMarkdown($report);
