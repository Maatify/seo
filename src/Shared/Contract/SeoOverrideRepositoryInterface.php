<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Contract;

use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\UpdateSeoOverrideCommand;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;

interface SeoOverrideRepositoryInterface
{
    public function create(CreateSeoOverrideCommand $command): int;
    public function update(UpdateSeoOverrideCommand $command): bool;
    public function findById(int $id): ?SeoOverrideDTO;
    public function findActiveForEntity(string $entityType, string $entityId, int $languageId): ?SeoOverrideDTO;
    /** @return list<SeoOverrideDTO> */
    public function findByEntity(string $entityType, string $entityId, ?int $languageId = null, bool $includeDeleted = false): array;
    public function softDelete(int $id): bool;
    public function hardDelete(int $id): bool;
}
