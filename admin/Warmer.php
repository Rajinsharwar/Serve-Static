<?php

namespace ServeStatic\Classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WarmerAdmin {

    public function warmer_sub_menu(){
        add_submenu_page( 'serve_static_settings', 'Cache Generator', 'Cache Generator', 'manage_options', 'serve_static_warmer', array( $this, 'warmer_callback'), 0);
    }

    public function warmer_callback(){
        $master_key = get_option('serve_static_master_key', false);
        $is_disabled = $master_key == 1 ? '' : 'disabled';
        ?>
        <div class="wrap serve-static-wrap">
            <h1 class="wp-heading-inline serve-static-wp-heading-inline">Static Cache Generator</h1>
            </br>
            <?php if( $master_key == 0 || $master_key === false ) { ?>
            </br>
            <div style="background-color: #900000; color: white; text-align: center; padding: 20px;">
                <b>Kindly enable the Plugin Functionality from Serve Static > Settings > Enable Plugin Functionality</b>
            </div>
            </br>
            <?php } ?>
            <button id="serve-static-send-requests-button" class="action-button" <?php echo esc_attr($is_disabled); ?> >Create Cache Files</button>
            </br>
            <div id="serve-static-request-progress-container" class="serve-static-notification" style="display: none;">
                <div id="serve-static-request-progress-bar-container">
                    <div id="serve-static-request-progress-bar" style="width: 0%;"></div>
                </div>
                <div id="serve-static-request-progress-text">In Progress.... Done: 0/0</div>
            </div>
            <div id="serve-static-request-success" class="serve-static-notification serve-static-success">
                <?php
                    $warmup_ajax = new WarmUpAjax;
                    $logs = $warmup_ajax->GetLogs(); // Get logs from custom DB table.
                    $all_done = get_option('serve_static_log_all_done');
                    $failed_count = get_option('serve_statis_failed_requests_count', 0);
                    if ( $all_done && $all_done == 1 ) { ?>
                        <p class="serve-static-success"><b>All Done...</b></p>
                        <p class="serve-static-error">Failed requests: <?php echo (int) $failed_count; ?></p>
                    <?php } elseif ( ! empty( $logs ) ) { ?>
                        <p class="serve-static-error"><b>Last Warmup was incomplete...</b></p>
                    <?php }
                ?>
            </div>
            <div id="serve-static-request-status" class="request-status">
                <table id="serve-static-request-table">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($logs)) {
                            foreach ($logs as $log) {
                                ?>
                                <tr>
                                    <td><?php echo esc_attr( $log->log_url ) ?></td>
                                    <td><?php echo wp_kses( $log->log_status, array( 'b' => array( 'style' => array(), ), ) ) ?></td>
                                </tr>
                            <?php }
                        }
                        ?>
                        <!-- Table rows will be dynamically populated here -->
                    </tbody>
                </table>
            </div>
        </div>
    <?php 
        // Warm Cache from Admin Toolbar.
        if (isset($_GET['action']) && $_GET['action'] === 'warm_cache' && isset($_GET['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

            if (wp_verify_nonce($nonce, 'serve_static_warm_cache')) {
                // Inject JavaScript to auto-click the button
                ?>
                <script type="text/javascript">
                    setTimeout(() => {
                        document.getElementById("serve-static-send-requests-button").click();
                    }, 500 );
                </script>
                <?php
            }
        }
    }

    public function warmer_remove_admin_notices() {

        $current_url = 'http' . ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 's' : '' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        $base_url = admin_url('admin.php?page=serve_static_warmer');

        if ( false !== strpos( $current_url, $base_url ) ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
        }
    }
}

$warmer_admin = new WarmerAdmin();
add_action('admin_menu', array($warmer_admin, 'warmer_sub_menu'));
add_action( 'in_admin_header', array( $warmer_admin, 'warmer_remove_admin_notices' ), 20 );