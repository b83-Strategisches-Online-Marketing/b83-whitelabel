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

		add_action( 'login_enqueue_scripts', array( $this, 'login_styles' ) );
		add_filter( 'login_body_class', array( $this, 'login_body_class' ) );
		add_action( 'login_footer', array( $this, 'login_credit' ) );

		if ( Settings::value( 'login_logo_enabled' ) ) {
			add_filter( 'login_headerurl', array( $this, 'login_url' ) );
			add_filter( 'login_headertext', array( $this, 'login_title' ) );
		}
	}

	/**
	 * Gestaltung der Login-Seite: dark, light, image oder off.
	 * „image“ ohne gewähltes Bild fällt auf „dark“ zurück.
	 */
	private function login_style(): string {
		$style = (string) Settings::value( 'login_style' );
		if ( ! in_array( $style, array( 'dark', 'light', 'image', 'off' ), true ) ) {
			$style = 'dark';
		}
		if ( 'image' === $style && '' === $this->login_background() ) {
			$style = 'dark';
		}
		return $style;
	}

	private function login_background(): string {
		$id  = (int) Settings::value( 'login_bg_id' );
		$url = $id > 0 ? (string) wp_get_attachment_image_url( $id, 'full' ) : '';

		/**
		 * Adresse des Hintergrundbilds der Login-Seite.
		 *
		 * @param string $url Bild-URL (leer = kein Bild).
		 */
		return (string) apply_filters( 'b83_wl_login_background_url', $url );
	}

	/**
	 * @param string[] $classes Klassen am body der Login-Seite.
	 * @return string[]
	 */
	public function login_body_class( $classes ): array {
		$classes = (array) $classes;
		if ( Settings::value( 'login_logo_enabled' ) ) {
			$classes[] = 'b83-wl-logo';
		}
		$style = $this->login_style();
		if ( 'off' !== $style ) {
			$classes[] = 'b83-wl-login';
			$classes[] = 'b83-wl-login--' . $style;
			if ( ! Settings::value( 'login_animate' ) ) {
				$classes[] = 'b83-wl-login--still';
			}
		}
		return $classes;
	}

	/** Hinweis unter dem Login-Formular (Text der Fußzeile). */
	public function login_credit(): void {
		if ( 'off' === $this->login_style() || ! Settings::value( 'login_credit' ) ) {
			return;
		}
		$text = (string) apply_filters( 'b83_wl_footer_text', (string) Settings::value( 'footer_text' ) );
		if ( '' !== trim( wp_strip_all_tags( $text ) ) ) {
			echo '<div class="b83-wl-login__credit">' . wp_kses( $text, Settings::inline_html() ) . '</div>';
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
		$logo_on = (bool) Settings::value( 'login_logo_enabled' );
		$style   = $this->login_style();
		if ( ! $logo_on && 'off' === $style ) {
			return;
		}

		wp_enqueue_style( 'b83-wl-login', B83_WL_URL . 'assets/css/login.css', array( 'login' ), B83_WL_VERSION );

		$inline = '';
		if ( $logo_on ) {
			$logo    = $this->logo();
			$inline .= sprintf(
				'body.login div#login h1 a{background-image:url("%1$s");width:%2$dpx;height:%3$dpx;background-size:contain}',
				esc_url_raw( $logo['url'] ),
				$logo['width'],
				$logo['height']
			);
		}
		if ( 'image' === $style ) {
			$inline .= sprintf( 'body.login.b83-wl-login--image{background-image:url("%s")}', esc_url_raw( $this->login_background() ) );
		}
		if ( '' !== $inline ) {
			wp_add_inline_style( 'b83-wl-login', $inline );
		}
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
