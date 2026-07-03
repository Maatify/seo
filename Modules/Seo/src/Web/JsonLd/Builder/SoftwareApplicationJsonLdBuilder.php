<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class SoftwareApplicationJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    public function setApplicationCategory(string $applicationCategory): static { return $this->set('applicationCategory', $applicationCategory); }
    /** @param string|array<int, string> $operatingSystem */
    public function setOperatingSystem(string|array $operatingSystem): static { return $this->set('operatingSystem', $operatingSystem); }
    public function setSoftwareVersion(string $softwareVersion): static { return $this->set('softwareVersion', $softwareVersion); }
    /** @param array<string, mixed>|list<array<string, mixed>> $offers */
    public function setOffers(array $offers): static { return $this->set('offers', $offers); }
    /** @param array<string, mixed> $aggregateRating */
    public function setAggregateRating(array $aggregateRating): static { return $this->set('aggregateRating', $this->defaultTypedValue($aggregateRating, 'AggregateRating')); }
    /** @param string|array<string, mixed> $author */
    public function setAuthor(string|array $author): static { return $this->set('author', $this->normalizeTypedValue($author, 'Person', 'name')); }
    /** @param string|array<string, mixed> $publisher */
    public function setPublisher(string|array $publisher): static { return $this->set('publisher', $this->normalizeTypedValue($publisher, 'Organization', 'name')); }
    public function setDownloadUrl(string $downloadUrl): static { return $this->set('downloadUrl', $downloadUrl); }
    /** @param string|array<int, string>|array<string, mixed> $screenshot */
    public function setScreenshot(string|array $screenshot): static { return $this->set('screenshot', $screenshot); }
}
