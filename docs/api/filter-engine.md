# FilterEngine

The filter processing engine for applying presets and adjustments to images.

## Constructor

```typescript
new FilterEngine()
```

## Methods

### applyPreset

Apply a named filter preset to image data.

```typescript
applyPreset(imageData: ImageData, preset: FilterPreset): ImageData
```

**Parameters:**

- `imageData` - Source ImageData from canvas
- `preset` - Preset name (see [Available Presets](#available-presets))

**Returns:** New ImageData with filter applied (original unchanged)

**Example:**

```typescript
const filterEngine = new FilterEngine();
const imageData = ctx.getImageData(0, 0, width, height);

const vintage = filterEngine.applyPreset(imageData, 'vintage');
ctx.putImageData(vintage, 0, 0);
```

### process

Apply custom adjustments to image data.

```typescript
process(imageData: ImageData, adjustments: FilterAdjustments): ImageData
```

**Parameters:**

- `imageData` - Source ImageData
- `adjustments` - Object with adjustment values

**Returns:** New ImageData with adjustments applied

**Example:**

```typescript
const adjusted = filterEngine.process(imageData, {
  brightness: 0.1,
  contrast: 0.2,
  saturation: -0.1
});
```

## Available Presets

| Preset | Description |
|--------|-------------|
| `original` | No changes (pass-through) |
| `vintage` | Warm, faded retro look |
| `noir` | Black and white with high contrast |
| `dramatic` | High contrast with enhanced saturation |
| `warm` | Warm color temperature shift |
| `cool` | Cool color temperature shift |
| `fade` | Lifted blacks, reduced contrast |
| `vibrant` | Boosted saturation and contrast |
| `muted` | Reduced saturation, soft look |
| `sepia` | Classic sepia tone |
| `chrome` | High contrast chrome look |
| `polaroid` | Polaroid-style processing |
| `lomo` | Lomography-inspired look |
| `clarendon` | Instagram Clarendon style |
| `gingham` | Instagram Gingham style |

## Adjustment Properties

| Property | Range | Default | Description |
|----------|-------|---------|-------------|
| `brightness` | -1 to 1 | 0 | Overall brightness |
| `contrast` | -1 to 1 | 0 | Image contrast |
| `saturation` | -1 to 1 | 0 | Color saturation |
| `exposure` | -1 to 1 | 0 | Exposure compensation |
| `highlights` | -1 to 1 | 0 | Highlight recovery/boost |
| `shadows` | -1 to 1 | 0 | Shadow lifting/crushing |
| `temperature` | -1 to 1 | 0 | Color temperature (warm/cool) |
| `tint` | -1 to 1 | 0 | Green/magenta tint |
| `sharpness` | 0 to 1 | 0 | Sharpening amount |
| `blur` | 0 to 50 | 0 | Gaussian blur radius |
| `vignette` | 0 to 1 | 0 | Vignette intensity |

## Example Usage

### Chained Adjustments

```typescript
const filterEngine = new FilterEngine();

// Start with a preset
let result = filterEngine.applyPreset(imageData, 'vintage');

// Then fine-tune
result = filterEngine.process(result, {
  brightness: 0.05,
  contrast: 0.1
});
```

### Creating Custom Presets

```typescript
const myPresets = {
  'moody': {
    brightness: -0.1,
    contrast: 0.2,
    saturation: -0.2,
    shadows: 0.1,
    vignette: 0.3
  },
  'bright-airy': {
    brightness: 0.15,
    contrast: -0.1,
    exposure: 0.1,
    highlights: -0.2,
    saturation: -0.1
  }
};

function applyCustomPreset(imageData: ImageData, presetName: string): ImageData {
  const filterEngine = new FilterEngine();
  return filterEngine.process(imageData, myPresets[presetName]);
}
```

### Real-time Preview

```typescript
const filterEngine = new FilterEngine();
const originalImageData = ctx.getImageData(0, 0, width, height);

// Store original for reset
let currentImageData = originalImageData;

// Slider change handler
brightnessSlider.addEventListener('input', (e) => {
  const value = parseFloat(e.target.value);
  currentImageData = filterEngine.process(originalImageData, {
    brightness: value
  });
  ctx.putImageData(currentImageData, 0, 0);
});
```

## Performance Notes

- FilterEngine processes pixels on the CPU using Canvas 2D
- For large images, consider processing on a web worker
- Avoid calling `process()` on every slider change - use debouncing or throttling
- Consider WebGL for real-time filter preview (future enhancement)
