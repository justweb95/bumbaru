<?php
/**
 * Početna strana (prenesena iz index.astro).
 */
defined( 'ABSPATH' ) || exit;

$faq = array(
	array( 'q' => 'Kako se poručuje?', 'a' => 'Izaberite proizvod, upišite ime ili izaberite boju i kliknite „Poruči preko sajta”: upišete ime, adresu i telefon i potvrdite porudžbinu, bez naloga. Ili kliknite „Poruči preko Messengera”, pa se poruka sa vašim izborom složi sama. <a href="' . esc_url( bu_page_url( 'pomoc' ) ) . '#kako-poruciti">Više u Pomoći</a>' ),
	array( 'q' => 'Kako se plaća?', 'a' => 'Pouzećem, kuriru kad paket stigne. Kartica i nalog nisu potrebni. <a href="' . esc_url( bu_page_url( 'pomoc' ) ) . '#placanje">Više u Pomoći</a>' ),
	array( 'q' => 'Koliko košta dostava?', 'a' => 'Za porudžbine od ' . bu_free_ship_label() . ' dostava je besplatna, a ispod toga je plaćate kuriru po cenovniku Post Express-a. Paket stiže za 1–2 radna dana od slanja. <a href="' . esc_url( bu_page_url( 'pomoc' ) ) . '#dostava">Više u Pomoći</a>' ),
	array( 'q' => 'Kada stiže?', 'a' => 'Proizvodi sa imenom se prave 3–5 radnih dana, a kurir nosi 1–2 radna dana. <a href="' . esc_url( bu_page_url( 'pomoc' ) ) . '#rokovi">Više u Pomoći</a>' ),
	array( 'q' => 'Mogu li ime da napišem ćirilicom?', 'a' => 'Možete. Štampamo tačno kako upišete, latinicom ili ćirilicom.' ),
	array( 'q' => 'Mogu li da pošaljem poklon na drugu adresu?', 'a' => 'Možete, ali primalac plaća kuriru, jer je plaćanje pouzećem.' ),
	array( 'q' => 'Mogu li da vratim proizvod?', 'a' => 'Proizvode bez imena u roku od 14 dana od prijema. Proizvode sa imenom samo zbog greške ili oštećenja. <a href="' . esc_url( bu_page_url( 'pomoc' ) ) . '#reklamacije-i-povracaj">Više u Pomoći</a>' ),
);
bu_set_seo( 'Ukrasi za jelku sa imenom, personalizovani pokloni | Bumbaru', 'Personalizovani ukrasi za jelku sa imenom, latinicom ili ćirilicom, novogodišnja dekoracija i privesci iz 3D štampe. Pouzećem, besplatna dostava od 2.500 din.' );
$faq_ld = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array() );
foreach ( $faq as $f ) {
	$faq_ld['mainEntity'][] = array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $f['a'] ) ) );
}
bu_add_jsonld( $faq_ld );
$GLOBALS['bu_ornaments'] = true;

// Novogodišnji katalog na početnoj: proizvodi sa „Na početnoj, mesto” > 0, tim redom.
$ng = array();
if ( function_exists( 'wc_get_product' ) ) {
	$q = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'fields'         => 'ids',
		'meta_key'       => '_bu_home_order',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'meta_query'     => array( array( 'key' => '_bu_home_order', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' ) ),
		'no_found_rows'  => true,
	) );
	$ng = array_filter( array_map( 'wc_get_product', $q->posts ) );
	if ( ! $ng ) {
		$ng = wc_get_products( array( 'status' => 'publish', 'limit' => 12, 'featured' => true ) );
	}
}

$hang = array(
	array( 'x' => 110, 'hy' => 180, 'rot' => -3, 's' => 0.84, 'motif' => 'pahulja', 'color' => 'crvena', 'nameColor' => 'bela', 'fb' => 'Luka', 'key' => 'a', 'cls' => 'sway sway-1' ),
	array( 'x' => 530, 'hy' => 225, 'rot' => 3, 's' => 0.84, 'motif' => 'masna', 'color' => 'crvena', 'nameColor' => 'bela', 'fb' => 'Neda', 'key' => 'c', 'cls' => 'sway sway-3' ),
	array( 'x' => 330, 'hy' => 110, 'rot' => 0, 's' => 1.12, 'motif' => 'zvezde', 'color' => 'crvena', 'nameColor' => 'bela', 'fb' => 'Mila', 'key' => 'b', 'cls' => 'sway' ),
);
$prednosti = array(
	array( 'cash', 'Plaćanje pouzećem', 'Plaćate kuriru kad paket stigne.' ),
	array( 'truck', 'Besplatna dostava od ' . bu_free_ship_label(), 'Post Express za 1–2 radna dana.' ),
	array( 'star', 'Izrada po meri', 'Ime, mera ili boja: 3–5 radnih dana.' ),
	array( 'shield', 'Proveravamo svako ime', 'Pre štampe, a ako nešto nije jasno, pišemo vam.' ),
);
$ukras_url = bu_product_url( 'ukras-za-jelku-sa-imenom' );
$ng_url    = bu_cat_url( 'nova-godina' );

get_header();
?>
<div class="paper-zone">
	<!-- Vrh: sezona -->
	<section class="wrap hero-sec" aria-labelledby="hero-title">
		<div class="hero">
			<div class="hero-media">
				<picture class="hero-bg">
					<source media="(min-width: 901px)" srcset="<?php echo esc_url( bu_img( 'bg/hero-nova-godina.webp' ) ); ?>" />
					<img src="<?php echo esc_url( bu_img( 'bg/hero-nova-godina-tel.webp' ) ); ?>" alt="" width="1000" height="562" fetchpriority="high" />
				</picture>
				<div class="hero-stage">
					<svg class="hero-trail" aria-hidden="true" width="300" height="240" viewBox="0 0 300 240">
						<defs><mask id="trailMask" maskUnits="userSpaceOnUse" x="0" y="0" width="300" height="240"><path class="fx-trail" pathLength="100" d="M8 232 C40 130 120 160 160 100 S 250 30 292 40" fill="none" stroke="#fff" stroke-width="14" stroke-linecap="round" /></mask></defs>
						<path d="M8 232 C40 130 120 160 160 100 S 250 30 292 40" fill="none" stroke="#312018" stroke-width="5" stroke-linecap="round" stroke-dasharray="0 12" mask="url(#trailMask)" />
					</svg>
					<span class="hero-bee fx-bee"><img class="fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" /></span>
					<?php
					// Dve kartice u vrhu: glavna slika proizvoda iz WooCommerce-a (menja se u samom proizvodu).
					$hero_cards = array(
						array( 'irvas-lutkica-pleteni-izgled', 'Irvas lutkica', '-3deg', '' ),
						array( 'spiralna-jelka-sa-zvezdom', 'Spiralna jelka sa zvezdom', '3deg', ' fc-2' ),
					);
					foreach ( $hero_cards as $i => $hc ) :
						$hp  = function_exists( 'bu_product_by_slug' ) ? bu_product_by_slug( $hc[0] ) : null;
						$img = $hp ? wp_get_attachment_image_src( $hp->get_image_id(), 'woocommerce_single' ) : false;
						if ( ! $img ) {
							continue;
						}
						$alt = trim( (string) get_post_meta( $hp->get_image_id(), '_wp_attachment_image_alt', true ) );
						?>
						<a class="float-card<?php echo esc_attr( $hc[3] ); ?> fx-drop" style="--d:<?php echo (int) $i; ?>;--r:<?php echo esc_attr( $hc[2] ); ?>" href="<?php echo esc_url( get_permalink( $hp->get_id() ) ); ?>">
							<span class="fc-img" style="aspect-ratio:1/1"><img src="<?php echo esc_url( $img[0] ); ?>" alt="<?php echo esc_attr( $alt ? $alt : $hp->get_name() ); ?>" width="<?php echo (int) $img[1]; ?>" height="<?php echo (int) $img[2]; ?>" /></span>
							<span class="fc-label"><?php echo esc_html( $hc[1] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="hero-intro">
				<span class="badge badge-honey fx-rise" style="--d:0">Novogodišnji katalog</span>
				<h1 id="hero-title" class="display fx-rise" style="--d:1">Ukrasi za jelku sa imenom</h1>
				<p class="lead fx-rise" style="--d:2">Upišite ime, izaberite motiv i boju i odmah vidite kako ukras izgleda. U katalogu su i privesci, patuljak sa LED svećicom i dekoracija za praznični sto.</p>
			</div>
			<div class="hero-cta">
				<dl class="facts fx-rise" style="--d:3">
					<div><dt>Ukras sa imenom</dt><dd>4 motiva, 4 boje</dd></div>
					<div><dt>Porodični set</dt><dd>4–5 imena u kutiji</dd></div>
				</dl>
				<div class="btns fx-rise" style="--d:4">
					<a class="btn btn-primary btn-lg" href="<?php echo esc_url( $ukras_url ); ?>">Upišite ime</a>
					<a class="btn btn-paper btn-lg" href="<?php echo esc_url( $ng_url ); ?>">Pogledajte katalog</a>
				</div>
			</div>
		</div>
	</section>

	<!-- Novogodišnji katalog -->
	<section class="wrap section" aria-labelledby="ng-title">
		<div class="sec-head">
			<div>
				<h2 id="ng-title" class="h2">Novogodišnji katalog</h2>
				<p>Ukrasi, privesci i dekoracija za praznični sto.</p>
				<?php $odb = bu_odbrojavanje(); if ( $odb ) : ?><div style="margin-top:12px"><?php echo $odb; // phpcs:ignore ?></div><?php endif; ?>
			</div>
			<a class="link-arrow" href="<?php echo esc_url( $ng_url ); ?>">Ceo novogodišnji katalog<?php echo bu_icon( 'arrow' ); ?></a>
		</div>
		<div class="grid grid-3 rail-mobile">
			<?php foreach ( $ng as $i => $p ) { bu_product_card( $p, $i < 3 ); } ?>
		</div>
	</section>
	<div class="wrap"><?php echo bu_nedavno(); // phpcs:ignore ?></div>

	<!-- Vaše ime na ukrasu -->
	<section class="wrap section reveal" aria-labelledby="ukras-title">
		<div class="ukr-panel">
			<img class="ukr-bg" src="<?php echo esc_url( bu_img( 'bg/ukras-pozadina.webp' ) ); ?>" alt="" width="1000" height="750" loading="lazy" />
			<div class="ukr-text">
				<span class="badge">Po meri</span>
				<h2 id="ukras-title" class="h1">Vaše ime na ukrasu za jelku</h2>
				<p class="lead" style="color:var(--ink)">Upišite ime i odmah vidite kako izgleda na ukrasu. Četiri motiva: zvezdice i jelke, mašnica, kapica Deda Mraza i velika pahulja. Latinicom ili ćirilicom.</p>
				<form class="ukr-form" action="<?php echo esc_url( $ukras_url ); ?>" method="get">
					<div class="field">
						<label for="home-ime">Ime na ukrasu</label>
						<input id="home-ime" class="input" name="ime" maxlength="12" placeholder="npr. Mila ili Љубица" autocomplete="off" data-home-ime />
					</div>
					<button class="btn btn-primary btn-lg" type="submit">Prikaži na ukrasu</button>
				</form>
				<p class="small muted">Do 12 slova. Sa imenom primamo do 16. 12.</p>
			</div>
			<?php if ( function_exists( 'bumbaru_ornament_g' ) ) : ?>
			<div class="ukr-stage" role="img" aria-label="Tri ukrasa za jelku sa imenima: zeleni sa planinama, beli sa zvezdama i crveni sa mašnom">
				<?php foreach ( $hang as $h ) : $hy = function_exists( 'bumbaru_motiv' ) ? (int) bumbaru_motiv( $h['motif'] )['hy'] : 64; ?>
					<div class="ukr-layer <?php echo esc_attr( $h['cls'] ); ?>" style="transform-origin:<?php echo esc_attr( number_format( $h['x'] / 620 * 100, 2, '.', '' ) ); ?>% 0">
						<svg viewBox="0 0 620 560" aria-hidden="true">
							<line x1="<?php echo (int) $h['x']; ?>" y1="0" x2="<?php echo (int) $h['x']; ?>" y2="<?php echo (int) $h['hy']; ?>" stroke="#8E2A22" stroke-width="<?php echo $h['s'] > 1 ? 2 : 1.5; ?>" />
							<g transform="translate(<?php echo (int) $h['x']; ?> <?php echo (int) $h['hy']; ?>) rotate(<?php echo (int) $h['rot']; ?>) scale(<?php echo esc_attr( $h['s'] ); ?>) translate(-120 -<?php echo (int) $hy; ?>)" filter="url(#orn-sh)"><?php echo bumbaru_ornament_g( array( 'motif' => $h['motif'], 'color' => $h['color'], 'nameColor' => $h['nameColor'], 'fallback' => $h['fb'], 'key' => $h['key'] ) ); // phpcs:ignore ?></g>
						</svg>
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- Prednosti -->
	<section class="wrap section-sm" aria-label="Zašto Bumbaru">
		<ul class="trust">
			<?php foreach ( $prednosti as $x ) : ?>
				<li><span class="ti"><?php echo bu_icon( $x[0], 22 ); ?></span><span><strong><?php echo esc_html( $x[1] ); ?></strong><span class="small muted"><?php echo esc_html( $x[2] ); ?></span></span></li>
			<?php endforeach; ?>
		</ul>
	</section>
</div>

<!-- Stalna ponuda -->
<section class="wrap section" aria-labelledby="stalna-title">
	<div class="sec-head">
		<div>
			<h2 id="stalna-title" class="h1">Stalna ponuda</h2>
			<p>Pravimo ih cele godine: poklone i priveske sa imenom po vašem imenu, dekoraciju i sitnice za dom, a delove za radionicu i auto po vašem alatu i meri.</p>
		</div>
		<a class="link-arrow" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/katalog/' ) ); ?>">Ceo katalog<?php echo bu_icon( 'arrow' ); ?></a>
	</div>
	<div class="cats">
		<article class="cat cat-big">
			<div class="cat-media" style="background-image:url(<?php echo esc_url( bu_img( 'bg/kraft.webp' ) ); ?>)">
				<span class="cat-icon"><?php echo bu_icon( 'gift', 22 ); ?></span>
				<a class="polaroid" style="--r:-4deg;left:6%;top:20%" href="<?php echo esc_url( $ukras_url ); ?>"><img src="<?php echo esc_url( bu_img( 'c/ukras-sofija-sm.webp' ) ); ?>" alt="" width="640" height="640" loading="lazy" /><span>Sofija · bela</span></a>
				<a class="polaroid pol-lg" style="--r:2deg;left:33%;top:32%" href="<?php echo esc_url( bu_product_url( 'porodicni-set-ukrasa-sa-imenima' ) ); ?>"><img src="<?php echo esc_url( bu_img( 'c/ukras-set-sm.webp' ) ); ?>" alt="" width="640" height="640" loading="lazy" /><span>Porodični set</span></a>
				<a class="polaroid" style="--r:4deg;right:6%;top:16%" href="<?php echo esc_url( $ukras_url ); ?>"><img src="<?php echo esc_url( bu_img( 'c/ukras-luka-sm.webp' ) ); ?>" alt="" width="640" height="640" loading="lazy" /><span>Luka · zelena</span></a>
			</div>
			<div class="cat-body">
				<span class="badge badge-custom">Po meri · 3–5 dana</span>
				<h3 class="h2">Pokloni sa imenom</h3>
				<p class="muted">Ime upišete latinicom ili ćirilicom i odmah vidite kako izgleda. Pre štampe proveravamo svako ime.</p>
				<ul class="cat-list">
					<li><a href="<?php echo esc_url( $ukras_url ); ?>"><span><strong>Ukras za jelku sa imenom</strong><span class="small muted">4 motiva, ime u drugoj boji</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'porodicni-set-ukrasa-sa-imenima' ) ); ?>"><span><strong>Porodični set ukrasa sa imenima</strong><span class="small muted">4 ili 5 imena u jednoj kutiji</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'porodicni-privezak-sa-imenima' ) ); ?>"><span><strong>Porodični set privezaka sa imenima</strong><span class="small muted">2 do 8 imena, 5 stilova slova</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
				</ul>
				<div class="cat-actions">
					<a class="btn btn-primary btn-lg" href="<?php echo esc_url( $ukras_url ); ?>">Upišite ime</a>
					<a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'pokloni-sa-imenom' ) ); ?>">Svi pokloni sa imenom<?php echo bu_icon( 'arrow' ); ?></a>
				</div>
			</div>
		</article>

		<article class="cat cat-row">
			<div class="cat-media" style="background-image:url(https://images.unsplash.com/photo-1671040690726-b78261eff126?ixlib=rb-4.1.0&amp;auto=format&amp;fit=crop&amp;w=900&amp;q=70),url(<?php echo esc_url( bu_img( 'bg/radionica.webp' ) ); ?>)"><span class="cat-icon"><?php echo bu_icon( 'tool', 22 ); ?></span></div>
			<div class="cat-body">
				<span class="badge badge-leaf">PETG · po meri</span>
				<h3 class="h3">Radionica</h3>
				<p class="small muted">Alat visi na zidu, a baterije stoje pored njega. Za drugi brend pravimo držač po meri.</p>
				<ul class="cat-list">
					<li><a href="<?php echo esc_url( bu_product_url( 'drzac-za-aku-alat-parkside-x20v' ) ); ?>"><span><strong>Držači za Parkside X20 V</strong><span class="small muted">2 za alat, 2 za baterije</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'zidni-drzac-za-bateriju' ) ); ?>"><span><strong>Zidni držač za bateriju</strong><span class="small muted">Za vaš model, i drugi brendovi</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
				</ul>
				<a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'radionica' ) ); ?>">Sve za radionicu<?php echo bu_icon( 'arrow' ); ?></a>
			</div>
		</article>

		<article class="cat cat-row">
			<div class="cat-media" style="background-image:url(https://images.unsplash.com/photo-1590523815031-8a4c011eae39?ixlib=rb-4.1.0&amp;auto=format&amp;fit=crop&amp;w=900&amp;q=70),url(<?php echo esc_url( bu_img( 'bg/auto-moto.webp' ) ); ?>)"><span class="cat-icon"><?php echo bu_icon( 'car', 22 ); ?></span></div>
			<div class="cat-body">
				<span class="badge badge-leaf">Za auto i motor</span>
				<h3 class="h3">Auto i moto</h3>
				<p class="small muted">Tablica bez rama oko slova, nalepnica koja ostaje na mestu i sitnice za održavanje motora.</p>
				<ul class="cat-list">
					<li><a href="<?php echo esc_url( bu_product_url( 'drzac-tablice-bez-okvira' ) ); ?>"><span><strong>Držač tablice bez okvira</strong><span class="small muted">Par, bez bušenja</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'nosac-registracione-nalepnice-za-motor' ) ); ?>"><span><strong>Nosač nalepnice za motor</strong><span class="small muted">Za motor, skuter i kvad</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
				</ul>
				<a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'auto-i-moto' ) ); ?>">Sve za auto i motor<?php echo bu_icon( 'arrow' ); ?></a>
			</div>
		</article>

		<article class="cat cat-row cat-wing">
			<div class="cat-media cat-pol">
				<span class="cat-icon"><?php echo bu_icon( 'key', 22 ); ?></span>
				<a class="polaroid pol-sm pa" style="--r:-5deg" href="<?php echo esc_url( bu_product_url( 'porodicni-privezak-sa-imenima' ) ); ?>"><img src="<?php echo esc_url( bu_slug_img( 'porodicni-privezak-sa-imenima', 'c/privezak-ime-sm.webp' ) ); ?>" alt="" width="900" height="900" loading="lazy" /><span>Sa imenima</span></a>
				<a class="polaroid pol-sm pb" style="--r:4deg" href="<?php echo esc_url( bu_product_url( 'privezak-sa-imenom-kockice' ) ); ?>"><img src="<?php echo esc_url( bu_slug_img( 'privezak-sa-imenom-kockice', 'c/privezak-piksel-sm.webp' ) ); ?>" alt="" width="900" height="900" loading="lazy" /><span>Kockice</span></a>
			</div>
			<div class="cat-body">
				<span class="badge badge-custom">Sa imenom ili bez</span>
				<h3 class="h3">Privesci</h3>
				<p class="small muted">Mali poklon koji se nosi svaki dan: ime u stilu koji izaberete i bojama koje volite.</p>
				<ul class="cat-list">
					<li><a href="<?php echo esc_url( bu_product_url( 'privezak-sa-imenom' ) ); ?>"><span><strong>Privezak sa imenom</strong><span class="small muted">Pisana ili štampana slova, vaše boje</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'privezak-sa-imenom-kockice' ) ); ?>"><span><strong>Slova kao od kockica</strong><span class="small muted">I bajkovita ili pikselasta slova</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'porodicni-privezak-sa-imenima' ) ); ?>"><span><strong>Porodični set privezaka</strong><span class="small muted">2 do 8 imena, 5 stilova slova</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
				</ul>
				<a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'privesci' ) ); ?>">Svi privesci<?php echo bu_icon( 'arrow' ); ?></a>
			</div>
		</article>

		<article class="cat cat-row cat-honey">
			<div class="cat-media cat-toys"><span class="cat-icon"><?php echo bu_icon( 'blocks', 22 ); ?></span><img src="<?php echo esc_url( bu_img( 'c/igracke-sm.webp' ) ); ?>" alt="" width="600" height="600" loading="lazy" /></div>
			<div class="cat-body">
				<span class="badge badge-berry">Uskoro</span>
				<h3 class="h3">Igračke za decu</h3>
				<p class="small muted">Prve igračke od 3D štampe stižu uskoro. Javite nam šta vaše dete voli, pa vam pišemo kad krenu.</p>
				<a class="btn" href="<?php echo esc_url( bu_messenger_url( 'Zdravo, javite mi kad stignu igračke za decu.', 'igracke' ) ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Javite mi na Messenger-u</a>
				<a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'pokloni-sa-imenom' ) ); ?>">Do tada: pokloni sa imenom<?php echo bu_icon( 'arrow' ); ?></a>
			</div>
		</article>

		<article class="cat cat-wide cat-rev">
			<div class="cat-media cat-pol cat-slava">
				<span class="cat-icon"><?php echo bu_path_icon( 'M12 3a9 9 0 1 0 0 18a9 9 0 1 0 0-18zM12 7v10M8.5 10.5h7', 22 ); // phpcs:ignore ?></span>
				<a class="polaroid ph1" style="--r:-4deg" href="<?php echo esc_url( bu_product_url( 'slavski-set-modlica-sveti-nikola' ) ); ?>"><img src="<?php echo esc_url( bu_slug_img( 'slavski-set-modlica-sveti-nikola' ) ); ?>" alt="" width="900" height="900" loading="lazy" /><span>Set Sveti Nikola</span></a>
				<a class="polaroid ph2" style="--r:3deg" href="<?php echo esc_url( bu_product_url( 'slavske-modlice-sastavite-svoj-set' ) ); ?>"><img src="<?php echo esc_url( bu_slug_img( 'slavske-modlice-sastavite-svoj-set' ) ); ?>" alt="" width="900" height="900" loading="lazy" /><span>Sastavite svoj set</span></a>
				<a class="polaroid ph3" style="--r:-2deg" href="<?php echo esc_url( bu_product_url( 'bozicna-modlica-badnjak' ) ); ?>"><img src="<?php echo esc_url( bu_slug_img( 'bozicna-modlica-badnjak' ) ); ?>" alt="" width="900" height="900" loading="lazy" /><span>Za Božić</span></a>
			</div>
			<div class="cat-body">
				<span class="badge badge-honey">Za slavu i Božić</span>
				<h3 class="h3">Slavske modlice</h3>
				<p class="small muted">Sekač i pečat za slavski kolač: medaljon vašeg sveca, krst, pečat ИС ХС НИ КА, kukuruz, žito, grožđe i još motiva. Birate 8 modlica za set i odmah vidite kako izgleda.</p>
				<ul class="cat-list">
					<li><a href="<?php echo esc_url( add_query_arg( 'filter', 'sv-nikola', bu_cat_url( 'slavske-modlice' ) ) ); ?>"><span><strong>Slavski setovi po slavama</strong><span class="small muted">8 modlica i medaljon, jeftinije nego pojedinačno</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'slavske-modlice-sastavite-svoj-set' ) ); ?>"><span><strong>Sastavite svoj set</strong><span class="small muted">Kupite 5, šesta je gratis</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( add_query_arg( 'filter', 'bozic', bu_cat_url( 'slavske-modlice' ) ) ); ?>"><span><strong>Božićne modlice</strong><span class="small muted">Za česnicu i božićni kolač</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
				</ul>
				<a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'slavske-modlice' ) ); ?>">Sve slavske modlice<?php echo bu_icon( 'arrow' ); ?></a>
			</div>
		</article>

		<article class="cat cat-wide">
			<div class="cat-media cat-pol cat-home">
				<span class="cat-icon"><?php echo bu_path_icon( 'M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-6h4v6', 22 ); // phpcs:ignore ?></span>
				<a class="polaroid ph1" style="--r:-5deg" href="<?php echo esc_url( bu_product_url( 'rebrasta-vaza-sa-tacnom-i-svecnjakom' ) ); ?>"><img src="<?php echo esc_url( bu_slug_img( 'rebrasta-vaza-sa-tacnom-i-svecnjakom' ) ); ?>" alt="" width="900" height="900" loading="lazy" /><span>Vaza sa svećnjakom</span></a>
				<a class="polaroid ph2" style="--r:3deg" href="<?php echo esc_url( bu_product_url( 'podmetaci-monstera-sa-saksijom' ) ); ?>"><img src="<?php echo esc_url( bu_slug_img( 'podmetaci-monstera-sa-saksijom' ) ); ?>" alt="" width="900" height="900" loading="lazy" /><span>Podmetači Monstera</span></a>
				<a class="polaroid ph3" style="--r:-2deg" href="<?php echo esc_url( bu_product_url( 'klipovi-za-kesice' ) ); ?>"><img src="<?php echo esc_url( bu_slug_img( 'klipovi-za-kesice' ) ); ?>" alt="" width="900" height="900" loading="lazy" /><span>Klipovi u bojama</span></a>
			</div>
			<div class="cat-body">
				<span class="badge badge-leaf">Novo u katalogu</span>
				<h3 class="h3">Dom i kuhinja</h3>
				<p class="small muted">Dekoracija koja izgleda kao keramika, podmetači koji stoje u saksiji kao biljka i klipovi za kese u bojama koje birate sami.</p>
				<ul class="cat-list">
					<li><a href="<?php echo esc_url( bu_product_url( 'rebrasta-vaza-sa-tacnom-i-svecnjakom' ) ); ?>"><span><strong>Rebrasta vaza sa tacnom i svećnjakom</strong><span class="small muted">LED svećica u kompletu</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'podmetaci-monstera-sa-saksijom' ) ); ?>"><span><strong>Podmetači Monstera sa saksijom</strong><span class="small muted">Boje listova i saksije po izboru</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
					<li><a href="<?php echo esc_url( bu_product_url( 'klipovi-za-kesice' ) ); ?>"><span><strong>Klipovi za kesice</strong><span class="small muted">Birate boje, od 4 komada popust</span></span><?php echo bu_icon( 'arrow' ); ?></a></li>
				</ul>
				<a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'dom-i-kuhinja' ) ); ?>">Sve za dom i kuhinju<?php echo bu_icon( 'arrow' ); ?></a>
			</div>
		</article>
	</div>
</section>

<!-- Po meri -->
<section class="wrap section reveal" aria-labelledby="pomeri-title">
	<div class="pomeri dark">
		<img src="<?php echo esc_url( bu_img( 'bg/po-meri.webp' ) ); ?>" alt="3D štampač u radionici sa odštampanim delovima" width="1000" height="750" loading="lazy" />
		<div class="pomeri-text">
			<span class="label" style="color:var(--honey)">3D štampa po meri</span>
			<h2 id="pomeri-title" class="h1">Treba vam deo po meri?</h2>
			<p class="lead" style="color:var(--cream-muted)">Pošaljite meru, skicu ili fotografiju dela koji vam treba. Javljamo cenu i rok izrade, a gotov deo šaljemo kurirom.</p>
			<ul class="checks">
				<li><span><?php echo bu_icon( 'check', 14, 3.2 ); ?></span>Deo koji je pukao ili se više ne prodaje</li>
				<li><span><?php echo bu_icon( 'check', 14, 3.2 ); ?></span>Držač za alat drugog brenda</li>
				<li><span><?php echo bu_icon( 'check', 14, 3.2 ); ?></span>Poklon sa imenom kog nema u katalogu</li>
			</ul>
			<div><a class="btn btn-primary btn-lg" href="<?php echo esc_url( bu_page_url( 'po-meri' ) ); ?>">Pošaljite upit</a></div>
			<p class="small" style="color:var(--cream-muted)">Upit je besplatan. Ne plaćate ništa do potvrde cene i roka.</p>
		</div>
	</div>
</section>

<!-- Kako poručiti -->
<section class="wrap section reveal" aria-labelledby="kako-title">
	<div class="kako">
		<div class="sec-head" style="margin-bottom:24px">
			<div><h2 id="kako-title" class="h1">Kako poručiti</h2><p style="color:var(--ink-muted)">Tri koraka i paket je na putu.</p></div>
			<div class="chips"><span class="pill">Preko sajta ili Messengera</span><span class="pill">Besplatna dostava od <?php echo esc_html( bu_free_ship_label() ); ?></span><span class="pill">Plaćanje pouzećem</span></div>
		</div>
		<ol class="steps">
			<li>
				<div class="step-art art-1">
					<img class="s1a" src="<?php echo esc_url( bu_img( 'p/patuljak-sa-led-svecicom/1-sq.webp' ) ); ?>" alt="" width="900" height="900" loading="lazy" />
					<img class="s1b" src="<?php echo esc_url( bu_img( 'c/ukras-sofija-sm.webp' ) ); ?>" alt="" width="640" height="640" loading="lazy" />
					<img class="s1c" src="<?php echo esc_url( bu_img( 'p/mini-jelka-za-sto/1-sq.webp' ) ); ?>" alt="" width="900" height="900" loading="lazy" />
				</div>
				<div class="step-txt"><span class="num">1</span><div><h3 class="h3">Izaberite proizvod</h3><p class="small muted">Upišite ime, izaberite motiv ili boju i odmah vidite pregled.</p></div></div>
			</li>
			<li>
				<div class="step-art art-2">
					<div class="bubble">
						<span class="small muted">Vaša korpa</span>
						<p><strong>Ukras za jelku sa imenom</strong><br />Ime: Mila<br />Motiv: Zvezde i jelke</p>
					</div>
					<span class="send"><?php echo bu_icon( 'bag', 18 ); ?>Poruči preko sajta</span>
				</div>
				<div class="step-txt"><span class="num">2</span><div><h3 class="h3">Poručite</h3><p class="small muted">Preko sajta (upišete adresu i telefon, bez naloga) ili preko Messengera, gde se poruka složi sama.</p></div></div>
			</li>
			<li>
				<div class="step-art art-3">
					<svg viewBox="0 0 300 210" fill="none" aria-hidden="true">
						<path d="M14 112h50M30 136h34M6 160h58" stroke="#335A22" stroke-width="6" stroke-linecap="round" stroke-dasharray="0 13" />
						<path d="M86 78 L176 106 L176 198 L86 170 Z" fill="#C99A5E" />
						<path d="M176 106 L266 78 L266 170 L176 198 Z" fill="#D9B07A" />
						<path d="M86 78 L176 50 L266 78 L176 106 Z" fill="#E6C291" />
						<path d="M122 67 L140 61 L230 89 L212 95 Z" fill="#F5E2A8" />
						<path d="M212 95 L230 89 L230 181 L212 187 Z" fill="#F5E2A8" />
						<path d="M100 120 L150 135 L150 162 L100 147 Z" fill="#EEEAE3" stroke="#312018" stroke-width="2.5" stroke-linejoin="round" />
						<path d="M108 132 L140 142 M108 141 L132 148" stroke="#312018" stroke-width="2.5" stroke-linecap="round" />
						<path d="M86 78 L176 50 L266 78 L266 170 L176 198 L86 170 Z M86 78 L176 106 L266 78 M176 106 L176 198" stroke="#312018" stroke-width="4" stroke-linejoin="round" />
					</svg>
					<img class="s3bee" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" loading="lazy" />
					<span class="pay"><?php echo bu_icon( 'cash', 16, 2.2 ); ?>Plaćate kuriru</span>
				</div>
				<div class="step-txt"><span class="num">3</span><div><h3 class="h3">Stiže Post Expressom</h3><p class="small muted">Plaćate kuriru kad paket stigne. Od <?php echo esc_html( bu_free_ship_label() ); ?> dostava je besplatna.</p></div></div>
			</li>
		</ol>
	</div>
</section>

<!-- Česta pitanja -->
<section class="wrap section" aria-labelledby="faq-title">
	<div class="faq-wrap">
		<div class="faq-side">
			<h2 id="faq-title" class="h1">Česta pitanja</h2>
			<p class="muted" style="margin-top:8px">Plaćanje, dostava, rokovi i povraćaj, ukratko.</p>
			<div class="help-card">
				<img src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" loading="lazy" style="width:64px;height:64px" />
				<h3 class="h3">Niste našli odgovor?</h3>
				<p class="small">Pišite nam na Messenger-u ili pogledajte sva pitanja u Pomoći.</p>
				<a class="btn btn-paper" href="<?php echo esc_url( bu_messenger_url() ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Pišite nam</a>
				<a class="link-arrow small" href="<?php echo esc_url( bu_page_url( 'pomoc' ) ); ?>">Sva pitanja u Pomoći<?php echo bu_icon( 'arrow', 18 ); ?></a>
			</div>
		</div>
		<div class="faq-panel"><?php bu_accordion( $faq ); ?></div>
	</div>
</section>
<?php
get_footer();
