<?php
/**
 * FluentCRM Template Format Resources
 *
 * Provides comprehensive documentation resources for Gutenberg and Visual Builder
 * email formats to guide AI agents in generating valid FluentCRM content.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * Resources Ability Class
 *
 * Registers MCP resources that provide complete documentation for
 * FluentCRM email template formats.
 */
class Resources extends BaseAbility {

	/**
	 * Register all resource-related abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		$this->register_gutenberg_format_resource();
		$this->register_visual_builder_format_resource();
	}

	/**
	 * Register Gutenberg format documentation resource
	 *
	 * @return void
	 */
	private function register_gutenberg_format_resource(): void {
		wp_register_ability(
			'fluentcrm/resource-gutenberg-format',
			[
				'label'               => 'FluentCRM Gutenberg Format Guide',
				'description'         => 'Comprehensive guide to FluentCRM Gutenberg block format for email templates',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'permission_callback' => '__return_true', // Public resource
				'execute_callback'    => [ $this, 'execute_gutenberg_format_resource' ],
				'meta'                => [
					'category'      => 'fluentcrm',
					'subcategory'   => 'documentation',
					'resource_type' => 'documentation',
					'uri'           => 'fluentcrm://resource-gutenberg-format',
					'mimeType'      => 'text/markdown',
				],
			]
		);
	}

	/**
	 * Execute Gutenberg format resource
	 *
	 * Returns contents array for MCP resources/read response.
	 *
	 * @param array<string, mixed> $args Resource parameters
	 * @return array[] Array of content objects for MCP protocol
	 */
	public function execute_gutenberg_format_resource( array $args ): array {
		$documentation = $this->get_gutenberg_documentation();

		return [
			[
				'uri'      => 'fluentcrm://resource-gutenberg-format',
				'mimeType' => 'text/markdown',
				'text'     => $documentation,
			],
		];
	}

	/**
	 * Register Visual Builder format documentation resource
	 *
	 * @return void
	 */
	private function register_visual_builder_format_resource(): void {
		wp_register_ability(
			'fluentcrm/resource-visual-builder-format',
			[
				'label'               => 'FluentCRM Visual Builder Format Guide',
				'description'         => 'Comprehensive guide to FluentCRM Visual Builder JSON format for email templates',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'permission_callback' => '__return_true', // Public resource
				'execute_callback'    => [ $this, 'execute_visual_builder_format_resource' ],
				'meta'                => [
					'category'      => 'fluentcrm',
					'subcategory'   => 'documentation',
					'resource_type' => 'documentation',
					'uri'           => 'fluentcrm://resource-visual-builder-format',
					'mimeType'      => 'text/markdown',
				],
			]
		);
	}

	/**
	 * Execute Visual Builder format resource
	 *
	 * Returns contents array for MCP resources/read response.
	 *
	 * @param array<string, mixed> $args Resource parameters
	 * @return array[] Array of content objects for MCP protocol
	 */
	public function execute_visual_builder_format_resource( array $args ): array {
		$documentation = $this->get_visual_builder_documentation();

		return [
			[
				'uri'      => 'fluentcrm://resource-visual-builder-format',
				'mimeType' => 'text/markdown',
				'text'     => $documentation,
			],
		];
	}

	/**
	 * Get complete Gutenberg format documentation
	 *
	 * @return string Complete documentation markdown
	 */
	private function get_gutenberg_documentation(): string {
		return <<<'MARKDOWN'
# FluentCRM Gutenberg Block Format - Complete Guide

## Overview

Gutenberg is the default WordPress block editor format used by FluentCRM for email templates. It stores content as HTML with special WordPress block comment syntax.

### When to Use Gutenberg
- Default format for simple/classic templates
- `design_template`: "simple", "classic", "raw_classic", or "raw_html"
- Content stored in `email_body` field only
- No separate JSON design object

### Storage Location
- **Field**: `email_body` in `fc_campaigns` table
- **Format**: HTML with WordPress block comments
- **Rendering**: Direct HTML output with minimal processing

---

## Block Syntax Structure

### Basic Pattern
```html
<!-- wp:BLOCK_TYPE {JSON_ATTRIBUTES} -->
HTML_CONTENT
<!-- /wp:BLOCK_TYPE -->
```

### Critical Rules
1. ✅ Opening comment MUST include block type
2. ✅ JSON attributes MUST be valid, escaped JSON
3. ✅ HTML content between comments
4. ✅ Closing comment MUST match opening block type
5. ✅ Proper nesting for container blocks

---

## Common Block Types

### 1. Image Block
```html
<!-- wp:image {"id":502369,"width":"200px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized">
  <img src="https://example.com/image.jpg" alt="Product Image" class="wp-image-502369" style="width:200px"/>
</figure>
<!-- /wp:image -->
```

**Required Attributes:**
- `id`: Image attachment ID (integer)
- `width`: Width with px unit (string, e.g., "200px")
- `sizeSlug`: WordPress image size ("full", "large", "medium", "thumbnail")
- `align`: Alignment ("left", "center", "right", "none")

**Required HTML:**
- `<figure>` wrapper with classes matching attributes
- `<img>` tag with src, alt, class, and inline style
- Style attribute must include width from JSON

### 2. Paragraph Block
```html
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Welcome to our newsletter, {{contact.first_name}}!</p>
<!-- /wp:paragraph -->
```

**Required Attributes:**
- `align`: Text alignment ("left", "center", "right")

**Required HTML:**
- `<p>` tag with class matching alignment
- Class format: `has-text-align-{align}`
- Supports merge tags: `{{contact.property}}`

### 3. Heading Block
```html
<!-- wp:heading {"textAlign":"center","level":2,"textColor":"pale-pink","style":{"elements":{"link":{"color":{"text":"var:preset|color|pale-pink"}}}}} -->
<h2 class="wp-block-heading has-text-align-center has-pale-pink-color has-text-color has-link-color">Special Offer</h2>
<!-- /wp:heading -->
```

**Required Attributes:**
- `level`: Heading level 1-6 (integer)
- `textAlign`: Text alignment
- `textColor`: Color slug (optional)
- `style`: Nested style object (optional)

**Required HTML:**
- Heading tag matching level (level:2 = `<h2>`)
- Classes: `wp-block-heading`, alignment class, color classes
- Color classes: `has-{color}-color`, `has-text-color`

### 4. Button Block
```html
<!-- wp:button {"backgroundColor":"pale-pink","textColor":"black","style":{"border":{"radius":"10px"}}} -->
<div class="wp-block-button">
  <a class="wp-block-button__link has-pale-pink-background-color has-black-color has-text-color has-background wp-element-button"
     href="https://example.com/shop"
     style="border-radius:10px"
     target="_blank">
    Shop Now
  </a>
</div>
<!-- /wp:button -->
```

**Required Attributes:**
- `backgroundColor`: Background color slug
- `textColor`: Text color slug
- `style`: Border, spacing styles (optional)

**Required HTML:**
- Wrapper `<div class="wp-block-button">`
- Link with classes: `wp-block-button__link`, color classes, `wp-element-button`
- Inline styles matching JSON attributes
- href, target attributes

### 5. Columns Block (Multi-Column Layout)
```html
<!-- wp:columns -->
<div class="wp-block-columns">
  <!-- wp:column {"width":"50%"} -->
  <div class="wp-block-column" style="flex-basis:50%">
    <!-- Inner blocks -->
    <!-- wp:paragraph -->
    <p>Left column content</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->

  <!-- wp:column {"width":"50%"} -->
  <div class="wp-block-column" style="flex-basis:50%">
    <!-- Inner blocks -->
    <!-- wp:paragraph -->
    <p>Right column content</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->
</div>
<!-- /wp:columns -->
```

**Required Structure:**
- Wrapper: `<!-- wp:columns -->` with `<div class="wp-block-columns">`
- Each column: `<!-- wp:column -->` with `<div class="wp-block-column">`
- Column width attribute sets flex-basis style
- Inner blocks properly nested inside columns
- All opening/closing comments match

### 6. Group Block (Container)
```html
<!-- wp:group {"backgroundColor":"pale-pink","style":{"spacing":{"padding":"20px"}}} -->
<div class="wp-block-group has-pale-pink-background-color has-background" style="padding:20px">
  <!-- Inner blocks -->
  <!-- wp:heading {"level":3} -->
  <h3 class="wp-block-heading">Section Title</h3>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>Section content goes here.</p>
  <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

**Required Structure:**
- Container for grouping multiple blocks
- Supports backgroundColor, spacing, border styles
- Classes match background color attributes
- Inline styles for padding, margin, borders
- Properly nest all child blocks

---

## JSON Attribute Validation

### Valid JSON Examples
```javascript
// ✅ CORRECT - Proper escaping and formatting
{"id":123,"width":"200px","sizeSlug":"full"}
{"align":"center","className":"custom-class"}
{"backgroundColor":"pale-pink","textColor":"black"}
{"style":{"border":{"radius":"10px"}}}
```

### Invalid JSON Examples
```javascript
// ❌ WRONG - Unescaped quotes
{"width":"20"px"}  // Quote inside string

// ❌ WRONG - Missing quotes on keys
{width:"200px"}  // Keys must be quoted

// ❌ WRONG - Single quotes
{'width':'200px'}  // Must use double quotes

// ❌ WRONG - Trailing comma
{"width":"200px",}  // No trailing commas
```

### Escaping Rules
1. Property names: Always double quotes
2. String values: Always double quotes
3. Numbers: No quotes (e.g., `"id":123`)
4. Booleans: No quotes (e.g., `"active":true`)
5. Special characters: Escape with backslash (`\"`)

---

## Merge Tags (Personalization)

### Contact Merge Tags
```html
<!-- Use double curly braces -->
{{contact.first_name}}
{{contact.last_name}}
{{contact.full_name}}
{{contact.email}}
{{contact.phone}}
{{contact.address_line_1}}
{{contact.city}}
{{contact.state}}
{{contact.country}}

<!-- With fallback -->
{{contact.first_name | Friend}}
{{contact.company_name | there}}
```

### CRM System Tags
```html
<!-- Use double hash marks -->
##crm.unsubscribe_url##
##crm.unsubscribe_html##
##crm.business_name##
##crm.business_address##
##crm.manage_subscription_url##
```

### Invalid Merge Tag Formats
```html
<!-- ❌ WRONG -->
{contact.name}          // Single braces
{{ contact.name }}      // Spaces inside braces
{{contact}}             // No property specified
#crm.url#              // Single hash marks
```

---

## Complete Email Example

```html
<!-- wp:image {"id":12345,"width":"600px","sizeSlug":"full","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized">
  <img src="https://example.com/banner.jpg" alt="Welcome Banner" class="wp-image-12345" style="width:600px"/>
</figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">Welcome, {{contact.first_name}}!</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Thank you for joining our community. We're excited to have you!</p>
<!-- /wp:paragraph -->

<!-- wp:columns -->
<div class="wp-block-columns">
  <!-- wp:column {"width":"50%"} -->
  <div class="wp-block-column" style="flex-basis:50%">
    <!-- wp:image {"id":12346,"width":"250px","sizeSlug":"medium"} -->
    <figure class="wp-block-image size-medium is-resized">
      <img src="https://example.com/product1.jpg" alt="Product 1" class="wp-image-12346" style="width:250px"/>
    </figure>
    <!-- /wp:image -->

    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">Featured Product</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Check out our latest innovation.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->

  <!-- wp:column {"width":"50%"} -->
  <div class="wp-block-column" style="flex-basis:50%">
    <!-- wp:image {"id":12347,"width":"250px","sizeSlug":"medium"} -->
    <figure class="wp-block-image size-medium is-resized">
      <img src="https://example.com/product2.jpg" alt="Product 2" class="wp-image-12347" style="width:250px"/>
    </figure>
    <!-- /wp:image -->

    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">Bestseller</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Our most popular item this season.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:button {"backgroundColor":"pale-pink","align":"center"} -->
<div class="wp-block-button aligncenter">
  <a class="wp-block-button__link has-pale-pink-background-color has-background wp-element-button"
     href="https://example.com/shop"
     target="_blank">
    Shop Now
  </a>
</div>
<!-- /wp:button -->

<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size">
  ##crm.unsubscribe_html##<br>
  © 2025 Your Company. All rights reserved.
</p>
<!-- /wp:paragraph -->
```

---

## Validation Checklist

### Pre-Generation
- [ ] All block types are valid WordPress blocks
- [ ] JSON attributes properly escaped
- [ ] All required attributes included
- [ ] Nesting structure correct
- [ ] Merge tags use correct syntax

### Post-Generation
- [ ] Opening/closing comments match (count them)
- [ ] All JSON is valid (can be parsed)
- [ ] HTML structure valid
- [ ] Classes match JSON attributes
- [ ] Inline styles match JSON

### Common Errors to Avoid
1. ❌ Unescaped quotes in JSON
2. ❌ Missing closing block comments
3. ❌ Single braces for merge tags `{name}`
4. ❌ Mismatched opening/closing block types
5. ❌ Invalid HTML inside blocks
6. ❌ Missing required classes on elements

---

## Best Practices

### 1. Always Use Full Block Syntax
Even for simple elements, use complete block comments with proper JSON.

### 2. Match Classes to Attributes
If JSON has `"align":"center"`, HTML must have `has-text-align-center` class.

### 3. Include Fallbacks for Merge Tags
```html
{{contact.first_name | Friend}}  <!-- Better than just {{contact.first_name}} -->
```

### 4. Use Semantic HTML
Match heading levels logically (h1 for main title, h2 for sections, etc.)

### 5. Test Email Rendering
Gutenberg blocks render to plain HTML for email clients. Ensure your HTML is email-client compatible (avoid CSS Grid/Flexbox, use tables for layout if needed).

---

## Reference URLs
- WordPress Block Editor: https://developer.wordpress.org/block-editor/
- FluentCRM Documentation: https://fluentcrm.com/docs/
- Block Reference: https://developer.wordpress.org/block-editor/reference-guides/core-blocks/

---

**Last Updated**: 2025-10-03
**Format Version**: Gutenberg Blocks v1
**Compatible With**: FluentCRM 2.x, WordPress 6.4+
MARKDOWN;
	}

	/**
	 * Get complete Visual Builder format documentation
	 *
	 * @return string Complete documentation markdown
	 */
	private function get_visual_builder_documentation(): string {
		return <<<'MARKDOWN'
# FluentCRM Visual Builder JSON Format - Complete Guide

## Overview

Visual Builder is FluentCRM's advanced drag-and-drop email builder that uses JSON to store email designs. It generates professional, table-based HTML emails compatible with all email clients.

### When to Use Visual Builder
- Professional email designs with drag-and-drop editing
- `design_template`: "visual_builder"
- JSON design stored in `_visual_builder_design` meta field
- Generated HTML stored in `email_body` field
- External editor loads from https://fluentcrm.com/builder/

### Storage Locations
- **Design JSON**: `wp_fc_meta` table, key `_visual_builder_design`
- **Generated HTML**: `email_body` in `fc_campaigns` table
- **Object Type**: `FluentCrm\App\Models\Campaign`

---

## Top-Level JSON Structure

```json
{
  "counters": {
    "u_row": 3,
    "u_column": 3,
    "u_content_image": 2,
    "u_content_text": 2,
    "u_content_button": 1,
    "u_content_heading": 1,
    "u_content_divider": 1
  },
  "body": {
    "id": "9t__qU3fbj",
    "rows": [],
    "values": {}
  },
  "schemaVersion": 16
}
```

### Required Top-Level Keys
1. **counters**: Object tracking all element counts
2. **body**: Main design container with rows array
3. **schemaVersion**: Must be integer 16

---

## Counters Object

### Purpose
Tracks exact count of each element type for efficient rendering and validation.

### Required Properties
```json
{
  "u_row": 0,           // Total number of rows
  "u_column": 0,        // Total columns across all rows
  "u_content_image": 0,    // Image content blocks
  "u_content_text": 0,     // Text content blocks
  "u_content_button": 0,   // Button content blocks
  "u_content_heading": 0,  // Heading content blocks
  "u_content_divider": 0,  // Divider content blocks
  "u_content_html": 0      // HTML content blocks
}
```

### Counting Rules
1. ✅ Count MUST be exact integer
2. ✅ Include ALL rows in design
3. ✅ Include ALL columns across ALL rows
4. ✅ Count each content type separately
5. ✅ Set to 0 if type not used
6. ❌ NEVER leave out counter properties

### Example Calculation
```
Design has:
- 2 rows
  - Row 1: 1 column with 1 image, 1 text
  - Row 2: 2 columns with 1 button each

Counters:
{
  "u_row": 2,              // 2 rows total
  "u_column": 3,           // 1 + 2 = 3 columns total
  "u_content_image": 1,
  "u_content_text": 1,
  "u_content_button": 2    // 2 buttons
}
```

---

## Body Structure

### Required Properties
```json
{
  "id": "UNIQUE_10_CHAR_ID",
  "rows": [],
  "values": {
    "contentWidth": "600px",
    "fontFamily": {
      "label": "Arial",
      "value": "arial,helvetica,sans-serif"
    },
    "textColor": "#000000",
    "backgroundColor": "#e7e7e7",
    "linkStyle": {
      "body": true,
      "linkColor": "#0000ee",
      "linkHoverColor": "#0000ee"
    },
    "_meta": {
      "htmlID": "u_body",
      "htmlClassNames": "u_body"
    }
  }
}
```

### Body ID Requirements
- ✅ Exactly 10 characters
- ✅ Alphanumeric + underscore + hyphen
- ✅ Unique per design
- ✅ Mix of upper, lower, numbers
- ❌ No spaces or special chars

---

## Row Structure

### Complete Row Object
```json
{
  "id": "row_ZZl-_NkH09",
  "cells": [1],
  "columns": [],
  "values": {
    "displayCondition": null,
    "columns": false,
    "backgroundColor": "",
    "columnsBackgroundColor": "",
    "backgroundImage": {
      "url": "",
      "fullWidth": true,
      "repeat": false,
      "center": true,
      "cover": false
    },
    "padding": "0px",
    "hideDesktop": false,
    "_meta": {
      "htmlID": "u_row_1",
      "htmlClassNames": "u_row"
    },
    "selectable": true,
    "draggable": true,
    "duplicatable": true,
    "deletable": true,
    "hideable": true
  }
}
```

### Cells Array (Column Layout)
```javascript
// Single column (full width)
"cells": [1]

// Two equal columns
"cells": [50, 50]

// Three equal columns
"cells": [33.33, 33.33, 33.34]

// Two unequal columns (25% / 75%)
"cells": [25, 75]

// Four equal columns
"cells": [25, 25, 25, 25]
```

**Rules:**
- ✅ Sum should equal 100 (or use [1] for full width)
- ✅ Number of values MUST match number of columns
- ✅ Percentages can have decimals
- ❌ Never have cells array mismatch column count

### Row Values Properties

#### Required
- `padding`: String with unit (e.g., "0px", "20px")
- `_meta.htmlID`: Unique ID (e.g., "u_row_1")
- `_meta.htmlClassNames`: "u_row"

#### Optional
- `backgroundColor`: Hex color or empty string
- `backgroundImage`: Object with url, fullWidth, repeat, center, cover
- `displayCondition`: null or condition object
- `hideDesktop`: boolean
- Editor properties: selectable, draggable, duplicatable, deletable, hideable

---

## Column Structure

### Complete Column Object
```json
{
  "id": "col_abc123xyz",
  "contents": [],
  "values": {
    "backgroundColor": "",
    "padding": "0px",
    "border": {},
    "_meta": {
      "htmlID": "u_column_1",
      "htmlClassNames": "u_column"
    }
  }
}
```

### Column ID Requirements
- ✅ Unique per design
- ✅ 10+ characters
- ✅ Alphanumeric format
- ❌ No duplicate IDs

### Contents Array
Array of content blocks (image, text, button, heading, divider, html).
Can be empty array `[]` for blank column.

---

## Content Block Types

### 1. Image Content Block

```json
{
  "id": "img_9t__qU3fbj",
  "type": "image",
  "values": {
    "containerPadding": "10px",
    "anchor": "",
    "src": {
      "url": "https://example.com/image.jpg",
      "width": 600,
      "height": 400,
      "maxWidth": "100%",
      "autoWidth": false
    },
    "textAlign": "center",
    "altText": "Image description",
    "action": {
      "name": "web",
      "values": {
        "href": "https://example.com",
        "target": "_blank"
      }
    },
    "hideDesktop": false,
    "displayCondition": null,
    "_meta": {
      "htmlID": "u_content_image_1",
      "htmlClassNames": "u_content_image"
    },
    "selectable": true,
    "draggable": true,
    "duplicatable": true,
    "deletable": true,
    "hideable": true
  }
}
```

**Required Properties:**
- `src.url`: HTTPS URL to image
- `src.width`: Integer pixel width
- `src.height`: Integer pixel height
- `textAlign`: "left", "center", "right"
- `_meta.htmlID`: Unique (e.g., "u_content_image_1")

**Optional:**
- `altText`: Accessibility description
- `action`: Click action with href and target
- `containerPadding`: Spacing around image

### 2. Text Content Block

```json
{
  "id": "text_abc123xyz",
  "type": "text",
  "values": {
    "containerPadding": "10px",
    "anchor": "",
    "textAlign": "left",
    "lineHeight": "140%",
    "linkStyle": {
      "inherit": true,
      "linkColor": "#0000ee",
      "linkHoverColor": "#0000ee",
      "linkUnderline": true,
      "linkHoverUnderline": true
    },
    "text": "<p style=\"margin: 0; color: #333333; font-size: 16px;\">Hello {{contact.first_name}}, welcome to our newsletter!</p>",
    "hideDesktop": false,
    "displayCondition": null,
    "_meta": {
      "htmlID": "u_content_text_1",
      "htmlClassNames": "u_content_text"
    },
    "selectable": true,
    "draggable": true,
    "duplicatable": true,
    "deletable": true,
    "hideable": true
  }
}
```

**Required Properties:**
- `text`: HTML string with inline styles
- `lineHeight`: Percentage (e.g., "140%")
- `textAlign`: "left", "center", "right"
- `_meta.htmlID`: Unique

**Text HTML Requirements:**
- ✅ Use inline styles (email-safe)
- ✅ Escape quotes in JSON
- ✅ Support merge tags: `{{contact.property}}`
- ✅ Support CRM tags: `##crm.property##`
- ❌ No external CSS classes

### 3. Button Content Block

```json
{
  "id": "btn_xyz789def",
  "type": "button",
  "values": {
    "containerPadding": "10px",
    "anchor": "",
    "href": {
      "name": "web",
      "values": {
        "href": "https://example.com/shop",
        "target": "_blank"
      }
    },
    "buttonColors": {
      "color": "#FFFFFF",
      "backgroundColor": "#4A90E2",
      "hoverColor": "#FFFFFF",
      "hoverBackgroundColor": "#357ABD"
    },
    "size": {
      "autoWidth": true,
      "width": "100%"
    },
    "textAlign": "center",
    "lineHeight": "120%",
    "padding": "15px 40px",
    "border": {},
    "borderRadius": "4px",
    "text": "<span style=\"font-size: 16px; font-family: Arial, sans-serif; font-weight: bold;\">Click Here</span>",
    "hideDesktop": false,
    "displayCondition": null,
    "_meta": {
      "htmlID": "u_content_button_1",
      "htmlClassNames": "u_content_button"
    },
    "selectable": true,
    "draggable": true,
    "duplicatable": true,
    "deletable": true,
    "hideable": true,
    "calculatedWidth": 193,
    "calculatedHeight": 46
  }
}
```

**Required Properties:**
- `href.name`: Must be "web"
- `href.values.href`: Button URL
- `href.values.target`: "_blank" or "_self"
- `buttonColors.color`: Text color hex (#RRGGBB)
- `buttonColors.backgroundColor`: Background hex
- `text`: HTML with `<span>` wrapper
- `padding`: With unit (e.g., "15px 40px")
- `borderRadius`: With unit (e.g., "4px")

**Color Requirements:**
- ✅ Must be 6-digit hex: #FFFFFF
- ❌ Not 3-digit: #FFF
- ❌ Not color names: "white"
- ❌ Not rgb(): rgb(255,255,255)

### 4. Heading Content Block

```json
{
  "id": "head_123abc456",
  "type": "heading",
  "values": {
    "containerPadding": "10px",
    "anchor": "",
    "headingType": "h1",
    "fontSize": "32px",
    "textAlign": "center",
    "lineHeight": "140%",
    "linkStyle": {
      "inherit": true,
      "linkColor": "#0000ee",
      "linkHoverColor": "#0000ee",
      "linkUnderline": true,
      "linkHoverUnderline": true
    },
    "text": "Welcome to Our Newsletter",
    "hideDesktop": false,
    "displayCondition": null,
    "_meta": {
      "htmlID": "u_content_heading_1",
      "htmlClassNames": "u_content_heading"
    },
    "selectable": true,
    "draggable": true,
    "duplicatable": true,
    "deletable": true,
    "hideable": true
  }
}
```

**Required Properties:**
- `headingType`: "h1", "h2", "h3", "h4", "h5", or "h6"
- `fontSize`: With unit (e.g., "32px", "2em")
- `lineHeight`: Percentage (e.g., "140%")
- `text`: Plain text (NOT HTML)
- `textAlign`: "left", "center", "right"

**Important:**
- ✅ Heading text is PLAIN TEXT
- ❌ Do not use HTML tags in heading text
- ✅ Use text content blocks for formatted text

### 5. Divider Content Block

```json
{
  "id": "div_def789ghi",
  "type": "divider",
  "values": {
    "width": "100%",
    "border": {
      "borderTopWidth": "1px",
      "borderTopStyle": "solid",
      "borderTopColor": "#BBBBBB"
    },
    "textAlign": "center",
    "containerPadding": "10px",
    "anchor": "",
    "hideDesktop": false,
    "displayCondition": null,
    "_meta": {
      "htmlID": "u_content_divider_1",
      "htmlClassNames": "u_content_divider"
    },
    "selectable": true,
    "draggable": true,
    "duplicatable": true,
    "deletable": true,
    "hideable": true
  }
}
```

**Required Properties:**
- `width`: Percentage or pixel (e.g., "100%", "500px")
- `border.borderTopWidth`: With unit (e.g., "1px")
- `border.borderTopStyle`: "solid", "dashed", "dotted"
- `border.borderTopColor`: Hex color (#RRGGBB)
- `textAlign`: Controls divider alignment

---

## ID Generation System

### ID Format Requirements
```javascript
// Generate unique 10-character IDs
const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-';

function generateId() {
  let id = '';
  for (let i = 0; i < 10; i++) {
    id += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return id;
}

// Examples of valid IDs:
// "9t__qU3fbj"
// "ZZl-_NkH09"
// "abc_DEF123"
// "row_001xyz"
```

### ID Uniqueness Rules
1. ✅ Body id unique per design
2. ✅ Each row id unique per design
3. ✅ Each column id unique per design
4. ✅ Each content id unique per design
5. ✅ Each _meta.htmlID unique per design
6. ❌ NEVER reuse IDs from examples
7. ❌ NEVER duplicate IDs within same design

### Recommended ID Prefixes
```
body_XXXXXXXX   // For body.id
row_XXXXXXXX    // For rows
col_XXXXXXXX    // For columns
img_XXXXXXXX    // For image content
text_XXXXXXXX   // For text content
btn_XXXXXXXX    // For button content
head_XXXXXXXX   // For heading content
div_XXXXXXXX    // For divider content
```

---

## Merge Tags & Personalization

### Contact Merge Tags
```html
{{contact.first_name}}
{{contact.last_name}}
{{contact.full_name}}
{{contact.email}}
{{contact.phone}}
{{contact.company_name}}
{{contact.address_line_1}}
{{contact.city}}
{{contact.state}}
{{contact.country}}

<!-- With fallback -->
{{contact.first_name | Friend}}
```

### CRM System Tags
```html
##crm.unsubscribe_url##
##crm.unsubscribe_html##
##crm.business_name##
##crm.business_address##
##crm.manage_subscription_url##
```

**Usage in Visual Builder:**
- ✅ Use in text content blocks
- ✅ Use in heading text
- ✅ Use in button text
- ❌ Do not use in IDs or property names

---

## Complete Minimal Example

```json
{
  "counters": {
    "u_row": 1,
    "u_column": 1,
    "u_content_text": 1
  },
  "body": {
    "id": "9t__qU3fbj",
    "rows": [
      {
        "id": "row_ZZl-_NkH09",
        "cells": [1],
        "columns": [
          {
            "id": "col_abc123xyz",
            "contents": [
              {
                "id": "text_def456ghi",
                "type": "text",
                "values": {
                  "containerPadding": "20px",
                  "text": "<p style=\"margin: 0; color: #333333; font-size: 16px;\">Hello {{contact.first_name}}, welcome!</p>",
                  "textAlign": "left",
                  "lineHeight": "140%",
                  "_meta": {
                    "htmlID": "u_content_text_1",
                    "htmlClassNames": "u_content_text"
                  }
                }
              }
            ],
            "values": {
              "padding": "0px",
              "_meta": {
                "htmlID": "u_column_1",
                "htmlClassNames": "u_column"
              }
            }
          }
        ],
        "values": {
          "padding": "0px",
          "_meta": {
            "htmlID": "u_row_1",
            "htmlClassNames": "u_row"
          }
        }
      }
    ],
    "values": {
      "contentWidth": "600px",
      "fontFamily": {
        "label": "Arial",
        "value": "arial,helvetica,sans-serif"
      },
      "textColor": "#000000",
      "backgroundColor": "#e7e7e7",
      "_meta": {
        "htmlID": "u_body",
        "htmlClassNames": "u_body"
      }
    }
  },
  "schemaVersion": 16
}
```

---

## Validation Checklist

### Pre-Generation
- [ ] All IDs are unique 10-character strings
- [ ] Counters accurately reflect element counts
- [ ] Cells array length matches columns count
- [ ] All content blocks have required values
- [ ] All URLs are HTTPS
- [ ] All colors are 6-digit hex (#RRGGBB)
- [ ] All dimensions include units (px, %)
- [ ] schemaVersion is 16

### Post-Generation
- [ ] JSON is valid and parseable
- [ ] All required _meta fields present
- [ ] No duplicate IDs anywhere
- [ ] Counter numbers match actual counts
- [ ] All text properly escaped in JSON

### Common Errors to Avoid
1. ❌ Duplicate IDs
2. ❌ Inaccurate counter values
3. ❌ Cells array mismatch with columns
4. ❌ HTTP URLs instead of HTTPS
5. ❌ Colors without # or wrong format
6. ❌ Dimensions without units
7. ❌ HTML in heading text
8. ❌ Missing _meta.htmlID
9. ❌ Wrong schemaVersion

---

## Best Practices

### 1. Start Simple
Begin with single row, single column, single text block. Add complexity gradually.

### 2. Use Descriptive IDs
While IDs can be random, prefixes help:
- `row_001`, `row_002` for rows
- `col_001a`, `col_001b` for columns in row 1
- `text_welcome`, `btn_cta` for content

### 3. Count As You Build
Update counters as you add each element to avoid mismatches.

### 4. Test JSON Validity
Use JSON validator before submission. Invalid JSON will fail silently.

### 5. Email-Safe HTML
In text blocks, use inline styles only. No external CSS, no JavaScript.

### 6. Mobile-Friendly
Keep contentWidth at 600px. Use padding for spacing, not large fixed widths.

---

## Reference Examples

See campaigns 583 and 584 for working examples:
- **Campaign 583**: Single column welcome email
- **Campaign 584**: Multi-column product showcase

Both demonstrate proper:
- Counter accuracy
- Unique ID generation
- Multi-row layouts
- Multiple content types
- Merge tag usage

---

**Last Updated**: 2025-10-03
**Format Version**: Visual Builder v16
**Compatible With**: FluentCRM 2.x, FluentCampaign Pro 2.x
MARKDOWN;
	}
}
