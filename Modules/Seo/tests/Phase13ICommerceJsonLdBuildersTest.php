<?php

declare(strict_types=1);

namespace Maatify\Seo\Tests;

use Maatify\Seo\Web\JsonLd\Builder\ReviewJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\AggregateRatingJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\OfferJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ServiceJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\LocalBusinessJsonLdBuilder;
use RuntimeException;

require_once __DIR__ . '/../src/Web/JsonLd/Builder/JsonLdBuilderInterface.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/JsonLdBuilderTrait.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/AbstractJsonLdBuilder.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/ReviewJsonLdBuilder.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/AggregateRatingJsonLdBuilder.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/OfferJsonLdBuilder.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/ServiceJsonLdBuilder.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/LocalBusinessJsonLdBuilder.php';

function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void {
    if ($expected !== $actual) {
        throw new RuntimeException("Assertion failed: $message. Expected " . json_encode($expected) . ", got " . json_encode($actual));
    }
}

// 1. ReviewJsonLdBuilder
$review = (new ReviewJsonLdBuilder())
    ->setItemReviewed('Product Name')
    ->setReviewRating(4, 5.0, 1.0)
    ->setAuthor('John Doe')
    ->setName('Great product!')
    ->setReviewBody('I really loved using this product.')
    ->setDatePublished('2023-10-15')
    ->setPublisher('Awesome Review Site')
    ->toArray();

assertSameValue('Review', $review['@type']);
assertSameValue('Product Name', $review['itemReviewed']);
assertSameValue(['@type' => 'Rating', 'ratingValue' => 4, 'bestRating' => 5.0, 'worstRating' => 1.0], $review['reviewRating']);
assertSameValue(['@type' => 'Person', 'name' => 'John Doe'], $review['author']);
assertSameValue('Great product!', $review['name']);
assertSameValue('I really loved using this product.', $review['reviewBody']);
assertSameValue('2023-10-15', $review['datePublished']);
assertSameValue(['@type' => 'Organization', 'name' => 'Awesome Review Site'], $review['publisher']);

// Array usages
$review2 = (new ReviewJsonLdBuilder())
    ->setItemReviewed(['@type' => 'Product', 'name' => 'Product 2'])
    ->setReviewRating(['@type' => 'Rating', 'ratingValue' => 3])
    ->setAuthor(['@type' => 'Person', 'name' => 'Jane Doe'])
    ->setPublisher(['@type' => 'Organization', 'name' => 'Reviewer Inc'])
    ->toArray();

assertSameValue(['@type' => 'Product', 'name' => 'Product 2'], $review2['itemReviewed']);
assertSameValue(['@type' => 'Rating', 'ratingValue' => 3], $review2['reviewRating']);
assertSameValue(['@type' => 'Person', 'name' => 'Jane Doe'], $review2['author']);
assertSameValue(['@type' => 'Organization', 'name' => 'Reviewer Inc'], $review2['publisher']);

// 2. AggregateRatingJsonLdBuilder
$aggRating = (new AggregateRatingJsonLdBuilder())
    ->setRatingValue(4.5)
    ->setReviewCount(120)
    ->setRatingCount(150)
    ->setBestRating(5)
    ->setWorstRating(1)
    ->toArray();

assertSameValue('AggregateRating', $aggRating['@type']);
assertSameValue(4.5, $aggRating['ratingValue']);
assertSameValue(120, $aggRating['reviewCount']);
assertSameValue(150, $aggRating['ratingCount']);
assertSameValue(5, $aggRating['bestRating']);
assertSameValue(1, $aggRating['worstRating']);

// 3. OfferJsonLdBuilder
$offer = (new OfferJsonLdBuilder())
    ->setPrice(29.99)
    ->setPriceCurrency('USD')
    ->setAvailability('https://schema.org/InStock')
    ->setUrl('https://example.com/offer')
    ->setValidFrom('2023-11-01')
    ->setPriceValidUntil('2023-12-31')
    ->setItemCondition('https://schema.org/NewCondition')
    ->setSeller('Store Name')
    ->toArray();

assertSameValue('Offer', $offer['@type']);
assertSameValue(29.99, $offer['price']);
assertSameValue('USD', $offer['priceCurrency']);
assertSameValue('https://schema.org/InStock', $offer['availability']);
assertSameValue('https://example.com/offer', $offer['url']);
assertSameValue('2023-11-01', $offer['validFrom']);
assertSameValue('2023-12-31', $offer['priceValidUntil']);
assertSameValue('https://schema.org/NewCondition', $offer['itemCondition']);
assertSameValue(['@type' => 'Organization', 'name' => 'Store Name'], $offer['seller']);

$offer2 = (new OfferJsonLdBuilder())
    ->setSeller(['@type' => 'Organization', 'name' => 'Store Name 2'])
    ->toArray();
assertSameValue(['@type' => 'Organization', 'name' => 'Store Name 2'], $offer2['seller']);

// 4. ServiceJsonLdBuilder
$service = (new ServiceJsonLdBuilder())
    ->setName('Plumbing Service')
    ->setDescription('Professional plumbing services.')
    ->setServiceType('Home Repair')
    ->setProvider('Plumbers Inc')
    ->setAreaServed('New York')
    ->setOffers([
        ['@type' => 'Offer', 'price' => 50, 'priceCurrency' => 'USD']
    ])
    ->setAggregateRating([
        'ratingValue' => 4.8, 'reviewCount' => 20
    ])
    ->toArray();

assertSameValue('Service', $service['@type']);
assertSameValue('Plumbing Service', $service['name']);
assertSameValue('Professional plumbing services.', $service['description']);
assertSameValue('Home Repair', $service['serviceType']);
assertSameValue(['@type' => 'Organization', 'name' => 'Plumbers Inc'], $service['provider']);
assertSameValue(['@type' => 'Place', 'name' => 'New York'], $service['areaServed']);
assertSameValue([['@type' => 'Offer', 'price' => 50, 'priceCurrency' => 'USD']], $service['offers']);
assertSameValue(['ratingValue' => 4.8, 'reviewCount' => 20, '@type' => 'AggregateRating'], $service['aggregateRating']);

// 5. LocalBusinessJsonLdBuilder
$localBusiness = (new LocalBusinessJsonLdBuilder())
    ->setName('My Local Shop')
    ->setUrl('https://example.com/shop')
    ->setLogo('https://example.com/logo.png')
    ->setImage('https://example.com/image.jpg')
    ->setDescription('A great local shop.')
    ->setTelephone('555-1234')
    ->setEmail('contact@example.com')
    ->setAddress([
        'streetAddress' => '123 Main St'
    ])
    ->setGeo(40.7128, -74.0060)
    ->setOpeningHours([
        'Mo-Fr 09:00-17:00'
    ])
    ->addOpeningHours('Sa 10:00-14:00')
    ->setPriceRange('$$')
    ->setSameAs([
        'https://facebook.com/myshop'
    ])
    ->addSameAs('https://twitter.com/myshop')
    ->setAggregateRating([
        'ratingValue' => 4.9, 'reviewCount' => 50
    ])
    ->toArray();

assertSameValue('LocalBusiness', $localBusiness['@type']);
assertSameValue('My Local Shop', $localBusiness['name']);
assertSameValue('https://example.com/shop', $localBusiness['url']);
assertSameValue('https://example.com/logo.png', $localBusiness['logo']);
assertSameValue('https://example.com/image.jpg', $localBusiness['image']);
assertSameValue('A great local shop.', $localBusiness['description']);
assertSameValue('555-1234', $localBusiness['telephone']);
assertSameValue('contact@example.com', $localBusiness['email']);
assertSameValue(['streetAddress' => '123 Main St', '@type' => 'PostalAddress'], $localBusiness['address']);
assertSameValue(['@type' => 'GeoCoordinates', 'latitude' => 40.7128, 'longitude' => -74.0060], $localBusiness['geo']);
assertSameValue(['Mo-Fr 09:00-17:00', 'Sa 10:00-14:00'], $localBusiness['openingHours']);
assertSameValue('$$', $localBusiness['priceRange']);
assertSameValue(['https://facebook.com/myshop', 'https://twitter.com/myshop'], $localBusiness['sameAs']);
assertSameValue(['ratingValue' => 4.9, 'reviewCount' => 50, '@type' => 'AggregateRating'], $localBusiness['aggregateRating']);

$localBusiness2 = (new LocalBusinessJsonLdBuilder())
    ->setPostalAddress('456 Elm St', 'Cityville', 'ST', '12345', 'US')
    ->toArray();
assertSameValue([
    '@type' => 'PostalAddress',
    'streetAddress' => '456 Elm St',
    'addressLocality' => 'Cityville',
    'addressRegion' => 'ST',
    'postalCode' => '12345',
    'addressCountry' => 'US'
], $localBusiness2['address']);

echo "Phase 13I Commerce JSON-LD Builders tests passed successfully.\n";
