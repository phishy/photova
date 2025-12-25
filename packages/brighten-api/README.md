# Brighten API

A unified media processing API with configurable backends. Route image/video operations to different providers (Replicate, fal.ai, remove.bg, or local processing) based on configuration.

## Features

- **Unified API** - Single endpoint for all media operations
- **Configurable Routing** - Choose providers per operation via YAML config
- **Fallback Support** - Automatic failover to backup providers
- **Multiple Providers** - Replicate, fal.ai, remove.bg, and more
- **Environment Variables** - Secure API key injection

## Quick Start

```bash
# Install dependencies
npm install

# Copy and configure
cp config.example.yaml config.yaml
# Edit config.yaml with your API keys

# Start the server
npm run dev
```

## Configuration

```yaml
server:
  port: 3000

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
```

### System Endpoints
```
GET /api/health        # Health check
GET /api/operations    # List available operations
GET /api/openapi.json  # OpenAPI specification
```

### Execute Operation
```
POST /api/v1/:operation

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
| `face-restore` | Restore/enhance faces | replicate, fal |

## Environment Variables

| Variable | Description |
|----------|-------------|
| `REPLICATE_API_KEY` | Replicate API key |
| `FAL_API_KEY` | fal.ai API key |
| `REMOVEBG_API_KEY` | remove.bg API key |
| `CONFIG_PATH` | Custom config file path |

## License

BSL-1.1 - See [LICENSE](../../LICENSE)
