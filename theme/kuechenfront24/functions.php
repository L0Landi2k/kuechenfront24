<?php
defined( 'ABSPATH' ) || exit;

define( 'KF24T_VER', '1.2.0' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'woocommerce', [ 'thumbnail_image_width' => 600, 'single_image_width' => 1000 ] );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	register_nav_menus( [ 'primary' => 'Hauptnavigation', 'footer_shop' => 'Footer: Fronten', 'footer_help' => 'Footer: Hilfe', 'footer_legal' => 'Footer: Rechtliches' ] );
	add_image_size( 'kf24-card', 900, 560, true );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'kf24-theme', get_stylesheet_uri(), [], KF24T_VER );
	wp_enqueue_script( 'kf24-site', get_template_directory_uri() . '/assets/js/site.js', [], KF24T_VER, true );
	wp_localize_script( 'kf24-site', 'KF24SITE', [
		'img'     => get_template_directory_uri() . '/assets/img/',
		'ga'      => get_theme_mod( 'kf24_ga4', '' ),
		'swatches'=> kf24t_hero_swatches(),
	] );
} );

/** Preload the two main fonts. */
add_action( 'wp_head', function () {
	$u = get_template_directory_uri() . '/assets/fonts/';
	echo '<link rel="preload" href="' . esc_url( $u . 'archivo-latin-wdth-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( $u . 'source-sans-3-latin-400-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}, 1 );

/* ---------- Customizer: contact data, top bar, analytics ---------- */
add_action( 'customize_register', function ( $c ) {
	$c->add_section( 'kf24', [ 'title' => 'Küchenfront24', 'priority' => 30 ] );
	$fields = [
		'kf24_phone'    => [ 'Telefon', '089 462 05 236' ],
		'kf24_fax'      => [ 'Fax', '089 462 05 112' ],
		'kf24_email'    => [ 'E-Mail', 'info@kuechenfront24.de' ],
		'kf24_showroom' => [ 'Werksausstellung (Adresse)', 'Schreinerei Michael Senft · Gronsdorfer Straße 3–5 · 85540 Haar' ],
		'kf24_topbar'   => [ 'Top-Leiste (mit | trennen)', 'Gefertigt in Süddeutschland|Maßgenau bis auf den Millimeter|Seit 2008 online' ],
		'kf24_ga4'      => [ 'Google Analytics 4 Mess-ID (nur nach Einwilligung geladen)', '' ],
	];
	foreach ( $fields as $id => [ $label, $def ] ) {
		$c->add_setting( $id, [ 'default' => $def, 'sanitize_callback' => 'sanitize_text_field' ] );
		$c->add_control( $id, [ 'label' => $label, 'section' => 'kf24', 'type' => 'text' ] );
	}
} );

function kf24t( $key ) {
	$d = [ 'kf24_phone' => '089 462 05 236', 'kf24_fax' => '089 462 05 112', 'kf24_email' => 'info@kuechenfront24.de', 'kf24_showroom' => 'Schreinerei Michael Senft · Gronsdorfer Straße 3–5 · 85540 Haar', 'kf24_topbar' => 'Gefertigt in Süddeutschland|Maßgenau bis auf den Millimeter|Seit 2008 online' ];
	return get_theme_mod( $key, $d[ $key ] ?? '' );
}
function kf24t_tel() {
	return 'tel:+49' . ltrim( preg_replace( '/\D/', '', kf24t( 'kf24_phone' ) ), '0' );
}

/** Hero swatches with live "ab" prices from the configurator products. */
function kf24t_hero_swatches() {
	$min = [];
	if ( function_exists( 'kf24_all_cfg_products' ) ) {
		foreach ( kf24_all_cfg_products() as $p ) {
			$min[ $p['group'] ] = isset( $min[ $p['group'] ] ) ? min( $min[ $p['group'] ], $p['m2'] ) : $p['m2'];
			$min[ $p['name'] ]  = $p['m2'];
		}
	}
	$f   = fn( $k, $fallback ) => isset( $min[ $k ] ) ? 'ab ' . number_format_i18n( $min[ $k ], 0 ) . ' € je m²' : $fallback;
	$url = fn( $s ) => home_url( '/' . $s . '/' );
	return [
		[ 'm' => 'eiche', 'n' => 'Eiche massiv', 'p' => 'Massivholz 3-Schicht · ' . $f( 'Massivholzfront 3-Schicht PG2', 'ab 306 € je m²' ) ],
		[ 'm' => 'eiche-wild', 'n' => 'Wildeiche massiv', 'p' => 'Massivholz 3-Schicht · ' . $f( 'Massivholz', 'ab 217 € je m²' ) ],
		[ 'm' => 'nussbaum', 'n' => 'Nussbaum massiv', 'p' => 'Massivholz 3-Schicht · ' . $f( 'Massivholzfront 3-Schicht PG4', 'ab 544 € je m²' ) ],
		[ 'm' => 'esche', 'n' => 'Esche massiv', 'p' => 'Massivholz 3-Schicht · ' . $f( 'Massivholzfront 3-Schicht PG2', 'ab 306 € je m²' ) ],
		[ 'm' => 'furnier-eiche', 'n' => 'Eiche furniert', 'p' => 'Echtholz furniert · ' . $f( 'Furnier', 'ab 178 € je m²' ) ],
		[ 'm' => 'weiss', 'n' => 'Weiß matt', 'p' => 'Dekorfront matt · ' . $f( 'Dekor', 'ab 95 € je m²' ) ],
		[ 'm' => 'beton', 'n' => 'Beton-Optik', 'p' => 'Dekor Materialreproduktion · ' . $f( 'Dekor', 'ab 95 € je m²' ) ],
		[ 'm' => 'salbei', 'n' => 'Salbei matt lackiert', 'p' => 'Lackfront matt, RAL · ' . $f( 'Lackfront matt Flair bunt', 'ab 235 € je m²' ) ],
		[ 'm' => 'fenix', 'n' => 'Fenix NTM Nero', 'p' => 'Fenix NTM, supermatt · ' . $f( 'Fenix', 'ab 415 € je m²' ) ],
	];
}

/* ---------- Header cart count (AJAX fragments) ---------- */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $f ) {
	$f['span.cnt'] = '<span class="cnt">' . ( WC()->cart->get_cart_contents_count() ?: '' ) . '</span>';
	return $f;
} );

/* ---------- WooCommerce layout ---------- */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_filter( 'loop_shop_columns', fn() => 4 );
add_filter( 'loop_shop_per_page', fn() => 24 );

/* ---------- Content helpers ---------- */
function kf24t_icon_check() {
	return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M4 10.5l4 4 8-9"/></svg>';
}

/** Page head with breadcrumb for normal pages. */
function kf24t_pagehead( $title, $lead = '' ) {
	echo '<div class="pagehead"><div class="wrap"><div class="crumb"><a href="' . esc_url( home_url( '/' ) ) . '">Start</a> / ' . esc_html( $title ) . '</div><h1>' . esc_html( $title ) . '</h1>' . ( $lead ? '<p class="lead">' . wp_kses_post( $lead ) . '</p>' : '' ) . '</div></div>';
}

require get_template_directory() . '/inc/setup-content.php';

/* Plugin styles (tables, forms) are used on several content pages. */
add_action( 'wp_enqueue_scripts', function () {
	if ( wp_style_is( 'kf24', 'registered' ) ) {
		wp_enqueue_style( 'kf24' );
	}
}, 20 );

/** Meta description (skipped when an SEO plugin handles it). */
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || class_exists( 'All_in_One_SEO_Pack' ) || defined( 'SEOPRESS_VERSION' ) ) {
		return;
	}
	$d = '';
	if ( is_front_page() ) {
		$d = 'Neue Küchenfronten nach Maß für IKEA Metod, Faktum und jede andere Küche. Massivholz, Furnier, Lack, Fenix und Dekor – gefertigt in Süddeutschland, online konfigurieren.';
	} elseif ( is_singular() ) {
		$p = get_queried_object();
		$d = function_exists( 'wc_get_product' ) && is_singular( 'product' ) && ( $wp = wc_get_product( $p ) ) ? $wp->get_short_description() : ( has_excerpt( $p ) ? get_the_excerpt( $p ) : $p->post_content );
	} elseif ( is_category() || is_tax() ) {
		$d = term_description();
	}
	$d = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) $d ) ) ) );
	if ( $d === '' ) {
		$d = get_bloginfo( 'description' );
	}
	if ( mb_strlen( $d ) > 158 ) {
		$d = rtrim( mb_substr( $d, 0, 155 ), " ,.;:-" ) . ' …';
	}
	echo '<meta name="description" content="' . esc_attr( $d ) . '">' . "\n";
}, 2 );
