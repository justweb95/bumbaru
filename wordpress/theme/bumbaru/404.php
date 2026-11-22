<?php
/**
 * Stranica ne postoji (preneto iz 404.astro): pretraga i najčešće strane.
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="wrap">
	<section class="nf panel">
		<img src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="320" height="320" />
		<h1 class="h1">Ova stranica ne postoji</h1>
		<p class="lead">Možda je adresa promenjena ili ste je pogrešno upisali. Probajte pretragu ili idite tamo gde ljudi najčešće idu:</p>
		<form class="nf-form" action="<?php echo esc_url( bu_page_url( 'pretraga' ) ); ?>" method="get" role="search">
			<label class="sr-only" for="q-404">Pretraga</label>
			<?php echo bu_icon( 'search', 20 ); ?>
			<input id="q-404" class="input" name="q" type="search" placeholder="npr. ukras sa imenom, privezak" autocomplete="off" />
			<button class="btn btn-primary" type="submit">Traži</button>
		</form>
		<ul>
			<li><a class="link-arrow" href="<?php echo esc_url( bu_product_url( 'ukras-za-jelku-sa-imenom' ) ); ?>">Ukras za jelku sa imenom<?php echo bu_icon( 'arrow' ); ?></a></li>
			<li><a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'nova-godina' ) ); ?>">Novogodišnji katalog<?php echo bu_icon( 'arrow' ); ?></a></li>
			<li><a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'slavske-modlice' ) ); ?>">Slavske modlice<?php echo bu_icon( 'arrow' ); ?></a></li>
			<li><a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'dom-i-kuhinja' ) ); ?>">Dom i kuhinja<?php echo bu_icon( 'arrow' ); ?></a></li>
			<li><a class="link-arrow" href="<?php echo esc_url( bu_cat_url( 'radionica' ) ); ?>">Držači za aku alat<?php echo bu_icon( 'arrow' ); ?></a></li>
			<li><a class="link-arrow" href="<?php echo esc_url( bu_page_url( 'pomoc' ) ); ?>">Pomoć<?php echo bu_icon( 'arrow' ); ?></a></li>
		</ul>
		<a class="btn btn-primary btn-lg" href="<?php echo esc_url( home_url( '/' ) ); ?>">Na početnu</a>
	</section>
</div>
<?php
get_footer();
