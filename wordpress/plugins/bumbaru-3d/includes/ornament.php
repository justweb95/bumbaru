<?php
/**
 * Ukras sa imenom: motivi, raspored imena preko obruča i crtež ukrasa (SVG).
 * Isti kod kao na Astro sajtu (lib/ornament.ts i lib/ornament-svg.ts), prenet u PHP.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_motivi() {
	return array(
		array( 'id' => 'zvezde', 'label' => 'Zvezde i jelke', 'hy' => 64 ),
		array( 'id' => 'planine', 'label' => 'Planine i šuma', 'hy' => 64 ),
		array( 'id' => 'masna', 'label' => 'Mašna i cvet', 'hy' => 71 ),
		array( 'id' => 'grancica', 'label' => 'Grančica', 'hy' => 64 ),
		// Dizajni sa MakerWorld-a (komercijalna licenca dogovorena sa autorima), prevedeni u naš crtež:
		// Mr.Fusible (Customizable Name Ornament), purocrypto (Esferas personalizables),
		// Galai (Bola de navidad personalizable), ArmenioMarques (Christmas Baubles Ball).
		array( 'id' => 'pahulje', 'label' => 'Pahuljice', 'hy' => 53, 'ime' => array( 'ri' => 92 ) ),
		array( 'id' => 'pahulje-masna', 'label' => 'Pahuljice i mašna', 'hy' => 69, 'ime' => array( 'ri' => 92 ) ),
		array( 'id' => 'kapa', 'label' => 'Kapa Deda Mraza', 'hy' => 55, 'ime' => array( 'ri' => 92.5 ) ),
		array( 'id' => 'pahulja', 'label' => 'Velika pahulja', 'hy' => 72, 'ime' => array( 'traka' => 1, 'w' => 80, 'y' => 189, 'fs' => 46 ) ),
		// Okretni (po ideji ArmenioMarques, Christmas Baubles Ball): tri obruča se posle štampe okreću oko osovine,
		// pahuljica sa imenom u sredini. Obruči, rupe i osovina se prave u generatoru (okrBuild), debljina 4 mm.
		array( 'id' => 'okretni', 'label' => 'Okretna pahulja', 'hy' => 69, 'okretni' => true, 'ime' => array( 'traka' => 1, 'w' => 56, 'y' => 189, 'fs' => 34 ) ),
	);
}

function bumbaru_motiv( $id ) {
	foreach ( bumbaru_motivi() as $m ) {
		if ( $m['id'] === $id ) {
			return $m;
		}
	}
	$all = bumbaru_motivi();
	return $all[0];
}

/** SVG definicije motiva (<g id="orn-…">), senka i maska. */
function bumbaru_ornament_defs() {
	static $defs = null;
	if ( null === $defs ) {
		$defs = (string) file_get_contents( BUMBARU3D_DIR . 'data/ornament-defs.svg' );
	}
	return $defs;
}

/**
 * Fontovi za ime na ukrasu (SIL OFL). sw = debljina linije oko slova (Great Vibes je tanak, pa dobije deblju),
 * cyr = font ima ćirilicu (ako nema, ime ćirilicom ide u Pacifico). ttf = fajl za generator (assets/gen/).
 */
function bumbaru_fontovi() {
	return apply_filters( 'bumbaru_fontovi', array(
		array( 'id' => 'pacifico', 'label' => 'Okrugla', 'css' => 'Pacifico, cursive', 'ttf' => 'Pacifico-Regular.ttf', 'cyr' => true, 'sw' => 1.2 ),
		array( 'id' => 'lobster', 'label' => 'Debela', 'css' => "'Lobster', cursive", 'ttf' => 'Lobster-Regular.ttf', 'cyr' => true, 'sw' => 1.2 ),
		array( 'id' => 'great-vibes', 'label' => 'Elegantna', 'css' => "'Great Vibes', cursive", 'ttf' => 'GreatVibes-Regular.ttf', 'cyr' => true, 'sw' => 2.6 ),
		array( 'id' => 'kaushan', 'label' => 'Četkica', 'css' => "'Kaushan Script', cursive", 'ttf' => 'KaushanScript-Regular.ttf', 'cyr' => false, 'sw' => 1.2 ),
	) );
}

function bumbaru_font( $id ) {
	$all = bumbaru_fontovi();
	foreach ( $all as $f ) {
		if ( $f['id'] === $id ) {
			return $f;
		}
	}
	return $all[0];
}

/** Font kojim se stvarno piše ime: font bez ćirilice + ime ćirilicom = Pacifico (isto u ornament.js). */
function bumbaru_name_font( $id, $name ) {
	$f = bumbaru_font( $id );
	if ( empty( $f['cyr'] ) && preg_match( '/[\x{0400}-\x{04FF}]/u', (string) $name ) ) {
		return bumbaru_font( 'pacifico' );
	}
	return $f;
}

/** Mere fontova (data/font-widths.json): širine slova (1/1000 em), ik/yk/dxk u odnosu na Pacifico. */
function bumbaru_font_metrics() {
	static $m = null;
	if ( null === $m ) {
		$m = json_decode( (string) file_get_contents( BUMBARU3D_DIR . 'data/font-widths.json' ), true );
	}
	return $m;
}

/** Širine slova jednog fonta, da ime uvek ispuni obruč. */
function bumbaru_glyph_widths( $font = 'pacifico' ) {
	static $cache = array();
	if ( isset( $cache[ $font ] ) ) {
		return $cache[ $font ];
	}
	$m   = bumbaru_font_metrics();
	$f   = isset( $m['fonts'][ $font ] ) ? $m['fonts'][ $font ] : $m['fonts']['pacifico'];
	$chs = preg_split( '//u', $m['chars'], -1, PREG_SPLIT_NO_EMPTY );
	$adv = array();
	foreach ( $chs as $i => $c ) {
		if ( null !== $f['w'][ $i ] ) {
			$adv[ $c ] = $f['w'][ $i ];
		}
	}
	return $cache[ $font ] = $adv;
}

/** Početak i kraj crteža slova (1/1000 em od ivica širine slova): [levo, desno]. */
function bumbaru_glyph_ink( $font = 'pacifico' ) {
	static $cache = array();
	if ( isset( $cache[ $font ] ) ) {
		return $cache[ $font ];
	}
	$m   = bumbaru_font_metrics();
	$f   = isset( $m['fonts'][ $font ] ) ? $m['fonts'][ $font ] : $m['fonts']['pacifico'];
	$chs = preg_split( '//u', $m['chars'], -1, PREG_SPLIT_NO_EMPTY );
	$ink = array();
	foreach ( $chs as $i => $c ) {
		if ( isset( $f['l'] ) && null !== $f['w'][ $i ] ) {
			$ink[ $c ] = array( $f['l'][ $i ], $f['r'][ $i ] );
		}
	}
	return $cache[ $font ] = $ink;
}

/**
 * Veličina i položaj imena (isto kao fitName u assets/ornament.js).
 * Obruč: ime ide od jedne do druge strane obruča i krajevima ulazi u njega; kraća imena dobiju veći font
 * i blago razvučena slova (sx). Šinice ostaju samo za imena od 1–2 slova.
 * Traka/pločica ($m['ime']['traka']): ime leži na delu ukrasa (traka, pločica), pa se samo smesti u širinu w i visinu fs.
 */
function bumbaru_fit_name( $raw, $fallback = 'Mila', $m = null, $font = 'pacifico' ) {
	$cfg  = is_array( $m ) && ! empty( $m['ime'] ) ? $m['ime'] : array();
	$name = mb_substr( trim( (string) $raw ), 0, 12, 'UTF-8' );
	if ( '' === $name ) {
		$name = $fallback;
	}
	$fo  = bumbaru_name_font( $font, $name );
	$mt  = bumbaru_font_metrics();
	$fm  = isset( $mt['fonts'][ $fo['id'] ] ) ? $mt['fonts'][ $fo['id'] ] : $mt['fonts']['pacifico'];
	$adv   = bumbaru_glyph_widths( $fo['id'] );
	$ink   = bumbaru_glyph_ink( $fo['id'] );
	$chars = preg_split( '//u', $name, -1, PREG_SPLIT_NO_EMPTY );
	$sum   = 0;
	foreach ( $chars as $c ) {
		$sum += isset( $adv[ $c ] ) ? $adv[ $c ] : 520;
	}
	// Stvarni crtež: od početka crteža prvog slova do kraja crteža poslednjeg.
	$first = isset( $ink[ $chars[0] ] ) ? $ink[ $chars[0] ] : array( 0, 0 );
	$last  = isset( $ink[ end( $chars ) ] ) ? $ink[ end( $chars ) ] : array( 0, 0 );
	$inkem = max( 0.2, ( $sum - $first[0] - $last[1] ) / 1000 );
	$c0    = ( $first[0] - $last[1] ) / 2000;
	$sw    = (float) $fo['sw'];
	$traka = ! empty( $cfg['traka'] );
	$reach = $traka ? (float) $cfg['w'] : ( isset( $cfg['reach'] ) ? (float) $cfg['reach'] : 97 );
	$wt    = 2 * $reach - $sw;
	$cy    = 182;
	$ri    = isset( $cfg['ri'] ) ? (float) $cfg['ri'] : 94;
	$yc    = isset( $cfg['y'] ) ? (float) $cfg['y'] : $cy + 6;
	$fs    = $traka ? max( 16, min( (float) $cfg['fs'], $wt / $inkem ) ) : max( 22, min( 70, $wt / $inkem ) );
	$sx    = $traka ? 1 : max( 1, min( 1.6, $wt / ( $inkem * $fs ) ) );
	$y     = $yc + $fm['yk'] * $fs;
	$w     = $inkem * $fs * $sx + $sw;
	$dx    = $sx * $c0 * $fs;
	$r     = function ( $v ) {
		return number_format( $v, 1, '.', '' );
	};
	$r3    = function ( $v ) {
		return (string) ( round( $v * 1000 ) / 1000 );
	};
	$rails = '';
	if ( ! $traka && 2 * $reach - $w > 6 ) {
		$yr    = $y - 0.05 * $fs;
		$hr    = sqrt( $ri * $ri - ( $yr - $cy ) * ( $yr - $cy ) );
		$rails = 'M' . $r( 120 - $hr - 4 ) . ' ' . $r( $yr ) . 'L' . $r( 120 - $w / 2 + 0.18 * $fs ) . ' ' . $r( $yr )
			. 'M' . $r( 120 + $w / 2 - 0.22 * $fs ) . ' ' . $r( $yr ) . 'L' . $r( 120 + $hr + 4 ) . ' ' . $r( $yr );
	}
	return array(
		'name'  => $name,
		'fs'    => $r( $fs ),
		'y'     => $r( $y ),
		'rails' => $rails,
		'railW' => $r( max( 4.2, 0.085 * $fs ) ),
		'sx'    => $r3( $sx ),
		'dx'    => $r3( $dx ),
		'tf'    => 'matrix(' . $r3( $sx ) . ' 0 0 1 ' . $r3( 120 - 120 * $sx - $dx ) . ' 0)',
		'mode'  => $traka ? 'traka' : 'obruc',
		'reach' => $r( $reach ),
		'font'  => $fo['id'],
		'css'   => $fo['css'],
		'sw'    => (string) $fo['sw'],
	);
}

/**
 * Jedan ukras u lokalnim koordinatama (kutija 240 × 290, kukica na y = motiv.hy).
 * $o: motif, color, nameColor ('ista' ili oznaka boje), name, fallback, key.
 */
function bumbaru_ornament_g( $o ) {
	$o  = wp_parse_args( $o, array( 'motif' => 'zvezde', 'color' => 'bela', 'nameColor' => 'ista', 'name' => '', 'fallback' => 'Mila', 'key' => '', 'font' => 'pacifico' ) );
	$m  = bumbaru_motiv( $o['motif'] );
	$b  = bumbaru_color( $o['color'] );
	$n  = ( ! $o['nameColor'] || 'ista' === $o['nameColor'] ) ? $b : bumbaru_color( $o['nameColor'] );
	$f  = bumbaru_fit_name( $o['name'], $o['fallback'], $m, $o['font'] );
	$nl = function ( $cls, $col, $tf = '' ) use ( $f ) {
		return '<g class="' . $cls . '"' . $tf . ' clip-path="url(#orn-cn)" fill="' . esc_attr( $col ) . '" stroke="' . esc_attr( $col ) . '">'
			. '<path class="orn-rail" d="' . esc_attr( $f['rails'] ) . '" fill="none" stroke-width="' . $f['railW'] . '" stroke-linecap="round"></path>'
			. '<text class="orn-name" x="120" y="' . $f['y'] . '" transform="' . esc_attr( $f['tf'] ) . '" text-anchor="middle" font-family="' . esc_attr( $f['css'] ) . '" font-size="' . $f['fs'] . '" stroke-width="' . $f['sw'] . '" stroke-linejoin="round">' . esc_html( $f['name'] ) . '</text></g>';
	};
	return '<g class="orn"' . ( $o['key'] ? ' data-orn="' . esc_attr( $o['key'] ) . '"' : '' ) . ' data-hy="' . (int) $m['hy'] . '" data-motif="' . esc_attr( $m['id'] ) . '">'
		. '<use class="orn-edge" href="#orn-' . esc_attr( $m['id'] ) . '" transform="translate(1.4 3.2)" fill="' . esc_attr( $b['edge'] ) . '" stroke="' . esc_attr( $b['edge'] ) . '"></use>'
		. '<use class="orn-face" href="#orn-' . esc_attr( $m['id'] ) . '" fill="' . esc_attr( $b['face'] ) . '" stroke="' . esc_attr( $b['face'] ) . '"></use>'
		. $nl( 'orn-ne', $n['edge'], ' transform="translate(1.1 2.6)"' )
		. $nl( 'orn-nf', $n['face'] )
		. '</g>';
}

/**
 * Pregled onoga što je kupac izabrao (ukras sa imenom, set, jelka sa zvezdicom), kao SVG.
 * Koristi se u porudžbini u adminu i u korpi. $max = najviše ukrasa iz seta.
 */
function bumbaru_preview_svg( $raw, $max = 0 ) {
	if ( ! is_array( $raw ) || empty( $raw['type'] ) ) {
		return '';
	}
	$svg = '';
	if ( 'ukras' === $raw['type'] && isset( $raw['ime'] ) ) {
		$m   = bumbaru_motiv( isset( $raw['motiv'] ) ? $raw['motiv'] : 'zvezde' );
		$svg = '<svg viewBox="0 0 240 300" role="img" aria-label="Pregled ukrasa"><g transform="translate(0 ' . ( 6 - $m['hy'] + 64 ) . ')" filter="url(#orn-sh)">'
			. bumbaru_ornament_g( array( 'motif' => $m['id'], 'color' => $raw['boja'], 'nameColor' => $raw['boja_imena'], 'name' => $raw['ime'], 'font' => isset( $raw['font'] ) ? $raw['font'] : 'pacifico' ) ) . '</g></svg>';
	} elseif ( 'set' === $raw['type'] && ! empty( $raw['imena'] ) ) {
		foreach ( array_values( $raw['imena'] ) as $i => $ime ) {
			if ( $max && $i >= $max ) {
				break;
			}
			$color = 'sareno' === $raw['boja'] && ! empty( $raw['sareno'][ $i ] ) ? $raw['sareno'][ $i ] : $raw['boja'];
			$svg  .= '<svg viewBox="0 0 240 300" role="img" aria-label="' . esc_attr( $ime ) . '"><g filter="url(#orn-sh)">'
				. bumbaru_ornament_g( array( 'motif' => $raw['motiv'], 'color' => $color, 'nameColor' => $raw['boja_imena'], 'name' => $ime, 'font' => isset( $raw['font'] ) ? $raw['font'] : 'pacifico' ) ) . '</g></svg>';
		}
	} elseif ( 'jelka' === $raw['type'] ) {
		$svg = bumbaru_tree_svg( isset( $raw['tree'] ) ? $raw['tree'] : 'mini', isset( $raw['zvezda'] ) ? $raw['zvezda'] : 'zlatna', isset( $raw['tree_color'] ) ? $raw['tree_color'] : '' );
	}
	return $svg;
}
