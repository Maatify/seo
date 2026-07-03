<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ProductJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
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

    public function setSku(string $sku): static
    {
        return $this->set('sku', $sku);
    }

    public function setBrand(string $brand): static
    {
        return $this->set('brand', [
            '@type' => 'Brand',
            'name' => $brand,
        ]);
    }

    /** @param string|array<int, string> $image */
    public function setImage(string|array $image): static
    {
        return $this->set('image', $image);
    }

    public function setCategory(string $category): static
    {
        return $this->set('category', $category);
    }

    public function setUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function setCurrency(string $currency): static
    {
        return $this->setOfferField('priceCurrency', $currency);
    }

    public function setPrice(int|float|string $price): static
    {
        return $this->setOfferField('price', $price);
    }

    public function setAvailability(string $schemaAvailability): static
    {
        return $this->setOfferField('availability', $schemaAvailability);
    }

    public function setCondition(string $schemaCondition): static
    {
        return $this->setOfferField('itemCondition', $schemaCondition);
    }

    public function setOfferUrl(string $url): static
    {
        return $this->setOfferField('url', $url);
    }

    public function setAggregateRating(float $ratingValue, int $reviewCount): static
    {
        return $this->set('aggregateRating', [
            '@type' => 'AggregateRating',
            'ratingValue' => $ratingValue,
            'reviewCount' => $reviewCount,
        ]);
    }

    public function addReview(string $author, int|float $rating, string $reviewBody): static
    {
        $reviews = $this->get('review');
        if (!is_array($reviews)) {
            $reviews = [];
        }

        $reviews[] = [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name' => $author,
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $rating,
            ],
            'reviewBody' => $reviewBody,
        ];

        return $this->set('review', $reviews);
    }

    private function setOfferField(string $key, mixed $value): static
    {
        $offer = $this->get('offers');
        if (!is_array($offer)) {
            $offer = ['@type' => 'Offer'];
        }

        $offer[$key] = $value;

        return $this->set('offers', $offer);
    }
}
