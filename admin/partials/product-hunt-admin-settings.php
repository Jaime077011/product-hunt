<?php
/**
 * Admin template for plugin settings
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get saved settings
$settings = get_option('product_hunt_settings', array());

// Default values
$default_primary_color = isset($settings['default_primary_color']) ? $settings['default_primary_color'] : '#3498db';
$default_secondary_color = isset($settings['default_secondary_color']) ? $settings['default_secondary_color'] : '#2ecc71';
$default_button_style = isset($settings['default_button_style']) ? $settings['default_button_style'] : 'rounded';
$default_font_family = isset($settings['default_font_family']) ? $settings['default_font_family'] : '';

// Email integration
$mailchimp_api_key = isset($settings['mailchimp_api_key']) ? $settings['mailchimp_api_key'] : '';
$mailchimp_list_id = isset($settings['mailchimp_list_id']) ? $settings['mailchimp_list_id'] : '';
$email_integration = isset($settings['email_integration']) ? $settings['email_integration'] : 'none';

// Google Analytics
$enable_ga_tracking = isset($settings['enable_ga_tracking']) ? (bool)$settings['enable_ga_tracking'] : false;
$ga_event_category = isset($settings['ga_event_category']) ? $settings['ga_event_category'] : 'Product Quiz';

// GDPR settings
$enable_gdpr = isset($settings['enable_gdpr']) ? (bool)$settings['enable_gdpr'] : true;
$gdpr_message = isset($settings['gdpr_message']) ? $settings['gdpr_message'] : 'I agree to receive personalized product recommendations and marketing emails.';
$privacy_policy_page = isset($settings['privacy_policy_page']) ? $settings['privacy_policy_page'] : 0;

// Performance settings
$cache_results = isset($settings['cache_results']) ? (bool)$settings['cache_results'] : true;
$cache_duration = isset($settings['cache_duration']) ? intval($settings['cache_duration']) : 24;

// User permissions
$editor_access = isset($settings['editor_access']) ? (bool)$settings['editor_access'] : false;

// Get saved message
$settings_updated = isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true';
?>

<div class="wrap product-hunt-admin-wrap">
    <div class="product-hunt-admin-header">
        <h1><?php _e('Quiz Settings', 'product-hunt'); ?></h1>
    </div>
    
    <?php if ($settings_updated): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully!', 'product-hunt'); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php" class="product-hunt-settings-form">
        <?php settings_fields('product_hunt_settings_group'); ?>
        
        <div class="product-hunt-settings-container">
            <div class="product-hunt-settings-sidebar">
                <div class="product-hunt-settings-nav">
                    <ul>
                        <li><a href="#general-settings" class="active"><?php _e('General Settings', 'product-hunt'); ?></a></li>
                        <li><a href="#style-defaults"><?php _e('Style Defaults', 'product-hunt'); ?></a></li>
                        <li><a href="#email-integration"><?php _e('Email Integration', 'product-hunt'); ?></a></li>
                        <li><a href="#tracking-analytics"><?php _e('Tracking & Analytics', 'product-hunt'); ?></a></li>
                        <li><a href="#gdpr-privacy"><?php _e('GDPR & Privacy', 'product-hunt'); ?></a></li>
                        <li><a href="#performance"><?php _e('Performance', 'product-hunt'); ?></a></li>
                        <li><a href="#permissions"><?php _e('Permissions', 'product-hunt'); ?></a></li>
                    </ul>
                </div>
            </div>
            
            <div class="product-hunt-settings-content">
                <!-- General Settings -->
                <div id="general-settings" class="settings-section active">
                    <h2><?php _e('General Settings', 'product-hunt'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e('Plugin Version', 'product-hunt'); ?>
                            </th>
                            <td>
                                <p class="description"><?php echo PRODUCT_HUNT_VERSION; ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e('Documentation', 'product-hunt'); ?>
                            </th>
                            <td>
                                <a href="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'docs/README.md'); ?>" target="_blank" class="button">
                                    <?php _e('View Documentation', 'product-hunt'); ?>
                                </a>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Style Defaults -->
                <div id="style-defaults" class="settings-section">
                    <h2><?php _e('Style Defaults', 'product-hunt'); ?></h2>
                    <p><?php _e('Set default styling options for new quizzes. These settings can be overridden for individual quizzes.', 'product-hunt'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="default-primary-color"><?php _e('Default Primary Color', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="default-primary-color" name="product_hunt_settings[default_primary_color]" value="<?php echo esc_attr($default_primary_color); ?>" class="color-picker">
                                <p class="description"><?php _e('The main color for buttons and accents.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="default-secondary-color"><?php _e('Default Secondary Color', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="default-secondary-color" name="product_hunt_settings[default_secondary_color]" value="<?php echo esc_attr($default_secondary_color); ?>" class="color-picker">
                                <p class="description"><?php _e('Used for highlights and secondary elements.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="default-button-style"><?php _e('Default Button Style', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <select id="default-button-style" name="product_hunt_settings[default_button_style]">
                                    <option value="rounded" <?php selected($default_button_style, 'rounded'); ?>><?php _e('Rounded', 'product-hunt'); ?></option>
                                    <option value="square" <?php selected($default_button_style, 'square'); ?>><?php _e('Square', 'product-hunt'); ?></option>
                                    <option value="rounded-corners" <?php selected($default_button_style, 'rounded-corners'); ?>><?php _e('Rounded Corners', 'product-hunt'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="default-font-family"><?php _e('Default Font Family', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <select id="default-font-family" name="product_hunt_settings[default_font_family]">
                                    <option value="" <?php selected($default_font_family, ''); ?>><?php _e('Theme Default', 'product-hunt'); ?></option>
                                    <option value="Arial, sans-serif" <?php selected($default_font_family, 'Arial, sans-serif'); ?>>Arial</option>
                                    <option value="'Helvetica Neue', Helvetica, sans-serif" <?php selected($default_font_family, "'Helvetica Neue', Helvetica, sans-serif"); ?>>Helvetica</option>
                                    <option value="Georgia, serif" <?php selected($default_font_family, 'Georgia, serif'); ?>>Georgia</option>
                                    <option value="'Times New Roman', serif" <?php selected($default_font_family, "'Times New Roman', serif"); ?>>Times New Roman</option>
                                    <option value="Verdana, sans-serif" <?php selected($default_font_family, 'Verdana, sans-serif'); ?>>Verdana</option>
                                    <option value="Tahoma, sans-serif" <?php selected($default_font_family, 'Tahoma, sans-serif'); ?>>Tahoma</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Email Integration -->
                <div id="email-integration" class="settings-section">
                    <h2><?php _e('Email Integration', 'product-hunt'); ?></h2>
                    <p><?php _e('Configure email marketing integration to automatically add quiz participants to your mailing lists.', 'product-hunt'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="email-integration"><?php _e('Email Service', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <select id="email-integration" name="product_hunt_settings[email_integration]">
                                    <option value="none" <?php selected($email_integration, 'none'); ?>><?php _e('None', 'product-hunt'); ?></option>
                                    <option value="mailchimp" <?php selected($email_integration, 'mailchimp'); ?>>Mailchimp</option>
                                    <option value="custom" <?php selected($email_integration, 'custom'); ?>><?php _e('Custom (via Webhooks)', 'product-hunt'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr class="mailchimp-setting <?php echo $email_integration !== 'mailchimp' ? 'hidden' : ''; ?>">
                            <th scope="row">
                                <label for="mailchimp-api-key"><?php _e('Mailchimp API Key', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="mailchimp-api-key" name="product_hunt_settings[mailchimp_api_key]" value="<?php echo esc_attr($mailchimp_api_key); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Enter your Mailchimp API key.', 'product-hunt'); ?>
                                    <a href="https://mailchimp.com/help/about-api-keys/" target="_blank"><?php _e('How to get your API key', 'product-hunt'); ?></a>
                                </p>
                            </td>
                        </tr>
                        
                        <tr class="mailchimp-setting <?php echo $email_integration !== 'mailchimp' ? 'hidden' : ''; ?>">
                            <th scope="row">
                                <label for="mailchimp-list-id"><?php _e('Mailchimp List/Audience ID', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="mailchimp-list-id" name="product_hunt_settings[mailchimp_list_id]" value="<?php echo esc_attr($mailchimp_list_id); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Enter your Mailchimp List/Audience ID.', 'product-hunt'); ?>
                                </p>
                                <button type="button" id="test-mailchimp" class="button"><?php _e('Test Connection', 'product-hunt'); ?></button>
                                <span id="mailchimp-test-result"></span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Tracking & Analytics -->
                <div id="tracking-analytics" class="settings-section">
                    <h2><?php _e('Tracking & Analytics', 'product-hunt'); ?></h2>
                    <p><?php _e('Configure tracking options and external analytics integration.', 'product-hunt'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="enable-ga-tracking"><?php _e('Google Analytics Events', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="enable-ga-tracking" name="product_hunt_settings[enable_ga_tracking]" value="1" <?php checked($enable_ga_tracking); ?>>
                                <label for="enable-ga-tracking"><?php _e('Enable Google Analytics event tracking', 'product-hunt'); ?></label>
                                <p class="description"><?php _e('Track quiz interactions as Events in Google Analytics.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                        <tr class="ga-setting <?php echo !$enable_ga_tracking ? 'hidden' : ''; ?>">
                            <th scope="row">
                                <label for="ga-event-category"><?php _e('Event Category', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="ga-event-category" name="product_hunt_settings[ga_event_category]" value="<?php echo esc_attr($ga_event_category); ?>" class="regular-text">
                                <p class="description"><?php _e('The event category used for Google Analytics events.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- GDPR & Privacy -->
                <div id="gdpr-privacy" class="settings-section">
                    <h2><?php _e('GDPR & Privacy', 'product-hunt'); ?></h2>
                    <p><?php _e('Configure GDPR compliance and privacy settings.', 'product-hunt'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="enable-gdpr"><?php _e('GDPR Compliance', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="enable-gdpr" name="product_hunt_settings[enable_gdpr]" value="1" <?php checked($enable_gdpr); ?>>
                                <label for="enable-gdpr"><?php _e('Enable GDPR compliance features', 'product-hunt'); ?></label>
                            </td>
                        </tr>
                        <tr class="gdpr-setting <?php echo !$enable_gdpr ? 'hidden' : ''; ?>">
                            <th scope="row">
                                <label for="gdpr-message"><?php _e('Consent Message', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <textarea id="gdpr-message" name="product_hunt_settings[gdpr_message]" rows="2" class="large-text"><?php echo esc_textarea($gdpr_message); ?></textarea>
                                <p class="description"><?php _e('This message will be shown with a checkbox for users to consent.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                        <tr class="gdpr-setting <?php echo !$enable_gdpr ? 'hidden' : ''; ?>">
                            <th scope="row">
                                <label for="privacy-policy-page"><?php _e('Privacy Policy Page', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <select id="privacy-policy-page" name="product_hunt_settings[privacy_policy_page]">
                                    <option value="0"><?php _e('-- Select a page --', 'product-hunt'); ?></option>
                                    <?php
                                    $pages = get_pages();
                                    foreach ($pages as $page) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($page->ID),
                                            selected($privacy_policy_page, $page->ID, false),
                                            esc_html($page->post_title)
                                        );
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Select your privacy policy page.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Performance -->
                <div id="performance" class="settings-section">
                    <h2><?php _e('Performance', 'product-hunt'); ?></h2>
                    <p><?php _e('Configure caching and performance settings.', 'product-hunt'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="cache-results"><?php _e('Cache Results', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="cache-results" name="product_hunt_settings[cache_results]" value="1" <?php checked($cache_results); ?>>
                                <label for="cache-results"><?php _e('Enable caching of quiz results', 'product-hunt'); ?></label>
                                <p class="description"><?php _e('Improve performance by caching quiz results.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                        <tr class="cache-setting <?php echo !$cache_results ? 'hidden' : ''; ?>">
                            <th scope="row">
                                <label for="cache-duration"><?php _e('Cache Duration', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="cache-duration" name="product_hunt_settings[cache_duration]" value="<?php echo esc_attr($cache_duration); ?>" min="1" max="168" class="small-text">
                                <span><?php _e('hours', 'product-hunt'); ?></span>
                                <p class="description"><?php _e('How long to cache quiz results.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Permissions -->
                <div id="permissions" class="settings-section">
                    <h2><?php _e('Permissions', 'product-hunt'); ?></h2>
                    <p><?php _e('Configure user role permissions.', 'product-hunt'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="editor-access"><?php _e('Editor Access', 'product-hunt'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="editor-access" name="product_hunt_settings[editor_access]" value="1" <?php checked($editor_access); ?>>
                                <label for="editor-access"><?php _e('Allow Editors to create and manage quizzes', 'product-hunt'); ?></label>
                                <p class="description"><?php _e('By default, only Administrators can manage quizzes.', 'product-hunt'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="product-hunt-settings-footer">
            <?php submit_button(__('Save Settings', 'product-hunt'), 'primary', 'submit', false); ?>
        </div>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Initialize color pickers
        $('.color-picker').wpColorPicker();
        
        // Settings navigation
        $('.product-hunt-settings-nav a').on('click', function(e) {
            e.preventDefault();
            
            // Highlight active nav item
            $('.product-hunt-settings-nav a').removeClass('active');
            $(this).addClass('active');
            
            // Show the target section
            const targetSection = $(this).attr('href');
            $('.settings-section').removeClass('active');
            $(targetSection).addClass('active');
        });
        
        // Toggle conditional fields
        $('#email-integration').on('change', function() {
            if ($(this).val() === 'mailchimp') {
                $('.mailchimp-setting').removeClass('hidden');
            } else {
                $('.mailchimp-setting').addClass('hidden');
            }
        });
        
        $('#enable-ga-tracking').on('change', function() {
            if ($(this).is(':checked')) {
                $('.ga-setting').removeClass('hidden');
            } else {
                $('.ga-setting').addClass('hidden');
            }
        });
        
        $('#enable-gdpr').on('change', function() {
            if ($(this).is(':checked')) {
                $('.gdpr-setting').removeClass('hidden');
            } else {
                $('.gdpr-setting').addClass('hidden');
            }
        });
        
        $('#cache-results').on('change', function() {
            if ($(this).is(':checked')) {
                $('.cache-setting').removeClass('hidden');
            } else {
                $('.cache-setting').addClass('hidden');
            }
        });
        
        // Test Mailchimp connection
        $('#test-mailchimp').on('click', function() {
            const apiKey = $('#mailchimp-api-key').val();
            const listId = $('#mailchimp-list-id').val();
            
            if (!apiKey || !listId) {
                $('#mailchimp-test-result').html('<span class="test-error">Please enter both API key and List ID</span>');
                return;
            }
            
            $(this).prop('disabled', true);
            $('#mailchimp-test-result').html('<span class="test-loading">Testing connection...</span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'product_hunt_test_mailchimp',
                    security: product_hunt_admin.nonce,
                    api_key: apiKey,
                    list_id: listId
                },
                success: function(response) {
                    if (response.success) {
                        $('#mailchimp-test-result').html('<span class="test-success">Connection successful!</span>');
                    } else {
                        $('#mailchimp-test-result').html('<span class="test-error">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $('#mailchimp-test-result').html('<span class="test-error">Request failed. Please try again.</span>');
                },
                complete: function() {
                    $('#test-mailchimp').prop('disabled', false);
                }
            });
        });
    });
</script>
