<?php
/**
 * Online-Aufmaßtabelle (ausfüllbar, druckbar, an uns senden, in Preisrechner/Konfigurator übernehmen),
 * Faktum-/Metod-Bestellhilfe und RAL-Farbübersicht.
 */
defined( 'ABSPATH' ) || exit;

/** Common front names → configurator type. Shared by Preisrechner, Aufmaß and configurator. */
function kf24_front_terms() {
	return [
		'Türen'          => [ 'Tür' => 'tuer', 'Tür schmal' => 'tuer', 'Unterschrank-Tür' => 'tuer', 'Oberschrank-Tür' => 'tuer', 'Hochschrank-Tür' => 'tuer', 'Spülenschrank-Tür' => 'tuer', 'Eckschrank-Tür' => 'tuer', 'Kühlschrank-Tür (Einbau)' => 'tuer', 'Vitrinentür (ohne Glas)' => 'tuer' ],
		'Schubladen'     => [ 'Schubladenfront' => 'schublade', 'Schubladenfront flach' => 'schublade', 'Auszugfront hoch (Topfschublade)' => 'schublade', 'Apothekerfront (Apothekerauszug)' => 'schublade', 'Mülleimer-Auszugfront' => 'schublade' ],
		'Klappen'        => [ 'Klappe (Oberschrank)' => 'klappe', 'Klappe über Hochschrank' => 'klappe', 'Dunstabzug-Klappe' => 'klappe' ],
		'Blenden & Teile' => [ 'Geschirrspülerfront' => 'blende', 'Backofen-Blende' => 'blende', 'Passblende' => 'blende', 'Sockelblende' => 'blende', 'Seitenwange / Abdeckseite' => 'blende', 'Kranzblende' => 'blende' ],
		'Sonstiges'      => [ 'Sonstiges Bauteil' => 'sonstig' ],
	];
}

/** Flat label → type map for JS. */
function kf24_front_terms_flat() {
	$out = [];
	foreach ( kf24_front_terms() as $g => $items ) {
		foreach ( $items as $l => $t ) {
			$out[] = [ 'l' => $l, 't' => $t, 'g' => $g ];
		}
	}
	return $out;
}

function kf24_enqueue_aufmass() {
	kf24_enqueue_tools();
	wp_enqueue_script( 'kf24-aufmass', KF24_URL . 'assets/kf24-aufmass.js', [], KF24_VERSION, true );
	wp_localize_script( 'kf24-aufmass', 'KF24A', [
		'terms'   => kf24_front_terms_flat(),
		'catalog' => kf24_catalog_js(),
		'ajax'    => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'kf24_aufmass' ),
		'phone'   => kf24_opt( 'phone' ) ?: '089 462 05 236',
		'calc'    => ( $p = get_page_by_path( 'preisrechner' ) ) ? get_permalink( $p ) : home_url( '/preisrechner/' ),
		'max'     => [ (int) kf24_opt( 'max_long' ), (int) kf24_opt( 'max_short' ), (int) kf24_opt( 'min_dim' ) ],
	] );
}

/** Old Preisrechner address → Planen & Preis. */
add_action( 'template_redirect', function () {
	if ( is_404() && trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH ), '/' ) === 'preisrechner' ) {
		wp_safe_redirect( home_url( '/aufmass/' ), 301 );
		exit;
	}
} );

/** [kf24_aufmass system="faktum|metod"] */
add_shortcode( 'kf24_aufmass', function ( $atts ) {
	$a = shortcode_atts( [ 'system' => '' ], $atts );
	kf24_enqueue_aufmass();
	ob_start();
	?>
	<div class="kf24-am" id="kf24-am" data-system="<?php echo esc_attr( $a['system'] ); ?>">
		<div class="kf24-am-head kf24-noprint-soft">
			<fieldset class="kf24-am-sys"><legend>Für welche Küche?</legend>
				<label><input type="radio" name="am_sys" value="metod"> IKEA Metod</label>
				<label><input type="radio" name="am_sys" value="faktum"> IKEA Faktum</label>
				<label><input type="radio" name="am_sys" value="andere" checked> andere Küche</label>
			</fieldset>
			<label class="kf24-am-f">Korpus-Wandstärke<select id="am-body"><option value="">weiß ich nicht</option><option>16 mm</option><option>19 mm</option><option>18 mm</option></select></label>
			<label class="kf24-am-f">Oberfläche <small>(Holzfronten)</small><select id="am-fin"><option value="">Standard</option><option value="lack">Klarlack matt</option><option value="oel">geölt</option></select></label>
			<label class="kf24-am-f kf24-am-wide">Ihre Front <small>(für Preis und Bestellung)</small><select id="am-front"><option value="">bitte wählen</option></select></label>
		</div>

		<div class="kf24-am-helper kf24-noprint" hidden>
			<p><b class="kf24-am-helper-t"></b> <span class="kf24-hint">Tippen Sie die Teile an, die Ihre Küche hat. Die genauen Millimeter messen Sie danach an der alten Front.</span></p>
			<div class="kf24-am-chips"></div>
		</div>

		<div class="kf24-am-sheet" id="am-sheet" aria-hidden="true"></div>

		<div class="kf24-scroll"><table class="kf24-am-table">
			<thead><tr><th>#</th><th>Bezeichnung</th><th>Art</th><th>Breite<br><small>mm</small></th><th>Höhe<br><small>mm</small></th><th>Anschlag</th><th>Topf&shy;bänder</th><th>Stück</th><th>Bemerkung</th><th>Preis</th><th><span class="screen-reader-text">Aktionen</span></th></tr></thead>
			<tbody id="am-rows"></tbody>
		</table></div>
		<p class="kf24-noprint"><button type="button" class="button" id="am-add">+ Frontteil hinzufügen</button></p>
		<p class="kf24-am-sum" id="am-sum" aria-live="polite"></p>

		<div class="kf24-am-actions kf24-noprint">
			<button type="button" class="button alt" id="am-go">Weiter: Material wählen &amp; bestellen</button>
			<button type="button" class="button" id="am-print">Drucken / als PDF speichern</button>
			<button type="button" class="button kf24-ghost" id="am-blank">Leere Vorlage drucken</button>
			<button type="button" class="button kf24-ghost" id="am-reset">Tabelle leeren</button>
		</div>
		<p class="kf24-hint kf24-noprint">Unter „Details“ je Zeile: Maserung, Topfband-Abstände, Griffbohrung und Grifffräsung – alles kommt auch auf den Ausdruck. Mit „Weiter“ wählen Sie das Material und legen alle Positionen in den Warenkorb. Ihre Eingaben bleiben in diesem Browser gespeichert.</p>

		<form class="kf24-am-send kf24-noprint" id="am-send">
			<h3>Aufmaß an uns schicken</h3>
			<p class="kf24-hint">Wir prüfen Ihre Maße und melden uns mit einem Angebot. Unverbindlich.</p>
			<div class="kf24-am-grid">
				<label>Name *<input name="name" required autocomplete="name"></label>
				<label>E-Mail *<input name="email" type="email" required autocomplete="email"></label>
				<label>Telefon<input name="phone" type="tel" autocomplete="tel"></label>
			</div>
			<label>Nachricht<textarea name="msg" rows="3" placeholder="z. B. Wunschfarbe, Fragen zu den Maßen"></textarea></label>
			<label class="kf24-hp" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
			<label class="kf24-am-ok"><input type="checkbox" name="ok" required> Ich bin einverstanden, dass meine Angaben zur Bearbeitung der Anfrage verwendet werden (siehe <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Datenschutz</a>).</label>
			<button class="button alt" type="submit">Aufmaß senden</button>
			<p class="kf24-am-msg" role="status"></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
} );

add_action( 'wp_ajax_kf24_aufmass', 'kf24_ajax_aufmass' );
add_action( 'wp_ajax_nopriv_kf24_aufmass', 'kf24_ajax_aufmass' );
function kf24_ajax_aufmass() {
	if ( ! check_ajax_referer( 'kf24_aufmass', 'nonce', false ) ) {
		wp_send_json_error( [ 'msg' => 'Sitzung abgelaufen. Bitte Seite neu laden.' ] );
	}
	$in = wp_unslash( $_POST );
	if ( ! empty( $in['website'] ) ) {
		wp_send_json_success( [ 'msg' => 'Danke!' ] );
	}
	$name  = sanitize_text_field( $in['name'] ?? '' );
	$email = sanitize_email( $in['email'] ?? '' );
	if ( ! $name || ! is_email( $email ) || empty( $in['ok'] ) ) {
		wp_send_json_error( [ 'msg' => 'Bitte Name, E-Mail und Einverständnis angeben.' ] );
	}
	$rows = json_decode( (string) ( $in['rows'] ?? '[]' ), true );
	$rows = is_array( $rows ) ? array_slice( $rows, 0, 80 ) : [];
	if ( ! $rows ) {
		wp_send_json_error( [ 'msg' => 'Die Tabelle ist noch leer.' ] );
	}
	$f    = fn( $v ) => esc_html( sanitize_text_field( (string) $v ) );
	$html = '<p><b>Aufmaß von ' . $f( $name ) . '</b> · ' . $f( $email ) . ' · ' . $f( $in['phone'] ?? '' ) . '</p>';
	$html .= '<p>Küche: ' . $f( $in['sys'] ?? '' ) . ' · Wandstärke: ' . $f( $in['body'] ?? '' ) . ' · Gewünschte Front: ' . $f( $in['front'] ?? '' ) . ' · Oberfläche: ' . $f( $in['fin'] ?? '' ) . '</p>';
	$html .= '<p>' . nl2br( esc_html( sanitize_textarea_field( $in['msg'] ?? '' ) ) ) . '</p>';
	$html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font:14px sans-serif"><tr><th>#</th><th>Bezeichnung</th><th>Art</th><th>Breite</th><th>Höhe</th><th>Anschlag</th><th>Topfbänder</th><th>Stück</th><th>Bemerkung</th><th>Details</th></tr>';
	foreach ( $rows as $i => $r ) {
		$html .= '<tr><td>' . ( $i + 1 ) . '</td><td>' . $f( $r['n'] ?? '' ) . '</td><td>' . $f( $r['t'] ?? '' ) . '</td><td>' . (int) ( $r['w'] ?? 0 ) . '</td><td>' . (int) ( $r['h'] ?? 0 ) . '</td><td>' . $f( $r['a'] ?? '' ) . '</td><td>' . (int) ( $r['b'] ?? 0 ) . '</td><td>' . max( 1, (int) ( $r['q'] ?? 1 ) ) . '</td><td>' . $f( $r['r'] ?? '' ) . '</td><td>' . $f( $r['d'] ?? '' ) . '</td></tr>';
	}
	$html   .= '</table>';
	$to      = kf24_opt( 'notify_email' ) ?: get_option( 'admin_email' );
	$headers = [ 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' ];
	wp_mail( $to, 'Neues Aufmaß von ' . $name, $html, $headers );
	wp_mail( $email, 'Ihr Aufmaß bei Küchenfront24', '<p>Guten Tag ' . esc_html( $name ) . ',</p><p>vielen Dank. Wir haben Ihr Aufmaß erhalten und melden uns in der Regel innerhalb von zwei Werktagen.</p>' . $html . '<p>Ihr Team von Küchenfront24</p>', [ 'Content-Type: text/html; charset=UTF-8' ] );
	wp_send_json_success( [ 'msg' => 'Danke! Ihr Aufmaß ist bei uns angekommen. Eine Kopie haben wir Ihnen per E-Mail geschickt.' ] );
}

/** [kf24_ral] – all RAL colours offered for lacquered fronts. */
add_shortcode( 'kf24_ral', function () {
	kf24_enqueue_tools();
	$names = [ 1 => 'Gelb und Beige', 2 => 'Orange', 3 => 'Rot', 4 => 'Violett', 5 => 'Blau', 6 => 'Grün', 7 => 'Grau', 8 => 'Braun', 9 => 'Weiß und Schwarz' ];
	$by    = [];
	foreach ( kf24_materials() as $m ) {
		if ( $m['group'] === 'Lack RAL' && preg_match( '/RAL (\d)(\d{3})/', $m['name'] . ' ' . $m['code'], $mm ) ) {
			$by[ (int) $mm[1] ][ $mm[1] . $mm[2] ] = $m;
		}
	}
	ksort( $by );
	$lack = '';
	foreach ( kf24_all_cfg_products() as $p ) {
		if ( $p['group'] === 'Lack' ) {
			$lack = $p['url'];
			break;
		}
	}
	ob_start();
	echo '<div class="kf24-ral"><input type="search" class="kf24-ral-q" placeholder="RAL-Nummer oder Name, z. B. 7016 oder Anthrazit" aria-label="RAL-Farbe suchen">';
	foreach ( $by as $g => $list ) {
		ksort( $list );
		echo '<h3>' . esc_html( $names[ $g ] ?? '' ) . '</h3><div class="kf24-ral-grid">';
		foreach ( $list as $m ) {
			printf( '<div class="kf24-ral-c" data-q="%s"><span style="background:%s"></span><b>%s</b></div>', esc_attr( mb_strtolower( $m['name'] ) ), esc_attr( $m['color'] ), esc_html( $m['name'] ) );
		}
		echo '</div>';
	}
	echo '<p class="kf24-hint">Bildschirme zeigen Farben unterschiedlich. Verbindlich ist der Farbton am Muster. Metallic- und Signaltöne fertigen wir nicht.</p>';
	if ( $lack ) {
		echo '<p><a class="button alt" href="' . esc_url( $lack ) . '#kf24-konfigurator">Lackfront konfigurieren</a> <a class="button" href="' . esc_url( home_url( '/muster/' ) ) . '">Farbmuster bestellen</a></p>';
	}
	echo '</div>';
	?>
	<script>document.querySelectorAll('.kf24-ral-q').forEach(function(q){q.addEventListener('input',function(){var v=q.value.toLowerCase().trim();q.parentNode.querySelectorAll('.kf24-ral-c').forEach(function(c){c.hidden=v&&c.dataset.q.indexOf(v)<0;});});});</script>
	<?php
	return ob_get_clean();
} );
