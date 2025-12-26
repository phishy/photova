# Build from monorepo root: docker build -f packages/brighten-api/Dockerfile .
FROM node:20-alpine AS base

FROM base AS deps
RUN apk add --no-cache libc6-compat
WORKDIR /app

COPY package.json package-lock.json ./
COPY packages/brighten/package.json ./packages/brighten/
COPY packages/brighten-api/package.json ./packages/brighten-api/

RUN npm ci

FROM base AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY packages/brighten ./packages/brighten
COPY packages/brighten-api ./packages/brighten-api

WORKDIR /app/packages/brighten
RUN npm run build

WORKDIR /app/packages/brighten-api
ENV NEXT_TELEMETRY_DISABLED=1
RUN npm run build

FROM base AS runner
WORKDIR /app

ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

RUN apk add --no-cache wget unzip ca-certificates

ARG POCKETBASE_VERSION=0.25.9
RUN wget -q https://github.com/pocketbase/pocketbase/releases/download/v${POCKETBASE_VERSION}/pocketbase_${POCKETBASE_VERSION}_linux_amd64.zip \
    && unzip pocketbase_${POCKETBASE_VERSION}_linux_amd64.zip -d /usr/local/bin/ \
    && rm pocketbase_${POCKETBASE_VERSION}_linux_amd64.zip \
    && chmod +x /usr/local/bin/pocketbase

COPY --from=builder /app/packages/brighten-api/.next/standalone ./
COPY --from=builder /app/packages/brighten-api/.next/static ./packages/brighten-api/.next/static
COPY --from=builder /app/packages/brighten-api/pb_migrations ./pb_migrations

RUN mkdir -p /app/pb_data

COPY <<'EOF' /app/start.sh
#!/bin/sh
set -e

echo "Starting PocketBase..."
/usr/local/bin/pocketbase serve \
  --http=0.0.0.0:8090 \
  --dir=/app/pb_data \
  --migrationsDir=/app/pb_migrations &

echo "Waiting for PocketBase to be ready..."
for i in $(seq 1 30); do
  if wget -q --spider http://127.0.0.1:8090/api/health 2>/dev/null; then
    echo "PocketBase is ready!"
    break
  fi
  if [ $i -eq 30 ]; then
    echo "PocketBase failed to start"
    exit 1
  fi
  sleep 1
done

echo "Starting Next.js server..."
cd /app/packages/brighten-api
exec node server.js
EOF

RUN chmod +x /app/start.sh

EXPOSE 3001 8090

ENV PORT=3001
ENV HOSTNAME="0.0.0.0"
ENV POCKETBASE_URL="http://127.0.0.1:8090"

CMD ["/app/start.sh"]
