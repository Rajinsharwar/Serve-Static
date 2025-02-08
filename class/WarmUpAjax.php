<?php

namespace ServeStatic\Classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WarmUpAjax {

    public function enqueue_custom_scripts() {
        wp_enqueue_script('ajax-script', plugin_dir_url( __DIR__ ) . 'assets/js/ajax-script.js', ['jquery'], null, true);
        wp_localize_script('ajax-script', 'ajax_object', [
            'ajax_url'      => admin_url('admin-ajax.php'),
            'time_interval' => get_option( 'serve_static_requests_interval', 0 ),
            'nonce'         => wp_create_nonce('serve_static_send_requests_nonce')
        ]);
    }

    public function enqueue_custom_styles() {
        wp_enqueue_style( 'ajax-style', plugin_dir_url( __DIR__ ) . 'assets/css/ajax-style.css' );
    }

    // Get all the URLs, and pass to the AJAX.
    public function get_urls() {
        check_ajax_referer('serve_static_send_requests_nonce', 'security');

        if ( get_option( 'serve_static_make_static' ) == 1 || get_option( 'serve_static_post_types_static' ) == 1 ){
            $urls = ['all'];
        } elseif (get_option('serve_static_urls') !== false) {
            $urls = array_keys(get_option( 'serve_static_urls' ));
        } else {
            return;
        }

        $warmup = new WarmUp();
        $frontend_urls = $urls == ['all'] ? $warmup->GetUrls() : $urls;
        $frontend_urls = array_values($frontend_urls);
        $flush = new StaticServe();
        $flush->Flush();
        $this->FlushLog();
        delete_option( 'serve_static_log_all_done' );
        delete_option( 'serve_statis_failed_requests_count' );
        delete_transient( 'serve_static_plugin_modified_notice' ); // Delete the Regenerate cache nag.
        delete_transient( 'serve_static_db_is_updated_notice' ); // Delete the Regenerate cache nag after DB update.

        if ( is_array( $frontend_urls ) ) {
            wp_send_json_success( ['urls' => $frontend_urls ] );
        } else {
            wp_send_json_error( ['message' => 'No URLs found.'] );
        }
    
        wp_die();
    }

    // Send a single request to a URL
    public function send_single_request() {
        check_ajax_referer('serve_static_send_requests_nonce', 'security');

        $urls = isset($_POST['urls']) ? $_POST['urls'] : array();
        $url_index = isset($_POST['url_index']) ? intval($_POST['url_index']) : 0;
        $last_url = isset($_POST['last_url']) ? $_POST['last_url'] : '';

        if ( is_array($urls) && isset($urls[$url_index])) {
            $url = esc_url_raw($urls[$url_index]);
            $warmup = new WarmUp();
            $enable_for_logged_in = apply_filters( 'serve_static_enable_logged_in', false );

            if ( $enable_for_logged_in && false === get_transient( 'serve_static_logged_in_cookie' ) ) {
                $logged_in_role = apply_filters( 'serve_static_logged_in_role', 'Administrator' );

                $args = [
                    'role' => $logged_in_role,
                    'number' => 1,
                    'orderby' => 'ID',
                    'order' => 'ASC',
                ];

                $user_query = new \WP_User_Query($args);
                $cookies = [];

                if ( ! empty( $user_query->results ) ) {
                    $user = $user_query->results[0];
                    if (isset($_COOKIE['wordpress_logged_in_' . COOKIEHASH])) {
                        $cookies = array_merge($cookies, [
                            'wordpress_logged_in_' . COOKIEHASH => $_COOKIE['wordpress_logged_in_' . COOKIEHASH],
                            // 'wp-settings-' . $user->ID => $_COOKIE['wp-settings-' . $user->ID],
                            // 'wp-settings-time-' . $user->ID => $_COOKIE['wp-settings-time-' . $user->ID],
                        ]);
                    }

                    set_transient( 'serve_static_logged_in_cookies', $cookies, DAY_IN_SECONDS );
                } else {
                    set_transient( 'serve_static_logged_in_cookies', [], DAY_IN_SECONDS );
                }
            }

            $response = $warmup->SendRequest( $url, $last_url );
            if ( is_string( $response ) ) { // String means error.
                $this->Log( $url, '<b style="color:red;">' . $response . '</b>' ); // Log in the DB.
                
                if ( $url == $last_url ){ // If the Last URl was an error.
                    update_option( 'serve_static_log_all_done', 1 );
                    delete_transient( 'serve_static_logged_in_cookies' );
                }

                wp_send_json_error(['url_index' => $url_index, 'error' => $response ]);
            } else {
                $this->Log( $url, 'Success! ✅' );
                if ( $url == $last_url ){
                    update_option( 'serve_static_log_all_done', 1 );
                }
                wp_send_json_success(['message' => 'Success! ✅', 'url_index' => $url_index]);
            }
        } else {
            wp_send_json_error(['message' => 'Invalid URL index.', 'url_index' => $url_index]);
        }
    
        wp_die();
    }

    public function Log( $url, $response ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'serve_static_warm_logs';

        $wpdb->insert(
            $table_name,
            array(
                'log_url'       => $url,
                'log_status'    => wp_kses( $response, array(
                    'b' => array(
                        'style' => array(),
                    ),
                ) )
            )
        );
    }

    public function FlushLog() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'serve_static_warm_logs';
        $results = $wpdb->query("DELETE FROM $table_name");
    }

    public function GetLogs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'serve_static_warm_logs';

        $results = $wpdb->get_results( "SELECT * FROM $table_name" );
        return $results;
    }

    public function update_failed_requests_count() {
        // Check the nonce for security
        check_ajax_referer('serve_static_send_requests_nonce', 'security');

        // Get the current count from the database
        $current_count = get_option('serve_statis_failed_requests_count', 0);

        // Increment the count by the value sent in the AJAX request
        $failed_count = isset( $_POST['failed_count']) ? intval($_POST['failed_count']) : 0;
        $new_count = $current_count + $failed_count;

        // Update the option in the database
        update_option('serve_statis_failed_requests_count', $new_count);

        // Return a success response
        wp_send_json_success(array('new_count' => $new_count));
    }
}

$warm_up_ajax = new WarmUpAjax();
add_action('admin_enqueue_scripts', array($warm_up_ajax, 'enqueue_custom_scripts'));
add_action('admin_enqueue_scripts', array($warm_up_ajax, 'enqueue_custom_styles'));
add_action('wp_ajax_get_urls', array($warm_up_ajax, 'get_urls'));
add_action('wp_ajax_send_single_request', array($warm_up_ajax, 'send_single_request'));
add_action('wp_ajax_update_failed_requests_count', array($warm_up_ajax, 'update_failed_requests_count'));