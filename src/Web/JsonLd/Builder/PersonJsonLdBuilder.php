<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class PersonJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
        ]);
    }

    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    public function setUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @param string|array<int, string> $image */
    public function setImage(string|array $image): static
    {
        return $this->set('image', $image);
    }

    public function setDescription(string $description): static
    {
        return $this->set('description', $description);
    }

    public function setJobTitle(string $jobTitle): static
    {
        return $this->set('jobTitle', $jobTitle);
    }

    /** @param string|array<string, mixed> $worksFor */
    public function setWorksFor(string|array $worksFor): static
    {
        if (is_string($worksFor)) {
            $worksFor = [
                '@type' => 'Organization',
                'name' => $worksFor,
            ];
        } elseif (!isset($worksFor['@type'])) {
            $worksFor['@type'] = 'Organization';
        }

        return $this->set('worksFor', $worksFor);
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

        /** @var array<int, string> $normalizedSameAs */
        $normalizedSameAs = array_values(array_filter($sameAs, 'is_string'));
        $normalizedSameAs[] = $url;

        return $this->setSameAs($normalizedSameAs);
    }

    public function setEmail(string $email): static
    {
        return $this->set('email', $email);
    }

    public function setTelephone(string $telephone): static
    {
        return $this->set('telephone', $telephone);
    }

    /** @param array<string, mixed> $address */
    public function setAddress(array $address): static
    {
        if (!isset($address['@type'])) {
            $address['@type'] = 'PostalAddress';
        }

        return $this->set('address', $address);
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
}
