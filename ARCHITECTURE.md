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

### Legal Aid Article

A Legal Aid Article is a primarily text-based self-help page on CTLawHelp that explains one complete legal topic or legal task.

Examples:
- how to represent yourself in an eviction case
- how to get debt collectors to stop contacting you
- how to apply for a restraining order

Characteristics:
- long-form informational content
- stands on its own
- may include collapsible sections
- may include pamphlet links, help blocks, and feedback
- not primarily interactive
- distinct from Interactive Guides, which are decision-based tools

Article Structure
Required
Title
Breadcrumbs / category context
Last reviewed date
Body (main content)
Optional
Pamphlet links
Attribution (toggleable)
Global (not part of article model)
Get Help block (footer/global component)
Feedback survey (global component below content)
Article Body Layout
Articles begin with an intro area using standard Gutenberg blocks.
The intro appears before any Section blocks.
The intro is not part of the section system.
Section blocks are used for all structured content after the intro.
TOC and accordion behavior apply only to Section blocks, not the intro.
The intro should be visually distinct using subtle spacing (not a special component).
Article Sections (Body Builder)
Articles will be built using a custom Gutenberg Section block.
Section blocks will be available only for Legal Aid Articles in v1.
Section Structure

Each Section block:

contains standard Gutenberg blocks (InnerBlocks)
may optionally begin with a heading block (H2, H3, etc.)
does not require a separate title field
Section Title Behavior
The first heading block within a Section is treated as the section label
Only the first heading is used for TOC and accordion labels
Additional headings inside the section are treated as normal content
If no heading exists, the section has no label and will not appear in the TOC
Section Usage
Section blocks are strongly preferred for structured content
Standard Gutenberg blocks may still be used in v1
TOC and accordion behavior rely only on Section blocks
Articles without Section blocks render as standard content without structured navigation features
Rationale
allows flexible heading levels (not forced H2)
supports rich formatting in headings
keeps editing experience simple and inline
avoids rigid field-based section systems
keeps content portable in post_content
Article Display Controls

Legal Aid Articles support document-level display settings:

Show Table of Contents (on/off)
Use Accordion Sections (on/off)
Rules
These are display settings, not content structure
They do not change how content is stored
Section blocks remain the source for section-based behavior
If sections are not used, TOC/accordion behavior may be unavailable or limited
Location
Controlled via document-level settings in the WordPress editor sidebar
Section Block UI (Editor)
Visual Design
Sections should be visually distinct using:
light border or subtle background
spacing between sections
UI should be clear and structured, but not visually heavy
Structure & Behavior
Each section displays a non-editable label:
“Section”
All content is visible inline:
no collapsed edit panels
no hidden content areas
Editors can:
add a heading (optional)
add standard content blocks (paragraphs, lists, images, etc.)
Movement:
use native Gutenberg drag and reorder controls
Empty State Guidance
“Add a heading (optional)”
“Start writing…”
Guiding Principle
Structure should be visible and intuitive for editors
Avoid hidden panels, nested editing layers, or form-based content systems
Prefer inline editing with clear visual grouping
Balance structure with flexibility — guide, do not over-restrict

Article Body Structure

- Legal Aid Articles should use a structured page layout:
  - intro area first
  - Section blocks for all structured content after the intro

- The page/article structure should be controlled to preserve consistency.

- Section contents should remain flexible.
- Editors may use a wide range of standard content blocks inside sections.

- The system should prefer restricting layout/container blocks at the page level rather than over-restricting content blocks inside sections.

Article Body Structure

- Legal Aid Articles should use a structured page layout:
  - intro area first
  - Section blocks for all structured content after the intro

- The page/article structure should be controlled to preserve consistency.

- Section contents should remain flexible.
- Editors may use a wide range of standard content blocks inside sections.

- The system should prefer restricting layout/container blocks at the page level rather than over-restricting content blocks inside sections.

Migration consideration:
The article system should support both manual authoring and future Drupal migration. Because Drupal articles are currently composed of ordered segment-like nodes, the Section block model may provide a workable migration target where one Drupal segment maps to one Section block.

Article Display Behavior

Section Headings (Front-End Rendering)

- Each Section block may contain a heading (H2, H3, etc.)
- The first heading is used as the accordion label (<summary>)
- To avoid duplicate visible titles:
  - the heading remains in the DOM
  - the heading is visually hidden on the front end

Rationale:
- preserves semantic structure for accessibility and SEO
- avoids duplicate visible headings in accordion UI

Note:
- current implementation injects a visually-hidden style into the first heading
- future refinement may be needed if headings include inline styles