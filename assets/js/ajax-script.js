jQuery(document).ready(function ($) {
  $("#serve-static-send-requests-button").on("click", function () {
    $("#serve-static-request-table tbody").empty(); // Clear the status table.
    $("#serve-static-request-success").empty(); // Clear the status table.
    var button = document.getElementById("serve-static-send-requests-button");
    button.setAttribute("disabled", "");
    $("#serve-static-request-success").append("<p>Getting URLs...</p>"); // Show fetching message

    // Initialize the failed requests counter
    var failedRequestsCount = 0;

    // Get URLs from the server
    $.ajax({
      url: ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "get_urls",
        security: ajax_object.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#serve-static-request-success").empty(); // Clear the "Getting URLS.."
          $("#serve-static-request-success").append(
            "<p>In Progress.... </br>Please avoid reloading or deleting or navigating away from this window. Instead, you can open a new Tab, and use the WordPress Admin Dashboard.</p>"
          );
          let urls = response.data.urls;
          sendRequestSequentially(urls, 0);
        } else {
          $("#serve-static-request-status").append(
            "<p>" + response.data.message + "</p>"
          );
        }
      },
      error: function (error) {
        $("#serve-static-request-status").append(
          "<p>An error occurred while fetching the URLs.</p>"
        );
      },
    });

    function sendRequestSequentially(urls, index) {
      if (index >= urls.length) {
        $("#serve-static-request-success").empty(); // Clear the "In Progress..."
        $("#serve-static-request-success").append(
          `<p style="color: green;"><b>All Done...</b></p>`
        ); // Show all done message
        $("#serve-static-request-success").append(
          `<p class="error">Failed requests: ${failedRequestsCount}</p>` // Display failed requests count
        );
        // Save the failed requests count in the database
        $.ajax({
          url: ajax_object.ajax_url,
          type: "POST",
          data: {
            action: "update_failed_requests_count",
            security: ajax_object.nonce,
            failed_count: failedRequestsCount,
          },
        });
        var button = document.getElementById(
          "serve-static-send-requests-button"
        );
        button.removeAttribute("disabled");
        return; // Stop if all URLs have been processed
      }

      // Dynamically populate table row
      var newRow = $("<tr>");
      newRow.append("<td>" + urls[index] + "</td>");
      newRow.append("<td>Sending...</td>");
      $("#serve-static-request-table tbody").append(newRow);

      // Send AJAX request
      $.ajax({
        url: ajax_object.ajax_url,
        type: "POST",
        data: {
          action: "send_single_request",
          security: ajax_object.nonce,
          urls: urls,
          last_url: urls[urls.length - 1],
          url_index: index,
        },
        success: function (response) {
          if (response.success) {
            newRow.find("td:last").text(response.data.message); // Update status
          } else {
            var error_message = `<span class="info-icon">\
                <i class="fa fa-info-circle"></i>\
                <span class="tooltip-text" style="color: red;"><b>${response.data.error}</b></span>\
              </span>`;

            newRow.find("td:last").html(error_message);

            failedRequestsCount++; // Increment failed requests counter
          }
          // Process the next URL after the current one is done
          setTimeout(() => {
            sendRequestSequentially(urls, index + 1);
          }, ajax_object.time_interval * 1000); // Pause for X seconds
        },
        error: function (error) {
          var error_message = `<span class="info-icon">\
              <i class="fa fa-info-circle"></i>\
              <span class="tooltip-text" style="color: red;"><b>${error.status}</b></span>\
            </span>`;
          console.log(error.status);

          newRow.find("td:last").html(error_message);

          failedRequestsCount++; // Increment failed requests counter

          // Process the next URL even if there's an error
          setTimeout(() => {
            sendRequestSequentially(urls, index + 1);
          }, ajax_object.time_interval * 1000); // Pause for X seconds
        },
      });
    }
  });
});
