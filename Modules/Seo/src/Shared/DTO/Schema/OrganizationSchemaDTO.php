<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

final readonly class OrganizationSchemaDTO implements \JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}
