<?php
/**
 * Plugin Name: Serve Static - Automatic WordPress Static Page generator
 * Description: Cache and serve HTML copies of your webpages. Avoid the PHP hit on any page load, and deploy pages fully static on your server.
 * Version: 2.4
 * Author: Rajin Sharwar
 * Author URI: https://linkedin.com/in/rajinsharwar
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Initialize the WP_Filesystem
if ( ! function_exists( 'WP_Filesystem' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

if ( ! function_exists( 'serve_static_analytics' ) ) {

    function serve_static_analytics() {
        global $serve_static_analytics;

        if ( ! isset( $serve_static_analytics ) ) {

            require_once dirname(__FILE__) . '/vendor/freemius/wordpress-sdk/start.php';

            $serve_static_analytics = fs_dynamic_init( array(
                'id'                  => '15144',
                'slug'                => 'serve_static',
                'type'                => 'plugin',
                'public_key'          => 'pk_64d60e26e8cbab86074543b09964d',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'serve_static_settings',
                    'first-path'     => 'admin.php?page=serve_static_warmer',
                    'account'        => false,
                ),
            ) );
        }

        return $serve_static_analytics;
    }

    serve_static_analytics();
    do_action( 'serve_static_analytics_loaded' );
}

function serve_static_analytics_uninstall_cleanup(){
    //Options
    delete_option( 'serve_static_master_key' );
    delete_option( 'serve_static_post_types_static' );
    delete_option( 'serve_static_make_static' );
    delete_option( 'serve_static_manual_entry' );
    delete_option( 'serve_static_specific_post_types' );
    delete_option( 'serve_static_js_minify_enabled' );
    delete_option( 'serve_static_css_minify_enabled' );
    delete_option( 'serve_static_html_minify_enabled' );
    delete_option( 'serve_static_urls' );
    delete_option( 'serve_static_exclude_urls' );
    delete_option( 'serve_static_warm_on_save' );
    delete_option( 'serve_static_cron_time' );

    //Transients
    $transients_to_delete = array(
        'serve_static_htaccess_not_writable',
        'serve_static_cron_not_running',
        'serve_static_nginx_notice_dismissed',
        'serve_static_apache_notice_dismissed',
        'serve_static_cron_notice_dismissed',
        'serve_static_wp_cron_notice_dismissed',
        'serve_static_cron_not_working_notice_dismissed',
        'serve_static_cron_not_scheduled_notice_dismissed',
        'serve_static_plugin_modified_notice',
        'serve_static_initial',
        'serve_static_cache_warming_in_progress'
    );

    foreach ($transients_to_delete as $transient) {
        delete_transient($transient);
    }
}

serve_static_analytics()->add_action('after_uninstall', 'serve_static_analytics_uninstall_cleanup');

WP_Filesystem();

require_once __DIR__.'/class/Activate.php';
require_once __DIR__.'/admin/Admin.php';
require_once __DIR__.'/admin/Warmer.php';
require_once __DIR__.'/class/StaticServe.php';
require_once __DIR__.'/class/WarmUp.php';
require_once __DIR__.'/class/WarmUpAjax.php';
require_once __DIR__.'/class/Triggers.php';
require_once __DIR__.'/class/Cron.php';
require_once __DIR__.'/class/Server.php';
require_once __DIR__.'/class/Migrate.php';

function serve_static_activate( $plugin ) {

    if ( is_network_admin() ) {
		return false;
	}

	// Skip redirect if WP_DEBUG is enabled.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
		return false;
	}

	// Determine if multi-activation is enabled.
    if (
		( isset( $_REQUEST['action'] ) && 'activate-selected' === $_REQUEST['action'] ) &&
		( isset( $_POST['checked'] ) && count( $_POST['checked'] ) > 1 ) ) {
		return;
	}
    
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_safe_redirect(esc_url(admin_url('admin.php?page=serve_static_settings')))); //phpcs:ignore
    }
}
add_action( 'activated_plugin', 'serve_static_activate' );

// Register Custom DB table.
register_activation_hook(__FILE__, 'serve_static_create_database_tables');

function serve_static_create_database_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'serve_static_warm_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        log_url text NOT NULL,
        log_status text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

function serve_static_deactivate(){

    global $wp_filesystem;

    // Check if the .htaccess file exists
    $htaccess_path = ABSPATH . '.htaccess';
    if (file_exists($htaccess_path)) {
        // Read the content of the .htaccess file
        $htaccess_content = $wp_filesystem->get_contents($htaccess_path);

        // Define the begin and end markers
        $begin_marker = '# BEGIN Serve Static Cache';
        $end_marker = '# END Serve Static Cache';

        // Check if the markers exist in the .htaccess content
        $begin_pos = strpos($htaccess_content, $begin_marker);
        $end_pos = strpos($htaccess_content, $end_marker);

        if ($begin_pos !== false && $end_pos !== false) {
            // Remove the block of rules from the .htaccess content
            $rules_to_remove = substr($htaccess_content, $begin_pos, ($end_pos + strlen($end_marker)) - $begin_pos );

            $htaccess_content = implode(PHP_EOL,
                array_filter(
                    array_map("trim",
                        explode(PHP_EOL, str_replace($rules_to_remove, '', $htaccess_content))
                    )
                )
            );

            // Write the modified content back to the .htaccess file
            $wp_filesystem->put_contents($htaccess_path, $htaccess_content);
        }
    }
}

//Deactivation Hook.
register_deactivation_hook( __FILE__, 'serve_static_deactivate' );