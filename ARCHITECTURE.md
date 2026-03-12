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

Later, additional shared NSMI helpers or shared admin menu logic may also move there.## Plugin Responsibility Map

This section describes what each custom CTLawHelp plugin is responsible for.

### ctlawhelp-core
Shared infrastructure used by multiple plugins.
- Registers the `nsmi_category` taxonomy
- Future home for shared NSMI helpers or utilities

### ctlawhelp-legal-aid-articles
Article content system.
- Registers the `legal_article` post type
- Provides admin UI and metadata related to articles
- Hosts the NSMI management admin screen

### ctlawhelp-nsmi-landing
Landing pages for top-level NSMI topics.
- Registers the `nsmi_landing` post type
- Renders NSMI landing page content (accordion, grids, etc.)
- Integrates with sidebars and Elementor layout

### ctlawhelp-sidebars
Sidebar management.
- Registers sidebar content
- Provides sidebar assignment UI for pages like NSMI landing pages

### ctlawhelp-tax-breadcrumbs
Breadcrumb logic based on NSMI hierarchy.
- Determines breadcrumb trails for articles and guides
- Uses `_primary_nsmi_category` metadata

### ctlawhelp-tax-breadcrumbs-drupal
Temporary compatibility layer.
- Overrides breadcrumb links to point to the legacy Drupal site
- Intended to be removed once the Drupal site is retired

### ctlawhelp-interactive-guides
Interactive legal guides system.
- Registers the `interactive_guide` post type
- Integrates guides with NSMI taxonomy

### ctlawhelp-permalinks
Custom URL handling.
- Generates URLs using language + NSMI hierarchy
- Handles custom path overrides
- Registers rewrite rules
- Uses cached rule map to avoid recomputing rules on every request

### ctlawhelp-menu-icons
Admin / navigation enhancement.
- Adds icons to menu items related to NSMI structure

### ctlawhelp-snippets
Reusable content blocks.
- Registers a snippets content type
- Allows snippets to be inserted via shortcode

### ctlawhelp-pii-masker
Gravity Forms privacy utility.
- Masks PII fields after submission
- Runs scheduled masking tasks