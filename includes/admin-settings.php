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
        
        add_settings_field(
            'color',
            'Accent Color',
            array($this, 'color_callback'),
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
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Shortcode Usage</h2>
                <p>Use the following shortcode to display the calendar in your posts or pages:</p>
                <code>[exoclass_calendar]</code>
                
                <h3>Basic Customization</h3>
                <p>You can customize the calendar appearance with these parameters:</p>
                <code>[exoclass_calendar height="600px"]</code>
                <br><code>[exoclass_calendar show_images="false"]</code>
                
                <h3>Filtering Options</h3>
                <p>Pre-filter the calendar to show specific content using these filter parameters:</p>
                
                <div style="margin: 15px 0;">
                    <h4>Filter by Location</h4>
                    <code>[exoclass_calendar filter_location="123"]</code>
                    <p style="margin: 5px 0; color: #666; font-size: 13px;">Shows only classes at the specified location. Use the location ID from your ExoClass system.</p>
                </div>
                
                <div style="margin: 15px 0;">
                    <h4>Filter by Activity Type</h4>
                    <code>[exoclass_calendar filter_activity="456"]</code>
                    <p style="margin: 5px 0; color: #666; font-size: 13px;">Shows only classes of a specific activity type (e.g., yoga, dance, fitness). Use the activity ID from your ExoClass system.</p>
                </div>
                
                <div style="margin: 15px 0;">
                    <h4>Filter by Teacher</h4>
                    <code>[exoclass_calendar filter_teacher="789"]</code>
                    <p style="margin: 5px 0; color: #666; font-size: 13px;">Shows only classes taught by a specific instructor. Use the teacher ID from your ExoClass system.</p>
                </div>
                
                <div style="margin: 15px 0;">
                    <h4>Filter by Difficulty Level</h4>
                    <code>[exoclass_calendar filter_level="234"]</code>
                    <p style="margin: 5px 0; color: #666; font-size: 13px;">Shows only classes of a specific difficulty level. Use the difficulty level ID from your ExoClass system.</p>
                </div>
                
                <div style="margin: 15px 0;">
                    <h4>Filter by Age Group</h4>
                    <code>[exoclass_calendar filter_age="1,2,3"]</code>
                    <p style="margin: 5px 0; color: #666; font-size: 13px;">Shows only classes for specific age groups. Use age group IDs: -1 to 18. Supports multiple values separated by commas.</p>
                </div>
                
                <div style="margin: 15px 0;">
                    <h4>Filter by Class</h4>
                    <code>[exoclass_calendar filter_class="1,2,3"]</code>
                    <p style="margin: 5px 0; color: #666; font-size: 13px;">Shows only classes for specific school classes. Use class IDs: -1 to 12. Supports multiple values separated by commas.</p>
                </div>
                
                <h3>Combining Filters</h3>
                <p>You can combine multiple filters for more specific results:</p>
                <code>[exoclass_calendar filter_activity="456,789" filter_age="1,2,3" height="500px"]</code>
                <br><code>[exoclass_calendar filter_location="123" filter_teacher="789" filter_level="234" filter_age="1,2" filter_class="1,2,3"]</code>
                
                <h3>Complete Parameter Reference</h3>
                <ul>
                    <li><strong>height</strong>: Set the calendar height (default: "auto")</li>
                    <li><strong>show_images</strong>: Enable/disable event images (default: "true")</li>
                    <li><strong>filter_location</strong>: Pre-filter by location ID(s) - supports comma-separated values</li>
                    <li><strong>filter_activity</strong>: Pre-filter by activity type ID(s) - supports comma-separated values</li>
                    <li><strong>filter_teacher</strong>: Pre-filter by teacher/instructor ID(s) - supports comma-separated values</li>
                    <li><strong>filter_level</strong>: Pre-filter by difficulty level ID(s) - supports comma-separated values</li>
                    <li><strong>filter_age</strong>: Pre-filter by age group ID(s) (-1 to 18) - supports comma-separated values</li>
                    <li><strong>filter_class</strong>: Pre-filter by class ID(s) (-1 to 12) - supports comma-separated values</li>
                </ul>
                
                <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">
                    <h4 style="margin-top: 0;">💡 How to Find Filter IDs</h4>
                    <p style="margin-bottom: 0;">To find the correct IDs for locations, activities, teachers, and levels:</p>
                    <ol style="margin: 10px 0 0 20px;">
                        <li>Use the "Test API Connection" button below to verify your API settings</li>
                        <li>Check your ExoClass admin panel for the specific IDs</li>
                        <li>Contact your ExoClass provider for assistance with ID mapping</li>
                        <li>Use browser developer tools to inspect the calendar's filter dropdowns for ID values</li>
                    </ol>
                </div>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Test API Connection</h2>
                <p>Click the button below to test the API connection:</p>
                <button type="button" id="test-api-connection" class="button button-secondary">Test API Connection</button>
                <div id="api-test-result" style="margin-top: 10px;"></div>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Update Cache Management</h2>
                <p>Clear the plugin update cache to force a fresh check for updates:</p>
                <button type="button" id="clear-update-cache" class="button button-secondary">Clear Update Cache</button>
                <div id="cache-clear-result" style="margin-top: 10px;"></div>
                <p style="margin-top: 10px; color: #666; font-size: 13px;">
                    Note: This will clear both the GitHub release cache and WordPress update transients. 
                    Use this if you see a persistent update notification after updating the plugin.
                </p>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Filter ID Finder</h2>
                <p>Find the correct IDs for your filter parameters:</p>
                
                <div style="margin: 15px 0;">
                    <button type="button" id="get-filter-ids" class="button button-primary">Load Available Filter IDs</button>
                    <div id="filter-ids-result" style="margin-top: 15px;"></div>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">
                    <h4 style="margin-top: 0;">📋 How to Use</h4>
                    <p style="margin-bottom: 0;">Click "Load Available Filter IDs" to fetch and display all available locations, activities, teachers, and difficulty levels with their corresponding IDs. Copy the ID numbers to use in your shortcode filters.</p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Clear update cache button
            $('#clear-update-cache').on('click', function() {
                var button = $(this);
                var resultDiv = $('#cache-clear-result');
                
                button.prop('disabled', true).text('Clearing...');
                resultDiv.html('<p>Clearing update cache...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'exoclass_clear_update_cache',
                        nonce: '<?php echo wp_create_nonce('exoclass_clear_cache_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<p style="color: green;">✅ Update cache cleared successfully!</p>');
                        } else {
                            resultDiv.html('<p style="color: red;">❌ Failed to clear cache: ' + (response.data.message || 'Unknown error') + '</p>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<p style="color: red;">❌ An error occurred while clearing the cache.</p>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Clear Update Cache');
                    }
                });
            });
            
            // Test API connection button
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
            
            $('#get-filter-ids').on('click', function() {
                var button = $(this);
                var resultDiv = $('#filter-ids-result');
                
                button.prop('disabled', true).text('Loading...');
                resultDiv.html('<p>Fetching available filter IDs...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'exoclass_get_filter_ids',
                        nonce: '<?php echo wp_create_nonce('exoclass_filter_ids_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">';
                            
                            // Locations
                            if (response.data.locations && response.data.locations.length > 0) {
                                html += '<h4 style="color: #0073aa; margin-top: 0;">📍 Locations</h4>';
                                html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; margin-bottom: 20px;">';
                                response.data.locations.forEach(function(item) {
                                    html += '<div style="background: white; padding: 8px 12px; border-radius: 3px; border-left: 3px solid #0073aa;">';
                                    html += '<strong>' + item.name + '</strong><br>';
                                    html += '<code style="background: #f0f0f0; padding: 2px 4px; font-size: 12px;">filter_location="' + item.id + '"</code>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            }
                            
                            // Activities
                            if (response.data.activities && response.data.activities.length > 0) {
                                html += '<h4 style="color: #d63384;">🏃 Activities</h4>';
                                html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; margin-bottom: 20px;">';
                                response.data.activities.forEach(function(item) {
                                    html += '<div style="background: white; padding: 8px 12px; border-radius: 3px; border-left: 3px solid #d63384;">';
                                    html += '<strong>' + item.name + '</strong><br>';
                                    html += '<code style="background: #f0f0f0; padding: 2px 4px; font-size: 12px;">filter_activity="' + item.id + '"</code>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            }
                            
                            // Teachers
                            if (response.data.teachers && response.data.teachers.length > 0) {
                                html += '<h4 style="color: #198754;">👨‍🏫 Teachers</h4>';
                                html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; margin-bottom: 20px;">';
                                response.data.teachers.forEach(function(item) {
                                    var teacherName = item.first_name + (item.last_name ? ' ' + item.last_name : '');
                                    html += '<div style="background: white; padding: 8px 12px; border-radius: 3px; border-left: 3px solid #198754;">';
                                    html += '<strong>' + teacherName + '</strong><br>';
                                    html += '<code style="background: #f0f0f0; padding: 2px 4px; font-size: 12px;">filter_teacher="' + item.id + '"</code>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            }
                            
                            // Difficulty Levels
                            if (response.data.difficulty_levels && response.data.difficulty_levels.length > 0) {
                                html += '<h4 style="color: #fd7e14;">📊 Difficulty Levels</h4>';
                                html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; margin-bottom: 20px;">';
                                response.data.difficulty_levels.forEach(function(item) {
                                    html += '<div style="background: white; padding: 8px 12px; border-radius: 3px; border-left: 3px solid #fd7e14;">';
                                    html += '<strong>' + item.name + '</strong><br>';
                                    html += '<code style="background: #f0f0f0; padding: 2px 4px; font-size: 12px;">filter_level="' + item.id + '"</code>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            }
                            
                            // Age Groups
                            if (response.data.ages && response.data.ages.length > 0) {
                                html += '<h4 style="color: #20c997;">👶 Amžiaus grupės</h4>';
                                html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; margin-bottom: 20px;">';
                                response.data.ages.forEach(function(item) {
                                    const displayName = item.name || item.value || item.display_name || 'Age Group ' + item.id;
                                    html += '<div style="background: white; padding: 8px 12px; border-radius: 3px; border-left: 3px solid #20c997;">';
                                    html += '<strong>' + displayName + '</strong><br>';
                                    html += '<code style="background: #f0f0f0; padding: 2px 4px; font-size: 12px;">filter_age="' + item.id + '"</code>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            }
                            
                            // Classes
                            if (response.data.classes && response.data.classes.length > 0) {
                                html += '<h4 style="color: #6f42c1;">🎓 Klasės</h4>';
                                html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; margin-bottom: 20px;">';
                                response.data.classes.forEach(function(item) {
                                    html += '<div style="background: white; padding: 8px 12px; border-radius: 3px; border-left: 3px solid #6f42c1;">';
                                    html += '<strong>' + item.name + '</strong><br>';
                                    html += '<code style="background: #f0f0f0; padding: 2px 4px; font-size: 12px;">filter_class="' + item.id + '"</code>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            }
                            
                            html += '</div>';
                            
                            // Add information about hardcoded filters
                            html += '<div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 4px; margin-top: 15px;">';
                            html += '<h4 style="margin-top: 0; color: #856404;">ℹ️ Amžiaus grupės ir klasės</h4>';
                            html += '<p style="margin-bottom: 10px;">Amžiaus grupės ir klasės yra fiksuotos reikšmės, kurios nėra gaunamos iš API:</p>';
                            html += '<ul style="margin: 10px 0 10px 20px;">';
                            html += '<li><strong>Amžiaus grupės:</strong> Nuo -1 (<6 mėn) iki 18 (Suaugusieji)</li>';
                            html += '<li><strong>Klasės:</strong> Nuo -1 (Darželinukai) iki 12 (12 Klasės)</li>';
                            html += '</ul>';
                            html += '<p style="margin-bottom: 0;">Šie ID\'ai yra standartiniai ir veiks visuose ExoClass sistemose.</p>';
                            html += '</div>';
                            
                            // Add information about multiple values
                            html += '<div style="background: #e7f3ff; border: 1px solid #b3d9ff; color: #0c5aa6; padding: 15px; border-radius: 4px; margin-top: 15px;">';
                            html += '<h4 style="margin-top: 0; color: #0c5aa6;">🔢 Kelių reikšmių palaikymas</h4>';
                            html += '<p style="margin-bottom: 10px;">Visi filtrai palaiko kelias reikšmes, atskirtas kableliais:</p>';
                            html += '<ul style="margin: 10px 0 10px 20px;">';
                            html += '<li><code>filter_age="1,2,3"</code> - Rodyti 1, 2 ir 3 metų amžiaus grupes</li>';
                            html += '<li><code>filter_class="1,2,3"</code> - Rodyti 1, 2 ir 3 klases</li>';
                            html += '<li><code>filter_activity="123,456"</code> - Rodyti kelias veiklas</li>';
                            html += '<li><code>filter_location="789,101"</code> - Rodyti kelias vietas</li>';
                            html += '</ul>';
                            html += '<p style="margin-bottom: 0;">Pavyzdys: <code>[exoclass_calendar filter_age="1,2,3" filter_class="1,2"]</code></p>';
                            html += '</div>';
                            
                            html += '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin-top: 15px;">';
                            html += '<strong>💡 Patarimas:</strong> Spustelėkite bet kurį kodo fragmentą aukščiau, kad nukopijuotumėte jį į iškarpinę lengvam naudojimui shortcode\'uose!';
                            html += '</div>';
                            
                            resultDiv.html(html);
                            
                            // Add click-to-copy functionality
                            resultDiv.find('code').css('cursor', 'pointer').on('click', function() {
                                var text = $(this).text();
                                navigator.clipboard.writeText(text).then(function() {
                                    // Show temporary success message
                                    var originalText = $(this).text();
                                    $(this).text('Copied!').css('background', '#d4edda');
                                    setTimeout(function() {
                                        $(this).text(originalText).css('background', '#f0f0f0');
                                    }.bind(this), 1000);
                                }.bind(this));
                            });
                            
                        } else {
                            resultDiv.html('<p style="color: red;">❌ Failed to load filter IDs: ' + response.data.message + '</p>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<p style="color: red;">❌ AJAX request failed. Please check your API settings.</p>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Load Available Filter IDs');
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
                        $value = isset($options['api_base_url']) ? $options['api_base_url'] : 'https://api.exoclass.com/api/v1/en';
        ?>
        <input type="url" name="exoclass_calendar_options[api_base_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description">The base URL for the ExoClass API (e.g., https://api.exoclass.com/api/v1/en)</p>
        <?php
    }
    
    public function provider_key_callback() {
        $options = get_option('exoclass_calendar_options', array());
        $value = isset($options['provider_key']) ? $options['provider_key'] : '';
        ?>
        <input type="text" name="exoclass_calendar_options[provider_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="e.g., 8691382b-5bae-4396-9b8f-f6e6d7ffd44c" />
        <p class="description">Your ExoClass provider key. You can obtain this from your ExoClass account or contact ExoClass support.</p>
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
    
    public function color_callback() {
        $options = get_option('exoclass_calendar_options', array());
        $value = isset($options['color']) ? $options['color'] : '#667eea';
        ?>
        <input type="color" id="color_picker" name="exoclass_calendar_options[color]" value="<?php echo esc_attr($value); ?>" />
        <input type="text" id="color_text" name="exoclass_calendar_options[color_text]" value="<?php echo esc_attr($value); ?>" class="regular-text" style="margin-left: 10px;" placeholder="#667eea" readonly />
        <p class="description">Choose the accent color for filters, buttons, and highlights. Default: #667eea</p>
        
        <script>
        jQuery(document).ready(function($) {
            $('#color_picker').on('change', function() {
                $('#color_text').val($(this).val());
            });
        });
        </script>
        <?php
    }
    

    
    public static function get_api_config() {
        $options = get_option('exoclass_calendar_options', array());
        $api_base_url = isset($options['api_base_url']) ? $options['api_base_url'] : 'https://api.exoclass.com/api/v1/en';
        
        // Derive embed URL from API URL
        $embed_url = self::get_embed_url_from_api($api_base_url);
        
        return array(
            'base_url' => $api_base_url,
            'embed_url' => $embed_url,
            'provider_key' => isset($options['provider_key']) ? $options['provider_key'] : ''
        );
    }
    
    private static function get_embed_url_from_api($api_url) {
        // Convert API URL to embed URL
        // Example: https://test.api.exoclass.com/api/v1/en -> https://test.embed.exoclass.com
        // Example: https://api.exoclass.com/api/v1/en -> https://embed.exoclass.com
        
        $parsed = parse_url($api_url);
        if (!$parsed || !isset($parsed['host'])) {
            return 'https://embed.exoclass.com'; // fallback
        }
        
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
        $host = $parsed['host'];
        
        // Replace 'api.' with 'embed.' in hostname
        if (strpos($host, 'api.') === 0) {
            $embed_host = str_replace('api.', 'embed.', $host);
        } elseif (strpos($host, 'test.api.') === 0) {
            $embed_host = str_replace('test.api.', 'test.embed.', $host);
        } else {
            // If no 'api.' prefix, assume main domain and add 'embed.'
            $embed_host = 'embed.' . $host;
        }
        
        return $scheme . '://' . $embed_host;
    }
    
    public static function get_display_config() {
        $options = get_option('exoclass_calendar_options', array());
        
        return array(
            'default_height' => isset($options['default_height']) ? $options['default_height'] : 'auto',
            'color' => isset($options['color']) ? $options['color'] : '#667eea'
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

// AJAX handler for fetching filter IDs
add_action('wp_ajax_exoclass_get_filter_ids', 'exoclass_get_filter_ids_callback');
function exoclass_get_filter_ids_callback() {
    check_ajax_referer('exoclass_filter_ids_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $api_config = ExoClassCalendarAdmin::get_api_config();
    
    try {
        $endpoints = array(
            'locations' => '/provider/locations',
            'activities' => '/provider/activities',
            'difficulty_levels' => '/provider/group-difficulty-levels',
            'teachers' => '/provider/teachers'
        );
        
        $filter_data = array();
        $errors = array();
        
        foreach ($endpoints as $key => $endpoint) {
            $url = $api_config['base_url'] . $endpoint . '?provider_key=' . $api_config['provider_key'];
            $response = wp_remote_get($url);
            
            if (is_wp_error($response)) {
                $errors[] = "Failed to fetch {$key}: " . $response->get_error_message();
                continue;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Invalid JSON response for {$key}";
                continue;
            }
            
            // Handle different response structures
            if (is_array($data)) {
                $filter_data[$key] = $data;
            } elseif (isset($data['data']) && is_array($data['data'])) {
                $filter_data[$key] = $data['data'];
            } else {
                $filter_data[$key] = array();
            }
        }
        
        // Add hardcoded age groups and classes (same as in main plugin)
        $filter_data['ages'] = array(
            array('id' => -1, 'name' => '<6 mėn'),
            array('id' => 0, 'name' => '6-12 mėn'),
            array('id' => 1, 'name' => '1 metų'),
            array('id' => 2, 'name' => '2 metų'),
            array('id' => 3, 'name' => '3 metų'),
            array('id' => 4, 'name' => '4 metų'),
            array('id' => 5, 'name' => '5 metų'),
            array('id' => 6, 'name' => '6 metų'),
            array('id' => 7, 'name' => '7 metų'),
            array('id' => 8, 'name' => '8 metų'),
            array('id' => 9, 'name' => '9 metų'),
            array('id' => 10, 'name' => '10 metų'),
            array('id' => 11, 'name' => '11 metų'),
            array('id' => 12, 'name' => '12 metų'),
            array('id' => 13, 'name' => '13 metų'),
            array('id' => 14, 'name' => '14 metų'),
            array('id' => 15, 'name' => '15 metų'),
            array('id' => 16, 'name' => '16 metų'),
            array('id' => 17, 'name' => '17 metų'),
            array('id' => 18, 'name' => 'Suaugusieji')
        );
        
        $filter_data['classes'] = array(
            array('id' => -1, 'name' => 'Darželinukai'),
            array('id' => 0, 'name' => 'Nulinukai'),
            array('id' => 1, 'name' => '1 Klasės'),
            array('id' => 2, 'name' => '2 Klasės'),
            array('id' => 3, 'name' => '3 Klasės'),
            array('id' => 4, 'name' => '4 Klasės'),
            array('id' => 5, 'name' => '5 Klasės'),
            array('id' => 6, 'name' => '6 Klasės'),
            array('id' => 7, 'name' => '7 Klasės'),
            array('id' => 8, 'name' => '8 Klasės'),
            array('id' => 9, 'name' => '9 Klasės'),
            array('id' => 10, 'name' => '10 Klasės'),
            array('id' => 11, 'name' => '11 Klasės'),
            array('id' => 12, 'name' => '12 Klasės')
        );
        
        // If we have some data, consider it a success even if some endpoints failed
        if (!empty($filter_data)) {
            $response_data = $filter_data;
            
            if (!empty($errors)) {
                $response_data['warnings'] = $errors;
            }
            
            wp_send_json_success($response_data);
        } else {
            $error_message = !empty($errors) ? implode('; ', $errors) : 'No filter data available';
            throw new Exception($error_message);
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

// AJAX handler for clearing update cache
add_action('wp_ajax_exoclass_clear_update_cache', 'exoclass_clear_update_cache_callback');
function exoclass_clear_update_cache_callback() {
    check_ajax_referer('exoclass_clear_cache_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    try {
        // Clear our plugin's GitHub release cache
        delete_transient('exoclass_calendar_github_release');
        
        // Clear WordPress update plugins transient
        delete_site_transient('update_plugins');
        
        // If updater class exists and has clear_cache method, use it
        if (class_exists('ExoClassCalendar_Updater')) {
            $updater = new ExoClassCalendar_Updater(
                WP_PLUGIN_DIR . '/exoclass-calendar/exoclass-calendar.php',
                EXOCLASS_CALENDAR_VERSION,
                'racademy',
                'exoclass_calendar_plugin'
            );
            if (method_exists($updater, 'clear_cache')) {
                $updater->clear_cache();
            }
        }
        
        wp_send_json_success(array(
            'message' => 'Update cache cleared successfully. WordPress will check for updates on the next page load.'
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Failed to clear cache: ' . $e->getMessage()));
    }
} 