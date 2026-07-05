# Maatify SEO Library

[![Latest Version](https://img.shields.io/packagist/v/maatify/seo.svg?style=for-the-badge)](https://packagist.org/packages/maatify/seo)
[![PHP Version](https://img.shields.io/packagist/php-v/maatify/seo.svg?style=for-the-badge)](https://packagist.org/packages/maatify/seo)
[![License](https://img.shields.io/packagist/l/maatify/seo.svg?style=for-the-badge)](LICENSE)

![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-4E8CAE)

[![Changelog](https://img.shields.io/badge/Changelog-View-blue)](CHANGELOG.md)
[![Security](https://img.shields.io/badge/Security-Policy-important)](SECURITY.md)

![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/seo?label=Monthly%20Downloads&color=00A8E8)
![Total Downloads](https://img.shields.io/packagist/dt/maatify/seo?label=Total%20Downloads&color=2AA9E0)

![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-blueviolet?style=for-the-badge)

[![Install](https://img.shields.io/badge/Install-composer%20require-blue?style=for-the-badge)](https://packagist.org/packages/maatify/seo)

A framework-agnostic PHP SEO library for metadata generation, JSON-LD schemas, sitemaps, hreflang, redirects, slug history, validation, and admin-oriented SEO tooling.

## Table of Contents

- [Installation](#installation)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Features](#features)
- [Practical Examples](#practical-examples)
- [Architecture Overview](#architecture-overview)
- [Documentation](#documentation)
- [Design Principles](#design-principles)
- [License](#license)

## Installation

```bash
composer require maatify/seo
```

## Requirements

- PHP >= 8.2
- `ext-xmlwriter`

## Quick Start

Creating a basic page metadata output and rendering it using existing public APIs:

```php
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;

$metaTags = new MetaTagsDTO(
    title: 'About Us',
    description: 'Learn more about our framework-agnostic SEO library.',
    canonicalUrl: 'https://example.com/about',
    openGraphTitle: 'About Us',
    openGraphDescription: 'Learn more about our framework-agnostic SEO library.',
    openGraphUrl: 'https://example.com/about',
    openGraphType: 'website',
);

$renderer = new SeoHeadHtmlRenderer();
echo $renderer->render($metaTags);
```

## Features

- **Metadata generation:** Easily construct standard HTML meta tags and canonical URLs.
- **JSON-LD schemas:** Framework-agnostic structured data generation for SEO (e.g., Breadcrumbs, Products) via strictly typed DTOs.
- **Social metadata:** Generate Open Graph and Twitter Card tags seamlessly.
- **Sitemap generation:** In-memory XML sitemap generation stream dynamically powered by strict DTOs.
- **Hreflang support:** Multi-language indexing (`xhtml:link`) generation helpers.
- **Redirects and slug history:** Logic to manage URL migrations and legacy paths cleanly.
- **SEO validation and scoring:** Audit generated SEO metadata arrays or objects to warn about missing fields, conflicts, and compute actionable SEO scores.
- **Import/export:** SEO metadata import and export functionality for administrative portability.
- **Admin tooling:** Admin-specific commands and queries for managing SEO overrides, tracking slug history, and SERP/Social previews.
- **Framework-agnostic architecture:** 100% PHP domain logic with zero framework or UI dependencies, ready to drop into any stack.

## Practical Examples

To see how the library functions in real-world scenarios, you can run the following standalone examples from the command line:

- `php examples/admin-previews.php`: Generate SERP and Social previews for admin interfaces.
- `php examples/basic-head-render.php`: Rendering of standard meta tags and array-based JSON-LD.
- `php examples/category-page-seo.php`: Construct schema and metadata for category pages using FluentSeoBuilder.
- `php examples/hreflang-generation.php`: Hreflang link generation for multi-language indexing.
- `php examples/import-export.php`: SEO metadata import and export functionalities.
- `php examples/meta-robots-canonical.php`: Construct meta robots tags and canonical URLs.
- `php examples/product-page-seo.php`: Open graph tags and schema generation for product pages.
- `php examples/schema-output.php`: Outputs structured data schemas from arrays, DTOs, and adapted optional Spatie objects.
- `php examples/seo-page-presets.php`: Generic, e-commerce, content, and local business SEO page presets.
- `php examples/sitemap-output.php`: Native sitemap XML outputs using provided DTOs and renderers.
- `php examples/social-builders.php`: OpenGraph and TwitterCard builders to generate social metadata.
- `php examples/phase7-output-showcase.php`: Showcases rendered SEO head output helpers and DTO output sections.
- `php examples/phase13-jsonld-builders.php`: Demonstrates the JSON-LD builder suite across supported schema types.

## Architecture Overview

The library follows a strict layered architecture to ensure clean separation of concerns and maximum portability.

**Core Library**
↓
**Admin Essentials**
↓
**Host Application**

- **No Controllers:** Routing decisions are strictly the host application's responsibility.
- **No UI:** The library provides raw strings, arrays, or DTOs. Any HTML rendering is purely optional utility output, avoiding template engine coupling.
- **No Framework Dependency:** Built with standard PHP, using generic contracts (interfaces) to integrate with frameworks.
- **No ORM Dependency:** Database interactions are defined by abstract repositories, allowing the host application to use Doctrine, Eloquent, or native PDO.

## Documentation

Full documentation, including internal compliance checks, library references, and guides:

- [docs/](docs/) - Central repository for library documentation.
- [Roadmap](docs/roadmap/) - Development and feature roadmap.
- [Proposals](docs/proposals/) - Architectural design proposals.
- [Verification Reports](docs/verification/) - Detailed audit trails and release readiness reports.

## Design Principles

This library strictly adheres to Maatify's core design standards for standalone modules:

- **Host-Agnostic:** Designed to plug into any existing PHP project using standardized interface definitions.
- **Pure Domain Logic:** Excludes any coupling to HTTP requests, global `$_SERVER` states, or environment `.env` files.
- **Testable:** Easily unit tested via straightforward constructor injection.
- **Extensible:** Internal Builders and Services are composed, allowing developers to inject custom overrides via Dependency Injection.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
