<?php
/**
 * Meni naloga (Bumbaru): kartice sa ikonicama umesto spiska linkova.
 * Zasnovano na WooCommerce šablonu myaccount/navigation.php.
 *
 * @version 9.3.0
 */
defined( 'ABSPATH' ) || exit;

$bu_ico = array(
	'dashboard'       => 'home',
	'orders'          => 'list',
	'downloads'       => 'download',
	'edit-address'    => 'pin',
	'payment-methods' => 'cash',
	'edit-account'    => 'user',
	'customer-logout' => 'logout',
);

do_action( 'woocommerce_before_account_navigation' );
?>
<nav class="woocommerce-MyAccount-navigation acc-nav" aria-label="<?php esc_attr_e( 'Account pages', 'woocommerce' ); ?>">
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); // phpcs:ignore ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>>
					<span class="acc-ic" aria-hidden="true"><?php echo bu_icon( isset( $bu_ico[ $endpoint ] ) ? $bu_ico[ $endpoint ] : 'star', 18 ); ?></span>
					<span><?php echo esc_html( $label ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
<?php
do_action( 'woocommerce_after_account_navigation' );
