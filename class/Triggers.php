<?php

namespace ServeStatic\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Triggers {
    /**
     * This class is for hooking the process of flushing cache based on tirggers like, post saved/updated.
     * This is also used to show Admin Notices to prompt the user to regenrate the cache when a plugin/theme is newly activated/deactivated.
     * @since 1.0.1
     * 
     */
    public function flush_on_post_save_all($post_id, $post, $update){

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( get_option( 'serve_static_make_static' ) == 1 ){

            $post_url = get_permalink($post_id);
            $excluded_urls = get_option( 'serve_static_exclude_urls' );

            $flush = new StaticServe();
            $flush->Flush($post_url); //Flushing before checking for excluded urls so that we can clean the cache if it is excluded.

            if ( $excluded_urls !== false && isset($excluded_urls[$post_url]) ){
                return;
            }

            $warmup = new WarmUp();
            $warmup->WarmitUpTriggers($post_url);

        } elseif ( get_option( 'serve_static_manual_entry' ) == 1 ) {
            $post_url = get_permalink($post_id);
            $included_urls = get_option( 'serve_static_urls' );
            
            if ( isset( $included_urls[ $post_url ] )){
    
                $flush = new StaticServe();
                $flush->Flush($post_url);
        
                $warmup = new WarmUp();
                $warmup->WarmitUpTriggers($post_url);
            }
        }
    }

    public function flush_on_post_save_post_types($post_id, $post, $update){

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $post_url = get_permalink($post_id);
        $excluded_urls = get_option( 'serve_static_exclude_urls' );

        $flush = new StaticServe();
        $flush->Flush($post_url);

        if ( $excluded_urls !== false && isset($excluded_urls[$post_url]) ){
            return;
        }

        $warmup = new WarmUp();
        $warmup->WarmitUpTriggers($post_url);

    }

    /**
     * WP PostRate
     */
    public function flush_on_post_rate_wp_postrate( $user_id, $post_id ){

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $flush = new StaticServe();
        $flush->FlushPost( $post_id );
    }

    /**
     * Feedback WP
     */
    public function flush_on_post_rate_feedbackwp( $post_id ){

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $flush = new StaticServe();
        $flush->FlushPost( $post_id );
    }

    /**
     * kk Star Ratings
     */
    public function flush_on_post_rate_kkr( $post_id ){

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $flush = new StaticServe();
        $flush->FlushPost( $post_id );
    }

    /**
     * Clear cache on comment approved
     */
    public function comment_approved( $comment_id, $comment_status ){
        if ($comment_status == 'approve'){
            $comment = get_comment($comment_id);
            $post_id = $comment->comment_post_ID;

            $flush = new StaticServe();
            $flush->FlushPost( $post_id );
        }
    }

    /**
     * Clear cache on comment posted
     */
    public function comment_posted( $comment_id, $comment_status ){
        if ( $comment_status == 1 ){
            $comment = get_comment($comment_id);
            $post_id = $comment->comment_post_ID;

            $flush = new StaticServe();
            $flush->FlushPost( $post_id );
        }
    }

    /**
     * Plugins Modified Admin notices.
     */
    public function plugins_modified() {

        set_transient( 'serve_static_plugin_modified_notice', true, DAY_IN_SECONDS );

    }
    
    public function show_plugins_modified_notice() {
    
        // Define the Warm Cache URL.
        $warm_cache_url = admin_url('admin.php?page=serve_static_warmer');
    
        ?>
        <div class="notice notice-warning serve-static-notice is-dismissible">
            <p><?php esc_html_e( 'One or more plugins have been activated or deactivated, or the Theme was changed. Please regenerate the Static Cache if this affects the frontend.', 'serve_static' ); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url( $warm_cache_url ); ?>"><?php esc_html_e( 'Regenerate Cache', 'serve_static' ); ?></a>
                <a class="serve-static-dismiss" href="<?php echo esc_url( add_query_arg( 'serve_static_dismiss_notice', 'true' ) ); ?>"><?php esc_html_e( 'Dismiss', 'serve_static' ); ?></a>
            </p>
        </div>
        <?php
    }
    
    public function dismiss_plugins_modified_notice() {
        if ( isset( $_GET['serve_static_dismiss_notice'] ) ) {
            delete_transient( 'serve_static_plugin_modified_notice' );
            wp_safe_redirect( remove_query_arg( 'serve_static_dismiss_notice' ) );
            exit;
        }
    }

    /**
     * Database Updated Admin Notice.
     */
    public function db_updated() {
        set_transient( 'serve_static_db_update_notice', true, DAY_IN_SECONDS );
    }
    
    public function show_db_update_notice() {
    
        // Return early if the transient not present.
        if ( ! get_transient( 'serve_static_db_update_notice' ) ) {
            return;
        }

        // Define the Warm Cache URL.
        $base_url = admin_url('admin.php?page=serve_static_warmer');
        $warm_cache_url = wp_nonce_url($base_url . '&action=warm_cache', 'serve_static_warm_cache', '_wpnonce', 10);
    
        ?>
        <div class="notice notice-warning serve-static-notice is-dismissible">
            <p><?php esc_html_e( 'The Database verson of serve Static has been changed. Please regenerate the Static Cache for this plugin to function properly', 'serve_static' ); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url( $warm_cache_url ); ?>"><?php esc_html_e( 'Regenerate Cache', 'serve_static' ); ?></a>
                <a class="serve-static-dismiss" href="<?php echo esc_url( add_query_arg( 'serve_static_dismiss_db_update_notice', 'true' ) ); ?>"><?php esc_html_e( 'Dismiss', 'serve_static' ); ?></a>
            </p>
        </div>
        <?php
    }
    
    public function dismiss_db_updated_notice() {
        if ( isset( $_GET['serve_static_dismiss_db_update_notice'] ) ) {
            delete_transient( 'serve_static_db_update_notice' );
            wp_safe_redirect( remove_query_arg( 'serve_static_dismiss_db_update_notice' ) );
            exit;
        }
    }
}

$triggers = new Triggers();

if ( get_option( 'serve_static_master_key' ) == 1 ){

    if ( get_option( 'serve_static_post_types_static' ) == 1 ){
        $post_types = array_keys(get_option( 'serve_static_specific_post_types' ));

        foreach ( $post_types as $post_type ){
            add_action( 'save_post_' . $post_type, array( $triggers, 'flush_on_post_save_post_types'), 10, 3 );
        }
    } else {
        add_action( 'save_post', array( $triggers, 'flush_on_post_save_all'), 10, 3 );
    }
    /**
     * Conflict fix for WP-PostRatings.
     */
    add_action( 'rate_post', array( $triggers, 'flush_on_post_rate_wp_postrate'), 10, 2 );
    /**
     * Conflict for FeedbackWP
     */
    add_action( 'rmp_after_vote', array( $triggers, 'flush_on_post_rate_feedbackwp'), 10, 1 );
    /**
     * Conflict for kkr Feedback
     */
    add_action( 'kksr_rate', array( $triggers, 'flush_on_post_rate_kkr'), 10, 1 );
    /**
     * Conflict for WordPress comment approved.
     */
    add_action( 'wp_set_comment_status', array( $triggers, 'comment_approved'), 10, 2 );
    /**
     * Conflict for WordPress comment posted.
     */
    add_action( 'comment_post', array( $triggers, 'comment_posted'), 10, 2 );

    add_action( 'activated_plugin', array( $triggers, 'plugins_modified') );
    add_action( 'deactivated_plugin', array( $triggers, 'plugins_modified') );
    add_action( 'switch_theme', array( $triggers, 'plugins_modified') );

    // Add Plugins Modified notice.
    if ( get_transient( 'serve_static_plugin_modified_notice' )){
        add_action( 'admin_notices', array( $triggers, 'show_plugins_modified_notice' ) );
        add_action( 'admin_init', array( $triggers, 'dismiss_plugins_modified_notice' ) );
    }

    // Add DB Updated notice.
    if ( get_transient( 'serve_static_db_is_updated_notice' )){
        add_action( 'admin_notices', array( $triggers, 'show_db_update_notice' ) );
        add_action( 'admin_init', array( $triggers, 'dismiss_db_updated_notice' ) );
    }
}
