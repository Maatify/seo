<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class LocalBusinessJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setUrl(string $url): static { return $this->set('url', $url); }
    public function setLogo(string $logo): static { return $this->set('logo', $logo); }
    /** @param string|array<int, string>|array<string, mixed> $image */
    public function setImage(string|array $image): static { return $this->set('image', $image); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    public function setTelephone(string $telephone): static { return $this->set('telephone', $telephone); }
    public function setEmail(string $email): static { return $this->set('email', $email); }

    /** @param array<string, mixed> $address */
    public function setAddress(array $address): static
    {
        return $this->set('address', $this->withDefaultType($address, 'PostalAddress'));
    }

    public function setPostalAddress(
        ?string $streetAddress = null,
        ?string $addressLocality = null,
        ?string $addressRegion = null,
        ?string $postalCode = null,
        ?string $addressCountry = null,
    ): static {
        $address = ['@type' => 'PostalAddress'];

        if ($streetAddress !== null) { $address['streetAddress'] = $streetAddress; }
        if ($addressLocality !== null) { $address['addressLocality'] = $addressLocality; }
        if ($addressRegion !== null) { $address['addressRegion'] = $addressRegion; }
        if ($postalCode !== null) { $address['postalCode'] = $postalCode; }
        if ($addressCountry !== null) { $address['addressCountry'] = $addressCountry; }

        return $this->setAddress($address);
    }

    public function setGeo(float|string $latitude, float|string $longitude): static
    {
        return $this->set('geo', [
            '@type' => 'GeoCoordinates',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    /** @param array<int, string> $openingHours */
    public function setOpeningHours(array $openingHours): static
    {
        return $this->set('openingHours', $openingHours);
    }

    public function addOpeningHours(string $openingHours): static
    {
        $hours = $this->get('openingHours');
        if (!is_array($hours)) {
            $hours = [];
        }

        /** @var array<int, string> $normalizedHours */
        $normalizedHours = array_values(array_filter($hours, 'is_string'));
        $normalizedHours[] = $openingHours;

        return $this->setOpeningHours($normalizedHours);
    }

    public function setPriceRange(string $priceRange): static { return $this->set('priceRange', $priceRange); }

    /** @param array<int, string> $sameAs */
    public function setSameAs(array $sameAs): static { return $this->set('sameAs', $sameAs); }

    public function addSameAs(string $url): static
    {
        $sameAs = $this->get('sameAs');
        if (!is_array($sameAs)) {
            $sameAs = [];
        }

        /** @var array<int, string> $normalizedSameAs */
        $normalizedSameAs = array_values(array_filter($sameAs, 'is_string'));
        $normalizedSameAs[] = $url;

        return $this->setSameAs($normalizedSameAs);
    }

    /** @param array<string, mixed> $aggregateRating */
    public function setAggregateRating(array $aggregateRating): static
    {
        return $this->set('aggregateRating', $this->withDefaultType($aggregateRating, 'AggregateRating'));
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function withDefaultType(array $schema, string $type): array
    {
        if (!isset($schema['@type'])) {
            $schema['@type'] = $type;
        }

        return $schema;
    }
}
