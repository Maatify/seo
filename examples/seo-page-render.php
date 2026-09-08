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

use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\UpdateSeoOverrideCommand;
use Maatify\Seo\Shared\Contract\HostUrlGeneratorInterface;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Shared\DTO\Schema\WebPageSchemaDTO;
use Maatify\Seo\Shared\Service\MetaGeneratorService;
use Maatify\Seo\Shared\Service\SchemaGeneratorService;
use Maatify\Seo\Shared\Service\SeoOverrideQueryService;
use Maatify\Seo\Web\SeoRender\Command\RenderSeoPageCommand;
use Maatify\Seo\Web\SeoRender\Service\SeoPageRenderService;

final class EmptySeoOverrideRepository implements SeoOverrideRepositoryInterface
{
    public function create(CreateSeoOverrideCommand $command): int
    {
        return 1;
    }

    public function update(UpdateSeoOverrideCommand $command): bool
    {
        return false;
    }

    public function findById(int $id): ?SeoOverrideDTO
    {
        return null;
    }

    public function findActiveForEntity(string $entityType, string $entityId, int $languageId): ?SeoOverrideDTO
    {
        return null;
    }

    /** @return list<SeoOverrideDTO> */
    public function findByEntity(
        string $entityType,
        string $entityId,
        ?int $languageId = null,
        bool $includeDeleted = false,
    ): array {
        return [];
    }

    public function softDelete(int $id): bool
    {
        return false;
    }

    public function hardDelete(int $id): bool
    {
        return false;
    }
}

final class ExampleHostUrlGenerator implements HostUrlGeneratorInterface
{
    public function generateEntityUrl(string $entityType, string $entityId, int $languageId, ?string $slug): string
    {
        $language = $languageId === 1 ? 'en' : 'lang-' . $languageId;
        $resolvedSlug = $slug ?? $entityId;

        return 'https://example.com/' . $language . '/' . rawurlencode($entityType) . '/' . rawurlencode($resolvedSlug);
    }

    public function generateHomeUrl(int $languageId): string
    {
        $language = $languageId === 1 ? 'en' : 'lang-' . $languageId;

        return 'https://example.com/' . $language;
    }
}

function printSection(string $title, mixed $value): void
{
    echo "\n==============================\n";
    echo $title . "\n";
    echo "==============================\n";

    $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Unable to encode example output.');
    }

    echo $encoded . "\n";
}

$overrideQueryService = new SeoOverrideQueryService(new EmptySeoOverrideRepository());
$metaGeneratorService = new MetaGeneratorService($overrideQueryService, new ExampleHostUrlGenerator());
$schemaGeneratorService = new SchemaGeneratorService();
$seoPageRenderService = new SeoPageRenderService($metaGeneratorService, $schemaGeneratorService);

$command = new RenderSeoPageCommand(
    entityType: 'page',
    entityId: '42',
    languageId: 1,
    defaultTitle: 'About Example.com',
    defaultDescription: 'Learn how Example.com helps teams publish discoverable content.',
    slug: 'about',
    robots: 'index,follow',
    schemas: [
        new WebPageSchemaDTO(
            name: 'About Example.com',
            url: 'https://example.com/en/page/about',
            description: 'Learn how Example.com helps teams publish discoverable content.',
        ),
    ],
);

$payload = $seoPageRenderService->render($command);

echo "SEO page render orchestration\n";
echo "RenderSeoPageCommand -> SeoPageRenderService -> SeoPagePayloadDTO\n";
printSection('Meta tags / result', $payload->metaTags);
printSection('Schemas / JSON-LD graph', $payload->schemas);
printSection('SeoPagePayloadDTO sections', $payload);
