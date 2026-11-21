<?php
defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_shipping_init', function () {
	class KF24_Shipping extends WC_Shipping_Method {
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'kf24_shipping';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = 'Küchenfront24 Versand';
			$this->method_description = 'Versand nach Gewicht (Paket/Spedition) und Musterversand. Beträge unter WooCommerce → Küchenfront24.';
			$this->supports           = [ 'shipping-zones', 'instance-settings' ];
			$this->title              = 'Versand';
			$this->enabled            = 'yes';
		}

		public function calculate_shipping( $package = [] ) {
			$country = $package['destination']['country'] ?? 'DE';
			$weight  = 0;
			$only_samples = true;
			foreach ( $package['contents'] as $item ) {
				$weight += kf24_item_weight( $item ) * $item['quantity'];
				if ( ! kf24_is_sample_product( $item['data'] ) ) {
					$only_samples = false;
				}
			}
			if ( $only_samples ) {
				$cost  = $country === 'CH' ? kf24_opt( 'ship_sample_ch' ) : kf24_opt( 'ship_sample_de' );
				$label = 'Musterversand';
			} elseif ( $country !== 'DE' ) {
				return; // Fronten ins Ausland nur auf Anfrage (siehe Hinweis im Warenkorb)
			} elseif ( $weight < 10 ) {
				$cost  = kf24_opt( 'ship_t1' );
				$label = 'Paket (UPS)';
			} elseif ( $weight < 20 ) {
				$cost  = kf24_opt( 'ship_t2' );
				$label = 'Paket (UPS)';
			} elseif ( $weight < 50 ) {
				$cost  = kf24_opt( 'ship_t3' );
				$label = 'Spedition';
			} else {
				$cost  = kf24_opt( 'ship_t4' );
				$label = 'Spedition';
			}
			$this->add_rate( [
				'id'    => $this->get_rate_id(),
				'label' => $label,
				'cost'  => kf24_net( (float) $cost ),
				'meta_data' => [ 'Gewicht (geschätzt)' => wc_format_localized_decimal( round( $weight, 1 ) ) . ' kg' ],
			] );
		}
	}
} );

add_filter( 'woocommerce_shipping_methods', function ( $m ) {
	$m['kf24_shipping'] = 'KF24_Shipping';
	return $m;
} );

add_filter( 'woocommerce_no_shipping_available_html', 'kf24_no_ship_msg' );
add_filter( 'woocommerce_cart_no_shipping_available_html', 'kf24_no_ship_msg' );
function kf24_no_ship_msg( $html ) {
	return '<p>Fronten liefern wir außerhalb Deutschlands nur auf Anfrage. Bitte rufen Sie uns an (' . esc_html( kf24_opt( 'phone' ) ) . ') oder <a href="' . esc_url( home_url( '/kontakt/' ) ) . '">schreiben Sie uns</a>. Muster versenden wir auch in die Schweiz.</p>';
}
