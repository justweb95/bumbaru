<?php
/**
 * Podnožje i prozor „Poruka za Bumbaru” (porudžbina preko Messengera za proizvode bez cene).
 */
defined( 'ABSPATH' ) || exit;
?>
</main>
<footer class="footer dark">
	<div class="wrap">
		<div class="cols">
			<div class="brand">
				<img src="<?php echo esc_url( bu_img( 'brand/logo-svetli.png' ) ); ?>" alt="Bumbaru" width="640" height="205" style="height:40px;width:auto" loading="lazy" />
				<p><?php echo esc_html( bu_site( 'brand_sentence' ) ); ?></p>
				<div class="social">
					<a href="<?php echo esc_url( bu_site( 'facebook_url' ) ); ?>" target="_blank" rel="noopener" aria-label="Bumbaru na Facebook-u"><?php echo bu_icon( 'facebook' ); ?></a>
					<a href="<?php echo esc_url( bu_messenger_url() ); ?>" target="_blank" rel="noopener" aria-label="Pišite nam na Messenger-u"><?php echo bu_icon( 'messenger' ); ?></a>
					<?php if ( bu_site( 'instagram_url' ) ) : ?>
						<a href="<?php echo esc_url( bu_site( 'instagram_url' ) ); ?>" target="_blank" rel="noopener" aria-label="Bumbaru na Instagram-u"><?php echo bu_icon( 'instagram' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div>
				<h2>Katalog</h2>
				<ul>
					<?php foreach ( bu_categories() as $c ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( bu_cat_menu_label( $c ) ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( bu_page_url( 'po-meri' ) ); ?>">Po meri</a></li>
				</ul>
			</div>
			<div>
				<h2>Pomoć</h2>
				<ul>
					<?php $pomoc = bu_page_url( 'pomoc' ); ?>
					<li><a href="<?php echo esc_url( $pomoc . '#kako-poruciti' ); ?>">Kako poručiti</a></li>
					<li><a href="<?php echo esc_url( $pomoc . '#dostava' ); ?>">Dostava i plaćanje</a></li>
					<li><a href="<?php echo esc_url( $pomoc . '#reklamacije-i-povracaj' ); ?>">Reklamacije i povraćaj</a></li>
					<li><a href="<?php echo esc_url( $pomoc . '#o-nama' ); ?>">O nama</a></li>
				</ul>
			</div>
			<div>
				<h2>Kontakt</h2>
				<ul>
					<li><a href="<?php echo esc_url( bu_messenger_url() ); ?>" target="_blank" rel="noopener">Messenger</a></li>
					<li><a href="<?php echo esc_url( bu_site( 'facebook_url' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( bu_site( 'facebook_url' ) ) ) ); ?></a></li>
					<li style="color:var(--cream-muted)">Radionica: <?php echo esc_html( bu_site( 'city' ) ); ?></li>
				</ul>
			</div>
		</div>
		<div class="trail trail-honey" style="margin-top:48px" aria-hidden="true"></div>
		<div class="bottom" style="margin-top:0;border:0">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Bumbaru</span>
			<nav aria-label="Pravne informacije"><a href="<?php echo esc_url( bu_page_url( 'politika-privatnosti' ) ); ?>">Politika privatnosti</a><a href="<?php echo esc_url( $pomoc ); ?>">Pomoć</a></nav>
		</div>
	</div>
</footer>

<dialog class="order-dialog" id="order-dialog" aria-labelledby="od-title">
	<form method="dialog" class="od-inner">
		<div class="od-head">
			<h2 id="od-title" class="h3">Poruka za Bumbaru</h2>
			<button class="icon-btn" value="close" aria-label="Zatvori"><?php echo bu_icon( 'close', 20 ); ?></button>
		</div>
		<div class="od-prod" data-od-prod>
			<img data-od-img alt="" width="72" height="72" />
			<div class="od-ph" data-od-ph hidden><img src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="40" height="40" /></div>
			<div>
				<p style="font-weight:700" data-od-name></p>
				<p class="small muted">Šifra: <span data-od-id></span></p>
			</div>
		</div>
		<div class="field">
			<label for="od-text">Vaša poruka (možete je izmeniti)</label>
			<textarea id="od-text" class="textarea" rows="7" data-od-text></textarea>
		</div>
		<ol class="od-steps">
			<li>Kliknite dugme ispod: poruka se kopira i otvara se naš Messenger.</li>
			<li>Ako se poruka ne pojavi sama, nalepite je (dugo pritisnite polje, pa „Nalepi”) i pošaljite.</li>
			<li>Odgovaramo sa cenom i rokom. Plaćate kuriru kad paket stigne, a od <?php echo esc_html( bu_free_ship_label() ); ?> dostava je besplatna.</li>
		</ol>
		<div class="od-actions">
			<a class="btn btn-primary btn-lg btn-block" data-od-go href="https://m.me/" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Kopiraj i otvori Messenger</a>
			<button class="btn btn-block" type="button" data-od-copy><?php echo bu_icon( 'copy' ); ?>Samo kopiraj poruku</button>
		</div>
		<p class="od-status" role="status" aria-live="polite" data-od-status></p>
	</form>
</dialog>
<div class="bu-toast" role="status" aria-live="polite" data-toast hidden></div>
<?php if ( ! empty( $GLOBALS['bu_need_products'] ) ) : ?>
<script type="application/json" id="bu-products"><?php echo bu_products_json(); // phpcs:ignore -- wp_json_encode ?></script>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
