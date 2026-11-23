<?php
/**
 * Izgled mejlova (porudžbina primljena, u obradi, završena, nalog…) kao na sajtu:
 * krem pozadina, bela kartica sa tamnim okvirom i „nalepnica” senkom, med-žuto zaglavlje sa logom,
 * tabela porudžbine sa isprekidanim linijama i blok „Šta sledi” u mejlovima kupcu.
 *
 * Boje se zadaju preko opcija WooCommerce-a (pre_option_*), pa važe i kad neko promeni podešavanja,
 * a ostatak ide kroz woocommerce_email_styles (WooCommerce taj CSS upisuje u sam mejl, inline).
 */

defined( 'ABSPATH' ) || exit;

/** Boje sajta (main.css + fun.css). */
function bumbaru_mail_colors() {
	return array(
		'ink'    => '#312018',
		'muted'  => '#4E3E33',
		'honey'  => '#F5C137',
		'hsoft'  => '#FFE7A3',
		'paper'  => '#FFF8EC',
		'raised' => '#F7EDDC',
		'line'   => '#E3D7C5',
		'leaf'   => '#335A22',
		'lsoft'  => '#DDF0D0',
		'sky'    => '#DDEEF7',
		'rose'   => '#FBDCD2',
		'berry'  => '#9A3420',
	);
}

/** Slika iz teme Bumbaru (logo, pčelica). */
function bumbaru_mail_img( $path ) {
	if ( function_exists( 'bu_img' ) ) {
		return bu_img( $path );
	}
	return get_template_directory_uri() . '/assets/img/' . ltrim( $path, '/' );
}

// Boje, logo i tekst u dnu mejla.
$bumbaru_mail_opts = array(
	'woocommerce_email_base_color'            => '#F5C137',
	'woocommerce_email_background_color'      => '#FFF8EC',
	'woocommerce_email_body_background_color' => '#FFFFFF',
	'woocommerce_email_text_color'            => '#312018',
	'woocommerce_email_footer_text_color'     => '#4E3E33',
	'woocommerce_email_header_alignment'      => 'center',
	'woocommerce_email_font_family'           => 'Helvetica',
);
foreach ( $bumbaru_mail_opts as $bumbaru_k => $bumbaru_v ) {
	add_filter( 'pre_option_' . $bumbaru_k, function () use ( $bumbaru_v ) {
		return $bumbaru_v;
	} );
}
unset( $bumbaru_mail_opts, $bumbaru_k, $bumbaru_v );

add_filter( 'pre_option_woocommerce_email_header_image', function () {
	return bumbaru_mail_img( 'brand/logo-tamni.png' );
} );
add_filter( 'pre_option_woocommerce_email_footer_text', function () {
	return 'Bumbaru · ručno pravljeni 3D pokloni · <a href="' . esc_url( home_url( '/' ) ) . '">bumbaru.rs</a><br>Pitanja? Samo odgovorite na ovaj mejl ili pišite na ' . BUMBARU_MAIL . '.';
} );

// Stilovi (dodaju se posle WooCommerce-ovih, pa ih nadjačavaju).
add_filter( 'woocommerce_email_styles', function ( $css ) {
	$c    = bumbaru_mail_colors();
	$font = "'Geologica', 'Segoe UI', Helvetica, Arial, sans-serif";
	$css .= "
body, #wrapper { background-color: {$c['paper']} !important; }
#wrapper { padding: 32px 12px !important; }
#template_container { background-color: #ffffff !important; border: 2px solid {$c['ink']} !important; border-radius: 28px !important; box-shadow: 6px 6px 0 {$c['ink']} !important; overflow: hidden; }
#template_header_image { padding: 8px 0 18px !important; }
#template_header_image img, #template_header_image p img { max-width: 200px !important; height: auto !important; }
#template_header { background-color: {$c['honey']} !important; background-image: linear-gradient(115deg, {$c['hsoft']} 0%, #FFF5D6 48%, {$c['sky']} 100%) !important; border-bottom: 2px solid {$c['ink']} !important; border-radius: 26px 26px 0 0 !important; color: {$c['ink']} !important; }
#header_wrapper { padding: 30px 40px 26px !important; }
#template_header h1, #header_wrapper h1 { color: {$c['ink']} !important; font-family: {$font} !important; font-size: 28px !important; font-weight: 800 !important; line-height: 1.2 !important; text-align: center !important; text-shadow: none !important; margin: 0 !important; }
#body_content { background-color: #ffffff !important; }
#body_content_inner, #body_content_inner p, #body_content td, #body_content th { color: {$c['ink']} !important; font-family: {$font} !important; font-size: 15px !important; line-height: 1.6 !important; }
#body_content_inner { padding: 6px 0 !important; }
h2, h3 { color: {$c['ink']} !important; font-family: {$font} !important; font-weight: 800 !important; }
h2 { font-size: 19px !important; margin: 26px 0 12px !important; }
h2 a { color: {$c['ink']} !important; text-decoration: none !important; }
h3 { font-size: 16px !important; }
a, .link { color: {$c['leaf']} !important; font-weight: 700; }
.td, table.td { border-color: {$c['line']} !important; color: {$c['ink']} !important; }
table.td { border: 2px solid {$c['ink']} !important; border-radius: 18px !important; border-collapse: separate !important; border-spacing: 0 !important; overflow: hidden; }
table.td thead th { background-color: {$c['sky']} !important; border: 0 !important; font-weight: 800 !important; }
table.td tbody td, table.td tbody th { border: 0 !important; border-top: 2px dashed {$c['line']} !important; }
table.td tfoot th, table.td tfoot td { border: 0 !important; border-top: 2px dashed {$c['line']} !important; background-color: {$c['paper']} !important; }
table.td tfoot tr:last-child th, table.td tfoot tr:last-child td { background-color: {$c['hsoft']} !important; font-size: 17px !important; font-weight: 800 !important; }
.order_item td img { border: 2px solid {$c['ink']} !important; border-radius: 12px !important; }
.wc-item-meta, .wc-item-meta li, .wc-item-meta p { color: {$c['muted']} !important; font-size: 13px !important; }
address.address, .address { background-color: {$c['paper']} !important; border: 2px solid {$c['ink']} !important; border-radius: 18px !important; padding: 14px 16px !important; color: {$c['ink']} !important; font-style: normal !important; }
#addresses td { padding: 0 6px !important; }
#template_footer #credit, #template_footer #credit p, #credit { color: {$c['muted']} !important; font-family: {$font} !important; font-size: 13px !important; line-height: 1.6 !important; text-align: center !important; }
#template_footer #credit a, #credit a { color: {$c['ink']} !important; }
.bu-mail-steps td { font-family: {$font} !important; }
";
	return $css;
} );

/** Blok „Šta sledi” iznad tabele, samo u mejlovima kupcu kad je porudžbina primljena / u obradi. */
add_action( 'woocommerce_email_before_order_table', function ( $order, $sent_to_admin, $plain_text, $email ) {
	if ( $sent_to_admin || ! is_object( $email ) || ! in_array( $email->id, array( 'customer_processing_order', 'customer_on_hold_order' ), true ) ) {
		return;
	}
	if ( $plain_text ) {
		echo "\nŠta sledi:\n1. Porudžbina je primljena.\n2. Izrada (3D štampa): 3–5 radnih dana. Ako ima ime, proveravamo ga pre štampe.\n3. Šaljemo Post Express-om (1–2 radna dana), plaćate pouzećem.\n\n";
		return;
	}
	$c     = bumbaru_mail_colors();
	$koraci = array(
		array( '1', 'Primljeno', 'Porudžbina je stigla do nas.', $c['lsoft'] ),
		array( '2', 'Izrada', '3–5 radnih dana. Ime proveravamo pre štampe.', $c['hsoft'] ),
		array( '3', 'Slanje', 'Post Express 1–2 dana, plaćate pouzećem.', $c['sky'] ),
	);
	?>
	<table class="bu-mail-steps" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:4px 0 26px;border-collapse:separate;border-spacing:0;">
		<tr>
			<td colspan="3" style="padding:0 0 10px;font-size:17px;font-weight:800;color:<?php echo esc_attr( $c['ink'] ); ?>;">
				<img src="<?php echo esc_url( bumbaru_mail_img( 'brand/pcelica.png' ) ); ?>" alt="" width="28" height="28" style="vertical-align:middle;margin-right:6px;border:0;" />Šta sledi
			</td>
		</tr>
		<tr>
			<?php foreach ( $koraci as $i => $k ) : ?>
				<td valign="top" width="33%" style="padding:<?php echo 0 === $i ? '0 6px 0 0' : ( 2 === $i ? '0 0 0 6px' : '0 3px' ); ?>;">
					<div style="background-color:<?php echo esc_attr( $k[3] ); ?>;border:2px solid <?php echo esc_attr( $c['ink'] ); ?>;border-radius:16px;padding:12px 12px 14px;color:<?php echo esc_attr( $c['ink'] ); ?>;">
						<span style="display:inline-block;width:26px;height:26px;line-height:26px;border-radius:13px;background-color:#ffffff;border:2px solid <?php echo esc_attr( $c['ink'] ); ?>;text-align:center;font-weight:800;font-size:13px;"><?php echo esc_html( $k[0] ); ?></span>
						<div style="margin:8px 0 2px;font-weight:800;font-size:15px;"><?php echo esc_html( $k[1] ); ?></div>
						<div style="font-size:13px;line-height:1.45;color:<?php echo esc_attr( $c['muted'] ); ?>;"><?php echo esc_html( $k[2] ); ?></div>
					</div>
				</td>
			<?php endforeach; ?>
		</tr>
	</table>
	<?php
}, 5, 4 );
