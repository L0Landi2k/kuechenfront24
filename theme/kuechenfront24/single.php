<?php
get_header();
while ( have_posts() ) {
	the_post();
	$cats = get_the_category();
	?>
	<div class="pagehead"><div class="wrap narrow">
		<div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Start</a><?php if ( $cats ) { echo ' / <a href="' . esc_url( get_category_link( $cats[0] ) ) . '">' . esc_html( $cats[0]->name ) . '</a>'; } ?></div>
		<h1><?php the_title(); ?></h1>
		<p class="small"><?php echo esc_html( get_the_date() ); ?></p>
	</div></div>
	<?php if ( has_post_thumbnail() && ! get_post_meta( get_the_ID(), '_kf24_ref', true ) ) : ?>
		<div class="band" style="padding-bottom:0"><div class="wrap single-hero"><?php the_post_thumbnail( 'full', [ 'sizes' => '(max-width:1240px) 100vw, 1240px' ] ); ?></div></div>
	<?php endif; ?>
	<div class="entry-content"><?php the_content(); ?>
		<div class="note" style="margin-top:32px"><b>Ihre Küche als Nächstes?</b> <a href="<?php echo esc_url( home_url( '/aufmass/' ) ); ?>">Preis berechnen</a> oder <a href="<?php echo esc_url( home_url( '/muster/' ) ); ?>">Muster bestellen</a>.</div>
	</div>
	<?php
}
get_footer();
