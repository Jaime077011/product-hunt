<?php
/**
 * Admin template for displaying quiz analytics
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get date range filter
$date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30days';
$custom_start = isset($_GET['custom_start']) ? sanitize_text_field($_GET['custom_start']) : '';
$custom_end = isset($_GET['custom_end']) ? sanitize_text_field($_GET['custom_end']) : '';
$quiz_filter = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

// Calculate date ranges
$end_date = current_time('Y-m-d');
$start_date = '';

switch ($date_range) {
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        break;
    case 'custom':
        $start_date = !empty($custom_start) ? $custom_start : date('Y-m-d', strtotime('-30 days'));
        $end_date = !empty($custom_end) ? $custom_end : current_time('Y-m-d');
        break;
}

// Fetch quiz statistics from database
global $wpdb;

// Get all quizzes for filter dropdown
$quizzes = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}ph_quizzes ORDER BY title ASC");

// Query conditions based on filters
$where_conditions = [];
$where_conditions[] = "DATE(created_at) BETWEEN '$start_date' AND '$end_date'";

if ($quiz_filter > 0) {
    $where_conditions[] = "quiz_id = " . intval($quiz_filter);
}

$where_sql = implode(' AND ', $where_conditions);

// Completions over time
$completions_data = $wpdb->get_results("
    SELECT DATE(created_at) as date, COUNT(DISTINCT user_data_id) as completions 
    FROM {$wpdb->prefix}ph_user_responses 
    WHERE $where_sql
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
");

// Format data for chart
$dates = [];
$completion_counts = [];

foreach ($completions_data as $data) {
    $dates[] = date('M d', strtotime($data->date));
    $completion_counts[] = intval($data->completions);
}

// Get total completions in this period
$total_completions = array_sum($completion_counts);

// Get total starts in this period
$total_starts = $wpdb->get_var("
    SELECT COUNT(DISTINCT user_data_id) 
    FROM {$wpdb->prefix}ph_user_responses 
    WHERE $where_sql
");

// Calculate completion rate
$completion_rate = ($total_starts > 0) ? round(($total_completions / $total_starts) * 100, 2) : 0;

// Top performing quizzes
$top_quizzes = $wpdb->get_results("
    SELECT q.id, q.title, COUNT(DISTINCT ur.user_data_id) as completions,
    (SELECT COUNT(*) FROM {$wpdb->prefix}ph_questions WHERE quiz_id = q.id) as question_count
    FROM {$wpdb->prefix}ph_quizzes q
    JOIN {$wpdb->prefix}ph_user_responses ur ON q.id = ur.quiz_id
    WHERE $where_sql
    GROUP BY q.id
    ORDER BY completions DESC
    LIMIT 5
");

// Popular questions (most answered)
$popular_questions = $wpdb->get_results("
    SELECT q.id, q.question_text, qu.title as quiz_title, COUNT(DISTINCT ur.user_data_id) as responses
    FROM {$wpdb->prefix}ph_questions q
    JOIN {$wpdb->prefix}ph_quizzes qu ON q.quiz_id = qu.id
    JOIN {$wpdb->prefix}ph_user_responses ur ON q.id = ur.question_id
    WHERE $where_sql
    GROUP BY q.id
    ORDER BY responses DESC
    LIMIT 10
");

// Most recommended products
$top_products = $wpdb->get_results("
    SELECT p.ID, p.post_title, COUNT(ri.id) as recommendation_count,
    SUM(CASE WHEN ri.interaction_type = 'click' THEN 1 ELSE 0 END) as clicks
    FROM {$wpdb->prefix}ph_result_interactions ri
    JOIN {$wpdb->posts} p ON ri.product_id = p.ID
    WHERE p.post_type = 'product' AND $where_sql
    GROUP BY p.ID
    ORDER BY recommendation_count DESC
    LIMIT 10
");

// Average time spent on quizzes (if tracking is enabled)
$avg_completion_time = $wpdb->get_var("
    SELECT AVG(TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)))
    FROM {$wpdb->prefix}ph_user_responses
    WHERE $where_sql
    GROUP BY user_data_id
");

$avg_completion_minutes = $avg_completion_time ? round($avg_completion_time / 60, 1) : 0;

// Email capture stats
$total_emails = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->prefix}ph_user_data 
    WHERE email != '' AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'"
);

// Enqueue required scripts
wp_enqueue_script('product-hunt-chartjs');
wp_enqueue_style('product-hunt-admin-analytics', plugin_dir_url(dirname(__FILE__)) . 'css/product-hunt-admin-analytics.css', array(), '1.0.0');
?>

<div class="wrap product-hunt-admin-wrap">
    <div class="product-hunt-admin-header">
        <h1><?php _e('Quiz Analytics', 'product-hunt'); ?></h1>
    </div>
    
    <!-- Analytics Filters -->
    <div class="product-hunt-analytics-filters">
        <form method="get">
            <input type="hidden" name="page" value="product-hunt-analytics">
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="date-range"><?php _e('Date Range:', 'product-hunt'); ?></label>
                    <select id="date-range" name="date_range" class="date-range-select">
                        <option value="7days" <?php selected($date_range, '7days'); ?>><?php _e('Last 7 Days', 'product-hunt'); ?></option>
                        <option value="30days" <?php selected($date_range, '30days'); ?>><?php _e('Last 30 Days', 'product-hunt'); ?></option>
                        <option value="90days" <?php selected($date_range, '90days'); ?>><?php _e('Last 90 Days', 'product-hunt'); ?></option>
                        <option value="year" <?php selected($date_range, 'year'); ?>><?php _e('Last Year', 'product-hunt'); ?></option>
                        <option value="custom" <?php selected($date_range, 'custom'); ?>><?php _e('Custom Range', 'product-hunt'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group custom-date-inputs" style="<?php echo $date_range === 'custom' ? '' : 'display: none;'; ?>">
                    <label for="custom-start"><?php _e('From:', 'product-hunt'); ?></label>
                    <input type="date" id="custom-start" name="custom_start" value="<?php echo esc_attr($custom_start); ?>">
                    
                    <label for="custom-end"><?php _e('To:', 'product-hunt'); ?></label>
                    <input type="date" id="custom-end" name="custom_end" value="<?php echo esc_attr($custom_end); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="quiz-filter"><?php _e('Quiz:', 'product-hunt'); ?></label>
                    <select id="quiz-filter" name="quiz_id">
                        <option value="0"><?php _e('All Quizzes', 'product-hunt'); ?></option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option value="<?php echo $quiz->id; ?>" <?php selected($quiz_filter, $quiz->id); ?>>
                                <?php echo esc_html($quiz->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'product-hunt'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=product-hunt-analytics'); ?>" class="button"><?php _e('Reset', 'product-hunt'); ?></a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Summary Stats Cards -->
    <div class="product-hunt-summary-stats">
        <div class="stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-chart-line"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($total_completions); ?></h3>
                    <p><?php _e('Quiz Completions', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-performance"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($completion_rate); ?>%</h3>
                    <p><?php _e('Completion Rate', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-clock"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($avg_completion_minutes); ?> <?php _e('min', 'product-hunt'); ?></h3>
                    <p><?php _e('Avg. Completion Time', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-email"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($total_emails); ?></h3>
                    <p><?php _e('Emails Captured', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Analytics Sections -->
    <div class="product-hunt-analytics-grid">
        <!-- Completions Chart -->
        <div class="analytics-card analytics-card-full">
            <h2><?php _e('Quiz Completions Over Time', 'product-hunt'); ?></h2>
            <div class="analytics-chart-container">
                <canvas id="completionsChart"></canvas>
            </div>
        </div>
        
        <!-- Top Quizzes -->
        <div class="analytics-card">
            <h2><?php _e('Top Performing Quizzes', 'product-hunt'); ?></h2>
            <?php if (!empty($top_quizzes)): ?>
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th><?php _e('Quiz', 'product-hunt'); ?></th>
                            <th><?php _e('Completions', 'product-hunt'); ?></th>
                            <th><?php _e('Questions', 'product-hunt'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_quizzes as $quiz): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz&id=' . $quiz->id); ?>">
                                        <?php echo esc_html($quiz->title); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($quiz->completions); ?></td>
                                <td><?php echo esc_html($quiz->question_count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data"><?php _e('No quiz data available for the selected period.', 'product-hunt'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Top Products -->
        <div class="analytics-card">
            <h2><?php _e('Most Recommended Products', 'product-hunt'); ?></h2>
            <?php if (!empty($top_products)): ?>
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'product-hunt'); ?></th>
                            <th><?php _e('Recommendations', 'product-hunt'); ?></th>
                            <th><?php _e('Click Rate', 'product-hunt'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <?php 
                            $click_rate = $product->recommendation_count > 0 
                                ? round(($product->clicks / $product->recommendation_count) * 100, 1) 
                                : 0; 
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($product->ID); ?>" target="_blank">
                                        <?php echo esc_html($product->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($product->recommendation_count); ?></td>
                                <td><?php echo esc_html($click_rate); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data"><?php _e('No product recommendation data available.', 'product-hunt'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Popular Questions -->
        <div class="analytics-card analytics-card-full">
            <h2><?php _e('Most Answered Questions', 'product-hunt'); ?></h2>
            <?php if (!empty($popular_questions)): ?>
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th><?php _e('Question', 'product-hunt'); ?></th>
                            <th><?php _e('Quiz', 'product-hunt'); ?></th>
                            <th><?php _e('Responses', 'product-hunt'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_questions as $question): ?>
                            <tr>
                                <td><?php echo esc_html($question->question_text); ?></td>
                                <td><?php echo esc_html($question->quiz_title); ?></td>
                                <td><?php echo esc_html($question->responses); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data"><?php _e('No question response data available.', 'product-hunt'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Toggle custom date inputs
        $('#date-range').on('change', function() {
            if ($(this).val() === 'custom') {
                $('.custom-date-inputs').show();
            } else {
                $('.custom-date-inputs').hide();
            }
        });
        
        // Completions Chart
        if (document.getElementById('completionsChart')) {
            const completionsCtx = document.getElementById('completionsChart').getContext('2d');
            const completionsChart = new Chart(completionsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: '<?php _e('Quiz Completions', 'product-hunt'); ?>',
                        data: <?php echo json_encode($completion_counts); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        tension: 0.2,
                        pointBackgroundColor: 'rgba(52, 152, 219, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            titleColor: '#fff',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyColor: '#fff',
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#646970',
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#646970',
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    });
</script>
