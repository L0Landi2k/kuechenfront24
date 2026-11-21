<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main">Zum Inhalt springen</a>
<div class="pre"><?php foreach ( array_filter( explode( '|', kf24t( 'kf24_topbar' ) ) ) as $t ) { echo '<span>' . esc_html( trim( $t ) ) . '</span>'; } ?><span>Beratung: <?php echo esc_html( kf24t( 'kf24_phone' ) ); ?></span></div>
<header class="site-header">
	<div class="bar">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Küchenfront24 Startseite">
			<svg width="34" height="34" viewBox="0 0 34 34" aria-hidden="true"><rect x="2" y="2" width="14" height="30" rx="2" fill="#b98b57"/><rect x="18" y="2" width="14" height="9" rx="2" fill="currentColor"/><rect x="18" y="13" width="14" height="9" rx="2" fill="currentColor" opacity=".7"/><rect x="18" y="24" width="14" height="8" rx="2" fill="currentColor" opacity=".45"/><rect x="12" y="10" width="2" height="9" rx="1" fill="#fff"/></svg>
			<span>Küchenfront<b>24</b></span>
		</a>
		<nav class="main-nav" aria-label="Hauptnavigation">
			<?php wp_nav_menu( [ 'theme_location' => 'primary', 'container' => false, 'fallback_cb' => false, 'depth' => 2 ] ); ?>
		</nav>
		<div class="tools">
			<a class="btn sm ghost tel" href="<?php echo esc_attr( kf24t_tel() ); ?>"><?php echo esc_html( kf24t( 'kf24_phone' ) ); ?></a>
			<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
				<a class="icon-btn" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="Warenkorb"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg><span class="cnt"><?php echo WC()->cart ? esc_html( WC()->cart->get_cart_contents_count() ?: '' ) : ''; ?></span></a>
				<a class="icon-btn" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="Mein Konto"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg></a>
			<?php endif; ?>
			<button class="icon-btn burger" id="menuBtn" aria-label="Menü öffnen" aria-controls="menu" aria-expanded="false"><svg width="22" height="22" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
		</div>
	</div>
</header>
<div class="drawer" id="menu"><div class="scrim" data-close></div><div class="sheet" role="dialog" aria-label="Menü">
	<div class="sheet-head"><b class="disp" style="font-size:20px">Menü</b><button class="icon-btn" data-close aria-label="Menü schließen">✕</button></div>
	<?php wp_nav_menu( [ 'theme_location' => 'primary', 'container' => false, 'fallback_cb' => false, 'depth' => 2 ] ); ?>
	<a class="btn" href="<?php echo esc_attr( kf24t_tel() ); ?>" style="margin-top:12px;color:#fff">Anrufen: <?php echo esc_html( kf24t( 'kf24_phone' ) ); ?></a>
</div></div>
<main id="main" class="site-main-wrap">
