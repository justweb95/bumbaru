<?php
/**
 * Pomoćne funkcije teme: podešavanja sajta, ikonice, kartica proizvoda, putanja, pitanja.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Podešavanja sajta (menjaju se u Bumbaru 3D → Podešavanja sajta).
 */
function bu_site_defaults() {
	return array(
		'brand_sentence' => 'Pokloni sa imenom i delovi po meri. Sve štampamo, proveravamo i pakujemo sami, a šaljemo kurirom širom Srbije.',
		'city'           => 'Leskovac, Srbija',
		'facebook_url'   => 'https://www.facebook.com/bumbaru.rs',
		'messenger_page' => 'bumbaru.rs',
		'instagram_url'  => '',
		'whatsapp'       => '',
		'season'         => 'nova-godina',
		'paleta'         => 'svetla',
		'ribbon_ng'      => 'Sa imenom do 16. 12, ostale porudžbine do 20. 12.',
		'ribbon_stalna'  => 'Proizvodi na stanju šalju se isti dan.',
		'ribbon_2'       => 'Plaćanje pouzećem',
		'ribbon_3'       => 'Besplatna dostava od 2.500 din',
		'ng_deadline'    => 'Poručite do 16. 12. i stiže pre Nove godine.',
	);
}

function bu_site( $key ) {
	static $opts = null;
	if ( null === $opts ) {
		$saved = get_option( 'bumbaru_site', array() );
		$opts  = wp_parse_args( is_array( $saved ) ? array_filter( $saved, 'strlen' ) : array(), bu_site_defaults() );
		if ( isset( $saved['instagram_url'] ) ) {
			$opts['instagram_url'] = $saved['instagram_url'];
		}
	}
	return isset( $opts[ $key ] ) ? $opts[ $key ] : '';
}

function bu_asset( $path ) {
	return get_template_directory_uri() . '/assets/' . ltrim( $path, '/' );
}

function bu_img( $path ) {
	return bu_asset( 'img/' . ltrim( $path, '/' ) );
}

/** Slika iz teme (putanja) ili pun URL. */
function bu_img_src( $src ) {
	if ( ! $src ) {
		return '';
	}
	if ( preg_match( '#^(https?:)?//#', $src ) || 0 === strpos( $src, '/' ) ) {
		return $src;
	}
	return bu_img( $src );
}

function bu_messenger_url( $text = '', $ref = '' ) {
	$base = 'https://m.me/' . bu_site( 'messenger_page' );
	$q    = array();
	if ( $text ) {
		$q[] = 'text=' . rawurlencode( $text );
	}
	if ( $ref ) {
		$q[] = 'ref=' . rawurlencode( $ref );
	}
	return $q ? $base . '?' . implode( '&', $q ) : $base;
}

/** 1590 → „1.590 din”. */
function bu_din( $n ) {
	return number_format( (float) $n, 0, ',', '.' ) . ' din';
}

/** Linijske ikonice 24×24 (iste kao na Astro sajtu). */
function bu_icon( $name, $size = 20, $stroke = 2, $class = '', $label = '' ) {
	static $p = array(
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
		'messenger' => '<path d="M12 3C7 3 3 6.7 3 11.3c0 2.6 1.3 4.9 3.3 6.4V21l3-1.7c.9.3 1.8.4 2.7.4 5 0 9-3.7 9-8.4S17 3 12 3z"/><path d="M7.5 13.5l3-3.2 2.3 2.2 3.7-3.5-3 3.2-2.3-2.2z"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'     => '<path d="M6 6l12 12M18 6L6 18"/>',
		'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.2-4 4.3-6 8-6s6.8 2 8 6"/>',
		'logout'    => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 8l-4 4 4 4M6 12h10"/>',
		'home'      => '<path d="M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-6h4v6"/>',
		'list'      => '<path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>',
		'download'  => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'chev'      => '<path d="M6 9l6 6 6-6"/>',
		'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'cash'      => '<path d="M3 7h18v10H3zM7 12h.01M17 12h.01M12 14.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>',
		'truck'     => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>',
		'star'      => '<path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z"/>',
		'shield'    => '<path d="M9 12.5l2 2 4-4.5M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"/>',
		'calendar'  => '<path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/>',
		'box'       => '<path d="M3 7.5L12 3l9 4.5v9L12 21l-9-4.5zM3 7.5l9 4.5 9-4.5M12 12v9"/>',
		'gift'      => '<path d="M3 9h18v12H3zM12 9v12M3 13h18M12 9c-2-4-6-4-6-1.5S10 9 12 9c2 0 6 0 6-1.5S14 5 12 9z"/>',
		'tool'      => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>',
		'car'       => '<path d="M5 16h14v-4l-2-5H7l-2 5zM8 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM16 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>',
		'key'       => '<path d="M8 3a5 5 0 1 0 0 10 5 5 0 0 0 0-10zM11.5 11.5L20 20M16 16l2.5-2.5M18.5 18.5l2.5-2.5"/>',
		'blocks'    => '<path d="M4 14h7v7H4zM13 14h7v7h-7zM8.5 4.5h7v7h-7z"/>',
		'copy'      => '<path d="M8 8h12v12H8z"/><path d="M16 8V4H4v12h4"/>',
		'facebook'  => '<path d="M15 3h-3a4 4 0 0 0-4 4v3H5v4h3v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>',
		'pin'       => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'camera'    => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
		'ruler'     => '<path d="M3 17L17 3l4 4L7 21zM7 13l2 2M10 10l2 2M13 7l2 2"/>',
		'bag'       => '<path d="M5.5 8.5h13l-1.2 11.5H6.7z"/><path d="M9 10V7a3 3 0 0 1 6 0v3"/>',
		'palette'   => '<path d="M12 3a9 9 0 1 0 0 18c1.4 0 2-1 2-2 0-1.6-1.6-2 0-3.5 1-.9 2.5-.5 4-.5a3 3 0 0 0 3-3C21 6.6 17 3 12 3z"/><circle cx="7.5" cy="11" r="1.2"/><circle cx="10.5" cy="7.5" r="1.2"/><circle cx="15" cy="7.8" r="1.2"/>',
		'bulb'      => '<path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2V16h5v-.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3z"/>',
		'pen'       => '<path d="M4 20l4-1L19 8a2.1 2.1 0 0 0-3-3L5 16zM14 7l3 3"/>',
		'layers'    => '<path d="M12 3l9 5-9 5-9-5zM3 13l9 5 9-5"/>',
		'trash'     => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
		'sparkle'   => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z"/>',
	);
	$aria = $label ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"';
	$cls  = $class ? ' class="' . esc_attr( $class ) . '"' : '';
	// Messenger, Facebook, Instagram, WhatsApp: originalni logo u originalnim bojama, iste veličine kao ostale ikonice.
	$brand = bu_brand_icon( $name );
	if ( $brand ) {
		return '<svg class="ico-brand ico-' . esc_attr( $name ) . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24"' . $aria . '>' . $brand . '</svg>';
	}
	$body = isset( $p[ $name ] ) ? $p[ $name ] : '';
	return '<svg' . $cls . ' width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . esc_attr( $stroke ) . '" stroke-linecap="round" stroke-linejoin="round"' . $aria . '>' . $body . '</svg>';
}

/** Originalni logo društvene mreže (SVG sadržaj za viewBox 0 0 24 24), ili prazno. */
function bu_brand_icon( $name ) {
	static $n = 0;
	$id = 'bu-br' . ( ++$n );
	switch ( $name ) {
		case 'messenger':
			return '<defs><radialGradient id="' . $id . '" cx="19.25%" cy="99.47%" r="108.96%"><stop offset="0" stop-color="#09F"/><stop offset=".61" stop-color="#A033FF"/><stop offset=".93" stop-color="#FF5280"/><stop offset="1" stop-color="#FF7061"/></radialGradient></defs>'
				. '<path fill="url(#' . $id . ')" d="M12 .6C5.6.6.6 5.3.6 11.6c0 3.3 1.4 6.2 3.6 8.2.2.2.3.4.3.7l.1 2c0 .6.7 1 1.3.8l2.3-1c.2-.1.4-.1.6 0 1 .3 2.1.4 3.2.4 6.4 0 11.4-4.7 11.4-11S18.4.6 12 .6z"/>'
				. '<path fill="#fff" d="M5.2 14.9l3.3-5.3c.5-.8 1.7-1.1 2.5-.5l2.7 2c.2.2.6.2.8 0l3.6-2.7c.5-.4 1.1.2.8.7l-3.3 5.3c-.5.8-1.7 1.1-2.5.5l-2.7-2c-.2-.2-.6-.2-.8 0L6 15.6c-.5.4-1.1-.2-.8-.7z"/>';
		case 'facebook':
			return '<circle cx="12" cy="12" r="11.4" fill="#0866FF"/>'
				. '<path fill="#fff" d="M13.6 23.3v-8.1h2.7l.4-3.3h-3.1V9.8c0-.9.3-1.6 1.6-1.6h1.7V5.3c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.4H7.4v3.3h2.8v8.1z"/>';
		case 'instagram':
			return '<defs><radialGradient id="' . $id . '" cx="26.5%" cy="107.7%" r="99%"><stop offset="0" stop-color="#FD5"/><stop offset=".1" stop-color="#FD5"/><stop offset=".5" stop-color="#FF543E"/><stop offset="1" stop-color="#C837AB"/></radialGradient>'
				. '<radialGradient id="' . $id . 'b" cx="-16.7%" cy="7.2%" r="44.3%" gradientTransform="matrix(.2 1 -4.1 .9 0 0)"><stop offset="0" stop-color="#3771C8"/><stop offset=".13" stop-color="#3771C8"/><stop offset="1" stop-color="#60F" stop-opacity="0"/></radialGradient></defs>'
				. '<rect x=".6" y=".6" width="22.8" height="22.8" rx="6.4" fill="url(#' . $id . ')"/><rect x=".6" y=".6" width="22.8" height="22.8" rx="6.4" fill="url(#' . $id . 'b)"/>'
				. '<rect x="5" y="5" width="14" height="14" rx="4" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="12" cy="12" r="3.4" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="16.3" cy="7.7" r="1.05" fill="#fff"/>';
		case 'whatsapp':
			return '<path fill="#25D366" d="M12 .7C5.8.7.7 5.8.7 12c0 2 .5 3.9 1.5 5.6L.6 23.4l6-1.6c1.6.9 3.5 1.4 5.4 1.4 6.2 0 11.3-5.1 11.3-11.3S18.2.7 12 .7z"/>'
				. '<path fill="#fff" d="M17.6 14.6c-.3-.2-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.3 5.2 4.6 2.6 1 3.1.8 3.7.8.6-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.2-.3-.3-.6-.4z"/>';
	}
	return '';
}

/** WhatsApp link za broj iz podešavanja (prazno ako broj nije upisan). */
function bu_whatsapp_url( $text = '' ) {
	$num = preg_replace( '/\D+/', '', (string) bu_site( 'whatsapp' ) );
	if ( '' === $num ) {
		return '';
	}
	if ( 0 === strpos( $num, '0' ) ) {
		$num = '381' . substr( $num, 1 );
	}
	return 'https://wa.me/' . $num . ( $text ? '?text=' . rawurlencode( $text ) : '' );
}

/** Putanja (Početna / … ) sa BreadcrumbList podacima. */
function bu_crumbs( $items ) {
	$all = array_merge( array( array( 'label' => 'Početna', 'href' => home_url( '/' ) ) ), $items );
	$n   = count( $all );
	echo '<nav aria-label="Putanja"><ol class="crumbs">';
	foreach ( $all as $i => $x ) {
		echo '<li>';
		if ( ! empty( $x['href'] ) && $i < $n - 1 ) {
			echo '<a href="' . esc_url( $x['href'] ) . '">' . esc_html( $x['label'] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $x['label'] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
	if ( function_exists( 'bu_seo_plugin_active' ) && bu_seo_plugin_active() ) {
		return; // Yoast ima svoju putanju u strukturisanim podacima.
	}
	$ld = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => array(),
	);
	foreach ( $all as $i => $x ) {
		$el = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $x['label'] );
		if ( ! empty( $x['href'] ) ) {
			$el['item'] = $x['href'];
		}
		$ld['itemListElement'][] = $el;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}

/** Delovi na otvaranje (česta pitanja). Odgovor sme da sadrži link. */
function bu_accordion( $items, $open = -1, $icons = false ) {
	if ( ! $items ) {
		return;
	}
	echo '<div class="acc' . ( $icons ? ' acc-icons' : '' ) . '">';
	foreach ( array_values( $items ) as $i => $it ) {
		$ico = $icons ? '<span class="acc-ico acc-c' . ( $i % 4 ) . '">' . bu_icon( bu_spec_icon( $it['q'] ), 18 ) . '</span>' : '';
		echo '<details' . ( $i === $open ? ' open' : '' ) . '><summary><span class="acc-q">' . $ico . esc_html( $it['q'] ) . '</span>' . bu_icon( 'chev', 20, 2.4, 'chev' ) . '</summary>';
		echo '<div class="ans-wrap"><div class="ans">' . wp_kses_post( $it['a'] ) . '</div></div></details>';
	}
	echo '</div>';
}

/**
 * Tekst „Naslov | Tekst” po redu → niz parova. Koristi se za karakteristike, detalje i pitanja.
 */
function bu_lines( $text, $keys = array( 'q', 'a' ) ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) < 2 || '' === $parts[0] ) {
			continue;
		}
		$out[] = array( $keys[0] => $parts[0], $keys[1] => $parts[1] );
	}
	return $out;
}

/** Podatak proizvoda iz Bumbaru polja. */
function bu_pmeta( $product, $key ) {
	$id = is_object( $product ) ? $product->get_id() : (int) $product;
	return get_post_meta( $id, '_bu_' . $key, true );
}

/** Podatak kategorije (term meta). */
function bu_tmeta( $term, $key ) {
	$id = is_object( $term ) ? $term->term_id : (int) $term;
	return get_term_meta( $id, 'bu_' . $key, true );
}

function bu_cat_url( $slug ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $t ) {
		$l = get_term_link( $t );
		if ( ! is_wp_error( $l ) ) {
			return $l;
		}
	}
	return home_url( '/katalog/' . $slug . '/' );
}

function bu_product_url( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'product' );
	return $p ? get_permalink( $p ) : home_url( '/proizvod/' . $slug . '/' );
}

function bu_page_url( $slug ) {
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
}

/** Glavna kategorija proizvoda (za putanju i oznaku). */
function bu_primary_cat( $product ) {
	$slug = bu_pmeta( $product, 'primary_cat' );
	if ( $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $t ) {
			return $t;
		}
	}
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/** Fotografije proizvoda: galerija, a ako je nema, glavna slika. */
function bu_product_images( $product ) {
	$ids = $product->get_gallery_image_ids();
	if ( ! $ids && $product->get_image_id() ) {
		// Proizvod sa pregledom uživo ne ponavlja glavnu sliku (ona je za karticu).
		$live = function_exists( 'bumbaru_has_live' ) && bumbaru_has_live( $product );
		$ids  = $live ? array() : array( $product->get_image_id() );
	}
	$out = array();
	foreach ( $ids as $id ) {
		$full = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $full ) {
			continue;
		}
		$sq    = wp_get_attachment_image_src( $id, 'woocommerce_thumbnail' );
		$out[] = array(
			'id'     => $id,
			'srcset' => (string) wp_get_attachment_image_srcset( $id, 'full' ),
			'src'    => $full[0],
			'w'   => (int) $full[1],
			'h'   => (int) $full[2],
			'sq'  => $sq ? $sq[0] : $full[0],
			'alt' => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
		);
	}
	return $out;
}

/** srcset za karticu (telefon učitava manju sliku). */
function bu_card_srcset( $product ) {
	$id = $product->get_image_id();
	if ( ! $id ) {
		return '';
	}
	$set = wp_get_attachment_image_srcset( $id, 'woocommerce_thumbnail' );
	return $set ? ' srcset="' . esc_attr( $set ) . '" sizes="(max-width: 760px) 62vw, (max-width: 1100px) 33vw, 330px"' : '';
}

/** Kvadratna slika za karticu: glavna slika proizvoda. */
function bu_card_img( $product ) {
	$id = $product->get_image_id();
	if ( ! $id ) {
		return '';
	}
	$src = wp_get_attachment_image_src( $id, 'woocommerce_thumbnail' );
	return $src ? $src[0] : '';
}

/** Cena sa doplatom (plugin računa akciju i za doplatu). */
function bu_unit_price( $product, $add ) {
	if ( function_exists( 'bumbaru_unit_price' ) ) {
		return bumbaru_unit_price( $product, $add );
	}
	return (float) $product->get_price() + $add;
}

/** Procenat popusta (10 za −10%) ili 0: akcija kategorije ili akcijska cena proizvoda. */
function bu_sale_pct( $product ) {
	if ( function_exists( 'bumbaru_discount_pct' ) ) {
		return bumbaru_discount_pct( $product );
	}
	if ( ! $product->is_on_sale() ) {
		return 0;
	}
	$reg  = (float) $product->get_regular_price();
	$sale = (float) $product->get_sale_price();
	return ( $reg > 0 && $sale < $reg ) ? (int) round( ( $reg - $sale ) / $reg * 100 ) : 0;
}

/** Okrugla nalepnica „−10%” (crvena, sa isprekidanom ivicom), kao na novom sajtu. */
function bu_sale_badge( $product, $class = '' ) {
	$pct = bu_sale_pct( $product );
	if ( ! $pct ) {
		return '';
	}
	return '<span class="ak-sticker' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-label="' . esc_attr( 'Akcija −' . $pct . '%' ) . '">−' . (int) $pct . '%</span>';
}

/**
 * Cena za prikaz ili prazno ako cena nije upisana.
 * Kartica: „od 890 din” kad cena zavisi od izbora, „/ kom” kod izbora komada; na akciji precrtana redovna cena.
 */
function bu_price_html( $product ) {
	if ( '' === $product->get_price() || null === $product->get_price() ) {
		return '';
	}
	if ( function_exists( 'bumbaru_card_price' ) ) {
		$c = bumbaru_card_price( $product );
		return $c ? $c['html'] : '';
	}
	return esc_html( bu_din( $product->get_price() ) );
}

/** Kratka cena kao tekst (za stare pozive). */
function bu_price_text( $product ) {
	return trim( wp_strip_all_tags( bu_price_html( $product ) ) );
}

/** Tip konfiguratora proizvoda (ukras, set, privezak, motivi, boje, sastav, jelka…) iz plugina. */
function bu_config_type( $product ) {
	if ( function_exists( 'bumbaru_config' ) ) {
		$c = bumbaru_config( $product );
		return $c['type'];
	}
	return '';
}

/** Proizvod traži izbor na strani proizvoda (opcije, boje, motivi, ime…). */
function bu_has_options( $product ) {
	if ( function_exists( 'bumbaru_config' ) ) {
		$c = bumbaru_config( $product );
		return ! empty( $c['options'] ) || ! empty( $c['izbor_boja'] ) || '' !== $c['type'];
	}
	return false;
}

/** Proizvod se kupuje kroz korpu (ima cenu i na stanju je). */
function bu_buyable( $product ) {
	return $product->is_purchasable() && $product->is_in_stock() && '' !== $product->get_price();
}

/** Kartica proizvoda (ista kao ProductCard.astro). */
/**
 * Kartice proizvoda za katalog: ukras sa imenom ima posebnu karticu za svaki motiv
 * (klik otvara konfigurator sa tim motivom), ostali proizvodi jednu.
 */
function bu_product_cards( $product, $eager = false, $with_tags = false ) {
	$kartice = ( $product && function_exists( 'bumbaru_motiv_kartice' ) ) ? bumbaru_motiv_kartice( $product ) : array();
	if ( ! $kartice ) {
		bu_product_card( $product, $eager, $with_tags );
		return;
	}
	foreach ( $kartice as $k ) {
		bu_product_card( $product, $eager, $with_tags, $k );
	}
}

/** $motiv: kartica jednog motiva ukrasa (bumbaru_motiv_kartice): adresa sa #motiv, naziv, fotografija ili crtež. */
function bu_product_card( $product, $eager = false, $with_tags = false, $motiv = null ) {
	if ( ! $product ) {
		return;
	}
	$url    = $motiv ? $motiv['url'] : get_permalink( $product->get_id() );
	$img    = $motiv ? $motiv['img'] : bu_card_img( $product );
	$svg    = $motiv ? $motiv['svg'] : '';
	$price  = bu_price_html( $product );
	$custom = bu_pmeta( $product, 'custom' );
	$type   = bu_config_type( $product );
	$title  = $motiv ? $motiv['label'] . ' – ' . bu_lcfirst( $product->get_name() ) : $product->get_name();
	$cp     = function_exists( 'bumbaru_card_price' ) && '' !== $product->get_price() ? bumbaru_card_price( $product ) : null;
	$komadi = function_exists( 'bumbaru_izbor_komada' ) && bumbaru_izbor_komada( $product );
	$pdo    = function_exists( 'bumbaru_popust_do' ) ? bumbaru_popust_do( $product ) : 0;
	$tags   = '';
	if ( $with_tags ) {
		$t    = wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'slugs' ) );
		$tags = is_wp_error( $t ) ? '' : implode( ' ', $t );
	}
	?>
	<article class="card"<?php echo $with_tags ? ' data-tags="' . esc_attr( $tags ) . '"' : ''; ?>>
		<div class="media">
			<a class="media-link" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
				<?php if ( $svg ) : ?>
					<div class="orn-card-bg"><?php echo $svg; // phpcs:ignore -- crtež iz plugina ?></div>
				<?php elseif ( $img ) : ?>
					<img src="<?php echo esc_url( $img ); ?>"<?php echo $motiv ? ( $motiv['srcset'] ? ' srcset="' . esc_attr( $motiv['srcset'] ) . '" sizes="(max-width: 760px) 62vw, (max-width: 1100px) 33vw, 330px"' : '' ) : bu_card_srcset( $product ); // phpcs:ignore ?> alt="" width="900" height="900" loading="<?php echo $eager ? 'eager' : 'lazy'; ?>" decoding="async" />
				<?php else : ?>
					<div class="soon-photo"><img src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" loading="lazy" /><span>Fotografija uskoro</span></div>
				<?php endif; ?>
			</a>
			<?php
			if ( $cp && $cp['pct'] ) {
				echo '<span class="ak-sticker" aria-label="' . esc_attr( 'Akcija −' . $cp['pct'] . '%' ) . '">−' . (int) $cp['pct'] . '%</span>';
			} elseif ( $cp && $cp['stpct'] ) {
				echo '<span class="ak-sticker" aria-label="' . esc_attr( 'Akcijska cena, −' . $cp['stpct'] . '%' ) . '">−' . (int) $cp['stpct'] . '%</span>';
			}
			if ( $pdo ) {
				echo '<span class="badge badge-custom">Popust do ' . esc_html( $pdo ) . '%</span>';
			} elseif ( $custom ) {
				echo '<span class="badge badge-custom">Po meri · 3–5 dana</span>';
			}
			$needs_choice = bu_has_options( $product );
			if ( bu_buyable( $product ) && ! $needs_choice ) {
				// Odmah u korpu.
				printf(
					'<a class="quick" href="%s" data-add-to-cart="%d" aria-label="%s">%s</a>',
					esc_url( add_query_arg( 'add-to-cart', $product->get_id(), $url ) ),
					(int) $product->get_id(),
					esc_attr( 'Dodaj u korpu: ' . $title ),
					bu_icon( 'plus', 22, 2.4 )
				);
			} elseif ( bu_buyable( $product ) || in_array( $type, array( 'ukras', 'set', 'privezak', 'ime', 'imena', 'motivi', 'boje', 'sastav' ), true ) ) {
				// Treba izabrati ime, motive, boje ili opciju na strani proizvoda.
				if ( $komadi ) {
					$label = ( 'boje' === $type ? 'Izaberite boje: ' : 'Izaberite motive: ' );
				} elseif ( in_array( $type, array( 'ukras', 'set', 'privezak', 'ime', 'imena' ), true ) ) {
					$label = 'Poruči sa imenom: ';
				} else {
					$label = 'Izaberite opcije: ';
				}
				printf( '<a class="quick" href="%s" aria-label="%s">%s</a>', esc_url( $url ), esc_attr( $label . $title ), bu_icon( 'plus', 22, 2.4 ) );
			} else {
				// Nema cene: porudžbina preko Messengera.
				printf(
					'<button class="quick" type="button" data-order="%s" data-order-title="%s" data-order-url="%s" data-order-img="%s" aria-label="%s">%s</button>',
					esc_attr( $product->get_sku() ? $product->get_sku() : $product->get_id() ),
					esc_attr( $title ),
					esc_url( $url ),
					esc_url( $img ),
					esc_attr( 'Poruči preko Messengera: ' . $title ),
					bu_icon( 'plus', 22, 2.4 )
				);
			}
			?>
		</div>
		<div class="body">
			<div class="row">
				<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h3>
				<?php if ( $price ) : ?><span class="price" style="font-size:18px"><?php echo $price; // phpcs:ignore -- već očišćeno ?></span><?php endif; ?>
			</div>
			<p class="note"><?php echo esc_html( wp_strip_all_tags( $product->get_short_description() ) ); ?></p>
		</div>
	</article>
	<?php
}

/** Prvo slovo malo (naziv proizvoda posle naziva motiva), osim kad je cela reč velikim slovima. */
function bu_lcfirst( $s ) {
	$f = mb_substr( $s, 0, 1 );
	$r = mb_substr( $s, 1 );
	return ( mb_strtoupper( mb_substr( $s, 0, 2 ) ) === mb_substr( $s, 0, 2 ) ) ? $s : mb_strtolower( $f ) . $r;
}

/** Kartica proizvoda po adresi (slug), ako postoji i objavljen je. */
function bu_product_by_slug( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'product' );
	return ( $p && 'publish' === $p->post_status && function_exists( 'wc_get_product' ) ) ? wc_get_product( $p->ID ) : null;
}

/** Slika kartice proizvoda po adresi, ili rezervna slika iz teme. */
function bu_slug_img( $slug, $fallback = '' ) {
	$p   = bu_product_by_slug( $slug );
	$img = $p ? bu_card_img( $p ) : '';
	return $img ? $img : ( $fallback ? bu_img( $fallback ) : bu_img( 'brand/pcelica.webp' ) );
}

/** Odbrojavanje do kraja akcije (Odbrojavanje.astro). */
function bu_odbrojavanje( $mali = false ) {
	$a = function_exists( 'bumbaru_akcija' ) ? bumbaru_akcija() : null;
	if ( ! $a ) {
		return '';
	}
	return '<div class="odb ak-on' . ( $mali ? ' odb-mali' : '' ) . '" data-odb="' . esc_attr( $a['kraj_iso'] ) . '">'
		. '<span class="odb-ic" aria-hidden="true">' . bu_icon( 'gift', $mali ? 16 : 18 ) . '</span>'
		. '<span class="odb-t"><b>' . esc_html( $a['naziv'] . ' −' . (float) $a['procenat'] . '%' ) . '</b><span class="odb-do">do ' . esc_html( bumbaru_datum_kratko( $a['do'] ) ) . '</span></span>'
		. '<span class="odb-sat" aria-label="Do kraja akcije" data-odb-sat><span><b data-d>–</b><small>dana</small></span><span><b data-h>–</b><small>sati</small></span><span><b data-m>–</b><small>min</small></span></span></div>';
}

/** Polje za pretragu kataloga (PretragaPolje.astro): u kategoriji traži samo u njoj. */
function bu_search_field( $kategorija = '', $naziv = '' ) {
	$id = 'q-' . ( $kategorija ? $kategorija : 'katalog' );
	echo '<form class="kp-search" action="' . esc_url( bu_page_url( 'pretraga' ) ) . '" method="get" role="search">'
		. '<label class="sr-only" for="' . esc_attr( $id ) . '">' . esc_html( $naziv ? 'Pretraga u kategoriji ' . $naziv : 'Pretraga kataloga' ) . '</label>'
		. bu_icon( 'search', 18 )
		. '<input id="' . esc_attr( $id ) . '" name="q" type="search" size="10" placeholder="' . esc_attr( $naziv ? 'Pretražite: ' . $naziv : 'Pretražite ceo katalog' ) . '" autocomplete="off" enterkeyhint="search" />'
		. ( $kategorija ? '<input type="hidden" name="kategorija" value="' . esc_attr( $kategorija ) . '" />' : '' )
		. '<button class="btn btn-primary" type="submit">Traži</button></form>';
}

/** „Nedavno ste gledali” (Nedavno.astro); podaci o proizvodima su u #bu-products u podnožju. */
function bu_nedavno( $ovaj = '', $naslov = 'Nedavno ste gledali' ) {
	$GLOBALS['bu_need_products'] = true;
	return '<section class="ned section" data-ned' . ( $ovaj ? ' data-ovaj="' . esc_attr( $ovaj ) . '"' : '' ) . ' hidden aria-labelledby="ned-t"><div class="sec-head">'
		. '<div class="ned-h"><span class="ned-ic" aria-hidden="true">' . bu_icon( 'clock', 20 ) . '</span><h2 id="ned-t" class="h2">' . esc_html( $naslov ) . '</h2></div>'
		. '<button type="button" class="ned-clear" data-ned-clear>Obriši listu</button></div><div class="ned-rail" data-ned-list></div></section>';
}

/** Podaci o svim proizvodima za „Nedavno ste gledali” (šifra → naziv, link, slika, cena, akcija). */
function bu_products_json() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '{}';
	}
	$map = array();
	foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) ) as $p ) {
		$cp  = function_exists( 'bumbaru_card_price' ) && '' !== $p->get_price() ? bumbaru_card_price( $p ) : null;
		$key = $p->get_sku() ? $p->get_sku() : (string) $p->get_id();
		$map[ $key ] = array(
			't' => $p->get_name(),
			'u' => get_permalink( $p->get_id() ),
			'i' => bu_card_img( $p ),
			's' => $p->get_slug(),
			'c' => $cp ? (float) $cp['n'] : 0,
			'e' => $cp ? (float) bumbaru_eff( $p, $cp['n'] ) : 0,
			'a' => $cp ? (int) $cp['pct'] : 0,
			'o' => $cp && $cp['pre'] ? 1 : 0,
			'k' => $cp && $cp['post'] ? 1 : 0,
		);
	}
	return wp_json_encode( $map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

/** Kategorije za meni „Katalog”: slika (prvo prave fotografije, različite za svaku kategoriju) i broj proizvoda. */
function bu_menu_categories() {
	static $out = null;
	if ( null !== $out ) {
		return $out;
	}
	$out  = array();
	$used = array();
	foreach ( bu_categories() as $t ) {
		$list = bu_category_products( $t->slug );
		$pick = null;
		foreach ( $list as $p ) {
			if ( $p->get_image_id() && ! isset( $used[ $p->get_id() ] ) ) {
				$pick = $p;
				break;
			}
		}
		if ( $pick ) {
			$used[ $pick->get_id() ] = 1;
		}
		$bg    = bu_tmeta( $t, 'bg' );
		$out[] = array(
			'term'  => $t,
			'n'     => count( $list ),
			'slika' => $pick ? bu_card_img( $pick ) : bu_img_src( $bg ? $bg : 'bg/kraft.webp' ),
			'ime'   => 'nova-godina' === $t->slug ? 'Nova godina' : $t->name,
			'icon'  => bu_tmeta( $t, 'icon' ),
			'soon'  => (bool) bu_tmeta( $t, 'soon' ),
			'url'   => get_term_link( $t ),
			'list'  => $list,
		);
	}
	return $out;
}

/** 1 proizvod, 2 proizvoda… */
function bu_plural( $n ) {
	return ( 1 === $n % 10 && 11 !== $n % 100 ) ? 'proizvod' : 'proizvoda';
}

/** Ikonica kategorije (putanja 24×24 upisana u kategoriji). */
function bu_path_icon( $d, $size = 18 ) {
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . esc_attr( $d ) . '" /></svg>';
}

/**
 * Proizvodi kategorije: sa fotografijom prvi, zatim po redosledu (menu_order), kao na Astro sajtu.
 */
function bu_category_products( $slug, $limit = -1 ) {
	static $cache = array();
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	if ( isset( $cache[ $slug ] ) ) {
		return $limit > 0 ? array_slice( $cache[ $slug ], 0, $limit ) : $cache[ $slug ];
	}
	$list = wc_get_products( array(
		'status'   => 'publish',
		'limit'    => -1,
		'category' => array( $slug ),
		'orderby'  => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'visibility' => 'catalog',
	) );
	$cache[ $slug ] = bu_sort_photo_first( $list );
	return $limit > 0 ? array_slice( $cache[ $slug ], 0, $limit ) : $cache[ $slug ];
}

function bu_sort_photo_first( $list, $limit = -1 ) {
	$with = array();
	$without = array();
	foreach ( $list as $p ) {
		if ( $p->get_image_id() ) {
			$with[] = $p;
		} else {
			$without[] = $p;
		}
	}
	$all = array_merge( $with, $without );
	return $limit > 0 ? array_slice( $all, 0, $limit ) : $all;
}

/** Kategorije kataloga po redosledu (bu_order), bez „Nekategorizovano”. */
function bu_categories() {
	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
	) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$default = (int) get_option( 'default_product_cat' );
	$terms   = array_values( array_filter( $terms, function ( $t ) use ( $default ) {
		return (int) $t->term_id !== $default && 'uncategorized' !== $t->slug && 'nekategorizovano' !== $t->slug;
	} ) );
	usort( $terms, function ( $a, $b ) {
		return (int) bu_tmeta( $a, 'order' ) - (int) bu_tmeta( $b, 'order' );
	} );
	return $terms;
}

/** Naziv kategorije u meniju i podnožju („Novogodišnji katalog” za sezonu). */
function bu_cat_menu_label( $term ) {
	$m = bu_tmeta( $term, 'menu_label' );
	return $m ? $m : $term->name;
}

/** Linkovi u zaglavlju: meni iz WordPress-a, a ako nije podešen, isti kao na Astro sajtu. */
function bu_nav_links() {
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['primary'] ) ) {
		$items = wp_get_nav_menu_items( $locations['primary'] );
		if ( $items ) {
			$out = array();
			foreach ( $items as $it ) {
				if ( (int) $it->menu_item_parent ) {
					continue;
				}
				$out[] = array(
					'href'    => $it->url,
					'label'   => $it->title,
					'season'  => in_array( 'season', (array) $it->classes, true ),
					'current' => untrailingslashit( $it->url ) === untrailingslashit( bu_current_url() ),
				);
			}
			return $out;
		}
	}
	$cur  = untrailingslashit( bu_current_url() );
	$defs = array(
		array( bu_cat_url( 'nova-godina' ), 'Novogodišnji katalog', true ),
		array( bu_cat_url( 'radionica' ), 'Radionica', false ),
		array( bu_cat_url( 'auto-i-moto' ), 'Auto i moto', false ),
		array( bu_cat_url( 'pokloni-sa-imenom' ), 'Pokloni sa imenom', false ),
		array( bu_cat_url( 'privesci' ), 'Privesci', false ),
		array( bu_page_url( 'po-meri' ), 'Po meri', false ),
	);
	$out = array();
	foreach ( $defs as $d ) {
		$out[] = array(
			'href'    => $d[0],
			'label'   => $d[1],
			'season'  => $d[2] && 'nova-godina' === bu_site( 'season' ),
			'current' => untrailingslashit( $d[0] ) === $cur || ( is_product() && bu_current_product_cat_url() === untrailingslashit( $d[0] ) ),
		);
	}
	return $out;
}

/** Gde smo (za oznaku u meniju): katalog, slug kategorije, akcija, kviz, po-meri. */
function bu_current_key() {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return 'katalog';
	}
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		return get_queried_object()->slug;
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		$t = $p ? bu_primary_cat( $p ) : null;
		return $t ? $t->slug : '';
	}
	if ( is_page( 'akcija' ) ) {
		return 'akcija';
	}
	if ( is_page( 'nadji-poklon' ) ) {
		return 'kviz';
	}
	if ( is_page( 'po-meri' ) ) {
		return 'po-meri';
	}
	return '';
}

function bu_current_url() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( wp_unslash( $_SERVER['REQUEST_URI'] ), '?' ) : '/';
	return home_url( $path );
}

function bu_current_product_cat_url() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return '';
	}
	$p = wc_get_product( get_queried_object_id() );
	$t = $p ? bu_primary_cat( $p ) : null;
	if ( ! $t ) {
		return '';
	}
	$l = get_term_link( $t );
	return is_wp_error( $l ) ? '' : untrailingslashit( $l );
}

/** Ime za pretragu: mala slova, bez kvačica (đ → dj), kao na Astro sajtu. */
function bu_norm( $s ) {
	$s = mb_strtolower( (string) $s, 'UTF-8' );
	$s = str_replace( 'đ', 'dj', $s );
	if ( class_exists( 'Normalizer' ) ) {
		$s = Normalizer::normalize( $s, Normalizer::FORM_D );
		$s = preg_replace( '/\p{Mn}+/u', '', $s );
	} else {
		$s = remove_accents( $s );
	}
	return $s;
}

/** Ikonica za karakteristiku ili deo na otvaranje, po nazivu. */
function bu_spec_icon( $label ) {
	$l   = mb_strtolower( (string) $label, 'UTF-8' );
	$map = array(
		'motiv'      => 'star',
		'boj'        => 'palette',
		'imen'       => 'pen',
		'ime'        => 'pen',
		'materijal'  => 'blocks',
		'pakovanj'   => 'box',
		'rok'        => 'calendar',
		'dostav'     => 'truck',
		'plaćanj'    => 'cash',
		'povraćaj'   => 'shield',
		'reklamac'   => 'shield',
		'održavanj'  => 'sparkle',
		'samo led'   => 'star',
		'karika'     => 'key',
		'montaž'     => 'tool',
		'sklapanj'   => 'tool',
		'u setu'     => 'box',
		'u kompletu' => 'box',
		'u pakovanju' => 'box',
		'visin'      => 'ruler',
		'veličin'    => 'ruler',
		'debljin'    => 'ruler',
		'tablic'     => 'ruler',
		'svetlo'     => 'star',
		'led'        => 'star',
		'oblik'      => 'blocks',
		'za'         => 'tool',
	);
	foreach ( $map as $k => $icon ) {
		if ( 0 === strpos( $l, $k ) || false !== strpos( $l, ' ' . $k ) ) {
			return $icon;
		}
	}
	return 'check';
}

/** Vrednost karakteristike: nazivi boja dobijaju kružić u toj boji. */
function bu_spec_value( $text ) {
	$html = esc_html( $text );
	if ( ! function_exists( 'bumbaru_colors' ) ) {
		return $html;
	}
	$dots = '';
	$seen = array();
	foreach ( bumbaru_colors() as $c ) {
		$name = mb_strtolower( $c['label'], 'UTF-8' );
		if ( preg_match( '/(^|[^\p{L}])' . preg_quote( $name, '/' ) . '([^\p{L}]|$)/iu', $text ) && ! isset( $seen[ $c['face'] ] ) ) {
			$seen[ $c['face'] ] = 1;
			$dots              .= '<i class="spec-dot" style="background:' . esc_attr( $c['face'] ) . '"></i>';
		}
	}
	return ( $dots ? '<span class="spec-dots" aria-hidden="true">' . $dots . '</span>' : '' ) . $html;
}
