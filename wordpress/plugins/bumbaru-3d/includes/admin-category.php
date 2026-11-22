<?php
/**
 * Kategorija proizvoda: naslov, pozadina, filteri, tekst i pitanja (kao na Astro sajtu).
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_cat_fields() {
	return array(
		'title'      => array( 'Naslov na strani (H1)', 'text', 'npr. Novogodišnji ukrasi i pokloni. Uvod ispod naslova je „Opis” kategorije.' ),
		'menu_label' => array( 'Naziv u meniju i podnožju', 'text', 'npr. Novogodišnji katalog. Prazno = naziv kategorije.' ),
		'focus_kw'   => array( 'Ključna reč', 'text', 'Reč koju ljudi kucaju u Google, npr. novogodišnji ukrasi. Upisuje se i u Yoast.' ),
		'seo_title'  => array( 'SEO naslov', 'text', 'Do 60 znakova sa „ | Bumbaru”.' ),
		'seo_desc'   => array( 'SEO opis', 'textarea', '120–158 znakova.' ),
		'bg'         => array( 'Pozadina na vrhu', 'text', 'Fajl iz teme (npr. bg/radionica.webp) ili pun link slike iz Medija.' ),
		'bg_pos'     => array( 'Položaj pozadine', 'text', 'npr. center ili right top' ),
		'filters'    => array( 'Filteri (oznake proizvoda)', 'text', 'Oznake odvojene zarezom, npr. ime,privesci,sto,jelka. Proizvodima dodelite iste oznake.' ),
		'about_h2'   => array( 'Tekst ispod liste: naslov', 'text', '' ),
		'about_p'    => array( 'Tekst ispod liste', 'textarea', 'Pasusi odvojeni praznim redom.' ),
		'faq'        => array( 'Česta pitanja', 'textarea', 'Jedno po redu: Pitanje | Odgovor.' ),
		'order'      => array( 'Redosled', 'number', 'Redosled u meniju, podnožju i katalogu.' ),
		'icon'       => array( 'Ikonica (SVG putanja)', 'text', 'Za stranu Katalog. Može ostati kako jeste.' ),
		'soon'       => array( 'Uskoro', 'checkbox', 'Kategorija još nema proizvode: prikazuje se „Uskoro”.' ),
	);
}

add_action( 'product_cat_edit_form_fields', function ( $term ) {
	foreach ( bumbaru_cat_fields() as $k => $f ) {
		$v = get_term_meta( $term->term_id, 'bu_' . $k, true );
		echo '<tr class="form-field"><th scope="row"><label for="bu_' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( 'textarea' === $f[1] ) {
			echo '<textarea id="bu_' . esc_attr( $k ) . '" name="bu_term[' . esc_attr( $k ) . ']" rows="4">' . esc_textarea( $v ) . '</textarea>';
		} elseif ( 'checkbox' === $f[1] ) {
			echo '<label><input type="checkbox" name="bu_term[' . esc_attr( $k ) . ']" value="1"' . checked( (bool) $v, true, false ) . ' /> da</label>';
		} else {
			echo '<input id="bu_' . esc_attr( $k ) . '" type="' . ( 'number' === $f[1] ? 'number' : 'text' ) . '" name="bu_term[' . esc_attr( $k ) . ']" value="' . esc_attr( $v ) . '" />';
		}
		if ( $f[2] ) {
			echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
		}
		echo '</td></tr>';
	}
	wp_nonce_field( 'bumbaru_term', 'bumbaru_term_nonce' );
} );

add_action( 'edited_product_cat', function ( $term_id ) {
	if ( ! isset( $_POST['bumbaru_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bumbaru_term_nonce'] ), 'bumbaru_term' ) ) {
		return;
	}
	$in = isset( $_POST['bu_term'] ) ? (array) wp_unslash( $_POST['bu_term'] ) : array(); // phpcs:ignore
	foreach ( bumbaru_cat_fields() as $k => $f ) {
		if ( 'checkbox' === $f[1] ) {
			$v = ! empty( $in[ $k ] ) ? '1' : '';
		} elseif ( 'textarea' === $f[1] ) {
			$v = isset( $in[ $k ] ) ? wp_kses_post( $in[ $k ] ) : '';
		} elseif ( 'number' === $f[1] ) {
			$v = isset( $in[ $k ] ) ? (int) $in[ $k ] : 0;
		} else {
			$v = isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : '';
		}
		update_term_meta( $term_id, 'bu_' . $k, $v );
	}
} );
