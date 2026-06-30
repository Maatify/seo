# Phase 7: Usability and Rendering Helpers Plan

## 1. Objective
The primary objective of Phase 7 is to provide a developer-friendly usability layer for the `Maatify\Seo` module. This layer will allow developers to easily consume the underlying core architectural components and render ready-to-print SEO HTML payloads, construct SEO structures fluidly, and manage sitemap outputs without manually wiring together raw DTOs and Services.

## 2. Scope
- **HTML Rendering Helpers:** Utilities to render meta tags, canonical links, OpenGraph, Twitter Cards, and JSON-LD schema blocks as raw HTML strings.
- **Render Output DTOs:** DTOs that encapsulate the generated SEO payloads for easy inspection and consumption.
- **Fluent SEO Builder:** A facade-like fluent builder (without static global state) to streamline the setup of SEO components.
- **Optional Schema Integration:** An adapter layer for optional integration with `spatie/schema-org` if present.
- **Sitemap Usability Helpers:** Builders/helpers to abstract away complex URL and XML sitemap and sitemap index creation.

## 3. Non-goals
- **No Full-Page Rendering:** The rendering helpers will only output specific SEO tags (e.g., `<head>` content), not complete HTML document structures.
- **No HTTP Layer Integration:** We are not providing middleware, controllers, routes, or HTTP response manipulations.
- **No Hard Dependency Additions:** Do not add mandatory heavy external packages. Any integrations (like `spatie/schema-org`) will remain optional and suggested.

## 4. Framework-neutral constraints
To maintain the `Maatify` standard of true framework independence, the Phase 7 implementations MUST strictly adhere to the following constraints:
- **Do NOT add:** Controllers, routes, HTTP responses, PSR-7 response handling, Slim coupling, Laravel coupling, Symfony coupling, PHP-DI coupling, template engine coupling (no Twig/Blade), or static global state.
- **Allowed:** DTOs, value objects, builders, services, renderers, pure strings, HTML rendering as plain strings only, and optional adapters that are safe when dependencies are missing.

## 5. Proposed class list
- `MetaTagsHtmlRenderer`
- `OpenGraphHtmlRenderer`
- `TwitterCardHtmlRenderer`
- `JsonLdScriptRenderer`
- `SeoHeadHtmlRenderer`
- `SeoHeadHtmlDTO`
- `FluentSeoBuilder`
- `SpatieSchemaOrgAdapter` (Optional)
- `SitemapBuilder`
- `SitemapIndexBuilder`

## 6. Proposed namespaces/paths
Following the standard `Maatify` module structure under the `src/Web/` layer for web consumption:

- `Maatify\Seo\Web\Render\MetaTagsHtmlRenderer` -> `src/Web/Render/MetaTagsHtmlRenderer.php`
- `Maatify\Seo\Web\Render\OpenGraphHtmlRenderer` -> `src/Web/Render/OpenGraphHtmlRenderer.php`
- `Maatify\Seo\Web\Render\TwitterCardHtmlRenderer` -> `src/Web/Render/TwitterCardHtmlRenderer.php`
- `Maatify\Seo\Web\Render\JsonLdScriptRenderer` -> `src/Web/Render/JsonLdScriptRenderer.php`
- `Maatify\Seo\Web\Render\SeoHeadHtmlRenderer` -> `src/Web/Render/SeoHeadHtmlRenderer.php`
- `Maatify\Seo\Web\DTO\SeoHeadHtmlDTO` -> `src/Web/DTO/SeoHeadHtmlDTO.php`
- `Maatify\Seo\Web\Builder\FluentSeoBuilder` -> `src/Web/Builder/FluentSeoBuilder.php`
- `Maatify\Seo\Web\Adapter\SpatieSchemaOrgAdapter` -> `src/Web/Adapter/SpatieSchemaOrgAdapter.php`
- `Maatify\Seo\Web\Builder\SitemapBuilder` -> `src/Web/Builder/SitemapBuilder.php`
- `Maatify\Seo\Web\Builder\SitemapIndexBuilder` -> `src/Web/Builder/SitemapIndexBuilder.php`

## 7. Responsibilities per class
- **`MetaTagsHtmlRenderer`**: Converts meta tags (title, description, canonical, robots, etc.) from DTOs into valid HTML `<title>`, `<meta>`, and `<link>` strings.
- **`OpenGraphHtmlRenderer`**: Specific renderer for OpenGraph property tags.
- **`TwitterCardHtmlRenderer`**: Specific renderer for Twitter Card name tags.
- **`JsonLdScriptRenderer`**: Takes JSON-serializable schema objects or arrays and wraps them securely within `<script type="application/ld+json">` tags.
- **`SeoHeadHtmlRenderer`**: Orchestrates the other renderers to output the complete block of SEO tags ready to be dropped into an HTML `<head>`.
- **`SeoHeadHtmlDTO`**: A read-only transfer object that holds the generated HTML string payload and potentially the individual sections (meta, OG, Twitter, JSON-LD) for testing and inspection.
- **`FluentSeoBuilder`**: A stateful builder (instance-based, not global) that provides a fluent API (e.g., `->setTitle(...)`, `->setDescription(...)`) to construct SEO contexts without manually instantiating all underlying DTOs and Services. It ultimately builds the `SeoHeadHtmlDTO`.
- **`SpatieSchemaOrgAdapter`**: Checks if `spatie/schema-org` classes exist. If so, converts them into standard `Maatify` schema DTOs or JSON payloads. If not, safely handles the absence.
- **`SitemapBuilder`**: Provides a fluid interface to add URLs, priority, change frequency, and alternates for standard XML sitemaps, outputting or streaming the final XML.
- **`SitemapIndexBuilder`**: Provides a fluid interface to link multiple sitemaps together into a sitemap index.

## 8. Public API examples

**FluentSeoBuilder Usage:**
```php
$builder = new FluentSeoBuilder($metaGeneratorService);
$seoHeadDto = $builder
    ->setTitle('My Page')
    ->setDescription('My description')
    ->setCanonical('https://example.com/page')
    ->addOpenGraph('og:image', 'https://example.com/img.jpg')
    ->buildHtml($seoHeadHtmlRenderer);

echo $seoHeadDto->getFullHtml();
```

**SitemapBuilder Usage:**
```php
$sitemapBuilder = new SitemapBuilder();
$sitemapBuilder->addUrl('https://example.com/page-1', new DateTimeImmutable(), '0.8', 'weekly');
$xml = $sitemapBuilder->render();
```

## 9. Dependency rules
- The `Web\Render` classes may depend on `Shared\DTO` classes to know what data to render.
- The `Web\Builder` classes may depend on `Shared\Service` classes (e.g., `MetaGeneratorService`) to orchestrate the generation.
- No class in this phase should have direct dependencies on databases or framework-specific HTTP contexts.
- `SpatieSchemaOrgAdapter` must wrap class-exists checks so that a hard dependency on `spatie/schema-org` is never required.

## 10. Backward compatibility notes
- Phase 7 introduces entirely new classes in the `Web\` namespace.
- Existing `Shared\` and `Admin\` layer classes remain completely untouched, ensuring 100% backward compatibility for any existing integrations using the core module directly.
- The `composer.json` will only be updated with a `suggest` block for `spatie/schema-org`, guaranteeing no new required packages will break existing installations.

## 11. Testing plan
- **Unit Tests**: Every Renderer and Builder will be 100% covered by unit tests. Renderers will be tested for correct HTML escaping (preventing XSS) and valid tag structures.
- **Integration Tests**: The `FluentSeoBuilder` will be tested to ensure it correctly orchestrates the underlying DTOs and renderers to produce the expected composite `SeoHeadHtmlDTO`.
- **Static Analysis**: All new classes will be strictly typed and must pass `phpstan analyse -c phpstan.neon` at `level: max`.
- **Adapter Safety**: The optional `SpatieSchemaOrgAdapter` will be tested both with and without the `spatie/schema-org` package present (if feasible via mock autoloaders) to ensure it fails gracefully or is a no-op when the package is missing.

## 12. Implementation phases

### Phase 7A: HTML Rendering Helpers
Implement `MetaTagsHtmlRenderer`, `OpenGraphHtmlRenderer`, `TwitterCardHtmlRenderer`, `JsonLdScriptRenderer`, and `SeoHeadHtmlRenderer`.

### Phase 7B: Render Output DTOs
Implement the `SeoHeadHtmlDTO` to act as the transport mechanism for the rendered outputs.

### Phase 7C: Fluent SEO Builder
Implement the `FluentSeoBuilder` to orchestrate DTO creation and data population in a developer-friendly manner.

### Phase 7D: Optional spatie/schema-org integration
Implement `SpatieSchemaOrgAdapter` ensuring no hard dependency constraints are introduced. Update `composer.json` to suggest `spatie/schema-org`.

### Phase 7E: Sitemap Usability Helpers
Implement `SitemapBuilder` and `SitemapIndexBuilder` to simplify the generation of standard and index sitemaps.

## 13. Acceptance criteria
- All rendering helpers produce correctly escaped, plain HTML strings.
- No controllers, routes, HTTP responses, or framework-specific tools are introduced.
- The fluent builder operates purely on instances (no static global state).
- The `SpatieSchemaOrgAdapter` does not cause fatal errors if the library is not installed.
- PHPStan level max passes with zero errors on the new classes.
- The core services/DTOs are unaffected.
