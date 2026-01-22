=== PWP Forms ===
Contributors: dhanushrs1
Donate link: https://prowpkit.com/
Tags: form, contact form, forms, email, spam
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A secure, cache-compatible WordPress form builder. Write custom HTML forms with automatic security, spam protection, and email notifications.

== Description ==

**PWP Forms** is a developer-first form plugin that gives you complete control over your form design while automatically handling security, spam protection, and professional email notifications.

= Why Choose PWP Forms? =

* **🔒 Secure by Default** - Built-in nonce validation, honeypot, captcha, and rate limiting
* **🔐 Secure File Vault** - Protected uploads directory with role-based access control
* **⚡ Cache Compatible** - Works perfectly with all caching plugins and CDNs
* **📧 Professional Emails** - Branded HTML templates with customizable styling
* **🛡️ Anti-Spam** - Cloudflare Turnstile, reCAPTCHA, and IP-based rate limiting
* **📊 Powerful Dashboard** - Manage submissions with filters, search, and bulk actions
* **🎨 Full Design Control** - Write your own HTML, style with your CSS

= Perfect For =

* Contact forms
* Support ticket systems
* Quote request forms
* Job applications
* Custom data collection
* Government/enterprise applications requiring strict security

= Key Features =

**Security First**
* Automatic nonce validation
* Honeypot spam trap
* IP-based rate limiting (customizable)
* Cloudflare Turnstile or Google reCAPTCHA
* Secure file uploads with access control
* GDPR-compliant data deletion

**File Upload Security**
* Files stored in protected `/pwp-secured/` directory
* Direct web access blocked via .htaccess
* Admins can view all files
* Users can only view their own files
* Ownership verification on every access
* Supports Apache and Nginx

**Cache Compatibility**
* Works with WP Super Cache, W3 Total Cache, etc.
* Dynamic nonce fetching via JavaScript
* No PII leaks on cached pages
* CDN compatible

**Professional Email System**
* HTML email templates
* Custom logo, colors, and fonts
* Smart tags for dynamic content
* Admin reply system
* Styled notifications

**Developer Friendly**
* Hooks and filters for customization
* Clean, documented code
* WordPress coding standards
* RESTful AJAX endpoints
* Full developer documentation

= Smart Tags =

Use these in your email templates:

* `[your-name]`, `[your-email]` - Form field values
* `[_all_fields]` - Auto-generated table of all submissions
* `[_site_title]`, `[_site_url]` - Website information
* `[_date]`, `[_time]` - Submission timestamp

= Getting Started =

1. Install and activate the plugin
2. Go to **PWP Forms → Add New Form**
3. Write your HTML in the Form tab
4. Configure email settings in the Mail tab
5. Add shortcode `[pwp_form id="123"]` to any page

See the [complete documentation](https://github.com/dhanushrs1/pwp-forms) for detailed guides.

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Search for "PWP Forms"
4. Click **Install Now** and then **Activate**

= Manual Installation =

1. Download the plugin zip file
2. Extract to `wp-content/plugins/pwp-forms/`
3. Activate via **Plugins** in WordPress admin
4. Go to **PWP Forms** to create your first form

= Configuration =

1. Navigate to **PWP Forms → Settings**
2. Configure email templates, SMTP settings, and anti-spam options
3. Set up Cloudflare Turnstile or reCAPTCHA (optional but recommended)
4. Customize rate limiting if needed

== Frequently Asked Questions ==

= Is this plugin compatible with caching plugins? =

Yes! PWP Forms is fully compatible with all caching plugins (WP Super Cache, W3 Total Cache, WP Rocket, etc.) and CDNs. We use dynamic JavaScript to fetch fresh nonces, preventing cached nonce expiry issues.

= Are file uploads secure? =

Absolutely. Files are uploaded to a protected `/pwp-secured/` directory with .htaccess blocking direct web access. Only authenticated users can view files through secure PHP endpoints with permission checks.

= How does the spam protection work? =

PWP Forms uses multiple layers:
* Honeypot fields (invisible to humans)
* IP-based rate limiting (10 submissions/hour by default)
* Cloudflare Turnstile or Google reCAPTCHA
* Nonce validation on every submission

= Can I customize the email templates? =

Yes! Go to **PWP Forms → Settings → Email Templates** to customize logo, colors, fonts, and footer text. You can also use hooks to completely override templates.

= Does it work with Nginx? =

Yes, but you need to manually add a server block configuration to deny access to the `/pwp-secured/` directory. See the [developer documentation](https://github.com/dhanushrs1/pwp-forms) for Nginx configuration.

= Is it GDPR compliant? =

Yes. The plugin includes a Privacy Tools section where you can delete all data for any email address, removing both database records and uploaded files.

= Can users upload files without logging in? =

No. For security reasons, file uploads are restricted to logged-in users only. Guest users will see locked file input fields with a message to log in.

= How do I customize rate limiting? =

Use the `pwp_max_submissions_per_hour` filter in your theme's functions.php:

`add_filter( 'pwp_max_submissions_per_hour', function( $limit ) {
    return 20; // Allow 20 submissions per hour
});`

= Can I style the forms? =

Yes! The plugin provides minimal base styling. Add your custom CSS in your theme to match your design. Built-in classes include `.pwp-form`, `.pwp-input`, `.pwp-textarea`, `.pwp-submit`, etc.

= Where can I get support? =

* [GitHub Issues](https://github.com/dhanushrs1/pwp-forms/issues) for bug reports
* [Developer Documentation](https://github.com/dhanushrs1/pwp-forms/blob/main/DEVELOPER.md) for technical details
* [WordPress Support Forum](https://wordpress.org/support/plugin/pwp-forms/)

== Screenshots ==

1. Form builder with HTML editor and field insertion toolbar
2. Email template configuration with smart tags
3. Submissions dashboard with filters and search
4. Individual submission view with reply functionality
5. Email template settings with color customization
6. Anti-spam settings with Turnstile and reCAPTCHA options
7. Secure file upload with protected directory
8. GDPR privacy tools for data deletion

== Changelog ==

= 1.1.0 - 2026-01-22 =

**Major Security Enhancements:**
* Added secure file vault - files now upload to protected `/pwp-secured/` directory
* Implemented .htaccess blocking for direct file access (403 Forbidden)
* Created role-based file viewers with permission checks
* Added admin file viewer for viewing all uploads
* Added user file viewer with ownership verification
* Implemented output buffer cleaning to prevent file corruption
* Added proper MIME type detection for file downloads
* Included Nginx configuration instructions

**Security Improvements:**
* Fixed PII (Personally Identifiable Information) leak on cached pages
* Fixed nonce expiry issues on cached pages
* Added IP-based rate limiting (10 submissions per hour)
* Improved file deletion with usage checks before removing
* Enhanced admin reply system with HTML template support

**Performance:**
* Full compatibility with all caching plugins and CDNs
* Dynamic data loading via JavaScript for cache-safe operation
* Reduced server-side processing overhead
* Fresh nonce fetching on page load

**New Features:**
* Client-side user data population for logged-in users
* Customizable rate limits via filters
* Enhanced AJAX endpoints for cache compatibility
* Support for both Apache and Nginx servers

= 1.0.0 - 2025-12-15 =
* Initial release
* Custom HTML form builder
* Email notifications with smart tags
* Cloudflare Turnstile integration
* Google reCAPTCHA support
* Submission management dashboard
* GDPR compliance tools
* File upload support
* Rate limiting
* Honeypot spam protection

== Upgrade Notice ==

= 1.1.0 =
Major security update! Files now upload to protected directory. Existing uploads remain in their current location. New uploads will be secured. Highly recommended update for all users handling sensitive data.

= 1.0.0 =
Initial release of PWP Forms.

== Privacy Policy ==

PWP Forms stores form submission data in your WordPress database. This includes:

* User email addresses
* IP addresses (for rate limiting)
* Uploaded files (stored in protected directory)
* Form field data as submitted by users

**Data Retention:**
* Administrators can manually delete submissions
* GDPR-compliant deletion tools included
* Bulk deletion available

**Third-Party Services:**
* Cloudflare Turnstile (if enabled) - [Privacy Policy](https://www.cloudflare.com/privacypolicy/)
* Google reCAPTCHA (if enabled) - [Privacy Policy](https://policies.google.com/privacy)

**User Rights:**
* Right to access data
* Right to deletion (via Privacy Tools)
* Right to data portability

== Developer Notes ==

= Hooks & Filters =

**Rate Limiting:**
`add_filter( 'pwp_max_submissions_per_hour', function( $limit ) { return 20; });`

**Upload Quota:**
`add_filter( 'pwp_user_max_quota_mb', function( $quota, $user_id ) { return 100; }, 10, 2 );`

**Upload Rate:**
`add_filter( 'pwp_max_uploads_per_hour', function( $limit, $user_id ) { return 30; }, 10, 2 );`

= AJAX Endpoints =

* `pwp_get_form_nonce` - Fetch fresh nonce
* `pwp_get_user_data` - Get current user info
* `pwp_submit_form` - Process form submission

See [DEVELOPER.md](https://github.com/dhanushrs1/pwp-forms/blob/main/DEVELOPER.md) for complete documentation.

== Credits ==

Developed by the ProWPKit Team
Website: [https://prowpkit.com/](https://prowpkit.com/)
GitHub: [https://github.com/dhanushrs1](https://github.com/dhanushrs1)
