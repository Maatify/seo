<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Maatify\Seo\Web\JsonLd\Builder\PersonJsonLdBuilder;

function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new \RuntimeException($message . "\nExpected: " . print_r($expected, true) . "\nActual: " . print_r($actual, true));
    }
}

echo "Testing PersonJsonLdBuilder...\n";

// 1. Initial State
$builder = new PersonJsonLdBuilder();
$data = $builder->toArray();
assertSameValue('https://schema.org', $data['@context'], 'Context should be schema.org');
assertSameValue('Person', $data['@type'], 'Type should be Person');

// 2. Setters
$builder->setName('John Doe')
        ->setUrl('https://example.com/johndoe')
        ->setDescription('A software engineer')
        ->setJobTitle('Senior Developer')
        ->setEmail('john@example.com')
        ->setTelephone('+1-555-555-1234');

$data = $builder->toArray();
assertSameValue('John Doe', $data['name'], 'Name should be set');
assertSameValue('https://example.com/johndoe', $data['url'], 'Url should be set');
assertSameValue('A software engineer', $data['description'], 'Description should be set');
assertSameValue('Senior Developer', $data['jobTitle'], 'Job title should be set');
assertSameValue('john@example.com', $data['email'], 'Email should be set');
assertSameValue('+1-555-555-1234', $data['telephone'], 'Telephone should be set');

// 3. Image arrays
$builder->setImage('https://example.com/img1.jpg');
$data = $builder->toArray();
assertSameValue('https://example.com/img1.jpg', $data['image'], 'Image as string should work');

$builder->setImage(['https://example.com/img1.jpg', 'https://example.com/img2.jpg']);
$data = $builder->toArray();
assertSameValue(['https://example.com/img1.jpg', 'https://example.com/img2.jpg'], $data['image'], 'Image as array should work');

// 4. worksFor string becomes Organization array with name
$builder->setWorksFor('Acme Corp');
$data = $builder->toArray();
assertSameValue('Organization', $data['worksFor']['@type'] ?? null, 'worksFor type should be Organization');
assertSameValue('Acme Corp', $data['worksFor']['name'] ?? null, 'worksFor name should be set from string');

// 5. worksFor array is accepted and defaults @type to Organization if missing
$builder->setWorksFor([
    'name' => 'Tech Corp',
]);
$data = $builder->toArray();
assertSameValue('Organization', $data['worksFor']['@type'] ?? null, 'worksFor type should be accepted and defaulted to Organization');
assertSameValue('Tech Corp', $data['worksFor']['name'] ?? null, 'worksFor name should be accepted as array');

$builder->setWorksFor([
    '@type' => 'Corporation',
    'name' => 'Big Corp',
]);
$data = $builder->toArray();
assertSameValue('Corporation', $data['worksFor']['@type'] ?? null, 'worksFor type should not be overridden if present');
assertSameValue('Big Corp', $data['worksFor']['name'] ?? null, 'worksFor name should be present');

// 6. sameAs supports setSameAs and addSameAs
$builder->setSameAs(['https://twitter.com/johndoe']);
$data = $builder->toArray();
assertSameValue(['https://twitter.com/johndoe'], $data['sameAs'], 'setSameAs should set array');

$builder->addSameAs('https://linkedin.com/in/johndoe');
$data = $builder->toArray();
assertSameValue(['https://twitter.com/johndoe', 'https://linkedin.com/in/johndoe'], $data['sameAs'], 'addSameAs should append to array');

// 7. address array defaults @type to PostalAddress if missing
$builder->setAddress([
    'streetAddress' => '123 Main St',
]);
$data = $builder->toArray();
assertSameValue('PostalAddress', $data['address']['@type'] ?? null, 'address type should default to PostalAddress');
assertSameValue('123 Main St', $data['address']['streetAddress'] ?? null, 'address field should be present');

$builder->setAddress([
    '@type' => 'SomeAddress',
    'streetAddress' => '456 Main St',
]);
$data = $builder->toArray();
assertSameValue('SomeAddress', $data['address']['@type'] ?? null, 'address type should not be overridden if present');

// 8. setPostalAddress builds a PostalAddress array
$builder->setPostalAddress(
    '789 Main St',
    'Anytown',
    'CA',
    '90210',
    'USA'
);
$data = $builder->toArray();
assertSameValue('PostalAddress', $data['address']['@type'] ?? null, 'setPostalAddress should set @type PostalAddress');
assertSameValue('789 Main St', $data['address']['streetAddress'] ?? null, 'streetAddress should be set');
assertSameValue('Anytown', $data['address']['addressLocality'] ?? null, 'addressLocality should be set');
assertSameValue('CA', $data['address']['addressRegion'] ?? null, 'addressRegion should be set');
assertSameValue('90210', $data['address']['postalCode'] ?? null, 'postalCode should be set');
assertSameValue('USA', $data['address']['addressCountry'] ?? null, 'addressCountry should be set');

// Partial postal address
$builder->setPostalAddress(
    addressLocality: 'City',
    addressCountry: 'Country'
);
$data = $builder->toArray();
assertSameValue('PostalAddress', $data['address']['@type'] ?? null, 'setPostalAddress should set @type PostalAddress');
assertSameValue(false, isset($data['address']['streetAddress']), 'streetAddress should not be set');
assertSameValue('City', $data['address']['addressLocality'] ?? null, 'addressLocality should be set');
assertSameValue(false, isset($data['address']['addressRegion']), 'addressRegion should not be set');
assertSameValue(false, isset($data['address']['postalCode']), 'postalCode should not be set');
assertSameValue('Country', $data['address']['addressCountry'] ?? null, 'addressCountry should be set');

// 9. Output remains compatible with JSON-LD rendering
$json = $builder->toJson();
$decoded = json_decode($json, true);
assertSameValue('John Doe', $decoded['name'] ?? null, 'JSON rendering should work');

echo "PersonJsonLdBuilder passed all tests!\n";
