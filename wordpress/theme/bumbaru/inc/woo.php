<?php
/**
 * WooCommerce: korpa u zaglavlju, stilovi korpe i plaćanja, sitna podešavanja prikaza.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** Stranice na kojima se vide WooCommerce obrasci (korpa, plaćanje, nalog, porudžbina). */
function bu_is_woo_form_page() {
	return is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url();
}

// WooCommerce stilovi samo na korpi i plaćanju; ostale strane imaju dizajn teme.
add_filter( 'woocommerce_enqueue_styles', function ( $styles ) {
	if ( is_admin() || bu_is_woo_form_page() ) {
		return $styles;
	}
	return array();
} );

// Brzina: WooCommerce skripte (i jQuery) samo na korpi, plaćanju i nalogu.
// Kartice i strana proizvoda koriste skriptu teme, bez jQuery-ja.
add_action( 'wp_enqueue_scripts', function () {
	if ( bu_is_woo_form_page() ) {
		return;
	}
	foreach ( array( 'wc-add-to-cart', 'woocommerce', 'wc-cart-fragments', 'sourcebuster-js', 'wc-order-attribution', 'jquery-blockui', 'js-cookie', 'wc-jquery-blockui', 'wc-js-cookie', 'wc-single-product', 'zoom', 'flexslider', 'photoswipe', 'photoswipe-ui-default' ) as $h ) {
		wp_dequeue_script( $h );
	}
}, 99 );

// Broj proizvoda u korpi, osvežava se posle dodavanja bez ponovnog učitavanja strane.
function bu_cart_count_html() {
	$n = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	return '<span class="korpa-n cart-count" data-cart-count' . ( $n ? '' : ' hidden' ) . '>' . (int) $n . '</span>';
}

add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['span.cart-count'] = bu_cart_count_html();
	return $fragments;
} );

// Obaveštenja WooCommerce-a (dodato u korpu, greške) prikazujemo u dizajnu teme.
function bu_print_notices() {
	if ( function_exists( 'wc_print_notices' ) && wc_notice_count() > 0 ) {
		echo '<div class="bu-notices">';
		wc_print_notices();
		echo '</div>';
	}
}

// Prazna korpa: dugme vodi u katalog.
add_filter( 'woocommerce_return_to_shop_redirect', function () {
	return wc_get_page_permalink( 'shop' );
} );
add_filter( 'woocommerce_return_to_shop_text', function () {
	return 'Pogledajte katalog';
} );

// Slike u korpi: za ukras sa imenom, set i jelku pregled tačno onoga što je kupac izabrao, inače slika proizvoda.
add_filter( 'woocommerce_cart_item_thumbnail', function ( $thumb, $item ) {
	if ( ! empty( $item['bumbaru']['raw'] ) && function_exists( 'bumbaru_preview_svg' ) ) {
		$svg = bumbaru_preview_svg( $item['bumbaru']['raw'], 2 );
		if ( $svg ) {
			return '<span class="bu-cart-prev">' . $svg . '</span>'; // phpcs:ignore
		}
	}
	if ( empty( $item['data'] ) || ! $item['data']->get_image_id() ) {
		return '<span class="bu-cart-ph"><img src="' . esc_url( bu_img( 'brand/pcelica.webp' ) ) . '" alt="" width="48" height="48" /></span>';
	}
	return $thumb;
}, 10, 2 );

// Levo od zbira u korpi: predlozi da se stigne do besplatne dostave.
add_action( 'woocommerce_cart_collaterals', function () {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$in = array();
	foreach ( WC()->cart->get_cart() as $it ) {
		$in[] = (int) $it['product_id'];
	}
	$left = function_exists( 'bumbaru_free_ship_min' ) ? bumbaru_free_ship_min() - bumbaru_free_ship_cart_total() : 0;
	$all  = array_filter(
		wc_get_products( array( 'status' => 'publish', 'limit' => 40, 'exclude' => $in ) ),
		function ( $p ) {
			return '' !== $p->get_price() && $p->get_image_id();
		}
	);
	// Prvo oni koji sami pokriju ostatak do besplatne dostave (najjeftiniji takav), pa ostali od jeftinijih.
	usort( $all, function ( $a, $b ) use ( $left ) {
		$pa = (float) $a->get_price();
		$pb = (float) $b->get_price();
		$ca = $left > 0 && $pa >= $left ? 0 : 1;
		$cb = $left > 0 && $pb >= $left ? 0 : 1;
		return $ca === $cb ? $pa <=> $pb : $ca <=> $cb;
	} );
	$pick = array_slice( $all, 0, 3 );
	if ( ! $pick ) {
		echo '<div class="cart-suggest is-empty"></div>';
		return;
	}
	echo '<div class="cart-suggest">';
	echo '<p class="cs-kicker">' . ( $left > 0 ? 'Do besplatne dostave fali još ' . esc_html( wp_strip_all_tags( wc_price( $left ) ) ) : 'Dostava je već besplatna' ) . '</p>';
	echo '<h2 class="cs-title">' . ( $left > 0 ? 'Dodajte još nešto malo' : 'Uz ovo lepo ide' ) . '</h2><ul class="cs-list">';
	foreach ( $pick as $i => $p ) {
		$img = wp_get_attachment_image_src( $p->get_image_id(), 'woocommerce_thumbnail' );
		echo '<li class="cs-c' . (int) $i . '"><a href="' . esc_url( get_permalink( $p->get_id() ) ) . '">'
			. '<span class="cs-img">' . ( $img ? '<img src="' . esc_url( $img[0] ) . '" alt="" width="120" height="120" loading="lazy" />' : '' ) . '</span>'
			. '<span class="cs-txt"><strong>' . esc_html( $p->get_name() ) . '</strong><span class="cs-price">' . wp_kses_post( bu_price_html( $p ) ) . '</span></span>'
			. '<span class="cs-go">' . bu_icon( 'arrow', 18 ) . '</span></a></li>'; // phpcs:ignore
	}
	echo '</ul></div>';
}, 5 );

// Prazna korpa: veseo blok sa kategorijama.
add_action( 'woocommerce_cart_is_empty', function () {
	$cats = array( 'nova-godina' => 'Nova godina', 'pokloni-sa-imenom' => 'Pokloni sa imenom', 'privesci' => 'Privesci', 'radionica' => 'Radionica', 'auto-i-moto' => 'Auto i moto' );
	echo '<div class="empty-cart"><img class="ec-bee" src="' . esc_url( bu_img( 'brand/pcelica.webp' ) ) . '" alt="" width="320" height="320" /><p class="ec-title">Ovde još ništa ne zuji.</p><p class="ec-sub">Izaberite kategoriju i dodajte proizvod u korpu.</p><ul class="ec-cats">';
	$i = 0;
	foreach ( $cats as $slug => $label ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $t ) {
			echo '<li class="ec-c' . (int) ( $i++ % 5 ) . '"><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	echo '</ul></div>';
}, 20 );

// Bez WooCommerce putanje i omotača: šabloni teme imaju svoje.
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

// Na korpi se ne nudi „cross-sell” blok WooCommerce-a (dizajn ga nema).
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
