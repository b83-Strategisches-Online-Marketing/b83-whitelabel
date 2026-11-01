<?php
/**
 * Einstellungsseite unter b83 → White Label (nur für b83-Benutzer).
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel\Admin;

use B83\Whitelabel\Agency;
use B83\Whitelabel\Menu;
use B83\Whitelabel\Plugin;
use B83\Whitelabel\Settings;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	public const PAGE   = 'b83-whitelabel';
	private const ACTION = 'b83_wl_save';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ), 20 );
		add_filter( 'b83_admin_plugin_links', array( $this, 'overview_link' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_notices', array( $this, 'setup_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( B83_WL_FILE ), array( $this, 'action_links' ) );
	}

	private function can_manage(): bool {
		return current_user_can( 'manage_options' ) && Agency::is_agency();
	}

	private function url( string $tab = 'general', array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => self::PAGE, 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
	}

	public function add_page(): void {
		if ( ! $this->can_manage() ) {
			return;
		}
		add_submenu_page(
			B83Menu::SLUG,
			__( 'b83 White Label', 'b83-whitelabel' ),
			__( 'White Label', 'b83-whitelabel' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * @param array<string,string> $links Plugin-Datei => URL.
	 * @return array<string,string>
	 */
	public function overview_link( $links ): array {
		$links = (array) $links;
		if ( $this->can_manage() ) {
			$links[ plugin_basename( B83_WL_FILE ) ] = $this->url();
		}
		return $links;
	}

	/**
	 * @param string[] $links Links in der Plugin-Liste.
	 * @return string[]
	 */
	public function action_links( $links ): array {
		$links = (array) $links;
		if ( $this->can_manage() ) {
			array_unshift( $links, '<a href="' . esc_url( $this->url() ) . '">' . esc_html__( 'Einstellungen', 'b83-whitelabel' ) . '</a>' );
		}
		return $links;
	}

	public function enqueue( string $hook ): void {
		if ( ! str_ends_with( $hook, '_page_' . self::PAGE ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'b83-wl-admin', B83_WL_URL . 'assets/css/admin.css', array(), B83_WL_VERSION );
		wp_enqueue_script( 'b83-wl-admin', B83_WL_URL . 'assets/js/admin.js', array( 'jquery' ), B83_WL_VERSION, true );
		wp_localize_script(
			'b83-wl-admin',
			'b83WlAdmin',
			array(
				'chooseLogo' => __( 'Login-Logo wählen', 'b83-whitelabel' ),
				'useLogo'    => __( 'Dieses Bild verwenden', 'b83-whitelabel' ),
				'defaultUrl' => B83_WL_URL . 'assets/img/b83-logo.svg',
			)
		);
	}

	/** Hinweis nach der Installation, bis das Menü einmal gespeichert wurde. */
	public function setup_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) || ! $this->can_manage() || Menu::configured() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'b83 White Label ist aktiv und verwendet die Standard-Freigaben für das Menü. Bitte einmal prüfen, welche Menüpunkte Kunden auf dieser Website sehen sollen.', 'b83-whitelabel' );
		echo ' <a href="' . esc_url( $this->url( 'menu' ) ) . '">' . esc_html__( 'Menü-Freigaben öffnen', 'b83-whitelabel' ) . '</a>';
		echo '</p></div>';
	}

	/* ---------------------------------------------------------------------
	 * Speichern
	 * ------------------------------------------------------------------- */

	public function save(): void {
		if ( ! $this->can_manage() ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'b83-whitelabel' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( (string) $_POST['tab'] ) ) : '';
		if ( ! in_array( $tab, array( 'general', 'dashboard', 'menu', 'users' ), true ) ) {
			wp_safe_redirect( $this->url() );
			exit;
		}

		// Auf Standard zurücksetzen (Menü bzw. Widgets).
		if ( isset( $_POST['b83_wl_reset'] ) ) {
			if ( 'menu' === $tab ) {
				Settings::update( array( 'allowed_menu' => array(), 'menu_seen' => array(), 'menu_configured' => false ) );
			} elseif ( 'dashboard' === $tab ) {
				Settings::update( array( 'allowed_widgets' => array(), 'widgets_configured' => false ) );
			}
			wp_safe_redirect( $this->url( $tab, array( 'b83_wl_msg' => 'reset' ) ) );
			exit;
		}

		$raw   = isset( $_POST['b83_wl'] ) && is_array( $_POST['b83_wl'] ) ? wp_unslash( $_POST['b83_wl'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wird in Settings::sanitize() geprüft.
		$clean = Settings::sanitize( $tab, (array) $raw );
		$msg   = 'saved';

		// Wer speichert, sperrt sich nicht selbst aus.
		if ( 'users' === $tab && ! empty( $clean['agency_users'] ) && ! in_array( get_current_user_id(), $clean['agency_users'], true ) ) {
			$clean['agency_users'][] = get_current_user_id();
			$msg                     = 'self_added';
		}

		Settings::update( $clean );
		wp_safe_redirect( $this->url( $tab, array( 'b83_wl_msg' => $msg ) ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Ausgabe
	 * ------------------------------------------------------------------- */

	public function render(): void {
		if ( ! $this->can_manage() ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'b83-whitelabel' ), '', array( 'response' => 403 ) );
		}

		$tabs = array(
			'general'   => __( 'Allgemein', 'b83-whitelabel' ),
			'dashboard' => __( 'Dashboard', 'b83-whitelabel' ),
			'menu'      => __( 'Menü', 'b83-whitelabel' ),
			'users'     => __( 'b83-Benutzer', 'b83-whitelabel' ),
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nur Anzeige.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'general';
		$tab = isset( $tabs[ $tab ] ) ? $tab : 'general';

		echo '<div class="wrap b83-wl">';
		echo '<h1>' . esc_html__( 'b83 White Label', 'b83-whitelabel' ) . '</h1>';

		$this->messages();

		if ( Plugin::disabled() ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'B83_WL_DISABLE ist in der wp-config.php gesetzt: Alle Anpassungen sind derzeit abgeschaltet.', 'b83-whitelabel' ) . '</p></div>';
		}

		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $key => $label ) {
			printf(
				'<a href="%1$s" class="nav-tab%2$s">%3$s</a>',
				esc_url( $this->url( $key ) ),
				$key === $tab ? ' nav-tab-active' : '',
				esc_html( $label )
			);
		}
		echo '</nav>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::ACTION );
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';

		$settings = Settings::get();
		switch ( $tab ) {
			case 'dashboard':
				$this->tab_dashboard( $settings );
				break;
			case 'menu':
				$this->tab_menu( $settings );
				break;
			case 'users':
				$this->tab_users( $settings );
				break;
			default:
				$this->tab_general( $settings );
		}

		echo '<p class="submit">';
		submit_button( __( 'Speichern', 'b83-whitelabel' ), 'primary', 'submit', false );
		if ( in_array( $tab, array( 'menu', 'dashboard' ), true ) ) {
			echo ' ';
			submit_button( __( 'Auf Standard zurücksetzen', 'b83-whitelabel' ), 'secondary', 'b83_wl_reset', false, array( 'onclick' => "return confirm('" . esc_js( __( 'Freigaben auf die b83-Standardwerte zurücksetzen?', 'b83-whitelabel' ) ) . "');" ) );
		}
		echo '</p></form></div>';
	}

	private function messages(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- fester Schlüssel, kein Freitext.
		$key  = isset( $_GET['b83_wl_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['b83_wl_msg'] ) ) : '';
		$list = array(
			'saved'      => array( 'success', __( 'Einstellungen gespeichert.', 'b83-whitelabel' ) ),
			'reset'      => array( 'success', __( 'Auf Standardwerte zurückgesetzt.', 'b83-whitelabel' ) ),
			'self_added' => array( 'warning', __( 'Gespeichert. Du wurdest automatisch als b83-Benutzer eingetragen, damit du diese Seite weiter öffnen kannst.', 'b83-whitelabel' ) ),
		);
		if ( isset( $list[ $key ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $list[ $key ][0] ), esc_html( $list[ $key ][1] ) );
		}
	}

	private function checkbox( string $name, bool $checked, string $label ): void {
		printf(
			'<label><input type="checkbox" name="b83_wl[%1$s]" value="1"%2$s> %3$s</label>',
			esc_attr( $name ),
			checked( $checked, true, false ),
			esc_html( $label )
		);
	}

	/** @param array<string,mixed> $s Einstellungen. */
	private function tab_general( array $s ): void {
		$logo_id  = (int) $s['login_logo_id'];
		$logo_url = $logo_id > 0 ? (string) wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		$preview  = '' !== $logo_url ? $logo_url : B83_WL_URL . 'assets/img/b83-logo.svg';

		echo '<h2>' . esc_html__( 'Fußzeile', 'b83-whitelabel' ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row"><label for="b83-wl-footer">' . esc_html__( 'Text links', 'b83-whitelabel' ) . '</label></th><td>';
		echo '<textarea id="b83-wl-footer" name="b83_wl[footer_text]" rows="2" class="large-text">' . esc_textarea( (string) $s['footer_text'] ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Ersetzt „Danke für dein Vertrauen in WordPress“. Erlaubt: Links, fett, kursiv.', 'b83-whitelabel' ) . '</p></td></tr>';
		echo '<tr><th scope="row"><label for="b83-wl-footer-right">' . esc_html__( 'Text rechts', 'b83-whitelabel' ) . '</label></th><td>';
		echo '<input id="b83-wl-footer-right" type="text" name="b83_wl[footer_right]" class="large-text" value="' . esc_attr( (string) $s['footer_right'] ) . '">';
		echo '<p class="description">' . esc_html__( 'Steht bei Kunden statt der WordPress-Version. Leer lassen für nichts. b83-Benutzer sehen weiterhin die Version.', 'b83-whitelabel' ) . '</p></td></tr>';
		echo '</table>';

		echo '<h2>' . esc_html__( 'Werkzeugleiste', 'b83-whitelabel' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tr><th scope="row">' . esc_html__( 'WordPress-Logo', 'b83-whitelabel' ) . '</th><td>';
		$this->checkbox( 'hide_wp_logo', (bool) $s['hide_wp_logo'], __( 'WordPress-Logo oben links samt Untermenü entfernen (für alle)', 'b83-whitelabel' ) );
		echo '</td></tr></table>';

		echo '<h2>' . esc_html__( 'Login-Seite', 'b83-whitelabel' ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row">' . esc_html__( 'Eigenes Logo', 'b83-whitelabel' ) . '</th><td>';
		$this->checkbox( 'login_logo_enabled', (bool) $s['login_logo_enabled'], __( 'Logo auf der Login-Seite ersetzen (sonst WordPress-Logo)', 'b83-whitelabel' ) );
		echo '</td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Logo', 'b83-whitelabel' ) . '</th><td>';
		echo '<div class="b83-wl-logo"><img id="b83-wl-logo-preview" src="' . esc_url( $preview ) . '" alt=""></div>';
		echo '<input type="hidden" id="b83-wl-logo-id" name="b83_wl[login_logo_id]" value="' . esc_attr( (string) $logo_id ) . '">';
		echo '<button type="button" class="button" id="b83-wl-logo-choose">' . esc_html__( 'Bild wählen', 'b83-whitelabel' ) . '</button> ';
		echo '<button type="button" class="button-link" id="b83-wl-logo-reset">' . esc_html__( 'b83-Logo verwenden', 'b83-whitelabel' ) . '</button>';
		echo '</td></tr>';
		echo '<tr><th scope="row"><label for="b83-wl-logo-width">' . esc_html__( 'Breite', 'b83-whitelabel' ) . '</label></th><td>';
		echo '<input id="b83-wl-logo-width" type="number" min="40" max="320" name="b83_wl[login_logo_width]" value="' . esc_attr( (string) $s['login_logo_width'] ) . '" class="small-text"> px</td></tr>';
		echo '<tr><th scope="row"><label for="b83-wl-login-url">' . esc_html__( 'Link des Logos', 'b83-whitelabel' ) . '</label></th><td>';
		echo '<input id="b83-wl-login-url" type="url" name="b83_wl[login_url]" class="regular-text" value="' . esc_attr( (string) $s['login_url'] ) . '"></td></tr>';
		echo '<tr><th scope="row"><label for="b83-wl-login-title">' . esc_html__( 'Linktext', 'b83-whitelabel' ) . '</label></th><td>';
		echo '<input id="b83-wl-login-title" type="text" name="b83_wl[login_title]" class="regular-text" value="' . esc_attr( (string) $s['login_title'] ) . '">';
		echo '<p class="description">' . esc_html__( 'Für Screenreader, wird nicht angezeigt.', 'b83-whitelabel' ) . '</p></td></tr>';
		echo '</table>';

		echo '<h2>' . esc_html__( 'Sperre und Deinstallation', 'b83-whitelabel' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tr><th scope="row">' . esc_html__( 'Sperre', 'b83-whitelabel' ) . '</th><td>';
		$this->checkbox( 'block_hidden', (bool) $s['block_hidden'], __( 'Ausgeblendete Menüseiten auch beim direkten Aufruf sperren (Umleitung aufs Dashboard)', 'b83-whitelabel' ) );
		echo '</td></tr><tr><th scope="row">' . esc_html__( 'Daten', 'b83-whitelabel' ) . '</th><td>';
		$this->checkbox( 'delete_on_uninstall', (bool) $s['delete_on_uninstall'], __( 'Einstellungen beim Löschen des Plugins entfernen', 'b83-whitelabel' ) );
		echo '</td></tr></table>';
	}

	/** @param array<string,mixed> $s Einstellungen. */
	private function tab_dashboard( array $s ): void {
		echo '<h2>' . esc_html__( 'Eigenes Widget', 'b83-whitelabel' ) . '</h2>';
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row"><label for="b83-wl-notice-title">' . esc_html__( 'Titel', 'b83-whitelabel' ) . '</label></th><td>';
		echo '<input id="b83-wl-notice-title" type="text" name="b83_wl[notice_title]" class="regular-text" value="' . esc_attr( (string) $s['notice_title'] ) . '"></td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Text', 'b83-whitelabel' ) . '</th><td>';
		wp_editor(
			(string) $s['notice_text'],
			'b83_wl_notice_text',
			array(
				'textarea_name' => 'b83_wl[notice_text]',
				'textarea_rows' => 12,
				'media_buttons' => false,
			)
		);
		echo '<p class="description">' . esc_html__( 'Leer lassen, um das Widget auszublenden.', 'b83-whitelabel' ) . '</p></td></tr>';
		echo '</table>';

		echo '<h2>' . esc_html__( 'Weitere Widgets', 'b83-whitelabel' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tr><th scope="row">' . esc_html__( 'Gilt für', 'b83-whitelabel' ) . '</th><td>';
		$this->checkbox( 'dashboard_for_agency', (bool) $s['dashboard_for_agency'], __( 'Dashboard auch für b83-Benutzer aufräumen', 'b83-whitelabel' ) );
		echo '<br>';
		$this->checkbox( 'remove_welcome', (bool) $s['remove_welcome'], __( 'Willkommens-Bereich von WordPress ausblenden', 'b83-whitelabel' ) );
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Freigegeben', 'b83-whitelabel' ) . '</th><td>';
		$known = (array) get_option( Settings::OPTION_KNOWN_WIDGETS, array() );
		if ( empty( $known ) ) {
			echo '<p>' . wp_kses(
				sprintf(
					/* translators: %s: Link zum Dashboard */
					__( 'Noch keine Widgets erfasst. Bitte einmal das <a href="%s">Dashboard</a> öffnen, dann erscheinen sie hier.', 'b83-whitelabel' ),
					esc_url( admin_url( 'index.php' ) )
				),
				array( 'a' => array( 'href' => true ) )
			) . '</p>';
		} else {
			asort( $known );
			echo '<fieldset class="b83-wl-widgets">';
			foreach ( $known as $id => $title ) {
				$id     = (string) $id;
				$always = in_array( $id, Dashboard::always_allowed(), true );
				printf(
					'<label><input type="checkbox" name="b83_wl[allowed_widgets][]" value="%1$s"%2$s%3$s> %4$s <code>%5$s</code></label><br>',
					esc_attr( $id ),
					checked( Dashboard::allowed( $id ), true, false ),
					disabled( $always, true, false ),
					esc_html( '' !== $title ? $title : $id ),
					esc_html( $id )
				);
			}
			echo '</fieldset>';
			echo '<p class="description">' . esc_html__( 'Die Liste stammt vom letzten Aufruf des Dashboards. Neue Widgets anderer Plugins sind ausgeblendet, bis sie hier angehakt werden. Die WordPress-Warnungen zu PHP und Browser bleiben immer sichtbar.', 'b83-whitelabel' ) . '</p>';
		}
		echo '</td></tr></table>';
	}

	/** @param array<string,mixed> $s Einstellungen. */
	private function tab_menu( array $s ): void {
		$tree = Menu::known();
		$seen = (array) $s['menu_seen'];

		echo '<p>' . esc_html__( 'Angehakte Menüpunkte sehen alle Benutzer, die keine b83-Benutzer sind. Neue Menüpunkte (z. B. von später installierten Plugins) bleiben ausgeblendet, bis sie hier angehakt werden.', 'b83-whitelabel' ) . '</p>';
		if ( ! Menu::configured() ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Derzeit gelten die b83-Standardwerte. Nach dem ersten Speichern gilt genau diese Auswahl.', 'b83-whitelabel' ) . '</p></div>';
		}

		echo '<ul class="b83-wl-menu">';
		foreach ( $tree as $top ) {
			$slug   = (string) $top['slug'];
			$always = in_array( $slug, Menu::always_allowed(), true );
			$new    = Menu::configured() && ! in_array( $slug, $seen, true );

			echo '<li class="b83-wl-menu__top">';
			printf(
				'<label><input type="checkbox" class="b83-wl-top" name="b83_wl[allowed_menu][]" value="%1$s"%2$s%3$s> <strong>%4$s</strong></label> <code>%5$s</code>%6$s',
				esc_attr( $slug ),
				checked( Menu::top_allowed( $slug ), true, false ),
				disabled( $always, true, false ),
				esc_html( (string) $top['title'] ),
				esc_html( $slug ),
				$new ? ' <span class="b83-wl-new">' . esc_html__( 'neu', 'b83-whitelabel' ) . '</span>' : ''
			);
			if ( $always ) {
				// Deaktivierte Felder werden nicht gesendet: Wert trotzdem mitschicken.
				echo '<input type="hidden" name="b83_wl[allowed_menu][]" value="' . esc_attr( $slug ) . '">';
			}

			if ( ! empty( $top['children'] ) ) {
				echo '<ul class="b83-wl-menu__children">';
				foreach ( (array) $top['children'] as $child => $title ) {
					$child = (string) $child;
					$key   = $slug . Settings::SEP . $child;
					$first = $child === $slug;
					$cnew  = Menu::configured() && ! in_array( $key, $seen, true );
					printf(
						'<li><label><input type="checkbox" class="b83-wl-child" name="b83_wl[allowed_menu][]" value="%1$s"%2$s%3$s> %4$s</label> <code>%5$s</code>%6$s</li>',
						esc_attr( $key ),
						checked( $first || Menu::child_allowed( $slug, $child ), true, false ),
						disabled( $first, true, false ),
						esc_html( (string) $title ),
						esc_html( $child ),
						$cnew ? ' <span class="b83-wl-new">' . esc_html__( 'neu', 'b83-whitelabel' ) . '</span>' : ''
					);
				}
				echo '</ul>';
			}
			echo '</li>';
		}
		echo '</ul>';
		echo '<p class="description">' . esc_html__( 'Die Liste zeigt das Menü, wie du es gerade siehst. Der erste Unterpunkt gehört zum Hauptpunkt und ist mit ihm sichtbar.', 'b83-whitelabel' ) . '</p>';
	}

	/** @param array<string,mixed> $s Einstellungen. */
	private function tab_users( array $s ): void {
		$admins   = get_users( array( 'capability' => 'manage_options', 'orderby' => 'display_name' ) );
		$selected = array_map( 'intval', (array) $s['agency_users'] );

		echo '<p>' . esc_html__( 'b83-Benutzer sehen das volle Menü, die WordPress-Version und diese Einstellungsseite. Alle anderen sehen nur die freigegebenen Menüpunkte.', 'b83-whitelabel' ) . '</p>';
		if ( empty( $selected ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Noch niemand ausgewählt: Derzeit gelten alle Administratoren als b83-Benutzer, das Menü ist für sie nicht reduziert.', 'b83-whitelabel' ) . '</p></div>';
		}

		echo '<fieldset class="b83-wl-users">';
		foreach ( $admins as $user ) {
			printf(
				'<label><input type="checkbox" name="b83_wl[agency_users][]" value="%1$d"%2$s> %3$s <span class="description">(%4$s)</span></label><br>',
				(int) $user->ID,
				checked( in_array( (int) $user->ID, $selected, true ), true, false ),
				esc_html( (string) $user->display_name ),
				esc_html( (string) $user->user_email )
			);
		}
		echo '</fieldset>';
		echo '<p class="description">' . esc_html__( 'Notfall: define( \'B83_WL_DISABLE\', true ); in der wp-config.php schaltet alle Anpassungen ab.', 'b83-whitelabel' ) . '</p>';
	}
}
