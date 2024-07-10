<?php

namespace ServeStatic\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Server {

    private $events;

    /**
	 * Creates an instance
	 *
	 */
	public function __construct() {
		$this->events  = $this->get_events();
	}

    /**
	 * Returns true if server is Apache.
	 *
	 * @return bool
	 */
	public function is_apache() {
		// Assume apache when unknown, since most common.
		if ( empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			return true;
		}

		return isset( $_SERVER['SERVER_SOFTWARE'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ), 'Apache' ) !== false; // phpcs:ignore
	}


	/**
	 * Check whether server is LiteSpeed.
	 *
	 * @return bool
	 */
	public function is_litespeed() {
		return isset( $_SERVER['SERVER_SOFTWARE'] ) && stristr( wp_unslash( sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) ), 'LiteSpeed' ) !== false; // phpcs:ignore
	}

	/**
	 * Returns true if server is nginx.
	 *
	 * @return bool
	 */
	public function is_nginx() {
		return isset( $_SERVER['SERVER_SOFTWARE'] ) && stripos( wp_unslash( sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) ), 'nginx' ) !== false; // phpcs:ignore
	}

	/**
	 * Returns true if server is nginx.
	 *
	 * @return bool
	 */
	public function is_iis() {
		return isset( $_SERVER['SERVER_SOFTWARE'] ) && stripos( wp_unslash( sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) ), 'IIS' ) !== false; // phpcs:ignore
	}

    public function server_is_nginx(){
        if ( get_transient( 'serve_static_nginx_notice_dismissed' )){
            return;
        }
        $activate = new Activate();
        $rules = preg_replace( '/\'/i', '', $activate->nginx_rules());
        $rule = implode(PHP_EOL, $rules);

        ?>
        <div class="notice notice-error serve-static-notice is-dismissible">
            <p><?php esc_html_e( 'Looks like you are using a NGINX server. Our plugin\'s rely on being able to modify the server\'s rewrite rules, which is not possible via PHP on your server. For being able to make this plugin functional in your server, you will be needing to add the rewrite rules manually to your nginx.conf file, and then restart your server. Below is the rewrite rules you need to add.', 'serve_static' ); ?></p>
            <br><textarea readonly="readonly" id="serve_static_htaccess_rules" name="serve_static_htaccess_rules" class="large-text readonly" rows="6"><?php echo esc_xml($rule) //phpcs:ignore ?></textarea>
            <p>
                <a class="serve-static-dismiss" href="<?php echo esc_url( add_query_arg( 'serve_static_dismiss_nginx_notice', 'true' ) ); ?>"><?php esc_html_e( 'Dismiss', 'serve_static' ); ?></a>
            </p>
        </div>
        <?php
    }

    public function dismiss_nginx_server_notice() {
        if ( isset( $_GET['serve_static_dismiss_nginx_notice'] ) ) {
            set_transient( 'serve_static_nginx_notice_dismissed', 1 );
            wp_safe_redirect( remove_query_arg( 'serve_static_dismiss_nginx_notice' ) );
            exit;
        }
    }

    public function server_is_apache(){
        if ( get_transient( 'serve_static_apache_notice_dismissed' )){
            return;
        }
        $activate = new Activate();
        $rules = preg_replace( '/\'/i', '', $activate->rules());
        $rule = implode(PHP_EOL, $rules);

        ?>
        <div class="notice notice-error serve-static-notice is-dismissible">
            <p><?php esc_html_e( 'Serve Static isn\'t able to add/modify the ".htaccess" of your website. We need to add some code in your .htaccess to make make this plugin functional. Please contact with your webhost to make sure the .htaccess file of your website is writable by plugins. Alternately, you can copy the below code to manually add in your .htaccess file. This notice should disappear when you have added the code. If still the issue persists, please share the issue with us in our support forum.', 'serve_static' ); ?></p>
            <br><textarea readonly="readonly" id="serve_static_htaccess_rules" name="serve_static_htaccess_rules" class="large-text readonly" rows="6"><?php echo esc_xml($rule) //phpcs:ignore ?></textarea>
            <p>
                <a class="serve-static-dismiss" href="<?php echo esc_url( add_query_arg( 'serve_static_dismiss_apache_notice', 'true' ) ); ?>"><?php esc_html_e( 'Dismiss', 'serve_static' ); ?></a>
            </p>
        </div>
        <?php
    }

    public function dismiss_apache_server_notice() {
        if ( isset( $_GET['serve_static_dismiss_apache_notice'] ) ) {
            set_transient( 'serve_static_apache_notice_dismissed', 1, HOUR_IN_SECONDS );
            wp_safe_redirect( remove_query_arg( 'serve_static_dismiss_apache_notice' ) );
            exit;
        }
    }

    public function cron_not_running(){
        if ( get_transient( 'serve_static_cron_notice_dismissed' )){
            return;
        }
        $activate = new Activate();
        $rules = preg_replace( '/\'/i', '', $activate->rules());
        $rules = implode(PHP_EOL, $rules);

        ?>
        <div class="notice notice-error serve-static-notice is-dismissible">
            <p><?php esc_html_e( 'One or more scheduled event is failing. This indicates that our plugin is not able to schedule CRON events properly, which can cause issues like Static files not being served to your users. Please get in touch with your webhosting provider to fix this issue as soon as possible to avoid issues with Serve Static plugin. ', 'serve_static' ); ?></p>
            <p>
                <a class="serve-static-dismiss" href="<?php echo esc_url( add_query_arg( 'serve_static_dismiss_cron_notice', 'true' ) ); ?>"><?php esc_html_e( 'Dismiss', 'serve_static' ); ?></a>
            </p>
        </div>
        <?php
    }

    public function dismiss_cron_notice() {
        if ( isset( $_GET['serve_static_dismiss_cron_notice'] ) ) {
            set_transient( 'serve_static_cron_notice_dismissed', 1, HOUR_IN_SECONDS );
            wp_safe_redirect( remove_query_arg( 'serve_static_dismiss_cron_notice' ) );
            exit;
        }
    }

    public function wp_cron_disabled(){
        if ( get_transient( 'serve_static_wp_cron_notice_dismissed' )){
            return;
        }

        ?>
        <div class="notice notice-error serve-static-notice is-dismissible">
            <p><?php esc_html_e( 'The DISABLE_WP_CRON constant is set to true. This indicates that our plugin may not be able to schedule CRON events properly/function properly unless you have a SERVER level cron running. Please contact your hosting provider to check for more information. Or, you can dismiss this notice if you bielive server-level cron is implemented on your website. ', 'serve_static' ); ?></p>
            <p>
                <a class="serve-static-dismiss" href="<?php echo esc_url( add_query_arg( 'serve_static_dismiss_wp_cron_notice', 'true' ) ); ?>"><?php esc_html_e( 'Dismiss', 'serve_static' ); ?></a>
            </p>
        </div>
        <?php
    }

    public function dismiss_wp_cron_disabled_notice() {
        if ( isset( $_GET['serve_static_dismiss_wp_cron_notice'] ) ) {
            set_transient( 'serve_static_wp_cron_notice_dismissed', 1, WEEK_IN_SECONDS );
            wp_safe_redirect( remove_query_arg( 'serve_static_dismiss_wp_cron_notice' ) );
            exit;
        }
    }

    /**
	 * Display a warning notice if Serve Static scheduled events are not running properly.
	 *
	 * @since 1.0.1
	 */
	public function missed_cron() {

        /**
         * This works by checking the $timestamp of the crons, and then minus'ing them from the current time(). We are adding a delay here to avoid Fixing false positives.
         */

		$delay  = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON === true ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
		$list   = '';
		$events = $this->events;

		foreach ( $this->events as $event => $description ) {
			$timestamp = wp_next_scheduled( $event );

			if (
				false === $timestamp
				||
				( $timestamp + $delay - time() ) > 0 //We are adding one hour or 5 minutes, and then comparing it with the current time().
			) {
				unset( $events[ $event ] );
				continue;
			}

			$list .= "<li>{$description}</li>";
		}

		if ( empty( $events ) ) {
			return; //Means all cron is running properly.
		}

		return $message = sprintf(
			'<p>%1$s</p>
			<ul>%2$s</ul>
			<p>%3$s</p>',
			_n(
				'The following scheduled event failed to run. This may indicate the CRON system is not running properly, which is preventing Serve Static\'s features from working as intended:',
				'The following scheduled events failed to run. This may indicate the CRON system is not running properly, which is preventing Serve Static\'s features from working as intended:',
				count( $events ),
				'serve_static'
			),
			$list,
			__( 'Please contact with your hosting provider to check if CRON is working.', 'serve_static' )
		);
	}

    /**
	 * Gets an array of events with their descriptions.
	 *
	 * @since 1.0.1
	 *
	 * @return array array of events => descriptions.
	 */
	protected function get_events() {
		return [
			'serve_static_cache_cron_event'     => __( 'Scheduled Cache Regeneration', 'serve_static' ),
			'custom_warmup_cache_cron'          => __( 'Scheduled Event to get URLs for caching', 'serve_static' ),
			'warm_up_cache_request'             => __( 'Scheduled Event to regenerate cache', 'serve_static' ),
			'warm_up_cache_request_triggers'    => __( 'Scheduled Event to regenerate Cache for custom triggers', 'serve_static' ),
		];
	}

    public function cron_not_working(){
        if ( get_transient( 'serve_static_cron_not_working_notice_dismissed' )){
            return;
        }

        $message = $this->missed_cron();

        ?>
        <div class="notice notice-error serve-static-notice is-dismissible">
            <?php echo wp_kses_post( $message, 'serve_static' ); ?>
            <p>
                <a class="serve-static-dismiss" href="<?php echo esc_url( add_query_arg( 'serve_static_dismiss_cron_fail_notice', 'true' ) ); ?>"><?php esc_html_e( 'Dismiss', 'serve_static' ); ?></a>
            </p>
        </div>
        <?php
    }

    public function dismiss_cron_not_working_notice() {
        if ( isset( $_GET['serve_static_dismiss_cron_fail_notice'] ) ) {
            set_transient( 'serve_static_cron_not_working_notice_dismissed', 1, HOUR_IN_SECONDS );
            wp_safe_redirect( remove_query_arg( 'serve_static_dismiss_cron_fail_notice' ) );
            exit;
        }
    }

    public function cron_not_scheduled(){
        if ( ! wp_next_scheduled( 'custom_warmup_cache_cron' ) ){
            add_action( 'admin_notices', array( $this, 'cron_not_scheduled_notice' ) );
        }
    }

    public function cron_not_scheduled_notice(){
        if ( get_transient( 'serve_static_cron_not_scheduled_notice_dismissed' )){
            return;
        } ?>
        <div class="notice notice-error serve-static-notice is-dismissible">
            <p>
                <?php esc_html_e( 'One of the scheduled cron events to regenerate the cache didn\'t get scheduled. This may happen due to some issues with your webserver or CRON system. Please ', 'serve_static' ); ?>
                <a href="<?php echo esc_url( admin_url('admin.php?page=serve_static_settings#stopwarming') ); ?>"><?php esc_html_e( 'Stop the regeneration' , 'serve_static' );?></a>
                <?php esc_html_e( ', and try again. If this continues, please post in our support forum, also confirm if your CRON is working properly.', 'serve_static' ); ?>
            </p>
                <a class="serve-static-dismiss" href="<?php echo esc_url( add_query_arg( 'serve_static_dismiss_cron_scheduled_notice', 'true' ) ); ?>"><?php esc_html_e( 'Dismiss', 'serve_static' ); ?></a>
            </p>
        </div>
        <?php
    }

    public function dismiss_cron_not_scheduled_notice() {
        if ( isset( $_GET['serve_static_dismiss_cron_scheduled_notice'] ) ) {
            set_transient( 'serve_static_cron_not_scheduled_notice_dismissed', 1, HOUR_IN_SECONDS );
            wp_safe_redirect( remove_query_arg( 'serve_static_dismiss_cron_scheduled_notice' ) );
            exit;
        }
    }
}

$server = new Server();
if ( $server->is_nginx()){
    add_action( 'admin_notices', array( $server, 'server_is_nginx' ) );
    add_action( 'admin_init', array( $server, 'dismiss_nginx_server_notice' ) );
} elseif ( ( $server->is_apache() || $server->is_litespeed() ) && get_transient( 'serve_static_htaccess_not_writable' ) ){
    add_action( 'admin_notices', array( $server, 'server_is_apache' ) );
    add_action( 'admin_init', array( $server, 'dismiss_apache_server_notice' ) );
} elseif ( get_transient( 'serve_static_cron_not_running' ) ){
    add_action( 'admin_notices', array( $server, 'cron_not_running' ) );
    add_action( 'admin_init', array( $server, 'dismiss_cron_notice' ) );
} elseif ( defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true ){
    add_action( 'admin_notices', array( $server, 'wp_cron_disabled' ) );
    add_action( 'admin_init', array( $server, 'dismiss_wp_cron_disabled_notice' ) );
}

add_action( 'admin_init', array( $server, 'missed_cron' ) );
if ( $server->missed_cron() !== null ){
    add_action( 'admin_notices', array( $server, 'cron_not_working' ) );
    add_action( 'admin_init', array( $server, 'dismiss_cron_not_working_notice' ) );
}

if ( get_transient( 'serve_static_initial' ) ){
    add_action( 'admin_init', array( $server, 'cron_not_scheduled' ) );
    add_action( 'admin_init', array( $server, 'dismiss_cron_not_scheduled_notice' ) );
}
