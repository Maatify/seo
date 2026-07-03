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
- `sameAs` (string or array of strings)
- `contactPoint` (array or array of arrays)
- `address` (array or `PostalAddress`)

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
