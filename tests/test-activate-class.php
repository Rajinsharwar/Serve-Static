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
        global $wp_filesystem;
        $wp_filesystem->mkdir($this->testDirectory);

        // Create some files in the test directory
        $wp_filesystem->put_contents($this->testDirectory . '/file1.txt', 'Hello, World!');
        $wp_filesystem->put_contents($this->testDirectory . '/file2.txt', 'This is a test.');

		// This is the key here.
		set_current_screen( 'edit-post' );
        
        $this->activate = new ServeStatic\Class\Activate();
        $this->admin = new ServeStatic\Class\Admin();
    }

    public function tear_down() {
        parent::tear_down();

        // Remove the temporary test directory
        $this->removeDirectory($this->testDirectory);
    }

    private function removeDirectory($dir) {
        if (is_dir($dir)) {
            global $wp_filesystem;

            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        $this->removeDirectory($dir . '/' . $object);
                    } else {
                        wp_delete_file($dir . '/' . $object);
                    }
                }
            }
            $wp_filesystem->rmdir($dir);
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

    public function test_settings_save_master_key_on(){
        $_POST['submit'] = 'Save';
        $_POST['serve_static_update_nonce'] = wp_create_nonce('serve_static_update_options');
        $_POST[ 'serve_static_master_key' ] = true;

        $this->assertNull( $this->activate->SettingsSave() );

        $this->assertEquals( 1, get_option( 'serve_static_master_key' ) );

        unset( $_POST['submit'] );
        unset( $_POST['serve_static_update_nonce'] );
        unset( $_POST['serve_static_master_key'] );
        delete_option( 'serve_static_master_key' );
    }

    public function test_settings_save_master_key_off(){
        $_POST['submit'] = 'Save';
        $_POST['serve_static_update_nonce'] = wp_create_nonce('serve_static_update_options');

        $this->assertNull( $this->activate->SettingsSave() );

        $this->assertEquals( 0, get_option( 'serve_static_master_key' ) );

        unset( $_POST['submit'] );
        unset( $_POST['serve_static_update_nonce'] );
        delete_option( 'serve_static_master_key' );
    }

    public function test_settings_save_entry_type_manual(){
        $_POST['submit'] = 'Save';
        $_POST['serve_static_update_nonce'] = wp_create_nonce('serve_static_update_options');
        $_POST[ 'serve_static_master_key' ] = true;
        $_POST['serve_static_entry_type'] = 'manual';

        $this->assertNull( $this->activate->SettingsSave() );

        $this->assertEquals( 1, get_option( 'serve_static_master_key' ) );

        $this->assertEquals( 1, get_option( 'serve_static_manual_entry' ) );
        $this->assertEquals( 0, get_option( 'serve_static_make_static' ) );
        $this->assertEquals( 0, get_option( 'serve_static_post_types_static' ) );

        unset( $_POST['submit'] );
        unset( $_POST['serve_static_update_nonce'] );
        unset( $_POST['serve_static_master_key'] );
        unset( $_POST['serve_static_entry_type'] );
        delete_option( 'serve_static_master_key' );
    }

    public function test_settings_save_entry_type_all(){
        $_POST['submit'] = 'Save';
        $_POST['serve_static_update_nonce'] = wp_create_nonce('serve_static_update_options');
        $_POST[ 'serve_static_master_key' ] = true;
        $_POST['serve_static_entry_type'] = 'all';

        $this->assertNull( $this->activate->SettingsSave() );

        $this->assertEquals( 1, get_option( 'serve_static_master_key' ) );

        $this->assertEquals( 0, get_option( 'serve_static_manual_entry' ) );
        $this->assertEquals( 1, get_option( 'serve_static_make_static' ) );
        $this->assertEquals( 0, get_option( 'serve_static_post_types_static' ) );

        unset( $_POST['submit'] );
        unset( $_POST['serve_static_update_nonce'] );
        unset( $_POST['serve_static_master_key'] );
        unset( $_POST['serve_static_entry_type'] );
        delete_option( 'serve_static_master_key' );
    }

    public function test_settings_save_entry_type_post_types(){
        $_POST['submit'] = 'Save';
        $_POST['serve_static_update_nonce'] = wp_create_nonce('serve_static_update_options');
        $_POST[ 'serve_static_master_key' ] = true;
        $_POST['serve_static_entry_type'] = 'post_types';
        $_POST['serve_static_specific_post_types'] = 'post';

        $this->assertNull( $this->activate->SettingsSave() );

        $this->assertEquals( 1, get_option( 'serve_static_master_key' ) );

        $this->assertEquals( 0, get_option( 'serve_static_manual_entry' ) );
        $this->assertEquals( 0, get_option( 'serve_static_make_static' ) );
        $this->assertEquals( 1, get_option( 'serve_static_post_types_static' ) );

        unset( $_POST['submit'] );
        unset( $_POST['serve_static_update_nonce'] );
        unset( $_POST['serve_static_master_key'] );
        unset( $_POST['serve_static_entry_type'] );
        unset( $_POST['serve_static_specific_post_types'] );
        delete_option( 'serve_static_master_key' );
        delete_option( 'serve_static_specific_post_types' );
    }


}