<?php
/**
 * Reduziertes Admin-Menü für alle außer den b83-Benutzern.
 *
 * Für b83-Benutzer wird nur eine Bestandsaufnahme des Menüs gespeichert,
 * damit die Einstellungsseite alle Punkte zum Anhaken zeigen kann.
 * Alle anderen sehen nur freigegebene Punkte; nicht freigegebene Seiten
 * werden auf Wunsch zusätzlich gesperrt (Umleitung aufs Dashboard).
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel\Admin;

use B83\Whitelabel\Agency;
use B83\Whitelabel\Menu;
use B83\Whitelabel\Settings;

defined( 'ABSPATH' ) || exit;

final class MenuFilter {

	/** Slugs, die im Menü des aktuellen Benutzers ausgeblendet wurden. @var string[] */
	private array $hidden = array();

	/** Slugs, die sichtbar bleiben. @var string[] */
	private array $visible = array();

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'snapshot' ), PHP_INT_MAX - 10 );
		add_action( 'admin_menu', array( $this, 'filter' ), PHP_INT_MAX );
		add_action( 'current_screen', array( $this, 'maybe_block' ) );
		add_action( 'admin_notices', array( $this, 'blocked_notice' ) );
	}

	public function snapshot(): void {
		if ( Agency::is_agency() ) {
			Menu::store_snapshot();
		}
	}

	public function filter(): void {
		if ( Agency::is_agency() ) {
			return;
		}

		foreach ( Menu::read_current() as $top ) {
			$parent = $top['slug'];
			if ( ! Menu::top_allowed( $parent ) ) {
				$this->hidden[] = $parent;
				foreach ( array_keys( $top['children'] ) as $child ) {
					$this->hidden[] = $child;
				}
				remove_menu_page( $parent );
				continue;
			}
			$this->visible[] = $parent;
			foreach ( array_keys( $top['children'] ) as $child ) {
				if ( Menu::child_allowed( $parent, $child ) ) {
					$this->visible[] = $child;
				} else {
					$this->hidden[] = $child;
					remove_submenu_page( $parent, $child );
				}
			}
		}

		$this->tidy_separators();
	}

	/** Trennlinien entfernen, die nach dem Ausblenden doppelt oder am Rand stehen. */
	private function tidy_separators(): void {
		global $menu;

		$previous_was_separator = true;
		$last_key               = null;
		foreach ( (array) $menu as $key => $item ) {
			$is_separator = is_array( $item ) && str_starts_with( (string) ( $item[2] ?? '' ), 'separator' );
			if ( $is_separator && $previous_was_separator ) {
				unset( $menu[ $key ] );
				continue;
			}
			$previous_was_separator = $is_separator;
			$last_key               = $key;
		}
		if ( null !== $last_key && $previous_was_separator ) {
			unset( $menu[ $last_key ] );
		}
	}

	/* ---------------------------------------------------------------------
	 * Sperre
	 * ------------------------------------------------------------------- */

	public function maybe_block(): void {
		if ( empty( $this->hidden ) || ! Settings::value( 'block_hidden' ) || Agency::is_agency() || wp_doing_ajax() ) {
			return;
		}

		$blocked = false;
		foreach ( array_unique( $this->hidden ) as $slug ) {
			if ( $this->matches( $slug ) ) {
				$blocked = true;
				break;
			}
		}
		if ( ! $blocked ) {
			return;
		}
		// Ein sichtbarer Menüpunkt, der genau diese Seite meint, hat Vorrang.
		foreach ( array_unique( $this->visible ) as $slug ) {
			if ( $this->matches( $slug ) ) {
				return;
			}
		}

		wp_safe_redirect( add_query_arg( 'b83_wl_msg', 'blocked', admin_url( 'index.php' ) ) );
		exit;
	}

	/**
	 * Gehört die aufgerufene Seite zu diesem Menü-Slug?
	 *
	 * @param string $slug Menü-Slug, z. B. "upload.php", "edit.php?post_type=page" oder "elementor".
	 */
	private function matches( string $slug ): bool {
		global $pagenow;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- nur Vergleich, keine Verarbeitung.
		$query = array_map(
			static fn( $v ) => is_scalar( $v ) ? sanitize_text_field( wp_unslash( (string) $v ) ) : '',
			$_GET
		);
		// phpcs:enable

		if ( ! str_contains( $slug, '.php' ) ) {
			// Plugin-Seite: admin.php?page=… oder parent.php?page=…
			return isset( $query['page'] ) && $query['page'] === $slug;
		}

		$path = (string) wp_parse_url( $slug, PHP_URL_PATH );
		if ( basename( $path ) !== (string) $pagenow ) {
			return false;
		}
		parse_str( (string) wp_parse_url( $slug, PHP_URL_QUERY ), $args );

		foreach ( $args as $name => $value ) {
			if ( ! isset( $query[ $name ] ) || (string) $query[ $name ] !== (string) $value ) {
				return false;
			}
		}
		// "edit.php" allein meint Beiträge, nicht edit.php?post_type=page.
		foreach ( array( 'page', 'post_type', 'taxonomy' ) as $important ) {
			if ( ! isset( $args[ $important ] ) && isset( $query[ $important ] ) && '' !== $query[ $important ] ) {
				// Beiträge (post) sind auch ohne post_type gemeint.
				if ( 'post_type' === $important && 'post' === $query[ $important ] ) {
					continue;
				}
				return false;
			}
		}
		return true;
	}

	public function blocked_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- fester Schlüssel, kein Freitext.
		if ( isset( $_GET['b83_wl_msg'] ) && 'blocked' === $_GET['b83_wl_msg'] ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Diese Seite ist auf Ihrer Website nicht freigegeben. Bei Fragen hilft b83 gerne weiter: office@b83.at', 'b83-whitelabel' ) . '</p></div>';
		}
	}
}
