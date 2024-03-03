<?php
/**
 * Class Test_Starter_Plugin
 *
 * @package Starter_Plugin
 */

/**
 * Sample test case.
 */
class Test_Server_Class extends WP_UnitTestCase {
    public function setUp() {
        parent::setUp();

        // Set up any necessary objects here.
    }

    public function tearDown() {
        parent::tearDown();

        // Tear down any necessary objects here.
    }

    // Test is_apache() method
    public function test_is_apache() {
        // Instantiate the Server class
        $server = new Server();

        // Mock the $_SERVER global variable
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.18';

        // Test if server is detected as Apache
        $this->assertTrue($server->is_apache());

        // Clean up
        unset($_SERVER['SERVER_SOFTWARE']);
    }

    // Test is_litespeed() method
    public function test_is_litespeed() {
        // Instantiate the Server class
        $server = new Server();

        // Mock the $_SERVER global variable
        $_SERVER['SERVER_SOFTWARE'] = 'LiteSpeed';

        // Test if server is detected as LiteSpeed
        $this->assertTrue($server->is_litespeed());

        // Clean up
        unset($_SERVER['SERVER_SOFTWARE']);
    }

    // Test is_nginx() method
    public function test_is_nginx() {
        // Instantiate the Server class
        $server = new Server();

        // Mock the $_SERVER global variable
        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.18.0';

        // Test if server is detected as nginx
        $this->assertTrue($server->is_nginx());

        // Clean up
        unset($_SERVER['SERVER_SOFTWARE']);
    }

    // Test is_iis() method
    public function test_is_iis() {
        // Instantiate the Server class
        $server = new Server();

        // Mock the $_SERVER global variable
        $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';

        // Test if server is detected as IIS
        $this->assertTrue($server->is_iis());

        // Clean up
        unset($_SERVER['SERVER_SOFTWARE']);
    }
	// public function set_up() {
    //     parent::set_up();
        
    //     // Mock that we're in WP Admin context.
	// 	// See https://wordpress.stackexchange.com/questions/207358/unit-testing-in-the-wordpress-backend-is-admin-is-true
    //     set_current_screen( 'edit-post' );
        
    //     $this->starter_plugin = new Starter_Plugin();
    // }

    // public function tear_down() {
    //     parent::tear_down();
    // }

	// public function test_has_correct_token() {
	// 	$has_correct_token = ( 'starter-plugin' === $this->starter_plugin->token );
		
	// 	$this->assertTrue( $has_correct_token );
	// }

	// public function test_has_admin_interface() {
	// 	$has_admin_interface = ( is_a( $this->starter_plugin->admin, 'Starter_Plugin_Admin' ) );
		
	// 	$this->assertTrue( $has_admin_interface );
	// }

	// public function test_has_settings_interface() {
	// 	$has_settings_interface = ( is_a( $this->starter_plugin->settings, 'Starter_Plugin_Settings' ) );
		
	// 	$this->assertTrue( $has_settings_interface );
	// }

	// public function test_has_post_types() {
	// 	$has_post_types = ( 0 < count( $this->starter_plugin->post_types ) );
		
	// 	$this->assertTrue( $has_post_types );
	// }

	// public function test_has_load_plugin_textdomain() {
	// 	$has_load_plugin_textdomain = ( is_int( has_action( 'init', [ $this->starter_plugin, 'load_plugin_textdomain' ] ) ) );
		
	// 	$this->assertTrue( $has_load_plugin_textdomain );
	// }
}