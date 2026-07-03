<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class AggregateRatingJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'AggregateRating',
        ]);
    }

    public function setRatingValue(int|float|string $ratingValue): static
    {
        return $this->set('ratingValue', $ratingValue);
    }

    public function setReviewCount(int $reviewCount): static
    {
        return $this->set('reviewCount', $reviewCount);
    }

    public function setRatingCount(int $ratingCount): static
    {
        return $this->set('ratingCount', $ratingCount);
    }

    public function setBestRating(int|float|string $bestRating): static
    {
        return $this->set('bestRating', $bestRating);
    }

    public function setWorstRating(int|float|string $worstRating): static
    {
        return $this->set('worstRating', $worstRating);
    }
}
