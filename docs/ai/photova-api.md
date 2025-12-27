# Brighten API Setup

The Brighten API is a standalone server that handles AI processing requests. It acts as a proxy between your frontend and AI providers, keeping API keys secure.

## Installation

The API is included in the monorepo:

```bash
cd packages/photova-api
npm install
```

## Configuration

### 1. Create Config File

```bash
cp config.example.yaml config.yaml
```

### 2. Edit config.yaml

```yaml
server:
  port: 3001
  host: 0.0.0.0

operations:
  background-remove:
    provider: replicate
  colorize:
    provider: replicate
  restore:
    provider: replicate
  unblur:
    provider: replicate
  inpaint:
    provider: replicate

providers:
  replicate:
    api_key: ${REPLICATE_API_KEY}
```

### 3. Set Environment Variables

Create `.env.local` (gitignored):

```bash
REPLICATE_API_KEY=r8_your_api_key_here
```

Get your Replicate API key at [replicate.com/account/api-tokens](https://replicate.com/account/api-tokens)

## Running the Server

### Development

```bash
npm run dev
```

Server starts at `http://localhost:3001` with hot reload.

### Production

```bash
npm run build
npm start
```

## Configuration Reference

### Server Options

```yaml
server:
  port: 3001          # Port to listen on
  host: 0.0.0.0       # Host to bind to
```

### Operations

Each operation maps to a provider:

```yaml
operations:
  background-remove:
    provider: replicate    # Primary provider
    fallback: removebg     # Optional fallback provider
```

### Providers

#### Replicate

```yaml
providers:
  replicate:
    api_key: ${REPLICATE_API_KEY}
```

Supported operations: `background-remove`, `colorize`, `restore`, `unblur`, `inpaint`

#### remove.bg (Optional)

```yaml
providers:
  removebg:
    api_key: ${REMOVEBG_API_KEY}
```

Supported operations: `background-remove`

#### fal.ai (Optional)

```yaml
providers:
  fal:
    api_key: ${FAL_API_KEY}
```

## Environment Variable Interpolation

Use `${VAR_NAME}` syntax in config.yaml to reference environment variables:

```yaml
providers:
  replicate:
    api_key: ${REPLICATE_API_KEY}  # Reads from environment
```

Variables are loaded from:
1. System environment
2. `.env.local` file (if exists)

## Health Check

```bash
curl http://localhost:3001/health
```

Response:
```json
{
  "status": "ok",
  "version": "1.0.0"
}
```

## CORS

The server enables CORS by default for all origins. For production, you may want to restrict this:

```typescript
// In src/server/index.ts, modify the cors configuration
```

## Docker Deployment

```dockerfile
FROM node:20-alpine

WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY dist ./dist
COPY config.yaml ./

ENV NODE_ENV=production
EXPOSE 3001

CMD ["node", "dist/index.js"]
```

```bash
docker build -t brighten-api .
docker run -p 3001:3001 -e REPLICATE_API_KEY=r8_xxx brighten-api
```

## Troubleshooting

### "API key not configured"

Ensure your `.env.local` file exists and contains the API key:

```bash
REPLICATE_API_KEY=r8_your_key_here
```

### "Operation timed out"

Some AI models take 60-120 seconds to process. The default timeout is 120 seconds. If you're still hitting timeouts, check:

1. Image size (larger images take longer)
2. Model complexity
3. Provider status

### "Provider not found"

Check that the provider is configured in `config.yaml` and the operation maps to a valid provider.
