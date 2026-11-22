# Bumbaru – WordPress (produkcija)

Ovo je kod koji je trenutno online (paket „Bumbaru_paket_2.0_final”, 20. 11. 2026):

- `theme/bumbaru/` – tema „Bumbaru” 2.0.0 → `wp-content/themes/bumbaru/`
- `plugins/bumbaru-3d/` – plugin „Bumbaru 3D” 2.0.0 → `wp-content/plugins/bumbaru-3d/`
- `plugins/bumbaru-3d-fotografije/` – samo PHP fajl dodatka za uvoz fotografija.
  Folder `import/` (≈54 MB fotografija) nije u gitu; fotografije su iste kao u `public/img/`
  Astro sajta i već su uvezene u Medije na sajtu.
- `UPUTSTVO.txt` – postupak postavljanja, `Bumbaru_proizvodi_i_cene.csv` – svih 62 proizvoda.

Astro sajt u korenu repozitorijuma je izvor dizajna i podataka (`src/data`, `src/lib`);
plugin ih ima prenete u `plugins/bumbaru-3d/data/`.

Pravljenje zip-ova za otpremanje:
```bash
cd wordpress/theme   && zip -r ../../bumbaru-tema.zip bumbaru
cd ../plugins        && zip -r ../../bumbaru-3d-plugin.zip bumbaru-3d
```
