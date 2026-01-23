jQuery(document).ready(function ($) {
    
    // 1. Initialize WordPress Color Picker
    // (This fixes the "wrong symbol" issue by using the WP standard picker)
    if ($.fn.wpColorPicker) {
        $('.pwp-color-field').wpColorPicker();
    }

    // 2. Variable Insertion Logic (For Email Templates & Form Builder)
    // Works for buttons with class "pwp-insert-var" OR "pwp-tag-btn"
    $(document).on('click', '.pwp-insert-var, .pwp-tag-btn', function (e) {
        e.preventDefault();
        
        // Get value and target
        // For Form Builder, the value is in the 'onclick' data or data-value
        // We standardized to data-value in the PHP update below.
        const value = $(this).data('value');
        
        // Determine Target: 
        // If data-target exists, use it. Otherwise default to 'pwp_form_html' (Form Editor)
        let targetId = $(this).data('target');
        if (!targetId) {
            targetId = 'pwp_form_html';
        }

        const $textarea = $('#' + targetId);

        if ($textarea.length) {
            const domTextarea = $textarea[0];
            
            // Insert at Cursor Position
            if (document.selection) {
                domTextarea.focus();
                const sel = document.selection.createRange();
                sel.text = value;
            } else if (domTextarea.selectionStart || domTextarea.selectionStart == '0') {
                const startPos = domTextarea.selectionStart;
                const endPos = domTextarea.selectionEnd;
                domTextarea.value = domTextarea.value.substring(0, startPos)
                    + value
                    + domTextarea.value.substring(endPos, domTextarea.value.length);
                
                // Move cursor after insertion
                domTextarea.selectionStart = startPos + value.length;
                domTextarea.selectionEnd = startPos + value.length;
            } else {
                domTextarea.value += value;
            }
            domTextarea.focus();
        }
    });

    // 3. Tab Switching Logic (Moved here for reliability)
    $('.pwp-tab-item').click(function(){
        var tab = $(this).data('tab');
        $('.pwp-tab-item').removeClass('active');
        $(this).addClass('active');
        $('.pwp-tab-content').removeClass('active');
        $('#pwp-tab-' + tab).addClass('active');
    });

    // --- Modal Preview Logic (Existing) ---
    const modal = $('#pwp-preview-modal');
    const closeBtn = $('.pwp-modal-close');
    const previewFrame = $('#pwp-email-preview-frame');

    // Mock Data for Preview
    const mockData = {
        '{body}': '<table style="width:100%; border-collapse:collapse;"><tr><td style="padding:10px; border-bottom:1px solid #eee;"><strong>Name</strong></td><td style="padding:10px; border-bottom:1px solid #eee;">John Doe</td></tr><tr><td style="padding:10px; border-bottom:1px solid #eee;"><strong>Email</strong></td><td style="padding:10px; border-bottom:1px solid #eee;">john@example.com</td></tr><tr><td style="padding:10px; border-bottom:1px solid #eee;"><strong>Message</strong></td><td style="padding:10px; border-bottom:1px solid #eee;">I love this plugin!</td></tr></table>',
        '{site_name}': 'My WordPress Site',
        '{form_title}': 'Contact Form',
        '{logo}': '<img src="https://via.placeholder.com/150x50" alt="Logo">' 
    };

    function renderPreview() {
        // Content source is usually the body field if available, or just use mock
        // For global settings preview, we just show styles
        const content = mockData['{body}'];
        
        let processedContent = content;

        // Fetch Styles
        const bg = $('input[name="pwp_email_bg_color"]').val();
        const container = $('input[name="pwp_email_container_bg"]').val();
        const text = $('input[name="pwp_email_text_color"]').val();
        const accent = $('input[name="pwp_email_accent_color"]').val();
        const font = $('input[name="pwp_email_font_family"]').val();
        const size = $('input[name="pwp_email_font_size"]').val();
        const footer = $('textarea[name="pwp_email_footer"]').val();
        const logoUrl = $('input[name="pwp_email_logo"]').val();

        let logoHtml = '';
        if(logoUrl) logoHtml = `<div style="text-align:center; padding-bottom:20px;"><img src="${logoUrl}" style="max-width:200px;"></div>`;

        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { margin: 0; padding: 40px; background-color: ${bg}; font-family: ${font}; color: ${text}; }
                    .email-wrapper { max-width: 600px; margin: 0 auto; background: ${container}; padding: 30px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
                    p { font-size: ${size}px; line-height: 1.6; margin-bottom: 20px; }
                    a { color: ${accent}; text-decoration: none; }
                    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; }
                </style>
            </head>
            <body>
                <table width="100%"><tr><td align="center">
                ${logoHtml}
                <div class="email-wrapper">
                    <h1>Thanks for contacting us!</h1>
                    <p>This is a preview of your email styling.</p>
                    ${processedContent}
                </div>
                <div class="footer">${footer}</div>
                </td></tr></table>
            </body>
            </html>
        `;

        const doc = previewFrame[0].contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
    }

    $('.pwp-preview-btn').on('click', function (e) {
        e.preventDefault();
        renderPreview();
        modal.fadeIn(200);
        $('body').css('overflow', 'hidden');
    });

    closeBtn.on('click', function () {
        modal.fadeOut(200);
        $('body').css('overflow', 'auto');
    });

    $(window).on('click', function (e) {
        if ($(e.target).is(modal)) {
            modal.fadeOut(200);
            $('body').css('overflow', 'auto');
        }
    });

});
