<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\UpdateSeoOverrideCommand;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;

final readonly class SeoOverrideCommandService
{
    public function __construct(private SeoOverrideRepositoryInterface $repository)
    {
    }

    public function create(CreateSeoOverrideCommand $command): int
    {
        return $this->repository->create($command);
    }

    public function update(UpdateSeoOverrideCommand $command): void
    {
        if (! $this->repository->update($command)) {
            throw SeoNotFoundException::withId($command->id);
        }
    }

    public function softDelete(int $id): void
    {
        if (! $this->repository->softDelete($id)) {
            throw SeoNotFoundException::withId($id);
        }
    }

    public function hardDelete(int $id): void
    {
        if (! $this->repository->hardDelete($id)) {
            throw SeoNotFoundException::withId($id);
        }
    }
}
