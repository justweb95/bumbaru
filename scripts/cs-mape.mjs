// CS2 mapa u ramu: slike za konfigurator (plugin assets/cs/).
//   logo-<id>.webp       – logo mape u boji (360 px), za čip i medaljon na podlozi
//   logo-<id>-siva.webp  – isti logo u svetlo sivoj (900 px), velika pozadina iza 3D mape
//   mapa-<id>.webp       – 3D mapa odozgo, providna pozadina (900 px), iz gotovo/pregled/<id>.png (nivelisana, finalna),
//                          a dok nje nema iz <id>-ravna.png; ako nema nijedne, konfigurator crta pločicu (placeholder)
//   mape.json            – stvarne mere mape u mm { id: [širina, visina] }: PNG je 10 px po mm (najduža strana 160 mm),
//                          pa pregled i obris na štampi imaju pravu veličinu na A4
//   cs-znak.webp         – znak levo gore na MVP podlozi, iz izvornog fajla cs2-znak.png/webp (bez njega: nišan)
//   3mf/<id>.3mf         – gotov 3MF mape za štampu, iz gotovo/3mf/<id>.3mf; folder je zaključan (.htaccess),
//                          administrator ga skida preko admin-post.php?action=bumbaru_cs_3mf
//
// Upotreba: node scripts/cs-mape.mjs ["/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod"]
// Na kraju ispiše koje slike mapa fale. Ponovo pokrenite kad drugi stignu (prepisuje stare).
import sharp from 'sharp';
import { copyFileSync, existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const SRC = process.argv[2] || '/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod';
const OUT = join(ROOT, 'wordpress/plugins/bumbaru-3d/assets/cs');
mkdirSync(OUT, { recursive: true });

// id (isti kao u includes/csmapa.php) → izvorni logo
const MAPE = {
  dust2: 'dust-2-logo.webp',
  mirage: 'Mirage-logo.webp',
  inferno: 'inferno-logo.webp',
  ancient: 'ancient-logo.webp',
  cache: 'Cache.webp',
  nuke: 'Nuke-logo.webp',
  vertigo: 'Vertigo-logo.webp',
};
const prvi = (...f) => f.find((x) => existsSync(x));

/** Svetlo siva verzija: luminansa → 178..248 (tamno postaje srednje sivo, belo ostaje skoro belo), providnost ostaje. */
async function siva(file, size) {
  const { data, info } = await sharp(file).ensureAlpha().resize(size, size, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } }).raw().toBuffer({ resolveWithObject: true });
  for (let i = 0; i < data.length; i += 4) {
    const l = 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2];
    const v = Math.round(178 + (l / 255) * 70);
    data[i] = data[i + 1] = data[i + 2] = v;
  }
  return sharp(data, { raw: info }).webp({ quality: 82, alphaQuality: 90 }).toBuffer();
}

// Stvarna najduža strana štampane mape (gotovo/izvestaji/*.md). Sve mape se štampaju sa najdužom stranom 160 mm.
const NAJDUZA = {}; // npr. { ancient: 170 } ako neka mapa odstupi od 160 mm
const mere = {};
const fale = [];
for (const [id, logo] of Object.entries(MAPE)) {
  const lf = join(SRC, logo);
  if (existsSync(lf)) {
    writeFileSync(join(OUT, `logo-${id}.webp`), await sharp(lf).resize(360, 360, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } }).webp({ quality: 86, alphaQuality: 90 }).toBuffer());
    writeFileSync(join(OUT, `logo-${id}-siva.webp`), await siva(lf, 900));
  } else fale.push(`logo ${id} (${logo})`);
  const pf = prvi(join(SRC, 'gotovo/pregled', `${id}.png`), join(SRC, 'gotovo/pregled', `${id}-ravna.png`));
  if (pf) {
    const mt = await sharp(pf).metadata();
    // Najduža strana mape u mm (iz izveštaja 3D modela); podrazumevano 160 mm.
    const k = (NAJDUZA[id] || 160) / Math.max(mt.width, mt.height);
    mere[id] = [Math.round(mt.width * k * 10) / 10, Math.round(mt.height * k * 10) / 10];
    writeFileSync(join(OUT, `mapa-${id}.webp`), await sharp(pf).resize(900, 900, { fit: 'inside' }).webp({ quality: 84, alphaQuality: 90 }).toBuffer());
    console.log('mapa', id, mere[id].join(' × ') + ' mm', '←', pf);
  } else fale.push(`mapa ${id} (gotovo/pregled/${id}.png)`);
}
// 3MF mapa: samo za administraciju (direktan pristup folderu je zabranjen)
mkdirSync(join(OUT, '3mf'), { recursive: true });
writeFileSync(join(OUT, '3mf/.htaccess'), '# Samo preko administracije (admin-post.php?action=bumbaru_cs_3mf)\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n');
writeFileSync(join(OUT, '3mf/index.php'), '<?php // Tiho.\n');
for (const id of Object.keys(MAPE)) {
  const f = join(SRC, 'gotovo/3mf', `${id}.3mf`);
  if (existsSync(f)) copyFileSync(f, join(OUT, '3mf', `${id}.3mf`));
  else fale.push(`3mf ${id} (gotovo/3mf/${id}.3mf)`);
}
writeFileSync(join(OUT, 'mape.json'), JSON.stringify(mere, null, 1) + '\n');
const znak = prvi(join(SRC, 'cs2-znak.png'), join(SRC, 'cs2-znak.webp'));
if (znak) writeFileSync(join(OUT, 'cs-znak.webp'), await sharp(znak).resize(300, 300, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } }).webp({ quality: 88 }).toBuffer());
console.log(fale.length ? 'Fale (konfigurator crta obris dok ih nema):\n  ' + fale.join('\n  ') : 'Sve slike su tu.');
