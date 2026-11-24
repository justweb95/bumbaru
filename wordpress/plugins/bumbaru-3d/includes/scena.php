<?php
/**
 * Napravite svoj set (tip „scena”, NG-19): kupac dodaje i skida tacnu i figure (lutkice, spiralna jelka, jelka sa
 * postoljem), do 4 komada. Ništa nije obavezno, ni tacna. Pregled u galeriji: isečene figure na tacni ili na stolu,
 * u izabranim bojama (tacna, jelke). Cena je ista kao kad se kupuju posebno, sa popustom za različite proizvode
 * i za više istih (bumbaru_grupa_racun), i ide u korpu kao jedna stavka.
 *
 * Fotografije za pregled su u assets/scena/: pozadina.webp, figure sa providnom pozadinom (lutkice u bojama),
 * a za proizvode sa izborom boje „senka” (R = osvetljenje, G = deo: 255 prva boja, 0 druga boja, A = providnost).
 */

defined( 'ABSPATH' ) || exit;

/** Proizvodi u setu: slug => slika za pregled, visina (% pozadine), boje (id izbora boja → deo na slici). */
function bumbaru_scena_katalog() {
	return array(
		'rebrasta-ovalna-tacna'            => array( 'img' => 'tacna', 'tacna' => true, 'w' => 80, 'delovi' => array( 'boja' ) ),
		'deda-mraz-lutkica-pleteni-izgled' => array( 'img' => 'deda-mraz', 'h' => 33 ),
		'snesko-lutkica-pleteni-izgled'    => array( 'img' => 'snesko', 'h' => 33 ),
		'irvas-lutkica-pleteni-izgled'     => array( 'img' => 'irvas', 'h' => 33 ),
		'spiralna-jelka-sa-zvezdom'        => array( 'img' => 'spiralna', 'h' => 50, 'delovi' => array( 'boja', 'zvezda' ) ),
		'jelka-sa-postoljem'               => array( 'img' => 'jelka', 'h' => 48, 'delovi' => array( 'boja', 'zvezda' ) ),
	);
}

/** Podešavanje iz _bu_config: koji proizvodi, najviše komada, početni izbor. */
function bumbaru_scena_parse( $raw ) {
	$raw = is_array( $raw ) ? $raw : array();
	$kat = array_keys( bumbaru_scena_katalog() );
	$uz  = isset( $raw['uz'] ) ? array_values( array_intersect( array_map( 'sanitize_title', (array) $raw['uz'] ), $kat ) ) : array();
	$poc = isset( $raw['poc'] ) ? array_values( array_intersect( array_map( 'sanitize_title', (array) $raw['poc'] ), $kat ) ) : array();
	return array(
		'uz'  => $uz ? $uz : $kat,
		'max' => max( 1, min( 8, isset( $raw['max'] ) ? (int) $raw['max'] : 4 ) ),
		'poc' => $poc ? $poc : array( 'rebrasta-ovalna-tacna', 'deda-mraz-lutkica-pleteni-izgled', 'snesko-lutkica-pleteni-izgled', 'irvas-lutkica-pleteni-izgled' ),
	);
}

/** Proizvodi u setu sa cenom, slikom i izborom boja (samo objavljeni sa cenom). */
function bumbaru_scena_stavke( $product ) {
	static $cache = array();
	$id = $product->get_id();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}
	$c   = bumbaru_config( $product );
	$kat = bumbaru_scena_katalog();
	$out = array();
	foreach ( $c['scena']['uz'] as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		$x    = $post && 'publish' === $post->post_status ? wc_get_product( $post->ID ) : null;
		$cena = $x ? (float) bumbaru_base_price( $x ) : 0;
		if ( ! $x || ! $cena ) {
			continue;
		}
		$xc    = bumbaru_config( $x );
		$boje  = array();
		foreach ( $xc['izbor_boja'] as $b ) {
			if ( ! empty( $kat[ $slug ]['delovi'] ) && in_array( $b['id'], $kat[ $slug ]['delovi'], true ) ) {
				$boje[] = array( 'id' => $b['id'], 'label' => $b['label'], 'def' => bumbaru_pick_color( $b['def'] ) );
			}
		}
		$oi  = bumbaru_price_option_index( $xc );
		$vel = $oi >= 0 ? $xc['options'][ $oi ]['label'] . ': ' . $xc['options'][ $oi ]['values'][0]['label'] : '';
		$out[ $slug ] = array(
			'slug'  => $slug,
			'pid'   => $x->get_id(),
			'sku'   => $x->get_sku(),
			'naziv' => function_exists( 'bumbaru_grupa_kratko' ) ? bumbaru_grupa_kratko( $x->get_name() ) : $x->get_name(),
			'cena'  => $cena,
			'url'   => get_permalink( $x->get_id() ),
			'slika' => function_exists( 'bu_card_img' ) ? bu_card_img( $x ) : '',
			'vel'   => $vel,
			'boje'  => $boje,
			'kat'   => $kat[ $slug ],
		);
	}
	$cache[ $id ] = $out;
	return $out;
}

/** Obračun seta: $pick = [ slug => broj komada ]. */
function bumbaru_scena_racun( $product, $pick ) {
	$st  = bumbaru_scena_stavke( $product );
	$rac = array();
	foreach ( $pick as $slug => $n ) {
		if ( isset( $st[ $slug ] ) && $n > 0 ) {
			$rac[] = array( 'slug' => $slug, 'cena' => $st[ $slug ]['cena'], 'kom' => (int) $n );
		}
	}
	return bumbaru_grupa_racun( $rac );
}

/** Najniža i najviša cena seta (za „od … din”): jedan najjeftiniji komad, pa najskuplji pun set. */
function bumbaru_scena_cene( $product ) {
	$st = bumbaru_scena_stavke( $product );
	if ( ! $st ) {
		return array();
	}
	$c    = bumbaru_config( $product );
	$cene = array();
	foreach ( $st as $slug => $x ) {
		$cene[ $slug ] = $x['cena'];
	}
	asort( $cene );
	$min  = array( (string) key( $cene ) => 1 );
	arsort( $cene );
	$max  = array();
	foreach ( array_slice( array_keys( $cene ), 0, $c['scena']['max'] ) as $s ) {
		$max[ $s ] = 1;
	}
	return array( bumbaru_scena_racun( $product, $min )['ukupno'], bumbaru_scena_racun( $product, $max )['ukupno'] );
}

/** Izbor kupca iz korpe/porudžbine → stavka (cena, redovi za korpu i porudžbinu) ili WP_Error. */
function bumbaru_scena_choice( $product, $get ) {
	$c    = bumbaru_config( $product );
	$st   = bumbaru_scena_stavke( $product );
	$pick = array();
	$sum  = 0;
	foreach ( (array) $get( 'scena', array() ) as $slug => $n ) {
		$slug = sanitize_title( $slug );
		$n    = (int) $n;
		if ( isset( $st[ $slug ] ) && $n > 0 ) {
			$n             = ! empty( $st[ $slug ]['kat']['tacna'] ) ? 1 : min( $n, $c['scena']['max'] );
			$pick[ $slug ] = $n;
			$sum          += $n;
		}
	}
	if ( ! $pick ) {
		return new WP_Error( 'scena', 'Dodajte bar jedan proizvod u set.' );
	}
	if ( $sum > $c['scena']['max'] ) {
		return new WP_Error( 'scena', 'U setu može najviše ' . $c['scena']['max'] . ' komada.' );
	}
	$bj    = (array) $get( 'boje', array() );
	$lines = array();
	$boje  = array();
	$imena = array();
	foreach ( $pick as $slug => $n ) {
		$s   = $st[ $slug ];
		$izb = array();
		if ( $s['vel'] ) {
			$izb[] = $s['vel'];
		}
		foreach ( $s['boje'] as $b ) {
			$v = bumbaru_valid_color( isset( $bj[ $slug ][ $b['id'] ] ) ? $bj[ $slug ][ $b['id'] ] : '', $b['def'] );
			$boje[ $slug ][ $b['id'] ] = $v;
			$izb[] = $b['label'] . ': ' . bumbaru_color_label( $v );
		}
		$lines[] = array( ( $n > 1 ? $n . ' × ' : '' ) . $s['naziv'] . ( $s['sku'] ? ' (' . $s['sku'] . ')' : '' ), $izb ? implode( ', ', $izb ) : 'kao na fotografiji' );
		$imena[] = ( $n > 1 ? $n . ' × ' : '' ) . $s['naziv'];
	}
	$r = bumbaru_scena_racun( $product, $pick );
	if ( $r['popust'] > 0 ) {
		$lines[] = array( 'Popust', bumbaru_grupa_naziv( $r ) . ', −' . bumbaru_din( $r['popust'] ) );
	}
	return array(
		'lines' => $lines,
		'price' => $r['ukupno'],
		'qty'   => 0,
		'name'  => 'Set: ' . implode( ' + ', $imena ),
		'raw'   => array( 'type' => 'scena', 'scena' => $pick, 'boje' => $boje ),
		'old'   => $r['puna'] > $r['ukupno'] ? $r['puna'] : 0,
	);
}

/** Podaci za skriptu (scena.js). */
function bumbaru_scena_data( $product ) {
	$c     = bumbaru_config( $product );
	$boje  = array();
	foreach ( bumbaru_colors_in_stock() as $b ) {
		$boje[ $b['id'] ] = array( 'label' => $b['label'], 'hex' => $b['face'] );
	}
	$st = array();
	foreach ( bumbaru_scena_stavke( $product ) as $slug => $s ) {
		$st[] = array(
			'slug'   => $slug,
			'sku'    => $s['sku'],
			'naziv'  => $s['naziv'],
			'cena'   => $s['cena'],
			'vel'    => $s['vel'],
			'boje'   => $s['boje'],
			'img'    => BUMBARU3D_URL . 'assets/scena/' . $s['kat']['img'] . '.webp?v=' . BUMBARU3D_VERSION,
			'senka'  => ! empty( $s['kat']['delovi'] ),
			'tacna'  => ! empty( $s['kat']['tacna'] ),
			'h'      => isset( $s['kat']['h'] ) ? $s['kat']['h'] : 0,
			'w'      => isset( $s['kat']['w'] ) ? $s['kat']['w'] : 0,
		);
	}
	return array(
		'pid'     => $product->get_id(),
		'sku'     => $product->get_sku(),
		'naziv'   => $product->get_name(),
		'max'     => $c['scena']['max'],
		'poc'     => $c['scena']['poc'],
		'stavke'  => $st,
		'boje'    => $boje,
		'popusti' => bumbaru_grupa()['popusti'],
		'isti'    => bumbaru_grupa()['isti'],
		'slika'   => function_exists( 'bu_card_img' ) ? bu_card_img( $product ) : '',
	);
}

/** Slajd u galeriji: pregled seta (pozadina + isečene figure, crta ih scena.js). */
function bumbaru_scena_slide( $product ) {
	return '<div class="slide scena" data-slide="scena" data-sc-stage><span class="badge live-badge"><i class="dotlive"></i>Vaš set</span>'
		. '<div class="sc-stage" style="background-image:url(' . esc_url( BUMBARU3D_URL . 'assets/scena/pozadina.webp?v=' . BUMBARU3D_VERSION ) . ')" role="img" aria-label="Pregled vašeg seta" data-sc-scene></div>'
		. '<p class="sc-empty" data-sc-empty hidden>Dodajte tacnu ili figure i ovde vidite svoj set.</p></div>';
}

/** Sličica „Vaš set” ispod galerije. */
function bumbaru_scena_thumb( $product ) {
	$img = BUMBARU3D_URL . 'assets/scena/pozadina.webp?v=' . BUMBARU3D_VERSION;
	return '<button type="button" class="thumb thumb-live thumb-sas" data-thumb="scena" aria-pressed="true" aria-label="Pregled vašeg seta"><img src="' . esc_url( $img ) . '" alt="" width="60" height="60" /><span>Vaš set</span></button>';
}

/** Konfigurator u obrascu: pločice sa + i −, boje za izabrane proizvode, pčelica i cena seta. */
function bumbaru_scena_fields( $product ) {
	$c  = bumbaru_config( $product );
	$st = bumbaru_scena_stavke( $product );
	if ( ! $st ) {
		return '<p class="hint">Set trenutno nije dostupan.</p>';
	}
	$max = $c['scena']['max'];
	$o   = '<fieldset class="field mot sc" data-scena="' . esc_attr( wp_json_encode( bumbaru_scena_data( $product ) ) ) . '"><legend class="flabel">Šta ide u set <span class="sc-cnt" data-sc-cnt></span></legend>'
		. '<p class="hint mot-hint">Dodajte i skinite šta želite, do ' . (int) $max . ' komada: tacnu, lutkice i jelke. Može i više istih. Pregled u galeriji se menja odmah.</p>'
		. '<div class="mot-grid sc-grid">';
	foreach ( $st as $slug => $s ) {
		$o .= '<div class="mot-tile" data-sc="' . esc_attr( $slug ) . '"><button type="button" class="mot-pick" aria-pressed="false" data-sc-pick aria-label="' . esc_attr( $s['naziv'] ) . '">'
			. '<span class="mot-img">' . ( $s['slika'] ? '<img src="' . esc_url( $s['slika'] ) . '" alt="" width="160" height="160" loading="lazy" />' : '' ) . '</span>'
			. '<span class="mot-name">' . esc_html( $s['naziv'] ) . '</span><small class="chip-cena">' . esc_html( bumbaru_din( $s['cena'] ) ) . '</small>'
			. '<span class="mot-check" aria-hidden="true">' . bumbaru_icon( 'check', 14, 3 ) . '</span></button>'
			. '<div class="mot-step" data-sc-step hidden><button type="button" data-sc-dec aria-label="' . esc_attr( 'Skini: ' . $s['naziv'] ) . '">−</button><span data-sc-n aria-live="polite">1</span><button type="button" data-sc-inc aria-label="' . esc_attr( 'Još jedan: ' . $s['naziv'] ) . '"' . ( ! empty( $s['kat']['tacna'] ) ? ' hidden' : '' ) . '>+</button></div></div>';
	}
	$o .= '</div>';
	// Boje izabranih proizvoda (tacna, jelke): vide se samo kad je proizvod u setu.
	foreach ( $st as $slug => $s ) {
		if ( ! $s['boje'] ) {
			continue;
		}
		$o .= '<div class="sc-boje" data-sc-boje="' . esc_attr( $slug ) . '" hidden><p class="sc-boje-t">' . esc_html( $s['naziv'] ) . '</p>';
		foreach ( $s['boje'] as $b ) {
			$o .= bumbaru_color_picker( 'sc-' . $slug . '-' . $b['id'], $b['label'], $b['def'], array( 'attrs' => array( 'data-sc-color' => $slug . '|' . $b['id'] ) ) );
		}
		$o .= '</div>';
	}
	$o .= '<div class="field mot kol grp sc-mm">' . bumbaru_grupa_traka( 1 )
		. '<p class="mm-msg" aria-live="polite"><span class="mm-count" data-mm-count></span><span class="mm-badge" data-mm-badge hidden></span><span data-mm-text></span></p>'
		. '<p class="mm-total sc-total" data-sc-total></p></div>'
		. '<div class="sas-acts"><button type="button" class="btn btn-sm" data-sc-reset>Vrati početni set</button><button type="button" class="btn btn-sm" data-sc-clear>Isprazni set</button></div>'
		. '<p class="hint mot-err" data-sc-err role="alert"></p></fieldset>';
	return $o;
}
