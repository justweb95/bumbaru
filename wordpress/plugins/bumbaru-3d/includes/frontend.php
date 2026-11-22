<?php
/**
 * Konfigurator na strani proizvoda (isti izgled kao proizvod/[slug].astro na novom sajtu):
 * pregled uživo (ukras, set, jelka, privezak sa imenom), crtež u izabranim bojama, slavski set u krug,
 * izbor motiva i boja sa trakom popusta, izbor boja filamenta, opcije sa cenom, poziv, komplet i popusti za firme.
 * Tema poziva ove funkcije iz woocommerce/single-product.php.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	$on_product = function_exists( 'is_product' ) && is_product();
	if ( ! $on_product && ! is_front_page() ) {
		return;
	}
	$colors = array();
	foreach ( bumbaru_colors() as $c ) {
		$colors[ $c['id'] ] = array(
			'label' => $c['label'],
			'face'  => $c['face'],
			'edge'  => $c['edge'],
			'mid'   => bumbaru_mix( $c['face'], $c['edge'], 0.42 ),
		);
	}
	$boje = array();
	foreach ( bumbaru_colors() as $c ) {
		$boje[] = array( 'id' => $c['id'], 'naziv' => $c['label'], 'hex' => $c['face'], 'dostupna' => ! empty( $c['dostupna'] ) );
	}
	wp_enqueue_script( 'bumbaru-ornament', BUMBARU3D_URL . 'assets/ornament.js', array(), BUMBARU3D_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'bumbaru-ornament', 'BUMBARU3D', array(
		'colors'  => $colors,
		'boje'    => $boje,
		'motivi'  => bumbaru_motivi(),
		'fontovi' => bumbaru_fontovi(),
		'fm'      => bumbaru_font_metrics(),
		'stilovi' => bumbaru_astro_data( 'STILOVI' ),
		'primeri' => bumbaru_astro_data( 'PRIMERI' ),
		'ajax'    => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : '',
		'cart'    => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
		'checkout' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
	) );
	wp_enqueue_style( 'bumbaru-3d', BUMBARU3D_URL . 'assets/front.css', array(), BUMBARU3D_VERSION );
	// Pregled ukrasa u korpi, plaćanju i nalogu: ime u izabranom fontu.
	if ( ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) ) {
		wp_enqueue_style( 'bumbaru-ukrasi-fontovi', BUMBARU3D_URL . 'assets/fonts/ukrasi.css', array(), BUMBARU3D_VERSION );
	}
	if ( $on_product ) {
		$p = wc_get_product( get_queried_object_id() );
		if ( $p && in_array( bumbaru_config( $p )['type'], array( 'ukras', 'set' ), true ) ) {
			wp_enqueue_style( 'bumbaru-ukrasi-fontovi', BUMBARU3D_URL . 'assets/fonts/ukrasi.css', array(), BUMBARU3D_VERSION );
		}
		if ( $p && 'privezak' === bumbaru_config( $p )['type'] ) {
			wp_enqueue_style( 'bumbaru-privesci-fontovi', BUMBARU3D_URL . 'assets/fonts/privesci.css', array(), BUMBARU3D_VERSION );
		}
		wp_enqueue_script( 'bumbaru-product', BUMBARU3D_URL . 'assets/product.js', array( 'bumbaru-ornament' ), BUMBARU3D_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		$kp = $p ? bumbaru_komplet( $p ) : null;
		if ( $kp && $kp['biranje'] ) {
			wp_enqueue_script( 'bumbaru-komplet', BUMBARU3D_URL . 'assets/komplet.js', array(), BUMBARU3D_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
		if ( $p && bumbaru_config( $p )['kolicina'] ) {
			wp_enqueue_script( 'bumbaru-kolicina', BUMBARU3D_URL . 'assets/kolicina.js', array(), BUMBARU3D_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	}
}, 30 );

/** URL slike iz biblioteke (veličina) ili prazno. */
function bumbaru_att_url( $id, $size = 'full' ) {
	if ( ! $id ) {
		return '';
	}
	$s = wp_get_attachment_image_src( $id, $size );
	return $s ? $s[0] : '';
}

/** Izbor boje iz liste boja filamenta (samo boje na stanju), kao ColorPicker.astro. */
function bumbaru_color_picker( $name, $label, $def, $args = array() ) {
	$args  = wp_parse_args( $args, array( 'mix' => '', 'mix_default' => false, 'extra' => array(), 'attrs' => array() ) );
	$in    = bumbaru_colors_in_stock();
	$def   = bumbaru_pick_color( $def );
	$extra = null;
	foreach ( $args['extra'] as $e ) {
		if ( ! empty( $e['checked'] ) ) {
			$extra = $e;
		}
	}
	$checked = $args['mix_default'] || $extra ? '' : $def;
	$shown   = $args['mix_default'] ? $args['mix'] : ( $extra ? $extra['label'] : bumbaru_color( $def )['label'] );
	$attrs   = '';
	foreach ( $args['attrs'] as $k => $v ) {
		$attrs .= ' ' . esc_attr( $k ) . ( '' === $v ? '' : '="' . esc_attr( $v ) . '"' );
	}
	$o = '<fieldset class="field cp" data-cp' . $attrs . '><legend class="flabel">' . esc_html( $label ) . ': <span class="cp-name" data-cp-name>' . esc_html( $shown ) . '</span></legend><div class="cp-list">';
	if ( $args['mix'] ) {
		$o .= '<label class="cp-sw" title="' . esc_attr( $args['mix'] ) . '"><input type="radio" name="' . esc_attr( $name ) . '" value="sareno"' . ( $args['mix_default'] ? ' checked' : '' ) . ' data-label="' . esc_attr( $args['mix'] ) . '" aria-label="' . esc_attr( $args['mix'] ) . '" /><span class="cp-dot cp-mix" aria-hidden="true"></span></label>';
	}
	foreach ( $args['extra'] as $e ) {
		$o .= '<label class="cp-sw" title="' . esc_attr( $e['label'] ) . '"><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $e['value'] ) . '"' . ( ! empty( $e['checked'] ) ? ' checked' : '' ) . ' data-label="' . esc_attr( $e['label'] ) . '" aria-label="' . esc_attr( $e['label'] ) . '" /><span class="cp-dot" style="background:' . esc_attr( $e['css'] ) . '" aria-hidden="true"></span></label>';
	}
	foreach ( $in as $b ) {
		$o .= '<label class="cp-sw" title="' . esc_attr( $b['label'] ) . '"><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $b['id'] ) . '"' . ( $b['id'] === $checked ? ' checked' : '' ) . ' data-label="' . esc_attr( $b['label'] ) . '" data-hex="' . esc_attr( $b['face'] ) . '" aria-label="' . esc_attr( $b['label'] ) . '" /><span class="cp-dot' . ( bumbaru_is_light( $b['face'] ) ? ' cp-light' : '' ) . '" style="background:' . esc_attr( $b['face'] ) . '" aria-hidden="true"></span></label>';
	}
	return $o . '</div></fieldset>';
}

/** Ikonica (tema ima bu_icon; ovo je rezerva ako tema nije aktivna). */
function bumbaru_icon( $name, $size = 20, $stroke = 2 ) {
	if ( function_exists( 'bu_icon' ) ) {
		return bu_icon( $name, $size, $stroke );
	}
	return '';
}

/** Podaci za skriptu strane proizvoda (cena, akcija, izbor komada, privezak, slavski set, komplet, firme). */
function bumbaru_product_data( $product ) {
	$c    = bumbaru_config( $product );
	$base = bumbaru_base_price( $product );
	list( $kind, $v ) = bumbaru_discount( $product );
	$a    = bumbaru_akcija();
	$d    = array(
		'id'       => $product->get_sku() ? $product->get_sku() : (string) $product->get_id(),
		'pid'      => $product->get_id(),
		'type'     => $c['type'],
		'cena'     => $base,
		'od'       => (bool) get_post_meta( $product->get_id(), '_bu_price_from', true ),
		'buy'      => function_exists( 'bu_buyable' ) ? bu_buyable( $product ) : ( $product->is_purchasable() && '' !== $product->get_price() ),
		'popust'   => array( 'vrsta' => $kind, 'v' => $v, 'naziv' => 'ak' === $kind && $a ? $a['naziv'] : 'akcija' ),
		'setExtra' => 'set' === $c['type'] ? (float) $c['set_extra'] : 0,
		'naslov'   => $product->get_name(),
		'url'      => get_permalink( $product->get_id() ),
		'slika'    => function_exists( 'bu_card_img' ) ? bu_card_img( $product ) : '',
	);
	if ( 'privezak' === $c['type'] ) {
		$d['kc'] = array_merge( $c['keychain'], array( 'sarenoDefault' => $c['keychain']['startImena'] > 1 && $c['keychain']['sareno'] ) );
	}
	if ( bumbaru_izbor_komada( $product ) ) {
		$m     = $c['motivi'];
		$lista = array();
		if ( 'boje' === $c['type'] ) {
			foreach ( bumbaru_colors_in_stock() as $b ) {
				$lista[] = array( 'id' => $b['id'], 'naziv' => $b['label'], 'hex' => $b['face'] );
			}
		} else {
			foreach ( $m['lista'] as $it ) {
				$e = array( 'id' => $it['id'], 'naziv' => $it['naziv'], 'hex' => '' );
				if ( $base && $it['cena'] && $it['cena'] != $base ) {
					$e['cena'] = $it['cena'];
					if ( $it['cene'] && array_filter( $it['cene'] ) ) {
						$e['cene'] = $it['cene'];
					}
				}
				$lista[] = $e;
			}
		}
		$sil       = bumbaru_silueta( $m['silueta'] );
		$d['mot']  = array( 'boje' => 'boje' === $c['type'], 'sil' => $sil ? $sil['id'] : '', 'vb' => $sil ? $sil['viewBox'] : '', 'lista' => $lista, 'popusti' => bumbaru_tiers_iznad_akcije( $product, $m['popusti'] ), 'cena' => $base );
	}
	if ( 'sastav' === $c['type'] ) {
		$list = array();
		foreach ( bumbaru_set_modlice() as $m ) {
			$list[] = array( 'id' => $m['id'], 'naziv' => $m['naziv'], 'slika' => $m['slika'] );
		}
		$poc = bumbaru_astro_data( 'SET_POCETNI' );
		$d['sas'] = array(
			'broj'  => $c['sastav']['broj'],
			'poc'   => array_map( function ( $i ) use ( $poc ) {
				return $poc[ $i % count( $poc ) ];
			}, range( 0, $c['sastav']['broj'] - 1 ) ),
			'lista' => $list,
		);
	}
	$k = bumbaru_komplet( $product );
	if ( $k && ! $k['biranje'] ) {
		$d['km'] = $k;
	}
	if ( $c['firme'] && $base ) {
		$d['fm'] = array( 'popusti' => $c['firme']['popusti'], 'cena' => $base );
	}
	return $d;
}

/** Cena izabrane vrednosti opcije sa cenom po nazivu vrednosti (npr. „Srednji”), za komplet iste veličine. */
function bumbaru_komplet_velicine( $p ) {
	$c  = bumbaru_config( $p );
	$oi = bumbaru_price_option_index( $c );
	$v  = array();
	if ( $oi >= 0 ) {
		foreach ( $c['options'][ $oi ]['values'] as $val ) {
			$n                  = bumbaru_value_price( $p, $val );
			$v[ $val['label'] ] = array( 'cena' => $n, 'eff' => bumbaru_eff( $p, $n ) );
		}
	}
	return array( 'opcija' => $oi >= 0 ? $c['options'][ $oi ]['label'] : '', 'v' => $v );
}

/**
 * Komplet „Uz ovo često uzimaju”: samo za proizvode bez konfiguratora, i kad svi imaju cenu.
 * $sel = izabrani proizvodi (ID-jevi) kad kupac bira komplet; $vel = naziv izabrane veličine glavnog proizvoda
 * (proizvodi u kompletu sa istom opcijom dobijaju istu veličinu).
 */
function bumbaru_komplet( $product, $sel = null, $vel = '' ) {
	$c = bumbaru_config( $product );
	if ( empty( $c['komplet'] ) || ! bumbaru_base_price( $product ) || in_array( $c['type'], array( 'ukras', 'set', 'privezak', 'ime', 'imena', 'motivi', 'boje' ), true ) ) {
		return null;
	}
	$opt_main = bumbaru_komplet_velicine( $product )['opcija'];
	$uz       = array();
	foreach ( $c['komplet']['uz'] as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		$x    = $post && 'publish' === $post->post_status ? wc_get_product( $post->ID ) : null;
		if ( ! $x ) {
			continue;
		}
		$xc = bumbaru_config( $x );
		if ( in_array( $xc['type'], array( 'ukras', 'set', 'privezak', 'ime', 'imena', 'motivi', 'boje', 'sastav' ), true ) ) {
			continue;
		}
		$all = bumbaru_all_prices( $x );
		if ( ! $all ) {
			continue;
		}
		$vx   = bumbaru_komplet_velicine( $x );
		$same = $opt_main && $vx['opcija'] === $opt_main;
		$cena = $all[0];
		$izv  = '';
		if ( $same && $vel && isset( $vx['v'][ $vel ] ) ) {
			$cena = $vx['v'][ $vel ]['cena'];
			$izv  = $vel;
		}
		$izbor = array();
		foreach ( $xc['izbor_boja'] as $b ) {
			$izbor[] = $b['label'] . ': ' . bumbaru_color( bumbaru_pick_color( $b['def'] ) )['label'];
		}
		foreach ( $xc['options'] as $o ) {
			$izbor[] = $o['label'] . ': ' . ( $izv && $o['label'] === $vx['opcija'] ? $izv : $o['values'][0]['label'] );
		}
		$uz[] = array(
			'pid'      => $x->get_id(),
			'id'       => $x->get_sku() ? $x->get_sku() : (string) $x->get_id(),
			'naziv'    => $x->get_name(),
			'url'      => get_permalink( $x->get_id() ),
			'slika'    => function_exists( 'bu_card_img' ) ? bu_card_img( $x ) : '',
			'cena'     => $cena,
			'eff'      => bumbaru_eff( $x, $cena ),
			'izbor'    => $izbor,
			'velicine' => $same ? $vx['v'] : array(),
		);
	}
	$tiers = ! empty( $c['komplet']['popusti'] ) ? $c['komplet']['popusti'] : array();
	if ( $tiers && is_array( $sel ) ) {
		$sel = array_map( 'intval', $sel );
		$uz  = array_values( array_filter( $uz, function ( $x ) use ( $sel ) {
			return in_array( $x['pid'], $sel, true );
		} ) );
	}
	if ( ! $uz ) {
		return null;
	}
	$popust = $tiers ? bumbaru_popust_za( $tiers, 1 + count( $uz ) ) : $c['komplet']['popust'];
	return array( 'popust' => $popust, 'uz' => $uz, 'popusti' => $tiers, 'biranje' => (bool) $tiers );
}

/** Pregled uživo (prvi slajd galerije): ukras, set, jelka, privezak sa imenom. */
function bumbaru_live_slide( $product ) {
	$c = bumbaru_config( $product );
	if ( ! bumbaru_has_live( $product ) ) {
		return '';
	}
	$key = 'privezak' === $c['type'];
	$out = '<div class="slide live" data-slide="live"><span class="badge live-badge"><i class="dotlive"></i>' . ( $key ? 'Okvirni pregled' : 'Pregled uživo' ) . '</span>';
	if ( 'ukras' === $c['type'] ) {
		$hy   = 136;
		$out .= '<div class="sway-box sway"><svg viewBox="0 0 600 600" role="img" aria-label="Pregled ukrasa sa vašim imenom" data-preview="ukras">'
			. '<line x1="300" y1="0" x2="300" y2="' . $hy . '" stroke="#8E2A22" stroke-width="2.6" />'
			. '<g transform="translate(300 ' . $hy . ') scale(1.62) translate(-120 -64)" filter="url(#orn-sh)" data-tf>'
			. bumbaru_ornament_g( array( 'motif' => 'zvezde', 'color' => bumbaru_pick_color( 'bela' ), 'nameColor' => bumbaru_pick_color( 'crvena', 1 ), 'fallback' => 'Mila', 'key' => 'main' ) )
			. '</g></svg></div>';
	} elseif ( 'set' === $c['type'] ) {
		$sareno = bumbaru_sareno();
		$names  = array( 'Marko', 'Jelena', 'Luka', 'Mila', 'Ana' );
		foreach ( array( 4, 5 ) as $n ) {
			$l    = 4 === $n
				? array( 's' => 0.92, 'pts' => array( array( 165, 36 ), array( 435, 36 ), array( 165, 312 ), array( 435, 312 ) ) )
				: array( 's' => 0.76, 'pts' => array( array( 108, 44 ), array( 300, 44 ), array( 492, 44 ), array( 204, 318 ), array( 396, 318 ) ) );
			$out .= '<svg viewBox="0 0 600 600" role="img" aria-label="Pregled porodičnog seta od ' . $n . ' ukrasa" data-preview="set' . $n . '"' . ( 5 === $n ? ' hidden' : '' ) . '>';
			foreach ( $l['pts'] as $i => $pt ) {
				$out .= '<g transform="translate(' . $pt[0] . ' ' . $pt[1] . ') scale(' . $l['s'] . ') translate(-120 -49)" filter="url(#orn-sh)">'
					. bumbaru_ornament_g( array( 'motif' => 'zvezde', 'color' => $sareno[ $i ], 'nameColor' => 'ista', 'fallback' => $names[ $i ], 'key' => 's' . $i ) )
					. '</g>';
			}
			$out .= '</svg>';
		}
	} elseif ( 'jelka' === $c['type'] ) {
		$out .= bumbaru_tree_svg( $c['tree'], $c['star_default'], $c['tree_color'], bumbaru_size_option_index( $c ) >= 0 );
	} elseif ( $key ) {
		$out .= '<svg class="kc-svg" viewBox="0 0 600 300" role="img" aria-label="Pregled privezaka sa vašim imenima" data-kc-svg></svg>';
	}
	return $out . '</div>';
}

/** Ostali slajdovi pre fotografija: crtež u bojama, slavski set, „Vaš izbor” boja. */
function bumbaru_extra_slides( $product ) {
	$c   = bumbaru_config( $product );
	$out = '';
	$cr  = $c['crtez'] ? bumbaru_crtez( $c['crtez'] ) : null;
	if ( $cr && $c['izbor_boja'] ) {
		$vars = bumbaru_crtez_vars( $product );
		$out .= '<div class="slide crtez" data-slide="crtez" hidden style="' . esc_attr( $vars ) . '" data-crtez-slide><span class="badge live-badge"><i class="dotlive"></i>Vaše boje</span>'
			. '<svg viewBox="' . esc_attr( $cr['viewBox'] ) . '" role="img" aria-label="' . esc_attr( 'Crtež: ' . $cr['label'] . ' u izabranim bojama' ) . '">' . $cr['svg'] . '</svg>'
			. '<p class="mix-hint">Crtež pokazuje izabrane boje, a pravi izgled je na fotografijama.</p></div>';
	}
	if ( 'sastav' === $c['type'] ) {
		$med  = bumbaru_set_medaljon( $c['sastav']['medaljon'] );
		$all  = array();
		foreach ( bumbaru_set_modlice() as $m ) {
			$all[ $m['id'] ] = $m;
		}
		$poc  = bumbaru_astro_data( 'SET_POCETNI' );
		$n    = $c['sastav']['broj'];
		$out .= '<div class="slide sastav" data-slide="sastav" data-sas-stage><span class="badge live-badge"><i class="dotlive"></i>Vaš set</span>'
			. '<div class="ss-stage" style="background-image:url(' . esc_url( BUMBARU3D_URL . 'assets/set/pozadina.webp' ) . ')" data-vel="0">'
			. '<img class="ss-med" src="' . esc_url( $med['slika'] ) . '" alt="' . esc_attr( 'Medaljon ' . $med['naziv'] ) . '" width="520" height="500" />';
		for ( $i = 0; $i < $n; $i++ ) {
			$m    = $all[ $poc[ $i % count( $poc ) ] ];
			$a    = number_format( -90 + ( $i * 360 ) / $n, 2, '.', '' );
			$r    = ( ( $i * 37 ) % 11 ) - 5;
			$out .= '<span class="ss-m" data-ss-slot="' . $i . '" style="--a:' . $a . 'deg;--r:' . $r . 'deg"><img src="' . esc_url( $m['slika'] ) . '" alt="' . esc_attr( $m['naziv'] ) . '" loading="eager" /></span>';
		}
		$out .= '</div></div>';
	}
	if ( 'boje' === $c['type'] ) {
		$out .= '<div class="slide mix" data-slide="mix" hidden><span class="badge live-badge"><i class="dotlive"></i>Vaš izbor<b data-mix-n></b></span><div class="mix-grid" data-mix-grid></div><p class="mix-hint">Boje na crtežu su okvirne, a pravi izgled je na fotografijama.</p></div>';
	}
	return $out;
}

/** --c1, --c2… za crtež: boje redom izbora boja. */
function bumbaru_crtez_vars( $product ) {
	$c  = bumbaru_config( $product );
	$cr = bumbaru_crtez( $c['crtez'] );
	if ( ! $cr || ! $c['izbor_boja'] ) {
		return '';
	}
	$v = array();
	foreach ( $cr['delovi'] as $i => $deo ) {
		$ch  = isset( $c['izbor_boja'][ $i ] ) ? $c['izbor_boja'][ $i ] : $c['izbor_boja'][0];
		$v[] = '--c' . ( $i + 1 ) . ':' . bumbaru_color( bumbaru_pick_color( $ch['def'] ) )['face'];
	}
	return implode( ';', $v );
}

/** Slajdovi sa fotografijama motiva (npr. modlice): klik na motiv pokaže njegovu fotografiju. */
function bumbaru_motiv_slides( $product, $first_visible = false ) {
	$c = bumbaru_config( $product );
	if ( 'motivi' !== $c['type'] ) {
		return '';
	}
	$out   = '';
	$first = true;
	foreach ( $c['motivi']['lista'] as $m ) {
		if ( ! $m['img'] ) {
			continue;
		}
		$full = wp_get_attachment_image_src( $m['img'], 'full' );
		if ( ! $full ) {
			continue;
		}
		$alt  = trim( (string) get_post_meta( $m['img'], '_wp_attachment_image_alt', true ) );
		$vis  = $first_visible && $first;
		$out .= '<div class="slide" data-slide="m-' . esc_attr( $m['id'] ) . '"' . ( $vis ? '' : ' hidden' ) . '>';
		if ( (int) $full[1] !== (int) $full[2] ) {
			$out .= '<img class="backdrop" src="' . esc_url( bumbaru_att_url( $m['img'], 'woocommerce_thumbnail' ) ) . '" alt="" width="900" height="900" loading="lazy" aria-hidden="true" />';
		}
		$out .= '<img class="full" src="' . esc_url( $full[0] ) . '" alt="' . esc_attr( $alt ? $alt : $m['naziv'] ) . '" width="' . (int) $full[1] . '" height="' . (int) $full[2] . '" loading="' . ( $vis ? 'eager' : 'lazy' ) . '" decoding="async" />'
			. '<span class="badge mot-slide-name">' . esc_html( $m['naziv'] ) . '</span></div>';
		$first = false;
	}
	return $out;
}

/** Sličice ispod galerije za pregled, slavski set, „Vaš izbor” i crtež. */
function bumbaru_extra_thumbs( $product ) {
	$c   = bumbaru_config( $product );
	$out = '';
	if ( bumbaru_has_live( $product ) ) {
		$out .= '<button type="button" class="thumb thumb-live" data-thumb="live" aria-pressed="true" aria-label="' . esc_attr( 'jelka' === $c['type'] ? 'Pregled jelke sa izabranom zvezdicom' : 'Pregled sa vašim imenom' ) . '">' . bumbaru_icon( 'star', 22 ) . '<span>Pregled</span></button>';
	}
	if ( 'sastav' === $c['type'] ) {
		$med  = bumbaru_set_medaljon( $c['sastav']['medaljon'] );
		$out .= '<button type="button" class="thumb thumb-live thumb-sas" data-thumb="sastav" aria-pressed="true" aria-label="Pregled vašeg seta"><img src="' . esc_url( $med['slika'] ) . '" alt="" width="60" height="58" /><span>Vaš set</span></button>';
	}
	if ( 'boje' === $c['type'] ) {
		$out .= '<button type="button" class="thumb thumb-live" data-thumb="mix" aria-pressed="false" aria-label="Vaš izbor boja" hidden>' . bumbaru_icon( 'palette', 22 ) . '<span>Vaš izbor</span></button>';
	}
	$cr = $c['crtez'] ? bumbaru_crtez( $c['crtez'] ) : null;
	if ( $cr && $c['izbor_boja'] ) {
		$vars = bumbaru_crtez_vars( $product );
		$dots = '';
		foreach ( $cr['delovi'] as $i => $d ) {
			$dots .= '<i style="background:var(--c' . ( $i + 1 ) . ')"></i>';
		}
		$out .= '<button type="button" class="thumb thumb-live thumb-crtez" data-thumb="crtez" aria-pressed="false" aria-label="Crtež u izabranim bojama" style="' . esc_attr( $vars ) . '"><span class="tc-dots" aria-hidden="true">' . $dots . '</span><span>Vaše boje</span></button>';
	}
	return $out;
}

/** Ima li galerija slajdove pre fotografija (pa sličice idu i za jednu fotografiju). */
function bumbaru_has_extra_slides( $product ) {
	$c = bumbaru_config( $product );
	return bumbaru_has_live( $product ) || in_array( $c['type'], array( 'sastav', 'boje' ), true ) || ( $c['crtez'] && $c['izbor_boja'] );
}

/** Cena na pločici (vrednost opcije ili motiva). */
function bumbaru_chip_price( $product, $n, $old = 0 ) {
	return bumbaru_price_markup( $product, $n, '', '', $old );
}

/** Polja konfiguratora (isti redosled i izgled kao na novom sajtu). */
function bumbaru_config_fields( $product ) {
	$c     = bumbaru_config( $product );
	$type  = $c['type'];
	$base  = bumbaru_base_price( $product );
	$out   = '';
	$orn   = 'ukras' === $type || 'set' === $type;
	$oi    = bumbaru_price_option_index( $c );

	// Ime na ukrasu (ili na jednostavnom privesku).
	if ( 'ukras' === $type || 'ime' === $type ) {
		$out .= '<div class="field"><label for="f-ime">' . ( 'ukras' === $type ? 'Ime na ukrasu' : 'Ime na privesku' ) . '</label>'
			. '<input id="f-ime" class="input" name="ime" maxlength="12" placeholder="npr. Mila ili Љубица" autocomplete="off" data-ime required />'
			. '<p class="hint"><span data-err-ime>Latinicom ili ćirilicom. Štampamo tačno kako upišete.</span><span data-cnt>0/12</span></p></div>';
	}

	// Privezak sa imenom: stil slova, imena, boje slojeva.
	if ( 'privezak' === $type ) {
		$kc      = $c['keychain'];
		$stilovi = array_values( array_filter( array_map( 'bumbaru_kc_style', $kc['stilovi'] ) ) );
		$prvi    = $stilovi[0];
		if ( count( $stilovi ) > 1 ) {
			$out .= '<fieldset class="field"><legend class="flabel">Stil slova</legend><div class="style-grid">';
			foreach ( $stilovi as $i => $st ) {
				$out .= '<label class="style-tile"><input type="radio" name="stil" value="' . esc_attr( $st['id'] ) . '"' . ( 0 === $i ? ' checked' : '' ) . ' data-label="' . esc_attr( $st['label'] ) . '" />'
					. '<img class="style-img" src="' . esc_url( BUMBARU3D_URL . 'assets/stil/' . $st['id'] . '.webp' ) . '" alt="" width="360" height="180" loading="lazy" />'
					. '<span class="style-name">' . esc_html( $st['label'] ) . '</span></label>';
			}
			$out .= '</div></fieldset>';
		}
		$primeri = bumbaru_astro_data( 'PRIMERI' );
		$out    .= '<fieldset class="field"><legend class="flabel">' . ( $kc['maxImena'] > 1 ? 'Imena (svako ime je jedan privezak)' : 'Ime' ) . '</legend><div class="kc-names" data-kc-names>';
		for ( $i = 0; $i < $kc['startImena']; $i++ ) {
			$out .= '<div class="kc-row" data-kc-row><label class="sr-only" for="kc-' . $i . '">Ime ' . ( $i + 1 ) . '</label>'
				. '<input id="kc-' . $i . '" class="input" maxlength="12" placeholder="' . esc_attr( 'npr. ' . $primeri[ $i % count( $primeri ) ] ) . '" autocomplete="off" data-kc-name />'
				. '<button type="button" class="icon-btn kc-del" aria-label="Ukloni ime ' . ( $i + 1 ) . '" data-kc-del' . ( $i < $kc['minImena'] ? ' hidden' : '' ) . '>' . bumbaru_icon( 'close', 18 ) . '</button></div>';
		}
		$out .= '</div>';
		if ( $kc['maxImena'] > 1 ) {
			$out .= '<button type="button" class="btn btn-sm kc-add" data-kc-add>' . bumbaru_icon( 'plus', 18 ) . 'Dodajte još jedno ime</button>';
		}
		$out .= '<p class="hint"><span data-err-names>Do 12 slova po imenu, latinicom ili ćirilicom.</span></p></fieldset>';
		$sl   = bumbaru_astro_data( 'SLOJ_LABEL' );
		$mix  = $kc['sareno'] ? 'Šareno' : '';
		// Kad podrazumevana boja slova nije na stanju, slova i osnova bi bili iste boje (belo na belom):
		// tada slova dobijaju prvu boju na stanju koja se razlikuje od osnove i okvira (npr. crvenu).
		$def_sl = bumbaru_pick_color( $prvi['def']['slova'] );
		$def_os = bumbaru_pick_color( $prvi['def']['osnova'] );
		$def_ok = bumbaru_pick_color( $prvi['def']['okvir'] );
		if ( $def_sl === $def_os || ( in_array( 'okvir', $prvi['slojevi'], true ) && $def_sl === $def_ok ) ) {
			foreach ( array_merge( array( 'crvena', 'plava', 'zelena', 'crna' ), wp_list_pluck( bumbaru_colors_in_stock(), 'id' ) ) as $cid ) {
				if ( $cid !== $def_os && $cid !== $def_ok && bumbaru_pick_color( $cid ) === $cid ) {
					$prvi['def']['slova'] = $cid;
					break;
				}
			}
		}
		$out .= '<div class="kc-colors">'
			. bumbaru_color_picker( 'slova', $sl['slova'], $prvi['def']['slova'], array( 'mix' => $mix, 'mix_default' => $kc['startImena'] > 1 && $kc['sareno'], 'attrs' => array( 'data-layer' => 'slova' ) ) )
			. bumbaru_color_picker( 'okvir', $sl['okvir'], $prvi['def']['okvir'], array( 'mix' => $mix, 'attrs' => in_array( 'okvir', $prvi['slojevi'], true ) ? array( 'data-layer' => 'okvir' ) : array( 'data-layer' => 'okvir', 'hidden' => '' ) ) )
			. bumbaru_color_picker( 'osnova', $sl['osnova'], $prvi['def']['osnova'], array( 'attrs' => array( 'data-layer' => 'osnova' ) ) )
			. ( $kc['sareno'] ? '<p class="hint">Šareno: svako ime u drugoj boji.</p>' : '' )
			. '</div>';
	}

	// Porodični set ukrasa: broj ukrasa i imena.
	if ( 'set' === $type ) {
		$extra = (float) $c['set_extra'];
		$out  .= '<fieldset class="field"><legend class="flabel">Broj ukrasa</legend><div class="chips">'
			. '<label class="chip"><input type="radio" name="broj" value="4" checked data-label="4 ukrasa" />4 ukrasa</label>'
			. '<label class="chip"><input type="radio" name="broj" value="5" data-label="5 ukrasa" />5 ukrasa' . ( $extra && $base ? '<small class="chip-cena">+' . esc_html( bumbaru_din( $extra ) ) . '</small>' : '' ) . '</label>'
			. '</div></fieldset>';
	}
	// Imena (set ili stari porodični privezak).
	$fields = 'set' === $type ? 5 : ( 'imena' === $type ? 4 : 0 );
	if ( $fields ) {
		$out .= '<fieldset class="field"><legend class="flabel">Imena</legend><div class="names">';
		for ( $i = 0; $i < $fields; $i++ ) {
			$ph   = 'Ime ' . ( $i + 1 ) . ( 'imena' === $type && $i >= 2 ? ' (nije obavezno)' : '' );
			$out .= '<div class="name-row" data-name-row="' . $i . '"' . ( 'set' === $type && 4 === $i ? ' hidden' : '' ) . '>'
				. '<label class="sr-only" for="f-n' . $i . '">Ime ' . ( $i + 1 ) . '</label>'
				. '<input id="f-n' . $i . '" class="input" name="ime' . $i . '" maxlength="12" placeholder="' . esc_attr( $ph ) . '" autocomplete="off" data-set-name="' . $i . '" /></div>';
		}
		$out .= '</div><p class="hint"><span data-err-names>Do 12 slova po imenu, latinicom ili ćirilicom.</span></p></fieldset>';
	}
	// Motiv i boje ukrasa.
	if ( $orn ) {
		$out .= '<fieldset class="field"><legend class="flabel">' . ( 'set' === $type ? 'Motiv za sve ukrase' : 'Motiv' ) . '</legend><div class="tile-grid">';
		foreach ( bumbaru_motivi() as $i => $m ) {
			$out .= '<label class="tile"><input type="radio" name="motiv" value="' . esc_attr( $m['id'] ) . '"' . ( 0 === $i ? ' checked' : '' ) . ' data-label="' . esc_attr( $m['label'] ) . '" />'
				. '<svg viewBox="-12 34 264 256" aria-hidden="true"><use href="#orn-' . esc_attr( $m['id'] ) . '" fill="#7A6A5A" stroke="#7A6A5A" /></svg>' . esc_html( $m['label'] ) . '</label>';
		}
		$out .= '</div></fieldset>';
		$out .= bumbaru_font_picker( 'set' === $type );
		$out .= bumbaru_color_picker( 'boja', 'Boja ukrasa', 'bela', array( 'mix' => 'set' === $type ? 'Šareno' : '', 'mix_default' => 'set' === $type ) );
		$out .= '<div>' . bumbaru_color_picker( 'bojaImena', 'Boja imena', 'crvena', array( 'extra' => array( array( 'value' => 'ista', 'label' => 'Kao ukras', 'css' => 'linear-gradient(135deg, #EEEAE3 50%, #ADA498 50%)', 'checked' => 'set' === $type ) ) ) )
			. '<p class="hint" style="margin-top:8px">Ime u drugoj boji štampamo u istom komadu, bez lepljenja.</p></div>';
	}

	// Izbor motiva ili boja sa trakom popusta.
	if ( bumbaru_izbor_komada( $product ) ) {
		$out .= bumbaru_motivi_fields( $product );
	}

	// Zvezdica na jelki.
	if ( 'jelka' === $type ) {
		$tree_label = $c['tree_color'] ? mb_strtolower( bumbaru_color( $c['tree_color'] )['label'], 'UTF-8' ) : 'zelena';
		$out .= '<fieldset class="field"><legend class="flabel">Boja zvezdice</legend><div class="chips star-chips">';
		foreach ( $c['star_colors'] as $id ) {
			$col  = bumbaru_color( $id );
			$out .= '<label class="chip"><input type="radio" name="zvezda" value="' . esc_attr( $id ) . '"' . ( $id === $c['star_default'] ? ' checked' : '' ) . ' data-label="' . esc_attr( $col['label'] ) . '" />'
				. '<span class="sw sw-star" style="--sf:' . esc_attr( $col['face'] ) . ';--se:' . esc_attr( $col['edge'] ) . '" aria-hidden="true"></span>' . esc_html( $col['label'] ) . '</label>';
		}
		$out .= '</div><p class="hint">Jelka je uvek ' . esc_html( $tree_label ) . ( 'mini' === $c['tree'] ? ' sa braon stablom' : '' ) . ', a birate boju zvezdice na vrhu. Kliknite na boju i vidite je na jelki.</p></fieldset>';
	}

	// Izbor boja filamenta (npr. Boja jelke, Boja zvezde).
	foreach ( $c['izbor_boja'] as $b ) {
		$out .= bumbaru_color_picker( 'c-' . $b['id'], $b['label'], $b['def'], array( 'attrs' => array( 'data-color-choice' => $b['label'] ) ) );
	}

	// Opcije (model, veličina…), sa cenom po vrednosti ili doplatom.
	$size_i = bumbaru_size_option_index( $c );
	foreach ( $c['options'] as $i => $o ) {
		$priced = $i === $oi && $base;
		if ( count( $o['values'] ) <= 4 ) {
			$out .= '<fieldset class="field"' . ( $i === $size_i ? ' data-size-option' : '' ) . '><legend class="flabel">' . esc_html( $o['label'] ) . '</legend><div class="chips">';
			foreach ( $o['values'] as $vi => $v ) {
				$n    = bumbaru_value_price( $product, $v );
				$attr = $priced ? ' data-cena="' . esc_attr( $n ) . '"' . ( $v['old'] > $n ? ' data-stara="' . esc_attr( $v['old'] ) . '"' : '' ) : ' data-add="' . esc_attr( $v['add'] ) . '"';
				$out .= '<label class="chip"><input type="radio" name="o-' . $i . '" value="' . $vi . '"' . ( 0 === $vi ? ' checked' : '' ) . ' data-option="' . esc_attr( $o['label'] ) . '" data-label="' . esc_attr( $v['label'] ) . '"' . $attr . ' />'
					. esc_html( $v['label'] );
				if ( $priced ) {
					$out .= '<small class="chip-cena">' . bumbaru_chip_price( $product, $n, $v['old'] ) . '</small>';
				} elseif ( $v['add'] && $base ) {
					$out .= '<small class="chip-cena">' . ( $v['add'] > 0 ? '+' : '−' ) . esc_html( bumbaru_din( abs( $v['add'] ) ) ) . '</small>';
				}
				$out .= '</label>';
			}
			$out .= '</div></fieldset>';
		} else {
			$out .= '<div class="field"><label for="o-' . $i . '">' . esc_html( $o['label'] ) . '</label><select id="o-' . $i . '" class="select" name="o-' . $i . '" data-option="' . esc_attr( $o['label'] ) . '">';
			foreach ( $o['values'] as $vi => $v ) {
				$n    = bumbaru_value_price( $product, $v );
				$attr = $priced ? ' data-cena="' . esc_attr( $n ) . '"' . ( $v['old'] > $n ? ' data-stara="' . esc_attr( $v['old'] ) . '"' : '' ) : ' data-add="' . esc_attr( $v['add'] ) . '"';
				$txt  = $v['label'] . ( $priced ? ' – ' . bumbaru_din( bumbaru_eff( $product, $n ) ) : ( $v['add'] && $base ? ' (' . ( $v['add'] > 0 ? '+' : '−' ) . bumbaru_din( abs( $v['add'] ) ) . ')' : '' ) );
				$out .= '<option value="' . $vi . '" data-label="' . esc_attr( $v['label'] ) . '"' . $attr . '>' . esc_html( $txt ) . '</option>';
			}
			$out .= '</select></div>';
		}
	}

	$out .= bumbaru_kolicina_html( $product );

	// Slavski set: izbor modlica.
	if ( 'sastav' === $type ) {
		$med  = bumbaru_set_medaljon( $c['sastav']['medaljon'] );
		$n    = $c['sastav']['broj'];
		$out .= '<fieldset class="field mot sas" data-sastav><legend class="flabel">Izaberite ' . (int) $n . ' modlica za set</legend>'
			. '<p class="hint mot-hint">Uz medaljon ' . esc_html( $med['naziv'] ) . ' birate ' . (int) $n . ' modlica. Može i više komada iste. Pregled seta u galeriji se menja sa vašim izborom.</p>'
			. '<div class="mot-grid sas-grid" data-sas-grid>';
		foreach ( bumbaru_set_modlice() as $m ) {
			$out .= '<div class="mot-tile" data-sas="' . esc_attr( $m['id'] ) . '"><button type="button" class="mot-pick" aria-pressed="false" data-sas-pick aria-label="' . esc_attr( $m['naziv'] ) . '">'
				. '<span class="mot-img sas-img"><img src="' . esc_url( $m['slika'] ) . '" alt="" width="' . (int) $m['w'] . '" height="' . (int) $m['h'] . '" loading="lazy" /></span>'
				. '<span class="mot-name">' . esc_html( $m['naziv'] ) . '</span><span class="mot-check" aria-hidden="true">' . bumbaru_icon( 'check', 14, 3 ) . '</span></button>'
				. '<div class="mot-step" data-sas-step hidden><button type="button" data-sas-dec aria-label="' . esc_attr( 'Jedna manje: ' . $m['naziv'] ) . '">−</button><span data-sas-n aria-live="polite">1</span><button type="button" data-sas-inc aria-label="' . esc_attr( 'Još jedna: ' . $m['naziv'] ) . '">+</button></div></div>';
		}
		$out .= '</div><p class="mm-msg sas-msg" aria-live="polite"><span class="mm-count" data-sas-count>0 / ' . (int) $n . '</span><span data-sas-text></span></p>'
			. '<div class="sas-acts"><button type="button" class="btn btn-sm" data-sas-reset>Vrati početni izbor</button><button type="button" class="btn btn-sm" data-sas-clear>Isprazni izbor</button></div>'
			. '<p class="hint mot-err" data-err-sas role="alert"></p></fieldset>';
	}
	return $out;
}

/** Koliko komada ovog proizvoda je već u korpi. */
function bumbaru_u_korpi( $product_id ) {
	$n = 0;
	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $it ) {
			if ( (int) $it['product_id'] === (int) $product_id ) {
				$n += (int) $it['quantity'];
			}
		}
	}
	return $n;
}

/** Popust za više komada (npr. ukrasi sa imenom): traka sa pčelicom koja pokazuje koliko još do popusta. */
function bumbaru_kolicina_html( $product ) {
	$c     = bumbaru_config( $product );
	$tiers = bumbaru_tiers_iznad_akcije( $product, $c['kolicina'] );
	if ( ! $tiers ) {
		return '';
	}
	$max  = end( $tiers )['od'];
	$korp = bumbaru_u_korpi( $product->get_id() );
	$unit = bumbaru_eff( $product, bumbaru_base_price( $product ) );
	$rec  = 'ukras' === $c['type'] ? array( 'ukras', 'ukrasa' ) : array( 'komad', 'komada' );
	$data = array( 'tiers' => $tiers, 'korpa' => $korp, 'cena' => $unit, 'lista' => bumbaru_base_price( $product ), 'pid' => $product->get_id(), 'rec' => $rec );
	// Ukras sa imenom: za 2–4 komada predloži porodični set (povoljniji po komadu).
	if ( 'ukras' === $c['type'] ) {
		$set = bumbaru_set_ponuda();
		if ( $set ) {
			$data['set'] = $set;
		}
	}
	$o    = '<div class="field mot kol" data-kolicina="' . esc_attr( wp_json_encode( $data ) ) . '"><p class="flabel">Popust za više ' . esc_html( $rec[1] ) . '</p>'
		. '<p class="hint mot-hint">' . ( 'ukras' === $c['type'] ? 'Svaki ukras može imati drugo ime: upišite ime, dodajte u korpu, pa sledeće. ' : '' ) . 'Sabiramo sve komade u korpi.</p>'
		. '<div class="mm" aria-hidden="true"><div class="mm-track"><div class="mm-fill" data-mm-fill></div>';
	foreach ( $tiers as $t ) {
		$o .= '<span class="mm-stop" data-mm-stop="' . (int) $t['od'] . '" style="--x:' . esc_attr( round( $t['od'] / $max * 100, 2 ) ) . '%"><i></i><span class="mm-lab"><b>−' . esc_html( (float) $t['procenat'] ) . '%</b><small>' . (int) $t['od'] . ' kom</small></span></span>';
	}
	$o .= '<img class="mm-bee" data-mm-bee src="' . esc_url( function_exists( 'bu_img' ) ? bu_img( 'brand/pcelica.webp' ) : '' ) . '" alt="" width="44" height="44" /></div></div>'
		. '<p class="mm-msg" aria-live="polite"><span class="mm-count" data-mm-count>1 kom</span><span class="mm-badge" data-mm-badge hidden></span><span data-mm-text></span></p>'
		. ( $unit ? '<p class="mm-total" data-mm-total hidden></p>' : '' )
		. ( ! empty( $data['set'] ) ? '<p class="mm-set" data-mm-set hidden></p>' : '' )
		. '</div>';
	return $o;
}

/**
 * Pragovi popusta koji donose nešto preko akcije: važi veći popust (akcija ili popust za komade/komplet), ne zbir,
 * pa prag koji nije veći od akcije ne nudimo (tokom akcije −10% prag „5 kom −10%” ne menja cenu).
 */
function bumbaru_tiers_iznad_akcije( $product, $tiers ) {
	$ak = bumbaru_discount_pct( $product );
	return array_values( array_filter( (array) $tiers, function ( $t ) use ( $ak ) {
		return (float) $t['procenat'] > $ak;
	} ) );
}

/** Porodični set ukrasa za predlog uz pojedinačni ukras: { url, naziv, cena (za 4), po (po komadu), kom }. */
function bumbaru_set_ponuda() {
	static $r = false;
	if ( false !== $r ) {
		return $r;
	}
	$r   = null;
	$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_bu_config' ) ); // phpcs:ignore
	foreach ( $ids as $id ) {
		$raw = get_post_meta( $id, '_bu_config', true );
		if ( ! is_array( $raw ) || empty( $raw['type'] ) || 'set' !== $raw['type'] ) {
			continue;
		}
		$p = wc_get_product( $id );
		if ( $p && $p->is_visible() ) {
			$cena = bumbaru_eff( $p, bumbaru_base_price( $p ) );
			if ( $cena > 0 ) {
				$r = array( 'url' => get_permalink( $id ), 'naziv' => $p->get_name(), 'cena' => $cena, 'po' => round( $cena / 4 ), 'kom' => 4 );
			}
			break;
		}
	}
	return $r;
}

/** Izbor fonta za ime na ukrasu: dugme sa primerom imena u tom fontu. */
function bumbaru_font_picker( $set = false ) {
	$o = '<fieldset class="field"><legend class="flabel">' . ( $set ? 'Slova za sva imena' : 'Slova' ) . '</legend><div class="chips font-chips">';
	foreach ( bumbaru_fontovi() as $i => $f ) {
		$o .= '<label class="chip font-chip"><input type="radio" name="font" value="' . esc_attr( $f['id'] ) . '"' . ( 0 === $i ? ' checked' : '' ) . ' data-label="' . esc_attr( $f['label'] ) . '" />'
			. '<span class="font-ex" style="font-family:' . esc_attr( $f['css'] ) . '">Ime</span><small>' . esc_html( $f['label'] ) . '</small></label>';
	}
	return $o . '</div><p class="hint">Ćirilicu imaju sva slova osim „Četkica” (tada ime štampamo okruglim slovima).</p></fieldset>';
}

/** Izbor motiva (npr. modlice) ili boja (npr. klipovi): pločice, brojač i traka popusta sa pčelicom. */
function bumbaru_motivi_fields( $product ) {
	$c     = bumbaru_config( $product );
	$m     = $c['motivi'];
	$boje  = 'boje' === $c['type'];
	$base  = bumbaru_base_price( $product );
	$sil   = $boje ? bumbaru_silueta( $m['silueta'] ) : null;
	$oi    = bumbaru_price_option_index( $c );
	$f0    = $oi >= 0 && $base ? bumbaru_value_price( $product, $c['options'][ $oi ]['values'][0] ) / $base : 1;
	$razne = bumbaru_razna_cena_motiva( $product );
	$items = array();
	if ( $boje ) {
		foreach ( bumbaru_colors_in_stock() as $b ) {
			$items[] = array( 'id' => $b['id'], 'naziv' => $b['label'], 'hex' => $b['face'], 'cena' => 0, 'cene' => array(), 'img' => 0 );
		}
	} else {
		foreach ( $m['lista'] as $it ) {
			$items[] = array( 'id' => $it['id'], 'naziv' => $it['naziv'], 'hex' => '', 'cena' => $it['cena'] && $it['cena'] != $base ? $it['cena'] : 0, 'cene' => $it['cene'], 'img' => $it['img'] );
		}
	}
	$tiers = bumbaru_tiers_iznad_akcije( $product, $m['popusti'] );
	$max   = $tiers ? end( $tiers )['od'] : 0;
	$rec   = $boje ? 'boje' : 'motive';
	$o     = '<fieldset class="field mot" data-motivi' . ( $boje ? ' data-boje' : '' ) . '><legend class="flabel">' . ( $boje ? 'Izaberite boje i broj komada' : 'Izaberite motive' ) . '</legend>'
		. '<p class="hint mot-hint">' . ( $boje ? 'Kliknite boju koju želite, pa sa + i − podesite broj komada. Možete uzeti i više boja zajedno.' : 'Kliknite one koji vam se sviđaju. Može i više komada istog motiva.' ) . ( $razne ? ' Medaljoni su veći, pa im je cena upisana na pločici.' : '' ) . '</p>';
	if ( $boje ) {
		$o .= '<svg width="0" height="0" class="sil-sprite" aria-hidden="true"><defs>' . bumbaru_astro_data( 'SILUETE_DEFS' ) . '</defs>' . ( $sil ? '<symbol id="sil-' . esc_attr( $sil['id'] ) . '" viewBox="' . esc_attr( $sil['viewBox'] ) . '">' . $sil['symbol'] . '</symbol>' : '' ) . '</svg>';
	}
	$o .= '<div class="mot-grid' . ( $boje ? ' mot-grid-boje' . ( count( $items ) > 6 ? ' mot-grid-4' : '' ) : '' ) . '" data-mot-grid>';
	foreach ( $items as $it ) {
		$o .= '<div class="mot-tile" data-mot="' . esc_attr( $it['id'] ) . '"><button type="button" class="mot-pick" aria-pressed="false" data-mot-pick aria-label="' . esc_attr( $it['naziv'] ) . '">';
		if ( $boje ) {
			$o .= '<span class="mot-img mot-boja" style="--c:' . esc_attr( $it['hex'] ) . '">' . ( $sil ? '<svg class="sil" viewBox="' . esc_attr( $sil['viewBox'] ) . '" aria-hidden="true"><use href="#sil-' . esc_attr( $sil['id'] ) . '" /></svg>' : '<i class="mot-krug"></i>' ) . '</span>';
		} else {
			$src = $it['img'] ? bumbaru_att_url( $it['img'], 'woocommerce_thumbnail' ) : '';
			$o  .= '<span class="mot-img">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="" width="300" height="300" loading="lazy" />' : '<img class="mot-ph" src="' . esc_url( function_exists( 'bu_img' ) ? bu_img( 'brand/pcelica.webp' ) : '' ) . '" alt="" width="160" height="160" loading="lazy" />' ) . '</span>';
		}
		$o .= '<span class="mot-name">' . esc_html( $it['naziv'] ) . '</span>';
		if ( $base && $it['cena'] ) {
			$n  = ! empty( $it['cene'][0] ) ? $it['cene'][0] : round( $it['cena'] * $f0 / 10 ) * 10;
			$o .= '<span class="mot-cena" data-mot-cena>' . bumbaru_chip_price( $product, $n ) . '</span>';
		}
		$o .= '<span class="mot-check" aria-hidden="true">' . bumbaru_icon( 'check', 14, 3 ) . '</span></button>'
			. '<div class="mot-step" data-mot-step hidden><button type="button" data-mot-dec aria-label="' . esc_attr( 'Jedan manje: ' . $it['naziv'] ) . '">−</button><span data-mot-n aria-live="polite">1</span><button type="button" data-mot-inc aria-label="' . esc_attr( 'Još jedan: ' . $it['naziv'] ) . '">+</button></div></div>';
	}
	$o .= '</div>';
	if ( $boje && $tiers && count( $items ) > 1 ) {
		$o .= '<div class="mot-quick"><span class="mot-quick-lab">' . bumbaru_icon( 'sparkle', 16 ) . 'Šareni miks:</span>';
		foreach ( $tiers as $t ) {
			$o .= '<button type="button" class="mq" data-mot-set="' . (int) $t['od'] . '">' . (int) $t['od'] . ' kom<small>−' . esc_html( (float) $t['procenat'] ) . '%</small></button>';
		}
		$o .= '</div>';
	}
	if ( $tiers ) {
		$o .= '<div class="mm" aria-hidden="true"><div class="mm-track"><div class="mm-fill" data-mm-fill></div>';
		foreach ( $tiers as $t ) {
			$o .= '<span class="mm-stop" data-mm-stop="' . (int) $t['od'] . '" style="--x:' . esc_attr( round( $t['od'] / $max * 100, 2 ) ) . '%"><i></i><span class="mm-lab"><b>−' . esc_html( (float) $t['procenat'] ) . '%</b><small>' . (int) $t['od'] . ' kom</small></span></span>';
		}
		$o .= '<img class="mm-bee" data-mm-bee src="' . esc_url( function_exists( 'bu_img' ) ? bu_img( 'brand/pcelica.webp' ) : '' ) . '" alt="" width="44" height="44" /></div></div>';
	}
	$first = $tiers ? 'Od ' . (int) $tiers[0]['od'] . ' komada dobijate popust ' . (float) $tiers[0]['procenat'] . '%.' : 'Kliknite ' . $rec . ' koje želite.';
	$o    .= '<p class="mm-msg" aria-live="polite"><span class="mm-count" data-mm-count>0 kom</span><span class="mm-badge" data-mm-badge hidden></span><span data-mm-text>' . esc_html( $first ) . '</span></p>';
	if ( $base ) {
		$o .= '<p class="mm-total" data-mm-total hidden></p>';
	}
	return $o . '<p class="hint mot-err" data-err-mot role="alert"></p></fieldset>';
}

/** Istaknut poziv na Messenger (npr. „Pošaljite mi celu kolekciju”). */
function bumbaru_poziv_html( $product ) {
	$c = bumbaru_config( $product );
	if ( ! $c['poziv'] ) {
		return '';
	}
	$sku = $product->get_sku() ? $product->get_sku() : (string) $product->get_id();
	$msg = $c['poziv']['poruka'] ? $c['poziv']['poruka'] : 'Zdravo! ' . $c['poziv']['dugme'] . ' (' . $product->get_name() . ').';
	$url = function_exists( 'bu_messenger_url' ) ? bu_messenger_url( $msg, $sku ) : '#';
	return '<div class="poziv"><img class="poziv-bee fx-float" src="' . esc_url( function_exists( 'bu_img' ) ? bu_img( 'brand/pcelica.webp' ) : '' ) . '" alt="" width="72" height="72" loading="lazy" />'
		. '<p>' . esc_html( $c['poziv']['tekst'] ) . '</p>'
		. '<a class="btn btn-primary" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . bumbaru_icon( 'messenger' ) . esc_html( $c['poziv']['dugme'] ) . '</a></div>';
}

/** Komplet „Uz ovo često uzimaju” sa popustom (dodaje se u korpu kao jedna stavka). */
function bumbaru_komplet_html( $product ) {
	$k = bumbaru_komplet( $product );
	if ( ! $k ) {
		return '';
	}
	if ( $k['biranje'] ) {
		return bumbaru_komplet_izbor_html( $product, $k );
	}
	$c     = bumbaru_config( $product );
	$oi    = bumbaru_price_option_index( $c );
	$a     = $oi >= 0 ? bumbaru_value_price( $product, $c['options'][ $oi ]['values'][0] ) : bumbaru_base_price( $product );
	$puna  = $a;
	$zbir  = bumbaru_eff( $product, $a );
	$names = array();
	foreach ( $k['uz'] as $x ) {
		$puna   += $x['cena'];
		$zbir   += $x['eff'];
		$names[] = $x['naziv'];
	}
	$cena = round( $zbir * ( 1 - $k['popust'] / 100 ) / 10 ) * 10;
	$img  = function_exists( 'bu_card_img' ) ? bu_card_img( $product ) : '';
	$o    = '<div class="komplet" data-komplet><div class="km-head"><h2 class="h3">Uz ovo često uzimaju</h2><span class="badge badge-leaf">−' . esc_html( (float) $k['popust'] ) . '% za komplet</span></div><div class="km-row">'
		. '<span class="km-item"><span class="km-img">' . ( $img ? '<img src="' . esc_url( $img ) . '" alt="" width="120" height="120" loading="lazy" />' : '' ) . '</span><span class="km-name">' . esc_html( $product->get_name() ) . '</span></span>';
	foreach ( $k['uz'] as $x ) {
		$o .= '<span class="km-plus" aria-hidden="true">+</span><a class="km-item" href="' . esc_url( $x['url'] ) . '"><span class="km-img">' . ( $x['slika'] ? '<img src="' . esc_url( $x['slika'] ) . '" alt="" width="120" height="120" loading="lazy" />' : '' ) . '</span><span class="km-name">' . esc_html( $x['naziv'] ) . '</span></a>';
	}
	$o .= '</div><div class="km-foot"><p class="km-cena">Komplet: <s data-km-puna>' . esc_html( bumbaru_din( $puna ) ) . '</s> <b data-km-cena>' . esc_html( bumbaru_din( $cena ) ) . '</b></p>'
		. '<button class="btn btn-primary" type="button" data-km-add>' . bumbaru_icon( 'bag' ) . 'Dodaj komplet u korpu</button></div>'
		. '<p class="small muted">Za ' . esc_html( implode( ' i ', $names ) ) . ' uzimamo osnovnu veličinu i boju; drugačije napišite u napomeni.</p></div>';
	return $o;
}

/** Komplet po izboru: kupac kućicama bira šta ide uz ovaj proizvod, popust raste sa brojem proizvoda. */
function bumbaru_komplet_izbor_html( $product, $k ) {
	$vm   = bumbaru_komplet_velicine( $product );
	$base = bumbaru_base_price( $product );
	$main = $vm['v'] ? $vm['v'] : array( '' => array( 'cena' => $base, 'eff' => bumbaru_eff( $product, $base ) ) );
	$img  = function_exists( 'bu_card_img' ) ? bu_card_img( $product ) : '';
	$data = array(
		'popusti' => bumbaru_tiers_iznad_akcije( $product, $k['popusti'] ),
		'opcija'  => $vm['opcija'],
		'main'    => $main,
		'uz'      => array_map( function ( $x ) {
			return array( 'pid' => $x['pid'], 'naziv' => $x['naziv'], 'cena' => $x['cena'], 'eff' => $x['eff'], 'v' => $x['velicine'] );
		}, $k['uz'] ),
		'pid'     => $product->get_id(),
		'naziv'   => $product->get_name(),
		'slika'   => $img,
	);
	$tiers = array();
	foreach ( bumbaru_tiers_iznad_akcije( $product, $k['popusti'] ) as $t ) {
		$tiers[] = (int) $t['od'] . ' kom −' . (float) $t['procenat'] . '%';
	}
	$o = '<div class="komplet km-izbor" data-komplet2="' . esc_attr( wp_json_encode( $data ) ) . '"><div class="km-head"><h2 class="h3">Napravite komplet</h2><span class="badge badge-leaf" data-km-badge>' . esc_html( implode( ' · ', $tiers ) ) . '</span></div>'
		. '<p class="small muted km-hint">Štiklirajte šta ide uz ' . esc_html( $product->get_name() ) . '. Što više u kompletu, veći popust.</p><div class="km-row km-grid">'
		. '<span class="km-item km-main"><span class="km-img">' . ( $img ? '<img src="' . esc_url( $img ) . '" alt="" width="120" height="120" loading="lazy" />' : '' ) . '</span><span class="km-name">' . esc_html( $product->get_name() ) . '</span><span class="km-tag">ovaj proizvod</span></span>';
	foreach ( $k['uz'] as $i => $x ) {
		$o .= '<label class="km-item km-pick"><input type="checkbox" value="' . (int) $x['pid'] . '" data-km-pick' . ( $i < 2 ? ' checked' : '' ) . ' /><span class="km-img">' . ( $x['slika'] ? '<img src="' . esc_url( $x['slika'] ) . '" alt="" width="120" height="120" loading="lazy" />' : '' ) . '<span class="km-check" aria-hidden="true">' . bumbaru_icon( 'check', 14, 3 ) . '</span></span><span class="km-name">' . esc_html( $x['naziv'] ) . '</span></label>';
	}
	$o .= '</div><p class="mm-msg km-msg" aria-live="polite"><span class="mm-count" data-km-n></span><span class="mm-badge" data-km-pct hidden></span><span data-km-text></span></p>'
		. '<div class="km-foot"><p class="km-cena">Komplet: <s data-km-puna></s> <b data-km-cena></b></p>'
		. '<button class="btn btn-primary" type="button" data-km2-add>' . bumbaru_icon( 'bag' ) . 'Dodaj komplet u korpu</button></div>'
		. ( $vm['opcija'] ? '<p class="small muted">' . esc_html( $vm['opcija'] ) . ' iz izbora iznad važi za ceo komplet.</p>' : '' ) . '</div>';
	return $o;
}

/** Popusti za firme (velike količine): kalkulator i upit na Messenger. */
function bumbaru_firme_html( $product ) {
	$c = bumbaru_config( $product );
	if ( ! $c['firme'] || ! bumbaru_base_price( $product ) ) {
		return '';
	}
	$f = $c['firme'];
	$o = '<div class="firme" data-firme><div class="fm-head"><span class="fm-ic" aria-hidden="true">' . bumbaru_icon( 'box', 22 ) . '</span><div><h2 class="h3">Za firme i veće količine</h2>' . ( $f['tekst'] ? '<p class="small muted">' . esc_html( $f['tekst'] ) . '</p>' : '' ) . '</div></div><ul class="fm-tiers">';
	foreach ( $f['popusti'] as $t ) {
		$o .= '<li><b>−' . esc_html( (float) $t['procenat'] ) . '%</b><span>od ' . (int) $t['od'] . ' kom</span></li>';
	}
	$min = (int) $f['popusti'][0]['od'];
	$o  .= '</ul><div class="fm-calc"><label for="fm-kol">Količina</label><div class="qty fm-qty" role="group" aria-label="Količina za firmu"><button type="button" data-fm-dec aria-label="Manje">−</button>'
		. '<input id="fm-kol" type="number" min="' . $min . '" max="100000" value="' . $min . '" inputmode="numeric" data-fm-kol /><button type="button" data-fm-inc aria-label="Više">+</button></div>'
		. '<p class="fm-out" aria-live="polite"><span data-fm-jed></span><b data-fm-uk></b></p></div>'
		. '<button class="btn btn-lg fm-btn" type="button" data-fm-send>' . bumbaru_icon( 'messenger' ) . 'Pošaljite upit za firmu</button></div>';
	return $o;
}
