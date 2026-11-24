// Zamena pravih fotografija novom obradom: ista mesta u galeriji, isti alt tekst i motiv, nove datoteke.
// node: npx esbuild scripts/zameni-fotografije.ts --bundle --platform=node --format=esm --outfile=/tmp/z.mjs && node /tmp/z.mjs <stare.json>
// <stare.json> = prethodni scripts/prave-fotografije.json; novi je scripts/prave-fotografije.json (isti fajlovi iz mapa.json).
import { readFileSync, writeFileSync } from 'node:fs';
import { ocistiSve, fajlovi, PUTANJE, type CmsPodaci } from '../src/lib/cms-model';
type Foto = { fajl: string; src: string; og: string; w: number; h: number };
const stare: Foto[] = JSON.parse(readFileSync(process.argv[2], 'utf8'));
const nove: Foto[] = JSON.parse(readFileSync('scripts/prave-fotografije.json', 'utf8'));
const zamena = new Map(stare.map((s) => [s.src, nove.find((n) => n.fajl === s.fajl)!]));
const c = (f: string) => JSON.parse(readFileSync(f, 'utf8'));
const ulaz: Partial<CmsPodaci> = { proizvodi: c(PUTANJE.proizvodi).proizvodi, kategorije: c(PUTANJE.kategorije).kategorije, boje: c(PUTANJE.boje).boje, podesavanja: (({ napomena, ...x }) => x)(c(PUTANJE.podesavanja)) };
let n = 0;
for (const p of ulaz.proizvodi!) {
  p.images = p.images.map((im) => {
    const z = zamena.get(im.src);
    if (!z) return im;
    n++;
    return { ...im, src: z.src, w: z.w, h: z.h, ...(im.og ? { og: z.og } : {}) };
  });
}
const out = ocistiSve(ulaz);
writeFileSync(PUTANJE.proizvodi, fajlovi(out).proizvodi);
// WordPress: iste zamene u data/products.json (putanje bez /img/)
const WP = 'wordpress/plugins/bumbaru-3d/data/products.json';
const wp = c(WP);
const wpPut = (src: string) => src.replace(/^\/img\//, '');
let m = 0;
for (const p of wp) {
  for (const im of p.images) {
    const z = zamena.get('/img/' + im.full.replace(/-lg\.webp$/, ''));
    if (!z) continue;
    m++;
    im.full = wpPut(z.src) + '-lg.webp'; im.card = wpPut(z.src) + '-sq.webp'; im.w = z.w; im.h = z.h;
  }
  const og = p.og && zamena.get('/img/' + p.og.replace(/-og\.jpg$/, ''));
  if (og) p.og = wpPut(og.og);
}
writeFileSync(WP, JSON.stringify(wp, null, 1) + '\n');
console.log('Zamenjeno: Astro', n, '| WordPress', m);
