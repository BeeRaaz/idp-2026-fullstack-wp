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
## WPML
## SQL import / export (WP-CLI)
## Migration: client <-> staging checklist and rollback
## Patterns from complex projects
