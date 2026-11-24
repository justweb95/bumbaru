<?php
/**
 * Podešavanje prodavnice jednim klikom:
 * dinari, Srbija, plaćanje pouzećem, Post Express (besplatno od 2.500 din), stranice i linkovi kao na Astro sajtu.
 */

defined( 'ABSPATH' ) || exit;

/** Napravi ili pronađi stranicu; vraća ID. */
function bumbaru_ensure_page( $slug, $title, $content = '', $reuse_id = 0 ) {
	$page = $reuse_id ? get_post( $reuse_id ) : null;
	if ( ! $page || 'page' !== $page->post_type || 'trash' === $page->post_status ) {
		$page = get_page_by_path( $slug );
	}
	$data = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => $content,
	);
	if ( $page ) {
		$data['ID'] = $page->ID;
		// Postojeći tekst stranice se ne briše (osim za korpu i plaćanje, gde mora da stoji šifra obrasca).
		if ( '' === $content ) {
			unset( $data['post_content'] );
		}
		wp_update_post( $data );
		return (int) $page->ID;
	}
	return (int) wp_insert_post( $data );
}

/**
 * Glavno podešavanje. $opts: switch_theme (bool).
 * Vraća spisak urađenog (za prikaz u administraciji).
 */
function bumbaru_setup_store( $opts = array() ) {
	$done = array();
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array( 'WooCommerce nije aktivan.' );
	}

	// Valuta, zemlja, format cene („1.590 din”).
	$set = array(
		'woocommerce_currency'                      => 'RSD',
		'woocommerce_currency_pos'                  => 'right_space',
		'woocommerce_price_thousand_sep'            => '.',
		'woocommerce_price_decimal_sep'             => ',',
		'woocommerce_price_num_decimals'            => '0',
		'woocommerce_default_country'               => 'RS',
		'woocommerce_store_city'                    => 'Leskovac',
		'woocommerce_store_postcode'                => '16000',
		'woocommerce_allowed_countries'             => 'specific',
		'woocommerce_specific_allowed_countries'    => array( 'RS' ),
		'woocommerce_ship_to_countries'             => '',
		'woocommerce_ship_to_destination'           => 'billing',
		'woocommerce_calc_taxes'                    => 'no',
		'woocommerce_enable_coupons'                => 'no',
		'woocommerce_enable_guest_checkout'         => 'yes',
		'woocommerce_cart_redirect_after_add'       => 'no',
		'woocommerce_enable_ajax_add_to_cart'       => 'yes',
		'woocommerce_enable_shipping_calc'          => 'no',
		'woocommerce_shipping_cost_requires_address' => 'no',
		'woocommerce_coming_soon'                   => 'no',
		'woocommerce_store_pages_only'              => 'no',
		'woocommerce_email_from_name'               => 'Bumbaru',
		'woocommerce_weight_unit'                   => 'g',
		'woocommerce_dimension_unit'                => 'cm',
		'woocommerce_checkout_privacy_policy_text'  => 'Podatke iz porudžbine koristimo samo za izradu i dostavu paketa, kako piše u [privacy_policy].',
		'woocommerce_registration_privacy_policy_text' => '',
		'woocommerce_checkout_phone_field'          => 'required',
		'woocommerce_feature_order_attribution_enabled' => 'no',
		'woocommerce_allow_tracking'                => 'no',
		'woocommerce_checkout_company_field'        => 'hidden',
		'woocommerce_checkout_address_2_field'      => 'optional',
		'timezone_string'                           => 'Europe/Belgrade',
		'date_format'                               => 'j. n. Y.',
		'time_format'                               => 'H:i',
		'start_of_week'                             => 1,
		'blogname'                                  => 'Bumbaru',
		'blogdescription'                           => 'Ukrasi za jelku sa imenom i 3D štampa po meri',
	);
	foreach ( $set as $k => $v ) {
		update_option( $k, $v );
	}
	$done[] = 'Valuta: dinar (1.590 din), prodaja samo u Srbiji, bez kupona; kupovina i bez naloga.';

	// Plaćanje pouzećem (jedini način plaćanja).
	update_option( 'woocommerce_cod_settings', array(
		'enabled'            => 'yes',
		'title'              => 'Plaćanje pouzećem',
		'description'        => 'Plaćate gotovinom kuriru kad paket stigne.',
		'instructions'       => 'Plaćate pouzećem: gotovinom kuriru kad paket stigne. Javljamo vam se kad paket krene.',
		'enable_for_methods' => array(),
		'enable_for_virtual' => 'yes',
	) );
	foreach ( array( 'bacs', 'cheque', 'paypal' ) as $g ) {
		$s = get_option( 'woocommerce_' . $g . '_settings', array() );
		if ( ! is_array( $s ) ) {
			$s = array();
		}
		$s['enabled'] = 'no';
		update_option( 'woocommerce_' . $g . '_settings', $s );
	}
	$order = array( 'cod' => 0, 'bacs' => 1, 'cheque' => 2 );
	update_option( 'woocommerce_gateway_order', $order );
	$done[] = 'Plaćanje: pouzećem (ostali načini isključeni).';

	// Dostava: zona Srbija. Od 2.000 din besplatna Post Express, ispod toga kupac plaća kuriru po cenovniku pošte.
	$zone = null;
	foreach ( WC_Shipping_Zones::get_zones() as $z ) {
		if ( 'Srbija' === $z['zone_name'] ) {
			$zone = new WC_Shipping_Zone( $z['id'] );
			break;
		}
	}
	if ( ! $zone ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Srbija' );
		$zone->set_zone_order( 0 );
		$zone->save();
	}
	$zone->set_locations( array( array( 'code' => 'RS', 'type' => 'country' ) ) );
	$zone->save();
	$free = 0;
	$paid = 0;
	foreach ( $zone->get_shipping_methods() as $m ) {
		if ( 'free_shipping' === $m->id ) {
			$free = $m->get_instance_id();
		}
		if ( 'flat_rate' === $m->id ) {
			$paid = $m->get_instance_id();
		}
	}
	if ( ! $free ) {
		$free = $zone->add_shipping_method( 'free_shipping' );
	}
	if ( ! $paid ) {
		$paid = $zone->add_shipping_method( 'flat_rate' );
	}
	$min = (int) get_option( 'bumbaru_free_ship_min', 2500 );
	if ( $free ) {
		$old = (array) get_option( 'woocommerce_free_shipping_' . $free . '_settings', array() );
		// Iznos koji ste sami promenili u WooCommerce → Dostava ostaje.
		$keep = isset( $old['requires'] ) && 'min_amount' === $old['requires'] && (float) $old['min_amount'] > 0;
		update_option( 'woocommerce_free_shipping_' . $free . '_settings', array(
			'title'            => 'Post Express – besplatna dostava',
			'requires'         => 'min_amount',
			'min_amount'       => $keep ? $old['min_amount'] : (string) $min,
			'ignore_discounts' => 'no',
		) );
	}
	if ( $paid ) {
		update_option( 'woocommerce_flat_rate_' . $paid . '_settings', array(
			'title'      => BUMBARU_PAID_SHIP_TITLE,
			'tax_status' => 'none',
			'cost'       => '0',
		) );
	}
	WC_Cache_Helper::get_transient_version( 'shipping', true );
	$done[] = 'Dostava: zona „Srbija”, Post Express, besplatna od ' . bumbaru_free_ship_label() . '; ispod toga kupac plaća kuriru po cenovniku pošte.';

	// Stranice (iste adrese kao na Astro sajtu) i WooCommerce stranice.
	$home    = bumbaru_ensure_page( 'pocetna', 'Početna' );
	$shop    = bumbaru_ensure_page( 'katalog', 'Katalog', '', (int) get_option( 'woocommerce_shop_page_id' ) );
	$cart    = bumbaru_ensure_page( 'korpa', 'Korpa', '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->', (int) get_option( 'woocommerce_cart_page_id' ) );
	$check   = bumbaru_ensure_page( 'placanje', 'Plaćanje', '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->', (int) get_option( 'woocommerce_checkout_page_id' ) );
	$pomeri  = bumbaru_ensure_page( 'po-meri', 'Po meri' );
	$pomoc   = bumbaru_ensure_page( 'pomoc', 'Pomoć' );
	$privacy = bumbaru_ensure_page( 'politika-privatnosti', 'Politika privatnosti', '', (int) get_option( 'wp_page_for_privacy_policy' ) );
	$search  = bumbaru_ensure_page( 'pretraga', 'Pretraga' );
	bumbaru_ensure_new_pages();
	$done  = array_merge( $done, bumbaru_setup_account() );
	update_option( 'woocommerce_shop_page_id', $shop );
	update_option( 'woocommerce_cart_page_id', $cart );
	update_option( 'woocommerce_checkout_page_id', $check );
	update_option( 'wp_page_for_privacy_policy', $privacy );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	// Tekst politike privatnosti je u temi; prazna stranica prikazuje taj tekst.
	$pp = get_post( $privacy );
	if ( $pp && false !== strpos( $pp->post_content, 'wp:heading' ) && false !== strpos( $pp->post_content, 'Suggested text' ) ) {
		wp_update_post( array( 'ID' => $privacy, 'post_content' => '' ) );
	}
	// Stara podrazumevana strana „Sample Page” i „Hello world” nisu potrebne.
	foreach ( array( 'sample-page', 'primer-stranice' ) as $s ) {
		$p = get_page_by_path( $s );
		if ( $p ) {
			wp_trash_post( $p->ID );
		}
	}
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello ) {
		wp_trash_post( $hello->ID );
	}
	$done[] = 'Stranice: Početna, Katalog (/katalog/), Korpa (/korpa/), Plaćanje (/placanje/), Po meri, Pomoć, Politika privatnosti, Pretraga, Akcija, Nađi poklon, Moj nalog (/moj-nalog/).';

	// Linkovi kao na Astro sajtu: /proizvod/<naziv>/ i /katalog/<kategorija>/.
	update_option( 'permalink_structure', '/%postname%/' );
	$perma                     = (array) get_option( 'woocommerce_permalinks', array() );
	$perma['product_base']     = '/proizvod';
	$perma['category_base']    = 'katalog';
	$perma['tag_base']         = 'oznaka';
	$perma['attribute_base']   = '';
	$perma['use_verbose_page_rules'] = false;
	update_option( 'woocommerce_permalinks', $perma );
	$done[] = 'Linkovi: /proizvod/<naziv>/ i /katalog/<kategorija>/, isti kao na starom sajtu.';

	// Tema.
	if ( ! empty( $opts['switch_theme'] ) ) {
		$theme = wp_get_theme( 'bumbaru' );
		if ( $theme->exists() && 'bumbaru' !== get_stylesheet() ) {
			switch_theme( 'bumbaru' );
			$done[] = 'Aktivirana tema Bumbaru.';
		}
	}

	bumbaru_yoast_sync_pages();
	if ( bumbaru_yoast_settings() ) {
		$done[] = 'Yoast SEO: podešen i popunjen (naslovi, opisi, ključne reči).';
	}
	bumbaru_install_defaults();
	update_option( 'bumbaru_store_setup_done', time() );
	update_option( 'bumbaru_flush_rewrite', 1 );
	return $done;
}

/** Strane iz nove verzije sajta: Akcija i kviz „Nađi poklon” (šablone ima tema). */
function bumbaru_ensure_new_pages() {
	$ids = array();
	foreach ( array( 'akcija' => 'Akcija', 'nadji-poklon' => 'Nađi poklon' ) as $slug => $title ) {
		$p = get_page_by_path( $slug );
		if ( ! $p || 'trash' === $p->post_status ) {
			$ids[ $slug ] = bumbaru_ensure_page( $slug, $title );
		} else {
			$ids[ $slug ] = $p->ID;
		}
	}
	$seo = bumbaru_import_data( 'pages-seo.json' );
	foreach ( $ids as $slug => $id ) {
		if ( isset( $seo[ $slug ] ) && '' === (string) get_post_meta( $id, '_bu_seo_title', true ) ) {
			update_post_meta( $id, '_bu_seo_title', $seo[ $slug ]['title'] );
			update_post_meta( $id, '_bu_seo_desc', $seo[ $slug ]['desc'] );
		}
	}
	return $ids;
}

// Linkovi se osvežavaju na sledećem učitavanju (posle promene osnove linkova WooCommerce-a).
add_action( 'init', function () {
	if ( get_option( 'bumbaru_flush_rewrite' ) ) {
		delete_option( 'bumbaru_flush_rewrite' );
		flush_rewrite_rules();
	}
}, 99 );

// Dinar se prikazuje kao „din”.
add_filter( 'woocommerce_currency_symbol', function ( $symbol, $currency ) {
	return 'RSD' === $currency ? 'din' : $symbol;
}, 10, 2 );

/**
 * Nadogradnja na 2.3: besplatna dostava od 2.500 din (podešavanje, WooCommerce dostava i tekstovi proizvoda i strana)
 * i komplet po izboru za lutkice u pletenom izgledu (irvas, Sneško, Deda Mraz, jelkica).
 */
function bumbaru_migrate_23() {
	global $wpdb;
	if ( get_option( 'bumbaru_migrate_23' ) ) {
		return;
	}
	// Prag za besplatnu dostavu (samo ako je bio 2.000, iznos koji ste sami promenili ostaje).
	if ( 2000 === (int) get_option( 'bumbaru_free_ship_min', 2000 ) ) {
		update_option( 'bumbaru_free_ship_min', 2500 );
	}
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		foreach ( WC_Shipping_Zones::get_zones() as $z ) {
			foreach ( $z['shipping_methods'] as $m ) {
				if ( 'free_shipping' !== $m->id ) {
					continue;
				}
				$key = 'woocommerce_free_shipping_' . $m->get_instance_id() . '_settings';
				$s   = (array) get_option( $key, array() );
				if ( isset( $s['min_amount'] ) && 2000 === (int) $s['min_amount'] ) {
					$s['min_amount'] = '2500';
					update_option( $key, $s );
				}
			}
		}
	}
	// Tekstovi: „od 2.000 din” → „od 2.500 din” (ista dužina, pa i serijalizovani podaci ostaju ispravni).
	$types = "'product','page'";
	$wpdb->query( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'od 2.000 din', 'od 2.500 din'), post_excerpt = REPLACE(post_excerpt, 'od 2.000 din', 'od 2.500 din') WHERE post_type IN ($types)" ); // phpcs:ignore
	$wpdb->query( "UPDATE {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id SET pm.meta_value = REPLACE(pm.meta_value, 'od 2.000 din', 'od 2.500 din') WHERE p.post_type IN ($types) AND pm.meta_value LIKE '%od 2.000 din%'" ); // phpcs:ignore
	$wpdb->query( "UPDATE {$wpdb->termmeta} SET meta_value = REPLACE(meta_value, 'od 2.000 din', 'od 2.500 din') WHERE meta_value LIKE '%od 2.000 din%'" ); // phpcs:ignore
	$wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET description = REPLACE(description, 'od 2.000 din', 'od 2.500 din') WHERE description LIKE '%od 2.000 din%'" ); // phpcs:ignore
	$site = get_option( 'bumbaru_site', array() );
	if ( is_array( $site ) && isset( $site['ribbon_3'] ) && 'Besplatna dostava od 2.000 din' === $site['ribbon_3'] ) {
		$site['ribbon_3'] = 'Besplatna dostava od 2.500 din';
		update_option( 'bumbaru_site', $site );
	}

	// Lutkice: komplet po izboru (prva dva su štiklirana = komplet od 3), popust po broju lutkica.
	$lutkice = array(
		'irvas-lutkica-pleteni-izgled'     => array( 'snesko-lutkica-pleteni-izgled', 'deda-mraz-lutkica-pleteni-izgled', 'jelkica-pleteni-izgled' ),
		'snesko-lutkica-pleteni-izgled'    => array( 'irvas-lutkica-pleteni-izgled', 'deda-mraz-lutkica-pleteni-izgled', 'jelkica-pleteni-izgled' ),
		'deda-mraz-lutkica-pleteni-izgled' => array( 'snesko-lutkica-pleteni-izgled', 'irvas-lutkica-pleteni-izgled', 'jelkica-pleteni-izgled' ),
		'jelkica-pleteni-izgled'           => array( 'deda-mraz-lutkica-pleteni-izgled', 'snesko-lutkica-pleteni-izgled', 'irvas-lutkica-pleteni-izgled' ),
	);
	foreach ( $lutkice as $slug => $uz ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( ! $post ) {
			continue;
		}
		$cfg = get_post_meta( $post->ID, '_bu_config', true );
		$cfg = is_array( $cfg ) ? $cfg : array();
		// Komplet koji ste sami podesili (sa popustom po broju) ostaje.
		if ( ! empty( $cfg['komplet']['popusti'] ) ) {
			continue;
		}
		$cfg['komplet'] = array( 'uz' => $uz, 'popust' => isset( $cfg['komplet']['popust'] ) ? $cfg['komplet']['popust'] : 10, 'popusti' => '2=10, 3=15, 4=20' );
		update_post_meta( $post->ID, '_bu_config', $cfg );
	}
	wp_cache_flush();
	update_option( 'bumbaru_migrate_23', 1 );
}

/**
 * 2.5: cene ukrasa sa imenom po kalkulatoru (290 din; ako proizvod ima obračun, predlog kalkulatora),
 * popust za više komada 5/7/10 i porodični set povoljniji od pojedinačnih (4 imena 1.090, peto +190).
 * Menja samo stare podrazumevane vrednosti (ono što ste sami promenili ostaje).
 */
function bumbaru_migrate_25() {
	if ( get_option( 'bumbaru_migrate_25' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$ids = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_bu_config' ) ); // phpcs:ignore
	foreach ( $ids as $id ) {
		$p   = wc_get_product( $id );
		$cfg = get_post_meta( $id, '_bu_config', true );
		if ( ! $p || ! is_array( $cfg ) || empty( $cfg['type'] ) ) {
			continue;
		}
		if ( 'ukras' === $cfg['type'] ) {
			if ( ! isset( $cfg['kolicina'] ) || ( is_string( $cfg['kolicina'] ) && '2=5, 3=10, 5=15' === bumbaru_tiers_to_text( bumbaru_parse_tiers( $cfg['kolicina'] ) ) ) ) {
				$cfg['kolicina'] = '5=10, 7=15, 10=20';
				update_post_meta( $id, '_bu_config', $cfg );
			}
			if ( ! bumbaru_calc_sync_price( $p ) && 390 === (int) $p->get_regular_price( 'edit' ) ) {
				$p->set_regular_price( '290' );
				$p->save();
			}
		} elseif ( 'set' === $cfg['type'] ) {
			if ( 1590 === (int) $p->get_regular_price( 'edit' ) ) {
				$p->set_regular_price( '1090' );
				$p->save();
			}
			if ( isset( $cfg['set_extra'] ) && 390 === (int) $cfg['set_extra'] ) {
				$cfg['set_extra'] = 190;
				update_post_meta( $id, '_bu_config', $cfg );
			}
		}
	}
	update_option( 'bumbaru_migrate_25', 1 );
}

/**
 * 2.5: bez stalnih precrtanih („starih”) cena u opcijama (npr. [300 / 350] → [300]): precrtana cena mora biti
 * cena koju je proizvod zaista imao pre popusta. Prave akcije (akcija kategorije, akcijska cena) ostaju.
 */
function bumbaru_migrate_25b() {
	if ( get_option( 'bumbaru_migrate_25b' ) ) {
		return;
	}
	$ids = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_bu_config' ) ); // phpcs:ignore
	foreach ( $ids as $id ) {
		$cfg = get_post_meta( $id, '_bu_config', true );
		if ( is_array( $cfg ) && ! empty( $cfg['options'] ) && is_string( $cfg['options'] ) ) {
			$new = preg_replace( '/\[(\d+(?:[.,]\d+)?)\s*\/\s*\d+(?:[.,]\d+)?\]/', '[$1]', $cfg['options'] );
			if ( $new !== $cfg['options'] ) {
				$cfg['options'] = $new;
				update_post_meta( $id, '_bu_config', $cfg );
			}
		}
	}
	// Lutkice u pletenom izgledu: mala 490 din (bila 590).
	foreach ( array( 'irvas-lutkica-pleteni-izgled', 'snesko-lutkica-pleteni-izgled', 'jelkica-pleteni-izgled', 'deda-mraz-lutkica-pleteni-izgled' ) as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		$cfg  = $post ? get_post_meta( $post->ID, '_bu_config', true ) : null;
		if ( is_array( $cfg ) && isset( $cfg['options'] ) && is_string( $cfg['options'] ) && false !== strpos( $cfg['options'], 'Mali [590]' ) ) {
			$cfg['options'] = str_replace( 'Mali [590]', 'Mali [490]', $cfg['options'] );
			update_post_meta( $post->ID, '_bu_config', $cfg );
			$p = wc_get_product( $post->ID );
			if ( $p && 590 === (int) $p->get_regular_price( 'edit' ) ) {
				$p->set_regular_price( '490' );
				$p->save();
			}
		}
	}
	update_option( 'bumbaru_migrate_25b', 1 );
}

/**
 * 2.6 Novogodišnja ponuda: cene, popust za različite proizvode (lutkice, spiralna jelka, jelka sa postoljem, rebrasta
 * tacna) umesto kompleta kod lutkica i spiralne jelke, popust za ukrase sa imenom 2/3/4+ kom, akcija −10% samo za
 * ukrase sa imenom (NG-01, NG-17), mini jelka (NG-07) i jelkica u pletenom izgledu (NG-13) na upit.
 * Cene ovih proizvoda više ne prate kalkulator. Nove proizvode (rotirajući ukras NG-17, jelka sa postoljem NG-18,
 * rebrasta ovalna tacna DM-04), prave fotografije i tekstove upisuje uvoz (Bumbaru 3D → Uvoz), korak po korak.
 */
function bumbaru_ng26_cene() {
	return array(
		// slug => [ redovna cena ('' = na upit), opcije (null = ne menja se) ]
		'irvas-lutkica-pleteni-izgled'     => array( 490, 'Veličina = Mali [490] | Srednji [890] | Veliki [1290] | Privezak [450]' ),
		'snesko-lutkica-pleteni-izgled'    => array( 490, 'Veličina = Mali [490] | Srednji [890] | Veliki [1290] | Privezak [450]' ),
		'deda-mraz-lutkica-pleteni-izgled' => array( 490, 'Veličina = Mali [490] | Srednji [890] | Veliki [1290] | Privezak [450]' ),
		'spiralna-jelka-sa-zvezdom'        => array( 590, 'Veličina = Mala [590] | Velika [1490] | Par: mala i velika [2190]' ),
		'jelka-sa-postoljem'               => array( 590, 'Visina = 15 cm [590] | 10 cm [390] | 7 cm [290]' ),
		'rebrasta-ovalna-tacna'            => array( 590, null ),
		'ukras-za-jelku-sa-imenom'         => array( 390, null ),
		'rotirajuci-ukras-sa-imenom'       => array( 390, null ),
		'porodicni-set-ukrasa-sa-imenima'  => array( 1120, null ),
		'mini-jelka-za-sto'                => array( '', 'Veličina = 15 cm | 10 cm | 7 cm' ),
		'jelkica-pleteni-izgled'           => array( '', 'Veličina = Mali | Srednji | Veliki | Privezak' ),
	);
}

function bumbaru_migrate_ng26() {
	if ( get_option( 'bumbaru_migrate_ng26' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$log = array();
	// Akcija −10% samo za ukrase sa imenom.
	$ak = get_option( 'bumbaru_akcija', null );
	$ak = wp_parse_args( is_array( $ak ) ? $ak : array(), bumbaru_akcija_defaults() );
	if ( '' === trim( (string) $ak['samo'] ) ) {
		$ak['samo'] = 'NG-01, NG-17';
		update_option( 'bumbaru_akcija', $ak );
		$log[] = 'Akcija: samo NG-01, NG-17';
	}
	$id_of = function ( $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		return $post ? (int) $post->ID : 0;
	};
	// Tacna iz prve verzije 2.6 (ako je napravljena) dobija novo ime, adresu i šifru; stara adresa se preusmerava.
	$stara = $id_of( 'novogodisnja-tacna-za-figure' );
	if ( $stara && ! $id_of( 'rebrasta-ovalna-tacna' ) ) {
		wp_update_post( array( 'ID' => $stara, 'post_name' => 'rebrasta-ovalna-tacna', 'post_title' => 'Rebrasta ovalna tacna' ) );
		$t = wc_get_product( $stara );
		if ( $t ) {
			try {
				$t->set_sku( 'DM-04' );
				$t->save();
			} catch ( Exception $e ) { // phpcs:ignore
			}
		}
		$log[] = 'Tacna: rebrasta-ovalna-tacna (DM-04)';
	}
	// Cene, opcije, konfigurator (proizvodi koji još ne postoje dobiju cenu pri uvozu).
	foreach ( bumbaru_ng26_cene() as $slug => $v ) {
		$id = $id_of( $slug );
		$p  = $id ? wc_get_product( $id ) : null;
		if ( ! $p ) {
			continue;
		}
		$p->set_regular_price( (string) $v[0] );
		$p->set_sale_price( '' );
		$p->set_date_on_sale_from( null );
		$p->set_date_on_sale_to( null );
		if ( '' === $v[0] && 'publish' !== $p->get_status() ) {
			$p->set_status( 'publish' ); // na upit, ali na sajtu
		}
		$p->save();
		$cfg = get_post_meta( $id, '_bu_config', true );
		$cfg = is_array( $cfg ) ? $cfg : array();
		if ( null !== $v[1] ) {
			$cfg['options'] = $v[1];
		}
		if ( bumbaru_grupa_slug( $id ) && 'rebrasta-ovalna-tacna' !== $slug ) {
			unset( $cfg['komplet'] ); // umesto kompleta važi popust za različite proizvode
		}
		if ( 'ukras-za-jelku-sa-imenom' === $slug ) {
			$cfg['kolicina'] = '2=10, 3=15, 4=20';
		}
		if ( 'porodicni-set-ukrasa-sa-imenima' === $slug ) {
			$cfg['set_extra'] = 280; // 5 imena = 1.400 din
		}
		update_post_meta( $id, '_bu_config', $cfg );
		// Cena ostaje ova i kad se sačuvaju „Filamenti i troškovi”.
		$calc = get_post_meta( $id, '_bu_calc', true );
		if ( is_array( $calc ) && ( ! isset( $calc['prati'] ) || ! empty( $calc['prati'] ) ) ) {
			$calc['prati'] = 0;
			update_post_meta( $id, '_bu_calc', $calc );
			$log[] = 'Kalkulator ne prati: ' . $slug;
		}
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $id );
		}
		$log[] = $slug . ' = ' . ( '' === $v[0] ? 'na upit' : $v[0] );
	}
	// Uvoz osvežava proizvode (nove proizvode, fotografije, tekstove): poruka u administraciji.
	update_option( 'bumbaru_update_notice', 1 );
	wp_cache_flush();
	do_action( 'litespeed_purge_all' ); // keš stranica (LiteSpeed), ako postoji
	update_option( 'bumbaru_migrate_ng26', 1 );
	update_option( 'bumbaru_migrate_ng26_log', $log, false );
}
