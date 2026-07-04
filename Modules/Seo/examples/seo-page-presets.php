<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Maatify\Seo\Web\Page\SeoPagePresetFactory;
use Maatify\Seo\Web\Page\EcommerceSeoPresetFactory;
use Maatify\Seo\Web\Page\ContentSeoPresetFactory;
use Maatify\Seo\Web\Page\LocalBusinessSeoPresetFactory;

echo "--- Generic SEO Preset ---\n";

$genericPreset = SeoPagePresetFactory::generic(
    'My Awesome Website',
    'A description of my awesome website.',
    [
        'canonicalUrl' => 'https://example.com/',
        'imageUrl' => 'https://example.com/logo.png',
        'siteName' => 'MySite',
        'locale' => 'en_US',
    ]
);

echo "Title: " . $genericPreset->metaTags->title . "\n";
echo "Description: " . $genericPreset->metaTags->description . "\n";
echo "Canonical: " . $genericPreset->canonicalUrl . "\n";
echo "Schemas Count: " . count($genericPreset->schemas) . "\n\n";

echo "--- Ecommerce Product Preset ---\n";

$productPreset = EcommerceSeoPresetFactory::productDetail(
    'Super Cool T-Shirt - MySite',
    'Buy this super cool t-shirt.',
    [
        'name' => 'Super Cool T-Shirt',
        'image' => ['https://example.com/tshirt.png'],
        'description' => 'A very nice t-shirt.',
        'sku' => 'TSHIRT-001',
        'offers' => [
            '@type' => 'Offer',
            'price' => '19.99',
            'priceCurrency' => 'USD',
            'availability' => 'https://schema.org/InStock',
        ],
    ],
    [
        'canonicalUrl' => 'https://example.com/product/tshirt',
        'imageUrl' => 'https://example.com/tshirt.png',
        'siteName' => 'MySite',
    ]
);

echo "Title: " . $productPreset->metaTags->title . "\n";
echo "Canonical: " . $productPreset->canonicalUrl . "\n";
echo "Schemas Count: " . count($productPreset->schemas) . "\n\n";


echo "--- Content Article Preset ---\n";

$articlePreset = ContentSeoPresetFactory::article(
    'How to Write Code - Blog',
    'A guide on writing better code.',
    [
        'headline' => 'How to Write Code',
        'image' => ['https://example.com/coding.jpg'],
        'datePublished' => '2023-10-26T10:00:00Z',
        'author' => 'Jane Doe',
    ],
    [
        'canonicalUrl' => 'https://example.com/blog/how-to-write-code',
    ]
);

echo "Title: " . $articlePreset->metaTags->title . "\n";
echo "Canonical: " . $articlePreset->canonicalUrl . "\n";
echo "Schemas Count: " . count($articlePreset->schemas) . "\n\n";

echo "--- Local Business Preset ---\n";

$localBusinessPreset = LocalBusinessSeoPresetFactory::businessHome(
    'Joe\'s Plumbing',
    'Best plumbing in town.',
    [
        'name' => 'Joe\'s Plumbing',
        'image' => ['https://example.com/storefront.jpg'],
        'telephone' => '555-1234',
        'address' => [
            'streetAddress' => '123 Main St',
            'addressLocality' => 'Anytown',
            'addressRegion' => 'CA',
            'postalCode' => '12345',
            'addressCountry' => 'US'
        ]
    ],
    [
        'canonicalUrl' => 'https://example.com/',
    ]
);

echo "Title: " . $localBusinessPreset->metaTags->title . "\n";
echo "Canonical: " . $localBusinessPreset->canonicalUrl . "\n";
echo "Schemas Count: " . count($localBusinessPreset->schemas) . "\n\n";

echo "Done.\n";
