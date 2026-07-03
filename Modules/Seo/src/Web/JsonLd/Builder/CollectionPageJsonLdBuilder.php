<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class CollectionPageJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
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

    /** @param array<int, string|array<string, mixed>> $hasPart */
    public function setHasPart(array $hasPart): static
    {
        return $this->set('hasPart', array_map(fn (string|array $part): array => $this->normalizeTypedValue($part, 'WebPage', 'url'), array_values($hasPart)));
    }

    /** @param string|array<string, mixed> $part */
    public function addHasPart(string|array $part): static
    {
        $hasPart = $this->get('hasPart');
        $normalized = is_array($hasPart) ? array_values(array_filter($hasPart, 'is_array')) : [];
        $normalized[] = $this->normalizeTypedValue($part, 'WebPage', 'url');
        return $this->set('hasPart', $normalized);
    }

}
