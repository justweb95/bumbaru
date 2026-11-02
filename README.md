# Bumbaru: sajt-katalog (prva verzija)

Statički sajt u [Astro](https://astro.build) okviru, po dizajnu „Bumbaru prodavnica”. Nema korpe ni plaćanja na sajtu:
dugme **„Poruči preko Messengera”** složi poruku (naziv, šifra, izbor kupca i link proizvoda), kopira je i otvara
Messenger stranice **facebook.com/bumbaru.rs**. Link proizvoda u poruci Messenger prikazuje sa slikom proizvoda (og:image).

Adrese su iste kao u SEO planu (`/katalog/<kategorija>/`, `/proizvod/<naziv>/`, `/pomoc/`, `/po-meri/`), pa kasniji
prelazak na WordPress + WooCommerce zadržava iste linkove.

## Pokretanje na računaru

```bash
npm install
npm run dev      # http://localhost:4321
npm run build    # gotov sajt u folderu dist/
```

Potreban je Node.js 20 ili noviji.

## Objavljivanje (deploy)

Sajt je statički, osim jedne male serverske funkcije za admin panel (`/api/cms/`), koja radi na Vercel-u
(adapter `@astrojs/vercel`, izlaz u `.vercel/output/`).

**Vercel**
1. vercel.com → Add New → Project → izaberite GitHub repozitorijum `justweb95/bumbaru` → Import.
2. Framework: Astro (prepozna sam). Build: `npm run build`. → Deploy.
3. Settings → Domains → dodajte `bumbaru.sajt.net`; Vercel prikaže DNS zapis (obično CNAME na `cname.vercel-dns.com`).

> Napomena: besplatni Vercel Hobby plan je po pravilima Vercel-a samo za nekomercijalnu upotrebu, a sajt koji
> oglašava proizvode za prodaju se računa kao komercijalni. Za besplatan hosting koji to dozvoljava koristite
> Cloudflare Pages (isti koraci), ili Vercel Pro.

**Cloudflare Pages** (besplatno, dozvoljena komercijalna upotreba)
1. dash.cloudflare.com → Workers & Pages → Create → Pages → Connect to Git → `justweb95/bumbaru`.
2. Framework preset: Astro. Build command: `npm run build`. Output directory: `dist`. → Save and Deploy.
3. Custom domains → `bumbaru.sajt.net` → Cloudflare prikaže CNAME (na `<projekat>.pages.dev`).

**Domen bumbaru.sajt.net**: u panelu sajt.net dodajte DNS zapis koji prikaže Vercel ili Cloudflare
(CNAME `bumbaru` → adresa iz koraka 3). Ako se domen promeni, promenite i `site` u `astro.config.mjs`
i `url` u `src/data/site.ts`.

Svaki `git push` na granu `main` posle toga sam objavljuje novu verziju.

## Admin panel (proizvodi, kategorije, boje, podešavanja)

Adresa: `/anycubic/` (nije u pretrazi, mapi sajta ni u robots.txt; stara adresa `/admin/` više ne postoji).
Prijava je korisničkim imenom i lozinkom. U kodu (`src/lib/admin-auth.ts`) je samo njihov otisak, ne i
sama lozinka; nova prijava se pravi sa `node scripts/admin-lozinka.mjs <korisnicko-ime> <lozinka>`, a
ispisani red ide u `ADMIN_HASH` u tom fajlu ili u istoimenu promenljivu na Vercel-u (ona ima prednost).

U adminu se menja:
- **Proizvodi**: naziv, šifra, adresa, kategorije i filteri, cena („od”), oznaka „Po meri”, fotografije,
  kratak opis, uvod, opis, specifikacije, detalji, česta pitanja, izbor boje i ostale opcije, povezani
  proizvodi, SEO. Proizvod može da se sakrije, kopira, obriše ili doda nov. Redosled u listi je redosled u katalogu.
- **Fotografije**: biraju se fajlovi, a pregledač sam napravi verzije (`-lg` do 1200 px, `-sm` 640 px,
  `-sq` kvadrat 900 × 900, `-og.jpg` 1200 × 630 za deljenje) i na svaku stavi logo dole levo (tamni ili
  svetli, prema svetlini tog dela fotografije). Fajlovi idu u `public/img/p/<adresa>/u…`. Fotografije
  otpremljene iz admina se brišu iz repozitorijuma kad ih nijedan proizvod više ne koristi; stare (ručno
  dodate) fotografije se nikad ne brišu automatski.
- **Kategorije**: naziv, naslov, uvod, „Uskoro”, tekst ispod proizvoda, česta pitanja, filteri, ikonica,
  pozadina, SEO, redosled u meniju. Adresa postojeće kategorije se ne menja.
- **Izbor motiva** (npr. modlice): u proizvodu se uključi „Kupac bira motive”, doda se lista motiva sa
  fotografijom i nazivom (ili više odjednom iz fotografija, naziv od imena fajla) i popusti prema broju
  komada (npr. od 4 kom 17%, od 6 kom 24%, od 8 kom 30%). Na strani proizvoda kupac klikne motive (i broj
  komada svakog), traka sa pčelicom pokazuje koliko fali do sledećeg popusta, a poruka nosi motive, popust
  i cenu (cena = broj × cena po komadu − popust, zaokruženo na 10 din).
- **Izbor boja sa popustom** (npr. klipovi za kesice, privezak-stalak): u istoj sekciji „Kupac bira” → „Boje
  filamenta”. Pločice su sve boje koje su na stanju (kartica Boje), a na svakoj je crtež proizvoda obojen u tu boju
  (crteži su u `src/lib/siluete.ts`; bez crteža se prikazuje krug boje). U galeriji se pojavi „Vaš izbor” sa
  izabranim bojama i brojem komada, a dugmad „Šareni miks” jednim klikom slože set za svaki prag popusta.
- **Cena po opciji** (npr. Veličina: Mali / Srednji / Veliki / Privezak): uz svaku vrednost opcije može da
  se upiše cena; na sajtu se cena menja sa izborom, kartica u katalogu pokazuje „od … din”, a poruka nosi
  izabranu cenu. Prazna cena = osnovna cena proizvoda.
- **Poziv na Messenger** (npr. „Pošaljite mi celu kolekciju”): istaknuta kartica ispod dugmeta za
  porudžbinu, sa tekstom, natpisom dugmeta i porukom koja se otvori u Messenger-u.
- **Boje filamenta** i **prikaz cena** na celom sajtu.

Konfiguratori (ukras, porodični set i privezak sa imenom: pregled uživo) ostaju u kodu (`src/lib`).

Podaci su u `src/data/proizvodi.json`, `kategorije.json`, `boje.json` i `podesavanja.json`. Posle
„Sačuvaj i objavi” sve izmene (i nove fotografije) idu na GitHub u jednom commit-u, a Vercel za oko minut
objavi sajt. Ako objava ne uspe, sajt ostaje na prethodnoj verziji. Admin proverava podatke pre čuvanja
(`src/lib/cms-model.ts`), isto i server.

Jednom treba podesiti na Vercel-u (Project → Settings → Environment Variables), pa uraditi Redeploy:

| Promenljiva | Vrednost |
| --- | --- |
| `GITHUB_TOKEN` | GitHub fine-grained token: Repository access → samo `justweb95/bumbaru`, Permissions → Contents: Read and write (po želji i Commit statuses: Read-only, da admin pokaže kad je objava gotova) |
| `ADMIN_HASH` (nije obavezno) | druga prijava bez menjanja koda (red iz `scripts/admin-lozinka.mjs`) |

Stara promenljiva `ADMIN_PASSWORD` se više ne koristi i može da se obriše. Bez `GITHUB_TOKEN` admin
prikazuje podatke iz poslednje objave, ali izmene i fotografije ne mogu da se sačuvaju.

## Gde se šta menja

| Šta | Fajl |
| --- | --- |
| Messenger stranica, Facebook, Instagram, adresa sajta, traka na vrhu, sezona | `src/data/site.ts` |
| Proizvodi, kategorije, boje, prikaz cena | admin panel `/anycubic/` (podaci u `src/data/*.json`) |
| Stilovi slova za priveske sa imenom | `src/lib/keychain.ts` |
| Crteži proizvoda za izbor boja (klip, privezak-stalak) | `src/lib/siluete.ts` |
| Početna strana | `src/pages/index.astro` |
| Pomoć, Po meri, Politika privatnosti | `src/pages/*.astro` |
| Boje, slova, dugmad | `src/styles/global.css` |

### Novi proizvod
U adminu: Proizvodi → Novi proizvod (ili „Napravi kopiju” postojećeg), pa „Sačuvaj i objavi”.

### Slike i žig
- Stare fotografije: kartice i prva fotografija proizvoda imaju logo, ostale u galeriji su bez loga.
- Fotografije dodate iz admina imaju logo na svim verzijama.
- Proizvodi bez fotografije prikazuju „Fotografija uskoro”.
- Modlice za medenjake nisu na sajtu (nema deklaracije za kontakt sa hranom). Zbirna fotografija privezaka
  koristi se samo isečena, bez zaštićenog lika.
- Privesci sa imenom: stilovi slova imaju opisne nazive (pisana, bajkovita, kao od kockica, pikselasta,
  štampana), bez imena tuđih brendova i likova. Na fotografiji „kockice” figurica i kocke su isečene.
- Privesci sa imenom imaju okvirni pregled (crtež sa imenima i bojama) uz jasno upozorenje da je pravi
  izgled na fotografijama i da gotov privezak može da odudara od pregleda. Stil se bira preko sličica
  isečenih iz fotografija (`public/img/stil/`).
- Fontovi u pregledu: Playball (pisana), Mogra + srca na i/j (bajkovita), Nunito Black Italic (kockice),
  Montserrat ExtraBold (štampana) — svi SIL OFL; pikselasta slova: „Bumbaru Mine” = MineCrafter 2.0
  (autor asherz08, CC BY-SA 3.0) sa dodatim ČĆŠŽĐ, ćirilica iz fonta Monocraft (SIL OFL). Licence su u
  `public/fonts/`. Waltograph („Disney” font) nije na sajtu jer mu licenca zabranjuje komercijalnu upotrebu.

## Šta je namerno izostavljeno u prvoj verziji
- Korpa, plaćanje i nalog (porudžbine idu preko Messengera).
- Uslovi kupovine i podaci o prodavcu (PIB, matični broj): dodaju se kad se otvori firma.
- Cene: upisane su za proizvode gde postoje, ali se ne prikazuju dok se u adminu (Podešavanja) ne uključi „Prikaži cene na sajtu”.
