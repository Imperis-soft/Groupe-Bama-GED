#!/bin/sh
# Build (linux/amd64) et publication des deux images sur Docker Hub.
#   ./docker/build-push.sh            → tag latest + tag daté (ex. 2026.10.02-1430)
#   ./docker/build-push.sh v1.4.0     → tag latest + v1.4.0
set -e

cd "$(dirname "$0")/.."

TAG="${1:-$(date +%Y.%m.%d-%H%M)}"
APP_IMAGE=imperissoft/grpbama
NGINX_IMAGE=imperissoft/grpbama-nginx

echo "→ Build ${APP_IMAGE}:${TAG}"
docker build --platform linux/amd64 --target app \
    -t "${APP_IMAGE}:${TAG}" -t "${APP_IMAGE}:latest" .

echo "→ Build ${NGINX_IMAGE}:${TAG}"
docker build --platform linux/amd64 --target nginx \
    -t "${NGINX_IMAGE}:${TAG}" -t "${NGINX_IMAGE}:latest" .

echo "→ Push"
docker push "${APP_IMAGE}:${TAG}"
docker push "${APP_IMAGE}:latest"
docker push "${NGINX_IMAGE}:${TAG}"
docker push "${NGINX_IMAGE}:latest"

echo "✓ Images publiées : ${TAG} et latest"
echo "  Sur le VPS : Portainer → stack grpbama → Pull and redeploy"
echo "  (ou : docker compose -p grpbama pull && docker compose -p grpbama up -d)"
