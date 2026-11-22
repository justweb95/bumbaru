<?php
/**
 * Obična stranica: korpa, plaćanje, potvrda porudžbine i ostale strane iz WordPress-a.
 */
defined( 'ABSPATH' ) || exit;
$is_shop_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
if ( $is_shop_page ) {
	$GLOBALS['bu_ornaments'] = true; // pregled ukrasa sa imenom u korpi
}
get_header();
the_post();
$title    = get_the_title();
$received = function_exists( 'is_order_received_page' ) && is_order_received_page();
if ( $received ) {
	$title = 'Hvala, porudžbina je primljena';
}
$step = 0;
if ( function_exists( 'is_cart' ) && is_cart() ) {
	$step = 1;
} elseif ( $received ) {
	$step = 3;
} elseif ( function_exists( 'is_checkout' ) && is_checkout() ) {
	$step = 2;
}
$empty = 1 === $step && WC()->cart && WC()->cart->is_empty();
// Moj nalog: šareni naslovni blok, a ispod meni naloga i sadržaj (WooCommerce šabloni u temi).
$acct = function_exists( 'is_account_page' ) && is_account_page() && ! $step;
if ( $acct ) {
	$acc_page = wc_get_page_permalink( 'myaccount' );
	$acc_end  = WC()->query->get_current_endpoint();
	$acc_sub  = is_user_logged_in()
		? 'Vaše porudžbine, adrese i podaci na jednom mestu. Kupovina je i dalje moguća i bez prijave.'
		: 'Prijavite se ili napravite nalog za tren. Nalog nije obavezan: možete poručiti i bez njega.';
	if ( 'lost-password' === $acc_end ) {
		$acc_sub = 'Upišite email adresu iz naloga i poslaćemo vam link za novu lozinku.';
	}
}
$subs  = array(
	1 => $empty ? 'Korpa je još prazna. Izaberite nešto lepo u katalogu, a mi ćemo ga odštampati.' : 'Proverite šta ste izabrali, pa nastavite na plaćanje. Plaćate kuriru kad paket stigne.',
	2 => 'Još samo adresa i telefon za kurira. Nalog ne treba, a plaćate tek kad paket stigne.',
	3 => 'Javljamo vam se ako nešto nije jasno, a paket šaljemo Post Expressom čim bude gotov.',
);
?>
<div class="wrap page-wrap<?php echo $is_shop_page ? ' shop-page' : ''; ?>">
	<?php if ( $step ) : ?>
		<section class="shop-hero shop-hero-<?php echo (int) $step; ?>">
			<span class="sh-blob sh-b1" aria-hidden="true"></span><span class="sh-blob sh-b2" aria-hidden="true"></span><span class="sh-blob sh-b3" aria-hidden="true"></span>
			<span class="sh-bee" aria-hidden="true"><img src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" /></span>
			<div class="sh-text">
				<?php bu_crumbs( array( array( 'label' => $title ) ) ); ?>
				<h1 class="h1 page-title"><?php echo esc_html( $title ); ?></h1>
				<p class="sh-sub"><?php echo esc_html( $subs[ $step ] ); ?></p>
				<ol class="shop-steps" aria-label="Koraci porudžbine">
					<?php foreach ( array( 1 => 'Korpa', 2 => 'Podaci i plaćanje', 3 => 'Gotovo' ) as $n => $label ) : ?>
						<li class="<?php echo $n < $step ? 'is-done' : ( $n === $step ? 'is-on' : '' ); ?>"<?php echo $n === $step ? ' aria-current="step"' : ''; ?>><span class="n"><?php echo $n < $step ? '✓' : (int) $n; ?></span><?php echo esc_html( $label ); ?></li>
					<?php endforeach; ?>
				</ol>
			</div>
			<div class="trail trail-honey sh-trail" aria-hidden="true"></div>
		</section>
		<?php if ( $step < 3 && ! $empty ) : ?>
			<ul class="checkout-perks">
				<li><?php echo bu_icon( 'cash', 20 ); ?>Plaćanje pouzećem</li>
				<li><?php echo bu_icon( 'truck', 20 ); ?>Besplatna dostava od <?php echo esc_html( bu_free_ship_label() ); ?></li>
				<li><?php echo bu_icon( 'shield', 20 ); ?>Bez naloga</li>
				<li><?php echo bu_icon( 'sparkle', 20 ); ?>Štampamo u Leskovcu</li>
			</ul>
		<?php endif; ?>
	<?php elseif ( $acct ) : ?>
		<section class="shop-hero shop-hero-acc">
			<span class="sh-blob sh-b1" aria-hidden="true"></span><span class="sh-blob sh-b2" aria-hidden="true"></span><span class="sh-blob sh-b3" aria-hidden="true"></span>
			<span class="sh-bee" aria-hidden="true"><img src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" /></span>
			<div class="sh-text">
				<?php
				$acc_crumbs = array( array( 'label' => 'Moj nalog', 'href' => $acc_page ) );
				if ( $acc_end && 'Moj nalog' !== $title ) {
					$acc_crumbs[] = array( 'label' => $title );
				} else {
					$acc_crumbs = array( array( 'label' => 'Moj nalog' ) );
				}
				bu_crumbs( $acc_crumbs );
				?>
				<h1 class="h1 page-title"><?php echo esc_html( $title ); ?></h1>
				<p class="sh-sub"><?php echo esc_html( $acc_sub ); ?></p>
			</div>
			<div class="trail trail-honey sh-trail" aria-hidden="true"></div>
		</section>
	<?php else : ?>
		<?php bu_crumbs( array( array( 'label' => $title ) ) ); ?>
		<h1 class="h1 page-title"><?php echo esc_html( $title ); ?></h1>
	<?php endif; ?>
	<div class="page-content<?php echo $is_shop_page ? '' : ' panel doc-panel'; ?><?php echo $acct ? ' acc-page' . ( is_user_logged_in() ? ' acc-in' : ' acc-out' ) : ''; ?>">
		<?php the_content(); ?>
	</div>
</div>
<?php
get_footer();
