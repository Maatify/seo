<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Maatify\Seo\Admin\Export\SeoMetadataExporter;
use Maatify\Seo\Admin\Import\SeoMetadataImporter;

echo "--- SEO Metadata Export ---\n";

$exporter = new SeoMetadataExporter();

$seoOverrides = [
    [
        'entity_type' => 'product',
        'entity_id' => '123',
        'title' => 'Custom Product Title',
        'description' => 'A custom description for product 123.',
        'is_active' => true,
    ]
];

$redirects = [
    [
        'source_url' => '/old-product-page',
        'destination_url' => '/product/new-product-page',
        'status_code' => 301,
        'is_active' => true,
    ]
];

$slugHistory = [
    [
        'entity_type' => 'category',
        'entity_id' => '456',
        'old_slug' => 'old-category',
        'new_slug' => 'new-category',
    ]
];

// Export generates a standardized DTO
$exportDto = $exporter->export($seoOverrides, $redirects, $slugHistory);

// Representing the export as JSON for file storage or transmission
$exportJson = json_encode($exportDto->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo $exportJson . "\n\n";

echo "--- SEO Metadata Import (Dry Run) ---\n";

// The importer doesn't require actual repositories if we only want to validate or do a dry run
// We omit repositories here as we have no DB dependency in this example.
$importer = new SeoMetadataImporter(null, null, null);

// Import the JSON string, setting dryRun to true
$importResult = $importer->importJson($exportJson, true);

echo "Dry Run Status: " . ($importResult->dryRun ? 'Enabled' : 'Disabled') . "\n";
echo "Created Items: " . $importResult->created . "\n";
echo "Updated Items: " . $importResult->updated . "\n";
echo "Failed Items: " . $importResult->failed . "\n";
echo "Errors Count: " . count($importResult->errors) . "\n";

if (!empty($importResult->errors)) {
    echo "Errors:\n";
    print_r($importResult->errors);
}

echo "Done.\n";
