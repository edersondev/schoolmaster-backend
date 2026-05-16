# SchoolMaster Backend

Laravel API backend for the SchoolMaster SaaS platform.

## Source Of Truth

The `specs/` submodule is authoritative for product behavior, API contracts,
business rules, architecture decisions, and implementation sequencing. Before
changing backend behavior, read:

1. `specs/AGENTS.md`
2. Relevant files under `specs/specs`
3. `specs/api/openapi.yaml`
4. Relevant files under `specs/docs`
5. Relevant files under `specs/decisions`

If backend code conflicts with `specs/`, update the specs and OpenAPI contract
first. Backend implementation follows documented contracts only.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
```

SchoolMaster uses MySQL as the primary transactional datastore. The default
`.env.example` values target a local `schoolmaster` database.

## API Boundary

Product APIs are RESTful and versioned under `/api/v1`. The first approved
backend slice is limited to these OpenAPI operation IDs:

- `login`
- `getCurrentUser`
- `logout`
- `listSchools`
- `createSchool`
- `getSchool`
- `updateSchool`

Operational framework routes, such as Laravel health checks, are not product
feature routes. Product Blade views are not part of this backend.

## Contract Validation

Run OpenAPI validation before merging product-visible behavior:

```bash
npx @redocly/cli lint specs/api/openapi.yaml
npx @redocly/cli lint specs/specs/001-schoolmaster-platform/contracts/openapi.yaml
```

## Backend Validation

```bash
php artisan route:list
php artisan test
```

`php artisan route:list` should show product routes only under `/api/v1` and no
undocumented product route outside `routes/api.php`.
