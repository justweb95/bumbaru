<?php
/**
 * Kategorija kataloga (prenesena iz katalog/[kategorija].astro).
 * Tekstovi, pozadina, filteri i pitanja se menjaju u Proizvodi → Kategorije.
 */
defined( 'ABSPATH' ) || exit;

$c       = get_queried_object();
$list    = bu_category_products( $c->slug );
$soon    = (bool) bu_tmeta( $c, 'soon' );
$h1      = bu_tmeta( $c, 'title' ) ? bu_tmeta( $c, 'title' ) : $c->name;
$text    = wp_strip_all_tags( term_description( $c ) );
$bg      = bu_img_src( bu_tmeta( $c, 'bg' ) ? bu_tmeta( $c, 'bg' ) : 'bg/kraft.webp' );
$bg_pos  = bu_tmeta( $c, 'bg_pos' ) ? bu_tmeta( $c, 'bg_pos' ) : 'center';
$about_h = bu_tmeta( $c, 'about_h2' );
$about_p = array_filter( array_map( 'trim', preg_split( '/\r\n\r\n|\n\n/', (string) bu_tmeta( $c, 'about_p' ) ) ) );
$faq     = bu_lines( bu_tmeta( $c, 'faq' ) );

// Filteri: oznake proizvoda (npr. ime, privesci, sto, jelka) upisane u kategoriji.
$counts = array();
$fslugs = array_filter( array_map( 'trim', explode( ',', (string) bu_tmeta( $c, 'filters' ) ) ) );
if ( $fslugs ) {
	$tags_of = array();
	foreach ( $list as $p ) {
		$t                        = wp_get_post_terms( $p->get_id(), 'product_tag', array( 'fields' => 'slugs' ) );
		$tags_of[ $p->get_id() ] = is_wp_error( $t ) ? array() : $t;
	}
	$counts[] = array( 'id' => 'sve', 'label' => 'Sve', 'n' => count( $list ) );
	foreach ( $fslugs as $slug ) {
		$tag = get_term_by( 'slug', $slug, 'product_tag' );
		$n   = 0;
		foreach ( $tags_of as $ts ) {
			if ( in_array( $slug, $ts, true ) ) {
				$n++;
			}
		}
		if ( $tag && $n > 0 ) {
			$counts[] = array( 'id' => $slug, 'label' => $tag->name, 'n' => $n );
		}
	}
}
$plural = function ( $n ) {
	return ( 1 === $n % 10 && 11 !== $n % 100 ) ? 'proizvod' : 'proizvoda';
};

$ld = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'CollectionPage',
	'name'       => $h1,
	'url'        => get_term_link( $c ),
	'mainEntity' => array( '@type' => 'ItemList', 'itemListElement' => array() ),
);
foreach ( $list as $i => $p ) {
	$ld['mainEntity']['itemListElement'][] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink( $p->get_id() ), 'name' => $p->get_name() );
}
bu_add_jsonld( $ld );

get_header();
?>
<div class="paper-zone">
<div class="wrap">
	<?php
	bu_crumbs( array( array( 'label' => 'Katalog', 'href' => wc_get_page_permalink( 'shop' ) ), array( 'label' => $c->name ) ) );
	bu_print_notices();
	?>
	<nav class="tabs" aria-label="Kategorije">
		<?php foreach ( bu_categories() as $k ) : ?>
			<a class="tab" href="<?php echo esc_url( get_term_link( $k ) ); ?>"<?php echo $k->term_id === $c->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $k->name ); ?></a>
		<?php endforeach; ?>
	</nav>

	<section class="cat-hero" aria-labelledby="cat-title">
		<img class="cat-hero-bg" src="<?php echo esc_url( $bg ); ?>" alt="" width="1000" height="562" style="object-position:<?php echo esc_attr( $bg_pos ); ?>" fetchpriority="high"<?php if ( preg_match( '#^https?://#', $bg ) && false === strpos( $bg, home_url() ) ) : ?> referrerpolicy="no-referrer" onerror="this.onerror=null;this.src='<?php echo esc_url( bu_img( 'bg/kraft.webp' ) ); ?>'"<?php endif; ?> />
		<div class="cat-hero-card">
			<span class="badge badge-honey"><?php echo esc_html( $c->name ); ?></span>
			<h1 id="cat-title" class="h1"><?php echo esc_html( $h1 ); ?></h1>
			<?php if ( $text ) : ?><p class="muted"><?php echo esc_html( $text ); ?></p><?php endif; ?>
			<?php
			$ak = function_exists( 'bumbaru_akcija' ) ? bumbaru_akcija() : null;
			if ( $ak && $ak['kategorija'] === $c->slug && empty( $ak['samo'] ) ) {
				echo bu_odbrojavanje(); // phpcs:ignore
			}
			?>
		</div>
	</section>

	<?php if ( $soon ) : ?>
		<section class="soon panel" aria-labelledby="soon-title">
			<img src="<?php echo esc_url( bu_img( 'c/igracke-sm.webp' ) ); ?>" alt="Crtež igračaka: čigra, kockice i gusenica" width="600" height="600" />
			<div>
				<span class="badge badge-berry">Uskoro</span>
				<h2 id="soon-title" class="h2" style="margin-top:12px">Igračke stižu uskoro</h2>
				<p class="muted" style="margin:10px 0 20px">Igračke stavljamo u prodaju tek kad prođu proveru bezbednosti. Javite nam šta vaše dete voli, pa vam pišemo kad krenu.</p>
				<div class="soon-actions">
					<a class="btn btn-primary btn-lg" href="<?php echo esc_url( bu_messenger_url( 'Zdravo, javite mi kad stignu igračke za decu.', 'igracke' ) ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Javite mi na Messenger-u</a>
					<a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'pokloni-sa-imenom' ) ); ?>">Do tada: pokloni sa imenom<?php echo bu_icon( 'arrow' ); ?></a>
				</div>
			</div>
		</section>
	<?php else : ?>
		<section class="list" aria-label="Proizvodi">
			<div class="toolbar">
				<?php if ( $counts ) : ?>
					<div class="chips" role="group" aria-label="Filter">
						<?php foreach ( $counts as $i => $f ) : ?>
							<button class="chip" type="button" data-filter="<?php echo esc_attr( $f['id'] ); ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"><?php echo esc_html( $f['label'] ); ?> <span class="cnt"><?php echo (int) $f['n']; ?></span></button>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<span></span>
				<?php endif; ?>
				<div class="tb-right">
					<?php bu_search_field( $c->slug, $c->name ); ?>
					<?php
					// Ukras sa imenom ima karticu za svaki motiv, pa se broje kartice.
					$n_kart = 0;
					foreach ( $list as $p ) {
						$n_kart += function_exists( 'bumbaru_motiv_kartice' ) ? max( 1, count( bumbaru_motiv_kartice( $p ) ) ) : 1;
					}
					?>
					<p class="small muted" data-count><?php echo (int) $n_kart . ' ' . esc_html( $plural( $n_kart ) ); ?></p>
				</div>
			</div>
			<div class="grid" data-grid>
				<?php foreach ( $list as $i => $p ) { bu_product_cards( $p, $i < 4, true ); } ?>
			</div>
		</section>
	<?php endif; ?>
</div>
</div>
<div class="wrap">
	<?php if ( ! $soon && ( $about_h || $about_p || $faq ) ) : ?>
		<section class="about" aria-labelledby="about-title">
			<div>
				<?php if ( $about_h ) : ?><h2 id="about-title" class="h2"><?php echo esc_html( $about_h ); ?></h2><?php endif; ?>
				<?php foreach ( $about_p as $t ) : ?><p class="muted" style="margin-top:14px"><?php echo esc_html( $t ); ?></p><?php endforeach; ?>
			</div>
			<div>
				<?php if ( $faq ) : ?>
					<h2 class="h3" style="margin-bottom:8px">Česta pitanja</h2>
					<?php bu_accordion( $faq ); ?>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="notfound" aria-labelledby="nf-title">
		<div>
			<h2 id="nf-title" class="h2">Niste našli šta tražite?</h2>
			<p class="muted" style="margin-top:8px">Pošaljite ideju, meru ili fotografiju. Javljamo cenu i rok izrade, a gotov proizvod šaljemo kurirom.</p>
		</div>
		<a class="btn btn-primary btn-lg" href="<?php echo esc_url( bu_page_url( 'po-meri' ) ); ?>">Pošaljite upit</a>
	</section>
</div>
<?php
get_footer();
