=== DV Private Documents ===
Contributors: bluantinoo
Tags: private documents, client portal, protected downloads, file sharing, wpml
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.3.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private document area: each document is assigned to one user, who downloads it through a protected link and can comment on it.

== Description ==

Administrators create a document, attach a file and assign it to one user. The user sees their own documents on a page of the site, downloads them through a link that checks who is asking, and can comment on each one. Nobody else can download the file, not even with its address.

= Features =

* **Private file storage** - files are copied to `wp-content/private-docs/` and removed from the Media Library, together with any copy a translation plugin made of them
* **Protected downloads** - served by a handler that checks login, assignment and a nonce; the files themselves are blocked by an `.htaccess` the plugin writes and checks
* **One user per document** - with a comment thread between the user and the administrators
* **Embedded login form** - posts to the standard WordPress login, so two-factor and login-limiting plugins keep working; optional Google reCAPTCHA v3 or Cloudflare Turnstile
* **User profile and menu** - self-service profile, email change with confirmation, compact user menu
* **WPML** - pages, redirects and email templates per language
* **Complianz** - the anti-bot service loads only after consent, and the login is refused (not let through unchecked) until it is given
* **Templates** - overridable from the theme, plus an unstyled mode

= What it is not =

* Not a file sharing tool for groups: a document belongs to exactly one user.
* Not a way to protect `wp-login.php`: the anti-bot check covers the embedded form only. Use a security plugin for the standard login screen.
* The storage folder is inside the web root. Its protection relies on the web server honouring `.htaccess` (Apache, LiteSpeed). On Nginx the folder must be denied in the server configuration: a weekly probe checks it and warns the administrators if the files can be downloaded.

= Use Cases =

* Corporate document distribution
* Client portals for agencies
* Student assignment delivery
* Secure file sharing with individual users

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/dv-private-docs/` or install via WordPress admin
2. Activate the plugin through the 'Plugins' menu
3. The plugin automatically creates the private storage directory
4. Create two pages with the shortcodes (see Setup section)
5. Configure settings under Private Documents → Settings

== Setup ==

= Quick Start =

1. **Create Document View Page**
   * Add new page with title "View Document"
   * Add shortcode: `[dv_private_doc_view]`
   * Publish and note the URL

2. **Create Documents List Page**
   * Add new page with title "My Documents"
   * Add shortcode: `[dv_private_docs]`
   * Publish

3. **Configure Settings**
   * Go to Private Documents → Settings
   * Select the pages you created
   * Configure login form and redirect options

4. **Add Documents**
   * Go to Private Documents → Add New
   * Enter title, assign to user, upload file
   * Publish

== Frequently Asked Questions ==

= How do I assign a document to a user? =

When creating or editing a document, select the user from the "Assign to User" dropdown in the Document Details metabox.

= Where do updates come from? =

From the GitHub releases of the plugin, not from wordpress.org. The site asks the GitHub API for the latest release about every 12 hours; only the zip attached to a release is installed. See the "Updates" section of the README on GitHub.

= Are files really protected? =

The files live in `wp-content/private-docs/`, inside the web root, with an `.htaccess` that denies every request and an `index.php`. The plugin rewrites both if they go missing, refuses to save a file if it cannot protect the folder, and once a week requests a test file over HTTP: if it is served, an admin notice says the server is ignoring `.htaccess`. Downloads go through a handler that checks login, assignment and a nonce.

If you keep the site in git, version `private-docs/.htaccess` and `private-docs/index.php`, and keep the documents out of the repository.

= Does it work with two-factor authentication? =

Yes. The embedded login form signs users in with `wp_signon()`, the function behind `wp-login.php`, so a two-factor plugin that asks for the code after the password shows its code screen and then sends the user back to the page they came from (tested with WP 2FA). Plugins that add the code field to the login screen with JavaScript (e.g. Wordfence Login Security) cannot finish there: the form shows a link to the login page (not tested). To let users turn on two-factor authentication without going to the dashboard, put the plugin's own setup shortcode on the profile page (for WP 2FA: `[wp-2fa-setup-form]`).

= What happens to the documents if I delete the plugin? =

Nothing, unless you check "Delete Data on Uninstall" in the settings. With that option on, deleting the plugin (not deactivating it) removes documents, comments, the files in the storage folder, the profile fields added by the plugin and all its settings.

= Can users download any document? =

No. Users can only download documents specifically assigned to them. Administrators can download all documents.

= Does it work with WPML? =

Yes, the plugin is fully compatible with WPML. You can set different pages per language and email notifications are sent in the recipient's language.

= Can I customize the appearance? =

Yes. The plugin includes external CSS that can be overridden by your theme. There's also an "Unstyled Mode" option that removes all plugin styling for complete theme control.

= Can I change the color scheme? =

Yes. Go to Settings → Appearance Settings → Color Scheme. You can choose between:
* **Auto** - Automatically follows the user's operating system dark/light mode preference
* **Light** - Always displays in light mode regardless of system preference
* **Dark** - Always displays in dark mode regardless of system preference

= How do I enable the login form? =

Go to Settings → Login Form Settings and enable "Embedded Login Form". Under "Anti-bot Protection" you can add Google reCAPTCHA v3 or Cloudflare Turnstile.

= Does it work with Complianz (or other cookie consent plugins)? =

Yes, the plugin integrates with Complianz out of the box. When Complianz is active, the anti-bot service is loaded and verified only after the user grants consent for its cookie category: `marketing` for Google reCAPTCHA (filter `dv_recaptcha_consent_category`), `functional` for Cloudflare Turnstile (filter `dv_turnstile_consent_category`; Complianz always grants that category).

If the user has not given (or has denied) consent:

* The anti-bot script is not loaded
* A notice is shown above the login form explaining that consent is required
* The login is blocked server-side until consent is granted
* A "Manage cookie preferences" link in the notice re-opens the Complianz banner

When the user accepts consent, the login page reloads automatically to re-evaluate state and load the anti-bot script.

You can change which Complianz category to check via the filter `dv_recaptcha_consent_category`, e.g. set it to `statistics` if your Complianz configuration classifies Google reCAPTCHA there:

`add_filter( 'dv_recaptcha_consent_category', function() { return 'statistics'; } );`

**Note**: if you use a page cache (WP Rocket, WP Fastest Cache, LiteSpeed, etc.), exclude the login page from caching, otherwise the consent notice may persist after the user accepts.

= Can I add extra content inside the login form area without writing code? =

Yes. Go to Settings → Login Form Settings. You will find two textarea fields:

* **Content Before Login Form**: paste HTML or any shortcode here. The content will appear inside the login box, above the form fields. Useful for notices, instructions, or a registration form via shortcode (e.g. a Gravity Forms form).
* **Content After Login Form**: same as above, but the content appears below the form. Useful for additional links, info boxes, or alternative sign-in options.

Both fields accept plain HTML and WordPress shortcodes. Unsafe tags are automatically stripped.

== Shortcodes ==

= [dv_private_docs] =

Displays list of documents for the current user.

Example: `[dv_private_docs]`

= [dv_private_doc_view] =

Displays single document view with download and comments.

Example: `[dv_private_doc_view]`

= [dv_user_profile] =

Displays user profile management form for logged-in users.

Example: `[dv_user_profile]`

= [dv_user_menu] =

Displays compact navigation menu for logged-in users.

Example: `[dv_user_menu]`

== Changelog ==

= 1.3.1 =
* New: "Turnstile Theme" setting (Auto, Light, Dark). Until now the widget always followed the visitor's device, so it could be dark on a light site.

= 1.3.0 =
* First public release.
* Changed: the embedded login form posts back to its page and signs users in with `wp_signon()` instead of an AJAX endpoint. Two-factor plugins, login limiters and fail2ban loggers now apply to it; with a two-factor plugin active, users who had to give a second factor could not log in from the form. Visitors do not go through `wp-login.php`. Logins are accepted only on the pages set in the settings and only from this site; those pages send `X-Frame-Options: SAMEORIGIN`. A failed login comes back to the same page with a message. The `dv_login` AJAX action is gone.
* Changed: fields other plugins add to the standard login form (`login_form` action) are printed in the embedded form.
* Changed: after logging in, a subscriber goes to the page the login asked for, if it is a page of the site; the Subscriber Redirect Page applies when there is no destination or a dashboard one. Users of the embedded form stay on the page they were on.
* Changed: the "Forgot your password?" link no longer carries the current page, so WordPress shows its confirmation after the request.
* New: Cloudflare Turnstile as an alternative to Google reCAPTCHA v3 ("Anti-bot Protection" setting). Existing reCAPTCHA settings keep working.
* New: "Delete Data on Uninstall" setting, off by default; `uninstall.php`.
* New: updates from the GitHub releases of the plugin.
* Security: reCAPTCHA tokens are accepted only for the `login` action, this site's host and with a score (default 0.5, filter `dv_recaptcha_min_score`).
* Security: the document save no longer accepts a request without a valid nonce from an administrator.
* Security: email change tokens are random, stored hashed and compared in constant time; the address is checked again before the change.
* Security: downloads send `X-Content-Type-Options: nosniff`.
* Security: storage protection rewritten: `.htaccess` (Apache 2.4 syntax) and `index.php` always written and checked every hour, weekly HTTP probe, file not saved if the folder cannot be protected.
* Security: copies of private documents left in the Media Library (WPML duplicates every upload into each language) are removed, after making sure the file exists in private storage; an hourly sweep catches any that remain.
* New: admin notice when an anti-bot service is chosen without its keys (every login from the form would be refused).
* Removed: `assets/css/frontend_default.css`, which nothing loaded.
* Fix: with unstyled mode on, the login script (reCAPTCHA, consent link) was not loaded.
* Fix: Italian translation completed.

= 1.2.0 =
* New: Registration link in login form when WordPress open registration is enabled
* New: Action hook `dv_after_login_form_wrapper`, fires at the top of the login wrapper, before the form
* New: Action hook `dv_after_login_form`, fires inside the login wrapper, after the form

= 1.1.0 =
* New: Embedded login form with AJAX submission
* New: Google reCAPTCHA v3 integration
* New: Automatic post-login redirects for subscribers
* New: User profile management shortcode
* New: User menu shortcode for navigation
* New: Email verification for profile changes
* New: Customizable email notification templates
* New: Color scheme option (Auto/Light/Dark mode)
* New: Unstyled Mode for theme customization
* New: Template Override System
* New: Complete Italian translation

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.3.1 =
Adds a light or dark choice for the Turnstile widget. Nothing changes until you pick one.

= 1.3.0 =
The embedded login form no longer uses AJAX: it posts back to its page. Purge the page cache after updating, because a cached page still has the old form.
