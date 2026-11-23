<?php
/**
 * Administracija: Bumbaru 3D → Kalkulator, Boje, Filamenti i troškovi, Podešavanja sajta, Prodavnica i uvoz.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	$ours   = false !== strpos( (string) $hook, 'bumbaru' );
	$prod   = $screen && in_array( $screen->id, array( 'product', 'edit-product_cat' ), true );
	$order  = $screen && ( 'shop_order' === $screen->id || 'woocommerce_page_wc-orders' === $screen->id );
	if ( ! $ours && ! $prod && ! $order ) {
		return;
	}
	wp_enqueue_style( 'bumbaru-admin', BUMBARU3D_URL . 'assets/admin.css', array(), BUMBARU3D_VERSION );
	wp_enqueue_script( 'bumbaru-admin', BUMBARU3D_URL . 'assets/admin.js', array( 'jquery' ), BUMBARU3D_VERSION, true );
	wp_localize_script( 'bumbaru-admin', 'BUMBARU3D_ADMIN', array(
		'costs'     => bumbaru_costs(),
		'filaments' => bumbaru_filaments(),
		'extras'    => bumbaru_extras(),
	) );
} );

add_action( 'admin_menu', function () {
	$cap  = 'manage_woocommerce';
	$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2" stroke-linejoin="round"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z"/></svg>' );
	add_menu_page( 'Bumbaru 3D', 'Bumbaru 3D', $cap, 'bumbaru-3d', 'bumbaru_page_calculator', $icon, 56 );
	add_submenu_page( 'bumbaru-3d', 'Kalkulator cene', 'Kalkulator cene', $cap, 'bumbaru-3d', 'bumbaru_page_calculator' );
	add_submenu_page( 'bumbaru-3d', 'Cene proizvoda', 'Cene proizvoda', $cap, 'bumbaru-cene', 'bumbaru_page_prices' );
	add_submenu_page( 'bumbaru-3d', 'Akcija', 'Akcija', $cap, 'bumbaru-akcija', 'bumbaru_page_akcija' );
	add_submenu_page( 'bumbaru-3d', 'Boje', 'Boje', $cap, 'bumbaru-boje', 'bumbaru_page_colors' );
	add_submenu_page( 'bumbaru-3d', 'Filamenti i troškovi', 'Filamenti i troškovi', $cap, 'bumbaru-troskovi', 'bumbaru_page_costs' );
	add_submenu_page( 'bumbaru-3d', 'Podešavanja sajta', 'Podešavanja sajta', $cap, 'bumbaru-sajt', 'bumbaru_page_site' );
	add_submenu_page( 'bumbaru-3d', 'Prodavnica i uvoz', 'Prodavnica i uvoz', $cap, 'bumbaru-uvoz', 'bumbaru_page_setup' );
} );

// Posle aktivacije: uputstvo gde se podešava prodavnica.
add_action( 'admin_notices', function () {
	if ( ! get_option( 'bumbaru_show_welcome' ) || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && false !== strpos( $screen->id, 'bumbaru-uvoz' ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>Bumbaru 3D je aktivan.</strong> Sledeći korak: <a href="' . esc_url( admin_url( 'admin.php?page=bumbaru-uvoz' ) ) . '">podesite prodavnicu i uvezite proizvode</a> (dva klika).</p></div>';
} );

// Posle nadogradnje na verziju 2: uvoz novih proizvoda (slavske modlice, dom i kuhinja, lutkice…).
add_action( 'admin_notices', function () {
	if ( ! get_option( 'bumbaru_update_notice' ) || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Bumbaru 3D 2.0:</strong> u paketu su novi proizvodi i fotografije. <a href="' . esc_url( admin_url( 'admin.php?page=bumbaru-uvoz' ) ) . '">Kliknite „Uvezi proizvode”</a>: postojeći proizvodi se osvežavaju, a cene koje ste upisali ostaju.</p></div>';
} );

function bumbaru_admin_head( $title, $lead = '' ) {
	echo '<div class="wrap bu-admin"><h1><svg width="34" height="34" viewBox="0 0 24 24" fill="#F5C137" stroke="#312018" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z"/></svg>' . esc_html( $title ) . '</h1>';
	if ( $lead ) {
		echo '<p class="bu-lead">' . wp_kses_post( $lead ) . '</p>';
	}
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>Sačuvano.</p></div>';
	}
}

function bumbaru_admin_redirect( $page, $args = array() ) {
	wp_safe_redirect( add_query_arg( array_merge( array( 'page' => $page, 'saved' => 1 ), $args ), admin_url( 'admin.php' ) ) );
	exit;
}

/** Opcije za izbor filamenta i dodatka. */
function bumbaru_filament_options( $sel = '' ) {
	$o = '<option value="">— filament —</option>';
	foreach ( bumbaru_filaments() as $f ) {
		$o .= '<option value="' . esc_attr( $f['id'] ) . '"' . selected( $sel, $f['id'], false ) . '>' . esc_html( $f['name'] . ' (' . number_format( (float) $f['price_kg'], 0, ',', '.' ) . ' din/kg)' ) . '</option>';
	}
	return $o;
}

function bumbaru_extra_options( $sel = '' ) {
	$o = '<option value="">— dodatak —</option>';
	foreach ( bumbaru_extras() as $e ) {
		$o .= '<option value="' . esc_attr( $e['id'] ) . '"' . selected( $sel, $e['id'], false ) . '>' . esc_html( $e['name'] . ' (' . number_format( (float) $e['price'], 0, ',', '.' ) . ' din)' ) . '</option>';
	}
	return $o;
}

/**
 * Polja kalkulatora. $name: prefiks imena polja (npr. bu_calc), $context: 'page' ili 'product'.
 */
function bumbaru_calc_ui( $calc, $name, $context = 'page' ) {
	$c    = wp_parse_args( is_array( $calc ) ? $calc : array(), bumbaru_calc_defaults() );
	$fils = $c['filaments'] ? $c['filaments'] : array( array( 'id' => '', 'g' => '' ) );
	$exts = $c['extras'] ? $c['extras'] : array();
	$n    = esc_attr( $name );
	ob_start();
	?>
	<div class="bu-calc" data-calc>
		<div class="bu-grid">
			<div>
				<h3>Filament (grami iz slicera)</h3>
				<div data-list="fil">
					<?php foreach ( $fils as $i => $r ) : ?>
						<div class="bu-row" data-row="fil"><select name="<?php echo $n; ?>[filaments][<?php echo (int) $i; ?>][id]"><?php echo bumbaru_filament_options( $r['id'] ); // phpcs:ignore ?></select><input type="number" step="0.1" min="0" name="<?php echo $n; ?>[filaments][<?php echo (int) $i; ?>][g]" value="<?php echo esc_attr( $r['g'] ); ?>" placeholder="g" /> g <a href="#" class="bu-del" data-del title="Ukloni">×</a></div>
					<?php endforeach; ?>
				</div>
				<template data-tpl="fil"><div class="bu-row" data-row="fil"><select name="<?php echo $n; ?>[filaments][__i__][id]"><?php echo bumbaru_filament_options(); // phpcs:ignore ?></select><input type="number" step="0.1" min="0" name="<?php echo $n; ?>[filaments][__i__][g]" placeholder="g" /> g <a href="#" class="bu-del" data-del title="Ukloni">×</a></div></template>
				<a href="#" class="button" data-add="fil">+ Još jedna boja / filament</a>

				<h3>Štampa i rad</h3>
				<div class="bu-fields">
					<label>Vreme štampe, sati<input type="number" min="0" step="1" data-c="hours" name="<?php echo $n; ?>[hours]" value="<?php echo esc_attr( $c['hours'] ); ?>" /></label>
					<label>i minuta<input type="number" min="0" max="59" step="1" data-c="minutes" name="<?php echo $n; ?>[minutes]" value="<?php echo esc_attr( $c['minutes'] ); ?>" /></label>
					<label>Komada iz jedne štampe<input type="number" min="1" step="1" data-c="pieces" name="<?php echo $n; ?>[pieces]" value="<?php echo esc_attr( $c['pieces'] ); ?>" /><small>Štampa se deli na komade.</small></label>
					<label>Vaš rad, minuta po komadu<input type="number" min="0" step="1" data-c="labor_min" name="<?php echo $n; ?>[labor_min]" value="<?php echo esc_attr( $c['labor_min'] ); ?>" /><small>Dorada, provera, pakovanje.</small></label>
				</div>

				<h3>Dodaci po komadu</h3>
				<div data-list="ext">
					<?php foreach ( $exts as $i => $r ) : ?>
						<div class="bu-row" data-row="ext"><select name="<?php echo $n; ?>[extras][<?php echo (int) $i; ?>][id]"><?php echo bumbaru_extra_options( $r['id'] ); // phpcs:ignore ?></select><input type="number" step="1" min="0" name="<?php echo $n; ?>[extras][<?php echo (int) $i; ?>][qty]" value="<?php echo esc_attr( $r['qty'] ); ?>" /> kom <a href="#" class="bu-del" data-del title="Ukloni">×</a></div>
					<?php endforeach; ?>
				</div>
				<template data-tpl="ext"><div class="bu-row" data-row="ext"><select name="<?php echo $n; ?>[extras][__i__][id]"><?php echo bumbaru_extra_options(); // phpcs:ignore ?></select><input type="number" step="1" min="0" name="<?php echo $n; ?>[extras][__i__][qty]" value="1" /> kom <a href="#" class="bu-del" data-del title="Ukloni">×</a></div></template>
				<a href="#" class="button" data-add="ext">+ Dodatak (karika, kutija, svećica…)</a>

				<h3>Ostalo</h3>
				<div class="bu-fields">
					<label>Ostali trošak, din<input type="number" min="0" step="1" data-c="other" name="<?php echo $n; ?>[other]" value="<?php echo esc_attr( $c['other'] ); ?>" /></label>
					<label>Zarada za ovaj proizvod, %<input type="number" step="1" data-c="margin" name="<?php echo $n; ?>[margin]" value="<?php echo esc_attr( $c['margin'] ); ?>" placeholder="<?php echo esc_attr( bumbaru_costs()['margin_pct'] ); ?> (iz podešavanja)" /></label>
				</div>
			</div>
			<div class="bu-out" data-calc-out>
				<table data-breakdown></table>
				<div class="bu-price" data-price>—</div>
				<div class="bu-profit" data-profit></div>
				<?php if ( 'product' === $context ) : ?>
					<p><label><input type="checkbox" name="<?php echo $n; ?>[prati]" value="1"<?php checked( ! isset( $c['prati'] ) || ! empty( $c['prati'] ) ); ?> /> <strong>Cena prati kalkulator</strong></label><br /><small>Posle „Ažuriraj” cena na sajtu = predlog. Kad promenite cenu filamenta, struje ili zaradu, cena se promeni sama.</small></p>
					<p><a href="#" class="button bu-btn" data-apply-price>Upiši kao cenu proizvoda</a></p>
					<p class="bu-note">Upisuje se u polje „Redovna cena” (Opšte), a čuva se klikom na „Ažuriraj”.</p>
					<p class="bu-row" style="margin-top:14px"><label>Akcija, % <input type="number" min="1" max="90" step="1" data-c="sale_pct" style="width:70px" placeholder="10" /></label> <a href="#" class="button secondary" data-apply-sale>Upiši akcijsku cenu</a></p>
					<p class="bu-note">Kad proizvod ima akcijsku cenu, na sajtu se sam pojavi bedž „−10%” i precrtana cena.</p>
				<?php endif; ?>
				<p class="bu-note" style="margin-top:12px">Cene filamenta, struje i rada menjate u <a href="<?php echo esc_url( admin_url( 'admin.php?page=bumbaru-troskovi' ) ); ?>">Filamenti i troškovi</a>.</p>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/* ——— Kalkulator ——— */

add_action( 'admin_post_bumbaru_calc_apply', function () {
	check_admin_referer( 'bumbaru_calc_apply' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$pid  = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
	$calc = bumbaru_calc_sanitize( isset( $_POST['bu_calc'] ) ? wp_unslash( $_POST['bu_calc'] ) : array() ); // phpcs:ignore
	$product = $pid ? wc_get_product( $pid ) : null;
	if ( $product ) {
		update_post_meta( $pid, '_bu_calc', $calc );
		$r = bumbaru_calc( $calc );
		if ( $r['price'] > 0 && ! empty( $_POST['set_price'] ) ) {
			$product->set_regular_price( (string) $r['price'] );
			$product->save();
		}
	}
	bumbaru_admin_redirect( 'bumbaru-3d' );
} );

add_action( 'admin_post_bumbaru_bulk_prices', function () {
	check_admin_referer( 'bumbaru_bulk_prices' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$n = 0;
	foreach ( (array) ( isset( $_POST['apply'] ) ? wp_unslash( $_POST['apply'] ) : array() ) as $pid ) { // phpcs:ignore
		$product = wc_get_product( (int) $pid );
		$calc    = get_post_meta( (int) $pid, '_bu_calc', true );
		if ( $product && bumbaru_calc_has_data( $calc ) ) {
			$r = bumbaru_calc( $calc );
			if ( $r['price'] > 0 ) {
				$product->set_regular_price( (string) $r['price'] );
				$product->save();
				$n++;
			}
		}
	}
	bumbaru_admin_redirect( 'bumbaru-3d', array( 'updated' => $n ) );
} );

function bumbaru_page_calculator() {
	bumbaru_admin_head( 'Kalkulator cene 3D štampe', 'Upišite grame filamenta i vreme štampe iz slicera (Bambu Studio, PrusaSlicer, Cura). Kalkulator dodaje struju, štampač, vaš rad, dodatke i zaradu i predlaže cenu zaokruženu kao na sajtu (npr. 390, 1.590 din). Obračun možete sačuvati uz proizvod i upisati kao cenu.' );
	if ( isset( $_GET['updated'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success"><p>Upisane nove cene: ' . (int) $_GET['updated'] . '.</p></div>'; // phpcs:ignore
	}
	$products = wc_get_products( array( 'status' => array( 'publish', 'draft' ), 'limit' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	?>
	<form class="bu-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bumbaru_calc_apply" />
		<?php wp_nonce_field( 'bumbaru_calc_apply' ); ?>
		<?php echo bumbaru_calc_ui( array(), 'bu_calc', 'page' ); // phpcs:ignore ?>
		<hr style="margin:20px 0" />
		<div class="bu-row">
			<strong>Sačuvaj uz proizvod:</strong>
			<select name="product_id" required>
				<option value="">— izaberite proizvod —</option>
				<?php foreach ( $products as $p ) : ?>
					<option value="<?php echo (int) $p->get_id(); ?>"><?php echo esc_html( $p->get_name() . ( $p->get_price() !== '' ? ' (sada ' . bumbaru_din_admin( $p->get_price() ) . ')' : ' (bez cene)' ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<label><input type="checkbox" name="set_price" value="1" checked /> i upiši predlog kao cenu</label>
			<button class="button bu-btn" type="submit">Sačuvaj</button>
		</div>
	</form>

	<form class="bu-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bumbaru_bulk_prices" />
		<?php wp_nonce_field( 'bumbaru_bulk_prices' ); ?>
		<h2>Cene svih proizvoda</h2>
		<p class="bu-note">Kad promenite cenu filamenta, struje ili zaradu, ovde vidite nove predloge za proizvode koji imaju sačuvan obračun. Štiklirajte one koje želite da promenite.</p>
		<table class="bu-table">
			<thead><tr><th class="w-xs"></th><th>Proizvod</th><th>Filament</th><th>Štampa</th><th>Trošak</th><th>Sada</th><th>Predlog</th></tr></thead>
			<tbody>
			<?php foreach ( $products as $p ) :
				$calc = get_post_meta( $p->get_id(), '_bu_calc', true );
				$has  = bumbaru_calc_has_data( $calc );
				$r    = $has ? bumbaru_calc( $calc ) : null;
				$now  = '' !== $p->get_price() ? (float) $p->get_regular_price() : 0;
				?>
				<tr>
					<td><?php if ( $has && $r['price'] && (int) $r['price'] !== (int) $now ) : ?><input type="checkbox" name="apply[]" value="<?php echo (int) $p->get_id(); ?>" /><?php endif; ?></td>
					<td><a href="<?php echo esc_url( get_edit_post_link( $p->get_id() ) ); ?>"><?php echo esc_html( $p->get_name() ); ?></a></td>
					<?php if ( $has ) : ?>
						<td><?php echo esc_html( round( $r['grams'], 1 ) . ' g' ); ?></td>
						<td><?php echo esc_html( floor( $r['hours'] ) . ' h ' . round( ( $r['hours'] - floor( $r['hours'] ) ) * 60 ) . ' min' ); ?></td>
						<td><?php echo esc_html( bumbaru_din_admin( $r['cost'] ) ); ?></td>
						<td><?php echo $now ? esc_html( bumbaru_din_admin( $now ) ) : '—'; ?></td>
						<td><strong><?php echo esc_html( bumbaru_din_admin( $r['price'] ) ); ?></strong></td>
					<?php else : ?>
						<td colspan="3" class="bu-note">nema obračuna: otvorite proizvod i popunite „Kalkulator cene”</td>
						<td><?php echo $now ? esc_html( bumbaru_din_admin( $now ) ) : '—'; ?></td>
						<td>—</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p><button class="button bu-btn" type="submit">Upiši izabrane cene</button></p>
	</form>
	</div>
	<?php
}

/* ——— Cene proizvoda: sve cene na jednom mestu ——— */

add_action( 'admin_post_bumbaru_save_prices', function () {
	check_admin_referer( 'bumbaru_save_prices' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$clean = function ( $v ) {
		$v = preg_replace( '/[^0-9,\.]/', '', (string) $v );
		$v = str_replace( '.', '', $v ); // 1.590 → 1590
		$v = str_replace( ',', '.', $v );
		return '' === $v ? '' : (string) round( (float) $v );
	};
	$reg  = isset( $_POST['reg'] ) ? (array) wp_unslash( $_POST['reg'] ) : array(); // phpcs:ignore
	$sale = isset( $_POST['sale'] ) ? (array) wp_unslash( $_POST['sale'] ) : array(); // phpcs:ignore
	$n    = 0;
	$bad  = 0;
	foreach ( $reg as $pid => $r ) {
		$product = wc_get_product( (int) $pid );
		if ( ! $product ) {
			continue;
		}
		$r  = $clean( $r );
		$sl = isset( $sale[ $pid ] ) ? $clean( $sale[ $pid ] ) : '';
		if ( '' !== $sl && ( '' === $r || (float) $sl >= (float) $r ) ) {
			$sl = ''; // akcijska mora biti manja od redovne
			$bad++;
		}
		if ( $r !== (string) $product->get_regular_price() || $sl !== (string) $product->get_sale_price() ) {
			$product->set_regular_price( $r );
			$product->set_sale_price( $sl );
			$product->save();
			$n++;
		}
	}
	bumbaru_admin_redirect( 'bumbaru-cene', array( 'updated' => $n, 'bad' => $bad ) );
} );

function bumbaru_page_prices() {
	bumbaru_admin_head( 'Cene proizvoda', 'Sve cene na jednom mestu: upišite redovnu cenu, a akcijsku samo kad je proizvod na akciji (tada se na sajtu sam pojavi bedž „−10%” i precrtana cena). Doplate za opcije (veličina, set, boja) menjate u samom proizvodu, u polju opcija.' );
	if ( isset( $_GET['updated'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success"><p>Sačuvano. Promenjeno proizvoda: ' . (int) $_GET['updated'] . '.</p></div>'; // phpcs:ignore
	}
	if ( ! empty( $_GET['bad'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-warning"><p>Akcijska cena mora biti manja od redovne. Kod ' . (int) $_GET['bad'] . ' proizvoda nije upisana.</p></div>'; // phpcs:ignore
	}
	$products = wc_get_products( array( 'status' => array( 'publish', 'draft', 'private' ), 'limit' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	usort( $products, function ( $a, $b ) {
		return strcmp( (string) $a->get_sku(), (string) $b->get_sku() );
	} );
	$empty = 0;
	foreach ( $products as $p ) {
		if ( '' === $p->get_regular_price() ) {
			$empty++;
		}
	}
	?>
	<form class="bu-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bumbaru_save_prices" />
		<?php wp_nonce_field( 'bumbaru_save_prices' ); ?>
		<p><?php echo $empty ? '<strong>Bez cene: ' . (int) $empty . ' proizvoda.</strong> Bez cene proizvod ne može da se kupi preko sajta (samo preko Messengera) i ne ide u Google Shopping.' : 'Svi proizvodi imaju cenu.'; ?></p>
		<table class="bu-table">
			<thead><tr><th>Šifra</th><th>Proizvod</th><th>Kategorija</th><th>Opcije i doplate</th><th>Redovna cena, din</th><th>Akcijska cena, din</th><th>Slike</th></tr></thead>
			<tbody>
			<?php foreach ( $products as $p ) :
				$cfg  = function_exists( 'bumbaru_config' ) ? bumbaru_config( $p ) : array();
				$opts = isset( $cfg['options_text'] ) ? trim( (string) $cfg['options_text'] ) : '';
				if ( 'set' === ( $cfg['type'] ?? '' ) ) {
					$opts = trim( 'Peto ime u setu: +' . (int) $cfg['set_extra'] . " din\n" . $opts );
				}
				$cats = wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields' => 'names' ) );
				$imgs = ( $p->get_image_id() ? 1 : 0 ) + count( $p->get_gallery_image_ids() );
				?>
				<tr<?php echo '' === $p->get_regular_price() ? ' style="background:#FFF6DA"' : ''; ?>>
					<td><code><?php echo esc_html( $p->get_sku() ); ?></code></td>
					<td><a href="<?php echo esc_url( get_edit_post_link( $p->get_id() ) ); ?>"><?php echo esc_html( $p->get_name() ); ?></a></td>
					<td><?php echo esc_html( is_wp_error( $cats ) ? '' : implode( ', ', $cats ) ); ?></td>
					<td class="bu-note" style="max-width:280px"><?php echo $opts ? nl2br( esc_html( $opts ) ) : '—'; ?></td>
					<td><input type="text" inputmode="numeric" name="reg[<?php echo (int) $p->get_id(); ?>]" value="<?php echo esc_attr( $p->get_regular_price() ); ?>" placeholder="npr. 590" style="width:100px" /></td>
					<td><input type="text" inputmode="numeric" name="sale[<?php echo (int) $p->get_id(); ?>]" value="<?php echo esc_attr( $p->get_sale_price() ); ?>" placeholder="prazno" style="width:100px" /></td>
					<td><?php echo $imgs ? (int) $imgs : '<span style="color:#9A3420">nema</span>'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p><button class="button bu-btn" type="submit">Sačuvaj sve cene</button></p>
		<p class="bu-note">Predlog cene iz gramaže i vremena štampe daje <a href="<?php echo esc_url( admin_url( 'admin.php?page=bumbaru-3d' ) ); ?>">Kalkulator cene</a>.</p>
	</form>
	</div>
	<?php
}

function bumbaru_din_admin( $n ) {
	return number_format( (float) $n, 0, ',', '.' ) . ' din';
}


/* ——— Akcija ——— */

add_action( 'admin_post_bumbaru_save_akcija', function () {
	check_admin_referer( 'bumbaru_save_akcija' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$in = isset( $_POST['ak'] ) ? (array) wp_unslash( $_POST['ak'] ) : array(); // phpcs:ignore
	$do = isset( $in['do'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $in['do'] ) ? $in['do'] : gmdate( 'Y-m-d', time() + 14 * DAY_IN_SECONDS );
	update_option( 'bumbaru_akcija', array(
		'ukljucena'  => ! empty( $in['ukljucena'] ) ? 1 : 0,
		'naziv'      => sanitize_text_field( isset( $in['naziv'] ) && '' !== trim( $in['naziv'] ) ? $in['naziv'] : 'Akcija' ),
		'procenat'   => max( 1, min( 90, isset( $in['procenat'] ) ? (float) $in['procenat'] : 10 ) ),
		'kategorija' => sanitize_title( isset( $in['kategorija'] ) ? $in['kategorija'] : '' ),
		'do'         => $do,
	) );
	bumbaru_admin_redirect( 'bumbaru-akcija' );
} );

function bumbaru_page_akcija() {
	bumbaru_admin_head( 'Akcija', 'Popust na sve proizvode iz jedne kategorije, do izabranog dana. Na sajtu se pojavljuje traka na vrhu, dugme „Akcija” u meniju, strana /akcija/, odbrojavanje i precrtane cene. Posle poslednjeg dana akcija sama prestaje. Proizvod koji ima svoju akcijsku cenu (Proizvod → Opšte) zadržava nju.' );
	$s = bumbaru_akcija_settings();
	$a = bumbaru_akcija();
	?>
	<form class="bu-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bumbaru_save_akcija" />
		<?php wp_nonce_field( 'bumbaru_save_akcija' ); ?>
		<p><strong><?php echo $a ? esc_html( 'Akcija je u toku: ' . $a['naziv'] . ' −' . (float) $a['procenat'] . '% do ' . bumbaru_datum_kratko( $a['do'] ) ) : 'Akcija trenutno ne važi.'; ?></strong></p>
		<table class="form-table" role="presentation">
			<tr><th scope="row">Uključena</th><td><label><input type="checkbox" name="ak[ukljucena]" value="1"<?php checked( ! empty( $s['ukljucena'] ) ); ?> /> Akcija važi na sajtu</label></td></tr>
			<tr><th scope="row"><label for="ak-n">Naziv</label></th><td><input class="regular-text" id="ak-n" name="ak[naziv]" value="<?php echo esc_attr( $s['naziv'] ); ?>" /></td></tr>
			<tr><th scope="row"><label for="ak-p">Popust, %</label></th><td><input type="number" id="ak-p" name="ak[procenat]" min="1" max="90" step="1" value="<?php echo esc_attr( $s['procenat'] ); ?>" style="width:90px" /></td></tr>
			<tr><th scope="row"><label for="ak-k">Kategorija</label></th><td><select id="ak-k" name="ak[kategorija]">
				<?php foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $t ) : ?>
					<option value="<?php echo esc_attr( $t->slug ); ?>"<?php selected( $s['kategorija'], $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
				<?php endforeach; ?>
			</select></td></tr>
			<tr><th scope="row"><label for="ak-d">Poslednji dan</label></th><td><input type="date" id="ak-d" name="ak[do]" value="<?php echo esc_attr( $s['do'] ); ?>" /> <span class="description">Akcija važi do kraja tog dana.</span></td></tr>
		</table>
		<p><button class="button bu-btn" type="submit">Sačuvaj akciju</button></p>
		<p class="bu-note">Popust se računa od redovne cene i zaokružuje na 10 din. Popust za veću količinu i za komplet se dodaje na akcijsku cenu.</p>
	</form>
	</div>
	<?php
}

/* ——— Boje ——— */

add_action( 'admin_post_bumbaru_save_colors', function () {
	check_admin_referer( 'bumbaru_save_colors' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$out  = array();
	$used = array();
	foreach ( (array) ( isset( $_POST['c'] ) ? wp_unslash( $_POST['c'] ) : array() ) as $row ) { // phpcs:ignore
		$label = sanitize_text_field( isset( $row['label'] ) ? $row['label'] : '' );
		if ( '' === $label ) {
			continue;
		}
		$id = sanitize_title( ! empty( $row['id'] ) ? $row['id'] : remove_accents( $label ) );
		if ( isset( $used[ $id ] ) ) {
			$id .= '-' . count( $used );
		}
		$used[ $id ] = 1;
		$face        = bumbaru_sanitize_hex( isset( $row['face'] ) ? $row['face'] : '', '#CCCCCC' );
		$out[]       = array(
			'id'    => $id,
			'label' => $label,
			'face'  => $face,
			'edge'  => bumbaru_sanitize_hex( isset( $row['edge'] ) ? $row['edge'] : '', bumbaru_darken( $face ) ),
			'dostupna' => ! empty( $row['dostupna'] ) ? 1 : 0,
		);
	}
	if ( $out ) {
		update_option( 'bumbaru_colors', $out );
	}
	bumbaru_admin_redirect( 'bumbaru-boje' );
} );

function bumbaru_page_colors() {
	bumbaru_admin_head( 'Boje', 'Boje filamenta. Kupac na sajtu bira samo boje označene „Na stanju” (ukras sa imenom, privesci, klipovi, izbor boja na proizvodima). „Boja” je površina, a „ivica” tamnija nijansa za 3D izgled u pregledu uživo. Zvezdica na jelkama koristi boje upisane u proizvodu, na kartici „Konfigurator”.' );
	?>
	<form class="bu-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bumbaru_save_colors" />
		<?php wp_nonce_field( 'bumbaru_save_colors' ); ?>
		<table class="bu-table" id="bu-colors">
			<thead><tr><th class="w-xs"></th><th>Naziv (vidi kupac)</th><th class="w-s">Oznaka</th><th class="w-s">Boja</th><th class="w-s">Ivica</th><th class="w-s">Na stanju</th><th class="w-xs"></th></tr></thead>
			<tbody>
			<?php foreach ( bumbaru_colors() as $i => $c ) : ?>
				<tr>
					<td><span class="bu-chip-preview" style="background:linear-gradient(135deg,<?php echo esc_attr( $c['face'] ); ?> 55%,<?php echo esc_attr( $c['edge'] ); ?> 55%)"></span></td>
					<td><input type="text" name="c[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $c['label'] ); ?>" /></td>
					<td><input type="text" name="c[<?php echo (int) $i; ?>][id]" value="<?php echo esc_attr( $c['id'] ); ?>" readonly title="Oznaka se ne menja, jer je koriste proizvodi i porudžbine." /></td>
					<td><input type="color" class="bu-color-face" name="c[<?php echo (int) $i; ?>][face]" value="<?php echo esc_attr( $c['face'] ); ?>" /></td>
					<td><input type="color" class="bu-color-edge" name="c[<?php echo (int) $i; ?>][edge]" value="<?php echo esc_attr( $c['edge'] ); ?>" /></td>
					<td><label><input type="checkbox" name="c[<?php echo (int) $i; ?>][dostupna]" value="1"<?php checked( ! empty( $c['dostupna'] ) ); ?> /> na stanju</label></td>
					<td><a href="#" class="bu-del" data-table-del title="Obriši">×</a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<template><tr><td><span class="bu-chip-preview" style="background:#ccc"></span></td><td><input type="text" name="c[__i__][label]" placeholder="npr. Ljubičasta" /></td><td><input type="text" name="c[__i__][id]" placeholder="sama se pravi" /></td><td><input type="color" class="bu-color-face" name="c[__i__][face]" value="#8E5BB5" /></td><td><input type="color" class="bu-color-edge" name="c[__i__][edge]" value="#603D7B" /></td><td><label><input type="checkbox" name="c[__i__][dostupna]" value="1" checked /> na stanju</label></td><td><a href="#" class="bu-del" data-table-del>×</a></td></tr></template>
		</table>
		<p><a href="#" class="button" data-table-add="#bu-colors">+ Nova boja</a> <button class="button bu-btn" type="submit">Sačuvaj boje</button></p>
		<p class="bu-note">Opcije proizvoda sa nazivom boje (npr. „Crvena”, „Tirkizna”) automatski dobijaju kružić u toj boji.</p>
	</form>
	</div>
	<?php
}

/* ——— Filamenti i troškovi ——— */

add_action( 'admin_post_bumbaru_save_costs', function () {
	check_admin_referer( 'bumbaru_save_costs' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$in    = isset( $_POST['costs'] ) ? (array) wp_unslash( $_POST['costs'] ) : array(); // phpcs:ignore
	$costs = bumbaru_default_costs();
	foreach ( $costs as $k => $v ) {
		if ( 'rounding' === $k ) {
			$costs[ $k ] = isset( $in[ $k ] ) && in_array( $in[ $k ], array( 'none', '10', '50', '100', 'end90' ), true ) ? $in[ $k ] : 'end90';
		} elseif ( isset( $in[ $k ] ) ) {
			$costs[ $k ] = max( 0, (float) str_replace( ',', '.', $in[ $k ] ) );
		}
	}
	update_option( 'bumbaru_costs', $costs );

	$save_list = function ( $key, $fields ) {
		$out  = array();
		$used = array();
		foreach ( (array) ( isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : array() ) as $row ) { // phpcs:ignore
			$name = sanitize_text_field( isset( $row['name'] ) ? $row['name'] : '' );
			if ( '' === $name ) {
				continue;
			}
			$id = sanitize_title( ! empty( $row['id'] ) ? $row['id'] : remove_accents( $name ) );
			while ( isset( $used[ $id ] ) ) {
				$id .= '-2';
			}
			$used[ $id ] = 1;
			$item        = array( 'id' => $id, 'name' => $name );
			foreach ( $fields as $f => $type ) {
				$v          = isset( $row[ $f ] ) ? $row[ $f ] : '';
				$item[ $f ] = 'num' === $type ? max( 0, (float) str_replace( ',', '.', $v ) ) : sanitize_text_field( $v );
			}
			$out[] = $item;
		}
		return $out;
	};
	update_option( 'bumbaru_filaments', $save_list( 'fil', array( 'material' => 'text', 'color' => 'text', 'price_kg' => 'num' ) ) );
	update_option( 'bumbaru_extras', $save_list( 'ext', array( 'price' => 'num' ) ) );
	bumbaru_admin_redirect( 'bumbaru-troskovi' );
} );

function bumbaru_page_costs() {
	bumbaru_admin_head( 'Filamenti i troškovi', 'Ovde upisujete cene po kojima kalkulator računa. <strong>Upisane vrednosti su primeri</strong>: promenite ih na svoje cene filamenta, struje i rada.' );
	$c    = bumbaru_costs();
	$mats = array( 'PLA', 'PLA Silk', 'PLA Matte', 'PETG', 'ASA', 'ABS', 'TPU', 'Drugo' );
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bumbaru_save_costs" />
		<?php wp_nonce_field( 'bumbaru_save_costs' ); ?>
		<div class="bu-card">
			<h2>Troškovi štampe i zarada</h2>
			<div class="bu-fields">
				<label>Struja, din za 1 kWh<input type="number" step="0.01" name="costs[kwh]" value="<?php echo esc_attr( $c['kwh'] ); ?>" /><small>Sa računa za struju.</small></label>
				<label>Potrošnja štampača, W<input type="number" step="1" name="costs[power_w]" value="<?php echo esc_attr( $c['power_w'] ); ?>" /><small>Prosek tokom štampe.</small></label>
				<label>Štampač, din po satu<input type="number" step="1" name="costs[machine_h]" value="<?php echo esc_attr( $c['machine_h'] ); ?>" /><small>Amortizacija, mlaznice, ploče.</small></label>
				<label>Vaš rad, din po satu<input type="number" step="1" name="costs[labor_h]" value="<?php echo esc_attr( $c['labor_h'] ); ?>" /></label>
				<label>Otpad, %<input type="number" step="1" name="costs[waste_pct]" value="<?php echo esc_attr( $c['waste_pct'] ); ?>" /><small>Podrška, purge, neuspele štampe.</small></label>
				<label>Zarada, %<input type="number" step="1" name="costs[margin_pct]" value="<?php echo esc_attr( $c['margin_pct'] ); ?>" /><small>Na trošak. Može i po proizvodu.</small></label>
				<label>PDV, %<input type="number" step="1" name="costs[vat_pct]" value="<?php echo esc_attr( $c['vat_pct'] ); ?>" /><small>0 dok niste u sistemu PDV-a.</small></label>
				<label>Zaokruživanje cene<select name="costs[rounding]">
					<?php foreach ( array( 'end90' => 'na …90 din (390, 1.590)', '10' => 'na 10 din', '50' => 'na 50 din', '100' => 'na 100 din', 'none' => 'bez zaokruživanja' ) as $k => $l ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $c['rounding'], $k ); ?>><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select></label>
			</div>
		</div>

		<div class="bu-card">
			<h2>Filamenti</h2>
			<table class="bu-table" id="bu-fil">
				<thead><tr><th>Naziv</th><th class="w-s">Materijal</th><th class="w-s">Boja</th><th class="w-s">Cena za 1 kg, din</th><th class="w-xs"></th></tr></thead>
				<tbody>
				<?php foreach ( bumbaru_filaments() as $i => $f ) : ?>
					<tr>
						<td><input type="hidden" name="fil[<?php echo (int) $i; ?>][id]" value="<?php echo esc_attr( $f['id'] ); ?>" /><input type="text" name="fil[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $f['name'] ); ?>" /></td>
						<td><select name="fil[<?php echo (int) $i; ?>][material]"><?php foreach ( $mats as $m ) { echo '<option' . selected( $f['material'], $m, false ) . '>' . esc_html( $m ) . '</option>'; } ?></select></td>
						<td><select name="fil[<?php echo (int) $i; ?>][color]"><option value="">—</option><?php foreach ( bumbaru_colors() as $col ) { echo '<option value="' . esc_attr( $col['id'] ) . '"' . selected( $f['color'], $col['id'], false ) . '>' . esc_html( $col['label'] ) . '</option>'; } ?></select></td>
						<td><input type="number" step="1" name="fil[<?php echo (int) $i; ?>][price_kg]" value="<?php echo esc_attr( $f['price_kg'] ); ?>" /></td>
						<td><a href="#" class="bu-del" data-table-del title="Obriši">×</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				<template><tr><td><input type="text" name="fil[__i__][name]" placeholder="npr. PETG crvena" /></td><td><select name="fil[__i__][material]"><?php foreach ( $mats as $m ) { echo '<option>' . esc_html( $m ) . '</option>'; } ?></select></td><td><select name="fil[__i__][color]"><option value="">—</option><?php foreach ( bumbaru_colors() as $col ) { echo '<option value="' . esc_attr( $col['id'] ) . '">' . esc_html( $col['label'] ) . '</option>'; } ?></select></td><td><input type="number" step="1" name="fil[__i__][price_kg]" value="2500" /></td><td><a href="#" class="bu-del" data-table-del>×</a></td></tr></template>
			</table>
			<p><a href="#" class="button" data-table-add="#bu-fil">+ Novi filament</a></p>
		</div>

		<div class="bu-card">
			<h2>Dodaci</h2>
			<table class="bu-table" id="bu-ext">
				<thead><tr><th>Naziv</th><th class="w-s">Cena po komadu, din</th><th class="w-xs"></th></tr></thead>
				<tbody>
				<?php foreach ( bumbaru_extras() as $i => $e ) : ?>
					<tr>
						<td><input type="hidden" name="ext[<?php echo (int) $i; ?>][id]" value="<?php echo esc_attr( $e['id'] ); ?>" /><input type="text" name="ext[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $e['name'] ); ?>" /></td>
						<td><input type="number" step="1" name="ext[<?php echo (int) $i; ?>][price]" value="<?php echo esc_attr( $e['price'] ); ?>" /></td>
						<td><a href="#" class="bu-del" data-table-del title="Obriši">×</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				<template><tr><td><input type="text" name="ext[__i__][name]" placeholder="npr. Magnet" /></td><td><input type="number" step="1" name="ext[__i__][price]" value="0" /></td><td><a href="#" class="bu-del" data-table-del>×</a></td></tr></template>
			</table>
			<p><a href="#" class="button" data-table-add="#bu-ext">+ Novi dodatak</a></p>
		</div>
		<p><button class="button bu-btn" type="submit">Sačuvaj</button></p>
	</form>
	</div>
	<?php
}

/* ——— Podešavanja sajta ——— */

add_action( 'admin_post_bumbaru_save_site', function () {
	check_admin_referer( 'bumbaru_save_site' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$in  = isset( $_POST['site'] ) ? (array) wp_unslash( $_POST['site'] ) : array(); // phpcs:ignore
	$out = array();
	foreach ( array( 'brand_sentence', 'city', 'messenger_page', 'whatsapp', 'season', 'paleta', 'ribbon_ng', 'ribbon_stalna', 'ribbon_2', 'ribbon_3', 'ng_deadline' ) as $k ) {
		$out[ $k ] = sanitize_text_field( isset( $in[ $k ] ) ? $in[ $k ] : '' );
	}
	foreach ( array( 'facebook_url', 'instagram_url' ) as $k ) {
		$out[ $k ] = esc_url_raw( isset( $in[ $k ] ) ? $in[ $k ] : '' );
	}
	update_option( 'bumbaru_site', $out );
	bumbaru_admin_redirect( 'bumbaru-sajt' );
} );

function bumbaru_page_site() {
	bumbaru_admin_head( 'Podešavanja sajta', 'Tekstovi i linkovi koji se vide na celom sajtu. Prazno polje = podrazumevani tekst.' );
	$s    = get_option( 'bumbaru_site', array() );
	$d    = function_exists( 'bu_site_defaults' ) ? bu_site_defaults() : array();
	$val  = function ( $k ) use ( $s, $d ) {
		return isset( $s[ $k ] ) && '' !== $s[ $k ] ? $s[ $k ] : ( isset( $d[ $k ] ) ? $d[ $k ] : '' );
	};
	$rows = array(
		'season'         => array( 'Sezona na sajtu', 'select' ),
		'paleta'         => array( 'Boje sajta', 'paleta' ),
		'ribbon_ng'      => array( 'Traka na vrhu (Nova godina)', 'text' ),
		'ribbon_stalna'  => array( 'Traka na vrhu (ostatak godine)', 'text' ),
		'ribbon_2'       => array( 'Traka: druga poruka', 'text' ),
		'ribbon_3'       => array( 'Traka: treća poruka', 'text' ),
		'ng_deadline'    => array( 'Rok za Novu godinu (na proizvodima sa imenom)', 'text' ),
		'messenger_page' => array( 'Messenger (korisničko ime stranice)', 'text' ),
		'facebook_url'   => array( 'Facebook stranica', 'url' ),
		'instagram_url'  => array( 'Instagram (prazno = ne prikazuje se)', 'url' ),
		'whatsapp'       => array( 'WhatsApp broj (npr. 064 123 4567; prazno = ne prikazuje se)', 'text' ),
		'city'           => array( 'Radionica (grad)', 'text' ),
		'brand_sentence' => array( 'Rečenica u podnožju', 'text' ),
	);
	?>
	<form class="bu-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bumbaru_save_site" />
		<?php wp_nonce_field( 'bumbaru_save_site' ); ?>
		<table class="form-table" role="presentation">
			<?php foreach ( $rows as $k => $r ) : ?>
				<tr>
					<th scope="row"><label for="s-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $r[0] ); ?></label></th>
					<td>
						<?php if ( 'paleta' === $r[1] ) : ?>
							<select id="s-<?php echo esc_attr( $k ); ?>" name="site[<?php echo esc_attr( $k ); ?>]">
								<option value="svetla"<?php selected( $val( $k ), 'svetla' ); ?>>Svetle i vesele (bela pozadina, kao sada)</option>
								<option value="nova"<?php selected( $val( $k ), 'nova' ); ?>>Kao novi sajt (topla siva i krem)</option>
							</select>
							<p class="description">Menja samo boje pozadine i kartica; raspored je isti.</p>
						<?php elseif ( 'select' === $r[1] ) : ?>
							<select id="s-<?php echo esc_attr( $k ); ?>" name="site[<?php echo esc_attr( $k ); ?>]">
								<option value="nova-godina"<?php selected( $val( $k ), 'nova-godina' ); ?>>Nova godina</option>
								<option value="stalna"<?php selected( $val( $k ), 'stalna' ); ?>>Ostatak godine</option>
							</select>
						<?php else : ?>
							<input class="regular-text" id="s-<?php echo esc_attr( $k ); ?>" type="<?php echo 'url' === $r[1] ? 'url' : 'text'; ?>" name="site[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $val( $k ) ); ?>" style="width:min(560px,100%)" />
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<p><button class="button bu-btn" type="submit">Sačuvaj</button></p>
	</form>
	<?php if ( function_exists( 'bumbaru_mail_card' ) && current_user_can( 'manage_options' ) ) { bumbaru_mail_card(); } ?>
	<?php $st = bumbaru_srpski_status(); ?>
	<form class="bu-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bumbaru_srpski" />
		<?php wp_nonce_field( 'bumbaru_srpski' ); ?>
		<h2>Jezik: srpski, latinica</h2>
		<p>Jezik sajta: <strong><?php echo esc_html( $st['locale'] ); ?></strong><?php echo $st['jezik'] ? ' ✓' : ''; ?> · WooCommerce prevod: <strong><?php echo $st['wc'] ? 'preuzet ✓' : 'nije preuzet'; ?></strong></p>
		<p class="description">WordPress, WooCommerce (administracija, korpa, plaćanje, emailovi) i ostali dodaci su na srpskom. Zvanični prevodi su na ćirilici, a plugin ih prikazuje latinicom. Dugme ponovo podesi jezik i preuzme najnovije prevode.</p>
		<p><button class="button" type="submit">Prebaci na srpski i osveži prevode</button></p>
	</form>
	</div>
	<?php
}

/* ——— Prodavnica i uvoz ——— */

add_action( 'admin_post_bumbaru_setup_store', function () {
	check_admin_referer( 'bumbaru_setup_store' );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$done = bumbaru_setup_store( array( 'switch_theme' => ! empty( $_POST['switch_theme'] ) ) );
	set_transient( 'bumbaru_setup_log', $done, 300 );
	delete_option( 'bumbaru_show_welcome' );
	bumbaru_admin_redirect( 'bumbaru-uvoz', array( 'setup' => 1 ) );
} );

function bumbaru_page_setup() {
	bumbaru_admin_head( 'Prodavnica i uvoz', 'Dva koraka: (1) podesite prodavnicu, (2) uvezite proizvode sa starog sajta. Oba koraka možete ponoviti bez duplih proizvoda; cene koje ste upisali ostaju.' );
	$log = get_transient( 'bumbaru_setup_log' );
	if ( $log && isset( $_GET['setup'] ) ) { // phpcs:ignore
		delete_transient( 'bumbaru_setup_log' );
		echo '<div class="notice notice-success"><p><strong>Prodavnica je podešena:</strong></p><ul style="list-style:disc;padding-left:20px">';
		foreach ( $log as $l ) {
			echo '<li>' . esc_html( $l ) . '</li>';
		}
		echo '</ul></div>';
	}
	$cod      = get_option( 'woocommerce_cod_settings', array() );
	$zone_ok  = false;
	foreach ( WC_Shipping_Zones::get_zones() as $z ) {
		foreach ( $z['shipping_methods'] as $m ) {
			if ( 'free_shipping' === $m->id && 'yes' === $m->enabled ) {
				$zone_ok = $m->get_title();
			}
		}
	}
	$status = array(
		array( 'Tema Bumbaru aktivna', 'bumbaru' === get_stylesheet() ),
		array( 'Plaćanje pouzećem', isset( $cod['enabled'] ) && 'yes' === $cod['enabled'] ),
		array( 'Dostava: ' . ( $zone_ok ? $zone_ok . ', od ' . bumbaru_free_ship_label() : 'Post Express' ), (bool) $zone_ok ),
		array( 'Valuta dinar', 'RSD' === get_option( 'woocommerce_currency' ) ),
		array( 'Linkovi /proizvod/ i /katalog/', 'katalog' === ( (array) get_option( 'woocommerce_permalinks' ) + array( 'category_base' => '' ) )['category_base'] ),
		array( 'Proizvodi uvezeni (' . (int) wp_count_posts( 'product' )->publish . ')', (bool) get_option( 'bumbaru_import_done' ) ),
	);
	?>
	<div class="bu-grid">
		<div>
			<form class="bu-card honey" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="bumbaru_setup_store" />
				<?php wp_nonce_field( 'bumbaru_setup_store' ); ?>
				<h2>1. Podesi prodavnicu</h2>
				<ul style="list-style:disc;padding-left:20px">
					<li>Plaćanje pouzećem (ostali načini plaćanja isključeni)</li>
					<li>Dostava Post Express za celu Srbiju: besplatna od <?php echo esc_html( bumbaru_free_ship_label() ); ?>, ispod toga kupac plaća kuriru po cenovniku pošte</li>
					<li>Dinari („1.590 din”), prodaja samo u Srbiji, kupovina bez naloga</li>
					<li>Stranice Korpa i Plaćanje, Katalog, Po meri, Pomoć, Politika privatnosti, Pretraga</li>
					<li>Isti linkovi kao na starom sajtu: /proizvod/…/ i /katalog/…/</li>
				</ul>
				<p><label><input type="checkbox" name="switch_theme" value="1" checked /> Aktiviraj i temu Bumbaru</label></p>
				<p><button class="button bu-btn" type="submit">Podesi prodavnicu</button></p>
			</form>
			<div class="bu-card leaf">
				<h2>2. Uvezi proizvode</h2>
				<p>Uvozi <?php echo (int) bumbaru_import_count(); ?> proizvoda sa fotografijama, tekstovima, karakteristikama, pitanjima i konfiguratorima, kategorije sa tekstovima i filterima i preporuke „Uz ovo preporučujemo”.</p>
				<div class="bu-bar" data-import-bar><span></span></div>
				<ol class="bu-log" data-import-log></ol>
				<p><button class="button bu-btn" data-import-start data-nonce="<?php echo esc_attr( wp_create_nonce( 'bumbaru_import' ) ); ?>"><?php echo get_option( 'bumbaru_import_done' ) ? 'Uvezi ponovo' : 'Uvezi proizvode'; ?></button></p>
				<p data-import-done style="display:none"><strong>Gotovo.</strong> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Otvorite sajt</a> · <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">Proizvodi</a></p>
			</div>
		</div>
		<div class="bu-card">
			<h2>Stanje</h2>
			<ul class="bu-status">
				<?php foreach ( $status as $s ) : ?>
					<li><span class="<?php echo $s[1] ? 'ok' : 'no'; ?>"><?php echo $s[1] ? '✓' : '○'; ?></span><?php echo esc_html( $s[0] ); ?></li>
				<?php endforeach; ?>
			</ul>
			<h2 style="margin-top:20px">Gde se šta menja</h2>
			<ul style="list-style:disc;padding-left:20px">
				<li><strong>Cene i akcije:</strong> Proizvodi → proizvod → Opšte (Redovna i Akcijska cena). Bedž „−10%” se pojavi sam.</li>
				<li><strong>Cena iz troškova štampe:</strong> Bumbaru 3D → Kalkulator cene, ili u proizvodu „Kalkulator cene”.</li>
				<li><strong>Ime, motiv, boje, zvezdica, veličine:</strong> proizvod → kartica „Konfigurator”.</li>
				<li><strong>Tekstovi, karakteristike, pitanja:</strong> proizvod → kartica „Bumbaru”.</li>
				<li><strong>Boje u konfiguratorima:</strong> Bumbaru 3D → Boje.</li>
				<li><strong>Porudžbine:</strong> WooCommerce → Porudžbine; izbor kupca (ime, boje…) piše uz svaki proizvod, sa slikom pregleda.</li>
			</ul>
		</div>
	</div>
	</div>
	<?php
}
