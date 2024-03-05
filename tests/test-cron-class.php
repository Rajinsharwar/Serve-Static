<?php
/**
 * Class Test_Cron_Class
 *
 * @package Serve Static
 */

/**
 * Sample test case.
 */
class Test_Cron_Class extends WP_UnitTestCase {
    public function set_up() {
        parent::set_up();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user = wp_set_current_user( $user_id );

		// This is the key here.
		set_current_screen( 'edit-post' );

        update_option( 'serve_static_master_key', '1' );

        $_POST['serve_static_cron_time'] = 'hourly';
        
		// Instantiate the CacheCron class
        $this->cron = new CacheCron();
    }

    public function tear_down() {
        parent::tear_down();
    }

    public function test_start(){
        $this->cron->start();

        $this->assertIsInt( wp_next_scheduled( 'serve_static_cache_cron_event' ) );
    }
}