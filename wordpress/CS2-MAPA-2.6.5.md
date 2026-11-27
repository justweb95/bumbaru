# 2.6.5 — Counter-Strike 2 mapa u ramu (PK-02)

Beleška za sledećeg agenta/programera. Stanje 27. 11. 2026. Paketi su napravljeni, ali na bumbaru.rs **još nisu otpremljeni**
(korisnik ih otprema sam). Pre bilo kakve izmene proveri na živom sajtu `?ver=` plugina i teme.

## Proizvod
- PK-02, slug `cs2-mapa-u-ramu`, kategorija „Pokloni sa imenom”, cena 1.490 din (kalkulator, `prati=1`).
- Fizički: crni A4 ram iz Jyska (350 din) + glossy A4 podloga štampana kod kuće (60 din, procena) + 3D štampana mapa
  (najduža strana uvek **160 mm**) zalepljena preko sivog logoa mape.
- Kalkulator je postavljen na obračun stare ravne Dust II (45 g, 1 h 36 min, rad 20 min, dodaci ram + papir).
  Nove mape sa nivelacijom su teže (Dust II ~72 g, Mirage ~64 g, Inferno 85–95 g) + višebojni purge ~20 g/40 min —
  korisnik treba da upiše stvarne grame/vreme iz slajsera (očekivano 1.590–1.690 din).

## Konfigurator (sajt)
- `plugins/bumbaru-3d/includes/csmapa.php` (podaci mapa, SVG podloga, polja, korpa), `assets/csmapa.js` (živi pregled),
  `assets/fonts/csmapa.css` (+ Geologica 600 woff2).
- Izbori: mapa (dust2, mirage, inferno, ancient, cache, nuke, vertigo), podloga MVP | General Info, jezik EN | SR,
  ime igrača (do 16 znakova; obavezno za MVP), Steam link (opciono). **Nema uploada slike** — kupac šalje sliku posle
  porudžbine ili admin preuzme Steam avatar.
- MVP: CS logo levo (`assets/cs/cs-znak.webp` iz korisnikovog `cs2-znak.png`), logo mape desno (oba 26×26 mm),
  sivi logo iza mape, „MOST VALUABLE PLAYER” / SR „NAJKORISNIJI IGRAČ” (korisnik izričito želi „najkorisniji”).
- General Info: naš dizajn (ne kopija tuđeg „Rubitek” proizvoda), podaci o mapama provereni na webu; nesigurni datumi:
  Vertigo (1999/2001), Inferno (samo godina 2001), Cache (jul 2014).
- Ispod „Pregled uživo” stoji napomena da je pregled računarski prikaz (+ pitanje u FAQ).
- Admin snimanje proizvoda: `_bu_config['csmapa']` ima polja u `admin-product.php` i fallback na `$prev`.

## Štampa i generator (admin)
- `includes/csmapa-admin.php`: `admin-post.php?action=bumbaru_cs_podloga` (A4 strana za štampu, @page A4, menja se
  mapa/model/jezik/ime, slika igrača samo lokalno u pregledaču), `&preuzmi=png` → PNG 2480×3508 (300 dpi) iz SVG-a,
  `action=bumbaru_cs_3mf` → gotov 3MF mape kao `PK-02-<porudžbina>-<mapa>.3mf` (samo `manage_woocommerce`).
- Generator modela → „Za štampu”: CS kartica sa „Skini 3MF mape”, „Štampaj podlogu A4”, „Skini A4 (PNG)”;
  stavka nestaje kad su skinuti i 3MF i podloga. CS stavke ne ulaze u „Skini sve nove u jedan 3MF”.
- 3MF fajlovi idu u glavni plugin: `assets/cs/3mf/*.3mf` (+ `.htaccess` deny; štiti samo na Apache/LiteSpeed).
  Plugin zip je zato ~10 MB. Korisnik je rekao da mu je svejedno gde su, bitno da ih skida uz porudžbinu.

## Migracija i uvoz
- `bumbaru_migrate_265` (setup.php): jednom doda dodatke `ram-a4` (350), `foto-papir-a4` (60) i filament `pla-bez`
  u postojeće opcije; uključuje poruku za uvoz. Uvoz pravi PK-02 iz `data/products.json` (novi ključ `calc`).
- Fotografije (4) su u dodatku `bumbaru-3d-fotografije` 2.6.5 (`import/p/cs2-mapa-u-ramu/`, gitignored) i u
  `public/img/p/cs2-mapa-u-ramu/`. Jedna („Gaming Desk Flat Lay”) deluje kao AI render — korisnik odlučuje.
- Astro podaci **nisu** usklađeni (samo WP).

## 3D mape (van repoa, na korisnikovom disku)
`E:\BUMBARU 3D Stampa\sve-za-cs-mape-proizvod\gotovo\` — `3mf/` (7 fajlova za štampu), `pregled/` (PNG pogled
odozgo, providna pozadina, 10 px/mm), `izvestaji/` (dimenzije, masa, šta je dodato), `primeri-stampe/` (PDF/PNG podloga).
- Filamenti: 1 bež #E6CBA0 (mapa), 2 crvena #C8102E (A/B slova, T spawn mrežica), 3 tamno plava #1B2A5C
  (CT spawn mrežica + po neki akcenat), 4 akcenat (zelena #2F5233 / braon #6B4226 / bela). Uvek najviše 4 (AMS).
- Mrežica spawna: dijagonalna rešetka, linije 0,8 mm, razmak 3 mm, 0,5 mm iznad poda.
- Dust II (iz 1.6 modela, prepravljen ka CS2 po korisnikovim oznakama), Mirage i Inferno imaju nivelaciju; Ancient,
  Cache, Nuke, Vertigo su ravne ekstruzije (nivelacija moguća samo iz CS2 .nav/geometrije — v. izvestaji/ravne-mape.md).
- Nijedan 3MF još nije otvoren u Bambu Studiu — proveriti boje/AMS pre prve štampe. Visina reljefa 7,5–23 mm:
  proveriti da staje u ram ispod stakla.
- Pregledi za sajt: `node scripts/cs-mape.mjs` (iz korena repoa) čita `gotovo/pregled/*.png` i `gotovo/3mf/*.3mf` i
  piše `assets/cs/` (mapa-*.webp, logoi, sivi logoi, mape.json sa merama u mm, 3mf/). Svih 7 pregleda je renderovano
  istim kodom (`scripts/cs-mape-3d/render7/render7.py`), isti ton poda (214,189,149), blaga senka ≤2,5 mm.
- `scripts/cs-mape-3d/` sadrži Python skripte kojima su modeli i pregledi pravljeni (trimesh, shapely, manifold3d,
  numpy, PIL; korisnik ima venv `~/.venvs/rembg`). Putanje u skriptama pokazuju na stari privremeni folder — prilagodi.

## Otvoreno
- **Licence izvornih modela (MakerWorld):** Dust II BY-NC-SA (autor 大琢磨) — „NC” zabranjuje prodaju; Mirage
  SmoTech3D i set ravnih mapa skeymo — „Standard Digital File License”. Korisnik treba dozvolu autora pre prodaje.
- Logoi mapa i „Counter-Strike 2” su Valve-ovi znaci (korisnikova odluka).
- Cache: pozicije sanduka približne (nema CS2 radara).
