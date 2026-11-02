// Admin panel Bumbaru (/anycubic/): proizvodi, kategorije, boje i podešavanja.
// Sve izmene se drže u pregledaču dok se ne klikne „Sačuvaj i objavi”; tada idu na GitHub u jednom commit-u,
// a Vercel za oko minut objavi sajt. Fotografije se otpremaju odmah po izboru (obrađene, sa logom).
import { h, ikona, dugmeIkona, din } from './dom';
import { obradi, uBase64 } from './slike';
import { ocistiSve, slugify, CmsGreska, IKONA_PODRAZUMEVANA, type CmsPodaci, type Boja } from '../lib/cms-model';
import type { Product, Img, Option, ColorChoice, Motiv, MotiviCfg } from '../data/products';
import { SILUETE } from '../lib/siluete';
import { CRTEZI } from '../lib/crtezi';
import type { Category, Faq } from '../data/categories';
import { STILOVI } from '../lib/keychain';

type Tab = 'proizvodi' | 'kategorije' | 'boje' | 'podesavanja';
const init = JSON.parse(document.getElementById('adm-data')!.textContent || '{}') as { podaci: CmsPodaci; pozadine: string[] };

// ——— stanje ———
let korisnik = '';
let lozinka = '';
let P: CmsPodaci = normalizuj(structuredClone(init.podaci));
let snimak = JSON.stringify(P);
let mozeCuvanje = true;
let cuvanjeUToku = false;
const nove = new Map<string, string>(); // tek otpremljene fotografije: putanja -> GitHub blob
const pregledi = new Map<string, string>(); // src -> lokalni pregled (dok sajt ne objavi fotografiju)
const noviObjekti = new WeakSet<object>(); // proizvodi i kategorije dodati u ovoj sesiji (adresa prati naziv)
const rucnaAdresa = new WeakSet<object>(); // adresa promenjena ručno
let tab: Tab = 'proizvodi';
let otvorenP: Product | null = null;
let otvorenK: Category | null = null;
let pretraga = '';
let filterKat = '';
let seq = 0;

const $ = <T extends HTMLElement = HTMLElement>(s: string) => document.querySelector<T>(s)!;
const panel = () => $('[data-panel]');
const norm = (s: string) => s.toLowerCase().replace(/đ/g, 'dj').normalize('NFD').replace(/[̀-ͯ]/g, '');

// Prazne liste da bi uređivanje bilo jednostavno; pri čuvanju se prazno izbacuje.
function normalizuj(d: CmsPodaci): CmsPodaci {
  for (const p of d.proizvodi) {
    p.categories ||= [p.category]; p.tags ||= []; p.images ||= []; p.options ||= []; p.colors ||= [];
    p.about ||= []; p.specs ||= []; p.details ||= []; p.faq ||= []; p.related ||= [];
    p.short ||= ''; p.lead ||= ''; p.seoTitle ||= ''; p.meta ||= '';
  }
  for (const c of d.kategorije) { c.filters ||= []; c.about ||= { h2: '', p: [] }; c.about.p ||= []; c.faq ||= []; }
  return d;
}

// ——— server ———
async function api(method: 'GET' | 'POST', body?: unknown, upit = '') {
  const r = await fetch('/api/cms/' + upit, {
    method,
    headers: { 'x-admin-user': encodeURIComponent(korisnik), 'x-admin-password': encodeURIComponent(lozinka), ...(body ? { 'Content-Type': 'application/json' } : {}) },
    body: body ? JSON.stringify(body) : undefined,
  });
  let data: any = {};
  try { data = await r.json(); } catch { data = { greska: r.status === 413 ? 'Slika je prevelika za slanje.' : `Server je vratio ${r.status}.` }; }
  return { ok: r.ok, status: r.status, data };
}

// ——— traka za čuvanje ———
const imaIzmena = () => JSON.stringify(P) !== snimak;
// saPorukom = false: posle čuvanja poruka (uspeh ili greška) ostaje dok korisnik ne promeni nešto
function izmena(saPorukom = true) {
  const dirty = imaIzmena();
  $('[data-bar]').classList.toggle('dirty', dirty);
  if (!cuvanjeUToku) {
    $<HTMLButtonElement>('[data-save]').disabled = !dirty || !mozeCuvanje;
    if (saPorukom) poruka(dirty ? 'Ima nesačuvanih izmena.' : mozeCuvanje ? 'Sve je sačuvano.' : '');
  }
  osveziBrojeve();
}
function poruka(t: string, tone: 'ok' | 'err' | '' = '') { const el = $('[data-msg]'); el.textContent = t; el.dataset.tone = tone; }

async function sacuvaj() {
  let cisto: CmsPodaci;
  try { cisto = ocistiSve(structuredClone(P)); }
  catch (e) { poruka('Nije sačuvano. ' + (e instanceof CmsGreska ? e.message : String(e)), 'err'); return; }
  cuvanjeUToku = true;
  const btn = $<HTMLButtonElement>('[data-save]');
  btn.disabled = true;
  poruka('Čuvam…');
  try {
    const r = await api('POST', { akcija: 'sacuvaj', podaci: cisto, slike: [...nove].map(([path, sha]) => ({ path, sha })) });
    if (!r.ok) { poruka('Nije sačuvano. ' + (r.data.greska || `Greška ${r.status}.`), 'err'); return; }
    const iP = otvorenP ? P.proizvodi.indexOf(otvorenP) : -1;
    const iK = otvorenK ? P.kategorije.indexOf(otvorenK) : -1;
    P = normalizuj(cisto);
    snimak = JSON.stringify(P);
    nove.clear();
    otvorenP = iP >= 0 ? P.proizvodi[iP] : null;
    otvorenK = iK >= 0 ? P.kategorije[iK] : null;
    render();
    if (r.data.bezIzmena) poruka('Nije bilo izmena za čuvanje.', 'ok');
    else { poruka('Sačuvano. Sajt se objavljuje…', 'ok'); pratiObjavu(r.data.commit); }
  } catch {
    poruka('Server nije dostupan. Proverite internet i pokušajte ponovo.', 'err');
  } finally {
    cuvanjeUToku = false;
    izmena(false);
  }
}

async function pratiObjavu(commit: string) {
  for (let i = 0; i < 40; i++) {
    await new Promise((r) => setTimeout(r, i === 0 ? 8000 : 5000));
    if (imaIzmena() || cuvanjeUToku) return;
    let s: any;
    try { s = (await api('GET', undefined, `?status=${commit}`)).data; } catch { continue; }
    if (s.stanje === 'success') { poruka('Objavljeno na sajtu. Osvežite stranu sajta da vidite izmene.', 'ok'); return; }
    if (s.stanje === 'failure' || s.stanje === 'error') { poruka('Izmene su sačuvane, ali objava nije uspela, pa sajt i dalje prikazuje prethodnu verziju. Javite Claude-u.', 'err'); return; }
    if (s.stanje === 'nepoznato') { poruka('Sačuvano. Sajt se osvežava za oko minut.', 'ok'); return; }
  }
  poruka('Sačuvano. Objava traje duže nego obično; proverite sajt za par minuta.', 'ok');
}

// ——— polja ———
function polje(label: string, kontrola: HTMLElement, pomoc?: string | Node) {
  const id = kontrola.id || (kontrola.id = `f${++seq}`);
  return h('div', { class: 'field' }, h('label', { for: id }, label), kontrola, pomoc ? h('p', { class: 'hint' }, pomoc) : null);
}
function ulaz(vred: string, set: (v: string) => void, attrs: Record<string, unknown> = {}) {
  return h('input', { class: 'input', value: vred, ...attrs, oninput: (e: Event) => { set((e.target as HTMLInputElement).value); izmena(); } });
}
function oblast(vred: string, set: (v: string) => void, attrs: Record<string, unknown> = {}) {
  const t = h('textarea', { class: 'textarea', rows: 3, ...attrs, oninput: (e: Event) => { set((e.target as HTMLTextAreaElement).value); izmena(); } });
  t.value = vred;
  return t;
}
function izbor(opcije: [string, string][], trenutna: string, set: (v: string) => void, attrs: Record<string, unknown> = {}) {
  return h('select', { class: 'select', ...attrs, onchange: (e: Event) => { set((e.target as HTMLSelectElement).value); izmena(); } },
    opcije.map(([v, l]) => h('option', { value: v, selected: v === trenutna }, l)));
}
function prekidac(ukljucen: boolean, set: (v: boolean) => void, tekst: string, onposle?: () => void) {
  return h('label', { class: 'tog' },
    h('input', { type: 'checkbox', checked: ukljucen, onchange: (e: Event) => { set((e.target as HTMLInputElement).checked); izmena(); onposle?.(); } }),
    h('span', { class: 'tog-ui', 'aria-hidden': 'true' }), h('span', { class: 'tog-t' }, tekst));
}
function brojac(input: HTMLInputElement | HTMLTextAreaElement, preporuka: number) {
  const b = h('span', { class: 'cnt' });
  const osvezi = () => { const n = input.value.length; b.textContent = `${n} / ${preporuka}`; b.classList.toggle('over', n > preporuka); };
  input.addEventListener('input', osvezi);
  osvezi();
  return b;
}
const sekcija = (naslov: string, ...deca: (Node | null | false)[]) => h('section', { class: 'card-box sec' }, h('h3', { class: 'h3' }, naslov), ...deca);
const grid = (...deca: (Node | null | false)[]) => h('div', { class: 'grid2' }, ...deca);

// Lista redova sa pomeranjem i brisanjem (specifikacije, pitanja, opcije…)
function lista<T>(niz: T[], red: (x: T, i: number) => Node, novi: () => T, dodajTekst: string, max = 30) {
  const box = h('div', { class: 'lst' });
  const crtaj = (fokus = false) => {
    box.replaceChildren(
      ...niz.map((x, i) => h('div', { class: 'lst-row' },
        h('div', { class: 'lst-body' }, red(x, i)),
        h('div', { class: 'lst-acts' },
          dugmeIkona('up', 'Pomeri gore', () => { [niz[i - 1], niz[i]] = [niz[i], niz[i - 1]]; crtaj(); izmena(); }, { disabled: i === 0 }),
          dugmeIkona('down', 'Pomeri dole', () => { [niz[i + 1], niz[i]] = [niz[i], niz[i + 1]]; crtaj(); izmena(); }, { disabled: i === niz.length - 1 }),
          dugmeIkona('close', 'Obriši red', () => { niz.splice(i, 1); crtaj(); izmena(); }, { class: 'del' })))),
      ...(niz.length < max ? [h('button', { type: 'button', class: 'btn btn-sm', onclick: () => { niz.push(novi()); crtaj(true); izmena(); } }, ikona('plus'), dodajTekst)] : []),
    );
    if (fokus) box.querySelector<HTMLElement>('.lst-row:last-of-type input, .lst-row:last-of-type textarea')?.focus();
  };
  crtaj();
  return box;
}
const listaPitanja = (niz: Faq[], ql: string, al: string, dodaj: string) =>
  lista(niz, (f) => h('div', { class: 'stack' },
    ulaz(f.q, (v) => (f.q = v), { placeholder: ql, 'aria-label': ql }),
    oblast(f.a, (v) => (f.a = v), { placeholder: al, 'aria-label': al, rows: 2 })), () => ({ q: '', a: '' }), dodaj);

// ——— pomoćno za proizvode ———
const katLabel = (slug: string) => P.kategorije.find((c) => c.slug === slug)?.label || slug;
const slikaUrl = (im: Img) => pregledi.get(im.src) || (im.src.startsWith('/img/p/') ? `${im.src}-sq.webp` : `${im.src}-sm.webp`);
const sacuvanProizvod = (p: Product) => (JSON.parse(snimak) as CmsPodaci).proizvodi.some((x) => x.slug === p.slug && !x.skriven);
function slobodnaAdresa(osnova: string, ja?: Product) {
  const zauzeto = new Set(P.proizvodi.filter((x) => x !== ja).map((x) => x.slug));
  let s = osnova || 'proizvod';
  for (let n = 2; zauzeto.has(s); n++) s = `${osnova}-${n}`;
  return s;
}
function predloziSifru(kat: string, ja?: Product) {
  const uKat = P.proizvodi.filter((x) => x.category === kat && x !== ja).map((x) => x.id.match(/^([A-Z]+)-(\d+)$/)).filter(Boolean) as RegExpMatchArray[];
  const pref = uKat[0]?.[1] || (norm(katLabel(kat)).replace(/[^a-z]/g, '').slice(0, 2).toUpperCase() || 'PR');
  const zauzeto = new Set(P.proizvodi.filter((x) => x !== ja).map((x) => x.id));
  let n = 1;
  while (zauzeto.has(`${pref}-${String(n).padStart(2, '0')}`)) n++;
  return `${pref}-${String(n).padStart(2, '0')}`;
}
const PODRAZUMEVANI_DETALJI: Faq[] = [
  { q: 'Rok i dostava', a: 'Rok slanja javljamo u odgovoru na vašu poruku. Kurir nosi 1–2 radna dana, a plaćate pouzećem, kuriru kad paket stigne.' },
  { q: 'Povraćaj i reklamacije', a: 'Proizvod možete vratiti u roku od 14 dana od prijema, bez navođenja razloga. Ako stigne oštećen, pošaljite nam fotografiju u Messenger-u i šaljemo novi.' },
];

// ——— render ———
function osveziBrojeve() {
  const n = (t: Tab) => document.querySelector(`[data-tab="${t}"] .num`);
  const a = n('proizvodi'); if (a) a.textContent = String(P.proizvodi.length);
  const b = n('kategorije'); if (b) b.textContent = String(P.kategorije.length);
  const c = n('boje'); if (c) c.textContent = String(P.boje.filter((x) => x.dostupna).length);
}
function render() {
  document.querySelectorAll<HTMLButtonElement>('[data-tab]').forEach((b) => b.setAttribute('aria-selected', String(b.dataset.tab === tab)));
  const el = tab === 'proizvodi' ? (otvorenP ? editorProizvoda(otvorenP) : listaProizvoda())
    : tab === 'kategorije' ? (otvorenK ? editorKategorije(otvorenK) : listaKategorija())
    : tab === 'boje' ? panelBoje() : panelPodesavanja();
  panel().replaceChildren(el);
  osveziBrojeve();
}
function otvoriP(p: Product | null) { otvorenP = p; render(); window.scrollTo({ top: 0 }); }
function otvoriK(c: Category | null) { otvorenK = c; render(); window.scrollTo({ top: 0 }); }

// ——— PROIZVODI: lista ———
function listaProizvoda() {
  const telo = h('div', { class: 'plist' });
  const crtaj = () => {
    const q = norm(pretraga.trim());
    const vidljivi = P.proizvodi.filter((p) => (!filterKat || p.categories.includes(filterKat)) && (!q || norm(`${p.title} ${p.id}`).includes(q)));
    telo.replaceChildren(...(vidljivi.length ? vidljivi.map((p) => {
      const i = P.proizvodi.indexOf(p);
      const im = p.images[0];
      return h('div', { class: `prow${p.skriven ? ' off' : ''}` },
        h('button', { type: 'button', class: 'prow-main', onclick: () => otvoriP(p) },
          h('span', { class: 'pthumb' }, im ? h('img', { src: slikaUrl(im), alt: '', loading: 'lazy', width: 56, height: 56, onerror: (e: Event) => ((e.target as HTMLImageElement).style.visibility = 'hidden') }) : ikona('camera', 22)),
          h('span', { class: 'ptxt' },
            h('strong', null, p.title || '(bez naziva)'),
            h('span', { class: 'small muted' }, `${p.id || '—'} · ${katLabel(p.category)}${p.categories.length > 1 ? ` +${p.categories.length - 1}` : ''}`)),
          h('span', { class: 'pmeta' },
            p.skriven ? h('span', { class: 'tag tag-off' }, 'Sakriven') : null,
            p.custom ? h('span', { class: 'tag' }, 'Po meri') : null,
            h('span', { class: 'pprice' }, p.price != null ? (p.priceFrom ? 'od ' : '') + din(p.price) : '—'))),
        h('div', { class: 'lst-acts' },
          dugmeIkona('up', 'Pomeri gore', () => { [P.proizvodi[i - 1], P.proizvodi[i]] = [P.proizvodi[i], P.proizvodi[i - 1]]; crtaj(); izmena(); }, { disabled: i === 0 }),
          dugmeIkona('down', 'Pomeri dole', () => { [P.proizvodi[i + 1], P.proizvodi[i]] = [P.proizvodi[i], P.proizvodi[i + 1]]; crtaj(); izmena(); }, { disabled: i === P.proizvodi.length - 1 })));
    }) : [h('p', { class: 'muted empty' }, 'Nema proizvoda za ovu pretragu.')]));
  };
  crtaj();
  return h('div', null,
    h('div', { class: 'toolbar' },
      h('label', { class: 'adm-search' }, ikona('search'), h('input', { class: 'input', type: 'search', placeholder: 'Pretraga po nazivu ili šifri', value: pretraga, 'aria-label': 'Pretraga proizvoda', oninput: (e: Event) => { pretraga = (e.target as HTMLInputElement).value; crtaj(); } })),
      izbor([['', 'Sve kategorije'], ...P.kategorije.map((c) => [c.slug, c.label] as [string, string])], filterKat, (v) => { filterKat = v; crtaj(); }, { 'aria-label': 'Kategorija' }),
      h('button', { type: 'button', class: 'btn btn-primary', onclick: noviProizvod }, ikona('plus'), 'Novi proizvod')),
    h('p', { class: 'small muted' }, 'Redosled u listi je redosled u katalogu (proizvodi sa fotografijom idu prvi). Kliknite proizvod da ga izmenite.'),
    telo);
}

function noviProizvod() {
  const kat = filterKat || P.kategorije[0]?.slug || '';
  const boja = P.boje.find((b) => b.dostupna)?.id || 'bela';
  const p: Product = {
    id: predloziSifru(kat), slug: '', title: '', category: kat, categories: [kat], tags: [], short: '', lead: '',
    images: [], options: [], colors: [{ id: 'boja', label: 'Boja', def: boja }],
    about: [], specs: [['Materijal', 'PLA plastika']], details: structuredClone(PODRAZUMEVANI_DETALJI), faq: [], related: [], seoTitle: '', meta: '',
  };
  noviObjekti.add(p);
  P.proizvodi.unshift(p);
  izmena();
  otvoriP(p);
  setTimeout(() => panel().querySelector<HTMLInputElement>('[data-naziv]')?.focus(), 0);
}

// ——— PROIZVODI: uređivanje ———
function editorProizvoda(p: Product) {
  const naSajtu = sacuvanProizvod(p);
  const adresaUlaz = ulaz(p.slug, (v) => { p.slug = v.trim(); rucnaAdresa.add(p); }, { placeholder: 'npr. mini-jelka-za-sto', spellcheck: 'false', autocapitalize: 'none' });
  const naziv = ulaz(p.title, (v) => {
    p.title = v;
    if (noviObjekti.has(p) && !rucnaAdresa.has(p)) { p.slug = slobodnaAdresa(slugify(v), p); adresaUlaz.value = p.slug; }
    naslov.textContent = v || 'Novi proizvod';
  }, { 'data-naziv': '', maxlength: 120, placeholder: 'npr. Mini jelka za sto' });
  const naslov = h('h2', { class: 'h2' }, p.title || 'Novi proizvod');
  let predlog = noviObjekti.has(p) ? p.id : '';
  const sifra = ulaz(p.id, (v) => (p.id = v.toUpperCase()), { maxlength: 20, placeholder: 'npr. NG-10', spellcheck: 'false' });
  // novi proizvod: predložena šifra prati glavnu kategoriju dok je ne promenite ručno
  const promenaKat = (kat: string) => { if (predlog && p.id === predlog) { p.id = predlog = predloziSifru(kat, p); sifra.value = p.id; } };

  const kratki = oblast(p.short, (v) => (p.short = v), { rows: 2, maxlength: 300, placeholder: 'Jedna ili dve rečenice za karticu u katalogu' });
  const seoT = ulaz(p.seoTitle, (v) => (p.seoTitle = v), { maxlength: 120, placeholder: `${p.title || 'Naziv'} | Bumbaru` });
  const seoM = oblast(p.meta, (v) => (p.meta = v), { rows: 2, maxlength: 300, placeholder: 'Ako ostane prazno, koristi se kratak opis' });

  return h('div', { class: 'editor' },
    h('div', { class: 'ed-head' },
      h('button', { type: 'button', class: 'btn btn-sm', onclick: () => otvoriP(null) }, ikona('left'), 'Svi proizvodi'),
      naSajtu ? h('a', { class: 'btn btn-sm', href: `/proizvod/${p.slug}/`, target: '_blank', rel: 'noopener' }, ikona('ext'), 'Otvori na sajtu') : null),
    naslov,

    sekcija('Osnovno',
      h('div', { class: 'tog-row' },
        prekidac(!p.skriven, (v) => { if (v) delete p.skriven; else p.skriven = true; }, 'Prikazan na sajtu'),
        prekidac(!!p.custom, (v) => { if (v) p.custom = true; else delete p.custom; }, 'Po meri (oznaka „Po meri · 3–5 dana”)')),
      grid(polje('Naziv', naziv), polje('Šifra', sifra, 'Ide u poruku na Messenger-u.')),
      polje('Adresa na sajtu', adresaUlaz, noviObjekti.has(p) ? 'Pravi se sama od naziva.' : 'Pažnja: ako promenite adresu, stari link (i deljenja na mrežama) više ne vodi na proizvod.')),

    sekcija('Fotografije', slikeSekcija(p)),
    sekcija('Kategorije', katSekcija(p, promenaKat)),

    sekcija('Cena',
      grid(
        polje('Cena (din)', ulaz(p.price != null ? String(p.price) : '', (v) => { const n = v.trim() === '' ? undefined : Math.round(Number(v)); if (n === undefined || Number.isNaN(n)) { delete p.price; delete p.priceFrom; } else p.price = n; }, { type: 'number', min: 0, step: 10, inputmode: 'numeric', placeholder: 'npr. 390' })),
        h('div', { class: 'field' }, h('span', { class: 'flabel' }, 'Prikaz'), prekidac(!!p.priceFrom, (v) => { if (v) p.priceFrom = true; else delete p.priceFrom; }, 'Piše „od” ispred cene'))),
      P.podesavanja.prikaziCene ? null : h('p', { class: 'hint warn' }, 'Cene su trenutno isključene na celom sajtu (kartica Podešavanja). Upisana cena se čuva i pojaviće se kad ih uključite.')),

    sekcija('Tekstovi',
      polje('Kratak opis (kartica u katalogu)', kratki, brojac(kratki, 120)),
      polje('Uvod (ispod naslova na strani proizvoda)', oblast(p.lead, (v) => (p.lead = v), { rows: 3, maxlength: 1200 })),
      polje('Opis', oblast(p.about.join('\n\n'), (v) => (p.about = v.split(/\n\s*\n/).map((x) => x.trim()).filter(Boolean)), { rows: 7 }), 'Pasuse odvojite praznim redom.')),

    sekcija('Specifikacije',
      h('p', { class: 'hint' }, 'Tabela na strani proizvoda, npr. Materijal → PLA plastika.'),
      lista(p.specs, (s) => h('div', { class: 'pair' },
        ulaz(s[0], (v) => (s[0] = v), { placeholder: 'Naziv (npr. Materijal)', 'aria-label': 'Naziv', maxlength: 60 }),
        ulaz(s[1], (v) => (s[1] = v), { placeholder: 'Vrednost (npr. PLA plastika)', 'aria-label': 'Vrednost', maxlength: 300 })), () => ['', ''] as [string, string], 'Dodaj red')),

    sekcija('Detalji (harmonika ispod opisa)', listaPitanja(p.details, 'Naslov (npr. Rok i dostava)', 'Tekst', 'Dodaj detalj')),
    sekcija('Česta pitanja', listaPitanja(p.faq, 'Pitanje', 'Odgovor', 'Dodaj pitanje')),
    sekcija('Izbor za kupca', izborSekcija(p)),
    sekcija('Izbor motiva ili boja sa popustom (npr. modlice, klipovi)', motiviSekcija(p)),
    sekcija('Poziv za celu kolekciju (Messenger)', pozivSekcija(p)),
    sekcija('Komplet „Uz ovo često uzimaju” (popust za set)', kompletSekcija(p)),
    sekcija('Popusti za firme (velike količine)', firmeSekcija(p)),
    sekcija('Povezani proizvodi', povezaniSekcija(p)),

    sekcija('Google (SEO)',
      polje('Naslov u pretrazi', seoT, brojac(seoT, 60)),
      polje('Opis u pretrazi', seoM, brojac(seoM, 160))),

    h('section', { class: 'card-box sec danger' },
      h('h3', { class: 'h3' }, 'Još'),
      h('div', { class: 'tog-row' },
        h('button', { type: 'button', class: 'btn btn-sm', onclick: () => kopija(p) }, ikona('copy'), 'Napravi kopiju'),
        obrisiDugme('Obriši proizvod', () => {
          P.proizvodi.splice(P.proizvodi.indexOf(p), 1);
          for (const x of P.proizvodi) x.related = x.related.filter((s) => s !== p.slug);
          izmena(); otvoriP(null);
        })),
      h('p', { class: 'hint' }, 'Proizvod koji privremeno nemate bolje je sakriti (prekidač „Prikazan na sajtu”) nego obrisati.')),
  );
}

function obrisiDugme(tekst: string, akcija: () => void) {
  let potvrda = false;
  const b = h('button', { type: 'button', class: 'btn btn-sm btn-del', onclick: () => {
    if (!potvrda) { potvrda = true; b.lastChild!.textContent = 'Kliknite ponovo da potvrdite'; setTimeout(() => { potvrda = false; b.lastChild!.textContent = tekst; }, 4000); return; }
    akcija();
  } }, ikona('trash'), h('span', null, tekst));
  return b;
}

function kopija(p: Product) {
  const k = structuredClone(p);
  k.title = `${p.title} (kopija)`;
  k.slug = slobodnaAdresa(`${p.slug}-kopija`);
  k.id = predloziSifru(p.category);
  k.skriven = true;
  delete k.personal; delete k.keychain; // konfigurator ostaje samo na originalu
  noviObjekti.add(k);
  P.proizvodi.splice(P.proizvodi.indexOf(p) + 1, 0, k);
  izmena();
  otvoriP(k);
}

// Jedna fotografija: obrada u pregledaču (logo, verzije), otpremanje, vraća sliku za podatke
async function otpremiFotografiju(p: Product, f: File, alt: string): Promise<Img> {
  const o = await obradi(f);
  const [lg, sm, sq, og] = await Promise.all([uBase64(o.lg), uBase64(o.sm), uBase64(o.sq), uBase64(o.og)]);
  const r = await api('POST', { akcija: 'slika', slug: p.slug, w: o.w, h: o.h, lg, sm, sq, og });
  if (!r.ok) throw new Error(r.data.greska || `Greška ${r.status}.`);
  for (const s of r.data.slike) nove.set(s.path, s.sha);
  pregledi.set(r.data.src, o.pregled);
  return { src: r.data.src, w: o.w, h: o.h, alt, og: r.data.og };
}
const mozeOtpremanje = (p: Product, status: HTMLElement) => {
  if (!mozeCuvanje) { status.textContent = 'Fotografije ne mogu da se otpreme dok čuvanje ne radi (GITHUB_TOKEN).'; status.dataset.tone = 'err'; return false; }
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(p.slug)) { status.textContent = 'Prvo upišite naziv proizvoda (od njega se pravi adresa), pa dodajte fotografije.'; status.dataset.tone = 'err'; return false; }
  return true;
};

// Fotografije: biraju se fajlovi, logo i verzije se prave sami
function slikeSekcija(p: Product) {
  const box = h('div', { class: 'imgs' });
  const status = h('p', { class: 'small', role: 'status', 'aria-live': 'polite' });
  const fajl = h('input', { type: 'file', accept: 'image/*', multiple: true, hidden: true, onchange: () => { const f = [...(fajl.files || [])]; fajl.value = ''; dodaj(f); } });
  let rad = false;

  const crtaj = () => {
    box.replaceChildren(
      ...p.images.map((im, i) => h('div', { class: 'imc' },
        h('div', { class: 'imc-img' },
          h('img', { src: slikaUrl(im), alt: '', loading: 'lazy', onerror: (e: Event) => ((e.target as HTMLImageElement).style.opacity = '0') }),
          i === 0 ? h('span', { class: 'tag imc-main' }, 'Glavna') : null),
        ulaz(im.alt, (v) => (im.alt = v), { placeholder: 'Opis fotografije (za Google)', 'aria-label': `Opis fotografije ${i + 1}`, maxlength: 200 }),
        h('div', { class: 'imc-acts' },
          dugmeIkona('left', 'Pomeri levo', () => { [p.images[i - 1], p.images[i]] = [p.images[i], p.images[i - 1]]; crtaj(); izmena(); }, { disabled: i === 0 }),
          dugmeIkona('right', 'Pomeri desno', () => { [p.images[i + 1], p.images[i]] = [p.images[i], p.images[i + 1]]; crtaj(); izmena(); }, { disabled: i === p.images.length - 1 }),
          dugmeIkona('close', 'Obriši fotografiju', () => { p.images.splice(i, 1); crtaj(); izmena(); }, { class: 'del' })))),
      ...(p.images.length < 12 ? [h('button', { type: 'button', class: 'imc imc-add', disabled: rad, onclick: () => fajl.click() },
        ikona('camera', 28), h('strong', null, rad ? 'Obrađujem…' : 'Dodaj fotografije'), h('span', { class: 'small muted' }, 'ili ih prevucite ovde'))] : []),
    );
  };

  async function dodaj(fajlovi: File[]) {
    if (rad || !fajlovi.length || !mozeOtpremanje(p, status)) return;
    rad = true; crtaj();
    const slobodno = 12 - p.images.length;
    const lista = fajlovi.slice(0, slobodno);
    let gotovo = 0;
    try {
      for (const [n, f] of lista.entries()) {
        status.dataset.tone = '';
        status.textContent = `Fotografija ${n + 1} od ${lista.length}: obrađujem i dodajem logo…`;
        const br = p.images.length;
        p.images.push(await otpremiFotografiju(p, f, br === 0 ? p.title : `${p.title}, fotografija ${br + 1}`));
        gotovo++;
        crtaj(); izmena();
      }
      status.textContent = `Dodato: ${gotovo}.${fajlovi.length > slobodno ? ' Najviše 12 fotografija po proizvodu.' : ''} Fotografije se objavljuju kad kliknete „Sačuvaj i objavi”.`;
      status.dataset.tone = 'ok';
    } catch (e) {
      status.textContent = (gotovo ? `Dodato: ${gotovo}. ` : '') + ((e as Error).message || 'Greška pri obradi fotografije.');
      status.dataset.tone = 'err';
    } finally { rad = false; crtaj(); }
  }

  box.addEventListener('dragover', (e) => { e.preventDefault(); box.classList.add('drop'); });
  box.addEventListener('dragleave', () => box.classList.remove('drop'));
  box.addEventListener('drop', (e) => { e.preventDefault(); box.classList.remove('drop'); dodaj([...(e.dataTransfer?.files || [])]); });
  crtaj();
  return h('div', null,
    h('p', { class: 'hint' }, 'Prva fotografija je glavna (kartica u katalogu i slika pri deljenju linka). Logo Bumbaru se dodaje automatski na svaku fotografiju.'),
    box, status, fajl);
}

function katSekcija(p: Product, promenaKat: (kat: string) => void) {
  const box = h('div');
  const crtaj = () => {
    const filtrirane = P.kategorije.filter((c) => p.categories.includes(c.slug) && (c.filters || []).some((f) => f.id !== 'sve'));
    box.replaceChildren(
      polje('Glavna kategorija', izbor(P.kategorije.map((c) => [c.slug, c.label]), p.category, (v) => {
        p.category = v;
        p.categories = [v, ...p.categories.filter((c) => c !== v)];
        promenaKat(v);
        crtaj();
      }), 'Od nje zavisi putanja (mrvice) na strani proizvoda.'),
      h('div', { class: 'field' }, h('span', { class: 'flabel' }, 'Prikazuje se i u'),
        h('div', { class: 'chips' }, P.kategorije.filter((c) => c.slug !== p.category).map((c) => h('label', { class: 'chip' },
          h('input', { type: 'checkbox', checked: p.categories.includes(c.slug), onchange: (e: Event) => {
            const on = (e.target as HTMLInputElement).checked;
            p.categories = on ? [...p.categories, c.slug] : p.categories.filter((x) => x !== c.slug);
            izmena(); crtaj();
          } }), h('span', { class: 'chk', 'aria-hidden': 'true' }, ikona('check', 14)), c.label)))),
      ...filtrirane.map((c) => h('div', { class: 'field' }, h('span', { class: 'flabel' }, `Filteri u kategoriji „${c.label}”`),
        h('div', { class: 'chips' }, (c.filters || []).filter((f) => f.id !== 'sve').map((f) => h('label', { class: 'chip' },
          h('input', { type: 'checkbox', checked: (p.tags || []).includes(f.id), onchange: (e: Event) => {
            const on = (e.target as HTMLInputElement).checked;
            p.tags = on ? [...new Set([...(p.tags || []), f.id])] : (p.tags || []).filter((x) => x !== f.id);
            izmena();
          } }), h('span', { class: 'chk', 'aria-hidden': 'true' }, ikona('check', 14)), f.label))))),
    );
  };
  crtaj();
  return box;
}

function izborSekcija(p: Product) {
  const bojeOpcije = (): [string, string][] => P.boje.map((b) => [b.id, b.naziv + (b.dostupna ? '' : ' (nije na stanju)')]);
  return h('div', { class: 'stack' },
    p.personal ? h('p', { class: 'hint warn' }, `Ovaj proizvod ima konfigurator (${p.personal === 'privezak' ? 'privezak sa imenom' : p.personal === 'set' ? 'porodični set ukrasa' : 'ukras sa imenom'}): kupac upisuje ime i bira boje u pregledu uživo. Sam konfigurator se menja u kodu; ovde menjate tekstove, cenu i fotografije.`) : null,
    h('div', { class: 'field' }, h('span', { class: 'flabel' }, 'Izbor boje'),
      h('p', { class: 'hint' }, 'Kupac bira od boja koje su na stanju (kartica Boje). Početna boja je ona koja je prva izabrana.'),
      lista<ColorChoice>(p.colors!, (c) => h('div', { class: 'pair' },
        ulaz(c.label, (v) => (c.label = v), { placeholder: 'Naziv (npr. Boja)', 'aria-label': 'Naziv izbora boje', maxlength: 30 }),
        izbor(bojeOpcije(), c.def, (v) => (c.def = v), { 'aria-label': 'Početna boja' })),
        () => ({ id: '', label: p.colors!.length ? `Boja ${p.colors!.length + 1}` : 'Boja', def: P.boje.find((b) => b.dostupna)?.id || 'bela' }), 'Dodaj izbor boje', 4),
      polje('Crtež u izabranim bojama (galerija)', izbor([['', '— bez crteža —'], ...CRTEZI.map((c): [string, string] => [c.id, `${c.label} (boje: ${c.delovi.map((d, i) => `${i + 1}. izbor boje = ${d}`).join(', ')})`])], p.crtez || '', (v) => { if (v) p.crtez = v; else delete p.crtez; }),
        'Kad kupac promeni boju, u galeriji se pojavi crtež proizvoda u izabranim bojama. Za novi crtež pišite programeru.')),
    h('div', { class: 'field' }, h('span', { class: 'flabel' }, 'Ostale opcije'),
      h('p', { class: 'hint' }, 'Npr. Veličina, Motiv, Oblik. Uz vrednost možete upisati i njenu cenu (npr. Mali 590, Veliki 1290): na sajtu se cena menja sa izborom. Prazna cena = osnovna cena proizvoda.'),
      lista<Option>(p.options!, (o) => h('div', { class: 'stack' },
        ulaz(o.label, (v) => (o.label = v), { placeholder: 'Naziv opcije (npr. Veličina)', 'aria-label': 'Naziv opcije', maxlength: 40 }),
        vrednostiOpcije(o)),
        () => ({ id: '', label: '', values: [''] }), 'Dodaj opciju', 6)),
  );
}

// Izbor motiva: kupac na sajtu klikne motive (i broj komada), popust raste sa brojem komada
const PODRAZUMEVANI_POPUSTI = [{ od: 4, procenat: 17 }, { od: 6, procenat: 24 }, { od: 8, procenat: 30 }];
const nazivIzFajla = (ime: string, n: number) => {
  const s = ime.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
  if (!s || /^(img|dsc|pxl|photo|image|whatsapp|screenshot|snimak)\b/i.test(s) || /^\d+$/.test(s)) return `Motiv ${n}`;
  return (s.charAt(0).toUpperCase() + s.slice(1)).slice(0, 40);
};
function motiviSekcija(p: Product) {
  const box = h('div', { class: 'stack' });
  const status = h('p', { class: 'small', role: 'status', 'aria-live': 'polite' });
  const fajlVise = h('input', { type: 'file', accept: 'image/*', multiple: true, hidden: true, onchange: () => { const f = [...(fajlVise.files || [])]; fajlVise.value = ''; dodajVise(f); } });
  let sacuvani: MotiviCfg | undefined;
  let rad = false;

  async function dodajVise(fajlovi: File[]) {
    if (rad || !fajlovi.length || !p.motivi || !mozeOtpremanje(p, status)) return;
    rad = true; crtaj();
    let gotovo = 0;
    try {
      for (const [i, f] of fajlovi.slice(0, 40 - p.motivi.lista.length).entries()) {
        status.dataset.tone = '';
        status.textContent = `Motiv ${i + 1} od ${fajlovi.length}: obrađujem i otpremam…`;
        const naziv = nazivIzFajla(f.name, p.motivi.lista.length + 1);
        const slika = await otpremiFotografiju(p, f, `${p.title}: ${naziv}`);
        p.motivi.lista.push({ id: '', naziv, slika });
        gotovo++; crtaj(); izmena();
      }
      status.textContent = `Dodato motiva: ${gotovo}. Proverite nazive (prave se od imena fajlova).`; status.dataset.tone = 'ok';
    } catch (e) {
      status.textContent = (gotovo ? `Dodato: ${gotovo}. ` : '') + ((e as Error).message || 'Greška pri obradi fotografije.'); status.dataset.tone = 'err';
    } finally { rad = false; crtaj(); }
  }

  async function zameniSliku(m: Motiv, f: File) {
    if (rad || !mozeOtpremanje(p, status)) return;
    rad = true; crtaj();
    try {
      status.dataset.tone = ''; status.textContent = `„${m.naziv || 'Motiv'}”: obrađujem i otpremam…`;
      m.slika = await otpremiFotografiju(p, f, `${p.title}: ${m.naziv}`);
      status.textContent = 'Fotografija motiva je dodata.'; status.dataset.tone = 'ok';
      izmena();
    } catch (e) { status.textContent = (e as Error).message; status.dataset.tone = 'err'; }
    finally { rad = false; crtaj(); }
  }

  const cenovnik = h('p', { class: 'hint' });
  const osveziCenovnik = () => {
    if (!p.motivi) return;
    const P = [...p.motivi.popusti].filter((x) => x.od > 1 && x.procenat > 0).sort((a, b) => a.od - b.od);
    if (!p.price) { cenovnik.textContent = 'Upišite cenu po komadu (sekcija Cena), pa ovde vidite cene paketa.'; return; }
    const c = (n: number) => { const pct = P.filter((x) => n >= x.od).reduce((a, x) => Math.max(a, x.procenat), 0); return Math.round((n * p.price! * (1 - pct / 100)) / 10) * 10; };
    cenovnik.textContent = 'Cene: ' + [1, ...P.map((x) => x.od)].map((n) => `${n} kom = ${din(c(n))}`).join(' · ') + ' (zaokruženo na 10 din).';
  };

  const crtaj = () => {
    const ukljuceno = !!p.motivi;
    box.replaceChildren(
      prekidac(ukljuceno, (v) => {
        if (v) p.motivi = sacuvani || { lista: [], popusti: structuredClone(PODRAZUMEVANI_POPUSTI) };
        else { sacuvani = p.motivi; delete p.motivi; }
      }, 'Kupac bira motive ili boje i broj komada (umesto obične količine)', crtaj),
      h('p', { class: 'hint' }, 'Na strani proizvoda kupac klikne motive (ili boje) koje želi, može i više komada istog. Traka sa pčelicom pokazuje koliko mu fali do sledećeg popusta.'),
      ...(ukljuceno ? [
        polje('Kupac bira', izbor([['motivi', 'Motive (svaki motiv sa svojom fotografijom)'], ['boje', 'Boje filamenta (sve boje koje su na stanju)']], p.motivi!.vrsta === 'boje' ? 'boje' : 'motivi', (v) => {
          if (v === 'boje') { p.motivi!.vrsta = 'boje'; p.motivi!.silueta ||= SILUETE[0]?.id; } else { delete p.motivi!.vrsta; delete p.motivi!.silueta; }
          crtaj();
        })),
      ] : []),
      ...(ukljuceno && p.motivi!.vrsta === 'boje' ? [
        polje('Crtež na pločicama boja', izbor([['', 'Krug u boji (bez crteža)'], ...SILUETE.map((x): [string, string] => [x.id, x.label])], p.motivi!.silueta || '', (v) => { if (v) p.motivi!.silueta = v; else delete p.motivi!.silueta; }),
          'Crtež proizvoda se oboji u svaku boju. Pločice su sve boje koje su na stanju (kartica Boje), pa se same menjaju kad uključite ili isključite boju. Za novi crtež pišite programeru.'),
        ...(p.colors?.length ? [h('p', { class: 'hint warn' }, 'Ovaj proizvod ima i poseban „Izbor boje” (sekcija Izbor za kupca). Kad kupac bira boje ovde, taj izbor obično treba obrisati.')] : []),
      ] : []),
      ...(ukljuceno && p.motivi!.vrsta !== 'boje' ? [
        h('div', { class: 'field' }, h('span', { class: 'flabel' }, `Motivi (${p.motivi!.lista.length})`),
          lista<Motiv>(p.motivi!.lista, (m, i) => {
            const fajl = h('input', { type: 'file', accept: 'image/*', hidden: true, onchange: () => { const f = fajl.files?.[0]; fajl.value = ''; if (f) zameniSliku(m, f); } });
            return h('div', { class: 'mrow' },
              h('button', { type: 'button', class: 'mthumb', disabled: rad, title: m.slika ? 'Zameni fotografiju' : 'Dodaj fotografiju', 'aria-label': m.slika ? `Zameni fotografiju: ${m.naziv}` : `Dodaj fotografiju: ${m.naziv}`, onclick: () => fajl.click() },
                m.slika ? h('img', { src: slikaUrl(m.slika), alt: '', onerror: (e: Event) => ((e.target as HTMLImageElement).style.opacity = '0') }) : ikona('camera', 22)),
              ulaz(m.naziv, (v) => { m.naziv = v; if (m.slika) m.slika.alt = `${p.title}: ${v}`; }, { placeholder: `Naziv motiva (npr. Jelka)`, 'aria-label': `Naziv motiva ${i + 1}`, maxlength: 40 }),
              fajl);
          }, () => ({ id: '', naziv: '' }), 'Dodaj motiv', 40),
          h('div', { class: 'tog-row' },
            h('button', { type: 'button', class: 'btn btn-sm btn-primary', disabled: rad, onclick: () => fajlVise.click() }, ikona('camera'), rad ? 'Obrađujem…' : 'Dodaj više motiva iz fotografija'),
            h('span', { class: 'hint' }, 'Izaberite fotografije svih motiva odjednom: za svaku se pravi motiv, a naziv od imena fajla.')),
          status),
      ] : []),
      ...(ukljuceno ? [
        h('div', { class: 'field' }, h('span', { class: 'flabel' }, 'Popust prema broju komada'),
          lista(p.motivi!.popusti, (x) => h('div', { class: 'pair' },
            h('label', { class: 'inl' }, 'Od', ulaz(String(x.od), (v) => { x.od = Math.round(Number(v)) || 0; osveziCenovnik(); }, { type: 'number', min: 2, max: 100, inputmode: 'numeric', 'aria-label': 'Od komada' }), 'kom'),
            h('label', { class: 'inl' }, 'popust', ulaz(String(x.procenat), (v) => { x.procenat = Math.round(Number(v)) || 0; osveziCenovnik(); }, { type: 'number', min: 1, max: 90, inputmode: 'numeric', 'aria-label': 'Procenat popusta' }), '%')),
            () => { const L = p.motivi!.popusti; const z = L[L.length - 1]; return { od: z ? z.od + 2 : 4, procenat: z ? Math.min(90, z.procenat + 5) : 10 }; }, 'Dodaj popust', 8),
          cenovnik),
      ] : []),
      fajlVise,
    );
    osveziCenovnik();
  };
  crtaj();
  return box;
}

// Vrednosti opcije sa cenom (cene idu u isti redosled kao vrednosti)
function vrednostiOpcije(o: Option) {
  const box = h('div', { class: 'vals' });
  const cene = () => (o.cene ||= o.values.map(() => 0));
  const zameni = (i: number, j: number) => { [o.values[i], o.values[j]] = [o.values[j], o.values[i]]; if (o.cene) [o.cene[i], o.cene[j]] = [o.cene[j], o.cene[i]]; };
  const crtaj = (fokus = false) => {
    box.replaceChildren(
      ...o.values.map((v, i) => h('div', { class: 'val' },
        ulaz(v, (x) => (o.values[i] = x), { placeholder: `Vrednost ${i + 1} (npr. Mali)`, 'aria-label': `Vrednost ${i + 1}`, maxlength: 60 }),
        h('label', { class: 'inl' }, ulaz(o.cene?.[i] ? String(o.cene[i]) : '', (x) => { const n = Math.round(Number(x)); cene()[i] = Number.isFinite(n) && n > 0 ? n : 0; }, { type: 'number', min: 0, step: 10, inputmode: 'numeric', placeholder: 'cena', 'aria-label': `Cena za vrednost ${i + 1}` }), 'din'),
        dugmeIkona('up', 'Pomeri gore', () => { zameni(i, i - 1); crtaj(); izmena(); }, { disabled: i === 0 }),
        dugmeIkona('down', 'Pomeri dole', () => { zameni(i, i + 1); crtaj(); izmena(); }, { disabled: i === o.values.length - 1 }),
        dugmeIkona('close', 'Obriši vrednost', () => { o.values.splice(i, 1); o.cene?.splice(i, 1); crtaj(); izmena(); }, { class: 'del' }))),
      ...(o.values.length < 30 ? [h('button', { type: 'button', class: 'btn btn-sm', onclick: () => { o.values.push(''); o.cene?.push(0); crtaj(true); izmena(); } }, ikona('plus'), 'Dodaj vrednost')] : []),
    );
    if (fokus) box.querySelector<HTMLInputElement>('.val:last-of-type input')?.focus();
  };
  crtaj();
  return box;
}

function pozivSekcija(p: Product) {
  const box = h('div', { class: 'stack' });
  const crtaj = () => box.replaceChildren(
    prekidac(!!p.poziv, (v) => { if (v) p.poziv = { tekst: 'Ovo je samo deo kolekcije. Pišite nam na Messenger-u i šaljemo vam celu ponudu.', dugme: 'Pošaljite mi celu kolekciju' }; else delete p.poziv; }, 'Prikaži poziv na Messenger ispod dugmeta za porudžbinu', crtaj),
    ...(p.poziv ? [
      polje('Tekst', oblast(p.poziv.tekst, (v) => (p.poziv!.tekst = v), { rows: 2, maxlength: 300 })),
      grid(
        polje('Natpis na dugmetu', ulaz(p.poziv.dugme, (v) => (p.poziv!.dugme = v), { maxlength: 50 })),
        polje('Poruka koja se otvori u Messenger-u (nije obavezno)', ulaz(p.poziv.poruka || '', (v) => { if (v.trim()) p.poziv!.poruka = v; else delete p.poziv!.poruka; }, { maxlength: 300, placeholder: `Zdravo! ${p.poziv.dugme} (${p.title}).` }))),
    ] : []),
  );
  crtaj();
  return box;
}

function kompletSekcija(p: Product) {
  const box = h('div', { class: 'stack' });
  const ostali: [string, string][] = [['', '— nijedan —'], ...P.proizvodi.filter((x) => x !== p && x.slug && !x.personal).map((x) => [x.slug, `${x.title}${x.skriven ? ' (sakriven)' : ''}`] as [string, string])];
  const crtaj = () => box.replaceChildren(
    prekidac(!!p.komplet, (v) => { if (v) p.komplet = { uz: [], popust: 10 }; else delete p.komplet; }, 'Prikaži komplet sa popustom ispod dugmeta za porudžbinu', crtaj),
    h('p', { class: 'hint' }, 'Kupac jednim klikom doda u korpu ovaj proizvod i izabrane proizvode, sa popustom na ukupnu cenu. Ne prikazuje se za proizvode sa imenom ili izborom komada, ni kad neki proizvod nema cenu.'),
    ...(p.komplet ? [
      grid(...[0, 1].map((i) => polje(`Proizvod ${i + 1} u kompletu`, izbor(ostali, p.komplet!.uz[i] || '', (v) => { const u = [...p.komplet!.uz]; u[i] = v; p.komplet!.uz = [...new Set(u.filter(Boolean))]; crtaj(); })))),
      polje('Popust za komplet (%)', ulaz(String(p.komplet.popust), (v) => { p.komplet!.popust = Math.round(Number(v)) || 0; }, { type: 'number', min: 1, max: 50, inputmode: 'numeric' })),
    ] : []),
  );
  crtaj();
  return box;
}

function firmeSekcija(p: Product) {
  const box = h('div', { class: 'stack' });
  const crtaj = () => box.replaceChildren(
    prekidac(!!p.firme, (v) => { if (v) p.firme = { popusti: [{ od: 25, procenat: 35 }, { od: 50, procenat: 40 }, { od: 100, procenat: 45 }] }; else delete p.firme; }, 'Prikaži kalkulator i upit za firme', crtaj),
    h('p', { class: 'hint' }, 'Kartica „Za firme i veće količine” na strani proizvoda: kupac upiše količinu, vidi cenu po komadu i ukupno, i pošalje upit na Messenger. Računa se od osnovne cene proizvoda.'),
    ...(p.firme ? [
      h('div', { class: 'field' }, h('span', { class: 'flabel' }, 'Popust prema broju komada'),
        lista(p.firme.popusti, (x) => h('div', { class: 'pair' },
          h('label', { class: 'inl' }, 'Od', ulaz(String(x.od), (v) => { x.od = Math.round(Number(v)) || 0; }, { type: 'number', min: 2, inputmode: 'numeric', 'aria-label': 'Od komada' }), 'kom'),
          h('label', { class: 'inl' }, 'popust', ulaz(String(x.procenat), (v) => { x.procenat = Math.round(Number(v)) || 0; }, { type: 'number', min: 1, max: 90, inputmode: 'numeric', 'aria-label': 'Procenat popusta' }), '%')),
          () => { const L = p.firme!.popusti; const z = L[L.length - 1]; return { od: z ? z.od * 2 : 25, procenat: z ? Math.min(90, z.procenat + 5) : 30 }; }, 'Dodaj popust', 8)),
      polje('Tekst na kartici (nije obavezno)', oblast(p.firme.tekst || '', (v) => { if (v.trim()) p.firme!.tekst = v; else delete p.firme!.tekst; }, { rows: 2, maxlength: 300, placeholder: 'npr. Na privezak možemo da stavimo logo firme.' })),
    ] : []),
  );
  crtaj();
  return box;
}

function povezaniSekcija(p: Product) {
  const ostali: [string, string][] = [['', '— nijedan —'], ...P.proizvodi.filter((x) => x !== p && x.slug).map((x) => [x.slug, `${x.title}${x.skriven ? ' (sakriven)' : ''}`] as [string, string])];
  const box = h('div', { class: 'grid2' });
  const crtaj = () => box.replaceChildren(...[0, 1, 2, 3].map((i) => izbor(ostali, p.related[i] || '', (v) => {
    const r = [...p.related]; r[i] = v;
    p.related = [...new Set(r.filter(Boolean))];
    crtaj();
  }, { 'aria-label': `Povezani proizvod ${i + 1}` })));
  crtaj();
  return h('div', null, h('p', { class: 'hint' }, 'Prikazuju se na dnu strane proizvoda („Možda vam se dopadne”).'), box);
}

// ——— KATEGORIJE ———
const IKONE_KAT: [string, string][] = [
  ['M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z', 'Zvezda'],
  ['M3 9h18v12H3zM12 9v12M3 13h18M12 9c-2-4-6-4-6-1.5S10 9 12 9c2 0 6 0 6-1.5S14 5 12 9z', 'Poklon'],
  ['M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z', 'Alat'],
  ['M5 16h14v-4l-2-5H7l-2 5zM8 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM16 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z', 'Auto'],
  ['M8 3a5 5 0 1 0 0 10 5 5 0 0 0 0-10zM11.5 11.5L20 20M16 16l2.5-2.5M18.5 18.5l2.5-2.5', 'Ključ'],
  ['M4 14h7v7H4zM13 14h7v7h-7zM8.5 4.5h7v7h-7z', 'Kockice'],
  [IKONA_PODRAZUMEVANA, 'Kutija'],
  ['M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z', 'Iskra'],
  ['M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z', 'Kap / srce'],
  ['M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2V16h5v-.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3z', 'Sijalica'],
  ['M3 17L17 3l4 4L7 21zM7 13l2 2M10 10l2 2M13 7l2 2', 'Lenjir'],
  ['M12 3l9 5-9 5-9-5zM3 13l9 5 9-5', 'Slojevi'],
];
const ikonaSvg = (d: string) => { const s = ikona('', 22); s.innerHTML = `<path d="${d}"/>`; return s; };

function listaKategorija() {
  const telo = h('div', { class: 'plist' });
  const crtaj = () => telo.replaceChildren(...P.kategorije.map((c, i) => {
    const n = P.proizvodi.filter((p) => p.categories.includes(c.slug) && !p.skriven).length;
    return h('div', { class: 'prow' },
      h('button', { type: 'button', class: 'prow-main', onclick: () => otvoriK(c) },
        h('span', { class: 'pthumb ico' }, ikonaSvg(c.icon)),
        h('span', { class: 'ptxt' }, h('strong', null, c.label || '(bez naziva)'), h('span', { class: 'small muted' }, `/katalog/${c.slug || '…'}/`)),
        h('span', { class: 'pmeta' }, c.soon ? h('span', { class: 'tag' }, 'Uskoro') : null, h('span', { class: 'pprice' }, `${n} ${n === 1 ? 'proizvod' : 'proizvoda'}`))),
      h('div', { class: 'lst-acts' },
        dugmeIkona('up', 'Pomeri gore', () => { [P.kategorije[i - 1], P.kategorije[i]] = [P.kategorije[i], P.kategorije[i - 1]]; crtaj(); izmena(); }, { disabled: i === 0 }),
        dugmeIkona('down', 'Pomeri dole', () => { [P.kategorije[i + 1], P.kategorije[i]] = [P.kategorije[i], P.kategorije[i + 1]]; crtaj(); izmena(); }, { disabled: i === P.kategorije.length - 1 })));
  }));
  crtaj();
  return h('div', null,
    h('div', { class: 'toolbar' }, h('p', { class: 'small muted grow' }, 'Redosled je redosled u meniju i u katalogu.'),
      h('button', { type: 'button', class: 'btn btn-primary', onclick: () => {
        const c: Category = { slug: '', label: '', title: '', text: '', seoTitle: '', meta: '', bg: '/img/bg/kraft.webp', icon: IKONA_PODRAZUMEVANA, filters: [], about: { h2: '', p: [] }, faq: [] };
        noviObjekti.add(c); P.kategorije.push(c); izmena(); otvoriK(c);
        setTimeout(() => panel().querySelector<HTMLInputElement>('[data-naziv]')?.focus(), 0);
      } }, ikona('plus'), 'Nova kategorija')),
    telo);
}

function editorKategorije(c: Category) {
  const nova = noviObjekti.has(c);
  const naslov = h('h2', { class: 'h2' }, c.label || 'Nova kategorija');
  const adresa = ulaz(c.slug, (v) => { c.slug = v.trim(); rucnaAdresa.add(c); }, { disabled: !nova, spellcheck: 'false', placeholder: 'npr. kuhinja' });
  const pozadine = [...new Set([...init.pozadine, ...P.kategorije.map((k) => k.bg)])];
  const seoT = ulaz(c.seoTitle, (v) => (c.seoTitle = v), { maxlength: 120, placeholder: `${c.label || 'Naziv'} | Bumbaru` });
  const seoM = oblast(c.meta, (v) => (c.meta = v), { rows: 2, maxlength: 300 });
  const uKat = P.proizvodi.filter((p) => p.categories.includes(c.slug));
  return h('div', { class: 'editor' },
    h('div', { class: 'ed-head' },
      h('button', { type: 'button', class: 'btn btn-sm', onclick: () => otvoriK(null) }, ikona('left'), 'Sve kategorije'),
      !nova ? h('a', { class: 'btn btn-sm', href: `/katalog/${c.slug}/`, target: '_blank', rel: 'noopener' }, ikona('ext'), 'Otvori na sajtu') : null),
    naslov,
    sekcija('Osnovno',
      h('div', { class: 'tog-row' }, prekidac(!!c.soon, (v) => { if (v) c.soon = true; else delete c.soon; }, 'Uskoro (umesto proizvoda piše „Uskoro”)')),
      grid(
        polje('Naziv u meniju', ulaz(c.label, (v) => {
          c.label = v; naslov.textContent = v || 'Nova kategorija';
          if (nova && !rucnaAdresa.has(c)) { const zauzeto = new Set(P.kategorije.filter((k) => k !== c).map((k) => k.slug)); let s = slugify(v, 60) || 'kategorija'; for (let n = 2; zauzeto.has(s); n++) s = `${slugify(v, 56)}-${n}`; c.slug = s; adresa.value = s; }
        }, { 'data-naziv': '', maxlength: 40 })),
        polje('Adresa', adresa, nova ? 'Pravi se sama od naziva; posle čuvanja se ne menja.' : 'Adresa postojeće kategorije se ne menja, da linkovi ne bi prestali da rade.')),
      polje('Naslov na strani (H1)', ulaz(c.title, (v) => (c.title = v), { maxlength: 120 })),
      polje('Uvod ispod naslova', oblast(c.text, (v) => (c.text = v), { rows: 3, maxlength: 600 })),
      grid(
        polje('Ikonica', izbor([...(IKONE_KAT.some(([d]) => d === c.icon) ? [] : [[c.icon, 'Trenutna'] as [string, string]]), ...IKONE_KAT], c.icon, (v) => (c.icon = v))),
        polje('Pozadina trake na vrhu', izbor(pozadine.map((b) => [b, b.split('/').pop()!.replace(/\.\w+$/, '')]), c.bg, (v) => (c.bg = v))))),
    sekcija('Tekst ispod proizvoda',
      polje('Podnaslov', ulaz(c.about.h2, (v) => (c.about.h2 = v), { maxlength: 160 })),
      polje('Tekst', oblast(c.about.p.join('\n\n'), (v) => (c.about.p = v.split(/\n\s*\n/).map((x) => x.trim()).filter(Boolean)), { rows: 6 }), 'Pasuse odvojite praznim redom.')),
    sekcija('Česta pitanja', listaPitanja(c.faq, 'Pitanje', 'Odgovor', 'Dodaj pitanje')),
    sekcija('Filteri',
      h('p', { class: 'hint' }, 'Dugmad iznad proizvoda (npr. „Za jelku”). Proizvodu se filter dodeljuje u njegovim podešavanjima. Prvi filter „Sve” prikazuje sve proizvode.'),
      lista(c.filters!, (f) => ulaz(f.label, (v) => (f.label = v), { placeholder: 'Naziv filtera', 'aria-label': 'Naziv filtera', maxlength: 30 }), () => ({ id: '', label: '' }), 'Dodaj filter', 12)),
    sekcija('Google (SEO)', polje('Naslov u pretrazi', seoT, brojac(seoT, 60)), polje('Opis u pretrazi', seoM, brojac(seoM, 160))),
    h('section', { class: 'card-box sec danger' },
      h('h3', { class: 'h3' }, 'Još'),
      P.proizvodi.some((p) => p.category === c.slug)
        ? h('p', { class: 'hint' }, `Kategorija je glavna za ${P.proizvodi.filter((p) => p.category === c.slug).length} proizvod(a), pa ne može da se obriše. Prvo im promenite glavnu kategoriju ili uključite „Uskoro”.`)
        : obrisiDugme('Obriši kategoriju', () => {
          P.kategorije.splice(P.kategorije.indexOf(c), 1);
          for (const p of uKat) p.categories = p.categories.filter((x) => x !== c.slug);
          izmena(); otvoriK(null);
        })),
  );
}

// ——— BOJE ———
function panelBoje() {
  const koriste = new Set<string>([...P.proizvodi.flatMap((p) => (p.colors || []).map((c) => c.def)), ...STILOVI.flatMap((s) => s.slojevi.map((sl) => s.def[sl])), 'bela', 'crvena']);
  const noveBoje = new WeakSet<Boja>();
  const pregled = h('div', { class: 'preview', 'aria-live': 'polite' });
  const osveziPregled = () => {
    const na = P.boje.filter((b) => b.dostupna);
    pregled.replaceChildren(...na.map((b) => h('span', { class: 'pv', style: `background:${b.hex}`, title: b.naziv })));
    if (!na.length) pregled.textContent = 'Nijedna boja nije na stanju.';
  };
  const redovi = lista<Boja>(P.boje, (b) => {
    const sw = h('label', { class: 'sw', title: 'Izaberite nijansu', style: `background:${b.hex}` });
    const kod = h('code', null, b.hex);
    sw.appendChild(h('input', { type: 'color', value: b.hex.toLowerCase(), 'aria-label': 'Nijansa', oninput: (e: Event) => { b.hex = (e.target as HTMLInputElement).value.toUpperCase(); sw.style.background = b.hex; kod.textContent = b.hex; osveziPregled(); izmena(); } }));
    const red = h('div', { class: `crow${b.dostupna ? '' : ' off'}` },
      sw,
      h('div', { class: 'nm' },
        ulaz(b.naziv, (v) => { b.naziv = v; if (noveBoje.has(b)) b.id = slobodanIdBoje(v, b); }, { maxlength: 30, placeholder: 'Naziv boje, npr. Narandžasta', 'aria-label': 'Naziv boje' }),
        h('span', { class: 'meta' }, kod, koriste.has(b.id) ? h('span', { class: 'tag' }, 'početna') : null)),
      prekidac(b.dostupna, (v) => { b.dostupna = v; red.classList.toggle('off', !v); osveziPregled(); }, 'Na stanju'));
    return red;
  }, () => { const b: Boja = { id: '', naziv: '', hex: '#E8722A', dostupna: true }; noveBoje.add(b); return b; }, 'Dodaj boju', 60);
  osveziPregled();
  return h('div', { class: 'card-box sec' },
    h('h3', { class: 'h3' }, 'Kupci sada vide'), pregled,
    h('p', { class: 'small muted' }, 'Boje označene „na stanju” kupci vide i biraju na svim proizvodima. Boju koju trenutno nemate bolje je isključiti nego obrisati. Ako obrišete boju koju neki proizvod koristi kao početnu, proizvod uzima prvu boju na stanju. Oznaka „početna” znači da je neki proizvod ili stil priveska nudi prvu.'),
    redovi);
}
function slobodanIdBoje(naziv: string, ja: Boja) {
  const zauzeto = new Set(P.boje.filter((x) => x !== ja).map((x) => x.id));
  const base = slugify(naziv, 26) || 'boja';
  let id = base;
  for (let n = 2; zauzeto.has(id); n++) id = `${base}-${n}`;
  return id;
}

// ——— PODEŠAVANJA ———
function panelPodesavanja() {
  const akBox = h('div', { class: 'stack' });
  const katOpc = (): [string, string][] => P.kategorije.map((c) => [c.slug, c.label]);
  const crtajAk = () => {
    const a = P.podesavanja.akcija;
    akBox.replaceChildren(
      prekidac(!!a?.ukljucena, (v) => {
        if (!P.podesavanja.akcija) P.podesavanja.akcija = { ukljucena: v, naziv: 'Novogodišnja akcija', procenat: 10, kategorija: 'nova-godina', do: `${new Date().getFullYear()}-12-31` };
        else P.podesavanja.akcija.ukljucena = v;
      }, 'Akcija je uključena', crtajAk),
      h('p', { class: 'hint' }, 'Proizvodi iz izabrane kategorije dobijaju popust: na sajtu se vidi precrtana redovna cena, nova cena, nalepnica „−X%” i odbrojavanje do kraja akcije. Posle izabranog dana akcija sama nestaje sa sajta.'),
      ...(a ? [
        grid(
          polje('Naziv akcije', ulaz(a.naziv, (v) => (a.naziv = v), { maxlength: 40 })),
          polje('Popust (%)', ulaz(String(a.procenat), (v) => (a.procenat = Math.round(Number(v)) || 0), { type: 'number', min: 1, max: 90, inputmode: 'numeric' }))),
        grid(
          polje('Kategorija', izbor(katOpc(), a.kategorija, (v) => (a.kategorija = v))),
          polje('Traje do (uključujući taj dan)', ulaz(a.do, (v) => (a.do = v), { type: 'date' }))),
      ] : []),
    );
  };
  crtajAk();
  return h('div', { class: 'stack' },
    h('div', { class: 'card-box sec' },
      h('h3', { class: 'h3' }, 'Cene'),
      prekidac(P.podesavanja.prikaziCene, (v) => (P.podesavanja.prikaziCene = v), 'Prikaži cene na sajtu'),
      h('p', { class: 'hint' }, 'Dok je isključeno, cene se ne vide nigde na sajtu (ni u Google rezultatima). Upisane cene se čuvaju u proizvodima. Proizvod bez upisane cene ni tada nema cenu.')),
    h('div', { class: 'card-box sec' }, h('h3', { class: 'h3' }, 'Akcija'), akBox));
}

// ——— prijava i pokretanje ———
const loginForm = $<HTMLFormElement>('[data-login]');
loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  korisnik = $<HTMLInputElement>('[data-user]').value.trim();
  lozinka = $<HTMLInputElement>('[data-pass]').value;
  const msg = $('[data-login-msg]');
  msg.textContent = 'Proveravam…'; msg.dataset.tone = '';
  let banner = '';
  try {
    const r = await api('GET');
    if (r.status === 401) { msg.textContent = 'Pogrešno korisničko ime ili lozinka.'; msg.dataset.tone = 'err'; return; }
    if (r.ok && r.data.podaci) {
      P = normalizuj(r.data.podaci);
      snimak = JSON.stringify(P);
    } else {
      mozeCuvanje = false;
      banner = (r.data.greska || `Server je vratio ${r.status}.`) + ' Prikazani su podaci iz poslednje objave sajta; izmene sada ne mogu da se sačuvaju.';
    }
  } catch {
    mozeCuvanje = false;
    banner = 'Server nije dostupan, pa izmene ne mogu da se sačuvaju. Prikazani su podaci iz poslednje objave sajta.';
  }
  loginForm.hidden = true;
  $('[data-app]').hidden = false;
  const b = $('[data-banner]');
  b.hidden = !banner; b.textContent = banner;
  render();
  izmena();
});

document.querySelectorAll<HTMLButtonElement>('[data-tab]').forEach((b) => b.addEventListener('click', () => { tab = b.dataset.tab as Tab; render(); }));
$('[data-save]').addEventListener('click', sacuvaj);
window.addEventListener('beforeunload', (e) => { if (imaIzmena()) { e.preventDefault(); e.returnValue = ''; } });
