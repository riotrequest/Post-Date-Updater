jQuery(document).ready(function($) {
    // Process button click event
    $('#pdp-process-button').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pdp_process_posts',
            },
            beforeSend: function() {
                // Disable the button during processing
                $('#pdp-process-button').attr('disabled', 'disabled');
            },
            success: function(response) {
                // Enable the button after processing
                $('#pdp-process-button').removeAttr('disabled');
                alert(response.data);
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    });
});
