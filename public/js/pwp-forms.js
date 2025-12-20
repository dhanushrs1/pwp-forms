jQuery(document).ready(function ($) {
  $(".pwp-form").on("submit", function (e) {
    e.preventDefault();

    var form = $(this);
    var formData = new FormData(this);
    var messageBox = form.find(".pwp-response-message");

    // Append action and security
    formData.append("action", "pwp_submit_form");
    formData.append("security", pwp_forms_vars.nonce);
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
