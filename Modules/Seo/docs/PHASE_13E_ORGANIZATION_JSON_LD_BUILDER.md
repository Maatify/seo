# Phase 13E: Organization JSON-LD Builder

## Overview
The `OrganizationJsonLdBuilder` is part of the `Maatify\Seo\Web\JsonLd\Builder` namespace and provides a framework-neutral, fluent interface to build `Organization`, `LocalBusiness`, `Corporation`, and `Store` Schema.org JSON-LD arrays.

## Supported Schema Types
- `Organization`
- `LocalBusiness`
- `Corporation`
- `Store`

## Supported Fields
- `name`
- `url`
- `logo`
- `description`
- `sameAs`:
  - Use `setSameAs(array $sameAs)` for multiple URLs. This method does not accept a string directly.
  - Use `addSameAs(string $url)` for a single URL.
- `contactPoint`:
  - Use `setContactPoint(array $contactPoint)` to set a single contact point.
  - Use `addContactPoint(array $contactPoint)` to append to existing contact points.
- `address`:
  - Use `setAddress(array $address)` to pass an entire address array.
  - Use `setPostalAddress(...)` to build it dynamically via named parameters.

## Usage Example

```php
use Maatify\Seo\Web\JsonLd\Builder\OrganizationJsonLdBuilder;

$builder = new OrganizationJsonLdBuilder();
// Alternatively:
// OrganizationJsonLdBuilder::localBusiness()
// OrganizationJsonLdBuilder::corporation()
// OrganizationJsonLdBuilder::store()
// OrganizationJsonLdBuilder::organization()

$schemaArray = $builder
    ->setName('Maatify Demo Org')
    ->setUrl('https://example.com')
    ->setLogo('https://example.com/logo.png')
    ->setDescription('A demo organization for JSON-LD output.')
    ->addSameAs('https://twitter.com/example')
    ->addSameAs('https://github.com/example')
    ->addContactPoint([
        'telephone' => '+1-800-555-1212',
        'contactType' => 'customer service',
    ])
    ->setPostalAddress(
        streetAddress: '123 Demo St',
        addressLocality: 'Demo City',
        addressRegion: 'CA',
        postalCode: '90210',
        addressCountry: 'US'
    )
    ->toArray();

// Or get as JSON string directly
$jsonString = $builder->toJson();
```
