import { z } from 'zod';

export const ProviderConfigSchema = z.object({
  api_key: z.string().optional(),
  endpoint: z.string().url().optional(),
  timeout: z.number().default(30000),
});

export const LocalProviderConfigSchema = ProviderConfigSchema.extend({
  gpu: z.boolean().default(false),
  models_path: z.string().default('./models'),
});

export const OperationConfigSchema = z.object({
  provider: z.string(),
  fallback: z.string().optional(),
  model: z.string().optional(),
  timeout: z.number().optional(),
  options: z.record(z.unknown()).optional(),
});

export const PocketBaseConfigSchema = z.object({
  url: z.string().url().optional().default('http://127.0.0.1:8090'),
  admin_email: z.string().optional(),
  admin_password: z.string().optional(),
});

export const AuthConfigSchema = z.object({
  enabled: z.boolean().default(false),
  pocketbase: PocketBaseConfigSchema.optional(),
});

export const ConfigSchema = z.object({
  server: z.object({
    port: z.number().default(3000),
    host: z.string().default('0.0.0.0'),
  }).default({}),

  auth: AuthConfigSchema.default({ enabled: false }),

  operations: z.record(OperationConfigSchema),

  providers: z.object({
    local: LocalProviderConfigSchema.optional(),
    replicate: ProviderConfigSchema.optional(),
    runpod: ProviderConfigSchema.optional(),
    fal: ProviderConfigSchema.optional(),
    removebg: ProviderConfigSchema.optional(),
  }).default({}),
});

export type Config = z.infer<typeof ConfigSchema>;
export type OperationConfig = z.infer<typeof OperationConfigSchema>;
export type ProviderConfig = z.infer<typeof ProviderConfigSchema>;
export type LocalProviderConfig = z.infer<typeof LocalProviderConfigSchema>;
export type PocketBaseConfig = z.infer<typeof PocketBaseConfigSchema>;
export type AuthConfig = z.infer<typeof AuthConfigSchema>;
