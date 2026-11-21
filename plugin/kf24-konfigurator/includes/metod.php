<?php
defined( 'ABSPATH' ) || exit;

/** Live drawing for Metod variable products (Breite/Höhe/Anschlag/Grifffräsung attributes). */
add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	if ( ! $product || ! $product->is_type( 'variable' ) || ! kf24_is_front_product( $product ) ) {
		return;
	}
	wp_enqueue_style( 'kf24' );
	wp_enqueue_script( 'kf24-metod' );
	$sw = get_post_meta( $product->get_id(), '_kf24_swatch', true );
	if ( ! $sw ) {
		$slug = $product->get_slug();
		$map  = [ 'pg1' => 'kernbuche', 'pg2' => 'eiche', 'pg3' => 'kirsche', 'pg4' => 'nussbaum' ];
		$sw   = str_contains( $slug, 'massivholz' ) ? ( $map[ preg_replace( '/.*(pg\d).*/', '$1', $slug ) ] ?? 'eiche' ) : ( str_contains( $slug, 'furniert' ) ? 'furnier-eiche' : ( str_contains( $slug, 'fenix' ) ? 'fenix' : ( str_contains( $slug, 'bunt' ) ? 'salbei' : 'weiss' ) ) );
	}
	wp_localize_script( 'kf24-metod', 'KF24MET', [ 'tex' => kf24_swatch_url( $sw ), 'color' => kf24_swatch_color( $sw ) ] );
	echo '<div class="kf24-metod" aria-live="polite"><div class="kf24-metod-draw" id="kf24-metod-draw"></div><p class="kf24-hint">Rastermaß nach IKEA-Küchenplan. Zeichnung zur Orientierung.</p></div>';
} );
