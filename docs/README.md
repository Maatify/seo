# Maatify SEO Documentation

This is the official entry point for the Maatify SEO library documentation.
Use the current normative documents for the library's present APIs, behavior,
integration guidance, and limitations. Use historical and planning documents as
evidence or context, not as overrides of current code, tests, or normative docs.

## Documentation authority

### Current normative documentation

These documents represent the current library and integration truth and must stay
aligned with the implementation and tests:

- [`README.md`](../README.md) — package overview and quick start.
- [`SEO/library/`](SEO/library/) — current engineering handbook and architecture.
- [`SEO_LIBRARY_REFERENCE.md`](SEO_LIBRARY_REFERENCE.md) — current API and capability reference.
- [`guides/`](guides/) — current usage and host-integration guides.

### Historical implementation evidence

These paths record work, verification, or decisions from completed phases:

- [`phases/`](phases/)
- [`verification/`](verification/)
- [`batches/`](batches/)
- completed documents under [`blueprints/`](blueprints/)
- legacy material under [`SEO/v1/`](SEO/v1/)

Historical documents explain what was implemented or verified at a point in time.
They do not override current code, tests, or current normative documentation.

### Architecture and audit evidence

- [`audits/`](audits/) contains audit findings, evidence, and remediation contracts.
- [`SEO_ARCHITECTURE_STANDARDS_CONTRACT_INTEGRITY_AUDIT.md`](audits/SEO_ARCHITECTURE_STANDARDS_CONTRACT_INTEGRITY_AUDIT.md)
  is the execution authority for the current SEO architecture remediation. It is
  not a replacement for a current user guide.

### Future and planning material

- [`roadmap/`](roadmap/) records planned sequencing and lifecycle context.
- [`proposals/`](proposals/) records proposed designs and RFCs.
- An unstarted [`blueprint/`](blueprints/) is planning material until its phase is
  implemented and accepted.

Roadmaps, proposals, and unstarted blueprints are not current API authority.

## Reading paths

- Start with the [package README](../README.md) for installation and a quick start.
- Use the [engineering handbook](SEO/library/README.md) for architecture and
  structured-data boundaries.
- Use the [library reference](SEO_LIBRARY_REFERENCE.md) for public classes and
  capability boundaries.
- Use the [usage guide](guides/USAGE_GUIDE.md) and [integration guide](guides/INTEGRATION_GUIDE.md)
  for runnable examples and host-application integration.

When documents disagree, first check the current implementation and tests, then
use the current normative documentation. Audit contracts govern remediation
decisions; historical and future documents provide evidence or context only.
