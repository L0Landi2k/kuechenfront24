<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'Küchenfront24', 'Küchenfront24', 'manage_woocommerce', 'kf24-settings', 'kf24_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'kf24_settings', 'kf24_settings', [
		'sanitize_callback' => function ( $in ) {
			$out = [];
			foreach ( kf24_defaults() as $k => $d ) {
				if ( ! isset( $in[ $k ] ) ) {
					continue;
				}
				$out[ $k ] = is_numeric( $d ) ? kf24_num( $in[ $k ] ) : sanitize_text_field( $in[ $k ] );
			}
			return $out;
		},
	] );
} );

function kf24_settings_fields() {
	return [
		'Aufpreise'          => [
			'milling_price'  => 'Grifffräsung je Front (Massivholz), €',
			'hinge_price'    => 'Topfband mitliefern, € je Stück',
			'seal_price_lfm' => 'Aufsteckdichtung, € je laufendem Meter',
			'seal_note'      => 'Zusatz hinter dem Dichtungspreis, z. B. „fiktiv“ (leer = keiner)',
			'min_area'       => 'Abgerechnete Mindestfläche je Front, m² (0 = keine)',
		],
		'Oberfläche Holzfronten (Massivholz, Furnier)' => [
			'finish_lack_label' => 'Name Standard-Oberfläche',
			'finish_lack_m2'    => 'Aufpreis Standard je m², € (0 = im m²-Preis der Front enthalten)',
			'finish_oil_label'  => 'Name zweite Oberfläche',
			'finish_oil_m2'     => 'Aufpreis geölt je m², €',
			'finish_oil_min'    => 'Aufpreis geölt mindestens je Front, €',
		],
		'Maße'               => [
			'min_dim'      => 'Mindestmaß Breite/Höhe, mm',
			'max_long'     => 'Höchstmaß lange Seite, mm',
			'max_short'    => 'Höchstmaß kurze Seite, mm',
			'kgm2_default' => 'Gewicht je m² (Standard), kg',
		],
		'Zuschläge'          => [
			'min_order_threshold' => 'Mindermengenzuschlag unter Warenwert (Fronten), €',
			'min_order_fee'       => 'Mindermengenzuschlag, €',
			'length_limit'        => 'Längenzuschlag ab Teillänge über, mm',
			'length_weight_limit' => 'Längenzuschlag nur unter Gesamtgewicht, kg',
			'length_fee'          => 'Längenzuschlag, €',
		],
		'Versand Deutschland' => [
			'ship_t1'        => 'bis 9,9 kg (Paket), €',
			'ship_t2'        => '10 bis 19,9 kg (Paket), €',
			'ship_t3'        => '20 bis 49,9 kg (Spedition), €',
			'ship_t4'        => 'ab 50 kg (Spedition), €',
			'ship_sample_de' => 'Nur Muster, Deutschland, €',
			'ship_sample_ch' => 'Nur Muster, Schweiz, €',
		],
		'Allgemein'          => [
			'front_cat'    => 'Kategorie-Slug der Fronten (inkl. Unterkategorien)',
			'sample_cat'   => 'Kategorie-Slug der Muster',
			'notify_email' => 'E-Mail für Widerruf und Kontakt (leer = Admin-E-Mail)',
			'phone'        => 'Telefon für Hinweise',
		],
	];
}

function kf24_settings_page() {
	$o = get_option( 'kf24_settings', [] );
	echo '<div class="wrap"><h1>Küchenfront24 – Einstellungen</h1>';
	echo '<p>Alle Beträge sind Bruttobeträge inkl. MwSt. Preise je m² stellen Sie direkt am Produkt im Reiter „Konfigurator“ ein.</p>';
	if ( (float) kf24_opt( 'hinge_price' ) <= 0 || (float) kf24_opt( 'seal_price_lfm' ) <= 0 ) {
		echo '<div class="notice notice-warning inline"><p><b>Bitte prüfen:</b> Für „Topfband mitliefern“ und/oder „Aufsteckdichtung“ ist noch kein Preis hinterlegt. Diese Optionen werden sonst kostenlos berechnet.</p></div>';
	}
	echo '<form method="post" action="options.php">';
	settings_fields( 'kf24_settings' );
	foreach ( kf24_settings_fields() as $section => $fields ) {
		echo '<h2>' . esc_html( $section ) . '</h2><table class="form-table" role="presentation">';
		foreach ( $fields as $k => $label ) {
			$v = $o[ $k ] ?? kf24_defaults()[ $k ];
			printf( '<tr><th scope="row"><label for="kf24_%1$s">%2$s</label></th><td><input class="regular-text" id="kf24_%1$s" name="kf24_settings[%1$s]" value="%3$s"></td></tr>', esc_attr( $k ), esc_html( $label ), esc_attr( $v ) );
		}
		echo '</table>';
	}
	submit_button();
	echo '</form>';
	echo '<hr><h2>Einrichtung</h2><p>Legt die 21 Konfigurator-Produkte und die Muster an bzw. aktualisiert vorhandene Produkte mit gleichem Titelform-Slug. Vorhandene Metod-Produkte bleiben unverändert.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'kf24_setup' );
	echo '<input type="hidden" name="action" value="kf24_setup"><label><input type="checkbox" name="overwrite_prices" value="1"> Auch Preise und Optionslisten vorhandener Produkte überschreiben</label><br><br>';
	submit_button( 'Konfigurator-Produkte einrichten', 'secondary', 'submit', false );
	echo '</form></div>';
}
