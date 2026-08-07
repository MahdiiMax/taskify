<p align="center">
  <h1 align="center">🎖️ Taskify API</h1>
  <p align="center">A clean, RESTful Task Management API built with Laravel 13</p>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL">
  <img src="https://img.shields.io/badge/Sanctum-Auth-000000?style=for-the-badge" alt="Sanctum">
  <img src="https://img.shields.io/badge/API-v1-0ea5e9?style=for-the-badge" alt="API v1">
  <img src="https://img.shields.io/badge/License-MIT-yellow?style=for-the-badge" alt="MIT">
</p>


## ✨ Overview

**Taskify** is a production-ready task management API with token-based authentication, per-user task isolation, filtering, and rate limiting — fully documented with interactive OpenAPI docs.

### 🎁 Features

- 🔐 **Sanctum token auth** — 24-hour token expiry, revocable via logout
- 📝 **Full CRUD** with soft delete & restore
- 🔍 **Smart filtering** — exact `status`/`priority` filters + case-insensitive search
- 👤 **Per-user isolation** — tasks are private to their owner
- 🚦 **Rate limiting** — auth (5/min) & API (60/min)
- 📚 **Interactive docs** via Scramble
- 🗃️ **PostgreSQL** + Docker setup included


## 📋 Table of Contents

- [Requirements](#-requirements)
- [Installation](#-installation)
- [Running Tests](#-running-tests)
- [Project Structure](#️-project-structure)
- [API Reference](#-api-reference)
- [Authentication](#-authentication)
- [Tasks](#-tasks)
- [Status Codes](#-status-codes)
- [Rate Limits](#-rate-limits)
- [Documentation](#-documentation)
- [Deployment](#-deployment)
- [License](#️-license)
- [Developed By](#-developed-by)


## 🔰 Requirements

| Tool | Version |
| --- | --- |
| PHP | 8.3+ |
| Composer | latest |
| PostgreSQL | 16 (or any Laravel-supported DB) |
| Node.js | optional (frontend assets only) |

## 💿 Installation

```bash
git clone https://github.com/MahdiiMax/taskify.git taskify
cd taskify
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

> 💡 **Tip:** run `composer run setup` to do all of the above automatically.

⚠️ *Make sure your database server is running before migrating the database tables.*

### 💾 Database (Docker)

A `docker-compose.yaml` spins up **PostgreSQL** (db `taskify`, user/password `admin`/`admin`) and **pgAdmin** at `http://localhost:5050` (login: `admin@admin.com` / `admin`):

```bash
docker compose up -d
```

Then configure `.env`:

```env
DB_CONNECTION=pgsql
DB_DATABASE=taskify
DB_USERNAME=admin
DB_PASSWORD=admin

AUTH_GUARD=sanctum
SANCTUM_EXPIRATION=1440
```

| Key | Purpose |
| --- | --- |
| `AUTH_GUARD=sanctum` | Makes `$request->user()` resolve API tokens |
| `SANCTUM_EXPIRATION=1440` | Token lifetime in minutes (24h) |

## 🔍 Running Tests

```bash
composer test    # or: php artisan test
```

The suite runs on in-memory SQLite — no DB server needed. ⚠️ *SQLite is more lenient than PostgreSQL; run the suite against real Postgres before release to catch driver-specific issues.*

If you want to run tests in your own PostgreSQL server:

```bash
php artisan test --env=pgsql
```


## 🗂️ Project Structure

    app/
    ├── Enums/                          # TaskPriority, TaskStatus
    ├── Http/
    │   ├── Controllers/Api/V1/        # AuthController, TaskController
    │   ├── Middleware/Api/V1/         # GuestMiddleware
    │   ├── Requests/Api/V1/           # Auth/ & Task/ Form Requests
    │   └── Resources/Api/V1/          # TaskResource, UserResource
    ├── Models/                        # Task, User
    ├── Policies/                      # TaskPolicy
    └── Providers/                     # AppServiceProvider

    routes/
    └── api.php                        # v1 API routes

    tests/
    ├── Feature/Api/V1/                # AuthTest, TaskTest


## 📜 API Reference

| Base URL | `http://localhost:8000/api/v1` |
| --- | --- |
| **Interactive docs** | `http://localhost:8000/docs/api` |

### 🔑 Authentication

All task endpoints require a Bearer token:

```
Authorization: Bearer {token}
```

Tokens are issued by `login`, live **24 hours**, and are revoked by `logout`.

#### Register — `POST /auth/register`

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

> `201` → `{ "message", "user": { "id", "name", "email", "created_at" } }`

#### Login — `POST /auth/login`

```json
{
  "email": "jane@example.com",
  "password": "password123"
}
```

> `200` → `{ "message", "user": {...}, "token": "<plain token>" }`

#### Logout — `POST /auth/logout`

> `200` → `{ "message": "logged out successfully" }` *(revokes the current token)*

### 📋 Tasks

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/tasks` | List own tasks *(paginated, 10/page)* |
| POST | `/tasks` | Create a task |
| GET | `/tasks/{task}` | Show a task |
| PUT/PATCH | `/tasks/{task}` | Update a task |
| DELETE | `/tasks/{task}` | Soft delete a task |
| GET | `/tasks/trashed` | List soft-deleted tasks |
| POST | `/tasks/{task}/restore` | Restore a soft-deleted task |

#### Task Fields

| Field | Rules |
| --- | --- |
| `title` | required · string · max 255 |
| `description` | nullable · string |
| `status` | nullable — `pending` · `in_progress` · `done` |
| `priority` | nullable — `low` · `medium` · `high` |
| `due_date` | nullable · date · today or later |

#### Filters — `GET /tasks`

| Query | Example | Behavior |
| --- | --- | --- |
| `status` | `?status=pending` | exact match |
| `priority` | `?priority=high` | exact match |
| `search` | `?search=buy milk` | case-insensitive partial match on title/description |

Invalid `status`/`priority` values → `422`.

### 🌐 Status Codes

| Code | Meaning |
| --- | --- |
| `200` | Success |
| `201` | Created |
| `400` | Already authenticated (on login/register) |
| `401` | Unauthenticated / expired or revoked token |
| `403` | Forbidden (another user's resource) |
| `422` | Validation error |
| `429` | Rate limit exceeded |

### ⛔ Rate Limits

| Scope | Limit |
| --- | --- |
| Auth routes (login/register) | **5/min** per email + IP |
| All API routes | **60/min** per user (or IP) |

## 📚 Documentation

Interactive API docs rendered with Stoplight Elements (dark theme):

| Docs UI | `http://localhost:8000/docs/api` |
| --- | --- |
| **OpenAPI spec** | `http://localhost:8000/docs/api.json` *(OpenAPI 3.1, served live)* |

Export the spec to a file:

```bash
php artisan scramble:export   # writes api.json
```

`api.json` is a standard OpenAPI document — import it into **Insomnia** or **Postman** to explore and test the API.

## 🚀 Deployment

- Run `php artisan config:cache` / `route:cache` **only in production** — in development a stale config cache can serve outdated settings.
- Warm the docs cache with `php artisan scramble:cache`.
- Expired tokens stay in the DB until pruned — schedule `php artisan sanctum:prune-expired` (daily via `routes/console.php` + the `schedule:run` cron) to keep the table clean.

## ©️ License

This project is open-sourced under the [MIT License](LICENSE) — © 2026 Mahdi Sadeghi.

## 👨‍💻 Developed By

<p align="center">
  <strong>Mahdi Sadeghi</strong><br>
  <em>Full-Stack Developer</em><br>
  <br>
  <a href="https://github.com/MahdiiMax">
    <img src="https://img.shields.io/badge/GitHub-MahdiiMax-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub">
  </a>
  <a href="mailto:mahdisadeghi.max@gmail.com">
    <img src="https://img.shields.io/badge/Email-mahdisadeghi.max%40gmail.com-EA4335?style=for-the-badge&logo=gmail&logoColor=white" alt="Email">
  </a>
</p>

<p align="center">Built with ❤️ and ☕ using Laravel</p>