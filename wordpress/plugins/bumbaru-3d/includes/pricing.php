<?php
/**
 * Cene: akcija za celu kategoriju (npr. „Novogodišnja akcija −10%”), cene po opciji (veličina),
 * precrtane redovne cene, cena po komadu i popust za više komada.
 *
 * Akcija se podešava u Bumbaru 3D → Akcija. Važi do kraja izabranog dana (Beograd) i posle toga
 * sama prestaje. Proizvod koji ima svoju akcijsku cenu (Proizvod → Opšte) zadržava nju.
 * „Samo za šifre” (npr. NG-01): akcija važi samo za te proizvode, a ne za celu kategoriju.
 * Proizvodi sa popustom za različite proizvode (grupa.php) nemaju akciju.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_akcija_defaults() {
	return array(
		'ukljucena'  => 1,
		'naziv'      => 'Novogodišnja akcija',
		'procenat'   => 10,
		'kategorija' => 'nova-godina',
		'do'         => '2026-12-31',
		'samo'       => '',
	);
}

/** Šifre iz polja „Samo za šifre” („NG-01, NG-02” → [NG-01, NG-02]). */
function bumbaru_akcija_sifre( $txt ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/[\s,;]+/', strtoupper( (string) $txt ) ) ) ) );
}

function bumbaru_akcija_settings() {
	$s = get_option( 'bumbaru_akcija', null );
	return wp_parse_args( is_array( $s ) ? $s : array(), bumbaru_akcija_defaults() );
}

/** Aktivna akcija ili null. 'kraj' = UNIX vreme kraja (23:59:59 izabranog dana, Beograd). */
function bumbaru_akcija() {
	static $a = false;
	if ( false !== $a ) {
		return $a;
	}
	$s = bumbaru_akcija_settings();
	$a = null;
	if ( empty( $s['ukljucena'] ) || (float) $s['procenat'] <= 0 || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $s['do'] ) ) {
		return $a;
	}
	try {
		$kraj = new DateTime( $s['do'] . ' 23:59:59', new DateTimeZone( 'Europe/Belgrade' ) );
	} catch ( Exception $e ) {
		return $a;
	}
	if ( time() > $kraj->getTimestamp() ) {
		return $a;
	}
	$a = array(
		'naziv'      => (string) $s['naziv'],
		'procenat'   => (float) $s['procenat'],
		'kategorija' => (string) $s['kategorija'],
		'do'         => (string) $s['do'],
		'kraj'       => $kraj->getTimestamp(),
		'kraj_iso'   => $kraj->format( DATE_ATOM ),
		'samo'       => bumbaru_akcija_sifre( $s['samo'] ),
	);
	// Za šta važi (traka u meniju, strana /akcija/): nazivi proizvoda ili kategorija.
	$a['opis'] = '';
	if ( $a['samo'] && function_exists( 'wc_get_product_id_by_sku' ) ) {
		$imena = array();
		foreach ( $a['samo'] as $sku ) {
			$pid = wc_get_product_id_by_sku( $sku );
			if ( $pid ) {
				$imena[] = get_the_title( $pid );
			}
		}
		$a['opis'] = implode( ', ', $imena );
	}
	if ( '' === $a['opis'] ) {
		$t         = get_term_by( 'slug', $a['kategorija'], 'product_cat' );
		$a['opis'] = 'sve iz kategorije ' . ( $t ? $t->name : $a['kategorija'] );
	}
	return $a;
}

/** „31. 12.” iz „2026-12-31”. */
function bumbaru_datum_kratko( $iso ) {
	$p = explode( '-', (string) $iso );
	return 3 === count( $p ) ? (int) $p[2] . '. ' . (int) $p[1] . '.' : $iso;
}

/** Cena posle popusta u procentima, zaokružena na 10 din. */
function bumbaru_snizi( $n, $pct ) {
	return $pct ? round( $n * ( 1 - $pct / 100 ) / 10 ) * 10 : $n;
}

/** Procenat akcije za proizvod (0 ako ne važi). Proizvod sa svojom akcijskom cenom nema akciju kategorije. */
function bumbaru_akcija_pct( $product ) {
	$a = bumbaru_akcija();
	if ( ! $a || ! $product ) {
		return 0;
	}
	$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	if ( '' !== (string) get_post_meta( $id, '_sale_price', true ) ) {
		return 0;
	}
	if ( '' === (string) get_post_meta( $id, '_regular_price', true ) ) {
		return 0;
	}
	if ( function_exists( 'bumbaru_grupa_slug' ) && bumbaru_grupa_slug( $id ) ) {
		return 0; // popust za različite proizvode se ne sabira sa akcijom
	}
	if ( $a['samo'] ) {
		return in_array( strtoupper( (string) get_post_meta( $id, '_sku', true ) ), $a['samo'], true ) ? $a['procenat'] : 0;
	}
	return has_term( $a['kategorija'], 'product_cat', $id ) ? $a['procenat'] : 0;
}

// Akcija kategorije kao akcijska cena WooCommerce-a (bedž, precrtana cena, korpa, Google).
// Stavke u korpi sa već izračunatom cenom (izbor kupca) se ne diraju.
function bumbaru_ak_filter_price( $price, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $price;
	}
	if ( '' !== (string) $product->get_meta( '_bu_cart_price', true ) ) {
		return $price;
	}
	$pct = bumbaru_akcija_pct( $product );
	if ( ! $pct ) {
		return $price;
	}
	$reg = (float) $product->get_regular_price( 'edit' );
	return $reg > 0 ? (string) bumbaru_snizi( $reg, $pct ) : $price;
}
add_filter( 'woocommerce_product_get_price', 'bumbaru_ak_filter_price', 20, 2 );
add_filter( 'woocommerce_product_get_sale_price', function ( $price, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $price;
	}
	$pct = bumbaru_akcija_pct( $product );
	if ( ! $pct ) {
		return $price;
	}
	$reg = (float) $product->get_regular_price( 'edit' );
	return $reg > 0 ? (string) bumbaru_snizi( $reg, $pct ) : $price;
}, 20, 2 );
add_filter( 'woocommerce_product_is_on_sale', function ( $on, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $on;
	}
	return $on || bumbaru_akcija_pct( $product ) > 0;
}, 20, 2 );
add_filter( 'woocommerce_product_get_date_on_sale_to', function ( $date, $product ) {
	if ( $date || is_admin() || ! bumbaru_akcija_pct( $product ) ) {
		return $date;
	}
	$a = bumbaru_akcija();
	return new WC_DateTime( '@' . $a['kraj'] );
}, 20, 2 );

/** Popust koji važi za proizvod: [procenat, faktor] (sopstvena akcijska cena ili akcija kategorije). */
function bumbaru_discount( $product ) {
	$reg  = (float) $product->get_regular_price( 'edit' );
	$sale = (string) $product->get_sale_price( 'edit' );
	if ( '' !== $sale && $reg > 0 && (float) $sale < $reg && $product->is_on_sale( 'edit' ) ) {
		return array( 'own', (float) $sale / $reg );
	}
	$pct = bumbaru_akcija_pct( $product );
	return $pct ? array( 'ak', $pct ) : array( '', 0 );
}

/** Cena posle popusta za datu „redovnu” cenu (osnovna cena, cena opcije, cena motiva). */
function bumbaru_eff( $product, $n ) {
	list( $kind, $v ) = bumbaru_discount( $product );
	if ( 'own' === $kind ) {
		return max( 0, round( $n * $v / 10 ) * 10 );
	}
	if ( 'ak' === $kind ) {
		return bumbaru_snizi( $n, $v );
	}
	return $n;
}

/** Procenat popusta za prikaz (−10%), 0 ako ga nema. */
function bumbaru_discount_pct( $product ) {
	list( $kind, $v ) = bumbaru_discount( $product );
	if ( 'own' === $kind ) {
		return (int) round( ( 1 - $v ) * 100 );
	}
	return 'ak' === $kind ? (int) round( $v ) : 0;
}

/** Redovna cena proizvoda (bez akcije). */
function bumbaru_base_price( $product ) {
	$r = $product->get_regular_price( 'edit' );
	return '' === (string) $r ? 0 : (float) $r;
}

/** Redovna cena vrednosti opcije: sopstvena cena ili osnovna cena + doplata. */
function bumbaru_value_price( $product, $v ) {
	if ( ! empty( $v['abs'] ) ) {
		return (float) $v['abs'];
	}
	return bumbaru_base_price( $product ) + (float) $v['add'];
}

/**
 * Set (komplet je glavni deo strane, npr. tacna + do 3 figure): najmanja i najveća cena seta.
 * Najmanji = sa najjeftinijim jednim proizvodom, najveći = sa najviše najskupljih (popust po broju u setu).
 */
function bumbaru_set_cene( $product ) {
	$c = bumbaru_config( $product );
	if ( empty( $c['komplet']['glavni'] ) || ! function_exists( 'bumbaru_komplet' ) ) {
		return array();
	}
	$k = bumbaru_komplet( $product );
	if ( ! $k || ! $k['uz'] ) {
		return array();
	}
	$main = bumbaru_eff( $product, bumbaru_base_price( $product ) );
	$cene = wp_list_pluck( $k['uz'], 'eff' );
	sort( $cene );
	$max  = $c['komplet']['max'] ? min( $c['komplet']['max'], count( $cene ) ) : count( $cene );
	$sa   = function ( $lista ) use ( $main, $c ) {
		$p = bumbaru_popust_za( $c['komplet']['popusti'], 1 + count( $lista ) );
		return round( ( $main + array_sum( $lista ) ) * ( 1 - $p / 100 ) / 10 ) * 10;
	};
	return array( $sa( array_slice( $cene, 0, 1 ) ), $sa( array_slice( array_reverse( $cene ), 0, $max ) ) );
}

/** Sve cene koje kupac može da dobije (za „od … din” i Google), kao sveCene() na Astro sajtu. */
function bumbaru_all_prices( $product ) {
	$base = bumbaru_base_price( $product );
	if ( ! $base ) {
		return array();
	}
	$sc = bumbaru_set_cene( $product );
	if ( $sc ) {
		return $sc;
	}
	$c  = bumbaru_config( $product );
	$oi = bumbaru_price_option_index( $c );
	if ( $oi < 0 ) {
		// stari način: doplate po opcijama (zbir najmanjih i najvećih)
		$min = $base;
		$max = $base;
		foreach ( $c['options'] as $o ) {
			$adds = wp_list_pluck( $o['values'], 'add' );
			$min += min( $adds );
			$max += max( $adds );
		}
		if ( 'set' === $c['type'] ) {
			$max += (float) $c['set_extra'];
		}
		return array_values( array_unique( array( $min, $max ) ) );
	}
	$out = array();
	foreach ( $c['options'][ $oi ]['values'] as $v ) {
		$p = bumbaru_value_price( $product, $v );
		if ( $p > 0 ) {
			$out[] = $p;
		}
	}
	return $out;
}

/** Redovna (precrtana) cena za datu cenu iz opcije sa cenama (npr. akcijske cene modlica), 0 ako je nema. */
function bumbaru_old_for( $product, $n ) {
	$c  = bumbaru_config( $product );
	$oi = bumbaru_price_option_index( $c );
	if ( $oi < 0 ) {
		return 0;
	}
	foreach ( $c['options'][ $oi ]['values'] as $v ) {
		if ( bumbaru_value_price( $product, $v ) == $n && ! empty( $v['old'] ) && $v['old'] > $n ) {
			return (float) $v['old'];
		}
	}
	return 0;
}

/** Motivi sa različitim cenama (npr. medaljoni skuplji od osnovnih modlica). */
function bumbaru_razna_cena_motiva( $product ) {
	$c    = bumbaru_config( $product );
	$base = bumbaru_base_price( $product );
	if ( ! $base || 'motivi' !== $c['type'] ) {
		return false;
	}
	foreach ( $c['motivi']['lista'] as $m ) {
		if ( $m['cena'] && $m['cena'] != $base ) {
			return true;
		}
	}
	return false;
}

/** „1.590 din”. */
function bumbaru_din( $n ) {
	return number_format( (float) $n, 0, ',', '.' ) . ' din';
}

/**
 * Cena za prikaz (kao Cena.astro): redovna precrtana i cena posle popusta.
 * $n = redovna cena, $old = upisana stara cena (uvek precrtana), $pre/$post = „od ”, „ / kom”.
 */
function bumbaru_price_markup( $product, $n, $pre = '', $post = '', $old = 0 ) {
	$e = bumbaru_eff( $product, $n );
	if ( $old > $n ) {
		return esc_html( $pre ) . '<s class="ak-s">' . esc_html( bumbaru_din( $old ) ) . '</s> <span class="ak-n">' . esc_html( bumbaru_din( $e ) ) . '</span>' . esc_html( $post );
	}
	if ( $e < $n ) {
		return esc_html( $pre ) . '<s class="ak-s">' . esc_html( bumbaru_din( $n ) ) . '</s> <span class="ak-n">' . esc_html( bumbaru_din( $e ) ) . '</span>' . esc_html( $post );
	}
	return esc_html( $pre . bumbaru_din( $n ) . $post );
}

/** Cena na kartici: najmanja cena („od …”), po komadu kod izbora motiva i boja. */
function bumbaru_card_price( $product ) {
	$cene = bumbaru_all_prices( $product );
	if ( ! $cene ) {
		return null;
	}
	$n      = min( $cene );
	$raspon = count( $cene ) > 1 && min( $cene ) != max( $cene );
	$c      = bumbaru_config( $product );
	$from   = $raspon || get_post_meta( $product->get_id(), '_bu_price_from', true ) || bumbaru_razna_cena_motiva( $product );
	$old    = bumbaru_old_for( $product, $n );
	$pct    = bumbaru_discount_pct( $product );
	$stpct  = $old && ! $pct ? (int) round( ( 1 - $n / $old ) * 100 ) : 0;
	return array(
		'n'     => $n,
		'pre'   => $from ? 'od ' : '',
		'post'  => bumbaru_izbor_komada( $product ) ? ' / kom' : '',
		'old'   => $old,
		'pct'   => $pct,
		'stpct' => $stpct,
		'html'  => bumbaru_price_markup( $product, $n, $from ? 'od ' : '', bumbaru_izbor_komada( $product ) ? ' / kom' : '', $old ),
	);
}

/** Najveći popust za više komada (za oznaku „Popust do 30%” na kartici). */
function bumbaru_popust_do( $product ) {
	if ( ! bumbaru_izbor_komada( $product ) ) {
		return 0;
	}
	$c = bumbaru_config( $product );
	$m = 0;
	foreach ( $c['motivi']['popusti'] as $t ) {
		$m = max( $m, $t['procenat'] );
	}
	return $m;
}

function bumbaru_popust_za( $tiers, $n ) {
	$p = 0;
	foreach ( (array) $tiers as $t ) {
		if ( $n >= $t['od'] ) {
			$p = max( $p, $t['procenat'] );
		}
	}
	return $p;
}

// ——— Stare funkcije (tema ih i dalje može pozvati) ———

/** Najmanja i najveća doplata, za „od … din”. */
function bumbaru_add_range( $product ) {
	$base = bumbaru_base_price( $product );
	$all  = bumbaru_all_prices( $product );
	if ( ! $all || ! $base ) {
		return array( 0, 0 );
	}
	return array( min( $all ) - $base, max( $all ) - $base );
}

/** Doplata za podrazumevani izbor (prva vrednost svake opcije). */
function bumbaru_default_add( $product ) {
	$c   = bumbaru_config( $product );
	$add = 0;
	foreach ( $c['options'] as $o ) {
		$v    = $o['values'][0];
		$add += ! empty( $v['abs'] ) ? $v['abs'] - bumbaru_base_price( $product ) : (float) $v['add'];
	}
	return $add;
}

/** Cena jednog komada sa doplatom, posle popusta. */
function bumbaru_unit_price( $product, $add ) {
	return bumbaru_eff( $product, bumbaru_base_price( $product ) + $add );
}
