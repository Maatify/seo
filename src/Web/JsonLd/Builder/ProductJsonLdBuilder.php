<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ProductJsonLdBuilder extends AbstractJsonLdBuilder
{
    private bool $hasExplicitOffers = false;

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

    public function setGtin(string $gtin): static
    {
        return $this->set('gtin', $gtin);
    }

    public function setMpn(string $mpn): static
    {
        return $this->set('mpn', $mpn);
    }

    public function setColor(string $color): static
    {
        return $this->set('color', $color);
    }

    public function setSize(string $size): static
    {
        return $this->set('size', $size);
    }

    public function setMaterial(string $material): static
    {
        return $this->set('material', $material);
    }

    public function setPattern(string $pattern): static
    {
        return $this->set('pattern', $pattern);
    }

    /** @param string|array<int|string, mixed>|JsonLdBuilderInterface $productGroup */
    public function setIsVariantOf(string|array|JsonLdBuilderInterface $productGroup): static
    {
        if (is_string($productGroup)) {
            $productGroup = [
                '@type' => 'ProductGroup',
                'productGroupID' => $productGroup,
            ];
        }

        return $this->set('isVariantOf', $productGroup);
    }

    public function setInProductGroupWithID(string $productGroupID): static
    {
        return $this->set('inProductGroupWithID', $productGroupID);
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

    /** @param array<int|string, mixed>|JsonLdBuilderInterface ...$offers */
    public function setOffers(array|JsonLdBuilderInterface ...$offers): static
    {
        if ($offers === []) {
            return $this;
        }

        if (count($offers) === 1 && is_array($offers[0]) && $offers[0] === []) {
            return $this;
        }

        $nodes = $offers;
        if (count($offers) === 1 && is_array($offers[0]) && array_is_list($offers[0])) {
            $nodes = $offers[0];
        }

        $this->set('offers', count($nodes) === 1 ? $nodes[0] : $nodes);
        $this->hasExplicitOffers = true;

        return $this;
    }

    /** @param array<int|string, mixed>|JsonLdBuilderInterface $offer */
    public function addOffer(array|JsonLdBuilderInterface $offer): static
    {
        if (!$this->hasExplicitOffers) {
            $this->set('offers', $offer);
            $this->hasExplicitOffers = true;

            return $this;
        }

        $offers = $this->get('offers');
        if ($offers === null) {
            $offers = $offer;
        } elseif (is_array($offers) && array_is_list($offers)) {
            $offers[] = $offer;
        } else {
            $offers = [$offers, $offer];
        }

        return $this->set('offers', $offers);
    }

    public function remove(string $key): static
    {
        parent::remove($key);

        if ($key === 'offers') {
            $this->hasExplicitOffers = false;
        }

        return $this;
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
        if ($this->hasExplicitOffers) {
            throw new JsonLdBuildException('Cannot set legacy offer fields after explicit offers have been configured.');
        }

        $offer = $this->get('offers');
        if (!is_array($offer)) {
            $offer = ['@type' => 'Offer'];
        }

        $offer[$key] = $value;

        return $this->set('offers', $offer);
    }
}
