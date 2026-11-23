<?php
/**
 * One-click setup: pages, menus, front page, WooCommerce page settings.
 * Darstellung → Küchenfront24 einrichten. Bestehende Seiten werden nur mit Häkchen überschrieben.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_theme_page( 'Küchenfront24 einrichten', 'Küchenfront24 einrichten', 'manage_options', 'kf24-setup', 'kf24t_setup_page' );
} );

add_action( 'admin_notices', function () {
	if ( get_option( 'kf24t_setup_done' ) || ( isset( $_GET['page'] ) && sanitize_key( wp_unslash( $_GET['page'] ) ) === 'kf24-setup' ) /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ ) {
		return;
	}
	echo '<div class="notice notice-info"><p><b>Küchenfront24-Theme aktiv.</b> <a href="' . esc_url( admin_url( 'themes.php?page=kf24-setup' ) ) . '">Seiten und Menüs jetzt einrichten</a></p></div>';
} );

function kf24t_setup_page() {
	echo '<div class="wrap"><h1>Küchenfront24 einrichten</h1>';
	if ( isset( $_GET['done'] ) ) {
		echo '<div class="notice notice-success"><p>Einrichtung abgeschlossen.</p><pre>' . esc_html( implode( "\n", get_transient( 'kf24t_log' ) ?: [] ) ) . '</pre></div>';
	}
	echo '<p>Legt alle Inhaltsseiten (Frontarten, Ratgeber, Rechtstexte …), die Menüs und die Startseite an. Bereits vorhandene Seiten mit gleicher Adresse bleiben unverändert, außer Sie setzen das Häkchen.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'kf24t_setup' );
	echo '<input type="hidden" name="action" value="kf24t_setup">';
	echo '<p><label><input type="checkbox" name="overwrite" value="1"> Vorhandene <b>Inhaltsseiten</b> mit gleicher Adresse überschreiben (Frontarten, Ratgeber, Versand, Über uns …)</label></p>';
	echo '<p><label><input type="checkbox" name="overwrite_legal" value="1"> Vorhandene <b>Rechtstexte</b> überschreiben (Impressum, Datenschutz, AGB, Widerrufsbelehrung, Vertrag widerrufen). Nur nach Prüfung der neuen Texte!</label></p>';
	echo '<p><label><input type="checkbox" name="classic" value="1" checked> Warenkorb und Kasse auf klassische Darstellung umstellen (empfohlen, nötig für „Zahlungspflichtig bestellen“ und Konfigurator-Details)</label></p>';
	echo '<p><label><input type="checkbox" name="menus" value="1" checked> Menüs anlegen bzw. ersetzen</label></p>';
	submit_button( 'Jetzt einrichten' );
	echo '</form></div>';
}

add_action( 'admin_post_kf24t_setup', function () {
	check_admin_referer( 'kf24t_setup' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Keine Berechtigung' );
	}
	$log = kf24t_run_setup( ! empty( $_POST['overwrite'] ), ! empty( $_POST['classic'] ), ! empty( $_POST['menus'] ), ! empty( $_POST['overwrite_legal'] ) );
	set_transient( 'kf24t_log', $log, 300 );
	update_option( 'kf24t_setup_done', 1 );
	wp_safe_redirect( admin_url( 'themes.php?page=kf24-setup&done=1' ) );
	exit;
} );

function kf24t_upsert_page( $slug, $title, $content, $overwrite, &$log, $lead = '' ) {
	$p = get_page_by_path( $slug );
	if ( $p && ! $overwrite ) {
		$log[] = 'Vorhanden (unverändert): /' . $slug . '/';
		return $p->ID;
	}
	if ( strpos( $content, '<!-- wp:' ) === false ) {
		// Layout HTML: keep it as one HTML block so WordPress does not add stray <p> tags.
		$content = '<!-- wp:html -->' . $content . '<!-- /wp:html -->';
	}
	$args = [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content ];
	if ( $p ) {
		$args['ID'] = $p->ID;
	}
	$id = wp_insert_post( $args );
	if ( $lead ) {
		update_post_meta( $id, '_kf24_lead', $lead );
	}
	$log[] = ( $p ? 'Überschrieben: /' : 'Angelegt: /' ) . $slug . '/';
	return $id;
}

function kf24t_run_setup( $overwrite = false, $classic = true, $menus = true, $overwrite_legal = false ) {
	$legal = [ 'impressum', 'datenschutz', 'agb', 'widerrufsbelehrung', 'vertrag-widerrufen' ];
	$log   = [];
	$pages = json_decode( file_get_contents( get_template_directory() . '/inc/pages.json' ), true );
	$ids   = [];
	foreach ( $pages as $p ) {
		$ids[ $p['slug'] ] = kf24t_upsert_page( $p['slug'], $p['title'], $p['content'], in_array( $p['slug'], $legal, true ) ? $overwrite_legal : $overwrite, $log, $p['lead'] );
	}
	$ids['start'] = kf24t_upsert_page( 'start', 'Start', '', false, $log );
	$ids['blog']  = kf24t_upsert_page( 'blog', 'Blog', '', false, $log );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['start'] );
	update_option( 'page_for_posts', $ids['blog'] );
	update_option( 'wp_page_for_privacy_policy', $ids['datenschutz'] );
	if ( ! get_category_by_slug( 'referenzen' ) ) {
		wp_insert_term( 'Referenzen', 'category', [ 'slug' => 'referenzen' ] );
		$log[] = 'Kategorie „Referenzen“ angelegt';
	}
	if ( function_exists( 'wc_get_page_id' ) ) {
		update_option( 'woocommerce_terms_page_id', $ids['agb'] );
		if ( $classic ) {
			foreach ( [ 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]' ] as $k => $sc ) {
				$pid = wc_get_page_id( $k );
				if ( $pid > 0 ) {
					wp_update_post( [ 'ID' => $pid, 'post_content' => '<!-- wp:shortcode -->' . $sc . '<!-- /wp:shortcode -->' ] );
					$log[] = 'Klassische Darstellung: ' . get_the_title( $pid );
				}
			}
		}
	}
	if ( $menus ) {
		$cat  = get_category_by_slug( 'referenzen' );
		$fc   = get_term_by( 'slug', 'fronten-fuer-metod', 'product_cat' );
		$shop = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		kf24t_menu( 'Hauptnavigation', 'primary', [
			[ 'page', $ids['frontarten'], 'Frontarten', [
				[ 'page', $ids['frontarten'], 'Alle Frontarten und Preise' ],
				[ 'page', $ids['ikea-metod'], 'Für IKEA Metod' ],
				[ 'page', $ids['ikea-faktum'], 'Für IKEA Faktum' ],
				[ 'page', $ids['nach-mass'], 'Nach Maß für jeden Korpus' ],
			] ],
			$shop > 0 ? [ 'page', $shop, 'Shop' ] : ( $fc ? [ 'term', $fc->term_id, 'Shop', [], 'product_cat' ] : null ),
			[ 'page', $ids['aufmass'], 'Planen & Preis' ],
			[ 'page', $ids['muster'], 'Muster' ],
			$cat ? [ 'term', $cat->term_id, 'Referenzen', [], 'category' ] : null,
			[ 'page', $ids['ratgeber'], 'Ratgeber', [
				[ 'page', $ids['ratgeber'], 'Aufmaß und Montage' ],
				[ 'page', $ids['aufmass'], 'Aufmaß-Tabelle' ],
				[ 'page', $ids['faq'], 'Häufige Fragen' ],
				[ 'page', $ids['service'], 'Service und Preise' ],
				[ 'page', $ids['versand'], 'Versand und Zahlung' ],
			] ],
			[ 'page', $ids['ueber-uns'], 'Über uns' ],
			[ 'page', $ids['kontakt'], 'Kontakt' ],
		] );
		kf24t_menu( 'Footer Fronten', 'footer_shop', [ [ 'page', $ids['frontarten'], 'Frontarten und Preise' ], [ 'page', $ids['ikea-metod'], 'IKEA Metod' ], [ 'page', $ids['ikea-faktum'], 'IKEA Faktum' ], [ 'page', $ids['nach-mass'], 'Nach Maß' ], [ 'page', $ids['muster'], 'Muster bestellen' ], [ 'page', $ids['aufmass'], 'Planen & Preis' ], [ 'page', $ids['ral-farben'], 'RAL-Farben' ] ] );
		kf24t_menu( 'Footer Hilfe', 'footer_help', [ [ 'page', $ids['ratgeber'], 'Aufmaß und Montage' ], [ 'page', $ids['aufmass'], 'Aufmaß-Tabelle' ], [ 'page', $ids['faq'], 'Häufige Fragen' ], [ 'page', $ids['service'], 'Service und Preise' ], [ 'page', $ids['versand'], 'Versand und Zahlung' ], [ 'page', $ids['ueber-uns'], 'Über uns' ], [ 'page', $ids['kontakt'], 'Kontakt' ] ] );
		kf24t_menu( 'Footer Rechtliches', 'footer_legal', [ [ 'page', $ids['impressum'], 'Impressum' ], [ 'page', $ids['datenschutz'], 'Datenschutz' ], [ 'page', $ids['agb'], 'AGB' ], [ 'page', $ids['widerrufsbelehrung'], 'Widerrufsbelehrung' ] ] );
		$log[] = 'Menüs angelegt';
	}
	flush_rewrite_rules();
	return $log;
}

function kf24t_menu( $name, $location, $items ) {
	$m = wp_get_nav_menu_object( $name );
	if ( $m ) {
		wp_delete_nav_menu( $m->term_id );
	}
	$mid = wp_create_nav_menu( $name );
	$add = function ( $it, $parent = 0 ) use ( $mid, &$add ) {
		if ( ! $it ) {
			return;
		}
		[ $type, $obj, $title ] = $it;
		$args = [ 'menu-item-title' => $title, 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent ];
		if ( $type === 'page' ) {
			$args += [ 'menu-item-object' => 'page', 'menu-item-object-id' => $obj, 'menu-item-type' => 'post_type' ];
		} else {
			$args += [ 'menu-item-object' => $it[4], 'menu-item-object-id' => $obj, 'menu-item-type' => 'taxonomy' ];
		}
		$id = wp_update_nav_menu_item( $mid, 0, $args );
		foreach ( $it[3] ?? [] as $child ) {
			$add( $child, $id );
		}
	};
	foreach ( $items as $it ) {
		$add( $it );
	}
	$loc              = get_theme_mod( 'nav_menu_locations', [] );
	$loc[ $location ] = $mid;
	set_theme_mod( 'nav_menu_locations', $loc );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'kf24 theme-setup', function ( $a, $assoc ) {
		foreach ( kf24t_run_setup( ! empty( $assoc['overwrite'] ), true, true, ! empty( $assoc['overwrite-legal'] ) ) as $l ) {
			WP_CLI::log( $l );
		}
		update_option( 'kf24t_setup_done', 1 );
		WP_CLI::success( 'Fertig.' );
	} );
}
