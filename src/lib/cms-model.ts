// Provera i čišćenje podataka iz admin panela (proizvodi, kategorije, boje, podešavanja).
// Koristi ga i server (/api/cms/) i admin u pregledaču, pa ovde nema ničeg specifičnog za Node.
// Svaka funkcija vraća čist objekat (samo poznata polja, u stalnom redosledu) ili baca CmsGreska.
import type { Product, Img, Option, ColorChoice, KeychainCfg, Personal, MotiviCfg, Poziv, Komplet, Firme } from '../data/products';
import type { Category, Faq } from '../data/categories';
import { SILUETE } from './siluete';
import { CRTEZI } from './crtezi';

export class CmsGreska extends Error {}
const greska = (msg: string): never => { throw new CmsGreska(msg); };

export interface Boja { id: string; naziv: string; hex: string; dostupna: boolean }
// Akcija (npr. novogodišnja): procenat popusta na proizvode iz jedne kategorije, do datuma (uključujući taj dan)
export interface Akcija { ukljucena: boolean; naziv: string; procenat: number; kategorija: string; do: string }
export interface Podesavanja { prikaziCene: boolean; akcija?: Akcija }
export interface CmsPodaci { proizvodi: Product[]; kategorije: Category[]; boje: Boja[]; podesavanja: Podesavanja }

export const PUTANJE = {
  proizvodi: 'src/data/proizvodi.json',
  kategorije: 'src/data/kategorije.json',
  boje: 'src/data/boje.json',
  podesavanja: 'src/data/podesavanja.json',
} as const;

const NAPOMENE = {
  proizvodi: 'Proizvodi. Menja se iz admin panela.',
  kategorije: 'Kategorije kataloga. Menja se iz admin panela.',
  boje: 'Boje filamenta. Menja se iz admin panela. dostupna: false = boja se ne nudi kupcima.',
  podesavanja: 'Podešavanja sajta koja se menjaju iz admin panela.',
};

// Slike otpremljene iz admina: /img/p/<slug>/u<9 heksa znakova> (stare fotografije su /img/p/<slug>/<broj>)
export const ADMIN_SLIKA = /^\/img\/p\/[a-z0-9-]{1,80}\/u[0-9a-f]{9}$/;
const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

export const slugify = (s: string, max = 80) =>
  s.toLowerCase().replace(/đ/g, 'dj').normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, max).replace(/-+$/, '');

// ——— pomoćne provere ———
const tekst = (v: unknown, gde: string, max: number, obavezno = false) => {
  const s = typeof v === 'string' ? v.replace(/\r\n?/g, '\n').trim() : v == null ? '' : greska(`${gde}: mora biti tekst.`);
  if (obavezno && !s) greska(`${gde}: polje je obavezno.`);
  if (s.length > max) greska(`${gde}: najviše ${max} znakova (sada ${s.length}).`);
  return s;
};
const tekst_ = (v: unknown, gde: string, max: number) => tekst(v, gde, max);
const red = (v: unknown, gde: string, max: number, obavezno = false) => tekst(v, gde, max, obavezno).replace(/\s+/g, ' ');
const niz = <T>(v: unknown, gde: string, max: number): T[] => {
  if (v == null) return [];
  if (!Array.isArray(v)) greska(`${gde}: mora biti lista.`);
  const a = v as T[];
  if (a.length > max) greska(`${gde}: najviše ${max} stavki.`);
  return a;
};
const ceo = (v: unknown, gde: string, min: number, max: number) => {
  const n = typeof v === 'number' ? v : typeof v === 'string' && v.trim() !== '' ? Number(v) : NaN;
  if (!Number.isInteger(n) || n < min || n > max) greska(`${gde}: mora biti ceo broj od ${min} do ${max}.`);
  return n;
};
const slugPolje = (v: unknown, gde: string, max = 80) => {
  const s = typeof v === 'string' ? v.trim() : '';
  if (!s || s.length > max || !SLUG.test(s)) greska(`${gde}: dozvoljena su mala slova bez kvačica, brojevi i crtice (npr. mini-jelka).`);
  return s;
};
const pitanja = (v: unknown, gde: string, max = 30): Faq[] =>
  niz<Faq>(v, gde, max)
    .map((f, i) => ({ q: red(f?.q, `${gde}, red ${i + 1} (pitanje/naslov)`, 200), a: tekst(f?.a, `${gde}, red ${i + 1} (odgovor/tekst)`, 2000) }))
    .filter((f) => f.q || f.a)
    .map((f, i) => (f.q && f.a ? f : greska(`${gde}, red ${i + 1}: popunite i naslov i tekst (ili obrišite red).`)));
const pasusi = (v: unknown, gde: string, max = 12) =>
  niz<string>(v, gde, max).map((p, i) => tekst(p, `${gde}, pasus ${i + 1}`, 2000)).filter(Boolean);

// ——— boje ———
export function ocistiBoje(ulaz: unknown): Boja[] {
  const lista = niz<Boja>(ulaz, 'Boje', 60);
  if (!lista.length) greska('Lista boja je prazna.');
  const ids = new Set<string>();
  const out = lista.map((b) => {
    const id = String(b?.id ?? '').trim();
    const naziv = red(b?.naziv, 'Boja', 30);
    const hex = String(b?.hex ?? '').trim().toUpperCase();
    if (!naziv) greska('Boje: svaka boja mora da ima naziv (do 30 znakova).');
    if (!/^[a-z0-9-]{1,30}$/.test(id)) greska(`Boje: neispravna oznaka boje „${naziv}”.`);
    if (ids.has(id)) greska(`Boje: boja „${naziv}” se ponavlja.`);
    if (!/^#[0-9A-F]{6}$/.test(hex)) greska(`Boje: neispravna nijansa za „${naziv}”.`);
    ids.add(id);
    return { id, naziv, hex, dostupna: b?.dostupna !== false };
  });
  if (!out.some((b) => b.dostupna)) greska('Boje: bar jedna boja mora biti na stanju.');
  return out;
}

// ——— podešavanja ———
export function ocistiPodesavanja(v: unknown): Podesavanja {
  const s = v as Podesavanja;
  const out: Podesavanja = { prikaziCene: s?.prikaziCene === true };
  const a = s?.akcija;
  if (a) {
    const datum = String(a.do ?? '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(datum) || Number.isNaN(Date.parse(datum))) greska('Akcija: datum završetka upišite kao 2026-12-31.');
    out.akcija = {
      ukljucena: a.ukljucena === true,
      naziv: red(a.naziv, 'Akcija: naziv', 40) || 'Akcija',
      procenat: ceo(a.procenat, 'Akcija: procenat', 1, 90),
      kategorija: SLUG.test(String(a.kategorija ?? '')) ? String(a.kategorija) : greska('Akcija: izaberite kategoriju.'),
      do: datum,
    };
  }
  return out;
}

// ——— kategorije ———
export function ocistiKategorije(ulaz: unknown): Category[] {
  const lista = niz<Category>(ulaz, 'Kategorije', 40);
  if (!lista.length) greska('Mora postojati bar jedna kategorija.');
  const slugs = new Set<string>();
  return lista.map((c, i) => {
    const ime = red(c?.label, `Kategorija ${i + 1}: naziv u meniju`, 40, true);
    const gde = `Kategorija „${ime}”`;
    const slug = slugPolje(c?.slug, `${gde}: adresa`, 60);
    if (slugs.has(slug)) greska(`${gde}: adresa /katalog/${slug}/ već postoji.`);
    slugs.add(slug);
    const bg = String(c?.bg ?? '').trim() || '/img/bg/kraft.webp';
    if (!/^\/img\/[a-z0-9/_-]+\.(webp|jpg|png)$/.test(bg)) greska(`${gde}: neispravna pozadina.`);
    const bgPos = String(c?.bgPos ?? '').trim();
    if (bgPos && !/^[a-z0-9 %]{1,30}$/.test(bgPos)) greska(`${gde}: neispravan položaj pozadine.`);
    const icon = String(c?.icon ?? '').trim() || IKONA_PODRAZUMEVANA;
    if (!/^[MmLlHhVvCcSsQqTtAaZz0-9 .,-]{1,800}$/.test(icon)) greska(`${gde}: neispravna ikonica.`);
    const fids = new Set<string>();
    const filters = niz<{ id: string; label: string }>(c?.filters, `${gde}: filteri`, 12)
      .map((f) => ({ id: String(f?.id ?? '').trim(), label: red(f?.label, `${gde}: filter`, 30) }))
      .filter((f) => f.label)
      .map((f) => {
        const id = f.id || slugify(f.label, 30);
        if (!SLUG.test(id) || fids.has(id)) greska(`${gde}: filter „${f.label}” ima neispravnu ili ponovljenu oznaku.`);
        fids.add(id);
        return { id, label: f.label };
      });
    const out: Category = {
      slug,
      label: ime,
      title: red(c?.title, `${gde}: naslov`, 120, true),
      text: tekst(c?.text, `${gde}: uvod`, 600),
      seoTitle: red(c?.seoTitle, `${gde}: SEO naslov`, 120) || `${ime} | Bumbaru`,
      meta: red(c?.meta, `${gde}: meta opis`, 300),
      bg,
      ...(bgPos ? { bgPos } : {}),
      icon,
      ...(filters.length ? { filters } : {}),
      ...(c?.soon ? { soon: true } : {}),
      about: { h2: red(c?.about?.h2, `${gde}: podnaslov`, 160), p: pasusi(c?.about?.p, `${gde}: tekst`, 10) },
      faq: pitanja(c?.faq, `${gde}: česta pitanja`),
    };
    return out;
  });
}
export const IKONA_PODRAZUMEVANA = 'M3 7.5L12 3l9 4.5v9L12 21l-9-4.5zM3 7.5l9 4.5 9-4.5M12 12v9';

// ——— proizvodi ———
const PERSONAL: Personal[] = ['ukras', 'set', 'privezak'];

function slika(im: Img, gde: string): Img {
  const src = String(im?.src ?? '').trim();
  if (!/^\/img\/(p|c)\/[a-z0-9-]{1,80}(\/[a-z0-9-]{1,40})?$/.test(src)) greska(`${gde} ima neispravnu putanju.`);
  const og = String(im?.og ?? '').trim();
  if (og && !/^\/img\/[a-z0-9/_-]{1,160}\.jpg$/.test(og)) greska(`${gde} ima neispravnu sliku za deljenje.`);
  return {
    src,
    w: ceo(im?.w, `${gde}: širina`, 1, 10000),
    h: ceo(im?.h, `${gde}: visina`, 1, 10000),
    alt: red(im?.alt, `${gde}: opis`, 200),
    ...(og ? { og } : {}),
  };
}
const slike = (v: unknown, gde: string): Img[] => niz<Img>(v, `${gde}: slike`, 12).map((im, i) => slika(im, `${gde}: slika ${i + 1}`));

function motivi(v: unknown, gde: string): MotiviCfg | undefined {
  if (v == null) return undefined;
  const m = v as MotiviCfg;
  const ids = new Set<string>();
  const lista = niz<MotiviCfg['lista'][number]>(m.lista, `${gde}: motivi`, 40).map((x, i) => {
    const naziv = red(x?.naziv, `${gde}: motiv ${i + 1} (naziv)`, 40, true);
    const base = SLUG.test(String(x?.id ?? '')) ? String(x.id) : slugify(naziv, 30) || 'motiv';
    let id = base;
    for (let n = 2; ids.has(id); n++) id = `${base.slice(0, 26)}-${n}`;
    ids.add(id);
    const c = x?.cena as unknown;
    const cena = c === undefined || c === null || c === '' || c === 0 ? 0 : ceo(c, `${gde}: cena motiva „${naziv}”`, 0, 10_000_000);
    return { id, naziv, ...(x?.slika ? { slika: slika(x.slika, `${gde}: fotografija motiva „${naziv}”`) } : {}), ...(cena > 0 ? { cena } : {}) };
  });
  const boje = m.vrsta === 'boje';
  if (!lista.length && !boje) greska(`${gde}: izbor motiva je uključen, a nema nijednog motiva.`);
  const sil = boje && SILUETE.some((s) => s.id === m.silueta) ? m.silueta : undefined;
  const od = new Set<number>();
  const popusti = niz<{ od: number; procenat: number }>(m.popusti, `${gde}: popusti`, 8)
    .map((x, i) => ({ od: ceo(x?.od, `${gde}: popust ${i + 1} (od komada)`, 2, 100), procenat: ceo(x?.procenat, `${gde}: popust ${i + 1} (procenat)`, 1, 90) }))
    .sort((a, b) => a.od - b.od)
    .map((x) => (od.has(x.od) ? greska(`${gde}: dva popusta od ${x.od} komada.`) : (od.add(x.od), x)));
  for (let i = 1; i < popusti.length; i++) if (popusti[i].procenat <= popusti[i - 1].procenat) greska(`${gde}: popust od ${popusti[i].od} komada mora biti veći nego od ${popusti[i - 1].od}.`);
  return { lista, popusti, ...(boje ? { vrsta: 'boje' as const } : {}), ...(sil ? { silueta: sil } : {}) };
}

function komplet(v: unknown, gde: string): Komplet | undefined {
  if (v == null) return undefined;
  const k = v as Komplet;
  const uz = [...new Set(niz<string>(k.uz, `${gde}: komplet`, 3).map((x) => String(x).trim()).filter((x) => SLUG.test(x)))];
  if (!uz.length) return undefined;
  return { uz, popust: ceo(k.popust, `${gde}: popust za komplet`, 1, 50) };
}
function stepenice(v: unknown, gde: string, min: number, max: number) {
  const od = new Set<number>();
  const l = niz<{ od: number; procenat: number }>(v, gde, 8)
    .map((x, i) => ({ od: ceo(x?.od, `${gde} ${i + 1} (od komada)`, min, max), procenat: ceo(x?.procenat, `${gde} ${i + 1} (procenat)`, 1, 90) }))
    .sort((a, b) => a.od - b.od)
    .map((x) => (od.has(x.od) ? greska(`${gde}: dva popusta od ${x.od} komada.`) : (od.add(x.od), x)));
  for (let i = 1; i < l.length; i++) if (l[i].procenat <= l[i - 1].procenat) greska(`${gde}: popust od ${l[i].od} komada mora biti veći nego od ${l[i - 1].od}.`);
  return l;
}
function firme(v: unknown, gde: string): Firme | undefined {
  if (v == null) return undefined;
  const f = v as Firme;
  const popusti = stepenice(f.popusti, `${gde}: popust za firme`, 2, 100000);
  if (!popusti.length) return undefined;
  const t = tekst(f.tekst, `${gde}: tekst za firme`, 300);
  return { popusti, ...(t ? { tekst: t } : {}) };
}

function opcije(v: unknown, gde: string): Option[] {
  const ids = new Set<string>();
  return niz<Option>(v, `${gde}: opcije`, 6)
    .map((o, i) => {
      const label = red(o?.label, `${gde}: opcija ${i + 1} (naziv)`, 40);
      const sirove = niz<string>(o?.values, `${gde}: opcija „${label}”`, 30).map((x) => red(x, `${gde}: opcija „${label}”`, 60));
      const cene0 = Array.isArray(o?.cene) ? o.cene : [];
      // prazne vrednosti se izbacuju zajedno sa svojom cenom
      const parovi = sirove.map((x, j) => [x, cene0[j]] as [string, unknown]).filter(([x]) => x);
      const values = parovi.map(([x]) => x);
      const cene = parovi.map(([x, c]) => (c === undefined || c === null || c === '' ? 0 : ceo(c, `${gde}: cena za „${x}”`, 0, 10_000_000)));
      return { id: String(o?.id ?? '').trim(), label, values, cene };
    })
    .filter((o) => o.label || o.values.length)
    .map((o) => {
      if (!o.label) greska(`${gde}: opcija bez naziva.`);
      if (!o.values.length) greska(`${gde}: opcija „${o.label}” nema nijednu vrednost.`);
      let id = SLUG.test(o.id) ? o.id : slugify(o.label, 30) || 'opcija';
      for (let n = 2; ids.has(id); n++) id = `${slugify(o.label, 26) || 'opcija'}-${n}`;
      ids.add(id);
      return { id, label: o.label, values: o.values, ...(o.cene.some((c) => c > 0) ? { cene: o.cene } : {}) };
    });
}

function poziv(v: unknown, gde: string): Poziv | undefined {
  if (v == null) return undefined;
  const x = v as Poziv;
  const tekst = tekst_(x.tekst, `${gde}: poziv (tekst)`, 300);
  const dugme = red(x.dugme, `${gde}: poziv (dugme)`, 50);
  const poruka = tekst_(x.poruka, `${gde}: poziv (poruka)`, 300);
  if (!tekst && !dugme && !poruka) return undefined;
  if (!tekst || !dugme) greska(`${gde}: poziv za Messenger mora da ima tekst i natpis na dugmetu.`);
  return { tekst, dugme, ...(poruka ? { poruka } : {}) };
}

function izborBoja(v: unknown, gde: string): ColorChoice[] {
  const ids = new Set<string>();
  return niz<ColorChoice>(v, `${gde}: izbor boja`, 4).map((c, i) => {
    const label = red(c?.label, `${gde}: izbor boje ${i + 1} (naziv)`, 30) || 'Boja';
    let id = SLUG.test(String(c?.id ?? '')) ? String(c.id) : slugify(label, 30) || 'boja';
    for (let n = 2; ids.has(id); n++) id = `${slugify(label, 26) || 'boja'}-${n}`;
    ids.add(id);
    const def = String(c?.def ?? '').trim();
    if (!/^[a-z0-9-]{1,30}$/.test(def)) greska(`${gde}: izbor boje „${label}” nema početnu boju.`);
    return { id, label, def };
  });
}

function privezak(v: unknown, gde: string): KeychainCfg | undefined {
  if (v == null) return undefined;
  const k = v as KeychainCfg;
  const stilovi = niz<string>(k.stilovi, `${gde}: stilovi priveska`, 10).map(String).filter((s) => /^[a-z0-9-]{1,30}$/.test(s));
  if (!stilovi.length) greska(`${gde}: privezak nema stil slova.`);
  const out: KeychainCfg = {
    stilovi,
    startImena: ceo(k.startImena, `${gde}: broj imena`, 1, 12),
    minImena: ceo(k.minImena, `${gde}: najmanje imena`, 1, 12),
    maxImena: ceo(k.maxImena, `${gde}: najviše imena`, 1, 12),
  };
  if (k.sareno) out.sareno = true;
  if (k.slike && typeof k.slike === 'object') {
    const s: Record<string, number> = {};
    for (const [id, n] of Object.entries(k.slike)) if (/^[a-z0-9-]{1,30}$/.test(id) && Number.isInteger(n) && n >= 0 && n < 12) s[id] = n;
    out.slike = s;
  }
  return out;
}

export function ocistiProizvode(ulaz: unknown, kategorije: Category[]): Product[] {
  const lista = niz<Product>(ulaz, 'Proizvodi', 400);
  const katSlugs = new Set(kategorije.map((c) => c.slug));
  const ids = new Set<string>();
  const slugs = new Set<string>();
  const out = lista.map((p, i) => {
    const title = red(p?.title, `Proizvod ${i + 1}: naziv`, 120, true);
    const gde = `„${title}”`;
    const id = red(p?.id, `${gde}: šifra`, 20, true).toUpperCase();
    if (!/^[A-Z0-9-]{1,20}$/.test(id)) greska(`${gde}: šifra sme da ima samo slova bez kvačica, brojeve i crtice (npr. NG-10).`);
    if (ids.has(id)) greska(`${gde}: šifra ${id} već postoji kod drugog proizvoda.`);
    ids.add(id);
    const slug = slugPolje(p?.slug, `${gde}: adresa`);
    if (slugs.has(slug)) greska(`${gde}: adresa /proizvod/${slug}/ već postoji kod drugog proizvoda.`);
    slugs.add(slug);

    const category = String(p?.category ?? '').trim();
    if (!katSlugs.has(category)) greska(`${gde}: izaberite glavnu kategoriju.`);
    const categories = [category, ...niz<string>(p?.categories, `${gde}: kategorije`, 20).map(String).filter((c) => c !== category && katSlugs.has(c))];
    // oznake = filteri kategorije (npr. „Za jelku” u Novoj godini); čuvaju se i kad proizvod trenutno nije u toj kategoriji
    const tags = [...new Set(niz<string>(p?.tags, `${gde}: oznake`, 20).map(String).filter((t) => SLUG.test(t) && t.length <= 30 && t !== 'sve'))];

    const cenaUlaz = p?.price as unknown;
    const price = cenaUlaz === undefined || cenaUlaz === null || cenaUlaz === '' ? undefined : ceo(cenaUlaz, `${gde}: cena`, 0, 10_000_000);
    const personal = PERSONAL.includes(p?.personal as Personal) ? (p.personal as Personal) : undefined;
    const keychain = personal === 'privezak' ? privezak(p?.keychain, gde) : undefined;

    const specs = niz<[string, string]>(p?.specs, `${gde}: specifikacije`, 30)
      .map((s, j) => [red(s?.[0], `${gde}: specifikacija ${j + 1}`, 60), red(s?.[1], `${gde}: specifikacija ${j + 1}`, 300)] as [string, string])
      .filter(([a, b]) => a || b)
      .map(([a, b], j) => (a && b ? ([a, b] as [string, string]) : greska(`${gde}: specifikacija ${j + 1}: popunite i naziv i vrednost.`)));

    const short = tekst(p?.short, `${gde}: kratak opis`, 300);
    const lead = tekst(p?.lead, `${gde}: uvod`, 1200);
    const o: Product = {
      id, slug, title, category, categories,
      ...(tags.length ? { tags } : {}),
      short, lead,
      ...(p?.custom ? { custom: true } : {}),
      ...(price !== undefined ? { price } : {}),
      ...(price !== undefined && p?.priceFrom ? { priceFrom: true } : {}),
      images: slike(p?.images, gde),
      ...(() => { const x = opcije(p?.options, gde); return x.length ? { options: x } : {}; })(),
      ...(() => { const x = izborBoja(p?.colors, gde); return x.length ? { colors: x } : {}; })(),
      ...(personal ? { personal } : {}),
      ...(keychain ? { keychain } : {}),
      ...(() => { const x = motivi(p?.motivi, gde); return x ? { motivi: x } : {}; })(),
      ...(() => { const x = poziv(p?.poziv, gde); return x ? { poziv: x } : {}; })(),
      ...(() => { const x = komplet(p?.komplet, gde); return x ? { komplet: x } : {}; })(),
      ...(() => { const x = firme(p?.firme, gde); return x ? { firme: x } : {}; })(),
      ...(CRTEZI.some((c) => c.id === p?.crtez) ? { crtez: p.crtez } : {}),
      about: pasusi(p?.about, `${gde}: opis`),
      specs,
      details: pitanja(p?.details, `${gde}: detalji`, 12),
      faq: pitanja(p?.faq, `${gde}: česta pitanja`),
      related: niz<string>(p?.related, `${gde}: povezani proizvodi`, 8).map(String),
      ...(p?.skriven ? { skriven: true } : {}),
      seoTitle: red(p?.seoTitle, `${gde}: SEO naslov`, 120) || `${title} | Bumbaru`,
      meta: red(p?.meta, `${gde}: meta opis`, 300) || short.replace(/\s+/g, ' ').slice(0, 160),
    };
    return o;
  });
  // povezani: samo postojeći, bez samog sebe, bez ponavljanja
  for (const p of out) p.related = [...new Set(p.related.filter((s) => s !== p.slug && slugs.has(s)))];
  // komplet: samo postojeći proizvodi, bez samog sebe
  for (const p of out) if (p.komplet) { p.komplet.uz = p.komplet.uz.filter((s) => s !== p.slug && slugs.has(s)); if (!p.komplet.uz.length) delete p.komplet; }
  return out;
}

export function ocistiSve(ulaz: Partial<CmsPodaci>): CmsPodaci {
  const kategorije = ocistiKategorije(ulaz.kategorije);
  return {
    kategorije,
    proizvodi: ocistiProizvode(ulaz.proizvodi, kategorije),
    boje: ocistiBoje(ulaz.boje),
    podesavanja: ocistiPodesavanja(ulaz.podesavanja),
  };
}

// ——— zapis u fajlove (isti oblik kao ručno pisani) ———
export const formatBoje = (boje: Boja[]) =>
  `{\n  "napomena": ${JSON.stringify(NAPOMENE.boje)},\n  "boje": [\n${boje
    .map((b) => `    { "id": ${JSON.stringify(b.id)}, "naziv": ${JSON.stringify(b.naziv)}, "hex": "${b.hex}", "dostupna": ${b.dostupna} }`)
    .join(',\n')}\n  ]\n}\n`;

export function fajlovi(d: CmsPodaci): Record<keyof typeof PUTANJE, string> {
  return {
    proizvodi: JSON.stringify({ napomena: NAPOMENE.proizvodi, proizvodi: d.proizvodi }, null, 2) + '\n',
    kategorije: JSON.stringify({ napomena: NAPOMENE.kategorije, kategorije: d.kategorije }, null, 2) + '\n',
    boje: formatBoje(d.boje),
    podesavanja: JSON.stringify({ napomena: NAPOMENE.podesavanja, ...d.podesavanja }, null, 2) + '\n',
  };
}

// Sve fotografije proizvoda: galerija i fotografije motiva
export const fotografije = (p: Product): Img[] => [...p.images, ...(p.motivi?.lista.flatMap((m) => (m.slika ? [m.slika] : [])) || [])];

// Verzije fotografije koje sajt koristi (moraju da postoje u repozitorijumu):
// fotografije /img/p/: -lg (galerija) i -sq (kartice); crteži /img/c/: -lg i -sm; plus slika za deljenje
export function fajloviSlike(src: string, og?: string) {
  const f = [`public${src}-lg.webp`, src.startsWith('/img/p/') ? `public${src}-sq.webp` : `public${src}-sm.webp`];
  if (og) f.push(`public${og}`);
  return f;
}
