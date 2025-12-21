jQuery(document).ready(function ($) {
    
    // --- Variable Insertion Logic ---
    $('.pwp-insert-var').on('click', function (e) {
        e.preventDefault();
        const value = $(this).data('value');
        const targetId = $(this).data('target');
        const $textarea = $('#' + targetId);

        if ($textarea.length) {
            const domTextarea = $textarea[0];
            
            // Insert at Cursor Position
            if (document.selection) {
                // IE support
                domTextarea.focus();
                const sel = document.selection.createRange();
                sel.text = value;
            } else if (domTextarea.selectionStart || domTextarea.selectionStart == '0') {
                // MOZILLA and others
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

    // --- Modal Preview Logic ---
    const modal = $('#pwp-preview-modal');
    const closeBtn = $('.pwp-modal-close');
    const previewFrame = $('#pwp-email-preview-frame');

    // Mock Data
    const mockData = {
        '{body}': '<table style="width:100%; border-collapse:collapse;"><tr><td style="padding:10px; border-bottom:1px solid #eee;"><strong>Name</strong></td><td style="padding:10px; border-bottom:1px solid #eee;">John Doe</td></tr><tr><td style="padding:10px; border-bottom:1px solid #eee;"><strong>Email</strong></td><td style="padding:10px; border-bottom:1px solid #eee;">john@example.com</td></tr><tr><td style="padding:10px; border-bottom:1px solid #eee;"><strong>Message</strong></td><td style="padding:10px; border-bottom:1px solid #eee;">I love this plugin!</td></tr></table>',
        '{site_name}': 'My WordPress Site',
        '{form_title}': 'Contact Form',
        '{logo}': '<img src="https://via.placeholder.com/150x50" alt="Logo">' // Default fallback
    };

    function renderPreview(targetTextareaId) {
        const content = $('#' + targetTextareaId).val();
        
        // 2. Fetch Styles & Logo
        const logoUrl = $('input[name="pwp_email_logo"]').val();
        if (logoUrl) {
            mockData['{logo}'] = '<img src="' + logoUrl + '" alt="Logo" style="max-height: 50px;">';
        }

        // 3. Replacements
        let processedContent = content;
        Object.keys(mockData).forEach(key => {
            const regex = new RegExp(key, 'g');
            processedContent = processedContent.replace(regex, mockData[key]);
        });

        // 4. Fetch Styles
        const bg = $('input[name="pwp_email_bg_color"]').val();
        const container = $('input[name="pwp_email_container_bg"]').val();
        const text = $('input[name="pwp_email_text_color"]').val();
        const accent = $('input[name="pwp_email_accent_color"]').val();
        const font = $('input[name="pwp_email_font_family"]').val();
        const size = $('input[name="pwp_email_font_size"]').val();
        const footer = $('textarea[name="pwp_email_footer"]').val();

        // 3. Build HTML
        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { margin: 0; padding: 40px; background-color: ${bg}; font-family: ${font}; color: ${text}; }
                    .email-wrapper { max-width: 600px; margin: 0 auto; background: ${container}; padding: 30px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
                    p { font-size: ${size}px; line-height: 1.6; margin-bottom: 20px; }
                    a { color: ${accent}; text-decoration: none; }
                    h1, h2, h3 { color: ${accent}; margin-top: 0; }
                    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; }
                </style>
            </head>
            <body>
                <div class="email-wrapper">
                    ${processedContent}
                </div>
                <div class="footer">
                    ${footer}
                </div>
            </body>
            </html>
        `;

        // 4. Inject
        const doc = previewFrame[0].contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
    }

    // Open Modal
    $('.pwp-preview-btn').on('click', function (e) {
        e.preventDefault();
        const targetId = $(this).data('target');
        
        renderPreview(targetId);
        modal.fadeIn(200);
        $('body').css('overflow', 'hidden'); // Prevent background scrolling
    });

    // Close Modal
    closeBtn.on('click', function () {
        modal.fadeOut(200);
        $('body').css('overflow', 'auto');
    });

    // Close on click outside
    $(window).on('click', function (e) {
        if ($(e.target).is(modal)) {
            modal.fadeOut(200);
            $('body').css('overflow', 'auto');
        }
    });

});
