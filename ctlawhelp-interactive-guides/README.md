# Legal Guides Plugin

## Description
A WordPress plugin that creates a custom post type for Legal Guides with support for NSMI (National Subject Matter Index) categories.

## Features
- Custom post type: `interactive_guide`
- Integration with NSMI taxonomy (`nsmi_category`)
- Responsive grid layout for displaying guides
- Admin interface for managing legal guides
- Custom styling and JavaScript for enhanced user experience
- Support for featured images, excerpts, and custom fields

## Post Type Details
- **Post Type**: `interactive_guide`
- **URL Slug**: `/interactive-guides/`
- **Supports**: Title, Editor, Excerpt, Thumbnail, Revisions, Custom Fields
- **Taxonomies**: NSMI Categories (`nsmi_category`)

## Installation
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. The plugin will automatically create the Legal Guides post type
4. NSMI categories will be shared with other legal aid plugins

## Usage
1. Go to "Legal Guides" in the WordPress admin
2. Click "Add Guide Page" to create new content
3. Assign NSMI categories to organize your guides
4. Use the built-in editor to create interactive content

## Files Structure
```
interactive-guides/
├── interactive-guides.php     # Main plugin file
├── inc/
│   ├── cpt.php               # Custom post type registration
│   ├── taxonomy-nsmi.php     # NSMI taxonomy integration
│   ├── taxonomy-guide-series.php # Guide series taxonomy
│   ├── admin-ui.php          # Meta box + save logic for guide steps
│   ├── render-shortcode.php  # Shortcode template for frontend
│   └── helpers.php           # Utility functions
├── assets/
│   ├── css/
│   │   └── interactive-guides.css
│   └── js/
│       ├── interactive-guides.js # Legacy JS file
│       ├── admin.js          # Editor UI logic for guide steps
│       └── front.js          # Interactive frontend behavior
└── README.md
```

## Compatibility
- WordPress 5.0+
- PHP 7.4+
- Compatible with other CTLawHelp plugins (legal-aid-articles, legal-aid-tax-bread)

## Development Notes
- The plugin checks if NSMI taxonomy already exists before registering it
- Uses WordPress best practices for custom post types and taxonomies
- Includes activation/deactivation hooks for proper permalink flushing
- Designed to work alongside existing legal aid plugins


# Interactive Guides Plugin – Dev Context

This WordPress plugin powers CTLawHelp.org’s interactive self-help guides.

## Goals
- Custom post type `interactive_guide` already exists.
- Add a meta box UI to edit all guide steps as one JSON field `_clh_guide_steps`.
- Each step can be `info`, `question`, or `form` type.
- Admin JS allows add/remove/reorder and type-specific fields.
- Frontend shortcode `[interactive_guide id="123"]` renders a split view:
  - Left: interactive questions
  - Right: dynamic content
  - Top: progress bar

## Files
- `inc/admin-ui.php`: Meta box + save logic for guide steps
- `inc/render-shortcode.php`: Shortcode template for frontend rendering
- `inc/helpers.php`: Utility functions for guide processing
- `inc/taxonomy-guide-series.php`: Guide series taxonomy registration
- `assets/js/admin.js`: Editor UI logic for building guide steps
- `assets/js/front.js`: Interactive frontend behavior
- `interactive-guides.php`: Main plugin loader

## To-Do
1. Build JS repeater UI for steps.
2. Serialize fields into JSON on save.
3. Front-end: parse JSON and render steps dynamically.
4. Add progress bar and branching logic later.

Copilot tips:
- When editing `admin.js`, suggest vanilla JS for dynamic meta box builder.
- When editing PHP, use WordPress meta box and shortcode APIs.


# Legal Guides Plugin – Developer Context

Custom WordPress plugin for CTLawHelp interactive guides.

- CPT: `interactive_guide`
- Each guide stores all steps as JSON in meta key `_clh_guide_steps`.
- Admin UI built in `inc/admin-ui.php` + `assets/js/admin.js`.
- Front-end shortcode `[interactive_guide id="123"]` renders steps.

To do next:
- Expand admin.js for question types & branching logic.
- Style front-end layout in Elementor using shortcode output.
- Later: add progress tracking & PDF generation.

Copilot: assume vanilla PHP + WordPress API + jQuery.