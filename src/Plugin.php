<?php
/**
 * Startet alle Bausteine des Plugins.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		load_plugin_textdomain( 'b83-whitelabel', false, dirname( plugin_basename( B83_WL_FILE ) ) . '/languages' );

		add_action( 'admin_init', array( Migration::class, 'maybe_run' ) );

		( new Updater() )->register();

		if ( is_admin() ) {
			( new Admin\B83Menu( B83_WL_URL, B83_WL_DIR ) )->register();
			( new Admin\SettingsPage() )->register();
		}

		/*
		 * Notbremse: define( 'B83_WL_DISABLE', true ); in der wp-config.php
		 * schaltet alle Anpassungen ab, die Einstellungsseite bleibt erreichbar.
		 */
		if ( self::disabled() ) {
			return;
		}

		( new Admin\Branding() )->register();

		if ( is_admin() ) {
			( new Admin\Dashboard() )->register();
			( new Admin\MenuFilter() )->register();
			( new Admin\UserColumns() )->register();
		}
	}

	public static function disabled(): bool {
		return defined( 'B83_WL_DISABLE' ) && B83_WL_DISABLE;
	}
}
