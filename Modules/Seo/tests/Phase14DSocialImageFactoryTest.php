<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Web/Social/SocialImage.php';
require_once __DIR__ . '/../src/Web/Social/SocialImageFactory.php';

use Maatify\Seo\Web\Social\SocialImage;
use Maatify\Seo\Web\Social\SocialImageFactory;

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

// 1. Test fromUrl()
$image = SocialImageFactory::fromUrl('https://example.com/img.jpg');
assertSameValue(true, $image instanceof SocialImage, 'fromUrl returns SocialImage');
assertSameValue('https://example.com/img.jpg', $image->getUrl(), 'fromUrl sets url');
assertSameValue(null, $image->getAlt(), 'fromUrl alt is null');

// 2. Test fromUrlWithAlt()
$image = SocialImageFactory::fromUrlWithAlt('https://example.com/img.jpg', 'Image Alt');
assertSameValue('https://example.com/img.jpg', $image->getUrl(), 'fromUrlWithAlt sets url');
assertSameValue('Image Alt', $image->getAlt(), 'fromUrlWithAlt sets alt');

// 3. Test openGraph()
$image = SocialImageFactory::openGraph('https://example.com/og.jpg');
assertSameValue('https://example.com/og.jpg', $image->getUrl(), 'openGraph sets url');
assertSameValue(null, $image->getAlt(), 'openGraph alt is optional');

$image = SocialImageFactory::openGraph('https://example.com/og.jpg', 'OG Alt');
assertSameValue('OG Alt', $image->getAlt(), 'openGraph sets alt');

// 4. Test twitterLargeImage()
$image = SocialImageFactory::twitterLargeImage('https://example.com/tw.jpg');
assertSameValue('https://example.com/tw.jpg', $image->getUrl(), 'twitterLargeImage sets url');
assertSameValue(null, $image->getAlt(), 'twitterLargeImage alt is optional');

$image = SocialImageFactory::twitterLargeImage('https://example.com/tw.jpg', 'TW Alt');
assertSameValue('TW Alt', $image->getAlt(), 'twitterLargeImage sets alt');

// 5. Test jpeg()
$image = SocialImageFactory::jpeg('https://example.com/img.jpg', 800, 600);
assertSameValue('https://example.com/img.jpg', $image->getUrl(), 'jpeg sets url');
assertSameValue('image/jpeg', $image->getType(), 'jpeg sets type');
assertSameValue(800, $image->getWidth(), 'jpeg sets width');
assertSameValue(600, $image->getHeight(), 'jpeg sets height');
assertSameValue(null, $image->getAlt(), 'jpeg alt is optional');

$image = SocialImageFactory::jpeg('https://example.com/img.jpg', 800, 600, 'JPG Alt');
assertSameValue('JPG Alt', $image->getAlt(), 'jpeg sets alt');

// 6. Test png()
$image = SocialImageFactory::png('https://example.com/img.png', 400, 300);
assertSameValue('https://example.com/img.png', $image->getUrl(), 'png sets url');
assertSameValue('image/png', $image->getType(), 'png sets type');
assertSameValue(400, $image->getWidth(), 'png sets width');
assertSameValue(300, $image->getHeight(), 'png sets height');
assertSameValue(null, $image->getAlt(), 'png alt is optional');

$image = SocialImageFactory::png('https://example.com/img.png', 400, 300, 'PNG Alt');
assertSameValue('PNG Alt', $image->getAlt(), 'png sets alt');

// 7. Test webp()
$image = SocialImageFactory::webp('https://example.com/img.webp', 1024, 768);
assertSameValue('https://example.com/img.webp', $image->getUrl(), 'webp sets url');
assertSameValue('image/webp', $image->getType(), 'webp sets type');
assertSameValue(1024, $image->getWidth(), 'webp sets width');
assertSameValue(768, $image->getHeight(), 'webp sets height');
assertSameValue(null, $image->getAlt(), 'webp alt is optional');

$image = SocialImageFactory::webp('https://example.com/img.webp', 1024, 768, 'WEBP Alt');
assertSameValue('WEBP Alt', $image->getAlt(), 'webp sets alt');

// 8. Test withDimensions()
$image = SocialImageFactory::withDimensions('https://example.com/img.jpg', 640, 480);
assertSameValue('https://example.com/img.jpg', $image->getUrl(), 'withDimensions sets url');
assertSameValue(640, $image->getWidth(), 'withDimensions sets width');
assertSameValue(480, $image->getHeight(), 'withDimensions sets height');
assertSameValue(null, $image->getAlt(), 'withDimensions alt is optional');

$image = SocialImageFactory::withDimensions('https://example.com/img.jpg', 640, 480, 'Dim Alt');
assertSameValue('Dim Alt', $image->getAlt(), 'withDimensions sets alt');

// 9. Test withSecureUrl()
$image = SocialImageFactory::withSecureUrl('http://example.com/img.jpg', 'https://example.com/img.jpg');
assertSameValue('http://example.com/img.jpg', $image->getUrl(), 'withSecureUrl sets url');
assertSameValue('https://example.com/img.jpg', $image->getSecureUrl(), 'withSecureUrl sets secure url');
assertSameValue(null, $image->getAlt(), 'withSecureUrl alt is optional');

$image = SocialImageFactory::withSecureUrl('http://example.com/img.jpg', 'https://example.com/img.jpg', 'Sec Alt');
assertSameValue('Sec Alt', $image->getAlt(), 'withSecureUrl sets alt');

if ($failures > 0) {
    echo "\nPhase 14D Social Image Factory tests failed with $failures failures.\n";
    exit(1);
}

echo "Phase 14D Social Image Factory tests passed.\n";
