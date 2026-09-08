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

use Maatify\Seo\Shared\Command\GenerateMetaTagsCommand;
use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\UpdateSeoOverrideCommand;
use Maatify\Seo\Shared\Contract\HostUrlGeneratorInterface;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Shared\Service\MetaGeneratorService;
use Maatify\Seo\Shared\Service\SeoOverrideCommandService;
use Maatify\Seo\Shared\Service\SeoOverrideQueryService;

final class InMemorySeoOverrideRepository implements SeoOverrideRepositoryInterface
{
    /** @var array<int, SeoOverrideDTO> */
    private array $records = [];

    private int $nextId = 1;

    public function create(CreateSeoOverrideCommand $command): int
    {
        $id = $this->nextId++;
        $this->records[$id] = new SeoOverrideDTO(
            id: $id,
            entityType: $command->entityType,
            entityId: $command->entityId,
            languageId: $command->languageId,
            metaTitle: $command->metaTitle,
            metaDescription: $command->metaDescription,
            createdAt: '2026-09-08T12:00:00+00:00',
            updatedAt: '2026-09-08T12:00:00+00:00',
            deletedAt: null,
        );

        return $id;
    }

    public function update(UpdateSeoOverrideCommand $command): bool
    {
        $record = $this->records[$command->id] ?? null;
        if ($record === null || $record->deletedAt !== null) {
            return false;
        }

        $this->records[$command->id] = new SeoOverrideDTO(
            id: $record->id,
            entityType: $record->entityType,
            entityId: $record->entityId,
            languageId: $record->languageId,
            metaTitle: $command->metaTitle,
            metaDescription: $command->metaDescription,
            createdAt: $record->createdAt,
            updatedAt: '2026-09-08T12:05:00+00:00',
            deletedAt: $record->deletedAt,
        );

        return true;
    }

    public function findById(int $id): ?SeoOverrideDTO
    {
        return $this->records[$id] ?? null;
    }

    public function findActiveForEntity(string $entityType, string $entityId, int $languageId): ?SeoOverrideDTO
    {
        foreach (array_reverse($this->records, true) as $record) {
            if ($record->deletedAt === null
                && $record->entityType === $entityType
                && $record->entityId === $entityId
                && $record->languageId === $languageId
            ) {
                return $record;
            }
        }

        return null;
    }

    /** @return list<SeoOverrideDTO> */
    public function findByEntity(string $entityType, string $entityId, ?int $languageId = null, bool $includeDeleted = false): array
    {
        $matches = [];
        foreach (array_reverse($this->records, true) as $record) {
            if ($record->entityType !== $entityType
                || $record->entityId !== $entityId
                || ($languageId !== null && $record->languageId !== $languageId)
                || (!$includeDeleted && $record->deletedAt !== null)
            ) {
                continue;
            }

            $matches[] = $record;
        }

        return $matches;
    }

    public function softDelete(int $id): bool
    {
        $record = $this->records[$id] ?? null;
        if ($record === null || $record->deletedAt !== null) {
            return false;
        }

        $this->records[$id] = new SeoOverrideDTO(
            id: $record->id,
            entityType: $record->entityType,
            entityId: $record->entityId,
            languageId: $record->languageId,
            metaTitle: $record->metaTitle,
            metaDescription: $record->metaDescription,
            createdAt: $record->createdAt,
            updatedAt: $record->updatedAt,
            deletedAt: '2026-09-08T12:10:00+00:00',
        );

        return true;
    }

    public function hardDelete(int $id): bool
    {
        if (!isset($this->records[$id])) {
            return false;
        }

        unset($this->records[$id]);
        return true;
    }
}

final class ExampleMetaUrlGenerator implements HostUrlGeneratorInterface
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

        return 'https://example.com/' . $language . '/';
    }
}

function printOverride(string $label, SeoOverrideDTO $override): void
{
    echo "\n{$label}\n";
    echo "------------------------------\n";
    echo 'ID: ' . $override->id . "\n";
    echo 'Entity: ' . $override->entityType . ':' . $override->entityId . "\n";
    echo 'Manual title: ' . ($override->metaTitle ?? '(none)') . "\n";
    echo 'Manual description: ' . ($override->metaDescription ?? '(none)') . "\n";
}

function printMetaResult(string $label, MetaTagsDTO $metaTags): void
{
    echo "\n{$label}\n";
    echo "------------------------------\n";
    echo 'Final title: ' . $metaTags->title . "\n";
    echo 'Final description: ' . ($metaTags->description ?? '(none)') . "\n";
    echo 'Canonical URL: ' . ($metaTags->canonicalUrl ?? '(none)') . "\n";
    echo 'Robots: ' . $metaTags->robots . "\n";
}

$repository = new InMemorySeoOverrideRepository();
$overrideCommandService = new SeoOverrideCommandService($repository);
$overrideQueryService = new SeoOverrideQueryService($repository);
$metaGeneratorService = new MetaGeneratorService(
    overrideQueryService: $overrideQueryService,
    urlGenerator: new ExampleMetaUrlGenerator(),
);

$languageId = 1;
$productType = 'product';
$productId = '42';

$overrideId = $overrideCommandService->create(new CreateSeoOverrideCommand(
    entityType: $productType,
    entityId: $productId,
    languageId: $languageId,
    metaTitle: 'Manual Product Title | Example Store',
    metaDescription: 'Manual product description supplied by the SEO override workflow.',
));
$override = $overrideQueryService->getActiveForEntity($productType, $productId, $languageId);

echo "\n==============================\n";
echo "SEO Override + Meta Generation\n";
echo "==============================\n";
echo "\n1. Override-present case\n";
echo "==============================\n";
echo "Created SEO override ID: {$overrideId}\n";
printOverride('Queried active SEO override', $override);

$overrideMeta = $metaGeneratorService->generate(new GenerateMetaTagsCommand(
    entityType: $productType,
    entityId: $productId,
    languageId: $languageId,
    defaultTitle: 'Default Product Title',
    defaultDescription: 'Default product description used when no manual override exists.',
    slug: 'super-widget-pro',
    canonicalUrl: 'https://example.com/products/super-widget-pro',
));
printMetaResult('MetaGeneratorService result with manual override', $overrideMeta);

$articleType = 'article';
$articleId = '99';
$fallbackMeta = $metaGeneratorService->generate(new GenerateMetaTagsCommand(
    entityType: $articleType,
    entityId: $articleId,
    languageId: $languageId,
    defaultTitle: 'Default Article Title',
    defaultDescription: 'Default article description used by the fallback path.',
    slug: 'seo-library-integration',
));

echo "\n2. Override-absent case\n";
echo "==============================\n";
echo "No active SEO override exists for {$articleType}:{$articleId}; defaults are retained.\n";
printMetaResult('MetaGeneratorService fallback result', $fallbackMeta);
echo "Canonical source: HostUrlGeneratorInterface because no explicit canonical URL was passed.\n";
