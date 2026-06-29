<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

final readonly class SitemapUrlDTO implements \JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}
