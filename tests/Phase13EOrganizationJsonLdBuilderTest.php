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
use Maatify\Seo\Web\JsonLd\Builder\OrganizationJsonLdBuilder;

function assertSameValue13E(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13E(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

$builder = new OrganizationJsonLdBuilder();
assertTrueValue13E('organization builder implements builder interface', $builder instanceof JsonLdBuilderInterface);
assertSameValue13E('organization builder seeds schema.org defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
], $builder->toArray());

$builder = OrganizationJsonLdBuilder::localBusiness();
assertSameValue13E('local business builder factory', [
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
], $builder->toArray());

$builder = OrganizationJsonLdBuilder::corporation();
assertSameValue13E('corporation builder factory', [
    '@context' => 'https://schema.org',
    '@type' => 'Corporation',
], $builder->toArray());

$builder = OrganizationJsonLdBuilder::store();
assertSameValue13E('store builder factory', [
    '@context' => 'https://schema.org',
    '@type' => 'Store',
], $builder->toArray());

$builder = OrganizationJsonLdBuilder::organization();
assertSameValue13E('setName is fluent', $builder, $builder->setName('Maatify Demo Org'));

$schema = $builder
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

assertSameValue13E('full organization schema', [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'Maatify Demo Org',
    'url' => 'https://example.com',
    'logo' => 'https://example.com/logo.png',
    'description' => 'A demo organization for JSON-LD output.',
    'sameAs' => [
        'https://twitter.com/example',
        'https://github.com/example',
    ],
    'contactPoint' => [
        [
            'telephone' => '+1-800-555-1212',
            'contactType' => 'customer service',
            '@type' => 'ContactPoint',
        ]
    ],
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => '123 Demo St',
        'addressLocality' => 'Demo City',
        'addressRegion' => 'CA',
        'postalCode' => '90210',
        'addressCountry' => 'US',
    ]
], $schema);

// Test changing type
$builder->asLocalBusiness();
assertSameValue13E('changed type to LocalBusiness', 'LocalBusiness', $builder->get('@type'));

// Test setting whole address array
$builder->setAddress([
    '@type' => 'PostalAddress',
    'addressCountry' => 'UK'
]);
assertSameValue13E('set address directly', [
    '@type' => 'PostalAddress',
    'addressCountry' => 'UK'
], $builder->get('address'));

echo "Phase 13E organization JSON-LD builder tests passed.\n";
