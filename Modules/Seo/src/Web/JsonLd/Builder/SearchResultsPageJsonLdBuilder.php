<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class SearchResultsPageJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'SearchResultsPage',
            'itemListElement' => [],
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setUrl(string $url): static { return $this->set('url', $url); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<string, mixed> $website */
    public function setIsPartOf(string|array $website): static { return $this->set('isPartOf', $this->normalizeTypedValue($website, 'WebSite', 'url')); }
    /** @param string|array<string, mixed> $breadcrumb */
    public function setBreadcrumb(string|array $breadcrumb): static { return $this->set('breadcrumb', $this->normalizeTypedValue($breadcrumb, 'BreadcrumbList', '@id')); }
    /** @param string|array<string, mixed> $image */
    public function setPrimaryImageOfPage(string|array $image): static { return $this->set('primaryImageOfPage', $this->normalizeTypedValue($image, 'ImageObject', 'url')); }
    public function setDatePublished(string $datePublished): static { return $this->set('datePublished', $datePublished); }
    public function setDateModified(string $dateModified): static { return $this->set('dateModified', $dateModified); }
    /** @param string|array<string, mixed> $about */
    public function setAbout(string|array $about): static { return $this->set('about', $this->normalizeTypedValue($about, 'Thing', 'name')); }
    /** @param string|array<string, mixed> $mainEntity */
    public function setMainEntity(string|array $mainEntity): static { return $this->set('mainEntity', $this->normalizeTypedValue($mainEntity, 'Thing', 'name')); }

    public function setQuery(string $query): static { return $this->set('query', $query); }

    /** @param array<int, string|array<string, mixed>> $items */
    public function setItemListElement(array $items): static
    {
        $normalized = [];
        foreach (array_values($items) as $item) {
            $normalized[] = $this->normalizeResult($item, count($normalized) + 1);
        }
        return $this->set('itemListElement', $normalized);
    }

    /** @param string|array<string, mixed> $item */
    public function addResult(string|array $item, ?string $name = null): static
    {
        $items = $this->get('itemListElement');
        $normalized = is_array($items) ? array_values(array_filter($items, 'is_array')) : [];
        $normalized[] = $this->normalizeResult($item, count($normalized) + 1, $name);
        return $this->set('itemListElement', $normalized);
    }

    /**
     * @param string|array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function normalizeTypedValue(string|array $value, string $type, string $stringKey): array
    {
        if (is_string($value)) {
            return ['@type' => $type, $stringKey => $value];
        }
        if (!isset($value['@type'])) { $value['@type'] = $type; }
        return $value;
    }

    /**
     * @param string|array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeResult(string|array $item, int $position, ?string $name = null): array
    {
        if (is_array($item) && ($item['@type'] ?? null) === 'ListItem') {
            $item['position'] = $position;
            if ($name !== null && !isset($item['name'])) { $item['name'] = $name; }
            return $item;
        }
        $listItem = ['@type' => 'ListItem', 'position' => $position, 'item' => $item];
        if ($name !== null) { $listItem['name'] = $name; }
        return $listItem;
    }
}
