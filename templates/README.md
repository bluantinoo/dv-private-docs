# DV Private Documents - Template System

## Overview

This plugin uses a template override system similar to WooCommerce. Theme developers can customize the HTML structure by copying template files to their theme.

## How to Override Templates

### Step 1: Create directory in your theme

```
wp-content/themes/your-theme/dv-private-docs/
```

### Step 2: Copy template files from plugin

```
wp-content/plugins/dv-private-docs/templates/
```

### Step 3: Edit the copied files

Make your HTML customizations in your theme's copy of the template.

### Step 4: Templates load in this order

1. **Child theme**: `child-theme/dv-private-docs/{template}`
2. **Parent theme**: `parent-theme/dv-private-docs/{template}`
3. **Plugin**: `plugin/templates/{template}`

The first template found in this order will be used.

## Available Templates

### Document List

- **`document-list.php`** - Main list wrapper
  - Contains the loop that displays all documents
  - Calls `document-list-item.php` for each document
  - Shows `document-list-empty.php` when no documents exist

- **`document-list-item.php`** - Single document card
  - Displays individual document in the list
  - Shows thumbnail, title, date, file size, excerpt
  - Includes "Open Document" button

- **`document-list-empty.php`** - No documents message
  - Displayed when user has no accessible documents

### Document View

- **`document-view.php`** - Single document wrapper
  - Contains back link and calls header/comments templates
  - Main container for single document display

- **`document-view-header.php`** - Document header section
  - Shows thumbnail, title, publication date
  - Displays document description
  - Includes download button

- **`document-view-comments.php`** - Comments section
  - Lists existing comments
  - Shows comment form (if open)
  - Displays "conversation closed" message (if applicable)

### Other Templates

- **`login-notice.php`** - Login required notice
  - Shown when user needs to log in or lacks access
  - Customizable message and optional redirect link

## Template Variables

Each template receives specific variables from the shortcode. See inline documentation in template files for complete details.

### document-list.php

```php
$documents       // WP_Query object with documents
$current_user_id // Current user ID
$is_admin        // Boolean: Is user admin?
$view_url        // URL to view page
```

### document-list-item.php

```php
$doc_id          // Document post ID
$view_url        // Base view URL
$current_user_id // Current user ID
$is_admin        // Is user admin?
```

### document-view.php

```php
$doc_id        // Document post ID
$document      // WP_Post object
$back_url      // Back to list URL
$download_url  // Secure download URL
$has_file      // Does document have file?
$comments_data // Array with comments configuration
```

### document-view-header.php

```php
$doc_id       // Document post ID
$document     // WP_Post object
$download_url // Secure download URL
$has_file     // Does document have file?
```

### document-view-comments.php

```php
$doc_id        // Document post ID
$has_comments  // Are there comments?
$comments_open // Are comments open?
$all_comments  // Array of comment objects
```

### login-notice.php

```php
$message             // Message to display
$redirect_to         // Redirect URL after login
$show_embedded_form  // Show embedded login form?
```

## Hooks

### Filters

**`dv_get_template_args`**
Modify template data before it's passed to the template.

```php
add_filter( 'dv_get_template_args', function( $args, $template_name ) {
    if ( 'document-list-item.php' === $template_name ) {
        // Add custom data
        $args['custom_field'] = 'custom_value';
    }
    return $args;
}, 10, 2 );
```

**`dv_locate_template`**
Change template location.

```php
add_filter( 'dv_locate_template', function( $template, $template_name, $template_path ) {
    // Use different location for specific template
    if ( 'document-list.php' === $template_name ) {
        return get_stylesheet_directory() . '/custom-templates/' . $template_name;
    }
    return $template;
}, 10, 3 );
```

**`dv_get_template`**
Override template path completely.

```php
add_filter( 'dv_get_template', function( $template, $template_name, $args, $template_path, $default_path ) {
    // Complete override
    return $template;
}, 10, 5 );
```

**`dv_load_template_args`**
Filter template arguments just before loading.

```php
add_filter( 'dv_load_template_args', function( $args, $template ) {
    // Modify args right before template loads
    return $args;
}, 10, 2 );
```

### Actions

**`dv_before_template_part`**
Fires before template is loaded.

```php
add_action( 'dv_before_template_part', function( $template, $args ) {
    if ( strpos( $template, 'document-list.php' ) !== false ) {
        echo '<div class="custom-wrapper">';
    }
}, 10, 2 );
```

**`dv_after_template_part`**
Fires after template is loaded.

```php
add_action( 'dv_after_template_part', function( $template, $args ) {
    if ( strpos( $template, 'document-list.php' ) !== false ) {
        echo '</div><!-- .custom-wrapper -->';
    }
}, 10, 2 );
```

**`dv_after_login_form_wrapper`**
Fires at the top of the `.dv-login-form-wrapper` div, before the login form. Useful for prepending content (e.g. a registration form or a notice).

```php
add_action( 'dv_after_login_form_wrapper', function() {
    echo do_shortcode( '[gravityforms id="1"]' );
} );
```

**`dv_after_login_form`**
Fires inside the `.dv-login-form-wrapper` div, after the `<form>` element. Useful for appending content below the login form.

```php
add_action( 'dv_after_login_form', function() {
    echo '<p class="custom-note">Need an account? Contact us.</p>';
} );
```

## Login Form Content via Settings (no-code)

Both hooks above also have a corresponding settings field under **Private Documents → Settings → Login Form Settings**, designed for users who do not want to write code.

| Setting | Hook it feeds | Position |
|---|---|---|
| **Content Before Login Form** | `dv_after_login_form_wrapper` | Inside the wrapper, above the form |
| **Content After Login Form** | `dv_after_login_form` | Inside the wrapper, below the form |

Both fields accept plain HTML and WordPress shortcodes. Content is sanitized with `wp_kses_post` and processed with `do_shortcode()`.

**Example: add a Gravity Forms registration form above the login:**

Paste in the "Content Before Login Form" field:
```
[gravityforms id="3"]
```

**Example: add a note below the login:**

Paste in the "Content After Login Form" field:
```html
<p style="text-align:center; margin-top:1em;">
    Don't have an account? <a href="/contact">Contact us</a>.
</p>
```

> **Priority note:** the settings-based content is added at hook priority 10 (default). Code-based `add_action()` calls using a different priority will execute before or after it accordingly.

## Template Functions

### dv_get_template()

Load a template file.

```php
dv_get_template(
    'document-list-item.php',
    array(
        'doc_id'  => 123,
        'view_url' => 'https://example.com/view',
    )
);
```

### dv_get_template_html()

Get template output as string.

```php
$html = dv_get_template_html(
    'login-notice.php',
    array(
        'message' => 'Please log in',
    )
);
echo $html;
```

### dv_locate_template()

Find template file path.

```php
$path = dv_locate_template( 'document-list.php' );
// Returns theme path if exists, otherwise plugin path
```

## Example Customizations

### Example 1: Custom Document Card Layout

Put thumbnail on top instead of side-by-side:

```php
// Copy to: themes/your-theme/dv-private-docs/document-list-item.php

<div class="dv-private-doc-item custom-layout" data-doc-id="<?php echo esc_attr( $doc_id ); ?>">
    <?php if ( has_post_thumbnail( $doc_id ) ) : ?>
        <div class="dv-doc-thumbnail">
            <?php echo get_the_post_thumbnail( $doc_id, 'medium' ); ?>
        </div>
    <?php endif; ?>

    <div class="dv-doc-content">
        <h3><?php echo esc_html( get_the_title( $doc_id ) ); ?></h3>
        <!-- Rest of content -->
    </div>
</div>
```

### Example 2: Add Custom Field to List

```php
// Copy to: themes/your-theme/dv-private-docs/document-list-item.php

<div class="dv-private-doc-item">
    <div class="dv-doc-content">
        <h3><?php echo esc_html( get_the_title( $doc_id ) ); ?></h3>

        <?php
        // Display custom field
        $custom_field = get_post_meta( $doc_id, 'my_custom_field', true );
        if ( $custom_field ) :
        ?>
            <p class="custom-field">
                <strong>Custom Info:</strong> <?php echo esc_html( $custom_field ); ?>
            </p>
        <?php endif; ?>

        <!-- Rest of template -->
    </div>
</div>
```

### Example 3: Change Empty State Message

```php
// Copy to: themes/your-theme/dv-private-docs/document-list-empty.php

<div class="dv-private-docs-empty">
    <div class="empty-state-icon">
        <svg><!-- Your custom icon --></svg>
    </div>
    <h3>No Documents Yet</h3>
    <p>Check back soon for new documents assigned to you.</p>
</div>
```

### Example 4: Customize Download Button

```php
// Copy to: themes/your-theme/dv-private-docs/document-view-header.php

<!-- Modify just the download section -->
<?php if ( $has_file ) : ?>
    <div class="custom-download-section">
        <a href="<?php echo esc_url( $download_url ); ?>" class="custom-download-btn">
            <svg class="download-icon"><!-- Icon --></svg>
            <span><?php esc_html_e( 'Download PDF', 'dv_private_docs' ); ?></span>
            <span class="file-size"><?php echo esc_html( dv_get_file_size( dv_get_document_file_path( $doc_id ) ) ); ?></span>
        </a>
    </div>
<?php endif; ?>
```

## CSS Customization

All templates use CSS classes that you can target in your theme's stylesheet:

### Document List Classes

- `.dv-private-docs-list` - List container
- `.dv-private-doc-item` - Individual document card
- `.dv-doc-thumbnail` - Thumbnail wrapper
- `.dv-doc-content` - Content wrapper
- `.dv-doc-meta` - Metadata (date, file size)
- `.dv-doc-assigned-notice` - Admin assignment notice
- `.dv-doc-excerpt` - Document excerpt
- `.dv-private-docs-empty` - Empty state

### Document View Classes

- `.dv-private-doc-view` - Main container
- `.dv-back-link` - Back link
- `.dv-doc-header` - Document header
- `.dv-doc-thumbnail-wrapper` - Thumbnail wrapper
- `.dv-doc-date` - Publication date
- `.dv-doc-description` - Document description
- `.dv-download-button` - Download button
- `.dv-file-unavailable` - File unavailable message
- `.dv-doc-comments` - Comments section
- `.dv-comments-list` - Comments list
- `.dv-comment-form` - Comment form
- `.dv-conversation-closed` - Closed conversation notice

### Other Classes

- `.dv-private-docs-notice` - Notice messages
- `.dv-notice-error` - Error notice
- `.comment-moderation` - Moderation notice

## Best Practices

1. **Always copy templates to your theme** - Never edit plugin files directly
2. **Check for updates** - When updating the plugin, compare template changes
3. **Maintain accessibility** - Keep ARIA labels and semantic HTML
4. **Test thoroughly** - Test logged-in, logged-out, admin, and user views
5. **Preserve data attributes** - Some features rely on `data-*` attributes
6. **Keep CSS classes** - Many classes are used for functionality, not just styling
7. **Use filters for data** - Use `dv_get_template_args` filter instead of modifying templates when possible
8. **Child theme safe** - Place templates in child theme for update safety

## Troubleshooting

### Template not overriding

**Check directory structure:**
```
wp-content/themes/your-theme/dv-private-docs/document-list.php ✓
wp-content/themes/your-theme/templates/document-list.php       ✗ Wrong path
```

**Check file permissions:**
Ensure template files are readable by WordPress.

### Variables not available

Make sure you're using the correct variable names as documented in each template file.

### PHP errors

Check that you haven't removed any required PHP opening/closing tags or syntax.

## Support

For issues or questions about template customization:
- Review inline template documentation
- Check available hooks and filters
- Test in default WordPress theme first
- Disable other plugins to isolate conflicts

## Version Compatibility

Template system introduced in: **v1.0.0**

When updating the plugin, review template changes in the changelog to ensure your customizations remain compatible.
