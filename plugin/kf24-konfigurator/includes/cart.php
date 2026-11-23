<?php
defined( 'ABSPATH' ) || exit;

/** Server-side validation – the browser is never trusted. */
// Add-to-cart forms in WooCommerce carry no nonce by design; every value is sanitized and re-validated server-side.
// phpcs:disable WordPress.Security.NonceVerification.Missing
add_filter( 'woocommerce_add_to_cart_validation', function ( $ok, $product_id ) {
	if ( ! kf24_is_cfg_product( $product_id ) ) {
		return $ok;
	}
	if ( empty( $_POST['kf24'] ) || ! is_array( $_POST['kf24'] ) ) {
		wc_add_notice( 'Bitte konfigurieren Sie die Front zuerst.', 'error' );
		return false;
	}
	$pcfg = kf24_product_cfg( $product_id );
	$cfg  = kf24_sanitize_config( $_POST['kf24'], $pcfg ); // phpcs:ignore
	foreach ( kf24_validate_config( $cfg, $pcfg ) as $e ) {
		wc_add_notice( $e, 'error' );
		$ok = false;
	}
	return $ok;
}, 10, 2 );

add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	if ( kf24_is_cfg_product( $product_id ) && ! empty( $_POST['kf24'] ) ) {
		$data['kf24']     = kf24_sanitize_config( $_POST['kf24'], kf24_product_cfg( $product_id ) ); // phpcs:ignore
		$data['kf24_key'] = md5( wp_json_encode( $data['kf24'] ) );
	}
	return $data;
}, 10, 2 );
// phpcs:enable WordPress.Security.NonceVerification.Missing

/** Recalculate price and weight from the stored configuration. */
add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['kf24'] ) ) {
			// Metod fronts without stored weight: estimate from Breite × Höhe
			if ( ! (float) $item['data']->get_weight() && kf24_is_front_product( $item['data'] ) ) {
				[ $w, $h ] = kf24_item_dims( $item );
				if ( $w && $h ) {
					$item['data']->set_weight( round( $w * $h / 1e6 * (float) kf24_opt( 'kgm2_default' ), 2 ) );
				}
			}
			continue;
		}
		$pcfg = kf24_product_cfg( $item['product_id'] );
		$p    = kf24_price( $item['kf24'], $pcfg );
		$item['data']->set_price( $p['unit'] );
		$item['data']->set_weight( $p['weight'] );
	}
}, 20 );

add_filter( 'woocommerce_cart_item_name', function ( $name, $item ) {
	if ( ! empty( $item['kf24']['label'] ) ) {
		$name .= ' <span class="kf24-label">„' . esc_html( $item['kf24']['label'] ) . '“</span>';
	}
	return $name;
}, 10, 2 );

add_filter( 'woocommerce_get_item_data', function ( $data, $item ) {
	if ( empty( $item['kf24'] ) ) {
		return $data;
	}
	foreach ( kf24_spec_lines( $item['kf24'] ) as $k => $v ) {
		if ( $k === 'Bezeichnung' ) {
			continue;
		}
		$data[] = [ 'key' => $k, 'value' => $v ];
	}
	return $data;
}, 10, 2 );

/** Show the drawing as cart thumbnail. */
add_filter( 'woocommerce_cart_item_thumbnail', function ( $thumb, $item ) {
	if ( empty( $item['kf24'] ) ) {
		return $thumb;
	}
	return '<span class="kf24-thumb">' . kf24_svg( $item['kf24'], kf24_cfg_color( $item['kf24'], $item['product_id'] ), 120, 140 ) . '</span>';
}, 10, 2 );

/** Persist to the order: readable lines + full config for production. */
add_action( 'woocommerce_checkout_create_order_line_item', function ( $line, $key, $values ) {
	if ( empty( $values['kf24'] ) ) {
		return;
	}
	foreach ( kf24_spec_lines( $values['kf24'] ) as $k => $v ) {
		$line->add_meta_data( $k, $v, true );
	}
	$line->add_meta_data( '_kf24_cfg', $values['kf24'], true );
	$pcfg = kf24_product_cfg( $values['product_id'] );
	$line->add_meta_data( '_kf24_m2', $pcfg['m2'], true );
}, 10, 3 );

/** Edit link from the cart back to the configurator is not possible with a hash, so offer "duplicate & change" via notice. */
add_filter( 'woocommerce_cart_item_permalink', function ( $link, $item ) {
	return ! empty( $item['kf24'] ) ? $link . '#kf24-konfigurator' : $link;
}, 10, 2 );

/* Checkout wording required in Germany (Button-Lösung). */
add_filter( 'woocommerce_order_button_text', fn() => 'Zahlungspflichtig bestellen' );

/* Note under prices if no legal plugin (Germanized/German Market) handles it. */
add_filter( 'woocommerce_get_price_suffix', function ( $suffix, $product ) {
	if ( class_exists( 'WooCommerce_Germanized' ) || class_exists( 'Woocommerce_German_Market' ) || is_admin() ) {
		return $suffix;
	}
	if ( is_product() || is_shop() || is_product_taxonomy() ) {
		return $suffix . ' <small class="kf24-tax">inkl. MwSt., zzgl. <a href="' . esc_url( home_url( '/versand/' ) ) . '">Versand</a></small>';
	}
	return $suffix;
}, 10, 2 );

/** Delivery time on product pages for all products with the meta or Metod products. */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( ! $product || kf24_is_cfg_product( $product ) ) {
		return;
	}
	$lz = get_post_meta( $product->get_id(), '_kf24_lz', true );
	if ( $lz ) {
		echo '<p class="kf24-lz">Lieferzeit: ' . esc_html( $lz ) . ' ab Zahlungseingang</p>';
	}
}, 25 );

/**
 * Bulk add from the front list: ?wc-ajax=kf24_bulk_add (product_id, items = JSON list of configurations).
 * Every position is sanitized and validated like a single add; nothing is added unless all positions are valid.
 */
function kf24_ajax_bulk_add() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- same as WooCommerce add-to-cart; values are sanitized and validated
	$pid   = absint( $_POST['product_id'] ?? 0 );
	$items = json_decode( wp_unslash( (string) ( $_POST['items'] ?? '' ) ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded, then sanitized per field
	// phpcs:enable
	if ( ! $pid || ! kf24_is_cfg_product( $pid ) || ! is_array( $items ) || ! $items || count( $items ) > 60 ) {
		wp_send_json_error( [ 'errors' => [] ] );
	}
	$pcfg   = kf24_product_cfg( $pid );
	$clean  = [];
	$errors = [];
	foreach ( array_values( $items ) as $i => $raw ) {
		if ( ! is_array( $raw ) ) {
			$errors[ $i ] = [ 'Ungültige Position.' ];
			continue;
		}
		$raw['hpos'] = isset( $raw['hraw'] ) && is_array( $raw['hraw'] ) ? $raw['hraw'] : ( $raw['hpos'] ?? [] );
		$cfg         = kf24_sanitize_config( $raw, $pcfg );
		$e           = kf24_validate_config( $cfg, $pcfg );
		if ( $e ) {
			$errors[ $i ] = $e;
		}
		$clean[] = [ $cfg, max( 1, min( 99, (int) ( $raw['qty'] ?? 1 ) ) ) ];
	}
	if ( $errors ) {
		wp_send_json_error( [ 'errors' => $errors ] );
	}
	foreach ( $clean as [ $cfg, $qty ] ) {
		WC()->cart->add_to_cart( $pid, $qty, 0, [], [ 'kf24' => $cfg, 'kf24_key' => md5( wp_json_encode( $cfg ) . wp_rand() ) ] );
	}
	wc_add_notice( count( $clean ) . ' Positionen liegen im Warenkorb.', 'success' );
	wp_send_json_success( [ 'count' => count( $clean ) ] );
}
add_action( 'wc_ajax_kf24_bulk_add', 'kf24_ajax_bulk_add' );
