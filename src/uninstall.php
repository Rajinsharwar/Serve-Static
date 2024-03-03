<?php

// Exit if accessed directly or not on uninstall.
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

//Options
delete_option( 'serve_static_master_key' );
delete_option( 'serve_static_post_types_static' );
delete_option( 'serve_static_make_static' );
delete_option( 'serve_static_manual_entry' );
delete_option( 'serve_static_specific_post_types' );
delete_option( 'serve_static_js_minify_enabled' );
delete_option( 'serve_static_css_minify_enabled' );
delete_option( 'serve_static_html_minify_enabled' );
delete_option( 'serve_static_urls' );
delete_option( 'serve_static_exclude_urls' );
delete_option( 'serve_static_warm_on_save' );
delete_option( 'serve_static_cron_time' );

//Transients
$transients_to_delete = array(
    'serve_static_htaccess_not_writable',
    'serve_static_cron_not_running',
    'serve_static_nginx_notice_dismissed',
    'serve_static_apache_notice_dismissed',
    'serve_static_cron_notice_dismissed',
    'serve_static_wp_cron_notice_dismissed',
    'serve_static_cron_not_working_notice_dismissed',
    'serve_static_cron_not_scheduled_notice_dismissed',
    'serve_static_plugin_modified_notice',
    'serve_static_initial',
    'serve_static_cache_warming_in_progress'
);

foreach ($transients_to_delete as $transient) {
    delete_transient($transient);
}
