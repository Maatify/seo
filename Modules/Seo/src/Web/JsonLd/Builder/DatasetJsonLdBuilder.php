<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class DatasetJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setUrl(string $url): static { return $this->set('url', $url); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<string, mixed> $creator */
    public function setCreator(string|array $creator): static { return $this->set('creator', $this->normalizeTypedValue($creator, 'Person', 'name')); }
    /** @param string|array<string, mixed> $publisher */
    public function setPublisher(string|array $publisher): static { return $this->set('publisher', $this->normalizeTypedValue($publisher, 'Organization', 'name')); }
    public function setDatePublished(string $datePublished): static { return $this->set('datePublished', $datePublished); }
    public function setDateModified(string $dateModified): static { return $this->set('dateModified', $dateModified); }
    /** @param string|array<string, mixed> $license */
    public function setLicense(string|array $license): static { return $this->set('license', $license); }
    /** @param string|array<int, string> $keywords */
    public function setKeywords(string|array $keywords): static { return $this->set('keywords', $keywords); }
    /** @param array<string, mixed>|list<array<string, mixed>> $distribution */
    public function setDistribution(array $distribution): static
    {
        if (!array_is_list($distribution)) {
            return $this->set('distribution', $this->defaultTypedValue($distribution, 'DataDownload'));
        }

        /** @var list<array<string, mixed>> $distributionList */
        $distributionList = array_values($distribution);
        /** @var list<array<string, mixed>> $normalizedDistribution */
        $normalizedDistribution = [];
        foreach ($distributionList as $distributionItem) {
            $normalizedDistribution[] = $this->normalizeDistribution($distributionItem);
        }

        return $this->set('distribution', $normalizedDistribution);
    }
    /** @param array<string, mixed> $distribution */
    public function addDistribution(array $distribution): static { return $this->appendValue('distribution', $this->normalizeDistribution($distribution)); }
    /** @param string|array<string, mixed> $spatialCoverage */
    public function setSpatialCoverage(string|array $spatialCoverage): static { return $this->set('spatialCoverage', $this->normalizeTypedValue($spatialCoverage, 'Place', 'name')); }
    public function setTemporalCoverage(string $temporalCoverage): static { return $this->set('temporalCoverage', $temporalCoverage); }

    /**
     * @param array<string, mixed> $distribution
     * @return array<string, mixed>
     */
    private function normalizeDistribution(array $distribution): array
    {
        return $this->defaultTypedValue($distribution, 'DataDownload');
    }

    /** @param array<string, mixed> $value */
    private function appendValue(string $key, array $value): static
    {
        $values = $this->get($key);
        if (!is_array($values)) {
            $values = [];
        } elseif (isset($values['@type'])) {
            /** @var array<string, mixed> $existingValue */
            $existingValue = $values;
            $values = [$existingValue];
        } else {
            /** @var list<array<string, mixed>> $values */
            $values = array_values($values);
        }

        $values[] = $value;

        return $this->set($key, $values);
    }
}
