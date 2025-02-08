<?php

namespace ServeStatic\Classes;

if ( ! defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly.
}

class Migrate {

    /**
     * Serve Static DB version (Version saved in DB).
     */
    public $db_version;

    /**
     * Serve Static Live version (Actual version).
     */
    public $plugin_version;

    /**
     * Constructor.
     */
    public function __construct() {
        // Get the version from the DB.
        $this->db_version = get_option( 'serve_static_db_version' );

        // Get the version from the plugin.
        if( ! function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $main_plugin_file = dirname(__DIR__) . '/serve-static.php';
        // Get the plugin data
        $plugin_data = get_plugin_data($main_plugin_file);
        $this->plugin_version = $plugin_data[ 'Version' ];

        // Only hook if DB version and plugin version are different.
        if ( $this->db_version != $this->plugin_version ) {
            add_action( 'admin_init', array( $this, 'migrate_from_v2_0' ), 99 );
            add_action( 'admin_init', array( $this, 'migrate_from_v2_1' ), 99 );
        }
    }

    // Setup DB version for migrations.
    public function setup_db_version() {
        $serve_static_db_version = update_option( 'serve_static_db_version', $this->plugin_version );
        // Show Admin Notice.
        set_transient( 'serve_static_db_is_updated_notice', true );
    }

    public function migrate_from_v2_0() {
        if ( ! $this->db_version || 2.0 < $this->plugin_version ) {

            // Remove the rules by running deactivate function.
            serve_static_deactivate();

            // Rewrite the Rules
            $activate = new Activate();
            $rules = $activate->rules();

            // Delete the unneccessary html-cache folder.
            global $wp_filesystem;
            $wp_filesystem->rmdir( WP_CONTENT_DIR . '/html-cache', true);

            // Create or update DB version.
            $this->setup_db_version();
        }
    }

    public function migrate_from_v2_1() {
        if ( ! $this->db_version || 2.0 == $this->db_version || 2.1 < $this->plugin_version ) {

            $cache_dir = WP_CONTENT_DIR . '/serve-static-cache';

            $cache_htaccess_rules = [
                ''
            ];
            $cache_htaccess = implode(PHP_EOL, $cache_htaccess_rules) . PHP_EOL;

            // Empty the unneccessary .htaccess in serve-static-cache folder.
            global $wp_filesystem;
            $cache_htaccess = $wp_filesystem->put_contents($cache_dir . '/' . '.htaccess', $cache_htaccess, FS_CHMOD_FILE);

            if ( ! isset($cache_htaccess) || $cache_htaccess != 1 ){
                set_transient( 'serve_static_htaccess_not_writable', 1 );
            } elseif ( $cache_htaccess == 1 ) {
                delete_transient( 'serve_static_htaccess_not_writable' );
            }

            // Create or update DB version.
            $this->setup_db_version();
        }
    }

}

add_action( 'init', function() {
    new Migrate();
});
