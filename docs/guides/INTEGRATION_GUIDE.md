# SEO Library Integration Guide

This guide explains how host applications should integrate the Maatify SEO library. The library is strictly framework-neutral and decoupled from any specific routing, presentation, or DI container.

---

## 1. Overview

The core philosophy of the SEO library is simple: **it computes data and returns it**.

- The library returns pure DTOs, PHP strings, and standard service results.
- **The host application owns** the HTTP responses, controllers, route definitions, template engines, DI containers, and database wiring.
- The SEO library has zero dependency on frameworks like Slim, Laravel, Symfony, Twig, or Blade.

---

## 2. Recommended Integration Architecture

To keep your application decoupled and testable, follow this data flow:

1.  **Request Handling:** The framework's route or controller receives the HTTP request.
2.  **Data Preparation:** The controller calls your host's internal services to fetch the necessary page/product data.
3.  **SEO Generation:** The controller passes this data into the SEO library (e.g., using `FluentSeoBuilder` or `SeoHeadHtmlRenderer`) to build the DTOs or rendered HTML output.
4.  **Template Rendering:** The controller passes the final SEO output (as a string or a DTO) to the template engine.
5.  **Response:** The controller sends the template output in an HTTP response.

*Best Practice:* Keep the construction of SEO objects in the controller/service layer, not inside the template files.

---

## 3. Plain PHP Integration

Integrating without a framework is straightforward using Composer autoloading.

```php
<?php
// my-page.php
require 'vendor/autoload.php';

use Maatify\Seo\Web\Builder\FluentSeoBuilder;

// 1. Build SEO output using the Fluent Builder
$seoBuilder = (new FluentSeoBuilder())
    ->title('My Awesome Page')
    ->description('Learn more about our plain PHP integration.')
    ->canonical('https://example.com/my-page')
    ->schema([
        '@context' => 'https://schema.org',
        '@type'    => 'WebPage',
        'name'     => 'My Awesome Page',
    ]);

// 2. Render to a plain string
$seoHtml = $seoBuilder->render();

// Note on Escaping: The SEO library renderers natively and safely escape
// generated tags (e.g., htmlspecialchars on title and description).
// However, the host application is still responsible for how it echoes the final string.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- 3. Output inside the plain PHP template -->
    <?= $seoHtml ?>
</head>
<body>
    <h1>Welcome to Plain PHP</h1>
</body>
</html>
```

---

## 4. Slim Integration

Slim Framework integration relies on building the SEO content inside the route callback and passing the resulting payload to your view layer.

```php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Maatify\Seo\Web\Builder\FluentSeoBuilder;

$app->get('/products/{id}', function (Request $request, Response $response, array $args) {

    // 1. Fetch product from your host application
    $product = $this->get('ProductService')->find($args['id']);

    // 2. Build SEO output
    $seoBuilder = (new FluentSeoBuilder())
        ->title($product->name . ' - Store')
        ->description($product->shortDescription);

    // You can pass the raw string ($seoHtml) or a structured DTO ($seoDto)
    $seoHtml = $seoBuilder->render();
    // $seoDto = $seoBuilder->renderDto();

    // 3. Render the template
    // IMPORTANT: The SEO library does not return PSR-7 responses. You must write it to the response.
    return $this->get('view')->render($response, 'product.twig', [
        'product' => $product,
        'seoHtml' => $seoHtml
    ]);
});
```

---

## 5. Laravel Integration

In Laravel, manage SEO inside Controllers, View Composers, or dedicated host-level services. **Do not add Laravel-specific packages or dependencies to the SEO library itself.**

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatify\Seo\Web\Builder\FluentSeoBuilder;
use App\Models\Product;

class ProductController extends Controller
{
    public function show(Request $request, $id)
    {
        // 1. Fetch the product
        $product = Product::findOrFail($id);

        // 2. Build SEO output
        $builder = (new FluentSeoBuilder())
            ->title($product->name)
            ->description($product->description)
            ->canonical(route('products.show', $product->id));

        $seoHtml = $builder->render();
        // Alternatively, use ->renderDto() if you want Blade to place sections individually

        // 3. Pass to Blade template
        return view('products.show', [
            'product' => $product,
            'seoHtml' => $seoHtml,
        ]);
    }
}
```

---

## 6. Template Integration

### Twig Example (Pre-rendered string)

If you passed `$seoHtml` to Twig, use the `raw` filter to output the unescaped HTML block (since the SEO library already handled attribute escaping safely).

```twig
{# product.twig #}
<!DOCTYPE html>
<html>
<head>
    {{ seoHtml|raw }}
</head>
<body>
    <h1>{{ product.name }}</h1>
</body>
</html>
```

### Blade Example (Pre-rendered string)

In Blade, use the `{!! !!}` syntax.

```blade
{{-- product.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    {!! $seoHtml !!}
</head>
<body>
    <h1>{{ $product->name }}</h1>
</body>
</html>
```

### DTO Section Rendering Example

If you used `$seoDto = $builder->renderDto();` and passed it to the template (e.g., as `$seo`), you can distribute the tags to different blocks or sections.

```blade
{{-- layout.blade.php --}}
<head>
    {{-- Core Meta (Title, Description, Canonical) --}}
    {!! $seo->metaHtml !!}

    {{-- Social Tags --}}
    {!! $seo->openGraphHtml !!}
    {!! $seo->twitterCardHtml !!}

    {{-- Structured Data --}}
    {!! $seo->jsonLdHtml !!}

    {{-- Or just use the pre-combined full string --}}
    {{-- {!! $seo->fullHtml !!} --}}
</head>
```

---

## 7. Sitemap Integration

Sitemaps require the host application to configure the route, set the correct HTTP headers, and echo the XML string. The library does not emit HTTP headers itself.

```php
// Example in a basic framework or plain PHP
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

// 1. Host app routing handles `/sitemap.xml`
$sitemapUrls = [
    new SitemapUrlDTO(
        loc: 'https://example.com/en',
        lastmod: '2023-10-01',
        changefreq: 'daily',
        priority: 1.0,
        alternates: [
            new SitemapAlternateUrlDTO('en', 'https://example.com/en'),
            new SitemapAlternateUrlDTO('es', 'https://example.com/es')
        ],
        images: [
            new SitemapImageDTO('https://example.com/image.jpg', 'Hero Image')
        ],
        videos: [
            new SitemapVideoDTO('https://example.com/thumbnail.jpg', 'Hero Video', 'A great video', 'https://example.com/video.mp4')
        ],
        news: [
            new SitemapNewsDTO('Example Daily', 'en', '2023-10-01', 'Breaking News')
        ]
    ),
    new SitemapUrlDTO('https://example.com/about', '2023-09-15', 'monthly', 0.8)
];

// 2. SEO library generates the XML string.
// If any alternates, images, videos, or news data are provided, the xmlns:xhtml, xmlns:image, xmlns:video, and xmlns:news namespaces are automatically added.
$renderer = new SitemapXmlStringRenderer();
$xmlString = $renderer->renderUrlSet($sitemapUrls);

// 3. Host app sets Content-Type and emits the response
header('Content-Type: application/xml; charset=utf-8');
echo $xmlString;
exit;
```

If you need to output a `sitemapindex`:

```php
use Maatify\Seo\Web\Sitemap\SitemapIndexXmlStringRenderer;
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO;

$indexEntries = [
    new SitemapIndexEntryDTO('https://example.com/sitemap-products.xml', '2023-10-01'),
    new SitemapIndexEntryDTO('https://example.com/sitemap-articles.xml', '2023-10-02')
];

$renderer = new SitemapIndexXmlStringRenderer();
$xmlString = $renderer->renderIndex($indexEntries);

header('Content-Type: application/xml; charset=utf-8');
echo $xmlString;
exit;
```

---

## 8. Robots.txt Integration

Similar to sitemaps, rendering `robots.txt` files requires the host application to configure the route and output the correct headers. The library's renderer strictly returns a string and does not handle HTTP requests or responses.

```php
use Maatify\Seo\Web\Robots\RobotsTxtRenderer;
use Maatify\Seo\Web\Robots\DTO\RobotsTxtDTO;
use Maatify\Seo\Web\Robots\DTO\RobotsRuleDTO;

// 1. Host app routing handles `/robots.txt`
$txt = new RobotsTxtDTO(
    rules: [
        new RobotsRuleDTO('*', ['/'], ['/admin'])
    ],
    sitemaps: ['https://example.com/sitemap.xml']
);

// 2. SEO library generates the plain text string
$renderer = new RobotsTxtRenderer();
$robotsString = $renderer->render($txt);

// 3. Host app sets Content-Type and emits the response
header('Content-Type: text/plain; charset=utf-8');
echo $robotsString;
exit;
```

---

## 9. SEO Validation Helpers Integration

The `SeoMetaValidator` can be utilized by the host application to run audits on pages without emitting output.

### Background Job or Test Integration
You can build a script to scrape your own site's metadata (or catch it during generation) and run the array through the validator:

```php
use Maatify\Seo\Web\Validation\SeoMetaValidator;
use Maatify\Seo\Web\Validation\SeoValidationPreset;

// Assume the host app generated the $meta array for a product page
$meta = [
    'title' => 'Awesome Product',
    'description' => 'A great product.',
    // Missing required fields for OG and Canonical, perhaps?
];

// Use a preset like standard() which requires canonical and has a 50 char min description length
$preset = SeoValidationPreset::standard();

$result = SeoMetaValidator::validate($meta, $preset['validationOptions']);

if ($result->hasWarnings) {
    // Log warnings to host monitoring tools
    // e.g. "Page /products/123 generated a description that is too short."
    HostLogger::warning('SEO warnings detected', ['issues' => $result->warnings]);
}

// Calculate an actionable score for the payload
$scoreDto = \Maatify\Seo\Web\Validation\SeoValidationScoreCalculator::score($result, $preset['scoreOptions']);
if (!$scoreDto->isHealthy) {
    HostLogger::error("Unhealthy SEO Score ({$scoreDto->score}/100) generated for product.", ['deductions' => $scoreDto->deductions]);
}
```

### Pre-Render Check Integration
Host frameworks (like Laravel or Slim middleware) can hook the payload to validate before rendering to a template. If warnings occur, you might flash a message to an admin interface or skip outputting broken SEO tags.

---

## 10. Dependency Injection Guidance

While the SEO library provides a `SeoBindings.php` file mapping interfaces to factories, it **does not require** a specific DI container like PHP-DI or Laravel's container.

- **Host App Registration:** Your host application can take the definitions in `SeoBindings.php` and register them into its own DI container.
- **Plain PHP Constructors:** Every class in the library can be instantiated via plain PHP `new` using standard constructor injection.
- Do not introduce framework-specific container interfaces (like `Illuminate\Contracts\Container\Container`) into the SEO library code.

---

## 11. Persistence Integration Guidance

For features requiring database storage (like manual SEO overrides or slug histories), the module expects a plain `PDO` instance.

- **Host Provides PDO:** The host app is responsible for establishing the database connection and providing the `PDO` object to the SEO repositories (either manually or via the host's DI container).
- **No `.env` reading:** The SEO library must not read `.env` files, config files, or environment variables directly.
- **No Framework Config:** Do not pass Laravel `Config::get()` or Symfony parameter bags into the library's domain layer.

---

## 12. Error Handling

- **Module Exceptions:** The SEO library throws specific module exceptions (e.g., `SeoNotFoundException`, `SeoConflictException`) when operations fail.
- **Host Responsibility:** The host application is responsible for catching these exceptions, logging them, and converting them into appropriate HTTP status codes (like 404 Not Found or 400 Bad Request).
- The library should never call `http_response_code()` or throw HTTP-specific framework exceptions (like `Symfony\Component\HttpKernel\Exception\NotFoundHttpException`).

---

## 13. Common Integration Mistakes

To maintain module integrity, ensure you **do not**:

*   **Add controllers or routes to the library.** Routing belongs to the host application.
*   **Return PSR-7, Laravel, or Symfony responses from library classes.** Return strings or DTOs only.
*   **Call framework helpers inside library code.** (e.g., `request()`, `route()`, `env()`).
*   **Output headers from renderers.** Do not use `header('Content-Type: ...')` inside the SEO library.
*   **Commit `composer.lock`.** This is a reusable library; dependency resolution happens at the host application level.
*   **Add framework packages as dependencies.** (e.g., `illuminate/support`, `symfony/http-foundation`). Keep dependencies generic.

### Comprehensive SEO Reporting Integration

For a unified view, the host application can use the `SeoValidationReportBuilder` to combine the `SeoMetaValidator` and `SeoValidationScoreCalculator` into a single, comprehensive `SeoValidationReportDTO`. If you need to validate multiple items at once (e.g. for a bulk audit or CI report), you can use `SeoValidationBatchReportBuilder::build($items, $validationOptions = [], $scoreOptions = [], $sharedContext = [])`. This builder ensures existing validation and scoring behaviors remain completely unchanged. You can also use the `SeoValidationBatchReportExporter` to export these batch reports to arrays, JSON, summary arrays, and Markdown for external dashboards, QA tools, and CI environments.

```php
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationPreset;

$preset = SeoValidationPreset::standard();

$report = SeoValidationReportBuilder::build(
    meta: $pageMetaData,
    validationOptions: $preset['validationOptions'],
    scoreOptions: $preset['scoreOptions'],
    context: ['url' => 'https://example.com/blog/hello-world', 'entityType' => 'blog']
);

// Send the report array to a dashboard, logging service, or API response
return new JsonResponse($report->toArray());
```

The report builder is completely framework-neutral. It simply returns a DTO and has zero side effects: it does not mutate your original metadata, change internal validator logic, or emit any HTTP headers, routes, controllers, or responses.

If you are exporting the report to external dashboards, QA tools, or CI environments, the `SeoValidationReportExporter` can provide the same data in JSON, compact summary arrays, or Markdown:

```php
use Maatify\Seo\Web\Validation\SeoValidationReportExporter;

// For a dashboard API
return new JsonResponse(SeoValidationReportExporter::toSummaryArray($report));

// For a CLI tool or GitHub Action output
echo SeoValidationReportExporter::toMarkdown($report);
```
