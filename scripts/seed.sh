#!/usr/bin/env bash
# services.seed.json を wp-env のローカル WordPress に投入する。
# 前提: npx wp-env start 済み（Docker 必須）。
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> service データを投入します（wp eval-file）"
npx wp-env run cli wp eval-file scripts/import-seed.php

echo "==> 記事・法務ページを投入します（wp eval-file）"
npx wp-env run cli wp eval-file scripts/import-content.php

echo "==> 投入後の service 件数"
npx wp-env run cli wp post list --post_type=service --format=count
