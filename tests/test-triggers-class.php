<?php
/**
 * Class Test_Triggers_Class
 *
 * @package Serve Static
 */

/**
 * Sample test case.
 */
class Test_Triggers_Class extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user = wp_set_current_user( $user_id );

		// This is the key here.
		set_current_screen( 'edit-post' );

        update_option( 'serve_static_master_key', 1 );
        
		// Instantiate the Triggers class
        $this->triggers = new Triggers();
    }

    public function tear_down() {
        parent::tear_down();
    }

    public function is_dir_empty($cache_dir){
        // Get the list of files and directories in the cache directory
        $directory_contents = scandir($cache_dir);

        // Remove . and .. from the list of directory contents
        $directory_contents = array_diff($directory_contents, array('.', '..'));

        // Check if the directory contents array is empty
        if (empty($directory_contents)) {
            // The directory is empty
            return true;
        } else {
            // The directory is not empty
            return false;
        }
    }

    public function test_regenerate_all(){

        $post_id = wp_insert_post(array(
            'post_title' => 'Hello World',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world'
        ));

        $post_object = get_post($post_id);

        $cache_dir = WP_CONTENT_DIR . '/html-cache/hello-world';
        // Ensure the cache directory exists
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }

        // Set the file path
        $file_path = $cache_dir . '/index.html';

        // Your content to be written to the file
        $file_content = '<html><body><p>This is the content of the new file.</p></body></html>';

        // Write content to the file
        $this->assertIsInt(file_put_contents($file_path, $file_content));

        update_option('serve_static_make_static', 1);

        $this->assertFalse($this->is_dir_empty($cache_dir));
        $this->triggers->flush_on_post_save_all($post_id, $post_object, false);
        $cache_dir = WP_CONTENT_DIR . '/html-cache';
        $this->assertTrue($this->is_dir_empty($cache_dir));
    }
}