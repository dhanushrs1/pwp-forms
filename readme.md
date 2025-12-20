=== ProWPKit Forms ===
Contributors: prowpkit
Tags: form, contact, support, upload, captcha, secure
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
License: GPLv2 or later

A developer-first, secure, and professional form builder for Pro WP Kit.

== Description ==

ProWPKit Forms is a robust solution for handling contact forms, support tickets, and file uploads. It allows administrators to build raw HTML forms while handling the backend security, validation, and notification logic automatically.

**Key Features:**
*   **Custom HTML Forms**: Developer freedom to write Forms in raw HTML (saved as Custom Post Types).
*   **Secure Submissions**: Automated Nonce checks, Honeypot protection, and server-side capability enforcement.
*   **Cloudflare Turnstile**: Integrated captcha protection without tracking.
*   **File Uploads**: Safe upload handling with type validation (`wp_check_filetype`), unique renaming, and guest restrictions.
*   **Admin Dashboard**: A dedicated Submissions management interface.
    *   **Advanced Filtering**: Filter by Status (New, Read, Replied, Closed) and Date (Month/Year).
    *   **Enhanced Search**: Search by Email, Content, or Form ID.
    *   **Screen Options**: Customizable pagination (per page limit).
    *   **Detail View**: Clear metabox layout for viewing data and replying to users.
*   **Email Notifications**: Customizable Admin and User Confirmation email templates with placeholders.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/ProWPKit Support` directory.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Go to **Pro Forms** to create your first form.
4.  Use the shortcode `[pwp_form id="123"]` to display the form on any page.

== Changelog ==

= 1.0.0 =
*   **Initial Release**:
    *   Added Custom Post Type `pwp_form` logic.
    *   Implemented frontend form rendering with default CSS (`pwp-forms.css`).
    *   Added AJAX-based submission handling with security checks.
    *   Added **Cloudflare Turnstile** integration.
    *   Implemented **File Upload System** with "Locked" UI for guest users and strict server-side validation.
    *   Added **Tabbed Global Settings** page (General / Email Templates).
    *   Implemented **Email Notification System** with customizable HTML templates.
    *   Built **Admin Dashboard** for managing submissions.
    *   Implemented proper **List Table** with Pagination, Sorting, and Bulk Actions.
    *   Added **Screen Options** for custom submission limits.
    *   Added **Visual Date Filter** (Month Picker) and Status Filters for submissions.
    *   Added **Status Management** (New, Read, Replied, Closed).
    *   Enhanced **Search** to support Form ID queries.

