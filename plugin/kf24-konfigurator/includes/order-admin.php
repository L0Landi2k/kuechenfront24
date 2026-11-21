<?php
defined( 'ABSPATH' ) || exit;

/** Hide internal meta in admin item view. */
add_filter( 'woocommerce_hidden_order_itemmeta', function ( $h ) {
	$h[] = '_kf24_cfg';
	$h[] = '_kf24_m2';
	return $h;
} );

add_action( 'add_meta_boxes', function () {
	$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	add_meta_box( 'kf24-production', 'Fertigungsblatt', 'kf24_production_box', $screen, 'normal', 'default' );
} );

function kf24_production_box( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}
	$has = false;
	foreach ( $order->get_items() as $item ) {
		if ( $item->get_meta( '_kf24_cfg' ) ) {
			$has = true;
			break;
		}
	}
	if ( ! $has ) {
		echo '<p>Keine konfigurierten Fronten in dieser Bestellung.</p>';
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=kf24_sheet&order=' . $order->get_id() ), 'kf24_sheet' );
	echo '<p><a class="button button-primary" target="_blank" href="' . esc_url( $url ) . '">Fertigungsblatt drucken</a></p>';
	echo kf24_production_table( $order ); // phpcs:ignore
}

function kf24_production_table( $order ) {
	$o   = '<table class="widefat striped" style="border-collapse:collapse"><thead><tr><th>Pos.</th><th>Zeichnung</th><th>Front</th><th>Menge</th></tr></thead><tbody>';
	$pos = 0;
	foreach ( $order->get_items() as $item ) {
		$cfg = $item->get_meta( '_kf24_cfg' );
		if ( ! $cfg ) {
			continue;
		}
		$pos++;
		$pid  = $item->get_product_id();
		$fill = kf24_cfg_color( $cfg, $pid );
		$o   .= '<tr><td style="vertical-align:top"><b>' . $pos . '</b></td><td>' . kf24_svg( $cfg, $fill, 220, 260 ) . '</td><td style="vertical-align:top"><b>' . esc_html( $item->get_name() ) . '</b><br>';
		foreach ( kf24_spec_lines( $cfg ) as $k => $v ) {
			$o .= esc_html( $k ) . ': ' . esc_html( $v ) . '<br>';
		}
		$o .= '</td><td style="vertical-align:top;font-size:18px"><b>' . (int) $item->get_quantity() . '</b></td></tr>';
	}
	return $o . '</tbody></table>';
}

add_action( 'admin_post_kf24_sheet', function () {
	check_admin_referer( 'kf24_sheet' );
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_die( 'Keine Berechtigung' );
	}
	$order = wc_get_order( absint( $_GET['order'] ?? 0 ) );
	if ( ! $order ) {
		wp_die( 'Bestellung nicht gefunden' );
	}
	echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><title>Fertigungsblatt ' . esc_html( $order->get_order_number() ) . '</title><style>body{font:13px/1.45 Arial,sans-serif;margin:24px;color:#111}h1{font-size:20px;margin:0 0 4px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #bbb;padding:8px;text-align:left;vertical-align:top}tr{page-break-inside:avoid}@media print{button{display:none}}</style></head><body>';
	echo '<button onclick="print()" style="float:right;padding:8px 14px">Drucken</button>';
	echo '<h1>Fertigungsblatt – Bestellung ' . esc_html( $order->get_order_number() ) . '</h1>';
	echo '<p>' . esc_html( $order->get_formatted_billing_full_name() ) . ' · ' . esc_html( wc_format_datetime( $order->get_date_created() ) ) . ' · Status: ' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</p>';
	echo kf24_production_table( $order ); // phpcs:ignore
	echo '<p style="margin-top:16px">Topfbandbohrung Ø 35 mm. Positionen gemessen zur Mitte des Topfbands. Griffbohrungen: a = Abstand von oben bzw. unten, b = Abstand von der Öffnungskante.</p></body></html>';
	exit;
} );

/** Production details also in the admin "new order" e-mail. */
add_action( 'woocommerce_email_after_order_table', function ( $order, $sent_to_admin ) {
	if ( ! $sent_to_admin ) {
		return;
	}
	foreach ( $order->get_items() as $item ) {
		if ( $item->get_meta( '_kf24_cfg' ) ) {
			echo '<p><a href="' . esc_url( $order->get_edit_order_url() ) . '">Fertigungsblatt in der Bestellung öffnen</a></p>';
			return;
		}
	}
}, 10, 2 );
