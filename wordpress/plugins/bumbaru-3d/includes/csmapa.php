<?php
/**
 * Counter-Strike 2 mapa u ramu (tip „csmapa”): crni A4 ram sa staklom, odštampana glossy podloga i 3D štampana mapa.
 * Kupac bira mapu, model podloge (MVP | General Info), jezik na štampi (EN | SR), upisuje ime igrača (nick)
 * i, ako želi, link do Steam profila (sliku profila preuzimamo mi ili je kupac pošalje posle porudžbine; upload na sajtu ne postoji).
 *
 * Pregled podloge crta assets/csmapa.js (isti crtež i za stranu „Štampaj podlogu A4” u administraciji):
 * SVG A4 (210 × 297 mm), slike su u assets/cs/ (pravi ih scripts/cs-mape.mjs).
 */

defined( 'ABSPATH' ) || exit;

/** Mape: podaci za podlogu „General Info” (provereno: Wikipedia, Valve Developer wiki, Liquipedia; vidi izveštaj 2.6.5). */
function bumbaru_cs_mape() {
	return array(
		'dust2'   => array(
			'naziv'   => 'Dust II',
			'kod'     => 'de_dust2',
			'godina'  => '2001',
			'izdata'  => array( 'en' => 'Mar 13, 2001', 'sr' => '13. 3. 2001.' ),
			'verzija' => 'CS 1.1',
			'autor'   => 'Dave Johnston',
			'lok'     => array( 'en' => 'North Africa', 'sr' => 'Severna Afrika' ),
			'pod'     => array( 'en' => 'The desert classic', 'sr' => 'Pustinjski klasik' ),
			'levo'    => array( 'en' => array( 'Long A', 'Mid', 'B tunnels' ), 'sr' => array( 'Long A', 'Sredina', 'B tuneli' ) ),
			'fakt'    => array( 'en' => 'Two bombsites since 2001', 'sr' => 'Dva mesta za bombu od 2001.' ),
		),
		'mirage'  => array(
			'naziv'   => 'Mirage',
			'kod'     => 'de_mirage',
			'godina'  => '2013',
			'izdata'  => array( 'en' => 'Jun 12, 2013', 'sr' => '12. 6. 2013.' ),
			'verzija' => 'CS:GO',
			'autor'   => 'Michael „BubkeZ” Hüll',
			'lok'     => array( 'en' => 'North Africa', 'sr' => 'Severna Afrika' ),
			'pod'     => array( 'en' => 'Three lanes, one Mid', 'sr' => 'Tri puta, jedna sredina' ),
			'levo'    => array( 'en' => array( 'Palace', 'Mid', 'Apartments' ), 'sr' => array( 'Palata', 'Sredina', 'Apartmani' ) ),
			'fakt'    => array( 'en' => 'Played at every CS:GO Major', 'sr' => 'Igrana na svakom CS:GO Major-u' ),
		),
		'inferno' => array(
			'naziv'   => 'Inferno',
			'kod'     => 'de_inferno',
			'godina'  => '2001',
			'izdata'  => array( 'en' => '2001', 'sr' => '2001.' ),
			'verzija' => 'CS 1.1',
			'autor'   => 'Chris „Narby” Auty',
			'lok'     => array( 'en' => 'Italy', 'sr' => 'Italija' ),
			'pod'     => array( 'en' => 'Banana, Apartments, Arch', 'sr' => 'Banana, apartmani, luk' ),
			'levo'    => array( 'en' => array( 'Banana', 'Apartments', 'Arch' ), 'sr' => array( 'Banana', 'Apartmani', 'Luk' ) ),
			'fakt'    => array( 'en' => 'The most played map in Major history', 'sr' => 'Najigranija mapa u istoriji Major-a' ),
		),
		'ancient' => array(
			'naziv'   => 'Ancient',
			'kod'     => 'de_ancient',
			'godina'  => '2020',
			'izdata'  => array( 'en' => 'Dec 3, 2020', 'sr' => '3. 12. 2020.' ),
			'verzija' => 'CS:GO',
			'autor'   => 'Valve',
			'lok'     => array( 'en' => 'Central America', 'sr' => 'Centralna Amerika' ),
			'pod'     => array( 'en' => 'Temple in the jungle', 'sr' => 'Hram u džungli' ),
			'levo'    => array( 'en' => array( 'Jungle', 'Ruins', 'Temple' ), 'sr' => array( 'Džungla', 'Ruševine', 'Hram' ) ),
			'fakt'    => array( 'en' => 'Spiritual successor to Aztec', 'sr' => 'Duhovni naslednik Azteka' ),
		),
		'cache'   => array(
			'naziv'   => 'Cache',
			'kod'     => 'de_cache',
			'godina'  => '2014',
			'izdata'  => array( 'en' => 'Jul 2014', 'sr' => 'jul 2014.' ),
			'verzija' => 'CS:GO',
			'autor'   => 'Volcano i FMPONE',
			'lok'     => array( 'en' => 'Pripyat, Ukraine', 'sr' => 'Pripjat, Ukrajina' ),
			'pod'     => array( 'en' => 'In the shadow of Chernobyl', 'sr' => 'U senci Černobilja' ),
			'levo'    => array( 'en' => array( 'Squeaky', 'Mid', 'Checkers' ), 'sr' => array( 'Squeaky', 'Sredina', 'Checkers' ) ),
			'fakt'    => array( 'en' => 'The power plant looms over T spawn', 'sr' => 'Elektrana se vidi sa T spawna' ),
		),
		'nuke'    => array(
			'naziv'   => 'Nuke',
			'kod'     => 'de_nuke',
			'godina'  => '1999',
			'izdata'  => array( 'en' => 'Nov 5, 1999', 'sr' => '5. 11. 1999.' ),
			'verzija' => 'CS Beta 4.0',
			'autor'   => 'Jo Bieg',
			'lok'     => array( 'en' => 'Cedar Creek, USA', 'sr' => 'Cedar Creek, SAD' ),
			'pod'     => array( 'en' => 'Nuclear power plant', 'sr' => 'Nuklearna elektrana' ),
			'levo'    => array( 'en' => array( 'Outside', 'Ramp', 'Heaven' ), 'sr' => array( 'Napolju', 'Rampa', 'Heaven' ) ),
			'fakt'    => array( 'en' => 'Bombsite A sits right above B', 'sr' => 'Mesto A je tačno iznad mesta B' ),
		),
		'vertigo' => array(
			'naziv'   => 'Vertigo',
			'kod'     => 'de_vertigo',
			'godina'  => '2001',
			'izdata'  => array( 'en' => '2001', 'sr' => '2001.' ),
			'verzija' => 'CS 1.1',
			'autor'   => 'Chris „Narby” Auty',
			'lok'     => array( 'en' => 'Skyscraper', 'sr' => 'Neboder u izgradnji' ),
			'pod'     => array( 'en' => 'Fight on the top floors', 'sr' => 'Borba na vrhu nebodera' ),
			'levo'    => array( 'en' => array( 'Ramp', 'Mid', 'Elevators' ), 'sr' => array( 'Rampa', 'Sredina', 'Liftovi' ) ),
			'fakt'    => array( 'en' => 'One wrong step and you fall', 'sr' => 'Jedan pogrešan korak i padaš' ),
		),
	);
}

/** Modeli podloge i jezici. */
function bumbaru_cs_modeli() {
	return array( 'mvp' => 'MVP', 'info' => 'General Info' );
}

function bumbaru_cs_jezici() {
	return array( 'en' => 'EN', 'sr' => 'SR' );
}

/** Tekstovi na podlozi (naziv mape i „COUNTER-STRIKE 2” se ne prevode). */
function bumbaru_cs_tekstovi() {
	return array(
		'en' => array(
			'mvp'     => 'MOST VALUABLE PLAYER',
			'posveta' => 'Exclusive collector’s piece, made especially for %s.',
			'za'      => 'Made especially for %s',
			'izdata'  => 'Released',
			'autor'   => 'Map author',
			'tip'     => 'Map type',
			'igraci'  => 'Players',
			'lok'     => 'Location',
			'tipv'    => 'Bomb Defusal',
			'igraciv' => '5 vs 5',
			'cs2'     => 'CS2 by Valve',
			'moto'    => array( 'Aim.', 'Trade.', 'Clutch.' ),
		),
		'sr' => array(
			'mvp'     => 'NAJKORISNIJI IGRAČ',
			'posveta' => 'Ekskluzivan kolekcionarski primerak specijalno izrađen za %s.',
			'za'      => 'Specijalno izrađeno za %s',
			'izdata'  => 'Izdata',
			'autor'   => 'Autor mape',
			'tip'     => 'Tip mape',
			'igraci'  => 'Igrači',
			'lok'     => 'Lokacija',
			'tipv'    => 'Deaktivacija bombe',
			'igraciv' => '5 na 5',
			'cs2'     => 'CS2: Valve',
			'moto'    => array( 'Nišan.', 'Tim.', 'Pobeda.' ),
		),
	);
}

/** Podešavanje iz _bu_config: koje mape kupac bira i koja je izabrana kad se strana otvori. */
function bumbaru_cs_parse( $raw ) {
	$raw  = is_array( $raw ) ? $raw : array();
	$sve  = array_keys( bumbaru_cs_mape() );
	$mape = isset( $raw['mape'] ) ? array_values( array_intersect( array_map( 'sanitize_key', (array) $raw['mape'] ), $sve ) ) : array();
	$mape = $mape ? $mape : $sve;
	$poc  = isset( $raw['pocetna'] ) && in_array( $raw['pocetna'], $mape, true ) ? $raw['pocetna'] : ( in_array( 'dust2', $mape, true ) ? 'dust2' : $mape[0] );
	return array( 'mape' => $mape, 'pocetna' => $poc );
}

/** URL slike iz assets/cs/ ili prazno ako fajl ne postoji (npr. 3D mapa koja još nije snimljena). */
function bumbaru_cs_img( $file ) {
	return file_exists( BUMBARU3D_DIR . 'assets/cs/' . $file ) ? BUMBARU3D_URL . 'assets/cs/' . $file . '?v=' . BUMBARU3D_VERSION : '';
}

/** Podaci za crtež podloge (csmapa.js): mape sa slikama, tekstovi, znak levo. */
function bumbaru_cs_data( $samo = null ) {
	$mape = array();
	// Stvarne mere 3D mape u mm (najduža strana 160 mm), iz assets/cs/mape.json koji pravi scripts/cs-mape.mjs.
	$f    = BUMBARU3D_DIR . 'assets/cs/mape.json';
	$mere = file_exists( $f ) ? json_decode( (string) file_get_contents( $f ), true ) : array();
	$mere = is_array( $mere ) ? $mere : array();
	foreach ( bumbaru_cs_mape() as $id => $m ) {
		if ( $samo && ! in_array( $id, $samo, true ) ) {
			continue;
		}
		$m['id']    = $id;
		$m['logo']  = bumbaru_cs_img( 'logo-' . $id . '.webp' );
		$m['siva']  = bumbaru_cs_img( 'logo-' . $id . '-siva.webp' );
		$m['mapa']  = bumbaru_cs_img( 'mapa-' . $id . '.webp' );
		$m['mm']    = isset( $mere[ $id ][0], $mere[ $id ][1] ) ? array( (float) $mere[ $id ][0], (float) $mere[ $id ][1] ) : array( 160, 160 );
		$mape[ $id ] = $m;
	}
	return array( 'mape' => $mape, 'tekst' => bumbaru_cs_tekstovi(), 'znak' => bumbaru_cs_img( 'cs-znak.webp' ) );
}

/** Kontakti za sliku profila (iz podešavanja sajta). */
function bumbaru_cs_kontakti() {
	$k   = array( 'Messenger-u' );
	$ig  = function_exists( 'bu_site' ) ? bu_site( 'instagram_url' ) : '';
	$wa  = function_exists( 'bu_site' ) ? bu_site( 'whatsapp' ) : '';
	if ( $ig ) {
		$k[] = 'Instagram-u';
	}
	if ( $wa ) {
		$k[] = 'WhatsApp-u (' . $wa . ')';
	}
	$k[] = 'mejlom na prodavnica@bumbaru.rs';
	return count( $k ) > 1 ? implode( ', ', array_slice( $k, 0, -1 ) ) . ' ili ' . end( $k ) : $k[0];
}

/** Ime igrača: slova (latinica, ćirilica), brojevi, razmak i znaci tipični za nick; najviše 16 znakova. */
function bumbaru_cs_ime( $v ) {
	$v = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $v ) ) );
	$v = preg_replace( '/[^\p{L}\p{N}\p{M} ._\-!?*#@$&+=~^|\[\](){}\':;,\/]/u', '', $v );
	return mb_substr( trim( (string) $v ), 0, 16, 'UTF-8' );
}

/** Link do Steam profila: steamcommunity.com/id/… ili /profiles/…; vraća očišćen https link, '' ili false (pogrešan). */
function bumbaru_cs_steam( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) {
		return '';
	}
	if ( ! preg_match( '#^(?:https?://)?(?:www\.)?steamcommunity\.com/(id|profiles)/([A-Za-z0-9_\-]{2,64})/?(?:[?\#].*)?$#i', $v, $m ) ) {
		return false;
	}
	return 'https://steamcommunity.com/' . strtolower( $m[1] ) . '/' . $m[2] . '/';
}

/** Izbor kupca → stavka (redovi za korpu i porudžbinu) ili WP_Error. */
function bumbaru_cs_choice( $product, $get ) {
	$c     = bumbaru_config( $product );
	$mape  = bumbaru_cs_mape();
	$mapa  = sanitize_key( (string) $get( 'mapa', $c['csmapa']['pocetna'] ) );
	if ( ! in_array( $mapa, $c['csmapa']['mape'], true ) ) {
		return new WP_Error( 'mapa', 'Izaberite mapu.' );
	}
	$model = 'info' === $get( 'model', 'mvp' ) ? 'info' : 'mvp';
	$jezik = 'sr' === $get( 'jezik', 'en' ) ? 'sr' : 'en';
	$ime   = bumbaru_cs_ime( $get( 'ime', '' ) );
	if ( 'mvp' === $model && '' === $ime ) {
		return new WP_Error( 'ime', 'Upišite ime igrača (nick) koje štampamo na podlozi.' );
	}
	$steam = 'mvp' === $model ? bumbaru_cs_steam( $get( 'steam', '' ) ) : ''; // General Info nema sliku igrača
	if ( false === $steam ) {
		return new WP_Error( 'steam', 'Link do Steam profila nije ispravan. Primer: https://steamcommunity.com/id/vasnick (ili ostavite prazno).' );
	}
	$lines = array(
		array( 'Mapa', $mape[ $mapa ]['naziv'] ),
		array( 'Model podloge', bumbaru_cs_modeli()[ $model ] ),
		array( 'Jezik na podlozi', 'sr' === $jezik ? 'SR (srpski)' : 'EN (engleski)' ),
	);
	if ( '' !== $ime ) {
		$lines[] = array( 'mvp' === $model ? 'Ime igrača' : 'Posveta za', $ime );
	}
	if ( 'mvp' === $model ) {
		$lines[] = array( 'Slika igrača', $steam ? 'preuzimamo sa Steam profila' : 'kupac šalje posle porudžbine ili bez slike' );
	}
	if ( $steam ) {
		$lines[] = array( 'Steam profil', $steam );
	}
	return array(
		'lines' => $lines,
		'raw'   => array( 'type' => 'csmapa', 'mapa' => $mapa, 'model' => $model, 'jezik' => $jezik, 'ime' => $ime, 'steam' => $steam ),
	);
}

/** Slajd u galeriji: podloga u crnom ramu (crta csmapa.js). */
function bumbaru_cs_slide( $product ) {
	return '<div class="slide live cs-slide" data-slide="live"><span class="badge live-badge"><i class="dotlive"></i>Pregled uživo</span>'
		. '<div class="cs-stage" data-cs-stage role="img" aria-label="Pregled podloge sa mapom u ramu"></div>'
		. '<p class="mix-hint cs-hint">Pregled je računarski prikaz. Gotov ram sa 3D mapom može izgledati malo drugačije (nijanse filamenta, senke, odsjaj stakla).</p></div>';
}

/** Polja konfiguratora. */
function bumbaru_cs_fields( $product ) {
	$c    = bumbaru_config( $product );
	$cfg  = $c['csmapa'];
	$mape = bumbaru_cs_mape();
	$data = bumbaru_cs_data( $cfg['mape'] );
	$o    = '<div class="cs-cfg" data-csmapa="' . esc_attr( wp_json_encode( $data ) ) . '">';
	$o   .= '<fieldset class="field"><legend class="flabel">Mapa</legend><div class="chips cs-mape">';
	foreach ( $cfg['mape'] as $id ) {
		$logo = $data['mape'][ $id ]['logo'];
		$o   .= '<label class="chip cs-chip"><input type="radio" name="cs_mapa" value="' . esc_attr( $id ) . '"' . checked( $id, $cfg['pocetna'], false ) . ' data-label="' . esc_attr( $mape[ $id ]['naziv'] ) . '" />'
			. ( $logo ? '<img src="' . esc_url( $logo ) . '" alt="" width="26" height="26" loading="lazy" />' : '' ) . esc_html( $mape[ $id ]['naziv'] ) . '</label>';
	}
	$o .= '</div></fieldset>';
	$o .= '<fieldset class="field"><legend class="flabel">Model podloge</legend><div class="chips">'
		. '<label class="chip"><input type="radio" name="cs_model" value="mvp" checked data-label="MVP" />MVP</label>'
		. '<label class="chip"><input type="radio" name="cs_model" value="info" data-label="General Info" />General Info</label>'
		. '</div><p class="hint" data-cs-model-hint>MVP: „Most Valuable Player”, slika i ime igrača.</p></fieldset>';
	$o .= '<fieldset class="field"><legend class="flabel">Jezik na podlozi</legend><div class="chips">'
		. '<label class="chip"><input type="radio" name="cs_jezik" value="en" checked data-label="EN" />EN (engleski)</label>'
		. '<label class="chip"><input type="radio" name="cs_jezik" value="sr" data-label="SR" />SR (srpski)</label>'
		. '</div></fieldset>';
	$o .= '<div class="field"><label for="f-cs-ime" data-cs-ime-l>Ime igrača (nick)</label>'
		. '<input id="f-cs-ime" class="input" name="cs_ime" maxlength="16" placeholder="npr. duletr" autocomplete="off" spellcheck="false" data-cs-ime />'
		. '<p class="hint"><span data-cs-err-ime>Do 16 znakova: slova, brojevi i znaci kao u nicku (._-!?#…). Štampamo tačno kako upišete.</span><span data-cs-cnt>0/16</span></p></div>';
	$o .= '<div class="field" data-cs-steam-f><label for="f-cs-steam">Link do Steam profila <span style="text-transform:none;letter-spacing:0;font-weight:400">(nije obavezno)</span></label>'
		. '<input id="f-cs-steam" class="input" name="cs_steam" type="url" inputmode="url" maxlength="200" placeholder="https://steamcommunity.com/id/vasnick" autocomplete="off" spellcheck="false" data-cs-steam />'
		. '<p class="hint" data-cs-err-steam>Sa njega sami preuzmemo sliku profila (avatar) za podlogu.</p>'
		. '<p class="cs-note">' . bumbaru_icon( 'camera', 18 ) . '<span>Sliku ne otpremate ovde. Ako ne upišete Steam link, sliku profila pošaljite posle porudžbine u ' . esc_html( bumbaru_cs_kontakti() ) . ', uz broj porudžbine. Može i bez slike: tada je na podlozi samo ime.</span></p></div>';
	$o .= '<p class="hint mot-err" data-cs-err role="alert"></p></div>';
	return $o;
}
