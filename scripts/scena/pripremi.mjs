// Priprema slika za pregled seta (WP plugin assets/scena/) od isečenih figura (iseci.py).
// node scripts/scena/pripremi.mjs <folder sa isečenim PNG> [izlazni folder]
// - lutkice: isečena fotografija (WebP sa providnošću)
// - tacna i jelke: „senka” za bojenje u pregledaču: R = osvetljenje (normalizovano po delu), G = deo
//   (255 = prva boja: tacna / jelka, 0 = druga boja: zvezda / postolje i vrh), A = providnost
// - pozadina: fotografija stola sa Sneškom, Sneško uklonjen (popunjeno po redovima), blago zamućena
import sharp from '../../node_modules/sharp/lib/index.js';
import fs from 'node:fs';
import path from 'node:path';

const IN = process.argv[2];
const OUT = process.argv[3] || new URL('../../wordpress/plugins/bumbaru-3d/assets/scena/', import.meta.url).pathname;
const SRC = '/mnt/e/BUMBARU 3D Stampa/sajt-slike/';
fs.mkdirSync(OUT, { recursive: true });

const VISINA = 640; // px, visina figure posle sečenja
const BOJENE = { tacna: 'jedna', spiralna: 'dve', jelka: 'dve' };
const OKRENI = { tacna: 6 }; // tacna je na fotografiji nakrivljena (stepeni, u smeru kazaljke)

async function trimuj(f, ugao) {
  // ispravi nagib, obreži providne ivice (sa malo vazduha)
  if (ugao) f = await sharp(f).ensureAlpha().rotate(ugao, { background: { r: 0, g: 0, b: 0, alpha: 0 } }).png().toBuffer();
  const img = sharp(f).ensureAlpha();
  const { data, info } = await img.clone().raw().toBuffer({ resolveWithObject: true });
  let x0 = info.width, y0 = info.height, x1 = 0, y1 = 0;
  for (let y = 0; y < info.height; y++) for (let x = 0; x < info.width; x++) {
    if (data[(y * info.width + x) * 4 + 3] > 24) { if (x < x0) x0 = x; if (x > x1) x1 = x; if (y < y0) y0 = y; if (y > y1) y1 = y; }
  }
  const pad = 4;
  x0 = Math.max(0, x0 - pad); y0 = Math.max(0, y0 - pad); x1 = Math.min(info.width - 1, x1 + pad); y1 = Math.min(info.height - 1, y1 + pad);
  return sharp(f).ensureAlpha().extract({ left: x0, top: y0, width: x1 - x0 + 1, height: y1 - y0 + 1 });
}

function hsv(r, g, b) {
  const mx = Math.max(r, g, b), mn = Math.min(r, g, b), d = mx - mn;
  let h = 0;
  if (d) h = mx === r ? ((g - b) / d) % 6 : mx === g ? (b - r) / d + 2 : (r - g) / d + 4;
  return [(h * 60 + 360) % 360, mx ? d / mx : 0, mx / 255];
}

async function senka(ime, vrsta, pipe, sirina) {
  const { data, info } = await pipe.raw().toBuffer({ resolveWithObject: true });
  const n = info.width * info.height, deo = new Uint8Array(n), lum = new Float32Array(n);
  for (let i = 0; i < n; i++) {
    const r = data[i * 4], g = data[i * 4 + 1], b = data[i * 4 + 2];
    lum[i] = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
    if (vrsta === 'jedna') { deo[i] = 1; continue; }
    const [h, s] = hsv(r, g, b);
    // zeleno (jelka) = prvi deo; bakarno/narandžasto (zvezda, postolje, vrh) = drugi deo
    deo[i] = s < 0.12 ? 1 : (h >= 70 && h <= 210 ? 1 : 0);
  }
  // izgladi delove: većina u 5×5 okolini (bez šuma na ivicama)
  if (vrsta !== 'jedna') {
    const w = info.width, h = info.height, d2 = new Uint8Array(n);
    for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) {
      let a = 0, c = 0;
      for (let dy = -2; dy <= 2; dy++) for (let dx = -2; dx <= 2; dx++) {
        const xx = x + dx, yy = y + dy;
        if (xx < 0 || yy < 0 || xx >= w || yy >= h) continue;
        const j = yy * w + xx;
        if (data[j * 4 + 3] < 24) continue;
        a += deo[j]; c++;
      }
      d2[y * w + x] = c ? (a * 2 >= c ? 1 : 0) : deo[y * w + x];
    }
    deo.set(d2);
  }
  // osvetljenje po delu: 92. percentil → 1
  const p92 = [0, 1].map((k) => {
    const v = [];
    for (let i = 0; i < n; i++) if (data[i * 4 + 3] > 128 && deo[i] === k) v.push(lum[i]);
    v.sort((a, b) => a - b);
    return v.length ? v[Math.floor(v.length * 0.92)] : 1;
  });
  const out = Buffer.alloc(n * 4);
  for (let i = 0; i < n; i++) {
    const l = Math.min(1, lum[i] / (p92[deo[i]] || 1));
    out[i * 4] = Math.round(l * 255);
    out[i * 4 + 1] = deo[i] ? 255 : 0;
    out[i * 4 + 2] = 0;
    out[i * 4 + 3] = data[i * 4 + 3];
  }
  await sharp(out, { raw: { width: info.width, height: info.height, channels: 4 } }).webp({ lossless: true }).toFile(path.join(OUT, ime + '.webp'));
  console.log(ime, 'senka', info.width + '×' + info.height, 'deo2', deo.filter((x) => !x).length);
}

for (const f of fs.readdirSync(IN).filter((x) => x.endsWith('.png'))) {
  const ime = f.replace('.png', '');
  let pipe = await trimuj(path.join(IN, f), OKRENI[ime] || 0);
  const buf = await pipe.png().toBuffer();
  const meta = await sharp(buf).metadata();
  const visina = ime === 'tacna' ? Math.round(VISINA * 0.6) : VISINA;
  const s = sharp(buf).resize({ height: Math.min(visina, meta.height) });
  if (BOJENE[ime]) await senka(ime, BOJENE[ime], s, meta.width);
  else {
    await s.webp({ quality: 86, alphaQuality: 90 }).toFile(path.join(OUT, ime + '.webp'));
    console.log(ime, 'foto');
  }
}

// Pozadina: Sneško uklonjen sa fotografije (rupa popunjena delom istog reda sa strane).
{
  const POZ = process.env.POZ || 'snesko'; // figura čija fotografija daje pozadinu
  const foto = SRC + { snesko: 'snesko/bumbaru-snesko-1.jpg', spiralna: 'spiralna-jelka/bumbaru-spiralna-jelka-3.jpg', jelka: 'jelka-sa-postoljem/bumbaru-jelka-sa-postoljem-2.jpg' }[POZ];
  const { data, info } = await sharp(foto).removeAlpha().raw().toBuffer({ resolveWithObject: true });
  const w = info.width, h = info.height, c = info.channels;
  const alfa = await sharp(path.join(IN, POZ + '.png')).ensureAlpha().extractChannel(3).resize(w, h).raw().toBuffer();
  // maska: figura (alfa > 10) proširena za R px (kvadratno, dva prolaza), pa meka ivica od F px
  const R = 26, F = 10;
  const bin = new Uint8Array(w * h), tmp = new Uint8Array(w * h), m = new Uint8Array(w * h);
  for (let i = 0; i < w * h; i++) bin[i] = alfa[i] > 10 ? 1 : 0;
  for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) { let v = 0; for (let d = -R; d <= R && !v; d++) { const xx = x + d; if (xx >= 0 && xx < w && bin[y * w + xx]) v = 1; } tmp[y * w + x] = v; }
  for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) { let v = 0; for (let d = -R; d <= R + 30 && !v; d++) { const yy = y + d; if (yy >= 0 && yy < h && tmp[yy * w + x]) v = 1; } m[y * w + x] = v; } // dole više (senka)
  // rupa se popuni kopijom istog reda pomerenom u stranu (drvo i venac idu vodoravno):
  // gore (venac) sa desne strane, dole (sto) sa leve, gde nema kugle ni ukrasa
  let x0 = w, x1 = 0;
  for (let i = 0; i < w * h; i++) if (m[i]) { const x = i % w; if (x < x0) x0 = x; if (x > x1) x1 = x; }
  const orig = Buffer.from(data);
  const px = (x, y, j) => orig[(y * w + Math.min(w - 1, Math.max(0, x))) * c + j];
  // svaki red rupe: glatki prelaz od boje levo do boje desno (drvo ide vodoravno, venac se kasnije zamuti)
  for (let y = 0; y < h; y++) {
    let x = x0;
    while (x <= x1) {
      if (!m[y * w + x]) { x++; continue; }
      let e = x; while (e < w && m[y * w + e]) e++;
      const L = [0, 1, 2].map((j) => { let v = 0; for (let k = 1; k <= 6; k++) v += px(x - k, y, j); return v / 6; });
      const D = [0, 1, 2].map((j) => { let v = 0; for (let k = 0; k < 6; k++) v += px(e + k, y, j); return v / 6; });
      for (let k = x; k < e; k++) { const t = (k - x + 1) / (e - x + 1); for (let j = 0; j < 3; j++) data[(y * w + k) * c + j] = Math.round(L[j] * (1 - t) + D[j] * t); }
      x = e;
    }
  }
  // dubina: gore (venac, kugle, popunjena rupa) jako zamućeno, dole sto oštriji
  const jako = await sharp(data, { raw: { width: w, height: h, channels: c } }).blur(11).raw().toBuffer();
  const srednje = await sharp(data, { raw: { width: w, height: h, channels: c } }).blur(5).raw().toBuffer();
  const out = Buffer.alloc(w * h * c);
  for (let y = 0; y < h; y++) {
    const t = Math.min(1, Math.max(0, (y / h - 0.62) / 0.3)); // 0 = jako zamućeno, 1 = srednje
    for (let x = 0; x < w; x++) for (let j = 0; j < 3; j++) { const i = (y * w + x) * c + j; out[i] = Math.round(jako[i] * (1 - t) + srednje[i] * t); }
  }
  await sharp(out, { raw: { width: w, height: h, channels: c } }).resize(1000, 1000).webp({ quality: 78 }).toFile(path.join(OUT, 'pozadina.webp'));
  console.log('pozadina');
}
