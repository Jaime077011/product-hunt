<?php
/**
 * Quiz Edit Debugging Tools
 *
 * This file loads all necessary debugging tools for the quiz edit functionality.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only load in admin area
if (!is_admin()) {
    return;
}

// Include the debugging files
require_once plugin_dir_path(__FILE__) . 'debug-quiz-edit.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/quiz-debug.php';

// Add a quick link to the debug tools from the quiz list page
function ph_add_debug_link_to_quiz_list($actions, $post) {
    if ($post->post_type === 'product_hunt_quiz') {
        $actions['debug'] = sprintf(
            '<a href="%s">Debug</a>',
            admin_url('admin.php?page=product-hunt-quiz-debug&quiz_id=' . $post->ID)
        );
    }
    return $actions;
}
add_filter('post_row_actions', 'ph_add_debug_link_to_quiz_list', 10, 2);

/**
 * Add notice about debug mode when editing quizzes
 */
function ph_add_debug_mode_notice() {
    $screen = get_current_screen();
    
    // Only show on quiz edit screen
    if (!$screen || $screen->base !== 'product-hunt_page_product-hunt-quiz' || !isset($_GET['quiz_id'])) {
        return;
    }
    
    ?>
    <div class="notice notice-info is-dismissible">
        <p>
            <strong>Debugging tools are active.</strong> 
            If you're experiencing issues with quiz editing, please 
            <a href="<?php echo esc_url(admin_url('admin.php?page=product-hunt-quiz-debug&quiz_id=' . intval($_GET['quiz_id']))); ?>">visit the Debug Tools</a> 
            for additional assistance.
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'ph_add_debug_mode_notice');
