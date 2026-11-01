<?php
/**
 * Dashboard: eigenes Hinweis-Widget, alle anderen nur nach Freigabe.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel\Admin;

use B83\Whitelabel\Agency;
use B83\Whitelabel\Settings;

defined( 'ABSPATH' ) || exit;

final class Dashboard {

	public function register(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'add_notice' ), 5 );
		add_action( 'wp_dashboard_setup', array( $this, 'filter_widgets' ), PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function add_notice(): void {
		if ( '' === trim( wp_strip_all_tags( (string) Settings::value( 'notice_text' ) ) ) ) {
			return;
		}
		$title = (string) Settings::value( 'notice_title' );
		wp_add_dashboard_widget(
			Settings::NOTICE_WIDGET,
			'' !== $title ? esc_html( $title ) : esc_html__( 'Kundeninformation', 'b83-whitelabel' ),
			array( $this, 'render_notice' ),
			null,
			null,
			'normal',
			'high'
		);
	}

	public function render_notice(): void {
		echo '<div class="b83-wl-notice">' . wp_kses_post( (string) Settings::value( 'notice_text' ) ) . '</div>';
	}

	public function enqueue( string $hook ): void {
		if ( 'index.php' === $hook ) {
			wp_enqueue_style( 'b83-wl-dashboard', B83_WL_URL . 'assets/css/dashboard.css', array(), B83_WL_VERSION );
		}
	}

	/**
	 * Widgets, die immer bleiben: eigener Hinweis und die WordPress-Warnungen
	 * zu veralteter PHP-Version bzw. veraltetem Browser.
	 *
	 * @return string[]
	 */
	public static function always_allowed(): array {
		return array( Settings::NOTICE_WIDGET, 'dashboard_php_nag', 'dashboard_browser_nag' );
	}

	/**
	 * Standard, solange die Widgets nicht eingestellt wurden.
	 *
	 * @param string $id Widget-ID.
	 */
	public static function default_allowed( string $id ): bool {
		$defaults = (array) apply_filters( 'b83_wl_default_widgets', array() );
		return in_array( $id, $defaults, true ) || str_starts_with( $id, 'b83' );
	}

	public static function allowed( string $id ): bool {
		if ( in_array( $id, self::always_allowed(), true ) ) {
			return true;
		}
		if ( Settings::value( 'widgets_configured' ) ) {
			return in_array( $id, (array) Settings::value( 'allowed_widgets' ), true );
		}
		return self::default_allowed( $id );
	}

	public function filter_widgets(): void {
		global $wp_meta_boxes;

		$boxes = (array) ( $wp_meta_boxes['dashboard'] ?? array() );
		$agency = Agency::is_agency();

		// Bestandsaufnahme für die Einstellungsseite (nur b83-Benutzer).
		if ( $agency ) {
			$known = array();
			foreach ( $boxes as $priorities ) {
				foreach ( (array) $priorities as $widgets ) {
					foreach ( (array) $widgets as $id => $box ) {
						if ( is_array( $box ) && Settings::NOTICE_WIDGET !== $id ) {
							$known[ (string) $id ] = trim( wp_strip_all_tags( (string) ( $box['title'] ?? $id ) ) );
						}
					}
				}
			}
			if ( get_option( Settings::OPTION_KNOWN_WIDGETS, array() ) !== $known ) {
				update_option( Settings::OPTION_KNOWN_WIDGETS, $known, false );
			}
		}

		if ( $agency && ! Settings::value( 'dashboard_for_agency' ) ) {
			return;
		}

		if ( Settings::value( 'remove_welcome' ) ) {
			remove_action( 'welcome_panel', 'wp_welcome_panel' );
		}

		foreach ( $boxes as $context => $priorities ) {
			foreach ( (array) $priorities as $widgets ) {
				foreach ( (array) $widgets as $id => $box ) {
					if ( is_array( $box ) && ! self::allowed( (string) $id ) ) {
						remove_meta_box( (string) $id, 'dashboard', (string) $context );
					}
				}
			}
		}
	}
}
