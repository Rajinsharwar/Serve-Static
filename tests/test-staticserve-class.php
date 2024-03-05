<?php
/**
 * Class Test_StaticServe_Class
 *
 * @package Serve Static
 */

/**
 * Sample test case.
 */
class Test_StaticServe_Class extends WP_UnitTestCase {
    public function set_up() {
        parent::set_up();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user = wp_set_current_user( $user_id );

		// This is the key here.
		set_current_screen( 'edit-post' );

        // Mock the current URL
        $_SERVER['HTTP_HOST'] = 'wp-develop.local';
        $_SERVER['REQUEST_URI'] = '/';
        
		// Instantiate the StaticServe class
        $this->static_serve = new StaticServe();
    }

    public function tear_down() {
        parent::tear_down();
    }

    public function clean_up(){
        // Define the cache directory path
        $cache_dir = WP_CONTENT_DIR . '/html-cache';
    
        // Check if the cache directory exists
        if (is_dir($cache_dir)) {
            // Open the cache directory
            $dir_handle = opendir($cache_dir);
    
            // Loop through each file/directory in the cache directory
            while (($file = readdir($dir_handle)) !== false) {
                // Skip ".", "..", and any hidden files/directories
                if ($file != "." && $file != "..") {
                    // If it's a directory, recursively delete it
                    if (is_dir("$cache_dir/$file")) {
                        $this->clean_up("$cache_dir/$file");
                    } else {
                        // If it's a file, delete it
                        unlink("$cache_dir/$file");
                    }
                }
            }
    
            // Close the directory handle
            closedir($dir_handle);
    
            // Delete the directory itself
            rmdir($cache_dir);
        }
    }       
    
    public function test_build_denied(){
        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = '';

        $this->assertFalse($this->static_serve->Build());

        // Unset the mock request header
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
    }

    public function test_build_access(){
        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';

        $this->assertNull($this->static_serve->Build());

        // Unset the mock request header
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);

        ob_start();
        ob_end_flush();
        ob_get_clean();
    }

    public function test_make_all_static_exclude_urls_denied(){

        update_option( 'serve_static_make_static', '1' );

        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';
    
        // Update the option with the formatted array
        update_option('serve_static_exclude_urls', array('http://wp-develop.local/' => 1));

        $this->assertFalse($this->static_serve->Build());

        // Unset the mock request header
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
        delete_option( 'serve_static_exclude_urls' );
    }

    public function test_make_all_static_exclude_urls_access(){

        update_option( 'serve_static_make_static', '1' );

        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';
    
        // Update the option with the formatted array
        update_option('serve_static_exclude_urls', array('http://wp-develop.local/page1' => 1));

        $this->assertNull($this->static_serve->Build());
        $this->clean_up();

        ob_start();
        ob_end_flush();
        ob_get_clean();

        // Unset the mock request header
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
    }
}