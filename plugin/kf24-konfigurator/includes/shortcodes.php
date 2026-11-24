<?php
defined( 'ABSPATH' ) || exit;

function kf24_tools_data() {
	$list = [];
	foreach ( kf24_all_cfg_products() as $p ) {
		$sid    = kf24_sample_product_id( $p['sample'] );
		$list[] = [
			'id' => $p['id'], 'n' => $p['name'], 'g' => $p['group'], 'p' => $p['m2'], 'lz' => $p['lz'], 'u' => $p['url'],
			'kg' => $p['kgm2'], 'tex' => kf24_product_look( $p['id'] )['img'], 'col' => kf24_product_look( $p['id'] )['color'],
			'sid' => $sid, 'sp' => $sid ? (float) wc_get_product( $sid )->get_price() : 0,
			'sadd' => $sid ? kf24_sample_link( $sid ) : '',
			'e' => $p['edge'], 'pc' => $p['piece'], 'mp' => $p['minprice'], 'ov' => $p['overrides'] ?: new stdClass(),
		];
	}
	return [
		'products' => $list,
		'terms'    => function_exists( 'kf24_front_terms_flat' ) ? kf24_front_terms_flat() : [],
		's'        => [
			'min_t' => (float) kf24_opt( 'min_order_threshold' ), 'min_f' => (float) kf24_opt( 'min_order_fee' ),
			'len_l' => (float) kf24_opt( 'length_limit' ), 'len_w' => (float) kf24_opt( 'length_weight_limit' ), 'len_f' => (float) kf24_opt( 'length_fee' ),
			't1' => (float) kf24_opt( 'ship_t1' ), 't2' => (float) kf24_opt( 'ship_t2' ), 't3' => (float) kf24_opt( 'ship_t3' ), 't4' => (float) kf24_opt( 'ship_t4' ), 'hp' => (float) kf24_opt( 'hinge_price' ), 'ma' => (float) kf24_opt( 'min_area' ),
		],
	];
}

function kf24_enqueue_tools() {
	wp_enqueue_style( 'kf24' );
	wp_enqueue_script( 'kf24-tools' );
	static $done = false;
	if ( ! $done ) {
		wp_localize_script( 'kf24-tools', 'KF24T', kf24_tools_data() );
		$done = true;
	}
}

/** [kf24_preisrechner] */
add_shortcode( 'kf24_preisrechner', function () {
	kf24_enqueue_tools();
	ob_start(); ?>
	<div class="kf24-calc" id="kf24-calc">
		<div class="kf24-calc-in">
			<label class="kf24-block">Frontart<select id="kc-type"></select></label>
			<div class="kf24-calc-head"><h3>Frontteile</h3><span><button type="button" class="button kf24-ghost" id="kc-preset">Beispielküche laden</button> <button type="button" class="button kf24-ghost" id="kc-clear">Leeren</button></span></div>
			<div id="kc-parts" class="kf24-parts"></div>
			<button type="button" class="button" id="kc-add">+ Frontteil hinzufügen</button>
			<p class="kf24-hint">Maße in Millimetern, Breite × Höhe. Für IKEA Metod z. B. Tür 597 × 797, Schubladenfront 597 × 197.</p>
		</div>
		<aside class="kf24-calc-out" aria-live="polite">
			<div class="kf24-calc-chip"><i id="kc-chip"></i><div><small>Ihre Front</small><b id="kc-name"></b></div></div>
			<small>Richtpreis inkl. MwSt. und Versand</small>
			<div class="kf24-calc-big" id="kc-total">0 €</div>
			<table id="kc-lines"></table>
			<p class="kf24-hint" id="kc-lz"></p>
			<a class="button alt" id="kc-shop" href="#">Im Konfigurator bestellen</a> <a class="button" id="kc-sample" href="#">Muster</a>
			<p class="kf24-hint">Richtwert ohne Bohrungen und Sonderbearbeitung, Versand nach geschätztem Gewicht. Verbindlich ist der Preis im Konfigurator.</p>
		</aside>
	</div>
	<?php
	return ob_get_clean();
} );

/** [kf24_frontarten] – table of all configurable fronts with filter. */
add_shortcode( 'kf24_frontarten', function () {
	kf24_enqueue_tools();
	$groups = [ 'Dekor' => 'Dekor', 'Furnier' => 'Echtholz furniert', 'Massivholz' => 'Massivholz', 'Lack' => 'Lack', 'Fenix' => 'Fenix NTM' ];
	$o      = '<div class="kf24-ft"><div class="kf24-filter-row" role="group" aria-label="Nach Frontart filtern"><button type="button" class="button kf24-on" data-g="">Alle</button>';
	foreach ( $groups as $k => $l ) {
		$o .= '<button type="button" class="button kf24-ghost" data-g="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</button>';
	}
	$o .= '</div><div class="kf24-scroll"><table class="kf24-table kf24-stack"><thead><tr><th>Front</th><th>Auswahl</th><th class="n">je m²</th><th>Lieferzeit</th><th class="n">Muster</th><th></th></tr></thead><tbody>';
	foreach ( kf24_all_cfg_products() as $p ) {
		$sid = kf24_sample_product_id( $p['sample'] );
		$lk  = kf24_product_look( $p['id'] );
		$bg  = $lk['img'] ? 'background-image:url(' . esc_url( $lk['img'] ) . ')' : 'background:' . $lk['color'];
		$o  .= '<tr data-g="' . esc_attr( $p['group'] ) . '"><td data-l="Front"><span class="kf24-sw" style="' . esc_attr( $bg ) . '"></span><b>' . esc_html( $p['name'] ) . '</b></td><td data-l="Auswahl">' . count( $p['options'] ) . ' × ' . esc_html( $p['field'] ) . '</td><td data-l="je m²" class="n">' . wc_price( $p['m2'] ) . '</td><td data-l="Lieferzeit">' . esc_html( $p['lz'] ) . '</td><td data-l="Muster" class="n">' . ( $sid ? '<a class="button kf24-ghost" href="' . esc_url( kf24_sample_link( $sid ) ) . '">' . wc_price( wc_get_product( $sid )->get_price() ) . '</a>' : '–' ) . '</td><td><a class="button" href="' . esc_url( $p['url'] ) . '#kf24-konfigurator">Konfigurieren</a></td></tr>';
	}
	return $o . '</tbody></table></div><p class="kf24-hint">Alle Preise inkl. MwSt., zzgl. Versand. Grundpreis je m² ohne Aufpreise.</p></div>';
} );

/** Picture for a sample product: own image, else the look of the front it belongs to. */
function kf24_sample_look( $p ) {
	if ( $p->get_image_id() ) {
		return [ 'img' => wp_get_attachment_image_url( $p->get_image_id(), 'medium' ), 'color' => '#ddd' ];
	}
	foreach ( kf24_all_cfg_products() as $c ) {
		if ( $c['sample'] === $p->get_slug() ) {
			return kf24_product_look( $c['id'] );
		}
	}
	$sw = get_post_meta( $p->get_id(), '_kf24_swatch', true );
	return [ 'img' => kf24_swatch_url( $sw ), 'color' => kf24_swatch_color( $sw ) ];
}

/** [kf24_kontakt] */
add_shortcode( 'kf24_kontakt', function () {
	wp_enqueue_style( 'kf24' );
	$msg = '';
	if ( isset( $_POST['kf24_contact'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_kf24n'] ?? '' ) ), 'kf24_contact' ) ) {
		if ( ! empty( $_POST['kf24_website'] ) ) { // honeypot
			return '<div class="kf24-ok">Vielen Dank.</div>';
		}
		$f = fn( $k ) => sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) );
		$name = $f( 'k_name' );
		$mail = sanitize_email( wp_unslash( $_POST['k_mail'] ?? '' ) );
		$text = sanitize_textarea_field( wp_unslash( $_POST['k_msg'] ?? '' ) );
		if ( ! $name || ! is_email( $mail ) || ! $text ) {
			$msg = '<div class="kf24-err-box">Bitte füllen Sie Name, eine gültige E-Mail-Adresse und Ihre Nachricht aus.</div>';
		} else {
			$att = [];
			if ( ! empty( $_FILES['k_files']['name'][0] ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				$files = $_FILES['k_files']; // phpcs:ignore
				for ( $i = 0; $i < min( 5, count( $files['name'] ) ); $i++ ) {
					if ( $files['error'][ $i ] || $files['size'][ $i ] > 8 * MB_IN_BYTES ) {
						continue;
					}
					$one = [ 'name' => $files['name'][ $i ], 'type' => $files['type'][ $i ], 'tmp_name' => $files['tmp_name'][ $i ], 'error' => 0, 'size' => $files['size'][ $i ] ];
					$up  = wp_handle_upload( $one, [ 'test_form' => false, 'mimes' => [ 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf', 'heic' => 'image/heic' ] ] );
					if ( ! empty( $up['file'] ) ) {
						$att[] = $up['file'];
					}
				}
			}
			$to   = kf24_opt( 'notify_email' ) ?: get_option( 'admin_email' );
			$body = "Anliegen: {$f('k_topic')}\nName: $name\nE-Mail: $mail\nTelefon: {$f('k_tel')}\nBestellnummer: {$f('k_order')}\n\n$text";
			wp_mail( $to, 'Anfrage über die Website: ' . $f( 'k_topic' ), $body, [ 'Reply-To: ' . $name . ' <' . $mail . '>' ], $att );
			foreach ( $att as $a ) {
				wp_delete_file( $a );
			}
			return '<div class="kf24-ok"><b>Danke, ' . esc_html( $name ) . '!</b><br>Ihre Nachricht ist angekommen. Wir melden uns in der Regel innerhalb von zwei Werktagen.</div>';
		}
	}
	ob_start();
	echo $msg; // phpcs:ignore
	?>
	<form class="kf24-contact" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'kf24_contact', '_kf24n' ); ?>
		<input type="hidden" name="kf24_contact" value="1">
		<p class="kf24-hp" aria-hidden="true"><label>Website<input name="kf24_website" tabindex="-1" autocomplete="off"></label></p>
		<label class="kf24-block">Anliegen<select name="k_topic"><option>Beratung zu Material und Maßen</option><option>Individuelles Angebot</option><option>Aufmaßservice München</option><option>Sonderwunsch, Schubladen, Korpusse</option><option>Frage zu meiner Bestellung</option><option>Reklamation</option></select></label>
		<div class="kf24-row2"><label class="kf24-block">Name *<input name="k_name" required autocomplete="name"></label><label class="kf24-block">E-Mail *<input type="email" name="k_mail" required autocomplete="email"></label></div>
		<div class="kf24-row2"><label class="kf24-block">Telefon<input type="tel" name="k_tel" autocomplete="tel"></label><label class="kf24-block">Bestellnummer<input name="k_order"></label></div>
		<label class="kf24-block">Nachricht *<textarea name="k_msg" rows="6" required></textarea></label>
		<label class="kf24-block">Skizze oder Fotos (optional, bis 5 Dateien à 8 MB)<input type="file" name="k_files[]" multiple accept="image/*,.pdf"></label>
		<p class="kf24-hint">Wir verwenden Ihre Angaben nur zur Bearbeitung Ihrer Anfrage. Mehr in der <a href="<?php echo esc_url( get_privacy_policy_url() ?: home_url( '/datenschutz/' ) ); ?>">Datenschutzerklärung</a>.</p>
		<button class="button alt" type="submit">Nachricht senden</button>
	</form>
	<?php
	return ob_get_clean();
} );
