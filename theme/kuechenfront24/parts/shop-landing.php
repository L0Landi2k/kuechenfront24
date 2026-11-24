<?php
/**
 * "Fronten bestellen": the ONE entry for ordering.
 * Step 1: which kitchen (Metod / Faktum / other) – Step 2: which front. Everything ends in the same configurator.
 */
defined( 'ABSPATH' ) || exit;
$cfg    = function_exists( 'kf24_all_cfg_products' ) ? kf24_all_cfg_products() : [];
$groups = [ 'Dekor' => [ 'Dekor', 'dekor' ], 'Furnier' => [ 'Echtholz furniert', 'furnier' ], 'Massivholz' => [ 'Massivholz', 'massivholz' ], 'Lack' => [ 'Lack', 'lack' ], 'Fenix' => [ 'Fenix NTM', 'fenix' ] ];
$page   = fn( $slug ) => ( $p = get_page_by_path( $slug ) ) ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
$k      = isset( $_GET['kueche'] ) ? sanitize_key( wp_unslash( $_GET['kueche'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only
$k      = in_array( $k, [ 'metod', 'faktum', 'andere' ], true ) ? $k : '';
$opts   = [
	'metod'  => [ 'IKEA Metod', 'Teile aus Ihrem Küchenplan antippen – die Maße sind hinterlegt.' ],
	'faktum' => [ 'IKEA Faktum', 'Alle Faktum-Teile mit Bohrbild hinterlegt, auch wenn es die Serie nicht mehr gibt.' ],
	'andere' => [ 'Jede andere Küche', 'Nobilia, Nolte, Eigenbau … Maße millimetergenau eingeben.' ],
];
kf24t_pagehead( 'Fronten bestellen', 'In zwei Schritten zu Ihren neuen Fronten. Alle Teile Ihrer Küche sammeln Sie danach in einer Liste und bestellen mit einem Klick.' );
?>
<div class="entry-content shop-landing" id="kf24-shop" data-kueche="<?php echo esc_attr( $k ); ?>">
	<section class="shop-step">
		<h2><span class="step-n">1</span> Welche Küche haben Sie?</h2>
		<div class="kueche" role="radiogroup" aria-label="Ihre Küche">
			<?php foreach ( $opts as $key => $t ) : ?>
				<label class="kueche-o"><input type="radio" name="kueche" value="<?php echo esc_attr( $key ); ?>" <?php checked( $k, $key ); ?>><b><?php echo esc_html( $t[0] ); ?></b><small><?php echo esc_html( $t[1] ); ?></small></label>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="shop-step" id="front-waehlen">
		<h2><span class="step-n">2</span> Welche Front soll es sein?</h2>
		<p class="shop-help">Preis je m², inkl. MwSt., zzgl. Versand. Unsicher bei Farbe oder Holz? <a href="<?php echo esc_url( $page( 'muster' ) ); ?>">Erst Muster bestellen</a> – ab 0,50 €.</p>
		<?php foreach ( $groups as $g => [ $label, $anchor ] ) :
			$list = array_filter( $cfg, fn( $c ) => $c['group'] === $g );
			if ( ! $list ) {
				continue;
			} ?>
			<h3 class="shop-g" id="g-<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $label ); ?></h3>
			<div class="shop-grid">
				<?php foreach ( $list as $c ) :
					$p   = wc_get_product( $c['id'] );
					$img = $p && $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'medium' ) : '';
					$lk  = $img ? [ 'img' => $img, 'color' => '#ddd' ] : kf24_product_look( $c['id'] );
					$n   = count( $c['options'] );
					$url = in_array( $k, [ 'metod', 'faktum' ], true ) ? add_query_arg( 'system', $k, $c['url'] ) : $c['url']; ?>
					<a class="shop-card" data-base="<?php echo esc_url( $c['url'] ); ?>" href="<?php echo esc_url( $url . '#kf24-konfigurator' ); ?>">
						<?php if ( ! $img && $n > 1 && function_exists( 'kf24_material_collage' ) ) : ?>
							<span class="shop-pic is-collage"><?php echo kf24_material_collage( $c['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?></span>
						<?php else : ?>
							<span class="shop-pic" style="<?php echo esc_attr( ( $lk['img'] ? 'background-image:url(' . $lk['img'] . ');' : '' ) . 'background-color:' . $lk['color'] ); ?>"></span>
						<?php endif; ?>
						<span class="shop-b"><b><?php echo esc_html( preg_replace( '/\s+–\s+individuell konfigurierbar$/u', '', $c['name'] ) ); ?></b>
						<small><?php echo esc_html( $n . ' ' . ( $n === 1 ? 'Ausführung' : 'Ausführungen' ) . ' · Lieferzeit ' . $c['lz'] ); ?></small>
						<span class="shop-price">ab <?php echo wp_kses_post( wc_price( $c['m2'] ) ); ?> je m²</span></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</section>

	<section class="shop-step shop-how">
		<h2><span class="step-n">3</span> So geht es weiter</h2>
		<ol>
			<li><b>Material wählen</b> und die erste Front eingeben – bei Metod und Faktum einfach das Teil antippen.</li>
			<li>Alle weiteren Fronten Ihrer Küche kommen in eine <b>Liste</b>: Teile antippen oder „Weitere Front planen“. Preis und Zeichnung sehen Sie sofort.</li>
			<li><b>„Alle in den Warenkorb“</b> – fertig. Die Liste bleibt gespeichert, auch wenn Sie zwischendurch aufhören.</li>
		</ol>
		<p class="shop-help">Lieber erst in Ruhe messen? <a href="<?php echo esc_url( $page( 'aufmass' ) ); ?>">Aufmaß-Tabelle</a> zum Ausdrucken oder Ausfüllen. Fragen? <a href="tel:+498946205236">089 462 05 236</a></p>
	</section>
</div>
<script>
(function () {
	var root = document.getElementById('kf24-shop'); if (!root) return;
	var cur = root.dataset.kueche;
	try { if (cur) localStorage.setItem('kf24_kueche', cur); else cur = localStorage.getItem('kf24_kueche') || ''; } catch (e) {}
	function apply(v) {
		root.querySelectorAll('input[name=kueche]').forEach(function (r) { r.checked = r.value === v; });
		root.querySelectorAll('.shop-card').forEach(function (a) {
			var u = new URL(a.dataset.base, location.href);
			if (v === 'metod' || v === 'faktum') u.searchParams.set('system', v);
			u.hash = 'kf24-konfigurator'; a.href = u.toString();
		});
	}
	root.addEventListener('change', function (e) {
		if (e.target.name !== 'kueche') return;
		try { localStorage.setItem('kf24_kueche', e.target.value); } catch (er) {}
		apply(e.target.value);
		document.getElementById('front-waehlen').scrollIntoView({ behavior: 'smooth' });
	});
	apply(cur);
})();
</script>
