<?php
/**
 * Material library: every decor, wood species, RAL colour and Fenix colour is one
 * "material" with a name, group, code, colour and an optional photo.
 * Products reference materials by name (meta _kf24_options, one name per line).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'kf24_material', [
		'label'        => 'Materialien',
		'public'       => false,
		'show_ui'      => false,
		'show_in_rest' => false,
		'supports'     => [ 'title', 'thumbnail' ],
		'capability_type' => 'product',
		'map_meta_cap' => true,
	] );
} );

/** Group names in display order. */
function kf24_material_groups() {
	return [ 'Holzdekor', 'Uni', 'Weiß', 'Materialdekor', 'Furnier', 'Massivholz', 'Lack RAL', 'Fenix' ];
}

/** All materials keyed by name. Cached per request and in a transient. */
function kf24_materials( $fresh = false ) {
	static $cache = null;
	if ( $cache !== null && ! $fresh ) {
		return $cache;
	}
	$cache = $fresh ? false : get_transient( 'kf24_materials' );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	$cache = [];
	$posts = get_posts( [ 'post_type' => 'kf24_material', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
	foreach ( $posts as $p ) {
		$cache[ $p->post_title ] = kf24_material_data( $p );
	}
	set_transient( 'kf24_materials', $cache, DAY_IN_SECONDS );
	return $cache;
}

function kf24_materials_flush() {
	delete_transient( 'kf24_materials' );
	kf24_materials( true );
}

function kf24_material_data( $p ) {
	$p     = get_post( $p );
	$thumb = (int) get_post_thumbnail_id( $p );
	$tex   = (string) get_post_meta( $p->ID, '_kf24_tex', true );
	$img   = $thumb ? wp_get_attachment_image_url( $thumb, 'medium_large' ) : ( $tex ? KF24_URL . 'assets/tex/' . $tex : '' );
	return [
		'id'     => $p->ID,
		'name'   => $p->post_title,
		'group'  => (string) get_post_meta( $p->ID, '_kf24_group', true ),
		'code'   => (string) get_post_meta( $p->ID, '_kf24_code', true ),
		'color'  => (string) get_post_meta( $p->ID, '_kf24_color', true ) ?: '#cccccc',
		'img'    => $img ?: '',
		'photo'  => $thumb > 0,
		'placeholder' => ! $thumb && $tex !== '',
	];
}

function kf24_material( $name ) {
	$m = kf24_materials();
	return $m[ $name ] ?? null;
}

/** Image (or '') and colour for a material name, with a sensible fallback. */
function kf24_material_look( $name, $fallback_color = '#d9c8ad' ) {
	$m = kf24_material( $name );
	return $m ? [ 'img' => $m['img'], 'color' => $m['color'] ] : [ 'img' => '', 'color' => $fallback_color ];
}

/** Product cover look: product image, else first material. */
function kf24_product_look( $product_id ) {
	$opts = array_values( array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $product_id, '_kf24_options', true ) ) ) ) );
	$first = $opts ? kf24_material_look( $opts[0] ) : [ 'img' => '', 'color' => '#d9c8ad' ];
	$cover = (string) get_post_meta( $product_id, '_kf24_cover', true );
	if ( $cover && kf24_material( $cover ) ) {
		$first = kf24_material_look( $cover );
	}
	return $first;
}

/** Average colour of an image file as #rrggbb (GD), or ''. */
function kf24_avg_color( $file ) {
	if ( ! function_exists( 'imagecreatefromstring' ) || ! is_readable( $file ) ) {
		return '';
	}
	$im = @imagecreatefromstring( file_get_contents( $file ) );
	if ( ! $im ) {
		return '';
	}
	$px = imagecreatetruecolor( 1, 1 );
	imagecopyresampled( $px, $im, 0, 0, 0, 0, 1, 1, imagesx( $im ), imagesy( $im ) );
	$c = imagecolorat( $px, 0, 0 );
	imagedestroy( $im );
	imagedestroy( $px );
	return sprintf( '#%02x%02x%02x', ( $c >> 16 ) & 255, ( $c >> 8 ) & 255, $c & 255 );
}

/** Create or update a material. Returns post ID or WP_Error. */
function kf24_material_save( $data, $id = 0 ) {
	$name = trim( wp_strip_all_tags( (string) ( $data['name'] ?? '' ) ) );
	if ( $name === '' ) {
		return new WP_Error( 'name', 'Bitte einen Namen eingeben.' );
	}
	$dupe = get_posts( [ 'post_type' => 'kf24_material', 'title' => $name, 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 1, 'exclude' => $id ? [ $id ] : [] ] );
	if ( $dupe ) {
		return new WP_Error( 'dupe', 'Ein Material mit dem Namen „' . $name . '“ gibt es schon.' );
	}
	$old = $id ? get_the_title( $id ) : '';
	$id  = wp_insert_post( [ 'ID' => $id, 'post_type' => 'kf24_material', 'post_status' => 'publish', 'post_title' => $name ], true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	foreach ( [ 'group', 'code', 'color', 'tex' ] as $k ) {
		if ( isset( $data[ $k ] ) ) {
			$v = $k === 'color' ? ( sanitize_hex_color( $data[ $k ] ) ?: '' ) : sanitize_text_field( $data[ $k ] );
			update_post_meta( $id, '_kf24_' . $k, $v );
		}
	}
	if ( $old && $old !== $name ) {
		kf24_material_rename_in_products( $old, $name );
	}
	kf24_materials_flush();
	return $id;
}

/** Keep product option lists in sync when a material is renamed. */
function kf24_material_rename_in_products( $old, $new ) {
	$ids = get_posts( [ 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_kf24_enabled', 'meta_value' => 'yes' ] );
	foreach ( $ids as $pid ) {
		$lines = explode( "\n", (string) get_post_meta( $pid, '_kf24_options', true ) );
		$hit   = false;
		foreach ( $lines as &$l ) {
			if ( trim( $l ) === $old ) {
				$l   = $new;
				$hit = true;
			}
		}
		unset( $l );
		if ( $hit ) {
			update_post_meta( $pid, '_kf24_options', implode( "\n", $lines ) );
		}
		if ( get_post_meta( $pid, '_kf24_cover', true ) === $old ) {
			update_post_meta( $pid, '_kf24_cover', $new );
		}
	}
}

/** Products that use a material (names). */
function kf24_material_usage( $name ) {
	$out = [];
	foreach ( get_posts( [ 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_kf24_enabled', 'meta_value' => 'yes' ] ) as $pid ) {
		$lines = array_map( 'trim', explode( "\n", (string) get_post_meta( $pid, '_kf24_options', true ) ) );
		if ( in_array( $name, $lines, true ) ) {
			$out[ $pid ] = get_the_title( $pid );
		}
	}
	return $out;
}

/** Delete a material and remove it from all products. */
function kf24_material_delete( $id ) {
	$name = get_the_title( $id );
	foreach ( array_keys( kf24_material_usage( $name ) ) as $pid ) {
		$lines = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $pid, '_kf24_options', true ) ) ), fn( $l ) => $l !== '' && $l !== $name );
		update_post_meta( $pid, '_kf24_options', implode( "\n", $lines ) );
	}
	wp_delete_post( $id, true );
	kf24_materials_flush();
}

/** Attach an uploaded file ($_FILES key) as photo of material $id. */
function kf24_material_set_photo( $id, $file_key ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$att = media_handle_upload( $file_key, 0, [ 'post_title' => get_the_title( $id ) ] );
	if ( is_wp_error( $att ) ) {
		return $att;
	}
	set_post_thumbnail( $id, $att );
	$c = kf24_avg_color( get_attached_file( $att ) );
	if ( $c ) {
		update_post_meta( $id, '_kf24_color', $c );
	}
	kf24_materials_flush();
	return $att;
}

/** Seed the library from data/materials.json (skips existing names). */
function kf24_import_materials() {
	$list = json_decode( (string) file_get_contents( KF24_DIR . 'data/materials.json' ), true ) ?: [];
	$have = kf24_materials( true );
	$n    = 0;
	foreach ( $list as $m ) {
		if ( isset( $have[ $m['name'] ] ) ) {
			continue;
		}
		$id = wp_insert_post( [ 'post_type' => 'kf24_material', 'post_status' => 'publish', 'post_title' => $m['name'] ] );
		foreach ( [ 'group', 'code', 'color', 'tex' ] as $k ) {
			update_post_meta( $id, '_kf24_' . $k, $m[ $k ] );
		}
		$n++;
	}
	kf24_materials_flush();
	return $n;
}

/** Make sure every option a product uses exists as material (e.g. typed by hand). */
function kf24_ensure_materials( $names, $group = '' ) {
	$have = kf24_materials();
	foreach ( $names as $n ) {
		if ( $n !== '' && ! isset( $have[ $n ] ) ) {
			kf24_material_save( [ 'name' => $n, 'group' => $group ?: 'Holzdekor', 'color' => '#cccccc' ] );
		}
	}
}
