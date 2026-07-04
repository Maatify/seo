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
        'language_id' => 1,
        'meta_title' => 'Custom Product Title',
        'meta_description' => 'A custom description for product 123.',
    ]
];

$redirects = [
    [
        'entity_type' => 'product',
        'language_id' => 1,
        'requested_slug' => 'old-product-page',
        'target_entity_type' => 'product',
        'target_entity_id' => '123',
        'http_status' => 301,
    ]
];

$slugHistory = [
    [
        'entity_type' => 'category',
        'entity_id' => '456',
        'language_id' => 1,
        'old_slug' => 'old-category',
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
