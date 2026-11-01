<?php
/**
 * Einstellungen: Standardwerte, Lesen, Speichern und Prüfen.
 *
 * Alle Eingaben laufen durch sanitize(); gespeichert wird nur, was hier
 * ausdrücklich vorgesehen ist (Whitelist).
 *
 * @package B83\Whitelabel
 */

declare(strict_types=1);

namespace B83\Whitelabel;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION               = 'b83_whitelabel_settings';
	public const OPTION_KNOWN_MENU    = 'b83_whitelabel_known_menu';
	public const OPTION_KNOWN_WIDGETS = 'b83_whitelabel_known_widgets';

	/** Trenner zwischen Haupt- und Unterpunkt im Menü-Schlüssel. */
	public const SEP = '::';

	/** ID des eigenen Dashboard-Widgets. */
	public const NOTICE_WIDGET = 'b83_wl_notice';

	/** @var array<string,mixed>|null */
	private static ?array $cache = null;

	/** @return array<string,mixed> */
	public static function defaults(): array {
		return array(
			'footer_text'          => 'Website betreut von <a href="https://b83.at" target="_blank" rel="noopener">b83 – Strategisches Online Marketing</a> · <a href="mailto:office@b83.at">office@b83.at</a>',
			'footer_right'         => '',
			'hide_wp_logo'         => true,
			'login_logo_id'        => 0,
			'login_logo_width'     => 120,
			'login_url'            => 'https://b83.at',
			'login_title'          => 'b83 – Strategisches Online Marketing',
			'notice_title'         => 'Kundeninformation',
			'notice_text'          => '<p>Diese Website verwendet Software mit lizenzpflichtigen Komponenten. Die Lizenzen hat <strong>b83 – Strategisches Online Marketing</strong> für Websites erworben, die im Rahmen unserer Agentur erstellt und betreut werden. Sie dürfen diese Lizenzen auf Ihrer Website nutzen und erhalten darüber laufend Updates.</p><p>Dazu gehören auch die von b83 selbst entwickelten Plugins, die wir offiziell zum Verkauf anbieten: Sie sind auf Ihrer Website als <strong>Pro-Version</strong> lizenziert.</p><p>Die Lizenz- und Updatekosten unserer Standardausstattung, also unserer eigenen Plugins, von Elementor und der weiteren von uns eingesetzten Komponenten, sind mit der vereinbarten Wartungs- und Updatepauschale abgegolten. Für Sie fallen dafür keine weiteren Kosten an. Ausnahmen, etwa wenn Sie eine Lizenz selbst erwerben und bezahlen, regeln wir individuell mit Ihnen. Die Lizenzen gelten für die Dauer der Wartungsvereinbarung.</p><p>Für Fragen stehen wir Ihnen gerne zur Verfügung.</p><p>b83 :: Strategisches Online Marketing<br>Tel +43-6135-21919<br>Fax +43-6135-21919 19<br><a href="mailto:office@b83.at">office@b83.at</a><br><a href="https://b83.at" target="_blank" rel="noopener">b83.at</a></p>',
			'dashboard_for_agency' => true,
			'remove_welcome'       => true,
			'allowed_widgets'      => array(),
			'widgets_configured'   => false,
			'allowed_menu'         => array(),
			'menu_seen'            => array(),
			'menu_configured'      => false,
			'block_hidden'         => true,
			'agency_users'         => array(),
			'delete_on_uninstall'  => false,
		);
	}

	/** @return array<string,mixed> */
	public static function get(): array {
		if ( null === self::$cache ) {
			$stored      = get_option( self::OPTION, array() );
			self::$cache = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		}
		return self::$cache;
	}

	/** @return mixed */
	public static function value( string $key ) {
		return self::get()[ $key ] ?? null;
	}

	/**
	 * Teile der Einstellungen ändern (bereits geprüfte Werte).
	 *
	 * @param array<string,mixed> $changes Neue Werte.
	 */
	public static function update( array $changes ): void {
		$merged = array_merge( self::get(), $changes );
		update_option( self::OPTION, $merged, true );
		self::$cache = $merged;
	}

	/* ---------------------------------------------------------------------
	 * Prüfung der Formulareingaben
	 * ------------------------------------------------------------------- */

	/**
	 * Erlaubtes HTML für Fußzeile.
	 *
	 * @return array<string,array<string,bool>>
	 */
	public static function inline_html(): array {
		return array(
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
			'strong' => array(),
			'em'     => array(),
			'br'     => array(),
		);
	}

	/**
	 * Formular eines Reiters prüfen. Nur die Felder dieses Reiters werden
	 * zurückgegeben, alles andere bleibt unverändert.
	 *
	 * @param string              $tab   Reiter.
	 * @param array<string,mixed> $input Rohdaten (bereits wp_unslash).
	 * @return array<string,mixed>
	 */
	public static function sanitize( string $tab, array $input ): array {
		switch ( $tab ) {
			case 'general':
				return array(
					'footer_text'         => self::limit( wp_kses( (string) ( $input['footer_text'] ?? '' ), self::inline_html() ), 1000 ),
					'footer_right'        => self::limit( wp_kses( (string) ( $input['footer_right'] ?? '' ), self::inline_html() ), 500 ),
					'hide_wp_logo'        => ! empty( $input['hide_wp_logo'] ),
					'login_logo_id'       => absint( $input['login_logo_id'] ?? 0 ),
					'login_logo_width'    => max( 40, min( 320, absint( $input['login_logo_width'] ?? 120 ) ) ),
					'login_url'           => esc_url_raw( self::limit( (string) ( $input['login_url'] ?? '' ), 500 ) ),
					'login_title'         => self::limit( sanitize_text_field( (string) ( $input['login_title'] ?? '' ) ), 200 ),
					'block_hidden'        => ! empty( $input['block_hidden'] ),
					'delete_on_uninstall' => ! empty( $input['delete_on_uninstall'] ),
				);

			case 'dashboard':
				return array(
					'notice_title'         => self::limit( sanitize_text_field( (string) ( $input['notice_title'] ?? '' ) ), 200 ),
					'notice_text'          => self::limit( wp_kses_post( (string) ( $input['notice_text'] ?? '' ) ), 20000 ),
					'dashboard_for_agency' => ! empty( $input['dashboard_for_agency'] ),
					'remove_welcome'       => ! empty( $input['remove_welcome'] ),
					'allowed_widgets'      => self::keys( $input['allowed_widgets'] ?? array(), array_keys( (array) get_option( self::OPTION_KNOWN_WIDGETS, array() ) ) ),
					'widgets_configured'   => true,
				);

			case 'menu':
				$known = Menu::known_keys();
				return array(
					'allowed_menu'    => self::keys( $input['allowed_menu'] ?? array(), $known ),
					'menu_seen'       => $known,
					'menu_configured' => true,
				);

			case 'users':
				$admins = array_map( 'intval', get_users(
					array(
						'capability' => 'manage_options',
						'fields'     => 'ID',
					)
				) );
				$ids    = array_values( array_intersect( array_map( 'absint', (array) ( $input['agency_users'] ?? array() ) ), $admins ) );
				return array( 'agency_users' => $ids );
		}
		return array();
	}

	/**
	 * Nur Schlüssel übernehmen, die es tatsächlich gibt.
	 *
	 * @param mixed    $raw     Eingabe.
	 * @param string[] $allowed Bekannte Schlüssel.
	 * @return string[]
	 */
	private static function keys( $raw, array $allowed ): array {
		$raw = array_map( 'strval', (array) $raw );
		return array_values( array_intersect( $allowed, $raw ) );
	}

	private static function limit( string $value, int $max ): string {
		return mb_substr( trim( $value ), 0, $max );
	}
}
