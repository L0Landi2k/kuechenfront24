<?php
/**
 * Plugin Name: Küchenfront24 Konfigurator
 * Description: Fronten-Konfigurator mit m²-Preis, Live-Zeichnung und Plausibilitätsprüfung, Zuschläge, Versand nach Gewicht, Fertigungsblatt, Preisrechner, Muster, Kontakt und elektronische Widerrufsfunktion (§ 356a BGB).
 * Version: 1.6.2
 * Author: Küchenfront24
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * WC requires at least: 9.0
 * Text Domain: kf24
 */

defined( 'ABSPATH' ) || exit;

define( 'KF24_VERSION', '1.6.2' );
define( 'KF24_FILE', __FILE__ );
define( 'KF24_DIR', plugin_dir_path( __FILE__ ) );
define( 'KF24_URL', plugin_dir_url( __FILE__ ) );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
	}
} );

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>Küchenfront24 Konfigurator benötigt WooCommerce.</p></div>';
		} );
		return;
	}
	foreach ( [ 'helpers', 'materials', 'settings', 'product-admin', 'frontend', 'cart', 'fees', 'shipping', 'order-admin', 'shortcodes', 'widerruf', 'setup', 'owner', 'catalog', 'demo', 'aufmass', 'samples', 'references' ] as $f ) {
		require_once KF24_DIR . 'includes/' . $f . '.php';
	}
}, 20 );
