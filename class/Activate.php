<?php

namespace ServeStatic;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Activate
{

    function __construct() {
        global $wp_filesystem;

        // Initialize the WP_Filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();

        if (!is_wp_error($wp_filesystem)) {
            // Edit the .htaccess file to serve HTML copies instead of WordPress PHP
            $htaccess = $wp_filesystem->get_contents(ABSPATH . '.htaccess');

            $begin = '# BEGIN Serve Static Cache';
            $end = '# END Serve Static Cache';

            if (strpos($htaccess, $begin) === false && strpos($htaccess, $end) === false) {
                $rules = $this->rules();

                $htaccess = implode(PHP_EOL, $rules) . PHP_EOL . $htaccess;

                // Write the updated .htaccess content
                $write_to_file = $wp_filesystem->put_contents(ABSPATH . '.htaccess', $htaccess, FS_CHMOD_FILE);
                if ( ! isset($write_to_file) || $write_to_file != 1 ){
                    set_transient( 'serve_static_htaccess_not_writable', 1 );
                } elseif ( $write_to_file == 1 ) {
                    delete_transient( 'serve_static_htaccess_not_writable' );
                }
            }

            // Create a directory for storing HTML copies
            $cache_dir = WP_CONTENT_DIR . '/html-cache';
            if (!$wp_filesystem->is_dir($cache_dir)) {
                $wp_filesystem->mkdir($cache_dir, 0755, true);
            }

            flush_rewrite_rules();
        }
    }

    public function one($a){
        return $a;
    }

    public function rules(){
        $begin = '# BEGIN Serve Static Cache';
        $end = '# END Serve Static Cache';
        return $rules = [
            $begin,
            'RewriteEngine On',
            'RewriteBase /',
            'RewriteCond %{HTTP_COOKIE} !(^|;\s*)wordpress_logged_in_.*$ [NC]',
            'RewriteCond %{REQUEST_URI} !^/(elementor|vc_row|fl_builder|fl-theme-builder) [NC]',
            'RewriteCond %{REQUEST_URI} !^/wp-admin/ [NC]',
            'RewriteCond %{REQUEST_METHOD} GET',
            'RewriteCond %{QUERY_STRING} ^$ [NC]',
            'RewriteCond %{DOCUMENT_ROOT}/wp-content/html-cache/$1/index.html -f',
            'RewriteRule ^(.*)$ /wp-content/html-cache/$1/index.html [L]',
            '',
            $end
        ];
    }

    public function nginx_rules(){
        $begin = '# BEGIN Serve Static Cache';
        $end = '# END Serve Static Cache';

        $nginxConfig = [
            $begin,
            'location / {',
            '    if ($http_cookie !~* "wordpress_logged_in_") {',
            '        set $cache_uri $request_uri;',
            '',
            '        if ($request_uri ~* "^/(elementor|vc_row|fl_builder|fl-theme-builder)") {',
            '            set $cache_uri "null cache";',
            '        }',
            '',
            '        if ($request_uri ~* "^/wp-admin/") {',
            '            set $cache_uri "null cache";',
            '        }',
            '',
            '        if ($request_method = GET) {',
            '            set $cache_uri "null cache";',
            '        }',
            '',
            '        if (-f $document_root/wp-content/html-cache$cache_uri/index.html) {',
            '            set $cache_file $document_root/wp-content/html-cache$cache_uri/index.html;',
            '        }',
            '',
            '        if ($cache_file) {',
            '            rewrite ^ /wp-content/html-cache$cache_uri/index.html break;',
            '        }',
            '    }',
            '}',
            $end
        ];

        return $nginxConfig;
    }

    public function Admin()
    {
        add_menu_page( __( 'Settings', 'serve-static' ), 'Serve Static', 'manage_options', 'serve_static_settings', false, 'dashicons-schedule');
        add_submenu_page( 'serve_static_settings', 'Settings', 'Settings', 'manage_options', 'serve_static_settings', array($this, 'SettingsPage') );
    }

    public function AddToolbarMenu()
    {
        if (current_user_can('manage_options')) {

            global $wp_admin_bar;
            // Check if the transient is set
            $is_cache_warming_in_progress = get_transient('serve_static_cache_warming_in_progress');

            // Define the base URL
            $base_url = admin_url('admin.php?page=serve_static_settings');

            // Define the final URLs based on the presence of the transient
            $flush_cache_url = $is_cache_warming_in_progress ? $base_url : wp_nonce_url($base_url . '&action=flush_cache', 'serve_static_flush_cache', '_wpnonce', 10);
            $warm_cache_url = $is_cache_warming_in_progress ? $base_url : wp_nonce_url($base_url . '&action=warm_cache', 'serve_static_warm_cache', '_wpnonce', 10);

            if ( ! is_admin() ){

                $current_url = add_query_arg($_SERVER['QUERY_STRING'], '', home_url($_SERVER['REQUEST_URI']));
                if ( get_option('serve_static_master_key') == 0 ){
                    $admin_toolbar_parent = 'Serve Static - Not Cached';
                } elseif ( get_option(' serve_static_make_static ') == 1 ){
                    $a = get_option(' serve_static_exclude_urls ');
                    $admin_toolbar_parent  = isset($a[$current_url]) ? 'Serve Static - Not Cached': 'Serve Static - Cached';
                } elseif (get_option(' serve_static_post_types_static ') == 1){
                    $a = get_option(' serve_static_exclude_urls ');
                    $post_type = get_post_type(url_to_postid($current_url));
                    $allowed_post_type = get_option( 'serve_static_specific_post_types' );
                    $admin_toolbar_parent = isset($allowed_post_type[$post_type]) ? 'Serve Static - Cached' : 'Serve Static - Not Cached';
                    $admin_toolbar_parent = isset($a[$current_url]) ? 'Serve Static - Not Cached': $admin_toolbar_parent;
                } elseif (get_option('serve_static_urls') !== false) {
                    $urls = get_option('serve_static_urls');
                    $admin_toolbar_parent = ! isset( $urls[$current_url] ) ? 'Serve Static - Not Cached' : 'Serve Static - Cached';
                } else {
                    $admin_toolbar_parent = 'Serve Static - Not Cached';
                }
            } else {
                $cache_dir = WP_CONTENT_DIR . '/html-cache';
                $cache_size = $this->get_directory_size($cache_dir);
                $admin_toolbar_parent = 'Static Cache Size: ' . size_format($cache_size, 2); ?>
                <style>
                    #wp-admin-bar-serve_static_cache_size {
                        background-color: green !important;
                    }
                </style> <?php
            }

            if ( $admin_toolbar_parent === 'Serve Static - Not Cached' ){ ?>
                <style>
                    #wp-admin-bar-serve_static_cache_size {
                        background-color: red !important;
                    }
                </style> 
                <?php
            } elseif ( $admin_toolbar_parent === 'Serve Static - Cached' ) {
                $static = new StaticServe();
                if ( $static->is_cache_available( $current_url ) === false ){
                    $admin_toolbar_parent = 'Cached but cache missing'; ?>
                        <style>
                            #wp-admin-bar-serve_static_cache_size {
                                background-color: red !important;
                            }
                        </style> 
                    <?php
                } else {
                    ?>
                    <style>
                        #wp-admin-bar-serve_static_cache_size {
                            background-color: green !important;
                        }
                    </style> 
                    <?php
                }
            }

            // Add a top-level menu item displaying cache size
            $wp_admin_bar->add_menu(
                array(
                    'id' => 'serve_static_cache_size',
                    'title' => $admin_toolbar_parent,
                    'href' => false, // No link for the cache size
                )
            );

            if ( ! is_admin() ) {
                $flush_url = $is_cache_warming_in_progress ? $base_url : wp_nonce_url(admin_url('admin-post.php?action=flush_url&url=' . urlencode($current_url)), 'serve_static_flush_url_action', 'flush_url_nonce');
                // Add the admin bar menu item
                $wp_admin_bar->add_menu(array(
                    'parent' => 'serve_static_cache_size',
                    'id' => 'serve_static_flush_url',
                    'title' => 'Regenerate cache for this URL',
                    'href' => $flush_url,
                ));
            }

            // Add a sub-menu item with "Flush Cache" button
            $wp_admin_bar->add_menu(
                array(
                    'parent' => 'serve_static_cache_size', // Attach to the cache size menu item
                    'id' => 'serve_static_flush_cache',
                    'title' => 'Flush All Cache',
                    'href' => esc_url($flush_cache_url),
                )
            );

            // Add a sub-menu item with "Regenerate Cache" button
            $wp_admin_bar->add_menu(
                array(
                    'parent' => 'serve_static_cache_size', // Attach to the cache size menu item
                    'id' => 'serve_static_warm_cache',
                    'title' => 'Regenerate Cache',
                    'href' => esc_url($warm_cache_url),
                )
            );

            if ( get_transient('serve_static_cache_warming_in_progress') ){
                $wp_admin_bar->add_menu(
                    array(
                        'parent' => 'serve_static_cache_size', // Attach to the cache size menu item
                        'id' => 'serve_static_stop_warming',
                        'title' => 'Stop Cache Regeneration',
                        'href' => esc_url(admin_url('admin.php?page=serve_static_settings#stopwarming')),
                    )
                );
            }
        }
    }

    // Function to get the size of a directory
    private function get_directory_size($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->get_directory_size($each);
        }

        return $size;
    }


    public function SettingsPage() {
        $warming_in_progress = get_transient('serve_static_cache_warming_in_progress');
        $urls = get_option('serve_static_urls', array());
        $make_static = get_option('serve_static_make_static', false);
        $manual_entry = get_option('serve_static_manual_entry', false);
        $html_minify_enabled = get_option('serve_static_html_minify_enabled', false);
        $css_minify_enabled = get_option('serve_static_css_minify_enabled', false);
        $js_minify_enabled = get_option('serve_static_js_minify_enabled', false);
        $cron_time = get_option('serve_static_cron_time', 'no-flush');
        $post_types_static = get_option('serve_static_post_types_static', false);
        $specific_post_types = get_option('serve_static_specific_post_types', [
            'page' => 'page',
            'post' => 'post'
        ]);
        $master_key = get_option('serve_static_master_key', false);
        $always_exclude_urls = get_option( 'serve_static_exclude_urls', array() );
        $warm_on_save = get_option('serve_static_warm_on_save', false);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Serve Static Settings</h1>
            <?php if (isset($warming_in_progress) && $warming_in_progress !== false){ ?>
            <div>
                <form method="post" action="">
                    <?php wp_nonce_field('reset_cache_warming_data', 'reset_cache_warming_nonce'); ?>
                    <button type="submit" name="stopwarming" id="stopwarming" style="background-color: black; width: 500px; height: 50px;" onMouseOver="this.style.backgroundColor='red'" onMouseOut="this.style.backgroundColor='black'" class="button button-primary">Stop Cache Warming</button>
                    <br><em style="margin-left: 60px;"><b>(Only use if you feel the process has been stuck for long time)</b></em>
                </form>
            </div>
            <?php } ?>
            <form method="post" action="">
                <?php wp_nonce_field('serve_static_update_options', 'serve_static_update_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Plugin Functionality</th>
                        <td>
                            <label>
                                <input type="checkbox" name="serve_static_master_key" <?php checked($master_key, true); ?>>
                                Enable Plugin Functionality
                                </br><p>The plugin will only function when this checkbox is checked. <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#enable-functionality')) ?>">Learn More</a></p>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Static Method</th>
                        <td>
                            <label>
                                <input type="radio" name="serve_static_entry_type" value="all" <?php checked($make_static && !$manual_entry, true); ?> required>
                                Make all pages as Static (<em>Upon choosing this option, all the pages/posts/any custom post types will be made static. <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#all-static')) ?>">Learn More</a></em>)
                                </br>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="serve_static_entry_type" value="post_types" <?php checked($post_types_static, true); ?> required>
                                Make specific post types Static (<em>Enter the post type names to make static separated by commas. eg "post, page". <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#all-static')) ?>">Learn More</a></em>)
                            </label>
                            <br><br>
                            <label>
                                <input type="text" name="serve_static_specific_post_types" value="<?php echo esc_attr(implode(', ', array_keys($specific_post_types))); ?>" <?php echo ($make_static || $manual_entry || !$post_types_static) ? 'disabled' : ''; ?> style="width: 300px;" required>
                            </label>
                            <br><br>
                            <label>
                                <input type="radio" name="serve_static_entry_type" value="manual" <?php checked($manual_entry, true); ?> required>
                                Enter URLs manually to make them static </br>(<em>Enter the full URLs you want to make static. One in one line. eg: "https://wp-develop.local/my-page" <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#all-static')) ?>">Learn More</a></em>)
                            </label>
                            <br><br>
                            <label>
                                <textarea id="serve_static_urls" name="serve_static_urls" rows="5" cols="50" <?php echo ($make_static && !$manual_entry) ? 'disabled' : ''; ?>
                                required><?php echo esc_textarea(implode("\n", array_keys($urls))); ?></textarea>
                            </label>
                            <br><br>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Minification</th>
                        <td>
                            <label>
                                <input type="checkbox" name="serve_static_html_minify_enabled" <?php checked($html_minify_enabled, true); ?>>
                                Enable HTML Minify &nbsp;&nbsp; (<em>Enable to Minify the cached HTML copy <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#minification')) ?>">Learn More</a></em>)
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="serve_static_css_minify_enabled" <?php checked($css_minify_enabled, true); ?>>
                                Enable CSS Minify &nbsp;&nbsp; (<em>Enable to Minify the CSS of your Static webpages <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#minification')) ?>">Learn More</a></em>)
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="serve_static_js_minify_enabled" <?php checked($js_minify_enabled, true); ?>>
                                Enable JS Minify &nbsp;&nbsp; (<em>Enable to Minify the Javascript of your Static webpages <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#minification')) ?>">Learn More</a></em>)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Always Exclude URLs</th>
                        <td>
                        <label>
                            <textarea id="serve_static_exclude_urls" name="serve_static_exclude_urls" rows="5" cols="50"><?php
                                // Loop through each URL in the array
                                foreach ($always_exclude_urls as $url => $value) {
                                    // Display the URL key as the description
                                    echo esc_html($url) . "\n";
                                }
                            ?></textarea>
                            <p>Enter the full URLs you want to exclude from making static. One in one line. eg: "https://wp-develop.local/my-page" <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#always-exclude-urls')) ?>">Learn More</a></p>
                        </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cron Time</th>
                        <td>
                            <select name="serve_static_cron_time" id="serve_static_cron_time">
                                <option value="no_flush" <?php selected($cron_time, 'no_flush'); ?>>Do not Flush regularly
                                </option>
                                <option value="hourly" <?php selected($cron_time, 'hourly'); ?>>Once Hourly</option>
                                <option value="twicedaily" <?php selected($cron_time, 'twicedaily'); ?>>Twice Daily</option>
                                <option value="daily" <?php selected($cron_time, 'daily'); ?>>Once Daily</option>
                                <option value="weekly" <?php selected($cron_time, 'weekly'); ?>>Once Weekly</option>
                            </select>
                            <p>Select cron time to Flush and regenerate cache on a regular basis. <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#cron-time')) ?>">Learn More</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Flush & Warm on Save</th>
                        <td>
                            <label>
                                <input type="checkbox" name="serve_static_warm_on_save" <?php checked($warm_on_save, true); ?>>
                                Flush and Regenerate cache on Save
                                <p>Check this option if you want to Flush and Save the Cache each time you Save the Settings of the plugin <a href="<?php echo esc_url(admin_url('admin.php?page=serve_static_guide#flush-warm-on-save')) ?>">Learn More</a></p>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php
                $is_disabled = !empty($warming_in_progress) ? 'disabled' : '';
                ?>
                <p>
                    <input type="submit" name="submit" class="button button-primary" value="Save Settings" <?php echo esc_attr($is_disabled); ?>>
                </p>
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var manualEntryRadio = document.querySelector('input[name="serve_static_entry_type"][value="manual"]');
                    var allPagesRadio = document.querySelector('input[name="serve_static_entry_type"][value="all"]');
                    var postTypesRadio = document.querySelector('input[name="serve_static_entry_type"][value="post_types"]');
                    var urlsTextarea = document.getElementById('serve_static_urls');
                    var specificPostTypesInput = document.querySelector('input[name="serve_static_specific_post_types"]');
                    var excludeUrlsTextarea = document.getElementById('serve_static_exclude_urls');

                    // Function to handle disabling based on selected radio button
                    function handleDisabledState() {
                        urlsTextarea.disabled = allPagesRadio.checked || postTypesRadio.checked;
                        specificPostTypesInput.disabled = !postTypesRadio.checked;
                        excludeUrlsTextarea.disabled = manualEntryRadio.checked; // Disable when manual entry is selected
                    }

                    // Initial setup based on saved options
                    handleDisabledState();

                    // Disable both text fields if no radio button is selected initially
                    if (!manualEntryRadio.checked && !allPagesRadio.checked && !postTypesRadio.checked) {
                        urlsTextarea.disabled = true;
                        specificPostTypesInput.disabled = true;
                    }

                    // Toggle the disabled state of the text fields based on radio button selection
                    manualEntryRadio.addEventListener('change', handleDisabledState);
                    allPagesRadio.addEventListener('change', handleDisabledState);
                    postTypesRadio.addEventListener('change', handleDisabledState);
                });
            </script>
            <br><div class="postbox" style="border: 1px solid black;">
                <h4 style="margin-left: 10px;">
                <?php
                    _e('I have spent many of my hours with this project so that you can serve a Static website. A <a href="https://wordpress.org/support/plugin/serve-static/reviews/?filter=5#new-post" target="_blank" style="color: #ffba00;">&#9733;&#9733;&#9733;&#9733;&#9733;</a> review will motivate me a lot.', 'serve-static'); //phpcs:ignore 
                ?>
                </h4>
            </div>
        </div>
        <?php
    }

    public function SettingsSave() {

        if ( isset( $_POST[ 'stopwarming' ] ) ){
            if (!isset($_POST['reset_cache_warming_nonce']) || !wp_verify_nonce($_POST['reset_cache_warming_nonce'], 'reset_cache_warming_data')) {
                return;
            }

            delete_transient( 'serve_static_cache_warming_in_progress' );
            delete_transient( 'serve_static_initial' );
            wp_unschedule_hook('custom_warmup_cache_cron');
            wp_safe_redirect($_SERVER['HTTP_REFERER']);
        }

        if (isset($_POST['submit'])) {
            // Verify nonce
            if (!isset($_POST['serve_static_update_nonce']) || !wp_verify_nonce($_POST['serve_static_update_nonce'], 'serve_static_update_options')) {
                return;
            }

            if ( isset( $_POST[ 'serve_static_master_key' ] ) ) {
                update_option('serve_static_master_key', 1);
            } else {
                update_option('serve_static_master_key', 0);
            }

            // Sanitize and save entry type
            if (isset($_POST['serve_static_entry_type']) && $_POST['serve_static_entry_type'] === 'manual') {
                update_option('serve_static_manual_entry', 1);
                update_option('serve_static_make_static', 0);
                update_option('serve_static_post_types_static', 0);
            }

            if (isset($_POST['serve_static_entry_type']) && $_POST['serve_static_entry_type'] === 'all') {
                update_option('serve_static_make_static', 1);
                update_option('serve_static_manual_entry', 0);
                update_option('serve_static_post_types_static', 0);
                $warmupnow = 1;
            }

            if (isset($_POST['serve_static_entry_type']) && $_POST['serve_static_entry_type'] === 'post_types') {
                update_option('serve_static_post_types_static', 1);
                update_option('serve_static_make_static', 0);
                update_option('serve_static_manual_entry', 0);
            
                // Assuming $postTypesArray contains the specific post types entered by the user
                $postTypesArray = explode(',', sanitize_text_field($_POST['serve_static_specific_post_types']));
                $postTypesArray = array_map('trim', $postTypesArray);

                // Initialize an empty array to store the post types as keys
                $postTypesFormatted = [];

                // Add each post type as a key in the array
                foreach ($postTypesArray as $postType) {
                    // Use the post type as the key and set the value to true
                    $postTypesFormatted[$postType] = true;
                }

                update_option('serve_static_specific_post_types', $postTypesFormatted);
                $warmupnow = 1;
            }

            // Save minification options
            $html_minify_enabled = isset($_POST['serve_static_html_minify_enabled']) ? 1 : 0;
            $css_minify_enabled = isset($_POST['serve_static_css_minify_enabled']) ? 1 : 0;
            $js_minify_enabled = isset($_POST['serve_static_js_minify_enabled']) ? 1 : 0;
            update_option('serve_static_html_minify_enabled', $html_minify_enabled);
            update_option('serve_static_css_minify_enabled', $css_minify_enabled);
            update_option('serve_static_js_minify_enabled', $js_minify_enabled);

            if (!isset($_POST['serve_static_make_static']) && !isset($_POST['serve_static_post_types_static'])) {
                // Sanitize and save URLs
                if (isset($_POST['serve_static_urls']) || isset($_POST['serve_static_manual_entry'])) {
                    $urls = explode("\n", sanitize_textarea_field($_POST['serve_static_urls']));
                    $urls = array_map('esc_url_raw', $urls);
            
                    // Initialize an empty array to store the URLs as keys
                    $urls_formatted = [];
            
                    // Add each URL as a key in the array
                    foreach ($urls as $url) {
                        // Trim any leading or trailing whitespace from the URL
                        $url = trim($url);
                        $url = rtrim($url, '*');
            
                        // Add a trailing slash to the URL if it doesn't already exist
                        if (substr($url, -1) !== '/') {
                            $url .= '/';
                        }
            
                        // Use the URL as the key and set the value to true
                        $urls_formatted[$url] = true;
                    }
            
                    // Update the option with the formatted array
                    update_option('serve_static_urls', $urls_formatted);
            
                    $warmupnow = 1;
                }
            }
            
            if (isset($_POST['serve_static_html_minify_enabled']) || isset($_POST['serve_static_css_minify_enabled']) || isset($_POST['serve_static_js_minify_enabled'])) {
                $warmupnow = 1;
            }

            // if ( isset($_POST['serve_static_exclude_urls']) ) {
            //     $excluded_urls = explode("\n", sanitize_textarea_field($_POST['serve_static_exclude_urls']));
            //     $excluded_urls = array_map('esc_url_raw', $excluded_urls);

            //     // Add a trailing slash to URLs if it doesn't already exist
            //     foreach ($excluded_urls as &$excluded_url) {
            //         if (substr($excluded_url, -1) !== '/') {
            //             $excluded_url .= '/';
            //         }
            //     }
            //     update_option('serve_static_exclude_urls', $excluded_urls);
            //     $warmupnow = 1;
            // }

            //Exclude urls.
            if (isset($_POST['serve_static_exclude_urls'])) {
                $excluded_urls = explode("\n", sanitize_textarea_field($_POST['serve_static_exclude_urls']));
            
                // Initialize an empty array to store the URLs as keys
                $excluded_urls_formatted = [];
            
                // Add each URL as a key in the array
                foreach ($excluded_urls as $url) {
                    // Trim any leading or trailing whitespace from the URL
                    $url = trim($url);
                    
                    // Add a trailing slash to the URL if it doesn't already exist
                    if (substr($url, -1) !== '/') {
                        $url .= '/';
                    }
            
                    // Use the URL as the key and set the value to true
                    $excluded_urls_formatted[$url] = true;
                }
            
                // Update the option with the formatted array
                update_option('serve_static_exclude_urls', $excluded_urls_formatted);
                $warmupnow = 1;
            } 

            // Save the value of "Flush and Warm cache on Save" checkbox
            if ( isset($_POST['serve_static_warm_on_save']) ){
                update_option('serve_static_warm_on_save', 1);
            } else {
                $warmupnow = 0;
                update_option('serve_static_warm_on_save', 0);
            }
            // $warmupnow = isset($_POST['serve_static_warm_on_save']) ? 1 : 0;
            // update_option('serve_static_warm_on_save', $warmupnow);

            if (isset($warmupnow) && $warmupnow == 1) {
                // Schedule warmup and flush rewrite rules
                $flush = new StaticServe();
                $flush->Flush();

                $warmup = new WarmUp();
                $warmup->ScheduleWarmup();
                // flush_rewrite_rules();
            }

            // Show success notice
            add_action('admin_notices', array($this, 'AdminSuccessSaved'));
        }

        //Flush URL Cache.
        if (isset($_GET['action']) && $_GET['action'] === 'flush_url' && isset($_GET['flush_url_nonce'])) {
            
            $nonce = sanitize_text_field(wp_unslash($_GET['flush_url_nonce']));
            if (wp_verify_nonce($nonce, 'serve_static_flush_url_action')) {
                $current_url = sanitize_url(urldecode($_GET['url']));

                $flush = new StaticServe();
                $flush->Flush($current_url);

                $warmup = new WarmUp();
                $warmup->WarmItUp($current_url);

                wp_safe_redirect($current_url);
                exit; // Exit to prevent further execution
            } else {
                wp_die('Nonce verification failed');
            }
        }

        //Flush Cache.
        if (isset($_GET['action']) && $_GET['action'] === 'flush_cache' && isset($_GET['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (wp_verify_nonce($nonce, 'serve_static_flush_cache')) {
                $flush = new StaticServe();
                $flush->Flush();
            }
            wp_safe_redirect(admin_url('admin.php?page=serve_static_settings&action=flush_cache'));
        }

        if (isset($_GET['action']) && $_GET['action'] === 'flush_cache') {
            add_action('admin_notices', array($this, 'AdminSuccessCacheCleared'));
        }

        //Warm Cache.
        if (isset($_GET['action']) && $_GET['action'] === 'warm_cache' && isset($_GET['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (wp_verify_nonce($nonce, 'serve_static_warm_cache')) {

                $flush = new StaticServe();
                $flush->Flush();

                $warmup = new WarmUp();
                $warmup->ScheduleWarmup();
                flush_rewrite_rules();
            }
            wp_safe_redirect(admin_url('admin.php?page=serve_static_settings&action=warm_cache'));
        }

    }

    public function AdminSuccessSaved()
    {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php esc_html_e('Settings saved.', 'serve-static'); ?>
            </p>
        </div>
        <?php
    }

    public function AdminSuccessCacheCleared()
    {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php esc_html_e('Cache cleared! Kindly Regenerate the cache to re-build the cache copies. Please note that visiting as and Administrator, Subscriber, or even ot-logged in user won\'t create any cache. Cache can only be generated using the "Regenerate Cache" function.', 'serve-static'); ?>
            </p>
        </div>
        <?php
    }

    public function AdminSuccessCacheWarmed()
    {
        if (get_transient('serve_static_cache_warming_in_progress')) {
            ?>
            <div class="notice notice-success">
                <p>
                    <?php
                    printf(
                        // Translators: Message of Cache regenerating.
                        esc_html__('Static Cache regeneration in progress... This message will disappear after all the cache is fully generated! Reload the page to check for progress. If you think this process has been stuck for long time, you can %s.', 'serve-static'),
                        '<a href="' . esc_url(admin_url('admin.php?page=serve_static_settings#stopwarming')) . '">' . esc_html__('stop the Warming by clicking here', 'serve-static') . '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

}

$activate = new Activate();
add_action('admin_menu', array($activate, 'Admin'));
add_action('admin_init', array($activate, 'SettingsSave'));
add_action('admin_bar_menu', array($activate, 'AddToolbarMenu'), 999);
add_action('admin_notices', array($activate, 'AdminSuccessCacheWarmed'));


