<?php
/**
 * Bumbaru 3D → Generator modela: 3MF i STL za štampu iz porudžbine (ukras sa imenom, porodični set).
 *
 * Model se pravi u pregledaču (assets/gen/bumbaru-gen.js): isti crtež motiva kao pregled na sajtu
 * (data/ornament-defs.svg) i slova fonta Pacifico. Ovde su samo strana, podaci porudžbina i podešavanja.
 * Novi model (npr. privezak) se dodaje u BumbaruGen.models u JS-u i u bumbaru_gen_models() ovde.
 */

defined( 'ABSPATH' ) || exit;

/** Modeli koje generator ume da napravi (ključ = BumbaruGen.models u JS-u). */
function bumbaru_gen_models() {
	return apply_filters( 'bumbaru_gen_models', array(
		'ukras' => array( 'naziv' => 'Ukras za jelku sa imenom', 'tipovi' => array( 'ukras', 'set' ) ),
	) );
}

function bumbaru_gen_defaults() {
	$d = array(
		'precnik'   => 80,  // spoljni prečnik obruča, mm
		'debljina'  => 3,   // debljina ukrasa, mm
		'izdignuto' => 0,   // ime izdignuto iznad ukrasa, mm
		'vrh'       => 0,   // druga boja imena samo u gornjih … mm (0 = cela debljina)
		'ploca'     => 250, // širina ploče štampača, mm (raspored više ukrasa)
	);
	$s = get_option( 'bumbaru_gen', array() );
	foreach ( $d as $k => $v ) {
		if ( isset( $s[ $k ] ) && is_numeric( $s[ $k ] ) ) {
			$d[ $k ] = (float) $s[ $k ];
		}
	}
	return $d;
}

/** Ukrasi iz jedne stavke porudžbine: [{ ime, motiv, boja, bojaImena }] (set = više ukrasa, količina = više komada). */
function bumbaru_gen_item_rows( $item ) {
	$raw = $item->get_meta( '_bu_raw' );
	if ( ! is_array( $raw ) || empty( $raw['type'] ) || ! in_array( $raw['type'], array( 'ukras', 'set' ), true ) ) {
		return array();
	}
	$qty   = max( 1, (int) $item->get_quantity() );
	$motiv = isset( $raw['motiv'] ) ? $raw['motiv'] : 'zvezde';
	$nc    = isset( $raw['boja_imena'] ) ? $raw['boja_imena'] : 'ista';
	$rows  = array();
	if ( 'ukras' === $raw['type'] ) {
		$one = array( 'ime' => (string) $raw['ime'], 'motiv' => $motiv, 'boja' => $raw['boja'], 'bojaImena' => $nc );
		$rows = array_fill( 0, $qty, $one );
	} else {
		for ( $q = 0; $q < $qty; $q++ ) {
			foreach ( array_values( (array) $raw['imena'] ) as $i => $ime ) {
				$b      = 'sareno' === $raw['boja'] ? ( ! empty( $raw['sareno'][ $i ] ) ? $raw['sareno'][ $i ] : 'bela' ) : $raw['boja'];
				$rows[] = array( 'ime' => (string) $ime, 'motiv' => $motiv, 'boja' => $b, 'bojaImena' => $nc );
			}
		}
	}
	return $rows;
}

/** Poslednje porudžbine sa ukrasima, za listu u generatoru. */
function bumbaru_gen_orders( $limit = 40 ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}
	$orders = wc_get_orders( array(
		'limit'   => $limit * 2,
		'orderby' => 'date',
		'order'   => 'DESC',
		'status'  => array_keys( wc_get_order_statuses() ),
	) );
	$out = array();
	foreach ( $orders as $o ) {
		if ( ! is_a( $o, 'WC_Order' ) ) {
			continue;
		}
		$items = array();
		foreach ( $o->get_items() as $iid => $it ) {
			$rows = bumbaru_gen_item_rows( $it );
			if ( $rows ) {
				$items[] = array( 'id' => (int) $iid, 'naziv' => $it->get_name(), 'kom' => (int) $it->get_quantity(), 'rows' => $rows );
			}
		}
		if ( ! $items ) {
			continue;
		}
		$out[] = array(
			'id'     => $o->get_id(),
			'broj'   => $o->get_order_number(),
			'kupac'  => trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ),
			'datum'  => $o->get_date_created() ? $o->get_date_created()->date_i18n( 'j. n. Y.' ) : '',
			'status' => wc_get_order_status_name( $o->get_status() ),
			'items'  => $items,
		);
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

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
	add_submenu_page( 'bumbaru-3d', 'Generator modela (3MF)', 'Generator modela', 'manage_woocommerce', 'bumbaru-generator', 'bumbaru_page_generator', 1 );
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
		if ( $o ) {
			$items = array();
			foreach ( $o->get_items() as $iid => $it ) {
				$rows = bumbaru_gen_item_rows( $it );
				if ( $rows ) {
					$items[] = array( 'id' => (int) $iid, 'naziv' => $it->get_name(), 'kom' => (int) $it->get_quantity(), 'rows' => $rows );
				}
			}
			if ( $items ) {
				array_unshift( $orders, array( 'id' => $o->get_id(), 'broj' => $o->get_order_number(), 'kupac' => trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ), 'datum' => '', 'status' => wc_get_order_status_name( $o->get_status() ), 'items' => $items ) );
			}
		}
	}
	wp_localize_script( 'bumbaru-gen-ui', 'BUMBARU_GEN', array(
		'font'     => BUMBARU3D_URL . 'assets/gen/Pacifico-Regular.ttf',
		'defs'     => BUMBARU3D_URL . 'data/ornament-defs.svg?v=' . $v,
		'colors'   => $colors,
		'motivi'   => bumbaru_motivi(),
		'models'   => bumbaru_gen_models(),
		'defaults' => bumbaru_gen_defaults(),
		'orders'   => $orders,
		'sel'      => array( 'order' => $sel_order, 'item' => $sel_item, 'download' => $download ),
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'bumbaru_gen' ),
	) );
	// ornament.js (fitName) čita boje i motive iz BUMBARU3D
	wp_add_inline_script( 'bumbaru-ornament', 'window.BUMBARU3D = window.BUMBARU3D || { colors: {}, motivi: ' . wp_json_encode( bumbaru_motivi() ) . ' };', 'before' );
} );

// Podrazumevane mere (prečnik, debljina…) se pamte za sledeći put.
add_action( 'wp_ajax_bumbaru_gen_defaults', function () {
	check_ajax_referer( 'bumbaru_gen' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( 'Nemate dozvolu.', 403 );
	}
	$out = array();
	$lim = array( 'precnik' => array( 20, 250 ), 'debljina' => array( 0.6, 20 ), 'izdignuto' => array( 0, 10 ), 'vrh' => array( 0, 20 ), 'ploca' => array( 100, 600 ) );
	foreach ( $lim as $k => $l ) {
		$v         = isset( $_POST[ $k ] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) : 0;
		$out[ $k ] = max( $l[0], min( $l[1], $v ) );
	}
	update_option( 'bumbaru_gen', $out, false );
	wp_send_json_success( $out );
} );

function bumbaru_page_generator() {
	bumbaru_admin_head( 'Generator modela', 'Izaberite porudžbinu ili upišite ime: model se napravi odmah, pa ga preuzmete kao 3MF (delovi u bojama) ili STL. Crtež je isti kao pregled koji je kupac video na sajtu.' );
	$d = bumbaru_gen_defaults();
	?>
	<div class="bg-wrap" data-gen>
		<aside class="bu-card bg-orders">
			<h2>Porudžbine sa ukrasima</h2>
			<p class="description">Poslednjih 40. Klik na stavku učita imena, motiv i boje.</p>
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

			<table class="widefat bg-rows">
				<thead><tr><th>#</th><th>Ime (do 12 slova)</th><th>Motiv</th><th>Boja ukrasa</th><th>Boja imena</th><th></th></tr></thead>
				<tbody data-gen-rows></tbody>
			</table>
			<p><button type="button" class="button" data-gen-add>+ Još jedan ukras</button></p>

			<details class="bg-dims" open>
				<summary>Mere za štampu</summary>
				<div class="bg-dims-grid">
					<label>Prečnik ukrasa (mm)<input type="number" step="0.5" min="20" max="250" data-gen-dim="precnik" value="<?php echo esc_attr( $d['precnik'] ); ?>" /></label>
					<label>Debljina (mm)<input type="number" step="0.1" min="0.6" max="20" data-gen-dim="debljina" value="<?php echo esc_attr( $d['debljina'] ); ?>" /></label>
					<label>Ime izdignuto (mm, 0 = ravno)<input type="number" step="0.1" min="0" max="10" data-gen-dim="izdignuto" value="<?php echo esc_attr( $d['izdignuto'] ); ?>" /></label>
					<label>Boja imena samo gornjih (mm, 0 = cela debljina)<input type="number" step="0.1" min="0" max="20" data-gen-dim="vrh" value="<?php echo esc_attr( $d['vrh'] ); ?>" /></label>
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
			<p class="description">3MF: svaki ukras je jedan predmet sa delovima „Telo” i „Ime”, svaki u svojoj boji. U slajseru (Anycubic, Bambu, Orca, Prusa) dodelite filament svakom delu. STL: zip sa posebnim fajlom za svaki deo.</p>
			<p class="bg-err" data-gen-err role="alert"></p>
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
	echo '<p class="bu-gen-links" style="margin:8px 0 0;display:flex;gap:6px;flex-wrap:wrap">'
		. '<a class="button button-small button-primary" target="_blank" href="' . esc_url( bumbaru_gen_url( $oid, $item_id, true ) ) . '">Preuzmi 3MF</a>'
		. '<a class="button button-small" target="_blank" href="' . esc_url( bumbaru_gen_url( $oid, $item_id ) ) . '">Otvori u generatoru</a></p>';
}, 20, 2 );
