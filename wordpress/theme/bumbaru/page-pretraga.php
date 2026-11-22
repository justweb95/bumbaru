<?php
/**
 * Pretraga (/pretraga/?q=…&kategorija=…), kao na novom sajtu: svi proizvodi su na strani,
 * a pregledač ih filtrira dok kupac kuca (po korenu reči, pa padeži i množina ne smetaju).
 */
defined( 'ABSPATH' ) || exit;
bu_set_seo( 'Pretraga | Bumbaru', 'Pretraga Bumbaru kataloga.', array( 'noindex' => true ) );

$list = function_exists( 'wc_get_products' ) ? bu_sort_photo_first( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'visibility' => 'search' ) ) ) : array();
$stock = function_exists( 'bumbaru_colors_in_stock' ) ? wp_list_pluck( bumbaru_colors_in_stock(), 'label' ) : array();

// Tekst po kome se traži: naziv, opis, šifra, kategorije, opcije, motivi (i boje kod izbora boja).
$hay = array();
$cat_of = array();
foreach ( $list as $p ) {
	$parts = array( $p->get_name(), wp_strip_all_tags( $p->get_short_description() ), (string) bu_pmeta( $p, 'lead' ), $p->get_sku() );
	$slugs = array();
	foreach ( wc_get_product_terms( $p->get_id(), 'product_cat' ) as $t ) {
		$parts[] = $t->name;
		$slugs[] = $t->slug;
	}
	foreach ( wc_get_product_terms( $p->get_id(), 'product_tag' ) as $t ) {
		$parts[] = $t->name;
	}
	if ( function_exists( 'bumbaru_config' ) ) {
		$cfg = bumbaru_config( $p );
		foreach ( $cfg['options'] as $o ) {
			$parts = array_merge( $parts, wp_list_pluck( $o['values'], 'label' ) );
		}
		if ( ! empty( $cfg['motivi']['lista'] ) ) {
			$parts = array_merge( $parts, wp_list_pluck( $cfg['motivi']['lista'], 'naziv' ) );
		}
		if ( 'boje' === $cfg['type'] ) {
			$parts = array_merge( $parts, $stock );
		}
	}
	$hay[ $p->get_id() ]    = bu_norm( implode( ' ', array_filter( $parts ) ) );
	$cat_of[ $p->get_id() ] = $slugs;
}
$kategorije = array_filter( bu_categories(), function ( $c ) {
	return ! bu_tmeta( $c, 'soon' ) && bu_category_products( $c->slug );
} );

// Često traženo: kratke reči koje uvek nešto nađu.
$koren     = function ( $w ) {
	return mb_strlen( $w ) > 4 ? mb_substr( $w, 0, max( 4, mb_strlen( $w ) - 2 ) ) : $w;
};
$popularno = array_filter( array( 'Ukras sa imenom', 'Privezak', 'Jelka', 'LED svećica', 'Klipovi', 'Vaza', 'Podmetači', 'Modlice', 'Slavski set' ), function ( $q ) use ( $hay, $koren ) {
	$w = array_map( $koren, preg_split( '/\s+/', bu_norm( $q ) ) );
	foreach ( $hay as $h ) {
		$ok = true;
		foreach ( $w as $x ) {
			if ( false === strpos( $h, $x ) ) {
				$ok = false;
				break;
			}
		}
		if ( $ok ) {
			return true;
		}
	}
	return false;
} );

// Predlozi kad ništa nije nađeno: prvi proizvod sa fotografijom iz svake kategorije.
$predlozi = array();
foreach ( $kategorije as $c ) {
	foreach ( bu_category_products( $c->slug ) as $p ) {
		if ( $p->get_image_id() && ! isset( $predlozi[ $p->get_id() ] ) ) {
			$predlozi[ $p->get_id() ] = $p;
			break;
		}
	}
	if ( count( $predlozi ) >= 4 ) {
		break;
	}
}
get_header();
?>
<div class="paper-zone">
	<div class="wrap">
		<?php bu_crumbs( array( array( 'label' => 'Pretraga' ) ) ); ?>

		<section class="s-hero" aria-labelledby="s-title">
			<div class="s-hero-text">
				<span class="badge badge-honey">Pretraga</span>
				<h1 id="s-title" class="h1" data-title>Šta tražite?</h1>
				<form class="s-form" action="<?php echo esc_url( get_permalink() ); ?>" method="get" role="search" data-form>
					<label class="sr-only" for="q-page">Šta tražite?</label>
					<span class="s-ic" aria-hidden="true"><?php echo bu_icon( 'search', 22 ); ?></span>
					<input id="q-page" name="q" type="search" placeholder="npr. ukras sa imenom, privezak, vaza" autocomplete="off" enterkeyhint="search" data-q />
					<button class="s-clear" type="button" aria-label="Obriši pretragu" data-clear hidden><?php echo bu_icon( 'close', 18 ); ?></button>
				</form>
				<?php if ( $popularno ) : ?>
					<div class="s-pop">
						<span class="s-pop-lab">Često traženo:</span>
						<?php foreach ( $popularno as $q ) : ?><button type="button" class="s-pill" data-pop="<?php echo esc_attr( $q ); ?>"><?php echo esc_html( $q ); ?></button><?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="s-hero-art" aria-hidden="true">
				<svg class="s-trail" viewBox="0 0 220 160"><path d="M10 150 C 40 90, 110 140, 120 90 S 70 30, 120 30 S 200 60, 205 20" fill="none" stroke="#312018" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="0 10" /></svg>
				<img class="s-bee fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" />
				<span class="s-glass"><?php echo bu_icon( 'search', 40, 2.4 ); ?></span>
			</div>
		</section>

		<div class="s-bar">
			<div class="chips" role="group" aria-label="Kategorije" data-cats>
				<button class="chip" type="button" data-cat="" aria-pressed="true">Sve <span class="cnt" data-cnt=""></span></button>
				<?php foreach ( $kategorije as $c ) : ?>
					<button class="chip" type="button" data-cat="<?php echo esc_attr( $c->slug ); ?>" aria-pressed="false"><?php echo esc_html( 'nova-godina' === $c->slug ? 'Nova godina' : $c->name ); ?> <span class="cnt" data-cnt="<?php echo esc_attr( $c->slug ); ?>"></span></button>
				<?php endforeach; ?>
			</div>
			<p class="s-count" data-info aria-live="polite"></p>
		</div>

		<div class="grid" data-results>
			<?php foreach ( $list as $i => $p ) : ?>
				<div class="s-item" data-hay="<?php echo esc_attr( $hay[ $p->get_id() ] ); ?>" data-title="<?php echo esc_attr( bu_norm( $p->get_name() ) ); ?>" data-cats="<?php echo esc_attr( implode( ' ', $cat_of[ $p->get_id() ] ) ); ?>" data-i="<?php echo (int) $i; ?>"><?php bu_product_card( $p ); ?></div>
			<?php endforeach; ?>
		</div>

		<section class="s-empty" data-empty hidden aria-labelledby="s-empty-t">
			<div class="s-empty-card">
				<div class="s-empty-art" aria-hidden="true">
					<img class="fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" />
					<span class="s-q">?</span>
				</div>
				<div>
					<h2 id="s-empty-t" class="h2" data-empty-title>Nismo našli taj proizvod</h2>
					<p class="muted">Proverite kako je napisano ili probajte kraću reč. Možda ga pravimo po meri: pošaljite nam ideju, meru ili fotografiju.</p>
					<div class="s-empty-actions">
						<a class="btn btn-primary btn-lg" href="<?php echo esc_url( bu_page_url( 'po-meri' ) ); ?>"><?php echo bu_icon( 'sparkle' ); ?>Pošaljite upit po meri</a>
						<a class="btn btn-lg" href="<?php echo esc_url( bu_messenger_url( 'Zdravo, tražim proizvod koji nisam našao na sajtu:', 'pretraga' ) ); ?>" target="_blank" rel="noopener" data-ask><?php echo bu_icon( 'messenger' ); ?>Pitajte na Messenger-u</a>
					</div>
				</div>
			</div>
			<?php if ( $predlozi ) : ?>
				<h2 class="h2 s-sug-t">Možda vam se dopadne</h2>
				<div class="grid rail-mobile"><?php foreach ( $predlozi as $p ) { bu_product_card( $p ); } ?></div>
			<?php endif; ?>
		</section>
	</div>
</div>
<?php
get_footer();
