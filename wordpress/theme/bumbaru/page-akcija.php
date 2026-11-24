<?php
/**
 * Akcija (/akcija/): proizvodi na akciji (preneto iz akcija.astro).
 * Akcija se podešava u Bumbaru 3D → Akcija (naziv, procenat, kategorija, datum kraja).
 */
defined( 'ABSPATH' ) || exit;

$a     = function_exists( 'bumbaru_akcija' ) ? bumbaru_akcija() : null;
$kat   = $a ? get_term_by( 'slug', $a['kategorija'], 'product_cat' ) : null;
$lista = array();
if ( $a && function_exists( 'wc_get_products' ) ) {
	foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'visibility' => 'catalog', 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $p ) {
		if ( bumbaru_akcija_pct( $p ) > 0 && bumbaru_all_prices( $p ) ) {
			$lista[] = $p;
		}
	}
	$lista = bu_sort_photo_first( $lista );
}
$naslov = $a ? $a['naziv'] . ' −' . (float) $a['procenat'] . '%' : 'Akcija';
$do     = $a ? bumbaru_datum_kratko( $a['do'] ) : '';
if ( $a ) {
	bu_set_seo( $naslov . ' | Bumbaru', $a['naziv'] . ': ' . (float) $a['procenat'] . '% popusta na ' . ( ! empty( $a['samo'] ) ? $a['opis'] : ( $kat ? $kat->name : 'izabrane proizvode' ) ) . ' do ' . $do . ' Plaćanje pouzećem, besplatna dostava od ' . bu_free_ship_label() . '.' );
} else {
	bu_set_seo( 'Akcija | Bumbaru', 'Trenutno nema akcije. Pogledajte ceo Bumbaru katalog.', array( 'noindex' => true ) );
}
get_header();
?>
<div class="paper-zone">
	<div class="wrap">
		<?php bu_crumbs( array( array( 'label' => 'Akcija' ) ) ); ?>
		<?php if ( $a ) : ?>
			<section class="ak-hero ak-on" aria-labelledby="ak-t">
				<div class="ak-hero-txt">
					<span class="badge badge-berry">Akcija traje do <?php echo esc_html( $do ); ?></span>
					<h1 id="ak-t" class="h1"><?php echo esc_html( $a['naziv'] ); ?>: <span class="ak-pct">−<?php echo esc_html( (float) $a['procenat'] ); ?>%</span></h1>
					<p class="lead"><?php echo esc_html( ! empty( $a['samo'] ) ? 'Popust važi za: ' . $a['opis'] : 'Popust važi za sve proizvode iz kategorije ' . ( $kat ? $kat->name : '' ) ); ?>, a cena je već umanjena. Popust za veću količinu i za komplet se dodaje na akcijsku cenu.</p>
					<?php echo bu_odbrojavanje(); // phpcs:ignore ?>
				</div>
				<div class="ak-hero-art" aria-hidden="true">
					<span class="ak-big">−<?php echo esc_html( (float) $a['procenat'] ); ?>%</span>
					<img class="fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="120" height="120" />
				</div>
			</section>
			<p class="muted ak-cnt"><b><?php echo (int) count( $lista ); ?></b> <?php echo esc_html( bu_plural( count( $lista ) ) ); ?> na akciji</p>
			<div class="grid ak-on"><?php foreach ( $lista as $i => $p ) { bu_product_card( $p, $i < 4 ); } ?></div>
			<div class="ak-kraj-msg">
				<h2 class="h2">Akcija je završena</h2>
				<p class="muted">Hvala svima! Ceo katalog je i dalje tu.</p>
				<a class="btn btn-primary btn-lg" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Pogledajte katalog</a>
			</div>
		<?php else : ?>
			<section class="ak-nema">
				<img src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="120" height="120" />
				<h1 class="h1">Trenutno nema akcije</h1>
				<p class="lead">Pratite nas na Facebook-u da ne propustite sledeću. Do tada, pogledajte ceo katalog ili pustite kviz da vam predloži poklon.</p>
				<div class="ak-nema-act"><a class="btn btn-primary btn-lg" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Katalog</a><a class="btn btn-lg" href="<?php echo esc_url( bu_page_url( 'nadji-poklon' ) ); ?>"><?php echo bu_icon( 'sparkle' ); ?>Nađi poklon</a></div>
			</section>
		<?php endif; ?>
		<?php if ( $kat ) : ?><p class="ak-link"><a class="link-arrow" href="<?php echo esc_url( get_term_link( $kat ) ); ?>">Cela kategorija <?php echo esc_html( $kat->name ); ?><?php echo bu_icon( 'arrow' ); ?></a></p><?php endif; ?>
	</div>
</div>
<?php
get_footer();
