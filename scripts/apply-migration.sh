#!/usr/bin/env bash
# Apply a migration bundle on the TARGET site. Run from the TARGET site's Local shell,
# inside its public/ folder (so WP-CLI uses the target database).
#
# Usage: scripts/apply-migration.sh <bundle-dir> <target-url> [--yes]
#   e.g. scripts/apply-migration.sh ../../../p2h-idp-source/app/public/backups/migration-2026... http://p2h-idp-staging.local
#
# Rollback point: db-import.sh takes a "pre-import" backup of the target DB first.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BUNDLE="${1:-}"
TARGET_URL="${2:-}"
CONFIRM="${3:-}"

if [[ -z "$BUNDLE" || -z "$TARGET_URL" || ! -f "$BUNDLE/manifest.env" ]]; then
	echo "Usage: $0 <bundle-dir> <target-url> [--yes]" >&2
	exit 1
fi

# Read the manifest as data (do NOT `source` it: values can contain spaces).
manifest_get() {
	grep -m1 "^$1=" "$BUNDLE/manifest.env" | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'
}
SOURCE_URL="$(manifest_get SOURCE_URL)"
SOURCE_PATH="$(manifest_get SOURCE_PATH)"

if [[ -z "$SOURCE_URL" || -z "$SOURCE_PATH" ]]; then
	echo "manifest.env is missing SOURCE_URL or SOURCE_PATH." >&2
	exit 1
fi
TARGET_URL="${TARGET_URL%/}"

if [[ "$SOURCE_PATH" == "$ROOT" ]]; then
	echo "Source and target are the same site. Refusing to continue." >&2
	exit 1
fi

echo "Source: $SOURCE_URL ($SOURCE_PATH)"
echo "Target: $TARGET_URL ($ROOT)"

# 1. Database (takes a pre-import backup = rollback point).
"$ROOT/scripts/db-import.sh" "$BUNDLE/db.sql.gz" ${CONFIRM:+"$CONFIRM"}

# 2. Uploads (additive; does not delete files that only exist on the target).
if [[ -f "$BUNDLE/uploads.tar.gz" ]]; then
	mkdir -p "$ROOT/wp-content"
	tar -xzf "$BUNDLE/uploads.tar.gz" -C "$ROOT/wp-content"
fi

# 3. URLs and absolute paths (serialized-safe). Cover the https variant of the source too.
"$ROOT/scripts/db-search-replace.sh" "$SOURCE_URL" "$TARGET_URL" --apply
SOURCE_HTTPS="${SOURCE_URL/http:\/\//https://}"
if [[ "$SOURCE_HTTPS" != "$SOURCE_URL" ]]; then
	"$ROOT/scripts/db-search-replace.sh" "$SOURCE_HTTPS" "$TARGET_URL" --apply
fi
"$ROOT/scripts/db-search-replace.sh" "$SOURCE_PATH" "$ROOT" --apply

# 4. Post-migration checks.
wp rewrite flush
echo "--- Checks ---"
echo "siteurl: $(wp option get siteurl)"
echo "home:    $(wp option get home)"
wp plugin list --fields=name,status,version
echo "Next: install missing plugins (e.g. wp plugin install polylang --activate), pull code via git, then test /events/ and /ne/events/."
