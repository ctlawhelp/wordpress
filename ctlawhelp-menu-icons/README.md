# Legal Aid Menu Icons

A WordPress plugin that adds icon support to navigation menu items with CSS variable-based styling.

## Features

- **Visual Icon Picker**: Select icons directly from WordPress Media Library
- **Universal Compatibility**: Works with classic menus, Elementor, and Gutenberg block navigation
- **CSS Variable Based**: Uses `--icon-url` custom properties for flexible styling
- **SVG Support**: Optimized for scalable vector graphics
- **Theme Agnostic**: Works with any WordPress theme
- **NSMI Integration**: Optional automatic icon inheritance from NSMI categories (requires Legal Aid Articles plugin)

## Installation

1. Upload the `legal-aid-menu-icons` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Appearance → Menus to start adding icons to menu items

## Usage

### Adding Icons to Menu Items

1. Go to **Appearance → Menus**
2. For each menu item, you'll see a "Menu Icon" section
3. Click **"Select Icon"** to choose an image from your Media Library
4. **Recommended**: Use SVG files for best quality and performance

### Controlling Icon Colors

Add one of these classes to your menu container to control icon colors:

- **`.menu-icons-white`** - White icons (for dark backgrounds)
- **`.menu-icons-blue`** - Blue icons (#29367C) 
- **`.menu-icons-dark`** - Dark gray icons (#333333)
- **`.menu-icons-custom`** - Uses CSS variable `--menu-icon-color`

**Example:**
```html
<nav class="elementor-nav-menu--main menu-icons-white">
  <!-- Menu items with white icons -->
</nav>
```

### CSS Structure

The plugin generates this HTML structure:
```html
<a href="/page/" 
   style="--icon-url: url('/wp-content/uploads/icon.svg')"
   class="has-menu-icon">
   Menu Item Text
</a>
```

## Customization

### Custom Icon Spacing

The default spacing can be customized by overriding these CSS properties:

```css
a[style*="--icon-url"] {
    padding-left: 3.2rem; /* Adjust text spacing */
}

a[style*="--icon-url"]::before {
    left: 0.5rem;    /* Icon position from left */
    width: 1.7rem;   /* Icon width */
    height: 1.7rem;  /* Icon height */
}
```

### Custom Icon Colors

For complete control over icon colors, use the `.menu-icons-custom` class and define your color:

```css
.my-menu.menu-icons-custom {
    --menu-icon-color: #ff6b35; /* Orange icons */
}
```

## NSMI Integration (Optional)

When the Legal Aid Articles plugin is active, this plugin provides automatic icon fallbacks:

1. **Manual Icons** (highest priority)
2. **Page → NSMI Category Icons** (for pages linked to NSMI terms)
3. **Direct NSMI Category Icons** (for NSMI taxonomy menu items)

This integration is completely optional and the plugin works independently without it.

## Technical Details

### Browser Support
- All modern browsers supporting CSS custom properties
- CSS masks for icon coloring (IE11 not supported for colored icons)

### Performance
- Minimal JavaScript (only in admin menu editor)
- CSS-based frontend rendering
- No external dependencies

### Hook Integration
- `nav_menu_link_attributes` - Classic WordPress menus
- `walker_nav_menu_start_el` - Custom walkers (Elementor, etc.)  
- `render_block` - Gutenberg block navigation

## Changelog

### 2.0.0
- Extracted from Legal Aid Articles plugin
- Added standalone plugin structure
- Improved CSS documentation
- Added version constants and proper plugin headers
- Enhanced NSMI integration with conditional loading

### 1.4.1
- Original version as part of Legal Aid Articles plugin
- CSS variable-based icon system
- Multi-walker support
- SVG upload support

## Support

This plugin was developed for Connecticut Legal Aid (CTLawHelp.org). For support or customization requests, contact your system administrator.

## License

GPL v2 or later