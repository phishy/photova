#!/bin/bash
set -e

cp -r ../../packages/brighten-api/pb_migrations ./pb_migrations

fly deploy

rm -rf ./pb_migrations
