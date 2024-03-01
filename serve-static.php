<?php
/**
 * Plugin Name: Serve Static
 * Description: Cache and serve HTML copies of your webpages. Avoid the PHP hit on any page load, and deploy pages fully static on your server.
 * Version: 1.0.1
 * Author: Rajin Sharwar
 * Author URI: https://linkedin.com/in/rajinsharwar
 * License: GPLv2
 */

namespace ServeStatic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Initialize the WP_Filesystem
if ( ! function_exists( 'WP_Filesystem' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

WP_Filesystem();

require_once __DIR__.'/class/Activate.php';
require_once __DIR__.'/admin/Admin.php';
require_once __DIR__.'/class/StaticServe.php';
require_once __DIR__.'/class/WarmUp.php';
require_once __DIR__.'/class/Triggers.php';
require_once __DIR__.'/class/Cron.php';
require_once __DIR__.'/class/Server.php';

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
        exit( wp_safe_redirect(esc_url(admin_url('admin.php?page=serve_static_guide')))); //phpcs:ignore
    }
}
add_action( 'activated_plugin', 'serve_static_activate' );

register_deactivation_hook( __FILE__, 'deactivateServeStatic' );

// add_action('admin_init', 'deactivateServeStatic');
// function deactivateServeStatic(){

//     global $wp_filesystem;

//     // Check if the .htaccess file exists
//     $htaccess_path = ABSPATH . '.htaccess';
//     if (file_exists($htaccess_path)) {
//         // Read the content of the .htaccess file
//         $htaccess_content = $wp_filesystem->get_contents($htaccess_path);

//         // Define the begin and end markers
//         $begin_marker = '# BEGIN Serve Static Cache';
//         $end_marker = '# END Serve Static Cache';

//         // Check if the markers exist in the .htaccess content
//         $begin_pos = strpos($htaccess_content, $begin_marker);
//         $end_pos = strpos($htaccess_content, $end_marker);
//         error_log($end_pos);

//         if ($begin_pos !== false && $end_pos !== false) {
//             // Remove the block of rules from the .htaccess content
//             $rules_to_remove = substr($htaccess_content, $begin_pos, ($end_pos + strlen($end_marker)) - $begin_pos );
//             $htaccess_content = str_replace($rules_to_remove, '', $htaccess_content);

//             // Write the modified content back to the .htaccess file
//             $wp_filesystem->put_contents($htaccess_path, $htaccess_content);
//         }
//     }
// }

function deactivateServeStatic(){

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
