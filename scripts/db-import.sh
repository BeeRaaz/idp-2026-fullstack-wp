#!/usr/bin/env bash
# Import a .sql or .sql.gz dump. Always takes a safety backup of the current DB first.
# Run from Local's "Open site shell".
#
# Usage: scripts/db-import.sh <dump.sql|dump.sql.gz> [--yes]
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

DUMP="${1:-}"
CONFIRM="${2:-}"

if [[ -z "$DUMP" || ! -f "$DUMP" ]]; then
	echo "Usage: $0 <dump.sql|dump.sql.gz> [--yes]" >&2
	exit 1
fi

if [[ "$CONFIRM" != "--yes" ]]; then
	read -r -p "This REPLACES the current database ($(wp config get DB_NAME)). Continue? [y/N] " ANSWER
	[[ "$ANSWER" == "y" || "$ANSWER" == "Y" ]] || { echo "Aborted."; exit 1; }
fi

echo "Taking safety backup first..."
"$ROOT/scripts/db-export.sh" pre-import

echo "Importing $DUMP ..."
if [[ "$DUMP" == *.gz ]]; then
	gunzip -c "$DUMP" | wp db import -
else
	wp db import "$DUMP"
fi

wp cache flush || true
wp rewrite flush || true
echo "Done. Next: run scripts/db-search-replace.sh <old-url> <new-url> if the domain changed."
