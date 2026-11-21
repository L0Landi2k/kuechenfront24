<?php
defined( 'ABSPATH' ) || exit;

function kf24_term( $name, $slug, $parent = 0 ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$r = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug, 'parent' => $parent ] );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

/**
 * Creates or updates the 21 configurable products and the samples.
 * Existing products (same slug) keep title, text and images; prices/options only change with $overwrite.
 */
function kf24_run_setup( $overwrite = false ) {
	$data = json_decode( file_get_contents( KF24_DIR . 'data/products.json' ), true );
	$log  = [];
	$n    = kf24_import_materials();
	$log[] = $n . ' Materialien in die Materialbibliothek übernommen';
	$fc   = kf24_term( 'Fronten für Metod', kf24_opt( 'front_cat' ) );
	$ic   = kf24_term( 'Individuell konfigurierbar', 'individuell-konfigurierbar', $fc );
	$mc   = kf24_term( 'Muster', kf24_opt( 'sample_cat' ) );
	foreach ( $data['samples'] as $s ) {
		$post = get_page_by_path( $s['slug'], OBJECT, 'product' );
		if ( $post ) {
			$p = wc_get_product( $post->ID );
			$log[] = 'Muster vorhanden: ' . $s['slug'];
		} else {
			$p = new WC_Product_Simple();
			$p->set_name( $s['name'] );
			$p->set_slug( $s['slug'] );
			$p->set_regular_price( $s['price'] );
			$p->set_status( 'publish' );
			$log[] = 'Muster angelegt: ' . $s['slug'];
		}
		if ( ! $p->get_weight() ) {
			$p->set_weight( 0.3 );
		}
		$p->set_category_ids( array_unique( array_merge( $p->get_category_ids(), [ $mc ] ) ) );
		$p->save();
		if ( ! get_post_meta( $p->get_id(), '_kf24_swatch', true ) ) {
			update_post_meta( $p->get_id(), '_kf24_swatch', $s['swatch'] );
		}
	}
	foreach ( $data['products'] as $d ) {
		$post = get_page_by_path( $d['slug'], OBJECT, 'product' );
		$new  = ! $post;
		if ( $new ) {
			$p = new WC_Product_Simple();
			$p->set_name( $d['name'] . ' – individuell konfigurierbar' );
			$p->set_slug( $d['slug'] );
			$p->set_status( 'publish' );
			$p->set_short_description( 'Individuelle Front nach Maß, passend für jeden Korpus. Maße millimetergenau, Bohrungen für Topfbänder und Griffe nach Ihren Angaben.' );
			$p->set_category_ids( [ $ic ] );
			$p->save();
		} else {
			$p = wc_get_product( $post->ID );
			if ( ! $p->is_type( 'simple' ) ) {
				$log[] = 'Übersprungen (kein einfaches Produkt): ' . $d['slug'];
				continue;
			}
		}
		$id    = $p->get_id();
		$force = $new || $overwrite;
		update_post_meta( $id, '_kf24_enabled', 'yes' );
		foreach ( [ '_kf24_group' => $d['group'], '_kf24_field' => $d['field'], '_kf24_lz' => $d['lz'], '_kf24_thickness' => $d['thickness'], '_kf24_kgm2' => $d['kgm2'], '_kf24_sample' => $d['sample'], '_kf24_swatch' => $d['swatch'], '_kf24_milling' => $d['milling'] ? 'yes' : 'no' ] as $k => $v ) {
			if ( $force || get_post_meta( $id, $k, true ) === '' ) {
				update_post_meta( $id, $k, $v );
			}
		}
		if ( $force || ! get_post_meta( $id, '_kf24_m2', true ) ) {
			update_post_meta( $id, '_kf24_m2', $d['m2'] );
			$p->set_regular_price( $d['m2'] );
		}
		if ( $force || ! get_post_meta( $id, '_kf24_options', true ) ) {
			update_post_meta( $id, '_kf24_options', implode( "\n", $d['options'] ) );
		}
		kf24_ensure_materials( $d['options'] );
		$p->set_sold_individually( false );
		$p->save();
		$log[] = ( $new ? 'Angelegt: ' : 'Aktualisiert: ' ) . $d['slug'];
	}
	return $log;
}

add_action( 'admin_post_kf24_setup', function () {
	check_admin_referer( 'kf24_setup' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Keine Berechtigung' );
	}
	$log = kf24_run_setup( ! empty( $_POST['overwrite_prices'] ) );
	set_transient( 'kf24_setup_log', $log, 300 );
	wp_safe_redirect( admin_url( 'admin.php?page=kf24-settings&kf24_done=1' ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( empty( $_GET['kf24_done'] ) ) {
		return;
	}
	$log = get_transient( 'kf24_setup_log' ) ?: [];
	echo '<div class="notice notice-success"><p><b>Einrichtung abgeschlossen.</b></p><details><summary>' . count( $log ) . ' Einträge</summary><pre>' . esc_html( implode( "\n", $log ) ) . '</pre></details></div>';
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'kf24 setup', function ( $args, $assoc ) {
		foreach ( kf24_run_setup( ! empty( $assoc['overwrite'] ) ) as $l ) {
			WP_CLI::log( $l );
		}
		WP_CLI::success( 'Fertig.' );
	} );
}
