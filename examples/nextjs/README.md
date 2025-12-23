# Brighten - Next.js Example

A minimal example of using Brighten with Next.js 14 (App Router).

## Setup

```bash
npm install
npm run dev
```

Then open http://localhost:3000 in your browser.

## Usage

This example demonstrates:

- Using `'use client'` directive for client-side rendering
- Proper SSR handling (editor only initializes on client)
- Loading images from file input
- Exporting edited images
- TypeScript integration

## Important Notes

- The editor requires client-side rendering, so the component uses `'use client'`
- `next.config.js` includes `transpilePackages: ['brighten']` for proper bundling
