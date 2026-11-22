<?php
/**
 * SEO: naslovi, opisi, kanonski link, og:image i strukturisani podaci, isto kao na Astro sajtu.
 * Ako se instalira Yoast SEO ili Rank Math, ovaj deo se sam isključuje.
 */

defined( 'ABSPATH' ) || exit;

function bu_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'SEOPRESS_VERSION' );
}

/** Šabloni mogu da postave naslov i opis pre get_header(). */
function bu_set_seo( $title, $desc, $extra = array() ) {
	$GLOBALS['bu_seo'] = array_merge( array( 'title' => $title, 'desc' => $desc ), $extra );
}

function bu_add_jsonld( $data ) {
	$GLOBALS['bu_jsonld'][] = $data;
}

function bu_seo_data() {
	$d = isset( $GLOBALS['bu_seo'] ) ? $GLOBALS['bu_seo'] : array();
	$d = wp_parse_args( $d, array(
		'title'     => '',
		'desc'      => '',
		'image'     => bu_img( 'og/bumbaru.jpg' ),
		'image_alt' => 'Bumbaru: ukrasi za jelku sa imenom',
		'type'      => 'website',
		'noindex'   => false,
	) );

	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		if ( $p ) {
			$d['title'] = bu_pmeta( $p, 'seo_title' ) ? bu_pmeta( $p, 'seo_title' ) : $p->get_name() . ' | Bumbaru';
			$d['desc']  = bu_pmeta( $p, 'seo_desc' ) ? bu_pmeta( $p, 'seo_desc' ) : wp_strip_all_tags( $p->get_short_description() );
			$d['type']  = 'product';
			$og         = (int) bu_pmeta( $p, 'og_image' );
			$src        = $og ? wp_get_attachment_image_src( $og, 'full' ) : ( $p->get_image_id() ? wp_get_attachment_image_src( $p->get_image_id(), 'full' ) : false );
			if ( $src ) {
				$d['image'] = $src[0];
			}
			$imgs = bu_product_images( $p );
			$d['image_alt'] = ! empty( $imgs[0]['alt'] ) ? $imgs[0]['alt'] : $p->get_name();
		}
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$t          = get_queried_object();
		$d['title'] = bu_tmeta( $t, 'seo_title' ) ? bu_tmeta( $t, 'seo_title' ) : $t->name . ' | Bumbaru';
		$d['desc']  = bu_tmeta( $t, 'seo_desc' ) ? bu_tmeta( $t, 'seo_desc' ) : wp_strip_all_tags( term_description( $t ) );
	} elseif ( function_exists( 'is_shop' ) && is_shop() && ! $d['title'] ) {
		$d['title'] = 'Katalog: ukrasi, pokloni sa imenom, radionica | Bumbaru';
		$d['desc']  = 'Ceo Bumbaru katalog: novogodišnji ukrasi i dekoracija, personalizovani pokloni, držači za Parkside alat i oprema za auto. Besplatna dostava od 2.000 din.';
	} elseif ( is_singular() && ! $d['title'] ) {
		$id         = get_queried_object_id();
		$d['title'] = get_post_meta( $id, '_bu_seo_title', true ) ? get_post_meta( $id, '_bu_seo_title', true ) : get_the_title( $id ) . ' | Bumbaru';
		$d['desc']  = get_post_meta( $id, '_bu_seo_desc', true );
	}
	// Naslov i opis strane iz admina (Bumbaru 3D ih upisuje pri podešavanju) imaju prednost.
	$page_id = 0;
	if ( is_front_page() && get_option( 'page_on_front' ) ) {
		$page_id = (int) get_option( 'page_on_front' );
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$page_id = (int) get_option( 'woocommerce_shop_page_id' );
	} elseif ( is_page() ) {
		$page_id = get_queried_object_id();
	}
	if ( $page_id && empty( $d['noindex'] ) ) {
		$mt = (string) get_post_meta( $page_id, '_bu_seo_title', true );
		$md = (string) get_post_meta( $page_id, '_bu_seo_desc', true );
		if ( '' !== $mt ) {
			$d['title'] = $mt;
		}
		if ( '' !== $md ) {
			$d['desc'] = $md;
		}
	}
	if ( is_404() ) {
		$d['title']   = 'Stranica ne postoji | Bumbaru';
		$d['desc']    = 'Ova stranica ne postoji. Pogledajte katalog ili pišite nam na Messenger-u.';
		$d['noindex'] = true;
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		$d['noindex'] = true;
	}
	// Oznake (filteri) i kategorija „uskoro” nemaju svoj sadržaj: ne idu u Google.
	if ( is_tax( 'product_tag' ) || ( function_exists( 'is_product_category' ) && is_product_category() && bu_tmeta( get_queried_object(), 'soon' ) ) ) {
		$d['noindex'] = true;
	}
	if ( is_page( 'pretraga' ) ) {
		$d['noindex'] = true;
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		if ( $p && '' !== $p->get_price() ) {
			$d['price']    = bumbaru_seo_price( $p );
			$d['in_stock'] = $p->is_in_stock();
		}
	}
	return $d;
}

/** Cena za og:price i schema (akcijska ako postoji). */
function bumbaru_seo_price( $p ) {
	return (float) $p->get_price();
}

/** noindex ide u WordPress robots meta (jedan tag za celu stranu). */
add_filter( 'wp_robots', function ( $robots ) {
	if ( bu_seo_plugin_active() ) {
		return $robots;
	}
	$d = bu_seo_data();
	if ( ! empty( $d['noindex'] ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
} );

if ( ! bu_seo_plugin_active() ) {
	add_filter( 'pre_get_document_title', function ( $title ) {
		$d = bu_seo_data();
		return $d['title'] ? $d['title'] : $title;
	}, 20 );

	remove_action( 'wp_head', 'rel_canonical' );

	add_action( 'wp_head', function () {
		$d   = bu_seo_data();
		$url = bu_current_url();
		if ( $d['desc'] ) {
			echo '<meta name="description" content="' . esc_attr( $d['desc'] ) . '" />' . "\n";
		}
		echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
		$title = $d['title'] ? $d['title'] : wp_get_document_title();
		$tags  = array(
			'og:site_name'   => 'Bumbaru',
			'og:locale'      => 'sr_RS',
			'og:type'        => $d['type'],
			'og:title'       => $title,
			'og:description' => $d['desc'],
			'og:url'         => $url,
			'og:image'       => $d['image'],
			'og:image:alt'   => $d['image_alt'],
		);
		foreach ( $tags as $k => $v ) {
			if ( '' !== $v ) {
				echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '" />' . "\n";
			}
		}
		if ( isset( $d['price'] ) ) {
			echo '<meta property="product:price:amount" content="' . esc_attr( $d['price'] ) . '" />' . "\n";
			echo '<meta property="product:price:currency" content="RSD" />' . "\n";
			echo '<meta property="product:availability" content="' . ( $d['in_stock'] ? 'in stock' : 'out of stock' ) . '" />' . "\n";
		}
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";

		$ld = array(
			array(
				'@context'               => 'https://schema.org',
				'@type'                  => 'OnlineStore',
				'@id'                    => home_url( '/#store' ),
				'name'                   => 'Bumbaru',
				'url'                    => home_url( '/' ),
				'logo'                   => bu_img( 'brand/favicon-512.png' ),
				'description'            => 'Ukrasi za jelku sa imenom, personalizovani pokloni i 3D štampa po meri. Plaćanje pouzećem, besplatna dostava od 2.000 din.',
				'address'                => array( '@type' => 'PostalAddress', 'addressLocality' => 'Leskovac', 'addressCountry' => 'RS' ),
				'areaServed'             => 'RS',
				'sameAs'                 => array_values( array_filter( array( bu_site( 'facebook_url' ), bu_site( 'instagram_url' ) ) ) ),
				'hasMerchantReturnPolicy' => bu_return_policy( false ),
			),
			array( '@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Bumbaru', 'url' => home_url( '/' ), 'inLanguage' => 'sr-Latn' ),
		);
		foreach ( $ld as $x ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		}
	}, 1 );
}

// Strukturisani podaci strane (proizvod, kategorija, pitanja) idu i kad je instaliran Yoast:
// Yoast (besplatni) nema Product schema, a ovi se ne dupliraju sa njegovim.
add_action( 'wp_head', function () {
	if ( empty( $GLOBALS['bu_jsonld'] ) ) {
		return;
	}
	foreach ( $GLOBALS['bu_jsonld'] as $x ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}, 2 );

/** Pravila povraćaja za Google (14 dana; proizvodi po meri se ne vraćaju zbog predomišljanja). */
function bu_return_policy( $custom ) {
	if ( $custom ) {
		return array( '@type' => 'MerchantReturnPolicy', 'applicableCountry' => 'RS', 'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted' );
	}
	return array(
		'@type'                => 'MerchantReturnPolicy',
		'applicableCountry'    => 'RS',
		'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
		'merchantReturnDays'   => 14,
		'returnMethod'         => 'https://schema.org/ReturnByMail',
		'returnFees'           => 'https://schema.org/ReturnShippingFees',
	);
}

/** Iznos za besplatnu dostavu (podešava se u WooCommerce → Dostava; plugin Bumbaru 3D ga čita). */
function bu_free_ship_min() {
	return function_exists( 'bumbaru_free_ship_min' ) ? (float) bumbaru_free_ship_min() : 2000.0;
}

/** „2.000 din” */
function bu_free_ship_label() {
	return number_format( bu_free_ship_min(), 0, ',', '.' ) . ' din';
}

/** Besplatna dostava Post Express u Srbiji (za Google), samo kad cena proizvoda prelazi prag. */
function bu_shipping_details( $custom, $price = 0 ) {
	if ( $price < bu_free_ship_min() ) {
		return null;
	}
	return array(
		'@type'               => 'OfferShippingDetails',
		'shippingRate'        => array( '@type' => 'MonetaryAmount', 'value' => 0, 'currency' => 'RSD' ),
		'shippingDestination' => array( '@type' => 'DefinedRegion', 'addressCountry' => 'RS' ),
		'deliveryTime'        => array(
			'@type'        => 'ShippingDeliveryTime',
			'handlingTime' => array( '@type' => 'QuantitativeValue', 'minValue' => $custom ? 3 : 1, 'maxValue' => $custom ? 5 : 3, 'unitCode' => 'DAY' ),
			'transitTime'  => array( '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 2, 'unitCode' => 'DAY' ),
		),
	);
}

// Mapa sajta: samo strane koje treba da budu u Google-u.
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );
add_filter( 'wp_sitemaps_taxonomies', function ( $tax ) {
	unset( $tax['product_tag'], $tax['category'], $tax['post_tag'] );
	return $tax;
} );
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$skip = array();
		foreach ( array( 'korpa', 'placanje', 'pretraga', 'my-account', 'moj-nalog', 'cart', 'checkout' ) as $slug ) {
			$pg = get_page_by_path( $slug );
			if ( $pg ) {
				$skip[] = $pg->ID;
			}
		}
		foreach ( array( 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_myaccount_page_id' ) as $opt ) {
			if ( get_option( $opt ) ) {
				$skip[] = (int) get_option( $opt );
			}
		}
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $skip );
	}
	return $args;
}, 10, 2 );
add_filter( 'wp_sitemaps_taxonomies_query_args', function ( $args, $taxonomy ) {
	if ( 'product_cat' === $taxonomy ) {
		$soon = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids', 'meta_key' => 'bu_soon', 'meta_value' => '1' ) );
		$args['exclude'] = array_merge( is_wp_error( $soon ) ? array() : $soon, array( (int) get_option( 'default_product_cat' ) ) );
	}
	return $args;
}, 10, 2 );

// robots.txt: pretraga, korpa i plaćanje ne treba da troše vreme Google-a.
add_filter( 'robots_txt', function ( $txt, $public ) {
	if ( ! $public ) {
		return $txt;
	}
	$add = "Disallow: /pretraga/\nDisallow: /korpa/\nDisallow: /placanje/\nDisallow: /*?s=\n";
	return preg_replace( '/(User-agent: \*\n)/', '$1' . $add, $txt, 1 );
}, 20, 2 );

// Stranice autora (otkrivaju korisničko ime) i prazne arhive vode na početnu.
add_action( 'template_redirect', function () {
	if ( is_author() || ( is_date() && ! is_admin() ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );

// WooCommerce ima svoje strukturisane podatke za proizvod; tema daje svoje (isti kao Astro), pa se ne dupliraju.
add_action( 'init', function () {
	if ( function_exists( 'WC' ) && isset( WC()->structured_data ) ) {
		remove_action( 'woocommerce_single_product_summary', array( WC()->structured_data, 'generate_product_data' ), 60 );
	}
} );
