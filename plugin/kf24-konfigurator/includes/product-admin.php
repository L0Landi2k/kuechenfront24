<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_product_data_tabs', function ( $tabs ) {
	$tabs['kf24'] = [ 'label' => 'Konfigurator', 'target' => 'kf24_product_data', 'class' => [ 'show_if_simple' ], 'priority' => 15 ];
	return $tabs;
} );

add_action( 'woocommerce_product_data_panels', function () {
	global $post;
	echo '<div id="kf24_product_data" class="panel woocommerce_options_panel"><p class="form-field" style="background:#f0f6fc;padding:10px 12px!important;margin:0">Einfacher geht es unter <a href="' . esc_url( admin_url( 'admin.php?page=kf24-front&id=' . (int) $post->ID ) ) . '"><b>Mein Shop → Fronten</b></a>: dort wählen Sie Materialien per Klick und ziehen Bilder einfach hinein.</p><div class="options_group">';
	woocommerce_wp_checkbox( [ 'id' => '_kf24_enabled', 'label' => 'Konfigurator aktiv', 'description' => 'Front wird nach m² berechnet und im Konfigurator bestellt.' ] );
	woocommerce_wp_text_input( [ 'id' => '_kf24_m2', 'label' => 'Grundpreis je m² (€, brutto)', 'data_type' => 'price' ] );
	woocommerce_wp_select( [ 'id' => '_kf24_group', 'label' => 'Gruppe', 'options' => [ 'Dekor' => 'Dekor', 'Furnier' => 'Echtholz furniert', 'Massivholz' => 'Massivholz', 'Lack' => 'Lack', 'Fenix' => 'Fenix NTM' ] ] );
	woocommerce_wp_text_input( [ 'id' => '_kf24_field', 'label' => 'Name des Auswahlfelds', 'placeholder' => 'z. B. Holzart, Dekor, Lackfarbe' ] );
	woocommerce_wp_textarea_input( [ 'id' => '_kf24_options', 'label' => 'Auswahl (eine Option je Zeile)', 'style' => 'height:160px' ] );
	woocommerce_wp_text_input( [ 'id' => '_kf24_lz', 'label' => 'Lieferzeit', 'placeholder' => 'ca. 6 Wochen' ] );
	woocommerce_wp_text_input( [ 'id' => '_kf24_thickness', 'label' => 'Frontstärke (mm)', 'type' => 'number' ] );
	woocommerce_wp_text_input( [ 'id' => '_kf24_kgm2', 'label' => 'Gewicht je m² (kg)', 'data_type' => 'decimal' ] );
	woocommerce_wp_checkbox( [ 'id' => '_kf24_milling', 'label' => 'Grifffräsung möglich' ] );
	woocommerce_wp_text_input( [ 'id' => '_kf24_sample', 'label' => 'Muster-Produkt (Slug)' ] );
	echo '</div></div>';
} );

// WooCommerce verifies its own nonce (woocommerce_meta_nonce) before this hook runs.
// phpcs:disable WordPress.Security.NonceVerification.Missing
add_action( 'woocommerce_process_product_meta', function ( $id ) {
	update_post_meta( $id, '_kf24_enabled', isset( $_POST['_kf24_enabled'] ) ? 'yes' : 'no' );
	update_post_meta( $id, '_kf24_milling', isset( $_POST['_kf24_milling'] ) ? 'yes' : 'no' );
	foreach ( [ '_kf24_m2', '_kf24_group', '_kf24_field', '_kf24_lz', '_kf24_thickness', '_kf24_kgm2', '_kf24_sample' ] as $k ) {
		if ( isset( $_POST[ $k ] ) ) {
			update_post_meta( $id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) );
		}
	}
	if ( isset( $_POST['_kf24_options'] ) ) {
		update_post_meta( $id, '_kf24_options', sanitize_textarea_field( wp_unslash( $_POST['_kf24_options'] ) ) );
		kf24_ensure_materials( array_filter( array_map( 'trim', explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['_kf24_options'] ) ) ) ) ) );
	}
	// keep the visible WooCommerce price in sync with the m² price ("ab … je m²")
	if ( isset( $_POST['_kf24_enabled'] ) && ! empty( $_POST['_kf24_m2'] ) ) {
		$product = wc_get_product( $id );
		if ( $product ) {
			$product->set_regular_price( wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_kf24_m2'] ) ) ) );
			$product->save();
		}
	}
}, 50 );
// phpcs:enable WordPress.Security.NonceVerification.Missing
