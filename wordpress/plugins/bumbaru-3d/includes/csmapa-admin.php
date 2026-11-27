<?php
/**
 * CS2 mapa u ramu, administracija: strana „Štampaj podlogu A4” (ista podloga kao pregled na sajtu, vektorski SVG
 * u punoj A4 veličini, @page A4 bez margina) i dugme uz stavku u porudžbini i u „Za štampu” (Generator modela).
 *
 * Uz to „Skini 3MF mape”: gotov 3MF izabrane mape (assets/cs/3mf/<mapa>.3mf, folder je zaključan .htaccess-om),
 * samo za administratora, preko admin-post.php?action=bumbaru_cs_3mf. Stavka je „gotova” kad su skinuti i 3MF i podloga.
 *
 * Avatar igrača: ako je kupac upisao Steam link, link se vidi na strani; sliku administrator učita sa računara
 * (file input samo u pregledaču, ništa se ne čuva na serveru) pre štampe.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_cs_stampa_url( $order_id = 0, $item_id = 0 ) {
	$args = array( 'action' => 'bumbaru_cs_podloga' );
	if ( $order_id ) {
		$args['porudzbina'] = (int) $order_id;
	}
	if ( $item_id ) {
		$args['stavka'] = (int) $item_id;
	}
	return add_query_arg( $args, admin_url( 'admin-post.php' ) );
}

function bumbaru_cs_3mf_url( $order_id = 0, $item_id = 0, $mapa = '' ) {
	$args = array( 'action' => 'bumbaru_cs_3mf' );
	if ( $order_id && $item_id ) {
		$args['porudzbina'] = (int) $order_id;
		$args['stavka']     = (int) $item_id;
	} elseif ( $mapa ) {
		$args['mapa'] = sanitize_key( $mapa );
	}
	return add_query_arg( $args, admin_url( 'admin-post.php' ) );
}

/** Fajl 3MF mape (ili '' ako ga nema u pluginu). */
function bumbaru_cs_3mf_file( $mapa ) {
	$f = BUMBARU3D_DIR . 'assets/cs/3mf/' . sanitize_key( $mapa ) . '.3mf';
	return file_exists( $f ) ? $f : '';
}

/** Stanje stavke: kada je skinut 3MF mape i kada je otvorena podloga za štampu (prazno = još nije). */
function bumbaru_cs_status( $item ) {
	return array(
		'3mf'     => (string) $item->get_meta( '_bu_cs_3mf' ),
		'podloga' => (string) $item->get_meta( '_bu_gen_skinuto' ),
	);
}

/** Stavka čeka štampu dok nisu skinuti i 3MF mape i podloga. */
function bumbaru_cs_ceka( $item ) {
	$s = bumbaru_cs_status( $item );
	return ! $s['3mf'] || ! $s['podloga'];
}

/** Izbor iz stavke porudžbine (ili null ako stavka nije CS2 mapa). */
function bumbaru_cs_item_raw( $item ) {
	if ( ! is_object( $item ) || ! is_callable( array( $item, 'get_meta' ) ) ) {
		return null;
	}
	$raw = $item->get_meta( '_bu_raw' );
	return is_array( $raw ) && isset( $raw['type'] ) && 'csmapa' === $raw['type'] ? $raw : null;
}

/** Kratak opis stavke: „Dust II · MVP · EN · duletr”. */
function bumbaru_cs_opis( $raw ) {
	$m = bumbaru_cs_mape();
	return implode( ' · ', array_filter( array(
		isset( $m[ $raw['mapa'] ] ) ? $m[ $raw['mapa'] ]['naziv'] : $raw['mapa'],
		bumbaru_cs_modeli()[ 'info' === $raw['model'] ? 'info' : 'mvp' ],
		strtoupper( $raw['jezik'] ),
		$raw['ime'],
	) ) );
}

// U porudžbini: uz stavku sa CS2 mapom „Skini 3MF mape”, „Štampaj podlogu A4” i „Skini A4 (PNG)”.
add_action( 'woocommerce_after_order_itemmeta', function ( $item_id, $item ) {
	$raw = bumbaru_cs_item_raw( $item );
	if ( ! $raw || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$st   = bumbaru_cs_status( $item );
	$oid  = $item->get_order_id();
	$znak = function ( $kad, $da, $ne ) {
		return $kad
			? '<span style="background:#e3efe5;color:#2f5b3b;font-weight:700;font-size:11px;padding:2px 8px;border-radius:999px">' . esc_html( $da . ' ' . mysql2date( 'j. n. \\u H:i', $kad ) ) . '</span>'
			: '<span style="background:#F5C137;color:#312018;font-weight:700;font-size:11px;padding:2px 8px;border-radius:999px">' . esc_html( $ne ) . '</span>';
	};
	echo '<p class="bu-gen-links" style="margin:8px 0 0;display:flex;gap:6px;flex-wrap:wrap;align-items:center">'
		. $znak( $st['3mf'], '3MF mape skinut', '3MF mape još nije skinut' ) // phpcs:ignore
		. $znak( $st['podloga'], 'Podloga otvorena', 'Podloga još nije štampana' ) // phpcs:ignore
		. '</p><p class="bu-gen-links" style="margin:6px 0 0;display:flex;gap:6px;flex-wrap:wrap;align-items:center">'
		. ( bumbaru_cs_3mf_file( $raw['mapa'] ) ? '<a class="button button-small button-primary" href="' . esc_url( bumbaru_cs_3mf_url( $oid, $item_id ) ) . '">Skini 3MF mape</a>' : '<span class="description">3MF ove mape nije u pluginu.</span>' )
		. '<a class="button button-small button-primary" target="_blank" href="' . esc_url( bumbaru_cs_stampa_url( $oid, $item_id ) ) . '">Štampaj podlogu A4</a>'
		. '<a class="button button-small" target="_blank" href="' . esc_url( add_query_arg( 'preuzmi', 'png', bumbaru_cs_stampa_url( $oid, $item_id ) ) ) . '">Skini A4 (PNG 300 dpi)</a>'
		. ( ! empty( $raw['steam'] ) ? '<a class="button button-small" target="_blank" rel="noopener noreferrer" href="' . esc_url( $raw['steam'] ) . '">Steam profil</a>' : '' )
		. '</p>';
}, 20, 2 );

// 3MF mape: samo za administratora; ime fajla „PK-02-<porudžbina>-<mapa>.3mf”, oznaka i beleška u porudžbini.
add_action( 'admin_post_bumbaru_cs_3mf', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.', 403 );
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- samo preuzimanje, bez izmene podataka kupca
	$oid  = isset( $_GET['porudzbina'] ) ? absint( $_GET['porudzbina'] ) : 0;
	$iid  = isset( $_GET['stavka'] ) ? absint( $_GET['stavka'] ) : 0;
	$mapa = isset( $_GET['mapa'] ) ? sanitize_key( wp_unslash( $_GET['mapa'] ) ) : '';
	// phpcs:enable
	$ime = 'PK-02-' . $mapa;
	if ( $oid && $iid && function_exists( 'wc_get_order' ) ) {
		$o   = wc_get_order( $oid );
		$it  = $o ? $o->get_item( $iid ) : null;
		$raw = $it ? bumbaru_cs_item_raw( $it ) : null;
		if ( ! $raw ) {
			wp_die( 'Stavka nije CS2 mapa.', 404 );
		}
		$mapa = $raw['mapa'];
		$ime  = 'PK-02-' . $o->get_order_number() . '-' . $mapa;
		if ( bumbaru_cs_3mf_file( $mapa ) && ! $it->get_meta( '_bu_cs_3mf' ) ) {
			$it->update_meta_data( '_bu_cs_3mf', current_time( 'mysql' ) );
			$it->save();
			$o->add_order_note( '3MF mape preuzet: ' . bumbaru_cs_opis( $raw ) . '.' );
			if ( function_exists( 'bumbaru_gen_flush_count' ) ) {
				bumbaru_gen_flush_count();
			}
		}
	}
	$f = bumbaru_cs_3mf_file( $mapa );
	if ( ! $f ) {
		wp_die( '3MF za ovu mapu nije u pluginu (assets/cs/3mf/). Pokrenite scripts/cs-mape.mjs i otpremite plugin.', 404 );
	}
	nocache_headers();
	header( 'Content-Type: model/3mf' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $ime ) . '.3mf"' );
	header( 'Content-Length: ' . filesize( $f ) );
	readfile( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
	exit;
} );

// Strana za štampu (samo za administratore prodavnice).
add_action( 'admin_post_bumbaru_cs_podloga', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- samo čitanje
	$oid = isset( $_GET['porudzbina'] ) ? absint( $_GET['porudzbina'] ) : 0;
	$iid = isset( $_GET['stavka'] ) ? absint( $_GET['stavka'] ) : 0;
	// phpcs:enable
	$raw  = array( 'mapa' => 'dust2', 'model' => 'mvp', 'jezik' => 'en', 'ime' => '', 'steam' => '' );
	$naslov = 'Podloga A4 (bez porudžbine)';
	$fajl   = 'PK-02';
	if ( $oid && $iid && function_exists( 'wc_get_order' ) ) {
		$o  = wc_get_order( $oid );
		$it = $o ? $o->get_item( $iid ) : null;
		$r  = $it ? bumbaru_cs_item_raw( $it ) : null;
		if ( $r ) {
			$raw    = array_merge( $raw, $r );
			$fajl   = 'PK-02-' . $o->get_order_number();
			$naslov = 'Porudžbina #' . $o->get_order_number() . ' · ' . trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ) . ' · ' . bumbaru_cs_opis( $raw ) . ( $it->get_quantity() > 1 ? ' · ' . (int) $it->get_quantity() . ' kom' : '' );
			if ( ! $it->get_meta( '_bu_gen_skinuto' ) ) {
				$it->update_meta_data( '_bu_gen_skinuto', current_time( 'mysql' ) );
				$it->save();
				$o->add_order_note( 'Podloga A4 otvorena za štampu: ' . bumbaru_cs_opis( $raw ) . '.' );
				if ( function_exists( 'bumbaru_gen_flush_count' ) ) {
					bumbaru_gen_flush_count();
				}
			}
		}
	}
	$data = bumbaru_cs_data();
	$o    = '';
	foreach ( bumbaru_cs_mape() as $id => $m ) {
		$o .= '<option value="' . esc_attr( $id ) . '"' . selected( $id, $raw['mapa'], false ) . '>' . esc_html( $m['naziv'] ) . '</option>';
	}
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	?>
<!doctype html>
<html lang="sr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo esc_html( 'Štampa podloge: ' . $naslov ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( BUMBARU3D_URL . 'assets/fonts/csmapa.css?v=' . BUMBARU3D_VERSION ); ?>" />
<style>
	@page { size: A4 portrait; margin: 0; }
	* { box-sizing: border-box; }
	body { margin: 0; font: 14px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #e9e6e1; color: #241a14; }
	.panel { position: sticky; top: 0; z-index: 2; background: #fff; border-bottom: 1px solid #d9d3ca; padding: 12px 18px; display: flex; flex-wrap: wrap; gap: 10px 18px; align-items: end; }
	.panel h1 { font-size: 15px; margin: 0 0 4px; flex-basis: 100%; }
	.panel label { display: grid; gap: 3px; font-size: 12px; font-weight: 600; }
	.panel input[type=text], .panel select { font: inherit; padding: 6px 8px; border: 1px solid #bbb; border-radius: 6px; min-width: 120px; }
	.panel .chk { display: flex; gap: 6px; align-items: center; font-weight: 500; font-size: 13px; }
	.panel button { font: inherit; font-weight: 700; padding: 9px 18px; border-radius: 8px; border: 0; background: #2271b1; color: #fff; cursor: pointer; }
	.panel .note { flex-basis: 100%; margin: 0; font-size: 12.5px; color: #5c5048; }
	.panel .steam { flex-basis: 100%; margin: 0; font-size: 13px; background: #fff7e0; padding: 6px 10px; border-radius: 6px; }
	.list { width: 210mm; height: 297mm; margin: 18px auto; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,.18); overflow: hidden; }
	.list svg { display: block; width: 210mm; height: 297mm; }
	@media print {
		body { background: #fff; }
		.panel { display: none; }
		.list { margin: 0; box-shadow: none; }
	}
</style>
</head>
<body>
<div class="panel">
	<h1><?php echo esc_html( $naslov ); ?></h1>
	<label>Mapa<select data-k="mapa"><?php echo $o; // phpcs:ignore -- očišćeno gore ?></select></label>
	<label>Model<select data-k="model"><option value="mvp"<?php selected( 'mvp', $raw['model'] ); ?>>MVP</option><option value="info"<?php selected( 'info', $raw['model'] ); ?>>General Info</option></select></label>
	<label>Jezik<select data-k="jezik"><option value="en"<?php selected( 'en', $raw['jezik'] ); ?>>EN</option><option value="sr"<?php selected( 'sr', $raw['jezik'] ); ?>>SR</option></select></label>
	<label>Ime igrača<input type="text" data-k="ime" maxlength="16" value="<?php echo esc_attr( $raw['ime'] ); ?>" /></label>
	<label>Slika igrača (samo MVP)<input type="file" accept="image/*" data-avatar /></label>
	<label class="chk"><input type="checkbox" data-bez-slike /> bez slike</label>
	<label class="chk"><input type="checkbox" data-obris /> obris mape (za probu)</label>
	<button type="button" onclick="window.print()">Štampaj</button>
	<button type="button" data-png>Skini A4 (PNG 300 dpi)</button>
	<?php if ( ! empty( $raw['steam'] ) ) : ?>
		<p class="steam">Steam profil kupca: <a href="<?php echo esc_url( $raw['steam'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $raw['steam'] ); ?></a> · otvorite ga, sačuvajte sliku profila (avatar) i učitajte je iznad.</p>
	<?php endif; ?>
	<p class="note">Glossy foto papir A4, u štampaču izaberite: veličina A4, razmera 100% (ne „Prilagodi”), kvalitet „Foto”, vrsta papira „Glossy/sjajni foto papir”. Margine ne smetaju: sav tekst je najmanje 10 mm od ivice. Slika se ne čuva na serveru, ostaje samo u ovom prozoru. PDF: Štampaj → „Sačuvaj kao PDF” (vektorski, sa fontovima). PNG 300 dpi (2480 × 3508) štampajte u razmeri 100% / „stvarna veličina”.</p>
</div>
<div class="list" data-list></div>
<script src="<?php echo esc_url( BUMBARU3D_URL . 'assets/csmapa.js?v=' . BUMBARU3D_VERSION ); ?>"></script>
<script>
(function () {
	var D = <?php echo wp_json_encode( $data ); ?>;
	var list = document.querySelector('[data-list]'), av = '';
	var k = function (n) { return document.querySelector('[data-k="' + n + '"]').value; };
	function crtaj() {
		var bez = document.querySelector('[data-bez-slike]').checked;
		list.innerHTML = BumbaruCS.svg({ mapa: k('mapa'), model: k('model'), jezik: k('jezik'), ime: k('ime').trim() }, D, { stampa: true, obris: document.querySelector('[data-obris]').checked, avatar: bez || !av ? 'none' : av });
		BumbaruCS.poravnaj(list);
	}
	document.querySelector('.panel').addEventListener('input', crtaj);
	document.querySelector('.panel').addEventListener('change', crtaj);
	document.querySelector('[data-avatar]').addEventListener('change', function (e) {
		var f = e.target.files && e.target.files[0];
		if (!f) { av = ''; crtaj(); return; }
		var r = new FileReader();
		r.onload = function () { av = r.result; document.querySelector('[data-bez-slike]').checked = false; crtaj(); };
		r.readAsDataURL(f);
	});
	// PNG 300 dpi iz istog SVG-a: slike i fontovi se ugrade u SVG (data URL), pa se nacrta na platno 2480 × 3508.
	var CSS = <?php echo wp_json_encode( BUMBARU3D_URL . 'assets/fonts/csmapa.css?v=' . BUMBARU3D_VERSION ); ?>, FAJL = <?php echo wp_json_encode( $fajl ); ?>;
	function dataUrl(u) {
		return fetch(u, { credentials: 'same-origin' }).then(function (r) { return r.blob(); }).then(function (b) {
			return new Promise(function (ok) { var f = new FileReader(); f.onload = function () { ok(f.result); }; f.readAsDataURL(b); });
		});
	}
	function fontovi() {
		return fetch(CSS).then(function (r) { return r.text(); }).then(function (css) {
			var urls = [], re = /url\(['"]?([^'")]+)['"]?\)/g, m;
			while ((m = re.exec(css))) urls.push(m[1]);
			return Promise.all(urls.map(function (u) { return dataUrl(new URL(u, new URL(CSS, location.href)).href).then(function (d) { css = css.split(u).join(d); }); })).then(function () { return css; });
		});
	}
	function png(btn) {
		if (btn) { btn.disabled = true; btn.textContent = 'Pravim PNG…'; }
		var el = list.querySelector('svg').cloneNode(true);
		var slike = Array.prototype.slice.call(el.querySelectorAll('image')).filter(function (i) { return !/^data:/.test(i.getAttribute('href')); });
		return Promise.all([fontovi()].concat(slike.map(function (i) { return dataUrl(i.getAttribute('href')).then(function (d) { i.setAttribute('href', d); }); }))).then(function (r) {
			var st = document.createElementNS('http://www.w3.org/2000/svg', 'style');
			st.textContent = r[0];
			el.insertBefore(st, el.firstChild);
			el.setAttribute('width', '2480'); el.setAttribute('height', '3508');
			var url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(el)], { type: 'image/svg+xml' }));
			return new Promise(function (ok, no) {
				var im = new Image();
				im.onload = function () {
					setTimeout(function () { // fontovi u SVG slici se dekodiraju posle onload-a
						var c = document.createElement('canvas'); c.width = 2480; c.height = 3508;
						var x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(im, 0, 0, c.width, c.height);
						URL.revokeObjectURL(url);
						c.toBlob(function (b) {
							var a = document.createElement('a');
							a.href = URL.createObjectURL(b);
							a.download = [FAJL, k('mapa'), k('model'), k('jezik')].join('-') + '-A4-300dpi.png';
							document.body.appendChild(a); a.click(); a.remove();
							ok();
						}, 'image/png');
					}, 300);
				};
				im.onerror = no;
				im.src = url;
			});
		}).catch(function (e) { alert('PNG nije napravljen: ' + e + '. Koristite Štampaj → Sačuvaj kao PDF.'); })
			.then(function () { if (btn) { btn.disabled = false; btn.textContent = 'Skini A4 (PNG 300 dpi)'; } });
	}
	document.querySelector('[data-png]').addEventListener('click', function (e) { png(e.currentTarget); });
	crtaj();
	var spremno = document.fonts && document.fonts.load ? Promise.all([document.fonts.load('800 10px Montserrat'), document.fonts.load('italic 900 10px Nunito'), document.fonts.load('600 10px Geologica')]) : Promise.resolve();
	spremno.then(crtaj, crtaj).then(function () {
		if (/[?&]preuzmi=png/.test(location.search)) png(document.querySelector('[data-png]'));
	});
})();
</script>
</body>
</html>
	<?php
	exit;
} );
