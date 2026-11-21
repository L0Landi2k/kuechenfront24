<?php
/**
 * Shop overview: three clear paths instead of one long product list.
 */
defined( 'ABSPATH' ) || exit;
$cfg    = function_exists( 'kf24_all_cfg_products' ) ? kf24_all_cfg_products() : [];
$groups = [ 'Dekor' => 'Dekor', 'Furnier' => 'Echtholz furniert', 'Massivholz' => 'Massivholz', 'Lack' => 'Lack', 'Fenix' => 'Fenix NTM' ];
$metod  = get_term_by( 'slug', function_exists( 'kf24_opt' ) ? kf24_opt( 'front_cat' ) : 'fronten-fuer-metod', 'product_cat' );
$nm     = get_term_by( 'slug', 'fronten-nach-mass', 'product_cat' );
$page   = fn( $slug ) => ( $p = get_page_by_path( $slug ) ) ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
kf24t_pagehead( 'Shop', 'Drei Wege zu Ihren neuen Fronten. Wählen Sie, was zu Ihrer Küche passt.' );
?>
<div class="entry-content shop-landing">
	<div class="paths">
		<a class="path" href="#nach-mass"><span class="path-n">1</span><h2>Fronten nach Maß</h2><p>Für jede Küche: Faktum, Nobilia, Nolte, Eigenbau … und Sonderteile für Metod. Sie geben die Maße in Millimetern ein.</p><span class="path-go">Front wählen ↓</span></a>
		<?php if ( $metod ) : ?><a class="path" href="<?php echo esc_url( get_term_link( $metod ) ); ?>"><span class="path-n">2</span><h2>Passend für IKEA Metod</h2><p>Fertige Teile in allen Metod-Rastermaßen. Einfach Teil aus dem Küchenplan auswählen.</p><span class="path-go">Zu den Metod-Fronten →</span></a><?php endif; ?>
		<a class="path" href="<?php echo esc_url( $page( 'muster' ) ); ?>"><span class="path-n">3</span><h2>Erst Muster bestellen</h2><p>Farbe und Holz in die Hand nehmen, bevor Sie bestellen. Ab 0,50 €.</p><span class="path-go">Zu den Mustern →</span></a>
	</div>
	<p class="shop-help">Unsicher? Die <a href="<?php echo esc_url( $page( 'aufmass' ) ); ?>">Aufmaß-Tabelle</a> sammelt alle Fronten Ihrer Küche, der <a href="<?php echo esc_url( $page( 'preisrechner' ) ); ?>">Preisrechner</a> zeigt den Richtpreis. Oder rufen Sie uns an: <a href="tel:+498946205236">089 462 05 236</a>.</p>

	<h2 id="nach-mass">Fronten nach Maß konfigurieren</h2>
	<p>Jede Front fertigen wir millimetergenau, mit Bohrungen für Topfbänder und Griffe. Preis je m², inkl. MwSt., zzgl. Versand.</p>
	<?php foreach ( $groups as $g => $label ) :
		$list = array_filter( $cfg, fn( $c ) => $c['group'] === $g );
		if ( ! $list ) { continue; } ?>
		<h3 class="shop-g"><?php echo esc_html( $label ); ?></h3>
		<div class="shop-grid">
			<?php foreach ( $list as $c ) :
				$p   = wc_get_product( $c['id'] );
				$img = $p && $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'medium' ) : '';
				$lk  = $img ? [ 'img' => $img, 'color' => '#ddd' ] : kf24_product_look( $c['id'] );
				$n   = count( $c['options'] ); ?>
				<a class="shop-card" href="<?php echo esc_url( $c['url'] ); ?>">
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

	<?php if ( $metod ) :
		$kids = get_terms( [ 'taxonomy' => 'product_cat', 'parent' => $metod->term_id, 'hide_empty' => false ] ); ?>
		<h2 id="metod" style="margin-top:48px">Passend für IKEA Metod</h2>
		<p>Fertige Teile in allen Metod-Rastermaßen, gebohrt für die IKEA-Beschläge. <a href="<?php echo esc_url( $page( 'ikea-metod' ) ); ?>">Mehr zu Metod</a></p>
		<div class="shop-cats">
			<?php foreach ( (array) $kids as $k ) : ?>
				<a class="card" href="<?php echo esc_url( get_term_link( $k ) ); ?>"><b><?php echo esc_html( $k->name ); ?></b><small><?php echo (int) $k->count; ?> Produkte</small></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="note" style="margin-top:40px"><b>IKEA Faktum?</b> Faktum-Fronten fertigen wir nach Maß (Weg 1). Die <a href="<?php echo esc_url( add_query_arg( 'system', 'faktum', $page( 'aufmass' ) ) ); ?>">Faktum-Bestellhilfe</a> listet alle typischen Teile.</div>
</div>
