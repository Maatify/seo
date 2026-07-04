# Batch 2: Admin Previews & Migrations

This document outlines the usage of the Admin Previews and Metadata Import/Export functionality within the Maatify SEO module.

## 1. Admin Previews

Admin Previews allow for generating mock views of Search Engine Results Pages (SERP) and Social Previews (Open Graph / Twitter). These previews can be visualized by consuming host applications in an admin dashboard or CMS.

### 1.1 SERP Preview

The `SerpPreviewDTO` and `SerpPreviewFactory` are used to generate a SERP preview.

**Generating a SERP Preview:**

```php
use Maatify\Seo\Admin\Preview\SerpPreviewFactory;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Page\SeoPagePresetOutputDTO;

// Using a Preset
$presetOutput = new SeoPagePresetOutputDTO(/* ... */);
$serpPreviewDTO = SerpPreviewFactory::fromPreset($presetOutput);

// Or using raw MetaTags
$metaTags = new MetaTagsDTO(title: 'Example Title', description: 'Example Description', canonicalUrl: 'https://example.com/page');
$serpPreviewDTO = SerpPreviewFactory::fromMetaTags($metaTags);

// Export to JSON array for your frontend
$previewArray = $serpPreviewDTO->toArray();
```

The output `SerpPreviewDTO` contains the title, description, display URL (parsed hostname and path), robots directives, warnings (e.g. if the title is empty), and optional fields like score and status.

### 1.2 Social Preview

The `SocialPreviewDTO` and `SocialPreviewFactory` generate previews suitable for Open Graph / Twitter Cards.

**Generating a Social Preview:**

```php
use Maatify\Seo\Admin\Preview\SocialPreviewFactory;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Page\SeoPagePresetOutputDTO;

// Using a Preset
$presetOutput = new SeoPagePresetOutputDTO(/* ... */);
$socialPreviewDTO = SocialPreviewFactory::fromPreset($presetOutput, siteName: 'My Awesome Site');

// Or using raw MetaTags
$metaTags = new MetaTagsDTO(title: 'Example Title', openGraphImage: 'https://example.com/image.jpg', canonicalUrl: 'https://example.com/page');
$socialPreviewDTO = SocialPreviewFactory::fromMetaTags($metaTags, siteName: 'My Awesome Site');

// Export to JSON array for your frontend
$previewArray = $socialPreviewDTO->toArray();
```

The output `SocialPreviewDTO` contains the best available title, description, image URL, URL, object type, site name, Twitter Card type, and validation warnings.

---

## 2. Metadata Migrations (Export & Import)

The export and import functionality enables transferring SEO metadata between environments.

### 2.1 Exporter

The `SeoMetadataExporter` aggregates overrides, redirects, and slug history into a versioned format.

```php
use Maatify\Seo\Admin\Export\SeoMetadataExporter;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Shared\DTO\RedirectDTO;
use Maatify\Seo\Shared\DTO\SlugHistoryDTO;

$exporter = new SeoMetadataExporter();

$overrides = [/* list of SeoOverrideDTO objects */];
$redirects = [/* list of RedirectDTO objects */];
$slugHistory = [/* list of SlugHistoryDTO objects */];

$exportDTO = $exporter->export($overrides, $redirects, $slugHistory);

// Convert to JSON for downloading/transfer
$jsonPayload = $exporter->toJson($exportDTO);
```

The exported schema includes versioning information (`schema_version`) and a timestamp (`exported_at`).

### 2.2 Importer

The `SeoMetadataImporter` parses an array-based schema payload, validates structure, and saves the data to the provided repositories. **Note:** The current repository contracts only support creation; updates are not supported by the importer at this time.

```php
use Maatify\Seo\Admin\Import\SeoMetadataImporter;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\Contract\RedirectRepositoryInterface;
use Maatify\Seo\Shared\Contract\SlugHistoryRepositoryInterface;

/**
 * Instantiate the importer with repositories injected from your framework.
 * Repositories may be null if you do not want to import that specific type of data.
 */
$importer = new SeoMetadataImporter(
    $seoOverrideRepository, // implements SeoOverrideRepositoryInterface
    $redirectRepository,    // implements RedirectRepositoryInterface
    $slugHistoryRepository  // implements SlugHistoryRepositoryInterface
);

// Decoded JSON payload array
$payload = json_decode($jsonPayloadString, true);

// Perform a Dry Run (no data is persisted to the database, repositories are not called)
$dryRunResult = $importer->importArray($payload, dryRun: true);
echo "Would create: " . $dryRunResult->created . " records.";
if (!empty($dryRunResult->errors)) {
    print_r($dryRunResult->errors);
}

// Perform the actual Import
$importResult = $importer->importArray($payload, dryRun: false);
echo "Successfully created " . $importResult->created . " records.";
echo "Skipped (no repository) " . $importResult->skipped . " records.";
echo "Failed to create " . $importResult->failed . " records.";
if (!empty($importResult->errors)) {
    echo "Import encountered errors:\n";
    print_r($importResult->errors);
}
```

The importer carefully validates the payload against `schema_version` and required/optional properties in the respective sections. Malformed payloads result in descriptive validation errors without throwing exceptions abruptly.
