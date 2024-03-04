<?php
/**
 * Class Test_Activate_Class
 *
 * @package Serve Static
 */

/**
 * Sample test case.
 */
class Test_Activate_Class extends WP_UnitTestCase {
	public function set_up() {
        parent::set_up();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user = wp_set_current_user( $user_id );

		// This is the key here.
		set_current_screen( 'edit-post' );
        
        $this->activate = new Activate();
        $this->admin = new Admin();
    }

    public function tear_down() {
        parent::tear_down();
    }

    public function test_check_htaccess(){
        global $wp_filesystem;
        $htaccess = $wp_filesystem->get_contents(ABSPATH . '.htaccess');

        $begin = '# BEGIN Serve Static Cache';
        $end = '# END Serve Static Cache';

        $this->assertFalse(strpos($htaccess, $begin) === false && strpos($htaccess, $end) === false);

    }

    // Test Admin() method to check if menu page and submenu page are added
    public function test_admin_menu_pages_added() {
        // Set up the admin environment
        global $menu, $submenu;

        // Call the Admin method
        $this->activate->Admin();

        // Check if the main menu page is added
        $this->assertContains('serve_static_settings', array_column($menu, 2));

        // Check if the submenu page is added
        $this->assertArrayHasKey('serve_static_settings', $submenu);

        // Check submenu page properties
        $this->assertEquals('Settings', $submenu['serve_static_settings'][0][0]); // Submenu title
        $this->assertEquals('manage_options', $submenu['serve_static_settings'][0][1]); // Required capability
        $this->assertEquals('serve_static_settings', $submenu['serve_static_settings'][0][2]); // Menu slug
    }

    public function test_guide_sub_menu_added() {
        // Set up the admin environment
        global $submenu;

        // Call the guide_sub_menu method
        $this->admin->guide_sub_menu();

        // Check if the submenu page is added
        $this->assertArrayHasKey('serve_static_settings', $submenu);
        $this->assertContains('serve_static_guide', array_column($submenu['serve_static_settings'], 2));

        // Check submenu page properties
        $this->assertEquals('Guide', $submenu['serve_static_settings'][1][0]); // Submenu title
        $this->assertEquals('manage_options', $submenu['serve_static_settings'][1][1]); // Required capability
        $this->assertEquals('serve_static_guide', $submenu['serve_static_settings'][1][2]); // Menu slug
    }
}