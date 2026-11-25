<?php
/**
 * Strana proizvoda (prenesena iz proizvod/[slug].astro, nova verzija).
 *
 * Konfigurator (ime, motiv, boje, privezak sa imenom, izbor motiva i boja sa popustom,
 * slavski set, crtež u bojama, komplet, popusti za firme) i pregled uživo dolaze iz
 * plugina „Bumbaru 3D”. Proizvod sa cenom ide u WooCommerce korpu (server sam računa cenu),
 * a proizvod bez cene se poručuje preko Messengera.
 */
defined( 'ABSPATH' ) || exit;

the_post();
$product = wc_get_product( get_the_ID() );
if ( ! $product ) {
	get_template_part( '404' );
	return;
}

$plugin   = function_exists( 'bumbaru_config' );
$cfg      = $plugin ? bumbaru_config( $product ) : array( 'type' => '' );
$cat      = bu_primary_cat( $product );
$cat_url  = $cat ? get_term_link( $cat ) : wc_get_page_permalink( 'shop' );
$images   = bu_product_images( $product );
$type     = bu_config_type( $product );
$live     = $plugin && bumbaru_has_live( $product );
$extra    = $plugin && bumbaru_has_extra_slides( $product );
$mot      = $plugin && bumbaru_izbor_komada( $product );
$mot_boje = 'boje' === $type;
$custom   = (bool) bu_pmeta( $product, 'custom' );
$buy      = bu_buyable( $product );
$sku      = $product->get_sku() ? $product->get_sku() : (string) $product->get_id();
$title    = $product->get_name();
$url      = get_permalink( $product->get_id() );
$ng       = $custom && has_term( 'nova-godina', 'product_cat', $product->get_id() );
$lead     = bu_pmeta( $product, 'lead' );
$specs    = bu_lines( bu_pmeta( $product, 'specs' ) );
$details  = bu_lines( bu_pmeta( $product, 'details' ) );
$faq      = bu_lines( bu_pmeta( $product, 'faq' ) );
$related  = array_filter( array_map( 'wc_get_product', $product->get_upsell_ids() ) );
$ask_text = 'Zdravo, imam pitanje o proizvodu: ' . $title . ' (šifra ' . $sku . ")\n" . $url;
$card_img = bu_card_img( $product );
$orn      = in_array( $type, array( 'ukras', 'set' ), true );
$is_key   = 'privezak' === $type;
$pct      = bu_sale_pct( $product );
$vel_map  = (array) get_post_meta( $product->get_id(), '_bu_img_vel', true );
// Fotografija → motiv ukrasa / boja (galerija prati izbor) i fotografije napravljene uz AI (napomena)
$mot_map  = (array) get_post_meta( $product->get_id(), '_bu_img_mot', true );
$boja_map = (array) get_post_meta( $product->get_id(), '_bu_img_boja', true );
$ai_map   = (array) get_post_meta( $product->get_id(), '_bu_img_ai', true );
$img_attr = function ( $id ) use ( $mot_map, $boja_map ) {
	return ( isset( $mot_map[ $id ] ) ? ' data-motiv="' . esc_attr( $mot_map[ $id ] ) . '"' : '' ) . ( isset( $boja_map[ $id ] ) ? ' data-boja="' . esc_attr( $boja_map[ $id ] ) . '"' : '' );
};

// Bez fotografija proizvoda (npr. „sastavite svoj set”): prva je fotografija prvog motiva.
$mot_first = false;
if ( 'motivi' === $type && ! $product->get_gallery_image_ids() && ! $live ) {
	$images    = array();
	$mot_first = '' !== bumbaru_motiv_slides( $product );
}

// Cena za prikaz: cena izabrane (prve) vrednosti opcije ili osnovna cena, „od …” i „po komadu”.
$price_html = '';
$all        = $plugin ? bumbaru_all_prices( $product ) : array();
if ( $all ) {
	$oi        = bumbaru_price_option_index( $cfg );
	$n         = $oi >= 0 ? bumbaru_value_price( $product, $cfg['options'][ $oi ]['values'][0] ) : bumbaru_base_price( $product );
	if ( function_exists( 'bumbaru_set_cene' ) && bumbaru_set_cene( $product ) ) {
		$n = min( $all ); // set: cena najmanjeg seta („od …”), tačna cena je u „Napravite svoj set”
	}
	$old       = $oi >= 0 ? (float) $cfg['options'][ $oi ]['values'][0]['old'] : 0;
	$od        = ( $oi < 0 && bu_pmeta( $product, 'price_from' ) ) || bumbaru_razna_cena_motiva( $product );
	$price_html = bumbaru_price_markup( $product, $n, $od ? 'od ' : '', $mot ? ' po komadu' : '', $old );
} elseif ( '' !== $product->get_price() ) {
	$price_html = esc_html( bu_din( $product->get_price() ) );
}

// Strukturisani podaci (Product) za Google: cena, dostava, povraćaj.
// Proizvod bez cene ih nema (Google bi javio grešku „nedostaje offers”).
$ld_img = $images ? wp_list_pluck( $images, 'src' ) : array_filter( array( $card_img ) );
if ( $all || '' !== $product->get_price() ) {
	$eff   = $all ? array_map( function ( $x ) use ( $product ) { return bumbaru_eff( $product, $x ); }, $all ) : array( (float) $product->get_price() );
	$a     = function_exists( 'bumbaru_akcija' ) ? bumbaru_akcija() : null;
	$offer = array(
		'@type'                   => 'Offer',
		'priceCurrency'           => 'RSD',
		'price'                   => (string) min( $eff ),
		'availability'            => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
		'itemCondition'           => 'https://schema.org/NewCondition',
		'url'                     => $url,
		'seller'                  => array( '@id' => home_url( '/#store' ) ),
		'hasMerchantReturnPolicy' => bu_return_policy( $custom ),
	);
	// Dostava je besplatna tek od praga; ispod njega cena zavisi od cenovnika pošte, pa je ne navodimo.
	$ship = bu_shipping_details( $custom, (float) $offer['price'] );
	if ( $ship ) {
		$offer['shippingDetails'] = $ship;
	}
	if ( $pct && $plugin ) {
		list( $kind ) = bumbaru_discount( $product );
		if ( 'ak' === $kind && $a ) {
			$offer['priceValidUntil'] = substr( $a['do'], 0, 10 );
		} elseif ( $product->get_date_on_sale_to() ) {
			$offer['priceValidUntil'] = $product->get_date_on_sale_to()->date( 'Y-m-d' );
		}
	}
	$ld = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'name'        => $title,
		'sku'         => $sku,
		'description' => $lead ? $lead : wp_strip_all_tags( $product->get_short_description() ),
		'brand'       => array( '@type' => 'Brand', 'name' => 'Bumbaru' ),
		'image'       => $ld_img,
		'url'         => $url,
		'category'    => $cat ? $cat->name : '',
		'offers'      => $offer,
	);
	// Ocene i utisci kupaca (samo odobreni; bez njih Google ne dobija ocenu).
	if ( function_exists( 'bumbaru_utisci_ld' ) ) {
		$ld = array_merge( $ld, bumbaru_utisci_ld( $product->get_id() ) );
	}
	if ( count( array_unique( $eff ) ) > 1 ) {
		$ld['offers'] = array(
			'@type'         => 'AggregateOffer',
			'priceCurrency' => 'RSD',
			'lowPrice'      => (string) min( $eff ),
			'highPrice'     => (string) max( $eff ),
			'offerCount'    => count( $eff ),
			'offers'        => array( $offer ),
		);
	}
	bu_add_jsonld( $ld );
}
// Česta pitanja o proizvodu (koriste ih i AI pretrage).
if ( $faq ) {
	$fq = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array() );
	foreach ( $faq as $f ) {
		$fq['mainEntity'][] = array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $f['a'] ) ) );
	}
	bu_add_jsonld( $fq );
}
if ( $orn ) {
	$GLOBALS['bu_ornaments'] = true;
}

// Ikonice za karakteristike i „Dobro je da znate” (kao na novom sajtu).
$spec_icon   = function ( $k ) {
	$s = mb_strtolower( $k, 'UTF-8' );
	$m = array(
		'motiv' => 'star', 'oblik' => 'star', 'stil' => 'star', 'boj' => 'palette', 'ime' => 'pen', 'materijal' => 'layers',
		'pakovanje' => 'gift', 'u setu' => 'gift', 'u kompletu' => 'gift', 'u pakovanju' => 'gift', 'rok' => 'calendar',
		'plaćanje' => 'cash', 'karika' => 'key', 'kačenje' => 'key', 'svetlo' => 'bulb', 'montaža' => 'tool',
		'sklapanje' => 'blocks', 'debljin' => 'ruler', 'dimenzij' => 'ruler', 'visin' => 'ruler', 'veličin' => 'ruler',
	);
	foreach ( $m as $pre => $ic ) {
		if ( 0 === strpos( $s, $pre ) ) {
			return $ic;
		}
	}
	return 'check';
};
$detail_icon = function ( $q ) {
	$s = mb_strtolower( $q, 'UTF-8' );
	if ( 0 === strpos( $s, 'pakovanje' ) ) {
		return 'gift';
	}
	if ( 0 === strpos( $s, 'rok' ) || false !== strpos( $s, 'dostav' ) ) {
		return 'truck';
	}
	if ( 0 === strpos( $s, 'povraćaj' ) || false !== strpos( $s, 'reklamac' ) ) {
		return 'shield';
	}
	if ( 0 === strpos( $s, 'održavanje' ) ) {
		return 'sparkle';
	}
	if ( false !== strpos( $s, 'led' ) ) {
		return 'bulb';
	}
	return 'check';
};
$about_title = $orn ? ( 'set' === $type ? 'O setu' : 'O ukrasu' ) : 'O proizvodu';
$sa_imenom   = in_array( $type, array( 'ukras', 'set', 'privezak', 'ime', 'imena' ), true );
$korak1      = $sa_imenom ? 'Upišite ime i izaberite boje' : ( $mot_boje ? 'Izaberite boje i količinu' : ( $mot ? 'Izaberite motive' : ( ! empty( $cfg['options'] ) || ! empty( $cfg['izbor_boja'] ) ? 'Izaberite šta vam treba' : 'Izaberite količinu' ) ) );
if ( 'sastav' === $type ) {
	$korak1 = 'Izaberite modlice za set';
} elseif ( 'scena' === $type ) {
	$korak1 = 'Sastavite set: tacna, lutkice, jelke';
} elseif ( 'jelka' === $type ) {
	$korak1 = 'Izaberite zvezdicu i visinu';
}
$bu_data = $plugin ? bumbaru_product_data( $product ) : array( 'id' => $sku, 'pid' => $product->get_id(), 'type' => '', 'cena' => (float) $product->get_price(), 'od' => false, 'buy' => $buy, 'popust' => array( 'vrsta' => '', 'v' => 0, 'naziv' => '' ), 'setExtra' => 0, 'naslov' => $title, 'url' => $url, 'slika' => $card_img );
$show_qty = ! $is_key && ! $mot && ! in_array( $type, array( 'sastav', 'scena' ), true );

get_header();
?>
<div class="paper-zone pz">
	<div class="wrap">
		<?php
		$crumbs = array();
		if ( $cat ) {
			$crumbs[] = array( 'label' => $cat->name, 'href' => $cat_url );
		}
		$crumbs[] = array( 'label' => $title );
		bu_crumbs( $crumbs );
		bu_print_notices();
		?>
	</div>
	<div class="wrap pd">
		<!-- Galerija -->
		<div class="gallery" data-gallery<?php echo ( $live || in_array( $type, array( 'motivi', 'sastav', 'scena', 'boje', 'ukras', 'set', 'privezak' ), true ) ) ? ' data-mini' : ''; ?>>
			<div class="stage">
				<?php
				if ( $live ) {
					echo bumbaru_live_slide( $product ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				if ( $plugin ) {
					echo bumbaru_extra_slides( $product ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				$first_hidden = $live || in_array( $type, array( 'sastav', 'scena' ), true );
				foreach ( $images as $i => $im ) :
					$hidden = $first_hidden || $i > 0;
					$eager  = 0 === $i && ! $first_hidden;
					$vel    = isset( $vel_map[ $im['id'] ] ) ? (int) $vel_map[ $im['id'] ] : null;
					?>
					<div class="slide" data-slide="i<?php echo (int) $i; ?>"<?php echo null !== $vel ? ' data-vel="' . (int) $vel . '"' : ''; ?><?php echo $img_attr( $im['id'] ); // phpcs:ignore ?><?php echo $hidden ? ' hidden' : ''; ?>>
						<?php if ( $im['w'] !== $im['h'] ) : ?>
							<img class="backdrop" src="<?php echo esc_url( $im['sq'] ); ?>" alt="" width="900" height="900" loading="lazy" aria-hidden="true" />
						<?php endif; ?>
						<img class="full" src="<?php echo esc_url( $im['src'] ); ?>"<?php echo $im['srcset'] ? ' srcset="' . esc_attr( $im['srcset'] ) . '" sizes="(max-width: 960px) 100vw, 680px"' : ''; ?> alt="<?php echo esc_attr( $im['alt'] ? $im['alt'] : $title ); ?>" width="<?php echo (int) $im['w']; ?>" height="<?php echo (int) $im['h']; ?>" loading="<?php echo $eager ? 'eager' : 'lazy'; ?>"<?php echo $eager ? ' fetchpriority="high"' : ''; ?> decoding="async" />
						<?php if ( ! empty( $ai_map[ $im['id'] ] ) ) : ?><p class="ai-note">Fotografija je napravljena uz AI: pravi proizvod može malo da se razlikuje.</p><?php endif; ?>
					</div>
				<?php endforeach; ?>
				<?php
				if ( $plugin ) {
					echo bumbaru_motiv_slides( $product, $mot_first ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
				<?php if ( ! $live && ! $images && ! $mot_first && 'sastav' !== $type ) : ?>
					<div class="slide"><div class="soon-photo"><img src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" /><span>Fotografija uskoro</span></div></div>
				<?php endif; ?>
			</div>
			<?php if ( $extra ? count( $images ) > 0 : count( $images ) > 1 ) : ?>
				<div class="thumbs" role="group" aria-label="Fotografije">
					<?php echo $plugin ? bumbaru_extra_thumbs( $product ) : ''; // phpcs:ignore ?>
					<?php
					foreach ( $images as $i => $im ) :
						$vel = isset( $vel_map[ $im['id'] ] ) ? (int) $vel_map[ $im['id'] ] : null;
						?>
						<button type="button" class="thumb" data-thumb="i<?php echo (int) $i; ?>"<?php echo null !== $vel ? ' data-vel="' . (int) $vel . '"' : ''; ?><?php echo $img_attr( $im['id'] ); // phpcs:ignore ?> aria-pressed="<?php echo ( ! $first_hidden && 0 === $i ) ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( 'Fotografija ' . ( $i + 1 ) . ': ' . ( $im['alt'] ? $im['alt'] : $title ) ); ?>"><img src="<?php echo esc_url( $im['sq'] ); ?>" alt="" width="120" height="120" loading="lazy" /></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( $is_key ) : ?>
				<p class="kc-note" role="note"><?php echo bu_icon( 'camera', 20 ); ?><span><strong>Pregled je okviran.</strong> Pokazuje vaša imena i izabrane boje, a pravi izgled priveska vidi se na fotografijama. Oblik slova, debljina i ivice na gotovom privesku mogu da odudaraju od pregleda.</span></p>
			<?php endif; ?>
		</div>

		<!-- Podaci i porudžbina -->
		<div class="info">
			<div class="badges">
				<?php if ( $custom ) : ?>
					<span class="badge badge-custom">Po meri</span><span class="badge">Izrada 3–5 radnih dana</span>
				<?php elseif ( $cat ) : ?>
					<span class="badge"><?php echo esc_html( $cat->name ); ?></span>
				<?php endif; ?>
			</div>
			<h1 class="h1"><?php echo esc_html( $title ); ?></h1>
			<?php
			if ( function_exists( 'bumbaru_utisci_ocena' ) ) {
				list( $rv_avg, $rv_n ) = bumbaru_utisci_ocena( $product->get_id() );
				if ( $rv_n ) {
					echo '<a class="rv-top" href="#utisci">' . bumbaru_zvezdice( $rv_avg, 16 ) . '<span>' . esc_html( str_replace( '.', ',', number_format( $rv_avg, 1 ) ) . ' · utisci kupaca (' . $rv_n . ')' ) . '</span></a>'; // phpcs:ignore
				}
			}
			?>
			<?php
			if ( $pct && 'ak' === $bu_data['popust']['vrsta'] ) {
				echo bu_odbrojavanje( true ); // phpcs:ignore
			}
			?>
			<?php if ( $price_html ) : ?>
				<p class="price" data-price-el><?php echo $price_html; // phpcs:ignore -- očišćeno u funkciji ?></p>
			<?php elseif ( 'nova-godina' === ( $cat ? $cat->slug : '' ) || $plugin ) : ?>
				<p class="price">Cena na upit <span class="muted" style="font-size:15px;font-weight:600">· javljamo u Messenger-u</span></p>
			<?php endif; ?>
			<?php if ( $lead ) : ?><p class="lead" style="color:var(--ink)"><?php echo esc_html( $lead ); ?></p><?php endif; ?>

			<?php $bu_set = $plugin && ! empty( bumbaru_config( $product )['komplet']['glavni'] ); // set: kupuje se samo kroz „Napravite svoj set” ?>
			<form class="order<?php echo $bu_set ? ' is-set' : ''; ?>" id="poruci" data-order-form data-id="<?php echo esc_attr( $sku ); ?>" data-personal="<?php echo esc_attr( $type ); ?>" data-bu="<?php echo esc_attr( wp_json_encode( $bu_data ) ); ?>" action="<?php echo esc_url( $url ); ?>" method="post" novalidate>
				<?php
				if ( $plugin ) {
					echo bumbaru_config_fields( $product ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
				<div class="field">
					<label for="f-nap">Napomena <span style="text-transform:none;letter-spacing:0;font-weight:400">(nije obavezno)</span></label>
					<input id="f-nap" class="input" name="napomena" maxlength="160" placeholder="<?php echo esc_attr( $custom ? 'npr. poruka za karticu u paketu' : ( $mot ? 'npr. rok ili poruka za karticu' : 'npr. rok ili poruka za karticu' ) ); ?>" autocomplete="off" />
				</div>
				<input type="hidden" name="bu_json" value="" />
				<?php if ( $buy ) : ?>
					<input type="hidden" name="add-to-cart" value="<?php echo (int) $product->get_id(); ?>" />
				<?php endif; ?>
				<div class="buy">
					<?php if ( $show_qty ) : ?>
						<div class="qty" role="group" aria-label="Količina">
							<button type="button" data-dec aria-label="Smanji količinu">−</button>
							<input type="number" name="quantity" value="1" min="1" max="50" inputmode="numeric" aria-label="Količina" />
							<button type="button" data-inc aria-label="Povećaj količinu">+</button>
						</div>
					<?php else : ?>
						<input type="hidden" name="quantity" value="1" />
					<?php endif; ?>
					<?php if ( $buy ) : ?>
						<button class="btn btn-lg add-btn" type="button" data-add><?php echo bu_icon( 'bag' ); ?>Dodaj u korpu</button>
					<?php endif; ?>
				</div>
				<?php if ( $buy ) : ?>
					<button class="btn btn-primary btn-lg buy-btn" type="submit" data-checkout><?php echo bu_icon( 'cash' ); ?>Poruči odmah, plaćanje pouzećem</button>
					<button class="btn btn-lg btn-block btn-msg-order" type="button" data-messenger-order><?php echo bu_icon( 'messenger' ); ?>Poruči preko Messengera</button>
				<?php else : ?>
					<button class="btn btn-primary btn-lg buy-btn" type="submit"><?php echo bu_icon( 'messenger' ); ?>Poruči preko Messengera</button>
				<?php endif; ?>
				<ul class="notes small">
					<?php if ( $ng ) : ?><li><?php echo bu_icon( 'calendar', 18 ); ?><?php echo esc_html( bu_site( 'ng_deadline' ) ); ?></li><?php endif; ?>
					<?php if ( $is_key ) : ?><li><?php echo bu_icon( 'camera', 18 ); ?>Pregled je okviran, a pravi izgled priveska je na fotografijama.</li><?php endif; ?>
					<?php if ( $buy ) : ?>
						<li><?php echo bu_icon( 'cash', 18 ); ?>Plaćate pouzećem, kuriru kad paket stigne. Bez naloga i bez kartice.</li>
						<li><?php echo bu_icon( 'truck', 18 ); ?>Post Express stiže za 1–2 radna dana. Od <?php echo esc_html( bu_free_ship_label() ); ?> dostava je besplatna.</li>
						<li><?php echo bu_icon( 'bag', 18 ); ?>Uzimate više stvari? Dodajte ih u korpu i poručite sve odjednom.</li>
					<?php else : ?>
						<li><?php echo bu_icon( 'cash', 18 ); ?>Plaćate kuriru kad paket stigne. Cenu i dostavu javljamo u odgovoru.</li>
						<li><?php echo bu_icon( 'messenger', 18 ); ?>Poruka se složi sama: naziv, šifra <?php echo esc_html( $sku ); ?>, vaš izbor i link proizvoda.</li>
					<?php endif; ?>
				</ul>
			</form>

			<?php
			if ( $plugin ) {
				echo bumbaru_poziv_html( $product ); // phpcs:ignore
				echo bumbaru_komplet_html( $product ); // phpcs:ignore
				echo bumbaru_firme_html( $product ); // phpcs:ignore
			}
			?>
			<div class="trail" aria-hidden="true"></div>
			<ul class="perks">
				<li><span class="ti"><?php echo bu_icon( 'cash' ); ?></span><span><strong>Plaćanje pouzećem</strong><span class="small muted">Plaćate kuriru kad paket stigne.</span></span></li>
				<li><span class="ti"><?php echo bu_icon( 'truck' ); ?></span><span><strong>Besplatna dostava od <?php echo esc_html( bu_free_ship_label() ); ?></strong><span class="small muted">Post Express za 1–2 radna dana.</span></span></li>
				<?php if ( $custom ) : ?>
					<li><span class="ti"><?php echo bu_icon( 'shield' ); ?></span><span><strong>Proveravamo svako ime</strong><span class="small muted">Pre štampe, a ako nešto nije jasno, pišemo vam.</span></span></li>
				<?php else : ?>
					<li><span class="ti"><?php echo bu_icon( 'box' ); ?></span><span><strong>Vraćanje 14 dana</strong><span class="small muted">Bez navođenja razloga.</span></span></li>
				<?php endif; ?>
			</ul>
			<div class="slicno">
				<img class="slicno-bee fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="64" height="64" loading="lazy" />
				<div>
					<h2 class="h3">Tražite nešto slično?</h2>
					<p class="small">Imamo još sličnih modela koji nisu na sajtu, a možemo da odštampamo i ono što ste videli negde drugde. Pošaljite nam fotografiju ili link, ili pitajte bilo šta pre porudžbine.</p>
					<a class="btn" href="<?php echo esc_url( bu_messenger_url( 'Zdravo! Tražim nešto slično kao ' . $title . ' (šifra ' . $sku . '). Šaljem fotografiju ili link:', $sku ) ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Pošaljite fotografiju ili link</a>
				</div>
			</div>
		</div>
	</div>

	<div class="wrap lower">
		<!-- O proizvodu i karakteristike -->
		<section class="about-grid" aria-labelledby="o-proizvodu">
			<div class="about-card reveal">
				<img class="about-bee fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" loading="lazy" />
				<span class="badge badge-honey"><?php echo esc_html( $custom ? 'Po meri · 3–5 dana' : ( $cat ? $cat->name : 'Bumbaru' ) ); ?></span>
				<h2 id="o-proizvodu" class="h1"><?php echo esc_html( $about_title ); ?></h2>
				<svg class="draw-trail" viewBox="0 0 260 24" aria-hidden="true">
					<defs><mask id="dtm" maskUnits="userSpaceOnUse" x="0" y="0" width="260" height="24"><path class="dt-m" pathLength="100" d="M4 16 C60 2 110 26 160 12 S 230 4 256 10" fill="none" stroke="#fff" stroke-width="12" stroke-linecap="round" /></mask></defs>
					<path d="M4 16 C60 2 110 26 160 12 S 230 4 256 10" fill="none" stroke="#312018" stroke-width="4.5" stroke-linecap="round" stroke-dasharray="0 11" mask="url(#dtm)" />
				</svg>
				<div class="about-text"><?php echo wp_kses_post( wpautop( $product->get_description() ) ); ?></div>
				<a class="btn btn-primary btn-lg" href="#poruci"><?php echo bu_icon( $buy ? 'bag' : 'messenger' ); ?><?php echo esc_html( $sa_imenom ? 'Upišite ime i poručite' : ( $buy ? 'Izaberite i poručite' : 'Poručite preko Messengera' ) ); ?></a>
			</div>
			<?php if ( $specs ) : ?>
				<div class="specs-wrap">
					<h3 class="label specs-title"><?php echo bu_icon( 'sparkle', 16 ); ?>Karakteristike</h3>
					<ul class="spec-grid">
						<?php foreach ( $specs as $i => $sp ) : ?>
							<li class="spec reveal tone-<?php echo (int) ( $i % 4 ); ?>" style="--d:<?php echo (int) $i; ?>">
								<span class="spec-ic"><?php echo bu_icon( $spec_icon( $sp['q'] ), 20 ); ?></span>
								<span class="spec-k"><?php echo esc_html( $sp['q'] ); ?></span>
								<span class="spec-v"><?php echo bu_spec_value( $sp['a'] ); // phpcs:ignore -- očišćeno u funkciji ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</section>

		<?php if ( $details ) : ?>
			<!-- Dobro je da znate -->
			<section class="section" aria-labelledby="znate-title">
				<div class="sec-head reveal">
					<div><h2 id="znate-title" class="h1">Dobro je da znate</h2><p>Pakovanje, rokovi i šta ako nešto nije u redu.</p></div>
				</div>
				<ul class="know-grid">
					<?php foreach ( $details as $i => $d ) : ?>
						<li class="know-card reveal tone-<?php echo (int) ( ( $i + 1 ) % 4 ); ?>" style="--d:<?php echo (int) $i; ?>">
							<span class="know-ic"><?php echo bu_icon( $detail_icon( $d['q'] ), 24 ); ?></span>
							<h3 class="h3"><?php echo esc_html( $d['q'] ); ?></h3>
							<p><?php echo wp_kses_post( $d['a'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<!-- Poručivanje u tri koraka -->
		<section class="section" aria-labelledby="kako-p">
			<div class="howto reveal">
				<div class="howto-head">
					<div>
						<span class="label">Bez naloga, plaćanje pouzećem</span>
						<h2 id="kako-p" class="h1">Poručivanje u tri koraka</h2>
					</div>
					<a class="btn btn-paper btn-lg" href="#poruci"><?php echo bu_icon( $buy ? 'bag' : 'messenger' ); ?><?php echo $buy ? 'Poruči preko sajta' : 'Poruči preko Messengera'; ?></a>
				</div>
				<ol class="howto-steps">
					<li class="reveal" style="--d:1"><span class="num">1</span><div><strong><?php echo esc_html( $korak1 ); ?></strong><p class="small"><?php echo esc_html( $orn || 'jelka' === $type ? 'Pregled odmah pokazuje kako će izgledati.' : ( $is_key ? 'Okvirni pregled pokazuje vaša imena i boje.' : 'Napomenu možete dodati pre porudžbine.' ) ); ?></p></div></li>
					<?php if ( $buy ) : ?>
						<li class="reveal" style="--d:2"><span class="num">2</span><div><strong>Dodajte u korpu i poručite</strong><p class="small">Upišete ime, adresu i telefon, bez naloga. Ili pošaljete poruku na Messenger-u.</p></div></li>
						<li class="reveal" style="--d:3"><span class="num">3</span><div><strong>Plaćate kuriru</strong><p class="small">Paket stiže Post Expressom za 1–2 radna dana. Od <?php echo esc_html( bu_free_ship_label() ); ?> dostava je besplatna.</p></div></li>
					<?php else : ?>
						<li class="reveal" style="--d:2"><span class="num">2</span><div><strong>Pošaljite poruku</strong><p class="small">Naziv, šifra <?php echo esc_html( $sku ); ?> i link proizvoda su već u poruci.</p></div></li>
						<li class="reveal" style="--d:3"><span class="num">3</span><div><strong>Plaćate kuriru</strong><p class="small">Javljamo cenu i rok, a paket stiže Post Expressom širom Srbije.</p></div></li>
					<?php endif; ?>
				</ol>
			</div>
		</section>

		<?php
		// Utisci kupaca: ocena, komentar, do 2 fotografije, odgovori (objavljuju se posle odobrenja).
		if ( function_exists( 'bumbaru_utisci_html' ) ) {
			echo bumbaru_utisci_html( $product ); // phpcs:ignore -- očišćeno u pluginu
		}
		?>

		<?php if ( $faq ) : ?>
			<!-- Česta pitanja -->
			<section class="section" aria-labelledby="faq-p">
				<div class="faq-grid">
					<div class="faq-side reveal">
						<h2 id="faq-p" class="h1">Česta pitanja</h2>
						<p class="muted">O ovom proizvodu, ukratko.</p>
						<div class="help-card">
							<img class="fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" loading="lazy" />
							<h3 class="h3">Imate drugo pitanje?</h3>
							<p class="small">Pišite nam pre porudžbine, odgovaramo u Messenger-u.</p>
							<a class="btn btn-paper" href="<?php echo esc_url( bu_messenger_url( $ask_text, $sku ) ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Pitajte na Messenger-u</a>
						</div>
					</div>
					<div class="faq-box reveal" style="--d:1"><?php bu_accordion( $faq, 0 ); ?></div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $related ) : ?>
			<!-- Preporuke -->
			<section class="section" aria-labelledby="rel-title">
				<div class="sec-head reveal">
					<div class="rel-h">
						<img class="fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" loading="lazy" />
						<div><h2 id="rel-title" class="h1">Uz ovo preporučujemo</h2><p>Proizvodi koji lepo idu uz ovaj.</p></div>
					</div>
					<a class="link-arrow" href="<?php echo esc_url( $cat_url ); ?>">Ceo katalog<?php echo bu_icon( 'arrow' ); ?></a>
				</div>
				<div class="grid rail-mobile">
					<?php foreach ( array_values( $related ) as $i => $r ) : ?>
						<div class="reveal" style="--d:<?php echo (int) $i; ?>"><?php bu_product_card( $r ); ?></div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
		<?php echo bu_nedavno( $sku ); // phpcs:ignore ?>
	</div>
</div>
<div class="sbar" data-sbar aria-hidden="true" hidden>
	<div class="sbar-in">
		<span class="sbar-img"><img src="<?php echo esc_url( $card_img ? $card_img : bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="48" height="48" loading="lazy" /></span>
		<span class="sbar-t"><span class="sbar-name"><?php echo esc_html( $title ); ?></span><b class="sbar-cena" data-sbar-cena></b></span>
		<?php if ( $buy ) : ?>
			<button class="icon-btn sbar-add" type="button" data-sbar-add tabindex="-1" aria-label="Dodaj u korpu"><?php echo bu_icon( 'bag', 20 ); ?></button>
			<button class="btn btn-primary sbar-buy" type="button" data-sbar-buy tabindex="-1"><?php echo bu_icon( 'cash', 18 ); ?>Poruči</button>
		<?php else : ?>
			<button class="btn btn-primary sbar-buy" type="button" data-sbar-buy tabindex="-1"><?php echo bu_icon( 'messenger', 18 ); ?>Poruči</button>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
