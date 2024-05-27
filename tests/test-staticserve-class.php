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

        global $wp_rewrite; 

        //Write the rule
        $wp_rewrite->set_permalink_structure('/%postname%/'); 

        //Set the option
        update_option( "rewrite_rules", FALSE ); 

        //Flush the rules and tell it to write htaccess
        $wp_rewrite->flush_rules( true );
        
		// Instantiate the StaticServe class
        $this->static_serve = new ServeStatic\Class\StaticServe();
    }

    public function tear_down() {
        parent::tear_down();
    }

    public function clean_up(){
        // Define the cache directory path
        $cache_dir = WP_CONTENT_DIR . '/serve-static-cache';
    
        // Check if the cache directory exists
        if (is_dir($cache_dir)) {
            global $wp_filesystem;

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
                        wp_delete_file("$cache_dir/$file");
                    }
                }
            }
    
            // Close the directory handle
            closedir($dir_handle);
    
            // Delete the directory itself
            $wp_filesystem->rmdir($cache_dir);
        }
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

        // Mock the current URL
        $_SERVER['HTTP_HOST'] = 'wp-develop.local';
        $_SERVER['REQUEST_URI'] = '/';

        update_option( 'serve_static_make_static', '1' );

        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';
    
        // Update the option with the formatted array
        update_option('serve_static_exclude_urls', array('http://wp-develop.local/' => 1));

        $this->assertFalse($this->static_serve->Build());

        // Unset the mock request header
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
        delete_option( 'serve_static_exclude_urls' );
        delete_option( 'serve_static_make_static' );
    }

    public function test_make_all_static_exclude_urls_access(){

        // Mock the current URL
        $_SERVER['HTTP_HOST'] = 'wp-develop.local';
        $_SERVER['REQUEST_URI'] = '/';

        update_option( 'serve_static_make_static', '1' );

        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';
    
        // Update the option with the formatted array
        update_option('serve_static_exclude_urls', array('http://wp-develop.local/page1' => 1));

        $this->assertNull($this->static_serve->Build());

        ob_start();
        ob_end_flush();
        ob_get_clean();

        // Unset the mock request header
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
        delete_option( 'serve_static_exclude_urls' );
        delete_option( 'serve_static_make_static' );
    }

    public function test_make_post_types_static_exclude_urls_denied(){

        // Mock the current URL
        $_SERVER['HTTP_HOST'] = 'wp-develop.local';
        $_SERVER['REQUEST_URI'] = '/';

        update_option( 'serve_static_post_types_static', '1' );

        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';
    
        // Update the option with the formatted array
        update_option('serve_static_exclude_urls', array('http://wp-develop.local/' => 1));

        $this->assertFalse($this->static_serve->Build());

        // Unset the mock request header
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
        unset($_SERVER['REQUEST_URI']);
        delete_option( 'serve_static_exclude_urls' );
        delete_option( 'serve_static_make_static' );
    }

    public function test_make_post_types_static_exclude_urls_access(){

        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';
        
        // Create a post with the URL "/hello-world/"
        $post_id = wp_insert_post(array(
            'post_title' => 'Hello World',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world'
        ));

        // Mock the current URL
        $site_url = get_site_url();

        // Remove "http://" from the beginning of the URL using regular expressions
        $site_url_without_http = preg_replace('/^http?:\/\//i', '', $site_url);

        // Set the HTTP_HOST using the modified URL
        $_SERVER['HTTP_HOST'] = $site_url_without_http;


        $_SERVER['REQUEST_URI'] = '/hello-world/';

        update_option( 'serve_static_post_types_static', '1' );
    
        // Update the option with the formatted array
        update_option('serve_static_exclude_urls', array('http://wp-develop.local/' => 1));
        update_option('serve_static_specific_post_types', array('post' => 1));

        $this->assertNull($this->static_serve->Build());

        ob_start();
        ob_end_flush();
        ob_get_clean();

        // Unset the mock request header
        wp_delete_post($post_id);
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
        unset($_SERVER['REQUEST_URI']);
        delete_option( 'serve_static_exclude_urls' );
        delete_option( 'serve_static_post_types_static' );
    }

    public function test_make_manual_entry_static_denied(){

        // Mock the current URL
        $_SERVER['HTTP_HOST'] = 'wp-develop.local';
        $_SERVER['REQUEST_URI'] = '/blog/';

        update_option( 'serve_static_manual_entry', '1' );

        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';
    
        // Update the option with the formatted array
        update_option('serve_static_urls', array('http://wp-develop.local/' => 1));

        $this->assertFalse($this->static_serve->Build());

        // Unset the mock request header
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
        unset($_SERVER['REQUEST_URI']);
        delete_option( 'serve_static_manual_entry' );
    }

    public function test_make_manual_entry_static_access(){

        switch_theme( 'twentytwenty' );
    
        // Create a new page and set it as the home page
        $new_page_id = wp_insert_post( array(
            'post_title'     => 'Your New Page Title',
            'post_content'   => 'Your page content goes here.',
            'post_status'    => 'publish',
            'post_type'      => 'page',
        ) );
        update_option( 'page_on_front', $new_page_id );
        update_option( 'show_on_front', 'page' );
    
        // Mock the current URL
        $site_url = get_site_url();
    
        // Remove "http://" from the beginning of the URL using regular expressions
        $site_url_without_http = preg_replace('/^http?:\/\//i', '', $site_url);
    
        // Set the HTTP_HOST using the modified URL
        $_SERVER['HTTP_HOST'] = $site_url_without_http;
        $_SERVER['REQUEST_URI'] = '/';
    
        update_option( 'serve_static_manual_entry', '1' );
    
        $_SERVER['HTTP_X_SERVE_STATIC_REQUEST'] = 'true';
    
        // Update the option with the formatted array
        update_option('serve_static_urls', array($site_url . '/' => 1));
    
        $this->assertNull($this->static_serve->Build());
    
        ob_start();
        ob_end_flush();
        ob_get_clean();
    
        // Unset the mock request header
        wp_delete_post($new_page_id);
        unset($_SERVER['HTTP_X_SERVE_STATIC_REQUEST']);
        delete_option( 'serve_static_manual_entry' );
    }

    public function test_flush_all(){
        $cache_dir = WP_CONTENT_DIR . '/serve-static-cache';
        global $wp_filesystem;

        // Ensure the cache directory exists
        if (!file_exists($cache_dir)) {
            $wp_filesystem->mkdir($cache_dir, 0755, true);
        }

        // Set the file path
        $file_path = $cache_dir . '/new_file.html';
        $htaccess_path = $cache_dir . '/.htaccess';

        // Your content to be written to the file
        $file_content = '<html><body><p>This is the content of the new file.</p></body></html>';
        $htaccess_content = '# .htaccess content';

        // Write content to the file
        $this->assertIsInt($wp_filesystem->put_contents($file_path, $file_content));
        $this->assertIsInt($wp_filesystem->put_contents($htaccess_path, $htaccess_content));

        $this->assertFalse($this->is_dir_empty($cache_dir));

        $this->static_serve->Flush();
        
        $this->assertFileExists($htaccess_path);
        $this->assertFileDoesNotExist($file_path);
        $this->assertTrue($this->is_dir_empty_except_htaccess($cache_dir));
    }

    /**
     * Helper function to check if a directory is empty except for .htaccess
     */
    private function is_dir_empty_except_htaccess($dir) {
        $files = array_diff(scandir($dir), array('.', '..', '.htaccess'));
        return empty($files);
    }

    public function test_flush_url(){
        $cache_dir = WP_CONTENT_DIR . '/serve-static-cache/blog';
        global $wp_filesystem;

        // Ensure the cache directory exists
        if (!file_exists($cache_dir)) {
            $wp_filesystem->mkdir($cache_dir, 0755, true);
        }

        // Set the file path
        $file_path = $cache_dir . '/index.html';

        // Your content to be written to the file
        $file_content = '<html><body><p>This is the content of the new file.</p></body></html>';

        // Write content to the file
        $this->assertIsInt($wp_filesystem->put_contents($file_path, $file_content));

        $this->assertFalse($this->is_dir_empty($cache_dir));
        $this->static_serve->Flush(get_site_url() . '/blog');
        $this->assertTrue($this->is_dir_empty($cache_dir));
    }

    public function test_not_cache_available(){
        $cache_dir = WP_CONTENT_DIR . '/serve-static-cache';
        global $wp_filesystem;

        // Ensure the cache directory exists
        if (!file_exists($cache_dir)) {
            $wp_filesystem->mkdir($cache_dir, 0755, true);
        }

        // Set the file path
        $file_path = $cache_dir . '/index.html';

        // Your content to be written to the file
        $file_content = '<html><body><p>This is the content of the new file.</p></body></html>';

        // Write content to the file
        $this->assertIsInt($wp_filesystem->put_contents($file_path, $file_content));

        $this->assertFalse($this->static_serve->is_cache_available(get_site_url() . '/blog'));
        $this->static_serve->Flush();
    }

    public function test_cache_available(){
        $cache_dir = WP_CONTENT_DIR . '/serve-static-cache/blog';
        global $wp_filesystem;

        // Ensure the cache directory exists
        if (!file_exists($cache_dir)) {
            $wp_filesystem->mkdir($cache_dir, 0755, true);
        }

        // Set the file path
        $file_path = $cache_dir . '/index.html';

        // Your content to be written to the file
        $file_content = '<html><body><p>This is the content of the new file.</p></body></html>';

        // Write content to the file
        $this->assertIsInt($wp_filesystem->put_contents($file_path, $file_content));

        $this->assertTrue($this->static_serve->is_cache_available(get_site_url() . '/blog'));
        $this->static_serve->Flush();
    }
    
}