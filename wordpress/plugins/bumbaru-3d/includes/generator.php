<?php
/**
 * Bumbaru 3D → Generator modela: 3MF i STL za štampu iz porudžbine (ukras sa imenom, porodični set, privezak sa imenom).
 *
 * Model se pravi u pregledaču (assets/gen/bumbaru-gen.js): isti crtež motiva kao pregled na sajtu
 * (data/ornament-defs.svg) i slova izabranog fonta (bumbaru_fontovi()). Ovde su samo strana, podaci porudžbina i podešavanja.
 * Novi model (npr. privezak) se dodaje u BumbaruGen.models u JS-u i u bumbaru_gen_models() ovde.
 */

defined( 'ABSPATH' ) || exit;

/** Modeli koje generator ume da napravi (ključ = BumbaruGen.models u JS-u). */
function bumbaru_gen_models() {
	return apply_filters( 'bumbaru_gen_models', array(
		'ukras'    => array( 'naziv' => 'Ukras za jelku sa imenom', 'tipovi' => array( 'ukras', 'set' ) ),
		'privezak' => array( 'naziv' => 'Privezak sa imenom', 'tipovi' => array( 'privezak' ) ),
	) );
}

function bumbaru_gen_defaults() {
	$d = array(
		'precnik'   => 80,  // spoljni prečnik obruča, mm
		'debljina'  => 3,   // debljina ukrasa, mm
		'izdignuto' => 0.6, // ime kao reljef iznad ukrasa, mm (druga boja samo u reljefu = jedna promena boje)
		'vrh'       => 0,   // druga boja imena samo u gornjih … mm (0 = cela debljina)
		'ploca'     => 250, // širina ploče štampača, mm (raspored više ukrasa)
		'kc_vel'    => 20,  // privezak: veličina slova, mm (visina priveska je oko 1,2 × ovo)
		'kc_duz'    => 80,  // privezak: najveća dužina sa ušicom, mm (duža imena se smanje)
		'kc_osnova' => 2,   // privezak: debljina osnove, mm
		'kc_sloj'   => 0.8, // privezak: debljina okvira, mm (tanka ivica je pola od toga)
		'kc_slova'  => 1,   // privezak: visina slova iznad, mm
	);
	$s = get_option( 'bumbaru_gen', array() );
	foreach ( $d as $k => $v ) {
		if ( isset( $s[ $k ] ) && is_numeric( $s[ $k ] ) ) {
			$d[ $k ] = (float) $s[ $k ];
		}
	}
	return $d;
}

/** Boja po nazivu („roze”) → id, za starije porudžbine privezaka u kojima je boja samo u tekstu. */
function bumbaru_gen_color_by_label( $label ) {
	$label = mb_strtolower( trim( (string) $label ), 'UTF-8' );
	foreach ( bumbaru_colors() as $c ) {
		if ( mb_strtolower( $c['label'], 'UTF-8' ) === $label ) {
			return $c['id'];
		}
	}
	return '';
}

/** Privesci iz stavke: jedan red po imenu [{ model, ime, stil, slova, okvir, osnova }]. */
function bumbaru_gen_kc_rows( $item, $raw ) {
	$sid   = isset( $raw['stil'] ) ? (string) $raw['stil'] : 'pisana';
	$st    = function_exists( 'bumbaru_kc_style' ) ? bumbaru_kc_style( $sid ) : null;
	$sl    = isset( $raw['slojevi'] ) && is_array( $raw['slojevi'] ) ? $raw['slojevi'] : array();
	$def   = $st && isset( $st['def'] ) ? $st['def'] : array( 'slova' => 'roze', 'okvir' => 'bela', 'osnova' => 'bela' );
	$sar   = $st && ! empty( $st['sareno'] ) ? $st['sareno'] : 'slova';
	$names = array_values( (array) ( isset( $raw['imena'] ) ? $raw['imena'] : array() ) );
	$per   = isset( $raw['sareno_boje'] ) ? array_values( (array) $raw['sareno_boje'] ) : array();
	$isSar = false;
	foreach ( $sl as $v ) {
		$isSar = $isSar || 'sareno' === $v;
	}
	if ( $isSar && ! $per ) {
		// starije porudžbine: „Milica (roze), Luka (plava)” u opisu stavke
		foreach ( $item->get_meta_data() as $m ) {
			$d = $m->get_data();
			if ( 0 === strpos( (string) $d['key'], 'Imena i boja' ) ) {
				foreach ( explode( ',', (string) $d['value'] ) as $i => $part ) {
					$per[ $i ] = preg_match( '/\(([^)]+)\)\s*$/u', $part, $mm ) ? bumbaru_gen_color_by_label( $mm[1] ) : '';
				}
			}
		}
		if ( ! array_filter( $per ) && function_exists( 'bumbaru_sareno' ) ) {
			$per = bumbaru_sareno();
		}
	}
	$qty  = max( 1, (int) $item->get_quantity() );
	$rows = array();
	for ( $q = 0; $q < $qty; $q++ ) {
		foreach ( $names as $i => $ime ) {
			$r = array( 'model' => 'privezak', 'ime' => (string) $ime, 'stil' => $sid );
			foreach ( array( 'slova', 'okvir', 'osnova' ) as $L ) {
				$v = isset( $sl[ $L ] ) ? (string) $sl[ $L ] : '';
				if ( 'sareno' === $v ) {
					$v = ! empty( $per[ $i % max( 1, count( $per ) ) ] ) ? $per[ $i % max( 1, count( $per ) ) ] : '';
				}
				$r[ $L ] = $v ? $v : $def[ $L ];
			}
			$rows[] = $r;
		}
	}
	return $rows;
}

/** Predmeti iz jedne stavke porudžbine: ukrasi [{ ime, motiv, boja, bojaImena }] (set = više ukrasa, količina = više komada) ili privesci. */
function bumbaru_gen_item_rows( $item ) {
	$raw = $item->get_meta( '_bu_raw' );
	if ( is_array( $raw ) && isset( $raw['type'] ) && 'privezak' === $raw['type'] ) {
		return bumbaru_gen_kc_rows( $item, $raw );
	}
	if ( ! is_array( $raw ) || empty( $raw['type'] ) || ! in_array( $raw['type'], array( 'ukras', 'set' ), true ) ) {
		return array();
	}
	$qty   = max( 1, (int) $item->get_quantity() );
	$motiv = isset( $raw['motiv'] ) ? $raw['motiv'] : 'zvezde';
	$nc    = isset( $raw['boja_imena'] ) ? $raw['boja_imena'] : 'ista';
	$font  = isset( $raw['font'] ) ? $raw['font'] : 'pacifico';
	$rows  = array();
	if ( 'ukras' === $raw['type'] ) {
		$one = array( 'model' => 'ukras', 'ime' => (string) $raw['ime'], 'motiv' => $motiv, 'boja' => $raw['boja'], 'bojaImena' => $nc, 'font' => $font );
		$rows = array_fill( 0, $qty, $one );
	} else {
		for ( $q = 0; $q < $qty; $q++ ) {
			foreach ( array_values( (array) $raw['imena'] ) as $i => $ime ) {
				$b      = 'sareno' === $raw['boja'] ? ( ! empty( $raw['sareno'][ $i ] ) ? $raw['sareno'][ $i ] : 'bela' ) : $raw['boja'];
				$rows[] = array( 'model' => 'ukras', 'ime' => (string) $ime, 'motiv' => $motiv, 'boja' => $b, 'bojaImena' => $nc, 'font' => $font );
			}
		}
	}
	return $rows;
}

/** Fontovi za priveske (TTF u assets/gen): stil → [glavni, rezervni za slova koja glavni nema, npr. ćirilicu]. */
function bumbaru_gen_kc_fonts() {
	$u   = BUMBARU3D_URL . 'assets/gen/';
	$lob = $u . 'Lobster-Regular.ttf';
	return array(
		'pisana'   => array( $u . 'Playball-Regular.ttf', $lob ),
		'bajka'    => array( $u . 'Mogra-Regular.ttf', $lob ),
		'kockice'  => array( $u . 'Nunito-BlackItalic.ttf', $lob ),
		'pikseli'  => array( $u . 'BumbaruMine.ttf', $u . 'BumbaruPiksel.ttf', $lob ),
		'stampana' => array( $u . 'Montserrat-ExtraBold.ttf', $lob ),
	);
}

/** Statusi porudžbina koje čekaju izradu (ulaze u „Za štampu” i u brojač u meniju). */
function bumbaru_gen_active_statuses() {
	return apply_filters( 'bumbaru_gen_active_statuses', array( 'pending', 'on-hold', 'processing' ) );
}

/** Jedna porudžbina za generator: stavke sa ukrasima, sa oznakom kad je model skinut. */
function bumbaru_gen_order_data( $o ) {
	$items = array();
	foreach ( $o->get_items() as $iid => $it ) {
		$rows = bumbaru_gen_item_rows( $it );
		if ( $rows ) {
			$sk      = (string) $it->get_meta( '_bu_gen_skinuto' );
			$items[] = array(
				'id'      => (int) $iid,
				'naziv'   => $it->get_name(),
				'kom'     => (int) $it->get_quantity(),
				'rows'    => $rows,
				'skinuto' => $sk ? mysql2date( 'j. n. \u H:i', $sk ) : '',
			);
		}
	}
	if ( ! $items ) {
		return null;
	}
	return array(
		'id'      => $o->get_id(),
		'broj'    => $o->get_order_number(),
		'kupac'   => trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ),
		'datum'   => $o->get_date_created() ? $o->get_date_created()->date_i18n( 'j. n. Y. \u H:i' ) : '',
		'status'  => wc_get_order_status_name( $o->get_status() ),
		'aktivna' => in_array( $o->get_status(), bumbaru_gen_active_statuses(), true ),
		'items'   => $items,
	);
}

/** Poslednje porudžbine sa ukrasima, za listu u generatoru (bez otkazanih, vraćenih i neuspelih). */
function bumbaru_gen_orders( $limit = 60 ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}
	$statuses = array_diff( array_map( function ( $s ) {
		return 0 === strpos( $s, 'wc-' ) ? substr( $s, 3 ) : $s;
	}, array_keys( wc_get_order_statuses() ) ), array( 'cancelled', 'refunded', 'failed', 'checkout-draft' ) );
	$orders = wc_get_orders( array(
		'limit'   => $limit * 2,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => array_values( $statuses ),
	) );
	$out = array();
	foreach ( $orders as $o ) {
		if ( ! is_a( $o, 'WC_Order' ) ) {
			continue;
		}
		$d = bumbaru_gen_order_data( $o );
		if ( $d ) {
			$out[] = $d;
		}
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

/** Koliko ukrasa čeka štampu (stavke u aktivnim porudžbinama čiji model još nije skinut). Kešira se. */
function bumbaru_gen_pending_count() {
	$n = get_transient( 'bumbaru_gen_pending' );
	if ( false !== $n ) {
		return (int) $n;
	}
	$n = 0;
	if ( function_exists( 'wc_get_orders' ) ) {
		foreach ( wc_get_orders( array( 'limit' => 200, 'status' => bumbaru_gen_active_statuses(), 'orderby' => 'date', 'order' => 'DESC' ) ) as $o ) {
			if ( ! is_a( $o, 'WC_Order' ) ) {
				continue;
			}
			foreach ( $o->get_items() as $it ) {
				if ( ! $it->get_meta( '_bu_gen_skinuto' ) ) {
					$n += count( bumbaru_gen_item_rows( $it ) );
				}
			}
		}
	}
	set_transient( 'bumbaru_gen_pending', $n, HOUR_IN_SECONDS );
	return $n;
}

function bumbaru_gen_flush_count() {
	delete_transient( 'bumbaru_gen_pending' );
}
add_action( 'woocommerce_new_order', 'bumbaru_gen_flush_count' );
add_action( 'woocommerce_checkout_order_processed', 'bumbaru_gen_flush_count' );
add_action( 'woocommerce_order_status_changed', 'bumbaru_gen_flush_count' );
add_action( 'woocommerce_update_order', 'bumbaru_gen_flush_count' );

// Model je skinut: oznaka na stavkama i beleška u porudžbini (status porudžbine se ne menja).
add_action( 'wp_ajax_bumbaru_gen_mark', function () {
	check_ajax_referer( 'bumbaru_gen' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Nemate dozvolu.', 403 );
	}
	$pairs = isset( $_POST['stavke'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['stavke'] ) ) : array();
	$by    = array();
	foreach ( $pairs as $p ) {
		if ( preg_match( '/^(\d+):(\d+)$/', $p, $m ) ) {
			$by[ (int) $m[1] ][] = (int) $m[2];
		}
	}
	$now  = current_time( 'mysql' );
	$done = array();
	foreach ( $by as $oid => $iids ) {
		$o = wc_get_order( $oid );
		if ( ! $o ) {
			continue;
		}
		$names = array();
		foreach ( $iids as $iid ) {
			$it = $o->get_item( $iid );
			if ( ! $it || ! bumbaru_gen_item_rows( $it ) ) {
				continue;
			}
			$first = ! $it->get_meta( '_bu_gen_skinuto' );
			$it->update_meta_data( '_bu_gen_skinuto', $now );
			$it->save();
			if ( $first ) {
				$names = array_merge( $names, wp_list_pluck( bumbaru_gen_item_rows( $it ), 'ime' ) );
			}
			$done[] = $oid . ':' . $iid;
		}
		if ( $names ) {
			$o->add_order_note( '3D model preuzet iz generatora: ' . implode( ', ', $names ) . '.' );
		}
	}
	bumbaru_gen_flush_count();
	wp_send_json_success( array( 'stavke' => $done, 'skinuto' => mysql2date( 'j. n. \u H:i', $now ), 'ceka' => bumbaru_gen_pending_count() ) );
} );

function bumbaru_gen_url( $order_id = 0, $item_id = 0, $download = false ) {
	$args = array( 'page' => 'bumbaru-generator' );
	if ( $order_id ) {
		$args['porudzbina'] = (int) $order_id;
	}
	if ( $item_id ) {
		$args['stavka'] = (int) $item_id;
	}
	if ( $download ) {
		$args['preuzmi'] = '3mf';
	}
	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

add_action( 'admin_menu', function () {
	global $menu;
	$n      = current_user_can( 'manage_woocommerce' ) ? bumbaru_gen_pending_count() : 0;
	$bubble = $n ? ' <span class="awaiting-mod count-' . $n . '"><span class="bu-gen-count">' . $n . '</span></span>' : '';
	add_submenu_page( 'bumbaru-3d', 'Generator modela (3MF)', 'Generator modela' . $bubble, 'manage_woocommerce', 'bumbaru-generator', 'bumbaru_page_generator', 1 );
	// Broj ukrasa koji čekaju štampu vidi se i na glavnom meniju „Bumbaru 3D”.
	if ( $n && is_array( $menu ) ) {
		foreach ( $menu as $k => $m ) {
			if ( isset( $m[2] ) && 'bumbaru-3d' === $m[2] ) {
				$menu[ $k ][0] .= $bubble; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}
	}
}, 20 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false === strpos( (string) $hook, 'bumbaru-generator' ) ) {
		return;
	}
	$v = BUMBARU3D_VERSION;
	wp_enqueue_script( 'bumbaru-opentype', BUMBARU3D_URL . 'assets/gen/vendor/opentype.min.js', array(), '1.3.4', true );
	wp_enqueue_script( 'bumbaru-clipper', BUMBARU3D_URL . 'assets/gen/vendor/clipper.js', array(), '6.4.2', true );
	wp_enqueue_script( 'bumbaru-earcut', BUMBARU3D_URL . 'assets/gen/vendor/earcut.min.js', array(), '2.2.4', true );
	wp_enqueue_script( 'bumbaru-ornament', BUMBARU3D_URL . 'assets/ornament.js', array(), $v, true );
	wp_enqueue_script( 'bumbaru-gen', BUMBARU3D_URL . 'assets/gen/bumbaru-gen.js', array( 'bumbaru-clipper', 'bumbaru-earcut' ), $v, true );
	wp_enqueue_script( 'bumbaru-gen-ui', BUMBARU3D_URL . 'assets/gen/generator-ui.js', array( 'bumbaru-gen', 'bumbaru-opentype', 'bumbaru-ornament' ), $v, true );
	wp_enqueue_style( 'bumbaru-gen-ui', BUMBARU3D_URL . 'assets/gen/generator.css', array(), $v );

	$colors = array();
	foreach ( bumbaru_colors() as $c ) {
		$colors[] = array( 'id' => $c['id'], 'naziv' => $c['label'], 'hex' => $c['face'] );
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$sel_order = isset( $_GET['porudzbina'] ) ? absint( $_GET['porudzbina'] ) : 0;
	$sel_item  = isset( $_GET['stavka'] ) ? absint( $_GET['stavka'] ) : 0;
	$download  = isset( $_GET['preuzmi'] ) ? sanitize_key( $_GET['preuzmi'] ) : '';
	// phpcs:enable
	$orders = bumbaru_gen_orders();
	// Porudžbina iz linka može biti starija od liste: dodaj je.
	if ( $sel_order && ! in_array( $sel_order, wp_list_pluck( $orders, 'id' ), true ) ) {
		$o = wc_get_order( $sel_order );
		$d = $o ? bumbaru_gen_order_data( $o ) : null;
		if ( $d ) {
			array_unshift( $orders, $d );
		}
	}
	wp_localize_script( 'bumbaru-gen-ui', 'BUMBARU_GEN', array(
		'font'     => BUMBARU3D_URL . 'assets/gen/Pacifico-Regular.ttf',
		'fonts'    => array_combine( wp_list_pluck( bumbaru_fontovi(), 'id' ), array_map( function ( $f ) {
			return BUMBARU3D_URL . 'assets/gen/' . $f['ttf'];
		}, bumbaru_fontovi() ) ),
		'fontovi'  => bumbaru_fontovi(),
		'stilovi'  => bumbaru_astro_data( 'STILOVI' ),
		'kcFonts'  => bumbaru_gen_kc_fonts(),
		'defs'     => BUMBARU3D_URL . 'data/ornament-defs.svg?v=' . $v,
		'colors'   => $colors,
		'motivi'   => bumbaru_motivi(),
		'models'   => bumbaru_gen_models(),
		'defaults' => bumbaru_gen_defaults(),
		'orders'   => $orders,
		'sel'      => array( 'order' => $sel_order, 'item' => $sel_item, 'download' => $download ),
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'bumbaru_gen' ),
		'ceka'     => bumbaru_gen_pending_count(),
	) );
	// ornament.js (fitName) čita boje i motive iz BUMBARU3D
	wp_add_inline_script( 'bumbaru-ornament', 'window.BUMBARU3D = window.BUMBARU3D || { colors: {}, motivi: ' . wp_json_encode( bumbaru_motivi() ) . ', fontovi: ' . wp_json_encode( bumbaru_fontovi() ) . ', fm: ' . wp_json_encode( bumbaru_font_metrics() ) . ' };', 'before' );
} );

// Podrazumevane mere (prečnik, debljina…) se pamte za sledeći put.
add_action( 'wp_ajax_bumbaru_gen_defaults', function () {
	check_ajax_referer( 'bumbaru_gen' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Nemate dozvolu.', 403 );
	}
	$out = array();
	$lim = array( 'precnik' => array( 20, 250 ), 'debljina' => array( 0.6, 20 ), 'izdignuto' => array( 0, 10 ), 'vrh' => array( 0, 20 ), 'ploca' => array( 100, 600 ),
		'kc_vel' => array( 8, 60 ), 'kc_duz' => array( 0, 250 ), 'kc_osnova' => array( 0.6, 10 ), 'kc_sloj' => array( 0.2, 5 ), 'kc_slova' => array( 0.2, 5 ) );
	foreach ( $lim as $k => $l ) {
		$v         = isset( $_POST[ $k ] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) : 0;
		$out[ $k ] = max( $l[0], min( $l[1], $v ) );
	}
	update_option( 'bumbaru_gen', $out, false );
	wp_send_json_success( $out );
} );

function bumbaru_page_generator() {
	bumbaru_admin_head( 'Generator modela', 'Izaberite porudžbinu ili upišite ime: model ukrasa ili priveska se napravi odmah, pa ga preuzmete kao 3MF (delovi u bojama) ili STL. Crtež je isti kao pregled koji je kupac video na sajtu.' );
	$d = bumbaru_gen_defaults();
	?>
	<div class="bg-wrap" data-gen>
		<aside class="bu-card bg-orders">
			<h2>Porudžbine sa ukrasima i privescima</h2>
			<p class="description">Nove porudžbine se pojave ovde same, sa imenima, motivom (ili stilom slova) i bojama. „Skini 3MF” odmah preuzme model; „Otvori” ga učita ispod da ga pregledate ili izmenite.</p>
			<div class="bg-tabs" role="tablist">
				<button type="button" class="bg-tab is-on" data-gen-tab="nove" role="tab">Za štampu <span data-gen-ceka></span></button>
				<button type="button" class="bg-tab" data-gen-tab="sve" role="tab">Sve</button>
			</div>
			<p data-gen-allnew hidden><button type="button" class="button button-primary bg-allnew" data-gen-skini-sve></button></p>
			<div data-gen-orders><p>Učitavam…</p></div>
		</aside>
		<section class="bu-card bg-main">
			<div class="bg-head">
				<label>Model
					<select data-gen-model>
						<?php foreach ( bumbaru_gen_models() as $k => $m ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $m['naziv'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<span class="bg-src" data-gen-src></span>
			</div>

			<div data-gen-sec="ukras">
				<h3 class="bg-sec-t">Ukrasi za jelku</h3>
				<table class="widefat bg-rows">
					<thead><tr><th>#</th><th>Ime (do 12 slova)</th><th>Motiv</th><th>Slova</th><th>Boja ukrasa</th><th>Boja imena</th><th></th></tr></thead>
					<tbody data-gen-rows></tbody>
				</table>
				<p><button type="button" class="button" data-gen-add>+ Još jedan ukras</button></p>
			</div>
			<div data-gen-sec="privezak" hidden>
				<h3 class="bg-sec-t">Privesci</h3>
				<table class="widefat bg-rows">
					<thead><tr><th>#</th><th>Ime</th><th>Stil slova</th><th>Boja slova</th><th>Boja okvira</th><th>Boja osnove</th><th></th></tr></thead>
					<tbody data-gen-kc-rows></tbody>
				</table>
				<p><button type="button" class="button" data-gen-kc-add>+ Još jedan privezak</button></p>
			</div>

			<details class="bg-dims" open>
				<summary>Mere za štampu</summary>
				<div class="bg-dims-grid">
					<label>Prečnik ukrasa (mm)<input type="number" step="0.5" min="20" max="250" data-gen-dim="precnik" value="<?php echo esc_attr( $d['precnik'] ); ?>" /></label>
					<label>Debljina (mm)<input type="number" step="0.1" min="0.6" max="20" data-gen-dim="debljina" value="<?php echo esc_attr( $d['debljina'] ); ?>" /></label>
					<label>Ime kao reljef iznad ukrasa (mm, 0 = ravno u ukrasu; reljef = samo jedna promena boje)<input type="number" step="0.1" min="0" max="10" data-gen-dim="izdignuto" value="<?php echo esc_attr( $d['izdignuto'] ); ?>" /></label>
					<label>Ravno ime: boja imena samo gornjih (mm, 0 = cela debljina)<input type="number" step="0.1" min="0" max="20" data-gen-dim="vrh" value="<?php echo esc_attr( $d['vrh'] ); ?>" /></label>
					<label data-kc-dim>Privezak: veličina slova (mm; privezak je visok oko 1,2 × ovo)<input type="number" step="0.5" min="8" max="60" data-gen-dim="kc_vel" value="<?php echo esc_attr( $d['kc_vel'] ); ?>" /></label>
					<label data-kc-dim>Privezak: najveća dužina sa ušicom (mm; duža imena se smanje, 0 = bez ograničenja)<input type="number" step="1" min="0" max="250" data-gen-dim="kc_duz" value="<?php echo esc_attr( $d['kc_duz'] ); ?>" /></label>
					<label data-kc-dim>Privezak: debljina osnove (mm)<input type="number" step="0.1" min="0.6" max="10" data-gen-dim="kc_osnova" value="<?php echo esc_attr( $d['kc_osnova'] ); ?>" /></label>
					<label data-kc-dim>Privezak: debljina okvira (mm; tanka ivica je pola)<input type="number" step="0.1" min="0.2" max="5" data-gen-dim="kc_sloj" value="<?php echo esc_attr( $d['kc_sloj'] ); ?>" /></label>
					<label data-kc-dim>Privezak: slova iznad (mm)<input type="number" step="0.1" min="0.2" max="5" data-gen-dim="kc_slova" value="<?php echo esc_attr( $d['kc_slova'] ); ?>" /></label>
					<label>Ploča štampača (mm)<input type="number" step="1" min="100" max="600" data-gen-dim="ploca" value="<?php echo esc_attr( $d['ploca'] ); ?>" /></label>
				</div>
				<p><button type="button" class="button" data-gen-save>Zapamti ove mere</button> <span class="description" data-gen-saved></span></p>
			</details>

			<div class="bg-preview" data-gen-preview><p>Učitavam font i motive…</p></div>
			<p class="bg-stats" data-gen-stats></p>
			<p class="bg-actions">
				<button type="button" class="button button-primary button-hero" data-gen-3mf disabled>Izvezi 3MF</button>
				<button type="button" class="button button-hero" data-gen-stl disabled>Izvezi STL</button>
			</p>
			<p class="description">3MF: svaki ukras je jedan predmet sa delovima „Telo” i „Ime”, svaki u svojoj boji. Privezak ima delove „Osnova”, „Okvir” i „Slova” jedan iznad drugog: dovoljna je promena boje na visini svakog dela (vidi napomenu ispod pregleda). U slajseru (Anycubic, Bambu, Orca, Prusa) dodelite filament svakom delu. STL: zip sa posebnim fajlom za svaki deo.</p>
			<p class="bg-err" data-gen-err role="alert"></p>
			<p class="description" data-gen-nap></p>
		</section>
	</div>
	</div>
	<?php
}

// U porudžbini: dugmad uz svaku stavku sa ukrasom.
add_action( 'woocommerce_after_order_itemmeta', function ( $item_id, $item ) {
	if ( ! is_callable( array( $item, 'get_meta' ) ) || ! current_user_can( 'manage_woocommerce' ) || ! bumbaru_gen_item_rows( $item ) ) {
		return;
	}
	$oid = $item->get_order_id();
	$sk  = (string) $item->get_meta( '_bu_gen_skinuto' );
	echo '<p class="bu-gen-links" style="margin:8px 0 0;display:flex;gap:6px;flex-wrap:wrap;align-items:center">'
		. ( $sk ? '<span style="background:#e3efe5;color:#2f5b3b;font-weight:700;font-size:11px;padding:2px 8px;border-radius:999px">3D model skinut ' . esc_html( mysql2date( 'j. n. \\u H:i', $sk ) ) . '</span>' : '<span style="background:#F5C137;color:#312018;font-weight:700;font-size:11px;padding:2px 8px;border-radius:999px">Model još nije skinut</span>' )
		. '<a class="button button-small button-primary" target="_blank" href="' . esc_url( bumbaru_gen_url( $oid, $item_id, true ) ) . '">Preuzmi 3MF</a>'
		. '<a class="button button-small" target="_blank" href="' . esc_url( bumbaru_gen_url( $oid, $item_id ) ) . '">Otvori u generatoru</a></p>';
}, 20, 2 );
