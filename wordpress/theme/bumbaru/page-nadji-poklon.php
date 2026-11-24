<?php
/**
 * Kviz „Nađi poklon” (/nadji-poklon/): 4 pitanja (za koga, povod, šta voli, budžet),
 * pa predlozi iz kataloga (preneto iz nadji-poklon.astro i lib/kviz.ts).
 * Proizvodi koji nisu u spisku dobijaju oznake po kategoriji, pa i novi proizvodi ulaze u kviz.
 */
defined( 'ABSPATH' ) || exit;

bu_set_seo( 'Nađi poklon za 20 sekundi | Bumbaru', 'Četiri brza pitanja, pa vam predlažemo poklone iz Bumbaru kataloga: za dete, za nju, za njega, za kolege ili za dom. Plaćanje pouzećem.' );

$pitanja = function_exists( 'bumbaru_astro_data' ) ? bumbaru_astro_data( 'PITANJA' ) : array();

$svi_za     = array( 'dete', 'ona', 'on', 'porodica', 'kolege', 'dom' );
$svi_povodi = array( 'nova-godina', 'rodjendan', 'useljenje', 'paznja' );
$P          = function ( $za, $povod, $stil ) {
	return array( 'za' => $za, 'povod' => $povod, 'stil' => $stil );
};
$po_kat  = array(
	'nova-godina'       => $P( $svi_za, array( 'nova-godina', 'paznja' ), array( 'lepo' ) ),
	'pokloni-sa-imenom' => $P( $svi_za, $svi_povodi, array( 'ime' ) ),
	'privesci'          => $P( array( 'dete', 'ona', 'on', 'kolege' ), array( 'rodjendan', 'paznja', 'nova-godina' ), array( 'zabavno', 'korisno' ) ),
	'dom-i-kuhinja'     => $P( array( 'ona', 'dom', 'porodica' ), array( 'useljenje', 'rodjendan', 'paznja' ), array( 'lepo', 'korisno' ) ),
	'radionica'         => $P( array( 'on', 'dom' ), array( 'rodjendan', 'useljenje', 'paznja' ), array( 'korisno' ) ),
	'auto-i-moto'       => $P( array( 'on' ), array( 'rodjendan', 'paznja' ), array( 'korisno' ) ),
	'slavske-modlice'   => $P( array( 'ona', 'porodica', 'dom' ), array( 'paznja', 'useljenje' ), array( 'korisno', 'lepo' ) ),
);
$lutkica = $P( array( 'dete', 'ona', 'porodica', 'kolege' ), array( 'nova-godina', 'paznja', 'rodjendan' ), array( 'zabavno', 'lepo' ) );
$ime     = $P( array( 'dete', 'ona', 'on', 'kolege' ), array( 'rodjendan', 'paznja', 'nova-godina' ), array( 'ime', 'zabavno' ) );
$profili = array(
	'ukras-za-jelku-sa-imenom'               => $P( $svi_za, array( 'nova-godina' ), array( 'ime', 'lepo' ) ),
	'rotirajuci-ukras-sa-imenom'             => $P( $svi_za, array( 'nova-godina' ), array( 'ime', 'lepo' ) ),
	'jelka-sa-postoljem'                     => $P( array( 'kolege', 'dom', 'ona', 'on' ), array( 'nova-godina', 'paznja' ), array( 'lepo' ) ),
	'rebrasta-ovalna-tacna'                  => $P( array( 'ona', 'dom', 'porodica' ), array( 'nova-godina', 'useljenje', 'paznja' ), array( 'lepo', 'korisno' ) ),
	'porodicni-set-ukrasa-sa-imenima'        => $P( array( 'porodica', 'ona', 'dom' ), array( 'nova-godina' ), array( 'ime', 'lepo' ) ),
	'privezak-deda-mraz'                     => $P( array( 'dete', 'kolege', 'ona', 'on' ), array( 'nova-godina', 'paznja' ), array( 'zabavno' ) ),
	'privezak-irvas'                         => $P( array( 'dete', 'kolege', 'ona', 'on' ), array( 'nova-godina', 'paznja' ), array( 'zabavno' ) ),
	'patuljak-sa-led-svecicom'               => $P( array( 'ona', 'porodica', 'dom', 'kolege' ), array( 'nova-godina', 'paznja' ), array( 'lepo' ) ),
	'prstenovi-za-salvete-set-od-6'          => $P( array( 'dom', 'porodica', 'ona' ), array( 'nova-godina' ), array( 'lepo', 'korisno' ) ),
	'mini-jelka-za-sto'                      => $P( array( 'kolege', 'dom', 'ona', 'on' ), array( 'nova-godina', 'paznja' ), array( 'lepo' ) ),
	'zlatna-jelka-za-sklapanje'              => $P( array( 'kolege', 'ona', 'dom' ), array( 'nova-godina', 'paznja' ), array( 'lepo', 'zabavno' ) ),
	'jelkica-za-punjenje'                    => $P( array( 'dete', 'porodica' ), array( 'nova-godina' ), array( 'zabavno' ) ),
	'novogodisnje-modlice-za-medenjake'      => $P( array( 'porodica', 'ona', 'dete', 'dom' ), array( 'nova-godina' ), array( 'korisno', 'zabavno' ) ),
	'irvas-lutkica-pleteni-izgled'           => $lutkica,
	'snesko-lutkica-pleteni-izgled'          => $lutkica,
	'jelkica-pleteni-izgled'                 => $lutkica,
	'deda-mraz-lutkica-pleteni-izgled'       => $lutkica,
	'novogodisnja-kucica-sa-led-svecicom'    => $P( array( 'porodica', 'dete', 'ona', 'dom' ), array( 'nova-godina' ), array( 'lepo', 'zabavno' ) ),
	'spiralna-jelka-sa-zvezdom'              => $P( array( 'dom', 'ona', 'kolege' ), array( 'nova-godina', 'useljenje' ), array( 'lepo' ) ),
	'porodicni-privezak-sa-imenima'          => $P( array( 'porodica' ), $svi_povodi, array( 'ime' ) ),
	'privezak-sa-imenom'                     => $ime,
	'privezak-sa-imenom-bajkovita-slova'     => $P( array( 'dete', 'ona' ), array( 'rodjendan', 'paznja', 'nova-godina' ), array( 'ime', 'zabavno' ) ),
	'privezak-sa-imenom-kockice'             => $P( array( 'dete', 'on' ), array( 'rodjendan', 'paznja', 'nova-godina' ), array( 'ime', 'zabavno' ) ),
	'pikselasti-privezak-sa-imenom'          => $P( array( 'dete' ), array( 'rodjendan', 'paznja', 'nova-godina' ), array( 'ime', 'zabavno' ) ),
	'privezak-i-stalak-za-telefon'           => $P( array( 'on', 'ona', 'kolege' ), array( 'paznja', 'rodjendan', 'nova-godina' ), array( 'korisno', 'zabavno' ) ),
	'klipovi-za-kesice'                      => $P( array( 'dom', 'porodica', 'kolege' ), array( 'useljenje', 'paznja' ), array( 'korisno', 'zabavno' ) ),
	'rebrasta-vaza-sa-tacnom-i-svecnjakom'   => $P( array( 'ona', 'dom' ), array( 'useljenje', 'rodjendan' ), array( 'lepo' ) ),
	'podmetaci-monstera-sa-saksijom'         => $P( array( 'ona', 'dom', 'kolege' ), array( 'useljenje', 'rodjendan', 'paznja' ), array( 'lepo', 'korisno' ) ),
);
// Grupe sličnih proizvoda: u predlozima najviše dva iz iste grupe.
$grupe = array(
	'irvas-lutkica-pleteni-izgled' => 'lutkice', 'snesko-lutkica-pleteni-izgled' => 'lutkice', 'jelkica-pleteni-izgled' => 'lutkice', 'deda-mraz-lutkica-pleteni-izgled' => 'lutkice',
	'privezak-sa-imenom' => 'ime', 'privezak-sa-imenom-bajkovita-slova' => 'ime', 'privezak-sa-imenom-kockice' => 'ime', 'pikselasti-privezak-sa-imenom' => 'ime',
	'privezak-deda-mraz' => 'ng-privesci', 'privezak-irvas' => 'ng-privesci',
);

$lista = array();
if ( function_exists( 'wc_get_products' ) ) {
	foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'visibility' => 'catalog', 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $i => $p ) {
		$slug = $p->get_slug();
		$cat  = (string) bu_pmeta( $p, 'primary_cat' );
		if ( ! $cat ) {
			$pc  = bu_primary_cat( $p );
			$cat = $pc ? $pc->slug : '';
		}
		$pr = isset( $profili[ $slug ] ) ? $profili[ $slug ] : ( isset( $po_kat[ $cat ] ) ? $po_kat[ $cat ] : $P( $svi_za, $svi_povodi, array() ) );
		// Cena za budžet: najmanja cena; kod izbora komada cena prvog paketa sa popustom (npr. 4 klipa).
		$cena = 0;
		if ( function_exists( 'bumbaru_all_prices' ) ) {
			$all = bumbaru_all_prices( $p );
			if ( $all ) {
				$n    = bumbaru_eff( $p, min( $all ) );
				$cena = $n;
				if ( bumbaru_izbor_komada( $p ) ) {
					$t = bumbaru_config( $p )['motivi']['popusti'];
					if ( $t ) {
						$k    = (int) $t[0]['od'];
						$cena = round( $k * $n * ( 1 - bumbaru_popust_za( $t, $k ) / 100 ) / 10 ) * 10;
					}
				}
			}
		}
		$g = isset( $grupe[ $slug ] ) ? $grupe[ $slug ] : '';
		if ( ! $g && 'slavske-modlice' === $cat && ! ( function_exists( 'bumbaru_izbor_komada' ) && bumbaru_izbor_komada( $p ) ) ) {
			$g = preg_match( '/set/i', $p->get_name() ) ? 'slavski-setovi' : 'slavske-modlice';
		}
		$lista[] = array( 'p' => $p, 'i' => $i, 'pr' => $pr, 'cena' => $cena, 'foto' => (bool) $p->get_image_id(), 'g' => $g ? $g : $slug );
	}
}
$n_q = count( $pitanja );
get_header();
?>
<div class="paper-zone">
	<div class="wrap kv-wrap">
		<section class="kv" data-kviz aria-live="polite">
			<div class="kv-top">
				<button type="button" class="kv-back" data-back hidden><?php echo bu_icon( 'arrow', 18 ); ?>Nazad</button>
				<span class="kv-step" data-step>Pitanje 1 od <?php echo (int) $n_q; ?></span>
			</div>
			<div class="kv-track" aria-hidden="true">
				<div class="kv-fill" data-fill></div>
				<?php for ( $i = 0; $i < $n_q; $i++ ) : ?><i class="kv-dot" style="--x:<?php echo esc_attr( round( ( $i + 1 ) / $n_q * 100, 2 ) ); ?>%"></i><?php endfor; ?>
				<img class="kv-bee" data-bee src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="52" height="52" />
			</div>

			<?php foreach ( $pitanja as $qi => $q ) : ?>
				<?php $ima_slike = (bool) array_filter( $q['odgovori'], function ( $o ) { return ! empty( $o['slika'] ); } ); ?>
				<div class="kv-q" data-q="<?php echo esc_attr( $q['id'] ); ?>"<?php echo $qi > 0 ? ' hidden' : ''; ?>>
					<<?php echo 0 === $qi ? 'h1' : 'h2'; ?> class="h1 kv-title"><?php echo esc_html( $q['naslov'] ); ?></<?php echo 0 === $qi ? 'h1' : 'h2'; ?>>
					<?php if ( 0 === $qi ) : ?><p class="lead kv-lead">Četiri brza pitanja, pa vam predlažemo poklone iz našeg kataloga.</p><?php endif; ?>
					<div class="kv-opts<?php echo $ima_slike ? '' : ' kv-opts-mali'; ?>">
						<?php
						foreach ( $q['odgovori'] as $oi => $o ) :
							$sl = ! empty( $o['slika'] ) ? bu_slug_img( $o['slika'], '' ) : '';
							if ( $sl && false !== strpos( $sl, 'pcelica' ) ) {
								$sl = '';
							}
							?>
							<button type="button" class="kv-opt" data-a="<?php echo esc_attr( $o['id'] ); ?>" data-label="<?php echo esc_attr( $o['label'] ); ?>" style="--r:<?php echo $oi % 2 ? '1.5' : '-1.5'; ?>deg">
								<?php if ( $sl ) : ?>
									<span class="kv-img"><img src="<?php echo esc_url( $sl ); ?>" alt="" width="300" height="300" loading="<?php echo 0 === $qi ? 'eager' : 'lazy'; ?>" /></span>
								<?php else : ?>
									<span class="kv-ic"><?php echo bu_icon( $o['ikonica'], 28 ); ?></span>
								<?php endif; ?>
								<span class="kv-lab"><?php echo esc_html( $o['label'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>

			<div class="kv-res" data-res hidden>
				<div class="kv-res-head">
					<img class="kv-res-bee fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="96" height="96" />
					<div>
						<span class="badge badge-honey">Vaš Bumbaru izbor</span>
						<h2 class="h1" data-res-title>Evo šta predlažemo</h2>
						<p class="muted" data-res-sub></p>
					</div>
				</div>
				<div class="grid kv-grid" data-grid>
					<?php foreach ( $lista as $x ) : ?>
						<div class="kv-item" hidden data-i="<?php echo (int) $x['i']; ?>" data-za="<?php echo esc_attr( implode( ' ', $x['pr']['za'] ) ); ?>" data-povod="<?php echo esc_attr( implode( ' ', $x['pr']['povod'] ) ); ?>" data-stil="<?php echo esc_attr( implode( ' ', $x['pr']['stil'] ) ); ?>" data-cena="<?php echo esc_attr( $x['cena'] ); ?>" data-foto="<?php echo $x['foto'] ? '1' : '0'; ?>" data-g="<?php echo esc_attr( $x['g'] ); ?>">
							<?php bu_product_card( $x['p'] ); ?>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="kv-res-act">
					<button type="button" class="btn btn-lg" data-again><?php echo bu_icon( 'sparkle' ); ?>Ponovi kviz</button>
					<a class="btn btn-primary btn-lg" data-ask href="<?php echo esc_url( bu_messenger_url( 'Zdravo! Tražim poklon, možete li da mi predložite nešto?', 'kviz' ) ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Pošaljite mi još predloga</a>
					<a class="link-arrow" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Ceo katalog<?php echo bu_icon( 'arrow' ); ?></a>
				</div>
			</div>
		</section>
	</div>
</div>
<?php
get_footer();
