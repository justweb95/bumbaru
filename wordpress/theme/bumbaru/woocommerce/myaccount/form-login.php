<?php
/**
 * Prijava i novi nalog (Bumbaru): dve kartice jedna pored druge.
 * Zasnovano na WooCommerce šablonu myaccount/form-login.php (ista polja, akcije i zaštita).
 *
 * @version 9.9.0
 */
defined( 'ABSPATH' ) || exit;

$bu_reg = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$bu_req = '&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">' . esc_html__( 'Required', 'woocommerce' ) . '</span>';

do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="acc-login<?php echo $bu_reg ? ' u-columns col2-set' : ''; ?>" id="customer_login">

	<div class="acc-box acc-box-login<?php echo $bu_reg ? ' u-column1 col-1' : ''; ?>">
		<span class="acc-box-ic" aria-hidden="true"><?php echo bu_icon( 'user', 22 ); ?></span>
		<h2 class="h2"><?php esc_html_e( 'Login', 'woocommerce' ); ?></h2>
		<p class="muted small">Već imate nalog? Prijavite se da vidite porudžbine i da se adresa sama upiše.</p>

		<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
			<?php do_action( 'woocommerce_login_form_start' ); ?>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username"><?php esc_html_e( 'Username or email address', 'woocommerce' ); ?><?php echo $bu_req; // phpcs:ignore ?></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore ?>
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?><?php echo $bu_req; // phpcs:ignore ?></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
			</p>
			<?php do_action( 'woocommerce_login_form' ); ?>
			<p class="form-row acc-row">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
				</label>
				<a class="acc-lost" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
			</p>
			<p class="form-row">
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button btn btn-primary btn-lg btn-block woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
			</p>
			<?php do_action( 'woocommerce_login_form_end' ); ?>
		</form>
	</div>

	<?php if ( $bu_reg ) : ?>
		<div class="acc-box acc-box-reg u-column2 col-2">
			<span class="acc-box-ic" aria-hidden="true"><?php echo bu_icon( 'sparkle', 22 ); ?></span>
			<h2 class="h2"><?php esc_html_e( 'Register', 'woocommerce' ); ?></h2>
			<ul class="acc-perks small">
				<li><?php echo bu_icon( 'check', 16, 2.6 ); ?>Sve porudžbine i njihov status na jednom mestu</li>
				<li><?php echo bu_icon( 'check', 16, 2.6 ); ?>Adresa se sama upiše kad sledeći put poručujete</li>
				<li><?php echo bu_icon( 'check', 16, 2.6 ); ?>Nalog nije obavezan: možete poručiti i bez njega</li>
			</ul>

			<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >
				<?php do_action( 'woocommerce_register_form_start' ); ?>
				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?><?php echo $bu_req; // phpcs:ignore ?></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore ?>
					</p>
				<?php endif; ?>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?><?php echo $bu_req; // phpcs:ignore ?></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore ?>
				</p>
				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?><?php echo $bu_req; // phpcs:ignore ?></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
					</p>
				<?php else : ?>
					<p class="small muted"><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></p>
				<?php endif; ?>
				<?php do_action( 'woocommerce_register_form' ); ?>
				<p class="woocommerce-form-row form-row">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="woocommerce-Button woocommerce-button button btn btn-lg btn-block woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
				</p>
				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
		</div>
	<?php endif; ?>
</div>
<p class="acc-guest small muted">Poručujete prvi put? Nalog nije potreban: <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">izaberite proizvod</a>, dodajte ga u korpu i upišite adresu. Plaćate pouzećem.</p>
<?php
do_action( 'woocommerce_after_customer_login_form' );
