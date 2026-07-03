<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ServiceJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Service',
        ]);
    }

    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    public function setDescription(string $description): static
    {
        return $this->set('description', $description);
    }

    public function setServiceType(string $serviceType): static
    {
        return $this->set('serviceType', $serviceType);
    }

    /** @param string|array<string, mixed> $provider */
    public function setProvider(string|array $provider): static
    {
        return $this->set('provider', $this->normalizeTypedValue($provider, 'Organization'));
    }

    /** @param string|array<string, mixed> $areaServed */
    public function setAreaServed(string|array $areaServed): static
    {
        return $this->set('areaServed', $this->normalizeTypedValue($areaServed, 'Place'));
    }

    /** @param array<string, mixed>|list<array<string, mixed>> $offers */
    public function setOffers(array $offers): static
    {
        return $this->set('offers', $offers);
    }

    /** @param array<string, mixed> $aggregateRating */
    public function setAggregateRating(array $aggregateRating): static
    {
        return $this->set('aggregateRating', $this->withDefaultType($aggregateRating, 'AggregateRating'));
    }

    /**
     * @param string|array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function normalizeTypedValue(string|array $value, string $type): array
    {
        if (is_string($value)) {
            return ['@type' => $type, 'name' => $value];
        }

        return $this->withDefaultType($value, $type);
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function withDefaultType(array $schema, string $type): array
    {
        if (!isset($schema['@type'])) {
            $schema['@type'] = $type;
        }

        return $schema;
    }
}
