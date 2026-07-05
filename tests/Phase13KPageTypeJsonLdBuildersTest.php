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

use Maatify\Seo\Web\JsonLd\Builder\AboutPageJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\CollectionPageJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ContactPageJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProfilePageJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\SearchResultsPageJsonLdBuilder;

function assertSameValue13K(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13K(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

// 1. AboutPageJsonLdBuilder Tests
$aboutBuilder = new AboutPageJsonLdBuilder();
assertSameValue13K('AboutPage seeds defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
], $aboutBuilder->toArray());

$aboutSchema = $aboutBuilder
    ->setName('About Us')
    ->setUrl('https://example.com/about')
    ->setDescription('Learn more about us')
    ->setIsPartOf('https://example.com')
    ->setBreadcrumb('https://example.com/about#breadcrumb')
    ->setPrimaryImageOfPage('https://example.com/image.jpg')
    ->setDatePublished('2023-01-01')
    ->setDateModified('2023-10-01')
    ->setAbout('Our Company')
    ->setMainEntity(['@type' => 'Organization', 'name' => 'Acme Corp'])
    ->toArray();

assertSameValue13K('AboutPage full schema string normalization', [
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    'name' => 'About Us',
    'url' => 'https://example.com/about',
    'description' => 'Learn more about us',
    'isPartOf' => ['@type' => 'WebSite', 'url' => 'https://example.com'],
    'breadcrumb' => ['@type' => 'BreadcrumbList', '@id' => 'https://example.com/about#breadcrumb'],
    'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => 'https://example.com/image.jpg'],
    'datePublished' => '2023-01-01',
    'dateModified' => '2023-10-01',
    'about' => ['@type' => 'Thing', 'name' => 'Our Company'],
    'mainEntity' => ['@type' => 'Organization', 'name' => 'Acme Corp'],
], $aboutSchema);


// 2. ContactPageJsonLdBuilder Tests
$contactBuilder = new ContactPageJsonLdBuilder();
assertSameValue13K('ContactPage seeds defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
], $contactBuilder->toArray());

$contactSchema = $contactBuilder
    ->setName('Contact Us')
    ->setContactPoint('customer support')
    ->toArray();

assertSameValue13K('ContactPage string normalization', [
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
    'name' => 'Contact Us',
    'contactPoint' => ['@type' => 'ContactPoint', 'contactType' => 'customer support'],
], $contactSchema);


// 3. CollectionPageJsonLdBuilder Tests
$collectionBuilder = new CollectionPageJsonLdBuilder();
assertSameValue13K('CollectionPage seeds defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
], $collectionBuilder->toArray());

$collectionBuilder->setHasPart(['https://example.com/item1', 'https://example.com/item2']);
assertSameValue13K('CollectionPage setHasPart normalization', [
    ['@type' => 'WebPage', 'url' => 'https://example.com/item1'],
    ['@type' => 'WebPage', 'url' => 'https://example.com/item2'],
], $collectionBuilder->get('hasPart'));

$collectionBuilder->addHasPart(['@type' => 'ItemPage', 'url' => 'https://example.com/item3']);
assertSameValue13K('CollectionPage addHasPart preservation', [
    ['@type' => 'WebPage', 'url' => 'https://example.com/item1'],
    ['@type' => 'WebPage', 'url' => 'https://example.com/item2'],
    ['@type' => 'ItemPage', 'url' => 'https://example.com/item3'],
], $collectionBuilder->get('hasPart'));


// 4. ProfilePageJsonLdBuilder Tests
$profileBuilder = new ProfilePageJsonLdBuilder();
assertSameValue13K('ProfilePage seeds defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'ProfilePage',
], $profileBuilder->toArray());

$profileBuilder->setMainEntity('Jane Doe');
assertSameValue13K('ProfilePage string normalization mainEntity', [
    '@type' => 'Person',
    'name' => 'Jane Doe',
], $profileBuilder->get('mainEntity'));


// 5. SearchResultsPageJsonLdBuilder Tests
$searchBuilder = new SearchResultsPageJsonLdBuilder();
assertSameValue13K('SearchResultsPage seeds defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'SearchResultsPage',
    'itemListElement' => [],
], $searchBuilder->toArray());

$searchBuilder->setQuery('json-ld');
assertSameValue13K('SearchResultsPage query', 'json-ld', $searchBuilder->get('query'));

$searchBuilder->setItemListElement(['https://example.com/r1', 'https://example.com/r2']);
assertSameValue13K('SearchResultsPage setItemListElement', [
    ['@type' => 'ListItem', 'position' => 1, 'item' => 'https://example.com/r1'],
    ['@type' => 'ListItem', 'position' => 2, 'item' => 'https://example.com/r2'],
], $searchBuilder->get('itemListElement'));

$searchBuilder->addResult('https://example.com/r3', 'Result 3');
$searchBuilder->addResult(['@type' => 'ListItem', 'item' => 'https://example.com/r4']);
assertSameValue13K('SearchResultsPage addResult', [
    ['@type' => 'ListItem', 'position' => 1, 'item' => 'https://example.com/r1'],
    ['@type' => 'ListItem', 'position' => 2, 'item' => 'https://example.com/r2'],
    ['@type' => 'ListItem', 'position' => 3, 'item' => 'https://example.com/r3', 'name' => 'Result 3'],
    ['@type' => 'ListItem', 'item' => 'https://example.com/r4', 'position' => 4],
], $searchBuilder->get('itemListElement'));

echo "Phase 13K Page Type JSON-LD builders tests passed.\n";
