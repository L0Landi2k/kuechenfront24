<?php
defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	$front_total = 0;
	$long        = false;
	$weight      = 0;
	foreach ( $cart->get_cart() as $item ) {
		$product = $item['data'];
		$weight += kf24_item_weight( $item ) * $item['quantity'];
		if ( ! kf24_is_front_product( $product ) ) {
			continue;
		}
		$front_total += (float) $product->get_price() * $item['quantity'];
		[ $w, $h ] = kf24_item_dims( $item );
		if ( max( $w, $h ) > (float) kf24_opt( 'length_limit' ) ) {
			$long = true;
		}
	}
	if ( $front_total > 0 && $front_total < (float) kf24_opt( 'min_order_threshold' ) ) {
		$cart->add_fee( sprintf( 'Mindermengenzuschlag (Fronten unter %s)', wp_strip_all_tags( wc_price( kf24_opt( 'min_order_threshold' ) ) ) ), kf24_net( (float) kf24_opt( 'min_order_fee' ) ), true );
	}
	if ( $long && $weight < (float) kf24_opt( 'length_weight_limit' ) ) {
		$cart->add_fee( sprintf( 'Längenzuschlag (Teil über %s mm)', kf24_opt( 'length_limit' ) ), kf24_net( (float) kf24_opt( 'length_fee' ) ), true );
	}
} );

/** Hint in cart when the Mindermengenzuschlag applies. */
add_action( 'woocommerce_before_cart_totals', function () {
	$t = 0;
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( kf24_is_front_product( $item['data'] ) ) {
			$t += (float) $item['data']->get_price() * $item['quantity'];
		}
	}
	$lim = (float) kf24_opt( 'min_order_threshold' );
	if ( $t > 0 && $t < $lim ) {
		printf( '<p class="kf24-note">Noch %s bis %s Frontenwert, dann entfällt der Mindermengenzuschlag.</p>', wp_kses_post( wc_price( $lim - $t ) ), wp_kses_post( wc_price( $lim ) ) );
	}
} );
