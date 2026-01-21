/**
 * Import/Export JavaScript
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    /**
     * Handle DOI import form submission
     */
    $("#pm-import-form").on("submit", function (e) {
      e.preventDefault();

      var $form = $(this);
      var $btn = $("#pm-import-btn");
      var $spinner = $("#pm-import-spinner");
      var $results = $("#pm-import-results");
      var doiInput = $("#pm_doi_input").val().trim();

      if (!doiInput) {
        alert("Please enter at least one DOI");
        return;
      }

      // Show loading state
      $btn.prop("disabled", true);
      $spinner.addClass("is-active");
      $results.html(
        '<div class="notice notice-info"><p>Importing publications from Crossref...</p></div>',
      );

      // Send AJAX request
      $.ajax({
        url: pmImport.ajaxurl,
        type: "POST",
        data: {
          action: "pm_import_doi",
          nonce: pmImport.nonce,
          doi_input: doiInput,
        },
        success: function (response) {
          $btn.prop("disabled", false);
          $spinner.removeClass("is-active");

          if (response.success) {
            displayImportResults(response.data);
            $("#pm_doi_input").val(""); // Clear input
          } else {
            $results.html(
              '<div class="notice notice-error"><p>' +
                (response.data.message || "Import failed") +
                "</p></div>",
            );
          }
        },
        error: function (xhr, status, error) {
          $btn.prop("disabled", false);
          $spinner.removeClass("is-active");
          $results.html(
            '<div class="notice notice-error"><p>An error occurred: ' +
              error +
              "</p></div>",
          );
        },
      });
    });

    /**
     * Display import results
     */
    function displayImportResults(data) {
      var html = "";

      // Debug: log the data
      console.log("Import results data:", data);

      // Count created vs updated
      var createdCount = 0;
      var updatedCount = 0;
      $.each(data.imported, function (index, item) {
        console.log("Item " + index + ":", item);
        if (item.action === "updated") {
          updatedCount++;
        } else {
          createdCount++;
        }
      });

      // Summary
      html += '<div class="notice notice-success"><p>';
      html += "<strong>Import Complete!</strong> ";
      html +=
        "Successfully processed " +
        data.imported.length +
        " of " +
        data.total +
        " publications";

      if (createdCount > 0 && updatedCount > 0) {
        html += " (" + createdCount + " new, " + updatedCount + " updated)";
      } else if (updatedCount > 0) {
        html += " (all updated)";
      } else {
        html += " (all new)";
      }

      html += ".";
      html += "</p></div>";

      // Successful imports
      if (data.imported.length > 0) {
        html += "<h3>Successfully Imported:</h3>";
        html += '<div class="pm-results-list">';

        $.each(data.imported, function (index, item) {
          // Default to 'created' if action is not set
          var action = item.action || "created";
          var actionText = action === "updated" ? "UPDATED" : "NEW";
          var actionClass = action === "updated" ? "updated" : "created";
          var statusIcon = action === "updated" ? "↻" : "✓";
          var statusMessage =
            action === "updated"
              ? "This publication already existed and was updated with latest data from Crossref"
              : "This is a new publication that was just imported";

          html += '<div class="pm-result-item success ' + actionClass + '">';
          html += '<div class="pm-status-indicator">';
          html += '<span class="pm-status-icon">' + statusIcon + "</span>";
          html += '<span class="pm-action-badge">' + actionText + "</span>";
          html += "</div>";
          html += '<div class="pm-result-content">';
          html +=
            '<div class="pm-result-title">' + escapeHtml(item.title) + "</div>";
          html +=
            '<div class="pm-result-message"><strong>DOI:</strong> ' +
            escapeHtml(item.doi) +
            '<br><em class="pm-status-message">' +
            statusMessage +
            "</em></div>";
          html += "</div>";
          html += '<div class="pm-result-actions">';
          html +=
            '<a href="post.php?post=' +
            item.post_id +
            '&action=edit" class="button button-small">Edit</a>';
          html += "</div>";
          html += "</div>";
        });

        html += "</div>";
      }

      // Failed imports
      if (data.failed.length > 0) {
        html += "<h3>Failed to Import:</h3>";
        html += '<div class="pm-results-list">';

        $.each(data.failed, function (index, item) {
          html += '<div class="pm-result-item error">';
          html += '<div class="pm-result-content">';
          html +=
            '<div class="pm-result-title">DOI: ' +
            escapeHtml(item.doi) +
            "</div>";
          html +=
            '<div class="pm-result-message">Error: ' +
            escapeHtml(item.error) +
            "</div>";
          html += "</div>";
          html += "</div>";
        });

        html += "</div>";
      }

      $("#pm-import-results").html(html);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
      var map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      };
      return text.replace(/[&<>"']/g, function (m) {
        return map[m];
      });
    }

    /**
     * Export form validation
     */
    $('form[action*="admin-post.php"]').on("submit", function (e) {
      var format = $("#pm_export_format").val();

      if (!format) {
        e.preventDefault();
        alert("Please select an export format");
        return false;
      }
    });
  });
})(jQuery);
