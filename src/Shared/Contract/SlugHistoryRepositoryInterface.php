<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Contract;

use Maatify\Seo\Shared\Command\CreateSlugHistoryCommand;
use Maatify\Seo\Shared\DTO\SlugHistoryDTO;

interface SlugHistoryRepositoryInterface
{
    public function create(CreateSlugHistoryCommand $command): int;
    public function findById(int $id): ?SlugHistoryDTO;
    public function findActiveBySlug(string $entityType, int $languageId, string $oldSlug): ?SlugHistoryDTO;
    /** @return list<SlugHistoryDTO> */
    public function findActiveForEntity(string $entityType, string $entityId, int $languageId): array;
    public function softDelete(int $id): bool;
    public function hardDelete(int $id): bool;
}
