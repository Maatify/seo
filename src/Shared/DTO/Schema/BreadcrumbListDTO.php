<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

/**
 * @implements \IteratorAggregate<int, BreadcrumbItemDTO>
 */
final readonly class BreadcrumbListDTO implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var list<BreadcrumbItemDTO>
     */
    private array $items;

    /**
     * @param array<mixed> $items
     */
    public function __construct(array $items)
    {
        if ($items === []) {
            throw SeoInvalidArgumentException::emptyField('items');
        }

        $validItems = [];
        foreach ($items as $item) {
            if (!$item instanceof BreadcrumbItemDTO) {
                throw SeoInvalidArgumentException::emptyField('items');
            }
            $validItems[] = $item;
        }

        $this->items = $validItems;
    }

    /**
     * @return \ArrayIterator<int, BreadcrumbItemDTO>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (BreadcrumbItemDTO $item): array => $item->jsonSerialize(),
                $this->items,
            ),
        ];
    }
}
