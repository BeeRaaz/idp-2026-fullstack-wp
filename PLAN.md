# IDP 2026 – WordPress Track Plan

Scope: IDP #5 (WP builders), #7 (commercial-ready WordPress), plus the cross-cutting goals #1 (estimation), #2 (code quality), #4 (AI usage) applied to this work.
Out of scope here: #3 Drupal/HubSpot, #6 Squarespace, headless CMS + Next.js (optional WP-as-headless bridge at the end).

## Repo layout (decide in Phase 0)

Track only custom code, not WP core.

```
.gitignore            # ignore wp-admin, wp-includes, core php files, uploads, wp-config.php
docs/
  estimates.md        # estimate vs actual log (IDP #1)
  ai-log.md           # AI usage cases + before/after time (IDP #4)
  builders-matrix.md  # builder comparison (IDP #5)
  wp-playbook.md      # migration, WPML, SQL, phpcs notes (IDP #7)
wp-content/
  themes/idp-builders-lab/   # demo pages/templates per builder
  plugins/idp-cpt-ajax/      # CPT + AJAX demo plugin
  mu-plugins/
```

## Phase 0 – Setup (Week 1)

- [ ] `.gitignore`, first commit, push to `origin` (main branch name: local is `master`, remote default likely `main`).
- [ ] Install tooling: Composer, WP-CLI, PHPCS + WordPress Coding Standards (`phpcs.xml`), optional pre-commit hook.
- [ ] Create `docs/estimates.md` template: task | estimate | buffer | actual | deviation % | notes.
- [ ] Create `docs/ai-log.md` template: task | AI used for | time before/after | quality notes.
- [ ] Break the plan below into tasks of ≤ 4h each, estimate them **before** starting, and review the estimates with the Tech Lead/PM (IDP #1).

## Phase 1 – Commercial-ready WordPress (IDP #7), Weeks 1–5

Build one realistic demo: a "Property/Events directory" style site that touches every item on the list.

| Wk | Topic | Deliverable | Done when |
|----|-------|-------------|-----------|
| 1 | PHPCS integration | `phpcs.xml` (WPCS), `composer phpcs` / `phpcbf` scripts, optionally a GitHub Action | All custom code passes with 0 errors |
| 2 | CPT + taxonomies + meta | Plugin `idp-cpt-ajax`: CPT, taxonomy, meta boxes/REST fields, archive + single templates | CPT works, sanitized/escaped, nonces in place |
| 2–3 | AJAX | Filter/load-more via `admin-ajax` **and** REST endpoint; compare both | Both variants work; notes on when to use which |
| 3 | WPML | Install WPML (or Polylang if no license), translate CPT/taxonomies/strings, `wpml-config.xml`, language switcher | 2+ languages incl. translated CPT and strings |
| 4 | SQL import/export | WP-CLI `db export/import`, `search-replace` (serialized-safe), handling of large dumps | Documented, repeatable script |
| 4–5 | Migration client ↔ staging | Practice local → staging → "client" (second Local site) and back: files, DB, URLs, uploads, media, wp-config differences, cache/permalink flush | Playbook with checklist + rollback steps |
| 5 | Study complex projects | Read 2–3 open-source complex plugins/themes (e.g. WooCommerce, an ACF-heavy theme, a multilingual site); note patterns | Short write-up of patterns to reuse |

Deliverables: working demo plugin/theme, `docs/wp-playbook.md`, short team share-out (satisfies "use-case recommendations documented or shared").

## Phase 2 – WordPress builders (IDP #5), Weeks 5–9

Pick at least 2 builders (suggest **Elementor** or **Divi**, plus **WPBakery** or **Beaver Builder**; add Gutenberg/block theme FSE as a baseline). Build the **same 3 pages** in each so the comparison is fair:
1. Landing page (hero, features grid, CTA, form)
2. Listing page driven by the CPT from Phase 1 (dynamic content)
3. Blog/single template with global header/footer

| Wk | Task | Deliverable |
|----|------|-------------|
| 5–6 | Research: pricing, market share, licensing, architecture (shortcodes vs JSON vs blocks), dev extensibility | Notes in `docs/builders-matrix.md` |
| 6–7 | Builder A: build the 3 pages, one custom widget/module | Demo + time taken |
| 7–8 | Builder B: same 3 pages, one custom widget/module | Demo + time taken |
| 8 | Baseline: same pages as native blocks / block theme | Demo |
| 9 | Measure + write-up | Matrix |

Comparison criteria: build time, flexibility, dynamic data support, performance (Lighthouse, page weight, DOM size), output cleanliness, lock-in on deactivation, maintainability/updates, scalability, dev extensibility, cost. End with "recommend builder X when…" per use case.

## Cross-cutting (apply throughout)

**Estimation (#1):** estimate every task up front with a ~20% buffer, log actuals daily, review the deviation weekly (Friday, 15 min); at weeks 4 and 9 share the log with PM/Tech Lead. Target: ≤ 10–20% deviation.

**Code quality (#2):** PHPCS clean before every commit, self-review checklist before each PR (escaping/sanitizing, nonces, capability checks, i18n, no hardcoded URLs, prefixes), one PR per feature, ask Tech Lead for early review on the migration playbook and the AJAX plugin.

**AI usage (#4):** use Claude for scaffolding, WPCS fixes, test data, SQL/search-replace scripts, and doc drafting. Log each case in `docs/ai-log.md` with time before/after. Claim only measured savings.

## Milestones

| When | Milestone |
|------|-----------|
| End Wk 1 | Repo pushed, tooling done, estimates logged |
| End Wk 3 | CPT + AJAX plugin reviewed by Tech Lead |
| End Wk 5 | WPML + migration playbook done |
| End Wk 8 | 2 builders + baseline demos complete |
| End Wk 9 | Builder matrix + playbook shared with team; final estimate-vs-actual review |

## Note on timeline

The IDP is dated June 2026 with 6-month goals, which lands around December 2026. This 9-week plan fits if you start now; if the deadline is firm, run Phase 2 research in parallel with Phase 1 weeks 3–5.

## Optional stretch

Expose the Phase 1 CPT via WPGraphQL/REST for the Next.js headless item.
