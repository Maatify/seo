<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Maatify\Seo\Web\Hreflang\HreflangLinkBuilder;

echo "--- Hreflang Link Generation ---\n";

$builder = new HreflangLinkBuilder();

// Add specific languages/regions
$builder->add('en', 'https://example.com/en/page');
$builder->add('en-US', 'https://example.com/en-us/page');
$builder->add('en-GB', 'https://example.com/en-gb/page');
$builder->add('fr', 'https://example.com/fr/page');

// Add the x-default fallback for unmatched languages
$builder->xDefault('https://example.com/en/page');

// Render the link tags
$html = $builder->render();

echo $html . "\n";

echo "Done.\n";
