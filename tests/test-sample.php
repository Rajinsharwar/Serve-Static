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
	public function set_up() {
        parent::set_up();
        
		// Instantiate the Server class
        $this->server = new Server();
    }

    public function tear_down() {
        parent::tear_down();
    }

    // Test is_apache() method
    public function test_is_apache() {

        // Mock the $_SERVER global variable
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.18';

        // Test if server is detected as Apache
        $this->assertTrue($this->server->is_apache());

        // Clean up
        unset($_SERVER['SERVER_SOFTWARE']);
    }

    // Test is_litespeed() method
    public function test_is_litespeed() {

        // Mock the $_SERVER global variable
        $_SERVER['SERVER_SOFTWARE'] = 'LiteSpeed';

        // Test if server is detected as LiteSpeed
        $this->assertTrue($this->server->is_litespeed());

        // Clean up
        unset($_SERVER['SERVER_SOFTWARE']);
    }

    // Test is_nginx() method
    public function test_is_nginx() {

        // Mock the $_SERVER global variable
        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.18.0';

        // Test if server is detected as nginx
        $this->assertTrue($this->server->is_nginx());

        // Clean up
        unset($_SERVER['SERVER_SOFTWARE']);
    }

    // Test is_iis() method
    public function test_is_iis() {

        // Mock the $_SERVER global variable
        $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';

        // Test if server is detected as IIS
        $this->assertTrue($this->server->is_iis());

        // Clean up
        unset($_SERVER['SERVER_SOFTWARE']);
    }

	// Test server_is_nginx() method
    public function test_server_is_nginx() {
        // Mock the get_transient function to return false
        $this->assertFalse(get_transient('serve_static_nginx_notice_dismissed'));

        // Set up output buffering to capture the output of server_is_nginx()
        ob_start();
        $this->server->server_is_nginx();
        $output = ob_get_clean();

        // Assert that the output contains the expected HTML content
        $this->assertContains('Looks like you are using a NGINX server.', $output);
    }

    // Test dismiss_nginx_server_notice() method
    public function test_dismiss_nginx_server_notice() {
        // Call the method to set the transient
        $this->server->dismiss_nginx_server_notice();

        // Assert that the transient is set
        $this->assertEquals(1, get_transient('serve_static_nginx_notice_dismissed'));
    }

    // Test missed_cron() method
    public function test_missed_cron() {
        // Mock the wp_next_scheduled function to return false
        $this->assertFalse(wp_next_scheduled('serve_static_cache_cron_event'));

        // Set up output buffering to capture the output of missed_cron()
        ob_start();
        $this->server->missed_cron();
        $output = ob_get_clean();

        // Assert that the output contains the expected HTML content
        $this->assertContains('The following scheduled event failed to run.', $output);
    }

    // Test cron_not_working() method
    public function test_cron_not_working() {
        // Set up the transient to trigger the notice
        set_transient('serve_static_cron_not_working_notice_dismissed', false);

        // Set up output buffering to capture the output of cron_not_working()
        ob_start();
        $this->server->cron_not_working();
        $output = ob_get_clean();

        // Assert that the output contains the expected HTML content
        $this->assertContains('The following scheduled event failed to run.', $output);
    }

    // Test cron_not_scheduled() method
    public function test_cron_not_scheduled() {
        // Mock the wp_next_scheduled function to return false
        $this->assertFalse(wp_next_scheduled('custom_warmup_cache_cron'));

        // Set up the method to trigger the notice
        $this->server->cron_not_scheduled();

        // Check if the notice is added to admin_notices
        $this->assertTrue(has_action('admin_notices', array($this->server, 'cron_not_scheduled_notice')));
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