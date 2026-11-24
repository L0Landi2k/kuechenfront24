<?php
/**
 * Builds the complete Küchenfront24 test shop inside WordPress Playground.
 * Run with: wp eval-file demo-setup.php
 * Expects WooCommerce, the plugin "kf24-konfigurator" and the theme "kuechenfront24" to be active,
 * and the demo pictures in /tmp/kf24/ (written by the blueprint).
 */

( function () {
// Run as the administrator so page layouts (forms, SVG icons) are stored unfiltered.
wp_set_current_user( 1 );
kses_remove_filters();
$log = function ( $m ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $m );
	}
};

/* ---------- WordPress basics ---------- */
update_option( 'blogname', 'Küchenfront24' );
update_option( 'blogdescription', 'Neue Fronten für IKEA Metod, Faktum und jede andere Küche' );
update_option( 'timezone_string', 'Europe/Berlin' );
update_option( 'date_format', 'd.m.Y' );
update_option( 'time_format', 'H:i' );
update_option( 'start_of_week', 1 );
update_option( 'blog_public', 0 );
update_option( 'default_comment_status', 'closed' );
update_option( 'permalink_structure', '/%postname%/' );
wp_delete_post( 1, true ); // "Hello world"
if ( $p = get_page_by_path( 'sample-page' ) ) {
	wp_delete_post( $p->ID, true );
}

/* ---------- WooCommerce basics ---------- */
$wc = [
	'woocommerce_store_address'                => 'Roseggerstraße 6',
	'woocommerce_store_city'                   => 'Ottobrunn',
	'woocommerce_store_postcode'               => '85521',
	'woocommerce_default_country'              => 'DE',
	'woocommerce_allowed_countries'            => 'specific',
	'woocommerce_specific_allowed_countries'   => [ 'DE', 'CH' ],
	'woocommerce_ship_to_countries'            => '',
	'woocommerce_currency'                     => 'EUR',
	'woocommerce_currency_pos'                 => 'right_space',
	'woocommerce_price_thousand_sep'           => '.',
	'woocommerce_price_decimal_sep'            => ',',
	'woocommerce_price_num_decimals'           => 2,
	'woocommerce_calc_taxes'                   => 'yes',
	'woocommerce_prices_include_tax'           => 'yes',
	'woocommerce_tax_display_shop'             => 'incl',
	'woocommerce_tax_display_cart'             => 'incl',
	'woocommerce_tax_based_on'                 => 'base',
	'woocommerce_weight_unit'                  => 'kg',
	'woocommerce_dimension_unit'               => 'mm',
	'woocommerce_enable_guest_checkout'        => 'yes',
	'woocommerce_enable_checkout_login_reminder' => 'no',
	'woocommerce_enable_reviews'               => 'no',
	'woocommerce_coming_soon'                  => 'no',
	'woocommerce_store_pages_only'             => 'no',
	'woocommerce_onboarding_profile'           => [ 'skipped' => true, 'completed' => true ],
	'woocommerce_task_list_hidden'             => 'yes',
	'woocommerce_task_list_complete'           => 'yes',
	'woocommerce_extended_task_list_hidden'    => 'yes',
	'woocommerce_show_marketplace_suggestions' => 'no',
	'woocommerce_allow_tracking'               => 'no',
	'woocommerce_analytics_enabled'            => 'no',
	'woocommerce_feature_order_attribution_enabled' => 'no',
	'woocommerce_email_from_name'              => 'Küchenfront24',
	'woocommerce_email_from_address'           => 'info@kuechenfront24.de',
	'admin_email'                              => 'info@kuechenfront24.de',
];
$wc['woocommerce_permalinks'] = [ 'product_base' => 'produkt', 'category_base' => 'produkt-kategorie', 'tag_base' => 'produkt-schlagwort', 'attribute_base' => '', 'use_verbose_page_rules' => false ];
foreach ( $wc as $k => $v ) {
	update_option( $k, $v );
}

// 19 % MwSt.
	global $wpdb;
if ( ! $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates" ) ) {
	WC_Tax::_insert_tax_rate( [ 'tax_rate_country' => 'DE', 'tax_rate' => '19.0000', 'tax_rate_name' => 'MwSt.', 'tax_rate_priority' => 1, 'tax_rate_compound' => 0, 'tax_rate_shipping' => 1, 'tax_rate_order' => 0, 'tax_rate_class' => '' ] );
}

// Versand: Deutschland + Schweiz mit der Küchenfront24-Versandart
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	( new WC_Shipping_Zone( $z['id'] ) )->delete();
}
foreach ( [ 'Deutschland' => 'DE', 'Schweiz' => 'CH' ] as $name => $cc ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( $name );
	$zone->add_location( $cc, 'country' );
	$zone->save();
	$zone->add_shipping_method( 'kf24_shipping' );
}

// Zahlung: Vorkasse (in der Testversion wird nichts abgebucht)
update_option( 'woocommerce_bacs_settings', [ 'enabled' => 'yes', 'title' => 'Vorkasse per Überweisung', 'description' => 'Die Bankverbindung erhalten Sie mit der Auftragsbestätigung.', 'instructions' => 'Bitte überweisen Sie den Betrag unter Angabe der Bestellnummer. Die Fertigung beginnt nach Zahlungseingang.' ] );
update_option( 'woocommerce_bacs_accounts', [ [ 'account_name' => 'Küchenfront24 (Testkonto)', 'account_number' => '', 'bank_name' => 'Testbank', 'sort_code' => '', 'iban' => 'DE00 0000 0000 0000 0000 00', 'bic' => 'TESTDEFFXXX' ] ] );
foreach ( [ 'cheque', 'cod', 'paypal' ] as $g ) {
	update_option( 'woocommerce_' . $g . '_settings', [ 'enabled' => 'no' ] );
}

// WooCommerce-Seiten auf Deutsch
foreach ( [ 'shop' => [ 'Shop', 'shop' ], 'cart' => [ 'Warenkorb', 'warenkorb' ], 'checkout' => [ 'Kasse', 'kasse' ], 'myaccount' => [ 'Mein Konto', 'mein-konto' ] ] as $k => [ $title, $slug ] ) {
	$id = wc_get_page_id( $k );
	if ( $id > 0 ) {
		wp_update_post( [ 'ID' => $id, 'post_title' => $title, 'post_name' => $slug ] );
	}
}

/* ---------- Küchenfront24: Fronten, Muster, Materialien, Seiten, Menüs ---------- */
foreach ( kf24_run_setup( true ) as $l ) {
	$log( $l );
}
if ( function_exists( 'kf24t_run_setup' ) ) {
	foreach ( kf24t_run_setup( true, true, true, true ) as $l ) {
		$log( $l );
	}
}

/* ---------- Referenzen (über den gleichen Weg wie in „Mein Shop“) ---------- */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$done = [];
$att  = function ( $file, $title ) use ( &$done ) {
	if ( isset( $done[ $file ] ) ) {
		return $done[ $file ];
	}
	$src = '/tmp/kf24/' . $file;
	if ( ! is_readable( $src ) ) {
		return 0;
	}
	$tmp = wp_tempnam( $file );
	copy( $src, $tmp );
	$id = media_handle_sideload( [ 'name' => $file, 'tmp_name' => $tmp ], 0, $title );
	return $done[ $file ] = is_wp_error( $id ) ? 0 : $id;
};
$refs = [
	[ 'Vorher, nachher: neue Fronten statt neuer Küche', 'Dieselbe Küche, nur die Fronten getauscht: aus grauer Betonoptik wird warme Eiche.', "Die Korpusse waren noch top, nur die grauen Fronten passten nicht mehr. Statt die Küche abzureißen, haben wir alle Fronten in massiver Eiche neu gefertigt.\n\nZiehen Sie den Regler im Bild hin und her: links vorher, rechts nachher.", 'vorher-nachher', 'ad10.jpg', 'ad11.jpg', '', 'Massivholz Eiche, geölt', 'IKEA Metod', 'München', 26 ],
	[ 'Eichenfronten mit angefräster Griffmulde', 'Zwei Küchenzeilen auf Metod-Korpussen. Fronten aus geölter Eiche, Arbeitsplatten aus massiver Eiche.', 'Grifflos, ruhig und warm: Die Griffmulde ist direkt in die 3-Schicht-Eiche gefräst. So bleibt die Front flächig, und trotzdem greift man sicher zu.', 'einzelbild', '', '', 'ad11.jpg', 'Massivholz Eiche mit Grifffräsung', 'IKEA Metod', '', 18 ],
	[ 'Hängesideboard auf Basis Metod', 'Wildeiche 3-Schicht massiv, geölt, mit angefräster Griffmulde. Abdeckplatte aus astiger Eiche, 305 cm lang.', "Aus vier Metod-Korpussen wird ein schwebendes Sideboard fürs Wohnzimmer. Die Fronten sind aus Wildeiche mit Griffmulde, die Abdeckplatte aus astiger Eiche mit 305 cm Länge.\n\nDas Bild ist eine Illustration. Das Foto des fertigen Sideboards folgt.", 'einzelbild', '', '', 'sideboard.jpg', 'Wildeiche massiv, Griffmulde', 'IKEA Metod (Wandmontage)', '', 9 ],
];
foreach ( $refs as [ $title, $excerpt, $text, $layout, $b, $a, $cover, $front, $kitchen, $place, $days ] ) {
	$old = get_posts( [ 'title' => $title, 'post_type' => 'post', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1 ] );
	$id  = kf24_ref_save( [
		'title'   => $title,
		'excerpt' => $excerpt,
		'text'    => $text,
		'layout'  => $layout,
		'before'  => $b ? $att( $b, 'Vorher' ) : 0,
		'after'   => $a ? $att( $a, 'Nachher' ) : 0,
		'cover'   => $cover ? $att( $cover, $title ) : 0,
		'gallery' => [],
		'front'   => $front,
		'kitchen' => $kitchen,
		'place'   => $place,
		'online'  => true,
	], $old ? $old[0] : 0 );
	if ( ! is_wp_error( $id ) ) {
		$date = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		wp_update_post( [ 'ID' => $id, 'post_date' => $date, 'post_date_gmt' => $date ] );
	}
}
$log( 'Referenzen angelegt' );

/* ---------- Variante B: Teileliste neu laden, zwei Beispiel-Festpreise ---------- */
kf24_import_catalog( true );
$mpg2 = get_page_by_path( 'massivholzfront-3-schicht-pg2-individuell-konfigurierbar', OBJECT, 'product' );
if ( $mpg2 && ! get_post_meta( $mpg2->ID, '_kf24_overrides', true ) ) {
	update_post_meta( $mpg2->ID, '_kf24_overrides', [ 'metod-tuer60x80' => 189.0, 'metod-schublade60x20' => 79.0 ] );
}

/* ---------- Michi als Shop-Inhaber ---------- */
if ( function_exists( 'kf24_add_owner_role' ) ) {
	kf24_add_owner_role();
}
if ( ! username_exists( 'michi' ) ) {
	wp_insert_user( [ 'user_login' => 'michi', 'user_pass' => 'kuechenfront24', 'user_email' => 'michi@example.test', 'first_name' => 'Michi', 'display_name' => 'Michi', 'role' => 'kf24_owner', 'locale' => 'de_DE' ] );
}
foreach ( get_users( [ 'fields' => 'ID' ] ) as $uid ) {
	update_user_meta( $uid, 'locale', 'de_DE' );
	update_user_meta( $uid, 'show_welcome_panel', 0 );
	update_user_meta( $uid, 'woocommerce_admin_help_panel_highlight_shown', 'yes' );
}

/* ---------- Testmodus ---------- */
update_option( 'kf24_demo', 1 );
update_option( 'kf24_demo_name', getenv( 'KF24_DEMO_NAME' ) ?: 'Andreas' );
delete_option( 'kf24_demo_mails' );

/* ---------- Eine Beispielbestellung, damit Fertigungsblatt & Co. sofort sichtbar sind ---------- */
$pid = (int) ( get_page_by_path( 'massivholzfront-3-schicht-pg2-individuell-konfigurierbar', OBJECT, 'product' )->ID ?? 0 );
if ( $pid && ! wc_get_orders( [ 'limit' => 1, 'return' => 'ids' ] ) ) {
	$pcfg  = kf24_product_cfg( $pid );
	$order = wc_create_order( [ 'status' => 'pending' ] );
	foreach ( [ [ 'tuer', 597, 797, 'links', 2, 'Spüle links' ], [ 'tuer', 397, 797, 'rechts', 2, 'Hochschrank' ], [ 'schublade', 597, 197, 'keine', 0, 'Besteck' ] ] as [ $type, $w, $h, $hinge, $n, $label ] ) {
		$raw = [ 'type' => $type, 'decor' => 'MFEI Eiche', 'w' => $w, 'h' => $h, 'grain' => 'aufrecht', 'label' => $label, 'hinge' => $hinge, 'hinges' => $n, 'body' => '19', 'deliver' => 'yes', 'grip' => '1', 'gpos' => $type === 'schublade' ? 'mitte' : 'oben-v', 'ga' => '40', 'gd' => '128', 'qty' => 1 ];
		$cfg = kf24_sanitize_config( $raw, $pcfg );
		$pr  = kf24_price( $cfg, $pcfg );
		$item_id = $order->add_product( wc_get_product( $pid ), 1, [ 'subtotal' => kf24_net( $pr['unit'] ), 'total' => kf24_net( $pr['unit'] ) ] );
		$item    = $order->get_item( $item_id );
		foreach ( kf24_spec_lines( $cfg ) as $k => $v ) {
			$item->add_meta_data( $k, $v, true );
		}
		$item->add_meta_data( '_kf24_cfg', $cfg, true );
		$item->add_meta_data( '_kf24_m2', $pcfg['m2'], true );
		$item->save();
	}
	$order->set_address( [ 'first_name' => 'Anna', 'last_name' => 'Beispiel', 'address_1' => 'Musterstraße 12', 'postcode' => '81667', 'city' => 'München', 'country' => 'DE', 'email' => 'anna@example.test', 'phone' => '089 123456' ], 'billing' );
	$order->set_address( [ 'first_name' => 'Anna', 'last_name' => 'Beispiel', 'address_1' => 'Musterstraße 12', 'postcode' => '81667', 'city' => 'München', 'country' => 'DE' ], 'shipping' );
	$ship = new WC_Order_Item_Shipping();
	$ship->set_method_title( 'Paketversand (10 bis 19,9 kg)' );
	$ship->set_method_id( 'kf24_shipping' );
	$ship->set_total( kf24_net( (float) kf24_opt( 'ship_t2' ) ) );
	$order->add_item( $ship );
	$order->set_payment_method( 'bacs' );
	$order->set_payment_method_title( 'Vorkasse per Überweisung' );
	$order->calculate_totals();
	$order->update_status( 'on-hold', 'Beispielbestellung für den Test.' );
	$log( 'Beispielbestellung angelegt' );
}
delete_option( 'kf24_demo_mails' );

flush_rewrite_rules();
$log( 'Testshop fertig.' );
} )();
