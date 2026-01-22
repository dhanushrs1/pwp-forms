=== ProWPKit Forms ===
Contributors: prowpkit
Tags: form, contact, support, upload, captcha, secure
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.1.0
License: GPLv2 or later

A developer-first, secure, and professional form builder for Pro WP Kit.

== Description ==

ProWPKit Forms is a robust solution for handling contact forms, support tickets, and file uploads. It allows administrators to build raw HTML forms while handling the backend security, validation, and notification logic automatically.

**Key Features:**

- **Custom HTML Forms**: Developer freedom to write Forms in raw HTML (saved as Custom Post Types).
- **Secure Submissions**: Automated Nonce checks, Honeypot protection, and server-side capability enforcement.
- **Cloudflare Turnstile**: Integrated captcha protection without tracking.
- **File Uploads**: Safe upload handling with type validation (`wp_check_filetype`), unique renaming, and guest restrictions.
- **Admin Dashboard**: A dedicated Submissions management interface.
  - **Advanced Filtering**: Filter by Status (New, Read, Replied, Closed) and Date (Month/Year).
  - **Enhanced Search**: Search by Email, Content, or Form ID.
  - **Screen Options**: Customizable pagination (per page limit).
  - **Detail View**: Clear metabox layout for viewing data and replying to users.
- **Email Notifications**: Customizable Admin and User Confirmation email templates with placeholders.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/ProWPKit Support` directory.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Go to **Pro Forms** to create your first form.
4.  Use the shortcode `[pwp_form id="123"]` to display the form on any page.

== Changelog ==

= 1.1.0 =

- **Security Enhancements**:
  - Fixed PII leak: Moved user data pre-filling from server-side to client-side JavaScript to prevent cached pages from exposing personal information.
  - Fixed nonce expiry: Implemented dynamic nonce fetching via AJAX to prevent form failures on cached pages.
  - Added IP-based rate limiting (10 submissions/hour default) to prevent spam and abuse.
  - Added safe file deletion with usage checks to prevent race conditions.
  - Standardized admin reply emails to use HTML templates for consistency.
- **Email Improvements**:
  - Admin replies now use `PWP_Email_Manager` for professional HTML formatting.
  - Consistent branding across all email communications.
- **JavaScript Enhancements**:
  - Implemented client-side user data population for logged-in users.
  - Fresh nonce fetching on page load for cache compatibility.
  - Auto-populate and re-populate name/email fields after form submission.
- **Performance**:
  - Optimized for full-page caching (WP Super Cache, W3 Total Cache, CDN).
  - Reduced server-side processing by moving dynamic data to JavaScript.
- **Developer Features**:
  - Added `pwp_max_submissions_per_hour` filter for customizable rate limits.
  - Database prepared statements for improved SQL injection protection.

= 1.0.2 =

- **Form Builder Improvements**:
  - Implemented horizontally scrollable toolbar for better accessibility.
  - Updated field snippets to include placeholders and human-readable values.
  - Replaced manual submit button with an automatic, mandatory submit button.
- **Email & Notifications**:
  - Added visual color swatches for color picker values in emails.
- **Submission Management**:
  - Standardized default submission status to 'New'.
  - Added 'Closed' status for better workflow management.
  - Fixed status display issues in dashboard.

= 1.0.1 =

- **Security Fixes**:
  - Fixed critical vulnerability in `delete_user_data` to restrict file deletion to the `wp-content/uploads` directory.
  - Enforced strict MIME type checking for file uploads against an internal whitelist.
- **Bug Fixes**:
  - Fixed handling of multi-file uploads (e.g., `<input name="files[]">`).
  - Improved `$_FILES` array normalization to correctly handle both single and array uploads.

= 1.0.0 =

- **Initial Release**:
  - Added Custom Post Type `pwp_form` logic.
  - Implemented frontend form rendering with default CSS (`pwp-forms.css`).
  - Added AJAX-based submission handling with security checks.
  - Added **Cloudflare Turnstile** integration.
  - Implemented **File Upload System** with "Locked" UI for guest users and strict server-side validation.
  - Added **Tabbed Global Settings** page (General / Email Templates).
  - Implemented **Email Notification System** with customizable HTML templates.
  - Built **Admin Dashboard** for managing submissions.
  - Implemented proper **List Table** with Pagination, Sorting, and Bulk Actions.
  - Added **Screen Options** for custom submission limits.
  - Added **Visual Date Filter** (Month Picker) and Status Filters for submissions.
  - Added **Status Management** (New, Read, Replied, Closed).
  - Enhanced **Search** to support Form ID queries.
