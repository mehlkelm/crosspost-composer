<?php
/**
 * Remove the plugin's stored settings when it is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'crosspost_composer_settings' );
