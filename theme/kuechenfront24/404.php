<?php
get_header();
kf24t_pagehead( 'Seite nicht gefunden', 'Diese Seite gibt es nicht (mehr). Vielleicht hilft Ihnen einer dieser Wege weiter.' );
?>
<div class="band"><div class="wrap row">
	<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">Zur Startseite</a>
	<a class="btn ghost" href="<?php echo esc_url( home_url( '/frontarten/' ) ); ?>">Frontarten</a>
	<a class="btn ghost" href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a>
	<?php get_search_form(); ?>
</div></div>
<?php
get_footer();
