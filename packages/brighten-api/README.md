# Brighten API

A unified media processing API with configurable backends. Route image/video operations to different providers (Replicate, fal.ai, remove.bg, or local processing) based on configuration.

## Features

- **Unified API** - Single endpoint for all media operations
- **Configurable Routing** - Choose providers per operation via YAML config
- **Fallback Support** - Automatic failover to backup providers
- **Multiple Providers** - Replicate, fal.ai, remove.bg, and more
- **Authentication** - User accounts, API keys, and usage tracking (via PocketBase)
- **Dashboard** - Web UI for managing API keys and viewing usage analytics
- **Environment Variables** - Secure API key injection

## Quick Start

```bash
npm install

cp .env.example .env.local
# Edit .env.local with your API keys

npm run setup   # Downloads PocketBase, creates collections
npm run dev     # Start the API server
```

For development with auth enabled, run in separate terminals:
```bash
npm run dev:pocketbase   # Terminal 1: PocketBase
npm run dev              # Terminal 2: API server
```

## Configuration

```yaml
server:
  port: 3000

auth:
  enabled: false  # Set to true to enable authentication
  pocketbase:
    url: http://127.0.0.1:8090

operations:
  background-remove:
    provider: replicate
    fallback: removebg
    
  upscale:
    provider: fal
    
providers:
  replicate:
    api_key: ${REPLICATE_API_KEY}
  fal:
    api_key: ${FAL_API_KEY}
```

## API Endpoints

### Homepage & Documentation
```
GET /          # HTML homepage with API overview
GET /docs      # Interactive API documentation (Redoc)
GET /dashboard # User dashboard (when auth enabled)
```

### System Endpoints
```
GET /api/health        # Health check
GET /api/operations    # List available operations
GET /api/openapi.json  # OpenAPI specification
```

### Authentication (when enabled)
```
POST /api/auth/signup   # Create account
POST /api/auth/login    # Sign in
POST /api/auth/logout   # Sign out
GET  /api/auth/me       # Get current user
PATCH /api/auth/me      # Update profile
```

### API Keys (when enabled)
```
GET    /api/keys           # List API keys
POST   /api/keys           # Create new key
PATCH  /api/keys/:id       # Update key
DELETE /api/keys/:id       # Delete key
POST   /api/keys/:id/regenerate  # Regenerate key
```

### Usage Analytics (when enabled)
```
GET /api/usage/summary     # Usage summary with breakdown
GET /api/usage/timeseries  # Time series data for charts
GET /api/usage/current     # Current month usage
```

### Execute Operation
```
POST /api/v1/:operation

Headers (when auth enabled):
  Authorization: Bearer <api_key>
  # or
  X-API-Key: <api_key>

Body:
{
  "image": "data:image/png;base64,...",
  "options": {}
}

Response:
{
  "image": "data:image/png;base64,...",
  "metadata": {
    "provider": "replicate",
    "processingTime": 1234
  }
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

## Authentication Setup

The setup script automatically downloads PocketBase and creates the required collections:

```bash
cp .env.example .env.local
# Edit .env.local with admin credentials

npm run setup
```

Then enable auth in `config.yaml`:
```yaml
auth:
  enabled: true
```

Start both servers and visit `/dashboard`:
```bash
npm run dev:pocketbase   # Terminal 1
npm run dev              # Terminal 2
```

PocketBase admin UI: http://127.0.0.1:8090/_/

## Environment Variables

| Variable | Description |
|----------|-------------|
| `REPLICATE_API_KEY` | Replicate API key |
| `FAL_API_KEY` | fal.ai API key |
| `REMOVEBG_API_KEY` | remove.bg API key |
| `CONFIG_PATH` | Custom config file path |
| `POCKETBASE_URL` | PocketBase server URL |
| `POCKETBASE_ADMIN_EMAIL` | PocketBase admin email |
| `POCKETBASE_ADMIN_PASSWORD` | PocketBase admin password |

## License

BSL-1.1 - See [LICENSE](../../LICENSE)
