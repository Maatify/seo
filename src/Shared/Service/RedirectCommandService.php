<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Command\CreateRedirectCommand;
use Maatify\Seo\Shared\Command\UpdateRedirectCommand;
use Maatify\Seo\Shared\Contract\RedirectRepositoryInterface;

final readonly class RedirectCommandService
{
    public function __construct(private RedirectRepositoryInterface $repository)
    {
    }

    public function create(CreateRedirectCommand $command): int
    {
        return $this->repository->create($command);
    }

    public function update(UpdateRedirectCommand $command): void
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
