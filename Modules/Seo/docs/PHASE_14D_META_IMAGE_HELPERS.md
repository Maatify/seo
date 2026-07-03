# Phase 14D: Meta Image Helpers

This phase introduces `SocialImageFactory`, a standalone factory that provides helper methods for creating `SocialImage` instances consistently across standard, Open Graph, and Twitter/X Card usages.

## Overview

The `Maatify\Seo\Web\Social\SocialImageFactory` creates fully configured `SocialImage` Data Transfer Objects (DTOs) tailored for common social media meta tag requirements.

**Architectural Guarantees:**
* **String-Only Output:** The factory only instantiates `SocialImage` DTOs. It does not generate meta tags, nor does it perform HTML rendering.
* **No File Operations:** The factory does not perform filesystem probing, image format detection, or GD/Imagick manipulation. Dimensions and types must be explicitly provided where required.
* **No HTTP Operations:** The factory does not fetch remote URLs or validate external resources.
* **No Validation Beyond Typing:** The factory relies on PHP's strict typing to ensure input correctness but does not perform domain-specific validation (e.g., verifying if a URL actually returns a valid image).
* **No Builder Mutation:** The factory does not mutate `OpenGraphBuilder` or `TwitterCardBuilder`.
* **Zero Dependencies:** The factory operates entirely free of framework, dependency injection container, static global state, or HTTP framework coupling.

## API Usage

### Standard Image Creation

```php
use Maatify\Seo\Web\Social\SocialImageFactory;

// Simple URL-only image
$image = SocialImageFactory::fromUrl('https://example.com/image.jpg');

// URL with an alternative text description
$image = SocialImageFactory::fromUrlWithAlt('https://example.com/image.jpg', 'Descriptive text');
```

### Social Platform Helpers

Helper methods designed for Open Graph and Twitter/X Card. These methods explicitly treat `alt` text as optional.

```php
// Open Graph standard image
$ogImage = SocialImageFactory::openGraph('https://example.com/og-image.jpg', 'Optional Alt Text');

// Twitter Large Image
$twitterImage = SocialImageFactory::twitterLargeImage('https://example.com/twitter-image.jpg', 'Optional Alt Text');
```

### Format-Specific Helpers

These methods automatically populate the `type` property (e.g., `image/jpeg`) and require explicit width and height dimensions.

```php
// JPEG image
$jpeg = SocialImageFactory::jpeg('https://example.com/img.jpg', 1200, 630, 'Optional Alt Text');

// PNG image
$png = SocialImageFactory::png('https://example.com/img.png', 1200, 630, 'Optional Alt Text');

// WebP image
$webp = SocialImageFactory::webp('https://example.com/img.webp', 1200, 630, 'Optional Alt Text');
```

### Property Modification Helpers

```php
// Dimensions without type
$image = SocialImageFactory::withDimensions('https://example.com/img.jpg', 800, 600, 'Optional Alt Text');

// With secure URL explicitly defined
$secureImage = SocialImageFactory::withSecureUrl('http://example.com/img.jpg', 'https://example.com/img.jpg', 'Optional Alt Text');
```
