<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Contract;

interface HostEntityProviderInterface
{
    /**
     * Return true if entity was discontinued without replacement (410)
     */
    public function isPermanentlyDiscontinued(string $entityType, string $entityId): bool;

    public function getDiscontinuedReplacementId(string $entityType, string $entityId): ?string;
}
