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
