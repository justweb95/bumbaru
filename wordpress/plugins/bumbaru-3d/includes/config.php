<?php
/**
 * Podešavanje konfiguratora po proizvodu (čuva se u meta polju _bu_config).
 *
 * Tipovi:
 *  ''        – samo opcije i izbor boja
 *  ukras     – ukras za jelku sa imenom (ime, motiv, boja ukrasa, boja imena, pregled uživo)
 *  set       – porodični set ukrasa (4 ili 5 imena, motiv, boje, pregled uživo)
 *  privezak  – privezak sa imenom: stilovi slova, jedno ili više imena (svako ime je jedan privezak), boje slojeva
 *  ime       – privezak sa imenom (jedno ime, bez pregleda; stari tip)
 *  imena     – porodični privezak sa 2–4 imena (stari tip)
 *  jelka     – jelka sa zvezdicom (pregled uživo, menja se boja zvezdice)
 *  motivi    – kupac bira motive i broj komada svakog (npr. modlice), popust raste sa brojem komada
 *  boje      – kupac bira boje filamenta i broj komada svake (npr. klipovi), popust raste sa brojem komada
 *  sastav    – slavski set: kupac bira modlice za set uz medaljon sveca, pregled seta u krug
 *
 * Uz svaki tip mogu da idu: opcije (sa doplatom ili sopstvenom cenom), izbor boja, poziv na Messenger,
 * komplet „Uz ovo često uzimaju”, popusti za firme i crtež u izabranim bojama.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_config_types() {
	return array(
		''         => 'Bez konfiguratora (samo opcije i boje)',
		'ukras'    => 'Ukras sa imenom (ime, motiv, boje, pregled uživo)',
		'set'      => 'Porodični set ukrasa (4 ili 5 imena, pregled uživo)',
		'privezak' => 'Privezak sa imenom (stil slova, jedno ili više imena, pregled)',
		'motivi'   => 'Kupac bira motive i broj komada (popust za više komada)',
		'boje'     => 'Kupac bira boje i broj komada (popust za više komada)',
		'sastav'   => 'Slavski set (kupac bira modlice uz medaljon)',
		'jelka'    => 'Jelka sa zvezdicom (bira se boja zvezdice, pregled uživo)',
		'ime'      => 'Privezak sa imenom, jednostavno (jedno ime)',
		'imena'    => 'Porodični privezak, jednostavno (2–4 imena)',
	);
}

/** Podaci iz novog sajta: stilovi slova, siluete, crteži, slavski set (data/astro.json). */
function bumbaru_astro_data( $key = '' ) {
	static $d = null;
	if ( null === $d ) {
		$f = BUMBARU3D_DIR . 'data/astro.json';
		$d = file_exists( $f ) ? json_decode( (string) file_get_contents( $f ), true ) : array();
		$d = is_array( $d ) ? $d : array();
	}
	if ( '' === $key ) {
		return $d;
	}
	return isset( $d[ $key ] ) ? $d[ $key ] : array();
}

function bumbaru_kc_style( $id ) {
	$all = bumbaru_astro_data( 'STILOVI' );
	foreach ( $all as $s ) {
		if ( $s['id'] === $id ) {
			return $s;
		}
	}
	return $all ? $all[0] : null;
}

function bumbaru_silueta( $id ) {
	foreach ( bumbaru_astro_data( 'SILUETE' ) as $s ) {
		if ( $s['id'] === $id ) {
			return $s;
		}
	}
	return null;
}

function bumbaru_crtez( $id ) {
	foreach ( bumbaru_astro_data( 'CRTEZI' ) as $s ) {
		if ( $s['id'] === $id ) {
			return $s;
		}
	}
	return null;
}

/** Modlice za slavski set (slike su u pluginu: assets/set/). */
function bumbaru_set_modlice() {
	$out = array();
	foreach ( bumbaru_astro_data( 'SET_MODLICE' ) as $m ) {
		$m['slika'] = BUMBARU3D_URL . 'assets/set/' . basename( $m['slika'] );
		$out[]      = $m;
	}
	return $out;
}

function bumbaru_set_medaljon( $id ) {
	foreach ( bumbaru_astro_data( 'SET_MEDALJONI' ) as $m ) {
		if ( $m['id'] === $id ) {
			$m['slika'] = BUMBARU3D_URL . 'assets/set/' . basename( $m['slika'] );
			return $m;
		}
	}
	return null;
}

function bumbaru_set_medaljoni() {
	return bumbaru_astro_data( 'SET_MEDALJONI' );
}

/**
 * Opcije u tekstu, jedna po redu:  Naziv = Vrednost 1 | Vrednost 2 +150 | Vrednost 3
 *  „+150” / „-150” na kraju vrednosti: doplata ili umanjenje u dinarima.
 *  „[590]” na kraju vrednosti: sopstvena cena te vrednosti (npr. Veličina = Mali [590] | Veliki [1290]).
 *  „[300 / 350]”: cena 300 din, redovna (precrtana) cena 350 din.
 */
function bumbaru_parse_options( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || false === strpos( $line, '=' ) ) {
			continue;
		}
		list( $label, $vals ) = array_map( 'trim', explode( '=', $line, 2 ) );
		$values = array();
		foreach ( explode( '|', $vals ) as $v ) {
			$v   = trim( $v );
			$add = 0;
			$abs = 0;
			$old = 0;
			if ( preg_match( '/\s*\[\s*(\d+(?:[.,]\d+)?)\s*(?:\/\s*(\d+(?:[.,]\d+)?)\s*)?\]\s*$/u', $v, $m ) ) {
				$abs = (float) str_replace( ',', '.', $m[1] );
				$old = isset( $m[2] ) ? (float) str_replace( ',', '.', $m[2] ) : 0;
				$v   = trim( substr( $v, 0, -strlen( $m[0] ) ) );
			} elseif ( preg_match( '/\s\(?([+\-−])\s*(\d+(?:[.,]\d+)?)\s*(?:din)?\)?\s*$/u', ' ' . $v, $m ) ) {
				$add = (float) str_replace( ',', '.', $m[2] ) * ( '+' === $m[1] ? 1 : -1 );
				$v   = trim( substr( ' ' . $v, 0, -strlen( $m[0] ) ) );
			}
			if ( '' !== $v ) {
				$values[] = array( 'label' => $v, 'add' => $add, 'abs' => $abs, 'old' => $old > $abs ? $old : 0 );
			}
		}
		if ( '' !== $label && $values ) {
			$out[] = array( 'label' => $label, 'values' => $values );
		}
	}
	return $out;
}

/** Opcije nazad u tekst (za polje u administraciji). */
function bumbaru_options_to_text( $options ) {
	$lines = array();
	$num   = function ( $n ) {
		return floor( $n ) == $n ? (string) (int) $n : (string) $n;
	};
	foreach ( (array) $options as $o ) {
		$vals = array();
		foreach ( $o['values'] as $v ) {
			if ( ! empty( $v['abs'] ) ) {
				$vals[] = $v['label'] . ' [' . $num( $v['abs'] ) . ( ! empty( $v['old'] ) ? ' / ' . $num( $v['old'] ) : '' ) . ']';
				continue;
			}
			$a      = (float) $v['add'];
			$vals[] = $v['label'] . ( $a ? ( $a > 0 ? ' +' : ' -' ) . $num( abs( $a ) ) : '' );
		}
		$lines[] = $o['label'] . ' = ' . implode( ' | ', $vals );
	}
	return implode( "\n", $lines );
}

/** Popusti u tekstu: „4=17, 6=24, 8=30” (od koliko komada = procenat). */
function bumbaru_parse_tiers( $text ) {
	$out = array();
	if ( is_array( $text ) ) {
		foreach ( $text as $t ) {
			if ( isset( $t['od'], $t['procenat'] ) && (int) $t['od'] > 0 && (float) $t['procenat'] > 0 ) {
				$out[] = array( 'od' => (int) $t['od'], 'procenat' => (float) $t['procenat'] );
			}
		}
	} else {
		foreach ( preg_split( '/[,;\n]+/', (string) $text ) as $part ) {
			if ( preg_match( '/(\d+)\s*(?:kom)?\s*[=:]\s*(\d+(?:[.,]\d+)?)\s*%?/u', $part, $m ) ) {
				$out[] = array( 'od' => (int) $m[1], 'procenat' => (float) str_replace( ',', '.', $m[2] ) );
			}
		}
	}
	usort( $out, function ( $a, $b ) {
		return $a['od'] - $b['od'];
	} );
	return $out;
}

function bumbaru_tiers_to_text( $tiers ) {
	$o = array();
	foreach ( (array) $tiers as $t ) {
		$o[] = $t['od'] . '=' . ( floor( $t['procenat'] ) == $t['procenat'] ? (int) $t['procenat'] : $t['procenat'] );
	}
	return implode( ', ', $o );
}

/** Izbor boja: „Boja jelke = zelena” po redu (naziv polja = podrazumevana boja). */
function bumbaru_parse_color_choices( $in ) {
	$out = array();
	if ( is_array( $in ) ) {
		foreach ( $in as $i => $c ) {
			if ( ! empty( $c['label'] ) ) {
				$out[] = array( 'id' => isset( $c['id'] ) && $c['id'] ? sanitize_key( $c['id'] ) : 'b' . $i, 'label' => (string) $c['label'], 'def' => isset( $c['def'] ) ? sanitize_key( $c['def'] ) : 'bela' );
			}
		}
		return $out;
	}
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $in ) as $i => $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '=', $line, 2 ) );
		$def   = isset( $parts[1] ) ? sanitize_title( remove_accents( $parts[1] ) ) : 'bela';
		$out[] = array( 'id' => 0 === count( $out ) ? 'boja' : 'b' . count( $out ), 'label' => $parts[0], 'def' => $def );
	}
	return $out;
}

function bumbaru_color_choices_to_text( $list ) {
	$o = array();
	foreach ( (array) $list as $c ) {
		$o[] = $c['label'] . ' = ' . $c['def'];
	}
	return implode( "\n", $o );
}

/** Podešavanje konfiguratora proizvoda, sa podrazumevanim vrednostima. */
function bumbaru_config( $product ) {
	static $cache = array();
	$id = is_object( $product ) ? $product->get_id() : (int) $product;
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}
	$raw = get_post_meta( $id, '_bu_config', true );
	$raw = is_array( $raw ) ? $raw : array();
	$ids = wp_list_pluck( bumbaru_colors(), 'id' );

	$filter = function ( $list, $default ) use ( $ids ) {
		$list = array_values( array_intersect( (array) $list, $ids ) );
		if ( ! $list ) {
			$list = array_values( array_intersect( $default, $ids ) );
		}
		return $list ? $list : array( $ids[0] );
	};

	$type = isset( $raw['type'] ) && array_key_exists( $raw['type'], bumbaru_config_types() ) ? $raw['type'] : '';
	$tree = isset( $raw['tree'] ) && in_array( $raw['tree'], array( 'mini', 'spirala', 'ravna' ), true ) ? $raw['tree'] : 'mini';

	$c = array(
		'type'         => $type,
		'colors'       => $filter( isset( $raw['colors'] ) ? $raw['colors'] : array(), array( 'bela', 'crvena', 'zelena', 'zlatna' ) ),
		'name_color'   => isset( $raw['name_color'] ) ? (string) $raw['name_color'] : '',
		'set_extra'    => isset( $raw['set_extra'] ) ? max( 0, (float) $raw['set_extra'] ) : 390,
		'tree'         => $tree,
		'tree_color'   => isset( $raw['tree_color'] ) && in_array( $raw['tree_color'], $ids, true ) ? $raw['tree_color'] : '',
		'star_colors'  => $filter( isset( $raw['star_colors'] ) ? $raw['star_colors'] : array(), array( 'zlatna', 'crvena', 'bela', 'srebrna', 'braon' ) ),
		'star_default' => isset( $raw['star_default'] ) ? (string) $raw['star_default'] : 'zlatna',
		'options'      => bumbaru_parse_options( isset( $raw['options'] ) ? $raw['options'] : '' ),
		'options_text' => isset( $raw['options'] ) ? (string) $raw['options'] : '',
		'izbor_boja'   => bumbaru_parse_color_choices( isset( $raw['izbor_boja'] ) ? $raw['izbor_boja'] : array() ),
		'keychain'     => null,
		'motivi'       => null,
		'sastav'       => null,
		'poziv'        => null,
		'komplet'      => null,
		'firme'        => null,
		'crtez'        => isset( $raw['crtez'] ) && bumbaru_crtez( $raw['crtez'] ) ? $raw['crtez'] : '',
		// Popust za više komada istog proizvoda (svi komadi u korpi se sabiraju, npr. ukrasi sa različitim imenima).
		'kolicina'     => in_array( $type, array( 'motivi', 'boje', 'sastav' ), true ) ? array() : bumbaru_parse_tiers( isset( $raw['kolicina'] ) ? $raw['kolicina'] : ( 'ukras' === $type ? bumbaru_kolicina_default() : '' ) ),
		'kolicina_txt' => isset( $raw['kolicina'] ) ? (string) $raw['kolicina'] : ( 'ukras' === $type ? bumbaru_kolicina_default() : '' ),
	);
	if ( ! in_array( $c['star_default'], $c['star_colors'], true ) ) {
		$c['star_default'] = $c['star_colors'][0];
	}
	if ( ! $c['name_color'] ) {
		$c['name_color'] = 'ukras' === $type ? 'crvena' : 'ista';
	}
	// Privezak sa imenom
	if ( 'privezak' === $type ) {
		$k        = isset( $raw['keychain'] ) && is_array( $raw['keychain'] ) ? $raw['keychain'] : array();
		$all      = wp_list_pluck( bumbaru_astro_data( 'STILOVI' ), 'id' );
		$stilovi  = array_values( array_intersect( isset( $k['stilovi'] ) ? (array) $k['stilovi'] : array(), $all ) );
		$min      = max( 1, isset( $k['minImena'] ) ? (int) $k['minImena'] : 1 );
		$max      = max( $min, isset( $k['maxImena'] ) ? (int) $k['maxImena'] : 6 );
		$start    = min( $max, max( $min, isset( $k['startImena'] ) ? (int) $k['startImena'] : $min ) );
		$c['keychain'] = array(
			'stilovi'    => $stilovi ? $stilovi : array( 'pisana' ),
			'startImena' => $start,
			'minImena'   => $min,
			'maxImena'   => $max,
			'sareno'     => ! empty( $k['sareno'] ),
			'slike'      => isset( $k['slike'] ) && is_array( $k['slike'] ) ? array_map( 'intval', $k['slike'] ) : array(),
		);
	}
	// Izbor motiva ili boja sa popustom
	if ( in_array( $type, array( 'motivi', 'boje' ), true ) ) {
		$m     = isset( $raw['motivi'] ) && is_array( $raw['motivi'] ) ? $raw['motivi'] : array();
		$lista = array();
		if ( 'motivi' === $type ) {
			foreach ( isset( $m['lista'] ) ? (array) $m['lista'] : array() as $it ) {
				if ( empty( $it['naziv'] ) ) {
					continue;
				}
				$lista[] = array(
					'id'    => ! empty( $it['id'] ) ? sanitize_key( $it['id'] ) : sanitize_title( remove_accents( $it['naziv'] ) ),
					'naziv' => (string) $it['naziv'],
					'cena'  => isset( $it['cena'] ) ? max( 0, (float) $it['cena'] ) : 0,
					'cene'  => isset( $it['cene'] ) ? array_map( 'floatval', (array) $it['cene'] ) : array(),
					'img'   => isset( $it['img'] ) ? (int) $it['img'] : 0,
				);
			}
		}
		$c['motivi'] = array(
			'vrsta'   => 'boje' === $type ? 'boje' : '',
			'silueta' => isset( $m['silueta'] ) && bumbaru_silueta( $m['silueta'] ) ? $m['silueta'] : '',
			'popusti' => bumbaru_parse_tiers( isset( $m['popusti'] ) ? $m['popusti'] : array() ),
			'lista'   => $lista,
		);
	}
	if ( 'sastav' === $type ) {
		$s  = isset( $raw['sastav'] ) && is_array( $raw['sastav'] ) ? $raw['sastav'] : array();
		$md = isset( $s['medaljon'] ) && bumbaru_set_medaljon( $s['medaljon'] ) ? $s['medaljon'] : 'sv-nikola';
		$c['sastav'] = array( 'medaljon' => $md, 'broj' => max( 1, min( 16, isset( $s['broj'] ) ? (int) $s['broj'] : 8 ) ) );
	}
	if ( ! empty( $raw['poziv']['tekst'] ) && ! empty( $raw['poziv']['dugme'] ) ) {
		$c['poziv'] = array( 'tekst' => (string) $raw['poziv']['tekst'], 'dugme' => (string) $raw['poziv']['dugme'], 'poruka' => isset( $raw['poziv']['poruka'] ) ? (string) $raw['poziv']['poruka'] : '' );
	}
	if ( ! empty( $raw['komplet']['uz'] ) ) {
		$uz = array_values( array_filter( array_map( 'sanitize_title', (array) $raw['komplet']['uz'] ) ) );
		if ( $uz ) {
			$c['komplet'] = array(
				'uz'      => $uz,
				'popust'  => max( 0, min( 90, isset( $raw['komplet']['popust'] ) ? (float) $raw['komplet']['popust'] : 10 ) ),
				// Popust po broju proizvoda u kompletu (npr. 2=10, 3=15, 4=20): kupac sam bira šta ide u komplet.
				'popusti' => bumbaru_parse_tiers( isset( $raw['komplet']['popusti'] ) ? $raw['komplet']['popusti'] : '' ),
			);
		}
	}
	if ( ! empty( $raw['firme']['popusti'] ) ) {
		$t = bumbaru_parse_tiers( $raw['firme']['popusti'] );
		if ( $t ) {
			$c['firme'] = array( 'popusti' => $t, 'tekst' => isset( $raw['firme']['tekst'] ) ? (string) $raw['firme']['tekst'] : '' );
		}
	}
	$cache[ $id ] = $c;
	return $c;
}

/** Podrazumevani popust za više ukrasa sa imenom (menja se u proizvodu → Konfigurator). */
function bumbaru_kolicina_default() {
	return '2=5, 3=10, 5=15';
}

function bumbaru_has_live( $product ) {
	$c = bumbaru_config( $product );
	return in_array( $c['type'], array( 'ukras', 'set', 'jelka', 'privezak' ), true );
}

/** Kupac bira komade (motive ili boje) sa popustom po broju komada. */
function bumbaru_izbor_komada( $product ) {
	$c = bumbaru_config( $product );
	return 'boje' === $c['type'] || ( 'motivi' === $c['type'] && ! empty( $c['motivi']['lista'] ) );
}

/** „Šareno” za set: svaki ukras u drugoj boji (boje na stanju, prvo klasične boje ukrasa). */
function bumbaru_sareno( $colors = null ) {
	$in   = wp_list_pluck( bumbaru_colors_in_stock(), 'id' );
	$klas = array( 'bela', 'crvena', 'zelena', 'zlatna' );
	$red  = array_merge( array_values( array_intersect( $klas, $in ) ), array_values( array_diff( $in, $klas ) ) );
	if ( ! $red ) {
		$red = array( 'bela' );
	}
	$out = array();
	for ( $i = 0; $i < 5; $i++ ) {
		$out[] = $red[ $i % count( $red ) ];
	}
	return $out;
}

/** Indeks opcije „Veličina” / „Visina” sa vrednostima u cm (za pregled jelke), ili -1. */
function bumbaru_size_option_index( $config ) {
	foreach ( $config['options'] as $i => $o ) {
		if ( preg_match( '/veli[cč]in|visin/iu', $o['label'] ) ) {
			foreach ( $o['values'] as $v ) {
				if ( preg_match( '/\d+(?:[.,]\d+)?\s*cm/iu', $v['label'] ) ) {
					return $i;
				}
			}
		}
	}
	return -1;
}

/** Opcija sa sopstvenim cenama po vrednosti (npr. veličina), ili -1. */
function bumbaru_price_option_index( $config ) {
	foreach ( $config['options'] as $i => $o ) {
		foreach ( $o['values'] as $v ) {
			if ( ! empty( $v['abs'] ) ) {
				return $i;
			}
		}
	}
	return -1;
}
