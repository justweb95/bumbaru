<?php
/**
 * Zaglavlje (Header.astro): traka na vrhu sa akcijom, logo, meni „Katalog” sa kategorijama,
 * Akcija, Nađi poklon, Po meri, korpa i „Pišite nam”. Na telefonu meni sa kategorijama.
 */
defined( 'ABSPATH' ) || exit;
$season  = bu_site( 'season' );
$ribbon  = 'stalna' === $season ? bu_site( 'ribbon_stalna' ) : bu_site( 'ribbon_ng' );
$search  = bu_page_url( 'pretraga' );
$cart    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
$ak      = function_exists( 'bumbaru_akcija' ) ? bumbaru_akcija() : null;
$kat     = bu_menu_categories();
$cur     = bu_current_key();
$kat_on  = 'katalog' === $cur || in_array( $cur, wp_list_pluck( wp_list_pluck( $kat, 'term' ), 'slug' ), true );
$ak_cat  = $ak ? get_term_by( 'slug', $ak['kategorija'], 'product_cat' ) : null;
$ak_url  = bu_page_url( 'akcija' );
$kv_url  = bu_page_url( 'nadji-poklon' );
$pm_url  = bu_page_url( 'po-meri' );
$shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/katalog/' );
?><!doctype html>
<html lang="sr-Latn">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<script>document.documentElement.classList.add('js');<?php if ( $ak ) : ?>if (Date.now() > Date.parse('<?php echo esc_js( $ak['kraj_iso'] ); ?>')) document.documentElement.classList.add('akcija-kraj');<?php endif; ?></script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#sadrzaj">Preskoči na sadržaj</a>
<?php if ( function_exists( 'bumbaru_ornament_defs' ) && ! empty( $GLOBALS['bu_ornaments'] ) ) : ?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs><?php echo bumbaru_ornament_defs(); // phpcs:ignore WordPress.Security.EscapeOutput ?></defs></svg>
<?php endif; ?>
<div class="ribbon<?php echo $ak ? ' rib-ak' : ''; ?>">
	<div class="wrap">
		<?php if ( $ak ) : ?>
			<a class="rib-akcija ak-on" href="<?php echo esc_url( $ak_url ); ?>"><?php echo bu_icon( 'gift', 16 ); ?><?php echo esc_html( $ak['naziv'] . ' −' . (float) $ak['procenat'] . '% do ' . bumbaru_datum_kratko( $ak['do'] ) ); ?></a><i class="dot opt ak-on" aria-hidden="true"></i>
		<?php endif; ?>
		<span class="rib-rok"><?php echo bu_icon( 'truck', 16 ); ?><?php echo esc_html( $ribbon ); ?></span>
		<?php if ( bu_site( 'ribbon_2' ) ) : ?>
			<i class="dot opt opt2" aria-hidden="true"></i>
			<span class="opt opt2"><?php echo bu_icon( 'cash', 16 ); ?><?php echo esc_html( bu_site( 'ribbon_2' ) ); ?></span>
		<?php endif; ?>
		<?php if ( bu_site( 'ribbon_3' ) ) : ?>
			<i class="dot opt opt2" aria-hidden="true"></i>
			<span class="opt opt2"><?php echo bu_icon( 'box', 16 ); ?><?php echo esc_html( bu_site( 'ribbon_3' ) ); ?></span>
		<?php endif; ?>
	</div>
</div>
<header class="header" id="top" data-header>
	<div class="wrap">
		<button class="icon-btn menu-btn" type="button" aria-label="Otvori meni" aria-controls="mobile-menu" aria-expanded="false" data-menu-open>
			<?php echo bu_icon( 'menu', 22 ); ?>
		</button>
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Bumbaru, početna strana">
			<picture>
				<source srcset="<?php echo esc_url( bu_img( 'brand/logo-tamni.webp' ) ); ?>" type="image/webp" />
				<img src="<?php echo esc_url( bu_img( 'brand/logo-tamni.png' ) ); ?>" alt="Bumbaru" width="640" height="205" />
			</picture>
		</a>
		<nav class="nav" aria-label="Glavni meni">
			<a class="nav-kat<?php echo $kat_on ? ' is-current' : ''; ?>" href="<?php echo esc_url( $shop ); ?>" aria-haspopup="true" aria-expanded="false" aria-controls="mega" data-mega-btn>
				<?php echo bu_icon( 'menu', 18 ); ?>Katalog<?php echo bu_icon( 'chev', 16 ); ?>
			</a>
			<?php if ( $ak ) : ?>
				<a href="<?php echo esc_url( $ak_url ); ?>" class="nav-ak ak-on"<?php echo 'akcija' === $cur ? ' aria-current="page"' : ''; ?>><?php echo bu_icon( 'gift', 17 ); ?>Akcija −<?php echo esc_html( (float) $ak['procenat'] ); ?>%</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( $kv_url ); ?>" class="nav-kviz"<?php echo 'kviz' === $cur ? ' aria-current="page"' : ''; ?>><?php echo bu_icon( 'sparkle', 17 ); ?>Nađi poklon</a>
			<a href="<?php echo esc_url( $pm_url ); ?>"<?php echo 'po-meri' === $cur ? ' aria-current="page"' : ''; ?>>Po meri</a>
		</nav>
		<div class="h-act">
			<?php
			$nalog = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
			if ( $nalog ) :
				$ulogovan = is_user_logged_in();
				$nl       = $ulogovan ? 'Moj nalog' : 'Prijava i moj nalog';
				?>
				<a class="icon-btn nalog-btn<?php echo $ulogovan ? ' is-in' : ''; ?>" href="<?php echo esc_url( $nalog ); ?>" aria-label="<?php echo esc_attr( $nl ); ?>" title="<?php echo esc_attr( $nl ); ?>"<?php echo function_exists( 'is_account_page' ) && is_account_page() ? ' aria-current="page"' : ''; ?>><?php echo bu_icon( 'user', 22 ); ?></a>
			<?php endif; ?>
			<?php if ( $cart ) : ?>
				<a class="icon-btn korpa-btn" href="<?php echo esc_url( $cart ); ?>" aria-label="Korpa" data-korpa-btn><?php echo bu_icon( 'bag', 22 ); ?><?php echo bu_cart_count_html(); // phpcs:ignore ?></a>
			<?php endif; ?>
			<a class="msg-btn" href="<?php echo esc_url( bu_messenger_url() ); ?>" target="_blank" rel="noopener" aria-label="Pišite nam na Messenger-u" title="Pišite nam na Messenger-u">
				<?php echo bu_icon( 'messenger', 20 ); ?><span class="msg-t">Pišite nam</span>
			</a>
		</div>
	</div>

	<!-- Padajući meni sa svim kategorijama -->
	<div class="mega" id="mega" data-mega hidden>
		<div class="wrap mega-in">
			<div class="mega-grid">
				<?php foreach ( $kat as $i => $k ) : ?>
					<a class="mega-kat<?php echo $cur === $k['term']->slug ? ' on' : ''; ?>" href="<?php echo esc_url( $k['url'] ); ?>" style="--r:<?php echo $i % 2 ? 1 : -1; ?>deg">
						<span class="mega-img"><img src="<?php echo esc_url( $k['slika'] ); ?>" alt="" width="160" height="160" loading="lazy" decoding="async" /><?php if ( $k['icon'] ) : ?><span class="mega-ic"><?php echo bu_path_icon( $k['icon'], 16 ); // phpcs:ignore ?></span><?php endif; ?></span>
						<span class="mega-t"><b><?php echo esc_html( $k['ime'] ); ?></b><small><?php echo esc_html( $k['soon'] ? 'Uskoro' : $k['n'] . ' ' . bu_plural( $k['n'] ) ); ?></small></span>
					</a>
				<?php endforeach; ?>
				<a class="mega-kat mega-all" href="<?php echo esc_url( $shop ); ?>">
					<span class="mega-img"><?php echo bu_icon( 'blocks', 30 ); ?></span>
					<span class="mega-t"><b>Ceo katalog</b><small>Sve kategorije na jednom mestu</small></span>
				</a>
			</div>
			<aside class="mega-side">
				<?php if ( $ak ) : ?>
					<a class="mega-promo mp-ak ak-on" href="<?php echo esc_url( $ak_url ); ?>">
						<span class="mp-lab"><?php echo bu_icon( 'gift', 18 ); ?><?php echo esc_html( $ak['naziv'] ); ?></span>
						<b class="mp-big">−<?php echo esc_html( (float) $ak['procenat'] ); ?>% na <?php echo esc_html( isset( $ak['opis'] ) ? $ak['opis'] : 'sve iz kategorije ' . ( $ak_cat ? $ak_cat->name : '' ) ); ?></b>
						<span class="small">Traje do <?php echo esc_html( bumbaru_datum_kratko( $ak['do'] ) ); ?>, cene su već umanjene.</span>
						<span class="link-arrow">Pogledajte akciju<?php echo bu_icon( 'arrow' ); ?></span>
					</a>
				<?php endif; ?>
				<a class="mega-promo mp-kviz" href="<?php echo esc_url( $kv_url ); ?>">
					<img class="fx-float" src="<?php echo esc_url( bu_img( 'brand/pcelica.webp' ) ); ?>" alt="" width="64" height="64" loading="lazy" />
					<span><b>Ne znate šta da poklonite?</b><span class="small">Četiri pitanja i predlažemo vam poklon.</span></span>
				</a>
			</aside>
		</div>
	</div>
</header>
<div class="mega-bg" data-mega-bg hidden></div>

<div class="mobile-menu" id="mobile-menu" data-menu>
	<div class="panel" role="dialog" aria-modal="true" aria-label="Meni">
		<div class="top">
			<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( bu_img( 'brand/logo-tamni.png' ) ); ?>" alt="Bumbaru" width="640" height="205" style="height:32px;width:auto" /></a>
			<button class="icon-btn" type="button" aria-label="Zatvori meni" data-menu-close><?php echo bu_icon( 'close', 22 ); ?></button>
		</div>
		<form action="<?php echo esc_url( $search ); ?>" method="get" role="search">
			<label class="sr-only" for="q-mob">Pretraga</label>
			<?php echo bu_icon( 'search', 18 ); ?>
			<input id="q-mob" name="q" type="search" placeholder="Pretraga proizvoda" autocomplete="off" />
		</form>
		<div class="m-promo">
			<?php if ( $ak ) : ?>
				<a class="m-ak ak-on" href="<?php echo esc_url( $ak_url ); ?>"><?php echo bu_icon( 'gift', 22 ); ?><span><strong>Akcija −<?php echo esc_html( (float) $ak['procenat'] ); ?>%</strong><span class="small"><?php echo esc_html( $ak['naziv'] . ' do ' . bumbaru_datum_kratko( $ak['do'] ) ); ?></span></span></a>
			<?php endif; ?>
			<a class="m-kviz" href="<?php echo esc_url( $kv_url ); ?>"><?php echo bu_icon( 'sparkle', 22 ); ?><span><strong>Nađi poklon</strong><span class="small">Kviz od 20 sekundi</span></span></a>
		</div>
		<p class="m-lab">Kategorije</p>
		<nav class="m-kat" aria-label="Kategorije">
			<?php foreach ( $kat as $k ) : ?>
				<a href="<?php echo esc_url( $k['url'] ); ?>"<?php echo $cur === $k['term']->slug ? ' aria-current="page"' : ''; ?>><img src="<?php echo esc_url( $k['slika'] ); ?>" alt="" width="44" height="44" loading="lazy" /><span><?php echo esc_html( $k['ime'] ); ?></span><small><?php echo esc_html( $k['soon'] ? 'Uskoro' : $k['n'] ); ?></small></a>
			<?php endforeach; ?>
		</nav>
		<nav class="m-links" aria-label="Ostalo">
			<a href="<?php echo esc_url( $pm_url ); ?>">3D štampa po meri</a>
			<a href="<?php echo esc_url( $shop ); ?>">Ceo katalog</a>
			<a href="<?php echo esc_url( bu_page_url( 'pomoc' ) ); ?>">Pomoć i kako poručiti</a>
			<?php if ( $cart ) : ?><a href="<?php echo esc_url( $cart ); ?>">Korpa</a><?php endif; ?>
			<?php if ( ! empty( $nalog ) ) : ?><a href="<?php echo esc_url( $nalog ); ?>"><?php echo is_user_logged_in() ? 'Moj nalog i porudžbine' : 'Prijava i moj nalog'; ?></a><?php endif; ?>
		</nav>
		<a class="btn btn-primary btn-lg" href="<?php echo esc_url( bu_messenger_url() ); ?>" target="_blank" rel="noopener"><?php echo bu_icon( 'messenger' ); ?>Pišite nam na Messenger-u</a>
	</div>
</div>
<main id="sadrzaj">
