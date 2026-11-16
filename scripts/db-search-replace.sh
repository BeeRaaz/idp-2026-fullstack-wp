#!/usr/bin/env bash
# Serialized-safe URL/path replacement. DRY RUN by default.
# Run from Local's "Open site shell".
#
# Usage:
#   scripts/db-search-replace.sh <old> <new>           # dry run, shows counts
#   scripts/db-search-replace.sh <old> <new> --apply   # performs the replacement
#
# Example: scripts/db-search-replace.sh https://prod.example.com http://p2h-idp.local --apply
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

OLD="${1:-}"
NEW="${2:-}"
MODE="${3:-}"

if [[ -z "$OLD" || -z "$NEW" ]]; then
	echo "Usage: $0 <old> <new> [--apply]" >&2
	exit 1
fi

# wp search-replace unserializes, replaces, and re-serializes, so string lengths in
# serialized data (options, widgets, builder data) stay valid. Never use raw sed on a dump.
# --skip-columns=guid: GUIDs are permanent identifiers and must not change.
ARGS=(search-replace "$OLD" "$NEW" --all-tables-with-prefix --skip-columns=guid --report-changed-only)

if [[ "$MODE" == "--apply" ]]; then
	"$ROOT/scripts/db-export.sh" pre-search-replace
	wp "${ARGS[@]}"
	wp cache flush || true
	wp rewrite flush || true
else
	echo "DRY RUN (add --apply to write changes):"
	wp "${ARGS[@]}" --dry-run
fi
