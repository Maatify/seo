<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Page;

use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\JsonLd\Builder\ContactPageJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\LocalBusinessJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ServiceJsonLdBuilder;

final class LocalBusinessSeoPresetFactory
{
    /** @param array<string, mixed> $business @param array<string, mixed> $options */
    public static function businessHome(string $title, ?string $description, array $business, array $options = []): SeoPagePresetOutputDTO
    {
        return SeoPagePresetFactory::home($title, $description, self::withBusinessSchema($business, $options, $description));
    }

    /** @param array<string, mixed> $business @param array<string, mixed> $options */
    public static function locationPage(string $title, ?string $description, array $business, array $options = []): SeoPagePresetOutputDTO
    {
        return SeoPagePresetFactory::generic($title, $description, self::withBusinessSchema($business, $options, $description));
    }

    /** @param array<string, mixed> $service @param array<string, mixed> $business @param array<string, mixed> $options */
    public static function servicePage(string $title, ?string $description, array $service, array $business, array $options = []): SeoPagePresetOutputDTO
    {
        $businessName = DomainSeoPresetFactoryHelper::requireString($business, 'name', 'business.name');
        $serviceName = DomainSeoPresetFactoryHelper::requireString($service, 'name', 'service.name');
        $builder = (new ServiceJsonLdBuilder())->setName($serviceName)->setProvider($businessName);

        if ($description !== null && $description !== '') {
            $builder->setDescription($description);
        }

        if (array_key_exists('serviceType', $service)) {
            $builder->setServiceType(DomainSeoPresetFactoryHelper::expectString($service['serviceType'], 'service.serviceType'));
        }

        if (array_key_exists('areaServed', $service)) {
            $areaServed = is_array($service['areaServed'])
                ? DomainSeoPresetFactoryHelper::associativeArray($service['areaServed'], 'service.areaServed')
                : DomainSeoPresetFactoryHelper::expectString($service['areaServed'], 'service.areaServed');

            $builder->setAreaServed($areaServed);
        }

        $options = self::withBusinessSchema($business, $options, null);
        $options = DomainSeoPresetFactoryHelper::appendExtraSchema($options, new JsonLdSchemaDTO($builder->toArray()));

        return SeoPagePresetFactory::generic($title, $description, $options);
    }

    /** @param array<string, mixed> $business @param array<string, mixed> $contact @param array<string, mixed> $options */
    public static function contactPage(string $title, ?string $description, array $business, array $contact = [], array $options = []): SeoPagePresetOutputDTO
    {
        $page = (new ContactPageJsonLdBuilder())->setName($title);
        $canonical = DomainSeoPresetFactoryHelper::canonicalFromOptions($options);

        if ($canonical !== null) {
            $page->setUrl($canonical);
        }

        if ($description !== null && $description !== '') {
            $page->setDescription($description);
        }

        if (array_key_exists('contactType', $contact)) {
            $page->setContactPoint(DomainSeoPresetFactoryHelper::expectString($contact['contactType'], 'contact.contactType'));
        }

        $options = self::withBusinessSchema($business, $options, $description);
        $options = DomainSeoPresetFactoryHelper::appendExtraSchema($options, new JsonLdSchemaDTO($page->toArray()));

        return SeoPagePresetFactory::generic($title, $description, $options);
    }

    /** @param array<string, mixed> $business @param array<string, mixed> $options @return array<string, mixed> */
    private static function withBusinessSchema(array $business, array $options, ?string $description): array
    {
        $name = DomainSeoPresetFactoryHelper::requireString($business, 'name', 'business.name');
        $canonical = DomainSeoPresetFactoryHelper::canonicalFromOptions($options);
        $builder = (new LocalBusinessJsonLdBuilder())->setName($name);

        if ($canonical !== null) {
            $builder->setUrl($canonical);
        }

        if ($description !== null && $description !== '') {
            $builder->setDescription($description);
        }

        if (array_key_exists('telephone', $business)) {
            $builder->setTelephone(DomainSeoPresetFactoryHelper::expectString($business['telephone'], 'business.telephone'));
        }

        if (array_key_exists('email', $business)) {
            $builder->setEmail(DomainSeoPresetFactoryHelper::expectString($business['email'], 'business.email'));
        }

        if (array_key_exists('logo', $business)) {
            $builder->setLogo(DomainSeoPresetFactoryHelper::expectString($business['logo'], 'business.logo'));
        }

        if (array_key_exists('priceRange', $business)) {
            $builder->setPriceRange(DomainSeoPresetFactoryHelper::expectString($business['priceRange'], 'business.priceRange'));
        }

        if (array_key_exists('address', $business)) {
            $builder->setAddress(DomainSeoPresetFactoryHelper::associativeArray($business['address'], 'business.address'));
        }

        if ($canonical === null && !array_key_exists('address', $business) && !array_key_exists('telephone', $business) && !array_key_exists('email', $business)) {
            DomainSeoPresetFactoryHelper::expectString(null, 'business.url');
        }

        return DomainSeoPresetFactoryHelper::appendExtraSchema($options, new JsonLdSchemaDTO($builder->toArray()));
    }
}
