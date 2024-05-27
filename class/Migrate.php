<?php

namespace ServeStatic\Class;

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

}

$migrate = new Migrate();
