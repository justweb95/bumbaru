<?php
/**
 * Kontrolna tabla naloga (Bumbaru): pozdrav, prečice i poslednje porudžbine.
 * Zasnovano na WooCommerce šablonu myaccount/dashboard.php (iste akcije za dodatke).
 *
 * @version 4.4.0
 */
defined( 'ABSPATH' ) || exit;

$bu_ime    = $current_user->first_name ? $current_user->first_name : $current_user->display_name;
$bu_orders = wc_get_orders( array( 'customer_id' => get_current_user_id(), 'limit' => 3, 'orderby' => 'date', 'order' => 'DESC', 'status' => array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded' ) ) );
$bu_cards  = array(
	array( 'orders', 'list', 'Porudžbine', 'Šta ste poručili, status i detalji svake porudžbine.', 'tone-honey' ),
	array( 'edit-address', 'pin', 'Adrese', 'Adresa za dostavu se sama upisuje kad poručujete.', 'tone-wing' ),
	array( 'edit-account', 'user', 'Podaci naloga', 'Ime, email adresa i promena lozinke.', 'tone-leaf' ),
);
?>
<div class="acc-hello">
	<img class="fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="72" height="72" />
	<div>
		<h2 class="h2">Zdravo, <?php echo esc_html( $bu_ime ); ?>!</h2>
		<p class="muted"><?php echo esc_html( $current_user->user_email ); ?> · Niste vi? <a href="<?php echo esc_url( wc_logout_url() ); ?>">Odjavite se</a></p>
	</div>
</div>

<ul class="acc-cards">
	<?php foreach ( $bu_cards as $c ) : ?>
		<?php if ( 'orders' !== $c[0] && ! array_key_exists( $c[0], wc_get_account_menu_items() ) ) { continue; } ?>
		<li class="<?php echo esc_attr( $c[4] ); ?>">
			<a href="<?php echo esc_url( wc_get_endpoint_url( $c[0] ) ); ?>">
				<span class="acc-cic" aria-hidden="true"><?php echo bu_icon( $c[1], 22 ); ?></span>
				<b><?php echo esc_html( $c[2] ); ?></b>
				<span class="small"><?php echo esc_html( $c[3] ); ?></span>
			</a>
		</li>
	<?php endforeach; ?>
	<li class="tone-berry">
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
			<span class="acc-cic" aria-hidden="true"><?php echo bu_icon( 'gift', 22 ); ?></span>
			<b>Katalog</b>
			<span class="small">Pokloni sa imenom, ukrasi i sve što štampamo.</span>
		</a>
	</li>
</ul>

<?php if ( $bu_orders ) : ?>
	<section class="acc-recent" aria-labelledby="acc-recent-t">
		<div class="acc-recent-h">
			<h2 id="acc-recent-t" class="h3"><?php esc_html_e( 'Recent orders', 'woocommerce' ); ?></h2>
			<a class="link-arrow small" href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>">Sve porudžbine<?php echo bu_icon( 'arrow', 18 ); ?></a>
		</div>
		<ul class="acc-olist">
			<?php foreach ( $bu_orders as $o ) : ?>
				<?php
				$first = '';
				foreach ( $o->get_items() as $it ) {
					$first = $it->get_name();
					break;
				}
				$more = max( 0, $o->get_item_count() - 1 );
				?>
				<li>
					<a href="<?php echo esc_url( $o->get_view_order_url() ); ?>">
						<span class="acc-on">#<?php echo esc_html( $o->get_order_number() ); ?></span>
						<span class="acc-ot"><b><?php echo esc_html( $first ); ?><?php echo $more ? ' i još ' . (int) $more : ''; ?></b><span class="small muted"><?php echo esc_html( wc_format_datetime( $o->get_date_created() ) ); ?></span></span>
						<mark class="order-status status-<?php echo esc_attr( $o->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $o->get_status() ) ); ?></mark>
						<b class="acc-ou"><?php echo wp_kses_post( $o->get_formatted_order_total() ); ?></b>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<p class="acc-help small muted">Pitanje o porudžbini? <a href="<?php echo esc_url( bu_messenger_url( 'Zdravo, imam pitanje o porudžbini:', 'nalog' ) ); ?>" target="_blank" rel="noopener">Pišite nam na Messenger-u</a>, odgovaramo brzo.</p>

<?php
do_action( 'woocommerce_account_dashboard' );
do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );
