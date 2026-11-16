#!/usr/bin/env bash
# Package this site (DB + uploads) into a migration bundle for another environment.
# Run from the SOURCE site's Local shell.
#
# Usage: scripts/pack-migration.sh
# Output: backups/migration-<timestamp>/{db.sql.gz,uploads.tar.gz,manifest.env}
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

STAMP="$(date +%Y%m%d-%H%M%S)"
BUNDLE="$ROOT/backups/migration-${STAMP}"
mkdir -p "$BUNDLE"

wp db export - --single-transaction --default-character-set=utf8mb4 | gzip > "$BUNDLE/db.sql.gz"

if [[ -d "$ROOT/wp-content/uploads" ]]; then
	tar -czf "$BUNDLE/uploads.tar.gz" -C "$ROOT/wp-content" uploads
fi

{
	echo "SOURCE_URL=\"$(wp option get siteurl)\""
	echo "SOURCE_PATH=\"$ROOT\""
	echo "SOURCE_GIT_REF=$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"
	echo "CREATED=$STAMP"
} > "$BUNDLE/manifest.env"

echo "Bundle ready: $BUNDLE"
cat "$BUNDLE/manifest.env"
echo "Code is NOT in the bundle: it travels through git (push source, pull on target)."
