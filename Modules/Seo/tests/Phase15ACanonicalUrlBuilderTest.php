<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Web/Indexing/CanonicalUrlBuilder.php';

use Maatify\Seo\Web\Indexing\CanonicalUrlBuilder;

$failures = 0;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    global $failures;
    if ($expected !== $actual) {
        $failures++;
        echo "FAIL: $message\n";
        echo "  Expected: " . print_r($expected, true) . "\n";
        echo "  Actual:   " . print_r($actual, true) . "\n";
    }
}

function assertTrueValue(bool $actual, string $message): void
{
    assertSameValue(true, $actual, $message);
}

function assertFalseValue(bool $actual, string $message): void
{
    assertSameValue(false, $actual, $message);
}

echo "Running Phase 15A Canonical URL Builder Tests...\n\n";

// 1. Constructor and base URL
$builder = new CanonicalUrlBuilder();
assertSameValue('', $builder->build(), 'Empty builder should return empty string');

$builder = new CanonicalUrlBuilder('https://example.com');
assertSameValue('https://example.com', $builder->build(), 'Constructor with baseUrl should build it');

$builder = new CanonicalUrlBuilder();
$builder->setBaseUrl('https://example.com/');
assertSameValue('https://example.com', $builder->build(), 'setBaseUrl should strip trailing slash when no path');

// 2. Path behavior
$builder = new CanonicalUrlBuilder('https://example.com');
$builder->setPath('about-us');
assertSameValue('https://example.com/about-us', $builder->build(), 'Path should be appended');

$builder = new CanonicalUrlBuilder('https://example.com/');
$builder->setPath('/about-us/');
assertSameValue('https://example.com/about-us/', $builder->build(), 'Duplicate slashes should be normalized');

$builder = new CanonicalUrlBuilder('https://example.com');
$builder->setPath('');
assertSameValue('https://example.com', $builder->build(), 'Empty path should only return baseUrl');

$builder = new CanonicalUrlBuilder();
$builder->setPath('https://another.com/page');
assertSameValue('https://another.com/page', $builder->build(), 'No baseUrl with absolute URL path should return path');

$builder = new CanonicalUrlBuilder();
$builder->setPath('/relative/path');
assertSameValue('/relative/path', $builder->build(), 'No baseUrl with relative path should return path');

// 3. Query params
$builder = new CanonicalUrlBuilder('https://example.com/page');
$builder->setQueryParams(['sort' => 'asc', 'page' => 2]);
assertSameValue('https://example.com/page?sort=asc&page=2', $builder->build(), 'setQueryParams should add queries');

$builder->addQueryParam('filter', 'new');
assertSameValue('https://example.com/page?sort=asc&page=2&filter=new', $builder->build(), 'addQueryParam should add one param');

$builder->addQueryParam('sort', 'desc');
assertSameValue('https://example.com/page?sort=desc&page=2&filter=new', $builder->build(), 'addQueryParam should replace existing param');

$builder->removeQueryParam('page');
assertSameValue('https://example.com/page?sort=desc&filter=new', $builder->build(), 'removeQueryParam should remove one param');

$builder->preserveQueryParams(['sort']);
assertSameValue('https://example.com/page?sort=desc', $builder->build(), 'preserveQueryParams should keep only allowed keys');

$builder->clearQueryParams();
assertSameValue('https://example.com/page', $builder->build(), 'clearQueryParams should remove all params');

$builder->setQueryParams(['a' => 'b', 'c' => null, 'd' => 'e']);
assertSameValue('https://example.com/page?a=b&d=e', $builder->build(), 'Null values should be omitted');

$builder->setQueryParams(['is_active' => true, 'is_admin' => false]);
assertSameValue('https://example.com/page?is_active=1&is_admin=0', $builder->build(), 'Boolean true becomes 1, false becomes 0');

$builder->setQueryParams(['q' => 'hello world', 'path' => 'a/b/c', 'emoji' => '🚀']);
assertSameValue('https://example.com/page?q=hello%20world&path=a%2Fb%2Fc&emoji=%F0%9F%9A%80', $builder->build(), 'RFC3986 query encoding');

$builder = new CanonicalUrlBuilder('https://example.com/page?existing=1');
$builder->setQueryParams(['new' => 2]);
assertSameValue('https://example.com/page?existing=1&new=2', $builder->build(), 'Builder should handle baseUrl with existing query string properly');

// 4. Output
$builder = new CanonicalUrlBuilder('https://example.com/page');
$builder->setQueryParams(['q' => 'test"escape']);
$expectedHtml = '<link rel="canonical" href="https://example.com/page?q=test%22escape">';
assertSameValue($expectedHtml, $builder->toHtml(), 'toHtml should escape quotes and output link tag');

// 5. Architectural guarantees
$isStringOnly = is_string($builder->build()) && is_string($builder->toHtml());
assertTrueValue($isStringOnly, 'Output remains strings only');

echo "\n";
if ($failures > 0) {
    echo "FAILED with $failures errors.\n";
    exit(1);
}

echo "SUCCESS: All tests passed.\n";
exit(0);
