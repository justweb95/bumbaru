<?php
/**
 * Tekstovi WooCommerce-a na srpskom, latinicom (korpa, plaćanje, potvrda, email kupcu).
 * Zvanični srpski prevod WooCommerce-a je na ćirilici, a sajt je na latinici,
 * pa ovde prevodimo ono što kupac vidi. Administracija ostaje na jeziku koji ste izabrali.
 */

defined( 'ABSPATH' ) || exit;

function bumbaru_wc_strings() {
	return array(
		// Korpa
		'Cart'                                                   => 'Korpa',
		'Product'                                                => 'Proizvod',
		'Price'                                                  => 'Cena',
		'Quantity'                                               => 'Količina',
		'Subtotal'                                               => 'Međuzbir',
		'Subtotal:'                                              => 'Međuzbir:',
		'Total'                                                  => 'Ukupno',
		'Total:'                                                 => 'Ukupno:',
		'Totals'                                                 => 'Ukupno',
		'Cart totals'                                            => 'Ukupno u korpi',
		'Proceed to checkout'                                    => 'Nastavi na plaćanje',
		'Update cart'                                            => 'Osveži korpu',
		'Remove this item'                                       => 'Ukloni iz korpe',
		'Remove %s from cart'                                    => 'Ukloni „%s” iz korpe',
		'Your cart is currently empty.'                          => 'Korpa je prazna.',
		'Return to shop'                                         => 'Pogledajte katalog',
		'Cart updated.'                                          => 'Korpa je osvežena.',
		'%s removed.'                                            => '%s je uklonjen iz korpe.',
		'Undo?'                                                  => 'Vrati?',
		'View cart'                                              => 'Pogledaj korpu',
		'Shipping'                                               => 'Dostava',
		'Shipping:'                                              => 'Dostava:',
		'Shipment'                                               => 'Dostava',
		'Shipment %d'                                            => 'Dostava %d',
		'Shipping method'                                        => 'Dostava',
		'Free shipping'                                          => 'Besplatna dostava',
		'Free!'                                                  => 'Besplatno',
		'Shipping to %s.'                                        => 'Dostava na adresu: %s.',
		'Shipping options will be updated during checkout.'      => 'Dostava se potvrđuje pri plaćanju.',
		'Shipping costs are calculated during checkout.'         => 'Dostava se potvrđuje pri plaćanju.',
		'Enter your address to view shipping options.'           => 'Upišite adresu da vidite dostavu.',
		'Change address'                                         => 'Promeni adresu',
		'Calculate shipping'                                     => 'Izračunaj dostavu',
		'Update'                                                 => 'Osveži',
		'Estimated for %s'                                       => 'Procena za: %s',
		'Sorry, this product cannot be purchased.'               => 'Ovaj proizvod se trenutno ne može kupiti.',
		'Please choose product options&hellip;'                  => 'Izaberite opcije proizvoda…',
		'Quantity of %s in your cart.'                           => 'Količina za „%s” u korpi.',
		'Product quantity'                                       => 'Količina',
		'%s in stock'                                            => 'Na stanju: %s',
		'In stock'                                               => 'Na stanju',
		'Out of stock'                                           => 'Nema na stanju',
		'Sale!'                                                  => 'Akcija!',
		'Thumbnail image'                                        => 'Slika',
		'Remove item'                                            => 'Ukloni',
		'Coupon:'                                                => 'Kupon:',
		'Coupon code'                                            => 'Kod kupona',
		'Apply coupon'                                           => 'Primeni kupon',
		// Plaćanje
		'Checkout'                                               => 'Plaćanje',
		'Billing details'                                        => 'Podaci za dostavu',
		'Billing &amp; Shipping'                                 => 'Podaci za dostavu',
		'Ship to a different address?'                          => 'Dostava na drugu adresu?',
		'Additional information'                                 => 'Dodatne informacije',
		'Order notes'                                            => 'Napomena za porudžbinu',
		'Notes about your order, e.g. special notes for delivery.' => 'Npr. kada ste kod kuće ili poruka za karticu u paketu.',
		'Your order'                                             => 'Vaša porudžbina',
		'Place order'                                            => 'Potvrdi porudžbinu',
		'First name'                                             => 'Ime',
		'Last name'                                              => 'Prezime',
		'Company name'                                           => 'Firma',
		'Country / Region'                                       => 'Država',
		'Street address'                                         => 'Ulica i broj',
		'House number and street name'                           => 'Ulica i kućni broj',
		'Apartment, suite, unit, etc.'                           => 'Stan, sprat, ulaz',
		'Apartment, suite, unit, etc. (optional)'                => 'Stan, sprat, ulaz (nije obavezno)',
		'Town / City'                                            => 'Mesto',
		'State / County'                                         => 'Okrug',
		'District'                                               => 'Okrug',
		'Postcode / ZIP'                                         => 'Poštanski broj',
		'Phone'                                                  => 'Telefon',
		'Email address'                                          => 'Email adresa',
		'(optional)'                                             => '(nije obavezno)',
		'optional'                                               => 'nije obavezno',
		'required'                                               => 'obavezno',
		'Cash on delivery'                                       => 'Plaćanje pouzećem',
		'Pay with cash upon delivery.'                           => 'Plaćate gotovinom kuriru kad paket stigne.',
		'%s is a required field.'                                => '%s je obavezno polje.',
		'Please enter a valid postcode / ZIP.'                   => 'Upišite ispravan poštanski broj.',
		'%s is not a valid phone number.'                        => '%s nije ispravan broj telefona.',
		'%s is not a valid postcode / ZIP.'                      => '%s nije ispravan poštanski broj.',
		'Invalid billing email address'                          => 'Email adresa nije ispravna',
		'%s is not a valid email address.'                       => '%s nije ispravna email adresa.',
		'Please read and accept the terms and conditions to proceed with your order.' => 'Pročitajte i prihvatite uslove kupovine da biste nastavili.',
		'privacy policy'                                         => 'politici privatnosti',
		'Billing address'                                        => 'Adresa',
		'Shipping address'                                       => 'Adresa za dostavu',
		'Have a coupon?'                                         => 'Imate kupon?',
		'Click here to enter your code'                          => 'Kliknite da upišete kod',
		'Select a country / region&hellip;'                      => 'Izaberite državu…',
		'Select an option&hellip;'                               => 'Izaberite…',
		'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.' => 'Trenutno nema dostupnog načina plaćanja. Pišite nam na Messenger-u.',
		'Processing'                                             => 'Obrađuje se',
		// Potvrda porudžbine
		'Order received'                                         => 'Porudžbina je primljena',
		'Thank you. Your order has been received.'               => 'Hvala! Vaša porudžbina je primljena.',
		'Order number:'                                          => 'Broj porudžbine:',
		'Order number'                                           => 'Broj porudžbine',
		'Date:'                                                  => 'Datum:',
		'Email:'                                                 => 'Email:',
		'Payment method:'                                        => 'Način plaćanja:',
		'Order details'                                          => 'Detalji porudžbine',
		'Note:'                                                  => 'Napomena:',
		'Customer details'                                       => 'Podaci kupca',
		'via %s'                                                 => '(%s)',
		'Order again'                                            => 'Poruči ponovo',
		// Moj nalog
		'My account'                                             => 'Moj nalog',
		'Account pages'                                          => 'Delovi naloga',
		'Dashboard'                                              => 'Moj nalog',
		'Orders'                                                 => 'Porudžbine',
		'Orders (page %d)'                                       => 'Porudžbine (strana %d)',
		'Downloads'                                              => 'Preuzimanja',
		'Addresses'                                              => 'Adrese',
		'Payment methods'                                        => 'Načini plaćanja',
		'Add payment method'                                     => 'Dodaj način plaćanja',
		'Account details'                                        => 'Podaci naloga',
		'Log out'                                                => 'Odjava',
		'Set password'                                           => 'Postavite lozinku',
		'Lost password'                                          => 'Zaboravljena lozinka',
		'Pay for order'                                          => 'Plaćanje porudžbine',
		'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)'      => 'Zdravo, %1$s (niste %1$s? <a href="%2$s">Odjavite se</a>)',
		'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.' => 'Ovde vidite <a href="%1$s">svoje porudžbine</a>, menjate <a href="%2$s">adrese za dostavu</a> i <a href="%3$s">lozinku i podatke naloga</a>.',
		'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">billing address</a>, and <a href="%3$s">edit your password and account details</a>.' => 'Ovde vidite <a href="%1$s">svoje porudžbine</a>, menjate <a href="%2$s">adresu</a> i <a href="%3$s">lozinku i podatke naloga</a>.',
		'Login'                                                  => 'Prijava',
		'Log in'                                                 => 'Prijavite se',
		'Register'                                               => 'Napravite nalog',
		'Username'                                               => 'Korisničko ime',
		'Username or email'                                      => 'Korisničko ime ili email',
		'Username or email address'                              => 'Email adresa ili korisničko ime',
		'Password'                                               => 'Lozinka',
		'Remember me'                                            => 'Zapamti me na ovom uređaju',
		'Required'                                               => 'obavezno',
		'Lost your password?'                                    => 'Zaboravili ste lozinku?',
		'A link to set a new password will be sent to your email address.' => 'Na vašu email adresu stiže link da postavite lozinku.',
		'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.' => 'Upišite email adresu (ili korisničko ime) i poslaćemo vam link za novu lozinku.',
		'Reset password'                                         => 'Pošalji link za novu lozinku',
		'Password reset email has been sent.'                    => 'Poslali smo vam email za novu lozinku.',
		'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.' => 'Poslali smo email sa linkom za novu lozinku na adresu iz vašeg naloga. Može da stigne za nekoliko minuta (proverite i neželjenu poštu), pa sačekajte bar 10 minuta pre novog pokušaja.',
		'Enter a new password below.'                            => 'Upišite novu lozinku.',
		'New password'                                           => 'Nova lozinka',
		'Re-enter new password'                                  => 'Ponovite novu lozinku',
		'Save'                                                   => 'Sačuvaj',
		'Your password has been reset successfully.'             => 'Lozinka je promenjena. Sada se prijavite novom lozinkom.',
		'This key is invalid or has already been used. Please reset your password again if needed.' => 'Ovaj link je istekao ili je već iskorišćen. Zatražite novi link za lozinku.',
		'Password reset is not allowed for this user'            => 'Za ovaj nalog promena lozinke nije dozvoljena.',
		'This password reset key is for a different user account. Please log out and try again.' => 'Ovaj link je za drugi nalog. Odjavite se i pokušajte ponovo.',
		'Enter a username or email address.'                     => 'Upišite email adresu ili korisničko ime.',
		'Invalid username or email.'                             => 'Ne postoji nalog sa tim korisničkim imenom ili email adresom.',
		'Username is required.'                                  => 'Upišite korisničko ime ili email.',
		'Password is required.'                                  => 'Upišite lozinku.',
		'Error:'                                                 => 'Greška:',
		'Please provide a valid email address.'                  => 'Upišite ispravnu email adresu.',
		'Please provide a valid account username.'               => 'Upišite ispravno korisničko ime.',
		'Please create a password for your account.'             => 'Izaberite lozinku za nalog.',
		'Please enter an account password.'                      => 'Izaberite lozinku za nalog.',
		'An account is already registered with %s. Please log in or use a different email address.' => 'Već postoji nalog sa adresom %s. Prijavite se ili upišite drugu email adresu.',
		'An account is already registered with your email address. <a href="#" class="showlogin">Please log in.</a>' => 'Već postoji nalog sa ovom email adresom. <a href="#" class="showlogin">Prijavite se.</a>',
		'An account is already registered with that username. Please choose another.' => 'To korisničko ime je zauzeto. Izaberite drugo.',
		'Your account was created successfully and a password has been sent to your email address.' => 'Nalog je napravljen, a lozinka je poslata na vašu email adresu.',
		'Your account was created successfully. Your login details have been sent to your email address.' => 'Nalog je napravljen. Podatke za prijavu smo poslali na vašu email adresu.',
		// Porudžbine u nalogu
		'Recent orders'                                          => 'Poslednje porudžbine',
		'Order'                                                  => 'Porudžbina',
		'Date'                                                   => 'Datum',
		'Status'                                                 => 'Status',
		'Actions'                                                => 'Šta možete',
		'View'                                                   => 'Pogledaj',
		'Pay'                                                    => 'Plati',
		'Cancel'                                                 => 'Otkaži',
		'View order number %s'                                   => 'Pogledaj porudžbinu broj %s',
		'%1$s order number %2$s'                                 => '%1$s porudžbinu broj %2$s',
		'No order has been made yet.'                            => 'Još nemate porudžbina preko naloga.',
		'Browse products'                                        => 'Pogledajte katalog',
		'Previous'                                               => 'Prethodna',
		'Next'                                                   => 'Sledeća',
		'Order #%1$s was placed on %2$s and is currently %3$s.'  => 'Porudžbina #%1$s je napravljena %2$s Status: %3$s.',
		'Order updates'                                          => 'Novosti o porudžbini',
		'Invalid order.'                                         => 'Porudžbina ne postoji.',
		'Your order can no longer be cancelled. Please contact us if you need assistance.' => 'Porudžbina se više ne može otkazati. Pišite nam na Messenger-u.',
		'Pending payment'                                        => 'Čeka potvrdu',
		'On hold'                                                => 'Na čekanju',
		'Completed'                                              => 'Poslato',
		'Cancelled'                                              => 'Otkazano',
		'Refunded'                                               => 'Novac vraćen',
		'Failed'                                                 => 'Nije uspelo',
		'Draft'                                                  => 'Nacrt',
		'No downloads available yet.'                            => 'Nemate ništa za preuzimanje.',
		'Available downloads'                                    => 'Za preuzimanje',
		// Adrese i podaci naloga
		'The following addresses will be used on the checkout page by default.' => 'Ove adrese se same upisuju kada poručujete.',
		'Edit %s'                                                => 'Izmeni: %s',
		'Add %s'                                                 => 'Dodaj: %s',
		'Edit'                                                   => 'Izmeni',
		'Add'                                                    => 'Dodaj',
		'You have not set up this type of address yet.'          => 'Ova adresa još nije upisana.',
		'Save address'                                           => 'Sačuvaj adresu',
		'Address changed successfully.'                          => 'Adresa je sačuvana.',
		'Display name'                                           => 'Ime koje se prikazuje',
		'This will be how your name will be displayed in the account section and in reviews' => 'Ovako vas pozdravljamo u nalogu.',
		'Password change'                                        => 'Promena lozinke',
		'Current password (leave blank to leave unchanged)'      => 'Trenutna lozinka (prazno ako ne menjate)',
		'New password (leave blank to leave unchanged)'          => 'Nova lozinka (prazno ako ne menjate)',
		'Confirm new password'                                   => 'Ponovite novu lozinku',
		'Save changes'                                           => 'Sačuvaj izmene',
		'Account details changed successfully.'                  => 'Podaci naloga su sačuvani.',
		'Display name cannot be changed to email address due to privacy concern.' => 'Zbog privatnosti, ime koje se prikazuje ne može biti email adresa.',
		'An error occurred while saving account details: %s'     => 'Podaci nisu sačuvani: %s',
		'Please enter your current password.'                    => 'Upišite trenutnu lozinku.',
		'Please enter your password.'                            => 'Upišite lozinku.',
		'Please re-enter your password.'                         => 'Ponovite lozinku.',
		'Please fill out all password fields.'                   => 'Popunite sva polja za lozinku.',
		'New passwords do not match.'                            => 'Nove lozinke se ne poklapaju.',
		'Passwords do not match.'                                => 'Lozinke se ne poklapaju.',
		'Your current password is incorrect.'                    => 'Trenutna lozinka nije tačna.',
		'This email address is already registered.'              => 'Ova email adresa je već zauzeta.',
		'Very weak - Please enter a stronger password.'          => 'Vrlo slaba lozinka: dodajte još slova i brojeva.',
		'Weak - Please enter a stronger password.'               => 'Slaba lozinka: dodajte još slova i brojeva.',
		'Medium'                                                 => 'Srednja',
		'Strong'                                                 => 'Jaka',
		'Hint: The password should be at least twelve characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ & ).' => 'Savet: lozinka neka ima bar 12 znakova, sa velikim i malim slovima, brojevima i znakovima kao ! ? $ %.',
		'Please enter a stronger password.'                      => 'Izaberite jaču lozinku.',
		'Show password'                                          => 'Prikaži lozinku',
		'Hide password'                                          => 'Sakrij lozinku',
		// Nalog na plaćanju
		'Returning customer?'                                    => 'Već imate nalog?',
		'Click here to login'                                    => 'Prijavite se',
		'If you have shopped with us before, please enter your details below. If you are a new customer, please proceed to the Billing section.' => 'Prijavite se da se adresa upiše sama. Ako nemate nalog, samo nastavite: nalog nije potreban.',
		'Create an account?'                                     => 'Napravite nalog da kasnije pratite porudžbine (nije obavezno)',
		'Create account password'                                => 'Lozinka za nalog',
		'Account username'                                       => 'Korisničko ime',
		'Account password'                                       => 'Lozinka za nalog',
		// Emailovi za nalog
		'Your {site_title} account has been created!'            => 'Vaš nalog na sajtu {site_title} je napravljen',
		'Welcome to {site_title}'                                => 'Dobro došli u {site_title}',
		'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s' => 'Hvala što ste napravili nalog na sajtu %1$s. Vaše korisničko ime je %2$s. U nalogu vidite porudžbine, menjate lozinku i adrese: %3$s',
		'Thanks for creating an account on %s. Here’s a copy of your user details.' => 'Hvala što ste napravili nalog na sajtu %s. Ovo su vaši podaci za prijavu.',
		'Username: %s'                                           => 'Korisničko ime: %s',
		'Username: <b>%s</b>'                                    => 'Korisničko ime: <b>%s</b>',
		'You can access your account area to view orders, change your password, and more via the link below:' => 'U nalogu vidite porudžbine, menjate lozinku i adrese. Link je ispod:',
		'Click here to set your new password.'                   => 'Kliknite da postavite lozinku.',
		'Set your new password.'                                 => 'Postavite lozinku.',
		'To set your password, visit the following address: '   => 'Lozinku postavljate na ovoj adresi: ',
		'We look forward to seeing you soon.'                    => 'Radujemo se vašoj sledećoj porudžbini.',
		'Password Reset Request for {site_title}'                => 'Nova lozinka za {site_title}',
		'Password Reset Request'                                 => 'Nova lozinka',
		'Reset your password for {site_title}'                   => 'Nova lozinka za {site_title}',
		'Reset your password'                                    => 'Postavite novu lozinku',
		'Someone has requested a new password for the following account on %s:' => 'Neko je zatražio novu lozinku za ovaj nalog na sajtu %s:',
		'If you didn’t make this request, just ignore this email. If you’d like to proceed, reset your password via the link below:' => 'Ako to niste bili vi, samo zanemarite ovaj email. Ako jeste, novu lozinku postavljate preko linka ispod:',
		'If you didn\'t make this request, just ignore this email. If you\'d like to proceed:' => 'Ako to niste bili vi, samo zanemarite ovaj email. Ako jeste:',
		'Click here to reset your password'                      => 'Kliknite da postavite novu lozinku',
		// Email kupcu i vama
		'Thanks for your order'                                  => 'Hvala na porudžbini',
		'Thank you for your order'                               => 'Hvala na porudžbini',
		'Your {site_title} order has been received!'             => 'Vaša porudžbina u prodavnici {site_title} je primljena!',
		'Your {site_title} order has been received'              => 'Vaša porudžbina u prodavnici {site_title} je primljena',
		'[{site_title}]: New order #{order_number}'              => '[{site_title}]: Nova porudžbina #{order_number}',
		'New Order: #{order_number}'                             => 'Nova porudžbina #{order_number}',
		'Hi %s,'                                                 => 'Zdravo %s,',
		'Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:' => 'Primili smo vašu porudžbinu #%s i počinjemo sa izradom:',
		'Just to let you know &mdash; we&#039;ve received your order #%s, and it is now being processed:' => 'Primili smo vašu porudžbinu #%s i počinjemo sa izradom:',
		'Thanks for using {site_url}!'                           => 'Hvala što kupujete na {site_url}!',
		'Thanks for reading.'                                    => 'Hvala.',
		'We look forward to fulfilling your order soon.'         => 'Javljamo se čim paket krene.',
		'You&rsquo;ve received the following order from %s:'      => 'Stigla je nova porudžbina od kupca %s:',
		'You’ve received the following order from %s:'           => 'Stigla je nova porudžbina od kupca %s:',
		'Congratulations on the sale.'                           => 'Čestitamo na prodaji.',
		'[Order #%s]'                                            => '[Porudžbina #%s]',
		'(#%s)'                                                  => '(#%s)',
		'Order summary'                                          => 'Pregled porudžbine',
		'Order #%s'                                              => 'Porudžbina #%s',
		'Payment method'                                         => 'Način plaćanja',
		'Discount:'                                              => 'Popust:',
		'Your order on {site_title} is now complete'             => 'Vaša porudžbina u prodavnici {site_title} je poslata',
		'Good things are heading your way!'                      => 'Paket je na putu!',
		'Your order is complete'                                 => 'Porudžbina je poslata',
		'We have finished processing your order.'                => 'Porudžbina je spakovana i poslata.',
	);
}

function bumbaru_translate_frontend() {
	// Kupac: sajt, korpa, plaćanje i emailovi koji se šalju posle porudžbine.
	return ! is_admin() || wp_doing_ajax() || doing_action( 'woocommerce_checkout_order_processed' );
}

add_filter( 'gettext_woocommerce', function ( $translation, $text ) {
	if ( ! bumbaru_translate_frontend() ) {
		return $translation;
	}
	static $map = null;
	if ( null === $map ) {
		$map = bumbaru_wc_strings();
	}
	return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
}, 20, 2 );

add_filter( 'gettext_with_context_woocommerce', function ( $translation, $text, $context ) {
	if ( ! bumbaru_translate_frontend() ) {
		return $translation;
	}
	static $map = null;
	if ( null === $map ) {
		$map = bumbaru_wc_strings();
	}
	if ( 'Order status' === $context && 'Processing' === $text ) {
		return 'U izradi';
	}
	return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
}, 20, 3 );

add_filter( 'ngettext_woocommerce', function ( $translation, $single, $plural, $number ) {
	if ( ! bumbaru_translate_frontend() ) {
		return $translation;
	}
	$m = array(
		'%s has been added to your cart.'  => '„%s” je u korpi.',
		'%s have been added to your cart.' => '„%s” su u korpi.',
		'%d item'                          => '%d proizvod',
		'%d items'                         => '%d proizvoda',
		'%1$s for %2$s item'               => '%1$s za %2$s proizvod',
		'%1$s for %2$s items'              => '%1$s za %2$s proizvoda',
		'Address'                          => 'Adresa',
		'Addresses'                        => 'Adrese',
	);
	$key = 1 === (int) $number ? $single : $plural;
	return isset( $m[ $key ] ) ? $m[ $key ] : $translation;
}, 20, 4 );

// Polja na plaćanju: kraće i prilagođeno Srbiji (bez firme i okruga; telefon obavezan, email nije).
add_filter( 'woocommerce_default_address_fields', function ( $f ) {
	unset( $f['company'], $f['state'] );
	if ( isset( $f['address_1'] ) ) {
		$f['address_1']['label']       = 'Ulica i broj';
		$f['address_1']['placeholder'] = 'npr. Bulevar oslobođenja 12';
	}
	if ( isset( $f['address_2'] ) ) {
		$f['address_2']['label']       = 'Stan, sprat, ulaz';
		$f['address_2']['label_class'] = array();
		$f['address_2']['placeholder'] = 'nije obavezno';
	}
	if ( isset( $f['city'] ) ) {
		$f['city']['label'] = 'Mesto';
	}
	if ( isset( $f['postcode'] ) ) {
		$f['postcode']['label']    = 'Poštanski broj';
		$f['postcode']['priority'] = 65;
	}
	if ( isset( $f['first_name'] ) ) {
		$f['first_name']['label'] = 'Ime';
	}
	if ( isset( $f['last_name'] ) ) {
		$f['last_name']['label'] = 'Prezime';
	}
	return $f;
}, 20 );

add_filter( 'woocommerce_billing_fields', function ( $f ) {
	if ( isset( $f['billing_phone'] ) ) {
		$f['billing_phone']['required']    = true;
		$f['billing_phone']['label']       = 'Telefon';
		$f['billing_phone']['placeholder'] = 'za kurira, npr. 064 123 4567';
		$f['billing_phone']['priority']    = 25;
	}
	if ( isset( $f['billing_email'] ) ) {
		$f['billing_email']['required']    = false;
		$f['billing_email']['label']       = 'Email adresa';
		$f['billing_email']['placeholder'] = 'za potvrdu porudžbine';
	}
	return $f;
}, 20 );

// Srbija na srpskom.
add_filter( 'woocommerce_countries', function ( $c ) {
	if ( isset( $c['RS'] ) ) {
		$c['RS'] = 'Srbija';
	}
	return $c;
} );

// Napomena na plaćanju; telefon obavezan i odmah posle imena (kurir zove pre dostave).
add_filter( 'woocommerce_checkout_fields', function ( $f ) {
	if ( isset( $f['billing']['billing_phone'] ) ) {
		$f['billing']['billing_phone']['required'] = true;
		$f['billing']['billing_phone']['priority'] = 25;
	}
	if ( isset( $f['billing']['billing_email'] ) ) {
		$f['billing']['billing_email']['required'] = false;
	}
	foreach ( array( 'billing', 'shipping' ) as $g ) {
		if ( isset( $f[ $g ] ) ) {
			uasort( $f[ $g ], function ( $a, $b ) {
				return ( isset( $a['priority'] ) ? $a['priority'] : 0 ) - ( isset( $b['priority'] ) ? $b['priority'] : 0 );
			} );
		}
	}
	if ( isset( $f['order']['order_comments'] ) ) {
		$f['order']['order_comments']['label']       = 'Napomena za porudžbinu';
		$f['order']['order_comments']['placeholder'] = 'Npr. kada ste kod kuće ili poruka za karticu u paketu.';
	}
	return $f;
}, 999 );

// Greške pri prijavi dolaze iz samog WordPress-a: i one na srpskom, na sajtu (administracija ostaje kakva jeste).
add_filter( 'gettext', function ( $translation, $text, $domain ) {
	if ( 'default' !== $domain || is_admin() ) {
		return $translation;
	}
	static $m = array(
		'<strong>Error:</strong> The username field is empty.' => '<strong>Greška:</strong> upišite email adresu ili korisničko ime.',
		'<strong>Error:</strong> The password field is empty.' => '<strong>Greška:</strong> upišite lozinku.',
		'<strong>Error:</strong> The username <strong>%s</strong> is not registered on this site. If you are unsure of your username, try your email address instead.' => '<strong>Greška:</strong> korisničko ime <strong>%s</strong> ne postoji. Probajte sa email adresom.',
		'<strong>Error:</strong> The password you entered for the username %s is incorrect.' => '<strong>Greška:</strong> lozinka za korisnika %s nije tačna.',
		'<strong>Error:</strong> The password you entered for the email address %s is incorrect.' => '<strong>Greška:</strong> lozinka za adresu %s nije tačna.',
		'<strong>Error:</strong> Unknown email address. Check again or try your username.' => '<strong>Greška:</strong> ne postoji nalog sa ovom email adresom. Proverite adresu.',
		'Unknown email address. Check again or try your username.' => 'Ne postoji nalog sa ovom email adresom. Proverite adresu.',
		'Lost your password?' => 'Zaboravili ste lozinku?',
		'Hint: The password should be at least twelve characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).' => 'Savet: lozinka neka ima bar 12 znakova, sa velikim i malim slovima, brojevima i znakovima kao ! ? $ %.',
	);
	return isset( $m[ $text ] ) ? $m[ $text ] : $translation;
}, 20, 3 );

// Jačina lozinke (WordPress skripta, i na nalogu i na plaćanju).
add_filter( 'gettext_with_context', function ( $translation, $text, $context, $domain ) {
	if ( 'default' !== $domain || is_admin() || ( 'password strength' !== $context && 'password mismatch' !== $context ) ) {
		return $translation;
	}
	static $m = array(
		'Password strength unknown' => 'Jačina lozinke nepoznata',
		'Very weak'                 => 'Vrlo slaba',
		'Weak'                      => 'Slaba',
		'Medium'                    => 'Srednja',
		'Strong'                    => 'Jaka',
		'Mismatch'                  => 'Ne poklapa se',
	);
	return isset( $m[ $text ] ) ? $m[ $text ] : $translation;
}, 20, 4 );
