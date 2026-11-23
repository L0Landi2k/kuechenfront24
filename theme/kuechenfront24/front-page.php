<?php
get_header();
$u = fn( $s ) => esc_url( home_url( '/' . $s . '/' ) );
?>
<section class="hero">
	<div class="hero-top">
		<div>
			<h1>Neue Fronten.<br>Ihre Küche bleibt.</h1>
			<p class="lead">Küchenfronten nach Maß aus der Schreinerei: passend für IKEA Metod, Faktum und jeden anderen Korpus. Fertig gebohrt geliefert, in wenigen Stunden selbst montiert.</p>
			<div class="row"><a class="btn" href="<?php echo $u( 'aufmass' ); ?>">Fronten planen &amp; Preis</a><a class="btn ghost" href="<?php echo $u( 'muster' ); ?>">Muster ab 0,50 €</a></div>
		</div>
		<ul>
			<li><?php echo kf24t_icon_check(); ?><span><b>Über 120 Oberflächen</b> von Dekor über Lack bis Massivholz und Fenix NTM</span></li>
			<li><?php echo kf24t_icon_check(); ?><span><b>Millimetergenau</b> gefertigt, Bohrungen für Topfbänder und Griffe nach Ihren Angaben</span></li>
			<li><?php echo kf24t_icon_check(); ?><span><b>Aus Bayern und Baden-Württemberg</b>, ohne Tropenholz, jede Front endkontrolliert</span></li>
		</ul>
	</div>
	<div class="stage">
		<div id="heroScene"></div>
		<div class="dock">
			<div class="sw-row" id="swRow" role="group" aria-label="Frontmaterial wählen"></div>
			<div class="sw-info" aria-live="polite"><span class="small">Gewählte Front</span><b id="swName"></b><span id="swPrice" class="small"></span></div>
		</div>
	</div>
	<p class="small" style="max-width:1240px;margin:14px auto 0;padding-bottom:28px;text-align:center">Materialdarstellung als Illustration. Für die echte Farbe und Haptik empfehlen wir ein Muster.</p>
</section>

<div class="band"><div class="wrap usp">
	<div><b>Seit 2008</b><span class="small">Online-Vertrieb der Schreinerei Senft</span></div>
	<div><b>Fertig gebohrt</b><span class="small">Beschläge von IKEA einfach anschrauben</span></div>
	<div><b>Lieferung ab 4 Wochen</b><span class="small">per UPS oder Spedition</span></div>
	<div><b>PayPal oder Überweisung</b><span class="small">Produktion nach Zahlungseingang</span></div>
</div></div>

<div class="band panel"><div class="wrap">
	<h2>Welche Küche haben Sie?</h2>
	<p class="lead">Wählen Sie Ihren Weg. Alle Fronten fertigen wir auf Bestellung.</p>
	<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr))">
		<a class="path" href="<?php echo $u( 'ikea-metod' ); ?>"><div class="pic" data-scene="metod"></div><div class="txt"><h3>IKEA Metod</h3><p class="small">Fertige Frontmaße aus Ihrem Küchenplan auswählen, in den Warenkorb legen, fertig.</p><span class="btn sm ghost">Zu den Metod-Fronten</span></div></a>
		<a class="path" href="<?php echo $u( 'ikea-faktum' ); ?>"><div class="pic" data-scene="faktum"></div><div class="txt"><h3>IKEA Faktum</h3><p class="small">Die alte Serie gibt es nicht mehr. Wir fertigen passende Fronten mit allen Bohrungen.</p><span class="btn sm ghost">Faktum aufmöbeln</span></div></a>
		<a class="path" href="<?php echo $u( 'nach-mass' ); ?>"><div class="pic" data-scene="indiv"></div><div class="txt"><h3>Jede andere Küche</h3><p class="small">Maße eingeben, Anschlag und Bohrungen festlegen. Passend für jeden Korpus mit 16 oder 19 mm.</p><span class="btn sm ghost">Individuell konfigurieren</span></div></a>
	</div>
</div></div>

<?php if ( function_exists( 'kf24_all_cfg_products' ) && ( $all = kf24_all_cfg_products() ) ) :
	$groups = [ 'Dekor' => [ 'Dekorfronten', 'Pflegeleicht und günstig: Weiß, Unifarben, Holz- und Materialoptik.', 'weiss' ], 'Furnier' => [ 'Echtholz furniert', 'Echtes Holz auf stabiler Trägerplatte, glatt oder im Landhausstil.', 'furnier-eiche' ], 'Massivholz' => [ 'Massivholz 3-Schicht', 'Durchgehend Holz. Eiche, Nussbaum, Kirsche und mehr.', 'eiche-wild' ], 'Lack' => [ 'Lackfronten matt', 'Jede RAL-Farbe, seidig matt lackiert.', 'salbei' ], 'Fenix' => [ 'Fenix NTM', 'Supermatt, angenehm warm, Fingerabdrücke bleiben nicht.', 'fenix' ] ];
	?>
<div class="band"><div class="wrap">
	<div class="row" style="justify-content:space-between;align-items:end;margin-bottom:8px"><div><h2>Frontarten und Preise</h2><p class="lead" style="margin:0">Grundpreise je Quadratmeter, inkl. MwSt., zzgl. Versand.</p></div><a class="btn ghost" href="<?php echo $u( 'frontarten' ); ?>">Alle <?php echo count( $all ); ?> Varianten</a></div>
	<?php foreach ( $groups as $k => [ $t, $d, $sw ] ) :
		$ps = array_filter( $all, fn( $p ) => $p['group'] === $k );
		if ( ! $ps ) { continue; }
		$min = min( array_column( $ps, 'm2' ) );
		$lz  = reset( $ps )['lz'];
		$bg  = kf24_swatch_url( $sw ) ? 'background-image:url(' . esc_url( kf24_swatch_url( $sw ) ) . ')' : 'background:' . kf24_swatch_color( $sw );
		?>
		<div class="mat"><div class="chip" style="<?php echo esc_attr( $bg ); ?>"></div><div><div class="row" style="justify-content:space-between"><h3 style="margin:0"><?php echo esc_html( $t ); ?></h3><span class="price-big" style="font-size:20px">ab <?php echo esc_html( number_format_i18n( $min, 0 ) ); ?> € je m²</span></div><p class="small" style="margin:4px 0 0"><?php echo esc_html( $d ); ?> Lieferzeit <?php echo esc_html( $lz ); ?>.</p></div></div>
	<?php endforeach; ?>
</div></div>
<?php endif; ?>

<div class="band panel"><div class="wrap">
	<h2>So einfach geht’s</h2>
	<div class="steps">
		<div class="step"><h3>Ausmessen</h3><p class="small">Höhe und Breite jeder Front messen und in unsere Aufmaßtabelle eintragen.</p></div>
		<div class="step"><h3>Konfigurieren</h3><p class="small">Material wählen, Maße eingeben, Anschlag und Bohrungen festlegen. Die Zeichnung zeigt sofort, was wir fertigen.</p></div>
		<div class="step"><h3>Fertigen lassen</h3><p class="small">Nach Zahlungseingang geht Ihr Auftrag in die Produktion, mit Endkontrolle jeder Front.</p></div>
		<div class="step"><h3>Selbst montieren</h3><p class="small">Fronten einhängen, Schubladen anschrauben, justieren. Die Montageanleitung liegt bei.</p></div>
	</div>
	<div class="row" style="margin-top:24px"><a class="btn" href="<?php echo $u( 'ratgeber' ); ?>">Anleitung für Aufmaß und Montage</a></div>
</div></div>

<?php
$refs = get_posts( [ 'category_name' => 'referenzen', 'posts_per_page' => 3 ] );
if ( $refs ) : ?>
<div class="band"><div class="wrap">
	<div class="row" style="justify-content:space-between;align-items:end"><div><h2>Aus unserer Werkstatt</h2><p class="lead" style="margin:0">Küchen, Sideboards und Wohnwände unserer Kunden.</p></div><a class="btn ghost" href="<?php echo esc_url( get_category_link( get_category_by_slug( 'referenzen' ) ) ); ?>">Alle Referenzen</a></div>
	<div class="posts" style="margin-top:22px"><?php foreach ( $refs as $post ) { setup_postdata( $post ); get_template_part( 'parts/card' ); } wp_reset_postdata(); ?></div>
</div></div>
<?php endif; ?>

<div class="band deep"><div class="wrap split">
	<div><h2>Lieber persönlich beraten lassen?</h2><p>In unserer Werksausstellung bei München sehen und fühlen Sie die Fronten. Bitte vereinbaren Sie vorher einen Termin.</p><p class="small"><?php echo esc_html( kf24t( 'kf24_showroom' ) ); ?></p></div>
	<div class="row"><a class="btn" style="--acc:#62c0bf;--acc2:#7fd0cf;color:#0b1a1a" href="<?php echo esc_attr( kf24t_tel() ); ?>"><?php echo esc_html( kf24t( 'kf24_phone' ) ); ?></a><a class="btn ghost" style="color:#9fe0df;border-color:#9fe0df" href="<?php echo $u( 'kontakt' ); ?>">Anfrage senden</a></div>
</div></div>

<?php
while ( have_posts() ) {
	the_post();
	if ( trim( get_the_content() ) ) {
		echo '<div class="entry-content">';
		the_content();
		echo '</div>';
	}
}
get_footer();
