<?php
/**
 * Redakteure dürfen die Elementor-Übermittlungen sehen.
 *
 * Elementor Pro verlangt für die Seite „Übermittlungen“ (Slug e-form-submissions)
 * und für ihre REST-Routen (elementor-pro/v1/form-submissions und /forms) die
 * Berechtigung manage_options. Redakteure sehen den Punkt deshalb nie, auch
 * wenn er im Reiter „Menü“ freigegeben ist.
 *
 * Ist die Option aktiv, passiert für Benutzer mit edit_pages (Redakteure):
 * 1. Menü: Der Eintrag „Übermittlungen“ und sein Hauptpunkt (je nach Elementor-
 *    Version „Elementor“ oder „Elementor Home“) verlangen nur noch edit_pages.
 * 2. REST: Nur während einer Anfrage an diese beiden Routen gilt manage_options
 *    als vorhanden.
 * Rollen werden nicht verändert, andere Elementor-Seiten bleiben gesperrt.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel\Admin;

use B83\Whitelabel\Settings;

defined( 'ABSPATH' ) || exit;

final class EditorAccess {

	/** Berechtigung, die für den Menüeintrag verlangt wird. */
	private const CAPABILITY = 'edit_pages';

	/** Seiten-Slug der Elementor-Übermittlungen. */
	private const PAGE = 'e-form-submissions';

	/** REST-Routen (Anfang), für die manage_options zugestanden wird. */
	private const ROUTES = array(
		'/elementor-pro/v1/form-submissions',
		'/elementor-pro/v1/forms',
	);

	public function register(): void {
		// Vor MenuFilter (PHP_INT_MAX - 10 / PHP_INT_MAX) und vor der Rechteprüfung von WordPress.
		add_action( 'admin_menu', array( $this, 'lower_menu_capabilities' ), PHP_INT_MAX - 20 );
		add_filter( 'rest_request_before_callbacks', array( $this, 'rest_before' ), 10, 3 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'rest_after' ), 10, 3 );
	}

	private function active(): bool {
		return (bool) Settings::value( 'editor_submissions' ) && current_user_can( self::CAPABILITY );
	}

	public function lower_menu_capabilities(): void {
		global $menu, $submenu;

		if ( ! $this->active() ) {
			return;
		}

		foreach ( (array) $submenu as $parent => $items ) {
			foreach ( (array) $items as $key => $item ) {
				if ( ! is_array( $item ) || self::PAGE !== (string) ( $item[2] ?? '' ) ) {
					continue;
				}
				$submenu[ $parent ][ $key ][1] = self::CAPABILITY;

				// Hauptpunkt, unter dem „Übermittlungen“ hängt.
				foreach ( (array) $menu as $menu_key => $top ) {
					if ( is_array( $top ) && (string) ( $top[2] ?? '' ) === (string) $parent ) {
						$menu[ $menu_key ][1] = self::CAPABILITY;
					}
				}
			}
		}
	}

	/**
	 * Vor der Rechteprüfung der Route: manage_options zugestehen.
	 *
	 * @param mixed            $response Antwort (unverändert durchreichen).
	 * @param array            $handler  Handler der Route.
	 * @param \WP_REST_Request $request  Anfrage.
	 * @return mixed
	 */
	public function rest_before( $response, $handler, $request ) {
		if ( $request instanceof \WP_REST_Request && $this->is_submissions_route( $request->get_route() ) && $this->active() ) {
			add_filter( 'user_has_cap', array( $this, 'grant_manage_options' ), 10, 3 );
		}
		return $response;
	}

	/**
	 * Nach der Anfrage: Zugeständnis wieder entfernen.
	 *
	 * @param mixed $response Antwort (unverändert durchreichen).
	 * @return mixed
	 */
	public function rest_after( $response ) {
		remove_filter( 'user_has_cap', array( $this, 'grant_manage_options' ), 10 );
		return $response;
	}

	/**
	 * @param array<string,bool> $allcaps Berechtigungen des Benutzers.
	 * @param string[]           $caps    Geprüfte Berechtigungen.
	 * @param array<int,mixed>   $args    Argumente (0 = Berechtigung).
	 * @return array<string,bool>
	 */
	public function grant_manage_options( $allcaps, $caps, $args ) {
		if ( isset( $args[0] ) && 'manage_options' === $args[0] ) {
			$allcaps['manage_options'] = true;
		}
		return $allcaps;
	}

	private function is_submissions_route( string $route ): bool {
		foreach ( self::ROUTES as $prefix ) {
			if ( $route === $prefix || str_starts_with( $route, $prefix . '/' ) ) {
				return true;
			}
		}
		return false;
	}
}
