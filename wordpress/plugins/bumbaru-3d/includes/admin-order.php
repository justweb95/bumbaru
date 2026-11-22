<?php
/**
 * Porudžbina u administraciji: uz svaki proizvod sa imenom ili bojama vidi se mali pregled
 * (ukras sa imenom, set, jelka sa zvezdicom), da se štampa tačno ono što je kupac video.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_after_order_itemmeta', function ( $item_id, $item ) {
	if ( ! is_callable( array( $item, 'get_meta' ) ) ) {
		return;
	}
	$raw = $item->get_meta( '_bu_raw' );
	if ( ! is_array( $raw ) || empty( $raw['type'] ) ) {
		return;
	}
	$GLOBALS['bumbaru_order_defs'] = true;
	$svg = bumbaru_preview_svg( $raw );
	if ( $svg ) {
		echo '<div class="bu-mini-preview" style="display:flex;gap:6px;flex-wrap:wrap">' . $svg . '</div>'; // phpcs:ignore
	}
}, 10, 2 );

add_action( 'admin_footer', function () {
	if ( empty( $GLOBALS['bumbaru_order_defs'] ) ) {
		return;
	}
	echo '<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>' . bumbaru_ornament_defs() . '</defs></svg>'; // phpcs:ignore
} );
