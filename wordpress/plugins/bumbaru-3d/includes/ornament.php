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

/** Širine slova fonta Pacifico (1/1000 em), da ime uvek ispuni obruč. */
function bumbaru_glyph_widths() {
	static $adv = null;
	if ( null !== $adv ) {
		return $adv;
	}
	$ch  = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyzČĆŠŽĐčćšžđАБВГДЂЕЖЗИЈКЛЉМНЊОПРСТЋУФХЦЧЏШабвгдђежзијклљмнњопрстћуфхцчџш -'.";
	$w   = array( 786, 827, 686, 836, 686, 712, 811, 873, 495, 743, 937, 673, 1232, 884, 816, 805, 816, 880, 693, 697, 890, 763, 1217, 712, 851, 721, 470, 473, 368, 515, 393, 371, 479, 502, 246, 254, 525, 326, 744, 496, 451, 481, 500, 455, 427, 393, 462, 449, 700, 529, 479, 445, 686, 686, 693, 721, 821, 368, 368, 427, 445, 470, 786, 917, 827, 635, 964, 974, 686, 1234, 725, 890, 743, 937, 861, 1158, 1133, 873, 1182, 816, 892, 805, 686, 697, 979, 824, 977, 712, 890, 773, 890, 1272, 470, 473, 473, 380, 479, 514, 393, 717, 435, 462, 254, 465, 549, 794, 734, 475, 751, 451, 496, 481, 368, 744, 502, 479, 721, 529, 501, 555, 600, 693, 265, 403, 290, 257 );
	$chs = preg_split( '//u', $ch, -1, PREG_SPLIT_NO_EMPTY );
	$adv = array();
	foreach ( $chs as $i => $c ) {
		$adv[ $c ] = $w[ $i ];
	}
	return $adv;
}

/**
 * Veličina i položaj imena na obruču (isto kao fitName u assets/ornament.js).
 * Ime ide od jedne do druge strane obruča i krajevima ulazi u njega; kraća imena dobiju veći font
 * i blago razvučena slova (sx). Šinice ostaju samo za imena od 1–2 slova.
 */
function bumbaru_fit_name( $raw, $fallback = 'Mila' ) {
	$name = mb_substr( trim( (string) $raw ), 0, 12, 'UTF-8' );
	if ( '' === $name ) {
		$name = $fallback;
	}
	$adv = bumbaru_glyph_widths();
	$sum = 0;
	foreach ( preg_split( '//u', $name, -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
		$sum += isset( $adv[ $c ] ) ? $adv[ $c ] : 520;
	}
	$reach = 97;
	$em    = $sum / 1000 + 0.1;
	$cy    = 182;
	$ri    = 94;
	$yc    = $cy + 6;
	$fs    = max( 22, min( 70, 2 * $reach / $em ) );
	$sx    = max( 1, min( 1.6, 2 * $reach / ( $em * $fs ) ) );
	$y     = $yc + 0.23 * $fs;
	$w     = $em * $fs * $sx;
	$dx    = 0.07 * $fs * $sx;
	$r     = function ( $v ) {
		return number_format( $v, 1, '.', '' );
	};
	$r3    = function ( $v ) {
		return (string) ( round( $v * 1000 ) / 1000 );
	};
	$rails = '';
	if ( $reach - $w / 2 > 3 ) {
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
	);
}

/**
 * Jedan ukras u lokalnim koordinatama (kutija 240 × 290, kukica na y = motiv.hy).
 * $o: motif, color, nameColor ('ista' ili oznaka boje), name, fallback, key.
 */
function bumbaru_ornament_g( $o ) {
	$o  = wp_parse_args( $o, array( 'motif' => 'zvezde', 'color' => 'bela', 'nameColor' => 'ista', 'name' => '', 'fallback' => 'Mila', 'key' => '' ) );
	$m  = bumbaru_motiv( $o['motif'] );
	$b  = bumbaru_color( $o['color'] );
	$n  = ( ! $o['nameColor'] || 'ista' === $o['nameColor'] ) ? $b : bumbaru_color( $o['nameColor'] );
	$f  = bumbaru_fit_name( $o['name'], $o['fallback'] );
	$nl = function ( $cls, $col, $tf = '' ) use ( $f ) {
		return '<g class="' . $cls . '"' . $tf . ' fill="' . esc_attr( $col ) . '" stroke="' . esc_attr( $col ) . '">'
			. '<path class="orn-rail" d="' . esc_attr( $f['rails'] ) . '" fill="none" stroke-width="' . $f['railW'] . '" stroke-linecap="round"></path>'
			. '<text class="orn-name" x="120" y="' . $f['y'] . '" transform="' . esc_attr( $f['tf'] ) . '" text-anchor="middle" font-family="Pacifico, cursive" font-size="' . $f['fs'] . '" stroke-width="1.2" stroke-linejoin="round">' . esc_html( $f['name'] ) . '</text></g>';
	};
	return '<g class="orn"' . ( $o['key'] ? ' data-orn="' . esc_attr( $o['key'] ) . '"' : '' ) . ' data-hy="' . (int) $m['hy'] . '">'
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
			. bumbaru_ornament_g( array( 'motif' => $m['id'], 'color' => $raw['boja'], 'nameColor' => $raw['boja_imena'], 'name' => $raw['ime'] ) ) . '</g></svg>';
	} elseif ( 'set' === $raw['type'] && ! empty( $raw['imena'] ) ) {
		foreach ( array_values( $raw['imena'] ) as $i => $ime ) {
			if ( $max && $i >= $max ) {
				break;
			}
			$color = 'sareno' === $raw['boja'] && ! empty( $raw['sareno'][ $i ] ) ? $raw['sareno'][ $i ] : $raw['boja'];
			$svg  .= '<svg viewBox="0 0 240 300" role="img" aria-label="' . esc_attr( $ime ) . '"><g filter="url(#orn-sh)">'
				. bumbaru_ornament_g( array( 'motif' => $raw['motiv'], 'color' => $color, 'nameColor' => $raw['boja_imena'], 'name' => $ime ) ) . '</g></svg>';
		}
	} elseif ( 'jelka' === $raw['type'] ) {
		$svg = bumbaru_tree_svg( isset( $raw['tree'] ) ? $raw['tree'] : 'mini', isset( $raw['zvezda'] ) ? $raw['zvezda'] : 'zlatna', isset( $raw['tree_color'] ) ? $raw['tree_color'] : '' );
	}
	return $svg;
}
