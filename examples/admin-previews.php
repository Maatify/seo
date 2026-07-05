<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Maatify\Seo\Admin\Preview\SerpPreviewFactory;
use Maatify\Seo\Admin\Preview\SocialPreviewFactory;

echo "--- Admin SERP Preview ---\n";

$serpPreview = SerpPreviewFactory::fromArray([
    'title' => 'My Page Title - Example',
    'description' => 'This is the description that will show up in search engine results.',
    'url' => 'https://example.com/my-page',
    'robots' => 'index, follow',
]);

// toArray() formats the DTO to an array
$serpData = $serpPreview->toArray();

echo json_encode($serpData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "--- Admin Social Preview ---\n";

$socialPreview = SocialPreviewFactory::fromArray([
    'title' => 'My Social Media Title',
    'description' => 'A catchy description for Facebook, LinkedIn, etc.',
    'url' => 'https://example.com/my-page',
    'image_url' => 'https://example.com/social-share.jpg',
    'site_name' => 'My Example Site',
    'twitter_card' => 'summary_large_image'
]);

// toArray() formats the DTO to an array
$socialData = $socialPreview->toArray();

echo json_encode($socialData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "Done.\n";
