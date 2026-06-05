# SchoolMaster Backend

Laravel API backend for the SchoolMaster SaaS platform.

## Source Of Truth

The SchoolMaster specs repository is authoritative for product behavior, API
contracts, business rules, architecture decisions, and implementation
sequencing.

Specs repository:

- https://github.com/edersondev/schoolmaster-specs

In local development, this backend repo may expose that repository through a
`specs/` symlink. Before changing backend behavior, read:

1. `specs/AGENTS.md`
2. Relevant files under `specs/specs`
3. `specs/api/openapi.yaml`
4. Relevant files under `specs/docs`
5. Relevant files under `specs/decisions`

If backend code conflicts with the specs repository, update the specs and
OpenAPI contract first. Backend implementation follows documented contracts
only.

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

Product APIs are RESTful and versioned under `/api/v1`. The approved backend
foundation and school administration slices are limited to these OpenAPI
operation IDs:

- `login`
- `getCurrentUser`
- `logout`
- `listSchools`
- `createSchool`
- `getSchool`
- `updateSchool`
- `listUsers`
- `createUser`
- `listRoles`
- `createRole`
- `listPermissions`
- `listAcademicYears`
- `createAcademicYear`
- `listAcademicPeriods`
- `createAcademicPeriod`
- `listGuardians`
- `createGuardian`
- `createGuardianUserLink`
- `deactivateGuardianUserLink`
- `listGuardianStudents`
- `getGuardianStudent`
- `getGuardianStudentAcademics`
- `getGuardianStudentContacts`

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

## Docker Test Runtime

Use Docker Compose when the host PHP runtime does not include a PDO database
driver or when tests should run against MySQL:

```bash
docker compose build app
docker compose run --rm app composer install
docker compose run --rm app php artisan test
```

The Compose stack provides PHP 8.3 with `pdo_mysql` and a MySQL 8 test database
named `schoolmaster_testing`.
