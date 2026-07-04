<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Page;

use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\JsonLd\Builder\OfferJsonLdBuilder;

final class EcommerceSeoPresetFactory
{
    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $options
     */
    public static function productDetail(string $title, ?string $description, array $product, array $options = []): SeoPagePresetOutputDTO
    {
        DomainSeoPresetFactoryHelper::requireString($product, 'name', 'product.name');

        return SeoPagePresetFactory::product($title, $description, $product, $options);
    }

    /**
     * @param list<array<string, mixed>|string> $items
     * @param array<string, mixed> $options
     */
    public static function categoryListing(string $title, ?string $description = null, array $items = [], array $options = []): SeoPagePresetOutputDTO
    {
        return SeoPagePresetFactory::category($title, $description, $items, $options);
    }

    /**
     * @param list<array<string, mixed>|string> $items
     * @param array<string, mixed> $options
     */
    public static function searchResults(string $title, ?string $description = null, array $items = [], array $options = []): SeoPagePresetOutputDTO
    {
        // Search result pages should not be indexed by default. A caller may
        // intentionally override this by passing an explicit robots option.
        if (!array_key_exists('robots', $options)) {
            $options['robots'] = ['noindex', 'follow'];
        }

        return SeoPagePresetFactory::category($title, $description, $items, $options);
    }

    /**
     * @param list<array<string, mixed>|string> $items
     * @param array<string, mixed> $options
     */
    public static function brandPage(string $title, ?string $description = null, array $items = [], array $options = []): SeoPagePresetOutputDTO
    {
        return SeoPagePresetFactory::category($title, $description, $items, $options);
    }

    /**
     * @param array<string, mixed> $offer
     * @param array<string, mixed> $options
     */
    public static function offerLanding(string $title, ?string $description, array $offer = [], array $options = []): SeoPagePresetOutputDTO
    {
        $canonical = DomainSeoPresetFactoryHelper::canonicalFromOptions($options);
        $builder = new OfferJsonLdBuilder();

        if ($canonical !== null) {
            $builder->setUrl($canonical);
        }

        if (array_key_exists('price', $offer)) {
            $builder->setPrice(DomainSeoPresetFactoryHelper::expectPrice($offer['price'], 'offer.price'));
        }

        if (array_key_exists('currency', $offer)) {
            $builder->setPriceCurrency(DomainSeoPresetFactoryHelper::expectString($offer['currency'], 'offer.currency'));
        }

        if (array_key_exists('availability', $offer)) {
            $builder->setAvailability(DomainSeoPresetFactoryHelper::expectString($offer['availability'], 'offer.availability'));
        }

        if (array_key_exists('seller', $offer)) {
            $seller = is_array($offer['seller'])
                ? DomainSeoPresetFactoryHelper::associativeArray($offer['seller'], 'offer.seller')
                : DomainSeoPresetFactoryHelper::expectString($offer['seller'], 'offer.seller');

            $builder->setSeller($seller);
        }

        $options = DomainSeoPresetFactoryHelper::appendExtraSchema($options, new JsonLdSchemaDTO($builder->toArray()));

        return SeoPagePresetFactory::generic($title, $description, $options);
    }
}
