<?php
/**
 * Korpa i porudžbina: provera izbora kupca, izbor uz proizvod (ime, stil, motivi, boje, opcije…),
 * cena izračunata na serveru (opcije sa cenom, broj imena, motivi sa popustom, komplet) i čuvanje u porudžbini.
 *
 * Strana proizvoda šalje izbor kao JSON u polju bu_json (skripta plugina). Bez skripte važe
 * podrazumevane vrednosti (prva vrednost svake opcije, podrazumevane boje).
 */

defined( 'ABSPATH' ) || exit;

/** Čisto ime: bez HTML-a, najviše 12 znakova. */
function bumbaru_clean_name( $v ) {
	$v = trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( (string) $v ) ) );
	return mb_substr( $v, 0, 12, 'UTF-8' );
}

function bumbaru_color_label( $id ) {
	return bumbaru_color( $id )['label'];
}

/** Boja iz izbora: samo boje na stanju; inače podrazumevana. */
function bumbaru_valid_color( $id, $def ) {
	$id = sanitize_key( (string) $id );
	foreach ( bumbaru_colors_in_stock() as $c ) {
		if ( $c['id'] === $id ) {
			return $id;
		}
	}
	return bumbaru_pick_color( $def );
}

/** Izbor kupca iz POST-a (bu_json ili stara polja). */
function bumbaru_choice_input( $src ) {
	if ( isset( $src['bu_json'] ) ) {
		$j = json_decode( wp_unslash( (string) $src['bu_json'] ), true );
		if ( is_array( $j ) ) {
			return $j;
		}
	}
	// Stari obrazac (bez skripte): bu_ime, bu_imena[], bu_motiv, bu_boja, bu_boja_imena, bu_zvezda, bu_opt[], bu_napomena
	$g = function ( $k ) use ( $src ) {
		return isset( $src[ $k ] ) ? wp_unslash( $src[ $k ] ) : null;
	};
	return array_filter( array(
		'ime'       => $g( 'bu_ime' ) ? $g( 'bu_ime' ) : $g( 'ime' ),
		'imena'     => $g( 'bu_imena' ),
		'motiv'     => $g( 'bu_motiv' ),
		'boja'      => $g( 'bu_boja' ),
		'bojaImena' => $g( 'bu_boja_imena' ),
		'zvezda'    => $g( 'bu_zvezda' ),
		'opcije'    => $g( 'bu_opt' ),
		'napomena'  => $g( 'bu_napomena' ) ? $g( 'bu_napomena' ) : $g( 'napomena' ),
	), function ( $v ) {
		return null !== $v;
	} );
}

/**
 * Izbor kupca → [ 'lines' => [[naziv, vrednost]…], 'price' => cena jedne stavke posle popusta (ili null),
 *  'qty' => količina koju nameće izbor (ili 0), 'name' => naziv stavke (komplet), 'raw' => … ] ili WP_Error.
 */
function bumbaru_read_choice( $product, $src ) {
	$c     = bumbaru_config( $product );
	$type  = $c['type'];
	$in    = bumbaru_choice_input( $src );
	$get   = function ( $k, $d = null ) use ( $in ) {
		return isset( $in[ $k ] ) ? $in[ $k ] : $d;
	};
	$lines = array();
	$raw   = array( 'type' => $type );
	$base  = bumbaru_base_price( $product );
	$price = null; // cena jedne stavke posle popusta
	$list  = null; // ista stavka po redovnoj ceni (bez akcije), za pravilo „važi veći popust”
	$qty   = 0;
	$name  = '';
	$oi    = bumbaru_price_option_index( $c );

	// Izabrane vrednosti opcija (indeksi) i redovna cena izabrane vrednosti opcije sa cenom.
	$opts   = (array) $get( 'opcije', array() );
	$sel    = array();
	$add    = 0;
	$optval = 0; // redovna cena izabrane vrednosti (opcija sa cenom)
	$old    = 0;
	$vel    = 0;
	foreach ( $c['options'] as $i => $o ) {
		$vi = isset( $opts[ $i ] ) ? (int) $opts[ $i ] : ( isset( $opts[ 'o-' . $i ] ) ? (int) $opts[ 'o-' . $i ] : 0 );
		if ( ! isset( $o['values'][ $vi ] ) ) {
			$vi = 0;
		}
		$sel[ $i ] = $vi;
		$v         = $o['values'][ $vi ];
		if ( $i === $oi ) {
			$optval = bumbaru_value_price( $product, $v );
			$old    = $v['old'];
			$vel    = $vi;
		} else {
			$add += (float) $v['add'];
		}
	}
	$unit_list = ( $optval ? $optval : $base ) + $add; // redovna cena jednog komada

	if ( 'ukras' === $type || 'ime' === $type ) {
		$ime = bumbaru_clean_name( $get( 'ime', '' ) );
		if ( '' === $ime ) {
			return new WP_Error( 'ime', 'Upišite ime koje štampamo.' );
		}
		$lines[]    = array( 'Ime', $ime );
		$raw['ime'] = $ime;
	}
	if ( 'set' === $type || 'imena' === $type ) {
		$n     = 'set' === $type ? ( '5' === (string) $get( 'broj', '4' ) ? 5 : 4 ) : 4;
		$names = array();
		foreach ( array_slice( array_values( (array) $get( 'imena', array() ) ), 0, $n ) as $v ) {
			$v = bumbaru_clean_name( $v );
			if ( '' !== $v ) {
				$names[] = $v;
			}
		}
		$need = 'set' === $type ? $n : 2;
		if ( count( $names ) < $need ) {
			return new WP_Error( 'imena', 'set' === $type ? ( 4 === $n ? 'Upišite sva 4 imena.' : 'Upišite svih 5 imena.' ) : 'Upišite najmanje 2 imena.' );
		}
		if ( 'set' === $type ) {
			$lines[]     = array( 'Broj ukrasa', (string) $n );
			$raw['broj'] = $n;
			if ( 5 === $n ) {
				$unit_list += (float) $c['set_extra'];
			}
		}
		$lines[]      = array( 'Imena', implode( ', ', $names ) );
		$raw['imena'] = $names;
	}
	if ( 'ukras' === $type || 'set' === $type ) {
		$m  = bumbaru_motiv( sanitize_key( (string) $get( 'motiv', 'zvezde' ) ) );
		$b  = sanitize_key( (string) $get( 'boja', '' ) );
		$b  = 'set' === $type && 'sareno' === $b ? 'sareno' : bumbaru_valid_color( $b, 'bela' );
		$nc = sanitize_key( (string) $get( 'bojaImena', 'ista' ) );
		$nc = 'ista' === $nc ? 'ista' : bumbaru_valid_color( $nc, 'crvena' );
		$fo = bumbaru_font( sanitize_key( (string) $get( 'font', 'pacifico' ) ) );
		$lines[]           = array( 'Motiv', $m['label'] );
		$lines[]           = array( 'Slova', $fo['label'] );
		$raw['font']       = $fo['id'];
		$lines[]           = array( 'Boja ukrasa', 'sareno' === $b ? 'Šareno' : bumbaru_color_label( $b ) );
		$lines[]           = array( 'Boja imena', 'ista' === $nc ? 'Kao ukras' : bumbaru_color_label( $nc ) );
		$raw['motiv']      = $m['id'];
		$raw['boja']       = $b;
		$raw['boja_imena'] = $nc;
		if ( 'sareno' === $b ) {
			$raw['sareno'] = bumbaru_sareno();
		}
	}
	if ( 'privezak' === $type ) {
		$kc    = $c['keychain'];
		$names = array();
		foreach ( (array) $get( 'imena', array() ) as $v ) {
			$v = bumbaru_clean_name( $v );
			if ( '' !== $v ) {
				$names[] = $v;
			}
		}
		$names = array_slice( $names, 0, $kc['maxImena'] );
		if ( count( $names ) < max( 1, $kc['minImena'] ) ) {
			return new WP_Error( 'imena', $kc['minImena'] > 1 ? 'Upišite najmanje ' . $kc['minImena'] . ' imena.' : 'Upišite ime koje štampamo.' );
		}
		$sid = sanitize_key( (string) $get( 'stil', '' ) );
		if ( ! in_array( $sid, $kc['stilovi'], true ) ) {
			$sid = $kc['stilovi'][0];
		}
		$s      = bumbaru_kc_style( $sid );
		$sl     = (array) $get( 'slojevi', array() );
		$sar    = ! empty( $s['sareno'] ) ? $s['sareno'] : 'slova';
		$layer  = array();
		$sareno = false;
		foreach ( array( 'slova', 'okvir', 'osnova' ) as $L ) {
			$v = isset( $sl[ $L ] ) ? sanitize_key( $sl[ $L ] ) : '';
			if ( 'sareno' === $v && $kc['sareno'] && $L === $sar ) {
				$layer[ $L ] = 'sareno';
				$sareno      = true;
			} else {
				$layer[ $L ] = bumbaru_valid_color( $v, $s['def'][ $L ] );
			}
		}
		$lines[] = array( 'Stil slova', $s['label'] );
		if ( $sareno ) {
			$boje = isset( $in['sarenoBoje'] ) ? array_map( 'sanitize_key', (array) $in['sarenoBoje'] ) : array();
			$parts = array();
			foreach ( $names as $i => $n ) {
				$parts[] = $n . ( ! empty( $boje[ $i ] ) ? ' (' . mb_strtolower( bumbaru_color_label( $boje[ $i ] ), 'UTF-8' ) . ')' : '' );
			}
			$lines[] = array( 'okvir' === $sar ? 'Imena i boja okvira' : 'Imena i boja slova', implode( ', ', $parts ) );
		} else {
			$lines[] = array( count( $names ) > 1 ? 'Imena' : 'Ime', implode( ', ', $names ) );
		}
		if ( ! ( $sareno && 'slova' === $sar ) ) {
			$lines[] = array( 'Boja slova', bumbaru_color_label( $layer['slova'] ) );
		}
		if ( in_array( 'okvir', $s['slojevi'], true ) && ! ( $sareno && 'okvir' === $sar ) ) {
			$lines[] = array( 'Boja okvira', bumbaru_color_label( $layer['okvir'] ) );
		}
		$lines[]        = array( 'Boja osnove', bumbaru_color_label( $layer['osnova'] ) );
		$raw['imena']   = $names;
		$raw['stil']    = $sid;
		$raw['slojevi'] = $layer;
		if ( $base ) {
			$price = bumbaru_eff( $product, $unit_list ) * count( $names );
			$list  = $unit_list * count( $names );
		}
		$qty = 1;
	}
	if ( 'jelka' === $type ) {
		$s = sanitize_key( (string) $get( 'zvezda', '' ) );
		if ( ! in_array( $s, $c['star_colors'], true ) ) {
			$s = $c['star_default'];
		}
		$lines[]           = array( 'Boja zvezdice', bumbaru_color_label( $s ) );
		$raw['zvezda']     = $s;
		$raw['tree']       = $c['tree'];
		$raw['tree_color'] = $c['tree_color'];
	}
	if ( 'sastav' === $type ) {
		$broj = $c['sastav']['broj'];
		$pick = (array) $get( 'sastav', array() );
		$all  = array();
		foreach ( bumbaru_set_modlice() as $m ) {
			$all[ $m['id'] ] = $m['naziv'];
		}
		$sum   = 0;
		$parts = array();
		foreach ( $pick as $id => $n ) {
			$id = sanitize_key( $id );
			$n  = max( 0, (int) $n );
			if ( ! $n || ! isset( $all[ $id ] ) ) {
				continue;
			}
			$sum    += $n;
			$parts[] = $all[ $id ] . ( $n > 1 ? ' ×' . $n : '' );
		}
		if ( $sum !== $broj ) {
			return new WP_Error( 'sastav', 'U setu je ' . $broj . ' modlica, a izabrano je ' . $sum . '.' . ( $sum < $broj ? ' Izaberite još ' . ( $broj - $sum ) . '.' : '' ) );
		}
		$lines[]       = array( 'Modlice u setu', implode( ', ', $parts ) );
		$raw['sastav'] = $pick;
	}
	if ( bumbaru_izbor_komada( $product ) ) {
		$m     = $c['motivi'];
		$boje  = 'boje' === $type;
		$pick  = (array) $get( 'motivi', array() );
		$items = array();
		if ( $boje ) {
			foreach ( bumbaru_colors_in_stock() as $b ) {
				$items[ $b['id'] ] = array( 'naziv' => $b['label'], 'cena' => 0, 'cene' => array() );
			}
		} else {
			foreach ( $m['lista'] as $it ) {
				$items[ $it['id'] ] = $it;
			}
		}
		$n     = 0;
		$parts = array();
		$sum_e = 0;
		$sum_l = 0;
		$osn   = $optval ? $optval : $base; // osnovna cena po komadu za izabranu veličinu
		foreach ( $items as $id => $it ) {
			$k = isset( $pick[ $id ] ) ? max( 0, min( 99, (int) $pick[ $id ] ) ) : 0;
			if ( ! $k ) {
				continue;
			}
			$n      += $k;
			$parts[] = $it['naziv'] . ( $k > 1 ? ' ×' . $k : '' );
			if ( $base ) {
				if ( ! empty( $it['cena'] ) && $it['cena'] != $base ) {
					$jed = ! empty( $it['cene'][ $vel ] ) ? $it['cene'][ $vel ] : round( $it['cena'] * $osn / $base / 10 ) * 10;
				} else {
					$jed = $osn;
				}
				$sum_l += $k * $jed;
				$sum_e += $k * bumbaru_eff( $product, $jed );
			}
		}
		if ( ! $n ) {
			return new WP_Error( 'motivi', $boje ? 'Izaberite bar jednu boju.' : 'Izaberite bar jedan motiv.' );
		}
		$pct     = bumbaru_popust_za( $m['popusti'], $n );
		$lines[] = array( $boje ? 'Boje' : 'Motivi', implode( ', ', $parts ) );
		$lines[] = array( 'Komada', (string) $n );
		if ( $pct ) {
			$lines[] = array( 'Popust', $pct . '%' );
		}
		if ( $base ) {
			// Važi veći popust (akcija ili popust za broj komada), ne zbir oba.
			$price = min( round( $sum_e / 10 ) * 10, round( $sum_l * ( 1 - $pct / 100 ) / 10 ) * 10 );
			$list  = $sum_l;
			if ( $price < $sum_l ) {
				$lines[] = array( 'Umesto', bumbaru_din( $sum_l ) );
			}
		}
		$raw['motivi'] = $pick;
		$qty           = 1;
	}
	// Izbor boja filamenta
	$bj = (array) $get( 'boje', array() );
	foreach ( $c['izbor_boja'] as $b ) {
		$v       = bumbaru_valid_color( isset( $bj[ $b['id'] ] ) ? $bj[ $b['id'] ] : '', $b['def'] );
		$lines[] = array( $b['label'], bumbaru_color_label( $v ) );
		$raw['boje'][ $b['id'] ] = $v;
	}
	foreach ( $c['options'] as $i => $o ) {
		$lines[] = array( $o['label'], $o['values'][ $sel[ $i ] ]['label'] );
	}
	$raw['opcije'] = $sel;

	// Komplet: ovaj proizvod + proizvodi uz njega, sa popustom na ukupnu cenu
	if ( $get( 'komplet' ) ) {
		$ksel = $get( 'kompletUz' );
		$k   = bumbaru_komplet( $product, is_array( $ksel ) ? $ksel : null, $oi >= 0 ? $c['options'][ $oi ]['values'][ $vel ]['label'] : '' );
		if ( $k && null === $price ) {
			$mine  = array();
			foreach ( $lines as $l ) {
				$mine[] = $l[0] . ': ' . $l[1];
			}
			$zbir  = bumbaru_eff( $product, $unit_list );
			$zlist = $unit_list;
			$lines = array( array( $product->get_name(), $mine ? implode( ', ', $mine ) : 'osnovni izbor' ) );
			$nazivi = array( $product->get_name() );
			foreach ( $k['uz'] as $x ) {
				$zbir    += $x['eff'];
				$zlist   += $x['cena'];
				$nazivi[] = $x['naziv'];
				$lines[]  = array( $x['naziv'] . ' (šifra ' . $x['id'] . ')', $x['izbor'] ? implode( ', ', $x['izbor'] ) : 'osnovni izbor' );
			}
			$lines[]        = array( 'Popust za komplet', $k['popust'] . '%' );
			// Važi veći popust (akcija ili popust za komplet), ne zbir oba.
			$price          = min( round( $zbir / 10 ) * 10, round( $zlist * ( 1 - $k['popust'] / 100 ) / 10 ) * 10 );
			$list           = $zlist;
			$name           = 'Komplet: ' . implode( ' + ', $nazivi );
			$raw['komplet'] = wp_list_pluck( $k['uz'], 'pid' );
		}
	}

	$nap = trim( sanitize_text_field( (string) $get( 'napomena', '' ) ) );
	if ( '' !== $nap ) {
		$lines[] = array( 'Napomena', mb_substr( $nap, 0, 160, 'UTF-8' ) );
	}
	// Obična cena po komadu: izabrana opcija (sa cenom ili doplatom), posle popusta
	if ( null === $price && $base && ( $unit_list != $base || 'set' === $type ) ) {
		$price = bumbaru_eff( $product, $unit_list );
		$list  = $unit_list;
	}
	return array( 'lines' => $lines, 'price' => $price, 'list' => $list, 'qty' => $qty, 'name' => $name, 'raw' => $raw, 'old' => $old );
}

// Provera pre dodavanja u korpu.
add_filter( 'woocommerce_add_to_cart_validation', function ( $ok, $product_id, $qty, $variation_id = 0 ) {
	$product = wc_get_product( $variation_id ? $variation_id : $product_id );
	if ( ! $product ) {
		return $ok;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce obrazac za korpu nema nonce.
	$choice = bumbaru_read_choice( $product, $_POST );
	if ( is_wp_error( $choice ) ) {
		wc_add_notice( $choice->get_error_message(), 'error' );
		return false;
	}
	return $ok;
}, 10, 4 );

// Izbor kupca ide uz proizvod u korpi (različit izbor = posebna stavka).
add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id, $variation_id ) {
	$product = wc_get_product( $variation_id ? $variation_id : $product_id );
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$choice = $product ? bumbaru_read_choice( $product, $_POST ) : null;
	if ( $choice && ! is_wp_error( $choice ) && ( $choice['lines'] || null !== $choice['price'] ) ) {
		$data['bumbaru'] = $choice;
	}
	return $data;
}, 10, 3 );

// Izbor koji nameće količinu (npr. 8 modlica = jedna stavka sa cenom za sve) ide kao jedna stavka.
add_filter( 'woocommerce_add_to_cart_quantity', function ( $qty, $product_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['bu_json'] ) ) {
		return $qty;
	}
	$product = wc_get_product( $product_id );
	$choice  = $product ? bumbaru_read_choice( $product, $_POST ) : null; // phpcs:ignore
	return ( $choice && ! is_wp_error( $choice ) && $choice['qty'] ) ? $choice['qty'] : $qty;
}, 10, 2 );

add_filter( 'woocommerce_get_cart_item_from_session', function ( $item, $values ) {
	if ( isset( $values['bumbaru'] ) ) {
		$item['bumbaru'] = $values['bumbaru'];
	}
	return $item;
}, 10, 2 );

// Cena stavke iz izbora (opcija sa cenom, broj imena, motivi sa popustom, komplet).
add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	foreach ( $cart->get_cart() as $item ) {
		if ( ! isset( $item['bumbaru'] ) || null === $item['bumbaru']['price'] ) {
			continue;
		}
		$item['data']->update_meta_data( '_bu_cart_price', (string) $item['bumbaru']['price'] );
		$item['data']->set_price( (float) $item['bumbaru']['price'] );
	}
}, 20 );

// Popust za više komada: saberu se svi komadi istog proizvoda u korpi (ukrasi sa različitim imenima).
add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	$count = array();
	foreach ( $cart->get_cart() as $item ) {
		$count[ $item['product_id'] ] = ( isset( $count[ $item['product_id'] ] ) ? $count[ $item['product_id'] ] : 0 ) + (int) $item['quantity'];
	}
	foreach ( $cart->get_cart() as $key => $item ) {
		$tiers = bumbaru_config( $item['product_id'] )['kolicina'];
		$pct   = $tiers ? bumbaru_popust_za( $tiers, $count[ $item['product_id'] ] ) : 0;
		if ( isset( $item['bumbaru']['price'] ) && null !== $item['bumbaru']['price'] ) {
			$unit = (float) $item['bumbaru']['price'];
			$list = ! empty( $item['bumbaru']['list'] ) ? (float) $item['bumbaru']['list'] : $unit;
		} else {
			$fresh = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
			$unit  = $fresh ? (float) $fresh->get_price() : 0;
			$list  = $fresh && (float) $fresh->get_regular_price( 'edit' ) > 0 ? (float) $fresh->get_regular_price( 'edit' ) : $unit;
		}
		// Važi veći popust: akcija (već u $unit) ili popust za količinu na redovnu cenu ($list), ne zbir oba.
		if ( $pct && bumbaru_snizi( $list, $pct ) >= $unit ) {
			$pct = 0;
		}
		$cart->cart_contents[ $key ]['bu_kolicina'] = $pct ? array( 'pct' => $pct, 'kom' => $count[ $item['product_id'] ], 'old' => $unit ) : null;
		if ( $pct && $unit ) {
			$new = bumbaru_snizi( $list, $pct );
			$item['data']->update_meta_data( '_bu_cart_price', (string) $new ); // akcija kategorije ovu cenu ne dira
			$item['data']->set_price( $new );
		} elseif ( ! isset( $item['bumbaru']['price'] ) || null === $item['bumbaru']['price'] ) {
			$item['data']->delete_meta_data( '_bu_cart_price' );
		}
	}
}, 30 );

add_filter( 'woocommerce_get_item_data', function ( $out, $item ) {
	if ( ! empty( $item['bu_kolicina'] ) ) {
		$k     = $item['bu_kolicina'];
		$out[] = array( 'key' => 'Popust za količinu', 'value' => '−' . $k['pct'] . '% (' . $k['kom'] . ' kom)', 'display' => '−' . esc_html( $k['pct'] ) . '% (' . (int) $k['kom'] . ' kom)' );
	}
	return $out;
}, 20, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $order_item, $cart_key, $values ) {
	if ( ! empty( $values['bu_kolicina'] ) ) {
		$order_item->add_meta_data( 'Popust za količinu', '−' . $values['bu_kolicina']['pct'] . '% (' . $values['bu_kolicina']['kom'] . ' kom)' );
	}
}, 20, 3 );

// Naziv stavke (komplet).
add_filter( 'woocommerce_cart_item_name', function ( $name, $item ) {
	if ( ! empty( $item['bumbaru']['name'] ) ) {
		return esc_html( $item['bumbaru']['name'] );
	}
	return $name;
}, 10, 2 );

// Izbor se vidi u korpi i na plaćanju.
add_filter( 'woocommerce_get_item_data', function ( $out, $item ) {
	if ( ! empty( $item['bumbaru']['lines'] ) ) {
		foreach ( $item['bumbaru']['lines'] as $l ) {
			$out[] = array( 'key' => $l[0], 'value' => esc_html( $l[1] ), 'display' => esc_html( $l[1] ) );
		}
	}
	return $out;
}, 10, 2 );

// Izbor se čuva u porudžbini (vidi se u administraciji, u emailu i na potvrdi).
add_action( 'woocommerce_checkout_create_order_line_item', function ( $order_item, $cart_key, $values ) {
	if ( empty( $values['bumbaru'] ) ) {
		return;
	}
	if ( ! empty( $values['bumbaru']['name'] ) ) {
		$order_item->set_name( $values['bumbaru']['name'] );
	}
	foreach ( $values['bumbaru']['lines'] as $l ) {
		$order_item->add_meta_data( $l[0], $l[1] );
	}
	$order_item->add_meta_data( '_bu_raw', $values['bumbaru']['raw'] );
}, 10, 3 );

// Dodavanje bez skripte (obrazac) vodi pravo u korpu.
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url, $product = null ) {
	if ( $url || wp_doing_ajax() ) {
		return $url;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( isset( $_POST['bu_json'] ) || isset( $_POST['bu_napomena'] ) || isset( $_POST['napomena'] ) ) {
		return wc_get_cart_url();
	}
	if ( $product && is_object( $product ) ) {
		return get_permalink( $product->get_id() );
	}
	return $url;
}, 10, 2 );

// Poruka posle dodavanja, na srpskom.
add_filter( 'wc_add_to_cart_message_html', function ( $message, $products ) {
	$titles = array();
	foreach ( (array) $products as $pid => $qty ) {
		$titles[] = '„' . wp_strip_all_tags( get_the_title( $pid ) ) . '”' . ( $qty > 1 ? ' × ' . (int) $qty : '' );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ( isset( $_POST['bu_json'] ) || isset( $_POST['napomena'] ) ) && ! wp_doing_ajax() ) {
		$back = wp_get_referer() ? wp_get_referer() : wc_get_page_permalink( 'shop' );
		return sprintf(
			'<a href="%s" class="button">Nastavi kupovinu</a> %s %s u korpi. Kliknite „Nastavi na plaćanje” da završite porudžbinu.',
			esc_url( $back ),
			esc_html( implode( ', ', $titles ) ),
			count( $titles ) > 1 ? 'su' : 'je'
		);
	}
	return sprintf(
		'<a href="%s" class="button wc-forward">Pogledaj korpu</a> %s %s u korpi.',
		esc_url( wc_get_cart_url() ),
		esc_html( implode( ', ', $titles ) ),
		count( $titles ) > 1 ? 'su' : 'je'
	);
}, 10, 2 );

add_action( 'wc_ajax_bumbaru_add', 'bumbaru_ajax_add' );
add_action( 'wp_ajax_nopriv_bumbaru_add', 'bumbaru_ajax_add' );
add_action( 'wp_ajax_bumbaru_add', 'bumbaru_ajax_add' );
/** Dodavanje u korpu sa strane proizvoda: vraća broj u korpi, naziv i poruku greške. */
function bumbaru_ajax_add() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$pid = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$qty = isset( $_POST['quantity'] ) ? max( 1, min( 50, absint( $_POST['quantity'] ) ) ) : 1;
	// phpcs:enable
	$product = $pid ? wc_get_product( $pid ) : null;
	if ( ! $product || ! WC()->cart ) {
		wp_send_json( array( 'ok' => false, 'msg' => 'Proizvod nije pronađen.' ) );
	}
	wc_clear_notices();
	$ok  = false;
	$key = false;
	if ( apply_filters( 'woocommerce_add_to_cart_validation', true, $pid, $qty ) ) {
		$key = WC()->cart->add_to_cart( $pid, $qty );
		$ok  = false !== $key;
	}
	$errs = wc_get_notices( 'error' );
	wc_clear_notices();
	if ( ! $ok ) {
		$msg = $errs ? wp_strip_all_tags( is_array( $errs[0] ) ? $errs[0]['notice'] : $errs[0] ) : 'Proizvod nije dodat u korpu.';
		wp_send_json( array( 'ok' => false, 'msg' => $msg ) );
	}
	WC()->cart->calculate_totals();
	$frag = apply_filters( 'woocommerce_add_to_cart_fragments', array() );
	wp_send_json( array(
		'ok'        => true,
		'count'     => WC()->cart->get_cart_contents_count(),
		'u_korpi'   => bumbaru_u_korpi( $pid ),
		'naziv'     => $key && ! empty( WC()->cart->cart_contents[ $key ]['bumbaru']['name'] ) ? WC()->cart->cart_contents[ $key ]['bumbaru']['name'] : $product->get_name(),
		'fragments' => $frag,
		'cart'      => wc_get_cart_url(),
		'checkout'  => wc_get_checkout_url(),
	) );
}
