<?php
/**
 * Popust za različite proizvode (2.6): lutkice u pletenom izgledu, spiralna jelka, jelka sa postoljem i rebrasta tacna.
 *
 * Popust zavisi od broja RAZLIČITIH proizvoda iz grupe u korpi: 2 = −5%, 3 = −10%, 4 i više = −15%.
 * Svaki proizvod se broji jednom (različite veličine istog proizvoda = isti proizvod). Popust važi za po
 * jedan komad svakog različitog proizvoda (najskuplji izabrani, ako ih ima više), a dodatni komadi istog
 * proizvoda idu po punoj ceni. Iznos se zaokružuje na 10 din i u korpi je poseban red (umanjenje).
 * Više komada ISTOG proizvoda: 2 kom −5%, 4 i više −10% na sve komade tog proizvoda (veličine se sabiraju).
 * Popusti se ne sabiraju: svaki komad dobija veći od dva (za različite ili za isti proizvod).
 * Akcija kategorije ne važi za proizvode iz grupe i ne sabira se sa ovim popustom.
 *
 * Grupa i stepenice se mogu promeniti opcijom bumbaru_grupa: { slugs: [...], popusti: "2=5, 3=10, 4=15" }.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_grupa() {
	static $g = null;
	if ( null !== $g ) {
		return $g;
	}
	$s = get_option( 'bumbaru_grupa', array() );
	$s = is_array( $s ) ? $s : array();
	$g = array(
		'naziv'   => 'Popust za različite proizvode',
		'slugs'   => ! empty( $s['slugs'] ) ? array_values( array_map( 'sanitize_title', (array) $s['slugs'] ) ) : array(
			'irvas-lutkica-pleteni-izgled',
			'snesko-lutkica-pleteni-izgled',
			'deda-mraz-lutkica-pleteni-izgled',
			'spiralna-jelka-sa-zvezdom',
			'jelka-sa-postoljem',
			'rebrasta-ovalna-tacna',
		),
		'popusti' => bumbaru_parse_tiers( ! empty( $s['popusti'] ) ? $s['popusti'] : '2=5, 3=10, 4=15' ),
		'isti'    => bumbaru_parse_tiers( ! empty( $s['isti'] ) ? $s['isti'] : '2=5, 4=10' ),
	);
	return $g;
}

/** Adresa (slug) proizvoda iz grupe ili '' ako nije u grupi. */
function bumbaru_grupa_slug( $product ) {
	if ( is_object( $product ) ) {
		$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	} else {
		$id = (int) $product;
	}
	if ( ! $id ) {
		return '';
	}
	$slug = get_post_field( 'post_name', $id );
	return in_array( $slug, bumbaru_grupa()['slugs'], true ) ? $slug : '';
}

/** „2 različita”, „5 različitih”. */
function bumbaru_grupa_rec( $n ) {
	return $n % 10 >= 2 && $n % 10 <= 4 && ( $n % 100 < 12 || $n % 100 > 14 ) ? 'različita' : ( 1 === $n % 10 && 11 !== $n % 100 ? 'različit' : 'različitih' );
}

/** „1 proizvod”, „2 različita”, „5 različitih” (za oznaku na traci). */
function bumbaru_grupa_broj( $n ) {
	return 1 === (int) $n ? '1 proizvod' : (int) $n . ' ' . bumbaru_grupa_rec( (int) $n );
}

/**
 * Obračun popusta. $stavke = [ [ 'slug' => …, 'cena' => cena jednog komada, 'kom' => broj komada ], … ].
 * Za različite: po jedan komad svakog proizvoda (najskuplji) dobija popust po broju različitih.
 * Za isti: svi komadi proizvoda kog ima 2 ili više dobijaju popust po broju komada. Svaki komad dobija veći.
 * Vraća [ 'n' => broj različitih, 'pct' => popust za različite, 'isti' => [ slug => procenat ], 'puna', 'popust' (din), 'ukupno' ].
 */
function bumbaru_grupa_racun( $stavke, $tiers = null, $isti = null ) {
	$g     = bumbaru_grupa();
	$tiers = null === $tiers ? $g['popusti'] : $tiers;
	$isti  = null === $isti ? $g['isti'] : $isti;
	$kom   = array(); // slug => cene svih komada
	$puna  = 0;
	foreach ( $stavke as $s ) {
		$k = max( 0, (int) $s['kom'] );
		for ( $i = 0; $i < $k; $i++ ) {
			$kom[ $s['slug'] ][] = (float) $s['cena'];
		}
		$puna += (float) $s['cena'] * $k;
	}
	$n    = count( $kom );
	$pct  = bumbaru_popust_za( $tiers, $n );
	$ip   = array();
	$sir  = 0; // popust pre zaokruživanja
	foreach ( $kom as $slug => $cene ) {
		rsort( $cene );
		$ip[ $slug ] = bumbaru_popust_za( $isti, count( $cene ) );
		foreach ( $cene as $i => $c ) {
			$sir += $c * max( $ip[ $slug ], 0 === $i ? $pct : 0 ) / 100;
		}
	}
	$ukupno = $sir > 0 ? round( ( $puna - $sir ) / 10 ) * 10 : $puna;
	return array( 'n' => $n, 'pct' => $pct, 'isti' => array_filter( $ip ), 'puna' => $puna, 'popust' => $puna - $ukupno, 'ukupno' => $ukupno, 'slugs' => array_keys( $kom ) );
}

/** Naziv popusta za korpu i porudžbinu. */
function bumbaru_grupa_naziv( $r ) {
	$d = array();
	if ( $r['pct'] ) {
		$d[] = $r['n'] . ' ' . bumbaru_grupa_rec( $r['n'] ) . ' −' . (float) $r['pct'] . '%';
	}
	if ( $r['isti'] ) {
		$d[] = 'više istih −' . (float) max( $r['isti'] ) . '%';
	}
	return 'Popust za lutkice, jelke i tacnu (' . implode( ', ', $d ) . ')';
}

/** Stavke iz grupe u korpi: [ slug, cena, kom, naziv, izbor ]. */
function bumbaru_grupa_korpa( $cart = null ) {
	$cart = $cart ? $cart : ( function_exists( 'WC' ) && WC()->cart ? WC()->cart : null );
	$out  = array();
	if ( ! $cart ) {
		return $out;
	}
	foreach ( $cart->get_cart() as $item ) {
		$slug = bumbaru_grupa_slug( $item['data'] );
		// Komplet („Uz ovo često uzimaju”) već ima svoj popust, pa se ne broji.
		if ( ! $slug || ! empty( $item['bumbaru']['raw']['komplet'] ) ) {
			continue;
		}
		$izbor = array();
		if ( ! empty( $item['bumbaru']['lines'] ) ) {
			foreach ( $item['bumbaru']['lines'] as $l ) {
				if ( 'Napomena' !== $l[0] ) {
					$izbor[] = $l[0] . ': ' . $l[1];
				}
			}
		}
		$out[] = array(
			'slug'  => $slug,
			'cena'  => (float) $item['data']->get_price(),
			'kom'   => (int) $item['quantity'],
			'naziv' => $item['data']->get_name(),
			'izbor' => implode( ', ', $izbor ),
		);
	}
	return $out;
}

// Popust u korpi: poseban red (umanjenje), čuva se i u porudžbini i u emailu.
add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	$r = bumbaru_grupa_racun( bumbaru_grupa_korpa( $cart ) );
	if ( $r['popust'] > 0 ) {
		$cart->add_fee( bumbaru_grupa_naziv( $r ), -$r['popust'], false );
	}
} );

/** Naziv proizvoda iz grupe za prikaz (bez „(pleteni izgled)”). */
function bumbaru_grupa_kratko( $naziv ) {
	return trim( preg_replace( '/\s*\(pleteni izgled\)\s*/u', ' ', $naziv ) );
}

/** Proizvodi iz grupe na sajtu: [ slug => [ naziv, url ] ]. */
function bumbaru_grupa_lista() {
	static $l = null;
	if ( null !== $l ) {
		return $l;
	}
	$l = array();
	foreach ( bumbaru_grupa()['slugs'] as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $post && 'publish' === $post->post_status ) {
			$l[ $slug ] = array( 'naziv' => bumbaru_grupa_kratko( get_the_title( $post ) ), 'url' => get_permalink( $post ) );
		}
	}
	return $l;
}

/** Traka sa pčelicom (ista kao kod popusta za količinu): stepenice po broju različitih proizvoda. */
function bumbaru_grupa_traka( $n, $attrs = '' ) {
	$tiers = bumbaru_grupa()['popusti'];
	$max   = end( $tiers )['od'];
	$x     = min( $n, $max ) / $max * 100;
	$o     = '<div class="mm" aria-hidden="true"' . $attrs . '><div class="mm-track"><div class="mm-fill" data-mm-fill style="width:' . esc_attr( round( $x, 2 ) ) . '%"></div>';
	foreach ( $tiers as $i => $t ) {
		$last = count( $tiers ) - 1 === $i;
		$o   .= '<span class="mm-stop' . ( $n >= $t['od'] ? ' on' : '' ) . '" data-mm-stop="' . (int) $t['od'] . '" style="--x:' . esc_attr( round( $t['od'] / $max * 100, 2 ) ) . '%"><i></i><span class="mm-lab"><b>−' . esc_html( (float) $t['procenat'] ) . '%</b><small>' . (int) $t['od'] . ( $last ? '+' : '' ) . ' ' . esc_html( bumbaru_grupa_rec( (int) $t['od'] ) ) . '</small></span></span>';
	}
	$o .= '<img class="mm-bee' . ( bumbaru_popust_za( $tiers, $n ) ? ' pop' : '' ) . '" data-mm-bee style="left:' . esc_attr( round( $x, 2 ) ) . '%" src="' . esc_url( function_exists( 'bu_img' ) ? bu_img( 'brand/pcelica.webp' ) : '' ) . '" alt="" width="44" height="44" /></div></div>';
	return $o;
}

/** Tekst za sledeću stepenicu: „Dodajte još jedan različit proizvod za −10%.” */
function bumbaru_grupa_poruka( $n ) {
	foreach ( bumbaru_grupa()['popusti'] as $t ) {
		if ( $t['od'] > $n ) {
			$k = $t['od'] - $n;
			return 1 === $k ? 'Dodajte još jedan različit proizvod za −' . (float) $t['procenat'] . '%.' : 'Dodajte još ' . $k . ' različita proizvoda za −' . (float) $t['procenat'] . '%.';
		}
	}
	return 'Imate najveći popust za različite proizvode.';
}

/** Linkovi na proizvode iz grupe kojih još nema u izboru. */
function bumbaru_grupa_predlog( $imam ) {
	$a = array();
	foreach ( bumbaru_grupa_lista() as $slug => $p ) {
		if ( ! in_array( $slug, $imam, true ) ) {
			$a[] = '<a href="' . esc_url( $p['url'] ) . '">' . esc_html( $p['naziv'] ) . '</a>';
		}
	}
	return $a ? 'Može uz ovo: ' . implode( ', ', $a ) . '.' : '';
}

/** Strana proizvoda iz grupe: traka sa pčelicom; skripta (grupa.js) računa uživo sa izborom i korpom. */
function bumbaru_grupa_html( $product ) {
	$slug = bumbaru_grupa_slug( $product );
	if ( ! $slug || ! bumbaru_base_price( $product ) ) {
		return '';
	}
	$korpa = array();
	foreach ( bumbaru_grupa_korpa() as $s ) {
		$korpa[] = array( 'slug' => $s['slug'], 'cena' => $s['cena'], 'kom' => $s['kom'], 'naziv' => $s['naziv'], 'izbor' => $s['izbor'] );
	}
	$lista = array();
	foreach ( bumbaru_grupa_lista() as $s => $p ) {
		$lista[] = array( 'slug' => $s, 'naziv' => $p['naziv'], 'url' => $p['url'] );
	}
	$data = array(
		'tiers' => bumbaru_grupa()['popusti'],
		'isti'  => bumbaru_grupa()['isti'],
		'slug'  => $slug,
		'sku'   => $product->get_sku(),
		'cena'  => bumbaru_base_price( $product ),
		'korpa' => $korpa,
		'lista' => $lista,
	);
	$imam = array_unique( array_merge( wp_list_pluck( $korpa, 'slug' ), array( $slug ) ) );
	$n    = count( $imam );
	return '<div class="field mot kol grp" data-grupa="' . esc_attr( wp_json_encode( $data ) ) . '"><p class="flabel">Popust za različite proizvode</p>'
		. '<p class="hint mot-hint">Lutkice, spiralna jelka, jelka sa postoljem i rebrasta tacna: 2 različita −5%, 3 različita −10%, 4 i više −15% (na po jedan komad svakog). Više komada istog proizvoda: 2 kom −5%, 4 i više −10%. Popusti se ne sabiraju, važi veći.</p>'
		. bumbaru_grupa_traka( $n )
		. '<p class="mm-msg" aria-live="polite"><span class="mm-count" data-mm-count>' . esc_html( bumbaru_grupa_broj( $n ) ) . '</span><span class="mm-badge" data-mm-badge hidden></span><span data-mm-text>' . esc_html( bumbaru_grupa_poruka( $n ) ) . '</span></p>'
		. '<p class="mm-total" data-mm-total hidden></p>'
		. '<p class="mm-set" data-mm-set>' . bumbaru_grupa_predlog( $imam ) . '</p>'
		. '</div>';
}

// Korpa: ista traka iznad stavki (u obrascu korpe, pa se osveži kad se promeni količina).
add_action( 'woocommerce_before_cart_table', function () {
	$st = bumbaru_grupa_korpa();
	if ( ! $st ) {
		return;
	}
	$r = bumbaru_grupa_racun( $st );
	echo '<div class="cart-grupa" data-cart-grupa><p class="cg-t"><b>Popust za različite proizvode</b> <span>2 različita −5% · 3 različita −10% · 4+ −15% · isti proizvod: 2 kom −5%, 4+ −10%</span></p>'; // phpcs:ignore
	echo bumbaru_grupa_traka( $r['n'] ); // phpcs:ignore
	echo '<p class="mm-msg"><span class="mm-count">' . esc_html( bumbaru_grupa_broj( $r['n'] ) ) . '</span>'
		. ( $r['pct'] ? '<span class="mm-badge pop">Popust ' . esc_html( (float) $r['pct'] ) . '%</span>' : '' )
		. '<span>' . esc_html( bumbaru_grupa_poruka( $r['n'] ) ) . '</span></p>';
	if ( $r['popust'] > 0 ) {
		echo '<p class="mm-total">Ovi proizvodi sa popustom: <strong>' . esc_html( bumbaru_din( $r['ukupno'] ) ) . '</strong><s>' . esc_html( bumbaru_din( $r['puna'] ) ) . '</s></p>';
	}
	$pr = bumbaru_grupa_predlog( $r['slugs'] );
	if ( $pr ) {
		echo '<p class="mm-set">' . $pr . '</p>'; // phpcs:ignore
	}
	echo '</div>';
} );
