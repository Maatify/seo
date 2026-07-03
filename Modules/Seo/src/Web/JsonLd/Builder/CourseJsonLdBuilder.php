<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class CourseJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<string, mixed> $provider */
    public function setProvider(string|array $provider): static { return $this->set('provider', $this->normalizeTypedValue($provider, 'Organization', 'name')); }
    public function setCourseCode(string $courseCode): static { return $this->set('courseCode', $courseCode); }
    public function setEducationalCredentialAwarded(string $credential): static { return $this->set('educationalCredentialAwarded', $credential); }
    /** @param array<string, mixed> $courseInstance */
    public function setHasCourseInstance(array $courseInstance): static { return $this->set('hasCourseInstance', $this->defaultTypedValue($courseInstance, 'CourseInstance')); }
    /** @param array<string, mixed> $courseInstance */
    public function addCourseInstance(array $courseInstance): static
    {
        $instances = $this->get('hasCourseInstance');
        if (!is_array($instances) || isset($instances['@type'])) { $instances = $instances === null ? [] : [$instances]; }
        $instances[] = $this->defaultTypedValue($courseInstance, 'CourseInstance');
        return $this->set('hasCourseInstance', $instances);
    }
    /** @param array<string, mixed>|list<array<string, mixed>> $offers */
    public function setOffers(array $offers): static { return $this->set('offers', $offers); }
    /** @param array<string, mixed> $aggregateRating */
    public function setAggregateRating(array $aggregateRating): static { return $this->set('aggregateRating', $this->defaultTypedValue($aggregateRating, 'AggregateRating')); }
}
