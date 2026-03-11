# Legal Aid Conditional Fields

A lightweight, minimal conditional fields plugin for Elementor forms - built specifically for CTLawHelp without the bloat of third-party alternatives.

## Features

- **Lightweight**: Single PHP file core (vs 558+ lines in alternatives)
- **Clean Interface**: Simple repeater control in Elementor
- **Essential Operators**: equals, not_equals, contains, empty, not_empty
- **Show/Hide Actions**: Simple show or hide field actions
- **Form Validation**: Properly handles validation for hidden fields
- **No Admin Panel**: Everything configured directly in Elementor (no bloated admin interface)

## How to Use

1. **Enable Conditional Fields**: In your Elementor form widget, scroll to the bottom of the "Form Fields" section and toggle on "Enable Conditional Fields"

2. **Add Rules**: Click "Add Item" in the "Conditional Rules" section

3. **Configure Each Rule**:
   - **Target Field ID**: The field you want to show/hide (e.g., "message")
   - **Condition Field ID**: The field that triggers the condition (e.g., "contact_reason")
   - **Operator**: Choose how to compare values
   - **Value**: What to compare against (leave empty for "empty"/"not empty")
   - **Action**: Show or Hide the target field

## Example Use Cases

### Show Additional Field Based on Selection
- Target Field: `additional_info`
- Condition Field: `contact_reason` 
- Operator: `equals`
- Value: `legal_help`
- Action: `show`

### Hide Field When Another is Empty
- Target Field: `follow_up_questions`
- Condition Field: `phone_number`
- Operator: `empty`
- Value: (leave blank)
- Action: `hide`

## Field Operators

- **equals**: Exact match
- **not_equals**: Not an exact match  
- **contains**: Value contains the specified text
- **empty**: Field is empty/blank
- **not_empty**: Field has any content

## Comparison with Bloated Alternatives

| Feature | Legal Aid CF | Bloated Plugin |
|---------|--------------|----------------|
| Core PHP Lines | ~150 | 558+ |
| PHP Files | 1 | 8+ |
| Admin Interface | None (Elementor only) | Complex admin panel |
| File Size | ~15KB | 200KB+ |
| Features | Essential only | Dozens of unused features |
| Load Time | Minimal | Significant overhead |

## Technical Notes

- Requires Elementor Pro (for form widget)
- Works with all Elementor form field types
- Handles form validation correctly
- Lightweight JavaScript (~100 lines vs 500+ in alternatives)
- No database tables or complex caching
- Clean, maintainable code

## Auto-Save Feature

The plugin automatically captures form interactions without requiring submission, helping you understand user behavior and improve the experience:

### How It Works
- **Silent Capture**: Saves form data as users type or make selections (with 1-second debounce)
- **No Interruption**: Users don't know data is being captured - no popups or notifications
- **Session Tracking**: Each interaction gets a unique session ID for analytics
- **Privacy Focused**: Stores minimal data (field values, timestamp, page URL, IP)

### View Captured Data
1. Go to **Tools > Form Data** in your WordPress admin
2. View all form interactions in a clean table format
3. Export to CSV for analysis
4. Clear old data when needed

### Data Storage
- Temporary storage in WordPress transients (24-hour expiration)
- Permanent log in WordPress options (limited to 1000 most recent entries)
- No external databases or third-party services required

### Use Cases
- **UX Research**: See where users drop off or get confused
- **Content Optimization**: Understand which questions get the most engagement
- **A/B Testing**: Track different form variations performance
- **Lead Intelligence**: Capture partial submissions that would otherwise be lost

## Version History

- **1.1.0**: Added auto-save functionality and admin data viewer
- **1.0.0**: Initial release - minimal, focused conditional fields