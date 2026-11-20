# DV Private Documents: user manual

Secure document management with user assignment, protected downloads, and comments.

## Description

DV Private Documents provides a secure private documents area where administrators can assign documents to individual users with protected downloads and comment functionality.

## Features

- **Private Document Management**: Create and manage documents visible only to assigned users
- **Private File Storage**: Files kept in `wp-content/private-docs/`, outside the Media Library, protected by an `.htaccess` the plugin writes and checks
- **Protected Downloads**: Files are served only through a download handler that checks login, assignment and a nonce
- **User Assignment**: Assign each document to exactly one user
- **Comments System**: Two-way communication between admins and users on documents
- **Embedded Login Form**: Optional inline login form that posts to the standard login, so two-factor and login-limiting plugins keep working; optional Google reCAPTCHA v3 or Cloudflare Turnstile
- **User Profile Management**: Self-service profile editing without admin dashboard access
- **User Menu Shortcode**: Compact navigation menu for logged-in users
- **Smart Redirects**: Automatic post-login redirects for subscribers
- **Customizable Notifications**: Language-aware email templates with customizable admin notifications
- **Admin Dashboard**: Clean admin interface with custom columns showing assignments and file status
- **Multi-language Ready**: Full i18n support with translatable strings and WPML compatibility
- **Unstyled Mode**: Optional mode to remove all plugin styling for complete theme customization
- **Theme Customizable**: External CSS file for easy style overrides or full unstyled mode

## Requirements

- WordPress 6.3 or higher
- PHP 8.1 or higher
- Apache (or a server that honours `.htaccess`): see "Private File Storage" below
- WPML and Complianz are optional

## Installation

1. **Upload the plugin**
   - Upload the `dv-private-docs` folder to `/wp-content/plugins/`
   - Or install via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate the plugin**
   - Go to Plugins in WordPress admin
   - Find "DV Private Documents" and click "Activate"

3. **Automatic setup**
   - Upon activation, the plugin automatically creates:
     - Private storage directory: `wp-content/private-docs/`
     - `.htaccess` file to block direct access
     - Protection files

## Setup

### Step 1: Create the two pages

1. **Pages → Add New**, title "My Documents" (or your own), content `[dv_private_docs]`, publish
2. **Pages → Add New**, title "View Document" (or your own), content `[dv_private_doc_view]`, publish

### Step 2: Select them in the settings

**Private Documents → Settings → General Settings**: choose "My Documents" as Documents List Page and "View Document" as Document View Page. The list links each document to the view page with `?doc=ID`, and the view page links back to the list. On a WPML site, switch language in the admin bar and select the translated pages for each language.

### Step 3: Optional pages

A "My Profile" page with `[dv_user_profile]`, selected as User Profile Page.

### Step 4: Configure Navigation (Optional)

Add a link to "My Documents" in your main menu or user dashboard so users can easily access their documents.

## Plugin Settings

Go to **Private Documents → Settings** to configure the plugin options.

### General Settings

#### Hide Admin Bar for Subscribers
- Hide the WordPress admin bar for users with the Subscriber role
- Provides a cleaner frontend experience for subscribers

#### Show Login Message
- Show a login message on `[dv_private_docs]` shortcode for non-logged-in users
- If disabled, non-logged-in users see nothing
- If enabled, they see a message with a login link or form (depending on Login Form Settings)

#### Notification Email
- Email address to receive notifications when users comment on documents
- Leave empty to use the default WordPress admin email

#### Notify Admin on User Comments
- Enable or disable email notifications to administrators when users add comments
- When enabled, admins receive notifications about new user comments on documents
- Useful for managing communication workflows

#### Documents List Page
- Select the page containing the `[dv_private_docs]` shortcode
- Used as the default URL for viewing individual documents
- **WPML**: Set different pages per language by switching language in admin bar

#### Document View Page
- Select the page containing the `[dv_private_doc_view]` shortcode
- Used as the default "back" URL when viewing documents
- **WPML**: Set different pages per language

#### User Profile Page
- Select the page containing the `[dv_user_profile]` shortcode
- Used for linking users to their profile management page
- Enables self-service profile editing without wp-admin access
- **WPML**: Set different pages per language

#### Enable Unstyled Mode
- Remove all plugin styling to allow full theme customization
- When enabled, the plugin skips loading CSS and removes all inline styles
- All semantic CSS classes remain for custom styling
- Ideal for web designers who want complete control over appearance

### Appearance Settings

Configure the visual appearance of the plugin frontend.

#### Color Scheme
- Choose between Auto, Light, or Dark mode for the plugin frontend
- **Auto (default)**: Automatically follows the user's operating system dark/light mode preference (via `prefers-color-scheme` media query)
- **Light**: Always displays in light mode regardless of system preference
- **Dark**: Always displays in dark mode regardless of system preference

The color scheme affects all plugin frontend elements including:
- Document lists and cards
- Document view pages
- Login forms
- User profile forms
- User menu
- Buttons and interactive elements

The dark mode implementation uses CSS variables that intelligently adapt colors for optimal readability in both light and dark environments.

### Login Form Settings

#### Enable Embedded Login Form
- Show an inline login form instead of a simple login link
- The form posts back to the page it is on, and the plugin signs the user in with `wp_signon()`, the same function behind `wp-login.php`: two-factor plugins, login limiters and fail2ban loggers hooked to the WordPress login apply to it without any extra setup. Visitors never go through `wp-login.php`, so whatever guards that URL (HTTP authentication, an IP allowlist, a CDN challenge) does not stand in front of them
- With a two-factor plugin that asks for the code after the password (tested with WP 2FA), the code screen appears and the user then goes back to the page they came from
- A wrong username or password, or a failed anti-bot check, comes back to the same page with a specific message. Any other error (a lockout, another plugin's captcha, a two-factor plugin that only works on its own login screen) shows a generic message with a link to the login page
- Logins are accepted only on the documents list, document view and profile pages set in the settings (all languages; filter `dv_login_form_page_ids`), only from this site, and those pages cannot be framed by other sites
- Plugins that protect only the `wp-login.php` screen (rather than the WordPress login itself) do not cover the form
- Fields that other plugins add to the standard login form (the `login_form` action, e.g. a captcha) are printed in this form too; their script must also load on the front end

#### Anti-bot Protection
- None, Google reCAPTCHA v3 or Cloudflare Turnstile, on the embedded form only
- `wp-login.php` itself is not covered: protect it with a security plugin
- A token is accepted only if the service confirms it, it was issued for the `login` action and for this site's host; reCAPTCHA also needs a score of at least 0.5 (filter `dv_recaptcha_min_score`)
- With Complianz, the service loads only after consent: category `marketing` for reCAPTCHA (filter `dv_recaptcha_consent_category`), `functional` for Turnstile (filter `dv_turnstile_consent_category`). Without consent the login is refused, not let through without the check

#### reCAPTCHA Site Key
- Your Google reCAPTCHA v3 site key
- Get keys from [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin)

#### reCAPTCHA Secret Key
- Your Google reCAPTCHA v3 secret key
- Keep this key secret and never share it publicly

#### Turnstile Site Key and Secret Key
- From the Turnstile section of the Cloudflare dashboard; add the site's domain to the widget

### Redirect Settings

#### Subscriber Redirect Page
- Where subscribers land after logging in when the login has no destination of its own (or a dashboard one), e.g. from a plain `wp-login.php`
- Typically set to the documents list page
- Administrators and editors are not redirected
- A login that asks for a page of the site goes to that page: the embedded form always sends the page it is on, so users who log in from it stay there
- **WPML**: Set different redirect pages per language

### Email Notification Templates

Customize email notifications sent to users and administrators.

#### Assignment Notification Template
- Email sent to users when a document is assigned to them
- Supports placeholders: `{user_name}`, `{document_title}`, `{document_url}`
- **WPML**: Configure different templates per language
- Default provides a professional, clear notification format

#### Comment Notification Template
- Email sent to users/admins when a new comment is added
- Supports placeholders: `{user_name}`, `{comment_author}`, `{document_title}`, `{comment_text}`, `{document_url}`
- **WPML**: Configure different templates per language
- Used for both user-to-admin and admin-to-user comment notifications

**Available Placeholders**:
- `{user_name}` - Recipient's display name
- `{document_title}` - Title of the document
- `{document_url}` - Link to view the document
- `{comment_author}` - Name of person who wrote the comment
- `{comment_text}` - Content of the comment

**Example Assignment Template**:
```
Hello {user_name},

A new document has been assigned to you:

{document_title}

You can view and download it here:
{document_url}

Best regards
```

**Example Comment Template**:
```
Hello {user_name},

New comment from {comment_author} on document "{document_title}":

{comment_text}

View and reply here:
{document_url}

Best regards
```

## Usage

### For Administrators

#### Creating a New Document

1. Go to **Private Documents → Add New**
2. Enter document title (required)
3. Add description in the editor (optional)
4. **Set featured image** as document thumbnail (optional)
5. **Assign to User** (required):
   - Select the user from the dropdown
6. **Upload Document File** (required):
   - Click "Upload Document File"
   - Select file from media library or upload new
   - The file will be shown in the metabox (remember to save)
7. Click **Publish**

**Important Notes**:
- Documents cannot be published without both a user assignment and a file
- When you save the document, the file is **automatically moved to private storage**
- The file is **permanently deleted from the Media Library** for security
- Files in private storage are not publicly accessible

#### Managing Documents

- **View all documents**: Go to Private Documents in admin menu
- **See assignments**: The "Assigned User" column shows who can access each document
- **Check file status**: The "File Status" column indicates if files are ready for download
- **Edit anytime**: Click on a document to modify assignment, file, or content

#### Commenting on Documents

- Open the document detail page on the frontend
- Scroll to the comments section
- Add your comment to communicate with the assigned user
- Admins can see and comment on all documents

### For Users

#### Viewing Documents

1. Log in to your account
2. Navigate to the documents list page (URL set in Step 1)
3. See all documents assigned to you
4. Click "Open Document" to view details

#### Downloading Documents

1. Open a document
2. Click "Download Document" button
3. File downloads securely to your device

**Note**: Download links are protected and expire. They only work for the assigned user.

#### Commenting on Documents

1. Open a document
2. Scroll to the comments section
3. Add your comment
4. Submit to communicate with administrators

## Common Use Cases

### Complete Self-Service Portal

Configure the plugin for a complete subscriber experience without wp-admin access:

1. **Enable all self-service features** in Settings:
   - ✅ Enable Embedded Login Form
   - ✅ Anti-bot Protection: reCAPTCHA or Turnstile (recommended)
   - ✅ Set Subscriber Redirect Page to "My Documents"
   - ✅ Hide Admin Bar for Subscribers

2. **Create these pages**:
   - **My Documents** - `[dv_private_docs]`
   - **View Document** - `[dv_private_doc_view]`
   - **My Profile** - `[dv_user_profile]`

3. **Add navigation**:
   - **Option A**: Add `[dv_user_menu]` shortcode to header/sidebar for compact menu
   - **Option B**: Add links to "My Documents" and "My Profile" in main menu

4. **Configure profile page setting**:
   - In Settings → select "My Profile" page as User Profile Page
   - Enables automatic profile linking throughout plugin

**Result**: Subscribers can log in, view documents, update profile, and change password, all without ever seeing wp-admin. The user menu provides quick navigation to all features.

### Simple Document Portal (Minimal Setup)

For basic document sharing without extra features:

1. **Settings**:
   - ❌ Disable Embedded Login Form (use standard wp-login.php)
   - ✅ Set document list and view pages
   - ❌ No redirect needed (WordPress default behavior)

2. **Create pages**:
   - **Documents** - `[dv_private_docs]`
   - **Document** - `[dv_private_doc_view]`

**Result**: Clean, simple document portal with standard WordPress login.

### Multilingual Document Portal (WPML)

For multilingual sites using WPML:

1. **WPML Configuration**:
   - Set `dv_private_doc` CPT to **"Not translatable"** (recommended)
   - Translate your "Documents" and "Document View" pages
   - Translate "My Profile" page if using profile shortcode

2. **Plugin Settings**:
   - Switch language in admin bar
   - Set language-specific pages for each language
   - Set language-specific redirect pages

3. **User Experience**:
   - Users see interface in their preferred language
   - Email notifications sent in recipient's language
   - Document links point to translated pages

**Result**: Seamless multilingual experience with single document threads per user.

### Secure Corporate Document Distribution

Maximum security configuration for sensitive documents:

1. **Security Settings**:
   - ✅ Anti-bot Protection on the embedded login form
   - ✅ Hide Admin Bar for Subscribers
   - ✅ A two-factor plugin for administrators (and, if you want, available to users)

2. **Server Configuration** (recommended):
   - Check that `wp-content/private-docs/` is not reachable from the web (the plugin's weekly probe does it for you, see "Private File Storage")
   - Enable SSL/HTTPS
   - Protect `wp-login.php` with a login-limiting or security plugin

3. **User Management**:
   - Use strong passwords for all accounts
   - Regularly audit document assignments
   - Check the download counts in the documents list if needed

**Result**: a document area whose protection does not depend on a single layer.

## Technical Details

### Private File Storage

- The file chosen for a document is copied to `wp-content/private-docs/` and the attachment is then **deleted from the Media Library**, together with any copy a translation plugin made of it (WPML duplicates uploads into every language). An hourly sweep removes any attachment still linked to a private document, keeping a copy in private storage first
- Filenames are hashed and unpredictable: `dvdoc-{post_id}-{attachment_id}-{hash}.{ext}`
- Original filename is preserved in metadata for downloads (Content-Disposition header)
- The folder is inside the web root: what keeps it private is the `.htaccess` (`Require all denied`, PHP execution off) and the `index.php` the plugin writes there, and checks every hour. If they cannot be written, the file is not saved
- A weekly HTTP probe writes a file with a random token in the folder and requests it: if the token comes back, the server is ignoring `.htaccess` (e.g. Nginx, or `AllowOverride None`) and an admin notice says so. On Nginx, deny the folder in the server configuration
- If you keep the site in git, version `private-docs/.htaccess` and `private-docs/index.php` and ignore the documents
- Deleting a document deletes its file

### Security Features

- **Nonce verification** on all forms that change data and on downloads (the login form, like `wp-login.php`, relies on the credentials and the optional anti-bot check)
- **Capability checks** for admin operations
- **Access control** enforced via `dv_user_can_access_doc()` function
- **Sanitization** of all inputs
- **Escaping** of all outputs
- **No public access** to uploaded files
- **Protected endpoints** for downloads
- **Comment validation** to prevent unauthorized submissions

### Access Control Logic

Users can access a document if:
- They have `manage_options` capability (administrators), OR
- They are the assigned user (`dv_assigned_user_id` matches their user ID)

### Shortcodes

#### `[dv_private_docs]`

Displays list of documents for the current user.

**Parameters**: none. Documents link to the Document View Page set in the settings (if none is set, to the current page).

**Features**:
- Shows only documents assigned to the logged-in user
- Admins see all documents with user assignment info
- Displays thumbnail, title, date, file size, excerpt
- "Open Document" button for each item
- Login prompt for non-logged-in users
- Data attributes for filtering: `data-doc-id`, `data-user-id`, `data-file-type`

**Example**:
```
[dv_private_docs]
```

#### `[dv_private_doc_view]`

Displays single document view with download and comments.

**Parameters**: none. The "Back to documents" link goes to the Documents List Page set in the settings (if none is set, to the home page).

**Features**:
- Back navigation link
- Document title, date, description
- Featured image (if set)
- Download button
- Comments section with form
- Access control validation

**Example**:
```
[dv_private_doc_view]
```

#### `[dv_user_profile]`

Displays user profile management form for logged-in users.

**Parameters**: None

**Features**:
- **Username**: Display only (cannot be changed)
- **First Name**: Editable text field
- **Last Name**: Editable text field
- **Nickname**: Editable text field
- **Organization**: Custom text field (optional)
- **Email**: Editable with verification required
- **Website**: Editable URL field
- **Preferred Language**: Dropdown (if WPML is active)
- **Password Change**: Secure password update with current password verification

**Email Change Security**:
- When user changes email, a verification link is sent to the new address
- Email is not updated until the user clicks the verification link
- Verification links expire after 24 hours
- Prevents unauthorized email changes

**Password Change Security**:
- Requires current password for verification
- New password must match confirmation
- Minimum 8 characters required

**Example**:
```
[dv_user_profile]
```

**Typical Usage**:
Create a "My Profile" page with this shortcode so subscribers can manage their information without accessing wp-admin.

#### `[dv_user_menu]`

Displays a compact navigation menu for logged-in users.

**Parameters**: None

**Features**:
- **Login State Detection**: Shows different content based on login status
- **User Greeting**: Displays "Hello, [User Name]" for logged-in users
- **Quick Navigation**: Links to documents list and user profile
- **Logout Button**: Secure logout functionality
- **Login Prompt**: For non-logged-in users, shows link to login
- **Responsive**: Adapts to mobile and desktop layouts
- **WPML Compatible**: Uses language-aware page URLs

**Example**:
```
[dv_user_menu]
```

**Typical Placement**:
- Sidebar widget area
- Header template
- Above main content area
- User dashboard page

**What Users See**:
- **Logged In**: "Hello, John Doe | My Documents | My Profile | Logout"
- **Logged Out**: a "Login" link

### Post Meta Keys

**Document Meta:**
- `dv_assigned_user_id` (int) - ID of the assigned user
- `dv_attachment_id` (int) - Media library attachment ID
- `dv_private_relpath` (string) - Relative path to private file
- `dv_original_filename` (string) - Original filename for downloads
- `dv_download_count` (int) - Number of times document was downloaded

**User Meta:**
- `dv_organization` (string) - User's organization (custom field)
- `icl_admin_language` (string) - User's preferred language (WPML)
- `dv_pending_email` (array) - Pending email change awaiting verification

### Download Endpoint

Protected download URL structure:
```
/wp-admin/admin-post.php?action=dv_private_doc_download&doc={ID}&_wpnonce={NONCE}
```

**Security**:
- Requires valid nonce
- Requires logged-in user
- Validates document access
- Streams file in chunks
- Never reveals filesystem paths

## Customization

### Styling with CSS

The plugin includes an external stylesheet (`assets/css/frontend.css`) that can be easily customized by your theme.

#### Available CSS Classes

**Documents List:**
- `.dv-private-docs-list` - Documents list container
- `.dv-private-doc-item` - Individual document item
- `.dv-doc-thumbnail` - Thumbnail wrapper
- `.dv-doc-content` - Document content

**Document View:**
- `.dv-private-doc-view` - Single document view container
- `.dv-doc-header` - Document header section
- `.dv-doc-comments` - Comments section

**Login Form:**
- `.dv-login-form-wrapper` - Login form container
- `.dv-login-form-wrapper h3` - Form heading
- `.dv-login-form-wrapper label` - Form labels
- `.dv-login-form-wrapper input[type="text"]` - Text inputs
- `.dv-login-form-wrapper input[type="password"]` - Password inputs
- `.dv-login-form-wrapper .button-primary` - Login button
- `.dv-login-links` - Password recovery link container
- `#dv-login-message` - Login feedback message
- `#dv-login-message.success` - Success message state
- `#dv-login-message.error` - Error message state

**User Profile Form:**
- `.dv-user-profile` - Profile form container
- `.dv-user-profile h3` - Section headings
- `.dv-user-profile label` - Form labels
- `.dv-user-profile input` - Form inputs
- `.dv-user-profile select` - Dropdown fields
- `.dv-user-profile .description` - Help text
- `.dv-profile-message` - Feedback message container
- `.dv-profile-message.success` - Success state
- `.dv-profile-message.error` - Error state

**Notice Messages:**
- `.dv-private-docs-notice` - General notice container
- `.dv-private-docs-notice p` - Notice paragraphs
- `.dv-private-docs-notice .button` - Notice action button

#### Method 1: Override Specific Styles

Add custom CSS to your theme's `style.css` or via Customizer:

```css
/* Customize login form */
.dv-login-form-wrapper {
    max-width: 500px;
    background: #f9f9f9;
    border: 2px solid #your-brand-color;
}

.dv-login-form-wrapper .button-primary {
    background: #your-brand-color;
    border-color: #your-brand-color;
}

/* Customize user profile form */
.dv-user-profile h3 {
    color: #your-brand-color;
    border-bottom-color: #your-brand-color;
}

/* Customize notice messages */
.dv-private-docs-notice {
    border-left-color: #your-brand-color;
    background: #your-light-color;
}

/* Customize success/error messages */
.dv-profile-message.success {
    border-left-color: #your-success-color;
    background: #your-success-bg;
}

.dv-profile-message.error {
    border-left-color: #your-error-color;
    background: #your-error-bg;
}
```

#### Method 2: Replace Plugin Stylesheet

To completely replace the plugin's styles with your own:

```php
// In your theme's functions.php
add_action( 'wp_enqueue_scripts', function() {
    // Remove plugin stylesheet
    wp_dequeue_style( 'dv-private-docs-frontend' );
    wp_deregister_style( 'dv-private-docs-frontend' );

    // Enqueue your custom stylesheet
    wp_enqueue_style(
        'my-custom-docs-style',
        get_stylesheet_directory_uri() . '/css/custom-docs.css',
        array(),
        '1.0.0'
    );
}, 100 );
```

Then create `css/custom-docs.css` in your theme with your own styles.

#### Method 3: Use Theme's Existing Classes

The plugin uses standard WordPress button classes (`.button`, `.button-primary`, `.button-large`) which automatically inherit your theme's button styles.

#### Method 4: Unstyled Mode (Complete Theme Control)

For web designers who want **complete control** over the plugin's appearance without fighting CSS specificity:

**Enable Unstyled Mode** in Settings → DV Private Docs → General Settings:
- ✅ Check "Enable Unstyled Mode"
- Save settings

**What Happens**:
- ❌ Plugin CSS file (`frontend.css`) is **not loaded**
- ⚠️ A few notices (e.g. on the profile page) keep their inline styles
- ✅ All **semantic CSS classes remain** for styling

**Available CSS Classes for Custom Styling**:

**Documents List (`[dv_private_docs]`)**:
- `.dv-private-docs-list` - Main container
- `.dv-private-doc-item` - Individual document card
- `.dv-doc-thumbnail` - Thumbnail wrapper
- `.dv-doc-content` - Content section
- `.dv-private-docs-empty` - Empty state message

**Document View (`[dv_private_doc_view]`)**:
- `.dv-private-doc-view` - Main container
- `.dv-doc-header` - Header section
- `.dv-doc-description` - Description content
- `.dv-doc-comments` - Comments section
- `.dv-comment-form` - Comment form wrapper
- `.comment-body` - Individual comment
- `.comment-meta` - Comment metadata
- `.comment-author.vcard` - Comment author
- `.comment-content` - Comment text

**User Profile (`[dv_user_profile]`)**:
- `.dv-user-profile` - Form container
- `.dv-profile-message` - Success/error messages
- `.description` - Help text

**User Menu (`[dv_user_menu]`)**:
- `.dv-user-menu` - Menu container
- `.dv-user-menu-logged-out` - Not logged in state
- `.dv-user-menu-logged-in` - Logged in state
- `.dv-menu-user-name` - User name display
- `.dv-menu-nav` - Navigation links
- `.dv-menu-link` - Individual links
- `.dv-menu-login`, `.dv-menu-documents`, `.dv-menu-profile`, `.dv-menu-logout` - Specific links

**Login Form (`[dv_private_docs]` when logged out)**:
- `.dv-login-form-wrapper` - Form container
- `.dv-login-links` - Links container

**Notice Messages**:
- `.dv-private-docs-notice` - Notice boxes

**Example Custom CSS**:
```css
/* Your theme's custom styling */
.dv-private-doc-item {
    background: var(--theme-card-bg);
    border: 1px solid var(--theme-border);
    border-radius: var(--theme-radius);
    padding: var(--theme-spacing);
    margin-bottom: var(--theme-gap);
}

.dv-doc-header {
    background: var(--theme-header-bg);
    padding: var(--theme-header-padding);
}

.dv-user-menu {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-family: var(--theme-font);
}
```

**When to Use Unstyled Mode**:
- ✅ Building a custom theme with specific design system
- ✅ Using CSS frameworks (Tailwind, Bootstrap, etc.)
- ✅ Need pixel-perfect control over appearance
- ✅ Want to match existing site design exactly
- ❌ Don't use if you're happy with default styling

**Backwards Compatibility**:
- Unstyled mode is **disabled by default**
- Existing sites continue working with default styles
- No breaking changes when updating plugin

#### Method 5: Template Override System (Advanced HTML Customization)

For developers who need **complete control over the HTML structure** (not just CSS styling), the plugin includes a WooCommerce-style template override system.

**How It Works**:
1. Template files are stored in the plugin's `/templates/` directory
2. Copy any template to your theme to customize it
3. Your theme's version automatically overrides the plugin's version
4. Update-safe: Your customizations survive plugin updates

**Template Loading Order**:
1. **Child theme**: `your-child-theme/dv-private-docs/{template-name}.php`
2. **Parent theme**: `your-theme/dv-private-docs/{template-name}.php`
3. **Plugin**: `dv-private-docs/templates/{template-name}.php`

**Available Templates**:

*Document List*:
- `document-list.php` - Main list wrapper (contains the loop)
- `document-list-item.php` - Individual document card
- `document-list-empty.php` - Empty state message

*Document View*:
- `document-view.php` - Single document wrapper
- `document-view-header.php` - Document header section (title, date, download button)
- `document-view-comments.php` - Comments section (list + form)

*Other*:
- `login-notice.php` - Login required/access denied notices

**Quick Start Example**:

```bash
# 1. Create directory in your theme
mkdir -p wp-content/themes/your-theme/dv-private-docs/

# 2. Copy template you want to customize
cp wp-content/plugins/dv-private-docs/templates/document-list-item.php \
   wp-content/themes/your-theme/dv-private-docs/

# 3. Edit the copied file to customize HTML structure
```

**Example: Customize Document Card Layout**

```php
<?php
// File: your-theme/dv-private-docs/document-list-item.php
// Put thumbnail on top instead of side-by-side

defined( 'ABSPATH' ) || exit;
?>

<div class="dv-private-doc-item custom-layout" data-doc-id="<?php echo esc_attr( $doc_id ); ?>">
    <!-- Thumbnail on top -->
    <?php if ( has_post_thumbnail( $doc_id ) ) : ?>
        <div class="dv-doc-thumbnail">
            <?php echo get_the_post_thumbnail( $doc_id, 'large' ); ?>
        </div>
    <?php endif; ?>

    <!-- Content below -->
    <div class="dv-doc-content">
        <h3><?php echo esc_html( get_the_title( $doc_id ) ); ?></h3>

        <!-- Add custom field -->
        <?php $category = get_post_meta( $doc_id, 'document_category', true ); ?>
        <?php if ( $category ) : ?>
            <p class="doc-category">Category: <?php echo esc_html( $category ); ?></p>
        <?php endif; ?>

        <!-- Rest of template -->
        <a href="<?php echo esc_url( add_query_arg( 'doc', $doc_id, $view_url ) ); ?>"
           class="button button-primary">
            <?php esc_html_e( 'View Document', 'dv-private-docs' ); ?>
        </a>
    </div>
</div>
```

**Template Variables**:

Each template receives specific variables. For example, `document-list-item.php` receives:
- `$doc_id` - Document post ID
- `$view_url` - URL to view page
- `$current_user_id` - Current user ID
- `$is_admin` - Boolean: Is user admin?

See inline documentation in each template file for complete variable list.

**Developer Hooks**:

*Filters*:
```php
// Modify template data
add_filter( 'dv_get_template_args', function( $args, $template_name ) {
    if ( 'document-list-item.php' === $template_name ) {
        $args['custom_data'] = 'custom_value';
    }
    return $args;
}, 10, 2 );

// Override template location
add_filter( 'dv_locate_template', function( $template, $template_name ) {
    if ( 'document-list.php' === $template_name ) {
        return get_stylesheet_directory() . '/custom-path/' . $template_name;
    }
    return $template;
}, 10, 2 );
```

*Actions*:
```php
// Add HTML before template
add_action( 'dv_before_template_part', function( $template, $args ) {
    if ( strpos( $template, 'document-list.php' ) !== false ) {
        echo '<div class="custom-wrapper">';
    }
}, 10, 2 );

// Add HTML after template
add_action( 'dv_after_template_part', function( $template, $args ) {
    if ( strpos( $template, 'document-list.php' ) !== false ) {
        echo '</div><!-- .custom-wrapper -->';
    }
}, 10, 2 );
```

**When to Use Template Overrides**:
- ✅ Need to change HTML structure (add/remove/reorder elements)
- ✅ Add custom fields or metadata to document display
- ✅ Integrate with page builders or custom layouts
- ✅ Create completely custom document card designs
- ❌ Don't use if you only need CSS styling (use Methods 1-4 instead)

**Best Practices**:
1. **Always copy to theme** - Never edit plugin files directly
2. **Keep data attributes** - Some features rely on `data-*` attributes
3. **Preserve CSS classes** - Many classes are functional, not just decorative
4. **Check for updates** - Compare template changes when updating plugin
5. **Test thoroughly** - Test as logged-in user, logged-out, admin, and regular user

**Complete Documentation**:

For detailed template documentation, variable references, and more examples, see:
`wp-content/plugins/dv-private-docs/templates/README.md`

#### Responsive Considerations

The plugin includes responsive styles for mobile devices (max-width: 600px):
- Login form adjusts padding and margins
- Profile form inputs expand to full width
- All containers adapt to smaller screens

Override these by adding your own media queries:

```css
@media screen and (max-width: 768px) {
    .dv-login-form-wrapper {
        margin: 10px;
        padding: 15px;
    }
}
```

### Translations

The plugin is translation-ready with text domain `dv-private-docs`.

**To translate**:
1. Use a plugin like Loco Translate
2. Or create `.po` and `.mo` files manually
3. Place in `dv-private-docs/languages/` directory

### WPML Multilingual Support

The plugin is fully compatible with WPML (WordPress Multilingual Plugin) for multilingual websites.

#### Recommended Configuration

**IMPORTANT**: For optimal functionality with this plugin's design (single document = single thread/file per user), configure WPML as follows:

1. **Set Custom Post Type as "Not translatable"**:
   - Go to **WPML → Settings → Post Types Translation**
   - Find `dv_private_doc` (Private Documents)
   - Set to **"Not translatable"**

This maintains a single document/thread per assigned user across all languages, which is the intended design for private document workflows.

#### Multilingual Page Setup

When using WPML, configure separate list/view pages for each language:

1. **Create translated versions** of your list and view pages:
   - Duplicate "My Documents" page for each language
   - Duplicate "View Document" page for each language
   - Add the same shortcodes to each translated page

2. **Configure page settings per language**:
   - Go to **Private Documents → Settings**
   - Switch language in WPML language switcher (admin bar)
   - Select the appropriate pages for that language
   - Repeat for each active language

The plugin automatically:
- Uses the correct page URLs based on current language
- Sends email notifications in the recipient's language
- Generates document links in the correct language

#### Email Notifications

Email notifications are automatically sent in the recipient's preferred language:
- Users: Language preference from WPML user meta
- Admins: WPML admin language setting
- Document links point to the translated view page

#### If You Set CPT as Translatable

If you choose to make `dv_private_doc` translatable:
- You'll have separate document posts per language
- The same file will be copied across translations (via wpml-config.xml)
- Comments will be per-translation (not merged across languages)
- The plugin will filter queries by language to prevent duplicates

**Note**: This configuration is not recommended for typical use cases where documents are assigned to specific users.

#### Technical Details

The plugin includes:
- `wpml-config.xml` for meta field synchronization
- Language-aware option storage (`dv_list_page_{lang}`, `dv_view_page_{lang}`)
- WPML helper functions in `includes/helpers.php`
- Automatic language switching for email generation

## Troubleshooting

### "Document could not be published" Error

**Cause**: Missing user assignment or file upload

**Solution**: Ensure both fields are filled before publishing

### Download Not Working

**Possible causes**:
1. File not copied to private storage
2. Insufficient permissions
3. Expired nonce (cached page: exclude the document pages from page cache)

**Solution**:
- Check "File Status" column in admin
- Re-save the document to copy file again
- Generate fresh download link

### Comments Not Appearing

**Possible causes**:
1. User not logged in
2. Comments closed on document
3. User doesn't have access

**Solution**: Verify user is logged in and has document access

### Private Directory Not Created

**Solution**: Manually create `wp-content/private-docs/` with write permissions

## Support

Open an issue on https://github.com/bluantinoo/dv-private-docs

## Changelog

See `readme.txt`.
