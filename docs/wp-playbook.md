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
- Known gap: ordering by `meta_key` hides events with no date.
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

WPML vs Polylang (notes for later comparison):
- WPML: paid, translation management/workflow, string/ATE tooling, heavier. Polylang free: language/UI basics, translations edited by hand; Pro adds slugs translation, REST, etc.
- Both: translations are separate posts linked by language; content in default language is the "source".
## SQL import / export (WP-CLI)
## Migration: client <-> staging checklist and rollback
## Patterns from complex projects
