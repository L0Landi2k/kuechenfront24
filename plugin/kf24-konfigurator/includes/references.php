<?php
/**
 * Referenzen: simple editor in "Mein Shop" with fixed layouts, drag & drop pictures and a before/after slider.
 * A reference is a normal post in the category "Referenzen"; its content is generated from the form.
 */
defined( 'ABSPATH' ) || exit;

function kf24_ref_cat() {
	$t = get_term_by( 'slug', 'referenzen', 'category' );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$r = wp_insert_term( 'Referenzen', 'category', [ 'slug' => 'referenzen' ] );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

function kf24_ref_layouts() {
	return [
		'vorher-nachher' => [ 'Vorher / Nachher', 'Schieberegler zwischen zwei Fotos, darunter Text und weitere Bilder.' ],
		'galerie'        => [ 'Galerie', 'Großes Titelbild, Text, darunter alle Bilder als Raster.' ],
		'einzelbild'     => [ 'Ein Bild + Text', 'Ein großes Foto und Ihr Text – ideal für kleine Projekte.' ],
	];
}

/* ---------- Before/after slider ---------- */
add_shortcode( 'kf24_vorher_nachher', function ( $atts ) {
	$a = shortcode_atts( [ 'vorher' => 0, 'nachher' => 0 ], $atts );
	$b = wp_get_attachment_image_url( (int) $a['vorher'], 'large' );
	$n = wp_get_attachment_image_url( (int) $a['nachher'], 'large' );
	if ( ! $b || ! $n ) {
		return '';
	}
	wp_enqueue_style( 'kf24' );
	static $js = false;
	$o = '<figure class="kf24-ba" style="--p:50%"><img src="' . esc_url( $n ) . '" alt="Nachher" loading="lazy"><div class="kf24-ba-b"><img src="' . esc_url( $b ) . '" alt="Vorher" loading="lazy"></div><span class="kf24-ba-l is-b">Vorher</span><span class="kf24-ba-l is-a">Nachher</span><span class="kf24-ba-h" aria-hidden="true"></span><input type="range" min="0" max="100" value="50" aria-label="Vorher-Nachher-Vergleich verschieben"></figure>';
	if ( ! $js ) {
		$js = true;
		$o .= "<script>document.addEventListener('input',function(e){if(e.target.matches('.kf24-ba input')){e.target.parentNode.style.setProperty('--p',e.target.value+'%');}});</script>";
	}
	return $o;
} );

/** Compose a split picture (before | after) for cards, cached as attachment meta-less file in uploads. */
function kf24_ref_card_html( $post_id ) {
	$b = (int) get_post_meta( $post_id, '_kf24_before', true );
	$n = (int) get_post_meta( $post_id, '_kf24_after', true );
	if ( ! $b || ! $n || get_post_meta( $post_id, '_kf24_layout', true ) !== 'vorher-nachher' ) {
		return '';
	}
	$bu = wp_get_attachment_image_url( $b, 'medium_large' );
	$nu = wp_get_attachment_image_url( $n, 'medium_large' );
	return '<span class="kf24-split"><span style="background-image:url(' . esc_url( $bu ) . ')"><i>Vorher</i></span><span style="background-image:url(' . esc_url( $nu ) . ')"><i>Nachher</i></span></span>';
}

/** Build the post content from the structured fields. */
function kf24_ref_content( $d ) {
	$p   = fn( $t ) => $t === '' ? '' : '<!-- wp:paragraph --><p>' . $t . '</p><!-- /wp:paragraph -->';
	$img = function ( $id, $size = 'large' ) {
		$u = wp_get_attachment_image_url( $id, $size );
		return $u ? '<!-- wp:image {"id":' . (int) $id . ',"sizeSlug":"' . $size . '","linkDestination":"none"} --><figure class="wp-block-image size-' . $size . '"><img src="' . esc_url( $u ) . '" alt="" class="wp-image-' . (int) $id . '"/></figure><!-- /wp:image -->' : '';
	};
	$o = '';
	if ( $d['layout'] === 'vorher-nachher' && $d['before'] && $d['after'] ) {
		$o .= '<!-- wp:shortcode -->[kf24_vorher_nachher vorher="' . (int) $d['before'] . '" nachher="' . (int) $d['after'] . '"]<!-- /wp:shortcode -->';
	} elseif ( $d['cover'] ) {
		$o .= $img( $d['cover'] );
	}
	foreach ( preg_split( "/\n\s*\n/", trim( $d['text'] ) ) as $para ) {
		$o .= $p( nl2br( esc_html( trim( $para ) ) ) );
	}
	$facts = array_filter( [ 'Front' => $d['front'], 'Küche' => $d['kitchen'], 'Ort' => $d['place'] ] );
	if ( $facts ) {
		$li = '';
		foreach ( $facts as $k => $v ) {
			$li .= '<li><strong>' . esc_html( $k ) . ':</strong> ' . esc_html( $v ) . '</li>';
		}
		$o .= '<!-- wp:list {"className":"kf24-facts"} --><ul class="kf24-facts">' . $li . '</ul><!-- /wp:list -->';
	}
	$gal = array_values( array_filter( array_map( 'intval', $d['gallery'] ) ) );
	if ( $d['layout'] !== 'einzelbild' && $gal ) {
		$inner = '';
		foreach ( $gal as $g ) {
			$inner .= $img( $g );
		}
		$o .= '<!-- wp:gallery {"linkTo":"none","columns":' . min( 3, count( $gal ) ) . '} --><figure class="wp-block-gallery has-nested-images columns-' . min( 3, count( $gal ) ) . ' is-cropped">' . $inner . '</figure><!-- /wp:gallery -->';
	}
	return $o;
}

function kf24_ref_data( $id ) {
	return [
		'title'   => $id ? get_the_title( $id ) : '',
		'excerpt' => $id ? get_post_field( 'post_excerpt', $id ) : '',
		'text'    => (string) get_post_meta( $id, '_kf24_text', true ),
		'layout'  => (string) get_post_meta( $id, '_kf24_layout', true ) ?: 'galerie',
		'before'  => (int) get_post_meta( $id, '_kf24_before', true ),
		'after'   => (int) get_post_meta( $id, '_kf24_after', true ),
		'cover'   => $id ? (int) get_post_thumbnail_id( $id ) : 0,
		'gallery' => array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_kf24_gallery', true ) ) ) ),
		'front'   => (string) get_post_meta( $id, '_kf24_front', true ),
		'kitchen' => (string) get_post_meta( $id, '_kf24_kitchen', true ),
		'place'   => (string) get_post_meta( $id, '_kf24_place', true ),
		'online'  => $id ? get_post_status( $id ) === 'publish' : true,
	];
}

/** Save a reference from structured data. Returns post ID or WP_Error. */
function kf24_ref_save( $d, $id = 0 ) {
	$d = array_merge( kf24_ref_data( 0 ), $d );
	if ( trim( $d['title'] ) === '' ) {
		return new WP_Error( 'title', 'Bitte einen Titel eingeben.' );
	}
	if ( $d['layout'] === 'vorher-nachher' && ( ! $d['before'] || ! $d['after'] ) ) {
		return new WP_Error( 'ba', 'Für „Vorher / Nachher“ brauchen wir beide Fotos.' );
	}
	$cover = $d['layout'] === 'vorher-nachher' ? $d['after'] : ( $d['cover'] ?: ( $d['gallery'][0] ?? 0 ) );
	$args  = [
		'ID'            => $id,
		'post_type'     => 'post',
		'post_status'   => $d['online'] ? 'publish' : 'draft',
		'post_title'    => sanitize_text_field( $d['title'] ),
		'post_excerpt'  => sanitize_textarea_field( $d['excerpt'] ),
		'post_content'  => kf24_ref_content( $d + [ 'cover' => $cover ] ),
		'post_category' => [ kf24_ref_cat() ],
	];
	$id = wp_insert_post( wp_slash( $args ), true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	foreach ( [ 'text', 'layout', 'before', 'after', 'front', 'kitchen', 'place' ] as $k ) {
		update_post_meta( $id, '_kf24_' . $k, $d[ $k ] );
	}
	update_post_meta( $id, '_kf24_gallery', implode( ',', array_map( 'intval', $d['gallery'] ) ) );
	update_post_meta( $id, '_kf24_ref', 1 );
	if ( $cover ) {
		set_post_thumbnail( $id, $cover );
	} else {
		delete_post_thumbnail( $id );
	}
	return $id;
}

/* ---------- Mein Shop pages ---------- */
add_action( 'admin_menu', function () {
	add_submenu_page( 'kf24-shop', 'Referenzen', 'Referenzen', KF24_CAP, 'kf24-referenzen', 'kf24_page_refs' );
	add_submenu_page( 'kf24-shop', 'Referenz bearbeiten', 'Neue Referenz', KF24_CAP, 'kf24-referenz', 'kf24_page_ref_edit' );
}, 11 );

function kf24_page_refs() {
	$posts = get_posts( [ 'post_type' => 'post', 'post_status' => [ 'publish', 'draft' ], 'posts_per_page' => -1, 'category' => kf24_ref_cat() ] );
	kf24_admin_head( 'Referenzen', 'Ihre Kundenküchen. Ein Klick auf „Neue Referenz“ – Titel, Text und Fotos hinein, fertig.', '<a class="button button-primary button-hero" href="' . esc_url( admin_url( 'admin.php?page=kf24-referenz' ) ) . '">+ Neue Referenz</a>' );
	kf24_notice_from_query();
	if ( ! $posts ) {
		echo '<p class="kf24a-empty">Noch keine Referenzen.</p></div>';
		return;
	}
	echo '<div class="kf24a-fronts">';
	foreach ( $posts as $p ) {
		$t   = get_post_thumbnail_id( $p );
		$pic = $t ? wp_get_attachment_image_url( $t, 'thumbnail' ) : '';
		$ed  = admin_url( 'admin.php?page=kf24-referenz&id=' . $p->ID );
		printf(
			'<div class="kf24a-front%6$s" data-id="%1$d" data-name="%2$s"><a class="kf24a-front-pic" href="%3$s" style="%4$s"></a><div class="kf24a-front-b"><a class="kf24a-front-name" href="%3$s">%2$s</a><span class="kf24a-meta">%5$s · %7$s</span></div><span></span><div class="kf24a-front-act"><a class="button" href="%3$s">Bearbeiten</a> <a class="button" href="%8$s" target="_blank">Ansehen</a></div></div>',
			(int) $p->ID,
			esc_html( $p->post_title ),
			esc_url( $ed ),
			esc_attr( $pic ? 'background-image:url(' . $pic . ')' : 'background:#e9e2d6' ),
			esc_html( get_the_date( 'd.m.Y', $p ) ),
			$p->post_status === 'publish' ? '' : ' is-off',
			$p->post_status === 'publish' ? 'online' : 'Entwurf',
			esc_url( get_permalink( $p ) )
		);
	}
	echo '</div></div>';
}

function kf24_page_ref_edit() {
	$id = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only
	$d  = kf24_ref_data( $id );
	if ( $id && ! get_post_meta( $id, '_kf24_ref', true ) && ! get_post_meta( $id, '_kf24_text', true ) ) {
		// older post written in the editor: offer its text
		$d['text'] = trim( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) ) );
	}
	$pic = fn( $att ) => $att ? wp_get_attachment_image_url( $att, 'medium' ) : '';
	kf24_admin_head( $id ? 'Referenz bearbeiten' : 'Neue Referenz', 'Sie füllen nur Text und Fotos aus. Das Layout baut die Seite automatisch.', $id ? '<a class="button" target="_blank" href="' . esc_url( get_permalink( $id ) ) . '">Ansehen ↗</a>' : '' );
	kf24_notice_from_query();
	?>
	<form class="kf24a-form" id="kf24a-ref-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="kf24_ref_save"><input type="hidden" name="id" value="<?php echo (int) $id; ?>"><?php wp_nonce_field( 'kf24_ref_save' ); ?>
		<div class="kf24a-cols"><div class="kf24a-main">
			<section class="kf24a-card"><h2>1. Layout wählen</h2><div class="kf24a-layouts">
				<?php foreach ( kf24_ref_layouts() as $k => $l ) : ?>
					<label class="kf24a-layout"><input type="radio" name="layout" value="<?php echo esc_attr( $k ); ?>" <?php checked( $d['layout'], $k ); ?>><span class="kf24a-lay-ico is-<?php echo esc_attr( $k ); ?>" aria-hidden="true"><i></i><i></i><i></i></span><b><?php echo esc_html( $l[0] ); ?></b><small><?php echo esc_html( $l[1] ); ?></small></label>
				<?php endforeach; ?>
			</div></section>
			<section class="kf24a-card"><h2>2. Fotos</h2>
				<div class="kf24a-ref-ba">
					<?php foreach ( [ 'before' => 'Vorher-Foto', 'after' => 'Nachher-Foto' ] as $k => $l ) : ?>
						<div><b class="kf24a-lab"><?php echo esc_html( $l ); ?></b><div class="kf24a-drop kf24a-drop-one" data-field="<?php echo esc_attr( $k ); ?>" tabindex="0" role="button"><div class="kf24a-drop-pic" style="<?php echo $d[ $k ] ? 'background-image:url(' . esc_url( $pic( $d[ $k ] ) ) . ')' : ''; ?>"></div><span>Foto hierher ziehen<br><small>oder klicken</small></span></div><input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo (int) $d[ $k ]; ?>"><button type="button" class="button-link kf24a-one-remove" <?php echo $d[ $k ] ? '' : 'hidden'; ?>>entfernen</button></div>
					<?php endforeach; ?>
				</div>
				<div class="kf24a-ref-cover"><b class="kf24a-lab">Titelbild</b><div class="kf24a-drop kf24a-drop-one" data-field="cover" tabindex="0" role="button"><div class="kf24a-drop-pic" style="<?php echo $d['cover'] ? 'background-image:url(' . esc_url( $pic( $d['cover'] ) ) . ')' : ''; ?>"></div><span>Titelbild hierher ziehen<br><small>oder klicken</small></span></div><input type="hidden" name="cover" value="<?php echo (int) $d['cover']; ?>"><button type="button" class="button-link kf24a-one-remove" <?php echo $d['cover'] ? '' : 'hidden'; ?>>entfernen</button></div>
				<div class="kf24a-ref-gal"><b class="kf24a-lab">Weitere Fotos <small>(beliebig viele, mehrere auf einmal hineinziehen)</small></b>
					<div class="kf24a-drop kf24a-drop-many" tabindex="0" role="button"><span><b>Fotos hierher ziehen</b> oder klicken</span></div>
					<div class="kf24a-gal" data-items="<?php echo esc_attr( wp_json_encode( array_map( fn( $g ) => [ 'id' => $g, 'url' => $pic( $g ) ], array_values( $d['gallery'] ) ) ) ); ?>"></div>
					<input type="hidden" name="gallery" value="<?php echo esc_attr( implode( ',', $d['gallery'] ) ); ?>">
				</div>
			</section>
			<section class="kf24a-card"><h2>3. Text</h2>
				<label class="kf24a-f"><span>Titel *</span><input type="text" name="title" required value="<?php echo esc_attr( $d['title'] ); ?>" placeholder="z. B. Landhausküche in Eiche, Haar"></label>
				<label class="kf24a-f"><span>Kurztext für die Übersicht (1–2 Sätze)</span><textarea name="excerpt" rows="2"><?php echo esc_textarea( $d['excerpt'] ); ?></textarea></label>
				<label class="kf24a-f"><span>Ihr Text <small>(Leerzeile = neuer Absatz)</small></span><textarea name="text" rows="8" placeholder="Was war die Ausgangslage? Was haben wir gemacht? Was sagt der Kunde?"><?php echo esc_textarea( $d['text'] ); ?></textarea></label>
				<div class="kf24a-row">
					<label class="kf24a-f"><span>Front / Material</span><input type="text" name="front" value="<?php echo esc_attr( $d['front'] ); ?>" placeholder="z. B. Massivholz Eiche, geölt" list="kf24a-fronts"></label>
					<label class="kf24a-f"><span>Küche</span><input type="text" name="kitchen" value="<?php echo esc_attr( $d['kitchen'] ); ?>" placeholder="z. B. IKEA Metod"></label>
					<label class="kf24a-f"><span>Ort</span><input type="text" name="place" value="<?php echo esc_attr( $d['place'] ); ?>" placeholder="z. B. München"></label>
				</div>
				<datalist id="kf24a-fronts"><?php foreach ( kf24_all_cfg_products() as $c ) { echo '<option value="' . esc_attr( $c['name'] ) . '">'; } ?></datalist>
			</section>
		</div>
		<aside class="kf24a-side"><section class="kf24a-card kf24a-sticky">
			<label class="kf24a-switch kf24a-switch-big"><input type="checkbox" name="online" value="1" <?php checked( $d['online'] ); ?>><span></span><em>Auf der Website zeigen</em></label>
			<button class="button button-primary button-hero kf24a-save" type="submit">Speichern</button>
			<?php if ( $id ) : ?><button type="button" class="button-link kf24a-ref-del" data-id="<?php echo (int) $id; ?>">Referenz löschen</button><?php endif; ?>
			<p class="kf24a-hint">Tipp: Fotos im Querformat wirken am besten. Für Vorher/Nachher beide Fotos vom gleichen Standpunkt aufnehmen.</p>
		</section></aside></div>
	</form>
	<?php
	echo '</div>';
}

add_action( 'admin_post_kf24_ref_save', function () {
	check_admin_referer( 'kf24_ref_save' );
	if ( ! current_user_can( KF24_CAP ) ) {
		wp_die( 'Keine Berechtigung' );
	}
	$in = wp_unslash( $_POST );
	$id = absint( $in['id'] ?? 0 );
	$r  = kf24_ref_save( [
		'title'   => sanitize_text_field( $in['title'] ?? '' ),
		'excerpt' => sanitize_textarea_field( $in['excerpt'] ?? '' ),
		'text'    => sanitize_textarea_field( $in['text'] ?? '' ),
		'layout'  => array_key_exists( $in['layout'] ?? '', kf24_ref_layouts() ) ? $in['layout'] : 'galerie',
		'before'  => absint( $in['before'] ?? 0 ),
		'after'   => absint( $in['after'] ?? 0 ),
		'cover'   => absint( $in['cover'] ?? 0 ),
		'gallery' => array_filter( array_map( 'absint', explode( ',', sanitize_text_field( $in['gallery'] ?? '' ) ) ) ),
		'front'   => sanitize_text_field( $in['front'] ?? '' ),
		'kitchen' => sanitize_text_field( $in['kitchen'] ?? '' ),
		'place'   => sanitize_text_field( $in['place'] ?? '' ),
		'online'  => ! empty( $in['online'] ),
	], $id );
	if ( is_wp_error( $r ) ) {
		wp_die( esc_html( $r->get_error_message() ) . ' <a href="javascript:history.back()">Zurück</a>', 'Bitte prüfen', [ 'response' => 200 ] );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=kf24-referenz&id=' . $r . '&kf24=saved' ) );
	exit;
} );

add_action( 'wp_ajax_kf24_ref_delete', function () {
	kf24_ajax_guard();
	$id = absint( $_POST['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in kf24_ajax_guard()
	if ( get_post_type( $id ) === 'post' && in_array( kf24_ref_cat(), wp_get_post_categories( $id ), true ) ) {
		wp_trash_post( $id );
	}
	wp_send_json_success( [ 'redirect' => admin_url( 'admin.php?page=kf24-referenzen&kf24=deleted' ) ] );
} );
