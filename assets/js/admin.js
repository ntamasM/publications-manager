/**
 * Admin JavaScript for Publications Manager
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    /**
     * Dynamic field visibility based on publication type
     */
    function updateFieldVisibility() {
      var selectedType = $("#pm_type").val();

      // This will be populated by PHP inline script
      // See PM_Meta_Boxes::enqueue_scripts()
    }

    // Initialize on page load
    if ($("#pm_type").length) {
      updateFieldVisibility();
      $("#pm_type").on("change", updateFieldVisibility);
    }

    /**
     * Auto-generate BibTeX key suggestion
     */
    $("#pm_author, #pm_date").on("blur", function () {
      if ($("#pm_bibtex").val()) {
        return; // Don't overwrite existing key
      }

      var author = $("#pm_author").val();
      var date = $("#pm_date").val();

      if (author && date) {
        var firstAuthor = author.split(" and ")[0].split(",")[0];
        var year = date.substring(0, 4);
        var suggestedKey = firstAuthor.replace(/[^a-zA-Z]/g, "") + year;

        $("#pm_bibtex").val(suggestedKey).css("background-color", "#fff3cd");
        setTimeout(function () {
          $("#pm_bibtex").css("background-color", "");
        }, 2000);
      }
    });

    /**
     * Form validation
     */
    $("form#post").on("submit", function (e) {
      var errors = [];

      // Check required fields
      if (!$("#pm_type").val()) {
        errors.push("Publication Type is required");
      }
      if (!$("#pm_bibtex").val()) {
        errors.push("BibTeX Key is required");
      }
      if (!$("#pm_date").val()) {
        errors.push("Publication Date is required");
      }
      if (!$("#pm_author").val()) {
        errors.push("Author is required");
      }

      if (errors.length > 0) {
        e.preventDefault();
        alert("Please fix the following errors:\n\n- " + errors.join("\n- "));
        return false;
      }
    });

    /**
     * Highlight recommended fields on hover
     */
    $(".pm-field-wrapper.pm-recommended").hover(
      function () {
        $(this).css("background-color", "#e8f2fc");
      },
      function () {
        $(this).css("background-color", "#f0f6fc");
      },
    );
  });
})(jQuery);
