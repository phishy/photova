/**
 * @fileoverview Brighten API - Unified media processing API with configurable AI backends.
 * 
 * Routes image operations (background removal, colorization, restoration, etc.) to
 * providers like Replicate, fal.ai, or remove.bg based on YAML configuration.
 * 
 * @example
 * ```typescript
 * import { createServer, loadConfig } from 'brighten-api';
 * 
 * const config = loadConfig('./config.yaml');
 * const app = createServer(config);
 * app.listen(3001);
 * ```
 * 
 * @example
 * ```bash
 * # Start with default config.yaml
 * npx brighten-api
 * 
 * # Start with custom config
 * CONFIG_PATH=./custom.yaml npx brighten-api
 * ```
 * 
 * @packageDocumentation
 */

import { loadConfig } from './config/index.js';
import { startServer } from './server/index.js';

async function main() {
  const configPath = process.env.CONFIG_PATH || undefined;
  const config = loadConfig(configPath);
  
  console.log(`Starting Brighten API server...`);
  console.log(`Available operations: ${Object.keys(config.operations).join(', ')}`);
  
  await startServer(config);
}

main().catch((error) => {
  console.error('Failed to start server:', error);
  process.exit(1);
});

/** Load and validate YAML configuration with environment variable interpolation. */
export { loadConfig } from './config/index.js';

/** Create or start the Hono server with configured routes. */
export { createServer, startServer } from './server/index.js';

/** Routes operations to configured providers with fallback support. */
export { OperationRouter } from './router/index.js';

/** Operation types: 'background-remove' | 'colorize' | 'restore' | 'unblur' | 'inpaint' */
export * from './operations/types.js';

/** Provider implementations: ReplicateProvider, FalProvider, RemoveBgProvider */
export * from './providers/index.js';
