# Phase 13J: Media JSON-LD Builders

This document covers the batch of media-focused JSON-LD builders implemented in Phase 13J. These builders allow generating standardized schema.org entities for video, image, and audio rich snippets.

The Phase 13J batch includes:
- `VideoObjectJsonLdBuilder`
- `ImageObjectJsonLdBuilder`
- `AudioObjectJsonLdBuilder`

All builders are strictly framework-neutral, independent of any HTTP or template engine layer, and are designed to return arrays or JSON strings for rendering.

## 1. VideoObjectJsonLdBuilder

Builds `VideoObject` schema.org markup with thumbnail, duration, publisher, and other relevant video properties.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\VideoObjectJsonLdBuilder;

$videoBuilder = new VideoObjectJsonLdBuilder();

$schema = $videoBuilder
    ->setName('Demo Video')
    ->setDescription('A demo video for JSON-LD')
    ->setThumbnailUrl(['https://example.com/thumb1.jpg', 'https://example.com/thumb2.jpg'])
    ->setUploadDate('2023-10-01T08:00:00+00:00')
    ->setDuration('PT1M33S')
    ->setContentUrl('https://example.com/video.mp4')
    ->setEmbedUrl('https://example.com/embed/video')
    ->setPublisher('Demo Org') // auto-normalized to Organization
    ->setTranscript('This is a transcript.')
    ->setRegionsAllowed(['US', 'CA'])
    ->addRegionAllowed('UK')
    ->toArray();

echo json_encode($schema, JSON_UNESCAPED_SLASHES);
```

### Methods
- `setName(string $name): static`
- `setDescription(string $description): static`
- `setThumbnailUrl(string|array $thumbnailUrl): static`
- `setUploadDate(string $uploadDate): static`
- `setDuration(string $duration): static`
- `setContentUrl(string $contentUrl): static`
- `setEmbedUrl(string $embedUrl): static`
- `setPublisher(string|array $publisher): static`
- `setTranscript(string $transcript): static`
- `setRegionsAllowed(array $regionsAllowed): static`
- `addRegionAllowed(string $regionCode): static`

## 2. ImageObjectJsonLdBuilder

Builds `ImageObject` schema.org markup with URL, dimensions, caption, creator, and copyright information.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\ImageObjectJsonLdBuilder;

$imageBuilder = new ImageObjectJsonLdBuilder();

$schema = $imageBuilder
    ->setContentUrl('https://example.com/image.jpg')
    ->setUrl('https://example.com/image-page')
    ->setName('Demo Image')
    ->setDescription('A beautiful view')
    ->setCaption('Sunset over mountains')
    ->setThumbnailUrl('https://example.com/image-thumb.jpg')
    ->setWidth('1920')
    ->setHeight(1080)
    ->setUploadDate('2023-10-02')
    ->setCreator('John Doe') // auto-normalized to Person
    ->setCopyrightHolder('Acme Corp') // auto-normalized to Organization
    ->setLicense('https://creativecommons.org/licenses/by/4.0/')
    ->toArray();
```

### Methods
- `setContentUrl(string $contentUrl): static`
- `setUrl(string $url): static`
- `setName(string $name): static`
- `setDescription(string $description): static`
- `setCaption(string $caption): static`
- `setThumbnailUrl(string $thumbnailUrl): static`
- `setWidth(int|string $width): static`
- `setHeight(int|string $height): static`
- `setUploadDate(string $uploadDate): static`
- `setCreator(string|array $creator): static`
- `setCopyrightHolder(string|array $copyrightHolder): static`
- `setLicense(string $license): static`


## 3. AudioObjectJsonLdBuilder

Builds `AudioObject` schema.org markup with duration, encoding format, and transcript.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\AudioObjectJsonLdBuilder;

$audioBuilder = new AudioObjectJsonLdBuilder();

$schema = $audioBuilder
    ->setName('Demo Audio')
    ->setDescription('A great podcast episode')
    ->setContentUrl('https://example.com/audio.mp3')
    ->setEmbedUrl('https://example.com/embed/audio')
    ->setUploadDate('2023-10-03')
    ->setDuration('PT45M')
    ->setTranscript('This is an audio transcript.')
    ->setEncodingFormat('audio/mpeg')
    ->setCreator('Podcast Host') // auto-normalized to Person
    ->setPublisher('Podcast Network') // auto-normalized to Organization
    ->toArray();
```

### Methods
- `setName(string $name): static`
- `setDescription(string $description): static`
- `setContentUrl(string $contentUrl): static`
- `setEmbedUrl(string $embedUrl): static`
- `setUploadDate(string $uploadDate): static`
- `setDuration(string $duration): static`
- `setTranscript(string $transcript): static`
- `setEncodingFormat(string $encodingFormat): static`
- `setCreator(string|array $creator): static`
- `setPublisher(string|array $publisher): static`
