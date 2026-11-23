<?php
/**
 * Bumbaru tema.
 *
 * Izgled je prenet 1:1 sa Astro sajta. Proizvodi, korpa i plaćanje su WooCommerce,
 * a konfiguratori (ukras sa imenom, set, privesci, jelke sa zvezdicom), boje i kalkulator
 * cene su u pluginu „Bumbaru 3D”.
 */

defined( 'ABSPATH' ) || exit;

define( 'BUMBARU_THEME_VERSION', '2.5.4' );

require_once __DIR__ . '/inc/template-tags.php';
require_once __DIR__ . '/inc/seo.php';
require_once __DIR__ . '/inc/woo.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 900,
		'single_image_width'    => 1200,
		'product_grid'          => array( 'default_columns' => 4 ),
	) );
	register_nav_menus( array(
		'primary' => 'Glavni meni (zaglavlje)',
	) );
} );

// Stilovi i skripte.
add_action( 'wp_enqueue_scripts', function () {
	$v   = BUMBARU_THEME_VERSION;
	$uri = get_template_directory_uri();
	wp_enqueue_style( 'bumbaru-main', $uri . '/assets/css/main.css', array(), $v );
	wp_enqueue_style( 'bumbaru-shop', $uri . '/assets/css/shop.css', array( 'bumbaru-main' ), $v );
	wp_enqueue_style( 'bumbaru-fun', $uri . '/assets/css/fun.css', array( 'bumbaru-shop' ), $v );
	// Boje „kao novi sajt” (Bumbaru 3D → Podešavanja sajta → Boje sajta): topla siva i krem umesto bele.
	if ( 'nova' === bu_site( 'paleta' ) ) {
		wp_add_inline_style( 'bumbaru-fun', ':root{--surface:#C0B8AD;--surface-raised:#D9D3CA;--surface-paper:#EEEAE3;--line:#ADA498;--shadow:0 2px 0 rgba(49,32,24,.10),0 12px 28px rgba(49,32,24,.14)}' );
	}
	wp_enqueue_script( 'bumbaru-site', $uri . '/assets/js/site.js', array(), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'bumbaru-site', 'BUMBARU', array(
		'messengerPage' => bu_site( 'messenger_page' ),
		'ajaxAddToCart' => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'add_to_cart' ) : '',
		'cartUrl'       => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
		'checkoutUrl'   => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
		'bee'           => bu_img( 'brand/pcelica.webp' ),
	) );

	// Blok stilovi WordPress-a nisu potrebni (tema ne koristi blokove).
	foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles', 'wc-blocks-style' ) as $h ) {
		wp_dequeue_style( $h );
	}
}, 20 );

// WordPress emoji skripte isključene: sajt ne zove spoljne servise.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'emoji_svg_url', '__return_false' );

// Klasa šablona na <body>, za stilove pojedinih strana.
add_filter( 'body_class', function ( $classes ) {
	$tpl = bu_template_class();
	if ( $tpl ) {
		$classes[] = $tpl;
	}
	return $classes;
} );

function bu_template_class() {
	if ( is_front_page() ) {
		return 'tpl-home';
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		return 'tpl-product';
	}
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		return 'tpl-cat';
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return 'tpl-katalog';
	}
	if ( is_404() ) {
		return 'tpl-404';
	}
	if ( is_page( 'po-meri' ) ) {
		return 'tpl-pomeri';
	}
	if ( is_page( 'pomoc' ) ) {
		return 'tpl-pomoc';
	}
	if ( is_page( 'pretraga' ) ) {
		return 'tpl-search';
	}
	if ( is_page( 'politika-privatnosti' ) ) {
		return 'tpl-doc';
	}
	if ( is_page( 'akcija' ) ) {
		return 'tpl-akcija';
	}
	if ( is_page( 'nadji-poklon' ) ) {
		return 'tpl-kviz';
	}
	return 'tpl-page';
}

// Ikonice i manifest.
add_action( 'wp_head', function () {
	$b = bu_img( 'brand/' );
	echo '<meta name="theme-color" content="#F5C137" />' . "\n";
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( $b . 'favicon.ico' ) . '" sizes="any" />' . "\n";
		echo '<link rel="icon" href="' . esc_url( $b . 'favicon-32.png' ) . '" type="image/png" sizes="32x32" />' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( $b . 'apple-touch-icon.png' ) . '" />' . "\n";
	}
	// Fontovi se učitavaju odmah, da tekst ne „skače”.
	$f = get_template_directory_uri() . '/assets/fonts/';
	foreach ( array( 'geologica-latin-400-normal', 'geologica-latin-800-normal', 'geologica-latin-ext-400-normal' ) as $font ) {
		echo '<link rel="preload" href="' . esc_url( $f . $font . '.woff2' ) . '" as="font" type="font/woff2" crossorigin />' . "\n";
	}
}, 2 );

// Stranice su široke kao dizajn, bez WordPress margina za admin traku na telefonu.
add_action( 'wp_head', function () {
	if ( is_admin_bar_showing() ) {
		echo '<style>.header{top:32px}@media(max-width:782px){.header{top:0}}</style>' . "\n";
	}
}, 99 );
