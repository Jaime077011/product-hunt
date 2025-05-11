<?php
/**
 * Admin template for displaying the overview dashboard
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Fetch quiz statistics
global $wpdb;

// Total quizzes
$total_quizzes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ph_quizzes");

// Active quizzes
$active_quizzes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ph_quizzes WHERE status = 'published'");

// Total completions
$total_completions = $wpdb->get_var("SELECT SUM(completions) FROM {$wpdb->prefix}ph_quiz_performance");
$total_completions = $total_completions ? $total_completions : 0;

// Popular quizzes (top 5)
$popular_quizzes = $wpdb->get_results(
    "SELECT q.id, q.title, COALESCE(qp.completions, 0) as completions 
     FROM {$wpdb->prefix}ph_quizzes q
     LEFT JOIN {$wpdb->prefix}ph_quiz_performance qp ON q.id = qp.quiz_id
     ORDER BY qp.completions DESC
     LIMIT 5"
);

// Top recommended products
$top_products = $wpdb->get_results(
    "SELECT p.ID, p.post_title, COUNT(ri.id) as recommendation_count, 
     SUM(CASE WHEN ri.interaction_type = 'click' THEN 1 ELSE 0 END) as click_count
     FROM {$wpdb->prefix}ph_result_interactions ri
     JOIN {$wpdb->posts} p ON ri.product_id = p.ID
     WHERE p.post_type = 'product'
     GROUP BY p.ID
     ORDER BY recommendation_count DESC
     LIMIT 8"
);

// Quiz completions over time (last 30 days)
$days = 30;
$completions_data = array();
$labels = array();

for ($i = $days - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime("-$i days"));
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM {$wpdb->prefix}ph_user_responses 
         WHERE DATE(created_at) = %s
         GROUP BY quiz_id", 
        $date
    ));
    
    $completions_data[] = $count ? intval($count) : 0;
}

// Total questions across all quizzes
$total_questions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ph_questions");

// Total users who completed quizzes
$total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_data_id) FROM {$wpdb->prefix}ph_user_responses");

// Add required scripts
wp_enqueue_style('dashicons');
wp_enqueue_script('jquery');
wp_enqueue_script('product-hunt-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js', array('jquery'), '3.7.0', true);
?>

<div class="wrap product-hunt-admin-wrap product-hunt-dashboard">
    <div class="product-hunt-admin-header">
        <h1><?php _e('Quiz Dashboard', 'product-hunt'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz'); ?>" class="page-title-action">
            <?php _e('Add New Quiz', 'product-hunt'); ?>
        </a>
    </div>

    <div class="product-hunt-welcome-panel">
        <div class="welcome-panel-content">
            <h2><?php _e('Welcome to Product Hunt Quizzes', 'product-hunt'); ?></h2>
            <p class="about-description">
                <?php _e('Create engaging quizzes that recommend products to your customers based on their answers.', 'product-hunt'); ?>
            </p>
            <div class="welcome-panel-column-container">
                <div class="welcome-panel-column">
                    <h3><?php _e('Get Started', 'product-hunt'); ?></h3>
                    <a class="button button-primary" href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz'); ?>">
                        <?php _e('Create a Quiz', 'product-hunt'); ?>
                    </a>
                </div>
                <div class="welcome-panel-column">
                    <h3><?php _e('Next Steps', 'product-hunt'); ?></h3>
                    <ul>
                        <li><?php _e('Add questions and answers', 'product-hunt'); ?></li>
                        <li><?php _e('Map answers to product recommendations', 'product-hunt'); ?></li>
                        <li><?php _e('Customize the quiz appearance', 'product-hunt'); ?></li>
                    </ul>
                </div>
                <div class="welcome-panel-column welcome-panel-last">
                    <h3><?php _e('More Actions', 'product-hunt'); ?></h3>
                    <ul>
                        <li><a href="<?php echo admin_url('admin.php?page=product-hunt-quizzes'); ?>"><?php _e('Manage Quizzes', 'product-hunt'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=product-hunt-analytics'); ?>"><?php _e('View Detailed Analytics', 'product-hunt'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="product-hunt-stats-cards">
        <div class="product-hunt-stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-clipboard"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($total_quizzes); ?></h3>
                    <p><?php _e('Total Quizzes', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="product-hunt-stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-yes-alt"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($active_quizzes); ?></h3>
                    <p><?php _e('Active Quizzes', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="product-hunt-stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-chart-bar"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($total_completions); ?></h3>
                    <p><?php _e('Total Completions', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="product-hunt-stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-editor-help"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($total_questions); ?></h3>
                    <p><?php _e('Questions Created', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="product-hunt-stat-card">
            <div class="stat-card-inner">
                <span class="dashicons dashicons-groups"></span>
                <div class="stat-card-content">
                    <h3><?php echo esc_html($total_users); ?></h3>
                    <p><?php _e('Total Participants', 'product-hunt'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Sections -->
    <div class="product-hunt-analytics-row">
        <!-- Completions Chart -->
        <div class="product-hunt-analytics-card product-hunt-chart-container">
            <h2><?php _e('Quiz Completions (Last 30 Days)', 'product-hunt'); ?></h2>
            <canvas id="completionsChart"></canvas>
        </div>
        
        <!-- Popular Quizzes Table -->
        <div class="product-hunt-analytics-card">
            <h2><?php _e('Most Popular Quizzes', 'product-hunt'); ?></h2>
            <?php if (!empty($popular_quizzes)): ?>
                <table class="product-hunt-table">
                    <thead>
                        <tr>
                            <th><?php _e('Quiz', 'product-hunt'); ?></th>
                            <th><?php _e('Completions', 'product-hunt'); ?></th>
                            <th><?php _e('Actions', 'product-hunt'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_quizzes as $quiz): ?>
                            <tr>
                                <td><?php echo esc_html($quiz->title); ?></td>
                                <td><?php echo esc_html($quiz->completions); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz&id=' . $quiz->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'product-hunt'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No quiz data available yet.', 'product-hunt'); ?></p>
            <?php endif; ?>
            
            <p class="view-all-link">
                <a href="<?php echo admin_url('admin.php?page=product-hunt-quizzes'); ?>"><?php _e('View All Quizzes →', 'product-hunt'); ?></a>
            </p>
        </div>
    </div>

    <div class="product-hunt-analytics-row">
        <!-- Top Products -->
        <div class="product-hunt-analytics-card">
            <h2><?php _e('Top Recommended Products', 'product-hunt'); ?></h2>
            <?php if (!empty($top_products)): ?>
                <table class="product-hunt-table">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'product-hunt'); ?></th>
                            <th><?php _e('Recommendations', 'product-hunt'); ?></th>
                            <th><?php _e('Clicks', 'product-hunt'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo esc_html($product->post_title); ?></td>
                                <td><?php echo esc_html($product->recommendation_count); ?></td>
                                <td><?php echo esc_html($product->click_count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No product recommendation data available yet.', 'product-hunt'); ?></p>
            <?php endif; ?>
            
            <p class="view-all-link">
                <a href="<?php echo admin_url('admin.php?page=product-hunt-analytics'); ?>"><?php _e('View Detailed Analytics →', 'product-hunt'); ?></a>
            </p>
        </div>
        
        <!-- Quick Links -->
        <div class="product-hunt-analytics-card product-hunt-quick-links">
            <h2><?php _e('Quick Links', 'product-hunt'); ?></h2>
            <div class="quick-links-container">
                <a href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz'); ?>" class="quick-link-card">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span><?php _e('New Quiz', 'product-hunt'); ?></span>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=product-hunt-quizzes'); ?>" class="quick-link-card">
                    <span class="dashicons dashicons-list-view"></span>
                    <span><?php _e('All Quizzes', 'product-hunt'); ?></span>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=product-hunt-analytics'); ?>" class="quick-link-card">
                    <span class="dashicons dashicons-chart-line"></span>
                    <span><?php _e('Analytics', 'product-hunt'); ?></span>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=product-hunt-settings'); ?>" class="quick-link-card">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span><?php _e('Settings', 'product-hunt'); ?></span>
                </a>
            </div>
            
            <h3><?php _e('Helpful Resources', 'product-hunt'); ?></h3>
            <ul class="product-hunt-resources-list">
                <li><a href="#" target="_blank"><?php _e('Documentation', 'product-hunt'); ?></a></li>
                <li><a href="#" target="_blank"><?php _e('Best Practices Guide', 'product-hunt'); ?></a></li>
                <li><a href="#" target="_blank"><?php _e('Support', 'product-hunt'); ?></a></li>
            </ul>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Completions Chart
        if(document.getElementById('completionsChart')) {
            const completionsCtx = document.getElementById('completionsChart').getContext('2d');
            const completionsChart = new Chart(completionsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: '<?php _e('Quiz Completions', 'product-hunt'); ?>',
                        data: <?php echo json_encode($completions_data); ?>,
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
