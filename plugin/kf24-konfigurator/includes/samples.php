<?php
/**
 * Muster with colour / wood choice: the customer picks one or more decors before the sample goes into the cart.
 * The choice list is the union of the materials of all fronts that point to this sample.
 */
defined( 'ABSPATH' ) || exit;

/** ['field' => 'Holzart', 'options' => [...]] or null when the sample has no choice. */
function kf24_sample_choices( $product ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product || ! kf24_is_sample_product( $product ) ) {
		return null;
	}
	static $cache = [];
	$id = $product->get_id();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}
	$field = '';
	$opts  = [];
	foreach ( kf24_all_cfg_products() as $c ) {
		if ( $c['sample'] === $product->get_slug() ) {
			$field = $field ?: $c['field'];
			foreach ( $c['options'] as $o ) {
				$opts[ $o ] = true;
			}
		}
	}
	if ( count( $opts ) < 2 ) {
		return $cache[ $id ] = null; // nothing to choose
	}
	$opts  = array_keys( $opts );
	$first = kf24_material( $opts[0] );
	$names = [ 'Furnier' => [ 'Holzart', 'Holzarten' ], 'Massivholz' => [ 'Holzart', 'Holzarten' ], 'Lack RAL' => [ 'Farbe', 'Farben' ], 'Fenix' => [ 'Farbe', 'Farben' ] ];
	$n     = $names[ $first['group'] ?? '' ] ?? [ 'Dekor', 'Dekore' ];
	return $cache[ $id ] = [ 'field' => $n[0], 'plural' => $n[1], 'options' => $opts ];
}

/** Where a "Muster bestellen" link should go. */
function kf24_sample_link( $sid ) {
	return kf24_sample_choices( $sid ) ? get_permalink( $sid ) . '#kf24-sample-pick' : add_query_arg( 'add-to-cart', $sid, wc_get_cart_url() );
}

/** Tile picker (checkboxes) used on the Muster page and on the sample product page. */
function kf24_sample_picker( $product, $choices ) {
	$big = count( $choices['options'] ) > 12;
	$o   = '<div class="kf24-sp" id="kf24-sample-pick"><p class="kf24-sp-h"><b>' . esc_html( $choices['field'] ) . ' wählen</b> <span class="kf24-hint">Mehrere möglich – jedes Muster kostet ' . wp_kses_post( wc_price( $product->get_price() ) ) . '.</span></p>';
	if ( $big ) {
		$o .= '<input type="search" class="kf24-filter kf24-sp-q" placeholder="Suchen …" aria-label="' . esc_attr( $choices['field'] ) . ' suchen">';
	}
	$o .= '<div class="kf24-swatches' . ( $big ? ' is-big' : '' ) . '" role="group" aria-label="' . esc_attr( $choices['field'] ) . '">';
	foreach ( $choices['options'] as $opt ) {
		$l  = kf24_material_look( $opt );
		$o .= '<label class="kf24-tile" title="' . esc_attr( $opt ) . '"><input type="checkbox" name="kf24_sample_decor[]" value="' . esc_attr( $opt ) . '"><span class="kf24-tile-img" style="' . esc_attr( ( $l['img'] ? 'background-image:url(' . $l['img'] . ');' : '' ) . 'background-color:' . $l['color'] ) . '"></span><span class="kf24-tile-name">' . esc_html( $opt ) . '</span></label>';
	}
	return $o . '</div><p class="kf24-sp-count" aria-live="polite">Noch nichts gewählt.</p></div>';
}

function kf24_sample_picker_js() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return "<script>(function(){function upd(f){var n=f.querySelectorAll('input[name=\"kf24_sample_decor[]\"]:checked').length,c=f.querySelector('.kf24-sp-count'),b=f.querySelector('button[type=submit],.single_add_to_cart_button');if(c)c.textContent=n?n+' ausgewählt':'Noch nichts gewählt.';if(b){b.disabled=!n;b.textContent=n>1?n+' Muster in den Warenkorb':'In den Warenkorb';}}
document.querySelectorAll('.kf24-sp').forEach(function(sp){var f=sp.closest('form');if(!f)return;f.addEventListener('change',function(){upd(f);});upd(f);var q=sp.querySelector('.kf24-sp-q');if(q)q.addEventListener('input',function(){var v=q.value.toLowerCase();sp.querySelectorAll('.kf24-tile').forEach(function(t){t.hidden=v&&t.title.toLowerCase().indexOf(v)<0;});});});
document.querySelectorAll('[data-kf24-open]').forEach(function(b){b.addEventListener('click',function(){var d=document.getElementById(b.dataset.kf24Open);if(d&&d.showModal){d.showModal();}});});
document.querySelectorAll('.kf24-dlg [data-close]').forEach(function(b){b.addEventListener('click',function(){b.closest('dialog').close();});});})();</script>";
}

/** [kf24_muster] – sample grid; samples with a choice open a picker first. */
add_shortcode( 'kf24_muster', function () {
	wp_enqueue_style( 'kf24' );
	$q = wc_get_products( [ 'category' => [ kf24_opt( 'sample_cat' ) ], 'limit' => 100, 'status' => 'publish', 'orderby' => 'price', 'order' => 'ASC' ] );
	if ( ! $q ) {
		return '<p>Derzeit sind keine Muster verfügbar.</p>';
	}
	ob_start();
	if ( function_exists( 'wc_print_notices' ) && WC()->session ) {
		wc_print_notices();
	}
	echo '<div class="kf24-samples">';
	foreach ( $q as $p ) {
		$lk   = kf24_sample_look( $p );
		$bg   = $lk['img'] ? 'background-image:url(' . $lk['img'] . ')' : 'background:' . $lk['color'];
		$ch   = kf24_sample_choices( $p );
		$name = str_replace( ' – Muster', '', $p->get_name() );
		$pic  = $ch && ! $p->get_image_id() ? kf24_collage_from_names( array_slice( $ch['options'], 0, 4 ) ) : '';
		echo '<div class="kf24-sample"><a class="kf24-sample-pic' . ( $pic ? ' is-collage' : '' ) . '" href="' . esc_url( $p->get_permalink() ) . '" style="' . esc_attr( $pic ? '' : $bg ) . '" aria-label="' . esc_attr( $p->get_name() ) . '">' . $pic . '</a><div class="kf24-sample-b"><h3>' . esc_html( $name ) . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput -- collage escaped inside
		if ( $ch ) {
			echo '<p class="kf24-hint">' . count( $ch['options'] ) . ' ' . esc_html( $ch['plural'] ) . ' zur Auswahl</p>';
		}
		echo '<div class="kf24-sample-r"><span class="price">' . wp_kses_post( wc_price( $p->get_price() ) ) . '</span>';
		if ( $ch ) {
			echo '<button type="button" class="button alt" data-kf24-open="kf24-dlg-' . (int) $p->get_id() . '">' . esc_html( $ch['field'] ) . ' wählen</button>';
		} else {
			echo '<a href="' . esc_url( $p->add_to_cart_url() ) . '" data-quantity="1" data-product_id="' . (int) $p->get_id() . '" class="button add_to_cart_button ajax_add_to_cart">In den Warenkorb</a>';
		}
		echo '</div></div></div>';
		if ( $ch ) {
			echo '<dialog class="kf24-dlg" id="kf24-dlg-' . (int) $p->get_id() . '" aria-label="' . esc_attr( $name ) . '"><form method="post" action="' . esc_url( get_permalink() ) . '"><div class="kf24-dlg-h"><h3>' . esc_html( $name ) . '</h3><button type="button" class="kf24-x" data-close aria-label="Schließen">×</button></div>';
			echo kf24_sample_picker( $p, $ch ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
			echo '<input type="hidden" name="add-to-cart" value="' . (int) $p->get_id() . '"><input type="hidden" name="quantity" value="1"><div class="kf24-dlg-f"><button type="button" class="button kf24-ghost" data-close>Abbrechen</button><button type="submit" class="button alt" disabled>In den Warenkorb</button></div></form></dialog>';
		}
	}
	echo '</div><p class="kf24-hint">Musterversand innerhalb Deutschlands ' . esc_html( wp_strip_all_tags( wc_price( kf24_opt( 'ship_sample_de' ) ) ) ) . ', in die Schweiz ' . esc_html( wp_strip_all_tags( wc_price( kf24_opt( 'ship_sample_ch' ) ) ) ) . '. Muster sind Standardware und können widerrufen werden.</p>';
	echo kf24_sample_picker_js(); // phpcs:ignore WordPress.Security.EscapeOutput -- static script
	return ob_get_clean();
} );

/* Sample product page: picker inside the add-to-cart form. */
add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	$ch = $product ? kf24_sample_choices( $product ) : null;
	if ( $ch ) {
		echo kf24_sample_picker( $product, $ch ) . kf24_sample_picker_js(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
	}
} );
add_filter( 'woocommerce_quantity_input_args', function ( $args, $product ) {
	if ( kf24_sample_choices( $product ) ) {
		$args['min_value'] = $args['max_value'] = 1;
	}
	return $args;
}, 10, 2 );

/* Shop loops: "Farbe wählen" instead of a direct add-to-cart. */
add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	$ch = kf24_sample_choices( $product );
	return $ch ? $ch['field'] . ' wählen' : $text;
}, 20, 2 );
add_filter( 'woocommerce_product_add_to_cart_url', function ( $url, $product ) {
	return kf24_sample_choices( $product ) ? $product->get_permalink() . '#kf24-sample-pick' : $url;
}, 20, 2 );
add_filter( 'woocommerce_product_supports', function ( $supports, $feature, $product ) {
	return $feature === 'ajax_add_to_cart' && kf24_sample_choices( $product ) ? false : $supports;
}, 10, 3 );

/* Validation + multiple choices → one cart line per decor. Add-to-cart forms carry no nonce in WooCommerce; values are whitelisted. */
// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
$GLOBALS['kf24_sample_queue']  = [];
$GLOBALS['kf24_sample_forced'] = null;

function kf24_posted_sample_decors( $product_id ) {
	$ch = kf24_sample_choices( $product_id );
	if ( ! $ch ) {
		return null;
	}
	$raw = isset( $_REQUEST['kf24_sample_decor'] ) ? (array) wp_unslash( $_REQUEST['kf24_sample_decor'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- whitelisted below
	$sel = array_values( array_intersect( array_map( 'sanitize_text_field', $raw ), $ch['options'] ) );
	return array_slice( array_unique( $sel ), 0, 12 );
}

add_filter( 'woocommerce_add_to_cart_validation', function ( $ok, $product_id ) {
	$sel = kf24_posted_sample_decors( $product_id );
	if ( $sel === null ) {
		return $ok;
	}
	if ( ! $sel ) {
		$ch = kf24_sample_choices( $product_id );
		wc_add_notice( 'Bitte wählen Sie beim Muster die ' . $ch['field'] . ' aus.', 'error' );
		return false;
	}
	$GLOBALS['kf24_sample_queue'] = array_slice( $sel, 1 );
	return $ok;
}, 20, 2 );

add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	if ( $GLOBALS['kf24_sample_forced'] !== null ) {
		$data['kf24_sample_decor'] = $GLOBALS['kf24_sample_forced'];
		return $data;
	}
	$sel = kf24_posted_sample_decors( $product_id );
	if ( $sel ) {
		$data['kf24_sample_decor'] = $sel[0];
	}
	return $data;
}, 10, 2 );

add_action( 'woocommerce_add_to_cart', function ( $key, $product_id ) {
	if ( $GLOBALS['kf24_sample_forced'] !== null || ! $GLOBALS['kf24_sample_queue'] ) {
		return;
	}
	$queue                        = $GLOBALS['kf24_sample_queue'];
	$GLOBALS['kf24_sample_queue'] = [];
	foreach ( $queue as $d ) {
		$GLOBALS['kf24_sample_forced'] = $d;
		WC()->cart->add_to_cart( $product_id, 1 );
	}
	$GLOBALS['kf24_sample_forced'] = null;
	wc_add_notice( ( count( $queue ) + 1 ) . ' Muster liegen im Warenkorb.', 'success' );
}, 10, 2 );
// phpcs:enable

add_filter( 'woocommerce_get_item_data', function ( $data, $item ) {
	if ( ! empty( $item['kf24_sample_decor'] ) ) {
		$ch     = kf24_sample_choices( $item['product_id'] );
		$data[] = [ 'key' => $ch ? $ch['field'] : 'Ausführung', 'value' => esc_html( $item['kf24_sample_decor'] ) ];
	}
	return $data;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $line, $key, $values ) {
	if ( ! empty( $values['kf24_sample_decor'] ) ) {
		$ch = kf24_sample_choices( $values['product_id'] );
		$line->add_meta_data( $ch ? $ch['field'] : 'Ausführung', $values['kf24_sample_decor'], true );
	}
}, 10, 3 );

/* Cart thumbnail shows the chosen decor. */
add_filter( 'woocommerce_cart_item_thumbnail', function ( $thumb, $item ) {
	if ( empty( $item['kf24_sample_decor'] ) ) {
		return $thumb;
	}
	$l = kf24_material_look( $item['kf24_sample_decor'] );
	return '<span class="kf24-thumb kf24-thumb-sample" style="' . esc_attr( ( $l['img'] ? 'background-image:url(' . $l['img'] . ');' : '' ) . 'background-color:' . $l['color'] ) . '" role="img" aria-label="' . esc_attr( $item['kf24_sample_decor'] ) . '"></span>';
}, 20, 2 );
