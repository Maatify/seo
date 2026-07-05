<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Maatify\Seo\Web\JsonLd\Builder\WebSiteJsonLdBuilder;

function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new \RuntimeException($message . "\nExpected: " . print_r($expected, true) . "\nActual: " . print_r($actual, true));
    }
}

echo "Testing WebSiteJsonLdBuilder...\n";

// 1. Initial State
$builder = new WebSiteJsonLdBuilder();
$data = $builder->toArray();
assertSameValue('https://schema.org', $data['@context'], 'Context should be schema.org');
assertSameValue('WebSite', $data['@type'], 'Type should be WebSite');

// 2. Setters
$builder->setName('Example Site')
        ->setUrl('https://example.com')
        ->setDescription('An example website for testing');

$data = $builder->toArray();
assertSameValue('Example Site', $data['name'], 'Name should be set');
assertSameValue('https://example.com', $data['url'], 'Url should be set');
assertSameValue('An example website for testing', $data['description'], 'Description should be set');

// 3. publisher string becomes Organization array with name
$builder->setPublisher('Example Publisher');
$data = $builder->toArray();
assertSameValue('Organization', $data['publisher']['@type'] ?? null, 'Publisher type should be Organization');
assertSameValue('Example Publisher', $data['publisher']['name'] ?? null, 'Publisher name should be set from string');

// 4. publisher array is accepted as provided
$builder->setPublisher([
    '@type' => 'Person',
    'name' => 'Jane Doe',
]);
$data = $builder->toArray();
assertSameValue('Person', $data['publisher']['@type'] ?? null, 'Publisher type should be accepted as array');
assertSameValue('Jane Doe', $data['publisher']['name'] ?? null, 'Publisher name should be accepted as array');

// 5. SearchAction includes @type, target, and query-input
$builder->setSearchAction('https://example.com/search?q={search_term_string}', 'search_term_string');
$data = $builder->toArray();
assertSameValue('SearchAction', $data['potentialAction']['@type'] ?? null, 'SearchAction type should be SearchAction');
assertSameValue('https://example.com/search?q={search_term_string}', $data['potentialAction']['target'] ?? null, 'SearchAction target should match');
assertSameValue('required name=search_term_string', $data['potentialAction']['query-input'] ?? null, 'SearchAction query-input should match');

// 6. setPotentialAction adds @type SearchAction if missing
$builder->setPotentialAction([
    'target' => 'https://example.com/search2?q={query}',
    'query-input' => 'required name=query'
]);
$data = $builder->toArray();
assertSameValue('SearchAction', $data['potentialAction']['@type'] ?? null, 'PotentialAction should default to SearchAction');
assertSameValue('https://example.com/search2?q={query}', $data['potentialAction']['target'] ?? null, 'Target should match from array');

// 7. Output remains compatible with JSON-LD rendering
$json = $builder->toJson();
$decoded = json_decode($json, true);
assertSameValue('https://example.com/search2?q={query}', $decoded['potentialAction']['target'] ?? null, 'JSON rendering should work');

echo "WebSiteJsonLdBuilder passed all tests!\n";
