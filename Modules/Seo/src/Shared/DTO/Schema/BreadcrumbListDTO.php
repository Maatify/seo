<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

/**
 * @implements \IteratorAggregate<int, BreadcrumbItemDTO>
 */
final readonly class BreadcrumbListDTO implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @return \ArrayIterator<int, BreadcrumbItemDTO>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator([]);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}
