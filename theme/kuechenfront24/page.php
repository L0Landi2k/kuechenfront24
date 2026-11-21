<?php
get_header();
while ( have_posts() ) {
	the_post();
	$lead  = get_post_meta( get_the_ID(), '_kf24_lead', true );
	$legal = in_array( get_post_field( 'post_name' ), [ 'impressum', 'datenschutz', 'agb', 'widerrufsbelehrung', 'vertrag-widerrufen' ], true );
	if ( $legal ) {
		echo '<div class="entry-content legal"><h1>' . esc_html( get_the_title() ) . '</h1>';
		the_content();
		echo '</div>';
	} else {
		kf24t_pagehead( get_the_title(), $lead );
		echo '<div class="entry-content">';
		the_content();
		echo '</div>';
	}
}
get_footer();
