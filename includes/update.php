<?php
/**
 * Plugin Update Handler - GitHub Releases Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExoClassCalendar_Updater {
    
    private $plugin_slug;
    private $plugin_path;
    private $version;
    private $github_repo;
    private $github_user;
    private $plugin_basename;
    
    public function __construct($plugin_path, $version, $github_user = 'brightprojects', $github_repo = 'exoclass-calendar') {
        $this->plugin_slug = plugin_basename($plugin_path);
        $this->plugin_path = $plugin_path;
        $this->version = $version;
        $this->github_user = $github_user;
        $this->github_repo = $github_repo;
        $this->plugin_basename = dirname($this->plugin_slug);
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_source_selection', array($this, 'upgrader_source_selection'), 10, 4);
    }
    
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Check if our plugin needs update
        if (isset($transient->checked[$this->plugin_slug])) {
            $release_info = $this->get_latest_release();
            
            if ($release_info && version_compare($this->version, $release_info['version'], '<')) {
                $transient->response[$this->plugin_slug] = (object) array(
                    'slug' => $this->plugin_basename,
                    'plugin' => $this->plugin_slug,
                    'new_version' => $release_info['version'],
                    'url' => $release_info['html_url'],
                    'package' => $release_info['download_url'],
                    'tested' => '6.4.0',
                    'requires_php' => '7.4',
                    'compatibility' => new stdClass()
                );
            }
        }
        
        return $transient;
    }
    
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if ($args->slug !== $this->plugin_basename) {
            return $result;
        }
        
        $release_info = $this->get_latest_release();
        
        if (!$release_info) {
            return $result;
        }
        
        return (object) array(
            'name' => 'ExoClass Calendar',
            'slug' => $this->plugin_basename,
            'version' => $release_info['version'],
            'author' => '<a href="https://brightprojects.io">Bright Projects</a>',
            'homepage' => 'https://exoclass.io',
            'requires' => '5.0',
            'tested' => '6.4.0',
            'requires_php' => '7.4',
            'downloaded' => 0,
            'last_updated' => $release_info['published_at'],
            'short_description' => 'A beautiful calendar plugin for displaying fitness classes from ExoClass API.',
            'sections' => array(
                'description' => $this->get_description(),
                'changelog' => $this->format_changelog($release_info['body']),
                'installation' => $this->get_installation_instructions()
            ),
            'download_link' => $release_info['download_url'],
            'banners' => array(),
            'icons' => array()
        );
    }
    
    /**
     * Fix GitHub ZIP extraction - GitHub creates subfolder with repo name
     */
    public function upgrader_source_selection($source, $remote_source, $upgrader, $hook_extra = null) {
        global $wp_filesystem;
        
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_slug) {
            $corrected_source = $remote_source . '/' . $this->plugin_basename . '/';
            
            if ($wp_filesystem->is_dir($corrected_source)) {
                return $corrected_source;
            }
            
            // GitHub creates folder like: exoclass-calendar-main or exoclass-calendar-1.2.0
            $files = $wp_filesystem->dirlist($remote_source);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file['type'] === 'd' && strpos($file['name'], $this->github_repo) === 0) {
                        return trailingslashit($remote_source) . trailingslashit($file['name']);
                    }
                }
            }
        }
        
        return $source;
    }
    
    /**
     * Get latest release from GitHub API
     */
    private function get_latest_release() {
        // Cache for 1 hour
        $cache_key = 'exoclass_calendar_github_release';
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'ExoClass-Calendar-Updater'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('ExoClass Calendar: GitHub API error - ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('ExoClass Calendar: GitHub API returned ' . $response_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body, true);
        
        if (!$release || !isset($release['tag_name'])) {
            error_log('ExoClass Calendar: Invalid GitHub release data');
            return false;
        }
        
        // Extract version from tag (remove 'v' prefix if exists)
        $version = ltrim($release['tag_name'], 'v');
        
        $result = array(
            'version' => $version,
            'download_url' => $release['zipball_url'],
            'html_url' => $release['html_url'],
            'body' => $release['body'],
            'published_at' => $release['published_at']
        );
        
        // Cache for 1 hour
        set_transient($cache_key, $result, 3600);
        
        return $result;
    }
    
    /**
     * Format changelog from GitHub release notes
     */
    private function format_changelog($changelog_text) {
        if (empty($changelog_text)) {
            return '<p>No changelog available.</p>';
        }
        
        // Convert markdown to HTML (basic)
        $changelog = nl2br(esc_html($changelog_text));
        
        // Convert markdown headers
        $changelog = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $changelog);
        $changelog = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $changelog);
        $changelog = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $changelog);
        
        // Convert markdown lists
        $changelog = preg_replace('/^- (.+)$/m', '<li>$1</li>', $changelog);
        $changelog = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $changelog);
        
        return $changelog;
    }
    
    /**
     * Get plugin description
     */
    private function get_description() {
        return '<p>ExoClass Calendar is a beautiful and responsive WordPress plugin that displays fitness classes and activities from the ExoClass API in an interactive calendar format.</p>
        
        <h4>Features:</h4>
        <ul>
            <li>📅 Interactive FullCalendar.js powered calendar</li>
            <li>🔍 Advanced filtering by location, activity, age group, and difficulty</li>
            <li>📱 Fully responsive design for mobile and desktop</li>
            <li>🌍 Lithuanian localization support</li>
            <li>⚡ Real-time data from ExoClass API</li>
            <li>🎨 Customizable styling and colors</li>
            <li>⚙️ Easy WordPress admin configuration</li>
        </ul>
        
        <h4>Installation:</h4>
        <p>Simply use the shortcode <code>[exoclass_calendar]</code> on any page or post where you want to display the calendar.</p>';
    }
    
    /**
     * Get installation instructions
     */
    private function get_installation_instructions() {
        return '<h4>Automatic Installation:</h4>
        <ol>
            <li>Go to your WordPress admin dashboard</li>
            <li>Navigate to Plugins → Add New</li>
            <li>Search for "ExoClass Calendar"</li>
            <li>Click "Install Now" and then "Activate"</li>
        </ol>
        
        <h4>Manual Installation:</h4>
        <ol>
            <li>Download the plugin ZIP file</li>
            <li>Go to Plugins → Add New → Upload Plugin</li>
            <li>Choose the ZIP file and click "Install Now"</li>
            <li>Activate the plugin</li>
        </ol>
        
        <h4>Configuration:</h4>
        <ol>
            <li>Go to Settings → ExoClass Calendar</li>
            <li>Enter your ExoClass API endpoint and provider key</li>
            <li>Configure display options as needed</li>
            <li>Add the shortcode <code>[exoclass_calendar]</code> to any page</li>
        </ol>';
    }
    
    /**
     * Clear update cache (useful for testing)
     */
    public function clear_cache() {
        delete_transient('exoclass_calendar_github_release');
    }
}