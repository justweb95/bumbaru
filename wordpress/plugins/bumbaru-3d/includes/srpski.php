<?php
/**
 * Ceo sajt na srpskom, latinicom: WordPress, WooCommerce (i administracija, korpa, plaćanje,
 * emailovi, blokovi) i svi drugi dodaci koji imaju srpski prevod.
 *
 * Jezik sajta se postavi na „Srpski” (sr_RS) i preuzmu se zvanični prevodi sa wordpress.org.
 * Zvanični srpski prevodi su na ćirilici, pa ih ovde u letu prebacujemo u latinicu:
 * PHP tekstove (gettext) i JavaScript tekstove (JSON prevodi za blokove i WooCommerce admin).
 * Prevodi se dalje sami osvežavaju kad WordPress ažurira jezike.
 */

defined( 'ABSPATH' ) || exit;

/** Ćirilica → latinica (srpska). Velika slova u reči od velikih slova: ЉУБАВ → LJUBAV. */
function bumbaru_lat( $s ) {
	if ( ! is_string( $s ) || '' === $s || ! preg_match( '/[\x{0400}-\x{04FF}]/u', $s ) ) {
		return $s;
	}
	static $map = array(
		'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Ђ' => 'Đ', 'Е' => 'E', 'Ж' => 'Ž', 'З' => 'Z', 'И' => 'I',
		'Ј' => 'J', 'К' => 'K', 'Л' => 'L', 'Љ' => 'Lj', 'М' => 'M', 'Н' => 'N', 'Њ' => 'Nj', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
		'С' => 'S', 'Т' => 'T', 'Ћ' => 'Ć', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Č', 'Џ' => 'Dž', 'Ш' => 'Š',
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'ђ' => 'đ', 'е' => 'e', 'ж' => 'ž', 'з' => 'z', 'и' => 'i',
		'ј' => 'j', 'к' => 'k', 'л' => 'l', 'љ' => 'lj', 'м' => 'm', 'н' => 'n', 'њ' => 'nj', 'о' => 'o', 'п' => 'p', 'р' => 'r',
		'с' => 's', 'т' => 't', 'ћ' => 'ć', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'č', 'џ' => 'dž', 'ш' => 'š',
	);
	$s = preg_replace_callback( '/[ЉЊЏ](?=[\x{0400}-\x{042F}])/u', function ( $m ) {
		return array( 'Љ' => 'LJ', 'Њ' => 'NJ', 'Џ' => 'DŽ' )[ $m[0] ];
	}, $s );
	$s = strtr( $s, $map );
	// „Вордпрес” → WordPress (latinicom se piše originalno ime, sa nastavkom posle crtice).
	return false === strpos( $s, 'Vordpres' ) ? $s : preg_replace_callback( '/Vordpres(\p{Ll}*)/u', function ( $m ) {
		return 'WordPress' . ( '' !== $m[1] ? '-' . $m[1] : '' );
	}, $s );
}

/** Da li je jezik (sajta ili korisnika) srpski. */
function bumbaru_is_sr() {
	static $sr = array();
	$l = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	if ( ! isset( $sr[ $l ] ) ) {
		$sr[ $l ] = 0 === strpos( $l, 'sr' );
	}
	return $sr[ $l ];
}

// PHP tekstovi svih dodataka i WordPress-a.
add_filter( 'gettext', function ( $t ) {
	return bumbaru_is_sr() ? bumbaru_lat( $t ) : $t;
}, 999 );
add_filter( 'gettext_with_context', function ( $t ) {
	return bumbaru_is_sr() ? bumbaru_lat( $t ) : $t;
}, 999 );
add_filter( 'ngettext', function ( $t ) {
	return bumbaru_is_sr() ? bumbaru_lat( $t ) : $t;
}, 999 );
add_filter( 'ngettext_with_context', function ( $t ) {
	return bumbaru_is_sr() ? bumbaru_lat( $t ) : $t;
}, 999 );

// JavaScript tekstovi (blokovi korpe i plaćanja, WooCommerce administracija).
add_filter( 'load_script_translations', function ( $json ) {
	return is_string( $json ) && bumbaru_is_sr() ? bumbaru_lat( $json ) : $json;
}, 999 );

// Nazivi država, okruga i meseci dolaze iz prevoda, ali neki dodaci ih keširaju: i njih u latinicu.
add_filter( 'woocommerce_countries', function ( $c ) {
	return bumbaru_is_sr() ? array_map( 'bumbaru_lat', (array) $c ) : $c;
}, 999 );
add_filter( 'woocommerce_states', function ( $s ) {
	if ( ! bumbaru_is_sr() ) {
		return $s;
	}
	foreach ( (array) $s as $k => $list ) {
		$s[ $k ] = is_array( $list ) ? array_map( 'bumbaru_lat', $list ) : $list;
	}
	return $s;
}, 999 );

/**
 * Prebacuje sajt na srpski: jezik sajta sr_RS i preuzimanje prevoda (WordPress, WooCommerce, ostali dodaci).
 * Vraća spisak poruka o tome šta je urađeno.
 */
function bumbaru_install_srpski() {
	$log = array();
	if ( ! function_exists( 'wp_download_language_pack' ) ) {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/update.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$have = in_array( 'sr_RS', get_available_languages(), true );
	if ( ! $have ) {
		$have = (bool) wp_download_language_pack( 'sr_RS' );
	}
	if ( $have ) {
		update_option( 'WPLANG', 'sr_RS' );
		$log[] = 'Jezik sajta: srpski (sr_RS).';
	} else {
		$log[] = 'Prevod WordPress-a nije preuzet (hosting ne dozvoljava upis u wp-content/languages). Podešavanja → Opšte → Jezik sajta → Srpski.';
		return $log;
	}

	// Prevodi dodataka i teme za srpski (WooCommerce i ostali).
	switch_to_locale( 'sr_RS' );
	delete_site_transient( 'update_plugins' );
	delete_site_transient( 'update_themes' );
	wp_clean_update_cache();
	wp_update_plugins();
	wp_update_themes();
	$updates = array_filter( wp_get_translation_updates(), function ( $u ) {
		return 'sr_RS' === $u->language;
	} );
	if ( $updates ) {
		$up  = new Language_Pack_Upgrader( new Automatic_Upgrader_Skin() );
		$res = $up->bulk_upgrade( array_values( $updates ) );
		$ok  = 0;
		foreach ( (array) $res as $r ) {
			$ok += ( $r && ! is_wp_error( $r ) ) ? 1 : 0;
		}
		$log[] = 'Preuzeti prevodi dodataka: ' . $ok . ' od ' . count( $updates ) . ' (' . implode( ', ', array_unique( wp_list_pluck( $updates, 'slug' ) ) ) . ').';
	} else {
		$log[] = 'Prevodi dodataka su već preuzeti.';
	}
	restore_previous_locale();
	update_option( 'bumbaru_srpski_done', BUMBARU3D_VERSION );
	return $log;
}

/** Stanje za stranu „Podešavanja sajta”: jezik i da li je WooCommerce prevod tu. */
function bumbaru_srpski_status() {
	$wc = WP_LANG_DIR . '/plugins/woocommerce-sr_RS.mo';
	$l  = get_option( 'WPLANG' );
	return array(
		'jezik'  => 'sr_RS' === $l,
		'wc'     => file_exists( $wc ) || file_exists( WP_LANG_DIR . '/plugins/woocommerce-sr_RS.l10n.php' ),
		'locale' => $l ? $l : 'en_US',
	);
}

// Jednom posle nadogradnje plugina: sajt sam pređe na srpski (u administraciji, da hosting stigne da preuzme).
add_action( 'admin_init', function () {
	if ( get_option( 'bumbaru_srpski_done' ) || ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
		return;
	}
	update_option( 'bumbaru_srpski_done', 'u-toku' );
	$log = bumbaru_install_srpski();
	set_transient( 'bumbaru_srpski_log', $log, 600 );
} );

add_action( 'admin_notices', function () {
	$log = get_transient( 'bumbaru_srpski_log' );
	if ( ! $log ) {
		return;
	}
	delete_transient( 'bumbaru_srpski_log' );
	echo '<div class="notice notice-success is-dismissible"><p><strong>Bumbaru 3D: sajt je na srpskom (latinica).</strong> ' . esc_html( implode( ' ', $log ) ) . ' Ako želite da administracija za vas bude na engleskom: Korisnici → Profil → Jezik.</p></div>';
} );

add_action( 'admin_post_bumbaru_srpski', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	check_admin_referer( 'bumbaru_srpski' );
	set_transient( 'bumbaru_srpski_log', bumbaru_install_srpski(), 600 );
	wp_safe_redirect( admin_url( 'admin.php?page=bumbaru-sajt' ) );
	exit;
} );
