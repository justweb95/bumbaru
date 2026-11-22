<?php
/**
 * Katalog (/katalog/): naslov sa pretragom i polaroidima, kategorije sa slikama
 * i šarene ploče sa po 4 proizvoda iz svake kategorije (preneto iz katalog/index.astro).
 * Za ostale liste proizvoda (npr. oznake) prikazuje mrežu kartica.
 */
defined( 'ABSPATH' ) || exit;

get_header();

if ( is_shop() && ! is_search() ) :
	$kat    = bu_menu_categories();
	$ukupno = 0;
	$aktivne = 0;
	foreach ( $kat as $k ) {
		if ( ! $k['soon'] ) {
			$ukupno += $k['n'];
			$aktivne++;
		}
	}
	// Proizvod može biti u više kategorija: broj proizvoda u katalogu je broj objavljenih proizvoda.
	$ukupno = count( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids', 'visibility' => 'catalog' ) ) );

	// Polaroidi u vrhu: po jedna prava fotografija iz različitih kategorija.
	$uzete = array();
	$pol   = array();
	foreach ( $kat as $k ) {
		foreach ( $k['list'] as $p ) {
			if ( $p->get_image_id() && ! isset( $uzete[ $p->get_id() ] ) ) {
				$uzete[ $p->get_id() ] = 1;
				$pol[]                 = $p;
				break;
			}
		}
		if ( count( $pol ) >= 5 ) {
			break;
		}
	}
	$gore   = array( array( -6, 0, 4 ), array( 4, 34, 0 ), array( -3, 68, 6 ) );
	$dole   = count( $pol ) > 4 ? array( array( 5, 17, 50 ), array( -4, 51, 52 ) ) : array( array( 4, 34, 50 ) );
	$pos    = array_merge( $gore, $dole );
	$tonovi = array( 'tone-honey', 'tone-wing', 'tone-leaf', 'tone-lilac', 'tone-berry', 'tone-peach' );
	$ak     = function_exists( 'bumbaru_akcija' ) ? bumbaru_akcija() : null;
	?>
	<div class="paper-zone">
		<div class="wrap">
			<?php
			bu_crumbs( array( array( 'label' => 'Katalog' ) ) );
			bu_print_notices();
			?>
			<nav class="tabs" aria-label="Kategorije">
				<a class="tab" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" aria-current="page">Sve kategorije</a>
				<?php foreach ( $kat as $k ) : ?>
					<a class="tab" href="<?php echo esc_url( $k['url'] ); ?>"><?php echo esc_html( $k['ime'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<!-- Vrh: naslov, pretraga i polaroidi proizvoda -->
			<section class="k-hero" aria-labelledby="k-title">
				<div class="k-hero-txt">
					<span class="badge badge-honey">Katalog</span>
					<h1 id="k-title" class="h1">Sve što štampamo, na jednom mestu</h1>
					<p class="lead">Izaberite kategoriju ili pretražite katalog. Kliknite na proizvod, izaberite šta vam treba i dodajte u korpu. Plaćate pouzećem, a od <?php echo esc_html( bu_free_ship_label() ); ?> dostava je besplatna.</p>
					<?php bu_search_field(); ?>
					<ul class="k-stats">
						<li><b><?php echo (int) $ukupno; ?></b><span>proizvoda</span></li>
						<li><b><?php echo (int) $aktivne; ?></b><span>kategorija</span></li>
						<li><b>3D</b><span>štampa iz Leskovca</span></li>
					</ul>
				</div>
				<div class="k-hero-art" aria-hidden="true">
					<svg class="k-trail" viewBox="0 0 300 220"><path d="M10 200 C 60 120, 140 190, 160 120 S 110 40, 170 36 S 270 70, 290 20" fill="none" stroke="#312018" stroke-width="3" stroke-linecap="round" stroke-dasharray="0 10" /></svg>
					<?php foreach ( $pol as $i => $p ) : ?>
						<a class="k-pol" href="<?php echo esc_url( get_permalink( $p->get_id() ) ); ?>" tabindex="-1" style="--r:<?php echo (int) $pos[ $i ][0]; ?>deg;left:<?php echo (int) $pos[ $i ][1]; ?>%;top:<?php echo (int) $pos[ $i ][2]; ?>%;--d:<?php echo (int) $i; ?>">
							<img src="<?php echo esc_url( bu_card_img( $p ) ); ?>" alt="" width="900" height="900" loading="eager" />
							<span><?php echo esc_html( $p->get_name() ); ?></span>
						</a>
					<?php endforeach; ?>
					<img class="k-bee fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="90" height="90" />
				</div>
			</section>

			<!-- Kategorije -->
			<nav class="k-tiles" aria-label="Kategorije sa slikama">
				<?php foreach ( $kat as $i => $k ) : ?>
					<a class="k-tile" href="<?php echo esc_url( $k['soon'] ? $k['url'] : '#k-' . $k['term']->slug ); ?>" style="--r:<?php echo $i % 2 ? '1.2' : '-1.2'; ?>deg">
						<span class="k-img"><img src="<?php echo esc_url( $k['slika'] ); ?>" alt="" width="300" height="300" loading="<?php echo $i < 4 ? 'eager' : 'lazy'; ?>" decoding="async" /></span>
						<span class="k-ic"><?php echo bu_path_icon( $k['icon'] ? $k['icon'] : 'M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z', 18 ); // phpcs:ignore ?></span>
						<span class="k-name"><?php echo esc_html( $k['ime'] ); ?></span>
						<span class="k-cnt"><?php echo esc_html( $k['soon'] ? 'Uskoro' : $k['n'] . ' ' . bu_plural( $k['n'] ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<!-- Po kategoriji: šarene ploče sa proizvodima -->
			<?php
			$ti = 0;
			foreach ( $kat as $k ) :
				if ( $k['soon'] ) {
					continue;
				}
				$c = $k['term'];
				?>
				<section class="k-sec <?php echo esc_attr( $tonovi[ $ti++ % count( $tonovi ) ] ); ?>" id="k-<?php echo esc_attr( $c->slug ); ?>" aria-labelledby="kh-<?php echo esc_attr( $c->slug ); ?>">
					<div class="k-sec-head">
						<div class="k-sec-t">
							<span class="kicon"><?php echo bu_path_icon( $k['icon'] ? $k['icon'] : 'M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z', 20 ); // phpcs:ignore ?></span>
							<div>
								<h2 id="kh-<?php echo esc_attr( $c->slug ); ?>" class="h2"><?php echo esc_html( 'nova-godina' === $c->slug ? 'Novogodišnji katalog' : $k['ime'] ); ?></h2>
								<?php $txt = wp_strip_all_tags( term_description( $c ) ); ?>
								<?php if ( $txt ) : ?><p class="muted"><?php echo esc_html( $txt ); ?></p><?php endif; ?>
							</div>
						</div>
						<div class="k-sec-act">
							<?php
							if ( $ak && $ak['kategorija'] === $c->slug ) {
								echo bu_odbrojavanje( true ); // phpcs:ignore
							}
							?>
							<a class="btn btn-paper" href="<?php echo esc_url( $k['url'] ); ?>"><?php echo esc_html( $k['n'] > 4 ? 'Svih ' . $k['n'] . ' proizvoda' : 'Cela kategorija' ); ?><?php echo bu_icon( 'arrow' ); ?></a>
						</div>
					</div>
					<div class="grid rail-mobile">
						<?php foreach ( array_slice( $k['list'], 0, 4 ) as $p ) { bu_product_card( $p ); } ?>
					</div>
				</section>
			<?php endforeach; ?>
			<?php
			foreach ( $kat as $k ) :
				if ( ! $k['soon'] ) {
					continue;
				}
				$c = $k['term'];
				?>
				<section class="k-sec k-soon" aria-labelledby="kh-<?php echo esc_attr( $c->slug ); ?>">
					<img src="<?php echo esc_url( bu_img( 'c/igracke-sm.webp' ) ); ?>" alt="" width="600" height="600" loading="lazy" />
					<div>
						<span class="badge badge-berry">Uskoro</span>
						<h2 id="kh-<?php echo esc_attr( $c->slug ); ?>" class="h2"><?php echo esc_html( $c->name ); ?></h2>
						<p class="muted"><?php echo esc_html( wp_strip_all_tags( term_description( $c ) ) ); ?></p>
						<a class="btn" href="<?php echo esc_url( bu_messenger_url( 'Zdravo, javite mi kad stigne kategorija ' . $c->name . '.', $c->slug ) ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Javite mi kad krene</a>
					</div>
				</section>
			<?php endforeach; ?>

			<!-- Ništa nije zapalo za oko? -->
			<div class="k-cta">
				<a class="k-cta-card k-cta-kviz" href="<?php echo esc_url( bu_page_url( 'nadji-poklon' ) ); ?>">
					<img class="fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="72" height="72" loading="lazy" />
					<span><b class="h3">Ne znate šta da izaberete?</b><span class="small">Četiri brza pitanja i predlažemo vam poklon iz kataloga.</span></span>
					<span class="btn btn-primary"><?php echo bu_icon( 'sparkle' ); ?>Nađi poklon</span>
				</a>
				<a class="k-cta-card k-cta-meri" href="<?php echo esc_url( bu_page_url( 'po-meri' ) ); ?>">
					<span class="k-cta-ic"><?php echo bu_icon( 'layers', 28 ); ?></span>
					<span><b class="h3">Niste našli šta tražite?</b><span class="small">Pošaljite ideju, meru ili fotografiju, pa javljamo cenu i rok izrade.</span></span>
					<span class="btn btn-paper">Pošaljite upit</span>
				</a>
			</div>

			<?php echo bu_nedavno(); // phpcs:ignore ?>
		</div>
	</div>
	<?php
else :
	?>
	<div class="wrap">
		<?php bu_crumbs( array( array( 'label' => 'Katalog', 'href' => wc_get_page_permalink( 'shop' ) ), array( 'label' => wp_strip_all_tags( woocommerce_page_title( false ) ) ) ) ); ?>
		<h1 class="h1"><?php woocommerce_page_title(); ?></h1>
		<div class="grid" style="margin-top:32px">
			<?php
			while ( have_posts() ) {
				the_post();
				bu_product_card( wc_get_product( get_the_ID() ) );
			}
			?>
		</div>
	</div>
	<?php
endif;

get_footer();
