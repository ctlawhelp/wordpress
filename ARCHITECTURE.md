# CTLawHelp Plugin Architecture

## Core concept
The central shared concept across the plugin ecosystem is the `nsmi_category` taxonomy.

It is used by multiple CTLawHelp plugins and should be treated as a shared site-wide classification system, not as a feature belonging only to the legal articles plugin.

## Current custom plugins

### ctlawhelp-legal-aid-articles
Registers the `legal_article` post type and contains NSMI-related article admin functionality.

### ctlawhelp-sidebars
Handles sidebar assignment and sidebar rendering logic for NSMI-related content.

### ctlawhelp-nsmi-landing
Handles NSMI landing pages, currently being refactored toward a dedicated `nsmi_landing` content type.

### ctlawhelp-tax-breadcrumbs
Handles breadcrumb behavior tied to NSMI hierarchy.

### ctlawhelp-tax-breadcrumbs-drupal
Temporary/override logic for Drupal-style breadcrumb URLs.

### ctlawhelp-interactive-guides
Handles the `interactive_guide` content type and guide-related NSMI integrations.

### ctlawhelp-permalinks
Handles custom permalink behavior tied to NSMI structure.

### ctlawhelp-menu-icons
Handles menu icon behavior tied to NSMI/admin display.

### ctlawhelp-pii-masker
Masks PII in Gravity Forms workflows.

### ctlawhelp-snippets
Reusable snippet/content block system.

## Architectural concern
`nsmi_category` is currently registered inside `ctlawhelp-legal-aid-articles`, but multiple plugins depend on it.

This means the taxonomy currently lives in the wrong place architecturally.

## Target direction
Create a shared plugin called `ctlawhelp-core` and move shared NSMI taxonomy registration there first.

Later, additional shared NSMI helpers or shared admin menu logic may also move there.