<?php
/**
 * Plugin Name:       Bumbaru 3D
 * Description:       Generator 3MF/STL modela za štampu, sajt na srpskom (latinica), konfiguratori proizvoda (ukras sa imenom, porodični set, privesci sa imenom u 5 stilova, slavske modlice i slavski set, izbor boja i motiva sa popustom, komplet, jelke sa zvezdicom), akcija za kategoriju, boje, filamenti i kalkulator cene 3D štampe, uvoz proizvoda i podešavanje prodavnice (plaćanje pouzećem, Post Express, besplatna dostava od 2.500 din).
 * Version:           2.5.1
 * Author:            Bumbaru
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Text Domain:       bumbaru-3d
 * WC requires at least: 8.0
 */

defined( 'ABSPATH' ) || exit;

define( 'BUMBARU3D_VERSION', '2.5.1' );
define( 'BUMBARU3D_FILE', __FILE__ );
define( 'BUMBARU3D_DIR', plugin_dir_path( __FILE__ ) );
define( 'BUMBARU3D_URL', plugin_dir_url( __FILE__ ) );

require_once BUMBARU3D_DIR . 'includes/data.php';
require_once BUMBARU3D_DIR . 'includes/ornament.php';
require_once BUMBARU3D_DIR . 'includes/tree.php';
require_once BUMBARU3D_DIR . 'includes/config.php';
require_once BUMBARU3D_DIR . 'includes/pricing.php';
require_once BUMBARU3D_DIR . 'includes/calculator.php';
require_once BUMBARU3D_DIR . 'includes/frontend.php';
require_once BUMBARU3D_DIR . 'includes/cart.php';
require_once BUMBARU3D_DIR . 'includes/i18n.php';
require_once BUMBARU3D_DIR . 'includes/srpski.php';
require_once BUMBARU3D_DIR . 'includes/setup.php';
require_once BUMBARU3D_DIR . 'includes/account.php';
require_once BUMBARU3D_DIR . 'includes/importer.php';
require_once BUMBARU3D_DIR . 'includes/seo-sync.php';
require_once BUMBARU3D_DIR . 'includes/shipping.php';

if ( is_admin() ) {
	require_once BUMBARU3D_DIR . 'includes/admin-pages.php';
	require_once BUMBARU3D_DIR . 'includes/admin-product.php';
	require_once BUMBARU3D_DIR . 'includes/admin-category.php';
	require_once BUMBARU3D_DIR . 'includes/admin-order.php';
	require_once BUMBARU3D_DIR . 'includes/generator.php';
}

// WooCommerce: plugin radi sa novim tabelama porudžbina (HPOS) i sa blokovima korpe.
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', BUMBARU3D_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', BUMBARU3D_FILE, true );
	}
} );

register_activation_hook( __FILE__, function () {
	bumbaru_install_defaults();
	bumbaru_migrate_colors();
	update_option( 'bumbaru_show_welcome', 1 );
	update_option( 'bumbaru3d_db', BUMBARU3D_VERSION );
} );

// Posle nadogradnje plugina (nova verzija preko stare): boje dobijaju oznaku „na stanju”,
// a u administraciji se pojavi poruka da uvezete nove proizvode.
add_action( 'init', function () {
	if ( get_option( 'bumbaru3d_db' ) === BUMBARU3D_VERSION ) {
		return;
	}
	bumbaru_install_defaults();
	bumbaru_migrate_colors();
	if ( get_option( 'bumbaru_store_setup_done' ) ) {
		bumbaru_ensure_new_pages();
		bumbaru_setup_account();
	}
	if ( get_option( 'bumbaru_import_done' ) ) {
		update_option( 'bumbaru_update_notice', 1 );
	}
	bumbaru_migrate_23();
	bumbaru_migrate_25();
	bumbaru_migrate_25b();
	update_option( 'bumbaru3d_db', BUMBARU3D_VERSION );
} );

/** Folder sa fotografijama za uvoz: u pluginu ili u dodatku „Bumbaru 3D – fotografije”. */
function bumbaru_import_dirs() {
	return array_filter( array(
		BUMBARU3D_DIR . 'import/',
		WP_PLUGIN_DIR . '/bumbaru-3d-fotografije/import/',
	), 'is_dir' );
}

// Upozorenje ako WooCommerce nije aktivan.
add_action( 'admin_notices', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-error"><p><strong>Bumbaru 3D</strong> radi uz WooCommerce. Instalirajte i aktivirajte WooCommerce.</p></div>';
	}
} );
