jQuery(document).ready(function ($) {
  // SECURITY FIX: Fetch fresh nonce and user data on page load
  // This prevents cached pages from having expired nonces or exposed PII
  var formNonce = null;

  // Initialize all forms on the page
  $(".pwp-form").each(function () {
    var form = $(this);

    // Fetch fresh nonce and user data via AJAX
    $.ajax({
      url: pwp_forms_vars.ajax_url,
      type: "POST",
      data: {
        action: "pwp_get_form_nonce",
      },
      success: function (response) {
        if (response.success) {
          formNonce = response.data.nonce;
        }
      },
    });

    // Fetch user data and populate fields if logged in
    $.ajax({
      url: pwp_forms_vars.ajax_url,
      type: "POST",
      data: {
        action: "pwp_get_user_data",
      },
      success: function (response) {
        if (response.success && response.data.logged_in) {
          // Find name and email fields (case-insensitive)
          var nameField = form.find(
            'input[name="name" i], input[name="your-name" i]',
          );
          var emailField = form.find(
            'input[name="email" i], input[name="your-email" i]',
          );

          // Populate and lock fields for logged-in users
          if (nameField.length) {
            nameField.val(response.data.name).prop("readonly", true);
          }
          if (emailField.length) {
            emailField.val(response.data.email).prop("readonly", true);
          }
        }
      },
    });
  });

  // Form submission handler
  $(".pwp-form").on("submit", function (e) {
    e.preventDefault();

    var form = $(this);
    var formData = new FormData(this);
    var messageBox = form.find(".pwp-response-message");

    // Check if nonce is ready
    if (!formNonce) {
      messageBox
        .addClass("pwp-error")
        .html("Security initialization failed. Please refresh the page.")
        .show();
      return;
    }

    // Append action and fresh nonce
    formData.append("action", "pwp_submit_form");
    formData.append("security", formNonce);
    formData.append("form_id", form.data("id"));

    // UI Loading State
    var btn = form.find('button[type="submit"]');
    var originalText = btn.text();
    btn.prop("disabled", true).text("Sending...");
    messageBox.hide().removeClass("pwp-success pwp-error").html("");

    $.ajax({
      url: pwp_forms_vars.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          messageBox.addClass("pwp-success").html(response.data.message).show();
          form[0].reset(); // Reset form on success

          // Re-populate locked fields after reset
          $.ajax({
            url: pwp_forms_vars.ajax_url,
            type: "POST",
            data: { action: "pwp_get_user_data" },
            success: function (userData) {
              if (userData.success && userData.data.logged_in) {
                var nameField = form.find(
                  'input[name="name" i], input[name="your-name" i]',
                );
                var emailField = form.find(
                  'input[name="email" i], input[name="your-email" i]',
                );
                if (nameField.length) nameField.val(userData.data.name);
                if (emailField.length) emailField.val(userData.data.email);
              }
            },
          });
        } else {
          messageBox.addClass("pwp-error").html(response.data.message).show();
        }
      },
      error: function () {
        messageBox
          .addClass("pwp-error")
          .html("Server error. Please try again.")
          .show();
      },
      complete: function () {
        btn.prop("disabled", false).text(originalText);
      },
    });
  });

  // Locked Upload Handler
  $(document).on("click", ".pwp-upload-locked-wrapper", function (e) {
    e.preventDefault();
    alert("You must log in or register to upload files.");
  });
});
