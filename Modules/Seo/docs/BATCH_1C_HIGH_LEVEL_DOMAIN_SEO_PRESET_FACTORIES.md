# Batch 1C: High-Level Domain SEO Preset Factories

The Maatify SEO library provides three high-level preset factories in `Modules/Seo/src/Web/Page/` tailored for specific business domains. These factories abstract away the lower-level builder and schema construction (as implemented in `SeoPagePresetFactory` and the JSON-LD builders) to provide simple, purpose-driven methods for generating complete SEO metadata sets for common page types.

These factories are framework-agnostic, do not process HTTP requests or route data, and generate standalone outputs (using `SeoPagePresetOutputDTO`) that the host application can use directly in its presentation layer.

## EcommerceSeoPresetFactory

Provides predefined configurations for common ecommerce page types.

```php
use Maatify\Seo\Web\Page\EcommerceSeoPresetFactory;

// Product detail page. Requires a product name.
$productOutput = EcommerceSeoPresetFactory::productDetail(
    'Blue Cotton Shirt',
    'A comfortable blue cotton shirt.',
    [
        'name' => 'Blue Cotton Shirt',
        'price' => '29.99',
        'currency' => 'USD'
    ],
    ['canonicalUrl' => 'https://example.com/p/blue-shirt']
);

// Category listing page. Takes a list of items (URLs or arrays) for standard Breadcrumb/ItemList schema.
$categoryOutput = EcommerceSeoPresetFactory::categoryListing(
    'Men\'s Shirts',
    'Browse our collection of men\'s shirts.',
    ['https://example.com/p/blue-shirt', 'https://example.com/p/red-shirt']
);

// Search results page. Default behavior includes noindex, follow robots tags.
$searchOutput = EcommerceSeoPresetFactory::searchResults(
    'Search Results for "Shirts"',
    'Found 2 results for "Shirts".',
    ['https://example.com/p/blue-shirt']
);

// Offer landing page. Uses generic schema but appends a specific Offer schema.
$offerOutput = EcommerceSeoPresetFactory::offerLanding(
    'Summer Sale',
    'Get great discounts on summer apparel.',
    [
        'price' => '19.99',
        'currency' => 'USD',
        'availability' => 'https://schema.org/InStock',
        'seller' => 'Example Apparel'
    ],
    [
        'canonicalBaseUrl' => 'https://example.com',
        'canonicalPath' => '/sale',
        'queryParams' => ['page' => 1]
    ]
);
```

## ContentSeoPresetFactory

Tailored for publishing and content-heavy sites (blogs, news, articles).

```php
use Maatify\Seo\Web\Page\ContentSeoPresetFactory;

// Standard Article. Requires author and datePublished.
$articleOutput = ContentSeoPresetFactory::article(
    'How to Write Better Code',
    'Learn techniques for improving your coding skills.',
    [
        'author' => 'Jane Developer',
        'datePublished' => '2023-10-27T10:00:00Z'
    ]
);

// Blog Post (BlogPosting schema).
$blogPostOutput = ContentSeoPresetFactory::blogPost(
    'My Journey into Web Development',
    'A personal reflection on learning to code.',
    [
        'author' => 'John Coder',
        'datePublished' => '2023-11-01T14:30:00Z'
    ]
);

// News Article (NewsArticle schema).
$newsOutput = ContentSeoPresetFactory::newsArticle(
    'Major Framework Update Released',
    'The latest version brings significant performance improvements.',
    [
        'author' => 'Tech News Team',
        'datePublished' => '2023-11-05T09:15:00Z'
    ]
);

// Author profile page.
$authorOutput = ContentSeoPresetFactory::authorPage(
    'Jane Developer',
    'Software engineer and technical writer.',
    ['name' => 'Jane Developer']
);
```

## LocalBusinessSeoPresetFactory

Focuses on businesses with physical locations and service offerings. Uses the `LocalBusiness` JSON-LD schema heavily.

```php
use Maatify\Seo\Web\Page\LocalBusinessSeoPresetFactory;

$businessData = [
    'name' => 'Example Plumbing',
    'telephone' => '+1-555-555-0100',
    'email' => 'contact@exampleplumbing.com',
    'address' => [
        'streetAddress' => '123 Main St',
        'addressLocality' => 'Anytown',
        'addressRegion' => 'CA',
        'postalCode' => '90210'
    ]
];

// Business Home Page
$homeOutput = LocalBusinessSeoPresetFactory::businessHome(
    'Example Plumbing - Home',
    'Your trusted local plumber.',
    $businessData,
    ['canonicalUrl' => 'https://exampleplumbing.com']
);

// Specific Service Page. Appends `Service` schema alongside the business schema.
$serviceOutput = LocalBusinessSeoPresetFactory::servicePage(
    'Emergency Drain Cleaning',
    'Fast and reliable emergency drain cleaning services.',
    [
        'name' => 'Emergency Drain Cleaning',
        'serviceType' => 'Plumbing'
    ],
    $businessData
);

// Contact Page. Appends `ContactPage` schema.
$contactOutput = LocalBusinessSeoPresetFactory::contactPage(
    'Contact Us',
    'Get in touch with Example Plumbing.',
    $businessData,
    ['contactType' => 'customer service']
);
```

## Supported Options

All preset factory methods accept an `$options` array as the final argument. These options are passed directly through to the underlying `SeoPagePresetFactory` and URL builders.

Supported options include:
*   `canonicalUrl`: (string) Explicit canonical URL to use.
*   `canonicalBaseUrl`: (string) Base URL for constructing the canonical URL.
*   `canonicalPath`: (string) Path component for constructing the canonical URL.
*   `queryParams`: (array) Query parameters to include in the constructed canonical URL.
*   `allowedQueryParams`: (array) List of parameter names permitted to pass through from the current request (handled by the caller mapping request data to the options array).
*   `robots`: (array) List of robots directives (e.g., `['noindex', 'nofollow']`). Defaults to `['index', 'follow']` for most pages, except `searchResults` which defaults to `['noindex', 'follow']`.
*   `imageUrl`: (string) URL for the social sharing image (Open Graph/Twitter).
*   `siteName`: (string) Site name for Open Graph.
*   `locale`: (string) Locale for Open Graph (e.g., `en_US`).
*   `twitterSite`: (string) Twitter site handle (e.g., `@site`).
*   `twitterCreator`: (string) Twitter creator handle (e.g., `@author`).
*   `extraSchemas`: (array of `JsonLdSchemaDTO`) Additional JSON-LD schemas to include on the page.
*   `breadcrumbs`: (array of associative arrays or strings) Custom breadcrumbs to override default generated ones.
