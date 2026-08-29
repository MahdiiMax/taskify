# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.2.0] - 2026-08-28

### Added

- `GET /tasks/stats` endpoint with aggregate counts by status/priority,
  overdue, due-today, and completed-this-week
- Sorting support on the task listing via a whitelisted `?sort=` parameter
  (`-` prefix for descending order)
- `per_page` pagination control (1–100, default 10)
- Continuous integration pipeline (Pint + Pest suite, including a PostgreSQL
  matrix) via GitHub Actions
- CI status badge in the README
- Project `color` cast to the `ProjectColor` enum

### Changed

- PHPUnit config split into `phpunit.xml` (SQLite) and `phpunit.pgsql.xml`
  (PostgreSQL) for the driver matrix
- Composer package renamed from `laravel/laravel` to `mahdiimax/taskify`
- Task `status`, `priority`, and `due_date` cast to enums / datetime
- Task filtering extracted into a reusable `filter()` query scope using
  driver-aware `whereLike()`

### Fixed

- Delete endpoints return a proper empty `204 No Content` response
- Redundant manual `Hash::make()` removed in favor of the `hashed` cast
- Eager loading added to prevent N+1 queries on task resources

## [1.1.0] - 2026-08-14

### Added

- Projects: full CRUD with soft delete & restore
- Color-coded projects via the `ProjectColor` enum
- `project_id` on tasks, with filtering by project scoped to the owner
- Exposed `project_id` in the task resource

## [1.0.0] - 2026-08-06

### Added

- Sanctum token authentication (24h expiry, logout revocation)
- Full task CRUD with soft delete & restore (`trashed` / `restore`)
- Task filtering by `status` / `priority` and case-insensitive `search`
- Per-user task isolation via policies
- Rate limiting (auth 5/min, API 60/min)
- Interactive OpenAPI docs via Scramble
- Pest feature tests: auth, revocation, token expiry, filtering, rate limits
- PostgreSQL + Docker / pgAdmin setup

[Unreleased]: https://github.com/MahdiiMax/taskify/compare/1.1.0...develop
[1.2.0]: https://github.com/MahdiiMax/taskify/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/MahdiiMax/taskify/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/MahdiiMax/taskify/releases/tag/1.0.0
