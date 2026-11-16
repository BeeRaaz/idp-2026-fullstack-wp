# Study: patterns from complex WordPress projects (IDP #7)

Projects read: WooCommerce 11.3.0, Advanced Custom Fields 6.8.11 (free), Polylang 3.9.2.
Method: source read with AI assistance (three parallel read-only passes), 2026-11-16.

> **Verify before relying on this.** These notes come from targeted reading and grep, not a full trace of each plugin. Open the cited files and confirm anything you plan to reuse or present. Note that ACF Blocks (`acf_register_block_type`) is Pro-only and not in the free build.

## Cross-project patterns (the short list)

| Pattern | Seen in | Reuse when |
|---|---|---|
| Thin plugin file + one main class with accessor (`WC()`, `acf()`, `PLL()`) | all | any plugin larger than one file |
| One "ready" action for add-ons (`woocommerce_init`, `acf/init`, `pll_init`) | all | others need a safe point to hook in |
| Filter every registration args array | WC, ACF | CPT/taxonomy args must be customizable by themes/add-ons |
| `pre_*` short-circuit filters (return non-null to skip core logic) | ACF, WC | expensive reads, testing, caching layers |
| Hooks before/after every write (dynamic names per object type) | WC | model save/delete lifecycle |
| Hidden taxonomy as a link table | Polylang | grouping objects that must stay queryable via WP_Query |
| Hidden `_key` meta holding the field key | ACF | a meta value needs type/definition info |
| Template override: theme folder first, then plugin; filter + version header | WC | client plugins with front-end output (we do a light version) |
| Registry + abstract base class with "override only what you need" | ACF field types | plugin-style extensions |
| Detect-then-hook compat modules, with a kill switch constant | Polylang | integrating with optional third-party plugins |
| Persist config as JSON in the theme (local JSON) | ACF | settings that must travel through git |
| One capability helper, one nonce per action, one sanitizer wrapper | WC, ACF | every request handler: capability -> nonce -> sanitize -> act |
| Group-versioned cache keys + `wp_cache_*` groups | WC, ACF | list/query caches that need cheap invalidation |
| Declarative REST schema generated from config | ACF, WC | many similar endpoints |

## WooCommerce
- **Bootstrap:** `woocommerce.php` is thin; `includes/class-woocommerce.php` holds the singleton, `define_constants()`, `includes()`, `init_hooks()`; ends `init()` with `do_action( 'woocommerce_init' )`.
- **Data layer:** CRUD objects (`WC_Data` -> `WC_Product`, `WC_Order`) with getters/setters and a `$changes` array; `save()` delegates to a data store chosen by factory `WC_Data_Store` (filter `woocommerce_data_stores`). Orders can live in custom tables (HPOS, `src/Internal/DataStores/Orders/`) while callers stay unchanged. Reuse: when an entity may leave post meta later, hide storage behind a store interface.
- **Hooks:** `woocommerce_` prefix; dynamic lifecycle hooks (`woocommerce_before_{$type}_object_save`); templates are built from small hooked functions so developers can `remove_action`; renamed hooks are mapped in `class-wc-deprecated-action-hooks.php`.
- **REST:** versioned controllers under `includes/rest-api/Controllers/`, generic CRUD base (`class-wc-rest-crud-controller.php`), `register_routes()`, `get_item_schema()`, `*_permissions_check`; capability mapping in one function (`wc_rest_check_post_permissions`).
- **Templates:** `wc_get_template()` -> `wc_locate_template()` (theme `woocommerce/` then plugin `templates/`), cached, filter `wc_get_template`, `@version` header in each template.
- **Security:** `wc_clean()` recursive sanitizer; per-action nonces in `class-wc-ajax.php`; capability checks first.
- **Structure:** PSR-4 in `src/` (+ small container, `RegisterHooksInterface::register()`), legacy `WC_*` naming autoloader in `includes/`.
- **Performance:** `WC_Cache_Helper::get_transient_version()` (bump version = invalidate group), object cache groups, Action Scheduler for background work.

## Advanced Custom Fields
- **Bootstrap:** "dummy constructor" + `initialize()` that includes files in dependency order; `acf()` accessor; `acf/init` action; `acf_did()` run-once guard.
- **Storage:** field groups, fields, post types, taxonomies are all posts (`acf-field-group`, `acf-field`, ...), settings serialized in `post_content`; **local JSON** (`includes/local-json.php`) mirrors them to `acf-json/` with filterable paths (`acf/json/save_paths`).
- **Field types:** subclass `acf_field` (`includes/fields/class-acf-field.php`), set name/label/defaults in `initialize()`, override `render_field`, `load_value`, `update_value`, `format_value`, `validate_value`; register with `acf_register_field_type()` on `acf/include_field_types`. Methods are auto-wired as filters.
- **Hooks:** `acf/<noun>/<event>` naming; scoped filters (`acf/load_field/type=text`, `/name=`, `/key=`); `acf/pre_*` short circuits; one settings registry (`acf_get_setting`).
- **Values:** value stored as `hero_title`, field key as `_hero_title`; `post_id` strings encode object (`user_5`, `term_3`, `option`); per-request store (`acf_get_store`) in front of `wp_cache` group `acf`.
- **Admin:** generic controller for all internal post types; context-mapped asset enqueueing (admin vs front end).
- **Security:** single-use nonce (`acf_verify_nonce` blanks it after use); `acf_current_user_can_admin()`; `acf_current_user_can_read_post()` so fields cannot leak restricted content; escaping helper with allowed tags.
- **REST/blocks:** one `acf` property via `register_rest_field`, schema built from field groups; Block Bindings in `src/Blocks/Bindings.php`.

## Polylang
- **Context split:** `Polylang::init()` (plugins_loaded, priority 1) picks one of `PLL_Settings`, `PLL_Admin`, `PLL_REST_Request`, `PLL_Frontend`; class name filterable (`pll_context`). Modules/integrations are auto-generated load lists.
- **Data model:** languages are terms in taxonomy `language`; a post's language is a term relationship; each translation group is one term in `post_translations` whose description is a serialized `lang => id` array. Reuses cached, indexed core tables, no schema migration.
- **Language detection:** URL directory/subdomain/domain/content, then cookie, then `Accept-Language`, then default. `PLL_Query` adds the `lang` query var (`'lang' => ''` or `all` disables the filter), turned into a `tax_query`.
- **API:** `pll_current_language`, `pll_languages_list`, `pll_get_post`, `pll_set_post_language`, `pll_save_post_translations`, `pll_register_string` / `pll__`. Compat filters: `pll_get_post_types`, `pll_get_taxonomies`, `pll_copy_post_metas`, `pll_rel_hreflang_attributes`. Always guard with `function_exists`.
- **wpml-config.xml:** parsed in `src/modules/wpml/wpml-config.php` (needs the `simplexml` extension): custom types, taxonomies, custom fields (copy/translate/ignore), admin texts.
- **Integrations:** one folder per plugin (`src/integrations/wpseo/`), detect constant on `plugins_loaded` priority 0, hook on `pll_init`; kill switch `PLL_PLUGINS_COMPAT`.
- **URLs:** strategy classes for directory/subdomain/domain; rewrite rules prefixed with the language; flush rewrites after changing translated CPTs/taxonomies.
- **Pitfalls:** page caches must vary by language URL, not cookie; AJAX counts as front end unless `pll_ajax_backend` is sent; in REST, WP-CLI and cron there is no reliable current language, so pass `lang` explicitly; code needing `pll_*` should wait for `pll_init` (API loads at plugins_loaded priority 1).

## How this applies to our `idp-cpt-ajax` plugin
- Already matches: sanitize + nonce + capability order; `lang` passed explicitly in AJAX/REST (Polylang pitfall); `function_exists` guards; overridable templates; filters-based CPT registration (could be filterable more).
- Candidate improvements (ideas, not done):
  1. Add `idp_cpt_ajax_*` filters on the CPT/taxonomy args arrays and the query args (WooCommerce/ACF pattern).
  2. Add a version header to templates and a `idp-cpt-ajax/ready` action for add-ons.
  3. Cache the filter query results in a versioned group (bump on `save_post_idp_event`).
  4. Add a `pre_` short-circuit filter in `idp_cpt_ajax_query_events()` for testing.
  5. Hook Polylang-specific code on `pll_init` rather than relying on `function_exists` at call time.
