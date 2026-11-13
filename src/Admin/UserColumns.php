<?php
/**
 * Spalte „Registriert“ in der Benutzerliste, sortierbar.
 *
 * WordPress kann Benutzer nach dem Registrierungsdatum sortieren
 * (orderby=registered), zeigt das Datum aber nirgends an.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel\Admin;

use B83\Whitelabel\Agency;
use B83\Whitelabel\Settings;

defined( 'ABSPATH' ) || exit;

final class UserColumns {

	public const COLUMN = 'b83_registered';

	public function register(): void {
		add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'render' ), 10, 3 );
		add_filter( 'manage_users_sortable_columns', array( $this, 'sortable' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/** Spalte für den aktuellen Benutzer anzeigen? */
	private function visible(): bool {
		$mode = (string) Settings::value( 'user_registered_column' );
		if ( 'all' === $mode ) {
			return true;
		}
		return 'agency' === $mode && Agency::is_agency();
	}

	/**
	 * @param array<string,string> $columns Spalten.
	 * @return array<string,string>
	 */
	public function add_column( $columns ): array {
		$columns = (array) $columns;
		if ( $this->visible() ) {
			$columns[ self::COLUMN ] = __( 'Registriert', 'b83-whitelabel' );
		}
		return $columns;
	}

	/**
	 * @param string $output  Bisherige Ausgabe.
	 * @param string $column  Spalten-ID.
	 * @param int    $user_id Benutzer-ID.
	 */
	public function render( $output, $column, $user_id ): string {
		if ( self::COLUMN !== $column ) {
			return (string) $output;
		}
		$user = get_userdata( (int) $user_id );
		if ( ! $user || empty( $user->user_registered ) || '0000-00-00 00:00:00' === $user->user_registered ) {
			return '–';
		}

		// user_registered ist in UTC gespeichert.
		$timestamp = strtotime( $user->user_registered . ' UTC' );
		if ( false === $timestamp ) {
			return '–';
		}

		return sprintf(
			'<time datetime="%1$s">%2$s<br><span class="description">%3$s</span></time>',
			esc_attr( gmdate( 'c', $timestamp ) ),
			esc_html( (string) wp_date( (string) get_option( 'date_format' ), $timestamp ) ),
			esc_html( (string) wp_date( (string) get_option( 'time_format' ), $timestamp ) )
		);
	}

	/**
	 * Beim ersten Klick die neuesten zuerst.
	 *
	 * @param array<string,mixed> $columns Sortierbare Spalten.
	 * @return array<string,mixed>
	 */
	public function sortable( $columns ): array {
		$columns = (array) $columns;
		if ( $this->visible() ) {
			$columns[ self::COLUMN ] = array( 'registered', true );
		}
		return $columns;
	}

	public function enqueue( string $hook ): void {
		if ( 'users.php' === $hook && $this->visible() ) {
			wp_enqueue_style( 'b83-wl-users', B83_WL_URL . 'assets/css/users.css', array(), B83_WL_VERSION );
		}
	}
}
