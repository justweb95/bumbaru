<?php
/**
 * Podaci koje vodite u pluginu: boje, filamenti, troškovi štampe i dodaci.
 * Sve se menja u Bumbaru 3D u administraciji.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Boje filamenta: face = boja površine, edge = tamnija ivica (za 3D izgled u pregledu),
 * dostupna = boja je na stanju (samo te boje kupac bira u izboru boja na sajtu).
 */
function bumbaru_default_colors() {
	return array(
		array( 'id' => 'bela', 'label' => 'Bela', 'face' => '#F3EEE5', 'edge' => '#B9AE9C', 'dostupna' => 1 ),
		array( 'id' => 'crna', 'label' => 'Crna', 'face' => '#2B2622', 'edge' => '#75726F', 'dostupna' => 1 ),
		array( 'id' => 'crvena', 'label' => 'Crvena', 'face' => '#B3312A', 'edge' => '#7E1F1A', 'dostupna' => 1 ),
		array( 'id' => 'zelena', 'label' => 'Zelena', 'face' => '#2F5B3B', 'edge' => '#1C3A25', 'dostupna' => 1 ),
		array( 'id' => 'svetlozelena', 'label' => 'Svetlozelena', 'face' => '#5DB761', 'edge' => '#3F7C42', 'dostupna' => 0 ),
		array( 'id' => 'zlatna', 'label' => 'Zlatna', 'face' => '#D6A23E', 'edge' => '#A0741F', 'dostupna' => 0 ),
		array( 'id' => 'zuta', 'label' => 'Žuta', 'face' => '#F2C230', 'edge' => '#A58421', 'dostupna' => 0 ),
		array( 'id' => 'narandzasta', 'label' => 'Narandžasta', 'face' => '#E8722A', 'edge' => '#9E4E1D', 'dostupna' => 1 ),
		array( 'id' => 'roze', 'label' => 'Roze', 'face' => '#E66A9C', 'edge' => '#9C486A', 'dostupna' => 0 ),
		array( 'id' => 'ljubicasta', 'label' => 'Ljubičasta', 'face' => '#7A4FB5', 'edge' => '#53367B', 'dostupna' => 0 ),
		array( 'id' => 'plava', 'label' => 'Plava', 'face' => '#2F6DB5', 'edge' => '#204A7B', 'dostupna' => 1 ),
		array( 'id' => 'tirkizna', 'label' => 'Tirkizna', 'face' => '#2BB3A8', 'edge' => '#1D7A72', 'dostupna' => 0 ),
		array( 'id' => 'siva', 'label' => 'Siva', 'face' => '#8A8D91', 'edge' => '#5E6063', 'dostupna' => 0 ),
		array( 'id' => 'srebrna', 'label' => 'Srebrna', 'face' => '#C9CDD2', 'edge' => '#8A9097', 'dostupna' => 0 ),
		array( 'id' => 'braon', 'label' => 'Braon', 'face' => '#7A5236', 'edge' => '#4E321F', 'dostupna' => 0 ),
	);
}

/**
 * Prelazak na verziju 2: boje dobijaju oznaku „na stanju” i nove boje iz novog sajta.
 * Boje koje ste sami menjali ostaju kakve jesu.
 */
function bumbaru_migrate_colors() {
	$saved = get_option( 'bumbaru_colors' );
	if ( ! is_array( $saved ) || ! $saved ) {
		return;
	}
	$defs  = array();
	foreach ( bumbaru_default_colors() as $c ) {
		$defs[ $c['id'] ] = $c;
	}
	// Ranije podrazumevane vrednosti (verzija 1): ako boja nije menjana, uzima novu nijansu.
	$v1  = array( 'crna' => '#2E2A28', 'tirkizna' => '#3FB5A3', 'plava' => '#3466A8', 'siva' => '#8C8C8C' );
	$ids = array();
	foreach ( $saved as $i => $c ) {
		$id    = isset( $c['id'] ) ? $c['id'] : '';
		$ids[] = $id;
		if ( isset( $v1[ $id ] ) && strtoupper( $c['face'] ) === $v1[ $id ] && isset( $defs[ $id ] ) ) {
			$saved[ $i ]['face'] = $defs[ $id ]['face'];
			$saved[ $i ]['edge'] = $defs[ $id ]['edge'];
		}
		if ( ! isset( $c['dostupna'] ) ) {
			$saved[ $i ]['dostupna'] = isset( $defs[ $id ] ) ? (int) $defs[ $id ]['dostupna'] : 0;
		}
	}
	foreach ( $defs as $id => $c ) {
		if ( ! in_array( $id, $ids, true ) ) {
			$saved[] = $c;
		}
	}
	update_option( 'bumbaru_colors', array_values( $saved ) );
}

/** Boje na stanju (izbor boja na sajtu). Ako nijedna nije označena, sve. */
function bumbaru_colors_in_stock() {
	$all = bumbaru_colors();
	$in  = array_values( array_filter( $all, function ( $c ) {
		return ! empty( $c['dostupna'] );
	} ) );
	return $in ? $in : $all;
}

/** Podrazumevana boja: tražena ako je na stanju, inače prva na stanju (kao pick() na Astro sajtu). */
function bumbaru_pick_color( $id, $fallback = 0 ) {
	$in = bumbaru_colors_in_stock();
	foreach ( $in as $c ) {
		if ( $c['id'] === $id ) {
			return $id;
		}
	}
	return isset( $in[ $fallback ] ) ? $in[ $fallback ]['id'] : $in[0]['id'];
}

/** Svetla boja (za tamniju ivicu kružića). */
function bumbaru_is_light( $hex ) {
	$h = ltrim( (string) $hex, '#' );
	if ( 6 !== strlen( $h ) ) {
		return false;
	}
	return 0.299 * hexdec( substr( $h, 0, 2 ) ) + 0.587 * hexdec( substr( $h, 2, 2 ) ) + 0.114 * hexdec( substr( $h, 4, 2 ) ) > 186;
}

/**
 * Filamenti: cena po kilogramu (din). Ovo su primeri — upišite svoje cene.
 */
function bumbaru_default_filaments() {
	return array(
		array( 'id' => 'pla-bela', 'name' => 'PLA bela', 'material' => 'PLA', 'color' => 'bela', 'price_kg' => 2500 ),
		array( 'id' => 'pla-crvena', 'name' => 'PLA crvena', 'material' => 'PLA', 'color' => 'crvena', 'price_kg' => 2500 ),
		array( 'id' => 'pla-zelena', 'name' => 'PLA zelena', 'material' => 'PLA', 'color' => 'zelena', 'price_kg' => 2500 ),
		array( 'id' => 'pla-zlatna', 'name' => 'PLA zlatna (silk)', 'material' => 'PLA', 'color' => 'zlatna', 'price_kg' => 2900 ),
		array( 'id' => 'pla-braon', 'name' => 'PLA braon', 'material' => 'PLA', 'color' => 'braon', 'price_kg' => 2500 ),
		array( 'id' => 'pla-tirkizna', 'name' => 'PLA tirkizna', 'material' => 'PLA', 'color' => 'tirkizna', 'price_kg' => 2500 ),
		array( 'id' => 'petg-crna', 'name' => 'PETG crna', 'material' => 'PETG', 'color' => 'crna', 'price_kg' => 2800 ),
		array( 'id' => 'asa-crna', 'name' => 'ASA crna', 'material' => 'ASA', 'color' => 'crna', 'price_kg' => 3600 ),
	);
}

/** Troškovi štampe. Primeri — upišite svoje. */
function bumbaru_default_costs() {
	return array(
		'kwh'        => 12,    // din za 1 kWh struje
		'power_w'    => 150,   // prosečna potrošnja štampača u W
		'machine_h'  => 30,    // amortizacija i održavanje štampača, din po satu štampe
		'labor_h'    => 600,   // vaš rad (dorada, pakovanje), din po satu
		'waste_pct'  => 10,    // otpad i neuspele štampe, % na materijal
		'margin_pct' => 80,    // zarada, % na trošak
		'vat_pct'    => 0,     // PDV, % (0 dok niste u sistemu PDV-a)
		'rounding'   => 'end90', // zaokruživanje cene: none, 10, 50, 100, end90
	);
}

/** Dodaci koji idu uz proizvod (karika, svećica, kutija…). Primeri cena. */
function bumbaru_default_extras() {
	return array(
		array( 'id' => 'karika', 'name' => 'Metalna karika za ključeve', 'price' => 25 ),
		array( 'id' => 'led', 'name' => 'LED svećica', 'price' => 60 ),
		array( 'id' => 'kutija', 'name' => 'Poklon kutija sa podlogom i karticom', 'price' => 90 ),
		array( 'id' => 'kesica', 'name' => 'Kesica i nalepnica', 'price' => 15 ),
		array( 'id' => 'srafovi', 'name' => 'Šrafovi i tiplovi', 'price' => 30 ),
		array( 'id' => 'koverta', 'name' => 'Koverta', 'price' => 20 ),
	);
}

function bumbaru_install_defaults() {
	if ( false === get_option( 'bumbaru_colors', false ) ) {
		update_option( 'bumbaru_colors', bumbaru_default_colors() );
	}
	if ( false === get_option( 'bumbaru_filaments', false ) ) {
		update_option( 'bumbaru_filaments', bumbaru_default_filaments() );
	}
	if ( false === get_option( 'bumbaru_costs', false ) ) {
		update_option( 'bumbaru_costs', bumbaru_default_costs() );
	}
	if ( false === get_option( 'bumbaru_extras', false ) ) {
		update_option( 'bumbaru_extras', bumbaru_default_extras() );
	}
}

function bumbaru_colors() {
	$c = get_option( 'bumbaru_colors' );
	return ( is_array( $c ) && $c ) ? array_values( $c ) : bumbaru_default_colors();
}

function bumbaru_filaments() {
	$f = get_option( 'bumbaru_filaments' );
	return is_array( $f ) ? array_values( $f ) : bumbaru_default_filaments();
}

function bumbaru_costs() {
	$c = get_option( 'bumbaru_costs' );
	return wp_parse_args( is_array( $c ) ? $c : array(), bumbaru_default_costs() );
}

function bumbaru_extras() {
	$e = get_option( 'bumbaru_extras' );
	return is_array( $e ) ? array_values( $e ) : bumbaru_default_extras();
}

/** Boja po oznaci (bela, crvena…); ako je nema, prva boja. */
function bumbaru_color( $id ) {
	$all = bumbaru_colors();
	foreach ( $all as $c ) {
		if ( $c['id'] === $id ) {
			return $c;
		}
	}
	return $all[0];
}

/** Boja po nazivu („Crvena”, „Tirkizna”), za kružić uz opcije proizvoda. */
function bumbaru_color_by_label( $label ) {
	$l = mb_strtolower( trim( (string) $label ), 'UTF-8' );
	foreach ( bumbaru_colors() as $c ) {
		if ( mb_strtolower( $c['label'], 'UTF-8' ) === $l ) {
			return $c;
		}
	}
	return null;
}

/** Tamnija nijansa boje (za ivicu kad nije upisana). */
function bumbaru_darken( $hex, $amount = 0.32 ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( ! preg_match( '/^[0-9a-f]{6}$/i', $hex ) ) {
		return '#000000';
	}
	$out = '#';
	for ( $i = 0; $i < 3; $i++ ) {
		$v    = hexdec( substr( $hex, $i * 2, 2 ) );
		$out .= str_pad( dechex( (int) round( $v * ( 1 - $amount ) ) ), 2, '0', STR_PAD_LEFT );
	}
	return strtoupper( $out );
}

/** Mešavina dve boje (t = 0 → a, t = 1 → b). */
function bumbaru_mix( $a, $b, $t = 0.5 ) {
	$a   = ltrim( $a, '#' );
	$b   = ltrim( $b, '#' );
	$out = '#';
	for ( $i = 0; $i < 3; $i++ ) {
		$va   = hexdec( substr( $a, $i * 2, 2 ) );
		$vb   = hexdec( substr( $b, $i * 2, 2 ) );
		$out .= str_pad( dechex( (int) round( $va + ( $vb - $va ) * $t ) ), 2, '0', STR_PAD_LEFT );
	}
	return strtoupper( $out );
}

function bumbaru_sanitize_hex( $v, $fallback = '#000000' ) {
	$v = trim( (string) $v );
	if ( '' !== $v && '#' !== $v[0] ) {
		$v = '#' . $v;
	}
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $v ) ? strtoupper( $v ) : $fallback;
}
