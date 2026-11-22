<?php
/**
 * Kalkulator cene 3D štampe.
 *
 * Trošak = materijal (grami × cena filamenta + otpad) + struja + amortizacija štampača + rad + dodaci + ostalo.
 * Cena  = trošak × (1 + zarada %) × (1 + PDV %), zaokruženo (npr. na …90 din).
 * Isti obračun radi i u pregledaču (assets/admin.js), da se cena vidi dok kucate.
 */

defined( 'ABSPATH' ) || exit;

/** Podrazumevani unos za jedan proizvod. */
function bumbaru_calc_defaults() {
	return array(
		'filaments' => array(), // [ [ 'id' => 'pla-bela', 'g' => 25 ], … ]
		'hours'     => 0,
		'minutes'   => 0,
		'labor_min' => 0,
		'extras'    => array(), // [ [ 'id' => 'karika', 'qty' => 1 ], … ]
		'other'     => 0,
		'margin'    => '',  // prazno = zarada iz podešavanja
		'pieces'    => 1,   // koliko komada izlazi iz jedne štampe (trošak štampe se deli)
	);
}

function bumbaru_round_price( $v, $mode ) {
	$v = max( 0, (float) $v );
	switch ( $mode ) {
		case '10':
			return ceil( $v / 10 ) * 10;
		case '50':
			return ceil( $v / 50 ) * 50;
		case '100':
			return ceil( $v / 100 ) * 100;
		case 'end90':
			// 344 → 390, 1520 → 1590, 1590 → 1590
			return $v <= 90 ? 90 : ceil( ( $v + 10 ) / 100 ) * 100 - 10;
		default:
			return round( $v );
	}
}

/**
 * Obračun. Vraća stavke troška, trošak, predlog cene i zaradu po komadu.
 */
function bumbaru_calc( $in, $costs = null ) {
	$in    = wp_parse_args( is_array( $in ) ? $in : array(), bumbaru_calc_defaults() );
	$costs = $costs ? $costs : bumbaru_costs();
	$fil   = array();
	foreach ( bumbaru_filaments() as $f ) {
		$fil[ $f['id'] ] = $f;
	}
	$ext = array();
	foreach ( bumbaru_extras() as $e ) {
		$ext[ $e['id'] ] = $e;
	}
	$pieces = max( 1, (int) $in['pieces'] );

	$grams    = 0;
	$material = 0;
	foreach ( (array) $in['filaments'] as $row ) {
		$g = max( 0, (float) ( isset( $row['g'] ) ? $row['g'] : 0 ) );
		if ( ! $g || empty( $row['id'] ) || ! isset( $fil[ $row['id'] ] ) ) {
			continue;
		}
		$grams    += $g;
		$material += $g / 1000 * (float) $fil[ $row['id'] ]['price_kg'];
	}
	$material *= 1 + (float) $costs['waste_pct'] / 100;

	$hours       = max( 0, (float) $in['hours'] + (float) $in['minutes'] / 60 );
	$electricity = $hours * (float) $costs['power_w'] / 1000 * (float) $costs['kwh'];
	$machine     = $hours * (float) $costs['machine_h'];

	// Štampa (materijal, struja, štampač) se deli na broj komada iz jedne štampe.
	$material    /= $pieces;
	$electricity /= $pieces;
	$machine     /= $pieces;
	$grams       /= $pieces;
	$hours       /= $pieces;

	$labor  = max( 0, (float) $in['labor_min'] ) / 60 * (float) $costs['labor_h'];
	$extras = 0;
	foreach ( (array) $in['extras'] as $row ) {
		if ( empty( $row['id'] ) || ! isset( $ext[ $row['id'] ] ) ) {
			continue;
		}
		$extras += max( 0, (float) ( isset( $row['qty'] ) ? $row['qty'] : 1 ) ) * (float) $ext[ $row['id'] ]['price'];
	}
	$other  = max( 0, (float) $in['other'] );
	$cost   = $material + $electricity + $machine + $labor + $extras + $other;
	$margin = '' === $in['margin'] || null === $in['margin'] ? (float) $costs['margin_pct'] : (float) $in['margin'];
	$raw    = $cost * ( 1 + $margin / 100 ) * ( 1 + (float) $costs['vat_pct'] / 100 );
	$price  = $cost > 0 ? bumbaru_round_price( $raw, $costs['rounding'] ) : 0;
	$net    = $price / ( 1 + (float) $costs['vat_pct'] / 100 );

	return array(
		'grams'       => $grams,
		'hours'       => $hours,
		'material'    => $material,
		'electricity' => $electricity,
		'machine'     => $machine,
		'labor'       => $labor,
		'extras'      => $extras,
		'other'       => $other,
		'cost'        => $cost,
		'margin'      => $margin,
		'raw'         => $raw,
		'price'       => $price,
		'profit'      => $net - $cost,
	);
}

/** Da li proizvod ima upisan obračun (bar jedan filament sa gramima ili vreme štampe). */
function bumbaru_calc_has_data( $calc ) {
	if ( ! is_array( $calc ) ) {
		return false;
	}
	foreach ( (array) ( isset( $calc['filaments'] ) ? $calc['filaments'] : array() ) as $r ) {
		if ( ! empty( $r['g'] ) ) {
			return true;
		}
	}
	return ! empty( $calc['hours'] ) || ! empty( $calc['minutes'] );
}

/** Očisti unos iz obrasca. */
function bumbaru_calc_sanitize( $in ) {
	$in  = is_array( $in ) ? $in : array();
	$out = bumbaru_calc_defaults();
	foreach ( (array) ( isset( $in['filaments'] ) ? $in['filaments'] : array() ) as $r ) {
		$id = isset( $r['id'] ) ? sanitize_key( $r['id'] ) : '';
		$g  = isset( $r['g'] ) ? (float) str_replace( ',', '.', $r['g'] ) : 0;
		if ( $id && $g > 0 ) {
			$out['filaments'][] = array( 'id' => $id, 'g' => $g );
		}
	}
	foreach ( (array) ( isset( $in['extras'] ) ? $in['extras'] : array() ) as $r ) {
		$id  = isset( $r['id'] ) ? sanitize_key( $r['id'] ) : '';
		$qty = isset( $r['qty'] ) ? (float) str_replace( ',', '.', $r['qty'] ) : 0;
		if ( $id && $qty > 0 ) {
			$out['extras'][] = array( 'id' => $id, 'qty' => $qty );
		}
	}
	foreach ( array( 'hours', 'minutes', 'labor_min', 'other' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) ? max( 0, (float) str_replace( ',', '.', $in[ $k ] ) ) : 0;
	}
	$out['pieces'] = isset( $in['pieces'] ) ? max( 1, (int) $in['pieces'] ) : 1;
	$out['margin'] = isset( $in['margin'] ) && '' !== trim( (string) $in['margin'] ) ? (float) str_replace( ',', '.', $in['margin'] ) : '';
	return $out;
}
