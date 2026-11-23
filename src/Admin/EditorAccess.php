<?php
/**
 * Redakteure dürfen die Elementor-Übermittlungen sehen.
 *
 * Elementor verlangt für sein Menü und die Seite „Übermittlungen“ die
 * Berechtigung manage_options. Redakteure sehen beides deshalb nie, auch
 * wenn der Punkt im Reiter „Menü“ freigegeben ist. Ist die Option aktiv,
 * wird die Berechtigung dieser beiden Menüeinträge auf edit_pages gesenkt.
 * Es werden keine Rollen verändert und keine anderen Elementor-Seiten geöffnet.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel\Admin;

use B83\Whitelabel\Settings;

defined( 'ABSPATH' ) || exit;

final class EditorAccess {

	/** Berechtigung, die für die freigegebenen Einträge verlangt wird. */
	private const CAPABILITY = 'edit_pages';

	public function register(): void {
		// Vor MenuFilter (PHP_INT_MAX - 10 / PHP_INT_MAX) und vor der Rechteprüfung von WordPress.
		add_action( 'admin_menu', array( $this, 'lower_capabilities' ), PHP_INT_MAX - 20 );
	}

	/**
	 * Menü-Slugs, deren Berechtigung gesenkt wird.
	 *
	 * @return string[]
	 */
	private function slugs(): array {
		return array_map(
			'strval',
			(array) apply_filters( 'b83_wl_editor_menu_slugs', array( 'elementor', 'e-form-submissions' ) )
		);
	}

	public function lower_capabilities(): void {
		global $menu, $submenu;

		if ( ! Settings::value( 'editor_submissions' ) || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$slugs = $this->slugs();

		foreach ( (array) $menu as $key => $item ) {
			if ( is_array( $item ) && in_array( (string) ( $item[2] ?? '' ), $slugs, true ) ) {
				$menu[ $key ][1] = self::CAPABILITY;
			}
		}

		// Nur der Hauptpunkt „elementor“ und die Seite „Übermittlungen“, keine weiteren Unterpunkte.
		foreach ( (array) ( $submenu['elementor'] ?? array() ) as $key => $item ) {
			if ( is_array( $item ) && 'e-form-submissions' === (string) ( $item[2] ?? '' ) ) {
				$submenu['elementor'][ $key ][1] = self::CAPABILITY;
			}
		}
	}
}
