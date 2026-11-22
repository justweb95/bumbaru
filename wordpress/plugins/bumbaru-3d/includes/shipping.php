<?php
/**
 * Dostava: Post Express je besplatan od 2.000 din (iznos se menja u WooCommerce → Podešavanja → Dostava → Srbija).
 * Ispod tog iznosa kupac plaća kuriru po cenovniku pošte, pa ta dostava u korpi nema cenu.
 * U korpi i na plaćanju stoji koliko još fali do besplatne dostave.
 */

defined( 'ABSPATH' ) || exit;

define( 'BUMBARU_PAID_SHIP_TITLE', 'Post Express – dostavu plaćate kuriru po cenovniku pošte' );

/** Iznos za besplatnu dostavu, iz podešavanja WooCommerce dostave (zona Srbija). */
function bumbaru_free_ship_min() {
	static $min = null;
	if ( null !== $min ) {
		return $min;
	}
	$min = (float) get_option( 'bumbaru_free_ship_min', 2000 );
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		foreach ( WC_Shipping_Zones::get_zones() as $z ) {
			foreach ( $z['shipping_methods'] as $m ) {
				if ( 'free_shipping' === $m->id && 'yes' === $m->enabled && in_array( $m->requires, array( 'min_amount', 'either' ), true ) && (float) $m->min_amount > 0 ) {
					$min = (float) $m->min_amount;
					return $min;
				}
			}
		}
	}
	return $min;
}

/** „2.000 din” */
function bumbaru_free_ship_label() {
	return number_format( bumbaru_free_ship_min(), 0, ',', '.' ) . ' din';
}

/** Iznos koji se računa za besplatnu dostavu (kao u WooCommerce: sa popustima). */
function bumbaru_free_ship_cart_total() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}
	$total = (float) WC()->cart->get_displayed_subtotal();
	$total -= (float) WC()->cart->get_discount_total();
	if ( WC()->cart->display_prices_including_tax() ) {
		$total -= (float) WC()->cart->get_discount_tax();
	}
	return max( 0, $total );
}

// Kad važi besplatna dostava, ne nudi se i plaćena.
add_filter( 'woocommerce_package_rates', function ( $rates ) {
	$has_free = false;
	foreach ( $rates as $r ) {
		if ( 'free_shipping' === $r->get_method_id() ) {
			$has_free = true;
		}
	}
	if ( $has_free ) {
		foreach ( $rates as $id => $r ) {
			if ( 'flat_rate' === $r->get_method_id() && 0.0 === (float) $r->get_cost() ) {
				unset( $rates[ $id ] );
			}
		}
	}
	return $rates;
}, 20 );

// Red u korpi i na plaćanju: koliko fali do besplatne dostave.
function bumbaru_free_ship_row() {
	$min = bumbaru_free_ship_min();
	if ( $min <= 0 || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$left = $min - bumbaru_free_ship_cart_total();
	$pct  = max( 0, min( 100, round( 100 * ( 1 - max( 0, $left ) / $min ) ) ) );
	if ( $left > 0 ) {
		$msg = sprintf( 'Još <strong>%s</strong> do besplatne dostave.', wp_strip_all_tags( wc_price( $left ) ) );
	} else {
		$msg = '<strong>Dostava je besplatna.</strong>';
	}
	echo '<tr class="bu-freeship' . ( $left > 0 ? '' : ' is-free' ) . '"><td colspan="2"><div class="bu-freeship-msg">' . wp_kses_post( $msg ) . '</div><div class="bu-freeship-bar" aria-hidden="true"><span style="width:' . (int) $pct . '%"></span></div></td></tr>';
}
add_action( 'woocommerce_cart_totals_before_shipping', 'bumbaru_free_ship_row' );
add_action( 'woocommerce_review_order_before_shipping', 'bumbaru_free_ship_row' );
