<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class OfferJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Offer',
        ]);
    }

    public function setPrice(int|float|string $price): static
    {
        return $this->set('price', $price);
    }

    public function setPriceCurrency(string $priceCurrency): static
    {
        return $this->set('priceCurrency', $priceCurrency);
    }

    public function setAvailability(string $availability): static
    {
        return $this->set('availability', $availability);
    }

    public function setUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function setValidFrom(string $validFrom): static
    {
        return $this->set('validFrom', $validFrom);
    }

    public function setPriceValidUntil(string $priceValidUntil): static
    {
        return $this->set('priceValidUntil', $priceValidUntil);
    }

    public function setItemCondition(string $itemCondition): static
    {
        return $this->set('itemCondition', $itemCondition);
    }

    /** @param string|array<string, mixed> $seller */
    public function setSeller(string|array $seller): static
    {
        if (is_string($seller)) {
            $seller = ['@type' => 'Organization', 'name' => $seller];
        } elseif (!isset($seller['@type'])) {
            $seller['@type'] = 'Organization';
        }

        return $this->set('seller', $seller);
    }
}
