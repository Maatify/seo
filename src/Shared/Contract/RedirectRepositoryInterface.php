<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Contract;

use Maatify\Seo\Shared\Command\CreateRedirectCommand;
use Maatify\Seo\Shared\Command\UpdateRedirectCommand;
use Maatify\Seo\Shared\DTO\RedirectDTO;

interface RedirectRepositoryInterface
{
    public function create(CreateRedirectCommand $command): int;
    public function update(UpdateRedirectCommand $command): bool;
    public function findById(int $id): ?RedirectDTO;
    public function findActiveByRequestedSlug(string $entityType, int $languageId, string $requestedSlug): ?RedirectDTO;
    /** @return list<RedirectDTO> */
    public function findByEntity(string $entityType, ?int $languageId = null, bool $includeDeleted = false): array;
    public function softDelete(int $id): bool;
    public function hardDelete(int $id): bool;
}
