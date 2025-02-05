jQuery(document).ready(function($) {
    $('#pdp-process-button').on('click', function() {
        // Retrieve form values.
        var postsToUpdate = $('#pdp-posts-to-update').val();
        var daysOffset = $('#pdp-days-offset').val();
        var timeRangeStart = $('#pdp-time-range-start').val();
        var timeRangeEnd = $('#pdp-time-range-end').val();

        // Confirm the action.
        if (!confirm('Are you sure you want to update the dates for the last ' + postsToUpdate + ' posts?')) {
            return;
        }

        // Disable button during processing.
        $('#pdp-process-button').attr('disabled', 'disabled');
        $('#pdp-result').html('Processing...');

        $.ajax({
            url: pdp_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'pdp_process_posts',
                nonce: pdp_vars.nonce,
                pdp_posts_to_update: postsToUpdate,
                pdp_days_offset: daysOffset,
                pdp_time_range_start: timeRangeStart,
                pdp_time_range_end: timeRangeEnd
            },
            success: function(response) {
                $('#pdp-process-button').removeAttr('disabled');
                if (response.success) {
                    $('#pdp-result').html(response.data);
                } else {
                    $('#pdp-result').html('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $('#pdp-process-button').removeAttr('disabled');
                $('#pdp-result').html('AJAX error: ' + error);
            }
        });
    });
});
