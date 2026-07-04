# Phase 14E: Social Preview Builder Usage

The `SocialPreviewBuilder` provides a unified interface for defining both Open Graph and Twitter/X Card metadata simultaneously. It is designed to act as an orchestration layer over the independent `OpenGraphBuilder` and `TwitterCardBuilder`, ensuring consistency across platforms without requiring redundant configuration.

## Key Features

1. **Shared Metadata Setters**: Easily set common values (Title, Description, Image) that apply to both Open Graph and Twitter/X configurations in a single method call.
2. **Platform-Specific Access**: Advanced properties or platform-specific configurations can be managed either via platform-specific setters or by retrieving the underlying internal builders directly.
3. **Unified Rendering**: Generates a strictly ordered sequence of metadata tags where Open Graph properties appear before Twitter/X attributes, maintaining HTML `<head>` organization.
4. **No Implicit Deduplication**: The builder intentionally avoids implicit logic to deduplicate overlapping fields (e.g. `og:title` vs. `twitter:title`). It outputs exactly what is requested based on its internal builder composition.

## Basic Usage

The easiest way to use the `SocialPreviewBuilder` is to configure common shared tags:

```php
use Maatify\Seo\Web\Social\SocialPreviewBuilder;

$builder = new SocialPreviewBuilder();

$builder->setTitle('Example Title')
        ->setDescription('Example description of the page.')
        ->setImage('https://example.com/cover.jpg')
        ->setUrl('https://example.com/page-url')      // Only sets Open Graph
        ->setSiteName('Example Site')                // Only sets Open Graph
        ->setTwitterCard('summary_large_image')      // Only sets Twitter
        ->setTwitterSite('@examplesite');            // Only sets Twitter

// Outputs standard HTML tags
echo $builder->toHtml();
```

## Advanced Usage via Internal Builders

For advanced properties that are specific to Open Graph or Twitter/X and not exposed on the shared `SocialPreviewBuilder` interface, you can retrieve the internal builders to set them directly.

```php
$builder = new SocialPreviewBuilder();

// Set basic fields first
$builder->setTitle('Podcast Episode')
        ->setImage('https://example.com/podcast.jpg');

// Use OpenGraphBuilder directly for OG-specific advanced settings
$builder->openGraph()
        ->setType('music.song')
        ->setAudio('https://example.com/episode.mp3')
        ->setAudioType('audio/mpeg');

// Use TwitterCardBuilder directly for Twitter-specific advanced settings
$builder->twitter()
        ->setCard('player')
        ->setPlayer('https://example.com/player')
        ->setPlayerWidth('480')
        ->setPlayerHeight('480');

// Generate final HTML
echo $builder->toHtml();
```

## Shared vs. Platform-Specific Setters

- **Shared Setters**: `setTitle()`, `setDescription()`, `setImage()`. These update both the internal `OpenGraphBuilder` and `TwitterCardBuilder` instances.
- **Open Graph Specific**: `setUrl()`, `setSiteName()`, `setLocale()`. These only apply to the Open Graph configuration.
- **Twitter/X Specific**: `setTwitterCard()`, `setTwitterSite()`, `setTwitterCreator()`. These only apply to the Twitter/X configuration.

## Output Generation

The builder supports multiple output formats to fit any architecture, strictly remaining string or DTO-based.

```php
// Render as standard HTML strings (separated by newlines)
$htmlString = $builder->toHtml();

// Convert to an array of strictly typed tags
// list<array{name: string, content: string, attribute: string}>
$tagArray = $builder->toArray();

// Retrieve as the underlying SocialMetaCollection
$collection = $builder->toCollection();

// Retrieve as a Render Output DTO for view layers
$renderOutput = $builder->toRenderOutput();
```

## Architectural Boundaries

- No network requests, URL parsing, or image existence checks are performed.
- Output generation uses correct platform attributes automatically (`property` for Open Graph, `name` for Twitter/X).
- The tag output strictly preserves internal builder ordering. Open Graph tags are systematically ordered before Twitter/X tags.
- No global state, static calls, routing, or framework bindings (like Laravel or Slim) are involved.
