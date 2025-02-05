<?php
/*
Plugin Name: Post Date Updater
Description: Updates the dates of the last N posts with customizable options.
Version: 2.0
Author: riotrequest
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Create a menu item in the admin dashboard.
function pdp_create_menu() {
    add_menu_page(
        'Post Date Updater',
        'Post Date Updater',
        'manage_options',
        'post-date-updater',
        'pdp_render_dashboard',
        'dashicons-clock'
    );
}
add_action('admin_menu', 'pdp_create_menu');

// Render the dashboard page.
function pdp_render_dashboard() {
    // Set defaults or retrieve from POST.
    $posts_to_update = isset($_POST['pdp_posts_to_update']) ? absint($_POST['pdp_posts_to_update']) : 100;
    $days_offset      = isset($_POST['pdp_days_offset']) ? absint($_POST['pdp_days_offset']) : 0;
    $time_range_start = isset($_POST['pdp_time_range_start']) ? absint($_POST['pdp_time_range_start']) : 12;
    $time_range_end   = isset($_POST['pdp_time_range_end']) ? absint($_POST['pdp_time_range_end']) : 17;
    ?>
    <div class="wrap">
        <h1>Post Date Updater</h1>
        <form id="pdp-settings-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <?php wp_nonce_field('pdp_update_settings', 'pdp_nonce'); ?>
            <input type="hidden" name="action" value="pdp_save_settings">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="pdp-posts-to-update">Number of Posts to Update:</label></th>
                    <td>
                        <input type="number" id="pdp-posts-to-update" name="pdp_posts_to_update" value="<?php echo esc_attr($posts_to_update); ?>" min="1" step="1">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pdp-days-offset">Initial Days Offset:</label></th>
                    <td>
                        <input type="number" id="pdp-days-offset" name="pdp_days_offset" value="<?php echo esc_attr($days_offset); ?>" min="0" step="1">
                        <p class="description">Start subtracting this many days from the current date for the first post.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pdp-time-range-start">Random Time Range Start (Hour):</label></th>
                    <td>
                        <input type="number" id="pdp-time-range-start" name="pdp_time_range_start" value="<?php echo esc_attr($time_range_start); ?>" min="0" max="23" step="1">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pdp-time-range-end">Random Time Range End (Hour):</label></th>
                    <td>
                        <input type="number" id="pdp-time-range-end" name="pdp_time_range_end" value="<?php echo esc_attr($time_range_end); ?>" min="0" max="23" step="1">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button button-secondary" value="Save Settings">
            </p>
        </form>
        <hr>
        <p>Click the button below to update the dates of the last <?php echo esc_html($posts_to_update); ?> posts.</p>
        <button id="pdp-process-button" class="button button-primary">Process</button>
        <div id="pdp-result" style="margin-top:20px; white-space: pre-wrap;"></div>
    </div>
    <?php
}

// Save settings if submitted.
function pdp_save_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }
    check_admin_referer('pdp_update_settings', 'pdp_nonce');
    // In this example, we simply reload the page to use the submitted values.
    // You can also save these settings to the database if you need persistence.
    wp_redirect(admin_url('admin.php?page=post-date-updater'));
    exit;
}
add_action('admin_post_pdp_save_settings', 'pdp_save_settings');

// Enqueue the necessary scripts.
function pdp_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_post-date-updater') {
        return;
    }
    wp_enqueue_script('pdp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '2.0', true);
    wp_localize_script('pdp-script', 'pdp_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('pdp_process_nonce'),
    ));
}
add_action('admin_enqueue_scripts', 'pdp_enqueue_scripts');

// Handle the AJAX request to update post dates.
function pdp_process_posts() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    check_ajax_referer('pdp_process_nonce', 'nonce');

    $posts_to_update = isset($_POST['pdp_posts_to_update']) ? absint($_POST['pdp_posts_to_update']) : 100;
    $days_offset      = isset($_POST['pdp_days_offset']) ? absint($_POST['pdp_days_offset']) : 0;
    $time_range_start = isset($_POST['pdp_time_range_start']) ? absint($_POST['pdp_time_range_start']) : 12;
    $time_range_end   = isset($_POST['pdp_time_range_end']) ? absint($_POST['pdp_time_range_end']) : 17;

    if ($time_range_start > $time_range_end) {
        wp_send_json_error('Invalid time range: start hour must be less than or equal to end hour.');
    }

    $current_datetime = current_time('Y-m-d H:i:s');
    $posts = get_posts(array(
        'numberposts' => $posts_to_update,
        'orderby'     => 'post_date',
        'order'       => 'DESC',
        'post_status' => 'any',
    ));

    $days_to_subtract = $days_offset;
    $results = array();
    foreach ($posts as $post) {
        $new_date = date('Y-m-d', strtotime("-$days_to_subtract days", strtotime($current_datetime)));
        // Randomize the hour, minute, and second within the defined time range.
        $rand_hour = rand($time_range_start, $time_range_end);
        $rand_minute = rand(0, 59);
        $rand_second = rand(0, 59);
        $new_time = sprintf('%02d:%02d:%02d', $rand_hour, $rand_minute, $rand_second);
        $new_datetime = $new_date . ' ' . $new_time;
        $result = wp_update_post(array(
            'ID'            => $post->ID,
            'post_date'     => $new_datetime,
            'post_date_gmt' => get_gmt_from_date($new_datetime),
        ));
        if (is_wp_error($result)) {
            $results[] = "Failed to update post ID {$post->ID}: " . $result->get_error_message();
        } else {
            $results[] = "Updated post ID {$post->ID} to $new_datetime";
        }
        $days_to_subtract++;
    }

    wp_send_json_success( implode("\n", $results) );
}
add_action('wp_ajax_pdp_process_posts', 'pdp_process_posts');
?>
