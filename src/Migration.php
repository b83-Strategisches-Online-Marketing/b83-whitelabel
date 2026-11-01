<?php
/**
 * Installation und Datenbank-Versionen.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel;

defined( 'ABSPATH' ) || exit;

final class Migration {

	public const DB_VERSION        = 1;
	public const OPTION_DB_VERSION = 'b83_whitelabel_db_version';

	public static function on_activation(): void {
		self::run();

		// Wer das Plugin aktiviert, wird b83-Benutzer (falls noch niemand eingetragen ist).
		$user_id = get_current_user_id();
		if ( $user_id > 0 && user_can( $user_id, 'manage_options' ) && Agency::unconfigured() ) {
			Settings::update( array( 'agency_users' => array( $user_id ) ) );
		}
	}

	/** Läuft im Backend, tut aber nur bei Versionswechsel etwas. */
	public static function maybe_run(): void {
		if ( (int) get_option( self::OPTION_DB_VERSION, 0 ) < self::DB_VERSION ) {
			self::run();
		}
	}

	private static function run(): void {
		if ( false === get_option( Settings::OPTION, false ) ) {
			add_option( Settings::OPTION, Settings::defaults(), '', true );
		}
		update_option( self::OPTION_DB_VERSION, self::DB_VERSION, true );
	}
}
