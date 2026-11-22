<?php
/**
 * Pregled uživo za jelke sa zvezdicom: Mini jelka za sto, Spiralna jelka i Jelka za sklapanje.
 * Jelka je uvek iste boje, a klikom se menja boja zvezdice na vrhu.
 * Ako proizvod ima opciju „Veličina” (npr. 15 cm, 10 cm, 7 cm), jelka u pregledu se smanji
 * i pored nje piše visina.
 * Crtež je u istom stilu kao ukras sa imenom: površina + tamnija ivica + senka.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_tree_styles() {
	return array(
		'mini'    => array(
			'label'      => 'Mini jelka (slojevite grane, braon stablo)',
			'face'       => '#6E8B5E',
			'edge'       => '#4B6440',
			'trunk'      => '#6E4A38',
			'trunk_edge' => '#47301F',
		),
		'spirala' => array(
			'label'      => 'Spiralna jelka (kupa sa spiralom)',
			'face'       => '#465E45',
			'edge'       => '#2C3D2C',
			'trunk'      => '#465E45',
			'trunk_edge' => '#2C3D2C',
		),
		'ravna'   => array(
			'label'      => 'Jelka za sklapanje (ravna, sa šarama, dva dela)',
			'face'       => '#2F5B3B',
			'edge'       => '#1C3A25',
			'trunk'      => '#2F5B3B',
			'trunk_edge' => '#1C3A25',
		),
	);
}

function bumbaru_n( $v ) {
	$s = number_format( (float) $v, 1, '.', '' );
	$s = rtrim( rtrim( $s, '0' ), '.' );
	return '-0' === $s ? '0' : $s;
}

/** Zvezda sa pet krakova: obris i desne polovine krakova (za 3D „brušeni” izgled). */
function bumbaru_star_geo( $cx, $cy, $R, $r ) {
	$pt      = function ( $ang, $rad ) use ( $cx, $cy ) {
		$a = deg2rad( $ang );
		return array( $cx + $rad * cos( $a ), $cy + $rad * sin( $a ) );
	};
	$outline = '';
	$halves  = '';
	for ( $k = 0; $k < 5; $k++ ) {
		$o        = $pt( -90 + $k * 72, $R );
		$i        = $pt( -90 + 36 + $k * 72, $r );
		$outline .= ( 0 === $k ? 'M' : 'L' ) . bumbaru_n( $o[0] ) . ' ' . bumbaru_n( $o[1] ) . 'L' . bumbaru_n( $i[0] ) . ' ' . bumbaru_n( $i[1] );
		$halves  .= 'M' . bumbaru_n( $cx ) . ' ' . bumbaru_n( $cy ) . 'L' . bumbaru_n( $o[0] ) . ' ' . bumbaru_n( $o[1] ) . 'L' . bumbaru_n( $i[0] ) . ' ' . bumbaru_n( $i[1] ) . 'Z';
	}
	return array( 'outline' => $outline . 'Z', 'halves' => $halves );
}

/** Mala zvezda kao rupa (šara) u ravnoj jelki. */
function bumbaru_star_hole( $cx, $cy, $R ) {
	$g = bumbaru_star_geo( $cx, $cy, $R, $R * 0.45 );
	return $g['outline'];
}

function bumbaru_circle_path( $cx, $cy, $r ) {
	return 'M' . bumbaru_n( $cx - $r ) . ' ' . bumbaru_n( $cy ) . 'a' . bumbaru_n( $r ) . ' ' . bumbaru_n( $r ) . ' 0 1 0 ' . bumbaru_n( 2 * $r ) . ' 0a' . bumbaru_n( $r ) . ' ' . bumbaru_n( $r ) . ' 0 1 0 ' . bumbaru_n( -2 * $r ) . ' 0Z';
}

/** Jedan sloj grana mini jelke: vrh, bočne krivine i zaobljeni krajevi grana dole. */
function bumbaru_tier_path( $cx, $yt, $yb, $hw, $lobes ) {
	$h    = $yb - $yt;
	$y0   = $yb - 14;
	$d    = 'M' . bumbaru_n( $cx ) . ' ' . bumbaru_n( $yt )
		. 'C' . bumbaru_n( $cx - $hw * 0.22 ) . ' ' . bumbaru_n( $yt + $h * 0.30 ) . ' ' . bumbaru_n( $cx - $hw * 0.80 ) . ' ' . bumbaru_n( $y0 - $h * 0.30 ) . ' ' . bumbaru_n( $cx - $hw ) . ' ' . bumbaru_n( $y0 );
	$step = 2 * $hw / $lobes;
	for ( $i = 0; $i < $lobes; $i++ ) {
		$x0 = $cx - $hw + $i * $step;
		$x1 = $x0 + $step;
		$d .= 'C' . bumbaru_n( $x0 + $step * 0.04 ) . ' ' . bumbaru_n( $yb + 11 ) . ' ' . bumbaru_n( $x1 - $step * 0.04 ) . ' ' . bumbaru_n( $yb + 11 ) . ' ' . bumbaru_n( $x1 ) . ' ' . bumbaru_n( $y0 );
	}
	$d .= 'C' . bumbaru_n( $cx + $hw * 0.80 ) . ' ' . bumbaru_n( $y0 - $h * 0.30 ) . ' ' . bumbaru_n( $cx + $hw * 0.22 ) . ' ' . bumbaru_n( $yt + $h * 0.30 ) . ' ' . bumbaru_n( $cx ) . ' ' . bumbaru_n( $yt ) . 'Z';
	return $d;
}

/** Žlebovi na granama (kao na odštampanoj jelki). */
function bumbaru_tier_grooves( $cx, $yt, $yb, $hw, $lobes ) {
	$h      = $yb - $yt;
	$step   = 2 * $hw / $lobes;
	$crease = '';
	$fine   = '';
	for ( $i = 0; $i < $lobes; $i++ ) {
		$x0 = $cx - $hw + $i * $step;
		foreach ( array( 0.5, 0.25, 0.75 ) as $j => $f ) {
			$x     = $x0 + $step * $f;
			$fine .= 'M' . bumbaru_n( $cx + ( $x - $cx ) * 0.06 ) . ' ' . bumbaru_n( $yt + 8 )
				. 'Q' . bumbaru_n( $cx + ( $x - $cx ) * 0.78 ) . ' ' . bumbaru_n( $yt + $h * 0.52 ) . ' ' . bumbaru_n( $x ) . ' ' . bumbaru_n( $yb + ( 0 === $j ? 2 : -4 ) );
		}
		if ( $i > 0 ) {
			$crease .= 'M' . bumbaru_n( $cx + ( $x0 - $cx ) * 0.06 ) . ' ' . bumbaru_n( $yt + 6 )
				. 'Q' . bumbaru_n( $cx + ( $x0 - $cx ) * 0.80 ) . ' ' . bumbaru_n( $yt + $h * 0.5 ) . ' ' . bumbaru_n( $x0 ) . ' ' . bumbaru_n( $yb - 13 );
		}
	}
	return array( 'crease' => $crease, 'fine' => $fine );
}

/** Spiralna jelka: obris kupe i spirala sa prednje strane. */
function bumbaru_spiral_geo() {
	$top = 112;
	$bot = 530;
	$hw  = function ( $y ) use ( $top, $bot ) {
		return 104 * pow( max( 0, ( $y - $top ) / ( $bot - $top ) ), 1.08 );
	};
	$left  = array();
	$right = array();
	for ( $y = $top; $y <= $bot; $y += 6 ) {
		$left[]  = array( 300 - $hw( $y ), $y );
		$right[] = array( 300 + $hw( $y ), $y );
	}
	$d = 'M300 ' . $top;
	foreach ( $left as $p ) {
		$d .= 'L' . bumbaru_n( $p[0] ) . ' ' . bumbaru_n( $p[1] );
	}
	$d .= 'Q300 ' . ( $bot + 16 ) . ' ' . bumbaru_n( 300 + $hw( $bot ) ) . ' ' . $bot;
	foreach ( array_reverse( $right ) as $p ) {
		$d .= 'L' . bumbaru_n( $p[0] ) . ' ' . bumbaru_n( $p[1] );
	}
	$d .= 'Z';

	// Spirala: 6 krugova, gore gušće; vidi se samo prednja polovina.
	$turns = 6;
	$T     = $turns * 2 * M_PI;
	$ridge = '';
	$open  = false;
	for ( $t = 0.6; $t <= $T; $t += 0.05 ) {
		$y = $top + 18 + ( $bot - $top - 30 ) * pow( $t / $T, 1.12 );
		if ( $y > $bot - 40 ) {
			break;
		}
		$x   = 300 + $hw( $y ) * sin( $t );
		$vis = cos( $t ) > -0.04;
		if ( $vis ) {
			$ridge .= ( $open ? 'L' : 'M' ) . bumbaru_n( $x ) . ' ' . bumbaru_n( $y );
			$open   = true;
		} else {
			$open = false;
		}
	}
	// Rebra od vrha ka dnu.
	$ribs = '';
	for ( $k = 1; $k < 16; $k++ ) {
		$xb    = 300 - 104 + $k * 208 / 16;
		$ribs .= 'M300 ' . ( $top + 10 ) . 'L' . bumbaru_n( $xb ) . ' ' . ( $bot + 6 );
	}
	return array( 'd' => $d, 'ridge' => $ridge, 'ribs' => $ribs );
}

/**
 * Ceo pregled jelke (SVG 600 × 600).
 * $style: 'mini', 'spirala' ili 'ravna'; $star: oznaka boje zvezdice;
 * $tree_color: oznaka boje jelke iz palete (prazno = podrazumevana boja oblika);
 * $ruler: prikaži visinu (za proizvode sa veličinama).
 */
function bumbaru_tree_svg( $style, $star, $tree_color = '', $ruler = false ) {
	$styles = bumbaru_tree_styles();
	$style  = isset( $styles[ $style ] ) ? $style : 'mini';
	$t      = $styles[ $style ];
	if ( $tree_color ) {
		$tc        = bumbaru_color( $tree_color );
		$t['face'] = $tc['face'];
		$t['edge'] = $tc['edge'];
		if ( 'mini' !== $style ) {
			$t['trunk']      = $tc['face'];
			$t['trunk_edge'] = $tc['edge'];
		}
	}
	$s      = bumbaru_color( $star );
	$mid    = bumbaru_mix( $s['face'], $s['edge'], 0.42 );
	$e      = 'esc_attr';
	$labels = array(
		'mini'    => 'Pregled mini jelke sa zvezdicom u izabranoj boji',
		'spirala' => 'Pregled spiralne jelke sa zvezdicom u izabranoj boji',
		'ravna'   => 'Pregled jelke za sklapanje sa zvezdicom u izabranoj boji',
	);
	// Tačka oko koje se jelka smanjuje (dno) i vrh zvezde, za visinu.
	$geo = array(
		'mini'    => array( 'base' => 548, 'scx' => 300, 'scy' => 66, 'R' => 41, 'r' => 17 ),
		'spirala' => array( 'base' => 536, 'scx' => 300, 'scy' => 88, 'R' => 31, 'r' => 13 ),
		'ravna'   => array( 'base' => 498, 'scx' => 300, 'scy' => 89, 'R' => 36, 'r' => 15 ),
	);
	$g   = $geo[ $style ];
	$out = '<svg viewBox="0 0 600 600" role="img" aria-label="' . $e( $labels[ $style ] ) . '" data-preview="jelka" class="tree-svg tree-' . $e( $style ) . '" data-base="' . $g['base'] . '" data-top="' . ( $g['scy'] - $g['R'] ) . '">';
	$out .= '<defs><filter id="bt-sh" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="6" dy="14" stdDeviation="9" flood-color="#3A2A1C" flood-opacity="0.24"></feDropShadow></filter>';

	$body = '';
	if ( 'mini' === $style ) {
		$tiers = array(
			array( 300, 296, 482, 170, 6 ),
			array( 300, 214, 394, 140, 5 ),
			array( 300, 146, 306, 108, 4 ),
			array( 300, 96, 220, 76, 3 ),
		);
		foreach ( $tiers as $k => $ti ) {
			$out .= '<clipPath id="bt-c' . $k . '"><path d="' . bumbaru_tier_path( $ti[0], $ti[1], $ti[2], $ti[3], $ti[4] ) . '"></path></clipPath>';
		}
		$body .= '<g filter="url(#bt-sh)">';
		$trunk = 'M256 462L344 462L362 542Q300 552 238 542Z';
		$body .= '<path d="' . $trunk . '" transform="translate(3 6)" fill="' . $e( $t['trunk_edge'] ) . '"></path>';
		$body .= '<path d="' . $trunk . '" fill="' . $e( $t['trunk'] ) . '"></path>';
		$ribs  = '';
		for ( $k = 1; $k < 8; $k++ ) {
			$ribs .= 'M' . bumbaru_n( 256 + $k * 88 / 8 ) . ' 470L' . bumbaru_n( 238 + $k * 124 / 8 ) . ' 544';
		}
		$body .= '<path d="' . $ribs . '" stroke="' . $e( $t['trunk_edge'] ) . '" stroke-width="3" stroke-linecap="round" opacity=".55" fill="none"></path>';
		foreach ( $tiers as $k => $ti ) {
			$p     = bumbaru_tier_path( $ti[0], $ti[1], $ti[2], $ti[3], $ti[4] );
			$gr    = bumbaru_tier_grooves( $ti[0], $ti[1], $ti[2], $ti[3], $ti[4] );
			$body .= '<path d="' . $p . '" transform="translate(3 6)" fill="' . $e( $t['edge'] ) . '"></path>';
			$body .= '<path d="' . $p . '" fill="' . $e( $t['face'] ) . '"></path>';
			$body .= '<g clip-path="url(#bt-c' . $k . ')" fill="none" stroke="' . $e( $t['edge'] ) . '" stroke-linecap="round">';
			$body .= '<path d="' . $gr['fine'] . '" stroke-width="2" opacity=".38"></path>';
			$body .= '<path d="' . $gr['crease'] . '" stroke-width="3.6" opacity=".7"></path>';
			$body .= '<path d="' . $p . '" stroke="#FFFFFF" stroke-width="5" opacity=".10" transform="translate(-2 -3)"></path>';
			$body .= '</g>';
		}
		$body .= '</g>';
		$shadow = '<ellipse cx="300" cy="550" rx="150" ry="13" fill="#3A2A1C" opacity=".13" data-tree-shadow></ellipse>';
	} elseif ( 'spirala' === $style ) {
		$sg    = bumbaru_spiral_geo();
		$out  .= '<clipPath id="bt-sp"><path d="' . $sg['d'] . '"></path></clipPath>';
		$light = bumbaru_mix( $t['face'], '#FFFFFF', 0.16 );
		$body .= '<g filter="url(#bt-sh)">';
		$body .= '<path d="' . $sg['d'] . '" transform="translate(3 6)" fill="' . $e( $t['edge'] ) . '"></path>';
		$body .= '<path d="' . $sg['d'] . '" fill="' . $e( $t['face'] ) . '"></path>';
		$body .= '<g clip-path="url(#bt-sp)"><path d="' . $sg['ribs'] . '" stroke="' . $e( $t['edge'] ) . '" stroke-width="2.2" opacity=".42" fill="none"></path>'
			. '<path d="' . $sg['d'] . '" fill="none" stroke="#FFFFFF" stroke-width="6" opacity=".08" transform="translate(-3 -2)"></path></g>';
		$body .= '<g clip-path="url(#bt-sp)"><path d="' . $sg['ridge'] . '" transform="translate(0 11)" stroke="' . $e( $t['edge'] ) . '" stroke-width="16" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity=".45"></path></g>';
		$body .= '<path d="' . $sg['ridge'] . '" transform="translate(0 5)" stroke="' . $e( $t['edge'] ) . '" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>';
		$body .= '<path d="' . $sg['ridge'] . '" stroke="' . $e( $light ) . '" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>';
		$body .= '<path d="' . $sg['ridge'] . '" transform="translate(0 -1.6)" stroke="#FFFFFF" stroke-width="1.6" opacity=".22" stroke-linecap="round" fill="none"></path>';
		$body .= '</g>';
		$shadow = '<ellipse cx="300" cy="538" rx="130" ry="12" fill="#3A2A1C" opacity=".13" data-tree-shadow></ellipse>';
	} else {
		$tree_d = 'M300 118L238 200L268 200L200 285L240 285L160 370L205 370L120 455L280 455L280 498L320 498L320 455L480 455L395 370L440 370L360 285L400 285L332 200L362 200Z';
		$holes  = bumbaru_circle_path( 300, 172, 10 )
			. bumbaru_star_hole( 284, 250, 15 ) . bumbaru_circle_path( 322, 262, 8 )
			. bumbaru_circle_path( 254, 344, 11 ) . bumbaru_star_hole( 318, 330, 19 ) . bumbaru_circle_path( 362, 352, 8 )
			. bumbaru_star_hole( 238, 428, 15 ) . bumbaru_circle_path( 292, 430, 13 ) . bumbaru_star_hole( 352, 424, 17 ) . bumbaru_circle_path( 404, 440, 7 );
		$d      = $tree_d . $holes;
		$body  .= '<g filter="url(#bt-sh)">';
		// Drugi deo jelke (ukršten), vidi se kroz šare.
		$body .= '<path d="' . $tree_d . '" transform="translate(300 0) scale(.46 1) translate(-300 0) translate(4 4)" fill="' . $e( bumbaru_darken( $t['edge'], 0.12 ) ) . '"></path>';
		$body .= '<path d="' . $d . '" fill-rule="evenodd" transform="translate(3 6)" fill="' . $e( $t['edge'] ) . '"></path>';
		$body .= '<path d="' . $d . '" fill-rule="evenodd" fill="' . $e( $t['face'] ) . '" stroke="' . $e( $t['face'] ) . '" stroke-width="2" stroke-linejoin="round"></path>';
		$body .= '<rect x="297" y="150" width="6" height="305" rx="3" fill="' . $e( $t['edge'] ) . '" opacity=".5"></rect>';
		$body .= '<path d="' . $tree_d . '" fill="none" stroke="#FFFFFF" stroke-width="3" opacity=".18" transform="translate(-1.5 -2)" stroke-linejoin="round"></path>';
		$body .= '</g>';
		$shadow = '<ellipse cx="300" cy="505" rx="185" ry="13" fill="#3A2A1C" opacity=".13" data-tree-shadow></ellipse>';
	}
	$out .= '</defs>';

	// Zvezdica na vrhu: menja boju na klik.
	$sg_star = bumbaru_star_geo( $g['scx'], $g['scy'], $g['R'], $g['r'] );
	$star_g  = '<g class="tree-star" data-star style="transform-origin:' . $g['scx'] . 'px ' . $g['scy'] . 'px">';
	$star_g .= '<g class="tree-star-in" filter="url(#bt-sh)">';
	$star_g .= '<path class="st-edge" d="' . $sg_star['outline'] . '" transform="translate(2.5 5)" fill="' . $e( $s['edge'] ) . '" stroke="' . $e( $s['edge'] ) . '" stroke-width="5" stroke-linejoin="round"></path>';
	$star_g .= '<path class="st-a" d="' . $sg_star['outline'] . '" fill="' . $e( $s['face'] ) . '" stroke="' . $e( $s['face'] ) . '" stroke-width="5" stroke-linejoin="round"></path>';
	$star_g .= '<path class="st-b" d="' . $sg_star['halves'] . '" fill="' . $e( $mid ) . '"></path>';
	$star_g .= '</g>';
	$sp      = function ( $x, $y, $r, $cls ) {
		return '<path class="spark ' . $cls . '" d="M' . bumbaru_n( $x ) . ' ' . bumbaru_n( $y - $r ) . 'Q' . bumbaru_n( $x ) . ' ' . bumbaru_n( $y ) . ' ' . bumbaru_n( $x + $r ) . ' ' . bumbaru_n( $y ) . 'Q' . bumbaru_n( $x ) . ' ' . bumbaru_n( $y ) . ' ' . bumbaru_n( $x ) . ' ' . bumbaru_n( $y + $r ) . 'Q' . bumbaru_n( $x ) . ' ' . bumbaru_n( $y ) . ' ' . bumbaru_n( $x - $r ) . ' ' . bumbaru_n( $y ) . 'Q' . bumbaru_n( $x ) . ' ' . bumbaru_n( $y ) . ' ' . bumbaru_n( $x ) . ' ' . bumbaru_n( $y - $r ) . 'Z" fill="#FFF6D8"></path>';
	};
	$star_g .= $sp( $g['scx'] - 58, $g['scy'] - 18, 9, 'sp1' ) . $sp( $g['scx'] + 54, $g['scy'] - 30, 7, 'sp2' ) . $sp( $g['scx'] + 62, $g['scy'] + 22, 10, 'sp3' );
	$star_g .= '</g>';

	$out .= $shadow;
	$out .= '<g class="tree-body" data-tree-body style="transform-origin:300px ' . $g['base'] . 'px">' . $body . $star_g . '</g>';

	// Visina (za proizvode sa veličinama): tačkasta linija i oznaka „15 cm”.
	if ( $ruler ) {
		$top  = $g['scy'] - $g['R'];
		$out .= '<g class="tree-ruler" data-ruler>'
			. '<line data-ruler-line x1="492" x2="492" y1="' . $top . '" y2="' . $g['base'] . '" stroke="#4E3E33" stroke-width="5" stroke-linecap="round" stroke-dasharray="0 12"></line>'
			. '<line data-ruler-top x1="478" x2="506" y1="' . $top . '" y2="' . $top . '" stroke="#4E3E33" stroke-width="3" stroke-linecap="round"></line>'
			. '<line x1="478" x2="506" y1="' . $g['base'] . '" y2="' . $g['base'] . '" stroke="#4E3E33" stroke-width="3" stroke-linecap="round"></line>'
			. '<g data-ruler-label transform="translate(492 ' . ( ( $top + $g['base'] ) / 2 ) . ')"><rect x="-42" y="-19" width="84" height="38" rx="19" fill="#F5C137" stroke="#312018" stroke-width="2.5"></rect>'
			. '<text data-ruler-text x="0" y="7" text-anchor="middle" font-family="Geologica, system-ui, sans-serif" font-size="19" font-weight="800" fill="#312018"></text></g>'
			. '</g>';
	}
	$out .= '</svg>';
	return $out;
}
