<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ReviewJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Review',
        ]);
    }

    /** @param string|array<string, mixed> $itemReviewed */
    public function setItemReviewed(string|array $itemReviewed): static
    {
        return $this->set('itemReviewed', $itemReviewed);
    }

    /** @param int|float|string|array<string, mixed> $rating */
    public function setReviewRating(int|float|string|array $rating, ?float $bestRating = null, ?float $worstRating = null): static
    {
        if (!is_array($rating)) {
            $rating = [
                '@type' => 'Rating',
                'ratingValue' => $rating,
            ];
        } elseif (!isset($rating['@type'])) {
            $rating['@type'] = 'Rating';
        }

        if ($bestRating !== null) {
            $rating['bestRating'] = $bestRating;
        }
        if ($worstRating !== null) {
            $rating['worstRating'] = $worstRating;
        }

        return $this->set('reviewRating', $rating);
    }

    /** @param string|array<string, mixed> $author */
    public function setAuthor(string|array $author): static
    {
        return $this->set('author', $this->normalizeTypedValue($author, 'Person'));
    }

    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    public function setReviewBody(string $reviewBody): static
    {
        return $this->set('reviewBody', $reviewBody);
    }

    public function setDatePublished(string $datePublished): static
    {
        return $this->set('datePublished', $datePublished);
    }

    /** @param string|array<string, mixed> $publisher */
    public function setPublisher(string|array $publisher): static
    {
        return $this->set('publisher', $this->normalizeTypedValue($publisher, 'Organization'));
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
        if (!isset($value['@type'])) {
            $value['@type'] = $type;
        }

        return $value;
    }
}
