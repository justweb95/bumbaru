<?php
/**
 * Utisci kupaca na strani proizvoda: ocena 1–5, komentar, do 2 fotografije i odgovori na utiske.
 * Čuvaju se kao WooCommerce recenzije (komentar tipa „review” sa ocenom „rating”), pa se odobravaju u
 * Proizvodi → Recenzije. Na sajtu i u podacima za Google (aggregateRating, review) su samo odobreni utisci.
 */

defined( 'ABSPATH' ) || exit;

const BUMBARU_RV_SLIKE = 2;          // najviše fotografija po utisku
const BUMBARU_RV_MB    = 8;          // najveća fotografija (MB)

/** Odobreni utisci proizvoda (bez odgovora), najnoviji prvi. */
function bumbaru_utisci( $product_id ) {
	static $cache = array();
	if ( ! isset( $cache[ $product_id ] ) ) {
		$cache[ $product_id ] = get_comments( array(
			'post_id' => $product_id,
			'status'  => 'approve',
			'type'    => 'review',
			'parent'  => 0,
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		) );
	}
	return $cache[ $product_id ];
}

/** Odobreni odgovori na utiske, po utisku: [ parent_id => [ komentari ] ]. */
function bumbaru_utisci_odgovori( $product_id ) {
	$out = array();
	$all = get_comments( array(
		'post_id'     => $product_id,
		'status'      => 'approve',
		'parent__not_in' => array( 0 ),
		'orderby'     => 'comment_date_gmt',
		'order'       => 'ASC',
	) );
	foreach ( $all as $c ) {
		$out[ (int) $c->comment_parent ][] = $c;
	}
	return $out;
}

/** Prosečna ocena i broj ocena: [ prosek, broj, [5 => n, 4 => n, …] ]. */
function bumbaru_utisci_ocena( $product_id ) {
	$sum = 0;
	$n   = 0;
	$po  = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
	foreach ( bumbaru_utisci( $product_id ) as $c ) {
		$r = (int) get_comment_meta( $c->comment_ID, 'rating', true );
		if ( $r >= 1 && $r <= 5 ) {
			$sum += $r;
			$n++;
			$po[ $r ]++;
		}
	}
	return array( $n ? round( $sum / $n, 1 ) : 0, $n, $po );
}

/** Podaci za Google (Product): aggregateRating i do 5 najnovijih utisaka. Bez odobrenih utisaka: prazno. */
function bumbaru_utisci_ld( $product_id ) {
	list( $avg, $n ) = bumbaru_utisci_ocena( $product_id );
	if ( ! $n ) {
		return array();
	}
	$ld = array(
		'aggregateRating' => array(
			'@type'       => 'AggregateRating',
			'ratingValue' => (string) $avg,
			'reviewCount' => $n,
			'bestRating'  => '5',
			'worstRating' => '1',
		),
		'review'          => array(),
	);
	foreach ( array_slice( bumbaru_utisci( $product_id ), 0, 5 ) as $c ) {
		$r = (int) get_comment_meta( $c->comment_ID, 'rating', true );
		if ( $r < 1 ) {
			continue;
		}
		$ld['review'][] = array(
			'@type'         => 'Review',
			'author'        => array( '@type' => 'Person', 'name' => $c->comment_author ),
			'datePublished' => mysql2date( 'Y-m-d', $c->comment_date ),
			'reviewBody'    => wp_strip_all_tags( $c->comment_content ),
			'reviewRating'  => array( '@type' => 'Rating', 'ratingValue' => (string) $r, 'bestRating' => '5', 'worstRating' => '1' ),
		);
	}
	return $ld;
}

/** Zvezdice (prikaz): puna, pola, prazna. */
function bumbaru_zvezdice( $ocena, $size = 18 ) {
	$o = '<span class="rv-stars" role="img" aria-label="' . esc_attr( 'Ocena ' . str_replace( '.', ',', (string) $ocena ) . ' od 5' ) . '" style="--s:' . (int) $size . 'px">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$f  = max( 0, min( 1, $ocena - $i + 1 ) );
		$o .= '<i style="--f:' . ( $f >= 0.75 ? 100 : ( $f >= 0.25 ? 50 : 0 ) ) . '%"></i>';
	}
	return $o . '</span>';
}

/** Odgovor prodavnice (komentar napisao administrator / menadžer prodavnice). */
function bumbaru_utisak_od_nas( $c ) {
	return $c->user_id && user_can( (int) $c->user_id, 'manage_woocommerce' );
}

function bumbaru_utisak_html( $c, $odgovori ) {
	$r     = (int) get_comment_meta( $c->comment_ID, 'rating', true );
	$slike = array_filter( array_map( 'intval', (array) get_comment_meta( $c->comment_ID, 'bu_slike', true ) ) );
	$kupac = get_comment_meta( $c->comment_ID, 'verified', true );
	$o     = '<article class="rv-item" id="utisak-' . (int) $c->comment_ID . '">'
		. '<header class="rv-head"><span class="rv-av" aria-hidden="true">' . esc_html( mb_strtoupper( mb_substr( $c->comment_author, 0, 1 ) ) ) . '</span>'
		. '<div><strong>' . esc_html( $c->comment_author ) . '</strong>' . ( $kupac ? '<span class="rv-badge">Kupac</span>' : '' )
		. '<small>' . esc_html( mysql2date( 'j. n. Y.', $c->comment_date ) ) . '</small></div>'
		. ( $r ? bumbaru_zvezdice( $r, 16 ) : '' ) . '</header>'
		. '<div class="rv-text">' . wpautop( esc_html( $c->comment_content ) ) . '</div>';
	if ( $slike ) {
		$o .= '<div class="rv-pics">';
		foreach ( $slike as $id ) {
			$sm = wp_get_attachment_image_src( $id, 'medium' );
			$lg = wp_get_attachment_image_src( $id, 'large' );
			if ( $sm && $lg ) {
				$o .= '<button type="button" class="rv-pic" data-rv-full="' . esc_url( $lg[0] ) . '" aria-label="Uvećaj fotografiju kupca"><img src="' . esc_url( $sm[0] ) . '" alt="' . esc_attr( 'Fotografija kupca: ' . $c->comment_author ) . '" width="' . (int) $sm[1] . '" height="' . (int) $sm[2] . '" loading="lazy" decoding="async" /></button>';
			}
		}
		$o .= '</div>';
	}
	if ( ! empty( $odgovori[ $c->comment_ID ] ) ) {
		$o .= '<div class="rv-replies">';
		foreach ( $odgovori[ $c->comment_ID ] as $a ) {
			$nas = bumbaru_utisak_od_nas( $a );
			$o  .= '<div class="rv-reply' . ( $nas ? ' rv-nas' : '' ) . '"><strong>' . esc_html( $nas ? 'Bumbaru' : $a->comment_author ) . '</strong>'
				. ( $nas ? '<span class="rv-badge">Odgovor prodavnice</span>' : '' ) . '<small>' . esc_html( mysql2date( 'j. n. Y.', $a->comment_date ) ) . '</small>'
				. wpautop( esc_html( $a->comment_content ) ) . '</div>';
		}
		$o .= '</div>';
	}
	$o .= '<button type="button" class="rv-odg" data-rv-reply="' . (int) $c->comment_ID . '" data-rv-name="' . esc_attr( $c->comment_author ) . '">Odgovori</button>';
	return $o . '</article>';
}

/** Sekcija „Utisci kupaca” na strani proizvoda. */
function bumbaru_utisci_html( $product ) {
	$pid                 = $product->get_id();
	list( $avg, $n, $po ) = bumbaru_utisci_ocena( $pid );
	$lista               = bumbaru_utisci( $pid );
	$odg                 = bumbaru_utisci_odgovori( $pid );
	$o                   = '<section class="section rv" id="utisci" aria-labelledby="rv-title" data-rv="' . (int) $pid . '" data-rv-url="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">'
		. '<div class="rv-grid"><div class="rv-side reveal">'
		. '<h2 id="rv-title" class="h1">Utisci kupaca</h2>';
	if ( $n ) {
		$o .= '<div class="rv-sum"><b class="rv-avg">' . esc_html( str_replace( '.', ',', number_format( $avg, 1 ) ) ) . '</b><div>' . bumbaru_zvezdice( $avg, 22 )
			. '<p class="small muted">' . esc_html( $n . ' ' . ( 1 === $n % 10 && 11 !== $n % 100 ? 'ocena' : ( in_array( $n % 10, array( 2, 3, 4 ), true ) && ! in_array( $n % 100, array( 12, 13, 14 ), true ) ? 'ocene' : 'ocena' ) ) ) . '</p></div></div>'
			. '<ul class="rv-bars">';
		foreach ( $po as $z => $k ) {
			$o .= '<li><span>' . (int) $z . '</span><i style="--w:' . ( $n ? round( $k / $n * 100 ) : 0 ) . '%"></i><small>' . (int) $k . '</small></li>';
		}
		$o .= '</ul>';
	} else {
		$o .= '<p class="muted">Još nema utisaka za ovaj proizvod. Kupili ste ga? Javite nam kako vam se dopao, sa fotografijom ako želite.</p>';
	}
	$o .= '<button type="button" class="btn btn-primary" data-rv-new>' . ( function_exists( 'bu_icon' ) ? bu_icon( 'pen', 18 ) : '' ) . 'Napišite utisak</button></div>';

	// Lista utisaka: prvih 5, ostali na „Prikaži još”.
	$o .= '<div class="rv-list reveal" style="--d:1">';
	foreach ( $lista as $i => $c ) {
		$h = bumbaru_utisak_html( $c, $odg );
		$o .= $i >= 5 ? str_replace( '<article class="rv-item"', '<article class="rv-item" hidden data-rv-more', $h ) : $h;
	}
	if ( count( $lista ) > 5 ) {
		$o .= '<button type="button" class="btn btn-paper" data-rv-all>Prikaži sve utiske (' . count( $lista ) . ')</button>';
	}

	// Obrazac (za novi utisak i za odgovor).
	$o .= '<form class="rv-form" data-rv-form hidden novalidate>'
		. '<h3 class="h3" data-rv-form-title>Vaš utisak</h3>'
		. '<p class="small muted" data-rv-reply-to hidden></p>'
		. '<input type="hidden" name="parent" value="0" />'
		. '<fieldset class="rv-rate" data-rv-rate><legend class="flabel">Ocena</legend><div class="rv-rate-in">';
	for ( $z = 5; $z >= 1; $z-- ) {
		$o .= '<input type="radio" id="rv-z' . $z . '" name="ocena" value="' . $z . '" /><label for="rv-z' . $z . '" title="' . $z . ' od 5"><span class="sr-only">' . $z . ' od 5</span></label>';
	}
	$o .= '</div></fieldset>'
		. '<div class="field"><label for="rv-ime">Ime</label><input id="rv-ime" class="input" name="ime" maxlength="40" autocomplete="given-name" required placeholder="npr. Jelena iz Novog Sada" /></div>'
		. '<div class="field" data-rv-mail><label for="rv-mail">Imejl <small class="muted">(ne prikazuje se; po njemu znamo da ste kupac)</small></label><input id="rv-mail" class="input" type="email" name="email" maxlength="100" autocomplete="email" /></div>'
		. '<div class="field"><label for="rv-tekst" data-rv-tlabel>Komentar</label><textarea id="rv-tekst" class="input" name="tekst" rows="4" maxlength="1500" required></textarea></div>'
		. '<div class="field" data-rv-pics-field><p class="flabel">Fotografije <small class="muted">(do ' . BUMBARU_RV_SLIKE . ', nije obavezno)</small></p>'
		. '<label class="btn btn-paper rv-file">' . ( function_exists( 'bu_icon' ) ? bu_icon( 'plus', 18 ) : '' ) . 'Dodaj fotografiju<input type="file" name="slike[]" accept="image/jpeg,image/png,image/webp" multiple data-rv-files /></label>'
		. '<div class="rv-prev" data-rv-prev></div></div>'
		. '<p class="rv-hp" aria-hidden="true"><label>Sajt <input name="web" tabindex="-1" autocomplete="off" /></label></p>'
		. '<p class="small" data-rv-msg aria-live="polite"></p>'
		. '<div class="rv-actions"><button class="btn btn-primary" type="submit">Pošalji</button><button class="btn btn-paper" type="button" data-rv-cancel>Odustani</button></div>'
		. '<p class="small muted">Utiske pregledamo pre objavljivanja, obično istog dana.</p>'
		. '</form></div></div>'
		. '<dialog class="rv-dlg" data-rv-dlg><form method="dialog"><button class="icon-btn" aria-label="Zatvori">×</button></form><img alt="Fotografija kupca" data-rv-dlg-img /></dialog>'
		. '</section>';
	return $o;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_script( 'bumbaru-utisci', BUMBARU3D_URL . 'assets/utisci.js', array(), BUMBARU3D_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
} );

/** Prijem utiska ili odgovora (AJAX, i bez naloga). Uvek čeka odobrenje, osim kad piše prodavnica. */
function bumbaru_utisak_prijem() {
	$err = function ( $m ) {
		wp_send_json( array( 'ok' => false, 'msg' => $m ) );
	};
	if ( ! empty( $_POST['web'] ) || empty( $_POST['t'] ) || 'bumbaru' !== $_POST['t'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- strane su keširane; zaštita: skriveno polje + JS oznaka + ograničenje po IP
		$err( 'Slanje nije uspelo. Osvežite stranu i pokušajte ponovo.' );
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$lim = 'bu_rv_' . md5( $ip );
	if ( get_transient( $lim ) ) {
		$err( 'Upravo ste poslali utisak. Sačekajte minut pa pokušajte ponovo.' );
	}
	$pid    = isset( $_POST['pid'] ) ? absint( $_POST['pid'] ) : 0; // phpcs:ignore
	$parent = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0; // phpcs:ignore
	$post   = get_post( $pid );
	if ( ! $post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
		$err( 'Proizvod nije pronađen.' );
	}
	if ( $parent ) {
		$p = get_comment( $parent );
		if ( ! $p || (int) $p->comment_post_ID !== $pid || '1' !== (string) $p->comment_approved ) {
			$err( 'Utisak na koji odgovarate nije pronađen.' );
		}
	}
	$ime   = trim( sanitize_text_field( wp_unslash( $_POST['ime'] ?? '' ) ) ); // phpcs:ignore
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ); // phpcs:ignore
	$tekst = trim( sanitize_textarea_field( wp_unslash( $_POST['tekst'] ?? '' ) ) ); // phpcs:ignore
	$ocena = absint( $_POST['ocena'] ?? 0 ); // phpcs:ignore
	$user  = wp_get_current_user();
	$nas   = $user->ID && user_can( $user, 'manage_woocommerce' );
	if ( $nas && '' === $ime ) {
		$ime = 'Bumbaru';
	}
	if ( mb_strlen( $ime ) < 2 ) {
		$err( 'Upišite ime.' );
	}
	if ( mb_strlen( $tekst ) < ( $parent ? 2 : 10 ) ) {
		$err( $parent ? 'Upišite odgovor.' : 'Napišite bar rečenicu o proizvodu.' );
	}
	if ( ! $parent && ( $ocena < 1 || $ocena > 5 ) ) {
		$err( 'Izaberite ocenu (zvezdice).' );
	}
	$ime   = mb_substr( $ime, 0, 40 );
	$tekst = mb_substr( $tekst, 0, 1500 );

	// Fotografije: samo uz utisak (ne uz odgovor), najviše 2.
	$files = array();
	if ( ! $parent && ! empty( $_FILES['slike']['name'] ) && is_array( $_FILES['slike']['name'] ) ) {
		foreach ( $_FILES['slike']['name'] as $i => $name ) { // phpcs:ignore
			if ( '' === $name || UPLOAD_ERR_NO_FILE === (int) $_FILES['slike']['error'][ $i ] ) { // phpcs:ignore
				continue;
			}
			$f = array(
				'name'     => sanitize_file_name( $name ),
				'type'     => $_FILES['slike']['type'][ $i ], // phpcs:ignore
				'tmp_name' => $_FILES['slike']['tmp_name'][ $i ], // phpcs:ignore
				'error'    => (int) $_FILES['slike']['error'][ $i ], // phpcs:ignore
				'size'     => (int) $_FILES['slike']['size'][ $i ], // phpcs:ignore
			);
			if ( $f['error'] ) {
				$err( 'Fotografija „' . $f['name'] . '” nije stigla. Pokušajte manju.' );
			}
			if ( $f['size'] > BUMBARU_RV_MB * 1024 * 1024 ) {
				$err( 'Fotografija je veća od ' . BUMBARU_RV_MB . ' MB.' );
			}
			$chk  = wp_check_filetype_and_ext( $f['tmp_name'], $f['name'], array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) );
			$info = @getimagesize( $f['tmp_name'] ); // phpcs:ignore
			if ( empty( $chk['type'] ) || ! $info ) {
				$err( 'Dozvoljene su samo fotografije (JPG, PNG, WebP).' );
			}
			$files[] = $f;
		}
		if ( count( $files ) > BUMBARU_RV_SLIKE ) {
			$err( 'Najviše ' . BUMBARU_RV_SLIKE . ' fotografije.' );
		}
	}

	$kupac = $email && function_exists( 'wc_customer_bought_product' ) && wc_customer_bought_product( $email, $user->ID, $pid );
	$meta  = array();
	if ( ! $parent ) {
		$meta['rating']   = $ocena;
		$meta['verified'] = $kupac ? 1 : 0;
	}
	$cid = wp_insert_comment( array(
		'comment_post_ID'      => $pid,
		'comment_parent'       => $parent,
		'comment_author'       => $ime,
		'comment_author_email' => $email,
		'comment_author_IP'    => $ip,
		'comment_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 250 ) : '',
		'comment_content'      => $tekst,
		'comment_type'         => 'review',
		'comment_approved'     => $nas ? 1 : 0,
		'user_id'              => $user->ID,
		'comment_meta'         => $meta,
	) );
	if ( ! $cid ) {
		$err( 'Slanje nije uspelo. Pokušajte ponovo.' );
	}
	set_transient( $lim, 1, MINUTE_IN_SECONDS );

	if ( $files ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$ids = array();
		foreach ( $files as $k => $f ) {
			$_FILES[ 'bu_rv_' . $k ] = $f;
			$id = media_handle_upload( 'bu_rv_' . $k, $pid, array( 'post_title' => 'Utisak kupca: ' . $ime ), array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) ) );
			if ( ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_wp_attachment_image_alt', 'Fotografija kupca: ' . $ime );
				update_post_meta( $id, '_bu_utisak', $cid );
				$ids[] = (int) $id;
			}
		}
		update_comment_meta( $cid, 'bu_slike', $ids );
	}

	if ( ! $nas ) {
		$naslov = ( $parent ? 'Novi odgovor na utisak' : 'Novi utisak (' . $ocena . '/5)' ) . ': ' . get_the_title( $pid );
		wp_mail(
			get_option( 'admin_email' ),
			$naslov,
			$ime . ( $kupac ? ' (kupac)' : '' ) . ":\n\n" . $tekst . "\n\n" . ( $files ? count( $files ) . " fotografija\n\n" : '' )
			. 'Odobrite ili obrišite: ' . admin_url( 'edit.php?post_type=product&page=product-reviews&comment_status=moderated' )
		);
	} else {
		bumbaru_utisci_purge( $pid );
	}
	wp_send_json( array(
		'ok'  => true,
		'msg' => $nas ? 'Objavljeno.' : ( $parent ? 'Hvala! Odgovor će se pojaviti kad ga pregledamo.' : 'Hvala na utisku! Pojaviće se na strani kad ga pregledamo.' ),
	) );
}
add_action( 'wp_ajax_bumbaru_utisak', 'bumbaru_utisak_prijem' );
add_action( 'wp_ajax_nopriv_bumbaru_utisak', 'bumbaru_utisak_prijem' );

/** Strana proizvoda se osveži (LiteSpeed) kad se utisak odobri, skloni ili obriše. */
function bumbaru_utisci_purge( $pid ) {
	do_action( 'litespeed_purge_post', (int) $pid );
}
add_action( 'transition_comment_status', function ( $new, $old, $c ) {
	if ( 'product' === get_post_type( $c->comment_post_ID ) ) {
		bumbaru_utisci_purge( $c->comment_post_ID );
	}
}, 10, 3 );

/** Brisanje utiska briše i njegove fotografije. */
add_action( 'delete_comment', function ( $cid ) {
	foreach ( (array) get_comment_meta( $cid, 'bu_slike', true ) as $id ) {
		if ( $id && (int) get_post_meta( (int) $id, '_bu_utisak', true ) === (int) $cid ) {
			wp_delete_attachment( (int) $id, true );
		}
	}
} );

/** Admin (Proizvodi → Recenzije, Komentari): fotografije uz tekst utiska i oznaka kupca. */
add_filter( 'comment_text', function ( $text, $c = null ) {
	if ( ! is_admin() || ! $c || 'review' !== $c->comment_type ) {
		return $text;
	}
	$ids = array_filter( array_map( 'intval', (array) get_comment_meta( $c->comment_ID, 'bu_slike', true ) ) );
	if ( $ids ) {
		$text .= '<p style="display:flex;gap:6px">';
		foreach ( $ids as $id ) {
			$sm = wp_get_attachment_image_src( $id, 'thumbnail' );
			$lg = wp_get_attachment_image_src( $id, 'large' );
			if ( $sm ) {
				$text .= '<a href="' . esc_url( $lg ? $lg[0] : $sm[0] ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $sm[0] ) . '" width="72" height="72" style="object-fit:cover;border-radius:6px" alt="" /></a>';
			}
		}
		$text .= '</p>';
	}
	if ( get_comment_meta( $c->comment_ID, 'verified', true ) ) {
		$text .= '<p><em>Kupac (imejl iz porudžbine)</em></p>';
	}
	return $text;
}, 20, 2 );
