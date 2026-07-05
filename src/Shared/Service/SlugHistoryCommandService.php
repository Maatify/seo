<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Command\CreateSlugHistoryCommand;
use Maatify\Seo\Shared\Contract\SlugHistoryRepositoryInterface;

final readonly class SlugHistoryCommandService
{
    public function __construct(private SlugHistoryRepositoryInterface $repository)
    {
    }

    public function create(CreateSlugHistoryCommand $command): int
    {
        return $this->repository->create($command);
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
