<?php
/**
 * Proizvod u administraciji: kartice „Bumbaru” (tekstovi) i „Konfigurator”, i okvir „Kalkulator cene”.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_product_data_tabs', function ( $tabs ) {
	$tabs['bumbaru']        = array( 'label' => 'Bumbaru', 'target' => 'bumbaru_product_data', 'priority' => 15 );
	$tabs['bumbaru_config'] = array( 'label' => 'Konfigurator', 'target' => 'bumbaru_config_data', 'priority' => 16 );
	return $tabs;
} );

add_action( 'woocommerce_product_data_panels', function () {
	global $post;
	$id  = $post->ID;
	$m   = function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, '_bu_' . $k, true );
	};
	$cfg = bumbaru_config( $id );
	$raw = get_post_meta( $id, '_bu_config', true );
	$raw = is_array( $raw ) ? $raw : array();
	$cats = get_the_terms( $id, 'product_cat' );
	wp_nonce_field( 'bumbaru_product', 'bumbaru_product_nonce' );
	?>
	<div id="bumbaru_product_data" class="panel woocommerce_options_panel">
		<div class="options_group">
			<?php
			woocommerce_wp_textarea_input( array( 'id' => '_bu_lead', 'label' => 'Uvod', 'value' => $m( 'lead' ), 'description' => 'Tekst ispod naslova na strani proizvoda.', 'desc_tip' => false ) );
			woocommerce_wp_checkbox( array( 'id' => '_bu_custom', 'label' => 'Po meri', 'value' => $m( 'custom' ) ? 'yes' : 'no', 'description' => 'Oznaka „Po meri · 3–5 dana” i „Proveravamo svako ime”.' ) );
			woocommerce_wp_checkbox( array( 'id' => '_bu_price_from', 'label' => 'Cena „od”', 'value' => $m( 'price_from' ) ? 'yes' : 'no', 'description' => 'Na kartici piše „od …”. Kad opcije menjaju cenu, „od” se dodaje samo.' ) );
			?>
		</div>
		<div class="options_group">
			<?php
			woocommerce_wp_textarea_input( array( 'id' => '_bu_specs', 'label' => 'Karakteristike', 'value' => $m( 'specs' ), 'rows' => 6, 'description' => 'Jedna po redu: Naziv | Vrednost (npr. Materijal | PLA plastika). Nazivi boja dobijaju kružić u boji.', 'desc_tip' => false ) );
			woocommerce_wp_textarea_input( array( 'id' => '_bu_details', 'label' => 'Pakovanje, dostava, povraćaj', 'value' => $m( 'details' ), 'rows' => 5, 'description' => 'Delovi na otvaranje, jedan po redu: Naslov | Tekst.', 'desc_tip' => false ) );
			woocommerce_wp_textarea_input( array( 'id' => '_bu_faq', 'label' => 'Česta pitanja', 'value' => $m( 'faq' ), 'rows' => 5, 'description' => 'Jedno po redu: Pitanje | Odgovor.', 'desc_tip' => false ) );
			?>
		</div>
		<div class="options_group">
			<?php
			woocommerce_wp_text_input( array( 'id' => '_bu_focus_kw', 'label' => 'Ključna reč', 'value' => $m( 'focus_kw' ), 'description' => 'Reč koju ljudi kucaju u Google (npr. ukras za jelku sa imenom). Upisuje se i u Yoast.', 'desc_tip' => false ) );
			woocommerce_wp_text_input( array( 'id' => '_bu_seo_title', 'label' => 'SEO naslov', 'value' => $m( 'seo_title' ), 'description' => 'Naslov u Google-u, do 60 znakova sa „ | Bumbaru”.', 'desc_tip' => false, 'custom_attributes' => array( 'maxlength' => 70 ) ) );
			woocommerce_wp_textarea_input( array( 'id' => '_bu_seo_desc', 'label' => 'SEO opis', 'value' => $m( 'seo_desc' ), 'rows' => 2, 'description' => 'Opis u Google-u, 120–158 znakova; neka kaže zašto da se klikne (pouzeće, besplatna dostava).', 'desc_tip' => false ) );
			$opts = array( '' => '— prva kategorija —' );
			foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $t ) {
				$opts[ $t->slug ] = $t->name;
			}
			woocommerce_wp_select( array( 'id' => '_bu_primary_cat', 'label' => 'Glavna kategorija', 'value' => $m( 'primary_cat' ), 'options' => $opts, 'description' => 'Za putanju (Početna / Kategorija / Proizvod).', 'desc_tip' => false ) );
			woocommerce_wp_text_input( array( 'id' => '_bu_home_order', 'label' => 'Na početnoj, mesto', 'type' => 'number', 'value' => $m( 'home_order' ), 'custom_attributes' => array( 'min' => 0, 'step' => 1 ), 'description' => '1–12 = prikazuje se u „Novogodišnji katalog” na početnoj, tim redom. 0 = ne prikazuje se.', 'desc_tip' => false ) );
			?>
		</div>
	</div>

	<div id="bumbaru_config_data" class="panel woocommerce_options_panel">
		<div class="options_group">
			<?php
			woocommerce_wp_select( array( 'id' => 'bu_config_type', 'name' => 'bu_config[type]', 'label' => 'Konfigurator', 'value' => $cfg['type'], 'options' => bumbaru_config_types() ) );
			?>
		</div>
		<div class="options_group" data-for-type="ukras set">
			<p class="form-field"><label>Boje ukrasa</label></p>
			<p class="form-field bu-checks" style="padding-left:162px">
				<?php foreach ( bumbaru_colors() as $c ) : ?>
					<label><input type="checkbox" name="bu_config[colors][]" value="<?php echo esc_attr( $c['id'] ); ?>"<?php checked( in_array( $c['id'], $cfg['colors'], true ) ); ?> /><i class="sw" style="background:<?php echo esc_attr( $c['face'] ); ?>"></i><?php echo esc_html( $c['label'] ); ?></label>
				<?php endforeach; ?>
			</p>
			<?php
			$nc = array( 'ista' => 'Kao ukras' );
			foreach ( bumbaru_colors() as $c ) {
				$nc[ $c['id'] ] = $c['label'];
			}
			woocommerce_wp_select( array( 'id' => 'bu_name_color', 'name' => 'bu_config[name_color]', 'label' => 'Boja imena (početna)', 'value' => isset( $raw['name_color'] ) ? $raw['name_color'] : $cfg['name_color'], 'options' => $nc ) );
			?>
		</div>
		<div class="options_group" data-for-type="set">
			<?php woocommerce_wp_text_input( array( 'id' => 'bu_set_extra', 'name' => 'bu_config[set_extra]', 'label' => 'Doplata za 5. ukras, din', 'type' => 'number', 'value' => $cfg['set_extra'], 'description' => 'Cena proizvoda je za 4 ukrasa.', 'desc_tip' => false ) ); ?>
		</div>
		<div class="options_group" data-for-type="jelka">
			<?php
			$styles = array();
			foreach ( bumbaru_tree_styles() as $k => $s ) {
				$styles[ $k ] = $s['label'];
			}
			woocommerce_wp_select( array( 'id' => 'bu_tree', 'name' => 'bu_config[tree]', 'label' => 'Oblik jelke', 'value' => $cfg['tree'], 'options' => $styles ) );
			$tc = array( '' => 'Podrazumevana za oblik' );
			foreach ( bumbaru_colors() as $c ) {
				$tc[ $c['id'] ] = $c['label'];
			}
			woocommerce_wp_select( array( 'id' => 'bu_tree_color', 'name' => 'bu_config[tree_color]', 'label' => 'Boja jelke (ne menja se)', 'value' => $cfg['tree_color'], 'options' => $tc ) );
			?>
			<p class="form-field"><label>Boje zvezdice</label></p>
			<p class="form-field bu-checks" style="padding-left:162px">
				<?php foreach ( bumbaru_colors() as $c ) : ?>
					<label><input type="checkbox" name="bu_config[star_colors][]" value="<?php echo esc_attr( $c['id'] ); ?>"<?php checked( in_array( $c['id'], $cfg['star_colors'], true ) ); ?> /><i class="sw" style="background:<?php echo esc_attr( $c['face'] ); ?>"></i><?php echo esc_html( $c['label'] ); ?></label>
				<?php endforeach; ?>
			</p>
			<?php
			$sd = array();
			foreach ( bumbaru_colors() as $c ) {
				$sd[ $c['id'] ] = $c['label'];
			}
			woocommerce_wp_select( array( 'id' => 'bu_star_default', 'name' => 'bu_config[star_default]', 'label' => 'Zvezdica na početku', 'value' => $cfg['star_default'], 'options' => $sd ) );
			?>
			<p class="form-field bu-mini-preview"><label>Pregled</label><?php echo bumbaru_tree_svg( $cfg['tree'], $cfg['star_default'], $cfg['tree_color'] ); // phpcs:ignore ?></p>
		</div>
		<div class="options_group">
			<?php
			woocommerce_wp_textarea_input( array(
				'id'          => 'bu_options',
				'name'        => 'bu_config[options]',
				'label'       => 'Opcije',
				'value'       => $cfg['options_text'],
				'rows'        => 4,
				'placeholder' => "Veličina = Mali [590] | Srednji [890] | Veliki [1290]\nOblik = Okrugli | Pravougaoni +100",
				'description' => 'Jedna opcija po redu: Naziv = vrednost | vrednost. Sopstvena cena vrednosti u uglastoj zagradi: „[590]”, a sa precrtanom redovnom cenom „[300 / 350]”. Doplata: „+150” ili „-200”. Cena proizvoda (Opšte) je cena prve vrednosti.',
				'desc_tip'    => false,
			) );
			woocommerce_wp_textarea_input( array(
				'id'          => 'bu_izbor_boja',
				'name'        => 'bu_config[izbor_boja]',
				'label'       => 'Izbor boja',
				'value'       => bumbaru_color_choices_to_text( $cfg['izbor_boja'] ),
				'rows'        => 2,
				'placeholder' => "Boja jelke = zelena\nBoja zvezde = zlatna",
				'description' => 'Jedno polje po redu: Naziv = podrazumevana boja (oznaka iz Bumbaru 3D → Boje). Kupac bira od boja koje su na stanju.',
				'desc_tip'    => false,
			) );
			$crt = array( '' => '— bez crteža —' );
			foreach ( bumbaru_astro_data( 'CRTEZI' ) as $cr ) {
				$crt[ $cr['id'] ] = $cr['label'];
			}
			woocommerce_wp_select( array( 'id' => 'bu_crtez', 'name' => 'bu_config[crtez]', 'label' => 'Crtež u bojama', 'value' => $cfg['crtez'], 'options' => $crt, 'description' => 'U galeriji crtež proizvoda u bojama koje kupac izabere (redom polja iz „Izbor boja”).', 'desc_tip' => false ) );
			?>
		</div>
		<div class="options_group" data-for-type="privezak">
			<?php $kc = $cfg['keychain'] ? $cfg['keychain'] : array( 'stilovi' => array( 'pisana' ), 'startImena' => 1, 'minImena' => 1, 'maxImena' => 6, 'sareno' => true ); ?>
			<p class="form-field"><label>Stilovi slova</label></p>
			<p class="form-field bu-checks" style="padding-left:162px">
				<?php foreach ( bumbaru_astro_data( 'STILOVI' ) as $st ) : ?>
					<label><input type="checkbox" name="bu_config[keychain][stilovi][]" value="<?php echo esc_attr( $st['id'] ); ?>"<?php checked( in_array( $st['id'], $kc['stilovi'], true ) ); ?> /><?php echo esc_html( $st['label'] ); ?></label>
				<?php endforeach; ?>
			</p>
			<?php
			woocommerce_wp_text_input( array( 'id' => 'bu_kc_start', 'name' => 'bu_config[keychain][startImena]', 'label' => 'Imena na početku', 'type' => 'number', 'value' => $kc['startImena'], 'custom_attributes' => array( 'min' => 1, 'max' => 12 ) ) );
			woocommerce_wp_text_input( array( 'id' => 'bu_kc_min', 'name' => 'bu_config[keychain][minImena]', 'label' => 'Najmanje imena', 'type' => 'number', 'value' => $kc['minImena'], 'custom_attributes' => array( 'min' => 1, 'max' => 12 ) ) );
			woocommerce_wp_text_input( array( 'id' => 'bu_kc_max', 'name' => 'bu_config[keychain][maxImena]', 'label' => 'Najviše imena', 'type' => 'number', 'value' => $kc['maxImena'], 'custom_attributes' => array( 'min' => 1, 'max' => 12 ), 'description' => 'Svako ime je jedan privezak; cena proizvoda je cena jednog priveska.', 'desc_tip' => false ) );
			woocommerce_wp_checkbox( array( 'id' => 'bu_kc_sareno', 'name' => 'bu_config[keychain][sareno]', 'label' => 'Šareno', 'value' => ! empty( $kc['sareno'] ) ? 'yes' : 'no', 'description' => 'Kupac može da izabere „Šareno”: svako ime u drugoj boji.' ) );
			?>
		</div>
		<div class="options_group" data-for-type="motivi boje">
			<?php
			$mot = $cfg['motivi'] ? $cfg['motivi'] : array( 'popusti' => array(), 'lista' => array(), 'silueta' => '' );
			woocommerce_wp_text_input( array( 'id' => 'bu_mot_popusti', 'name' => 'bu_config[motivi][popusti]', 'label' => 'Popust za količinu', 'value' => bumbaru_tiers_to_text( $mot['popusti'] ), 'placeholder' => '4=17, 6=24, 8=30', 'description' => 'Od koliko komada = popust u %. Traka sa pčelicom pokazuje koliko fali do sledećeg popusta.', 'desc_tip' => false ) );
			$sil = array( '' => '— krug boje —' );
			foreach ( bumbaru_astro_data( 'SILUETE' ) as $si ) {
				$sil[ $si['id'] ] = $si['label'];
			}
			woocommerce_wp_select( array( 'id' => 'bu_mot_sil', 'name' => 'bu_config[motivi][silueta]', 'label' => 'Crtež za boje', 'value' => $mot['silueta'], 'options' => $sil, 'description' => 'Samo za „Kupac bira boje”: crtež proizvoda obojen u svaku boju.', 'desc_tip' => false ) );
			$lines = array();
			foreach ( $mot['lista'] as $it ) {
				$lines[] = $it['naziv'] . ' | ' . ( $it['cena'] ? (float) $it['cena'] : '' ) . ' | ' . ( $it['cene'] ? implode( '/', array_map( 'floatval', $it['cene'] ) ) : '' ) . ' | ' . ( $it['img'] ? (int) $it['img'] : '' ) . ' | ' . $it['id'];
			}
			woocommerce_wp_textarea_input( array( 'id' => 'bu_mot_lista', 'name' => 'bu_config[motivi][lista]', 'label' => 'Motivi', 'value' => implode( "\n", $lines ), 'rows' => 8, 'placeholder' => "Krst | | | 1234\nSv. Nikola | 500 | 500/400/400 | 1240", 'description' => 'Samo za „Kupac bira motive”. Jedan po redu: Naziv | cena po komadu (prazno = cena proizvoda) | cene po veličini, redom (npr. 500/400/400) | ID fotografije iz Medija | oznaka (ne menjajte).', 'desc_tip' => false ) );
			?>
		</div>
		<div class="options_group" data-for-type="sastav">
			<?php
			$sas = $cfg['sastav'] ? $cfg['sastav'] : array( 'medaljon' => 'sv-nikola', 'broj' => 8 );
			$med = array();
			foreach ( bumbaru_set_medaljoni() as $md ) {
				$med[ $md['id'] ] = $md['naziv'];
			}
			woocommerce_wp_select( array( 'id' => 'bu_sas_med', 'name' => 'bu_config[sastav][medaljon]', 'label' => 'Medaljon', 'value' => $sas['medaljon'], 'options' => $med ) );
			woocommerce_wp_text_input( array( 'id' => 'bu_sas_broj', 'name' => 'bu_config[sastav][broj]', 'label' => 'Broj modlica u setu', 'type' => 'number', 'value' => $sas['broj'], 'custom_attributes' => array( 'min' => 1, 'max' => 16 ) ) );
			?>
		</div>
		<div class="options_group">
			<?php
			$pz = $cfg['poziv'] ? $cfg['poziv'] : array( 'tekst' => '', 'dugme' => '', 'poruka' => '' );
			woocommerce_wp_textarea_input( array( 'id' => 'bu_poziv_t', 'name' => 'bu_config[poziv][tekst]', 'label' => 'Poziv na Messenger', 'value' => $pz['tekst'], 'rows' => 2, 'placeholder' => 'Ovo je samo deo kolekcije: pišite nam i šaljemo vam celu ponudu.', 'description' => 'Istaknuta kartica ispod porudžbine. Prazno = nema kartice.', 'desc_tip' => false ) );
			woocommerce_wp_text_input( array( 'id' => 'bu_poziv_d', 'name' => 'bu_config[poziv][dugme]', 'label' => 'Natpis dugmeta', 'value' => $pz['dugme'], 'placeholder' => 'Pošaljite mi celu kolekciju' ) );
			woocommerce_wp_text_input( array( 'id' => 'bu_poziv_p', 'name' => 'bu_config[poziv][poruka]', 'label' => 'Poruka u Messenger-u', 'value' => $pz['poruka'] ) );
			$km = $cfg['komplet'] ? $cfg['komplet'] : array( 'uz' => array(), 'popust' => 10 );
			woocommerce_wp_text_input( array( 'id' => 'bu_km_uz', 'name' => 'bu_config[komplet][uz]', 'label' => 'Komplet: uz ovo', 'value' => implode( ', ', $km['uz'] ), 'placeholder' => 'adresa-drugog-proizvoda', 'description' => 'Adrese proizvoda (slug), odvojene zarezom. Prazno = nema kompleta.', 'desc_tip' => false ) );
			woocommerce_wp_text_input( array( 'id' => 'bu_km_p', 'name' => 'bu_config[komplet][popust]', 'label' => 'Popust za komplet, %', 'type' => 'number', 'value' => $km['popust'], 'custom_attributes' => array( 'min' => 0, 'max' => 90 ) ) );
			woocommerce_wp_text_input( array( 'id' => 'bu_kol', 'name' => 'bu_config[kolicina]', 'label' => 'Popust za više komada', 'value' => $cfg['kolicina_txt'], 'placeholder' => '2=5, 3=10, 5=15', 'description' => 'Od koliko komada = procenat. Sabiraju se svi komadi ovog proizvoda u korpi (npr. ukrasi sa različitim imenima). Na strani proizvoda pčelica na traci pokazuje koliko još do popusta. Prazno = nema popusta. Ne važi za izbor motiva/boja i slavski set (oni imaju svoj popust).', 'desc_tip' => false ) );
			$fm = $cfg['firme'] ? $cfg['firme'] : array( 'popusti' => array(), 'tekst' => '' );
			woocommerce_wp_text_input( array( 'id' => 'bu_fm_p', 'name' => 'bu_config[firme][popusti]', 'label' => 'Popusti za firme', 'value' => bumbaru_tiers_to_text( $fm['popusti'] ), 'placeholder' => '25=35, 50=40, 100=45', 'description' => 'Kartica „Za firme i veće količine” sa kalkulatorom. Prazno = nema kartice.', 'desc_tip' => false ) );
			woocommerce_wp_textarea_input( array( 'id' => 'bu_fm_t', 'name' => 'bu_config[firme][tekst]', 'label' => 'Tekst za firme', 'value' => $fm['tekst'], 'rows' => 2 ) );
			?>
		</div>
	</div>
	<?php
} );

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'bumbaru_calc', 'Kalkulator cene (3D štampa)', function ( $post ) {
		echo bumbaru_calc_ui( get_post_meta( $post->ID, '_bu_calc', true ), 'bu_calc', 'product' ); // phpcs:ignore
	}, 'product', 'normal', 'default' );
} );

add_action( 'woocommerce_process_product_meta', function ( $id ) {
	if ( ! isset( $_POST['bumbaru_product_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bumbaru_product_nonce'] ), 'bumbaru_product' ) ) {
		return;
	}
	$text = function ( $k ) {
		return isset( $_POST[ $k ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $k ] ) ) : ''; // phpcs:ignore
	};
	update_post_meta( $id, '_bu_lead', $text( '_bu_lead' ) );
	update_post_meta( $id, '_bu_custom', isset( $_POST['_bu_custom'] ) ? '1' : '' );
	update_post_meta( $id, '_bu_price_from', isset( $_POST['_bu_price_from'] ) ? '1' : '' );
	update_post_meta( $id, '_bu_specs', $text( '_bu_specs' ) );
	update_post_meta( $id, '_bu_details', isset( $_POST['_bu_details'] ) ? wp_kses_post( wp_unslash( $_POST['_bu_details'] ) ) : '' ); // phpcs:ignore
	update_post_meta( $id, '_bu_faq', isset( $_POST['_bu_faq'] ) ? wp_kses_post( wp_unslash( $_POST['_bu_faq'] ) ) : '' ); // phpcs:ignore
	update_post_meta( $id, '_bu_seo_title', $text( '_bu_seo_title' ) );
	update_post_meta( $id, '_bu_focus_kw', $text( '_bu_focus_kw' ) );
	update_post_meta( $id, '_bu_seo_desc', $text( '_bu_seo_desc' ) );
	update_post_meta( $id, '_bu_primary_cat', isset( $_POST['_bu_primary_cat'] ) ? sanitize_title( wp_unslash( $_POST['_bu_primary_cat'] ) ) : '' ); // phpcs:ignore
	update_post_meta( $id, '_bu_home_order', isset( $_POST['_bu_home_order'] ) ? max( 0, (int) $_POST['_bu_home_order'] ) : 0 );

	$in  = isset( $_POST['bu_config'] ) ? (array) wp_unslash( $_POST['bu_config'] ) : array(); // phpcs:ignore
	$ids = wp_list_pluck( bumbaru_colors(), 'id' );
	$prev = get_post_meta( $id, '_bu_config', true );
	$prev = is_array( $prev ) ? $prev : array();
	$cfg  = array(
		'type'         => isset( $in['type'] ) && array_key_exists( $in['type'], bumbaru_config_types() ) ? $in['type'] : '',
		'colors'       => array_values( array_intersect( (array) ( isset( $in['colors'] ) ? $in['colors'] : array() ), $ids ) ),
		'name_color'   => isset( $in['name_color'] ) ? sanitize_key( $in['name_color'] ) : '',
		'set_extra'    => isset( $in['set_extra'] ) ? max( 0, (float) $in['set_extra'] ) : 390,
		'tree'         => isset( $in['tree'] ) && array_key_exists( $in['tree'], bumbaru_tree_styles() ) ? $in['tree'] : 'mini',
		'tree_color'   => isset( $in['tree_color'] ) && in_array( $in['tree_color'], $ids, true ) ? $in['tree_color'] : '',
		'star_colors'  => array_values( array_intersect( (array) ( isset( $in['star_colors'] ) ? $in['star_colors'] : array() ), $ids ) ),
		'star_default' => isset( $in['star_default'] ) ? sanitize_key( $in['star_default'] ) : '',
		'options'      => isset( $in['options'] ) ? sanitize_textarea_field( $in['options'] ) : '',
		'izbor_boja'   => bumbaru_parse_color_choices( isset( $in['izbor_boja'] ) ? sanitize_textarea_field( $in['izbor_boja'] ) : '' ),
		'crtez'        => isset( $in['crtez'] ) ? sanitize_key( $in['crtez'] ) : '',
		'kolicina'     => isset( $in['kolicina'] ) ? bumbaru_tiers_to_text( bumbaru_parse_tiers( sanitize_text_field( $in['kolicina'] ) ) ) : '',
	);
	// Izbor boja zadržava oznake polja (npr. „zvezda”) kad se naziv ne menja.
	if ( ! empty( $prev['izbor_boja'] ) ) {
		foreach ( $cfg['izbor_boja'] as $k => $b ) {
			foreach ( (array) $prev['izbor_boja'] as $pb ) {
				if ( isset( $pb['label'], $pb['id'] ) && $pb['label'] === $b['label'] ) {
					$cfg['izbor_boja'][ $k ]['id'] = $pb['id'];
				}
			}
		}
	}
	if ( isset( $in['keychain'] ) && 'privezak' === $cfg['type'] ) {
		$k               = (array) $in['keychain'];
		$cfg['keychain'] = array(
			'stilovi'    => array_map( 'sanitize_key', isset( $k['stilovi'] ) ? (array) $k['stilovi'] : array( 'pisana' ) ),
			'startImena' => isset( $k['startImena'] ) ? (int) $k['startImena'] : 1,
			'minImena'   => isset( $k['minImena'] ) ? (int) $k['minImena'] : 1,
			'maxImena'   => isset( $k['maxImena'] ) ? (int) $k['maxImena'] : 6,
			'sareno'     => ! empty( $k['sareno'] ),
			'slike'      => isset( $prev['keychain']['slike'] ) ? $prev['keychain']['slike'] : array(),
		);
	}
	if ( isset( $in['motivi'] ) && in_array( $cfg['type'], array( 'motivi', 'boje' ), true ) ) {
		$m     = (array) $in['motivi'];
		$lista = array();
		foreach ( preg_split( '/\r\n|\r|\n/', isset( $m['lista'] ) ? (string) $m['lista'] : '' ) as $line ) {
			$p = array_map( 'trim', explode( '|', $line ) );
			if ( '' === $p[0] ) {
				continue;
			}
			$lista[] = array(
				'id'    => ! empty( $p[4] ) ? sanitize_key( $p[4] ) : sanitize_title( remove_accents( $p[0] ) ),
				'naziv' => sanitize_text_field( $p[0] ),
				'cena'  => isset( $p[1] ) && '' !== $p[1] ? (float) str_replace( ',', '.', $p[1] ) : 0,
				'cene'  => isset( $p[2] ) && '' !== $p[2] ? array_map( 'floatval', explode( '/', $p[2] ) ) : array(),
				'img'   => isset( $p[3] ) ? absint( $p[3] ) : 0,
			);
		}
		$cfg['motivi'] = array(
			'vrsta'   => 'boje' === $cfg['type'] ? 'boje' : '',
			'silueta' => isset( $m['silueta'] ) ? sanitize_key( $m['silueta'] ) : '',
			'popusti' => bumbaru_parse_tiers( isset( $m['popusti'] ) ? sanitize_text_field( $m['popusti'] ) : '' ),
			'lista'   => $lista,
		);
	}
	if ( isset( $in['sastav'] ) && 'sastav' === $cfg['type'] ) {
		$cfg['sastav'] = array( 'medaljon' => sanitize_key( $in['sastav']['medaljon'] ), 'broj' => (int) $in['sastav']['broj'] );
	}
	if ( ! empty( $in['poziv']['tekst'] ) && ! empty( $in['poziv']['dugme'] ) ) {
		$cfg['poziv'] = array( 'tekst' => sanitize_textarea_field( $in['poziv']['tekst'] ), 'dugme' => sanitize_text_field( $in['poziv']['dugme'] ), 'poruka' => sanitize_text_field( $in['poziv']['poruka'] ) );
	}
	if ( ! empty( $in['komplet']['uz'] ) ) {
		$cfg['komplet'] = array( 'uz' => array_values( array_filter( array_map( 'sanitize_title', explode( ',', $in['komplet']['uz'] ) ) ) ), 'popust' => (float) $in['komplet']['popust'] );
	}
	if ( ! empty( $in['firme']['popusti'] ) ) {
		$cfg['firme'] = array( 'popusti' => bumbaru_parse_tiers( sanitize_text_field( $in['firme']['popusti'] ) ), 'tekst' => sanitize_textarea_field( $in['firme']['tekst'] ) );
	}
	update_post_meta( $id, '_bu_config', $cfg );

	if ( isset( $_POST['bu_calc'] ) ) {
		update_post_meta( $id, '_bu_calc', bumbaru_calc_sanitize( wp_unslash( $_POST['bu_calc'] ) ) ); // phpcs:ignore
	}
} );
