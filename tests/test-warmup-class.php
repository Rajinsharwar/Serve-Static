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
        $this->warmup = new WarmUp();
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

        $this->assertTrue($this->warmup->SendRequest('https://weenyjob.com', 'https://bikroyshohoj.com'));
        $this->assertNotFalse(get_transient( 'serve_static_cache_warming_in_progress' ));

        $this->assertTrue($this->warmup->SendRequest('https://weenyjob.com', 'https://weenyjob.com'));
        $this->assertFalse(get_transient( 'serve_static_cache_warming_in_progress' ));
    }

}