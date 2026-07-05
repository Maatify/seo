<?php

declare(strict_types=1);

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

use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuilderInterface;
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;

function assertSameValue13B(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13B(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

$builder = new ProductJsonLdBuilder();
assertTrueValue13B('product builder implements builder interface', $builder instanceof JsonLdBuilderInterface);
assertSameValue13B('product builder seeds schema.org product defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
], $builder->toArray());
assertSameValue13B('setName is fluent', $builder, $builder->setName('Maatify Demo Product'));

$schema = $builder
    ->setDescription('A demo product for JSON-LD output.')
    ->setSku('SKU-13B')
    ->setBrand('Maatify')
    ->setImage([
        'https://example.com/images/product-front.jpg',
        'https://example.com/images/product-side.jpg',
    ])
    ->setCategory('Software')
    ->setUrl('https://example.com/products/demo')
    ->setCurrency('USD')
    ->setPrice('19.99')
    ->setAvailability('https://schema.org/InStock')
    ->setCondition('https://schema.org/NewCondition')
    ->setOfferUrl('https://example.com/products/demo?purchase=1')
    ->setAggregateRating(4.8, 27)
    ->addReview('Jane Doe', 5, 'Excellent product.')
    ->addReview('John Doe', 4.5, 'Useful and well documented.')
    ->toArray();

assertSameValue13B('full product schema', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => 'Maatify Demo Product',
    'description' => 'A demo product for JSON-LD output.',
    'sku' => 'SKU-13B',
    'brand' => [
        '@type' => 'Brand',
        'name' => 'Maatify',
    ],
    'image' => [
        'https://example.com/images/product-front.jpg',
        'https://example.com/images/product-side.jpg',
    ],
    'category' => 'Software',
    'url' => 'https://example.com/products/demo',
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'USD',
        'price' => '19.99',
        'availability' => 'https://schema.org/InStock',
        'itemCondition' => 'https://schema.org/NewCondition',
        'url' => 'https://example.com/products/demo?purchase=1',
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => 4.8,
        'reviewCount' => 27,
    ],
    'review' => [
        [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name' => 'Jane Doe',
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => 5,
            ],
            'reviewBody' => 'Excellent product.',
        ],
        [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name' => 'John Doe',
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => 4.5,
            ],
            'reviewBody' => 'Useful and well documented.',
        ],
    ],
], $schema);

assertSameValue13B(
    'single image remains a string',
    'https://example.com/image.jpg',
    (new ProductJsonLdBuilder())->setImage('https://example.com/image.jpg')->get('image')
);
assertSameValue13B(
    'toJson can encode product schema',
    '{"@context":"https://schema.org","@type":"Product","name":"Maatify Demo Product"}',
    (new ProductJsonLdBuilder())->setName('Maatify Demo Product')->toJson(JSON_UNESCAPED_SLASHES)
);

echo "Phase 13B product JSON-LD builder tests passed.\n";
