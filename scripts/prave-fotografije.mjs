// Prave fotografije proizvoda: ista obrada kao u adminu (src/admin/slike.ts), ali u Node-u (sharp).
//   lg  – cela fotografija, duža strana najviše 1200 px, logo dole levo
//   sm  – ista, 640 px širine (iz lg, sa logom)
//   sq  – kvadrat 900 × 900 iz sredine, logo dole levo
//   og  – 1200 × 630 JPEG: zamagljena traka kvadrata u pozadini, kvadrat na sredini
// Logo: tamni na svetlom delu fotografije, svetli na tamnom (svetlina ugla gde ide logo).
// Fajlovi: public/img/p/<slug>/u<9 heksa>-{lg,sm,sq}.webp i -og.jpg (kao otpremanje iz admina).
//
// Upotreba: node scripts/prave-fotografije.mjs <folder sa mapa.json> <izlaz.json>
// Ponovno pokretanje ne pravi duplikate: fotografije koje su već u izlaz.json se preskaču.
import sharp from 'sharp';
import { randomBytes } from 'node:crypto';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const [, , ULAZ, IZLAZ] = process.argv;
if (!ULAZ || !IZLAZ) { console.error('node scripts/prave-fotografije.mjs <folder> <izlaz.json>'); process.exit(1); }

// Ispravke mape (dogovoreno uz zadatak): jelka na postolju je nov proizvod, ne Jelkica (pleteni izgled).
const PREUSMERI = (f, slug) => (/jelka-sa-postoljem\//.test(f) || /komplet-(9|10)\.jpg$/.test(f) ? 'jelka-sa-postoljem' : slug);

const mapa = JSON.parse(readFileSync(join(ULAZ, 'mapa.json'), 'utf8'));
const gotovo = existsSync(IZLAZ) ? JSON.parse(readFileSync(IZLAZ, 'utf8')) : [];
const LOGO = { tamni: join(ROOT, 'public/img/brand/logo-tamni.png'), svetli: join(ROOT, 'public/img/brand/logo-svetli.png') };
const logoMeta = await sharp(LOGO.tamni).metadata();

// Logo dole levo (kao zig() u slike.ts): širina 20% kraće strane, razmak 2,8%.
async function saLogom(buf, w, h) {
  const m = Math.min(w, h);
  const lw = Math.round(m * 0.2);
  const lh = Math.round((lw * logoMeta.height) / logoMeta.width);
  const x = Math.round(m * 0.028), y = Math.round(h - m * 0.028 - lh);
  const { data } = await sharp(buf).extract({ left: x, top: y, width: lw, height: lh }).removeAlpha().raw().toBuffer({ resolveWithObject: true });
  let s = 0, n = 0;
  for (let i = 0; i < data.length; i += 12) { s += (0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2]) / 255; n++; } // svaki 4. piksel
  const logo = await sharp(s / n < 0.5 ? LOGO.svetli : LOGO.tamni).resize(lw, lh).toBuffer();
  return sharp(buf).composite([{ input: logo, left: x, top: y }]).png().toBuffer();
}

async function obradi(fajl) {
  const src = sharp(fajl).rotate();
  const { width: W, height: H } = await src.metadata();
  const orig = await src.png().toBuffer();
  const k = Math.min(1, 1200 / Math.max(W, H));
  const w = Math.round(W * k), h = Math.round(H * k);
  const lgPng = await saLogom(await sharp(orig).resize(w, h).png().toBuffer(), w, h);
  let lg = await sharp(lgPng).webp({ quality: 86 }).toBuffer();
  if (lg.length > 1_400_000) lg = await sharp(lgPng).webp({ quality: 74 }).toBuffer();
  const sw = Math.min(640, w), sh = Math.round((h * sw) / w);
  const sm = await sharp(lgPng).resize(sw, sh).webp({ quality: 84 }).toBuffer();
  const m = Math.min(W, H), S = 900;
  const sqPng = await saLogom(await sharp(orig).extract({ left: Math.round((W - m) / 2), top: Math.round((H - m) / 2), width: m, height: m }).resize(S, S).png().toBuffer(), S, S);
  const sq = await sharp(sqPng).webp({ quality: 84 }).toBuffer();
  // og: srednja traka kvadrata, razvučena malo preko ivica i zamagljena; preko nje kvadrat 630 × 630
  const th = Math.round((S * 630) / 1200);
  const pozadina = await sharp(sqPng).extract({ left: 0, top: Math.round((S - th) / 2), width: S, height: th }).resize(1320, 710, { fit: 'fill' }).blur(28).extract({ left: 60, top: 40, width: 1200, height: 630 }).png().toBuffer();
  const belo = await sharp({ create: { width: 1200, height: 630, channels: 4, background: { r: 255, g: 255, b: 255, alpha: 0.12 } } }).png().toBuffer();
  const og = await sharp(pozadina).composite([{ input: belo, left: 0, top: 0 }, { input: await sharp(sqPng).resize(630, 630).toBuffer(), left: 285, top: 0 }]).jpeg({ quality: 84 }).toBuffer();
  return { lg, sm, sq, og, w, h };
}

const out = [...gotovo];
for (const s of mapa.slike) {
  if (out.some((x) => x.fajl === s.fajl)) continue;
  const slug = PREUSMERI(s.fajl, s.proizvod);
  const r = await obradi(join(ULAZ, s.fajl));
  const id = 'u' + randomBytes(5).toString('hex').slice(0, 9);
  const dir = join(ROOT, 'public/img/p', slug);
  mkdirSync(dir, { recursive: true });
  writeFileSync(join(dir, `${id}-lg.webp`), r.lg);
  writeFileSync(join(dir, `${id}-sm.webp`), r.sm);
  writeFileSync(join(dir, `${id}-sq.webp`), r.sq);
  writeFileSync(join(dir, `${id}-og.jpg`), r.og);
  const src = `/img/p/${slug}/${id}`;
  out.push({ fajl: s.fajl, slug, src, og: `${src}-og.jpg`, w: r.w, h: r.h, alt: s.alt, ...(s.motiv ? { motiv: s.motiv } : {}) });
  writeFileSync(IZLAZ, JSON.stringify(out, null, 1));
  console.log(slug.padEnd(36), id, s.fajl);
}
console.log('Gotovo:', out.length, 'fotografija');
