<?php
/**
 * Class Test_WarmUp_Class
 *
 * @package Serve Static
 */

/**
 * Sample test case.
 */
class Test_WarmUp_Class extends WP_UnitTestCase {

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
        
		// Instantiate the WarmUp class
        $this->warmup = new ServeStatic\Class\WarmUp();
    }

    public function tear_down() {
        parent::tear_down();
    }

    public function test_ScheduleWarmup(){
        $this->warmup->ScheduleWarmup();
        $this->assertIsInt( wp_next_scheduled( 'custom_warmup_cache_cron' ) );
        $this->assertNotFalse(get_transient( 'serve_static_cache_warming_in_progress' ));
        $this->assertNotFalse(get_transient( 'serve_static_initial' ));

        delete_transient( 'serve_static_initial' );
        delete_transient( 'serve_static_cache_warming_in_progress' );
    }

    public function test_send_request(){
        set_transient('serve_static_cache_warming_in_progress', true, DAY_IN_SECONDS);

        $this->assertTrue($this->warmup->SendRequest('https://example.com', 'https://example-2.com'));
        $this->assertNotFalse(get_transient( 'serve_static_cache_warming_in_progress' ));

        $this->assertTrue($this->warmup->SendRequest('https://example.com', 'https://example.com'));
        $this->assertFalse(get_transient( 'serve_static_cache_warming_in_progress' ));
    }

    public function test_warmup_it_up_triggers(){
        $this->warmup->WarmitUpTriggers('https://example.com');
        $this->assertIsInt( wp_next_scheduled( 'warm_up_cache_request_triggers', array( 'https://example.com' ) ) );
    }

    public function test_get_urls_post_types(){

        $post_id_1 = wp_insert_post(array(
            'post_title' => 'Hello World 1',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world-1'
        ));

        $post_id_2 = wp_insert_post(array(
            'post_title' => 'Hello World 2',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world-2'
        ));

        $page_id_1 = wp_insert_post(array(
            'post_title' => 'Hello World 2',
            'post_content' => 'This is a test post.',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'hello-world-3'
        ));

        update_option( 'serve_static_post_types_static', 1 );
        update_option( 'serve_static_specific_post_types', array( 'post' => 1) );

        $urls = $this->warmup->GetUrls();

        $this->assertTrue(in_array( get_site_url() . '/hello-world-1/', $urls ));
        $this->assertFalse(in_array( get_site_url() . '/hello-world-3/', $urls ));

        wp_delete_post($post_id_1);
        wp_delete_post($post_id_2);
        wp_delete_post($page_id_1);
        delete_option( 'serve_static_post_types_static' );
        delete_option( 'serve_static_specific_post_types' );
    }

    public function test_get_urls_post_types_exclude(){

        $post_id_1 = wp_insert_post(array(
            'post_title' => 'Hello World 1',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world-1'
        ));

        $post_id_2 = wp_insert_post(array(
            'post_title' => 'Hello World 2',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world-2'
        ));

        $page_id_1 = wp_insert_post(array(
            'post_title' => 'Hello World 2',
            'post_content' => 'This is a test post.',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'hello-world-3'
        ));

        update_option( 'serve_static_post_types_static', 1 );
        update_option( 'serve_static_specific_post_types', array( 'post' => 1) );
        update_option('serve_static_exclude_urls', array( get_site_url() . '/hello-world-2/' => 1));

        $urls = $this->warmup->GetUrls();

        $this->assertTrue(in_array( get_site_url() . '/hello-world-1/', $urls ));
        $this->assertFalse(in_array( get_site_url() . '/hello-world-2/', $urls ));
        $this->assertFalse(in_array( get_site_url() . '/hello-world-3/', $urls ));

        wp_delete_post($post_id_1);
        wp_delete_post($post_id_2);
        wp_delete_post($page_id_1);
        delete_option( 'serve_static_post_types_static' );
        delete_option( 'serve_static_exclude_urls' );
        delete_option( 'serve_static_specific_post_types' );
    }

    public function test_get_urls_all(){

        $post_id_1 = wp_insert_post(array(
            'post_title' => 'Hello World 1',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world-1'
        ));

        $post_id_2 = wp_insert_post(array(
            'post_title' => 'Hello World 2',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world-2'
        ));

        $page_id_1 = wp_insert_post(array(
            'post_title' => 'Hello World 2',
            'post_content' => 'This is a test post.',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'hello-world-3'
        ));

        update_option( 'serve_static_make_static', 1 );

        $urls = $this->warmup->GetUrls();

        $this->assertTrue(in_array( get_site_url() . '/hello-world-1/', $urls ));
        $this->assertTrue(in_array( get_site_url() . '/hello-world-2/', $urls ));
        $this->assertTrue(in_array( get_site_url() . '/hello-world-3/', $urls ));

        wp_delete_post($post_id_1);
        wp_delete_post($post_id_2);
        wp_delete_post($page_id_1);
        delete_option( 'serve_static_make_static' );
    }

    public function test_get_urls_all_exclude(){

        $post_id_1 = wp_insert_post(array(
            'post_title' => 'Hello World 1',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world-1'
        ));

        $post_id_2 = wp_insert_post(array(
            'post_title' => 'Hello World 2',
            'post_content' => 'This is a test post.',
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_name' => 'hello-world-2'
        ));

        $page_id_1 = wp_insert_post(array(
            'post_title' => 'Hello World 2',
            'post_content' => 'This is a test post.',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_name' => 'hello-world-3'
        ));

        update_option( 'serve_static_make_static', 1 );
        update_option('serve_static_exclude_urls', array( get_site_url() . '/hello-world-2/' => 1));

        $urls = $this->warmup->GetUrls();

        $this->assertTrue(in_array( get_site_url() . '/hello-world-1/', $urls ));
        $this->assertFalse(in_array( get_site_url() . '/hello-world-2/', $urls ));
        $this->assertTrue(in_array( get_site_url() . '/hello-world-3/', $urls ));

        wp_delete_post($post_id_1);
        wp_delete_post($post_id_2);
        wp_delete_post($page_id_1);
        delete_option( 'serve_static_make_static' );
        delete_option( 'serve_static_exclude_urls' );
    }
}