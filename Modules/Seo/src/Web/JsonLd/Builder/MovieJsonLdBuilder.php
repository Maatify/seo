<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class MovieJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Movie',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setUrl(string $url): static { return $this->set('url', $url); }
    /** @param string|array<int, string>|array<string, mixed> $image */
    public function setImage(string|array $image): static { return $this->set('image', $image); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<string, mixed> $director */
    public function setDirector(string|array $director): static { return $this->set('director', $this->normalizeTypedValue($director, 'Person', 'name')); }
    /** @param array<int, string|array<string, mixed>> $actors */
    public function setActors(array $actors): static
    {
        /** @var list<array<string, mixed>> $normalizedActors */
        $normalizedActors = [];
        foreach ($actors as $actor) {
            $normalizedActors[] = $this->normalizePerson($actor);
        }

        return $this->set('actor', $normalizedActors);
    }
    /** @param string|array<string, mixed> $actor */
    public function addActor(string|array $actor): static { return $this->appendValue('actor', $this->normalizePerson($actor)); }
    /** @param string|array<string, mixed> $productionCompany */
    public function setProductionCompany(string|array $productionCompany): static { return $this->set('productionCompany', $this->normalizeTypedValue($productionCompany, 'Organization', 'name')); }
    public function setDatePublished(string $datePublished): static { return $this->set('datePublished', $datePublished); }
    public function setDuration(string $duration): static { return $this->set('duration', $duration); }
    /** @param string|array<int, string> $genre */
    public function setGenre(string|array $genre): static { return $this->set('genre', $genre); }
    /** @param array<string, mixed> $aggregateRating */
    public function setAggregateRating(array $aggregateRating): static { return $this->set('aggregateRating', $this->defaultTypedValue($aggregateRating, 'AggregateRating')); }

    /**
     * @param string|array<string, mixed> $person
     * @return array<string, mixed>
     */
    private function normalizePerson(string|array $person): array
    {
        return $this->normalizeTypedValue($person, 'Person', 'name');
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
