# Photova API

Laravel 12 + PostgreSQL media processing API with dashboard UI.

## Features

- **Unified API** - Single endpoint for all media operations
- **Configurable Routing** - Choose providers per operation via config
- **Fallback Support** - Automatic failover to backup providers
- **Multiple Providers** - Replicate, fal.ai, remove.bg
- **Authentication** - User accounts, API keys, and usage tracking
- **Asset Storage** - Configurable storage backends (filesystem, S3)

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Docker (for PostgreSQL)

### Setup (Recommended)

Run PostgreSQL via Docker, Laravel locally for fast development:

```bash
# From monorepo root - start PostgreSQL
docker compose up postgres -d

# Setup Laravel
cd packages/brighten-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Start dev server
php artisan serve
```

The `.env.example` defaults match the Docker PostgreSQL config, so no edits needed.

### Alternative: Local PostgreSQL

If you have PostgreSQL installed locally, update `.env` with your credentials and skip the Docker step.

### Alternative: Full Docker (Sail)

```bash
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

## Configuration

### Environment Variables

| Variable | Description |
|----------|-------------|
| `DB_CONNECTION` | Database driver (pgsql) |
| `DB_HOST` | Database host |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |
| `AUTH_ENABLED` | Enable API key auth (true/false) |
| `REPLICATE_API_KEY` | Replicate API key |
| `FAL_API_KEY` | fal.ai API key |
| `REMOVEBG_API_KEY` | remove.bg API key |

### Provider Configuration

Edit `config/photova.php` to configure operation routing:

```php
'operations' => [
    'background-remove' => [
        'provider' => 'replicate',
        'fallback' => 'removebg',
    ],
    'upscale' => [
        'provider' => 'replicate',
    ],
],
```

## API Endpoints

### System
```
GET  /api/health           Health check
GET  /api/operations       List available operations
GET  /api/openapi.json     OpenAPI specification
```

### Authentication
```
POST /api/auth/signup      Create account
POST /api/auth/login       Sign in (returns token)
POST /api/auth/logout      Sign out
GET  /api/auth/me          Get current user
PATCH /api/auth/me         Update profile
```

### API Keys
```
GET    /api/keys                  List API keys
POST   /api/keys                  Create new key
GET    /api/keys/{id}             Get key details
PATCH  /api/keys/{id}             Update key
DELETE /api/keys/{id}             Delete key
POST   /api/keys/{id}/regenerate  Regenerate key
```

### Usage Analytics
```
GET /api/usage/summary      Usage summary with breakdown
GET /api/usage/timeseries   Time series data for charts
GET /api/usage/current      Current month usage
```

### Asset Storage
```
POST   /api/assets              Upload asset
GET    /api/assets              List assets
GET    /api/assets/{id}         Get asset metadata
GET    /api/assets/{id}?download=true  Download asset
DELETE /api/assets/{id}         Delete asset
```

### Operations
```
POST /api/v1/{operation}

Headers:
  Authorization: Bearer <api_key>
  # or
  X-API-Key: <api_key>

Body:
{
  "image": "data:image/png;base64,...",
  "options": {}
}
```

## Supported Operations

| Operation | Description | Providers |
|-----------|-------------|-----------|
| `background-remove` | Remove image background | replicate, fal, removebg |
| `upscale` | Increase image resolution | replicate, fal |
| `unblur` | Deblur/sharpen images | replicate |
| `colorize` | Add color to B&W images | replicate |
| `inpaint` | Remove objects from images | replicate |
| `restore` | Restore old/damaged photos | replicate |

## Development

```bash
# Start PostgreSQL (from monorepo root)
docker compose up postgres -d

# Start Laravel dev server
php artisan serve

# Run tests (52 tests)
./vendor/bin/pest

# Other useful commands
php artisan route:list         # List all routes
php artisan migrate:fresh      # Reset database
```

### Stopping PostgreSQL

```bash
# From monorepo root
docker compose stop postgres
```

## License

BSL-1.1 - See [LICENSE](../../LICENSE)
