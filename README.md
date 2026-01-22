# ProWPKit Forms

![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPLv2%2B-green.svg)

A **developer-first**, **security-focused**, and **cache-compatible** WordPress form builder plugin. Write forms in raw HTML while ProWPKit Forms handles all the backend security, validation, email notifications, and submission management automatically.

---

## 🚀 Key Features

### 🎨 Developer-First Approach

- **Raw HTML Forms**: Write custom form HTML without drag-and-drop limitations
- **No Bloat**: Clean, efficient code without unnecessary dependencies
- **WordPress Standards**: Follows WordPress coding standards and best practices
- **Extensible**: Filters and hooks for customization

### 🔒 Security Built-In

- **Multi-Layer Validation**: Nonce checks, honeypot, captcha, and server-side validation
- **IP-Based Rate Limiting**: Prevent spam (10 submissions/hour per IP, customizable)
- **Cache-Safe**: Dynamic nonce and user data fetching prevents expired nonces and PII leaks
- **GDPR Compliant**: Right to be forgotten with secure data deletion
- **Safe File Deletion**: Race condition prevention for file references

### 📧 Professional Email System

- **HTML Email Templates**: Branded emails with logo, colors, and custom styling
- **Smart Tag System**: Dynamic placeholders like `[your-name]`, `[_site_title]`, `[_all_fields]`
- **Admin & User Emails**: Separate customizable templates
- **Reply System**: Admin replies use same professional template

### 📊 Powerful Admin Dashboard

- **Submissions List Table**: Pagination, sorting, bulk actions
- **Advanced Filtering**: Filter by status (New, Read, Replied, Closed) and date
- **Enhanced Search**: Search by email, content, or form ID
- **Detail View**: Metabox layout for viewing and replying to submissions
- **Screen Options**: Customizable items per page

### 🛡️ Anti-Spam Protection

- **Cloudflare Turnstile**: Privacy-focused captcha (no tracking)
- **Google reCAPTCHA v2**: Alternative captcha option
- **Honeypot Field**: Invisible spam trap
- **Rate Limiting**: IP-based submission limits
- **File Upload Restrictions**: Logged-in users only (configurable)

### 📤 Smart File Uploads

- **Type Validation**: Whitelist-based MIME type checking
- **Size Limits**: Configurable per-file and total user quotas
- **Auto-Organization**: Year/Month folder structure
- **Multi-File Support**: Handle single or array uploads seamlessly
- **Guest Protection**: File inputs locked for non-logged-in users

### ⚡ Performance & Compatibility

- **Cache Compatible**: Works with WP Super Cache, W3 Total Cache, CDN caching
- **No Page Reloads**: AJAX-based submissions with loading states
- **Optimized Queries**: Prepared statements and indexed database columns
- **Transient-Based Rate Limits**: Auto-expiring with object cache support

---

## 📦 Installation

### From GitHub

1. Download the latest release or clone this repository:

   ```bash
   cd wp-content/plugins/
   git clone https://github.com/dhanushrs1/pwp-forms/.git
   ```

2. Activate the plugin via **WordPress Admin → Plugins**

3. Navigate to **Pro Forms** to create your first form

### Manual Installation

1. Download and extract the plugin ZIP file
2. Upload the `pwp-forms` folder to `/wp-content/plugins/`
3. Activate through the 'Plugins' menu in WordPress

---

## 🎯 Quick Start Guide

### 1. Create Your First Form

1. Go to **Pro Forms → Add New Form**
2. Enter a form title (e.g., "Contact Form")
3. In the **Form** tab, add your HTML:

```html
<div class="pwp-field">
  <label>Your Name</label>
  <input type="text" name="name" class="pwp-input" required />
</div>

<div class="pwp-field">
  <label>Email Address</label>
  <input type="email" name="email" class="pwp-input" required />
</div>

<div class="pwp-field">
  <label>Message</label>
  <textarea name="message" class="pwp-textarea" rows="5" required></textarea>
</div>
```

4. Configure email settings in the **Mail** tab
5. Customize messages in the **Messages** tab
6. Click **Publish**

### 2. Display the Form

Copy the shortcode from the sidebar and add it to any page or post:

```
[pwp_form id="123"]
```

### 3. View Submissions

Go to **Pro Forms → Submissions** to view, filter, search, and reply to submissions.

---

## 📖 User Guide

### Form Builder

#### Toolbar Field Snippets

Use the horizontally scrollable toolbar to insert common field types:

**Text Inputs:**

- Text, Email, Tel, URL, Number, Date, Textarea

**Selection:**

- Dropdown, Checkbox, Radio

**Advanced:**

- File Upload, Acceptance Checkbox, Hidden Fields

#### Submit Button

The submit button is automatically added. Customize the label in the **Submit Button Label** field above the editor.

#### Available CSS Classes

Apply these classes for consistent styling:

- `.pwp-field` - Field container
- `.pwp-input` - Standard inputs
- `.pwp-textarea` - Textareas
- `.pwp-checkbox` - Checkbox labels
- `.pwp-radio` - Radio button labels
- `.pwp-half` - 50% width on desktop
- `.pwp-third` - 33% width on desktop

---

### Email Configuration

#### Mail Tab Options

**To:** Recipient email (supports tags like `[_site_admin_email]`)  
**From:** Sender name and email  
**Subject:** Email subject line with tag support  
**Headers:** Additional headers (e.g., `Reply-To: [your-email]`)  
**Body:** Email content with HTML and tags  
**Attachments:** File field tags to attach

#### Available Smart Tags

**Form Fields:**

- `[your-name]`, `[your-email]`, `[your-message]` - Any field by name attribute

**Special Tags:**

- `[_all_fields]` - Auto-generates HTML table with all submission data
- `[_site_title]` - Website name
- `[_site_url]` - Website URL
- `[_site_admin_email]` - Admin email from WordPress settings
- `[_date]` - Submission date
- `[_time]` - Submission time
- `[_remote_ip]` - Submitter's IP address

#### Example Email Template

```html
<h2>New Contact Form Submission</h2>
<p>You have received a new message from [your-name].</p>

<h3>Contact Details:</h3>
<ul>
  <li><strong>Name:</strong> [your-name]</li>
  <li><strong>Email:</strong> [your-email]</li>
</ul>

<h3>Message:</h3>
<p>[your-message]</p>

<hr />
<p><small>Submitted on [_date] at [_time] from IP: [_remote_ip]</small></p>
```

---

### Email Styling (Global Settings)

Navigate to **Pro Forms → Settings → Email Templates** to customize:

- **Logo URL:** Header logo for all emails
- **Color Palette:** Background, container, text, and accent colors
- **Typography:** Font family and size
- **Footer Text:** Appears at bottom of all emails

All emails (automated and admin replies) use these settings for consistent branding.

---

### Submission Management

#### Submissions List

**Filtering:**

- **Status:** New, Read, Replied, Closed
- **Date:** Month/year picker
- **Search:** Email, content, or form ID

**Bulk Actions:**

- Delete multiple submissions at once

**Screen Options:**

- Customize submissions per page (top-right corner)

#### Detail View

When viewing a submission:

1. **Submission Data:** All form fields in a table
2. **Attached Files:** Download links with file sizes
3. **Reply Form:** Send professional HTML email directly to submitter
4. **Status Management:** Change status (New → Read → Replied → Closed)
5. **Delete:** Permanently remove submission and files

#### GDPR Tools

Expand the **Privacy Tools** accordion to delete all data for a specific email address:

1. Enter the user's email
2. Click **Delete Data**
3. Confirms deletion of all submissions and associated files

---

### Security Settings

#### Captcha Configuration

**Settings → General → Spam Protection**

Choose your captcha provider:

**Cloudflare Turnstile (Recommended):**

1. Get free keys at [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/)
2. Enter Site Key and Secret Key
3. Captcha appears automatically on all forms

**Google reCAPTCHA v2:**

1. Get keys at [Google reCAPTCHA](https://www.google.com/recaptcha/)
2. Select reCAPTCHA v2 (checkbox)
3. Enter Site Key and Secret Key

**None:** Disable captcha (not recommended for public forms)

#### Rate Limiting

Default: **10 submissions per hour per IP address**

Customize via filter in your theme's `functions.php`:

```php
// Increase limit for logged-in users
add_filter( 'pwp_max_submissions_per_hour', function( $limit ) {
    if ( is_user_logged_in() ) {
        return 50; // 50/hour for logged-in users
    }
    return 10; // 10/hour for guests
});

// Unlimited for administrators
add_filter( 'pwp_max_submissions_per_hour', function( $limit ) {
    if ( current_user_can( 'manage_options' ) ) {
        return PHP_INT_MAX;
    }
    return $limit;
});

// Stricter limit for specific form
add_filter( 'pwp_max_submissions_per_hour', function( $limit ) {
    if ( ! empty( $_POST['form_id'] ) && $_POST['form_id'] == 123 ) {
        return 3; // Only 3/hour for form #123
    }
    return $limit;
});
```

---

## 🔧 Developer Documentation

### Hooks & Filters

#### Filters

```php
// Customize submission rate limit
apply_filters( 'pwp_max_submissions_per_hour', 10 );

// Customize user upload quota (MB)
apply_filters( 'pwp_user_max_quota_mb', 50, $user_id );

// Customize upload limit per hour
apply_filters( 'pwp_max_uploads_per_hour', 10, $user_id );
```

#### Actions

```php
// After submission saved (coming in future updates)
do_action( 'pwp_after_submission', $submission_id, $form_id, $data );

// Before email send (coming in future updates)
do_action( 'pwp_before_email', $to, $subject, $body, $headers );
```

### AJAX Endpoints

**Get Fresh Nonce:**

```javascript
$.post(ajaxurl, { action: "pwp_get_form_nonce" }, function (response) {
  console.log(response.data.nonce);
});
```

**Get User Data:**

```javascript
$.post(ajaxurl, { action: "pwp_get_user_data" }, function (response) {
  if (response.data.logged_in) {
    console.log(response.data.name, response.data.email);
  }
});
```

### Database Schema

**Table:** `wp_pwp_submissions`

| Column            | Type         | Description                         |
| ----------------- | ------------ | ----------------------------------- |
| `id`              | bigint(20)   | Primary key                         |
| `form_id`         | bigint(20)   | Reference to form post ID           |
| `user_id`         | bigint(20)   | WordPress user ID (NULL for guests) |
| `user_email`      | varchar(100) | Submitter email                     |
| `submission_type` | varchar(50)  | Form type (general, support)        |
| `submission_data` | longtext     | JSON-encoded form fields            |
| `uploaded_files`  | text         | JSON array of file paths            |
| `user_ip`         | varchar(45)  | IP address (IPv6 supported)         |
| `status`          | varchar(50)  | new/read/replied/closed             |
| `admin_notes`     | text         | Internal notes                      |
| `created_at`      | datetime     | Submission timestamp                |

---

## 🎨 Customization Examples

### Custom Form Styling

Add to your theme's CSS:

```css
.pwp-form {
  max-width: 600px;
  margin: 0 auto;
}

.pwp-field {
  margin-bottom: 1.5rem;
}

.pwp-input,
.pwp-textarea {
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  padding: 12px;
}

.pwp-input:focus,
.pwp-textarea:focus {
  border-color: #007cba;
  box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
}

.pwp-submit {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 16px 48px;
  font-weight: 600;
  letter-spacing: 0.5px;
}
```

### Multi-Column Layout

```html
<div class="pwp-field pwp-half">
  <label>First Name</label>
  <input type="text" name="first-name" class="pwp-input" required />
</div>

<div class="pwp-field pwp-half">
  <label>Last Name</label>
  <input type="text" name="last-name" class="pwp-input" required />
</div>

<div class="pwp-field">
  <label>Email (Full Width)</label>
  <input type="email" name="email" class="pwp-input" required />
</div>
```

---

## 🔄 Changelog

### Version 1.1.0 (Current)

**Security Enhancements:**

- ✅ Fixed PII leak on cached pages (moved user data to client-side JavaScript)
- ✅ Fixed nonce expiry on cached pages (dynamic AJAX fetching)
- ✅ Added IP-based rate limiting (10/hour default, filterable)
- ✅ Added safe file deletion with usage checks
- ✅ Standardized admin reply emails with HTML templates

**Email Improvements:**

- ✅ Admin replies use `PWP_Email_Manager` for consistent branding
- ✅ Professional HTML formatting for all communications

**JavaScript Enhancements:**

- ✅ Client-side user data population for logged-in users
- ✅ Fresh nonce fetching on page load
- ✅ Auto-populate fields after form reset

**Performance:**

- ✅ Full-page caching compatible (WP Super Cache, W3 Total Cache, CDN)
- ✅ Reduced server-side processing

**Developer Features:**

- ✅ `pwp_max_submissions_per_hour` filter
- ✅ Improved SQL injection protection with prepared statements

### Version 1.0.2

- Horizontally scrollable toolbar
- Auto-submit button with customizable label
- Visual color swatches in emails
- Added 'Closed' status
- Fixed status display bugs

### Version 1.0.1

- Fixed file deletion security vulnerability
- Strict MIME type checking
- Multi-file upload normalization

### Version 1.0.0

- Initial release
- Custom post type forms
- AJAX submissions
- Cloudflare Turnstile integration
- Email notification system
- Admin dashboard with WP_List_Table

[Full Changelog →](readme.txt)

---

## 🛠️ Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 8.0 or higher
- **MySQL:** 5.7 or higher (or MariaDB 10.2+)

**Recommended:**

- WordPress 6.4+
- PHP 8.1+
- HTTPS/SSL for security
- Object caching (Redis/Memcached) for high-traffic sites

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Add PHPDoc comments for all functions
- Write security-first code (sanitize inputs, escape outputs)
- Test with WordPress Debug mode enabled

---

## 📄 License

This plugin is licensed under the [GPLv2 (or later)](https://www.gnu.org/licenses/gpl-2.0.html).

```
ProWPKit Forms - WordPress Form Builder Plugin
Copyright (C) 2024 Pro WP Kit Team

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## 🐛 Bug Reports & Feature Requests

Found a bug or have a feature request? Please [open an issue](https://github.com/dhanushrs1/pwp-forms/issues) with:

**For Bugs:**

- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior
- Screenshots (if applicable)

**For Features:**

- Clear description of the feature
- Use case / problem it solves
- Proposed implementation (if technical)

---

## 💬 Support

- **Documentation:** This README and inline code comments
- **Issues:** [GitHub Issues](https://github.com/dhanushrs1/pwp-forms/issues)
- **Community:** [WordPress.org Support Forum](https://wordpress.org/support/plugin/pwp-forms/)

---

## 🌟 Credits

**Developed by:** [Pro WP Kit Team](https://prowpkit.com)

**Built with WordPress Standards:**

- Custom Post Types
- WP_List_Table
- WordPress Settings API
- WordPress Transients API
- wp_mail() for email
- wp_enqueue_script/style for assets

**Inspired by:** Contact Form 7's developer-friendly approach

---

## 🎯 Roadmap

- [ ] Visual form builder (optional, for non-developers)
- [ ] Conditional logic fields
- [ ] Multi-step forms
- [ ] CSV export for submissions
- [ ] Webhook integrations (Zapier, Make, n8n)
- [ ] Payment integration (Stripe, PayPal)
- [ ] Form analytics dashboard
- [ ] Email queue system for high-traffic sites
- [ ] A/B testing for forms

**Vote on features:** [GitHub Discussions](https://github.com/dhanushrs1/pwp-forms/discussions)

---

Made with ❤️ by the ProWPKit Team | [Website](https://prowpkit.com) | [GitHub](https://github.com/dhanushrs1)
