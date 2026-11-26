<?php
/**
 * "Mein Shop": a simple store-owner backend.
 * Overview, fronts (create / edit / duplicate / on-off / delete), material library with drag & drop photos,
 * a "Shop-Inhaber" role and a tidy admin menu for that role.
 */
defined( 'ABSPATH' ) || exit;

const KF24_CAP = 'manage_woocommerce';

/* -------------------------------------------------------------------------
 * Role + menu
 * ---------------------------------------------------------------------- */
function kf24_add_owner_role() {
	$base = get_role( 'shop_manager' );
	$caps = $base ? $base->capabilities : [ 'read' => true, 'manage_woocommerce' => true ];
	$caps['edit_theme_options'] = true;
	remove_role( 'kf24_owner' );
	add_role( 'kf24_owner', 'Shop-Inhaber', $caps );
}
add_action( 'init', function () {
	if ( get_option( 'kf24_role_v' ) !== '1' && get_role( 'shop_manager' ) ) {
		kf24_add_owner_role();
		update_option( 'kf24_role_v', '1' );
	}
} );

function kf24_is_simple_user() {
	$u = wp_get_current_user();
	return in_array( 'kf24_owner', (array) $u->roles, true ) || get_user_meta( $u->ID, 'kf24_simple', true ) === '1';
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Mein Shop', 'Mein Shop', KF24_CAP, 'kf24-shop', 'kf24_page_overview', 'dashicons-store', 2 );
	add_submenu_page( 'kf24-shop', 'Übersicht', 'Übersicht', KF24_CAP, 'kf24-shop', 'kf24_page_overview' );
	add_submenu_page( 'kf24-shop', 'Fronten', 'Fronten', KF24_CAP, 'kf24-fronten', 'kf24_page_fronts' );
	add_submenu_page( 'kf24-shop', 'Front bearbeiten', 'Neue Front', KF24_CAP, 'kf24-front', 'kf24_page_front_edit' );
	add_submenu_page( 'kf24-shop', 'Materialien & Fotos', 'Materialien & Fotos', KF24_CAP, 'kf24-materialien', 'kf24_page_materials' );
	add_submenu_page( 'kf24-shop', 'Preise, Versand & Zuschläge', 'Preise & Versand', KF24_CAP, 'kf24-preise', 'kf24_settings_page' );
}, 9 );

/** Tidy menu for the store owner. */
add_action( 'admin_menu', function () {
	if ( ! kf24_is_simple_user() ) {
		return;
	}
	foreach ( [ 'index.php', 'edit-comments.php', 'tools.php', 'woocommerce-marketing', 'wc-admin&path=/analytics/overview', 'wc-admin&path=/marketing', 'options-general.php' ] as $m ) {
		remove_menu_page( $m );
	}
	global $menu;
	foreach ( (array) $menu as $k => $item ) { // WooCommerce "Payments" entry and similar promos
		if ( isset( $item[2] ) && ( strpos( $item[2], 'task=payments' ) !== false || strpos( $item[2], 'wc-settings&tab=checkout' ) !== false || strpos( $item[2], 'woocommerce-payments' ) !== false ) ) {
			unset( $menu[ $k ] );
		}
	}
	remove_submenu_page( 'woocommerce', 'wc-admin' );
	remove_submenu_page( 'woocommerce', 'wc-status' );
	remove_submenu_page( 'woocommerce', 'wc-addons' );
	remove_submenu_page( 'woocommerce', 'kf24-settings' );
}, 999 );

/** Dashboard → Mein Shop for owners; login lands there too. */
add_action( 'load-index.php', function () {
	if ( kf24_is_simple_user() || get_option( 'kf24_demo' ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=kf24-shop' ) );
		exit;
	}
} );
add_filter( 'login_redirect', function ( $to, $req, $user ) {
	if ( $user instanceof WP_User && in_array( 'kf24_owner', (array) $user->roles, true ) ) {
		return admin_url( 'admin.php?page=kf24-shop' );
	}
	return $to;
}, 10, 3 );
add_filter( 'woocommerce_prevent_admin_access', '__return_false' );
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only decides which assets to load
	if ( strpos( $page, 'kf24-' ) !== 0 ) {
		return;
	}
	wp_enqueue_style( 'kf24-admin', KF24_URL . 'assets/kf24-admin.css', [], KF24_VERSION );
	wp_enqueue_script( 'kf24-admin', KF24_URL . 'assets/kf24-admin.js', [], KF24_VERSION, true );
	wp_localize_script( 'kf24-admin', 'KF24ADM', [
		'ajax'   => admin_url( 'admin-ajax.php' ),
		'nonce'  => wp_create_nonce( 'kf24_adm' ),
		'groups' => kf24_material_groups(),
	] );
} );

function kf24_admin_head( $title, $sub = '', $actions = '' ) {
	echo '<div class="wrap kf24a"><div class="kf24a-head"><div><h1>' . esc_html( $title ) . '</h1>' . ( $sub ? '<p class="kf24a-sub">' . wp_kses_post( $sub ) . '</p>' : '' ) . '</div><div class="kf24a-actions">' . $actions . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $actions built from escaped parts
}

/* -------------------------------------------------------------------------
 * Overview
 * ---------------------------------------------------------------------- */
function kf24_page_overview() {
	$u      = wp_get_current_user();
	$open   = wc_get_orders( [ 'status' => [ 'processing', 'on-hold' ], 'limit' => -1, 'return' => 'ids' ] );
	$recent = wc_get_orders( [ 'limit' => 6, 'orderby' => 'date', 'order' => 'DESC' ] );
	$sum    = 0;
	foreach ( wc_get_orders( [ 'status' => [ 'processing', 'completed', 'on-hold' ], 'date_created' => '>' . ( time() - 30 * DAY_IN_SECONDS ), 'limit' => -1 ] ) as $o ) {
		$sum += (float) $o->get_total();
	}
	$fronts = get_posts( [ 'post_type' => 'product', 'post_status' => [ 'publish' ], 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_kf24_enabled', 'meta_value' => 'yes' ] );
	$mats   = kf24_materials();
	$photos = count( array_filter( $mats, fn( $m ) => $m['photo'] ) );

	kf24_admin_head( 'Hallo ' . ( $u->first_name ?: $u->display_name ) . '!', 'Hier steuerst du deinen Shop. Alles Wichtige ist einen Klick entfernt.', '<a class="button button-hero" href="' . esc_url( home_url( '/' ) ) . '" target="_blank">Shop ansehen ↗</a>' );
	echo '<div class="kf24a-stats">';
	printf( '<a class="kf24a-stat" href="%s"><b>%d</b><span>offene Bestellungen</span></a>', esc_url( kf24_orders_url() ), count( $open ) );
	printf( '<div class="kf24a-stat"><b>%s</b><span>Umsatz letzte 30 Tage</span></div>', wp_kses_post( wc_price( $sum ) ) );
	printf( '<a class="kf24a-stat" href="%s"><b>%d</b><span>Fronten online</span></a>', esc_url( admin_url( 'admin.php?page=kf24-fronten' ) ), count( $fronts ) );
	printf( '<a class="kf24a-stat" href="%s"><b>%d / %d</b><span>Materialien mit eigenem Foto</span></a>', esc_url( admin_url( 'admin.php?page=kf24-materialien' ) ), (int) $photos, count( $mats ) );
	echo '</div>';

	$tiles = [
		[ 'admin.php?page=kf24-front', 'plus-alt', 'Neue Front anlegen', 'Name, Preis je m², Materialien – fertig.' ],
		[ 'admin.php?page=kf24-materialien', 'format-image', 'Materialien & Fotos', 'Fotos per Drag & Drop tauschen, Farben anlegen.' ],
		[ kf24_orders_url(), 'cart', 'Bestellungen', 'Bestellungen ansehen, Fertigungsblatt drucken.' ],
		[ 'admin.php?page=kf24-fronten', 'grid-view', 'Fronten verwalten', 'Ein-/ausschalten, duplizieren, löschen.' ],
		[ 'admin.php?page=kf24-preise', 'money-alt', 'Preise & Versand', 'Topfbänder, Oberfläche geölt, Zuschläge, Versand, Maße.' ],
		[ 'admin.php?page=kf24-systeme', 'calculator', 'Metod & Faktum', 'Eine Teileliste, Preisformel, Festpreise – statt hunderter Produkte.' ],
		[ 'edit.php?post_type=page', 'admin-page', 'Seiten & Texte', 'Über uns, FAQ, Service, Rechtstexte.' ],
		[ 'admin.php?page=kf24-referenzen', 'format-gallery', 'Referenzen', 'Kundenküchen mit Fotos und Vorher/Nachher zeigen.' ],
		[ 'nav-menus.php', 'menu', 'Menü & Fußzeile', 'Einmal ändern – gilt auf allen Seiten.' ],
		[ 'edit.php?post_type=product', 'products', 'Weitere Produkte', 'Muster, Zubehör und alles, was kein Konfigurator ist.' ],
		[ 'customize.php', 'admin-appearance', 'Kontaktdaten & Startseite', 'Telefon, E-Mail, Ausstellung, Google Analytics.' ],
	];
	if ( get_option( 'kf24_demo' ) ) {
		$tiles[] = [ 'admin.php?page=kf24-mails', 'email-alt', 'E-Mails (Testpostfach)', 'Alle Mails, die der Shop verschicken würde.' ];
	}
	echo '<div class="kf24a-tiles">';
	foreach ( $tiles as $t ) {
		$url = strpos( $t[0], 'http' ) === 0 ? $t[0] : admin_url( $t[0] );
		printf( '<a class="kf24a-tile" href="%s"><span class="dashicons dashicons-%s"></span><b>%s</b><small>%s</small></a>', esc_url( $url ), esc_attr( $t[1] ), esc_html( $t[2] ), esc_html( $t[3] ) );
	}
	echo '</div>';

	echo '<h2 class="kf24a-h2">Letzte Bestellungen</h2>';
	if ( ! $recent ) {
		echo '<p class="kf24a-empty">Noch keine Bestellungen. Bestell doch mal testweise eine Front im Shop.</p>';
	} else {
		echo '<table class="widefat striped kf24a-table"><thead><tr><th>Nr.</th><th>Datum</th><th>Kunde</th><th>Status</th><th class="n">Summe</th><th></th></tr></thead><tbody>';
		foreach ( $recent as $o ) {
			if ( ! $o instanceof WC_Order ) {
				continue;
			}
			printf(
				'<tr><td><a href="%1$s"><b>#%2$s</b></a></td><td>%3$s</td><td>%4$s</td><td><span class="kf24a-badge s-%5$s">%6$s</span></td><td class="n">%7$s</td><td><a class="button" href="%1$s">Öffnen</a> <a class="button" target="_blank" href="%8$s">Fertigungsblatt</a></td></tr>',
				esc_url( $o->get_edit_order_url() ),
				esc_html( $o->get_order_number() ),
				esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'd.m.Y H:i' ) : '' ),
				esc_html( trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ) ),
				esc_attr( $o->get_status() ),
				esc_html( wc_get_order_status_name( $o->get_status() ) ),
				wp_kses_post( $o->get_formatted_order_total() ),
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kf24_sheet&order=' . $o->get_id() ), 'kf24_sheet' ) )
			);
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

function kf24_orders_url() {
	return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ? admin_url( 'admin.php?page=wc-orders' ) : admin_url( 'edit.php?post_type=shop_order' );
}

/* -------------------------------------------------------------------------
 * Fronts list
 * ---------------------------------------------------------------------- */
function kf24_front_ids( $status = [ 'publish', 'draft', 'pending', 'private' ] ) {
	return get_posts( [ 'post_type' => 'product', 'post_status' => $status, 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_kf24_enabled', 'meta_value' => 'yes' ] );
}

function kf24_group_labels() {
	return [ 'Dekor' => 'Dekor', 'Furnier' => 'Echtholz furniert', 'Massivholz' => 'Massivholz', 'Lack' => 'Lack', 'Fenix' => 'Fenix NTM' ];
}

function kf24_page_fronts() {
	$ids   = kf24_front_ids();
	$order = array_flip( array_keys( kf24_group_labels() ) );
	usort( $ids, function ( $a, $b ) use ( $order ) {
		return [ $order[ get_post_meta( $a, '_kf24_group', true ) ] ?? 9, kf24_num( get_post_meta( $a, '_kf24_m2', true ) ) ] <=> [ $order[ get_post_meta( $b, '_kf24_group', true ) ] ?? 9, kf24_num( get_post_meta( $b, '_kf24_m2', true ) ) ];
	} );
	kf24_admin_head( 'Fronten', count( $ids ) . ' Fronten im Konfigurator. Schalter aus = im Shop unsichtbar.', '<a class="button button-primary button-hero" href="' . esc_url( admin_url( 'admin.php?page=kf24-front' ) ) . '">+ Neue Front</a>' );
	kf24_notice_from_query();
	echo '<input type="search" class="kf24a-search" placeholder="Front suchen …" data-filter=".kf24a-front">';
	echo '<div class="kf24a-fronts">';
	foreach ( $ids as $id ) {
		$p    = wc_get_product( $id );
		$look = $p->get_image_id() ? [ 'img' => wp_get_attachment_image_url( $p->get_image_id(), 'thumbnail' ), 'color' => '#ddd' ] : kf24_product_look( $id );
		$opts = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $id, '_kf24_options', true ) ) ) );
		$on   = get_post_status( $id ) === 'publish';
		$edit = admin_url( 'admin.php?page=kf24-front&id=' . $id );
		printf(
			'<div class="kf24a-front%9$s" data-id="%1$d" data-name="%2$s"><a class="kf24a-front-pic" href="%3$s" style="%4$s"></a><div class="kf24a-front-b"><a class="kf24a-front-name" href="%3$s">%2$s</a><span class="kf24a-meta">%5$s · %6$s je m² · %7$d Materialien</span></div><label class="kf24a-switch" title="Im Shop sichtbar"><input type="checkbox" class="kf24a-status" %8$s><span></span><em>%10$s</em></label><div class="kf24a-front-act"><a class="button" href="%3$s">Bearbeiten</a> <button type="button" class="button kf24a-dup">Duplizieren</button> <a class="button" href="%11$s" target="_blank">Ansehen</a> <button type="button" class="button-link kf24a-del">Löschen</button></div></div>',
			(int) $id,
			esc_html( $p->get_name() ),
			esc_url( $edit ),
			esc_attr( $look['img'] ? 'background-image:url(' . $look['img'] . ')' : 'background:' . $look['color'] ),
			esc_html( kf24_group_labels()[ get_post_meta( $id, '_kf24_group', true ) ] ?? '–' ),
			wp_kses_post( wc_price( kf24_num( get_post_meta( $id, '_kf24_m2', true ) ) ) ),
			count( $opts ),
			checked( $on, true, false ),
			$on ? '' : ' is-off',
			$on ? 'online' : 'offline',
			esc_url( get_permalink( $id ) )
		);
	}
	echo '</div><p class="kf24a-empty" hidden>Keine Front gefunden.</p></div>';
}

function kf24_notice_from_query() {
	$m = [ 'saved' => 'Gespeichert.', 'deleted' => 'Gelöscht. Im Papierkorb unter „Produkte“ kannst du es wiederherstellen.', 'dup' => 'Kopie angelegt – sie ist noch offline.' ];
	$k = sanitize_key( $_GET['kf24'] ?? '' );
	if ( isset( $m[ $k ] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $m[ $k ] ) . '</p></div>';
	}
}

/* -------------------------------------------------------------------------
 * Front editor
 * ---------------------------------------------------------------------- */
function kf24_page_front_edit() {
	$id = absint( $_GET['id'] ?? 0 );
	$p  = $id ? wc_get_product( $id ) : null;
	if ( $id && ! $p ) {
		echo '<div class="wrap"><p>Front nicht gefunden.</p></div>';
		return;
	}
	$c        = $id ? kf24_product_cfg( $id ) : [ 'name' => '', 'm2' => '', 'group' => 'Dekor', 'field' => 'Dekor', 'options' => [], 'lz' => 'ca. 4 Wochen', 'thickness' => 19, 'kgm2' => 13, 'milling' => false, 'finish' => false, 'sample' => '' ];
	$selected = $c['options'];
	$mats     = array_values( kf24_materials() );
	$samples  = wc_get_products( [ 'category' => [ kf24_opt( 'sample_cat' ) ], 'limit' => 200, 'status' => [ 'publish', 'draft' ] ] );
	$img_id   = $p ? $p->get_image_id() : 0;
	$on       = $p ? $p->get_status() === 'publish' : true;

	kf24_admin_head( $id ? $p->get_name() : 'Neue Front', $id ? 'Änderungen sind nach „Speichern“ sofort im Shop sichtbar.' : 'Fülle die Felder aus und wähle die Materialien. Mehr braucht es nicht.', $id ? '<a class="button" target="_blank" href="' . esc_url( get_permalink( $id ) ) . '">Im Shop ansehen ↗</a>' : '' );
	kf24_notice_from_query();
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kf24a-form" id="kf24a-front-form">
		<input type="hidden" name="action" value="kf24_front_save">
		<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
		<?php wp_nonce_field( 'kf24_front_save' ); ?>
		<div class="kf24a-cols">
			<div class="kf24a-main">
				<section class="kf24a-card">
					<h2>1. Grunddaten</h2>
					<label class="kf24a-f"><span>Name im Shop *</span><input type="text" name="name" required value="<?php echo esc_attr( $id ? $p->get_name() : '' ); ?>" placeholder="z. B. Massivholzfront Eiche – individuell konfigurierbar"></label>
					<div class="kf24a-row">
						<label class="kf24a-f"><span>Frontart</span><select name="group" id="kf24a-group"><?php foreach ( kf24_group_labels() as $k => $l ) { printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $c['group'], $k, false ), esc_html( $l ) ); } ?></select></label>
						<label class="kf24a-f"><span>Preis je m² (inkl. MwSt.) *</span><input type="text" inputmode="decimal" name="m2" required value="<?php echo esc_attr( $c['m2'] !== '' ? wc_format_localized_decimal( $c['m2'] ) : '' ); ?>" placeholder="z. B. 306"></label>
					</div>
					<div class="kf24a-row">
						<label class="kf24a-f"><span>Lieferzeit</span><input type="text" name="lz" value="<?php echo esc_attr( $c['lz'] ); ?>" placeholder="ca. 6 Wochen"></label>
						<label class="kf24a-f"><span>Stärke (mm)</span><input type="number" name="thickness" value="<?php echo esc_attr( $c['thickness'] ); ?>" min="8" max="60"></label>
						<label class="kf24a-f"><span>Gewicht je m² (kg)</span><input type="text" inputmode="decimal" name="kgm2" value="<?php echo esc_attr( wc_format_localized_decimal( $c['kgm2'] ) ); ?>"></label>
					</div>
					<label class="kf24a-check"><input type="checkbox" name="milling" value="1" <?php checked( $c['milling'] ); ?>> Grifffräsung möglich (Aufpreis lt. „Preise &amp; Versand“)</label>
					<label class="kf24a-check"><input type="checkbox" name="finish" value="1" <?php checked( ! empty( $c['finish'] ) ); ?>> Kunde wählt Oberfläche: Klarlack oder geölt (Aufpreis lt. „Preise &amp; Versand“)</label>
				</section>

				<section class="kf24a-card">
					<h2>2. Materialien <output id="kf24a-count"><?php echo count( $selected ); ?></output> ausgewählt</h2>
					<p class="kf24a-hint">Klick auf ein Material = auswählen. Der Kunde sieht genau diese Auswahl mit Bild. Fotos tauschst du unter <a href="<?php echo esc_url( admin_url( 'admin.php?page=kf24-materialien' ) ); ?>">Materialien &amp; Fotos</a>.</p>
					<label class="kf24a-f kf24a-inline"><span>Überschrift im Konfigurator</span><input type="text" name="field" id="kf24a-field" value="<?php echo esc_attr( $c['field'] ); ?>" placeholder="z. B. Holzart"></label>
					<div class="kf24a-picker" data-selected="<?php echo esc_attr( wp_json_encode( $selected ) ); ?>" data-mats="<?php echo esc_attr( wp_json_encode( array_map( fn( $m ) => [ 'n' => $m['name'], 'g' => $m['group'], 'i' => $m['img'], 'c' => $m['color'] ], $mats ) ) ); ?>">
						<div class="kf24a-chips"></div>
						<div class="kf24a-pick-tools"><input type="search" class="kf24a-pick-search" placeholder="Material suchen …"> <button type="button" class="button kf24a-pick-all">Alle angezeigten wählen</button> <button type="button" class="button kf24a-pick-none">Angezeigte abwählen</button> <label class="kf24a-check"><input type="checkbox" class="kf24a-pick-only"> nur ausgewählte zeigen</label></div>
						<div class="kf24a-pick-grid" role="group" aria-label="Materialien"></div>
					</div>
					<textarea name="options" id="kf24a-options" hidden><?php echo esc_textarea( implode( "\n", $selected ) ); ?></textarea>
				</section>

				<section class="kf24a-card">
					<h2>3. Texte</h2>
					<label class="kf24a-f"><span>Kurzbeschreibung (neben dem Bild)</span><textarea name="short" rows="3"><?php echo esc_textarea( $p ? $p->get_short_description() : 'Individuelle Front nach Maß, passend für jeden Korpus. Maße millimetergenau, Bohrungen für Topfbänder und Griffe nach Ihren Angaben.' ); ?></textarea></label>
					<div class="kf24a-f"><span>Ausführliche Beschreibung</span><?php wp_editor( $p ? $p->get_description() : '', 'kf24a_desc', [ 'textarea_name' => 'desc', 'media_buttons' => false, 'teeny' => true, 'textarea_rows' => 8 ] ); ?></div>
				</section>
			</div>

			<aside class="kf24a-side">
				<section class="kf24a-card kf24a-sticky">
					<label class="kf24a-switch kf24a-switch-big"><input type="checkbox" name="online" value="1" <?php checked( $on ); ?>><span></span><em>Im Shop sichtbar</em></label>
					<button class="button button-primary button-hero kf24a-save" type="submit">Speichern</button>
					<?php if ( $id ) : ?><button type="button" class="button-link kf24a-del-one" data-id="<?php echo (int) $id; ?>">Front löschen</button><?php endif; ?>
				</section>
				<section class="kf24a-card">
					<h2>Produktbild</h2>
					<div class="kf24a-drop kf24a-drop-product" data-target="image_id" tabindex="0" role="button" aria-label="Produktbild hochladen">
						<div class="kf24a-drop-pic" style="<?php echo $img_id ? 'background-image:url(' . esc_url( wp_get_attachment_image_url( $img_id, 'medium' ) ) . ')' : ''; ?>"></div>
						<span>Bild hierher ziehen<br><small>oder klicken zum Auswählen</small></span>
					</div>
					<input type="hidden" name="image_id" value="<?php echo (int) $img_id; ?>">
					<button type="button" class="button-link kf24a-img-remove" <?php echo $img_id ? '' : 'hidden'; ?>>Bild entfernen</button>
					<p class="kf24a-hint">Ohne eigenes Bild zeigt der Shop das erste Material.</p>
				</section>
				<section class="kf24a-card">
					<h2>Muster</h2>
					<label class="kf24a-f"><span>Passendes Muster-Produkt</span><select name="sample"><option value="">– kein Muster –</option><?php foreach ( $samples as $s ) { printf( '<option value="%s"%s>%s (%s)</option>', esc_attr( $s->get_slug() ), selected( $c['sample'], $s->get_slug(), false ), esc_html( $s->get_name() ), esc_html( wp_strip_all_tags( wc_price( $s->get_price() ) ) ) ); } ?></select></label>
				</section>
			</aside>
		</div>
	</form>
	<?php
	echo '</div>';
}

add_action( 'admin_post_kf24_front_save', function () {
	check_admin_referer( 'kf24_front_save' );
	if ( ! current_user_can( KF24_CAP ) ) {
		wp_die( 'Keine Berechtigung' );
	}
	$in   = wp_unslash( $_POST );
	$id   = absint( $in['id'] ?? 0 );
	$p    = $id ? wc_get_product( $id ) : new WC_Product_Simple();
	$name = sanitize_text_field( $in['name'] ?? '' ) ?: 'Neue Front';
	$m2   = kf24_num( $in['m2'] ?? 0 );
	$p->set_name( $name );
	$p->set_regular_price( wc_format_decimal( $m2 ) );
	$p->set_short_description( wp_kses_post( $in['short'] ?? '' ) );
	$p->set_description( wp_kses_post( $in['desc'] ?? '' ) );
	$p->set_status( ! empty( $in['online'] ) ? 'publish' : 'draft' );
	$p->set_image_id( absint( $in['image_id'] ?? 0 ) );
	$p->set_sold_individually( false );
	if ( ! $id ) {
		$fc = kf24_term( 'Fronten nach Maß', 'fronten-nach-mass' );
		$p->set_category_ids( [ $fc ] );
	}
	$id      = $p->save();
	$options = array_values( array_unique( array_filter( array_map( 'trim', explode( "\n", sanitize_textarea_field( $in['options'] ?? '' ) ) ) ) ) );
	$meta    = [
		'_kf24_enabled'   => 'yes',
		'_kf24_m2'        => $m2,
		'_kf24_group'     => array_key_exists( $in['group'] ?? '', kf24_group_labels() ) ? $in['group'] : 'Dekor',
		'_kf24_field'     => sanitize_text_field( $in['field'] ?? '' ) ?: 'Dekor',
		'_kf24_options'   => implode( "\n", $options ),
		'_kf24_lz'        => sanitize_text_field( $in['lz'] ?? '' ),
		'_kf24_thickness' => absint( $in['thickness'] ?? 19 ) ?: 19,
		'_kf24_kgm2'      => kf24_num( $in['kgm2'] ?? 0 ) ?: (float) kf24_opt( 'kgm2_default' ),
		'_kf24_milling'   => ! empty( $in['milling'] ) ? 'yes' : 'no',
		'_kf24_finish'    => ! empty( $in['finish'] ) ? 'yes' : 'no',
		'_kf24_sample'    => sanitize_title( $in['sample'] ?? '' ),
	];
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	kf24_ensure_materials( $options );
	wp_safe_redirect( admin_url( 'admin.php?page=kf24-front&id=' . $id . '&kf24=saved' ) );
	exit;
} );

/* -------------------------------------------------------------------------
 * Material library
 * ---------------------------------------------------------------------- */
function kf24_page_materials() {
	$mats  = kf24_materials();
	$usage = [];
	foreach ( kf24_front_ids() as $pid ) {
		foreach ( array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $pid, '_kf24_options', true ) ) ) ) as $n ) {
			$usage[ $n ][] = get_the_title( $pid );
		}
	}
	$data = array_map( fn( $m ) => $m + [ 'used' => $usage[ $m['name'] ] ?? [] ], array_values( $mats ) );
	kf24_admin_head( 'Materialien & Fotos', 'Zieh ein Foto direkt auf ein Material, um es auszutauschen. Klick öffnet die Details.', '<button type="button" class="button button-primary button-hero kf24a-mat-new">+ Material anlegen</button>' );
	?>
	<div class="kf24a-matbar">
		<input type="search" class="kf24a-mat-search" placeholder="Suchen: Name oder Nummer, z. B. RAL 7016 oder Eiche">
		<div class="kf24a-mat-groups"></div>
		<label class="kf24a-check"><input type="checkbox" class="kf24a-mat-ph"> nur Platzhalter-Bilder</label>
	</div>
	<div class="kf24a-bulk">
		<div class="kf24a-drop kf24a-drop-bulk" tabindex="0" role="button"><span><b>Mehrere neue Materialien auf einmal:</b> Fotos hierher ziehen<br><small>Der Dateiname wird zum Namen, z. B. „H1180 Halifax Eiche natur.jpg“. Gleicher Name = Foto wird ersetzt.</small></span></div>
		<label class="kf24a-f"><span>Gruppe für neue Materialien</span><select class="kf24a-bulk-group"><?php foreach ( kf24_material_groups() as $g ) { echo '<option>' . esc_html( $g ) . '</option>'; } ?></select></label>
	</div>
	<div class="kf24a-progress" hidden></div>
	<div class="kf24a-mats" data-mats="<?php echo esc_attr( wp_json_encode( $data ) ); ?>"></div>
	<p class="kf24a-empty" hidden>Kein Material gefunden.</p>

	<dialog class="kf24a-dialog" id="kf24a-mat-dialog">
		<form method="dialog" class="kf24a-dlg-form">
			<h2 class="kf24a-dlg-title">Material</h2>
			<div class="kf24a-dlg-cols">
				<div>
					<div class="kf24a-drop kf24a-drop-mat" tabindex="0" role="button" aria-label="Foto hochladen"><div class="kf24a-drop-pic"></div><span>Foto hierher ziehen<br><small>oder klicken</small></span></div>
					<button type="button" class="button-link kf24a-photo-remove">Eigenes Foto entfernen</button>
				</div>
				<div>
					<label class="kf24a-f"><span>Name (so sieht ihn der Kunde) *</span><input name="name" required></label>
					<div class="kf24a-row">
						<label class="kf24a-f"><span>Nummer / Code</span><input name="code" placeholder="z. B. H1180, RAL 7016"></label>
						<label class="kf24a-f"><span>Gruppe</span><select name="group"><?php foreach ( kf24_material_groups() as $g ) { echo '<option>' . esc_html( $g ) . '</option>'; } ?></select></label>
					</div>
					<label class="kf24a-f"><span>Farbe (wenn kein Foto)</span><input type="color" name="color"></label>
					<p class="kf24a-used"></p>
				</div>
			</div>
			<div class="kf24a-dlg-foot">
				<button type="button" class="button-link kf24a-mat-delete">Material löschen</button>
				<span class="kf24a-spacer"></span>
				<button type="button" class="button kf24a-dlg-close">Abbrechen</button>
				<button type="button" class="button button-primary kf24a-mat-save">Speichern</button>
			</div>
		</form>
	</dialog>
	<input type="file" accept="image/*" class="kf24a-file" hidden>
	<input type="file" accept="image/*" multiple class="kf24a-files" hidden>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * AJAX – every handler calls kf24_ajax_guard() first (nonce + capability).
 * ---------------------------------------------------------------------- */
// phpcs:disable WordPress.Security.NonceVerification.Missing
function kf24_ajax_guard() {
	check_ajax_referer( 'kf24_adm', 'nonce' );
	if ( ! current_user_can( KF24_CAP ) ) {
		wp_send_json_error( [ 'msg' => 'Keine Berechtigung.' ], 403 );
	}
}

function kf24_ajax_image_ok( $key ) {
	if ( empty( $_FILES[ $key ]['tmp_name'] ) || empty( $_FILES[ $key ]['name'] ) || ! is_uploaded_file( $_FILES[ $key ]['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- tmp path from PHP
		return new WP_Error( 'nofile', 'Keine Datei empfangen.' );
	}
	$type = wp_check_filetype_and_ext( $_FILES[ $key ]['tmp_name'], sanitize_file_name( wp_unslash( $_FILES[ $key ]['name'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp path from PHP
	if ( ! in_array( $type['type'], [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ], true ) ) {
		return new WP_Error( 'type', 'Bitte ein Bild (JPG, PNG oder WebP) verwenden.' );
	}
	return true;
}

add_action( 'wp_ajax_kf24_mat_save', function () {
	kf24_ajax_guard();
	$in = wp_unslash( $_POST );
	$id = absint( $in['id'] ?? 0 );
	$r  = kf24_material_save( [ 'name' => $in['name'] ?? '', 'code' => $in['code'] ?? '', 'group' => $in['group'] ?? '', 'color' => $in['color'] ?? '' ], $id );
	if ( is_wp_error( $r ) ) {
		wp_send_json_error( [ 'msg' => $r->get_error_message() ] );
	}
	if ( ! empty( $_FILES['file']['tmp_name'] ) ) {
		$ok = kf24_ajax_image_ok( 'file' );
		if ( is_wp_error( $ok ) ) {
			wp_send_json_error( [ 'msg' => $ok->get_error_message() ] );
		}
		kf24_material_set_photo( $r, 'file' );
	}
	wp_send_json_success( kf24_material_data( $r ) + [ 'used' => array_values( kf24_material_usage( get_the_title( $r ) ) ) ] );
} );

add_action( 'wp_ajax_kf24_mat_photo', function () {
	kf24_ajax_guard();
	$id = absint( $_POST['id'] ?? 0 );
	if ( get_post_type( $id ) !== 'kf24_material' ) {
		wp_send_json_error( [ 'msg' => 'Material nicht gefunden.' ] );
	}
	$ok = kf24_ajax_image_ok( 'file' );
	if ( is_wp_error( $ok ) ) {
		wp_send_json_error( [ 'msg' => $ok->get_error_message() ] );
	}
	$att = kf24_material_set_photo( $id, 'file' );
	if ( is_wp_error( $att ) ) {
		wp_send_json_error( [ 'msg' => $att->get_error_message() ] );
	}
	wp_send_json_success( kf24_material_data( $id ) );
} );

add_action( 'wp_ajax_kf24_mat_photo_remove', function () {
	kf24_ajax_guard();
	$id = absint( $_POST['id'] ?? 0 );
	delete_post_thumbnail( $id );
	$tex = get_post_meta( $id, '_kf24_tex', true );
	$src = json_decode( (string) file_get_contents( KF24_DIR . 'data/materials.json' ), true ) ?: [];
	foreach ( $src as $m ) {
		if ( $m['tex'] && $m['tex'] === $tex ) {
			update_post_meta( $id, '_kf24_color', $m['color'] );
		}
	}
	kf24_materials_flush();
	wp_send_json_success( kf24_material_data( $id ) );
} );

add_action( 'wp_ajax_kf24_mat_bulk', function () {
	kf24_ajax_guard();
	$ok = kf24_ajax_image_ok( 'file' );
	if ( is_wp_error( $ok ) ) {
		wp_send_json_error( [ 'msg' => $ok->get_error_message() ] );
	}
	$name  = trim( preg_replace( '/[_]+/', ' ', pathinfo( sanitize_text_field( wp_unslash( $_FILES['file']['name'] ?? '' ) ), PATHINFO_FILENAME ) ) );
	$group = sanitize_text_field( wp_unslash( $_POST['group'] ?? 'Holzdekor' ) );
	$m     = kf24_material( $name );
	$id    = $m ? $m['id'] : kf24_material_save( [ 'name' => $name, 'group' => $group, 'code' => strtok( $name, ' ' ) ] );
	if ( is_wp_error( $id ) ) {
		wp_send_json_error( [ 'msg' => $id->get_error_message() ] );
	}
	kf24_material_set_photo( $id, 'file' );
	wp_send_json_success( kf24_material_data( $id ) + [ 'used' => array_values( kf24_material_usage( $name ) ), 'replaced' => (bool) $m ] );
} );

add_action( 'wp_ajax_kf24_mat_delete', function () {
	kf24_ajax_guard();
	$id = absint( $_POST['id'] ?? 0 );
	if ( get_post_type( $id ) === 'kf24_material' ) {
		kf24_material_delete( $id );
	}
	wp_send_json_success();
} );

add_action( 'wp_ajax_kf24_upload', function () {
	kf24_ajax_guard();
	$ok = kf24_ajax_image_ok( 'file' );
	if ( is_wp_error( $ok ) ) {
		wp_send_json_error( [ 'msg' => $ok->get_error_message() ] );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$att = media_handle_upload( 'file', 0 );
	if ( is_wp_error( $att ) ) {
		wp_send_json_error( [ 'msg' => $att->get_error_message() ] );
	}
	wp_send_json_success( [ 'id' => $att, 'url' => wp_get_attachment_image_url( $att, 'medium' ) ] );
} );

add_action( 'wp_ajax_kf24_front_status', function () {
	kf24_ajax_guard();
	$id = absint( $_POST['id'] ?? 0 );
	if ( get_post_type( $id ) === 'product' ) {
		wp_update_post( [ 'ID' => $id, 'post_status' => ! empty( $_POST['on'] ) ? 'publish' : 'draft' ] );
	}
	wp_send_json_success();
} );

add_action( 'wp_ajax_kf24_front_delete', function () {
	kf24_ajax_guard();
	$id = absint( $_POST['id'] ?? 0 );
	if ( get_post_type( $id ) === 'product' && get_post_meta( $id, '_kf24_enabled', true ) === 'yes' ) {
		wp_trash_post( $id );
	}
	wp_send_json_success( [ 'redirect' => admin_url( 'admin.php?page=kf24-fronten&kf24=deleted' ) ] );
} );

add_action( 'wp_ajax_kf24_front_duplicate', function () {
	kf24_ajax_guard();
	$id = absint( $_POST['id'] ?? 0 );
	$p  = wc_get_product( $id );
	if ( ! $p ) {
		wp_send_json_error( [ 'msg' => 'Front nicht gefunden.' ] );
	}
	$n = new WC_Product_Simple();
	$n->set_name( $p->get_name() . ' (Kopie)' );
	$n->set_status( 'draft' );
	$n->set_regular_price( $p->get_regular_price() );
	$n->set_short_description( $p->get_short_description() );
	$n->set_description( $p->get_description() );
	$n->set_category_ids( $p->get_category_ids() );
	$n->set_image_id( $p->get_image_id() );
	$nid = $n->save();
	foreach ( [ '_kf24_enabled', '_kf24_m2', '_kf24_group', '_kf24_field', '_kf24_options', '_kf24_lz', '_kf24_thickness', '_kf24_kgm2', '_kf24_milling', '_kf24_finish', '_kf24_sample', '_kf24_swatch' ] as $k ) {
		update_post_meta( $nid, $k, get_post_meta( $id, $k, true ) );
	}
	wp_send_json_success( [ 'redirect' => admin_url( 'admin.php?page=kf24-front&id=' . $nid . '&kf24=dup' ) ] );
} );
// phpcs:enable WordPress.Security.NonceVerification.Missing
