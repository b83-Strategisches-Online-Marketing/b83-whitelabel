<?php
/**
 * Admin-Menü: Bestandsaufnahme und Freigaberegeln.
 *
 * Schlüssel: Hauptpunkt = Slug (z. B. "upload.php"),
 * Unterpunkt = Slug des Hauptpunkts . "::" . Slug des Unterpunkts.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel;

defined( 'ABSPATH' ) || exit;

final class Menu {

	/**
	 * Aktuelles Menü aus $menu/$submenu auslesen.
	 *
	 * @return array<int, array{slug:string,title:string,children:array<string,string>}>
	 */
	public static function read_current(): array {
		global $menu, $submenu;

		$tree = array();
		foreach ( (array) $menu as $item ) {
			if ( ! is_array( $item ) || empty( $item[2] ) ) {
				continue;
			}
			$slug = (string) $item[2];
			if ( str_starts_with( $slug, 'separator' ) || '' === trim( (string) ( $item[0] ?? '' ) ) ) {
				continue;
			}
			$children = array();
			foreach ( (array) ( $submenu[ $slug ] ?? array() ) as $sub ) {
				if ( is_array( $sub ) && ! empty( $sub[2] ) ) {
					$children[ (string) $sub[2] ] = self::clean_title( (string) ( $sub[0] ?? '' ) );
				}
			}
			$tree[] = array(
				'slug'     => $slug,
				'title'    => self::clean_title( (string) $item[0] ),
				'children' => $children,
			);
		}
		return $tree;
	}

	/** Zähler-Blasen (Updates, Kommentare …) und HTML aus Menütiteln entfernen. */
	public static function clean_title( string $title ): string {
		$title = (string) preg_replace( '#<span[^>]*class="[^"]*(count|pending|awaiting|update-plugins|menu-counter)[^"]*"[^>]*>.*?</span>(\s*</span>)?#is', '', $title );
		$title = trim( wp_strip_all_tags( $title ) );
		return '' !== $title ? $title : __( '(ohne Titel)', 'b83-whitelabel' );
	}

	/** Bestandsaufnahme speichern, wenn sich das Menü geändert hat. */
	public static function store_snapshot(): void {
		$tree   = self::read_current();
		$stored = get_option( Settings::OPTION_KNOWN_MENU, array() );
		if ( $stored !== $tree ) {
			update_option( Settings::OPTION_KNOWN_MENU, $tree, false );
		}
	}

	/** @return array<int, array{slug:string,title:string,children:array<string,string>}> */
	public static function known(): array {
		$tree = get_option( Settings::OPTION_KNOWN_MENU, array() );
		return is_array( $tree ) ? $tree : array();
	}

	/** @return string[] */
	public static function known_keys(): array {
		$keys = array();
		foreach ( self::known() as $top ) {
			$keys[] = $top['slug'];
			foreach ( array_keys( $top['children'] ) as $child ) {
				$keys[] = $top['slug'] . Settings::SEP . $child;
			}
		}
		return $keys;
	}

	/* ---------------------------------------------------------------------
	 * Regeln
	 * ------------------------------------------------------------------- */

	/**
	 * Hauptpunkte, die immer sichtbar bleiben.
	 *
	 * @return string[]
	 */
	public static function always_allowed(): array {
		return (array) apply_filters( 'b83_wl_always_allowed_pages', array( 'index.php', 'profile.php' ) );
	}

	/**
	 * Standard-Freigaben, solange das Menü nicht eingestellt wurde.
	 *
	 * @return string[]
	 */
	public static function default_tops(): array {
		return (array) apply_filters(
			'b83_wl_default_menu',
			array(
				'index.php',
				'edit.php',
				'edit.php?post_type=page',
				'upload.php',
				'profile.php',
				'edit.php?post_type=elementor_library',
			)
		);
	}

	/**
	 * Unterpunkte, die standardmäßig ausgeblendet sind.
	 *
	 * @return string[]
	 */
	public static function default_hidden_children(): array {
		return (array) apply_filters( 'b83_wl_default_hidden_children', array( 'update-core.php' ) );
	}

	public static function configured(): bool {
		return (bool) Settings::value( 'menu_configured' );
	}

	public static function top_allowed( string $slug ): bool {
		if ( in_array( $slug, self::always_allowed(), true ) ) {
			return true;
		}
		if ( self::configured() ) {
			return in_array( $slug, (array) Settings::value( 'allowed_menu' ), true );
		}
		return in_array( $slug, self::default_tops(), true );
	}

	public static function child_allowed( string $parent, string $child ): bool {
		if ( ! self::top_allowed( $parent ) ) {
			return false;
		}
		// Der erste Unterpunkt ist meist der Hauptpunkt selbst.
		if ( $parent === $child ) {
			return true;
		}
		if ( self::configured() ) {
			$key = $parent . Settings::SEP . $child;
			if ( in_array( $key, (array) Settings::value( 'allowed_menu' ), true ) ) {
				return true;
			}
			// Unterpunkte von Immer-sichtbar-Punkten, die beim Speichern unbekannt waren (z. B. nur bei Redakteuren vorhanden).
			return in_array( $parent, self::always_allowed(), true )
				&& ! in_array( $key, (array) Settings::value( 'menu_seen' ), true )
				&& ! in_array( $child, self::default_hidden_children(), true );
		}
		return ! in_array( $child, self::default_hidden_children(), true );
	}
}
