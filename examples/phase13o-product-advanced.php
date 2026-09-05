<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'Maatify\\Seo\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\OfferJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\AggregateOfferJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\OrganizationJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProductGroupJsonLdBuilder;
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;

function printSection(string $title, string $output): void
{
    echo "\n==============================\n";
    echo $title . "\n";
    echo "==============================\n";
    echo $output . "\n";
}

$renderer = new JsonLdScriptRenderer();

// --- Scenario 1: Product with typed explicit Offer ---
$seller = (new OrganizationJsonLdBuilder())->setName('My Awesome Store');
$offer = (new OfferJsonLdBuilder())
    ->setPrice('29.99')
    ->setPriceCurrency('USD')
    ->setAvailability('https://schema.org/InStock')
    ->setSeller($seller);

$product1 = (new ProductJsonLdBuilder())
    ->setName('Premium Widget')
    ->setDescription('A very nice widget.')
    ->setGtin('0123456789012')
    ->setMpn('PW-01')
    ->setOffers($offer);

printSection('1. Product with Typed Offer', $renderer->render($product1->toArray()));

// --- Scenario 2: Product with multiple Offers ---
$offer1 = (new OfferJsonLdBuilder())->setPrice('29.99')->setPriceCurrency('USD');
$offer2 = (new OfferJsonLdBuilder())->setPrice('34.99')->setPriceCurrency('CAD');

$product2 = (new ProductJsonLdBuilder())
    ->setName('International Widget')
    // using addOffer lifecycle
    ->addOffer($offer1)
    ->addOffer($offer2)
    // raw arrays are safely supported too
    ->addOffer(['@type' => 'Offer', 'price' => '24.99', 'priceCurrency' => 'EUR']);

printSection('2. Product with Multiple Offers', $renderer->render($product2->toArray()));

// --- Scenario 3: Product with AggregateOffer ---
$aggOffer = (new AggregateOfferJsonLdBuilder())
    ->setLowPrice('10.00')
    ->setHighPrice('50.00')
    ->setPriceCurrency('USD')
    ->setOfferCount(15)
    ->setOffers($offer1, $offer2); // AggregateOffer can also contain offers

$product3 = (new ProductJsonLdBuilder())
    ->setName('Widget Collection')
    ->setOffers($aggOffer);

printSection('3. Product with AggregateOffer', $renderer->render($product3->toArray()));

// --- Scenario 4: ProductGroup with multiple Product Variants ---
$redVariant = (new ProductJsonLdBuilder())
    ->setSku('TS-RED-L')
    ->setColor('Red')
    ->setSize('L');

$blueVariant = (new ProductJsonLdBuilder())
    ->setSku('TS-BLU-M')
    ->setColor('Blue')
    ->setSize('M');

$productGroup = (new ProductGroupJsonLdBuilder())
    ->setName('Classic T-Shirt Line')
    ->setProductGroupID('TSHIRT-BASE')
    ->setBrand('Maatify Apparel')
    ->setVariesBy(['https://schema.org/color', 'https://schema.org/size'])
    ->setHasVariant($redVariant, $blueVariant);

printSection('4. ProductGroup with Variants', $renderer->render($productGroup->toArray()));

// --- Scenario 5: Product Variant linked to ProductGroup ---
$childProduct = (new ProductJsonLdBuilder())
    ->setSku('TS-RED-L')
    ->setColor('Red')
    ->setSize('L')
    ->setIsVariantOf('TSHIRT-BASE'); // Auto casts string to ProductGroup array

printSection('5. Product Variant linked to ProductGroup', $renderer->render($childProduct->toArray()));
