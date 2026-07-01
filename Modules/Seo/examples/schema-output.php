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

use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;
use Maatify\Seo\Web\Schema\SpatieSchemaAdapter;

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

// 1. Array Schema
$arraySchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'My Company',
    'url' => 'https://example.com'
];

// 2. JsonLdSchemaDTO
$dtoSchema = new JsonLdSchemaDTO([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'My Website',
    'url' => 'https://example.com'
]);

// 3. Fake Spatie Schema object (mimics spatie/schema-org without depending on it)
class FakeSpatieSchema {
    public function toArray(): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => 'John Doe'
        ];
    }
}
$spatieObject = new FakeSpatieSchema();
$adapter = new SpatieSchemaAdapter();
$adaptedSpatieDto = $adapter->toJsonLdSchemaDTO($spatieObject);


$renderer = new JsonLdScriptRenderer();

printSection('Raw Array Schema Render', $renderer->render($arraySchema));
printSection('JsonLdSchemaDTO Render', $renderer->render($dtoSchema));
printSection('Adapted Fake Spatie Schema Render', $renderer->render($adaptedSpatieDto));

// 4. Multi-schema render simulation
$allSchemas = [$arraySchema, $dtoSchema, $adaptedSpatieDto];
$multiSchemaOutput = '';
foreach ($allSchemas as $schema) {
    $multiSchemaOutput .= $renderer->render($schema) . "\n";
}

printSection('Multiple Schemas Rendered', trim($multiSchemaOutput));
