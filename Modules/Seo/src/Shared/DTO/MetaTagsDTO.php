<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO;

final readonly class MetaTagsDTO implements \JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}
