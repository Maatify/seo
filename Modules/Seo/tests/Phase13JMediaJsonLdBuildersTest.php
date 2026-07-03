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

use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuilderInterface;
use Maatify\Seo\Web\JsonLd\Builder\VideoObjectJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ImageObjectJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\AudioObjectJsonLdBuilder;

function assertSameValue13J(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13J(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

// 1. VideoObjectJsonLdBuilder Tests
$videoBuilder = new VideoObjectJsonLdBuilder();
assertTrueValue13J('Video builder implements builder interface', $videoBuilder instanceof JsonLdBuilderInterface);
assertSameValue13J('Video builder seeds schema.org defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'VideoObject',
], $videoBuilder->toArray());

assertSameValue13J('Video builder fluent methods', $videoBuilder, $videoBuilder->setName('Demo Video'));

$videoSchema = $videoBuilder
    ->setDescription('A demo video for JSON-LD')
    ->setThumbnailUrl(['https://example.com/thumb1.jpg', 'https://example.com/thumb2.jpg'])
    ->setUploadDate('2023-10-01T08:00:00+00:00')
    ->setDuration('PT1M33S')
    ->setContentUrl('https://example.com/video.mp4')
    ->setEmbedUrl('https://example.com/embed/video')
    ->setPublisher('Demo Org')
    ->setTranscript('This is a transcript.')
    ->setRegionsAllowed(['US', 'CA'])
    ->addRegionAllowed('UK')
    ->toArray();

assertSameValue13J('Video builder full schema', [
    '@context' => 'https://schema.org',
    '@type' => 'VideoObject',
    'name' => 'Demo Video',
    'description' => 'A demo video for JSON-LD',
    'thumbnailUrl' => ['https://example.com/thumb1.jpg', 'https://example.com/thumb2.jpg'],
    'uploadDate' => '2023-10-01T08:00:00+00:00',
    'duration' => 'PT1M33S',
    'contentUrl' => 'https://example.com/video.mp4',
    'embedUrl' => 'https://example.com/embed/video',
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Demo Org'
    ],
    'transcript' => 'This is a transcript.',
    'regionsAllowed' => ['US', 'CA', 'UK'],
], $videoSchema);

$videoBuilder2 = new VideoObjectJsonLdBuilder();
$videoBuilder2->setPublisher([
    '@type' => 'Organization',
    'name' => 'Advanced Org',
    'url' => 'https://example.com',
]);
assertSameValue13J('Video builder advanced publisher', [
    '@type' => 'Organization',
    'name' => 'Advanced Org',
    'url' => 'https://example.com',
], $videoBuilder2->get('publisher'));


// 2. ImageObjectJsonLdBuilder Tests
$imageBuilder = new ImageObjectJsonLdBuilder();
assertTrueValue13J('Image builder implements builder interface', $imageBuilder instanceof JsonLdBuilderInterface);
assertSameValue13J('Image builder seeds schema.org defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'ImageObject',
], $imageBuilder->toArray());

$imageSchema = $imageBuilder
    ->setContentUrl('https://example.com/image.jpg')
    ->setUrl('https://example.com/image-page')
    ->setName('Demo Image')
    ->setDescription('A beautiful view')
    ->setCaption('Sunset over mountains')
    ->setThumbnailUrl('https://example.com/image-thumb.jpg')
    ->setWidth('1920')
    ->setHeight(1080)
    ->setUploadDate('2023-10-02')
    ->setCreator('John Doe')
    ->setCopyrightHolder('Acme Corp')
    ->setLicense('https://creativecommons.org/licenses/by/4.0/')
    ->toArray();

assertSameValue13J('Image builder full schema', [
    '@context' => 'https://schema.org',
    '@type' => 'ImageObject',
    'contentUrl' => 'https://example.com/image.jpg',
    'url' => 'https://example.com/image-page',
    'name' => 'Demo Image',
    'description' => 'A beautiful view',
    'caption' => 'Sunset over mountains',
    'thumbnailUrl' => 'https://example.com/image-thumb.jpg',
    'width' => '1920',
    'height' => 1080,
    'uploadDate' => '2023-10-02',
    'creator' => [
        '@type' => 'Person',
        'name' => 'John Doe'
    ],
    'copyrightHolder' => [
        '@type' => 'Organization',
        'name' => 'Acme Corp'
    ],
    'license' => 'https://creativecommons.org/licenses/by/4.0/',
], $imageSchema);

$imageBuilder2 = new ImageObjectJsonLdBuilder();
$imageBuilder2->setCreator(['@type' => 'Person', 'name' => 'Jane Doe']);
$imageBuilder2->setCopyrightHolder(['name' => 'Jane Corp']);

assertSameValue13J('Image builder complex creator', ['@type' => 'Person', 'name' => 'Jane Doe'], $imageBuilder2->get('creator'));
assertSameValue13J('Image builder complex copyrightHolder', ['name' => 'Jane Corp', '@type' => 'Organization'], $imageBuilder2->get('copyrightHolder'));

// 3. AudioObjectJsonLdBuilder Tests
$audioBuilder = new AudioObjectJsonLdBuilder();
assertTrueValue13J('Audio builder implements builder interface', $audioBuilder instanceof JsonLdBuilderInterface);
assertSameValue13J('Audio builder seeds schema.org defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'AudioObject',
], $audioBuilder->toArray());

$audioSchema = $audioBuilder
    ->setName('Demo Audio')
    ->setDescription('A great podcast episode')
    ->setContentUrl('https://example.com/audio.mp3')
    ->setEmbedUrl('https://example.com/embed/audio')
    ->setUploadDate('2023-10-03')
    ->setDuration('PT45M')
    ->setTranscript('This is an audio transcript.')
    ->setEncodingFormat('audio/mpeg')
    ->setCreator('Podcast Host')
    ->setPublisher('Podcast Network')
    ->toArray();

assertSameValue13J('Audio builder full schema', [
    '@context' => 'https://schema.org',
    '@type' => 'AudioObject',
    'name' => 'Demo Audio',
    'description' => 'A great podcast episode',
    'contentUrl' => 'https://example.com/audio.mp3',
    'embedUrl' => 'https://example.com/embed/audio',
    'uploadDate' => '2023-10-03',
    'duration' => 'PT45M',
    'transcript' => 'This is an audio transcript.',
    'encodingFormat' => 'audio/mpeg',
    'creator' => [
        '@type' => 'Person',
        'name' => 'Podcast Host'
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Podcast Network'
    ],
], $audioSchema);

echo "Phase 13J Media JSON-LD builders tests passed.\n";
