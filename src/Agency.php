<?php
/**
 * Wer zählt als b83-Benutzer (sieht alles, darf die Einstellungen ändern)?
 *
 * In den Einstellungen werden Administratoren als b83-Benutzer markiert.
 * Solange niemand markiert ist, gelten alle Administratoren als b83-Benutzer –
 * so sperrt sich nach der Installation niemand versehentlich aus.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel;

defined( 'ABSPATH' ) || exit;

final class Agency {

	public static function is_agency( int $user_id = 0 ): bool {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}

		if ( is_multisite() && is_super_admin( $user_id ) ) {
			$result = true;
		} else {
			$list   = self::users();
			$result = empty( $list )
				? user_can( $user_id, 'manage_options' )
				: in_array( $user_id, $list, true ) && user_can( $user_id, 'manage_options' );
		}

		/**
		 * Ob ein Benutzer als b83-Benutzer gilt.
		 *
		 * @param bool $result  Ergebnis.
		 * @param int  $user_id Benutzer-ID.
		 */
		return (bool) apply_filters( 'b83_wl_is_agency', $result, $user_id );
	}

	/** @return int[] */
	public static function users(): array {
		return array_values( array_filter( array_map( 'intval', (array) Settings::value( 'agency_users' ) ) ) );
	}

	/** Noch niemand ausdrücklich als b83-Benutzer markiert? */
	public static function unconfigured(): bool {
		return empty( self::users() );
	}
}
