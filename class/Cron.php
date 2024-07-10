<?php

namespace ServeStatic\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class CacheCron {

    public function start(){
        // Schedule the event to run.
        if ( get_option( 'serve_static_master_key' ) != 1 ){
            if ( wp_next_scheduled( 'serve_static_cache_cron_event' ) ) {
                wp_clear_scheduled_hook('serve_static_cache_cron_event');
            }
            return;
        }

        if ( isset($_POST['serve_static_cron_time']) ){
            $cron_time = sanitize_text_field($_POST['serve_static_cron_time']);
            update_option( 'serve_static_cron_time', $cron_time );

            if ( wp_next_scheduled( 'serve_static_cache_cron_event' ) ) {
                wp_clear_scheduled_hook('serve_static_cache_cron_event');
            }

            if ( $cron_time == 'no_flush' ){
                return;
            }

            switch ($cron_time) {
                case 'hourly':
                    $initial_delay = 60 * 60; // 1 hour
                    break;
                case 'twicedaily':
                    $initial_delay = 12 * 60 * 60; // 12 hours
                    break;
                case 'daily':
                    $initial_delay = 24 * 60 * 60; // 24 hours
                    break;
                case 'weekly':
                    $initial_delay = 7 * 24 * 60 * 60; // 7 days
                    break;
                default:
                    $initial_delay = 0; // No delay
            }
            
            // Calculate the next run time after the initial delay
            $next_run_time = time() + $initial_delay;

            $cron = wp_schedule_event( $next_run_time, $cron_time, 'serve_static_cache_cron_event' );

            if ( is_wp_error($cron) || $cron == false ){
                set_transient( 'serve_static_cron_not_running', 1 );
                return;
            } elseif ( $cron == 1){
                delete_transient( 'serve_static_cron_not_running' );
            }
        }

        // Hook the event to custom function.
        add_action( 'serve_static_cache_cron_event', array( $this, 'cache_cron_function' ) );
    }

    public function cache_cron_function() {
        $flush = new StaticServe();
        $flush->Flush();

        $warmup = new WarmUp();
        $warmup->ScheduleWarmup();
    }
}

// Instantiate the CacheCron class and start the cron
$cache_cron = new CacheCron();
$cache_cron->start();