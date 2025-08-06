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
        add_action('upgrader_process_complete', array($this, 'purge_transients_after_update'), 10, 2);
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
     * Fix GitHub ZIP extraction - Handle both release assets and zipball URLs
     */
    public function upgrader_source_selection($source, $remote_source, $upgrader, $hook_extra = null) {
        global $wp_filesystem;
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ExoClass Calendar Upgrader: Source selection called');
            error_log('Source: ' . $source);
            error_log('Remote source: ' . $remote_source);
            error_log('Plugin slug: ' . $this->plugin_slug);
            error_log('Hook extra plugin: ' . (isset($hook_extra['plugin']) ? $hook_extra['plugin'] : 'not set'));
        }
        
        // Only process our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_slug) {
            return $source;
        }
        
        // First, check if the correct folder already exists (from release asset)
        $correct_folder = trailingslashit($remote_source) . 'exoclass-calendar';
        if ($wp_filesystem->is_dir($correct_folder)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ExoClass Calendar: Found correct folder structure: ' . $correct_folder);
            }
            return trailingslashit($correct_folder);
        }
        
        // If not, we need to handle GitHub zipball structure
        $files = $wp_filesystem->dirlist($remote_source);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ExoClass Calendar: Files in ' . $remote_source . ': ' . print_r(array_keys($files), true));
        }
        
        if (!is_array($files)) {
            return $source;
        }
        
        // Look for GitHub-generated folder (username-repo-hash format)
        foreach ($files as $file) {
            if ($file['type'] !== 'd') {
                continue;
            }
            
            $folder_name = $file['name'];
            
            // Check various GitHub folder patterns
            if (strpos($folder_name, $this->github_repo) !== false || 
                strpos($folder_name, 'exoclass-calendar') !== false ||
                preg_match('/^[a-z0-9]+-exoclass[-_]calendar/i', $folder_name)) {
                
                $old_source = trailingslashit($remote_source) . $folder_name;
                $new_source = trailingslashit($remote_source) . 'exoclass-calendar';
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('ExoClass Calendar: Renaming ' . $old_source . ' to ' . $new_source);
                }
                
                // Rename the folder to the correct plugin name
                if ($wp_filesystem->move($old_source, $new_source)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('ExoClass Calendar: Successfully renamed folder');
                    }
                    return trailingslashit($new_source);
                } else {
                    // If rename fails, try to use the original folder
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('ExoClass Calendar: Could not rename, using original: ' . $old_source);
                    }
                    return trailingslashit($old_source);
                }
            }
        }
        
        // Fallback to original source
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ExoClass Calendar: No matching folder found, using original source');
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
        
        // Look for release asset ZIP file
        $download_url = $release['zipball_url']; // Default fallback
        
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                // Look for our plugin ZIP file
                if (strpos($asset['name'], 'exoclass-calendar') !== false && 
                    substr($asset['name'], -4) === '.zip') {
                    $download_url = $asset['browser_download_url'];
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('ExoClass Calendar: Using release asset: ' . $asset['name']);
                    }
                    break;
                }
            }
        }
        
        $result = array(
            'version' => $version,
            'download_url' => $download_url,
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
        delete_site_transient('update_plugins');
    }
    
    /**
     * Purge transients after plugin update
     */
    public function purge_transients_after_update($upgrader_object, $options) {
        // Check if our plugin was just updated
        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            // Check if single plugin update
            if (isset($options['plugin']) && $options['plugin'] == $this->plugin_slug) {
                $this->clear_cache();
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('ExoClass Calendar: Cleared update cache after successful update');
                }
            }
            // Check if bulk update
            elseif (isset($options['plugins']) && in_array($this->plugin_slug, $options['plugins'])) {
                $this->clear_cache();
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('ExoClass Calendar: Cleared update cache after bulk update');
                }
            }
        }
    }
}