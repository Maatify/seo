# Phase 14B: Open Graph Builder

The `OpenGraphBuilder` component (`Maatify\Seo\Web\Social\OpenGraphBuilder`) provides a framework-neutral way to generate Open Graph (`og:`) meta tags for social media sharing. It is built on top of the Phase 14A Social Meta Foundation.

## Architectural Principles

- **Framework-Neutral:** Contains no dependencies on external frameworks.
- **DTOs & Strings Only:** Generates `SocialMetaCollection`, DTO arrays, and plain HTML strings.
- **No Global State:** Pure builder pattern.
- **Standards Compliant:** Uses `property` instead of `name` for Open Graph tags.
- **Data Integrity:** Explicit scalar setters and robust image handling.

## Usage

### Instantiation and Scalar Fields

The builder exposes explicit setters for standard Open Graph scalar properties:

```php
use Maatify\Seo\Web\Social\OpenGraphBuilder;

$builder = new OpenGraphBuilder();

$builder->setTitle('Example Title')
        ->setDescription('Example Description')
        ->setType('article')
        ->setUrl('https://example.com/article')
        ->setSiteName('Example Site')
        ->setLocale('en_US')
        ->setDeterminer('the')
        ->setAudio('https://example.com/audio.mp3')
        ->setVideo('https://example.com/video.mp4');
```

### Image Handling

The builder supports comprehensive image definitions, either as scalar strings or structured `SocialImage` objects.

- `setImage()` replaces all existing images with a single image.
- `addImage()` appends an image to the existing list.
- `setImages()` replaces all existing images with a new array of images.

> **Note:** Duplicate images are not explicitly deduplicated, and insertion order is preserved exactly as provided.

```php
use Maatify\Seo\Web\Social\SocialImage;

// Add a simple image URL
$builder->addImage('https://example.com/image1.jpg');

// Add a structured image with dimensions and alt text
$image = new SocialImage('https://example.com/image2.jpg');
$image->setSecureUrl('https://secure.example.com/image2.jpg')
      ->setType('image/jpeg')
      ->setWidth(1200)
      ->setHeight(630)
      ->setAlt('Example Alt Text');

$builder->addImage($image);

// Replace all images
$builder->setImages([
    'https://example.com/image3.jpg',
    $image
]);
```

### Rendering Outputs

You can export the built tags into various formats:

```php
// Get a SocialMetaCollection object
$collection = $builder->toCollection();

// Get an array format
$array = $builder->toArray();
/*
[
    ['name' => 'og:title', 'content' => 'Example Title', 'attribute' => 'property'],
    ['name' => 'og:image', 'content' => 'https://example.com/image3.jpg', 'attribute' => 'property'],
    // ...
]
*/

// Get plain HTML strings
$html = $builder->toHtml();
// <meta property="og:title" content="Example Title">
// <meta property="og:image" content="https://example.com/image3.jpg">
```

### HTML Escaping

All outputs correctly escape potentially harmful characters through the underlying `SocialMetaTag` mechanism to ensure safe rendering in the `<head>` of the host application.
