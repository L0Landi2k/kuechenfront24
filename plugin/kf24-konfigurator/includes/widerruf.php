<?php
/**
 * Elektronische Widerrufsfunktion nach § 356a BGB (seit 19.06.2026).
 * Schritt 1: Schaltfläche „Vertrag widerrufen“ (z. B. im Footer) führt auf die Seite mit [kf24_vertrag_widerrufen].
 * Schritt 2: Angaben (Name, Vertrag, E-Mail) und Bestätigung „Widerruf bestätigen“.
 * Danach: Eingangsbestätigung mit Inhalt, Datum und Uhrzeit per E-Mail.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'kf24_widerruf', [
		'label'           => 'Widerrufe',
		'labels'          => [ 'name' => 'Widerrufe', 'singular_name' => 'Widerruf' ],
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => 'woocommerce',
		'capability_type' => 'shop_order',
		'map_meta_cap'    => true,
		'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
		'supports'        => [ 'title', 'editor' ],
	] );
} );

add_shortcode( 'kf24_vertrag_widerrufen', function () {
	wp_enqueue_style( 'kf24' );
	if ( isset( $_POST['kf24_wr'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_kf24wr'] ?? '' ) ), 'kf24_wr' ) ) {
		$name     = sanitize_text_field( wp_unslash( $_POST['wr_name'] ?? '' ) );
		$contract = sanitize_text_field( wp_unslash( $_POST['wr_contract'] ?? '' ) );
		$mail     = sanitize_email( wp_unslash( $_POST['wr_mail'] ?? '' ) );
		if ( $name && $contract && is_email( $mail ) ) {
			$when = current_datetime();
			$ts   = wp_date( 'd.m.Y', $when->getTimestamp() ) . ' um ' . wp_date( 'H:i', $when->getTimestamp() ) . ' Uhr';
			$body = "Widerrufserklärung\n\nName: $name\nVertrag: $contract\nE-Mail: $mail\nEingang: $ts";
			wp_insert_post( [ 'post_type' => 'kf24_widerruf', 'post_status' => 'private', 'post_title' => "Widerruf $name – $contract", 'post_content' => $body ] );
			$shop = get_bloginfo( 'name' );
			wp_mail( $mail, 'Eingangsbestätigung Ihres Widerrufs – ' . $shop, "Guten Tag $name,\n\nwir bestätigen den Eingang Ihres Widerrufs.\n\nInhalt Ihrer Widerrufserklärung:\nVertrag: $contract\nName: $name\n\nEingegangen am $ts.\n\nWir melden uns zur weiteren Abwicklung.\n\n$shop" );
			wp_mail( kf24_opt( 'notify_email' ) ?: get_option( 'admin_email' ), 'Neuer Widerruf: ' . $contract, $body );
			return '<div class="kf24-ok"><h3>Ihr Widerruf ist eingegangen</h3><p>Name: ' . esc_html( $name ) . '<br>Vertrag: ' . esc_html( $contract ) . '<br>Eingang: ' . esc_html( $ts ) . '</p><p>Eine Eingangsbestätigung mit diesen Angaben haben wir an ' . esc_html( $mail ) . ' gesendet.</p></div>';
		}
		$err = '<div class="kf24-err-box">Bitte füllen Sie alle drei Felder aus und prüfen Sie die E-Mail-Adresse.</div>';
	}
	ob_start();
	echo $err ?? ''; // phpcs:ignore
	?>
	<form class="kf24-wr" method="post">
		<?php wp_nonce_field( 'kf24_wr', '_kf24wr' ); ?>
		<input type="hidden" name="kf24_wr" value="1">
		<label class="kf24-block">Ihr Name *<input name="wr_name" required autocomplete="name"></label>
		<label class="kf24-block">Bestellnummer oder Angaben zum Vertrag *<input name="wr_contract" required placeholder="z. B. Bestellung 10458 vom 3. November"></label>
		<label class="kf24-block">E-Mail für die Eingangsbestätigung *<input type="email" name="wr_mail" required autocomplete="email"></label>
		<button class="button alt" type="submit">Widerruf bestätigen</button>
	</form>
	<?php
	return ob_get_clean();
} );

/** Also offer the function in "Mein Konto" for logged-in customers. */
add_action( 'woocommerce_account_dashboard', function () {
	$page = get_page_by_path( 'vertrag-widerrufen' );
	if ( $page ) {
		echo '<p><a class="button" href="' . esc_url( get_permalink( $page ) ) . '">Vertrag widerrufen</a></p>';
	}
} );
