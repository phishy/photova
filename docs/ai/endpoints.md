# API Endpoints

Complete reference for the Brighten API endpoints.

## Base URL

```
http://localhost:3001
```

## Authentication

Currently, the API does not require authentication. In production, you should add authentication middleware.

## Endpoints

### Health Check

```http
GET /api/health
```

**Response:**
```json
{
  "status": "ok",
  "version": "1.0.0"
}
```

---

### Background Remove

Remove the background from an image.

```http
POST /api/v1/background-remove
```

**Request Body:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo..."
}
```

**Response:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo...",
  "metadata": {
    "provider": "replicate",
    "model": "cjwbw/rembg",
    "processingTime": 3500
  }
}
```

---

### Colorize

Add color to black and white images.

```http
POST /api/v1/colorize
```

**Request Body:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo..."
}
```

**Response:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo...",
  "metadata": {
    "provider": "replicate",
    "model": "arielreplicate/deoldify_image",
    "processingTime": 8500
  }
}
```

---

### Restore

Restore and enhance old or damaged photos.

```http
POST /api/v1/restore
```

**Request Body:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo..."
}
```

**Response:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo...",
  "metadata": {
    "provider": "replicate",
    "model": "flux-kontext-apps/restore-image",
    "processingTime": 12000
  }
}
```

---

### Unblur

Sharpen and deblur images.

```http
POST /api/v1/unblur
```

**Request Body:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo..."
}
```

**Response:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo...",
  "metadata": {
    "provider": "replicate",
    "model": "jingyunliang/swinir",
    "processingTime": 6000
  }
}
```

---

### Inpaint

Remove objects from images and fill with AI-generated content.

```http
POST /api/v1/inpaint
```

**Request Body:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo...",
  "mask": "data:image/png;base64,iVBORw0KGgo..."
}
```

The mask should be a black and white image where white areas indicate regions to inpaint.

**Response:**
```json
{
  "image": "data:image/png;base64,iVBORw0KGgo...",
  "metadata": {
    "provider": "replicate",
    "model": "stability-ai/stable-diffusion-inpainting",
    "processingTime": 15000
  }
}
```

## Error Responses

### 400 Bad Request

```json
{
  "error": "Missing required field: image"
}
```

### 500 Internal Server Error

```json
{
  "error": "Provider error: API rate limit exceeded"
}
```

### 504 Gateway Timeout

```json
{
  "error": "Operation timed out after 120000ms"
}
```

## Image Format

All images should be sent as base64-encoded data URLs:

```
data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...
```

Supported formats: PNG, JPEG, WebP

### Converting to Base64

**JavaScript:**
```javascript
// From file input
const file = input.files[0];
const reader = new FileReader();
reader.onload = () => {
  const base64 = reader.result;  // data:image/png;base64,...
};
reader.readAsDataURL(file);

// From canvas
const base64 = canvas.toDataURL('image/png');
```

## Rate Limits

Rate limits depend on your AI provider plan:

| Provider | Free Tier | Paid |
|----------|-----------|------|
| Replicate | ~10 req/min | Based on plan |
| remove.bg | 50/month | Based on plan |
| fal.ai | Varies | Based on plan |

## OpenAPI Spec

The full OpenAPI specification is available at:

```
GET /api/openapi.json
```

Or view the static spec in the repository at `packages/photova-api/openapi.json`.
