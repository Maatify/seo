# Phase 14C: Twitter/X Card Builder

## Overview

The `TwitterCardBuilder` class provides a framework-neutral implementation for generating Twitter/X Card meta tags. It extends the Phase 14A Social Meta Foundation using `SocialMetaTag`, `SocialImage`, `SocialMetaCollection`, and `SocialMetaRenderOutput`.

This builder strictly maps inputs to generic arrays, Data Transfer Objects (DTOs), or plain string HTML. It avoids external testing framework dependencies, static global state, HTTP responses, or framework coupling.

## API Matching Note

This document strictly matches the existing public API of `Maatify\Seo\Web\Social\TwitterCardBuilder`.
* It does **not** claim multiple image support (it natively supports only a single `twitter:image`).
* It does **not** perform format, URL, or handle validation beyond basic PHP static typing.
* It documents Twitter/X features exclusively; it adds no Open Graph capabilities.

## Output Structure

The builder constructs a collection of `twitter:*` tags using `attribute="name"`.

```php
use Maatify\Seo\Web\Social\TwitterCardBuilder;

$builder = new TwitterCardBuilder();
$builder->setCard('summary_large_image')
        ->setSite('@site_handle')
        ->setCreator('@creator_handle')
        ->setTitle('Page Title')
        ->setDescription('Page Description');

// Extract tags as plain HTML (escapes special characters)
$html = $builder->toHtml();
/*
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@site_handle">
<meta name="twitter:creator" content="@creator_handle">
<meta name="twitter:title" content="Page Title">
<meta name="twitter:description" content="Page Description">
*/

// Extract as raw array for custom rendering (e.g. JSON integrations)
$array = $builder->toArray();
/*
[
    ['name' => 'twitter:card', 'content' => 'summary_large_image', 'attribute' => 'name'],
    // ...
]
*/

// Or wrap in DTO for further processing
$output = $builder->toRenderOutput();
```

## Image Handling

The builder supports a single image representation using either a string URL or a `SocialImage` instance.

* The `twitter:image:alt` tag is automatically generated if `SocialImage` provides an alt text and it wasn't manually overridden.
* Use `setImageAlt()` to explicitly override the image alt description (even before setting an image).
* Only one image is supported at a time. Setting a new image overrides the previously defined image entirely.

```php
use Maatify\Seo\Web\Social\TwitterCardBuilder;
use Maatify\Seo\Web\Social\SocialImage;

$builder = new TwitterCardBuilder();

// Using string mapping
$builder->setImage('https://example.com/image.jpg');

// Using SocialImage mapped with alt text
$image = new SocialImage('https://example.com/social-cover.jpg');
$image->setAlt('Cover image text for accessibility');
$builder->setImage($image);

// Explicitly overriding alt text (highest precedence)
$builder->setImageAlt('Override text for cover image');
```

## Player Cards

Video player integration relies on explicit string and integer typing for defining dimensions and the direct player URL.

```php
$builder = new TwitterCardBuilder();
$builder->setCard('player')
        ->setPlayer('https://example.com/video-player')
        ->setPlayerWidth(1280)
        ->setPlayerHeight(720);
```

## App Linking Cards

Explicit definitions for app linking across mobile platforms (iPhone, iPad, Google Play) are provided.

```php
$builder = new TwitterCardBuilder();
$builder->setCard('app')
        ->setAppNameIphone('App iPhone')
        ->setAppIdIphone('id_iphone')
        ->setAppUrlIphone('url_iphone')
        ->setAppNameIpad('App iPad')
        ->setAppIdIpad('id_ipad')
        ->setAppUrlIpad('url_ipad')
        ->setAppNameGoogleplay('App GooglePlay')
        ->setAppIdGoogleplay('id_googleplay')
        ->setAppUrlGoogleplay('url_googleplay');
```
