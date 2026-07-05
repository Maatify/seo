<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Web/Social/SocialMetaTag.php';
require_once __DIR__ . '/../src/Web/Social/SocialImage.php';
require_once __DIR__ . '/../src/Web/Social/SocialMetaCollection.php';
require_once __DIR__ . '/../src/Web/Social/SocialMetaRenderOutput.php';
require_once __DIR__ . '/../src/Web/Social/TwitterCardBuilder.php';

use Maatify\Seo\Web\Social\SocialMetaTag;
use Maatify\Seo\Web\Social\SocialImage;
use Maatify\Seo\Web\Social\SocialMetaCollection;
use Maatify\Seo\Web\Social\SocialMetaRenderOutput;
use Maatify\Seo\Web\Social\TwitterCardBuilder;

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

// 1. Scalar Twitter/X fields tests
$builder = new TwitterCardBuilder();
$builder->setCard('summary_large_image')
        ->setSite('@site_handle')
        ->setCreator('@creator_handle')
        ->setTitle('Test Title')
        ->setDescription('Test Description')
        ->setPlayer('https://example.com/player')
        ->setPlayerWidth(800)
        ->setPlayerHeight(600)
        ->setAppNameIphone('App iPhone')
        ->setAppIdIphone('id_iphone')
        ->setAppUrlIphone('url_iphone')
        ->setAppNameIpad('App iPad')
        ->setAppIdIpad('id_ipad')
        ->setAppUrlIpad('url_ipad')
        ->setAppNameGoogleplay('App GooglePlay')
        ->setAppIdGoogleplay('id_googleplay')
        ->setAppUrlGoogleplay('url_googleplay');

$expectedArray = [
    ['name' => 'twitter:card', 'content' => 'summary_large_image', 'attribute' => 'name'],
    ['name' => 'twitter:site', 'content' => '@site_handle', 'attribute' => 'name'],
    ['name' => 'twitter:creator', 'content' => '@creator_handle', 'attribute' => 'name'],
    ['name' => 'twitter:title', 'content' => 'Test Title', 'attribute' => 'name'],
    ['name' => 'twitter:description', 'content' => 'Test Description', 'attribute' => 'name'],
    ['name' => 'twitter:player', 'content' => 'https://example.com/player', 'attribute' => 'name'],
    ['name' => 'twitter:player:width', 'content' => '800', 'attribute' => 'name'],
    ['name' => 'twitter:player:height', 'content' => '600', 'attribute' => 'name'],
    ['name' => 'twitter:app:name:iphone', 'content' => 'App iPhone', 'attribute' => 'name'],
    ['name' => 'twitter:app:id:iphone', 'content' => 'id_iphone', 'attribute' => 'name'],
    ['name' => 'twitter:app:url:iphone', 'content' => 'url_iphone', 'attribute' => 'name'],
    ['name' => 'twitter:app:name:ipad', 'content' => 'App iPad', 'attribute' => 'name'],
    ['name' => 'twitter:app:id:ipad', 'content' => 'id_ipad', 'attribute' => 'name'],
    ['name' => 'twitter:app:url:ipad', 'content' => 'url_ipad', 'attribute' => 'name'],
    ['name' => 'twitter:app:name:googleplay', 'content' => 'App GooglePlay', 'attribute' => 'name'],
    ['name' => 'twitter:app:id:googleplay', 'content' => 'id_googleplay', 'attribute' => 'name'],
    ['name' => 'twitter:app:url:googleplay', 'content' => 'url_googleplay', 'attribute' => 'name'],
];

assertSameValue($expectedArray, $builder->toArray(), 'TwitterCardBuilder scalar tags toArray');

$expectedHtml = '<meta name="twitter:card" content="summary_large_image">' . "\n" .
                '<meta name="twitter:site" content="@site_handle">' . "\n" .
                '<meta name="twitter:creator" content="@creator_handle">' . "\n" .
                '<meta name="twitter:title" content="Test Title">' . "\n" .
                '<meta name="twitter:description" content="Test Description">' . "\n" .
                '<meta name="twitter:player" content="https://example.com/player">' . "\n" .
                '<meta name="twitter:player:width" content="800">' . "\n" .
                '<meta name="twitter:player:height" content="600">' . "\n" .
                '<meta name="twitter:app:name:iphone" content="App iPhone">' . "\n" .
                '<meta name="twitter:app:id:iphone" content="id_iphone">' . "\n" .
                '<meta name="twitter:app:url:iphone" content="url_iphone">' . "\n" .
                '<meta name="twitter:app:name:ipad" content="App iPad">' . "\n" .
                '<meta name="twitter:app:id:ipad" content="id_ipad">' . "\n" .
                '<meta name="twitter:app:url:ipad" content="url_ipad">' . "\n" .
                '<meta name="twitter:app:name:googleplay" content="App GooglePlay">' . "\n" .
                '<meta name="twitter:app:id:googleplay" content="id_googleplay">' . "\n" .
                '<meta name="twitter:app:url:googleplay" content="url_googleplay">';

assertSameValue($expectedHtml, $builder->toHtml(), 'TwitterCardBuilder scalar tags toHtml');

// 2. Image behavior tests
$builder = new TwitterCardBuilder();

// setImage(string)
$builder->setImage('https://example.com/image1.jpg');
$array = $builder->toArray();
assertSameValue('twitter:image', $array[0]['name'], 'setImage(string) creates twitter:image');
assertSameValue('https://example.com/image1.jpg', $array[0]['content'], 'setImage(string) correct URL');
assertSameValue(1, count($array), 'setImage(string) creates exactly one tag (no alt)');

// setImage(SocialImage) replaces existing images and uses alt from SocialImage
$image2 = new SocialImage('https://example.com/image2.jpg');
$image2->setAlt('Image 2 Alt');
$builder->setImage($image2);
$array = $builder->toArray();
assertSameValue('twitter:image', $array[0]['name'], 'setImage(SocialImage) correct tag name');
assertSameValue('https://example.com/image2.jpg', $array[0]['content'], 'setImage(SocialImage) replaces URL');
assertSameValue('twitter:image:alt', $array[1]['name'], 'setImage(SocialImage) populates alt tag');
assertSameValue('Image 2 Alt', $array[1]['content'], 'setImage(SocialImage) correct alt content');
assertSameValue(2, count($array), 'setImage(SocialImage) with alt creates two tags');

// setImageAlt() overrides SocialImage alt
$builder->setImageAlt('Override Alt');
$array = $builder->toArray();
assertSameValue('Override Alt', $array[1]['content'], 'setImageAlt() overrides existing image alt');

// setImageAlt() before setImage() still takes precedence
$builder = new TwitterCardBuilder();
$builder->setImageAlt('Precedence Alt');
$image3 = new SocialImage('https://example.com/image3.jpg');
$image3->setAlt('SocialImage Alt');
$builder->setImage($image3);
$array = $builder->toArray();
assertSameValue('Precedence Alt', $array[1]['content'], 'setImageAlt() before setImage() takes precedence over SocialImage alt');

// no multiple image support - setImage() overwrites completely
$builder = new TwitterCardBuilder();
$builder->setImage('https://example.com/first.jpg');
$builder->setImage('https://example.com/second.jpg');
$array = $builder->toArray();
assertSameValue(1, count($array), 'setImage() only supports one image (replaces previous)');
assertSameValue('https://example.com/second.jpg', $array[0]['content'], 'setImage() replaced URL');

// 3. Output formats, tag ordering and escaping
$builder = new TwitterCardBuilder();
$builder->setTitle('Title "with" quotes <&>')
        ->setImage('https://example.com/img.jpg');

$expectedArray = [
    ['name' => 'twitter:title', 'content' => 'Title "with" quotes <&>', 'attribute' => 'name'],
    ['name' => 'twitter:image', 'content' => 'https://example.com/img.jpg', 'attribute' => 'name'],
];
assertSameValue($expectedArray, $builder->toArray(), 'TwitterCardBuilder escaping array format');

$expectedHtmlEscaped = '<meta name="twitter:title" content="Title &quot;with&quot; quotes &lt;&amp;&gt;">' . "\n" .
                       '<meta name="twitter:image" content="https://example.com/img.jpg">';
assertSameValue($expectedHtmlEscaped, $builder->toHtml(), 'TwitterCardBuilder escaping HTML format');

$collection = $builder->toCollection();
assertSameValue(true, $collection instanceof SocialMetaCollection, 'toCollection returns SocialMetaCollection');
assertSameValue($expectedHtmlEscaped, $collection->toHtml(), 'toCollection HTML matches');

$renderOutput = $builder->toRenderOutput();
assertSameValue(true, $renderOutput instanceof SocialMetaRenderOutput, 'toRenderOutput returns SocialMetaRenderOutput');
assertSameValue($expectedHtmlEscaped, $renderOutput->toHtml(), 'toRenderOutput HTML matches');

if ($failures > 0) {
    echo "\nPhase 14C Twitter/X Card Builder tests failed with $failures failures.\n";
    exit(1);
}

echo "Phase 14C Twitter/X Card Builder tests passed.\n";
