# Bumbaru — CS2 mapa u ramu: zajednički brief za 3D agente

Proizvod: A4 ram (crni) sa odštampanom glossy A4 podlogom (logo mape u sivoj, natpis, "MOST VALUABLE PLAYER" + ime igrača),
a preko podloge je zalepljena 3D štampana mapa iz Counter-Strike 2 (pogled odozgo, reljef zidova).
Primer gotovog proizvoda: "/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/cs-mapa-slike jedinog proizvoda koje imamo on ide na sajt/cs2_gift_photo_color_light_edit.jpg"
Primeri obojenih mapa (ciljni izgled; ALI spawn zone NISU pune boje nego MREŽICA u boji):
  ".../sve-za-cs-mape-proizvod/primer obojenog dust-a ...png", "primer obojenog inferno-a ...png", "primer obojenog mirage-a ...png"

## Boje (filamenti) — svaki deo mora biti zaseban "part" dodeljen filamentu
1 = bež, "kao koža" (cela mapa)            render #E6CBA0
2 = crvena  (slova A i B, T spawn mrežica)  render #C8102E
3 = tamno plava (CT spawn mrežica)          render #1B2A5C
4 = tamno zelena                            render #2F5233
5 = braon                                   render #6B4226
6 = bela                                    render #F4F1EA
Akcenat (2–3 elementa po mapi, npr. kutije/sanduci, auto, drveće/žbunje, vrata, bure) u zelenoj, braon, crvenoj ili beloj — šta se uklapa za tu mapu i prepoznatljivo je igračima.
Ne koristi više od 4–5 filamenata po mapi (AMS ima 4 slota — idealno: bež, crvena, plava + jedan akcenat; peti samo ako mnogo dobija).

## Elementi
- A i B: podignuta slova (bold, bez serifa, Montserrat ExtraBold postoji u /var/www/html/bumbaru/wordpress/plugins/bumbaru-3d/assets/gen/Montserrat-ExtraBold.ttf), na podu bombsajta na TAČNOJ lokaciji (proveri pravi radar CS2 mape na internetu / iz znanja), visina ~0.6–0.8 mm iznad poda, veličina čitljiva (≈6–10 mm zavisno od razmere), ne seći zidove.
- T spawn i CT spawn: "mrežica" = dijagonalna unakrsna rešetka (45°/135°) linija ~0.8 mm širine, razmak ~3 mm, visine 0.4–0.6 mm iznad poda, isečena na oblik poda spawn zone (ne preko zidova). T crvena, CT tamno plava.
- 2–3 akcentna elementa (vidi gore), mala, na stvarnim lokacijama (npr. Dust II: auto kod Long-a/kutije na A, Mirage: kutije na A sajtu/ jungle, Inferno: banane/drveće, bure...). Mogu biti obojeni postojeći oblici (ako je geometrija već tu, izdvoj ih kao zaseban part u boji) ili novi mali oblici.
- Elementi moraju biti štampljivi: bez prevesa, min. debljina 0.8 mm, svi delovi leže na postojećoj površini (Z dodira = Z poda na tom mestu — uzmi visinu poda iz mreže ray-castom).

## Izlaz (piši ovde, ime fajla po mapi: dust2, inferno, mirage, ancient, cache, nuke, vertigo)
- 3MF: "/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/3mf/<mapa>.3mf" — jedan objekat sastavljen od delova (parts), svaki deo dodeljen filamentu (Bambu Studio / OrcaSlicer format: Metadata/model_settings.config sa <part ... > i "extruder" vrednostima, + Metadata/project_settings.config sa filament_colour listom ako možeš). Mora se otvoriti u Bambu Studiu kao jedan model sa obojenim delovima. Bez velike ploče/postolja ispod — samo mapa (ravna donja strana na Z=0).
- Pregled za sajt: "/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/pregled/<mapa>.png" — pogled ODOZGO (ortografski), providna pozadina, 1600 px po dužoj strani, osenčeno (svetlo spreda-levo-gore, ambient occlusion/senke po visini da se vidi reljef zidova), u render bojama iznad. Treba da izgleda kao fotografija štampe odozgo, jer ide preko podloge u konfiguratoru na sajtu.
- Izveštaj: "/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/izvestaji/<mapa>.md": dimenzije (mm), zapremina i procena grama (PLA 1.24 g/cm3, ~15% ispune + 2 zida — ili odnos prema Dust2 MVP verziji koja je 45 g), koje je elemente dodao gde, šta nije sigurno.

## Alati
Python sa trimesh, shapely, manifold3d, scipy, numpy, PIL: `~/.venvs/rembg/bin/python -I skripta.py`. Ne instaliraj ništa drugo.
Skripte i međufajlove drži u svom folderu u scratchpad-u (/tmp/claude-1000/-var-www-html-bumbaru-wordpress-theme-bumbaru/14849afa-50c8-42cc-a153-49ff5bc01e2e/scratchpad/<mapa>/). Originalne 3MF fajlove NE MENJAJ (samo čitaj).
3MF je zip: 3D/3dmodel.model + 3D/Objects/*.model (XML <vertex x y z>, <triangle v1 v2 v3>), + transformacije u <build><item transform> i <component transform>. Neki fajlovi su ogromni (Inferno object_10.model 162 MB) — parsiraj brzo (regex/np.fromstring ili iterparse), ne DOM.
Vizuelno proveri svoj rad: renderuj PNG i pogledaj ga (Read alat čita slike). Uporedi sa pravim radarom mape. Iteriraj dok ne izgleda kao poklon koji se prodaje.

Na kraju vrati KRATAK izveštaj (putanje fajlova, dimenzije, grami, šta je urađeno, šta je ostalo nesigurno). Odgovaraj na srpskom (latinica).
