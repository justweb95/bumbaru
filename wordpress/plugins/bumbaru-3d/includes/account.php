<?php
/**
 * Moj nalog (profil kupca) i srpske adrese strana.
 *
 * Kupovina bez naloga ostaje kao do sada. Kupac koji želi nalog pravi ga na strani
 * „Moj nalog” ili kućicom na plaćanju, pa vidi svoje porudžbine, adrese i podatke.
 * Sve adrese su na srpskom: /moj-nalog/porudzbine/, /moj-nalog/adrese/, /strana/2/…
 */

defined( 'ABSPATH' ) || exit;

/** Srpske adrese za delove naloga i plaćanja (WooCommerce „endpoints”). */
function bumbaru_account_endpoints() {
	return array(
		'woocommerce_checkout_pay_endpoint'                         => 'placanje-porudzbine',
		'woocommerce_checkout_order_received_endpoint'              => 'porudzbina-primljena',
		'woocommerce_myaccount_add_payment_method_endpoint'         => 'dodaj-nacin-placanja',
		'woocommerce_myaccount_delete_payment_method_endpoint'      => 'obrisi-nacin-placanja',
		'woocommerce_myaccount_set_default_payment_method_endpoint' => 'podrazumevani-nacin-placanja',
		'woocommerce_myaccount_orders_endpoint'                     => 'porudzbine',
		'woocommerce_myaccount_view_order_endpoint'                 => 'pregled-porudzbine',
		'woocommerce_myaccount_downloads_endpoint'                  => 'preuzimanja',
		'woocommerce_myaccount_edit_account_endpoint'               => 'detalji-naloga',
		'woocommerce_myaccount_edit_address_endpoint'               => 'adrese',
		'woocommerce_myaccount_payment_methods_endpoint'            => 'nacini-placanja',
		'woocommerce_myaccount_lost_password_endpoint'              => 'zaboravljena-lozinka',
		'woocommerce_logout_endpoint'                               => 'odjava',
	);
}

/**
 * Strana „Moj nalog” i podešavanja naloga. Poziva se iz podešavanja prodavnice
 * i jednom posle nadogradnje plugina (postojeći sajt dobija nalog bez ponovnog podešavanja).
 */
function bumbaru_setup_account() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}
	$done = array();
	$id   = bumbaru_ensure_page( 'moj-nalog', 'Moj nalog', '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->', (int) get_option( 'woocommerce_myaccount_page_id' ) );
	update_option( 'woocommerce_myaccount_page_id', $id );

	$set = array(
		// Nalog: kupac sam bira lozinku, korisničko ime se pravi od email adrese.
		'woocommerce_enable_myaccount_registration'         => 'yes',
		'woocommerce_registration_generate_username'        => 'yes',
		'woocommerce_registration_generate_password'        => 'no',
		// Na plaćanju: kupovina bez naloga, uz kućicu „Napravite nalog” i link za prijavu.
		'woocommerce_enable_guest_checkout'                 => 'yes',
		'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
		'woocommerce_enable_checkout_login_reminder'        => 'yes',
		'woocommerce_registration_privacy_policy_text'      => 'Podatke iz naloga koristimo samo za vaše porudžbine i dostavu, kako piše u [privacy_policy].',
		'woocommerce_erasure_request_removes_order_data'    => 'no',
	);
	foreach ( array_merge( $set, bumbaru_account_endpoints() ) as $k => $v ) {
		update_option( $k, $v );
	}
	// Osnove WordPress adresa na srpskom (kategorije i oznake bloga se ne koriste, ali ni one nisu „category”).
	if ( ! get_option( 'category_base' ) ) {
		update_option( 'category_base', 'kategorija' );
	}
	if ( ! get_option( 'tag_base' ) ) {
		update_option( 'tag_base', 'tema' );
	}
	// Podrazumevane kategorije („Uncategorized”) dobijaju srpski naziv i adresu.
	$dc = get_term( (int) get_option( 'default_product_cat' ), 'product_cat' );
	if ( $dc && ! is_wp_error( $dc ) && in_array( $dc->slug, array( 'uncategorized', 'nekategorizovano' ), true ) ) {
		wp_update_term( $dc->term_id, 'product_cat', array( 'name' => 'Ostalo', 'slug' => 'ostalo' ) );
	}
	$pc = get_term( (int) get_option( 'default_category' ), 'category' );
	if ( $pc && ! is_wp_error( $pc ) && 'uncategorized' === $pc->slug ) {
		wp_update_term( $pc->term_id, 'category', array( 'name' => 'Bez kategorije', 'slug' => 'bez-kategorije' ) );
	}
	// Stara podrazumevana strana WooCommerce-a „Refund and Returns Policy” (nacrt) dobija srpsku adresu.
	$rr = get_page_by_path( 'refund_returns' );
	if ( $rr && 'publish' !== $rr->post_status ) {
		wp_update_post( array( 'ID' => $rr->ID, 'post_name' => 'povracaj-i-reklamacije', 'post_title' => 'Povraćaj i reklamacije' ) );
	}
	update_option( 'bumbaru_flush_rewrite', 1 );
	$done[] = 'Moj nalog (/moj-nalog/): prijava, registracija, porudžbine, adrese i podaci naloga. Kupovina bez naloga ostaje.';
	$done[] = 'Adrese na srpskom: /moj-nalog/porudzbine/, /moj-nalog/adrese/, /moj-nalog/detalji-naloga/, /moj-nalog/odjava/, /strana/2/…';
	return $done;
}

// Osnove adresa koje WordPress ne čuva u podešavanjima: /strana/2/, /autor/…, /trazi/…
add_action( 'init', function () {
	global $wp_rewrite;
	if ( ! $wp_rewrite ) {
		return;
	}
	$wp_rewrite->pagination_base          = 'strana';
	$wp_rewrite->author_base              = 'autor';
	$wp_rewrite->search_base              = 'trazi';
	$wp_rewrite->comments_base            = 'komentari';
	$wp_rewrite->comments_pagination_base = 'komentari-strana';
}, 1 );

// Adrese u nalogu: /moj-nalog/adrese/kupac/ i /moj-nalog/adrese/dostava/ (umesto billing i shipping).
add_filter( 'woocommerce_edit_address_slugs', function () {
	return array( 'billing' => 'kupac', 'shipping' => 'dostava' );
} );

// Meni naloga: „Preuzimanja” samo ako kupac zaista ima nešto za preuzimanje (prodajemo fizičke proizvode).
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	if ( isset( $items['downloads'] ) && is_user_logged_in() && function_exists( 'wc_get_customer_available_downloads' ) && ! wc_get_customer_available_downloads( get_current_user_id() ) ) {
		unset( $items['downloads'] );
	}
	if ( isset( $items['edit-address'] ) ) {
		$items['edit-address'] = 'Adrese';
	}
	return $items;
}, 20 );

// Posle prijave i registracije kupac ide na svoj nalog (ili nazad na plaćanje, kad je tamo bio).
add_filter( 'woocommerce_login_redirect', function ( $redirect ) {
	return $redirect ? $redirect : wc_get_page_permalink( 'myaccount' );
}, 10 );

// Stare engleske adrese (iz starih emailova, obeleživača ili Google-a) vode na srpske: /my-account/orders/ → /moj-nalog/porudzbine/.
add_action( 'template_redirect', function () {
	if ( ! is_404() || ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}
	$path  = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore
	$parts = explode( '/', $path );
	$pages = array( 'my-account' => 'myaccount', 'cart' => 'cart', 'checkout' => 'checkout', 'shop' => 'shop' );
	if ( ! isset( $pages[ $parts[0] ] ) ) {
		return;
	}
	$to = wc_get_page_permalink( $pages[ $parts[0] ] );
	if ( isset( $parts[1] ) && in_array( $parts[0], array( 'my-account', 'checkout' ), true ) ) {
		$map = array(
			'orders' => 'porudzbine', 'view-order' => 'pregled-porudzbine', 'downloads' => 'preuzimanja', 'edit-address' => 'adrese',
			'edit-account' => 'detalji-naloga', 'lost-password' => 'zaboravljena-lozinka', 'customer-logout' => 'odjava',
			'payment-methods' => 'nacini-placanja', 'order-received' => 'porudzbina-primljena', 'order-pay' => 'placanje-porudzbine',
		);
		$rest = array_slice( $parts, 1 );
		$rest[0] = isset( $map[ $rest[0] ] ) ? $map[ $rest[0] ] : $rest[0];
		if ( isset( $rest[1] ) && 'adrese' === $rest[0] ) {
			$rest[1] = 'billing' === $rest[1] ? 'kupac' : ( 'shipping' === $rest[1] ? 'dostava' : $rest[1] );
		}
		$to = trailingslashit( $to ) . implode( '/', array_map( 'rawurlencode', $rest ) ) . '/';
	}
	$q = isset( $_SERVER['QUERY_STRING'] ) ? (string) wp_unslash( $_SERVER['QUERY_STRING'] ) : ''; // phpcs:ignore
	wp_safe_redirect( $to . ( $q ? '?' . $q : '' ), 301 );
	exit;
} );
