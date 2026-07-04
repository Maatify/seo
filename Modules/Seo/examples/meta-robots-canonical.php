<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Maatify\Seo\Web\Robots\MetaRobotsBuilder;
use Maatify\Seo\Web\Indexing\CanonicalUrlBuilder;

echo "--- Meta Robots Builder ---\n";

$robotsBuilder = new MetaRobotsBuilder();

// Example 1: Standard indexable page
$indexHtml = $robotsBuilder->index()->follow()->toHtml();
echo "Standard Indexable: \n" . $indexHtml . "\n\n";

// Example 2: Non-indexable search results page with snippet restrictions
$noIndexBuilder = new MetaRobotsBuilder();
$noIndexHtml = $noIndexBuilder
    ->noIndex()
    ->noFollow()
    ->noArchive()
    ->maxSnippet(50)
    ->toHtml();
echo "Restricted Non-Indexable: \n" . $noIndexHtml . "\n\n";


echo "--- Canonical URL Builder ---\n";

$canonicalBuilder = new CanonicalUrlBuilder('https://example.com');

// Example 1: Basic clean canonical URL
$basicCanonical = $canonicalBuilder
    ->setPath('/my-category/my-product')
    ->toHtml();

echo "Basic Canonical: \n" . $basicCanonical . "\n\n";

// Example 2: Preserving specific pagination query parameters
$paginatedCanonicalBuilder = new CanonicalUrlBuilder('https://example.com');
$paginatedCanonical = $paginatedCanonicalBuilder
    ->setPath('/blog')
    ->setQueryParams(['page' => 3, 'sort' => 'recent', 'session_id' => '123456'])
    ->preserveQueryParams(['page', 'sort']) // Drops session_id
    ->toHtml();

echo "Paginated Canonical (filtered queries): \n" . $paginatedCanonical . "\n\n";

echo "Done.\n";
