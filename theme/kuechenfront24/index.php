<?php
get_header();
$kf24t_title = is_category() ? single_cat_title( '', false ) : ( is_search() ? 'Suche: ' . get_search_query() : ( is_archive() ? get_the_archive_title() : 'Blog' ) );
$lead  = is_category( 'referenzen' ) ? 'Küchen, Sideboards und Wohnwände unserer Kunden – mit Fotos und Material.' : wp_strip_all_tags( category_description() );
kf24t_pagehead( wp_strip_all_tags( $kf24t_title ), $lead );
?>
<div class="band"><div class="wrap">
	<?php if ( have_posts() ) : ?>
		<div class="posts"><?php while ( have_posts() ) { the_post(); get_template_part( 'parts/card' ); } ?></div>
		<nav class="pagination" aria-label="Seiten"><?php echo wp_kses_post( (string) paginate_links( [ 'prev_text' => '‹', 'next_text' => '›' ] ) ); ?></nav>
	<?php else : ?>
		<p>Hier gibt es noch nichts zu sehen.</p>
	<?php endif; ?>
</div></div>
<?php
get_footer();
