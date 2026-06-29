<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Contract;

interface HostUrlGeneratorInterface
{
    public function generateEntityUrl(string $entityType, string $entityId, int $languageId, ?string $slug): string;
    public function generateHomeUrl(int $languageId): string;
}
