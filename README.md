# PWP Forms

![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPLv2%2B-green.svg)

A **secure**, **cache-compatible** WordPress form builder plugin. Write custom HTML forms while we handle security, spam protection, and email notifications automatically.

📘 **Developer Documentation**: [DEVELOPER.md](DEVELOPER.md) | 🌐 **Website**: [ProWPKit.com](https://prowpkit.com/)

---

## ✨ Features

- ⚡ **Raw HTML Forms** - Full control over your form design
- 🔒 **Built-in Security** - Nonce checks, honeypot, captcha, rate limiting
- � **Secure File Vault** - Protected uploads with role-based access control
- �📧 **Professional Emails** - Branded HTML templates with custom styling
- 🛡️ **Anti-Spam** - Cloudflare Turnstile, reCAPTCHA, IP rate limiting
- 📊 **Admin Dashboard** - Manage submissions with filters and search
- 💾 **Cache Compatible** - Works with all caching plugins and CDNs
- 🎨 **Customizable** - Full CSS control and email template customization
- 📁 **File Uploads** - Secure uploads with type validation and ownership checks

---

## 🚀 Quick Start

### Installation

1. Download the plugin
2. Upload to `/wp-content/plugins/pwp-forms/`
3. Activate via **WordPress Admin → Plugins**
4. Go to **PWP Forms** to create your first form

### Create a Form

1. Navigate to **PWP Forms → Add New Form**
2. Add your HTML in the Form tab:

```html
<div class="pwp-field">
  <label>Your Name</label>
  <input type="text" name="name" class="pwp-input" required />
</div>

<div class="pwp-field">
  <label>Email</label>
  <input type="email" name="email" class="pwp-input" required />
</div>

<div class="pwp-field">
  <label>Message</label>
  <textarea name="message" class="pwp-textarea" rows="5" required></textarea>
</div>
```

3. Configure email settings in the **Mail** tab
4. Click **Publish** and copy the shortcode

### Display the Form

Add the shortcode to any page or post:

```
[pwp_form id="123"]
```

---

## 📖 User Guide

### Form Builder Toolbar

Use the toolbar to insert field types:

- **Text Inputs:** Text, Email, Tel, URL, Number, Date
- **Selection:** Dropdown, Checkbox, Radio
- **Advanced:** File Upload, Acceptance, Hidden

The submit button is added automatically. Customize the label above the editor.

### Email Configuration

Configure in the **Mail** tab:

**Smart Tags Available:**

- `[your-name]`, `[your-email]` - Form field values
- `[_all_fields]` - Auto-generated table of all data
- `[_site_title]`, `[_site_url]` - Website info
- `[_date]`, `[_time]` - Submission timestamp

**Example Template:**

```html
<h2>New Contact Form Submission</h2>
<p>From: [your-name] ([your-email])</p>
<p>Message: [your-message]</p>
<hr />
<p>Submitted: [_date] at [_time]</p>
```

### Email Styling

Customize in **PWP Forms → Settings → Email Templates**:

- Logo URL
- Color palette (background, container, text, accent)
- Font family and size
- Footer text

All emails (automated + admin replies) use these settings.

### Managing Submissions

Go to **PWP Forms → Submissions** to:

**Filter by:**

- Status (New, Read, Replied, Closed)
- Date (month/year picker)
- Search (email, content, or form ID)

**Actions:**

- View submission details
- Reply directly to user
- Change status
- Delete submission
- Bulk delete

**GDPR Compliance:**

- Use "Privacy Tools" to delete all data for an email address
- Removes submissions and associated files

### Anti-Spam Settings

Configure in **PWP Forms → Settings → General**:

**Captcha Options:**

1. **Cloudflare Turnstile** (recommended)
   - Get free keys at [Turnstile](https://developers.cloudflare.com/turnstile/)
   - No tracking, privacy-friendly

2. **Google reCAPTCHA v2**
   - Get keys at [reCAPTCHA](https://www.google.com/recaptcha/)

**Rate Limiting:**

- Default: 10 submissions per hour per IP
- Prevents spam and abuse automatically
- No configuration needed

---

## 🎨 Styling Your Forms

The plugin includes minimal styling. Add custom CSS in your theme:

```css
.pwp-form {
  max-width: 600px;
}

.pwp-input,
.pwp-textarea {
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  padding: 12px;
}

.pwp-submit {
  background: #667eea;
  color: white;
  padding: 16px 48px;
}
```

**Built-in Classes:**

- `.pwp-field` - Field wrapper
- `.pwp-input` - Input styling
- `.pwp-textarea` - Textarea styling
- `.pwp-half` - 50% width on desktop
- `.pwp-third` - 33% width on desktop

---

## 📝 Changelog

### Version 1.1.0 (Current Release - 2026-01-22)

**🔐 Major Security Enhancements:**

- ✅ **Secure File Vault** - Files now upload to protected `/pwp-secured/` directory
- ✅ **Access Control** - `.htaccess` blocks all direct file access (403 Forbidden)
- ✅ **Role-Based Viewers** - Admins can view all files, users only their own
- ✅ **Ownership Verification** - Critical security checks prevent unauthorized access
- ✅ **Output Buffer Protection** - Prevents file corruption from stray output
- ✅ **Proper MIME Types** - Ensures correct file type headers for downloads

**🔒 Additional Security Improvements:**

- Fixed PII leak on cached pages
- Fixed nonce expiry issues
- Added IP-based rate limiting (10/hour)
- Safe file deletion with usage checks
- Admin replies use HTML templates

**⚡ Performance:**

- Full caching compatibility (CDN, page cache)
- Dynamic data loading via JavaScript
- Reduced server processing

**🆕 New Features:**

- Client-side user data population
- Fresh nonce fetching on page load
- Customizable rate limits via filter
- Nginx configuration support for file protection

---

### Version 1.0.2 (2025-12-20)

**Form Builder Improvements:**

- Implemented horizontally scrollable toolbar for better accessibility
- Updated field snippets to include placeholders and human-readable values
- Replaced manual submit button with automatic, mandatory submit button

**Email & Notifications:**

- Added visual color swatches for color picker values in emails

**Submission Management:**

- Standardized default submission status to 'New'
- Added 'Closed' status for better workflow management
- Fixed status display issues in dashboard

---

### Version 1.0.1 (2025-12-18)

**Security Fixes:**

- Fixed critical vulnerability in `delete_user_data` to restrict file deletion to `wp-content/uploads` directory only
- Enforced strict MIME type checking for file uploads against internal whitelist

**Bug Fixes:**

- Fixed handling of multi-file uploads (e.g., `<input name="files[]">`)
- Improved `$_FILES` array normalization to correctly handle both single and array uploads

---

### Version 1.0.0 (2025-12-15)

- Initial release

---

## 🛠️ Requirements

- WordPress 6.0+
- PHP 8.0+
- HTTPS recommended
- Apache with mod_rewrite or Nginx (see [DEVELOPER.md](DEVELOPER.md) for Nginx configuration)

---

## 🤝 Contributing

We welcome contributions! See [DEVELOPER.md](DEVELOPER.md) for technical documentation.

**For Developers:**

- Hooks & Filters documentation
- AJAX endpoints
- Database schema
- Secure file handling architecture
- Customization examples

---

## 📄 License

GPLv2 or later. See [LICENSE](LICENSE) for details.

---

## 💬 Support

- **Documentation:** [DEVELOPER.md](DEVELOPER.md) for technical docs
- **Website:** [ProWPKit.com](https://prowpkit.com/)
- **Issues:** [GitHub Issues](https://github.com/dhanushrs1/pwp-forms/issues)
- **Community:** [WordPress Support Forum](https://wordpress.org/support/plugin/pwp-forms/)

---

## 🎯 What's Next?

- [ ] Visual form builder
- [ ] Conditional logic
- [ ] Multi-step forms
- [ ] CSV export
- [ ] Webhook integrations
- [ ] Payment gateways

[Vote on features](https://github.com/dhanushrs1/pwp-forms/discussions)

---

**Made with ❤️ by the ProWPKit Team** | [Website](https://prowpkit.com/) | [GitHub](https://github.com/dhanushrs1)
