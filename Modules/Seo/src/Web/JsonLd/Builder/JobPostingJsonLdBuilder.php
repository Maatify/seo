<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class JobPostingJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
        ]);
    }

    public function setTitle(string $title): static { return $this->set('title', $title); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    public function setDatePosted(string $datePosted): static { return $this->set('datePosted', $datePosted); }
    public function setValidThrough(string $validThrough): static { return $this->set('validThrough', $validThrough); }
    /** @param string|array<int, string> $employmentType */
    public function setEmploymentType(string|array $employmentType): static { return $this->set('employmentType', $employmentType); }
    /** @param string|array<string, mixed> $hiringOrganization */
    public function setHiringOrganization(string|array $hiringOrganization): static { return $this->set('hiringOrganization', $this->normalizeTypedValue($hiringOrganization, 'Organization', 'name')); }
    /** @param string|array<string, mixed> $jobLocation */
    public function setJobLocation(string|array $jobLocation): static { return $this->set('jobLocation', $this->normalizeTypedValue($jobLocation, 'Place', 'name')); }
    /** @param array<string, mixed> $baseSalary */
    public function setBaseSalary(array $baseSalary): static { return $this->set('baseSalary', $this->defaultTypedValue($baseSalary, 'MonetaryAmount')); }
    /** @param string|array<string, mixed> $requirements */
    public function setApplicantLocationRequirements(string|array $requirements): static { return $this->set('applicantLocationRequirements', $this->normalizeTypedValue($requirements, 'Country', 'name')); }
    public function setJobLocationType(string $jobLocationType): static { return $this->set('jobLocationType', $jobLocationType); }
    public function setDirectApply(bool $directApply): static { return $this->set('directApply', $directApply); }
}
