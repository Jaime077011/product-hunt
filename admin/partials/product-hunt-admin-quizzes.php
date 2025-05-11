<?php
/**
 * Admin template for displaying all quizzes
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle filtering and sorting
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'created_at';
$sort_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Build query conditions
$where = array("1=1");
if (!empty($status_filter)) {
    $where[] = $wpdb->prepare("q.status = %s", $status_filter);
}
if (!empty($category_filter)) {
    $where[] = $wpdb->prepare("q.category = %s", $category_filter);
}

// Get all quizzes from the database
global $wpdb;
$quizzes = $wpdb->get_results(
    "SELECT q.*, COUNT(DISTINCT qp.id) as completions, 
            COUNT(DISTINCT qu.id) as questions_count,
            u.display_name as author_name
     FROM {$wpdb->prefix}ph_quizzes q
     LEFT JOIN {$wpdb->prefix}ph_quiz_performance qp ON q.id = qp.quiz_id
     LEFT JOIN {$wpdb->prefix}ph_questions qu ON q.id = qu.quiz_id
     LEFT JOIN {$wpdb->users} u ON q.author_id = u.ID
     WHERE " . implode(' AND ', $where) . "
     GROUP BY q.id
     ORDER BY q.$sort_by $sort_order"
);

// Get unique categories for filter dropdown
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$wpdb->prefix}ph_quizzes WHERE category != ''");
?>

<div class="wrap product-hunt-admin-wrap">
    <div class="product-hunt-admin-header">
        <h1><?php _e('Product Quiz Manager', 'product-hunt'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz'); ?>" class="page-title-action">
            <?php _e('Add New Quiz', 'product-hunt'); ?>
        </a>
    </div>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Quiz saved successfully.', 'product-hunt'); ?></p>
        </div>
    <?php elseif (isset($_GET['message']) && $_GET['message'] == '2'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Quiz deleted successfully.', 'product-hunt'); ?></p>
        </div>
    <?php elseif (isset($_GET['message']) && $_GET['message'] == '3'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Quiz duplicated successfully.', 'product-hunt'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="product-hunt-admin-content">
        <div class="product-hunt-filter-bar">
            <form method="get">
                <input type="hidden" name="page" value="product-hunt-quizzes">
                
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'product-hunt'); ?></option>
                    <option value="published" <?php selected($status_filter, 'published'); ?>><?php _e('Published', 'product-hunt'); ?></option>
                    <option value="draft" <?php selected($status_filter, 'draft'); ?>><?php _e('Draft', 'product-hunt'); ?></option>
                </select>
                
                <?php if (!empty($categories)): ?>
                <select name="category">
                    <option value=""><?php _e('All Categories', 'product-hunt'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category); ?>" <?php selected($category_filter, $category); ?>><?php echo esc_html($category); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                
                <select name="sort">
                    <option value="created_at" <?php selected($sort_by, 'created_at'); ?>><?php _e('Date Created', 'product-hunt'); ?></option>
                    <option value="title" <?php selected($sort_by, 'title'); ?>><?php _e('Title', 'product-hunt'); ?></option>
                    <option value="status" <?php selected($sort_by, 'status'); ?>><?php _e('Status', 'product-hunt'); ?></option>
                </select>
                
                <select name="order">
                    <option value="DESC" <?php selected($sort_order, 'DESC'); ?>><?php _e('Descending', 'product-hunt'); ?></option>
                    <option value="ASC" <?php selected($sort_order, 'ASC'); ?>><?php _e('Ascending', 'product-hunt'); ?></option>
                </select>
                
                <button type="submit" class="button"><?php _e('Filter', 'product-hunt'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=product-hunt-quizzes'); ?>" class="button"><?php _e('Reset', 'product-hunt'); ?></a>
            </form>
        </div>
        
        <?php if (empty($quizzes)): ?>
            <div class="product-hunt-no-quizzes">
                <p><?php _e('No quizzes found. Click "Add New Quiz" to create your first quiz.', 'product-hunt'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped product-hunt-quizzes-table">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'product-hunt'); ?></th>
                        <th><?php _e('Questions', 'product-hunt'); ?></th>
                        <th><?php _e('Status', 'product-hunt'); ?></th>
                        <th><?php _e('Category', 'product-hunt'); ?></th>
                        <th><?php _e('Completions', 'product-hunt'); ?></th>
                        <th><?php _e('Author', 'product-hunt'); ?></th>
                        <th><?php _e('Shortcode', 'product-hunt'); ?></th>
                        <th><?php _e('Date Created', 'product-hunt'); ?></th>
                        <th><?php _e('Actions', 'product-hunt'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quizzes as $quiz): ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz&id=' . $quiz->id); ?>">
                                        <?php echo esc_html($quiz->title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo intval($quiz->questions_count); ?></td>
                            <td>
                                <span class="product-hunt-status product-hunt-status-<?php echo sanitize_html_class($quiz->status); ?>">
                                    <?php echo esc_html(ucfirst($quiz->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($quiz->category); ?></td>
                            <td><?php echo intval($quiz->completions); ?></td>
                            <td><?php echo esc_html($quiz->author_name); ?></td>
                            <td>
                                <code>[product_quiz id="<?php echo esc_attr($quiz->id); ?>"]</code>
                                <button type="button" class="button-link copy-shortcode" data-shortcode='[product_quiz id="<?php echo esc_attr($quiz->id); ?>"]'>
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </td>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($quiz->created_at))); ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz&id=' . $quiz->id); ?>">
                                            <?php _e('Edit', 'product-hunt'); ?>
                                        </a> | 
                                    </span>
                                    <span class="preview">
                                        <a href="<?php echo esc_url(add_query_arg(array('preview_quiz' => $quiz->id), home_url())); ?>" target="_blank">
                                            <?php _e('Preview', 'product-hunt'); ?>
                                        </a> | 
                                    </span>
                                    <span class="duplicate">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=product-hunt-quizzes&action=duplicate&id=' . $quiz->id), 'duplicate_quiz'); ?>">
                                            <?php _e('Duplicate', 'product-hunt'); ?>
                                        </a> | 
                                    </span>
                                    <span class="trash">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=product-hunt-quizzes&action=delete&id=' . $quiz->id), 'delete_quiz'); ?>" class="delete-quiz" data-quiz-title="<?php echo esc_attr($quiz->title); ?>">
                                            <?php _e('Delete', 'product-hunt'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
    .product-hunt-filter-bar {
        margin: 15px 0;
        padding: 15px;
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
    }
    .product-hunt-filter-bar select {
        margin-right: 8px;
        vertical-align: middle;
    }
    .product-hunt-filter-bar .button {
        vertical-align: middle;
    }
    .product-hunt-status {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
    }
    .product-hunt-status-published {
        background-color: #dff0d8;
        color: #3c763d;
    }
    .product-hunt-status-draft {
        background-color: #f5f5f5;
        color: #777;
    }
</style>

<script>
    // Copy shortcode functionality
    jQuery(document).ready(function($) {
        $('.copy-shortcode').on('click', function() {
            const shortcode = $(this).data('shortcode');
            const tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(shortcode).select();
            document.execCommand('copy');
            tempInput.remove();
            
            // Show a temporary notification
            const $button = $(this);
            const originalHtml = $button.html();
            $button.html('<span class="dashicons dashicons-yes"></span>');
            
            setTimeout(function() {
                $button.html(originalHtml);
            }, 1000);
        });
        
        // Confirm delete
        $('.delete-quiz').on('click', function(e) {
            const title = $(this).data('quiz-title');
            if (!confirm('Are you sure you want to delete the quiz "' + title + '"? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
</script>