<?php
/**
 * Class Test_Server_Class
 *
 * @package Serve Static
 */

/**
 * Sample test case.
 */
class Test_Server_Class extends WP_UnitTestCase {
	public function set_up() {
        parent::set_up();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user = wp_set_current_user( $user_id );

		// This is the key here.
		set_current_screen( 'edit-post' );
        
		// Instantiate the Server class
        $this->server = new ServeStatic\Classes\Server();
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
}