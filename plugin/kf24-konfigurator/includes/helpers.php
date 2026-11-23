<?php
defined( 'ABSPATH' ) || exit;

/** Default settings. Prices are gross (incl. VAT). */
function kf24_defaults() {
	return [
		'hinge_price'          => 4,      // € je mitgeliefertem Topfband
		'seal_price_lfm'       => 2,      // € je lfm Aufsteckdichtung
		'seal_note'            => 'fiktiv', // Zusatz in Klammern hinter dem Preis (leer = keiner)
		'milling_price'        => 30,     // € Grifffräsung je Front (Massivholz)
		'min_order_threshold'  => 300,
		'min_order_fee'        => 30,
		'length_limit'         => 950,
		'length_weight_limit'  => 20,
		'length_fee'           => 21,
		'min_dim'              => 60,
		'max_long'             => 2300,
		'max_short'            => 900,
		'min_area'             => 0,      // abgerechnete Mindestfläche je Front in m²
		'kgm2_default'         => 13,
		'ship_t1'              => 18.90,  // bis 9,9 kg
		'ship_t2'              => 28.90,  // bis 19,9 kg
		'ship_t3'              => 100,    // bis 49,9 kg
		'ship_t4'              => 140,    // ab 50 kg
		'ship_sample_de'       => 6.50,
		'ship_sample_ch'       => 27,
		'front_cat'            => 'fronten-fuer-metod',
		'sample_cat'           => 'muster',
		'notify_email'         => '',
		'phone'                => '089 462 05 236',
	];
}

function kf24_opt( $key ) {
	$o = get_option( 'kf24_settings', [] );
	$d = kf24_defaults();
	if ( $key === 'seal_note' && array_key_exists( $key, (array) $o ) ) {
		return $o[ $key ]; // may be intentionally empty
	}
	return array_key_exists( $key, (array) $o ) && $o[ $key ] !== '' ? $o[ $key ] : ( $d[ $key ] ?? null );
}

function kf24_num( $v ) {
	return (float) str_replace( ',', '.', (string) $v );
}

function kf24_is_cfg_product( $product ) {
	$product = $product instanceof WC_Product ? $product : wc_get_product( $product );
	if ( ! $product ) {
		return false;
	}
	$id = $product->get_parent_id() ?: $product->get_id();
	return get_post_meta( $id, '_kf24_enabled', true ) === 'yes';
}

function kf24_product_cfg( $product_id ) {
	$opts = array_values( array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $product_id, '_kf24_options', true ) ) ) ) );
	return [
		'id'        => (int) $product_id,
		'name'      => get_the_title( $product_id ),
		'url'       => get_permalink( $product_id ),
		'm2'        => kf24_num( get_post_meta( $product_id, '_kf24_m2', true ) ),
		'field'     => get_post_meta( $product_id, '_kf24_field', true ) ?: 'Dekor',
		'options'   => $opts,
		'lz'        => get_post_meta( $product_id, '_kf24_lz', true ),
		'group'     => get_post_meta( $product_id, '_kf24_group', true ),
		'milling'   => get_post_meta( $product_id, '_kf24_milling', true ) === 'yes',
		'thickness' => (int) get_post_meta( $product_id, '_kf24_thickness', true ) ?: 19,
		'kgm2'      => kf24_num( get_post_meta( $product_id, '_kf24_kgm2', true ) ) ?: (float) kf24_opt( 'kgm2_default' ),
		'sample'    => get_post_meta( $product_id, '_kf24_sample', true ),
		'swatch'    => get_post_meta( $product_id, '_kf24_swatch', true ),
		// price formula (all optional, 0 = not used)
		'piece'     => kf24_num( get_post_meta( $product_id, '_kf24_piece', true ) ),
		'edge'      => kf24_num( get_post_meta( $product_id, '_kf24_edge', true ) ),
		'minprice'  => kf24_num( get_post_meta( $product_id, '_kf24_minprice', true ) ),
		'overrides' => array_map( 'floatval', (array) ( get_post_meta( $product_id, '_kf24_overrides', true ) ?: [] ) ),
	];
}

/** All configurable products, ordered by group then price. */
function kf24_all_cfg_products() {
	$ids = get_posts( [
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_kf24_enabled',
		'meta_value'     => 'yes',
	] );
	$order = [ 'Dekor' => 1, 'Furnier' => 2, 'Massivholz' => 3, 'Lack' => 4, 'Fenix' => 5 ];
	$list  = array_map( 'kf24_product_cfg', $ids );
	usort( $list, function ( $a, $b ) use ( $order ) {
		return [ $order[ $a['group'] ] ?? 9, $a['m2'], $a['name'] ] <=> [ $order[ $b['group'] ] ?? 9, $b['m2'], $b['name'] ];
	} );
	return $list;
}

function kf24_sample_product_id( $slug ) {
	if ( ! $slug ) {
		return 0;
	}
	$p = get_page_by_path( $slug, OBJECT, 'product' );
	return $p ? (int) $p->ID : 0;
}

/** Gross → net using the shop's standard rate for the base country. */
function kf24_net( $gross ) {
	$rates = WC_Tax::get_base_tax_rates( '' );
	$rate  = $rates ? (float) reset( $rates )['rate'] : 0;
	return $gross / ( 1 + $rate / 100 );
}

/** Legacy swatch keys (Metod products) → bundled texture. */
function kf24_swatch_url( $key ) {
	$map = [ 'eiche' => 'furnier-eiche', 'eiche-wild' => 'dekor-h1180', 'eiche-rustikal' => 'dekor-h3303', 'nussbaum' => 'massiv-nussbaum', 'kirsche' => 'massiv-kirschbaum', 'esche' => 'massiv-esche', 'ahorn' => 'massiv-ahorn', 'buche' => 'massiv-buche', 'kernbuche' => 'massiv-kernbuche', 'birke' => 'massiv-birke', 'erle' => 'massiv-erle', 'furnier-eiche' => 'furnier-eiche', 'beton' => 'dekor-f186' ];
	return isset( $map[ $key ] ) ? KF24_URL . 'assets/tex/' . $map[ $key ] . '.jpg' : '';
}
function kf24_swatch_color( $key ) {
	$c = [ 'weiss' => '#f2f0eb', 'uni-grau' => '#8d8f8d', 'salbei' => '#a0aa92', 'taubenblau' => '#6c8594', 'fenix' => '#2d2d2e', 'schwarz' => '#1f1e1d', 'hochglanz' => '#f6f6f4', 'kaschmir' => '#d8cdbd' ];
	return $c[ $key ] ?? '#d9c8ad';
}

/** Colour used in small drawings (cart, production sheet) for a configured item. */
function kf24_cfg_color( $cfg, $product_id = 0 ) {
	if ( ! empty( $cfg['decor'] ) && ( $m = kf24_material( $cfg['decor'] ) ) ) {
		return $m['color'];
	}
	return kf24_swatch_color( $product_id ? get_post_meta( $product_id, '_kf24_swatch', true ) : '' );
}

/** Is this cart/order item a front (for Mindermenge/Länge)? */
function kf24_is_front_product( $product ) {
	if ( ! $product ) {
		return false;
	}
	if ( kf24_is_cfg_product( $product ) ) {
		return true;
	}
	$id     = $product->get_parent_id() ?: $product->get_id();
	$sample = get_term_by( 'slug', kf24_opt( 'sample_cat' ), 'product_cat' );
	if ( $sample && has_term( $sample->term_id, 'product_cat', $id ) ) {
		return false;
	}
	$front = get_term_by( 'slug', kf24_opt( 'front_cat' ), 'product_cat' );
	if ( ! $front ) {
		return false;
	}
	$terms = wp_get_post_terms( $id, 'product_cat', [ 'fields' => 'ids' ] );
	foreach ( $terms as $t ) {
		if ( (int) $t === (int) $front->term_id || term_is_ancestor_of( $front->term_id, $t, 'product_cat' ) ) {
			return true;
		}
	}
	return false;
}

function kf24_is_sample_product( $product ) {
	if ( ! $product ) {
		return false;
	}
	$id     = $product->get_parent_id() ?: $product->get_id();
	$sample = get_term_by( 'slug', kf24_opt( 'sample_cat' ), 'product_cat' );
	return $sample && has_term( $sample->term_id, 'product_cat', $id );
}

/** Longest side (mm) of a cart item: configurable → config, Metod → attributes (Rastermaß in mm). */
function kf24_item_dims( $item ) {
	if ( ! empty( $item['kf24'] ) ) {
		return [ (float) $item['kf24']['w'], (float) $item['kf24']['h'] ];
	}
	$w = $h = 0;
	foreach ( (array) ( $item['variation'] ?? [] ) as $k => $v ) {
		$n = (float) preg_replace( '/[^0-9.,]/', '', str_replace( ',', '.', $v ) );
		if ( stripos( $k, 'breite' ) !== false ) {
			$w = $n;
		} elseif ( stripos( $k, 'hoehe' ) !== false || stripos( $k, 'höhe' ) !== false ) {
			$h = $n;
		}
	}
	return [ $w, $h ];
}

/* ---------- Pricing & validation (mirrors assets/kf24-config.js) ---------- */

function kf24_hinge_recommendation( $len ) {
	if ( $len <= 900 ) {
		return 2;
	}
	if ( $len <= 1600 ) {
		return 3;
	}
	if ( $len <= 2200 ) {
		return 4;
	}
	return 5;
}

/** Sanitize raw config posted by the form. */
function kf24_sanitize_config( $raw, $pcfg ) {
	$s   = fn( $k, $d = '' ) => isset( $raw[ $k ] ) ? sanitize_text_field( wp_unslash( $raw[ $k ] ) ) : $d;
	$n   = fn( $k, $d = 0 ) => isset( $raw[ $k ] ) && $raw[ $k ] !== '' ? round( kf24_num( wp_unslash( $raw[ $k ] ) ), 1 ) : $d;
	$cfg = [
		'type'     => in_array( $s( 'type' ), [ 'tuer', 'schublade', 'klappe', 'blende', 'sonstig' ], true ) ? $s( 'type' ) : 'tuer',
		'label'    => mb_substr( $s( 'label' ), 0, 60 ),
		'decor'    => $s( 'decor' ),
		'w'        => $n( 'w' ),
		'h'        => $n( 'h' ),
		'grain'    => $s( 'grain' ) === 'quer' ? 'quer' : 'aufrecht',
		'hlast'    => $s( 'hlast' ) === 'ende' ? 'ende' : 'start',
		'hinge'    => in_array( $s( 'hinge' ), [ 'links', 'rechts', 'oben', 'keine' ], true ) ? $s( 'hinge' ) : 'keine',
		'hinges'   => min( 5, max( 0, (int) $n( 'hinges' ) ) ),
		'hpos'     => [],
		'body'     => $s( 'body' ) === '16' ? '16' : '19',
		'deliver'  => $s( 'deliver' ) === 'yes' ? 'yes' : 'no',
		'grip'     => in_array( $s( 'grip' ), [ '0', '1', '2' ], true ) ? $s( 'grip' ) : '0',
		'gpos'     => in_array( $s( 'gpos' ), [ 'mitte', 'mitte-v', 'oben-h', 'unten-h', 'oben-v', 'unten-v' ], true ) ? $s( 'gpos' ) : 'mitte',
		'ga'       => $n( 'ga' ),
		'gb'       => $n( 'gb' ),
		'gd'       => min( 2000, max( 0, $n( 'gd', 128 ) ) ),
		'mill'     => in_array( $s( 'mill' ), [ 'keine', 'oben', 'unten' ], true ) ? $s( 'mill' ) : 'keine',
		'seal'     => $s( 'seal' ) === 'yes' ? 'yes' : 'no',
		'note'     => mb_substr( sanitize_textarea_field( wp_unslash( $raw['note'] ?? '' ) ), 0, 400 ),
		'part'     => isset( $raw['part'] ) && function_exists( 'kf24_catalog_part' ) && kf24_catalog_part( sanitize_key( $raw['part'] ) ) ? sanitize_key( $raw['part'] ) : '',
	];
	if ( ! $pcfg['milling'] ) {
		$cfg['mill'] = 'keine';
	}
	if ( $cfg['hinge'] === 'keine' ) {
		$cfg['hinges'] = 0;
	}
	if ( $cfg['type'] === 'blende' ) {
		$cfg['grain'] = 'laengs'; // Blenden und Sockel: Maserung immer längs
	}
	if ( in_array( $cfg['type'], [ 'schublade', 'klappe' ], true ) ) {
		$cfg['gpos'] = 'mitte'; // Schubladen und Klappen: Griff immer horizontal mittig, nur Höhe (A) frei
	}
	if ( $cfg['part'] ) { // catalog part only while the size is unchanged
		$pt = kf24_catalog_part( $cfg['part'] );
		if ( ! $pt || abs( $pt['w'] - $cfg['w'] ) > 0.5 || abs( $pt['h'] - $cfg['h'] ) > 0.5 ) {
			$cfg['part'] = '';
		}
	}
	for ( $i = 0; $i < $cfg['hinges']; $i++ ) {
		$cfg['hpos'][] = isset( $raw['hpos'][ $i ] ) ? round( kf24_num( wp_unslash( $raw['hpos'][ $i ] ) ), 1 ) : 0;
	}
	// The last hinge may be measured from the bottom (or from the right for flaps). Internally all positions are from the top/left.
	if ( $cfg['hlast'] === 'ende' && $cfg['hinges'] >= 2 ) {
		$len                 = $cfg['hinge'] === 'oben' ? $cfg['w'] : $cfg['h'];
		$cfg['hlast_val']    = end( $cfg['hpos'] );
		$cfg['hpos'][ $cfg['hinges'] - 1 ] = round( $len - $cfg['hlast_val'], 1 );
	} else {
		$cfg['hlast'] = 'start';
	}
	if ( $cfg['grip'] === '0' ) {
		$cfg['ga'] = $cfg['gb'] = 0;
	}
	return $cfg;
}

/** Returns list of blocking errors (strings). */
function kf24_validate_config( $cfg, $pcfg ) {
	$e     = [];
	$min   = (float) kf24_opt( 'min_dim' );
	$long  = (float) kf24_opt( 'max_long' );
	$short = (float) kf24_opt( 'max_short' );
	if ( ! $cfg['decor'] || ! in_array( $cfg['decor'], $pcfg['options'], true ) ) {
		$e[] = 'Bitte wählen Sie ' . $pcfg['field'] . '.';
	}
	if ( $cfg['w'] < $min || $cfg['h'] < $min ) {
		$e[] = sprintf( 'Breite und Höhe müssen mindestens %s mm betragen.', $min );
	}
	if ( max( $cfg['w'], $cfg['h'] ) > $long || min( $cfg['w'], $cfg['h'] ) > $short ) {
		$e[] = sprintf( 'Online bestellbar bis %s × %s mm. Größere Teile fertigen wir auf Anfrage – bitte kontaktieren Sie uns.', wc_format_localized_decimal( $long ), wc_format_localized_decimal( $short ) );
	}
	if ( $cfg['grip'] === '2' && $cfg['gd'] < 16 ) {
		$e[] = 'Bitte geben Sie den Lochabstand des Griffs an (Mitte Loch bis Mitte Loch).';
	}
	if ( $cfg['type'] === 'sonstig' && $cfg['label'] === '' ) {
		$e[] = 'Bitte geben Sie dem sonstigen Bauteil eine Bezeichnung.';
	}
	$len = $cfg['hinge'] === 'oben' ? $cfg['w'] : $cfg['h'];
	foreach ( $cfg['hpos'] as $p ) {
		if ( $p < 50 || $p > $len - 50 ) {
			$e[] = 'Topfbandpositionen müssen mindestens 50 mm vom Rand entfernt sein.';
			break;
		}
	}
	$sorted = $cfg['hpos'];
	sort( $sorted );
	for ( $i = 1; $i < count( $sorted ); $i++ ) {
		if ( $sorted[ $i ] - $sorted[ $i - 1 ] < 80 ) {
			$e[] = 'Topfbänder müssen mindestens 80 mm Abstand zueinander haben.';
			break;
		}
	}
	if ( $cfg['grip'] !== '0' ) {
		foreach ( kf24_grip_holes( $cfg ) as $hole ) {
			if ( $hole[0] < 15 || $hole[0] > $cfg['w'] - 15 || $hole[1] < 15 || $hole[1] > $cfg['h'] - 15 ) {
				$e[] = 'Die Griffbohrungen liegen zu nah am Rand oder außerhalb der Front.';
				break;
			}
		}
	}
	return $e;
}

/** Hinge cup centres in front coordinates (x,y from top-left). */
function kf24_hinge_points( $cfg ) {
	$pts = [];
	$edge = 22.5;
	foreach ( $cfg['hpos'] as $p ) {
		if ( $cfg['hinge'] === 'links' ) {
			$pts[] = [ $edge, $p ];
		} elseif ( $cfg['hinge'] === 'rechts' ) {
			$pts[] = [ $cfg['w'] - $edge, $p ];
		} elseif ( $cfg['hinge'] === 'oben' ) {
			$pts[] = [ $p, $edge ];
		}
	}
	return $pts;
}

/** Handle holes in front coordinates. a = from top/bottom edge, b = from opening edge, d = Lochabstand. */
function kf24_grip_holes( $cfg ) {
	$n = (int) $cfg['grip'];
	if ( ! $n ) {
		return [];
	}
	$w = $cfg['w'];
	$h = $cfg['h'];
	$a = $cfg['ga'];
	$b = $cfg['gb'];
	$d = $n === 2 ? $cfg['gd'] : 0;
	if ( $cfg['gpos'] === 'mitte' ) {
		$y = $a ?: $h / 2;
		return $n === 2 ? [ [ $w / 2 - $d / 2, $y ], [ $w / 2 + $d / 2, $y ] ] : [ [ $w / 2, $y ] ];
	}
	if ( $cfg['gpos'] === 'mitte-v' ) { // vertical, centred in height, at the opening edge
		$x = $cfg['hinge'] !== 'rechts' ? $w - $b : $b;
		$y = $a ?: $h / 2;
		return $n === 2 ? [ [ $x, $y - $d / 2 ], [ $x, $y + $d / 2 ] ] : [ [ $x, $y ] ];
	}
	$top    = str_starts_with( $cfg['gpos'], 'oben' );
	$vert   = str_ends_with( $cfg['gpos'], '-v' );
	$fromR  = $cfg['hinge'] !== 'rechts'; // opening side is opposite of the hinge side
	$x      = $fromR ? $w - $b : $b;
	$y      = $top ? $a : $h - $a;
	$holes  = [ [ $x, $y ] ];
	if ( $n === 2 ) {
		if ( $vert ) {
			$holes[] = [ $x, $top ? $y + $d : $y - $d ];
		} else {
			$holes[] = [ $fromR ? $x - $d : $x + $d, $y ];
		}
	}
	return $holes;
}

/**
 * Base price of one part without extras.
 * Formula: max( Mindestpreis, Fläche × m²-Preis + Umfang × Kantenpreis + Stückpauschale ).
 * A fixed price for a catalog part (Metod/Faktum) replaces the formula when the size matches the catalog.
 */
function kf24_base_price( $cfg, $pcfg, &$how = null ) {
	$area = max( $cfg['w'] * $cfg['h'] / 1e6, (float) kf24_opt( 'min_area' ) );
	if ( ! empty( $cfg['part'] ) && isset( $pcfg['overrides'][ $cfg['part'] ] ) && $pcfg['overrides'][ $cfg['part'] ] > 0 ) {
		$part = kf24_catalog_part( $cfg['part'] );
		if ( $part && abs( $part['w'] - $cfg['w'] ) < 0.6 && abs( $part['h'] - $cfg['h'] ) < 0.6 ) {
			$how = 'fest';
			return [ 'area' => $area, 'base' => round( $pcfg['overrides'][ $cfg['part'] ], 2 ) ];
		}
	}
	$lfm  = 2 * ( $cfg['w'] + $cfg['h'] ) / 1000;
	$calc = $area * $pcfg['m2'] + $lfm * ( $pcfg['edge'] ?? 0 ) + ( $pcfg['piece'] ?? 0 );
	$how  = ( $pcfg['minprice'] ?? 0 ) > $calc ? 'mindest' : 'formel';
	return [ 'area' => $area, 'base' => round( max( $calc, (float) ( $pcfg['minprice'] ?? 0 ) ), 2 ) ];
}

/** Unit price (gross) and breakdown. */
function kf24_price( $cfg, $pcfg ) {
	$b        = kf24_base_price( $cfg, $pcfg, $how );
	$area     = $b['area'];
	$base     = $b['base'];
	$mill     = ( $pcfg['milling'] && $cfg['mill'] !== 'keine' ) ? (float) kf24_opt( 'milling_price' ) : 0;
	$hinges   = $cfg['deliver'] === 'yes' ? $cfg['hinges'] * (float) kf24_opt( 'hinge_price' ) : 0;
	$seal     = $cfg['seal'] === 'yes' ? ( $cfg['w'] / 1000 ) * (float) kf24_opt( 'seal_price_lfm' ) : 0;
	$unit     = round( $base + $mill + $hinges + $seal, 2 );
	return [
		'area'   => $area,
		'base'   => $base,
		'how'    => $how,
		'mill'   => $mill,
		'hinges' => round( $hinges, 2 ),
		'seal'   => round( $seal, 2 ),
		'unit'   => $unit,
		'weight' => round( $cfg['w'] * $cfg['h'] / 1e6 * $pcfg['kgm2'], 2 ),
	];
}

/** Human readable spec lines for cart, order and production sheet. */
function kf24_spec_lines( $cfg, $pcfg = null ) {
	$types = [ 'tuer' => 'Tür', 'schublade' => 'Schubladenfront', 'klappe' => 'Klappe', 'blende' => 'Blende/Sockel', 'sonstig' => 'Sonstiges Bauteil' ];
	$gpos  = [ 'mitte' => 'mittig horizontal', 'mitte-v' => 'mittig vertikal', 'oben-h' => 'oben horizontal', 'unten-h' => 'unten horizontal', 'oben-v' => 'oben vertikal', 'unten-v' => 'unten vertikal' ];
	$l     = [];
	if ( $cfg['label'] ) {
		$l['Bezeichnung'] = $cfg['label'];
	}
	if ( ! empty( $cfg['part'] ) && function_exists( 'kf24_catalog_part' ) && ( $pt = kf24_catalog_part( $cfg['part'] ) ) ) {
		$l['Systemteil'] = 'IKEA ' . ucfirst( $pt['system'] ) . ': ' . $pt['name'];
	}
	$l['Frontart']   = $types[ $cfg['type'] ] ?? $cfg['type'];
	$l['Dekor/Holz'] = $cfg['decor'];
	$l['Maß (B × H)'] = sprintf( '%s × %s mm', wc_format_localized_decimal( $cfg['w'] ), wc_format_localized_decimal( $cfg['h'] ) );
	$l['Maserung']   = $cfg['grain'] === 'laengs' ? 'längs' : ( $cfg['grain'] === 'quer' ? 'quer (90° gedreht)' : 'aufrecht' );
	if ( $cfg['hinge'] !== 'keine' && $cfg['hinges'] ) {
		$l['Anschlag']     = $cfg['hinge'] . ( ( $cfg['twin'] ?? '' ) === 'zwilling' ? ', Zwillingsanschlag' : '' ) . ', Korpus ' . $cfg['body'] . ' mm';
		$ref               = $cfg['hinge'] === 'oben' ? 'von links' : 'von oben';
		$pos               = $cfg['hpos'];
		$txt               = '';
		if ( ( $cfg['hlast'] ?? '' ) === 'ende' && count( $pos ) >= 2 ) {
			array_pop( $pos );
			$txt = ', letzte ' . wc_format_localized_decimal( $cfg['hlast_val'] ) . ' mm ' . ( $cfg['hinge'] === 'oben' ? 'von rechts' : 'von unten' );
		}
		$l['Topfbänder']   = $cfg['hinges'] . ' Bohrungen Ø35 bei ' . implode( ' / ', array_map( 'wc_format_localized_decimal', $pos ) ) . ' mm ' . $ref . $txt;
		$l['Topfbänder mitliefern'] = $cfg['deliver'] === 'yes' ? 'ja (' . $cfg['hinges'] . ' Stück)' : 'nein';
	} else {
		$l['Anschlag'] = 'keine Topfbandbohrung';
	}
	if ( $cfg['grip'] !== '0' ) {
		$g = $cfg['grip'] === '1' ? '1 Bohrung (Knopf)' : '2 Bohrungen (Bügel), Lochabstand ' . wc_format_localized_decimal( $cfg['gd'] ) . ' mm';
		$l['Griffbohrung'] = $g . ', ' . ( $gpos[ $cfg['gpos'] ] ?? '' ) . ( $cfg['gpos'] === 'mitte' ? ', ' . wc_format_localized_decimal( $cfg['ga'] ?: $cfg['h'] / 2 ) . ' mm von oben' : ( $cfg['gpos'] === 'mitte-v' ? ', Mitte ' . wc_format_localized_decimal( $cfg['ga'] ?: $cfg['h'] / 2 ) . ' mm von oben, b = ' . wc_format_localized_decimal( $cfg['gb'] ) . ' mm' : ', a = ' . wc_format_localized_decimal( $cfg['ga'] ) . ' mm, b = ' . wc_format_localized_decimal( $cfg['gb'] ) . ' mm' ) );
	}
	if ( $cfg['mill'] !== 'keine' ) {
		$l['Grifffräsung'] = $cfg['mill'];
	}
	if ( $cfg['seal'] === 'yes' ) {
		$l['Aufsteckdichtung'] = 'ja';
	}
	if ( $cfg['note'] ) {
		$l['Hinweis'] = $cfg['note'];
	}
	return $l;
}

/** Technical drawing as SVG (used in admin, emails and as fallback). */
function kf24_svg( $cfg, $fill = '#d9c8ad', $maxw = 260, $maxh = 300 ) {
	$w = max( 1, (float) $cfg['w'] );
	$h = max( 1, (float) $cfg['h'] );
	$s = min( ( $maxw - 70 ) / $w, ( $maxh - 60 ) / $h );
	$fw = $w * $s;
	$fh = $h * $s;
	$ox = 50;
	$oy = 20;
	$o  = sprintf( '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" font-family="Arial,sans-serif" font-size="11">', $maxw, $maxh, $maxw, $maxh );
	$o .= sprintf( '<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="%s" stroke="#3b2a1e" stroke-width="1.5"/>', $ox, $oy, $fw, $fh, esc_attr( $fill ) );
	// grain
	for ( $i = 1; $i < 7; $i++ ) {
		if ( $cfg['grain'] === 'quer' || ( $cfg['grain'] === 'laengs' && $w > $h ) ) {
			$y = $oy + $fh * $i / 7;
			$o .= sprintf( '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#3b2a1e" stroke-opacity=".12"/>', $ox + 4, $y, $ox + $fw - 4, $y );
		} else {
			$x = $ox + $fw * $i / 7;
			$o .= sprintf( '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#3b2a1e" stroke-opacity=".12"/>', $x, $oy + 4, $x, $oy + $fh - 4 );
		}
	}
	if ( $cfg['mill'] !== 'keine' ) {
		$y = $cfg['mill'] === 'oben' ? $oy : $oy + $fh - 6;
		$o .= sprintf( '<rect x="%.1f" y="%.1f" width="%.1f" height="6" fill="#3b2a1e" fill-opacity=".55"/>', $ox, $y, $fw );
	}
	foreach ( kf24_hinge_points( $cfg ) as $p ) {
		$o .= sprintf( '<circle cx="%.1f" cy="%.1f" r="%.1f" fill="#fff" stroke="#0d5a5f" stroke-width="1.5"/>', $ox + $p[0] * $s, $oy + $p[1] * $s, max( 3, 17.5 * $s ) );
	}
	foreach ( kf24_grip_holes( $cfg ) as $p ) {
		$o .= sprintf( '<circle cx="%.1f" cy="%.1f" r="3" fill="#b3261e"/>', $ox + $p[0] * $s, $oy + $p[1] * $s );
	}
	$o .= sprintf( '<text x="%.1f" y="%.1f" text-anchor="middle">%s mm</text>', $ox + $fw / 2, $oy + $fh + 18, esc_html( wc_format_localized_decimal( $w ) ) );
	$o .= sprintf( '<text x="%.1f" y="%.1f" text-anchor="middle" transform="rotate(-90 %.1f %.1f)">%s mm</text>', $ox - 12, $oy + $fh / 2, $ox - 12, $oy + $fh / 2, esc_html( wc_format_localized_decimal( $h ) ) );
	return $o . '</svg>';
}

/** Weight of one unit of a cart item (kg), independent of when WooCommerce loaded the product object. */
function kf24_item_weight( $item ) {
	if ( ! empty( $item['kf24'] ) ) {
		$pcfg = kf24_product_cfg( $item['product_id'] );
		return round( $item['kf24']['w'] * $item['kf24']['h'] / 1e6 * $pcfg['kgm2'], 3 );
	}
	$w = (float) $item['data']->get_weight();
	if ( ! $w && kf24_is_front_product( $item['data'] ) ) {
		[ $a, $b ] = kf24_item_dims( $item );
		$w = $a * $b / 1e6 * (float) kf24_opt( 'kgm2_default' );
	}
	return $w;
}
