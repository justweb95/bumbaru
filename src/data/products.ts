// Proizvodi. Podaci su u src/data/proizvodi.json i menjaju se iz admin panela (proizvodi, cene, slike, tekstovi).
// Slike: /public/img/p/<slug>/<ime>-lg.webp (cela), -sm.webp (manja), -sq.webp (kvadrat za kartice, sa žigom);
//        og: slika za deljenje (1200 × 630), koristi se ona od prve fotografije.
// Boje: colors: [{ id, label, def }] nudi boje iz admin panela (src/data/boje.json).
// Cene: price je broj (npr. 390). Prikazuju se tek kad je u admin panelu uključeno „Prikaži cene na sajtu”.
// Konfiguratori (personal, keychain) se ne menjaju iz admina; njihov izgled je u src/lib.

import type { Faq } from './categories';
import data from './proizvodi.json';

export interface Img { src: string; w: number; h: number; alt: string; og?: string }
// cene: cena za svaku vrednost (isti redosled kao values); 0 = važi osnovna cena proizvoda
export interface Option { id: string; label: string; values: string[]; cene?: number[] }
// Istaknut poziv na Messenger (npr. „pišite nam za celu kolekciju”)
export interface Poziv { tekst: string; dugme: string; poruka?: string }
export type Personal = 'ukras' | 'set' | 'privezak';
// Izbor boje iz liste boja filamenta (admin panel); def = id podrazumevane boje
export interface ColorChoice { id: string; label: string; def: string }
// Privezak sa imenom: stilovi slova (src/lib/keychain.ts) i broj imena (svako ime je jedan privezak)
// Izbor motiva (npr. modlice): kupac klikne motive koje želi, može i više komada istog;
// popust zavisi od ukupnog broja komada (od: najmanje komada, procenat: popust na cenu po komadu).
// vrsta 'boje': kupac bira boje filamenta (sve boje na stanju) i broj komada svake, umesto motiva;
// silueta = crtež proizvoda koji se oboji u svaku boju (src/lib/siluete.ts), bez nje se prikazuje krug boje.
// cena: cena po komadu baš tog motiva (npr. veći medaljon); bez nje važi osnovna cena proizvoda
export interface Motiv { id: string; naziv: string; slika?: Img; cena?: number; cene?: number[] } // cene: po vrednosti opcije sa cenom (npr. veličina), 0 = srazmerno
export interface MotiviCfg { lista: Motiv[]; popusti: { od: number; procenat: number }[]; vrsta?: 'boje'; silueta?: string }
// Komplet „Uz ovo često uzimaju”: drugi proizvodi (adrese) i popust na ukupnu cenu kompleta
export interface Komplet { uz: string[]; popust: number }
// Popusti za firme (velike količine): od koliko komada i procenat; tekst se prikazuje u kartici
export interface Firme { popusti: { od: number; procenat: number }[]; tekst?: string }
export interface KeychainCfg { stilovi: string[]; startImena: number; minImena: number; maxImena: number; sareno?: boolean; slike?: Record<string, number> } // slike: koja fotografija prikazuje koji stil

export interface Product {
  id: string; // šifra koja ide u poruku
  slug: string;
  title: string;
  category: string; // glavna kategorija (za putanju)
  categories: string[]; // sve kategorije u kojima se prikazuje
  tags?: string[]; // filteri u kategoriji Nova godina
  short: string; // tekst na kartici
  lead: string; // uvod ispod naslova na strani proizvoda
  custom?: boolean; // pravi se po porudžbini (oznaka „Po meri · 3–5 dana”)
  price?: number;
  priceFrom?: boolean;
  images: Img[];
  options?: Option[];
  colors?: ColorChoice[];
  personal?: Personal;
  keychain?: KeychainCfg;
  motivi?: MotiviCfg;
  poziv?: Poziv;
  komplet?: Komplet;
  firme?: Firme;
  crtez?: string; // crtež u izabranim bojama (src/lib/crtezi.ts); delovi se boje redom izbora boja
  about: string[];
  specs: [string, string][];
  details: Faq[];
  faq: Faq[];
  related: string[];
  skriven?: boolean; // sakriven sa sajta (ostaje u adminu)
  seoTitle: string;
  meta: string;
}

// Svi proizvodi iz admina; sajt prikazuje samo one koji nisu sakriveni.
export const SVI_PROIZVODI = (data as unknown as { proizvodi: Product[] }).proizvodi;
export const PRODUCTS: Product[] = SVI_PROIZVODI.filter((x) => !x.skriven);

export const productBySlug = (slug: string) => PRODUCTS.find((x) => x.slug === slug);
export const productUrl = (slug: string) => `/proizvod/${slug}/`;
export const inCategory = (cat: string) => PRODUCTS.filter((x) => x.categories.includes(cat));
// Kupac bira komade (motive ili boje) sa popustom po broju komada
export const izborKomada = (x: Product) => !!x.motivi && (x.motivi.vrsta === 'boje' || x.motivi.lista.length > 0);
export const hasPhoto = (x: Product) => x.images.length > 0 || !!x.motivi?.lista.some((m) => m.slika);
// Kvadratna slika za kartice (fotografije imaju posebnu kvadratnu verziju sa žigom, crteži su već kvadratni)
export const cardImg = (i: Img) => (i.src.startsWith('/img/p/') ? `${i.src}-sq.webp` : `${i.src}-sm.webp`);

// Izbor motiva: popust za dati broj komada (0 ako nema) i ukupna cena zaokružena na 10 din
export const popustZa = (m: MotiviCfg, n: number) => m.popusti.filter((x) => n >= x.od).reduce((a, x) => Math.max(a, x.procenat), 0);
export const cenaMotiva = (cena: number, m: MotiviCfg, n: number) => Math.round((n * cena * (1 - popustZa(m, n) / 100)) / 10) * 10;
// Motivi sa sopstvenom cenom (npr. medaljoni skuplji od osnovnih modlica)
export const raznaCenaMotiva = (x: Product) => !!x.price && x.motivi?.vrsta !== 'boje' && !!x.motivi?.lista.some((m) => m.cena && m.cena !== x.price);

// Cene po opcijama: sve cene koje kupac može da dobije (za „od … din” i Google)
export const sveCene = (x: Product) => {
  const opc = (x.options || []).find((o) => o.cene?.some((c) => c > 0));
  if (!opc) return x.price ? [x.price] : [];
  return opc.values.map((_, i) => opc.cene![i] || x.price || 0).filter((c) => c > 0);
};
