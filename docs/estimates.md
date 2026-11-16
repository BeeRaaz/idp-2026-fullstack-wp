# Estimate vs Actual Log (IDP #1)

Rules: break tasks to <= 4h, estimate BEFORE starting, add ~20% buffer, log actual daily.
Target deviation: within 10-20%. Weekly review on Fridays (15 min).
Initial estimates drafted with AI help (2026-11-16); adjust to your own pace.

Deviation % = (Actual - Estimate incl. buffer) / Estimate incl. buffer.

| Wk | Task | Type | Estimate (h) | Buffer (h) | Actual (h) | Deviation % | Notes / why |
|----|------|------|--------------|------------|------------|-------------|-------------|
| 1 | Phase 0: repo, .gitignore, PHPCS, doc templates | setup | 2.0 | 0.5 | | | |
| 1 | PHPCS: verify rules, composer scripts, optional GitHub Action | tooling | 2.0 | 0.5 | | | |
| 1 | Plugin skeleton `idp-cpt-ajax` + CPT/taxonomy registration | wp-dev | 2.5 | 0.5 | | | |
| 2 | Meta fields (register_post_meta + meta box, nonce, sanitize) | wp-dev | 3.0 | 0.5 | | | |
| 2 | Archive + single templates for the CPT | wp-dev | 3.0 | 0.5 | | | |
| 2 | AJAX filter via admin-ajax (nonce, sanitize, JS) | wp-dev | 4.0 | 1.0 | | | |
| 3 | AJAX filter via REST endpoint + comparison notes | wp-dev | 4.0 | 1.0 | | | |
| 3 | Review fixes from Tech Lead on CPT/AJAX | review | 2.0 | 0.5 | | | |
| 3 | WPML (or Polylang): install, config, translate CPT/taxonomy | wp-dev | 4.0 | 1.0 | | | |
| 3 | `wpml-config.xml`, string translation, language switcher | wp-dev | 3.0 | 0.5 | | | |
| 4 | WP-CLI db export/import script + large dump notes | devops | 2.5 | 0.5 | | | |
| 4 | search-replace (serialized-safe) + URL/path gotchas | devops | 2.0 | 0.5 | | | |
| 4 | Migration local -> staging (files, DB, wp-config) | devops | 4.0 | 1.0 | | | |
| 5 | Migration staging -> client + rollback steps, checklist | devops | 3.0 | 0.5 | | | |
| 5 | Study 2-3 complex open-source projects, write patterns | research | 4.0 | 1.0 | | | |
| 5 | Playbook cleanup + team share-out | docs | 3.0 | 0.5 | | | |
| 5-6 | Builders research (pricing, architecture, extensibility) | research | 4.0 | 1.0 | | | |
| 6 | Builder A: landing page | builder | 3.0 | 0.5 | | | |
| 6-7 | Builder A: CPT listing (dynamic) + blog/single template | builder | 4.0 | 1.0 | | | |
| 7 | Builder A: custom widget/module | builder | 4.0 | 1.0 | | | |
| 7 | Builder B: landing page | builder | 3.0 | 0.5 | | | |
| 7-8 | Builder B: CPT listing (dynamic) + blog/single template | builder | 4.0 | 1.0 | | | |
| 8 | Builder B: custom widget/module | builder | 4.0 | 1.0 | | | |
| 8 | Native blocks / FSE baseline: 3 pages | builder | 4.0 | 1.0 | | | |
| 9 | Lighthouse + page weight measurements for all 3 | analysis | 3.0 | 0.5 | | | |
| 9 | Builders matrix + recommendations write-up | docs | 3.0 | 0.5 | | | |
| 9 | Final estimate-vs-actual review with PM / Tech Lead | review | 1.5 | 0.5 | | | |

## Weekly review
- Week 1:
