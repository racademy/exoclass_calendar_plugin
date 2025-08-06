<?php
/**
 * Plugin Name: ExoClass Calendar
 * Plugin URI: https://exoclass.io
 * Description: A beautiful calendar plugin for displaying fitness classes and activities from ExoClass API with filtering capabilities.
 * Version: 1.2.5
 * Author: Bright Projects
 * Author URI: https://brightprojects.io
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: exoclass-calendar
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EXOCLASS_CALENDAR_VERSION', '1.2.5');
define('EXOCLASS_CALENDAR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EXOCLASS_CALENDAR_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include admin settings
require_once EXOCLASS_CALENDAR_PLUGIN_PATH . 'includes/admin-settings.php';

// Include update handler
require_once EXOCLASS_CALENDAR_PLUGIN_PATH . 'includes/update.php';

class ExoClassCalendar {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_exoclass_get_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_nopriv_exoclass_get_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_exoclass_get_filters', array($this, 'ajax_get_filters'));
        add_action('wp_ajax_nopriv_exoclass_get_filters', array($this, 'ajax_get_filters'));
        add_shortcode('exoclass_calendar', array($this, 'calendar_shortcode'));
        
        // Initialize updater
        $this->init_updater();
        
        // Debug handler for testing updates (only in admin area)
        add_action('admin_init', array($this, 'handle_debug_request'));
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('exoclass-calendar', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function init_updater() {
        // Initialize the updater with GitHub repository info
        if (class_exists('ExoClassCalendar_Updater')) {
            new ExoClassCalendar_Updater(__FILE__, EXOCLASS_CALENDAR_VERSION, 'racademy', 'exoclass_calendar_plugin');
        }
    }
    
    public function handle_debug_request() {
        // Handle debug request for update testing
        if (current_user_can('manage_options') && isset($_GET['exoclass_debug'])) {
            require_once EXOCLASS_CALENDAR_PLUGIN_PATH . 'includes/debug-updater.php';
            exoclass_calendar_debug_updater();
            exit;
        }
    }
    
    public function enqueue_scripts() {
        // Only enqueue if shortcode is present
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'exoclass_calendar')) {
            $this->enqueue_calendar_assets();
        }
    }
    
    public function enqueue_calendar_assets($atts = array()) {
        // FullCalendar CSS
        wp_enqueue_style(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css',
            array(),
            '6.1.10'
        );
        
        // FullCalendar JavaScript
        wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
            array(),
            '6.1.10',
            true
        );
        
        // FullCalendar Lithuanian locale
        wp_enqueue_script(
            'fullcalendar-lt',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/lt.global.min.js',
            array('fullcalendar'),
            '6.1.10',
            true
        );
        
        // Custom plugin styles
        wp_enqueue_style(
            'exoclass-calendar-styles',
            EXOCLASS_CALENDAR_PLUGIN_URL . 'assets/css/calendar.css',
            array('fullcalendar'),
            EXOCLASS_CALENDAR_VERSION
        );
        
        // Custom plugin JavaScript
        wp_enqueue_script(
            'exoclass-calendar-script',
            EXOCLASS_CALENDAR_PLUGIN_URL . 'assets/js/calendar.js',
            array('fullcalendar', 'fullcalendar-lt'),
            EXOCLASS_CALENDAR_VERSION,
            true
        );
        
        // Get API configuration from admin settings
        $api_config = ExoClassCalendarAdmin::get_api_config();
        
        // Localize script for AJAX
        wp_localize_script('exoclass-calendar-script', 'exoclass_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('exoclass_calendar_nonce'),
            'api_config' => $api_config,
            'show_images' => !empty($atts) ? filter_var($atts['show_images'], FILTER_VALIDATE_BOOLEAN) : true,
            'initial_filters' => array(
                'location' => !empty($atts['filter_location']) ? sanitize_text_field($atts['filter_location']) : '',
                'activity' => !empty($atts['filter_activity']) ? sanitize_text_field($atts['filter_activity']) : '',
                'teacher' => !empty($atts['filter_teacher']) ? sanitize_text_field($atts['filter_teacher']) : '',
                'level' => !empty($atts['filter_level']) ? sanitize_text_field($atts['filter_level']) : '',
                'availability' => !empty($atts['filter_availability']) ? sanitize_text_field($atts['filter_availability']) : ''
            )
        ));
    }
    
    public function calendar_shortcode($atts) {
        $display_config = ExoClassCalendarAdmin::get_display_config();
        $atts = shortcode_atts(array(
            'height' => $display_config['default_height'],
            'show_images' => 'true',
            'filter_location' => '',
            'filter_activity' => '',
            'filter_teacher' => '',
            'filter_level' => '',
            'filter_availability' => ''
        ), $atts, 'exoclass_calendar');
        
        // Enqueue assets with attributes
        $this->enqueue_calendar_assets($atts);
        
        ob_start();
        ?>
        <div class="exoclass-calendar-container">
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filters-container">
                    <div class="filter-group">
                        <select class="filter-dropdown" id="locationDropdown">
                            <option value=""><?php _e('Visos vietos', 'exoclass-calendar'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select class="filter-dropdown" id="activityDropdown">
                            <option value=""><?php _e('Visos veiklos', 'exoclass-calendar'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group" id="ageGroup">
                        <select class="filter-dropdown" id="ageDropdown">
                            <option value=""><?php _e('Visi amžiai', 'exoclass-calendar'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group" id="difficultyGroup">
                        <select class="filter-dropdown" id="difficultyDropdown">
                            <option value=""><?php _e('Visi lygiai', 'exoclass-calendar'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group" id="teacherGroup">
                        <select class="filter-dropdown" id="teacherDropdown">
                            <option value=""><?php _e('Visi treneriai', 'exoclass-calendar'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select class="filter-dropdown" id="availabilityDropdown">
                            <option value=""><?php _e('Visos klasės', 'exoclass-calendar'); ?></option>
                            <option value="available"><?php _e('Yra laisvų vietų', 'exoclass-calendar'); ?></option>
                            <option value="full"><?php _e('Nėra laisvų vietų', 'exoclass-calendar'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn btn-clear" id="clearFilters"><?php _e('Išvalyti viską', 'exoclass-calendar'); ?></button>
                        <!-- <button class="btn btn-apply" id="applyFilters"><?php //_e('Filtruoti', 'exoclass-calendar'); ?></button> -->
                    </div>
                </div>
            </div>
            
            <div id="loadingIndicator" class="modern-loading-overlay">
                <div class="loading-container">
                    <div class="loading-spinner">
                        <div class="spinner-ring"></div>
                        <div class="spinner-ring"></div>
                        <div class="spinner-ring"></div>
                    </div>
                </div>
            </div>
            
            <!-- Calendar Container -->
            <div class="calendar-container">
                <div id='calendar' style="height: <?php echo esc_attr($atts['height']); ?>;"></div>
            </div>

            <!-- Event Details Modal -->
            <div id="eventDetailsModal" class="event-modal">
                <div class="event-modal-content">
                    <div class="event-modal-header">
                        <span class="close-modal">&times;</span>
                        <div class="event-modal-image"></div>
                        <h2 class="event-modal-title"></h2>
                    </div>
                    <div class="event-modal-body">
                        <div class="event-modal-info">
                            <div class="info-row">
                                <span class="info-label">🕒 <?php _e('Laikas', 'exoclass-calendar'); ?>:</span>
                                <span class="event-time"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">👤 <?php _e('Treneris', 'exoclass-calendar'); ?>:</span>
                                <span class="event-teacher"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">👥 <?php _e('Laisvos vietos', 'exoclass-calendar'); ?>:</span>
                                <span class="event-spots"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">🎯 <?php _e('Lygis', 'exoclass-calendar'); ?>:</span>
                                <span class="event-difficulty"></span>
                            </div>
                        </div>
                        <div class="event-modal-description">
                            <div class="description-content"></div>
                            <button class="read-more-btn description-read-more"><?php _e('Skaityti daugiau', 'exoclass-calendar'); ?></button>
                        </div>
                        <div class="event-modal-teacher-info" style="display: none;">
                            <div class="teacher-description">
                                <div class="teacher-description-content"></div>
                            </div>
                            <button class="read-more-btn"><?php _e('Skaityti daugiau', 'exoclass-calendar'); ?></button>
                        </div>
                        <div class="event-modal-actions">
                            <a href="#" class="register-button" target="_blank"><?php _e('Registruotis', 'exoclass-calendar'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function ajax_get_events() {
        check_ajax_referer('exoclass_calendar_nonce', 'nonce');
        
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        try {
            $events = $this->load_events_from_api($filters);
            wp_send_json_success($events);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    public function ajax_get_filters() {
        check_ajax_referer('exoclass_calendar_nonce', 'nonce');
        
        try {
            $filters = $this->load_filter_data();
            wp_send_json_success($filters);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    private function load_events_from_api($filters = array()) {
        $api_config = ExoClassCalendarAdmin::get_api_config();
        
        // Build API URL with filters
        $api_url = $api_config['base_url'] . '/groups?provider_key=' . $api_config['provider_key'] . '&page=1';
        
        // Add filter parameters
        if (!empty($filters['activities'])) {
            $api_url .= '&activities=' . implode(',', $filters['activities']);
        }
        if (!empty($filters['locations'])) {
            $api_url .= '&locations=' . implode(',', $filters['locations']);
        }
        if (!empty($filters['difficulties'])) {
            $api_url .= '&difficulty_levels=' . implode(',', $filters['difficulties']);
        }
        if (!empty($filters['ages'])) {
            $api_url .= '&ages=' . implode(',', $filters['ages']) . '&age_type=age';
        }
        if (!empty($filters['teachers'])) {
            $api_url .= '&teachers=' . implode(',', $filters['teachers']);
        }
        
        $response = wp_remote_get($api_url);
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch data from API: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        $events = array();
        
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $group) {
                if (!isset($group['schedule']) || !is_array($group['schedule'])) {
                    continue;
                }
                
                foreach ($group['schedule'] as $session) {
                    if (isset($session['is_holiday']) && $session['is_holiday']) {
                        continue;
                    }
                    
                    if (!isset($session['start_time']) || !isset($session['end_time'])) {
                        continue;
                    }
                    
                    $max_spots = isset($group['capacity']) ? $group['capacity'] : 0;
                    $current_attendees = isset($group['attendees']) ? $group['attendees'] : 0;
                    $available_spots = max(0, $max_spots - $current_attendees);
                    
                    $activity_name = isset($group['activity']['name']) ? $group['activity']['name'] : (isset($group['name']) ? $group['name'] : 'Unknown');
                    $teacher = $this->get_teacher_name($group);
                    
                    $events[] = array(
                        'title' => isset($group['name']) ? $group['name'] : $activity_name,
                        'start' => $session['start_time'],
                        'end' => $session['end_time'],
                        'extendedProps' => array(
                            'teacher' => $teacher,
                            'teacherData' => $this->get_teacher_data($group),
                            'availableSpots' => $available_spots,
                            'maxSpots' => $max_spots,
                            'activityName' => $activity_name,
                            'activityData' => isset($group['activity']) ? $group['activity'] : null,
                            'groupId' => $group['id'],
                            'groupExternalKey' => isset($group['external_key']) ? $group['external_key'] : $group['id'],
                            'sessionId' => $session['id'],
                            'difficulty' => isset($group['difficulty_type']['name']) ? $group['difficulty_type']['name'] : null
                        )
                    );
                }
            }
        }
        
        return $events;
    }
    
    private function load_filter_data() {
        $api_config = ExoClassCalendarAdmin::get_api_config();
        
        $endpoints = array(
            'locations' => '/provider/locations',
            'activities' => '/provider/activities',
            'difficulty_levels' => '/provider/group-difficulty-levels',
            'teachers' => '/provider/teachers',
            'filters' => '/provider/embed/v2/filters'
        );
        
        $filter_data = array();
        
        foreach ($endpoints as $key => $endpoint) {
            $url = $api_config['base_url'] . $endpoint . '?provider_key=' . $api_config['provider_key'];
            $response = wp_remote_get($url);
            
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    $filter_data[$key] = is_array($data) ? $data : (isset($data['data']) ? $data['data'] : array());
                }
            }
        }
        
        // TEMP: Output filter data for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ExoClass Calendar Filter Data: ' . print_r($filter_data, true));
        }
        
        return $filter_data;
    }
    
    private function get_teacher_name($group) {
        // Check 'teachers' array first (this is what we found in the API)
        if (isset($group['teachers']) && is_array($group['teachers']) && !empty($group['teachers'])) {
            $teacher = $group['teachers'][0];
            
            if (isset($teacher['name'])) {
                return $teacher['name'];
            }
            if (isset($teacher['first_name']) && isset($teacher['last_name'])) {
                return $teacher['first_name'] . ' ' . $teacher['last_name'];
            }
            if (isset($teacher['first_name'])) {
                return $teacher['first_name'];
            }
        }
        
        // Fallback to 'staff' array
        if (isset($group['staff']) && is_array($group['staff']) && !empty($group['staff'])) {
            $teacher = $group['staff'][0];
            
            if (isset($teacher['name'])) {
                return $teacher['name'];
            }
            if (isset($teacher['first_name']) && isset($teacher['last_name'])) {
                return $teacher['first_name'] . ' ' . $teacher['last_name'];
            }
            if (isset($teacher['first_name'])) {
                return $teacher['first_name'];
            }
        }
        
        // Fallback to 'instructor' object
        if (isset($group['instructor']['name'])) {
            return $group['instructor']['name'];
        }
        
        return __('Treneris', 'exoclass-calendar');
    }
    
    private function get_teacher_data($group) {
        // Try to get teacher info from the 'teachers' array
        if (isset($group['teachers']) && is_array($group['teachers']) && !empty($group['teachers'])) {
            $teacher = $group['teachers'][0];
            // Return the whole teacher array (contains bio/description if available)
            return $teacher;
        }
        // Fallbacks if needed
        if (isset($group['staff']) && is_array($group['staff']) && !empty($group['staff'])) {
            return $group['staff'][0];
        }
        if (isset($group['instructor'])) {
            return $group['instructor'];
        }
        return null;
    }
}

// Initialize the plugin
new ExoClassCalendar();

// Activation hook
register_activation_hook(__FILE__, 'exoclass_calendar_activate');
function exoclass_calendar_activate() {
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/exoclass-calendar';
    
    if (!file_exists($plugin_upload_dir)) {
        wp_mkdir_p($plugin_upload_dir);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'exoclass_calendar_deactivate');
function exoclass_calendar_deactivate() {
    // Cleanup if needed
} 