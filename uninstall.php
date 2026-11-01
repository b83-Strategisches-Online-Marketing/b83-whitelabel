<?php
/**
 * Deinstallation. Einstellungen werden nur entfernt, wenn das in den
 * Einstellungen ausdrücklich aktiviert wurde.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$b83_wl_settings = get_option( 'b83_whitelabel_settings', array() );

if ( is_array( $b83_wl_settings ) && ! empty( $b83_wl_settings['delete_on_uninstall'] ) ) {
	foreach ( array(
		'b83_whitelabel_settings',
		'b83_whitelabel_known_menu',
		'b83_whitelabel_known_widgets',
		'b83_whitelabel_db_version',
	) as $b83_wl_option ) {
		delete_option( $b83_wl_option );
	}
	delete_transient( 'b83_whitelabel_release' );
}
