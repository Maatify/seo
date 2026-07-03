<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class OrganizationJsonLdBuilder extends AbstractJsonLdBuilder
{
    private const TYPE_ORGANIZATION = 'Organization';
    private const TYPE_LOCAL_BUSINESS = 'LocalBusiness';
    private const TYPE_CORPORATION = 'Corporation';
    private const TYPE_STORE = 'Store';

    /** @var array<int, string> */
    private const SUPPORTED_TYPES = [
        self::TYPE_ORGANIZATION,
        self::TYPE_LOCAL_BUSINESS,
        self::TYPE_CORPORATION,
        self::TYPE_STORE,
    ];

    public function __construct(string $type = self::TYPE_ORGANIZATION)
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => self::normalizeType($type),
        ]);
    }

    public static function organization(): self
    {
        return new self(self::TYPE_ORGANIZATION);
    }

    public static function localBusiness(): self
    {
        return new self(self::TYPE_LOCAL_BUSINESS);
    }

    public static function corporation(): self
    {
        return new self(self::TYPE_CORPORATION);
    }

    public static function store(): self
    {
        return new self(self::TYPE_STORE);
    }

    public function setType(string $type): static
    {
        return $this->set('@type', self::normalizeType($type));
    }

    public function asOrganization(): static
    {
        return $this->setType(self::TYPE_ORGANIZATION);
    }

    public function asLocalBusiness(): static
    {
        return $this->setType(self::TYPE_LOCAL_BUSINESS);
    }

    public function asCorporation(): static
    {
        return $this->setType(self::TYPE_CORPORATION);
    }

    public function asStore(): static
    {
        return $this->setType(self::TYPE_STORE);
    }

    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    public function setUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function setLogo(string $logo): static
    {
        return $this->set('logo', $logo);
    }

    public function setDescription(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @param array<int, string> $sameAs */
    public function setSameAs(array $sameAs): static
    {
        return $this->set('sameAs', $sameAs);
    }

    public function addSameAs(string $url): static
    {
        $sameAs = $this->get('sameAs');
        if (!is_array($sameAs)) {
            $sameAs = [];
        }

        $sameAs[] = $url;

        return $this->setSameAs($sameAs);
    }

    /** @param array<string, mixed> $contactPoint */
    public function setContactPoint(array $contactPoint): static
    {
        return $this->set('contactPoint', self::withDefaultType($contactPoint, 'ContactPoint'));
    }

    /** @param array<string, mixed> $contactPoint */
    public function addContactPoint(array $contactPoint): static
    {
        $contactPoints = $this->get('contactPoint');
        if (!is_array($contactPoints)) {
            $contactPoints = [];
        } elseif (self::isAssociativeArray($contactPoints)) {
            $contactPoints = [$contactPoints];
        }

        $contactPoints[] = self::withDefaultType($contactPoint, 'ContactPoint');

        return $this->set('contactPoint', $contactPoints);
    }

    /** @param array<string, mixed> $address */
    public function setAddress(array $address): static
    {
        return $this->set('address', self::withDefaultType($address, 'PostalAddress'));
    }

    public function setPostalAddress(
        ?string $streetAddress = null,
        ?string $addressLocality = null,
        ?string $addressRegion = null,
        ?string $postalCode = null,
        ?string $addressCountry = null,
    ): static {
        $address = ['@type' => 'PostalAddress'];

        if ($streetAddress !== null) {
            $address['streetAddress'] = $streetAddress;
        }
        if ($addressLocality !== null) {
            $address['addressLocality'] = $addressLocality;
        }
        if ($addressRegion !== null) {
            $address['addressRegion'] = $addressRegion;
        }
        if ($postalCode !== null) {
            $address['postalCode'] = $postalCode;
        }
        if ($addressCountry !== null) {
            $address['addressCountry'] = $addressCountry;
        }

        return $this->setAddress($address);
    }

    /** @param array<string, mixed> $schema */
    private static function withDefaultType(array $schema, string $type): array
    {
        if (!isset($schema['@type'])) {
            $schema['@type'] = $type;
        }

        return $schema;
    }

    /** @param array<mixed> $value */
    private static function isAssociativeArray(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }

    private static function normalizeType(string $type): string
    {
        return in_array($type, self::SUPPORTED_TYPES, true) ? $type : self::TYPE_ORGANIZATION;
    }
}
