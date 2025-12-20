document.addEventListener("DOMContentLoaded", function () {
  const previewFrame = document.getElementById("pwp-email-preview-frame");
  if (!previewFrame) return;

  const doc =
    previewFrame.contentDocument || previewFrame.contentWindow.document;

    // Inputs to watch
    const inputs = [
        'pwp_email_logo',
        'pwp_email_bg_color',
        'pwp_email_container_bg',
        'pwp_email_text_color',
        'pwp_email_accent_color',
        'pwp_email_font_family',
        'pwp_email_font_size',
        'pwp_email_footer',
        'pwp_email_template_admin',
        'pwp_email_template_user'
    ];

    let currentMode = 'admin'; // 'admin' or 'user'

    // Mock Data
    const mockData = {
        title: 'New Submission',
        body: '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse; width:100%;"><tr><td style="background:#f9f9f9; width:30%;"><strong>Name</strong></td><td>John Doe</td></tr><tr><td style="background:#f9f9f9; width:30%;"><strong>Email</strong></td><td>john@example.com</td></tr><tr><td style="background:#f9f9f9; width:30%;"><strong>Message</strong></td><td>This is a test message to preview the email design.</td></tr></table><p><small>Submission ID: 123</small></p>',
        site_name: 'My WordPress Site',
        form_title: 'Contact Form',
        footer: 'Powered by ProWPKit'
    };

    function getVal(name) {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? el.value : '';
    }

    function updatePreview() {
        // Gather styles
        const logo = getVal('pwp_email_logo');
        const bg = getVal('pwp_email_bg_color');
        const containerBg = getVal('pwp_email_container_bg');
        const text = getVal('pwp_email_text_color');
        const font = getVal('pwp_email_font_family');
        const fontSize = getVal('pwp_email_font_size') || '16';
        const footerText = getVal('pwp_email_footer');

        // Logic for Content
        let rawContent = '';

        if (currentMode === 'admin') {
            let tmpl = getVal('pwp_email_template_admin');
            if (!tmpl) tmpl = "<h1>New Submission</h1><p>You have received a new form submission:</p>{body}<p><small>{site_name}</small></p>";
            rawContent = tmpl;
        } else {
            let tmpl = getVal('pwp_email_template_user');
            if (!tmpl) tmpl = "<h1>Thank you!</h1><p>We have received your submission.</p>{body}<p>Best Regards,<br>{site_name}</p>";
            rawContent = tmpl;
        }

        // Replacements
        let content = rawContent
            .replace(/{body}/g, mockData.body)
            .replace(/{site_name}/g, mockData.site_name)
            .replace(/{form_title}/g, mockData.form_title);

        const logoHtml = logo ? `<div style="text-align:center; padding-bottom:20px;"><img src="${logo}" style="max-width:200px; height:auto; border:0;"></div>` : '';

        const html = `
<!DOCTYPE html>
<html>
<body style="margin:0; padding:0; background-color:${bg}; font-family:${font};">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:${bg}; min-height:100vh;">
        <tr>
            <td align="center" style="padding:40px 10px;">
                ${logoHtml}
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:${containerBg}; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.05); max-width:600px;">
                    <tr>
                        <td align="left" style="padding:40px; color:${text}; font-size:${fontSize}px; line-height:1.6; font-family:${font};">
                            ${content}
                        </td>
                    </tr>
                </table>
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding:20px; color:#888888; font-size:12px;">
                            ${footerText.replace(/\n/g, "<br>")}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        `;

    doc.open();
    doc.write(html);
    doc.close();
  }

  // Attach Listeners
  inputs.forEach((name) => {
    const el = document.querySelector(`[name="${name}"]`);
    if (el) {
      el.addEventListener("input", updatePreview);
      el.addEventListener("change", updatePreview);
    }
  });

  // Mode Switchers
  const btnAdmin = document.getElementById("pwp-preview-mode-admin");
  const btnUser = document.getElementById("pwp-preview-mode-user");

  if (btnAdmin && btnUser) {
    btnAdmin.addEventListener("click", (e) => {
      e.preventDefault();
      currentMode = "admin";
      btnAdmin.classList.add("active");
      btnUser.classList.remove("active");
      updatePreview();
    });
    btnUser.addEventListener("click", (e) => {
      e.preventDefault();
      currentMode = "user";
      btnUser.classList.add("active");
      btnAdmin.classList.remove("active");
      updatePreview();
    });
  }

  // Initial load
  updatePreview();
});
