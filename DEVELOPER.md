# Developer Documentation - PWP Forms

Technical documentation for extending and customizing PWP Forms.

📘 **User Guide**: [README.md](README.md) | 🌐 **Website**: [ProWPKit.com](https://prowpkit.com/)

---

## 📚 Table of Contents

- [Architecture Overview](#architecture-overview)
- [Secure File Handling](#secure-file-handling)
- [Hooks & Filters](#hooks--filters)
- [AJAX Endpoints](#ajax-endpoints)
- [Database Schema](#database-schema)
- [Customization Examples](#customization-examples)
- [Contributing](#contributing)

---

## 🏗️ Architecture Overview

### Core Classes

Located in `/includes/`:

1. **class-prowpkit-forms.php** - Main singleton controller
2. **class-database.php** - Database operations & GDPR
3. **class-form-manager.php** - Custom post type & metaboxes
4. **class-form-render.php** - Frontend shortcode rendering
5. **class-form-submit.php** - AJAX submission handler
6. **class-email-manager.php** - Email notifications
7. **class-upload-handler.php** - Secure file vault system
8. **class-admin-dashboard.php** - Submissions management & admin file viewer
9. **class-submissions-list.php** - WP_List_Table implementation
10. **class-settings.php** - Global settings pages

### Request Flow

```
User submits form
    ↓
pwp-forms.js (AJAX)
    ↓
class-form-submit.php::handle_submission()
    ↓
Validation Chain:
  1. Nonce check
  2. Honeypot
  3. Rate limiting
  4. Captcha
  5. Capability check
  6. File validation
    ↓
Database insert (wp_pwp_submissions)
    ↓
Email notifications (class-email-manager.php)
    ↓
JSON response to frontend
```

---

## 🔐 Secure File Handling

### Architecture: The Secure Vault

PWP Forms implements a "Secure Vault" architecture for file uploads to protect sensitive user data.

#### How It Works

1. **Separate Directory**: Files upload to `/wp-content/uploads/pwp-secured/` instead of public directories
2. **Web Server Block**: `.htaccess` file with `Deny from all` prevents direct URL access
3. **Role-Based Viewers**: PHP scripts act as "tunnels" with permission checks

#### Upload Process

```php
// class-upload-handler.php
$secure_upload_filter = function( $param ) {
    $mydir = '/pwp-secured';
    $param['path'] = $param['basedir'] . $mydir;
    $param['url']  = $param['baseurl'] . $mydir;
    $param['subdir'] = $mydir;
    return $param;
};

add_filter( 'upload_dir', $secure_upload_filter );
$movefile = wp_handle_upload( $file_array, $upload_overrides );
remove_filter( 'upload_dir', $secure_upload_filter );

// Create .htaccess protection
$htaccess_content = "Order Deny,Allow\nDeny from all";
file_put_contents( $secure_dir . '/.htaccess', $htaccess_content );
```

#### File Viewing

**Admin Viewer** (`admin_post_pwp_view_file`):

- Checks `current_user_can('manage_options')`
- Can view any uploaded file
- Located in `class-admin-dashboard.php::handle_file_view()`

**User Viewer** (`admin_post_pwp_view_my_file`):

- Checks `is_user_logged_in()`
- Verifies ownership: `$submission->user_id === get_current_user_id()`
- Located in `class-form-render.php::handle_user_file_view()`

#### Security Features

| Protection                 | Implementation                                |
| -------------------------- | --------------------------------------------- |
| **Directory Isolation**    | `/pwp-secured/` folder                        |
| **Direct Access Block**    | `.htaccess` returns 403 Forbidden             |
| **Admin Permission**       | `manage_options` capability check             |
| **Ownership Verification** | User ID matching on submissions               |
| **Nonce Validation**       | `wp_verify_nonce()` on all viewers            |
| **Output Buffer Cleaning** | `ob_end_clean()` prevents corruption          |
| **MIME Type Validation**   | `wp_check_filetype()` ensures correct headers |

#### Nginx Configuration

For Nginx servers, add this to your server block:

```nginx
# PWP Forms - Secure File Vault
location ~* /wp-content/uploads/pwp-secured/ {
    deny all;
    return 403;
}
```

Then restart Nginx:

```bash
sudo nginx -t
sudo systemctl restart nginx
```

---

## 🔌 Hooks & Filters

### Filters

#### Rate Limiting

```php
/**
 * Customize submission rate limit
 *
 * @param int $limit Default: 10
 * @return int
 */
add_filter( 'pwp_max_submissions_per_hour', function( $limit ) {
    // Allow logged-in users more submissions
    if ( is_user_logged_in() ) {
        return 50;
    }
    return 10;
});
```

#### User Upload Quota

```php
/**
 * Customize per-user storage quota
 *
 * @param int $quota_mb Default: 50 MB
 * @param int $user_id User ID
 * @return int
 */
add_filter( 'pwp_user_max_quota_mb', function( $quota_mb, $user_id ) {
    // Admins get unlimited storage
    if ( user_can( $user_id, 'manage_options' ) ) {
        return PHP_INT_MAX;
    }
    return $quota_mb;
}, 10, 2 );
```

#### Upload Rate Limit

```php
/**
 * Customize upload frequency limit
 *
 * @param int $limit Default: 10 per hour
 * @param int $user_id User ID
 * @return int
 */
add_filter( 'pwp_max_uploads_per_hour', function( $limit, $user_id ) {
    return 20; // Allow 20 uploads per hour
}, 10, 2 );
```

### Actions

_(Coming in future versions)_

```php
// After submission saved
do_action( 'pwp_after_submission', $submission_id, $form_id, $data );

// Before email sent
do_action( 'pwp_before_email', $to, $subject, $body, $headers );

// After email sent
do_action( 'pwp_after_email', $to, $subject, $success );
```

---

## 🌐 AJAX Endpoints

### Get Fresh Nonce

**Action:** `pwp_get_form_nonce`  
**Access:** Public (logged-in + guests)  
**Purpose:** Fetch a fresh nonce for cache compatibility

**JavaScript Example:**

```javascript
jQuery.ajax({
  url: pwp_forms_vars.ajax_url,
  type: "POST",
  data: {
    action: "pwp_get_form_nonce",
  },
  success: function (response) {
    if (response.success) {
      var nonce = response.data.nonce;
      console.log("Fresh nonce:", nonce);
    }
  },
});
```

**Response:**

```json
{
  "success": true,
  "data": {
    "nonce": "abc123def456"
  }
}
```

### Get User Data

**Action:** `pwp_get_user_data`  
**Access:** Public  
**Purpose:** Get current user's name and email (if logged in)

**JavaScript Example:**

```javascript
jQuery.ajax({
  url: pwp_forms_vars.ajax_url,
  type: "POST",
  data: {
    action: "pwp_get_user_data",
  },
  success: function (response) {
    if (response.success && response.data.logged_in) {
      console.log("Name:", response.data.name);
      console.log("Email:", response.data.email);
    }
  },
});
```

**Response (Logged In):**

```json
{
  "success": true,
  "data": {
    "logged_in": true,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Response (Guest):**

```json
{
  "success": true,
  "data": {
    "logged_in": false
  }
}
```

### Submit Form

**Action:** `pwp_submit_form`  
**Access:** Public  
**Method:** POST with FormData  
**Purpose:** Process form submission

**JavaScript Example:**

```javascript
var formData = new FormData(document.getElementById("my-form"));
formData.append("action", "pwp_submit_form");
formData.append("security", nonce);
formData.append("form_id", 123);

jQuery.ajax({
  url: pwp_forms_vars.ajax_url,
  type: "POST",
  data: formData,
  processData: false,
  contentType: false,
  success: function (response) {
    if (response.success) {
      alert(response.data.message);
    }
  },
});
```

---

## 💾 Database Schema

### Table: `wp_pwp_submissions`

```sql
CREATE TABLE wp_pwp_submissions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    form_id bigint(20) NOT NULL,           -- Post ID of pwp_form
    user_id bigint(20) DEFAULT NULL,       -- WordPress user ID (NULL for guests)
    user_email varchar(100) NOT NULL,      -- Submitter email
    submission_type varchar(50) DEFAULT 'general',
    submission_data longtext NOT NULL,     -- JSON: {"name":"John","email":"..."}
    uploaded_files text DEFAULT NULL,      -- JSON: ["/path/file1.jpg",...]
    user_ip varchar(45) DEFAULT NULL,      -- IPv6 compatible
    status varchar(50) DEFAULT 'open',     -- new|read|replied|closed
    admin_notes text DEFAULT NULL,         -- Internal notes
    created_at datetime DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY form_id (form_id),
    KEY user_id (user_id),
    KEY user_email (user_email),
    KEY status (status)
);
```

### Querying Submissions

```php
global $wpdb;
$table = $wpdb->prefix . 'pwp_submissions';

// Get all unread submissions
$unread = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $table WHERE status = %s ORDER BY created_at DESC",
    'new'
) );

// Get submissions for specific form
$form_submissions = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $table WHERE form_id = %d",
    123
) );

// Count submissions by status
$counts = $wpdb->get_results(
    "SELECT status, COUNT(*) as count FROM $table GROUP BY status"
);
```

### Meta Data Fields

Forms use WordPress post meta:

```php
// Form configuration
get_post_meta( $form_id, '_pwp_form_html', true );
get_post_meta( $form_id, '_pwp_form_submit_label', true );

// Email settings
get_post_meta( $form_id, '_pwp_mail_to', true );
get_post_meta( $form_id, '_pwp_mail_from', true );
get_post_meta( $form_id, '_pwp_mail_subject', true );
get_post_meta( $form_id, '_pwp_mail_body', true );

// Messages
get_post_meta( $form_id, '_pwp_msg_success', true );
get_post_meta( $form_id, '_pwp_msg_fail', true );
```

---

## 🎨 Customization Examples

### Example 1: Different Rate Limits Per Form

```php
add_filter( 'pwp_max_submissions_per_hour', function( $limit ) {
    // Strict limit for public contact form
    if ( ! empty( $_POST['form_id'] ) && $_POST['form_id'] == 5 ) {
        return 3;
    }

    // Relaxed limit for support tickets (logged-in only)
    if ( ! empty( $_POST['form_id'] ) && $_POST['form_id'] == 10 ) {
        return 50;
    }

    return $limit;
});
```

### Example 2: IP Whitelist

```php
add_filter( 'pwp_max_submissions_per_hour', function( $limit ) {
    $trusted_ips = [
        '123.456.789.0',  // Office IP
        '98.76.54.32'     // Testing server
    ];

    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if ( in_array( $user_ip, $trusted_ips ) ) {
        return 1000; // Very high limit for trusted IPs
    }

    return $limit;
});
```

### Example 3: Custom Email Template

```php
add_filter( 'pwp_email_template', function( $template, $form_id ) {
    if ( $form_id == 123 ) {
        return <<<HTML
<div style="background:#f8f9fa; padding:40px;">
    <div style="max-width:600px; margin:0 auto; background:white; padding:30px;">
        <h1 style="color:#333;">Custom Template</h1>
        {body}
    </div>
</div>
HTML;
    }
    return $template;
}, 10, 2 );
```

### Example 4: Programmatically Create Submission

```php
global $wpdb;
$table = $wpdb->prefix . 'pwp_submissions';

$wpdb->insert( $table, [
    'form_id'         => 123,
    'user_id'         => get_current_user_id(),
    'user_email'      => 'test@example.com',
    'submission_type' => 'general',
    'submission_data' => json_encode([
        'name'  => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'Test submission'
    ]),
    'uploaded_files'  => json_encode([]),
    'user_ip'         => $_SERVER['REMOTE_ADDR'],
    'status'          => 'new'
] );
```

### Example 5: Add Custom Validation

```php
// Hook into form processing (requires custom action hook in future version)
add_action( 'pwp_before_submission_save', function( $data, $form_id ) {
    // Custom validation logic
    if ( empty( $data['phone'] ) && $form_id == 5 ) {
        wp_send_json_error([
            'message' => 'Phone number is required for this form.'
        ]);
    }
}, 10, 2 );
```

---

## 🔐 Security Best Practices

### For Form HTML

1. **Always use `required` attribute** for mandatory fields
2. **Use appropriate input types** (`email`, `tel`, `url`, etc.)
3. **Add `maxlength` to prevent spam** in text fields
4. **Use `pattern` attribute** for format validation

```html
<input
  type="email"
  name="email"
  required
  pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
  maxlength="100"
/>
```

### For Custom Code

1. **Always use `$wpdb->prepare()`** for database queries
2. **Sanitize inputs** with `sanitize_text_field()`, `sanitize_email()`, etc.
3. **Escape outputs** with `esc_html()`, `esc_attr()`, `esc_url()`
4. **Check capabilities** with `current_user_can()`

### For File Handling

1. **Never expose file paths** directly in HTML
2. **Use secure viewer endpoints** with nonce validation
3. **Verify ownership** before serving user files
4. **Clean output buffers** before `readfile()` to prevent corruption

```php
// GOOD: Secure file viewing
$viewer_url = admin_url( 'admin-post.php?action=pwp_view_file&id=' . $id . '&index=' . $index . '&_wpnonce=' . wp_create_nonce( 'pwp_view_file' ) );

// BAD: Direct file URL (publicly accessible)
$file_url = $upload_dir['baseurl'] . '/pwp-secured/file.pdf';
```

---

## 🧪 Testing

### Manual Test Scenarios

1. **Cache Compatibility:**
   - Enable WP Super Cache or W3 Total Cache
   - Submit form
   - Clear browser cache, revisit page
   - Verify nonce still works

2. **Rate Limiting:**
   - Submit form 10 times
   - 11th submission should be blocked
   - Wait 1 hour, should work again

3. **Secure File Upload:**
   - Upload a file through the form
   - Try accessing directly: `https://yoursite.com/wp-content/uploads/pwp-secured/file.pdf`
   - Expected: 403 Forbidden error
   - Admin should be able to view via secure viewer
   - User should only view their own files

4. **File Upload as Guest:**
   - Test as guest (should be locked)
   - Test as logged-in user (should work)
   - Verify MIME type validation

5. **GDPR Deletion:**
   - Create submissions with file uploads
   - Use Privacy Tools to delete by email
   - Verify database and files removed

---

## 🤝 Contributing

### Getting Started

1. Fork the repository
2. Create feature branch: `git checkout -b feature/my-feature`
3. Make changes following WordPress Coding Standards
4. Test thoroughly (see Testing section)
5. Commit: `git commit -m 'Add my feature'`
6. Push: `git push origin feature/my-feature`
7. Open Pull Request

### Coding Standards

- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use tabs for indentation
- Add PHPDoc blocks for all functions
- Sanitize all inputs, escape all outputs
- Use `$wpdb->prepare()` for SQL

### Pull Request Guidelines

**Include:**

- Clear description of changes
- Why the change is needed
- Screenshots (if UI changes)
- Test results

**Before submitting:**

- [ ] Code follows WordPress standards
- [ ] All functions have PHPDoc
- [ ] Tested with WordPress Debug mode ON
- [ ] No PHP warnings or errors
- [ ] Works with caching enabled

---

## 📖 Additional Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WP_List_Table Documentation](https://developer.wordpress.org/reference/classes/wp_list_table/)
- [Settings API](https://developer.wordpress.org/plugins/settings/settings-api/)
- [ProWPKit Website](https://prowpkit.com/)

---

## 💡 Tips & Tricks

### Debugging

Enable WordPress debug mode in `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Check logs in `wp-content/debug.log`

### Performance Optimization

1. **Use object caching** (Redis/Memcached) for transients
2. **Limit submission data size** - avoid storing huge text
3. **Index custom columns** if searching frequently
4. **Paginate results** in custom queries

### Common Issues

**Forms not submitting:**

- Check JavaScript console for errors
- Verify nonce is being fetched
- Check rate limit not exceeded

**Emails not sending:**

- Test with WP Mail SMTP plugin
- Check spam folder
- Verify email templates have no PHP errors

**Files not uploading:**

- Check PHP `upload_max_filesize`
- Verify directory permissions
- Check MIME type whitelist

**Files not viewable:**

- Verify `.htaccess` exists in `/pwp-secured/`
- Check file ownership in database
- Verify nonce in viewer URL

---

**Questions?** [Open an issue](https://github.com/dhanushrs1/pwp-forms/issues) or [start a discussion](https://github.com/dhanushrs1/pwp-forms/discussions)

**Made with ❤️ by the ProWPKit Team** | [Website](https://prowpkit.com/) | [GitHub](https://github.com/dhanushrs1)
