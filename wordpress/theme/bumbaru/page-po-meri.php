<?php
/**
 * Po meri (preneto iz po-meri.astro). Upit ide preko Messengera, kao do sada.
 */
defined( 'ABSPATH' ) || exit;

$faq = array(
	array( 'q' => 'Koliko košta 3D štampa?', 'a' => 'Zavisi od veličine, materijala i vremena štampe. Tačnu cenu javljamo posle upita, pre izrade.' ),
	array( 'q' => 'Koliko traje?', 'a' => 'Rok javljamo zajedno sa cenom.' ),
	array( 'q' => 'Gde je radionica?', 'a' => 'U Leskovcu. Šaljemo Post Expressom širom Srbije, a za porudžbine od ' . bu_free_ship_label() . ' dostava je besplatna.' ),
	array( 'q' => 'Da li plaćam nešto pre potvrde?', 'a' => 'Ne. Upit je besplatan, a plaćate pouzećem kad paket stigne.' ),
);
bu_set_seo( '3D štampa po meri, Leskovac i cela Srbija | Bumbaru', '3D štampa po meri: rezervni delovi, držači i pokloni. Pošaljite meru, skicu ili fotografiju, javljamo cenu i rok. Šaljemo širom Srbije.' );
get_header();
?>
<div class="wrap">
	<?php bu_crumbs( array( array( 'label' => 'Po meri' ) ) ); ?>
	<section class="pm-hero dark" aria-labelledby="pm-title">
		<div class="pm-text">
			<span class="label" style="color:var(--honey)">Po meri</span>
			<h1 id="pm-title" class="h1">3D štampa po meri</h1>
			<p class="h3" style="font-weight:600">Pošaljite ideju, mi javljamo cenu.</p>
			<p style="color:var(--cream-muted)">Deo koji je pukao, držač za alat, tablica sa prezimenom ili poklon kog nema u katalogu. Pošaljite meru, skicu ili fotografiju.</p>
		</div>
		<img src="<?php echo esc_url( bu_img( 'bg/po-meri.webp' ) ); ?>" alt="3D štampač u radionici sa odštampanim delovima" width="1000" height="750" />
	</section>

	<section class="how" aria-labelledby="how-title">
		<h2 id="how-title" class="h2">Kako ide</h2>
		<ol>
			<li><span class="n">1</span><div><strong>Pošaljete upit</strong><p class="small muted">Opišite šta vam treba, pošaljite poruku, a fotografiju ili skicu dodajte u Messenger-u.</p></div></li>
			<li><span class="n">2</span><div><strong>Javljamo cenu i rok</strong><p class="small muted">Do vašeg „da” ništa ne plaćate.</p></div></li>
			<li><span class="n">3</span><div><strong>Štampamo i šaljemo</strong><p class="small muted">Gotov proizvod stiže Post Expressom, a plaćate pouzećem.</p></div></li>
		</ol>
	</section>

	<div class="pm-grid">
		<section class="panel form-panel" aria-labelledby="upit-title">
			<h2 id="upit-title" class="h2">Upit za izradu</h2>
			<form class="upit" data-upit novalidate>
				<div class="field">
					<label for="u-sta">Šta treba da napravimo?</label>
					<textarea id="u-sta" class="textarea" name="sta" required placeholder="Za šta služi, gde stoji i koliko treba da izdrži."></textarea>
					<p class="hint"><span data-err>Fotografiju ili skicu pošaljite u Messenger-u, odmah posle poruke.</span></p>
				</div>
				<div class="field">
					<label for="u-mere">Mere <span class="opt">(nije obavezno)</span></label>
					<input id="u-mere" class="input" name="mere" placeholder="Dužina, širina i visina, ako ih znate" />
				</div>
				<fieldset class="field">
					<legend class="flabel">Materijal</legend>
					<div class="chips">
						<label class="chip"><input type="radio" name="materijal" value="Ne znam, predložite" checked />Ne znam, predložite</label>
						<label class="chip"><input type="radio" name="materijal" value="PLA" />PLA: ukrasi i pokloni</label>
						<label class="chip"><input type="radio" name="materijal" value="PETG" />PETG: radionica i auto</label>
						<label class="chip"><input type="radio" name="materijal" value="ASA" />ASA: za napolje</label>
					</div>
				</fieldset>
				<div class="two">
					<div class="field">
						<label for="u-kol">Količina <span class="opt">(nije obavezno)</span></label>
						<input id="u-kol" class="input" name="kol" inputmode="numeric" placeholder="npr. 2" />
					</div>
					<div class="field">
						<label for="u-rok">Do kada vam treba? <span class="opt">(nije obavezno)</span></label>
						<input id="u-rok" class="input" name="rok" placeholder="npr. do 15. 12." />
					</div>
				</div>
				<button class="btn btn-primary btn-lg" type="submit"><?php echo bu_icon( 'messenger' ); ?>Pošaljite upit preko Messengera</button>
				<p class="small muted">Upit je besplatan. Podatke iz upita ne čuvamo na sajtu: poruka ide direktno u naš Messenger.</p>
			</form>
		</section>
		<aside class="side">
			<section class="panel" aria-labelledby="mat-title" style="padding:28px">
				<h2 id="mat-title" class="h3">Od čega štampamo</h2>
				<p class="small muted" style="margin-top:6px">Ako ne znate koji materijal vam treba, izaberite „Ne znam”, pa ćemo predložiti.</p>
				<dl class="mat">
					<div><dt>PLA</dt><dd>Ukrasi, pokloni, natpisi i figure. Lep izgled i mnogo boja, ali ne voli toplotu.</dd></div>
					<div><dt>PETG</dt><dd>Delovi za radionicu i auto koji trpe udarce, vlagu i toplotu.</dd></div>
					<div><dt>ASA</dt><dd>Za napolje: ne bledi na suncu i podnosi kišu i mraz.</dd></div>
				</dl>
			</section>
			<section class="panel" aria-labelledby="ne-title" style="padding:28px;background:var(--berry-soft)">
				<h2 id="ne-title" class="h3">Šta ne štampamo</h2>
				<p class="small" style="margin-top:6px">Delove za kočnice, upravljanje i pojaseve, delove koji nose težinu ljudi, predmete za hranu, oružje i zaštićene likove i logotipe.</p>
			</section>
		</aside>
	</div>

	<section class="section" aria-labelledby="pm-faq">
		<h2 id="pm-faq" class="h2" style="margin-bottom:12px">Česta pitanja</h2>
		<div class="faq-panel"><?php bu_accordion( $faq ); ?></div>
	</section>
</div>
<?php
get_footer();
