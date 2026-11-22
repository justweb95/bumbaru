# Bumbaru 3D (plugin)

Konfiguratori proizvoda, boje, filamenti i kalkulator cene 3D štampe, uvoz proizvoda sa starog sajta
i podešavanje prodavnice (plaćanje pouzećem, besplatna dostava Post Express). Radi uz WooCommerce
i temu „Bumbaru”.

## Prvo pokretanje
1. Instalirajte WooCommerce, temu **Bumbaru** i ovaj plugin.
2. Bumbaru 3D → **Prodavnica i uvoz** → „Podesi prodavnicu”, pa „Uvezi proizvode”.
3. Upišite cene: Proizvodi → proizvod → Opšte → Redovna cena (ili Bumbaru 3D → Kalkulator cene).

## Gde se šta menja
| Šta | Gde |
| --- | --- |
| Cena, akcijska cena (bedž −10% se pojavi sam) | Proizvod → Opšte |
| Ime, motiv, boje, zvezdica na jelki, veličine, opcije sa doplatom | Proizvod → kartica „Konfigurator” |
| Uvod, karakteristike, pakovanje/dostava, česta pitanja, SEO | Proizvod → kartica „Bumbaru” |
| Cena iz grama filamenta i vremena štampe | Bumbaru 3D → Kalkulator cene, ili okvir „Kalkulator cene” u proizvodu |
| Boje u konfiguratorima | Bumbaru 3D → Boje |
| Cene filamenta, struje, rada, zarada, zaokruživanje | Bumbaru 3D → Filamenti i troškovi |
| Traka na vrhu, Messenger, Facebook, Instagram, sezona | Bumbaru 3D → Podešavanja sajta |
| Tekst, pozadina, filteri i pitanja kategorije | Proizvodi → Kategorije → kategorija |
| Proizvodi na početnoj strani | Proizvod → Bumbaru → „Na početnoj, mesto” (1–9) |

## Opcije proizvoda
Jedna opcija po redu: `Naziv = vrednost | vrednost | vrednost`. Doplata ili umanjenje na kraju vrednosti:
`Veličina = 15 cm | 10 cm -200 | 7 cm -400`. Cena proizvoda važi za prvu vrednost. Opcija „Veličina”
sa centimetrima smanjuje jelku u pregledu uživo i pokazuje visinu.

## Dva načina poručivanja
Svaki proizvod sa cenom ima dva dugmeta: „Poruči preko sajta” (korpa → plaćanje pouzećem, besplatna
dostava Post Express) i „Poruči preko Messengera” (poruka sa nazivom, šifrom, izborom kupca, cenom i linkom
se složi sama). Dok proizvod nema cenu, ima samo „Poruči preko Messengera”.

## Generator modela (3MF / STL)
Bumbaru 3D → **Generator modela**, ili u porudžbini dugme **„Preuzmi 3MF”** uz stavku sa ukrasom.
- Model je isti crtež kao pregled na sajtu (`data/ornament-defs.svg`) i slova fonta Pacifico (`assets/gen/Pacifico-Regular.ttf`).
- 3MF: svaki ukras je predmet sa delovima „Telo” i „Ime”, svaki sa svojom bojom; u slajseru dodelite filament delu.
- Mere (prečnik, debljina, izdignuto ime, boja imena samo na vrhu, ploča) se pamte dugmetom „Zapamti ove mere”.
- Novi model: dodajte ga u `BumbaruGen.models` (`assets/gen/bumbaru-gen.js`, vidi `models.ukras`) i u `bumbaru_gen_models()` (`includes/generator.php`).

## Popust za više komada
Proizvod → Konfigurator → „Popust za više komada” (npr. `2=5, 3=10, 5=15`). Sabiraju se svi komadi tog proizvoda
u korpi; na strani proizvoda pčelica na traci pokazuje koliko još do popusta. Za ukras sa imenom podrazumevano je `2=5, 3=10, 5=15`.

## Srpski jezik
Posle nadogradnje plugin sam postavi jezik sajta na srpski i preuzme prevode; zvanični (ćirilični) prevodi se prikazuju
latinicom. Ponovo: Bumbaru 3D → Podešavanja sajta → „Prebaci na srpski i osveži prevode”.
