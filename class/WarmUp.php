<?php

namespace ServeStatic\Classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WarmUp
{

    public function ScheduleWarmup()
    {
        if ( get_option('serve_static_master_key', '') == '' || get_option( 'serve_static_master_key' ) == 0 ) {
            return;
        }

        // Check if the cron job is already scheduled to avoid duplication
        if ( ! wp_next_scheduled('custom_warmup_cache_cron')) {
            // Schedule the cron job to run once in 1 second
            $cron = wp_schedule_single_event(time() + 1, 'custom_warmup_cache_cron', array(), true);
            set_transient( 'serve_static_initial' , 1);
        }

        set_transient('serve_static_cache_warming_in_progress', true, DAY_IN_SECONDS);
    }

    public function WarmItUp( $url = null )
    {
        delete_transient( 'serve_static_initial' );
        wp_unschedule_hook('warm_up_cache_request'); //Early call to eliminate any existing cron for conflicts.
        set_transient('serve_static_cache_warming_in_progress', true); //Set transeint if not already sent while flushing one URL.

        if ( $url ){
            $urls = array();
            $urls = [$url];
        } elseif ( get_option(' serve_static_make_static ') == 1 || get_option(' serve_static_post_types_static ') == 1 ){
            $urls = ['all'];
            delete_transient( 'serve_static_plugin_modified_notice' );
        } elseif (get_option('serve_static_urls') !== false) {
            $urls = array_keys(get_option('serve_static_urls'));
            delete_transient( 'serve_static_plugin_modified_notice' );
        } else {
            delete_transient('serve_static_cache_warming_in_progress', true);
            return;
        }

        $frontend_urls = $urls == ['all'] ? $this->GetUrls() : $urls;
        error_log(print_r($frontend_urls, true));
        $last_url = end($frontend_urls);
        error_log(print_r($last_url, true));
        $index = 0;
        foreach ($frontend_urls as $url) {
            if ( ! wp_next_scheduled(('warm_up_cache_request'), array($url, $last_url)) ){
                $cron = wp_schedule_single_event(time() + $index * 2, 'warm_up_cache_request', array($url, $last_url), true);

                if ( is_wp_error($cron) || $cron == false ){
                    set_transient( 'serve_static_cron_not_running', 1 );
                    delete_transient('serve_static_cache_warming_in_progress', true);
                    return;
                } elseif ( $cron == 1){
                    delete_transient( 'serve_static_cron_not_running' );
                }
                
                $index++;
            }
        }

    }

    public function send_cache_warmup_request($url, $last_url)
    {
        // Send request to $url
        $response = $this->SendRequest( $url, $last_url );
        if ( true === $response && ! is_string( $response ) ) {
            error_log('Sent to: ' . $url);
        }
    }

    public function SendRequest(string $url, string $last_url, int $timeout = 50, string $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36', array $cookies = [], array $request_headers = [])
    {
        $enable_for_logged_in = apply_filters( 'serve_static_enable_logged_in', false );

        if ( $enable_for_logged_in ) {
            $cookies = get_transient( 'serve_static_logged_in_cookies' );

            if ( false === $cookies ) {
                $cookies = [];
            }
        }

        $request_headers['X-Serve-Static-Request'] = 'true';
        
        $response = wp_safe_remote_get(
            $url,
            [
                'timeout' => $timeout,
                'user-agent' => $user_agent,
                'cookies' => $cookies,
                'headers' => $request_headers,
                'sslverify' => false,
            ]
        );

        if ( $url == $last_url ){
            delete_transient('serve_static_cache_warming_in_progress');
        }

        if ( ! is_array($response) ){ // Returns WP_ERROR on failure.
            error_log( 'Error while sending wp_safe_remote_get() for: ' . $url );
            error_log( 'Error: ' . print_r( $response, true ) );
            $error = is_wp_error( $response ) ? $response->get_error_message() : 'Null';
            return $error;
        } else {
            return true;
        }

    }

    public function WarmitUpTriggers( $url ){
        if ( ! wp_next_scheduled( ( 'warm_up_cache_request_triggers' ), array( $url ) ) ){
            $cron = wp_schedule_single_event(time() + 1, 'warm_up_cache_request_triggers', array($url), true);
            
            if ( is_wp_error($cron) || $cron == false ){
                set_transient( 'serve_static_cron_not_running', 1 );
                return;
            } elseif ( $cron == 1){
                delete_transient( 'serve_static_cron_not_running' );
            }

        }
    }

    public function SendRequestTriggers(string $url, int $timeout = 50, string $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36', array $cookies = [], array $request_headers = [])
    {

        $request_headers['X-Serve-Static-Request'] = 'true';
        
        wp_remote_get(
            $url,
            [
                'timeout' => $timeout,
                'user-agent' => $user_agent,
                'cookies' => $cookies,
                'headers' => $request_headers,
                'sslverify' => false,
            ]
        );

    }

    public function GetUrls()
    {
        // Add posts.
        if ( get_option( 'serve_static_post_types_static' ) == 1 ){
            $postTypes = [];
            $postTypes = array_keys(get_option( 'serve_static_specific_post_types' ));
            // Query the WordPress database to get all published pages
            $query_args = [
                'post_type' => $postTypes,
                'post_status' => 'publish',
                'posts_per_page' => -1,
            ];

            $pages = get_posts($query_args);

            // Get the URLs of published pages
            $links = [];
            foreach ($pages as $page_id) {
                $url = get_permalink($page_id);
                if ($url) {
                    $links[] = $url;
                }
            }

        } elseif ( get_option( 'serve_static_make_static' ) == 1 ) {

            $args  = [
                'post_type'      => 'any',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'perm'           => 'readable',
            ];
            $posts = get_posts( $args );
            foreach ( $posts as $post ) {
                $links[] = get_permalink( $post );
            }

            // Add pagination for blog posts.
            $args        = [
                'post_type'   => 'post',
                'post_status' => 'publish',
            ];
            $posts_query = new \WP_Query( $args );

            $pagination_format = get_option( 'permalink_structure' ) ? '/page/%d/' : '&paged=%d';

            $blog_page = get_option( 'page_for_posts' );
            if ( $blog_page ) {
                // Get the blog page link.
                $blog_link = get_permalink( $blog_page );

                if ( $blog_link ) {
                    // Add Pagination for blog page.
                    $total_pages = $posts_query->max_num_pages;

                    for ( $i = 2; $i <= $total_pages; $i ++ ) {
                        $links[] = untrailingslashit( $blog_link ) . sprintf( $pagination_format, $i );
                    }
                }
            }

            // Post types archives.
            foreach ( get_post_types() as $post_type ) {
                if ( is_post_type_viewable( $post_type ) ) {
                    $link = get_post_type_archive_link( $post_type );
                    if ( $link ) {
                        $links[] = $link;
                    }
                }
            }

            // Add term links.
            if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
                $wc_public_attributes = array_map(
                    function( $x ) {
                        return 'pa_' . $x;
                    },
                    array_keys( array_filter( wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_public', 'attribute_name' ) ) )
                );
            }

            foreach ( get_taxonomies() as $taxonomy ) {
                if (
                    is_taxonomy_viewable( $taxonomy ) &&
                    (
                        ! function_exists( 'wc_get_attribute_taxonomies' ) ||
                        ! str_starts_with( $taxonomy, 'pa_' ) ||
                        (
                            isset( $wc_public_attributes ) &&
                            in_array( $taxonomy, $wc_public_attributes, true )
                        )
                    )
                ) {
                    $terms = get_terms( $taxonomy );
                    foreach ( $terms as $term ) {
                        $term_link = get_term_link( $term );
                        if ( ! is_wp_error( $term_link ) ) {
                            $links[] = $term_link;

                            // Determine posts per page based on taxonomy type.
                            if (
                                (
                                    function_exists( 'wc_get_attribute_taxonomies' ) && str_starts_with( $taxonomy, 'pa_' ) ||
                                    str_starts_with( $taxonomy, 'product_' )
                                ) &&
                                function_exists( 'wc_get_default_products_per_row' )
                            ) {
                                $posts_per_page = (int) apply_filters(
                                    'loop_shop_per_page',
                                    wc_get_default_products_per_row() * wc_get_default_product_rows_per_page()
                                );
                            } else {
                                $posts_per_page = (int) get_option( 'posts_per_page' );
                            }

                            // Get the total number of posts in the term.
                            $total_posts = $term->count;

                            // Calculate total pages.
                            $total_pages = ceil( $total_posts / $posts_per_page );

                            // Get links for each page.
                            for ( $i = 2; $i <= $total_pages; $i ++ ) {
                                $links[] = untrailingslashit( $term_link ) . sprintf( $pagination_format, $i );
                            }
                        }
                    }
                }
            }
        }

        // Fetch the excluded URLs from the serve_static_exclude_urls option
        $excluded_urls = array_keys(get_option('serve_static_exclude_urls', array()));

        // Filter out excluded URLs from the $urls array
        $urls = array_diff($links, $excluded_urls);

        return $urls;
    }
}

//Register WarmUp service
$warmup = new WarmUp();
add_action('custom_warmup_cache_cron', array($warmup, 'WarmItUp'));
add_action('warm_up_cache_request_triggers', array($warmup, 'SendRequestTriggers'), 10, 1);

if ( get_transient('serve_static_cache_warming_in_progress') ){
    add_action('warm_up_cache_request', array($warmup, 'send_cache_warmup_request'), 10, 2);
} else {
    if ( (did_action( 'admin_init' ) >= 1) ) {
        return;
    }
    add_action( 'admin_init', function(){
        wp_unschedule_hook('warm_up_cache_request');
    });
}
