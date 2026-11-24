<?php
/**
 * Küchensysteme (IKEA Metod / Faktum) as ONE parts catalog instead of thousands of variations.
 * – Catalog: name, size, part type, hinge defaults – maintained once, used by every front family.
 * – Price: formula per front family (m², edge, piece, minimum) + optional fixed price per catalog part.
 * – The configurator offers "Teil aus Küchensystem wählen"; the Aufmaß/Bestellhilfe uses the same data.
 */
defined( 'ABSPATH' ) || exit;

function kf24_catalog() {
	static $c = null;
	if ( $c === null ) {
		$c = get_option( 'kf24_catalog' );
		if ( ! is_array( $c ) || ! $c ) {
			$c = json_decode( (string) file_get_contents( KF24_DIR . 'data/catalog.json' ), true ) ?: [];
		}
	}
	return $c;
}

function kf24_catalog_part( $id ) {
	foreach ( kf24_catalog() as $p ) {
		if ( $p['id'] === $id ) {
			return $p;
		}
	}
	return null;
}

function kf24_catalog_systems() {
	return [ 'metod' => 'IKEA Metod', 'faktum' => 'IKEA Faktum' ];
}

/** Seed the catalog option once (setup). */
function kf24_import_catalog( $force = false ) {
	if ( $force || ! is_array( get_option( 'kf24_catalog' ) ) ) {
		update_option( 'kf24_catalog', json_decode( (string) file_get_contents( KF24_DIR . 'data/catalog.json' ), true ) ?: [], false );
	}
}

/** Catalog for JS (configurator, planner). */
function kf24_catalog_js() {
	return array_map( fn( $p ) => [
		'id' => $p['id'], 's' => $p['system'], 'g' => $p['group'], 'n' => $p['name'], 'w' => (float) $p['w'], 'h' => (float) $p['h'],
		't' => $p['type'], 'a' => $p['hinge'], 'b' => (int) $p['hinges'], 'hr' => array_map( 'floatval', (array) $p['hraw'] ), 'hl' => $p['hlast'], 'x' => $p['note'],
	], kf24_catalog() );
}

/* ---------------------------------------------------------------------
 * Mein Shop → Küchensysteme & Preise (Variante B)
 * ------------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	add_submenu_page( 'kf24-shop', 'Metod & Faktum: Teile und Preise', 'Metod & Faktum', KF24_CAP, 'kf24-systeme', 'kf24_page_systems' );
}, 12 );

function kf24_page_systems() {
	$fronts = array_values( kf24_all_cfg_products() );
	$tab    = sanitize_key( $_GET['tab'] ?? 'preise' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only
	$sys    = sanitize_key( $_GET['sys'] ?? 'metod' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only
	$sys    = array_key_exists( $sys, kf24_catalog_systems() ) ? $sys : 'metod';
	$url    = fn( $t, $s = null ) => admin_url( 'admin.php?page=kf24-systeme&tab=' . $t . '&sys=' . ( $s ?: $sys ) );
	kf24_admin_head( 'Metod & Faktum – Teile und Preise', 'Statt hunderter Einzelprodukte gibt es <b>eine Teileliste</b> je Küchensystem und <b>eine Preisformel</b> je Frontart. Nur wo du einen festen Preis willst, trägst du ihn ein.' );
	echo '<nav class="nav-tab-wrapper kf24a-tabs">';
	foreach ( [ 'preise' => '1. Preise', 'formel' => '2. Preisformel je Front', 'teile' => '3. Teileliste' ] as $k => $l ) {
		printf( '<a class="nav-tab%s" href="%s">%s</a>', $tab === $k ? ' nav-tab-active' : '', esc_url( $url( $k ) ), esc_html( $l ) );
	}
	echo '</nav><div class="kf24a-sys">';
	foreach ( kf24_catalog_systems() as $k => $l ) {
		printf( '<a class="button%s" href="%s">%s</a> ', $sys === $k ? ' button-primary' : '', esc_url( $url( $tab, $k ) ), esc_html( $l ) );
	}
	echo '</div>';
	$parts = array_values( array_filter( kf24_catalog(), fn( $p ) => $p['system'] === $sys ) );

	if ( $tab === 'formel' ) {
		echo '<p class="kf24a-hint">So rechnet der Shop jedes Teil: <b>Fläche (m²) × m²-Preis + Umfang (lfm) × Kantenpreis + Stückpauschale</b>, mindestens aber der Mindestpreis. Topfbänder, Grifffräsung und Dichtungen kommen wie gewohnt dazu. Die Formel gilt auch für Maßanfertigungen.</p>';
		echo '<form id="kf24a-formula"><table class="widefat striped kf24a-table kf24a-formula"><thead><tr><th>Front</th><th>m²-Preis €</th><th>Kante € je lfm</th><th>Stückpauschale €</th><th>Mindestpreis €</th><th>Beispiel Tür 597 × 797</th></tr></thead><tbody>';
		foreach ( $fronts as $f ) {
			printf(
				'<tr data-id="%1$d"><td><b>%2$s</b></td><td><input type="text" inputmode="decimal" name="m2" value="%3$s"></td><td><input type="text" inputmode="decimal" name="edge" value="%4$s"></td><td><input type="text" inputmode="decimal" name="piece" value="%5$s"></td><td><input type="text" inputmode="decimal" name="minprice" value="%6$s"></td><td class="kf24a-ex"></td></tr>',
				(int) $f['id'],
				esc_html( preg_replace( '/\s+–\s+individuell konfigurierbar$/u', '', $f['name'] ) ),
				esc_attr( wc_format_localized_decimal( $f['m2'] ) ),
				esc_attr( $f['edge'] ? wc_format_localized_decimal( $f['edge'] ) : '' ),
				esc_attr( $f['piece'] ? wc_format_localized_decimal( $f['piece'] ) : '' ),
				esc_attr( $f['minprice'] ? wc_format_localized_decimal( $f['minprice'] ) : '' )
			);
		}
		echo '</tbody></table><p><button type="submit" class="button button-primary button-hero">Formeln speichern</button></p></form>';
	} elseif ( $tab === 'teile' ) {
		echo '<p class="kf24a-hint">Die Teileliste gilt für alle Fronten. Maße in mm wie gefertigt. Topfbänder: Positionen in mm, durch Komma getrennt; „v. u.“ = das letzte Maß gilt von unten (bei Klappen von rechts). Leer = Standardpositionen.</p>';
		echo '<form id="kf24a-parts" data-sys="' . esc_attr( $sys ) . '"><table class="widefat striped kf24a-table kf24a-parts"><thead><tr><th>Gruppe</th><th>Bezeichnung</th><th>Breite</th><th>Höhe</th><th>Art</th><th>Anschlag</th><th>Topfb.</th><th>Positionen</th><th>v. u.</th><th>Hinweis für die Fertigung</th><th></th></tr></thead><tbody></tbody></table>';
		echo '<p><button type="button" class="button kf24a-part-add">+ Teil hinzufügen</button> <button type="submit" class="button button-primary button-hero">Teileliste speichern</button></p></form>';
		echo '<script type="application/json" id="kf24a-parts-data">' . wp_json_encode( $parts ) . '</script>';
	} else {
		echo '<p class="kf24a-hint">Grau = Preis aus der Formel. Tragen Sie nur dort einen Betrag ein, wo Sie einen <b>festen Preis</b> wollen (z. B. weil kleine Teile sonst zu günstig wären). Feld leeren = wieder Formel. Preise inkl. MwSt., ohne Topfbänder und Zuschläge.</p>';
		echo '<form id="kf24a-prices"><div class="kf24a-scroll"><table class="widefat kf24a-table kf24a-prices"><thead><tr><th class="kf24a-sticky-c">Teil</th><th>Maß</th>';
		foreach ( $fronts as $f ) {
			echo '<th title="' . esc_attr( $f['name'] ) . '">' . esc_html( kf24_short_front( $f ) ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		$grp = '';
		foreach ( $parts as $p ) {
			if ( $p['group'] !== $grp ) {
				$grp = $p['group'];
				echo '<tr class="kf24a-grp"><th class="kf24a-sticky-c" colspan="2">' . esc_html( $grp ) . '</th><td colspan="' . count( $fronts ) . '"></td></tr>';
			}
			echo '<tr><th class="kf24a-sticky-c">' . esc_html( $p['name'] ) . '</th><td class="kf24a-dim">' . esc_html( $p['w'] . ' × ' . $p['h'] ) . '</td>';
			foreach ( $fronts as $f ) {
				$cfg  = [ 'w' => (float) $p['w'], 'h' => (float) $p['h'], 'part' => '' ];
				$calc = kf24_base_price( $cfg, $f );
				$ov   = $f['overrides'][ $p['id'] ] ?? '';
				printf( '<td><input type="text" inputmode="decimal" data-f="%d" data-p="%s" value="%s" placeholder="%s" aria-label="%s"></td>', (int) $f['id'], esc_attr( $p['id'] ), esc_attr( $ov !== '' ? wc_format_localized_decimal( $ov ) : '' ), esc_attr( number_format( $calc['base'], 2, ',', '' ) ), esc_attr( $p['name'] . ', ' . $f['name'] ) );
			}
			echo '</tr>';
		}
		echo '</tbody></table></div><p class="kf24a-savebar"><button type="submit" class="button button-primary button-hero">Preise speichern</button> <span class="kf24a-changed"></span></p></form>';
	}
	echo '</div>';
}

function kf24_short_front( $f ) {
	$n = preg_replace( '/\s+–\s+individuell konfigurierbar$/u', '', $f['name'] );
	return str_replace( [ 'Massivholzfront 3-Schicht ', 'Echtholzfront furniert ', 'Dekorfront ', 'Lackfront ', 'Fenix NTM' ], [ 'Massiv ', 'Furnier ', 'Dekor ', 'Lack ', 'Fenix' ], $n );
}

/* AJAX save handlers (nonce + capability in kf24_ajax_guard) */
// phpcs:disable WordPress.Security.NonceVerification.Missing
add_action( 'wp_ajax_kf24_save_prices', function () {
	kf24_ajax_guard();
	$data = json_decode( wp_unslash( (string) ( $_POST['data'] ?? '{}' ) ), true ) ?: []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values sanitized below
	$n    = 0;
	foreach ( $data as $fid => $parts ) {
		$fid = absint( $fid );
		if ( get_post_meta( $fid, '_kf24_enabled', true ) !== 'yes' ) {
			continue;
		}
		$ov = (array) ( get_post_meta( $fid, '_kf24_overrides', true ) ?: [] );
		foreach ( (array) $parts as $pid => $val ) {
			$pid = sanitize_key( $pid );
			if ( ! kf24_catalog_part( $pid ) ) {
				continue;
			}
			$v = kf24_num( sanitize_text_field( (string) $val ) );
			if ( $v > 0 ) {
				$ov[ $pid ] = round( $v, 2 );
			} else {
				unset( $ov[ $pid ] );
			}
			$n++;
		}
		update_post_meta( $fid, '_kf24_overrides', $ov );
	}
	wp_send_json_success( [ 'n' => $n ] );
} );

add_action( 'wp_ajax_kf24_save_formula', function () {
	kf24_ajax_guard();
	$data = json_decode( wp_unslash( (string) ( $_POST['data'] ?? '{}' ) ), true ) ?: []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values sanitized below
	foreach ( $data as $fid => $f ) {
		$fid = absint( $fid );
		if ( get_post_meta( $fid, '_kf24_enabled', true ) !== 'yes' ) {
			continue;
		}
		$m2 = kf24_num( sanitize_text_field( (string) ( $f['m2'] ?? '' ) ) );
		if ( $m2 > 0 ) {
			update_post_meta( $fid, '_kf24_m2', $m2 );
			update_post_meta( $fid, '_regular_price', wc_format_decimal( $m2 ) );
			update_post_meta( $fid, '_price', wc_format_decimal( $m2 ) );
		}
		foreach ( [ 'edge', 'piece', 'minprice' ] as $k ) {
			update_post_meta( $fid, '_kf24_' . $k, kf24_num( sanitize_text_field( (string) ( $f[ $k ] ?? '' ) ) ) );
		}
		wc_delete_product_transients( $fid );
	}
	wp_send_json_success();
} );

add_action( 'wp_ajax_kf24_save_parts', function () {
	kf24_ajax_guard();
	$sys  = sanitize_key( $_POST['sys'] ?? '' );
	$rows = json_decode( wp_unslash( (string) ( $_POST['data'] ?? '[]' ) ), true ) ?: []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- values sanitized below
	if ( ! array_key_exists( $sys, kf24_catalog_systems() ) ) {
		wp_send_json_error( [ 'msg' => 'Unbekanntes System.' ] );
	}
	$keep  = array_values( array_filter( kf24_catalog(), fn( $p ) => $p['system'] !== $sys ) );
	$types = [ 'tuer', 'schublade', 'klappe', 'blende', 'sonstig' ];
	$seen  = [];
	foreach ( $rows as $r ) {
		$name = sanitize_text_field( (string) ( $r['name'] ?? '' ) );
		$w    = kf24_num( $r['w'] ?? 0 );
		$h    = kf24_num( $r['h'] ?? 0 );
		if ( $name === '' || $w <= 0 || $h <= 0 ) {
			continue;
		}
		$id = sanitize_key( (string) ( $r['id'] ?? '' ) ) ?: sanitize_key( $sys . '-' . remove_accents( $name ) . '-' . wp_rand( 100, 999 ) );
		if ( isset( $seen[ $id ] ) ) {
			$id .= '-' . wp_rand( 100, 999 );
		}
		$seen[ $id ] = 1;
		$type        = in_array( $r['type'] ?? '', $types, true ) ? $r['type'] : 'tuer';
		$hinge       = in_array( $r['hinge'] ?? '', [ 'links', 'rechts', 'oben', 'keine' ], true ) ? $r['hinge'] : 'keine';
		$hraw        = array_values( array_filter( array_map( 'kf24_num', preg_split( '/[;\s]+/', str_replace( ',', ' ', (string) ( $r['hraw'] ?? '' ) ) ) ) ) );
		$keep[]      = [
			'id'     => $id,
			'system' => $sys,
			'group'  => sanitize_text_field( (string) ( $r['group'] ?? '' ) ) ?: 'Sonstiges',
			'name'   => $name,
			'w'      => $w,
			'h'      => $h,
			'type'   => $type,
			'hinge'  => $hinge,
			'hinges' => $hinge === 'keine' ? 0 : max( 1, min( 5, (int) ( $r['hinges'] ?? 2 ) ) ),
			'hraw'   => $hraw,
			'hlast'  => ! empty( $r['hlast'] ) ? 'ende' : 'start',
			'note'   => sanitize_text_field( (string) ( $r['note'] ?? '' ) ),
		];
	}
	update_option( 'kf24_catalog', $keep, false );
	wp_send_json_success( [ 'n' => count( $rows ) ] );
} );
// phpcs:enable

/* ---------------------------------------------------------------------
 * [kf24_faktum_tabelle] – the Faktum order help as a table on the website
 * ------------------------------------------------------------------ */
add_shortcode( 'kf24_faktum_tabelle', function () {
	wp_enqueue_style( 'kf24' );
	$parts = array_filter( kf24_catalog(), fn( $p ) => $p['system'] === 'faktum' );
	$by    = [];
	foreach ( $parts as $p ) {
		$by[ $p['group'] ][] = $p;
	}
	$planner = ( $pg = get_page_by_path( 'aufmass' ) ) ? get_permalink( $pg ) : home_url( '/aufmass/' );
	$o       = '<div class="kf24-ft">';
	foreach ( $by as $g => $list ) {
		$o .= '<details class="kf24-ft-g"' . ( $g === 'Türen' ? ' open' : '' ) . '><summary>' . esc_html( $g ) . ' <small>' . count( $list ) . ' Teile</small></summary><div class="kf24-scroll"><table class="kf24-ft-t"><thead><tr><th>Bezeichnung</th><th>Maß B × H (mm)</th><th>Topfbänder</th><th>Hinweis</th></tr></thead><tbody>';
		foreach ( $list as $p ) {
			$hb = '–';
			if ( $p['hinge'] !== 'keine' && $p['hinges'] ) {
				$pos = $p['hraw'];
				if ( $p['hlast'] === 'ende' && count( $pos ) >= 2 ) {
					$last = array_pop( $pos );
					$hb   = implode( ' / ', $pos ) . ' mm von ' . ( $p['hinge'] === 'oben' ? 'links' : 'oben' ) . ', ' . $last . ' mm von ' . ( $p['hinge'] === 'oben' ? 'rechts' : 'unten' );
				} else {
					$hb = $pos ? implode( ' / ', $pos ) . ' mm' : $p['hinges'] . ' Stück';
				}
			}
			$o .= '<tr><td><b>' . esc_html( $p['name'] ) . '</b></td><td>' . esc_html( $p['w'] . ' × ' . $p['h'] ) . '</td><td>' . esc_html( $hb ) . '</td><td>' . esc_html( $p['note'] ) . '</td></tr>';
		}
		$o .= '</tbody></table></div></details>';
	}
	return $o . '<p><a class="btn" href="' . esc_url( add_query_arg( 'system', 'faktum', $planner ) ) . '">Diese Teile in der Bestellhilfe antippen</a></p></div>';
} );
