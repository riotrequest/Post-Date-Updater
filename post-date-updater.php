<?php
/*
Plugin Name: Post Date Updater
Description: Updates the dates of the last N posts.
*/

// Create a menu item in the admin dashboard
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

// Render the dashboard page
function pdp_render_dashboard() {
    $posts_to_update = isset($_POST['pdp_posts_to_update']) ? absint($_POST['pdp_posts_to_update']) : 100;
    ?>
    <div class="wrap">
        <h1>Post Date Updater</h1>
        <form method="post" action="<?php echo admin_url('admin.php?page=post-date-updater'); ?>">
            <label for="pdp-posts-input">Number of Posts to Update:</label>
            <input type="number" id="pdp-posts-input" name="pdp_posts_to_update" value="<?php echo $posts_to_update; ?>" min="1" step="1">
            <button type="submit" class="button button-primary">Update</button>
        </form>
        <p>Click the button below to update the dates of the last <?php echo $posts_to_update; ?> posts.</p>
        <button id="pdp-process-button" class="button button-primary">Process</button>
    </div>
    <?php
}

// Enqueue the necessary scripts
function pdp_enqueue_scripts() {
    wp_enqueue_script('pdp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'pdp_enqueue_scripts');

// Handle the AJAX request to update post dates
function pdp_process_posts() {
    // Check if the user is logged in and has the necessary permissions
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }

    $posts_to_update = isset($_POST['pdp_posts_to_update']) ? absint($_POST['pdp_posts_to_update']) : 100;

    // Get the current date and time
    $current_datetime = current_time('Y-m-d H:i:s');

    // Get the posts to update
    $posts = get_posts(array(
        'numberposts' => $posts_to_update,
        'orderby'     => 'post_date',
        'order'       => 'DESC',
    ));

    // Update the post dates and times
    $days_to_subtract = 0;
    foreach ($posts as $post) {
        $new_date = date('Y-m-d', strtotime("-$days_to_subtract days", strtotime($current_datetime)));
        $new_time = date('H:i:s', strtotime(rand(12, 17) . ':00:00'));
        $new_datetime = $new_date . ' ' . $new_time;
        wp_update_post(array(
            'ID'            => $post->ID,
            'post_date'     => $new_datetime,
            'post_date_gmt' => get_gmt_from_date($new_datetime),
        ));
        $days_to_subtract++;
    }

    wp_send_json_success('Post dates and times updated successfully.');
}
add_action('wp_ajax_pdp_process_posts', 'pdp_process_posts');

// Register the AJAX action
function pdp_register_ajax_action() {
    add_action('wp_ajax_pdp_process_posts', 'pdp_process_posts');
}
add_action('wp_loaded', 'pdp_register_ajax_action');
