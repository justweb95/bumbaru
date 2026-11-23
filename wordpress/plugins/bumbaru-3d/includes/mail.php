<?php
/**
 * Mejl sajta: sve ide preko prodavnica@bumbaru.rs.
 *
 * – Svi mejlovi (porudžbine, nalog, nova lozinka, obaveštenja administratoru) šalju se sa prodavnica@bumbaru.rs, pošiljalac „Bumbaru”.
 * – Ista adresa je adresa administratora sajta i prima obaveštenja o novim porudžbinama.
 * – Slanje ide preko PHPMailer-a (deo WordPress-a) kroz SMTP sanduče prodavnica@bumbaru.rs na hostingu.
 *   Lozinka se upisuje jednom u Bumbaru 3D → Podešavanja sajta → „Mejl sajta” (čuva se u bazi, ne u kodu).
 *   Bez lozinke sajt šalje preko PHP mail() funkcije hostinga, sa iste adrese.
 */

defined( 'ABSPATH' ) || exit;

define( 'BUMBARU_MAIL', 'prodavnica@bumbaru.rs' );
define( 'BUMBARU_MAIL_NAME', 'Bumbaru' );

/** SMTP podešavanja (host, port, šifrovanje, korisnik, lozinka). */
function bumbaru_smtp() {
	$s = get_option( 'bumbaru_smtp', array() );
	$s = is_array( $s ) ? $s : array();
	return array(
		'host'   => ! empty( $s['host'] ) ? $s['host'] : 'mail.bumbaru.rs',
		'port'   => ! empty( $s['port'] ) ? (int) $s['port'] : 465,
		'secure' => isset( $s['secure'] ) && in_array( $s['secure'], array( 'ssl', 'tls', '' ), true ) ? $s['secure'] : 'ssl',
		'user'   => ! empty( $s['user'] ) ? $s['user'] : BUMBARU_MAIL,
		'pass'   => isset( $s['pass'] ) ? (string) $s['pass'] : '',
	);
}

// Pošiljalac svih mejlova.
add_filter( 'wp_mail_from', function () {
	return BUMBARU_MAIL;
}, 99 );
add_filter( 'wp_mail_from_name', function ( $name ) {
	return ( '' === (string) $name || 'WordPress' === $name ) ? BUMBARU_MAIL_NAME : $name;
}, 99 );
add_filter( 'woocommerce_email_from_address', function () {
	return BUMBARU_MAIL;
}, 99 );

// PHPMailer preko SMTP sandučeta prodavnica@bumbaru.rs (kad je lozinka upisana).
add_action( 'phpmailer_init', function ( $m ) {
	$s = bumbaru_smtp();
	$m->Sender = BUMBARU_MAIL; // povratna adresa (Return-Path) na našem domenu
	if ( '' === $s['pass'] ) {
		return;
	}
	$m->isSMTP();
	$m->Host       = $s['host'];
	$m->Port       = $s['port'];
	$m->SMTPAuth   = true;
	$m->Username   = $s['user'];
	$m->Password   = $s['pass'];
	$m->SMTPSecure = $s['secure'];
	$m->SMTPAutoTLS = 'ssl' !== $s['secure'];
	$m->Timeout    = 15;
	$m->CharSet    = 'UTF-8';
}, 99 );

/** Jednom: prodavnica@bumbaru.rs postaje adresa administratora, pošiljalac i primalac obaveštenja WooCommerce-a. */
function bumbaru_mail_setup() {
	if ( get_option( 'bumbaru_mail_setup' ) === BUMBARU_MAIL ) {
		return;
	}
	update_option( 'admin_email', BUMBARU_MAIL );
	delete_option( 'new_admin_email' );
	update_option( 'woocommerce_email_from_address', BUMBARU_MAIL );
	update_option( 'woocommerce_email_from_name', BUMBARU_MAIL_NAME );
	foreach ( array( 'new_order', 'cancelled_order', 'failed_order' ) as $e ) {
		$k = 'woocommerce_' . $e . '_settings';
		$v = get_option( $k, array() );
		$v = is_array( $v ) ? $v : array();
		$v['recipient'] = BUMBARU_MAIL;
		if ( 'new_order' === $e ) {
			$v['enabled'] = 'yes';
		}
		update_option( $k, $v );
	}
	update_option( 'bumbaru_mail_setup', BUMBARU_MAIL );
}
add_action( 'init', 'bumbaru_mail_setup', 20 );

/* ——— Administracija: kartica „Mejl sajta” na strani Podešavanja sajta ——— */

add_action( 'admin_post_bumbaru_save_mail', function () {
	check_admin_referer( 'bumbaru_save_mail' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemate dozvolu.' );
	}
	$in  = isset( $_POST['smtp'] ) ? (array) wp_unslash( $_POST['smtp'] ) : array(); // phpcs:ignore
	$old = bumbaru_smtp();
	$out = array(
		'host'   => sanitize_text_field( isset( $in['host'] ) ? $in['host'] : '' ),
		'port'   => absint( isset( $in['port'] ) ? $in['port'] : 0 ),
		'secure' => isset( $in['secure'] ) && in_array( $in['secure'], array( 'ssl', 'tls', '' ), true ) ? $in['secure'] : 'ssl',
		'user'   => sanitize_text_field( isset( $in['user'] ) ? $in['user'] : '' ),
		// prazno polje = lozinka ostaje ista
		'pass'   => isset( $in['pass'] ) && '' !== $in['pass'] ? (string) $in['pass'] : $old['pass'],
	);
	if ( ! empty( $in['obrisi'] ) ) {
		$out['pass'] = '';
	}
	update_option( 'bumbaru_smtp', $out, false );
	$msg = 'Sačuvano.';
	if ( ! empty( $_POST['probni'] ) ) {
		$err = '';
		$cb  = function ( $e ) use ( &$err ) {
			$err = $e->get_error_message();
		};
		add_action( 'wp_mail_failed', $cb );
		$ok  = wp_mail( BUMBARU_MAIL, 'Bumbaru: probni mejl', "Ovo je probni mejl sa sajta bumbaru.rs.\n\nAko ste ga dobili, mejlovi za porudžbine rade." );
		$msg = $ok ? 'Probni mejl je poslat na ' . BUMBARU_MAIL . '. Proverite sanduče (i neželjenu poštu).' : 'Probni mejl nije poslat: ' . ( $err ? $err : 'nepoznata greška' ) . '.';
	}
	set_transient( 'bumbaru_mail_msg', $msg, 120 );
	bumbaru_admin_redirect( 'bumbaru-sajt' );
} );

function bumbaru_mail_card() {
	$s   = bumbaru_smtp();
	$msg = get_transient( 'bumbaru_mail_msg' );
	delete_transient( 'bumbaru_mail_msg' );
	?>
	<form class="bu-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
		<input type="hidden" name="action" value="bumbaru_save_mail" />
		<?php wp_nonce_field( 'bumbaru_save_mail' ); ?>
		<h2>Mejl sajta: <?php echo esc_html( BUMBARU_MAIL ); ?></h2>
		<?php if ( $msg ) : ?>
			<div class="notice notice-info inline"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>
		<p class="description">Svi mejlovi sa sajta (porudžbine, nalog, nova lozinka) idu sa ove adrese, pošiljalac „Bumbaru”. Ista adresa je adresa administratora i na nju stižu nove porudžbine.
			Slanje ide preko sandučeta na hostingu (SMTP). Bez lozinke sajt šalje preko PHP-a hostinga.</p>
		<p><strong>Stanje:</strong> <?php echo '' !== $s['pass'] ? 'šalje se preko SMTP-a (' . esc_html( $s['user'] . ' @ ' . $s['host'] . ':' . $s['port'] ) . ') ✓' : 'lozinka nije upisana, šalje se preko PHP mail()'; ?></p>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="smtp-user">Korisničko ime</label></th><td><input class="regular-text" id="smtp-user" name="smtp[user]" value="<?php echo esc_attr( $s['user'] ); ?>" /><p class="description">Na cPanel hostingu je to cela adresa: <?php echo esc_html( BUMBARU_MAIL ); ?>.</p></td></tr>
			<tr><th scope="row"><label for="smtp-pass">Lozinka sandučeta</label></th><td><input class="regular-text" id="smtp-pass" type="password" name="smtp[pass]" value="" autocomplete="new-password" placeholder="<?php echo '' !== $s['pass'] ? '•••••••• (upisana; prazno = ostaje ista)' : ''; ?>" />
				<?php if ( '' !== $s['pass'] ) : ?><label style="margin-left:8px"><input type="checkbox" name="smtp[obrisi]" value="1" /> obriši lozinku</label><?php endif; ?></td></tr>
			<tr><th scope="row"><label for="smtp-host">Server (SMTP)</label></th><td><input class="regular-text" id="smtp-host" name="smtp[host]" value="<?php echo esc_attr( $s['host'] ); ?>" /><p class="description">U cPanelu: Email nalozi → Connect Devices → „Outgoing Server”.</p></td></tr>
			<tr><th scope="row"><label for="smtp-port">Port i šifrovanje</label></th><td>
				<input id="smtp-port" type="number" name="smtp[port]" value="<?php echo esc_attr( $s['port'] ); ?>" style="width:90px" />
				<select name="smtp[secure]">
					<option value="ssl"<?php selected( $s['secure'], 'ssl' ); ?>>SSL (port 465)</option>
					<option value="tls"<?php selected( $s['secure'], 'tls' ); ?>>TLS (port 587)</option>
					<option value=""<?php selected( $s['secure'], '' ); ?>>bez šifrovanja</option>
				</select></td></tr>
		</table>
		<p><button class="button bu-btn" type="submit">Sačuvaj</button> <button class="button" type="submit" name="probni" value="1">Sačuvaj i pošalji probni mejl</button></p>
	</form>
	<?php
}
