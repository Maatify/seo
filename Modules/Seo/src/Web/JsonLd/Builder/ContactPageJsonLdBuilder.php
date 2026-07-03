<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ContactPageJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
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


    /** @param string|array<string, mixed> $contactPoint */
    public function setContactPoint(string|array $contactPoint): static { return $this->set('contactPoint', $this->normalizeTypedValue($contactPoint, 'ContactPoint', 'contactType')); }

    /**
     * @param string|array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function normalizeTypedValue(string|array $value, string $type, string $stringKey): array
    {
        if (is_string($value)) {
            return ['@type' => $type, $stringKey => $value];
        }

        if (!isset($value['@type'])) {
            $value['@type'] = $type;
        }

        return $value;
    }
}