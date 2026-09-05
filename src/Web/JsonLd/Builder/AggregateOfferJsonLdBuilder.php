<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class AggregateOfferJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'AggregateOffer',
        ]);
    }

    public function setLowPrice(int|float|string $price): static
    {
        return $this->set('lowPrice', $price);
    }

    public function setHighPrice(int|float|string $price): static
    {
        return $this->set('highPrice', $price);
    }

    public function setPriceCurrency(string $currency): static
    {
        return $this->set('priceCurrency', $currency);
    }

    public function setOfferCount(int $count): static
    {
        return $this->set('offerCount', $count);
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

        return $this->set('offers', count($nodes) === 1 ? $nodes[0] : $nodes);
    }

    /** @param array<int|string, mixed>|JsonLdBuilderInterface $offer */
    public function addOffer(array|JsonLdBuilderInterface $offer): static
    {
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

    public function setAvailability(string $availability): static
    {
        return $this->set('availability', $availability);
    }
}
