# Maatify SEO Module Usage Guide

## 1. Overview

The Maatify SEO library provides robust, framework-agnostic tools to manage SEO metadata, schema generation (JSON-LD), redirects, slug history, and sitemaps.

**What it provides:**
*   Value Objects/DTOs for SEO data structures (e.g., `MetaTagsDTO`, `JsonLdSchemaDTO`).
*   Core logic for schema generation, resolving redirects, and generating in-memory sitemaps.
*   Optional rendering helpers to convert DTOs into plain HTML/XML strings.
*   Admin services and DTOs for overriding SEO metadata, managing redirects, and tracking slug changes.

**What it does NOT provide:**
*   It does **not** handle HTTP requests or responses.
*   It does **not** provide framework routing, controllers, or middlewares.
*   It does **not** couple to any specific templating engine (like Twig or Blade).
*   It does **not** enforce ORM patterns. Database persistence uses direct PDO via provided repositories.

**Host Application Responsibility:**
The host application is strictly responsible for managing all HTTP interactions (requests, responses, controllers, routes), utilizing preferred template engines, and providing implementations for the necessary host contracts (like `HostEntityProviderInterface` and `HostUrlGeneratorInterface`). You integrate the SEO library by using its builders and renderers within your existing architecture to get the SEO output, and then you send that output in your own responses.

---

## 2. Basic SEO Head Rendering Example

You can generate SEO head tags by creating a `MetaTagsDTO` and rendering it with the `SeoHeadHtmlRenderer`. This helper outputs standard HTML strings.

```php
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;

// 1. Prepare your data
$metaTags = new MetaTagsDTO(
    title: 'My Awesome Product',
    description: 'The best product you will ever buy.',
    canonicalUrl: 'https://example.com/products/awesome-product',
    robots: 'index,follow',
    openGraphTitle: 'My Awesome Product',
    openGraphDescription: 'The best product you will ever buy.',
    openGraphUrl: 'https://example.com/products/awesome-product',
    openGraphType: 'product',
    openGraphImage: 'https://cdn.example.com/images/awesome-product.jpg',
    twitterCard: 'summary_large_image',
    twitterTitle: 'My Awesome Product',
    twitterDescription: 'The best product you will ever buy.',
    twitterImage: 'https://cdn.example.com/images/awesome-product-twitter.jpg'
);

$schemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => 'My Awesome Product',
    ]
];

// 2. Render to a full HTML string
$renderer = new SeoHeadHtmlRenderer();
$headHtml = $renderer->render($metaTags, $schemas);

// 3. Inject $headHtml into your template (e.g., in `<head>`)
echo $headHtml;
```

---

## 3. Rendered Output DTO Example

If you prefer to inject individual sections of the SEO markup into different parts of your template layout, you can use the `renderDto()` method to obtain a `SeoHeadHtmlDTO`.

```php
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;

$renderer = new SeoHeadHtmlRenderer();

// Assuming $metaTags and $schemas are already created:
$dto = $renderer->renderDto($metaTags, $schemas);

// The DTO provides access to specific sections:
echo $dto->metaHtml;         // Outputs <title>, <meta name="description">, <link rel="canonical">, <meta name="robots">
echo $dto->openGraphHtml;    // Outputs <meta property="og:..."> tags
echo $dto->twitterCardHtml;  // Outputs <meta name="twitter:..."> tags
echo $dto->jsonLdHtml;       // Outputs <script type="application/ld+json">...</script>
echo $dto->fullHtml;         // Output the concatenated complete head HTML
```

This is particularly useful when integrating with template engines where blocks or sections are used for specific meta components.

---

## 4. FluentSeoBuilder Example

The `FluentSeoBuilder` provides a convenient, chainable API for constructing your SEO data without needing to instantiate DTOs manually upfront.

```php
use Maatify\Seo\Web\Builder\FluentSeoBuilder;

$builder = (new FluentSeoBuilder())
    ->title('About Us')
    ->description('Learn more about our company.')
    ->canonical('https://example.com/about')
    ->robots('index,follow')
    ->openGraphTitle('About Us - Example Co.')
    ->openGraphDescription('Discover the history of our company.')
    ->openGraphUrl('https://example.com/about')
    ->openGraphType('website')
    ->openGraphImage('https://example.com/og-about.jpg')
    ->twitterCard('summary_large_image')
    ->twitterTitle('About Us')
    ->twitterDescription('Discover the history of our company.')
    ->schema([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Example Co.',
        'url' => 'https://example.com',
    ]);

// Render a complete HTML string
$fullHtml = $builder->render();

// Or get a SeoHeadHtmlDTO
$dto = $builder->renderDto();
```

---

## 5. JSON-LD Examples

The library can generate structured data script tags using either raw associative arrays or the strictly typed `JsonLdSchemaDTO`.

### Using a raw associative array:

```php
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;

$renderer = new JsonLdScriptRenderer();

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => 'Understanding JSON-LD',
];

echo $renderer->render($schema);
```

### Using `JsonLdSchemaDTO`:

```php
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;

$renderer = new JsonLdScriptRenderer();

$schemaDto = new JsonLdSchemaDTO([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Homepage',
]);

echo $renderer->render($schemaDto);
```

---

## 6. Optional Spatie Schema Adapter Example

If your project utilizes the popular `spatie/schema-org` package, the SEO library provides an optional adapter (`SpatieSchemaAdapter`) to convert Spatie schema objects into native `JsonLdSchemaDTO` objects.

**Note:** The `spatie/schema-org` dependency is strictly optional and not required by the Maatify SEO module. It is provided via `composer suggest`.

```php
use Maatify\Seo\Web\Builder\FluentSeoBuilder;
use Maatify\Seo\Web\Schema\SpatieSchemaAdapter;
use Spatie\SchemaOrg\Schema; // Only if you have installed spatie/schema-org in your host app

// Assuming you have a Spatie schema object
// (We use a fake local object structure here for demonstration)
$localSchemaObject = new class {
    public function toArray(): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => 'Adapted Product'
        ];
    }
};

$adapter = new SpatieSchemaAdapter();

// Use the adapter with the fluent builder:
$builder = (new FluentSeoBuilder())
    ->title('Product View')
    ->spatieSchema($localSchemaObject, $adapter);

echo $builder->render();
```

---

## 7. Sitemap XML String Example

To easily render sitemap entries to XML strings without modifying core services, the library provides the `SitemapXmlStringRenderer`. It supports rendering basic URLs, alternate hreflang tags for multi-language indexing, Google image sitemap definitions, and Google video sitemap definitions.

```php
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

$renderer = new SitemapXmlStringRenderer();

// Example with SitemapUrlDTO, Alternate URLs (Hreflang), Images, and Videos
$urlDto = new SitemapUrlDTO(
    loc: 'https://example.com/en/page-1',
    lastmod: '2023-10-01',
    changefreq: 'monthly',
    priority: 0.5,
    alternates: [
        new SitemapAlternateUrlDTO('en', 'https://example.com/en/page-1'),
        new SitemapAlternateUrlDTO('es', 'https://example.com/es/page-1'),
    ],
    images: [
        new SitemapImageDTO(
            loc: 'https://example.com/image.jpg',
            title: 'Sample Image',
            caption: 'A view of the ocean',
            geoLocation: 'Limerick, Ireland',
            license: 'https://example.com/license'
        )
    ],
    videos: [
        new SitemapVideoDTO(
            thumbnailLoc: 'https://example.com/thumbnail.jpg',
            title: 'Sample Video',
            description: 'A sample video description',
            contentLoc: 'https://example.com/video.mp4',
            playerLoc: 'https://example.com/player',
            duration: 600,
            publicationDate: '2023-10-01T12:00:00+00:00'
        )
    ]
);
echo $renderer->renderUrlEntry($urlDto);
// Output includes local xmlns:xhtml, xmlns:image, and xmlns:video:
// <url xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
//   <loc>https://example.com/en/page-1</loc>
//   <lastmod>2023-10-01</lastmod>
//   <changefreq>monthly</changefreq>
//   <priority>0.5</priority>
//   <xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/page-1"/>
//   <xhtml:link rel="alternate" hreflang="es" href="https://example.com/es/page-1"/>
//   <image:image>
//     <image:loc>https://example.com/image.jpg</image:loc>
//     <image:title>Sample Image</image:title>
//     <image:caption>A view of the ocean</image:caption>
//     <image:geo_location>Limerick, Ireland</image:geo_location>
//     <image:license>https://example.com/license</image:license>
//   </image:image>
//   <video:video>
//     <video:thumbnail_loc>https://example.com/thumbnail.jpg</video:thumbnail_loc>
//     <video:title>Sample Video</video:title>
//     <video:description>A sample video description</video:description>
//     <video:content_loc>https://example.com/video.mp4</video:content_loc>
//     <video:player_loc>https://example.com/player</video:player_loc>
//     <video:duration>600</video:duration>
//     <video:publication_date>2023-10-01T12:00:00+00:00</video:publication_date>
//   </video:video>
// </url>

// Example with associative array
$arrayEntry = [
    'loc' => 'https://example.com/page-2',
    'lastmod' => '2023-10-02',
    'changefreq' => 'weekly',
    'priority' => '0.8',
    'alternates' => [
        ['hreflang' => 'x-default', 'url' => 'https://example.com/page-2'],
        ['hreflang' => 'de', 'url' => 'https://example.com/de/page-2'],
    ],
    'images' => [
        ['loc' => 'https://example.com/image2.jpg', 'title' => 'Image 2']
    ],
    'videos' => [
        [
            'thumbnailLoc' => 'https://example.com/thumbnail2.jpg',
            'title' => 'Video 2',
            'description' => 'Description 2',
            'contentLoc' => 'https://example.com/video2.mp4'
        ]
    ]
];
echo $renderer->renderUrlEntry($arrayEntry);

// Rendering an entire URL Set (passing multiple URLs)
$xmlOutput = $renderer->renderUrlSet([$urlDto, $arrayEntry]);
// <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">...urls...</urlset>
```

> **Note:** The `xmlns:xhtml="http://www.w3.org/1999/xhtml"` namespace is dynamically added to the root `<urlset>` (or `<url>` if rendering a single entry) only when `alternates` are present. Similarly, `xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"` is added only when `images` exist, and `xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"` is added only when `videos` exist. They are included together when alternate URLs, images, and videos exist together. If none are supplied, the sitemap output remains clean and unchanged. Existing URL-set, hreflang, and image sitemap output without videos remains exactly as it was.

---

## 8. Sitemap Index XML String Example

To render a sitemap index directly to an XML string, use the `SitemapIndexXmlStringRenderer`.

```php
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO;
use Maatify\Seo\Web\Sitemap\SitemapIndexXmlStringRenderer;

$renderer = new SitemapIndexXmlStringRenderer();

// Example with SitemapIndexEntryDTO
$dto = new SitemapIndexEntryDTO('https://example.com/sitemap-products.xml', '2023-10-01');
echo $renderer->renderEntry($dto);

// Example with associative array
$arrayEntry = [
    'loc' => 'https://example.com/sitemap-articles.xml',
    'lastmod' => '2023-10-02',
];
echo $renderer->renderEntry($arrayEntry);

// Render the full index
echo $renderer->renderIndex([$dto, $arrayEntry]);
```

---

## 9. Robots.txt String Output Example

To quickly render a `robots.txt` string dynamically, you can use the `RobotsTxtRenderer`.

```php
use Maatify\Seo\Web\Robots\RobotsTxtRenderer;
use Maatify\Seo\Web\Robots\DTO\RobotsTxtDTO;
use Maatify\Seo\Web\Robots\DTO\RobotsRuleDTO;

$renderer = new RobotsTxtRenderer();

$txt = new RobotsTxtDTO(
    rules: [
        new RobotsRuleDTO(
            userAgent: '*',
            allow: ['/'],
            disallow: ['/admin/', '/private/'],
            crawlDelay: 10,
            comments: ['Global rule for all bots']
        ),
        new RobotsRuleDTO(
            userAgent: 'BadBot',
            disallow: ['/']
        )
    ],
    sitemaps: [
        'https://example.com/sitemap.xml',
        'https://example.com/sitemap-images.xml'
    ],
    comments: [
        'Welcome to my robots.txt',
        'Created dynamically'
    ]
);

// Returns a correctly formatted robots.txt plain string.
// You must output this string and set the Content-Type: text/plain
// header in your host application controller.
echo $renderer->render($txt);
```

---

## 10. Existing SitemapGeneratorService Example

The core `SitemapGeneratorService` remains available. It is responsible for orchestrating sitemap generation logic and returning structured DTOs (`SitemapGenerationResultDTO`), which represents a structural abstraction over the XML data.

The core service outputs objects intended for further processing or structured output handling, while the `SitemapXmlStringRenderer` (demonstrated above) is specifically a presentation-layer helper designed to quickly output standard XML strings for web consumption.

```php
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;

$generator = new SitemapGeneratorService();

$urls = [
    new SitemapUrlDTO('https://example.com/item-1'),
    new SitemapUrlDTO('https://example.com/item-2'),
];

// Returns a SitemapGenerationResultDTO containing structured XML content.
$result = $generator->generateUrlSitemap($urls);

// You must take the result output and stream it or respond with it in your host application controller.
// The service itself does not emit an HTTP response.
$xmlContent = $result->xml;
```

---

## 11. Recommended Host Application Usage

The Maatify SEO module is designed to integrate cleanly into any PHP framework without introducing hard dependencies on the framework itself.

### Plain PHP
Use the provided builders or renderers directly within your PHP views.

```php
<?php
// my-page.php
require 'vendor/autoload.php';
$builder = (new \Maatify\Seo\Web\Builder\FluentSeoBuilder())->title('Plain PHP');
?>
<!DOCTYPE html>
<html>
<head>
    <?= $builder->render() ?>
</head>
<body>
    <h1>Hello World</h1>
</body>
</html>
```

### Slim Framework
Instantiate a builder within your route handler and pass the rendered string to your template.

```php
$app->get('/products/{id}', function ($request, $response, $args) {
    // 1. Fetch product data
    // 2. Build SEO output
    $seoHtml = (new \Maatify\Seo\Web\Builder\FluentSeoBuilder())
        ->title('Slim Product')
        ->render();

    // 3. Render template
    // This assumes a generic template rendering engine integration
    return $this->get('view')->render($response, 'product.twig', [
        'seoHtml' => $seoHtml
    ]);
});
```

### Laravel
Construct the SEO logic in your controllers or dedicated view composers and pass the resulting string to Blade.

```php
class ProductController extends Controller {
    public function show($id) {
        $builder = (new \Maatify\Seo\Web\Builder\FluentSeoBuilder())
            ->title('Laravel Product');

        return view('product', ['seoHtml' => $builder->render()]);
    }
}
```

In your Blade layout (`product.blade.php`):
```blade
<head>
    {!! $seoHtml !!}
</head>
```

### Template Rendering Tips
Always pass the pre-rendered HTML string (or the `SeoHeadHtmlDTO`) to your templating engine (like Twig, Blade, or Smarty) for output, avoiding calling the builder methods directly within the template files whenever possible. Keep the construction logic in the controller.

---

## 12. Common Mistakes

When implementing the Maatify SEO module, ensure you adhere strictly to the following guidelines:

*   **Do not output HTTP responses directly from the library.** All SEO services and helpers return strings or DTOs. The host application must format the final HTTP response (e.g., managing the `Content-Type: application/xml` header for sitemaps).
*   **Do not embed routing or controller logic.** Route mapping belongs exclusively within the host app.
*   **Do not hardcode framework dependencies.** Do not attempt to use `Illuminate\Support` or `Symfony\Component\HttpFoundation` inside the core module. Ensure integration points use standard PHP functionality or provided contracts.
*   **Do not commit `composer.lock`.** As a library module, `composer.lock` should not be tracked to allow proper dependency resolution in host environments.
*   **Do not rely on the `spatie/schema-org` package unless installed.** The module does not enforce this package as a required dependency. The provided adapter checks for class existence before relying on the object methods, allowing the host application to opt-in independently.
