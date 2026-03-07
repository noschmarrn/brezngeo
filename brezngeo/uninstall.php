<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
delete_option( 'brezngeo_settings' );
delete_post_meta_by_key( '_brezngeo_meta_description' );
