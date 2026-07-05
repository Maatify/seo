<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Maatify\\Seo\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require_once $path;
});

use Maatify\Seo\Admin\DTO\SeoMetadataImportResultDTO;
use Maatify\Seo\Admin\DTO\SerpPreviewDTO;
use Maatify\Seo\Admin\DTO\SocialPreviewDTO;
use Maatify\Seo\Admin\Export\SeoMetadataExporter;
use Maatify\Seo\Admin\Import\SeoMetadataImporter;
use Maatify\Seo\Admin\Preview\SerpPreviewFactory;
use Maatify\Seo\Admin\Preview\SocialPreviewFactory;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\RedirectDTO;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Shared\DTO\SlugHistoryDTO;
use Maatify\Seo\Web\Page\SeoPagePresetOutputDTO;

$failures = 0;
function ok(bool $value, string $message): void { global $failures; if (!$value) { $failures++; echo "FAIL: $message\n"; } }
function same(mixed $expected, mixed $actual, string $message): void { ok($expected === $actual, $message); }

echo "Running Batch 2 Admin Previews & Migrations Tests...\n\n";

$serp = new SerpPreviewDTO('Title', 'Description', 'https://example.com/a', 'example.com/a', 'index,follow', ['note'], 90, 'ok');
same('Title', $serp->toArray()['title'], 'SERP DTO serializes title');
same('example.com/a', $serp->jsonSerialize()['display_url'], 'SERP DTO serializes display URL');

$social = new SocialPreviewDTO('Title', 'Description', 'https://example.com/i.jpg', 'https://example.com/a', 'article', 'Example', 'summary_large_image', ['note']);
same('https://example.com/i.jpg', $social->toArray()['image_url'], 'Social DTO serializes image URL');
same('summary_large_image', $social->jsonSerialize()['twitter_card'], 'Social DTO serializes Twitter card');

$meta = new MetaTagsDTO('Preset Title', 'Preset Description', 'https://example.com/preset', 'index, follow', openGraphTitle: 'OG Title', openGraphUrl: 'https://example.com/og', openGraphType: 'article', openGraphImage: 'https://example.com/og.jpg', twitterCard: 'summary_large_image');
$preset = new SeoPagePresetOutputDTO($meta, 'https://example.com/preset', 'index, follow');
same('example.com/preset', SerpPreviewFactory::fromPreset($preset)->displayUrl, 'SERP factory builds from SeoPagePresetOutputDTO');
same('OG Title', SocialPreviewFactory::fromPreset($preset, 'Example')->title, 'Social factory builds from SeoPagePresetOutputDTO');

$exporter = new SeoMetadataExporter();
$export = $exporter->export(
    [new SeoOverrideDTO(1, 'product', '10', 1, 'Meta', 'Desc', '2026-01-01', '2026-01-01', null)],
    [new RedirectDTO(1, 'product', 1, 'old', 'product', '10', 301, '2026-01-01', null)],
    [new SlugHistoryDTO(1, 'product', '10', 1, 'old', '2026-01-01', null)]
);
same('1.0', $export->toArray()['schema_version'], 'Exporter produces versioned output');
same(1, count($export->toArray()['data']['redirects']), 'Exporter includes redirects');
$json = $exporter->toJson($export);
ok(json_decode($json, true) !== null, 'Exporter JSON output is valid');

$importer = new SeoMetadataImporter();
$bad = $importer->importArray(['schema_version' => 'bad']);
ok($bad->failed > 0 && $bad->errors !== [], 'Importer validates malformed payloads');
$dryRun = $importer->importArray($export->toArray(), true);
same(3, $dryRun->created, 'Importer dry-run counts all importable rows without persistence');
same(true, $dryRun->dryRun, 'Importer dry-run flag is preserved');
$result = new SeoMetadataImportResultDTO(1, 2, 3, 4, ['err'], true);
same(2, $result->toArray()['updated'], 'Import result DTO serializes updated count');

$source = file_get_contents(__DIR__ . '/../src/Admin/Export/SeoMetadataExporter.php') . file_get_contents(__DIR__ . '/../src/Admin/Import/SeoMetadataImporter.php');
ok(is_string($source) && !str_contains($source, 'Illuminate\\') && !str_contains($source, 'Symfony\\') && !str_contains($source, 'Response'), 'Admin migration helpers have no framework/HTTP coupling strings');

echo "\n";
if ($failures > 0) { echo "FAILED with $failures errors.\n"; exit(1); }
echo "SUCCESS: All tests passed.\n"; exit(0);
