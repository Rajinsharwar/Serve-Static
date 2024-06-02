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

        global $wp_rewrite;

        // Define your custom permalink structure here
        $custom_permalink_structure = '/%postname%/';

        // Save permalinks to a custom setting
        update_option('permalink_structure', $custom_permalink_structure);

        // Flush and regenerate rewrite rules
        $wp_rewrite->set_permalink_structure($custom_permalink_structure);
        $wp_rewrite->flush_rules(true);
        
		// Instantiate the Triggers class
        $this->triggers = new ServeStatic\Class\Triggers();
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

        $cache_dir = WP_CONTENT_DIR . '/serve-static-cache/hello-world';
        // Ensure the cache directory exists
        global $wp_filesystem;

        if (!file_exists($cache_dir)) {
            global $wp_filesystem;
            mkdir($cache_dir, 0755, true); // phpcs:ignore
        }

        // Set the file path
        $file_path = $cache_dir . '/index.html';

        // Your content to be written to the file
        $file_content = '<html><body><p>This is the content of the new file.</p></body></html>';

        // Write content to the file
        $this->assertTrue($wp_filesystem->put_contents($file_path, $file_content));

        update_option('serve_static_make_static', 1);

        $this->assertFalse($this->is_dir_empty($cache_dir));
        $this->triggers->flush_on_post_save_all($post_id, $post_object, false);
        $this->assertTrue($this->is_dir_empty($cache_dir));

        wp_delete_post($post_id);
        delete_option( 'serve_static_make_static' );
    }

    public function test_regenerate_manual_entry(){

        $post_id = wp_insert_post(array(
            'post_title' => 'Hello World',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world1'
        ));

        $post_permalink = get_permalink($post_id);

        $post_object = get_post($post_id);

        $cache_dir = WP_CONTENT_DIR . '/serve-static-cache/hello-world1';
        // Ensure the cache directory exists
        global $wp_filesystem;

        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true); // phpcs:ignore
        }

        // Set the file path
        $file_path = $cache_dir . '/index.html';

        // Your content to be written to the file
        $file_content = '<html><body><p>This is the content of the new file.</p></body></html>';

        // Write content to the file
        $this->assertTrue($wp_filesystem->put_contents($file_path, $file_content));

        update_option('serve_static_manual_entry', 1);
        update_option('serve_static_urls', array($post_permalink => 1));

        $this->assertFalse($this->is_dir_empty($cache_dir));
        $this->triggers->flush_on_post_save_all($post_id, $post_object, false);
        $this->assertTrue($this->is_dir_empty($cache_dir));

        wp_delete_post($post_id);
        delete_option( 'serve_static_manual_entry' );
        delete_option( 'serve_static_urls' );
    }
}