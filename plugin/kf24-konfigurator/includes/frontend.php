<?php
defined( 'ABSPATH' ) || exit;

/** "ab 306,00 € je m²" for configurable products everywhere. */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( kf24_is_cfg_product( $product ) && is_a( $product, 'WC_Product_Simple' ) ) {
		$m2 = kf24_num( get_post_meta( $product->get_id(), '_kf24_m2', true ) );
		return '<span class="kf24-from">ab</span> ' . wc_price( $m2 ) . ' <span class="kf24-unit">je m²</span>';
	}
	return $html;
}, 20, 2 );

/** Loop button: "Konfigurieren" instead of "In den Warenkorb". */
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
	if ( kf24_is_cfg_product( $product ) ) {
		return sprintf( '<a href="%s#kf24-konfigurator" class="button kf24-loop-btn">Konfigurieren</a>', esc_url( $product->get_permalink() ) );
	}
	return $html;
}, 20, 2 );

add_action( 'woocommerce_before_single_product', function () {
	global $product;
	if ( ! $product || ! kf24_is_cfg_product( $product ) ) {
		return;
	}
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	add_action( 'woocommerce_single_product_summary', 'kf24_summary_cta', 30 );
	add_action( 'woocommerce_after_single_product_summary', 'kf24_render_configurator', 5 );
} );

function kf24_summary_cta() {
	global $product;
	$c = kf24_product_cfg( $product->get_id() );
	echo '<div class="kf24-cta">';
	echo '<p class="kf24-cta-meta"><span>Lieferzeit: ' . esc_html( $c['lz'] ) . ' ab Zahlungseingang</span><span>Frontstärke ' . (int) $c['thickness'] . ' mm</span><span>' . count( $c['options'] ) . ' ' . esc_html( $c['field'] ) . '-Varianten</span></p>';
	echo '<a class="button alt kf24-scroll" href="#kf24-konfigurator">Jetzt konfigurieren</a>';
	$sid = kf24_sample_product_id( $c['sample'] );
	if ( $sid ) {
		echo ' <a class="button kf24-sample-btn" href="' . esc_url( add_query_arg( 'add-to-cart', $sid, wc_get_cart_url() ) ) . '">Muster bestellen (' . wp_kses_post( wc_price( wc_get_product( $sid )->get_price() ) ) . ')</a>';
	}
	echo '</div>';
}

function kf24_render_configurator() {
	global $product;
	$c   = kf24_product_cfg( $product->get_id() );
	$big = count( $c['options'] ) > 12;
	wp_enqueue_style( 'kf24' );
	wp_enqueue_script( 'kf24-config' );
	wp_localize_script( 'kf24-config', 'KF24CFG', [
		'product'  => $c,
		'tex'      => kf24_product_look( $c['id'] )['img'],
		'color'    => kf24_product_look( $c['id'] )['color'],
		'mats'     => array_combine( $c['options'], array_map( 'kf24_material_look', $c['options'] ) ) ?: new stdClass(),
		'settings' => [
			'hinge_price'    => (float) kf24_opt( 'hinge_price' ),
			'seal_price_lfm' => (float) kf24_opt( 'seal_price_lfm' ),
			'milling_price'  => (float) kf24_opt( 'milling_price' ),
			'min_area'       => (float) kf24_opt( 'min_area' ),
			'min_dim'        => (float) kf24_opt( 'min_dim' ),
			'max_long'       => (float) kf24_opt( 'max_long' ),
			'max_short'      => (float) kf24_opt( 'max_short' ),
			'seal_note'      => (string) kf24_opt( 'seal_note' ),
			'phone'          => kf24_opt( 'phone' ),
		],
	] );
	$f = fn( $s ) => esc_attr( $s );
	?>
	<section id="kf24-konfigurator" class="kf24">
	<form class="kf24-form cart" method="post" action="<?php echo esc_url( $product->get_permalink() ); ?>" novalidate>
	<div class="kf24-grid">
	<div class="kf24-steps">
		<fieldset class="kf24-step">
			<legend><span class="kf24-n">1</span> Was möchten Sie bestellen?</legend>
			<div class="kf24-types" role="radiogroup">
				<?php foreach ( [ 'tuer' => [ 'Tür', 'mit Topfbändern' ], 'schublade' => [ 'Schubladenfront', 'ohne Topfbänder' ], 'klappe' => [ 'Klappe', 'Anschlag oben' ], 'blende' => [ 'Blende / Sockel', 'ohne Bohrungen' ] ] as $k => $t ) : ?>
					<label class="kf24-type"><input type="radio" name="kf24[type]" value="<?php echo $f( $k ); ?>" <?php checked( $k, 'tuer' ); ?>><span class="kf24-type-ico" data-type="<?php echo $f( $k ); ?>"></span><b><?php echo esc_html( $t[0] ); ?></b><small><?php echo esc_html( $t[1] ); ?></small></label>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<fieldset class="kf24-step">
			<legend><span class="kf24-n">2</span> <?php echo esc_html( $c['field'] ); ?> <output class="kf24-decor-name" id="kf24-decor-name">bitte wählen</output></legend>
			<?php if ( $big ) : ?>
				<input type="search" class="kf24-filter" placeholder="Suchen, z. B. <?php echo esc_attr( preg_replace( '/^(\S+\s?\S*).*/u', '$1', $c['options'][0] ?? '' ) ); ?>" aria-label="<?php echo $f( $c['field'] ); ?> durchsuchen">
			<?php endif; ?>
			<div class="kf24-swatches<?php echo $big ? ' is-big' : ''; ?>" role="radiogroup" aria-label="<?php echo $f( $c['field'] ); ?>">
				<?php foreach ( $c['options'] as $o ) : $look = kf24_material_look( $o ); ?>
					<label class="kf24-tile" title="<?php echo $f( $o ); ?>"><input type="radio" name="kf24[decor]" value="<?php echo $f( $o ); ?>" required><span class="kf24-tile-img" style="<?php echo $look['img'] ? 'background-image:url(' . esc_url( $look['img'] ) . ');' : ''; ?>background-color:<?php echo esc_attr( $look['color'] ); ?>"></span><span class="kf24-tile-name"><?php echo esc_html( $o ); ?></span></label>
				<?php endforeach; ?>
			</div>
			<p class="kf24-filter-empty" hidden>Nichts gefunden. Anderer Suchbegriff?</p>
			<p class="kf24-hint">Bildschirmfarben weichen vom Original ab. <?php $sid = kf24_sample_product_id( $c['sample'] ); echo $sid ? '<a href="' . esc_url( kf24_sample_link( $sid ) ) . '">Muster bestellen</a>.' : 'Rufen Sie uns an: ' . esc_html( kf24_opt( 'phone' ) ) . '.'; ?></p>
		</fieldset>

		<fieldset class="kf24-step">
			<legend><span class="kf24-n">3</span> Maße</legend>
			<div class="kf24-row3">
				<label>Breite <span>mm</span><input type="number" inputmode="decimal" name="kf24[w]" id="kf24-w" min="1" step="0.5" value="597" required></label>
				<label>Höhe <span>mm</span><input type="number" inputmode="decimal" name="kf24[h]" id="kf24-h" min="1" step="0.5" value="797" required></label>
				<label>Maserung
					<select name="kf24[grain]" id="kf24-grain"><option value="aufrecht">aufrecht</option><option value="quer">quer (90° gedreht)</option></select>
				</label>
			</div>
			<label class="kf24-block">Eigene Bezeichnung <small>(optional, z. B. „Spüle links“)</small><input type="text" name="kf24[label]" maxlength="60" list="kf24-terms"></label><datalist id="kf24-terms"><?php foreach ( kf24_front_terms_flat() as $t ) { echo '<option value="' . esc_attr( $t['l'] ) . '">'; } ?></datalist>
			<p class="kf24-hint">Messen Sie die alte Front von Kante zu Kante. Für IKEA Metod: Rastermaß minus 3 mm, z. B. 597 × 797 mm.</p>
		</fieldset>

		<fieldset class="kf24-step" id="kf24-step-hinge">
			<legend><span class="kf24-n">4</span> Topfbänder</legend>
			<div class="kf24-seg" role="radiogroup" aria-label="Anschlag">
				<?php foreach ( [ 'links' => 'Anschlag links', 'rechts' => 'Anschlag rechts', 'oben' => 'oben (Klappe)', 'keine' => 'keine Bohrung' ] as $k => $t ) : ?>
					<label><input type="radio" name="kf24[hinge]" value="<?php echo $f( $k ); ?>" <?php checked( $k, 'links' ); ?>><span><?php echo esc_html( $t ); ?></span></label>
				<?php endforeach; ?>
			</div>
			<div class="kf24-hinge-detail">
				<div class="kf24-row3">
					<label>Anzahl <select name="kf24[hinges]" id="kf24-hinges"><?php for ( $i = 1; $i <= 5; $i++ ) { printf( '<option value="%1$d"%2$s>%1$d</option>', (int) $i, selected( $i, 2, false ) ); } ?></select></label>
					<label>Anschlagart <select name="kf24[twin]"><option value="einzel">Einzelanschlag</option><option value="zwilling">Zwillingsanschlag</option></select></label>
					<label>Korpus-Wandstärke <select name="kf24[body]"><option value="19">19 mm</option><option value="16">16 mm</option></select></label>
				</div>
				<p class="kf24-hint kf24-hint-body">Bei 18-mm-Platten bitte 19 mm wählen.</p>
				<div class="kf24-hpos" id="kf24-hpos"></div>
				<label class="kf24-check"><input type="checkbox" name="kf24[deliver]" value="yes"> Topfbänder mitliefern<?php echo (float) kf24_opt( 'hinge_price' ) > 0 ? ' (' . esc_html( wp_strip_all_tags( wc_price( kf24_opt( 'hinge_price' ) ) ) ) . ' je Stück)' : ''; ?></label>
			</div>
		</fieldset>

		<fieldset class="kf24-step">
			<legend><span class="kf24-n">5</span> Griff</legend>
			<div class="kf24-seg" role="radiogroup" aria-label="Griffbohrung">
				<label><input type="radio" name="kf24[grip]" value="0" checked><span>keine Bohrung</span></label>
				<label><input type="radio" name="kf24[grip]" value="1"><span>Knopf (1 Loch)</span></label>
				<label><input type="radio" name="kf24[grip]" value="2"><span>Bügelgriff (2 Löcher)</span></label>
			</div>
			<div class="kf24-grip-detail">
				<div class="kf24-row3">
					<label>Position <select name="kf24[gpos]" id="kf24-gpos"><option value="mitte">mittig horizontal</option><option value="oben-h">oben horizontal</option><option value="unten-h">unten horizontal</option><option value="oben-v">oben vertikal</option><option value="unten-v">unten vertikal</option></select></label>
					<label class="kf24-gd">Lochabstand <span>mm</span><select name="kf24[gd]" id="kf24-gd"><?php foreach ( [ 96, 128, 160, 192, 224, 256, 320, 448, 640 ] as $d ) { printf( '<option value="%1$d"%2$s>%1$d</option>', (int) $d, selected( $d, 128, false ) ); } ?></select></label>
				</div>
				<div class="kf24-row3">
					<label class="kf24-ga"><span class="kf24-ga-l">Abstand von oben (a)</span> <span>mm</span><input type="number" inputmode="decimal" name="kf24[ga]" id="kf24-ga" step="0.5"></label>
					<label class="kf24-gb">Abstand von der Öffnungskante (b) <span>mm</span><input type="number" inputmode="decimal" name="kf24[gb]" id="kf24-gb" step="0.5" value="40"></label>
				</div>
			</div>
			<?php if ( $c['milling'] ) : ?>
				<p class="kf24-sub">Grifffräsung <small>(+<?php echo esc_html( wp_strip_all_tags( wc_price( kf24_opt( 'milling_price' ) ) ) ); ?>)</small></p>
				<div class="kf24-seg" role="radiogroup" aria-label="Grifffräsung">
					<label><input type="radio" name="kf24[mill]" value="keine" checked><span>keine</span></label>
					<label><input type="radio" name="kf24[mill]" value="oben"><span>oben</span></label>
					<label><input type="radio" name="kf24[mill]" value="unten"><span>unten</span></label>
				</div>
			<?php endif; ?>
		</fieldset>

		<fieldset class="kf24-step">
			<legend><span class="kf24-n">6</span> Extras und Menge</legend>
			<label class="kf24-check kf24-seal"><input type="checkbox" name="kf24[seal]" value="yes"> Aufsteckdichtung für Sockelblende<?php echo (float) kf24_opt( 'seal_price_lfm' ) > 0 ? ' (' . esc_html( wp_strip_all_tags( wc_price( kf24_opt( 'seal_price_lfm' ) ) ) ) . ' je lfm' . ( kf24_opt( 'seal_note' ) ? ' – ' . esc_html( kf24_opt( 'seal_note' ) ) : '' ) . ')' : ''; ?></label>
			<label class="kf24-block">Hinweis zu dieser Front <small>(optional)</small><textarea name="kf24[note]" rows="2" maxlength="400" placeholder="Sonstige Bohrungen bitte mit vermaßter Skizze per E-Mail."></textarea></label>
			<label class="kf24-qty">Anzahl gleicher Fronten <input type="number" name="quantity" id="kf24-qty" value="1" min="1" max="99" step="1" inputmode="numeric"></label>
		</fieldset>
	</div>

	<aside class="kf24-side" aria-live="polite">
		<div class="kf24-drawing" id="kf24-drawing" role="img" aria-label="Zeichnung der Front"></div>
		<ul class="kf24-msgs" id="kf24-msgs"></ul>
		<div class="kf24-pricebox">
			<table class="kf24-breakdown" id="kf24-breakdown"></table>
			<div class="kf24-total"><span>Gesamt</span><b id="kf24-total">–</b></div>
			<p class="kf24-legal">inkl. MwSt., zzgl. <a href="<?php echo esc_url( home_url( '/versand/' ) ); ?>">Versand</a> · Lieferzeit <?php echo esc_html( $c['lz'] ); ?></p>
			<button type="submit" name="add-to-cart" value="<?php echo (int) $product->get_id(); ?>" class="single_add_to_cart_button button alt kf24-submit" id="kf24-submit">In den Warenkorb</button>
			<p class="kf24-legal">Maßanfertigung: kein Widerrufsrecht. Bitte prüfen Sie Maße und Bohrungen sorgfältig.</p>
		</div>
	</aside>
	</div>
	<div class="kf24-mbar"><span><small>Gesamt inkl. MwSt.</small><b id="kf24-mtotal">–</b></span><button type="submit" name="add-to-cart" value="<?php echo (int) $product->get_id(); ?>" class="button alt" id="kf24-msubmit">In den Warenkorb</button></div>
	</form>
	</section>
	<?php
}

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'kf24', KF24_URL . 'assets/kf24.css', [], KF24_VERSION );
	wp_register_script( 'kf24-config', KF24_URL . 'assets/kf24-config.js', [], KF24_VERSION, true );
	wp_register_script( 'kf24-metod', KF24_URL . 'assets/kf24-metod.js', [ 'jquery' ], KF24_VERSION, true );
	wp_register_script( 'kf24-tools', KF24_URL . 'assets/kf24-tools.js', [], KF24_VERSION, true );
	if ( is_product() || is_cart() || is_checkout() ) {
		wp_enqueue_style( 'kf24' );
	}
} );

/** Products without own image: show their materials instead of the grey placeholder. */
function kf24_material_collage( $product_id, $class = '' ) {
	$opts = array_slice( array_values( array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $product_id, '_kf24_options', true ) ) ) ) ), 0, 4 );
	return kf24_collage_from_names( $opts, $class );
}
function kf24_collage_from_names( $opts, $class = '' ) {
	if ( ! $opts ) {
		return '';
	}
	$o = '<div class="kf24-collage n' . count( $opts ) . ' ' . esc_attr( $class ) . '" role="img" aria-label="' . esc_attr( 'Materialauswahl: ' . implode( ', ', $opts ) ) . '">';
	foreach ( $opts as $n ) {
		$l  = kf24_material_look( $n );
		$o .= '<span style="' . esc_attr( ( $l['img'] ? 'background-image:url(' . $l['img'] . ');' : '' ) . 'background-color:' . $l['color'] ) . '"></span>';
	}
	return $o . '</div>';
}
add_filter( 'woocommerce_single_product_image_thumbnail_html', function ( $html, $att_id = 0 ) {
	global $product;
	if ( ! $att_id && $product && kf24_is_cfg_product( $product ) && ! $product->get_image_id() ) {
		$c = kf24_material_collage( $product->get_id(), 'is-single' );
		return $c ? '<div class="woocommerce-product-gallery__image--placeholder">' . $c . '</div>' : $html;
	}
	return $html;
}, 10, 2 );
add_filter( 'woocommerce_product_get_image', function ( $html, $product ) {
	if ( ! $product->get_image_id() && kf24_is_cfg_product( $product ) ) {
		return kf24_material_collage( $product->get_id() ) ?: $html;
	}
	return $html;
}, 10, 2 );
