/* b83 White Label – Einstellungsseite: Logo-Auswahl und Menü-Baum */
( function ( $ ) {
	'use strict';

	var cfg = window.b83WlAdmin || {};
	var frame = null;

	$( '#b83-wl-logo-choose' ).on( 'click', function ( e ) {
		e.preventDefault();
		if ( ! frame ) {
			frame = wp.media( {
				title: cfg.chooseLogo,
				button: { text: cfg.useLogo },
				library: { type: 'image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var img = frame.state().get( 'selection' ).first().toJSON();
				var url = ( img.sizes && img.sizes.medium ) ? img.sizes.medium.url : img.url;
				$( '#b83-wl-logo-id' ).val( img.id );
				$( '#b83-wl-logo-preview' ).attr( 'src', url );
			} );
		}
		frame.open();
	} );

	$( '#b83-wl-logo-reset' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#b83-wl-logo-id' ).val( '0' );
		$( '#b83-wl-logo-preview' ).attr( 'src', cfg.defaultUrl );
	} );

	// Hauptpunkt aus: Unterpunkte blass darstellen. Neu angehakt: alle Unterpunkte mit an.
	function sync( $top, fresh ) {
		var on = $top.is( ':checked' );
		var $li = $top.closest( '.b83-wl-menu__top' );
		$li.toggleClass( 'is-off', ! on );
		if ( on && fresh ) {
			$li.find( '.b83-wl-child' ).not( ':disabled' ).prop( 'checked', true );
		}
	}
	$( '.b83-wl-top' ).each( function () { sync( $( this ), false ); } )
		.on( 'change', function () { sync( $( this ), true ); } );
} )( jQuery );
