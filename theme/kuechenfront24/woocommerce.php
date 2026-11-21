<?php
get_header();
$kf24t_landing = function_exists( 'is_shop' ) && is_shop() && ! is_search() && ! is_paged() && empty( $_GET['orderby'] ) && empty( $_GET['filter'] ) && function_exists( 'kf24_all_cfg_products' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only
if ( $kf24t_landing ) {
	get_template_part( 'parts/shop-landing' );
} else {
	echo '<div class="woo-wrap">';
	woocommerce_content();
	echo '</div>';
}
get_footer();
