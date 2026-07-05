<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class BreadcrumbJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [],
        ]);
    }

    public function addItem(string $name, string $url): static
    {
        $items = $this->get('itemListElement');
        if (!is_array($items)) {
            $items = [];
        }

        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $name,
            'item' => $url,
        ];

        return $this->set('itemListElement', $items);
    }

    public function addBreadcrumb(string $name, string $url): static
    {
        return $this->addItem($name, $url);
    }

    /**
     * @param array<int, array{name: string, url: string}> $items
     */
    public function addItems(array $items): static
    {
        foreach ($items as $item) {
            $this->addItem($item['name'], $item['url']);
        }

        return $this;
    }

    public function clearItems(): static
    {
        return $this->set('itemListElement', []);
    }
}
