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
use Maatify\Seo\Web\JsonLd\Builder\OfferJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\OrganizationJsonLdBuilder;

function assertSameValue13O(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13O(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

$nestedOrganization = (new OrganizationJsonLdBuilder())->setName('Nested Organization');
$root = (new OfferJsonLdBuilder())->set('customNode', $nestedOrganization);

assertSameValue13O(
    'set stores nested builder without normalizing it',
    $nestedOrganization,
    $root->get('customNode')
);
assertSameValue13O(
    'nested builder is resolved to an array',
    [
        '@type' => 'Organization',
        'name' => 'Nested Organization',
    ],
    $root->toArray()['customNode']
);
assertSameValue13O(
    'root builder keeps its context',
    'https://schema.org',
    $root->toArray()['@context']
);
assertTrueValue13O(
    'nested builder context is removed',
    !array_key_exists('@context', $root->toArray()['customNode'])
);

$listRoot = new OfferJsonLdBuilder();
$listRoot->set('items', [
    (new OrganizationJsonLdBuilder())->setName('First'),
    (new OrganizationJsonLdBuilder())->setName('Second'),
]);
assertSameValue13O(
    'builders inside numeric lists are resolved',
    [
        [
            '@type' => 'Organization',
            'name' => 'First',
        ],
        [
            '@type' => 'Organization',
            'name' => 'Second',
        ],
    ],
    $listRoot->toArray()['items']
);

$nestedArrayRoot = new OfferJsonLdBuilder();
$nestedArrayRoot->set('wrapper', [
    '@context' => 'https://example.com/raw-context',
    'nodes' => [
        'organization' => (new OrganizationJsonLdBuilder())->setName('Deep Organization'),
    ],
]);
$nestedArrayOutput = $nestedArrayRoot->toArray();
assertSameValue13O(
    'builders inside nested raw arrays are resolved',
    [
        '@type' => 'Organization',
        'name' => 'Deep Organization',
    ],
    $nestedArrayOutput['wrapper']['nodes']['organization']
);
assertSameValue13O(
    'raw array context is preserved',
    'https://example.com/raw-context',
    $nestedArrayOutput['wrapper']['@context']
);

$stringSeller = (new OfferJsonLdBuilder())->setSeller('Store Name')->toArray();
assertSameValue13O(
    'string seller keeps the current output',
    [
        '@type' => 'Organization',
        'name' => 'Store Name',
    ],
    $stringSeller['seller']
);

$rawSellerWithoutType = (new OfferJsonLdBuilder())->setSeller(['name' => 'Raw Store'])->toArray();
assertSameValue13O(
    'raw seller without type keeps the current output',
    [
        'name' => 'Raw Store',
        '@type' => 'Organization',
    ],
    $rawSellerWithoutType['seller']
);

$rawSellerWithType = (new OfferJsonLdBuilder())->setSeller([
    '@type' => 'LocalBusiness',
    'name' => 'Raw Local Store',
])->toArray();
assertSameValue13O(
    'raw seller with type is preserved',
    [
        '@type' => 'LocalBusiness',
        'name' => 'Raw Local Store',
    ],
    $rawSellerWithType['seller']
);

$sellerBuilder = (new OrganizationJsonLdBuilder())->setName('Typed Store');
$builderSellerOffer = (new OfferJsonLdBuilder())->setSeller($sellerBuilder);
assertTrueValue13O(
    'builder seller implements the composition contract',
    $builderSellerOffer->get('seller') instanceof JsonLdBuilderInterface
);
assertSameValue13O(
    'builder seller is resolved without nested context',
    [
        '@type' => 'Organization',
        'name' => 'Typed Store',
    ],
    $builderSellerOffer->toArray()['seller']
);

$jsonOffer = (new OfferJsonLdBuilder())->setPrice('19.99');
assertSameValue13O(
    'toJson keeps its existing contract',
    '{"@context":"https://schema.org","@type":"Offer","price":"19.99"}',
    $jsonOffer->toJson(JSON_UNESCAPED_SLASHES)
);

echo "Phase 13O composition tests passed.\n";
