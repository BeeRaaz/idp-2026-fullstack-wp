# WordPress Playbook (IDP #7)

## PHPCS
- Tooling: `composer install`, then `composer phpcs` (check) / `composer phpcbf` (auto-fix). Config in `phpcs.xml` (WordPress, WordPress-Extra, WordPress-Docs, PHPCompatibilityWP), scanning only custom code.
- Gotchas hit so far:
  - The sanitization sniff doesn't recognize custom sanitizers: wrap input in `sanitize_text_field( wp_unslash( ... ) )` before passing it to your own function.
  - File naming is enforced: lowercase-hyphen names, and class files must be `class-<name>.php`. A template for a CPT named `idp_event` must be `single-idp-event.php` (loaded via `template_include`, not the theme hierarchy).
  - Meta/tax queries need a `phpcs:ignore WordPress.DB.SlowDBQuery...` with a reason; keep result sets small.
- WP-CLI can't reach Local's DB from a normal terminal; use Local's "Open site shell".
## CPT / taxonomies / meta
- Plugin `idp-cpt-ajax`: CPT `idp_event` (archive slug `events`), taxonomy `idp_event_type`, meta `idp_event_date|location|capacity` via `register_post_meta` (REST-exposed, sanitized) and a nonce-protected meta box.
- Flush rewrite rules on activation/deactivation, not on every `init`.
- Templates ship in the plugin and can be overridden in a theme at `idp-cpt-ajax/<file>`. Block themes need the header/footer built with `do_blocks()` template parts (see `includes/templates.php`).
- Ordering: a plain `meta_key` + `orderby=meta_value` query silently drops posts without that meta. Fix used: meta_query `relation OR` (key EXISTS + key NOT EXISTS) with a named clause (`idp_date`) for ordering, plus a `posts_orderby` filter to push undated events last.
- Filter state lives in the URL (`?search=&type=&date_from=`): the archive template reads and sanitizes GET values, so links are shareable and the form works without JS; JS keeps the URL in sync with `history.replaceState`.
## AJAX (admin-ajax vs REST)
Same filter implemented both ways, sharing one query function (`idp_cpt_ajax_query_events`). Switch in the browser with `/events/?transport=rest`.

| | admin-ajax | REST (`/wp-json/idp/v1/events`) |
|---|---|---|
| Method | POST, `action=idp_filter_events` | GET with query params |
| Security | Nonce (`check_ajax_referer`) | `permission_callback` (public read here); cookie nonce only for authenticated writes |
| Validation | Manual sanitizing in handler | Declarative `args` (type, default, minimum, sanitize) |
| Caching | POST, not cacheable | GET, cacheable by CDN/browser, has `X-WP-Total` headers |
| Speed | Loads full admin-ajax bootstrap | Generally lighter, no admin context |
| Reuse | Tied to WP front end | Usable by JS apps, mobile, headless (Next.js) |

Rule of thumb: use REST for new work, especially public reads and anything an external app may consume; use admin-ajax for legacy codebases, or quick wp-admin-only actions.
## Multilingual: Polylang (WPML substitute)
No WPML license, so Polylang (free, 3.9.2) is used. Plugin is installed in `wp-content/plugins/polylang` (not tracked in git; reinstall with `wp plugin install polylang --activate`).

Setup checklist (manual, in wp-admin):
1. Activate Polylang, add English (default) and Nepali (ne_NP).
2. Languages > Settings: confirm Events and Event Types are enabled for translation (the plugin forces them on via `pll_get_post_types` / `pll_get_taxonomies`).
3. Save Permalinks (Settings > Permalinks), then check `/events/` and `/ne/events/`.
4. Translate the event types first, then each event (use the "+" in the Languages column).
5. Languages > Translations: translate the registered string "Browse our upcoming events."

What the code does (`includes/polylang.php`, all no-ops without Polylang):
- Makes CPT and taxonomy translatable.
- Adds a language switcher on the archive (`pll_the_languages`).
- Passes `lang` into the shared query, the admin-ajax handler, and the REST endpoint so AJAX results follow the page language (validated against `pll_languages_list()`).
- Registers a custom string with `pll_register_string` and reads it with `pll__()`.
- UI strings come from the plugin's own `.po/.mo` (`languages/`, generated with `wp i18n make-pot`; `idp-cpt-ajax-ne_NP.po` is a draft translation, review before real use).
- `wpml-config.xml` at the plugin root: Polylang reads this WPML-format file for post types, taxonomies and custom-field behavior, so the same plugin is portable to WPML. It overlaps with the PHP filters; the filters are the guaranteed path.

Seeding translations (WP-CLI, run from Local's "Open site shell"):
- `wp idp seed-events --count=12 --translate`: create events plus linked Nepali copies.
- `wp idp translate-events`: translate existing events that have no Nepali version yet (also assigns the default language to events/types with none). Safe to re-run.
- Linking uses `pll_set_post_language` + `pll_save_post_translations` (and the term equivalents); meta (date, capacity) is copied, location is translated.

WPML vs Polylang (notes for later comparison):
- WPML: paid, translation management/workflow, string/ATE tooling, heavier. Polylang free: language/UI basics, translations edited by hand; Pro adds slugs translation, REST, etc.
- Both: translations are separate posts linked by language; content in default language is the "source".
## SQL import / export (WP-CLI)
Scripts in `scripts/` (run from Local's "Open site shell"; dumps go to `backups/`, which is git-ignored because dumps contain user data):
- `scripts/db-export.sh [label]`: gzipped, timestamped dump. Uses `--single-transaction` (no table locks) and `utf8mb4` (keeps Nepali text intact).
- `scripts/db-import.sh <dump> [--yes]`: asks for confirmation, takes a `pre-import` safety backup, imports `.sql` or `.sql.gz`, then flushes cache and rewrite rules.
- `scripts/db-search-replace.sh <old> <new> [--apply]`: dry run by default; `--apply` takes a `pre-search-replace` backup first.

Rules of thumb:
- Always export before import or search-replace. Never edit a dump with `sed`: serialized data stores string lengths and breaks.
- `wp search-replace` unserializes, replaces, re-serializes. Skip the `guid` column (identifiers, not links).
- Replace both `http://` and `https://` variants and, if needed, the filesystem path (`/Users/.../public` -> server path), because builders and plugins store absolute paths.
- Large dumps: use `gzip`; if the import fails with "max_allowed_packet", raise it on the DB server; for very large DBs consider `mysql < dump.sql` directly, or dump per table (`wp db export --tables=...`).
- After import: re-save permalinks (`wp rewrite flush`), clear caches, check `siteurl`/`home` (`wp option get siteurl`), and test a few pages/forms.
- Reminder: don't commit `wp-config.php` or dumps; migrate secrets separately.
## Migration: client <-> staging checklist and rollback
Three things move separately: **code** (git), **database** and **uploads** (bundle). Core and third-party plugins are reinstalled, not copied.

### One-time: create the target site
1. Create a new Local site (e.g. `p2h-idp-staging`) with the same PHP/WP version.
2. In its `public/` folder, in Local's shell:
   `git init && git remote add origin git@github.com:BeeRaaz/idp-2026-fullstack-wp.git && git fetch && git checkout -f main`
   (the `.gitignore` keeps WP core untracked, so only custom code is pulled).
3. Install untracked plugins: `wp plugin install polylang --activate`, then activate `idp-cpt-ajax`.

### Migrate (local -> staging)
1. Source: commit and push code (`git push`).
2. Source shell: `scripts/pack-migration.sh` -> creates `backups/migration-<stamp>/` (DB, uploads, manifest with the source URL/path).
3. Target shell: `git pull`.
4. Target shell (quote the path, it contains spaces; do not type the angle brackets, they are placeholders):
   `scripts/apply-migration.sh "/Users/birajdulal/Local Sites/p2h-idp-june-2026-fullstack-wp/app/public/backups/migration-20261116-153000" http://p2h-idp-staging.local`
   (list the real bundle folder name first with `ls` on the source site's `backups/` folder). It imports the DB (with a pre-import backup), unpacks uploads, replaces URLs (http + https) and absolute paths, flushes rewrites, and prints siteurl/home/plugins.
5. Verify: front page, `/events/`, `/ne/events/`, language switcher, AJAX filter and `?transport=rest`, a single event, wp-admin login, media library images, permalinks.

### Migrate (staging -> client)
Same steps with staging as the source. Extra care for a real client: lock staging first (maintenance mode) so no content is lost, back up the client site separately, confirm PHP/DB versions, make sure `wp-config.php` secrets/salts and `WP_HOME`/`WP_SITEURL` are set for the new host, disable search-engine blocking if going live, and handle caching/CDN purge.

### Rollback
- DB: `scripts/db-import.sh backups/db-pre-import-<stamp>.sql.gz` (the backup taken just before the import).
- Uploads: extraction is additive, so rollback only needs deleting files you know were added; if in doubt restore from the target's own backup before starting.
- Code: `git checkout <previous-commit>` (or `git revert`) and flush caches.
- Decide go/no-go points before starting: if verification fails at step 5, roll back immediately and investigate on the copy.

### Gotchas to watch for
- Paths with spaces (Local uses `Local Sites/`): quote every path in shell commands and never `source` a file containing them. A real migration bug caught during the first staging run (`manifest.env` parsing) came from this.
- Placeholders like `<bundle-dir>` in docs are not literal: zsh reads `<...>` as redirection.
- Hardcoded URLs in theme/plugin files or builder JSON (search-replace handles DB only).
- Missing untracked plugins/licences, different PHP version, memory/upload limits.
- File permissions and ownership on the client server.
- Polylang: language URLs/slugs live in options; re-save permalinks and check `/ne/`.
## Patterns from complex projects
See [complex-projects-study.md](complex-projects-study.md): WooCommerce, ACF and Polylang (bootstrap, data layer, hooks, REST, security, caching), with a list of ideas for our plugin.
