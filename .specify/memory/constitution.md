<!--
Sync Impact Report
Version change: template -> 1.0.0
Modified principles:
- Template principle 1 -> I. Specifications Are Source of Truth
- Template principle 2 -> II. Contract-First API Delivery
- Template principle 3 -> III. Tenant and Authorization Isolation
- Template principle 4 -> IV. Architecture Boundaries
- Template principle 5 -> V. Verification and Traceability
Added sections:
- SchoolMaster Delivery Constraints
- Speckit Workflow Gates
Removed sections:
- Placeholder template sections
Templates requiring updates:
- ✅ .specify/templates/plan-template.md
- ✅ .specify/templates/spec-template.md
- ✅ .specify/templates/tasks-template.md
- ✅ .specify/templates/commands/*.md not present in this project
Follow-up TODOs:
- None
-->
# SchoolMaster Constitution

## Core Principles

### I. Specifications Are Source of Truth

All product behavior, business rules, API contracts, architecture decisions, and
implementation sequencing MUST be defined in the `specs/` source artifacts
before backend or frontend implementation begins. Contributors MUST read
`specs/AGENTS.md`, the relevant feature specification, OpenAPI contracts,
implementation docs, and ADRs before planning, implementing, or reviewing
changes. If sources conflict, implementation MUST stop until the specification
repository is corrected.

Rationale: SchoolMaster is delivered across separate specification, backend,
and frontend repositories. Durable product behavior must be governed from one
contractual source instead of inferred independently in implementation code.

### II. Contract-First API Delivery

External API behavior MUST be documented in OpenAPI before matching backend
routes are exposed or frontend code consumes them. API changes MUST define
versioned `/api/v1` paths, operation IDs, request schemas, response schemas,
status semantics, error envelopes, pagination, filters, sorting, authorization,
and tenant-context behavior. Backend and frontend work MUST link implemented or
consumed behavior to approved OpenAPI operation IDs.

Rationale: Contract-first delivery prevents frontend/backend drift and makes
SchoolMaster API behavior reviewable before code creates de facto product
rules.

### III. Tenant and Authorization Isolation

SchoolMaster v1 MUST use `School` as the tenant root for school-owned records,
with `school_id` as the concrete tenant column unless a future ADR explicitly
replaces that rule. School-scoped operations MUST resolve an active permitted
school context before module-specific lookup, validation, authorization,
persistence, audit, or response shaping. Platform, school, teacher, student,
guardian, reporting, support, and billing permissions MUST remain separate, and
platform access MUST NOT bypass school-scoped authorization unless an approved
specification and OpenAPI contract explicitly grant that exception.

Rationale: Tenant leakage is a product and security failure for SchoolMaster.
Authorization behavior must be explicit, testable, and deny-by-default across
all school-owned data.

### IV. Architecture Boundaries

Backend implementation MUST remain API-only Laravel code using thin
controllers, Form Requests for validation, Services for business rules, Policies
for authorization, API Resources for response shaping, and DTOs when request or
service input has coordinated fields. Repositories or query objects SHOULD be
used only when tenant-scoped data access is complex enough to justify them.
Frontend implementation MUST remain Vue 3 SPA work using Composition API,
Pinia for state, Axios service layers, and Tailwind CSS. Feature work MUST stay
inside its documented repository boundary and MUST NOT introduce unrelated
refactors, product UI artifacts in backend code, or implementation-only product
rules.

Rationale: Clear architecture boundaries keep implementation predictable while
allowing each repository to evolve without leaking concerns into the others.

### V. Verification and Traceability

Every feature that changes API behavior, tenant-owned data, authorization,
lifecycle state, validation, audit behavior, or cross-repository contracts MUST
include verification proportional to the risk. Required verification includes
OpenAPI linting for contract changes, backend feature or unit tests for API and
domain behavior, tenant-isolation and authorization tests for school-owned data,
and frontend service or composable tests when frontend behavior is included.
Implementation notes or pull requests MUST record the feature id, relevant
operation IDs, and validation commands/results.

Rationale: The highest-risk SchoolMaster failures are contract drift, tenant
leakage, authorization mistakes, and lifecycle regressions. These must be
traceable to tests and contract validation before release.

## SchoolMaster Delivery Constraints

- The backend MUST expose API-only REST behavior under `/api/v1`.
- MySQL is the durable storage target unless a future ADR replaces it.
- Public cross-boundary identifiers SHOULD use UUIDs where applicable.
- Secrets MUST remain in environment configuration.
- File uploads, when in scope, MUST validate type, detected content, size,
  tenant ownership, and authorization before persistence.
- OpenAPI is authoritative for client-visible payloads, status codes, response
  envelopes, tenant semantics, pagination, filtering, sorting, and errors.
- Implementation MUST NOT create endpoints, request fields, response fields,
  filters, status values, lifecycle transitions, role semantics, or tenant
  exceptions that are absent from the approved specification and OpenAPI
  contract.

## Speckit Workflow Gates

Speckit feature work MUST follow this order:

1. Specify approved product behavior and explicit exclusions.
2. Clarify unresolved business, tenant, authorization, lifecycle, and contract
   questions before planning.
3. Plan architecture, data model, contracts, and verification against this
   constitution.
4. Generate tasks only after the plan and design artifacts are consistent.
5. Implement contract changes before backend route exposure.
6. Validate contracts and tests before merge or handoff.

Plans MUST include a Constitution Check covering source-of-truth alignment,
OpenAPI impact, repository boundary, tenant and authorization isolation,
architecture fit, verification, and deviations. Tasks MUST keep contract work
before implementation and include tests whenever the specification or this
constitution requires them.

## Governance

This constitution governs Speckit planning and implementation quality for the
SchoolMaster repositories. `AGENTS.md`, feature specifications, OpenAPI files,
documentation, and ADRs provide operational detail, but they MUST remain
consistent with these principles. If a required behavior conflicts with this
constitution, update the constitution explicitly before relying on the new rule.

Amendments require:

- a documented rationale,
- updates to affected templates, specifications, docs, contracts, or ADRs,
- a Sync Impact Report in this file,
- semantic versioning:
  - MAJOR for incompatible principle removals or redefinitions,
  - MINOR for new principles or materially expanded governance,
  - PATCH for clarifications that do not change governance semantics.

Reviews of Speckit artifacts MUST check compliance with this constitution
before implementation begins. Implementation reviews MUST reject undocumented
API behavior, tenant or authorization bypasses, missing required verification,
and repository-boundary violations.

**Version**: 1.0.0 | **Ratified**: 2026-05-30 | **Last Amended**: 2026-05-30
