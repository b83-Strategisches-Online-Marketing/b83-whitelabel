<?php
/**
 * Fußzeile, Werkzeugleiste und Login-Seite.
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel\Admin;

use B83\Whitelabel\Agency;
use B83\Whitelabel\Menu;
use B83\Whitelabel\Settings;

defined( 'ABSPATH' ) || exit;

final class Branding {

	public function register(): void {
		add_filter( 'admin_footer_text', array( $this, 'footer_left' ), 99 );
		add_filter( 'update_footer', array( $this, 'footer_right' ), 99 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 999 );

		if ( Settings::value( 'login_logo_enabled' ) ) {
			add_action( 'login_enqueue_scripts', array( $this, 'login_styles' ) );
			add_filter( 'login_headerurl', array( $this, 'login_url' ) );
			add_filter( 'login_headertext', array( $this, 'login_title' ) );
		}
	}

	/* ---------------------------------------------------------------------
	 * Fußzeile
	 * ------------------------------------------------------------------- */

	public function footer_left(): string {
		/**
		 * Text links in der Fußzeile des Backends.
		 *
		 * @param string $text Text mit einfachem HTML.
		 */
		$text = (string) apply_filters( 'b83_wl_footer_text', (string) Settings::value( 'footer_text' ) );
		return '<span id="footer-thankyou">' . wp_kses( $text, Settings::inline_html() ) . '</span>';
	}

	/**
	 * Rechts steht sonst die WordPress-Version: b83-Benutzer sehen sie
	 * weiterhin, alle anderen den eingestellten Text.
	 *
	 * @param string $content Bisheriger Inhalt.
	 */
	public function footer_right( $content ): string {
		if ( Agency::is_agency() ) {
			return (string) $content;
		}
		return wp_kses( (string) Settings::value( 'footer_right' ), Settings::inline_html() );
	}

	/* ---------------------------------------------------------------------
	 * Werkzeugleiste
	 * ------------------------------------------------------------------- */

	/** @param \WP_Admin_Bar $bar Werkzeugleiste. */
	public function admin_bar( $bar ): void {
		if ( ! is_object( $bar ) ) {
			return;
		}
		if ( Settings::value( 'hide_wp_logo' ) ) {
			$bar->remove_node( 'wp-logo' );
		}
		if ( Agency::is_agency() ) {
			return;
		}
		// Updates erledigt b83, Kommentare nur, wenn freigegeben.
		$bar->remove_node( 'updates' );
		if ( ! Menu::top_allowed( 'edit-comments.php' ) ) {
			$bar->remove_node( 'comments' );
		}
	}

	/* ---------------------------------------------------------------------
	 * Login
	 * ------------------------------------------------------------------- */

	public function login_styles(): void {
		$logo = $this->logo();
		wp_enqueue_style( 'b83-wl-login', B83_WL_URL . 'assets/css/login.css', array(), B83_WL_VERSION );
		wp_add_inline_style(
			'b83-wl-login',
			sprintf(
				'body.login div#login h1 a{background-image:url("%1$s");width:%2$dpx;height:%3$dpx;background-size:contain}',
				esc_url_raw( $logo['url'] ),
				$logo['width'],
				$logo['height']
			)
		);
	}

	/**
	 * Logo-Adresse und Anzeigegröße.
	 *
	 * @return array{url:string,width:int,height:int}
	 */
	private function logo(): array {
		$width = (int) Settings::value( 'login_logo_width' );
		$width = $width > 0 ? $width : 120;
		$url   = B83_WL_URL . 'assets/img/b83-logo.svg';
		$ratio = 1.0;

		$id = (int) Settings::value( 'login_logo_id' );
		if ( $id > 0 ) {
			$image = wp_get_attachment_image_src( $id, 'medium' );
			if ( is_array( $image ) && ! empty( $image[0] ) ) {
				$url = (string) $image[0];
				if ( ! empty( $image[1] ) && ! empty( $image[2] ) ) {
					$ratio = (int) $image[2] / (int) $image[1];
				}
			}
		}

		/**
		 * Adresse des Login-Logos.
		 *
		 * @param string $url Bild-URL.
		 */
		$url = (string) apply_filters( 'b83_wl_login_logo_url', $url );

		return array(
			'url'    => $url,
			'width'  => $width,
			'height' => max( 20, (int) round( $width * $ratio ) ),
		);
	}

	public function login_url(): string {
		$url = (string) Settings::value( 'login_url' );
		return '' !== $url ? $url : home_url( '/' );
	}

	public function login_title(): string {
		$title = (string) Settings::value( 'login_title' );
		return '' !== $title ? $title : (string) get_bloginfo( 'name' );
	}
}
