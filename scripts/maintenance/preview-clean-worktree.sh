#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

echo "[POS] Preview untracked files"
git clean -fdn

echo ""
echo "[POS] Preview ignored build artifacts"
git clean -fdXn

echo ""
echo "This script is PREVIEW ONLY."
echo "To execute cleanup manually, run one of:"
echo "  git clean -fd      # remove untracked files"
echo "  git clean -fdX     # remove ignored files only"
