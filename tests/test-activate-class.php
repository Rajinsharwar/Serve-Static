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

        // Create a temporary directory for testing
        $this->testDirectory = sys_get_temp_dir() . '/test_directory';
        mkdir($this->testDirectory);

        // Create some files in the test directory
        file_put_contents($this->testDirectory . '/file1.txt', 'Hello, World!');
        file_put_contents($this->testDirectory . '/file2.txt', 'This is a test.');

		// This is the key here.
		set_current_screen( 'edit-post' );
        
        $this->activate = new Activate();
        $this->admin = new Admin();
    }

    public function tear_down() {
        parent::tear_down();

        // Remove the temporary test directory
        $this->removeDirectory($this->testDirectory);
    }

    private function removeDirectory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        $this->removeDirectory($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function test_check_htaccess(){
        global $wp_filesystem;
        $htaccess = $wp_filesystem->get_contents(ABSPATH . '.htaccess');

        $begin = '# BEGIN Serve Static Cache';
        $end = '# END Serve Static Cache';

        $this->assertFalse(strpos($htaccess, $begin) === false && strpos($htaccess, $end) === false);

    }

    public function test_admin_menu_pages_added() {

        global $menu, $submenu;

        $this->activate->Admin();

        $this->assertContains('serve_static_settings', array_column($menu, 2));

        $this->assertArrayHasKey('serve_static_settings', $submenu);

        $this->assertEquals('Settings', $submenu['serve_static_settings'][0][0]); // Submenu title
        $this->assertEquals('manage_options', $submenu['serve_static_settings'][0][1]); // Required capability
        $this->assertEquals('serve_static_settings', $submenu['serve_static_settings'][0][2]); // Menu slug
    }

    public function test_guide_sub_menu_added() {

        global $submenu;

        $this->admin->guide_sub_menu();

        $this->assertArrayHasKey('serve_static_settings', $submenu);
        $this->assertContains('serve_static_guide', array_column($submenu['serve_static_settings'], 2));

        $this->assertEquals('Guide', $submenu['serve_static_settings'][1][0]); // Submenu title
        $this->assertEquals('manage_options', $submenu['serve_static_settings'][1][1]); // Required capability
        $this->assertEquals('serve_static_guide', $submenu['serve_static_settings'][1][2]); // Menu slug
    }

    public function test_get_directory_size() {

        // Call the method and get the size of the test directory
        $size = $this->activate->get_directory_size($this->testDirectory);

        // Calculate the expected size manually based on the files created
        $expectedSize = filesize($this->testDirectory . '/file1.txt') + filesize($this->testDirectory . '/file2.txt');

        // Assert that the calculated size matches the expected size
        $this->assertEquals($expectedSize, $size);
    }
}