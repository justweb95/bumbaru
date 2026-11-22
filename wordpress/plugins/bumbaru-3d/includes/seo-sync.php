<?php
/**
 * SEO bez dodatnih plugina radi u temi (naslovi, opisi, kanonski link, schema, mapa sajta).
 * Ako instalirate Yoast SEO, ovaj deo upisuje iste naslove, opise i ključne reči u Yoast polja
 * i podešava Yoast (firma, Facebook, bez oznaka i stranica autora u Google-u),
 * tako da je sve popunjeno od prvog dana. Tema tada prepušta zaglavlje Yoast-u.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_yoast_active() {
	return defined( 'WPSEO_VERSION' );
}

/** Yoast odmah osvežava svoju bazu za tu stranu (ako je aktivan). */
function bumbaru_yoast_rebuild( $id, $type ) {
	if ( ! bumbaru_yoast_active() || ! function_exists( 'YoastSEO' ) ) {
		return;
	}
	try {
		$builder = YoastSEO()->classes->get( 'Yoast\WP\SEO\Builders\Indexable_Builder' );
		if ( $builder ) {
			$builder->build_for_id_and_type( (int) $id, $type );
		}
	} catch ( \Throwable $e ) {
		// Yoast će stranu osvežiti sam pri sledećem čuvanju.
	}
}

function bumbaru_yoast_sync_product( $id ) {
	$map = array(
		'_bu_seo_title' => '_yoast_wpseo_title',
		'_bu_seo_desc'  => '_yoast_wpseo_metadesc',
		'_bu_focus_kw'  => '_yoast_wpseo_focuskw',
	);
	foreach ( $map as $from => $to ) {
		$v = (string) get_post_meta( $id, $from, true );
		if ( '' !== $v ) {
			update_post_meta( $id, $to, $v );
		}
	}
	$og = (int) get_post_meta( $id, '_bu_og_image', true );
	if ( $og && wp_get_attachment_url( $og ) ) {
		update_post_meta( $id, '_yoast_wpseo_opengraph-image', wp_get_attachment_url( $og ) );
		update_post_meta( $id, '_yoast_wpseo_opengraph-image-id', $og );
	}
	bumbaru_yoast_rebuild( $id, 'post' );
}

function bumbaru_yoast_sync_term( $term_id ) {
	$all = get_option( 'wpseo_taxonomy_meta', array() );
	$all = is_array( $all ) ? $all : array();
	$row = isset( $all['product_cat'][ $term_id ] ) ? (array) $all['product_cat'][ $term_id ] : array();
	foreach ( array( 'seo_title' => 'wpseo_title', 'seo_desc' => 'wpseo_desc', 'focus_kw' => 'wpseo_focuskw' ) as $from => $to ) {
		$v = (string) get_term_meta( $term_id, 'bu_' . $from, true );
		if ( '' !== $v ) {
			$row[ $to ] = $v;
		}
	}
	if ( get_term_meta( $term_id, 'bu_soon', true ) ) {
		$row['wpseo_noindex'] = 'noindex';
	} elseif ( isset( $row['wpseo_noindex'] ) && 'noindex' === $row['wpseo_noindex'] ) {
		unset( $row['wpseo_noindex'] );
	}
	$all['product_cat'][ $term_id ] = $row;
	update_option( 'wpseo_taxonomy_meta', $all );
	bumbaru_yoast_rebuild( $term_id, 'term' );
}

/** Strane (Početna, Katalog, Po meri, Pomoć, Politika): isti naslovi i opisi kao u temi. */
function bumbaru_yoast_sync_pages() {
	$path  = BUMBARU3D_DIR . 'data/pages-seo.json';
	$pages = file_exists( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : array();
	foreach ( (array) $pages as $slug => $seo ) {
		$page = get_page_by_path( $slug );
		if ( 'katalog' === $slug && get_option( 'woocommerce_shop_page_id' ) ) {
			$page = get_post( (int) get_option( 'woocommerce_shop_page_id' ) );
		}
		if ( 'pocetna' === $slug && get_option( 'page_on_front' ) ) {
			$page = get_post( (int) get_option( 'page_on_front' ) );
		}
		if ( ! $page ) {
			continue;
		}
		// Ne gazi ono što ste već sami upisali.
		$title = get_post_meta( $page->ID, '_bu_seo_title', true ) ? get_post_meta( $page->ID, '_bu_seo_title', true ) : $seo['title'];
		$desc  = get_post_meta( $page->ID, '_bu_seo_desc', true ) ? get_post_meta( $page->ID, '_bu_seo_desc', true ) : $seo['desc'];
		update_post_meta( $page->ID, '_bu_seo_title', $title );
		update_post_meta( $page->ID, '_bu_seo_desc', $desc );
		update_post_meta( $page->ID, '_yoast_wpseo_title', $title );
		update_post_meta( $page->ID, '_yoast_wpseo_metadesc', $desc );
		bumbaru_yoast_rebuild( $page->ID, 'post' );
	}
	// Korpa, plaćanje i pretraga ne idu u Google.
	foreach ( array( (int) get_option( 'woocommerce_cart_page_id' ), (int) get_option( 'woocommerce_checkout_page_id' ), ( get_page_by_path( 'pretraga' ) ? get_page_by_path( 'pretraga' )->ID : 0 ) ) as $id ) {
		if ( $id ) {
			update_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', '1' );
		}
	}
}

/** Podešavanja Yoast-a za prodavnicu (samo kad je Yoast instaliran). */
function bumbaru_yoast_settings() {
	if ( ! bumbaru_yoast_active() ) {
		return false;
	}
	$titles = get_option( 'wpseo_titles', array() );
	$titles = is_array( $titles ) ? $titles : array();
	$titles = array_merge( $titles, array(
		'separator'                => 'sc-pipe',
		'company_or_person'        => 'company',
		'company_name'             => 'Bumbaru',
		'website_name'             => 'Bumbaru',
		'noindex-tax-product_tag'  => true,
		'disable-author'           => true,
		'disable-date'             => true,
		'disable-attachment'       => true,
		'noindex-author-wpseo'     => true,
		'breadcrumbs-enable'       => false,
	) );
	update_option( 'wpseo_titles', $titles );
	$social = get_option( 'wpseo_social', array() );
	$social = is_array( $social ) ? $social : array();
	$social['facebook_site'] = function_exists( 'bu_site' ) ? bu_site( 'facebook_url' ) : 'https://www.facebook.com/bumbaru.rs';
	$social['opengraph']     = true;
	update_option( 'wpseo_social', $social );
	$wpseo = get_option( 'wpseo', array() );
	$wpseo = is_array( $wpseo ) ? $wpseo : array();
	$wpseo['enable_xml_sitemap'] = true;
	update_option( 'wpseo', $wpseo );
	return true;
}

/** Sve odjednom: proizvodi, kategorije, strane i podešavanja. */
function bumbaru_yoast_sync_all() {
	foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
		bumbaru_yoast_sync_product( $id );
	}
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids' ) ) as $tid ) {
		bumbaru_yoast_sync_term( $tid );
	}
	bumbaru_yoast_sync_pages();
	bumbaru_yoast_settings();
}

// Čuvanje proizvoda i kategorije: Yoast polja se osvežavaju (ako ih niste menjali u Yoast-u ručno).
add_action( 'woocommerce_process_product_meta', function ( $id ) {
	bumbaru_yoast_sync_product( $id );
}, 30 );
add_action( 'edited_product_cat', function ( $term_id ) {
	bumbaru_yoast_sync_term( $term_id );
}, 30 );

// Kad se Yoast aktivira: odmah prenesi sve.
add_action( 'activated_plugin', function ( $plugin ) {
	if ( false !== strpos( $plugin, 'wp-seo.php' ) ) {
		update_option( 'bumbaru_yoast_pending', 1 );
	}
} );
add_action( 'admin_init', function () {
	if ( get_option( 'bumbaru_yoast_pending' ) && bumbaru_yoast_active() ) {
		delete_option( 'bumbaru_yoast_pending' );
		bumbaru_yoast_sync_all();
	}
} );

// Polja „SEO naslov” i „SEO opis” na stranama (Početna, Po meri, Pomoć…). Sa Yoast-om se koristi Yoast-ov okvir.
add_action( 'add_meta_boxes_page', function () {
	if ( bumbaru_yoast_active() ) {
		return;
	}
	add_meta_box( 'bumbaru-seo', 'Google (SEO)', function ( $post ) {
		wp_nonce_field( 'bumbaru_page_seo', 'bumbaru_page_seo_nonce' );
		$t = (string) get_post_meta( $post->ID, '_bu_seo_title', true );
		$d = (string) get_post_meta( $post->ID, '_bu_seo_desc', true );
		echo '<p><label for="bu-seo-title"><strong>SEO naslov</strong> <span class="description">(do 60 znakova sa „ | Bumbaru”)</span></label><input type="text" id="bu-seo-title" name="_bu_seo_title" value="' . esc_attr( $t ) . '" class="widefat" maxlength="70"></p>';
		echo '<p><label for="bu-seo-desc"><strong>SEO opis</strong> <span class="description">(120–158 znakova)</span></label><textarea id="bu-seo-desc" name="_bu_seo_desc" class="widefat" rows="3">' . esc_textarea( $d ) . '</textarea></p>';
		echo '<p class="description">Prazno = naslov i opis iz teme. Upisuje se i u Yoast ako ga kasnije instalirate.</p>';
	}, 'page', 'normal', 'low' );
} );
add_action( 'save_post_page', function ( $id ) {
	if ( ! isset( $_POST['bumbaru_page_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bumbaru_page_seo_nonce'] ), 'bumbaru_page_seo' ) || ! current_user_can( 'edit_page', $id ) ) {
		return;
	}
	foreach ( array( '_bu_seo_title' => '_yoast_wpseo_title', '_bu_seo_desc' => '_yoast_wpseo_metadesc' ) as $k => $y ) {
		$v = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '';
		update_post_meta( $id, $k, $v );
		if ( '' !== $v ) {
			update_post_meta( $id, $y, $v );
		}
	}
} );
