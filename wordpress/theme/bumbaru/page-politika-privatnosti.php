<?php
/**
 * Politika privatnosti. Ako na stranici u WordPress-u upišete tekst, prikazuje se taj tekst;
 * u suprotnom ovaj, prilagođen prodavnici sa korpom.
 */
defined( 'ABSPATH' ) || exit;
bu_set_seo( 'Politika privatnosti | Bumbaru', 'Koje podatke Bumbaru koristi: samo podatke iz porudžbine za izradu i dostavu, bez kolačića za praćenje i bez oglasa.' );
get_header();
the_post();
$content = trim( get_the_content() );
?>
<div class="wrap">
	<?php bu_crumbs( array( array( 'label' => 'Politika privatnosti' ) ) ); ?>
	<article class="doc panel">
		<h1 class="h1">Politika privatnosti</h1>
		<?php if ( $content ) : ?>
			<p class="small muted">Poslednja izmena: <?php echo esc_html( get_the_modified_date( 'j. n. Y.' ) ); ?></p>
			<div class="doc-content"><?php the_content(); ?></div>
		<?php else : ?>
			<p class="small muted">Poslednja izmena: 20. 11. 2026.</p>
			<h2 class="h3">Ukratko</h2>
			<p>Na sajtu Bumbaru poručujete bez naloga. Podatke iz porudžbine koristimo samo da izradimo i pošaljemo paket i da vam odgovorimo. Ne koristimo kolačiće za praćenje, oglase ni alate za analitiku.</p>
			<h2 class="h3">Porudžbine na sajtu</h2>
			<p>Kad potvrdite porudžbinu, sajt čuva podatke koje ste upisali: ime i prezime, adresu, poštanski broj, telefon, email ako ste ga upisali, napomenu i imena ili izbor za personalizaciju. Ime, adresu i telefon dajemo kurirskoj službi Post Express samo radi dostave paketa. Podatke ne prodajemo i ne dajemo drugima.</p>
			<h2 class="h3">Poruke preko Messengera</h2>
			<p>Kad kliknete „Poruči preko Messengera”, „Pitajte na Messenger-u” ili „Pošaljite upit”, sajt u vašem pregledaču složi tekst poruke i otvara Messenger stranice Bumbaru. Poruku šaljete vi, iz svog naloga, i ona ide preko servisa Meta Platforms. Na tu poruku se odnose i pravila privatnosti kompanije Meta.</p>
			<h2 class="h3">Tehnički podaci</h2>
			<p>Kao i svaki sajt, i ovaj se poslužuje preko servisa za hosting, koji radi bezbednosti i rada sajta beleži tehničke podatke o zahtevima (na primer IP adresu i vrstu pregledača). Mi te podatke ne koristimo za praćenje posetilaca.</p>
			<p>Fontove i skoro sve slike sajt učitava sa sopstvene adrese. Dve pozadinske fotografije (Radionica i Auto i moto) učitavaju se sa servisa Unsplash (images.unsplash.com), koji tada vidi IP adresu vašeg uređaja.</p>
			<h2 class="h3">Kolačići</h2>
			<p>Sajt koristi samo kolačiće koji su potrebni da korpa radi: pamte šta ste stavili u korpu dok ne završite porudžbinu. Ne koristimo kolačiće za praćenje ni za oglase.</p>
			<h2 class="h3">Vaša prava i kontakt</h2>
			<p>Ako želite da obrišemo podatke iz porudžbine ili prepisku, ili imate pitanje o privatnosti, pišite nam na <a href="<?php echo esc_url( bu_messenger_url() ); ?>" target="_blank" rel="noopener">Messenger-u</a>. Radionica: <?php echo esc_html( bu_site( 'city' ) ); ?>.</p>
		<?php endif; ?>
	</article>
</div>
<?php
get_footer();
