<?php
/**
 * Slavske i božićne modlice: mere po veličinama, izbor boje i tekstovi (2.5.5).
 *
 * Veličine: velika = 100%, srednja = 80%, mala = 65% istog modela. Mere su izmerene na gotovim modlicama
 * (mala ili srednja), a ostale veličine izračunate iz njih; sve su „oko”, jer mogu da odstupe par milimetara.
 * Na izboru veličine piše samo Velika / Srednja / Mala, a mere su u kartici „Veličine” ispod.
 * Boja: „Svejedno” (prvo, obeleženo), Crvena ili Bela; drugu boju kupac napiše u napomeni.
 */

defined( 'ABSPATH' ) || exit;

/** Mere u cm po veličini (velika, srednja, mala), širina × visina kao na fotografiji. */
function bumbaru_modlice_mere() {
	return array(
		'slavska-modlica-krst'               => array( '6,8 × 8,1', '5,5 × 6,5', '4,4 × 5,2' ),
		'slavska-modlica-golub-u-letu'       => array( '6,5 × 5,4', '5,2 × 4,3', '4,2 × 3,5' ),
		'slavska-modlica-golub-sa-grancicom' => array( '5,8 × 5,4', '4,6 × 4,3', '3,8 × 3,5' ),
		'slavska-modlica-srecna-slava'       => array( '7,2 × 5,4', '5,8 × 4,3', '4,7 × 3,5' ),
		'slavski-pecat-sa-krstom'            => array( '6,3 × 6,3', '5 × 5', '4,1 × 4,1' ),
		'slavski-pecat-okrugli'              => array( 'Ø 6,3', 'Ø 5', 'Ø 4,1' ),
		'slavska-modlica-knjiga'             => array( '4,9 × 4,2', '3,9 × 3,3', '3,2 × 2,7' ),
		'slavska-modlica-kukuruz'            => array( '5,2 × 8,3', '4 × 6,5', '3,5 × 5,5' ),
		'slavska-modlica-zito'               => array( '8 × 5,8', '6,4 × 4,6', '5,2 × 3,8' ),
		'slavska-modlica-cvet'               => array( '5,4 × 5,4', '4,3 × 4,3', '3,5 × 3,5' ),
		'slavska-modlica-grozdje-sa-listom'  => array( '5,4 × 6,9', '4,3 × 5,5', '3,5 × 4,5' ),
		'slavska-modlica-bure'               => array( '5,7 × 6,9', '4,6 × 5,5', '3,7 × 4,5' ),
		'slavska-modlica-pletenica'          => array( '10,6 × 2,6', '8,5 × 2,1', '6,9 × 1,7' ),
		'bozicna-modlica-vitlejemska-zvezda' => array( '6,4 × 6,4', '5,1 × 5,1', '4,2 × 4,2' ),
		'bozicna-modlica-badnjak'            => array( '4,5 × 8,2', '3,6 × 6,6', '2,9 × 5,4' ),
		'bozicna-modlica-hristos-se-rodi'    => array( '8,1 × 5,6', '6,5 × 4,5', '5,3 × 3,7' ),
		// Medaljoni: velika Ø 9,5, srednja Ø 7,5 (mala Ø 6,2 se ne prodaje posebno).
		'modlica-sveti-nikola'               => array( 'Ø 9,5', 'Ø 7,5' ),
		'modlica-sveti-georgije'             => array( 'Ø 9,5', 'Ø 7,5' ),
		'modlica-arhangel-mihailo'           => array( 'Ø 9,5', 'Ø 7,5' ),
		'modlica-sveti-jovan'                => array( 'Ø 9,5', 'Ø 7,5' ),
		'modlica-sveti-dimitrije'            => array( 'Ø 9,5', 'Ø 7,5' ),
		'modlica-sveti-sava'                 => array( 'Ø 9,5', 'Ø 7,5' ),
		'bozicna-modlica-rodjenje-hristovo'  => array( 'Ø 9,5', 'Ø 7,5' ),
	);
}

/** Setovi i „Sastavite svoj set” (mere svake modlice su na njenoj strani). */
function bumbaru_modlice_setovi() {
	return array(
		'slavske-modlice-sastavite-svoj-set',
		'slavski-set-modlica-sveti-nikola',
		'slavski-set-modlica-sveti-georgije',
		'slavski-set-modlica-arhangel-mihailo',
		'slavski-set-modlica-sveti-jovan',
		'slavski-set-modlica-sveti-dimitrije',
		'slavski-set-modlica-sveti-sava',
	);
}

/** Crna ploča i zlatni reljef: bez izbora crvena/bela. */
function bumbaru_modlice_crno_zlatne() {
	return array( 'bozicna-modlica-vitlejemska-zvezda', 'bozicna-modlica-rodjenje-hristovo' );
}

const BUMBARU_MODLICE_ODSTUPA = 'Mere su okvirne i mogu da odstupe par milimetara.';
const BUMBARU_MODLICE_BOJA    = 'Boja = Svejedno | Crvena | Bela';

/** „Ø 9,5 cm” → „oko Ø 9,5 cm”, „Ø 7,6 cm” → „oko Ø 7,5 cm” (samo jednom). */
function bumbaru_modlice_oko( $val ) {
	return preg_replace( array( '/(?<!oko )Ø 9,5 cm/u', '/(?<!oko )Ø 7,6 cm/u' ), array( 'oko Ø 9,5 cm', 'oko Ø 7,5 cm' ), $val );
}

/**
 * Izmene za jedan proizvod: $p = array( 'options', 'specs' (linije „Naziv | vrednost”), 'about' ).
 * Vraća isti niz sa izmenama. Ne dira ono što ne prepozna (npr. tekst koji ste sami prepravili).
 */
function bumbaru_modlice_izmeni( $slug, $p ) {
	$mere  = bumbaru_modlice_mere();
	$set   = in_array( $slug, bumbaru_modlice_setovi(), true );
	$cz    = in_array( $slug, bumbaru_modlice_crno_zlatne(), true );
	if ( ! isset( $mere[ $slug ] ) && ! $set ) {
		return $p;
	}

	// 1) Izbor veličine bez mera: „Velika, 6,7 × 8 cm” → „Velika”, „Mala, uz srednji medaljon Ø 7,6 cm” → „Mala, uz srednji medaljon”.
	$lines = preg_split( '/\r\n|\r|\n/', (string) $p['options'] );
	$ima_b = false;
	foreach ( $lines as $k => $line ) {
		if ( preg_match( '/^\s*boj[ae]\s*=/iu', $line ) ) {
			$ima_b = true;
		}
		if ( ! preg_match( '/^\s*veli[cč]ina\s*=/iu', $line ) ) {
			continue;
		}
		$line = preg_replace( '/,\s*medaljon\s+Ø\s*[\d.,]+\s*cm/u', '', $line );
		$line = preg_replace( '/,?\s*Ø\s*[\d.,]+\s*cm/u', '', $line );
		$line = preg_replace( '/,\s*[\d.,]+\s*×\s*[\d.,]+\s*cm/u', '', $line );
		$lines[ $k ] = $line;
	}
	if ( ! $ima_b && ! $cz ) {
		$lines[] = BUMBARU_MODLICE_BOJA;
	}
	$p['options'] = implode( "\n", array_filter( $lines, 'strlen' ) );

	// 2) Kartica „Veličine” (i „Medaljon” u setovima) sa merama, i „Boje”.
	$specs = preg_split( '/\r\n|\r|\n/', (string) $p['specs'] );
	foreach ( $specs as $k => $row ) {
		$parts = array_map( 'trim', explode( '|', $row, 2 ) );
		if ( 2 !== count( $parts ) ) {
			continue;
		}
		list( $key, $val ) = $parts;
		if ( 'Veličine' === $key ) {
			if ( isset( $mere[ $slug ] ) ) {
				$m   = $mere[ $slug ];
				$val = 'velika oko ' . $m[0] . ' cm, srednja oko ' . $m[1] . ' cm' . ( isset( $m[2] ) ? ', mala oko ' . $m[2] . ' cm' : '' ) . '. ' . BUMBARU_MODLICE_ODSTUPA;
			} elseif ( false === strpos( $val, 'odstupe' ) ) {
				$val = bumbaru_modlice_oko( $val );
				$val = rtrim( $val, '. ' ) . '. Mere svake modlice su na njenoj strani i mogu da odstupe par milimetara.';
			}
		} elseif ( 'Medaljon' === $key && $set ) {
			$val = bumbaru_modlice_oko( $val );
		} elseif ( 'Boje' === $key ) {
			if ( $cz ) {
				if ( false === strpos( $val, 'razlikuj' ) ) {
					$val = 'crna ploča, zlatni reljef; boja može malo da se razlikuje od fotografija, a drugu boju napišite u napomeni';
				}
			} elseif ( 0 === strpos( $val, 'crvena ili bela' ) ) {
				$val = 'crvena ili bela, izaberite gore (ako vam je svejedno, ostavite „Svejedno”); boja može da se razlikuje od fotografija, a drugu boju napišite u napomeni';
			}
		}
		$specs[ $k ] = $key . ' | ' . $val;
	}
	$p['specs'] = implode( "\n", $specs );

	// 3) Opis: mere više nisu pored izbora; boja se bira.
	$p['about'] = str_replace(
		array(
			'Mere svake veličine su pored izbora.',
			'velikoj (Ø 9,5 cm)',
			'srednjoj (Ø 7,6 cm)',
			'veliki medaljon (Ø 9,5 cm), a u malom srednji (Ø 7,6 cm)',
			'Ostale modlice su oko 6–8 cm u velikom, 5–6,5 cm u srednjem i 4–5 cm u malom setu.',
			'u boji kao na fotografijama (crvena ili bela). Ako želite drugu boju, napišite je u napomeni uz porudžbinu.',
		),
		array(
			'Okvirne mere svake veličine su ispod, u kartici „Veličine”.',
			'velikoj (oko Ø 9,5 cm)',
			'srednjoj (oko Ø 7,5 cm)',
			'veliki medaljon (oko Ø 9,5 cm), a u malom srednji (oko Ø 7,5 cm)',
			'Ostale modlice su oko 5–8 cm u velikom, 4–6,5 cm u srednjem i 3–5,5 cm u malom setu (pletenica je duža).',
			'crvenu ili belu. Boju birate gore („Svejedno” ako vam nije važna), a može da se razlikuje od one na fotografijama. Ako želite neku drugu boju, napišite je u napomeni uz porudžbinu.',
		),
		(string) $p['about']
	);
	return $p;
}

/** Jednom posle nadogradnje: izmeni postojeće proizvode na sajtu. */
function bumbaru_migrate_modlice() {
	if ( get_option( 'bumbaru_migrate_modlice' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$slugs = array_merge( array_keys( bumbaru_modlice_mere() ), bumbaru_modlice_setovi() );
	foreach ( $slugs as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( ! $post ) {
			continue;
		}
		$cfg = get_post_meta( $post->ID, '_bu_config', true );
		$cfg = is_array( $cfg ) ? $cfg : array();
		$in  = array(
			'options' => isset( $cfg['options'] ) && is_string( $cfg['options'] ) ? $cfg['options'] : '',
			'specs'   => (string) get_post_meta( $post->ID, '_bu_specs', true ),
			'about'   => (string) $post->post_content,
		);
		$out = bumbaru_modlice_izmeni( $slug, $in );
		if ( $out['options'] !== $in['options'] && isset( $cfg['options'] ) && is_string( $cfg['options'] ) ) {
			$cfg['options'] = $out['options'];
			update_post_meta( $post->ID, '_bu_config', $cfg );
		}
		if ( $out['specs'] !== $in['specs'] ) {
			update_post_meta( $post->ID, '_bu_specs', $out['specs'] );
		}
		if ( $out['about'] !== $in['about'] ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_content' => $out['about'] ) );
		}
	}
	wp_cache_flush();
	update_option( 'bumbaru_migrate_modlice', 1 );
}
