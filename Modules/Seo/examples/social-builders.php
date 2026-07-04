<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Maatify\Seo\Web\Social\SocialPreviewBuilder;
use Maatify\Seo\Web\Social\OpenGraphBuilder;
use Maatify\Seo\Web\Social\TwitterCardBuilder;

echo "--- OpenGraph Builder ---\n";
$og = new OpenGraphBuilder();
$og->setTitle('Independent OpenGraph Title')
   ->setDescription('Independent description.')
   ->setType('article')
   ->setUrl('https://example.com/article')
   ->setImage('https://example.com/og-image.jpg')
   ->setSiteName('My Awesome Blog');

echo $og->toHtml() . "\n\n";

echo "--- TwitterCard Builder ---\n";
$tc = new TwitterCardBuilder();
$tc->setCard('summary_large_image')
   ->setTitle('Independent Twitter Title')
   ->setDescription('Twitter specific description.')
   ->setImage('https://example.com/twitter-image.jpg')
   ->setSite('@my_twitter_handle');

echo $tc->toHtml() . "\n\n";

echo "--- SocialPreview Builder (Orchestrator) ---\n";
$social = new SocialPreviewBuilder();
$social->setTitle('Shared Social Title')
       ->setDescription('Shared social description for both OG and Twitter.')
       ->setUrl('https://example.com/shared')
       ->setImage('https://example.com/shared-image.jpg')
       ->setSiteName('My Main Site')
       ->setTwitterSite('@main_site')
       ->setTwitterCard('summary');

// Override a specific value just for OpenGraph
$social->openGraph()->setType('website');

echo $social->toHtml() . "\n\n";

echo "Done.\n";
