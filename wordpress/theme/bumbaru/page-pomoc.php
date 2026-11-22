<?php
/**
 * Pomoć (preneto iz pomoc.astro, tekst prilagođen korpi, pouzeću i besplatnoj dostavi od 2.000 din).
 */
defined( 'ABSPATH' ) || exit;

$teme = array(
	'kako-poruciti'          => 'Kako poručiti',
	'dostava'                => 'Dostava',
	'placanje'               => 'Plaćanje',
	'rokovi'                 => 'Rokovi izrade',
	'reklamacije-i-povracaj' => 'Reklamacije i povraćaj',
	'cesta-pitanja'          => 'Česta pitanja',
	'kontakt'                => 'Kontakt',
	'o-nama'                 => 'O nama',
);
$faq = array(
	array( 'q' => 'Mogu li ime da napišem ćirilicom?', 'a' => 'Možete. Ime upisujete latinicom ili ćirilicom, do 12 slova, i štampamo ga tačno kako ste ga upisali.' ),
	array( 'q' => 'Šta ako pogrešim u imenu?', 'a' => 'Pišite nam što pre u Messenger-u, sa brojem porudžbine. Dok štampa nije počela, ime možemo da ispravimo.' ),
	array( 'q' => 'Od čega su proizvodi?', 'a' => 'Od 3D štampane plastike. Ukrase i poklone štampamo od PLA, a delove za radionicu i auto koji trpe toplotu i vlagu od PETG ili ASA.' ),
	array( 'q' => 'Koliko košta dostava?', 'a' => 'Za porudžbine od ' . bu_free_ship_label() . ' dostava Post Expressom je besplatna. Ispod tog iznosa dostavu plaćate kuriru pri preuzimanju, po cenovniku Post Express-a.' ),
	array( 'q' => 'Mogu li da platim karticom?', 'a' => 'Za sada ne. Plaćate pouzećem, kuriru kad paket stigne.' ),
	array( 'q' => 'Šta ako poručim proizvod sa imenom posle 16. 12.?', 'a' => 'Pišite nam pre porudžbine, pa ćemo vam reći da li stiže do praznika.' ),
	array( 'q' => 'Mogu li da pošaljem poklon na drugu adresu?', 'a' => 'Možete: pri plaćanju označite „Dostava na drugu adresu”. Primalac plaća kuriru, jer je plaćanje pouzećem.' ),
	array( 'q' => 'Moram li da napravim nalog na sajtu?', 'a' => 'Ne. Porudžbinu potvrđujete bez naloga, upišete samo podatke za dostavu.' ),
	array( 'q' => 'Pravite li proizvode po meri?', 'a' => 'Pravimo. Pošaljite meru, skicu ili fotografiju preko stranice <a href="' . esc_url( bu_page_url( 'po-meri' ) ) . '">Po meri</a>, a mi javljamo cenu i rok.' ),
);
$faq_ld = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array() );
foreach ( $faq as $f ) {
	$faq_ld['mainEntity'][] = array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $f['a'] ) ) );
}
bu_add_jsonld( $faq_ld );
bu_set_seo( 'Pomoć: poručivanje, dostava i plaćanje pouzećem | Bumbaru', 'Kako se poručuje preko sajta ili Messengera, plaćanje pouzećem, besplatna dostava od 2.000 din, rokovi za Novu godinu i povraćaj.' );
get_header();
?>
<div class="wrap">
	<?php bu_crumbs( array( array( 'label' => 'Pomoć' ) ) ); ?>
	<div class="intro">
		<div>
			<h1 class="h1">Pomoć</h1>
			<p class="lead" style="margin-top:12px;max-width:620px">Kako se poručuje, kada stiže i šta ako nešto nije u redu. Ako ne nađete odgovor, pišite nam na Messenger-u.</p>
		</div>
		<a class="btn btn-primary btn-lg" href="<?php echo esc_url( bu_messenger_url() ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Pišite nam</a>
	</div>
	<ul class="facts">
		<li><?php echo bu_icon( 'cash', 22 ); ?><span><strong>Plaćanje pouzećem</strong><span class="small muted">Kuriru, kad paket stigne.</span></span></li>
		<li><?php echo bu_icon( 'truck', 22 ); ?><span><strong>Besplatna dostava od <?php echo esc_html( bu_free_ship_label() ); ?></strong><span class="small muted">Post Express, 1–2 radna dana.</span></span></li>
		<li><?php echo bu_icon( 'calendar', 22 ); ?><span><strong>Sa imenom 3–5 dana</strong><span class="small muted">Za Novu godinu do 16. 12.</span></span></li>
	</ul>

	<div class="layout">
		<nav class="toc" aria-label="Teme">
			<p class="label" style="margin-bottom:12px">Teme</p>
			<ul>
				<?php foreach ( $teme as $id => $t ) : ?>
					<li><a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $t ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<div class="content">
			<section id="kako-poruciti">
				<h2 class="h2">Kako poručiti</h2>
				<ol class="num-list">
					<li>Izaberite proizvod u katalogu. Za poklone upišite ime, a za ostalo izaberite boju, motiv ili model.</li>
					<li>Kliknite „Poruči preko sajta”. Otvara se korpa, gde se vidi vaš izbor (ime, motiv, boja).</li>
					<li>Kliknite „Nastavi na plaćanje” i upišite ime i prezime, adresu, poštanski broj i telefon za kurira.</li>
					<li>Kliknite „Potvrdi porudžbinu”. Plaćate pouzećem, kuriru kad paket stigne. Za porudžbine od <?php echo esc_html( bu_free_ship_label() ); ?> dostava je besplatna.</li>
				</ol>
				<p class="muted">Nalog na sajtu nije potreban. Ako vam je lakše, svaki proizvod možete poručiti i preko Messengera: kliknite „Poruči preko Messengera” i poruka sa nazivom, šifrom i vašim izborom se složi sama. Odgovaramo sa potvrdom i rokom. Možete nam pisati i direktno na <a href="<?php echo esc_url( bu_site( 'facebook_url' ) ); ?>" target="_blank" rel="noopener">Facebook stranici</a>.</p>
			</section>
			<section id="dostava">
				<h2 class="h2">Dostava</h2>
				<p>Šaljemo Post Expressom na adrese u Srbiji. Za porudžbine od <?php echo esc_html( bu_free_ship_label() ); ?> dostava je besplatna, a ispod toga je plaćate kuriru po cenovniku Post Express-a. U korpi piše koliko vam još fali do besplatne dostave. Paket stiže za 1–2 radna dana od slanja, a kurir vas zove pre dostave.</p>
				<p>Paket možete poslati i kao poklon na drugu adresu: pri plaćanju označite „Dostava na drugu adresu”. Tada primalac plaća kuriru pri preuzimanju.</p>
			</section>
			<section id="placanje">
				<h2 class="h2">Plaćanje</h2>
				<p>Plaćate pouzećem: gotovinom kuriru kad paket stigne. Ne treba vam kartica ni nalog na sajtu. Za sada nemamo plaćanje karticom ni uplatu na račun.</p>
			</section>
			<section id="rokovi">
				<h2 class="h2">Rokovi izrade</h2>
				<table class="specs"><tbody>
					<tr><th scope="row">Proizvodi sa imenom</th><td>izrada 3–5 radnih dana</td></tr>
					<tr><th scope="row">Ostali proizvodi</th><td>rok slanja javljamo kad potvrdimo porudžbinu</td></tr>
					<tr><th scope="row">Po meri</th><td>rok javljamo zajedno sa cenom</td></tr>
				</tbody></table>
				<p>Za Novu godinu: porudžbine primamo do 20. 12, a proizvode sa imenom do 16. 12. Ako u porudžbini ima proizvoda sa imenom, šaljemo sve zajedno kad budu gotovi.</p>
			</section>
			<section id="reklamacije-i-povracaj">
				<h2 class="h2">Reklamacije i povraćaj</h2>
				<p>Ako proizvod stigne oštećen ili ne odgovara porudžbini, javite nam se u Messenger-u što pre, sa fotografijom proizvoda i paketa. Šaljemo novi ili vraćamo novac, kako vam više odgovara.</p>
				<p>Proizvode bez imena možete vratiti u roku od 14 dana od prijema, bez navođenja razloga. Troškove slanja nazad plaćate vi, a novac vraćamo kad paket stigne.</p>
				<p>Proizvode napravljene po vašoj meri ili sa vašim imenom ne možemo da primimo nazad ako se predomislite, jer ih pravimo samo za vas. Na njih se i dalje odnosi reklamacija zbog greške ili oštećenja.</p>
			</section>
			<section id="cesta-pitanja">
				<h2 class="h2">Česta pitanja</h2>
				<?php bu_accordion( $faq ); ?>
			</section>
			<section id="kontakt">
				<h2 class="h2">Kontakt</h2>
				<ul class="contact">
					<li><?php echo bu_icon( 'messenger' ); ?><span><strong>Messenger</strong><a href="<?php echo esc_url( bu_messenger_url() ); ?>" target="_blank" rel="noopener">m.me/<?php echo esc_html( bu_site( 'messenger_page' ) ); ?></a></span></li>
					<li><?php echo bu_icon( 'facebook' ); ?><span><strong>Facebook</strong><a href="<?php echo esc_url( bu_site( 'facebook_url' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( bu_site( 'facebook_url' ) ) ) ); ?></a></span></li>
					<?php if ( bu_whatsapp_url() ) : ?>
						<li><?php echo bu_icon( 'whatsapp' ); ?><span><strong>WhatsApp</strong><a href="<?php echo esc_url( bu_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( bu_site( 'whatsapp' ) ); ?></a></span></li>
					<?php endif; ?>
					<?php if ( bu_site( 'instagram_url' ) ) : ?>
						<li><?php echo bu_icon( 'instagram' ); ?><span><strong>Instagram</strong><a href="<?php echo esc_url( bu_site( 'instagram_url' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( bu_site( 'instagram_url' ) ) ) ); ?></a></span></li>
					<?php endif; ?>
					<li><?php echo bu_icon( 'pin' ); ?><span><strong>Radionica</strong><span class="muted"><?php echo esc_html( bu_site( 'city' ) ); ?></span></span></li>
				</ul>
			</section>
			<section id="o-nama">
				<h2 class="h2">O nama</h2>
				<p>Bumbaru je mala radionica 3D štampe. Pravimo poklone sa imenom i delove za radionicu, auto i motor. Svaki komad sami štampamo, proveravamo i pakujemo, a šaljemo Post Expressom širom Srbije.</p>
				<p>Ako vam treba nešto čega nema u katalogu, <a href="<?php echo esc_url( bu_page_url( 'po-meri' ) ); ?>">pošaljite nam ideju</a>.</p>
			</section>
		</div>
	</div>
</div>
<?php
get_footer();
