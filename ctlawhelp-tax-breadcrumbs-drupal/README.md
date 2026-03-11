# Legal Aid Tax Bread – Drupal Breadcrumb Overrides

Temporary companion plugin to rewrite Yoast breadcrumb links for `nsmi_category` to point at the Drupal site.

## What it does

- Hooks Yoast’s `wpseo_breadcrumb_links` filter.
- Only affects singular content: `post`, `legal_article`, `interactive_guide`.
- Only rewrites breadcrumbs where `taxonomy === nsmi_category`.
- Only runs when a post has `_primary_nsmi_category` set.
- Builds Drupal URLs like:
  - Top-level: `https://ctlawhelp.org/en/self-help/{TID}`
  - Child: `https://ctlawhelp.org/en/self-help/{PID}/{TID}`

## Configuration

- Enable/disable:
  - Set `LATB_DRUPAL_BREADCRUMBS_ENABLED` to `false` in `wp-config.php` or just deactivate the plugin.
- Base URL:
  - Set `LATB_DRUPAL_BREADCRUMBS_BASE` (defaults to `https://ctlawhelp.org/en/self-help`).

## Mapping table

This plugin uses a hardcoded mapping of **WordPress NSMI term slug → Drupal TID**.

Edit `legal-aid-tax-bread-drupal-breadcrumbs.php`:
- `latb_dbo_slug_to_tid_map()`
- `latb_dbo_tid_to_parent_tid_map()`

Important notes:
- WordPress slugs must match your `nsmi_category` term slugs.
- If you have duplicate-looking names in Drupal (e.g. Utilities), make sure the WordPress slugs are unique; add each slug to the map.

## Removal

Deactivate and delete this plugin once the WordPress section pages exist.
