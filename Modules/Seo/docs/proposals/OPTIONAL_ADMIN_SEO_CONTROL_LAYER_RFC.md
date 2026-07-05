# RFC: Optional Admin SEO Control Layer

**Status:** Proposed
**Target:** Post v1.0.0 (Future Candidate)
**Type:** Optional Layer

## Problem Statement

The current Maatify SEO library provides admin-facing essentials such as SEO overrides, redirect management, slug history, SERP previews, and social previews. This minimalist approach is intentional and sufficient for the initial `v1.0.0` release, as the package is strictly framework-agnostic. The responsibility of providing the UI, controllers, routes, permissions, and concrete admin panel integrations lies with the host application.

However, as applications scale, developers frequently need a unified control layer to manage broader SEO capabilities (e.g., meta tags, canonical URLs, JSON-LD, sitemaps) exposed by the library from within their admin panels. Without an optional, higher-level control layer, each host application must manually wire these underlying library features into their admin interfaces, leading to duplicated effort and potential inconsistencies.

## Goals

*   **Provide an optional, higher-level API:** Create a structured interface (e.g., Service or Facade pattern) that orchestrates the library's granular features (meta tags, open graph, twitter cards, robots, canonical URLs) specifically for admin use cases.
*   **Simplify Admin Integration:** Make it easier for host applications to build SEO settings pages, edit forms, and bulk-update tools by providing ready-to-use control methods.
*   **Maintain Framework Agnosticism:** Ensure this new layer remains independent of any specific framework, UI library, routing system, or HTTP request/response cycle.
*   **Encapsulate Complexity:** Hide the complexity of instantiating multiple builders, presets, and validation helpers behind a cohesive admin-centric API.
*   **Preserve Current Scope:** Ensure this layer is strictly optional and does not interfere with the core library's usage for those who prefer direct access to the low-level components.

## Non-Goals

*   **No UI or Views:** This RFC will *not* introduce any HTML templates, Vue/React components, or styling.
*   **No Controllers or Routing:** This layer will *not* provide HTTP controllers, framework-specific routes, or request validation middleware.
*   **No Permissions/Auth:** Authentication and authorization remain strictly the responsibility of the host application.
*   **No Active Record / Database Coupling:** The layer will orchestrate data but will not dictate database schemas or ORM models beyond the existing DTO contracts.
*   **Not Required for v1.0.0:** This is a future enhancement and is explicitly *not* a blocker for the initial release. The current admin layer acceptance criteria remain unchanged.

## Possible Architecture

The Optional Admin SEO Control Layer would sit above the existing `Shared` and `Admin` layers (like `Admin/SeoOverride/`, `Admin/Redirect/`, `Admin/SlugHistory/`).

It might look like a set of Service or Manager classes (e.g., `AdminSeoManager`, `AdminSitemapConfigurator`, `AdminSchemaEditor`) that take input data (likely as arrays or DTOs) from the host application's controllers, use the existing builders and validators, and return structured output or perform orchestration.

## Possible Components

*   **`AdminSeoMetadataManager`:** Centralizes reading and writing (via DTOs) of meta tags, Open Graph, Twitter cards, canonical URLs, and robots directives for a specific entity or route.
*   **`AdminSitemapConfigurator`:** Provides methods to manage sitemap options (changefreq, priority, active state) for different sections of the site.
*   **`AdminJsonLdEditor`:** A high-level interface for configuring JSON-LD schemas (e.g., Organization, WebSite) that apply globally or per-entity.
*   **`AdminSeoValidatorService`:** Wraps the existing validation and reporting helpers (Phase 11) to provide easy-to-consume feedback for admin dashboards or pre-publish checks.
*   **`AdminSeoImportExportService`:** Orchestrates the import and export of SEO metadata (Phase 19).

## Open Questions

*   **Data Persistence Interface:** Should this layer introduce generic interfaces for persistence (e.g., `SeoRepositoryInterface`) that the host application must implement, or should it purely transform data and return it to the host application to save?
*   **Granularity:** Should the manager classes be broken down by feature (Meta, Schema, Sitemap) or by entity type (Global, Page, Product)?
*   **DTO Mapping:** How can we best streamline the mapping between the host application's data arrays/requests and the library's strict input DTOs?

## Acceptance Criteria

*(To be defined during the implementation phase of this RFC, post v1.0.0)*

*   [ ] The layer is fully optional and does not add new mandatory dependencies.
*   [ ] The layer contains no framework-specific code (no HTTP request parsing, no routing).
*   [ ] The layer is documented with clear integration examples for host application controllers.
*   [ ] All manager classes are strictly typed and covered by standalone tests.
