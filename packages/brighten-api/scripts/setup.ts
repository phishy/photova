#!/usr/bin/env tsx
import { execSync, spawn } from 'child_process';
import { existsSync, mkdirSync, copyFileSync, chmodSync } from 'fs';
import { platform, arch } from 'os';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import PocketBase from 'pocketbase';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT_DIR = join(__dirname, '..');
const PB_DIR = join(ROOT_DIR, 'pb_data');
const PB_BINARY = join(ROOT_DIR, 'pocketbase');

const PB_VERSION = '0.27.2';

function getPocketBaseDownloadUrl(): string {
  const os = platform();
  const cpu = arch();

  let osName: string;
  let archName: string;

  switch (os) {
    case 'darwin':
      osName = 'darwin';
      break;
    case 'linux':
      osName = 'linux';
      break;
    case 'win32':
      osName = 'windows';
      break;
    default:
      throw new Error(`Unsupported OS: ${os}`);
  }

  switch (cpu) {
    case 'x64':
      archName = 'amd64';
      break;
    case 'arm64':
      archName = 'arm64';
      break;
    default:
      throw new Error(`Unsupported architecture: ${cpu}`);
  }

  return `https://github.com/pocketbase/pocketbase/releases/download/v${PB_VERSION}/pocketbase_${PB_VERSION}_${osName}_${archName}.zip`;
}

async function downloadPocketBase(): Promise<void> {
  if (existsSync(PB_BINARY)) {
    console.log('PocketBase binary already exists, skipping download');
    return;
  }

  const url = getPocketBaseDownloadUrl();
  console.log(`Downloading PocketBase from ${url}...`);

  const zipPath = join(ROOT_DIR, 'pocketbase.zip');

  execSync(`curl -L -o "${zipPath}" "${url}"`, { stdio: 'inherit' });
  execSync(`unzip -o "${zipPath}" -d "${ROOT_DIR}"`, { stdio: 'inherit' });
  execSync(`rm "${zipPath}"`, { stdio: 'inherit' });

  if (platform() !== 'win32') {
    chmodSync(PB_BINARY, 0o755);
  }

  console.log('PocketBase downloaded successfully');
}

async function startPocketBase(): Promise<{ process: ReturnType<typeof spawn>; url: string }> {
  const url = process.env.POCKETBASE_URL || 'http://127.0.0.1:8090';
  const port = new URL(url).port || '8090';

  if (!existsSync(PB_DIR)) {
    mkdirSync(PB_DIR, { recursive: true });
  }

  console.log(`Starting PocketBase on port ${port}...`);

  const pbProcess = spawn(PB_BINARY, ['serve', `--http=0.0.0.0:${port}`, `--dir=${PB_DIR}`], {
    stdio: ['ignore', 'pipe', 'pipe'],
    detached: false,
  });

  pbProcess.stdout?.on('data', (data) => {
    const msg = data.toString().trim();
    if (msg) console.log(`[PocketBase] ${msg}`);
  });

  pbProcess.stderr?.on('data', (data) => {
    const msg = data.toString().trim();
    if (msg) console.error(`[PocketBase] ${msg}`);
  });

  await new Promise((resolve) => setTimeout(resolve, 2000));

  return { process: pbProcess, url };
}

async function createAdminIfNeeded(pb: PocketBase, email: string, password: string): Promise<void> {
  try {
    await pb.collection('_superusers').authWithPassword(email, password);
    console.log('Admin already exists, authenticated successfully');
    return;
  } catch {
    console.log('Creating new admin account via CLI...');
  }

  try {
    execSync(`"${PB_BINARY}" superuser upsert "${email}" "${password}" --dir="${PB_DIR}"`, {
      stdio: 'inherit',
    });
    console.log('Admin account created');

    await new Promise((resolve) => setTimeout(resolve, 1000));

    await pb.collection('_superusers').authWithPassword(email, password);
    console.log('Authenticated as admin');
  } catch (err) {
    console.log('Failed to create admin:', (err as Error).message);
    throw err;
  }
}

async function setupCollections(pb: PocketBase): Promise<void> {
  console.log('Setting up collections...');

  const collections = await pb.collections.getFullList();
  const existingNames = collections.map((c) => c.name);
  const apiKeysCollection = collections.find((c) => c.name === 'api_keys');

  if (!existingNames.includes('api_keys')) {
    try {
      await pb.collections.create({
        name: 'api_keys',
        type: 'base',
        fields: [
          { type: 'relation', name: 'user', required: true, collectionId: '_pb_users_auth_', maxSelect: 1 },
          { type: 'text', name: 'name', required: true },
          { type: 'text', name: 'keyHash', required: true },
          { type: 'text', name: 'keyPrefix', required: true },
          { type: 'select', name: 'status', required: true, values: ['active', 'revoked'] },
          { type: 'json', name: 'scopes', required: false },
          { type: 'date', name: 'lastUsedAt', required: false },
          { type: 'date', name: 'expiresAt', required: false },
        ],
        listRule: '@request.auth.id = user.id',
        viewRule: '@request.auth.id = user.id',
        createRule: '@request.auth.id != ""',
        updateRule: '@request.auth.id = user.id',
        deleteRule: '@request.auth.id = user.id',
      });
      console.log('  Created api_keys collection');
    } catch (e) {
      console.log('  api_keys:', (e as Error).message);
    }
  } else {
    console.log('  api_keys already exists');
  }

  const apiKeysId = apiKeysCollection?.id || (await pb.collections.getOne('api_keys')).id;

  if (!existingNames.includes('usage_logs')) {
    try {
      await pb.collections.create({
        name: 'usage_logs',
        type: 'base',
        fields: [
          { type: 'relation', name: 'user', required: true, collectionId: '_pb_users_auth_', maxSelect: 1 },
          { type: 'relation', name: 'apiKey', required: true, collectionId: apiKeysId, maxSelect: 1 },
          { type: 'text', name: 'operation', required: true },
          { type: 'select', name: 'status', required: true, values: ['success', 'error'] },
          { type: 'number', name: 'latencyMs', required: true },
          { type: 'text', name: 'requestId', required: true },
          { type: 'text', name: 'errorMessage', required: false },
          { type: 'json', name: 'metadata', required: false },
        ],
        listRule: '@request.auth.id = user.id',
        viewRule: '@request.auth.id = user.id',
        createRule: null,
        updateRule: null,
        deleteRule: null,
      });
      console.log('  Created usage_logs collection');
    } catch (e) {
      console.log('  usage_logs:', (e as Error).message);
    }
  } else {
    console.log('  usage_logs already exists');
  }

  if (!existingNames.includes('usage_daily')) {
    try {
      await pb.collections.create({
        name: 'usage_daily',
        type: 'base',
        fields: [
          { type: 'relation', name: 'user', required: true, collectionId: '_pb_users_auth_', maxSelect: 1 },
          { type: 'text', name: 'date', required: true },
          { type: 'text', name: 'operation', required: true },
          { type: 'number', name: 'requestCount', required: true },
          { type: 'number', name: 'errorCount', required: true },
          { type: 'number', name: 'totalLatencyMs', required: true },
        ],
        listRule: '@request.auth.id = user.id',
        viewRule: '@request.auth.id = user.id',
        createRule: null,
        updateRule: null,
        deleteRule: null,
      });
      console.log('  Created usage_daily collection');
    } catch (e) {
      console.log('  usage_daily:', (e as Error).message);
    }
  } else {
    console.log('  usage_daily already exists');
  }

  const usersCollection = collections.find((c) => c.name === 'users');
  if (usersCollection) {
    const existingFieldNames = (usersCollection.fields || []).map((f: { name: string }) => f.name);
    const newFields = [...(usersCollection.fields || [])];
    let needsUpdate = false;

    if (!existingFieldNames.includes('plan')) {
      newFields.push({ type: 'select', name: 'plan', required: false, values: ['free', 'pro', 'enterprise'] } as never);
      needsUpdate = true;
    }
    if (!existingFieldNames.includes('monthlyLimit')) {
      newFields.push({ type: 'number', name: 'monthlyLimit', required: false } as never);
      needsUpdate = true;
    }

    if (needsUpdate) {
      try {
        await pb.collections.update(usersCollection.id, { fields: newFields });
        console.log('  Updated users collection with plan/monthlyLimit fields');
      } catch (e) {
        console.log('  users update:', (e as Error).message);
      }
    } else {
      console.log('  users collection already has required fields');
    }
  }
}

async function copyConfigIfNeeded(): Promise<void> {
  const configPath = join(ROOT_DIR, 'config.yaml');
  const examplePath = join(ROOT_DIR, 'config.example.yaml');

  if (!existsSync(configPath) && existsSync(examplePath)) {
    copyFileSync(examplePath, configPath);
    console.log('Created config.yaml from config.example.yaml');
  }
}

async function main(): Promise<void> {
  console.log('='.repeat(60));
  console.log('Brighten API Setup');
  console.log('='.repeat(60));
  console.log('');

  const { config } = await import('dotenv');
  config({ path: join(ROOT_DIR, '.env.local') });

  const adminEmail = process.env.POCKETBASE_ADMIN_EMAIL;
  const adminPassword = process.env.POCKETBASE_ADMIN_PASSWORD;

  if (!adminEmail || !adminPassword) {
    console.error('Error: POCKETBASE_ADMIN_EMAIL and POCKETBASE_ADMIN_PASSWORD must be set');
    console.error('Copy .env.example to .env.local and fill in your values');
    process.exit(1);
  }

  await copyConfigIfNeeded();

  console.log('\n[1/4] Downloading PocketBase...');
  await downloadPocketBase();

  console.log('\n[2/4] Starting PocketBase...');
  const { process: pbProcess, url } = await startPocketBase();

  try {
    console.log('\n[3/4] Creating admin account...');
    const pb = new PocketBase(url);
    pb.autoCancellation(false);
    await createAdminIfNeeded(pb, adminEmail, adminPassword);

    console.log('\n[4/4] Setting up collections...');
    await setupCollections(pb);

    console.log('\n' + '='.repeat(60));
    console.log('Setup complete!');
    console.log('='.repeat(60));
    console.log('');
    console.log('Next steps:');
    console.log('  1. Edit config.yaml to set auth.enabled: true (if you want auth)');
    console.log('  2. Run: npm run dev');
    console.log('');
    console.log('URLs:');
    console.log(`  API:            http://localhost:3001`);
    console.log(`  Dashboard:      http://localhost:3001/dashboard`);
    console.log(`  PocketBase:     ${url}/_/`);
    console.log('');
  } finally {
    pbProcess.kill();
    console.log('PocketBase stopped');
  }
}

main().catch((err) => {
  console.error('Setup failed:', err.message);
  process.exit(1);
});
