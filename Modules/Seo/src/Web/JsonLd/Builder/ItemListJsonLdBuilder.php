<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ItemListJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => [],
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }

    /** @param string|array<string, mixed> $item */
    public function addItem(string|array $item, ?string $name = null): static
    {
        $items = $this->get('itemListElement');
        if (!is_array($items)) { $items = []; }
        $items[] = $this->normalizeItem($item, count($items) + 1, $name);
        return $this->set('itemListElement', $items);
    }

    /** @param array<int, string|array<string, mixed>> $items */
    public function setItems(array $items): static
    {
        $normalized = [];
        foreach (array_values($items) as $item) {
            $normalized[] = $this->normalizeItem($item, count($normalized) + 1);
        }
        return $this->set('itemListElement', $normalized);
    }

    public function clearItems(): static { return $this->set('itemListElement', []); }

    /**
     * @param string|array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeItem(string|array $item, int $position, ?string $name = null): array
    {
        if (is_string($item)) {
            $listItem = ['@type' => 'ListItem', 'position' => $position, 'item' => $item];
            if ($name !== null) { $listItem['name'] = $name; }
            return $listItem;
        }

        if (($item['@type'] ?? null) === 'ListItem') {
            $item['position'] = $position;
            if ($name !== null && !isset($item['name'])) { $item['name'] = $name; }
            return $item;
        }

        $listItem = ['@type' => 'ListItem', 'position' => $position, 'item' => $item];
        if ($name !== null) { $listItem['name'] = $name; }
        return $listItem;
    }
}
