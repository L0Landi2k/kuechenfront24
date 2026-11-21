<?php
/**
 * Test mode for the review link (WordPress Playground):
 * banner, welcome, test checklist, feedback notes and a mailbox that catches all e-mails.
 * Active only when the option kf24_demo is set (the blueprint sets it). Never on the live site.
 */
defined( 'ABSPATH' ) || exit;

function kf24_demo() {
	return (bool) get_option( 'kf24_demo' );
}

/* Catch all mails instead of sending them. */
add_filter( 'pre_wp_mail', function ( $return, $atts ) {
	if ( ! kf24_demo() ) {
		return $return;
	}
	$box   = get_option( 'kf24_demo_mails', [] );
	$box   = is_array( $box ) ? $box : [];
	$to    = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'];
	$box[] = [ 't' => time(), 'to' => $to, 's' => (string) $atts['subject'], 'm' => (string) $atts['message'], 'att' => count( (array) $atts['attachments'] ) ];
	update_option( 'kf24_demo_mails', array_slice( $box, -40 ), false );
	return true;
}, 10, 2 );

add_action( 'admin_menu', function () {
	if ( kf24_demo() ) {
		add_submenu_page( 'kf24-shop', 'E-Mails (Testpostfach)', 'E-Mails (Test)', KF24_CAP, 'kf24-mails', 'kf24_page_mails' );
	}
}, 20 );

function kf24_page_mails() {
	$box = array_reverse( (array) get_option( 'kf24_demo_mails', [] ) );
	kf24_admin_head( 'E-Mails (Testpostfach)', 'In der Testversion wird nichts verschickt. Hier siehst du jede E-Mail, die der Shop an Kunden oder an dich senden würde.' );
	if ( ! $box ) {
		echo '<p class="kf24a-empty">Noch keine E-Mails. Bestell etwas im Shop, nutze das Kontaktformular oder „Vertrag widerrufen“ – dann erscheinen sie hier.</p></div>';
		return;
	}
	echo '<div class="kf24a-mails">';
	foreach ( $box as $i => $m ) {
		$html = stripos( $m['m'], '<html' ) !== false || stripos( $m['m'], '<table' ) !== false || stripos( $m['m'], '<p' ) !== false ? $m['m'] : '<pre style="white-space:pre-wrap;font:14px/1.5 sans-serif">' . esc_html( $m['m'] ) . '</pre>';
		printf(
			'<details class="kf24a-mail"%5$s><summary><b>%1$s</b><span>an %2$s · %3$s%4$s</span></summary><iframe sandbox="" srcdoc="%6$s" title="E-Mail-Inhalt"></iframe></details>',
			esc_html( $m['s'] ),
			esc_html( $m['to'] ),
			esc_html( wp_date( 'd.m.Y H:i', $m['t'] ) ),
			$m['att'] ? ' · ' . (int) $m['att'] . ' Anhang' : '',
			$i === 0 ? ' open' : '',
			esc_attr( $html )
		);
	}
	echo '</div></div>';
}

/* Assets everywhere (front + admin) in demo mode. */
function kf24_demo_assets() {
	if ( ! kf24_demo() ) {
		return;
	}
	wp_enqueue_style( 'kf24-demo', KF24_URL . 'assets/kf24-demo.css', [], KF24_VERSION );
	wp_enqueue_script( 'kf24-demo', KF24_URL . 'assets/kf24-demo.js', [], KF24_VERSION, true );
	$cfg   = kf24_all_cfg_products();
	$front = '';
	foreach ( $cfg as $c ) {
		if ( $c['group'] === 'Massivholz' ) {
			$front = $c['url'];
			break;
		}
	}
	$metod = get_page_by_path( 'massivholzfront-3-schicht-pg2-tuere', OBJECT, 'product' );
	$page  = fn( $slug ) => ( $p = get_page_by_path( $slug ) ) ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
	$steps = [
		[ 'Shop', 'Startseite ansehen – auch auf dem Handy', home_url( '/' ), 'Gefällt dir Optik, Text und Bilder? Tipp: Fenster schmal ziehen = Handy-Ansicht.' ],
		[ 'Shop', 'Eine Front konfigurieren und in den Warenkorb legen', $front ? $front . '#kf24-konfigurator' : wc_get_page_permalink( 'shop' ), 'Material wählen, Maße, Topfbänder, Griff – Zeichnung und Preis laufen live mit.' ],
		[ 'Shop', 'Preisrechner ausprobieren', $page( 'preisrechner' ), 'Mehrere Fronten auf einmal durchrechnen.' ],
		[ 'Shop', 'Muster in den Warenkorb legen', $page( 'muster' ), '' ],
		$metod ? [ 'Shop', 'IKEA-Metod-Front mit Varianten ansehen', get_permalink( $metod ), 'Wie bisher: Größe, Anschlag, Griff aus Listen wählen.' ] : null,
		[ 'Shop', 'Bestellung abschicken (Vorkasse)', wc_get_cart_url(), 'Fantasiedaten reichen. Es wird nichts bezahlt.' ],
		[ 'Shop', 'Kontaktformular senden', $page( 'kontakt' ), '' ],
		[ 'Shop', '„Vertrag widerrufen“ testen', $page( 'vertrag-widerrufen' ), 'Der neue Pflicht-Button seit Juni 2026.' ],
		[ 'Verwaltung', 'Die E-Mails dazu ansehen', admin_url( 'admin.php?page=kf24-mails' ), 'Bestellbestätigung, Widerruf, Kontakt.' ],
		[ 'Verwaltung', 'Bestellung öffnen und Fertigungsblatt drucken', kf24_orders_url(), '' ],
		[ 'Verwaltung', 'Neue Front anlegen', admin_url( 'admin.php?page=kf24-front' ), 'Name, Preis je m², Materialien anklicken, speichern – danach im Shop ansehen.' ],
		[ 'Verwaltung', 'Materialfoto per Drag & Drop austauschen', admin_url( 'admin.php?page=kf24-materialien' ), 'Ein Foto vom Rechner direkt auf ein Material ziehen.' ],
		[ 'Verwaltung', 'Front offline schalten, duplizieren, löschen', admin_url( 'admin.php?page=kf24-fronten' ), '' ],
		[ 'Verwaltung', 'Preise ändern (z. B. Topfband, Versand)', admin_url( 'admin.php?page=kf24-preise' ), '' ],
		[ 'Verwaltung', 'Text einer Seite ändern', admin_url( 'edit.php?post_type=page' ), 'z. B. „Über uns“ oder die FAQ.' ],
		[ 'Verwaltung', 'Referenz-Küche als Beitrag anlegen', admin_url( 'post-new.php' ), 'Titel, Text, Bilder – Kategorie „Referenzen“.' ],
		[ 'Verwaltung', 'Menü oder Fußzeile ändern', admin_url( 'nav-menus.php' ), 'Gilt sofort auf allen Seiten.' ],
	];
	wp_localize_script( 'kf24-demo', 'KF24DEMO', [
		'steps'   => array_values( array_filter( $steps ) ),
		'admin'   => is_admin(),
		'home'    => home_url( '/' ),
		'shop'    => admin_url( 'admin.php?page=kf24-shop' ),
		'phone'   => preg_replace( '/[^0-9]/', '', (string) get_option( 'kf24_demo_phone', '' ) ),
		'email'   => sanitize_email( (string) get_option( 'kf24_demo_email', '' ) ),
		'name'    => (string) get_option( 'kf24_demo_name', 'Andreas' ),
		'logged'  => is_user_logged_in(),
	] );
}
add_action( 'wp_enqueue_scripts', 'kf24_demo_assets', 30 );
add_action( 'admin_enqueue_scripts', 'kf24_demo_assets', 30 );

/* No robots, no tracking in test mode. */
add_action( 'wp_head', function () {
	if ( kf24_demo() ) {
		echo '<meta name="robots" content="noindex,nofollow">' . "\n";
	}
}, 1 );
