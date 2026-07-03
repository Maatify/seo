# Phase 13I: Commerce JSON-LD Builders

This document covers the batch of commerce-focused JSON-LD builders implemented in Phase 13I. These builders allow generating standardized schema.org entities for commerce and business related rich snippets.

The Phase 13I batch includes:
- `ReviewJsonLdBuilder`
- `AggregateRatingJsonLdBuilder`
- `OfferJsonLdBuilder`
- `ServiceJsonLdBuilder`
- `LocalBusinessJsonLdBuilder`

All builders are strictly framework-neutral, independent of any HTTP or template engine layer, and are designed to return arrays or JSON strings for rendering.

## 1. ReviewJsonLdBuilder

Builds `Review` schema.org markup with author, rating, and item reviewed.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\ReviewJsonLdBuilder;

$reviewBuilder = new ReviewJsonLdBuilder();

$schema = $reviewBuilder
    ->setItemReviewed('Awesome Product')
    ->setReviewRating(5, 5, 1) // rating, bestRating, worstRating
    ->setAuthor('John Doe') // auto-normalized to Person
    ->setName('Great product!')
    ->setReviewBody('I really loved using this product.')
    ->setDatePublished('2023-10-15')
    ->setPublisher('Awesome Review Site') // auto-normalized to Organization
    ->toArray();

echo json_encode($schema, JSON_UNESCAPED_SLASHES);
```

### Methods
- `setItemReviewed(string|array $itemReviewed): static`
- `setReviewRating(int|float|string|array $rating, ?float $bestRating = null, ?float $worstRating = null): static`
- `setAuthor(string|array $author): static`
- `setName(string $name): static`
- `setReviewBody(string $reviewBody): static`
- `setDatePublished(string $datePublished): static`
- `setPublisher(string|array $publisher): static`


## 2. AggregateRatingJsonLdBuilder

Builds `AggregateRating` schema.org markup for average ratings.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\AggregateRatingJsonLdBuilder;

$aggRatingBuilder = new AggregateRatingJsonLdBuilder();

$schema = $aggRatingBuilder
    ->setRatingValue(4.5)
    ->setReviewCount(120)
    ->setRatingCount(150)
    ->setBestRating(5)
    ->setWorstRating(1)
    ->toArray();
```

### Methods
- `setRatingValue(int|float|string $ratingValue): static`
- `setReviewCount(int $reviewCount): static`
- `setRatingCount(int $ratingCount): static`
- `setBestRating(int|float|string $bestRating): static`
- `setWorstRating(int|float|string $worstRating): static`


## 3. OfferJsonLdBuilder

Builds `Offer` schema.org markup for product offers and pricing.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\OfferJsonLdBuilder;

$offerBuilder = new OfferJsonLdBuilder();

$schema = $offerBuilder
    ->setPrice('29.99')
    ->setPriceCurrency('USD')
    ->setAvailability('https://schema.org/InStock')
    ->setUrl('https://example.com/offer')
    ->setValidFrom('2023-11-01')
    ->setPriceValidUntil('2023-12-31')
    ->setItemCondition('https://schema.org/NewCondition')
    ->setSeller('Store Name') // auto-normalized to Organization
    ->toArray();
```

### Methods
- `setPrice(int|float|string $price): static`
- `setPriceCurrency(string $priceCurrency): static`
- `setAvailability(string $availability): static`
- `setUrl(string $url): static`
- `setValidFrom(string $validFrom): static`
- `setPriceValidUntil(string $priceValidUntil): static`
- `setItemCondition(string $itemCondition): static`
- `setSeller(string|array $seller): static`


## 4. ServiceJsonLdBuilder

Builds `Service` schema.org markup for service providers.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\ServiceJsonLdBuilder;

$serviceBuilder = new ServiceJsonLdBuilder();

$schema = $serviceBuilder
    ->setName('Plumbing Service')
    ->setDescription('Professional plumbing services.')
    ->setServiceType('Home Repair')
    ->setProvider('Plumbers Inc') // auto-normalized to Organization
    ->setAreaServed('New York') // auto-normalized to Place
    ->setOffers([
        ['@type' => 'Offer', 'price' => '50.00', 'priceCurrency' => 'USD']
    ])
    ->setAggregateRating([
        'ratingValue' => 4.8, 'reviewCount' => 20
    ]) // auto-normalized to AggregateRating
    ->toArray();
```

### Methods
- `setName(string $name): static`
- `setDescription(string $description): static`
- `setServiceType(string $serviceType): static`
- `setProvider(string|array $provider): static`
- `setAreaServed(string|array $areaServed): static`
- `setOffers(array $offers): static`
- `setAggregateRating(array $aggregateRating): static`


## 5. LocalBusinessJsonLdBuilder

Builds `LocalBusiness` schema.org markup for local businesses and places.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\LocalBusinessJsonLdBuilder;

$localBusinessBuilder = new LocalBusinessJsonLdBuilder();

$schema = $localBusinessBuilder
    ->setName('My Local Shop')
    ->setUrl('https://example.com/shop')
    ->setLogo('https://example.com/logo.png')
    ->setImage('https://example.com/image.jpg')
    ->setDescription('A great local shop.')
    ->setTelephone('555-1234')
    ->setEmail('contact@example.com')
    ->setPostalAddress('123 Main St', 'Cityville', 'ST', '12345', 'US')
    ->setGeo(40.7128, -74.0060)
    ->setOpeningHours(['Mo-Fr 09:00-17:00'])
    ->addOpeningHours('Sa 10:00-14:00')
    ->setPriceRange('$$')
    ->addSameAs('https://facebook.com/myshop')
    ->setAggregateRating(['ratingValue' => 4.9, 'reviewCount' => 50])
    ->toArray();
```

### Methods
- `setName(string $name): static`
- `setUrl(string $url): static`
- `setLogo(string $logo): static`
- `setImage(string|array $image): static`
- `setDescription(string $description): static`
- `setTelephone(string $telephone): static`
- `setEmail(string $email): static`
- `setAddress(array $address): static`
- `setPostalAddress(?string $streetAddress = null, ?string $addressLocality = null, ?string $addressRegion = null, ?string $postalCode = null, ?string $addressCountry = null): static`
- `setGeo(float|string $latitude, float|string $longitude): static`
- `setOpeningHours(array $openingHours): static`
- `addOpeningHours(string $openingHours): static`
- `setPriceRange(string $priceRange): static`
- `setSameAs(array $sameAs): static`
- `addSameAs(string $url): static`
- `setAggregateRating(array $aggregateRating): static`
