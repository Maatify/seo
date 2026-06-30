# Maatify SEO Module

This is the standalone Maatify SEO library. It provides host-agnostic tools to manage SEO metadata, schema generation (JSON-LD), redirects, slug history, and sitemaps.

> **Note**: This package is intentionally framework-agnostic and host-agnostic. It contains zero coupling to frameworks (like Laravel or Symfony) and zero foreign-key relationships to host database tables. It relies on standard host interfaces (contracts).

## Installation
```bash
composer require maatify/seo
```

## Implemented Layers
Currently, the module has the following foundational layers implemented (Phases 1-3A):
- **Phase 1 (Foundation):** Base DTOs, Exceptions, Host Contracts.
- **Phase 2A (Schema):** Standalone SQL tables for slug history, redirects, and manual SEO overrides.
- **Phase 2B (Repositories):** PDO implementations for persistence layers without ORMs.
- **Phase 2C (Services):** Core domain logic orchestration, utilizing constructor injection and strict module exceptions.
- **Phase 3A (Meta Generator):** Logic to assemble and orchestrate standard HTML Meta tags, merging host-provided defaults with manual database overrides in a framework-agnostic way.
