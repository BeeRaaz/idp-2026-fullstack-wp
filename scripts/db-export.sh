#!/usr/bin/env bash
# Export the WordPress database to backups/ (gzipped, timestamped).
# Run from Local's "Open site shell" (so WP-CLI can reach the DB).
#
# Usage: scripts/db-export.sh [label]
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

LABEL="${1:-manual}"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_DIR="$ROOT/backups"
OUT="$OUT_DIR/db-${LABEL}-${STAMP}.sql.gz"

mkdir -p "$OUT_DIR"

# --single-transaction: consistent dump of InnoDB tables without locking.
# --default-character-set=utf8mb4: avoid mangling Nepali/emoji content.
wp db export - --single-transaction --default-character-set=utf8mb4 | gzip > "$OUT"

echo "Exported to: $OUT ($(du -h "$OUT" | cut -f1))"
