<?php
/**
 * Uvoz proizvoda sa starog sajta u WooCommerce: kategorije, oznake (filteri), proizvodi sa
 * fotografijama, tekstovima, karakteristikama, pitanjima, konfiguratorom i preporukama.
 * Uvoz se može ponoviti: postojeći proizvodi (po šifri) se osvežavaju, a cene koje ste
 * u međuvremenu upisali ostaju.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_import_data( $file ) {
	$path = BUMBARU3D_DIR . 'data/' . $file;
	return file_exists( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : array();
}

/** Slika iz foldera import/ u biblioteku medija (jednom; ponovni uvoz koristi istu). */
function bumbaru_import_image( $rel, $alt = '', $parent = 0 ) {
	if ( ! $rel ) {
		return 0;
	}
	$found = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'meta_key'       => '_bu_src',
		'meta_value'     => $rel,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	if ( $found ) {
		if ( $alt ) {
			update_post_meta( $found[0], '_wp_attachment_image_alt', $alt );
		}
		return (int) $found[0];
	}
	$src = '';
	foreach ( bumbaru_import_dirs() as $dir ) {
		if ( file_exists( $dir . $rel ) ) {
			$src = $dir . $rel;
			break;
		}
	}
	if ( ! $src ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	// Ime fajla: naziv-proizvoda-1-lg.webp (čitljivo i za Google slike).
	$name = sanitize_file_name( str_replace( '/', '-', preg_replace( '#^(p|c|og|gen)/#', '', $rel ) ) );
	$name = preg_replace( '/-u[0-9a-f]{9}(?=-)/', '', $name );
	$up   = wp_upload_bits( $name, null, (string) file_get_contents( $src ) );
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	$type = wp_check_filetype( $up['file'] );
	$id   = wp_insert_attachment( array(
		'post_mime_type' => $type['type'],
		'post_title'     => $alt ? wp_trim_words( $alt, 8, '' ) : $name,
		'post_status'    => 'inherit',
	), $up['file'], $parent );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
	update_post_meta( $id, '_bu_src', $rel );
	if ( $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}
	return (int) $id;
}

/** Kategorije i oznake (prvi korak uvoza). */
function bumbaru_import_terms() {
	$tags = bumbaru_import_data( 'tags.json' );
	foreach ( $tags as $slug => $name ) {
		if ( ! term_exists( $slug, 'product_tag' ) ) {
			wp_insert_term( $name, 'product_tag', array( 'slug' => $slug ) );
		}
	}
	foreach ( bumbaru_import_data( 'categories.json' ) as $c ) {
		$t = term_exists( $c['slug'], 'product_cat' );
		if ( ! $t ) {
			$t = wp_insert_term( $c['label'], 'product_cat', array( 'slug' => $c['slug'], 'description' => $c['text'] ) );
		} else {
			wp_update_term( (int) $t['term_id'], 'product_cat', array( 'name' => $c['label'], 'description' => $c['text'] ) );
		}
		if ( is_wp_error( $t ) ) {
			continue;
		}
		$id   = (int) $t['term_id'];
		$faq  = array();
		foreach ( $c['faq'] as $f ) {
			$faq[] = $f['q'] . ' | ' . $f['a'];
		}
		$meta = array(
			'title'      => $c['title'],
			'seo_title'  => $c['seoTitle'],
			'seo_desc'   => $c['meta'],
			'bg'         => $c['bg'],
			'bg_pos'     => isset( $c['bgPos'] ) ? $c['bgPos'] : '',
			'icon'       => $c['icon'],
			'filters'    => implode( ',', $c['filters'] ),
			'soon'       => ! empty( $c['soon'] ) ? '1' : '',
			'about_h2'   => $c['about']['h2'],
			'about_p'    => implode( "\n\n", $c['about']['p'] ),
			'faq'        => implode( "\n", $faq ),
			'order'      => $c['order'],
			'menu_label' => $c['menuLabel'],
			'focus_kw'   => isset( $c['focus'] ) ? $c['focus'] : '',
		);
		foreach ( $meta as $k => $v ) {
			update_term_meta( $id, 'bu_' . $k, $v );
		}
	}
	// „Nekategorizovano” ne treba u meniju; neka ostane poslednja.
	$def = (int) get_option( 'default_product_cat' );
	if ( $def ) {
		update_term_meta( $def, 'bu_order', 99 );
	}
}

/** Uvezi jedan proizvod (po redu iz products.json). Vraća poruku. */
function bumbaru_import_product( $i ) {
	$all = bumbaru_import_data( 'products.json' );
	if ( ! isset( $all[ $i ] ) ) {
		return '';
	}
	$p   = $all[ $i ];
	// Proizvod se traži po adresi (šifra je mogla da se promeni), pa po šifri.
	$post = get_page_by_path( $p['slug'], OBJECT, 'product' );
	$pid  = $post && 'trash' !== $post->post_status ? $post->ID : 0;
	if ( ! $pid ) {
		$pid = wc_get_product_id_by_sku( $p['id'] );
	}
	$is_new  = ! $pid;
	$product = $pid ? wc_get_product( $pid ) : new WC_Product_Simple();
	if ( ! $product ) {
		$product = new WC_Product_Simple();
		$is_new  = true;
	}
	// Šifra: ako je drugi proizvod drži (npr. stara šifra spiralne jelke), prvo se oslobodi.
	$holder = wc_get_product_id_by_sku( $p['id'] );
	if ( $holder && (int) $holder !== (int) $product->get_id() ) {
		$h = wc_get_product( $holder );
		if ( $h ) {
			$h->set_sku( '' );
			$h->save();
		}
	}
	try {
		$product->set_sku( $p['id'] );
	} catch ( Exception $e ) { // phpcs:ignore
		// šifra zauzeta: ostaje stara
	}
	$product->set_name( $p['title'] );
	$product->set_slug( $p['slug'] );
	if ( $is_new ) {
		$product->set_status( ! empty( $p['hidden'] ) ? 'draft' : 'publish' );
	} elseif ( 'trash' === $product->get_status() ) {
		$product->set_status( 'publish' );
	}
	$product->set_catalog_visibility( 'visible' );
	$product->set_short_description( $p['short'] );
	$product->set_description( implode( "\n\n", $p['about'] ) );
	$product->set_menu_order( $i );
	// Cena se upisuje pri prvom uvozu i kad proizvod još nema cenu; cene koje ste upisali ostaju.
	if ( ! empty( $p['price'] ) && ( $is_new || '' === (string) $product->get_regular_price( 'edit' ) ) ) {
		$product->set_regular_price( (string) $p['price'] );
	}
	if ( $is_new && ! empty( $p['sale'] ) ) {
		$product->set_sale_price( (string) $p['sale'] );
	}
	$product->set_featured( $p['homeOrder'] > 0 );
	$product->set_sold_individually( false );
	$product->set_tax_status( 'none' );

	$cats = array();
	foreach ( $p['categories'] as $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $t ) {
			$cats[] = $t->term_id;
		}
	}
	$product->set_category_ids( $cats );
	$tags = array();
	foreach ( (array) ( isset( $p['tags'] ) ? $p['tags'] : array() ) as $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_tag' );
		if ( $t ) {
			$tags[] = $t->term_id;
		}
	}
	$product->set_tag_ids( $tags );
	$pid = $product->save();

	// Fotografije: kartica (kvadrat sa žigom) i galerija (cele fotografije).
	$gallery = array();
	$card    = 0;
	$vel     = array();
	$imot    = array(); // fotografija → motiv ukrasa (galerija prikaže fotografije izabranog motiva)
	$iboja   = array(); // fotografija → boja (galerija prikaže fotografiju izabrane boje)
	$iai     = array(); // fotografije napravljene uz AI (napomena ispod fotografije)
	foreach ( $p['images'] as $k => $im ) {
		$full = bumbaru_import_image( $im['full'], $im['alt'], $pid );
		if ( 0 === $k ) {
			$card = $im['card'] === $im['full'] ? $full : bumbaru_import_image( $im['card'], $im['alt'], $pid );
		}
		if ( $full ) {
			$gallery[] = $full;
			if ( isset( $im['vel'] ) ) {
				$vel[ $full ] = (int) $im['vel'];
			}
			if ( ! empty( $im['motiv'] ) ) {
				$imot[ $full ] = sanitize_key( $im['motiv'] );
			}
			if ( ! empty( $im['boja'] ) ) {
				$iboja[ $full ] = sanitize_key( $im['boja'] );
			}
			if ( ! empty( $im['ai'] ) ) {
				$iai[ $full ] = 1;
			}
		}
	}
	// Bez fotografija proizvoda (npr. sastavite svoj set): kartica je fotografija prvog motiva.
	if ( ! $gallery && ! empty( $p['config']['motivi']['lista'] ) ) {
		foreach ( $p['config']['motivi']['lista'] as $m ) {
			if ( ! empty( $m['slika'] ) ) {
				$card = bumbaru_import_image( $m['slika']['card'], $m['slika']['alt'], $pid );
				break;
			}
		}
	}
	if ( ! $card && ! empty( $p['cardImage'] ) ) {
		$card = bumbaru_import_image( $p['cardImage'], $p['title'], $pid );
	}
	if ( $card ) {
		$product->set_image_id( $card );
	}
	$product->set_gallery_image_ids( $gallery );
	$product->save();

	// Bumbaru polja.
	$specs = array();
	foreach ( $p['specs'] as $s ) {
		$specs[] = $s[0] . ' | ' . $s[1];
	}
	$lines = function ( $list ) {
		$o = array();
		foreach ( $list as $x ) {
			$o[] = $x['q'] . ' | ' . $x['a'];
		}
		return implode( "\n", $o );
	};
	$meta = array(
		'_bu_lead'        => $p['lead'],
		'_bu_custom'      => ! empty( $p['custom'] ) ? '1' : '',
		'_bu_price_from'  => ! empty( $p['priceFrom'] ) ? '1' : '',
		'_bu_specs'       => implode( "\n", $specs ),
		'_bu_details'     => $lines( $p['details'] ),
		'_bu_faq'         => $lines( $p['faq'] ),
		'_bu_seo_title'   => $p['seoTitle'],
		'_bu_seo_desc'    => $p['meta'],
		'_bu_focus_kw'    => isset( $p['focus'] ) ? $p['focus'] : '',
		'_bu_primary_cat' => $p['category'],
		'_bu_home_order'  => (int) $p['homeOrder'],
		'_bu_config'      => bumbaru_import_config( $p['config'], $pid ),
		'_bu_img_vel'     => $vel,
		'_bu_img_mot'     => $imot,
		'_bu_img_boja'    => $iboja,
		'_bu_img_ai'      => $iai,
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $pid, $k, $v );
	}
	if ( ! empty( $p['calc'] ) ) {
		bumbaru_import_calc( $pid, $p['calc'] );
	}
	$og = ! empty( $p['og'] ) ? bumbaru_import_image( $p['og'], $p['title'], $pid ) : 0;
	update_post_meta( $pid, '_bu_og_image', $og );

	return ( $is_new ? 'Dodat: ' : 'Osvežen: ' ) . $p['title'];
}

/** Obračun cene iz paketa: upisuje se samo kad proizvod još nema svoj obračun (izmene u administraciji ostaju), pa cena prati kalkulator. */
function bumbaru_import_calc( $pid, $calc ) {
	if ( ! function_exists( 'bumbaru_calc_sanitize' ) || bumbaru_calc_has_data( get_post_meta( $pid, '_bu_calc', true ) ) ) {
		return;
	}
	update_post_meta( $pid, '_bu_calc', bumbaru_calc_sanitize( $calc ) );
	bumbaru_calc_sync_price( $pid );
}

/** Konfigurator iz paketa: fotografije motiva idu u biblioteku medija (oznaka slike umesto putanje). */
function bumbaru_import_config( $cfg, $pid ) {
	if ( ! empty( $cfg['motivi']['lista'] ) ) {
		foreach ( $cfg['motivi']['lista'] as $k => $m ) {
			if ( ! empty( $m['slika'] ) ) {
				$cfg['motivi']['lista'][ $k ]['img'] = bumbaru_import_image( $m['slika']['full'], $m['slika']['alt'], $pid );
				unset( $cfg['motivi']['lista'][ $k ]['slika'] );
			}
		}
	}
	return $cfg;
}

/** Preporuke („Uz ovo preporučujemo”), posle svih proizvoda. */
function bumbaru_import_related() {
	foreach ( bumbaru_import_data( 'products.json' ) as $p ) {
		$post = get_page_by_path( $p['slug'], OBJECT, 'product' );
		$pid  = $post ? $post->ID : wc_get_product_id_by_sku( $p['id'] );
		if ( ! $pid ) {
			continue;
		}
		$ids = array();
		foreach ( $p['related'] as $slug ) {
			$r = get_page_by_path( $slug, OBJECT, 'product' );
			if ( $r ) {
				$ids[] = $r->ID;
			}
		}
		$product = wc_get_product( $pid );
		$product->set_upsell_ids( $ids );
		$product->save();
	}
}

function bumbaru_import_count() {
	return count( bumbaru_import_data( 'products.json' ) );
}

/** Ceo uvoz odjednom (za WP-CLI: wp eval 'bumbaru_import_all();'). */
function bumbaru_import_all() {
	bumbaru_import_terms();
	$log = array();
	for ( $i = 0, $n = bumbaru_import_count(); $i < $n; $i++ ) {
		$log[] = bumbaru_import_product( $i );
	}
	bumbaru_import_related();
	bumbaru_yoast_sync_all();
	update_option( 'bumbaru_import_done', time() );
	delete_option( 'bumbaru_update_notice' );
	return $log;
}

// Uvoz korak po korak iz administracije (da ne istekne vreme na sporijem hostingu).
add_action( 'wp_ajax_bumbaru_import_step', function () {
	check_ajax_referer( 'bumbaru_import' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Nemate dozvolu.' );
	}
	@set_time_limit( 120 ); // phpcs:ignore
	$step = isset( $_POST['step'] ) ? (int) $_POST['step'] : 0;
	$n    = bumbaru_import_count();
	if ( 0 === $step ) {
		bumbaru_import_terms();
		wp_send_json_success( array( 'next' => 1, 'total' => $n + 2, 'msg' => 'Kategorije i filteri su spremni.' ) );
	}
	if ( $step <= $n ) {
		$msg = bumbaru_import_product( $step - 1 );
		wp_send_json_success( array( 'next' => $step + 1, 'total' => $n + 2, 'msg' => $msg ) );
	}
	bumbaru_import_related();
	bumbaru_yoast_sync_all();
	update_option( 'bumbaru_import_done', time() );
	delete_option( 'bumbaru_update_notice' );
	wp_send_json_success( array( 'next' => 0, 'total' => $n + 2, 'msg' => 'Preporuke i SEO podaci su upisani. Uvoz je gotov.' ) );
} );
