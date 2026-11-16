<?php
/**
 * Farbschema „b83“ für das Backend.
 *
 * Nutzt die Schnittstelle von WordPress für Admin-Farbschemata
 * (wp_admin_css_color). Je nach Einstellung wird das Schema allen
 * Benutzern vorgegeben oder nur im Profil zur Auswahl angeboten.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel\Admin;

use B83\Whitelabel\Settings;

defined( 'ABSPATH' ) || exit;

final class ColorScheme {

	public const SLUG = 'b83';

	public function register(): void {
		$mode = (string) Settings::value( 'admin_scheme' );
		if ( 'off' === $mode ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'add_scheme' ), 5 );

		if ( 'force' === $mode ) {
			add_filter( 'get_user_option_admin_color', array( $this, 'force' ) );
			add_action( 'admin_init', array( $this, 'remove_picker' ), 20 );
		}
	}

	public function add_scheme(): void {
		$suffix = is_rtl() ? '-rtl' : '';
		wp_admin_css_color(
			self::SLUG,
			'b83',
			add_query_arg( 'ver', B83_WL_VERSION, B83_WL_URL . 'assets/css/colors/b83' . $suffix . '.css' ),
			array( '#2a2622', '#161412', '#d97a0c', '#b3341f' ),
			array(
				'base'    => '#f4f2f0',
				'focus'   => '#fff',
				'current' => '#fff',
			)
		);
	}

	public function force(): string {
		return self::SLUG;
	}

	public function remove_picker(): void {
		remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
	}
}
