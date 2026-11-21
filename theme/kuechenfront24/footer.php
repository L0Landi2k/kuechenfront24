</main>
<footer class="site-footer">
	<div class="band"><div class="wrap">
		<div class="cols">
			<div>
				<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#f3ece2"><span>Küchenfront<b style="color:#62c0bf">24</b></span></a>
				<p class="small" style="margin-top:12px">Küchenfronten nach Maß aus der Schreinerei. Seit 2008.</p>
				<p><a href="<?php echo esc_attr( kf24t_tel() ); ?>" class="price-big" style="font-size:20px"><?php echo esc_html( kf24t( 'kf24_phone' ) ); ?></a><br><a href="mailto:<?php echo esc_attr( kf24t( 'kf24_email' ) ); ?>"><?php echo esc_html( kf24t( 'kf24_email' ) ); ?></a></p>
				<a class="wbtn" href="<?php echo esc_url( home_url( '/vertrag-widerrufen/' ) ); ?>">Vertrag widerrufen</a>
			</div>
			<div><h3>Fronten</h3><?php wp_nav_menu( [ 'theme_location' => 'footer_shop', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ] ); ?></div>
			<div><h3>Hilfe</h3><?php wp_nav_menu( [ 'theme_location' => 'footer_help', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ] ); ?></div>
			<div><h3>Rechtliches</h3><?php wp_nav_menu( [ 'theme_location' => 'footer_legal', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ] ); ?>
				<ul style="margin-top:8px"><li><a href="#" id="cookieLink">Cookie-Einstellungen</a></li></ul>
				<p class="small" style="margin-top:16px">Zahlung: PayPal · Vorkasse</p></div>
		</div>
		<p class="small" style="margin:36px 0 0;border-top:1px solid #4a3d32;padding-top:18px">© <?php echo esc_html( wp_date( 'Y' ) ); ?> Küchenfront24 · Alle Preise inkl. MwSt., zzgl. Versand</p>
	</div></div>
</footer>
<div class="cookie" id="cookie" role="dialog" aria-labelledby="ckTitle">
	<h3 id="ckTitle" style="margin-bottom:6px">Cookies und Statistik</h3>
	<p class="small" style="margin-bottom:12px">Notwendige Cookies brauchen wir für Warenkorb und Einstellungen. Mit Ihrer Erlaubnis nutzen wir zusätzlich Google Analytics, um die Website zu verbessern. Ihre Wahl können Sie jederzeit unter „Cookie-Einstellungen“ ändern. <a href="<?php echo esc_url( get_privacy_policy_url() ?: home_url( '/datenschutz/' ) ); ?>">Mehr erfahren</a></p>
	<div id="ckMore" hidden style="margin-bottom:12px">
		<label><input type="checkbox" checked disabled> Notwendig (immer aktiv)</label>
		<label><input type="checkbox" id="ckStats"> Statistik (Google Analytics 4)</label>
	</div>
	<div class="row"><button class="btn sm ghost" id="ckNec">Nur notwendige</button><button class="btn sm ghost" id="ckSet">Einstellungen</button><button class="btn sm" id="ckAll">Alle akzeptieren</button></div>
</div>
<?php wp_footer(); ?>
</body>
</html>
