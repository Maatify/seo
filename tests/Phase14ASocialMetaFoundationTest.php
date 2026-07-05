<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Web/Social/SocialMetaTag.php';
require_once __DIR__ . '/../src/Web/Social/SocialImage.php';
require_once __DIR__ . '/../src/Web/Social/SocialMetaCollection.php';
require_once __DIR__ . '/../src/Web/Social/SocialMetaRenderOutput.php';

use Maatify\Seo\Web\Social\SocialMetaTag;
use Maatify\Seo\Web\Social\SocialImage;
use Maatify\Seo\Web\Social\SocialMetaCollection;
use Maatify\Seo\Web\Social\SocialMetaRenderOutput;

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

// 1. SocialMetaTag tests
$tag = new SocialMetaTag('og:title', 'Test Title');
assertSameValue('og:title', $tag->getName(), 'SocialMetaTag getName');
assertSameValue('Test Title', $tag->getContent(), 'SocialMetaTag getContent');
assertSameValue('property', $tag->getAttribute(), 'SocialMetaTag getAttribute default');

$tagArray = $tag->toArray();
assertSameValue('og:title', $tagArray['name'], 'SocialMetaTag toArray name');
assertSameValue('Test Title', $tagArray['content'], 'SocialMetaTag toArray content');
assertSameValue('property', $tagArray['attribute'], 'SocialMetaTag toArray attribute');

$expectedHtml = '<meta property="og:title" content="Test Title">';
assertSameValue($expectedHtml, $tag->toHtml(), 'SocialMetaTag toHtml default');

$customTag = new SocialMetaTag('twitter:card', 'summary_large_image', 'name');
assertSameValue('twitter:card', $customTag->getName(), 'SocialMetaTag custom name');
assertSameValue('summary_large_image', $customTag->getContent(), 'SocialMetaTag custom content');
assertSameValue('name', $customTag->getAttribute(), 'SocialMetaTag custom attribute');
assertSameValue('<meta name="twitter:card" content="summary_large_image">', $customTag->toHtml(), 'SocialMetaTag toHtml custom attribute');

$itempropTag = new SocialMetaTag('image', 'https://example.com/img.jpg', 'itemprop');
assertSameValue('<meta itemprop="image" content="https://example.com/img.jpg">', $itempropTag->toHtml(), 'SocialMetaTag toHtml itemprop attribute');

$escapedTag = new SocialMetaTag('bad"name', 'bad"content<', 'bad"attr');
$expectedEscapedHtml = '<meta bad&quot;attr="bad&quot;name" content="bad&quot;content&lt;">';
assertSameValue($expectedEscapedHtml, $escapedTag->toHtml(), 'SocialMetaTag toHtml escaping');

// 2. SocialImage tests
$image = new SocialImage('https://example.com/image.jpg');
assertSameValue('https://example.com/image.jpg', $image->getUrl(), 'SocialImage getUrl');
assertSameValue(null, $image->getSecureUrl(), 'SocialImage getSecureUrl default null');
assertSameValue(null, $image->getType(), 'SocialImage getType default null');
assertSameValue(null, $image->getWidth(), 'SocialImage getWidth default null');
assertSameValue(null, $image->getHeight(), 'SocialImage getHeight default null');
assertSameValue(null, $image->getAlt(), 'SocialImage getAlt default null');

$imageArray = $image->toArray();
assertSameValue(['url' => 'https://example.com/image.jpg'], $imageArray, 'SocialImage toArray default');

$image->setSecureUrl('https://secure.example.com/image.jpg')
      ->setType('image/jpeg')
      ->setWidth(1200)
      ->setHeight(630)
      ->setAlt('Example Image');

assertSameValue('https://secure.example.com/image.jpg', $image->getSecureUrl(), 'SocialImage getSecureUrl after set');
assertSameValue('image/jpeg', $image->getType(), 'SocialImage getType after set');
assertSameValue(1200, $image->getWidth(), 'SocialImage getWidth after set');
assertSameValue(630, $image->getHeight(), 'SocialImage getHeight after set');
assertSameValue('Example Image', $image->getAlt(), 'SocialImage getAlt after set');

$expectedImageArray = [
    'url' => 'https://example.com/image.jpg',
    'secure_url' => 'https://secure.example.com/image.jpg',
    'type' => 'image/jpeg',
    'width' => 1200,
    'height' => 630,
    'alt' => 'Example Image',
];
assertSameValue($expectedImageArray, $image->toArray(), 'SocialImage toArray with optional fields');

// 3. SocialMetaCollection tests
$collection = new SocialMetaCollection();
assertTrueValue($collection->isEmpty(), 'SocialMetaCollection isEmpty initial');
assertSameValue(0, $collection->count(), 'SocialMetaCollection count initial');
assertSameValue([], $collection->all(), 'SocialMetaCollection all initial');
assertSameValue([], $collection->toArray(), 'SocialMetaCollection toArray initial');
assertSameValue('', $collection->toHtml(), 'SocialMetaCollection toHtml initial');

$collection->add(new SocialMetaTag('og:title', 'Collection Title'));
$collection->addTag('twitter:title', 'Twitter Title', 'name');

assertFalseValue($collection->isEmpty(), 'SocialMetaCollection isEmpty after add');
assertSameValue(2, $collection->count(), 'SocialMetaCollection count after add');

$tags = $collection->all();
assertSameValue('og:title', $tags[0]->getName(), 'SocialMetaCollection tags[0] name');
assertSameValue('twitter:title', $tags[1]->getName(), 'SocialMetaCollection tags[1] name');

$expectedCollectionArray = [
    ['name' => 'og:title', 'content' => 'Collection Title', 'attribute' => 'property'],
    ['name' => 'twitter:title', 'content' => 'Twitter Title', 'attribute' => 'name'],
];
assertSameValue($expectedCollectionArray, $collection->toArray(), 'SocialMetaCollection toArray');

$expectedCollectionHtml = '<meta property="og:title" content="Collection Title">' . "\n" . '<meta name="twitter:title" content="Twitter Title">';
assertSameValue($expectedCollectionHtml, $collection->toHtml(), 'SocialMetaCollection toHtml');
assertSameValue('<meta property="og:title" content="Collection Title">|<meta name="twitter:title" content="Twitter Title">', $collection->toHtml('|'), 'SocialMetaCollection toHtml custom separator');

// Test preserves insertion order and does not deduplicate duplicate tags
$collection->addTag('og:title', 'Second Title');
assertSameValue(3, $collection->count(), 'SocialMetaCollection count after duplicate add');
$tags = $collection->all();
assertSameValue('og:title', $tags[0]->getName(), 'SocialMetaCollection tags[0] name (duplicate test)');
assertSameValue('og:title', $tags[2]->getName(), 'SocialMetaCollection tags[2] name (duplicate test)');
assertSameValue('Collection Title', $tags[0]->getContent(), 'SocialMetaCollection tags[0] content (duplicate test)');
assertSameValue('Second Title', $tags[2]->getContent(), 'SocialMetaCollection tags[2] content (duplicate test)');

// 4. SocialMetaRenderOutput tests
$renderOutput = new SocialMetaRenderOutput($collection);
assertSameValue($collection, $renderOutput->getCollection(), 'SocialMetaRenderOutput getCollection');

$outputTags = $renderOutput->getTags();
assertSameValue($tags, $outputTags, 'SocialMetaRenderOutput getTags delegates to collection all');

$outputArray = $renderOutput->toArray();
assertSameValue($collection->toArray(), $outputArray, 'SocialMetaRenderOutput toArray delegates to collection toArray');

$outputHtml = $renderOutput->toHtml();
assertSameValue($collection->toHtml(), $outputHtml, 'SocialMetaRenderOutput toHtml delegates to collection toHtml');

$outputHtmlPipe = $renderOutput->toHtml('|');
assertSameValue($collection->toHtml('|'), $outputHtmlPipe, 'SocialMetaRenderOutput toHtml custom separator delegates to collection toHtml');

$outputIsEmpty = $renderOutput->isEmpty();
assertSameValue($collection->isEmpty(), $outputIsEmpty, 'SocialMetaRenderOutput isEmpty delegates to collection isEmpty');

$emptyCollection = new SocialMetaCollection();
$emptyRenderOutput = new SocialMetaRenderOutput($emptyCollection);
assertTrueValue($emptyRenderOutput->isEmpty(), 'SocialMetaRenderOutput isEmpty empty collection');

if ($failures > 0) {
    echo "\nPhase 14A Social Meta Foundation tests failed with $failures failures.\n";
    exit(1);
}

echo "Phase 14A Social Meta Foundation tests passed.\n";
