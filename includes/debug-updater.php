<?php
/**
 * Debug utilities for testing GitHub updater
 * 
 * Usage: Add ?exoclass_debug=1 to any WordPress admin URL
 * Or add this to functions.php temporarily:
 * 
 * add_action('init', function() {
 *     if (current_user_can('manage_options') && isset($_GET['exoclass_debug'])) {
 *         include_once(WP_PLUGIN_DIR . '/exoclass-calendar/includes/debug-updater.php');
 *         exoclass_calendar_debug_updater();
 *         exit;
 *     }
 * });
 */

if (!defined('ABSPATH')) {
    exit;
}

function exoclass_calendar_debug_updater() {
    // Only allow for administrators
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    echo '<html><head><title>ExoClass Calendar - GitHub Updater Debug</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-left: 3px solid #ddd; }
        .test-result { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
    </style></head><body>';
    
    echo '<h1>🔧 ExoClass Calendar - GitHub Updater Debug</h1>';
    
    // Test 1: Check if updater class exists
    echo '<div class="test-result">';
    echo '<h3>1. Updater Class Check</h3>';
    if (class_exists('ExoClassCalendar_Updater')) {
        echo '<p class="success">✅ ExoClassCalendar_Updater class exists</p>';
    } else {
        echo '<p class="error">❌ ExoClassCalendar_Updater class not found</p>';
        echo '<p>Make sure the plugin is active and update.php is loaded properly.</p>';
    }
    echo '</div>';
    
    // Test 2: GitHub API connectivity
    echo '<div class="test-result">';
    echo '<h3>2. GitHub API Test</h3>';
    
    // You need to update these values with actual GitHub repo
    $github_user = 'racademy';  // CHANGE THIS
    $github_repo = 'exoclass_calendar_plugin';
    
    echo '<p><strong>Testing repository:</strong> ' . esc_html($github_user . '/' . $github_repo) . '</p>';
    

    
    $api_url = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";
    
    $response = wp_remote_get($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'ExoClass-Calendar-Updater-Debug'
        )
    ));
    
    if (is_wp_error($response)) {
        echo '<p class="error">❌ GitHub API Error: ' . esc_html($response->get_error_message()) . '</p>';
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        echo '<p><strong>Response Code:</strong> ' . esc_html($response_code) . '</p>';
        
        if ($response_code === 200) {
            echo '<p class="success">✅ GitHub API accessible</p>';
            
            $data = json_decode($body, true);
            if ($data && isset($data['tag_name'])) {
                echo '<p><strong>Latest Release:</strong> ' . esc_html($data['tag_name']) . '</p>';
                echo '<p><strong>Published:</strong> ' . esc_html($data['published_at']) . '</p>';
                echo '<p><strong>Download URL:</strong> ' . esc_html($data['zipball_url']) . '</p>';
                
                if (!empty($data['body'])) {
                    echo '<p><strong>Release Notes:</strong></p>';
                    echo '<pre>' . esc_html(substr($data['body'], 0, 500)) . '</pre>';
                }
            } else {
                echo '<p class="error">❌ Invalid response format</p>';
                echo '<pre>' . esc_html(substr($body, 0, 500)) . '</pre>';
            }
        } elseif ($response_code === 404) {
            echo '<p class="error">❌ Repository not found or no releases exist</p>';
            echo '<p class="info">💡 Make sure you have created at least one release on GitHub</p>';
        } else {
            echo '<p class="error">❌ HTTP ' . esc_html($response_code) . '</p>';
            echo '<pre>' . esc_html(substr($body, 0, 500)) . '</pre>';
        }
    }
    echo '</div>';
    
    // Test 3: Current plugin version
    echo '<div class="test-result">';
    echo '<h3>3. Plugin Version Info</h3>';
    
    if (defined('EXOCLASS_CALENDAR_VERSION')) {
        echo '<p><strong>Current Version:</strong> ' . esc_html(EXOCLASS_CALENDAR_VERSION) . '</p>';
    } else {
        echo '<p class="error">❌ EXOCLASS_CALENDAR_VERSION constant not defined</p>';
    }
    
    // Get plugin data
    if (function_exists('get_plugin_data')) {
        $plugin_file = WP_PLUGIN_DIR . '/exoclass-calendar/exoclass-calendar.php';
        if (file_exists($plugin_file)) {
            $plugin_data = get_plugin_data($plugin_file);
            echo '<p><strong>Plugin Header Version:</strong> ' . esc_html($plugin_data['Version']) . '</p>';
            echo '<p><strong>Plugin Name:</strong> ' . esc_html($plugin_data['Name']) . '</p>';
        }
    }
    echo '</div>';
    
    // Test 4: Cache status
    echo '<div class="test-result">';
    echo '<h3>4. Cache Status</h3>';
    
    $cache_key = 'exoclass_calendar_github_release';
    $cached_data = get_transient($cache_key);
    
    if ($cached_data) {
        echo '<p class="info">ℹ️  Cached data exists</p>';
        echo '<pre>' . esc_html(print_r($cached_data, true)) . '</pre>';
        
        echo '<p><a href="?exoclass_debug=1&clear_cache=1">Clear Cache</a></p>';
    } else {
        echo '<p>ℹ️  No cached data</p>';
    }
    
    // Clear cache if requested
    if (isset($_GET['clear_cache'])) {
        delete_transient($cache_key);
        echo '<p class="success">✅ Cache cleared!</p>';
    }
    echo '</div>';
    
    // Test 5: WordPress update check
    echo '<div class="test-result">';
    echo '<h3>5. WordPress Update Transient</h3>';
    
    $update_plugins = get_site_transient('update_plugins');
    $plugin_slug = 'exoclass-calendar/exoclass-calendar.php';
    
    if ($update_plugins && isset($update_plugins->response[$plugin_slug])) {
        echo '<p class="info">ℹ️  Update available in WordPress transient</p>';
        echo '<pre>' . esc_html(print_r($update_plugins->response[$plugin_slug], true)) . '</pre>';
    } else {
        echo '<p>ℹ️  No update in WordPress transient</p>';
    }
    
    echo '<p><a href="?exoclass_debug=1&force_update_check=1">Force Update Check</a></p>';
    
    if (isset($_GET['force_update_check'])) {
        // Clear the update transient to force a fresh check
        delete_site_transient('update_plugins');
        wp_update_plugins();
        echo '<p class="success">✅ Forced update check completed!</p>';
        echo '<p>Refresh this page to see results.</p>';
    }
    echo '</div>';
    
    // Test 6: Manual updater test
    if (class_exists('ExoClassCalendar_Updater')) {
        echo '<div class="test-result">';
        echo '<h3>6. Manual Updater Test</h3>';
        
        try {
            $updater = new ExoClassCalendar_Updater(
                WP_PLUGIN_DIR . '/exoclass-calendar/exoclass-calendar.php',
                defined('EXOCLASS_CALENDAR_VERSION') ? EXOCLASS_CALENDAR_VERSION : 'undefined',
                $github_user,
                $github_repo
            );
            
            // Call private method via reflection (for testing only)
            $reflection = new ReflectionClass($updater);
            $method = $reflection->getMethod('get_latest_release');
            $method->setAccessible(true);
            $result = $method->invoke($updater);
            
            if ($result) {
                echo '<p class="success">✅ Updater working correctly</p>';
                echo '<pre>' . esc_html(print_r($result, true)) . '</pre>';
            } else {
                echo '<p class="error">❌ Updater returned no data</p>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Error testing updater: ' . esc_html($e->getMessage()) . '</p>';
        }
        echo '</div>';
    }
    
    // Instructions
    echo '<div class="test-result">';
    echo '<h3>📋 Setup Instructions</h3>';
    echo '<ol>';
    echo '<li>Update GitHub username in <code>exoclass-calendar.php</code> (line ~55)</li>';
    echo '<li>Create GitHub repository and push your code</li>';
    echo '<li>Create a release: <code>git tag v1.2.0 && git push origin v1.2.0</code></li>';
    echo '<li>Or use the release script: <code>./release.sh 1.2.0</code></li>';
    echo '<li>WordPress will check for updates every 12 hours automatically</li>';
    echo '</ol>';
    echo '</div>';
    
    echo '</body></html>';
}

// Auto-run if accessed directly with debug parameter
if (isset($_GET['exoclass_debug']) && current_user_can('manage_options')) {
    exoclass_calendar_debug_updater();
}