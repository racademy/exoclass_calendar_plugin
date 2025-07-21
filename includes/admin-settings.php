<?php
/**
 * Admin Settings for ExoClass Calendar Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ExoClassCalendarAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'ExoClass Calendar Settings',
            'ExoClass Calendar',
            'manage_options',
            'exoclass-calendar-settings',
            array($this, 'settings_page')
        );
    }
    
    public function init_settings() {
        register_setting('exoclass_calendar_settings', 'exoclass_calendar_options');
        
        add_settings_section(
            'exoclass_calendar_api_section',
            'API Configuration',
            array($this, 'api_section_callback'),
            'exoclass-calendar-settings'
        );
        
        add_settings_field(
            'api_base_url',
            'API Base URL',
            array($this, 'api_base_url_callback'),
            'exoclass-calendar-settings',
            'exoclass_calendar_api_section'
        );
        
        add_settings_field(
            'provider_key',
            'Provider Key',
            array($this, 'provider_key_callback'),
            'exoclass-calendar-settings',
            'exoclass_calendar_api_section'
        );
        
        add_settings_section(
            'exoclass_calendar_display_section',
            'Display Settings',
            array($this, 'display_section_callback'),
            'exoclass-calendar-settings'
        );
        
        add_settings_field(
            'default_height',
            'Default Calendar Height',
            array($this, 'default_height_callback'),
            'exoclass-calendar-settings',
            'exoclass_calendar_display_section'
        );
        

    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('exoclass_calendar_settings');
                do_settings_sections('exoclass-calendar-settings');
                submit_button();
                ?>
            </form>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>Shortcode Usage</h2>
                <p>Use the following shortcode to display the calendar in your posts or pages:</p>
                <code>[exoclass_calendar]</code>
                
                <h3>Advanced Usage</h3>
                <p>You can customize the calendar with parameters:</p>
                <code>[exoclass_calendar height="600px"]</code>
                <br><code>[exoclass_calendar show_images="false"]</code>
                
                <h3>Available Parameters</h3>
                <ul>
                    <li><strong>height</strong>: Set the calendar height (default: "auto")</li>
                    <li><strong>show_images</strong>: Enable/disable event images (default: "true")</li>
                </ul>
            </div>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>Test API Connection</h2>
                <p>Click the button below to test the API connection:</p>
                <button type="button" id="test-api-connection" class="button button-secondary">Test API Connection</button>
                <div id="api-test-result" style="margin-top: 10px;"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-api-connection').on('click', function() {
                var button = $(this);
                var resultDiv = $('#api-test-result');
                
                button.prop('disabled', true).text('Testing...');
                resultDiv.html('<p>Testing API connection...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'exoclass_test_api',
                        nonce: '<?php echo wp_create_nonce('exoclass_test_api_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<p style="color: green;">✅ API connection successful! Found ' + response.data.count + ' events.</p>');
                        } else {
                            resultDiv.html('<p style="color: red;">❌ API connection failed: ' + response.data.message + '</p>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<p style="color: red;">❌ AJAX request failed. Please check your settings.</p>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test API Connection');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function api_section_callback() {
        echo '<p>Configure the ExoClass API settings below:</p>';
    }
    
    public function api_base_url_callback() {
        $options = get_option('exoclass_calendar_options', array());
        $value = isset($options['api_base_url']) ? $options['api_base_url'] : 'https://test.api.exoclass.com/api/v1/en';
        ?>
        <input type="url" name="exoclass_calendar_options[api_base_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description">The base URL for the ExoClass API (e.g., https://test.api.exoclass.com/api/v1/en)</p>
        <?php
    }
    
    public function provider_key_callback() {
        $options = get_option('exoclass_calendar_options', array());
        $value = isset($options['provider_key']) ? $options['provider_key'] : 'af6791ea-6262-4705-a78c-b7fdc52aec6a';
        ?>
        <input type="text" name="exoclass_calendar_options[provider_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description">Your ExoClass provider key</p>
        <?php
    }
    
    public function display_section_callback() {
        echo '<p>Configure the default display settings for the calendar:</p>';
    }
    
    public function default_height_callback() {
        $options = get_option('exoclass_calendar_options', array());
        $value = isset($options['default_height']) ? $options['default_height'] : 'auto';
        ?>
        <input type="text" name="exoclass_calendar_options[default_height]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description">Default height for the calendar (e.g., "auto", "600px", "100vh")</p>
        <?php
    }
    

    
    public static function get_api_config() {
        $options = get_option('exoclass_calendar_options', array());
        
        return array(
            'base_url' => isset($options['api_base_url']) ? $options['api_base_url'] : 'https://test.api.exoclass.com/api/v1/en',
            'provider_key' => isset($options['provider_key']) ? $options['provider_key'] : 'af6791ea-6262-4705-a78c-b7fdc52aec6a'
        );
    }
    
    public static function get_display_config() {
        $options = get_option('exoclass_calendar_options', array());
        
        return array(
            'default_height' => isset($options['default_height']) ? $options['default_height'] : 'auto'
        );
    }
}

// Initialize admin settings
new ExoClassCalendarAdmin();

// AJAX handler for API testing
add_action('wp_ajax_exoclass_test_api', 'exoclass_test_api_callback');
function exoclass_test_api_callback() {
    check_ajax_referer('exoclass_test_api_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $api_config = ExoClassCalendarAdmin::get_api_config();
    
    try {
        $url = $api_config['base_url'] . '/groups?provider_key=' . $api_config['provider_key'] . '&page=1&limit=5';
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to connect to API: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        $count = isset($data['data']) && is_array($data['data']) ? count($data['data']) : 0;
        
        wp_send_json_success(array(
            'message' => 'API connection successful',
            'count' => $count
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
} 