# DV Private Documents

A WordPress plugin for a private document area. An administrator creates a document, attaches a file and assigns it to one user. That user finds it on a page of the site, downloads it through a link that checks who is asking, and can discuss it with the administrators in a comment thread. Nobody else can download the file, not even with its address.

It is written around one rule: **a file that belongs to one user must never end up somewhere the web server hands out to anyone.** Most of the code that is not a form or a template is there to keep that rule true when other plugins, the server or a deploy work against it.

## What it does

- **Documents assigned to one user**: a private post type (not public, not in the REST API). Users see only their own documents; administrators see all of them.
- **Protected downloads**: files are served by a handler that checks login, assignment and a nonce, with `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`.
- **Comments per document**, between the user and the administrators, with email notifications in both directions.
- **Front-end area without the dashboard**: document list, document view, profile (name, email with confirmation link, password, organisation, language) and a compact user menu, as shortcodes.
- **Embedded login form** that signs users in through the WordPress login itself (see below), with optional Google reCAPTCHA v3 or Cloudflare Turnstile.
- **WPML**: pages, redirects and email templates per language; notifications go out in the recipient's language.
- **Complianz**: the anti-bot service loads only after consent, and the login is refused, not let through unchecked, until consent is given.
- **Templates** overridable from the theme, an unstyled mode, light, dark and automatic colour schemes.
- Italian translation included.

## How the files are kept private

The file chosen for a document is copied to `wp-content/private-docs/` under a hashed name, and the attachment is deleted from the Media Library. That folder is inside the web root, so what keeps it private is the server:

- The plugin writes an `.htaccess` (`Require all denied`, Apache 2.4 syntax with a 2.2 fallback, PHP execution off) and an `index.php` into the folder, **always** when they are missing, not only when the folder is created. It checks them again every hour from the dashboard.
- If the folder cannot be protected, **the file is not saved**: losing an upload is better than publishing it.
- Once a week it writes a test file with a random token into the folder and requests it over HTTP. If the token comes back, the server is ignoring `.htaccess` (Nginx, or Apache with `AllowOverride None`) and every administrator gets a red notice. From the filesystem that case is invisible: the `.htaccess` is there and correct.

**Why the Media Library gets special treatment.** `wp-content/uploads/` is served by the web server without going through WordPress: anything in it can be downloaded by whoever finds the address. WPML duplicates every upload into each active language *during* the upload, before any other plugin sees the attachment, each copy with its own physical file. A plugin that deletes "its" attachment leaves the twin behind. So:

- every attachment the plugin takes over is deleted together with its translations (through WPML's public `wpml_element_trid` and `wpml_get_element_translations` filters; on a site without WPML this is a plain `wp_delete_attachment()`);
- once an hour a sweep looks for **any** attachment still linked to a private document, whoever created it (a translation plugin, an import, a person), and removes it. Before deleting, it makes sure the content exists in private storage, comparing by hash because duplicates get renamed; if it cannot secure a copy it does not delete and raises a notice.

**If you deploy with git**, version `wp-content/private-docs/.htaccess` and `index.php`, and ignore the documents. An `.htaccess` that is in `.gitignore` never reaches a server whose folder arrived with the deploy:

```gitignore
.htaccess
wp-content/private-docs/*
!wp-content/private-docs/.htaccess
!wp-content/private-docs/index.php
```

The negations must come after the `.htaccess` line.

## The login form and two-factor authentication

The embedded login form posts back to the page it is on, and the plugin signs the user in with `wp_signon()`, the function behind `wp-login.php`. Everything hooked to the WordPress login applies to it without extra code: two-factor plugins, login limiters, loggers for fail2ban. With a two-factor plugin that asks for the code after the password (tested with WP 2FA), the code screen appears and the user then goes back to the page they came from.

Logins are accepted only on the pages set in the settings (documents list, document view, profile, in every language; filter `dv_login_form_page_ids`), only from this site (a POST whose `Origin` is another host is ignored), and those pages are sent with `X-Frame-Options: SAMEORIGIN`, like `wp-login.php`. Visitors never go through `wp-login.php`, so whatever guards that URL (HTTP authentication, an IP allowlist, a CDN challenge) is not in their way. The other side of it: protection bound to the `wp-login.php` screen rather than to the login itself does not cover the form, so put rate limits on the pages with the form too if you rely on them.

A wrong username or password, or a failed anti-bot check, comes back to the same page with a specific message. Any other error (a lockout, another plugin's captcha, a two-factor plugin that works only on its own login screen) gets a generic message with a link to the login page. Fields that other plugins add to the standard login form (the `login_form` action, for example a captcha) are printed in this form too; their script must also load on the front end.

Versions before 1.3.0 logged in through an AJAX endpoint that set the cookies itself: a two-factor plugin withdrew the session as it should, but the form could not show the challenge and stopped working for users who had to give a second factor.

To let users turn on two-factor authentication without going to the dashboard, put the two-factor plugin's own setup shortcode on the profile page (for WP 2FA, `[wp-2fa-setup-form]`).

## Anti-bot check

**Private Documents → Settings → Login Form Settings → Anti-bot Protection**: none, Google reCAPTCHA v3 or Cloudflare Turnstile. It covers the embedded form only: `wp-login.php` itself is left to whatever protects it on your site.

A token is accepted only if the service confirms it, it was issued for the `login` action and for this site's host. reCAPTCHA also needs a score of at least 0.5. The check runs whatever the password: a request without a valid token gets the same answer with a right or a wrong password.

| Filter | Default | |
|---|---|---|
| `dv_recaptcha_min_score` | `0.5` | Minimum reCAPTCHA v3 score |
| `dv_recaptcha_consent_category` | `marketing` | Complianz category reCAPTCHA needs |
| `dv_turnstile_consent_category` | `functional` | Complianz category Turnstile needs (Complianz always grants it) |

If you use a page cache, exclude the page with the login form: the consent notice is decided on the server.

## Requirements

- WordPress 6.3 or later, PHP 8.1 or later.
- A web server that honours `.htaccess` (Apache, LiteSpeed). On Nginx, deny `wp-content/private-docs/` in the server configuration: the weekly probe tells you if it is reachable.
- WPML and Complianz are optional.

## Installation

1. Download `dv-private-docs.zip` from the [latest release](https://github.com/bluantinoo/dv-private-docs/releases/latest) (not the "Source code" archives).
2. **Plugins → Add New → Upload Plugin**, then activate. The storage folder and its protection are created on activation.
3. Create two pages, one with `[dv_private_docs]` (the list) and one with `[dv_private_doc_view]` (a single document), and select them in **Private Documents → Settings → General Settings**.
4. **Private Documents → Add New**: title, user, file, publish.

Optional: a profile page with `[dv_user_profile]`, the `[dv_user_menu]` shortcode in the header, the embedded login form, the subscriber redirect after login.

The full manual is in [docs/manual.md](docs/manual.md); the template system in [templates/README.md](templates/README.md).

## For developers

**Templates.** Copy a file from `templates/` to `your-theme/dv-private-docs/` to override it. Filters `dv_locate_template`, `dv_get_template`, `dv_get_template_args`, `dv_load_template_args`; actions `dv_before_template_part`, `dv_after_template_part`.

**Login form hooks.** `dv_after_login_form_wrapper` (inside the wrapper, before the form) and `dv_after_login_form` (after the form). The two "Content Before/After Login Form" settings use them.

**Options per language.** Settings that change with the language (pages, redirect page, email templates, the login form texts) are stored with a language suffix, `dv_list_page_en`, with the base option as fallback, rather than through WPML String Translation. The settings screen follows the WPML language switcher in the admin bar: switch language, edit, save. Reading one in your own code:

```php
$lang  = dv_wpml_is_active() ? dv_get_current_lang() : null;
$value = get_option( $lang ? 'dv_list_page_' . $lang : 'dv_list_page', '' );
if ( '' === $value && $lang ) {
	$value = get_option( 'dv_list_page', '' );
}
```

**Fail closed.** Where a precondition of a sensitive flow is missing (consent for the anti-bot check, protection of the storage folder) the plugin refuses and says why, rather than skipping the check.

## Limits

- One user per document. There is no sharing between users or groups.
- The storage folder is inside the web root, so its protection depends on the web server. Moving it outside would mean migrating the stored paths of every existing document; not done yet.
- The anti-bot check covers the embedded form, not `wp-login.php`.
- Download counts are kept, but there is no download log.

## Uninstall

Deactivating changes nothing. Deleting the plugin removes only its update state, unless **Delete Data on Uninstall** is checked in General Settings: then documents, their comments, the files in the storage folder, the profile fields the plugin added and all its settings are deleted for good. On a multisite network the shared storage folder is removed only if every site asked for it.

## Updates

The plugin is not on wordpress.org: it updates itself from the [GitHub releases](https://github.com/bluantinoo/dv-private-docs/releases) of this repository, through the bundled [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library (MIT). New versions show up in **Dashboard → Updates** and in `wp plugin update`, like any other plugin. The `Update URI` header tells WordPress not to look for this slug on wordpress.org, so a different plugin published there under the same name can never be offered as an update.

Only the zip attached to a release is installed. The site asks the GitHub API for the latest release about every 12 hours. GitHub sees what WordPress sends with every outgoing request: the server's IP address, and the WordPress version and site URL in the User-Agent.

Whoever can publish a release on this repository can ship code to every site that runs the plugin, which is true of any update channel. Releases are built by the workflow in `.github/workflows/release.yml` from a version tag on `main`.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

## Author

Diego Vagnoli, [diegovagnoli.it](https://diegovagnoli.it)
