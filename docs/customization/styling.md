# Styling

Brighten provides multiple ways to customize the editor's appearance.

## Theme

The simplest customization is choosing a theme:

```typescript
const editor = new EditorUI({
  container: '#editor',
  theme: 'dark',  // or 'light'
});
```

## Custom Styles

For deeper customization, use the `styles` option:

```typescript
const editor = new EditorUI({
  container: '#editor',
  theme: 'dark',
  styles: {
    primary: '#8b5cf6',        // Primary accent color
    primaryHover: '#7c3aed',   // Primary hover state
    background: '#0a0a0a',     // Main background
    surface: '#171717',        // Panel backgrounds
    surfaceHover: '#262626',   // Panel hover state
    border: '#262626',         // Border color
    text: '#fafafa',           // Primary text
    textSecondary: '#a3a3a3',  // Secondary text
    danger: '#ef4444',         // Danger/delete actions
    success: '#22c55e',        // Success states
    radius: '8px',             // Border radius
    fontFamily: 'Inter, system-ui, sans-serif'
  }
});
```

## Style Properties

| Property | Type | Description |
|----------|------|-------------|
| `primary` | `string` | Primary accent color (buttons, highlights) |
| `primaryHover` | `string` | Primary color hover state |
| `background` | `string` | Main editor background |
| `surface` | `string` | Panel and toolbar backgrounds |
| `surfaceHover` | `string` | Surface hover state |
| `border` | `string` | Border color |
| `text` | `string` | Primary text color |
| `textSecondary` | `string` | Secondary/muted text |
| `danger` | `string` | Danger actions (delete, etc.) |
| `success` | `string` | Success states |
| `radius` | `string` | Border radius (e.g., `'8px'`, `'0'`) |
| `fontFamily` | `string` | Font family |

## CSS Variables

Brighten sets CSS variables on the editor root element. You can also override these directly:

```css
.brighten-editor {
  --brighten-primary: #8b5cf6;
  --brighten-primary-hover: #7c3aed;
  --brighten-background: #0a0a0a;
  --brighten-surface: #171717;
  --brighten-surface-hover: #262626;
  --brighten-border: #262626;
  --brighten-text: #fafafa;
  --brighten-text-secondary: #a3a3a3;
  --brighten-danger: #ef4444;
  --brighten-success: #22c55e;
  --brighten-radius: 8px;
  --brighten-font: Inter, system-ui, sans-serif;
}
```

## Brand Examples

### Purple Theme

```typescript
styles: {
  primary: '#8b5cf6',
  primaryHover: '#7c3aed',
}
```

### Green Theme

```typescript
styles: {
  primary: '#22c55e',
  primaryHover: '#16a34a',
}
```

### Corporate Blue

```typescript
styles: {
  primary: '#2563eb',
  primaryHover: '#1d4ed8',
  radius: '4px',
  fontFamily: 'Arial, sans-serif'
}
```

### Rounded Minimal

```typescript
styles: {
  primary: '#000000',
  primaryHover: '#262626',
  background: '#ffffff',
  surface: '#f5f5f5',
  text: '#171717',
  textSecondary: '#737373',
  radius: '9999px',  // Fully rounded
}
```

## Unstyled Mode

For complete control, use `unstyled: true` to skip all default CSS injection:

```typescript
const editor = new EditorUI({
  container: '#editor',
  unstyled: true,  // No default styles
});
```

!!! warning "Unstyled Mode"
    When using `unstyled: true`, you're responsible for all styling. The editor will render but may look broken without custom CSS.

See [Headless Mode](headless.md) for building completely custom UIs.
