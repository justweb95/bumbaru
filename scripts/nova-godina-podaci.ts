// Novogodišnja ponuda, nastavak: prave fotografije, motivi ukrasa, rotirajući ukras (NG-17), jelka sa postoljem (NG-18),
// rebrasta ovalna tacna (DM-04, ranije NG-17 „Novogodišnja tacna za figure”), NG-07 i NG-13 na upit.
// Sve izmene idu kroz model iz src/lib/cms-model.ts (ocistiSve + fajlovi), pa ostaje izmenljivo u adminu.
// Pokretanje (iz korena repoa):
//   npx esbuild scripts/nova-godina-podaci.ts --bundle --platform=node --format=esm --outfile=/tmp/ng.mjs && node /tmp/ng.mjs
import { readFileSync, writeFileSync } from 'node:fs';
import { ocistiSve, fajlovi, PUTANJE, type CmsPodaci } from '../src/lib/cms-model';
import type { Product, Img } from '../src/data/products';

const citaj = (f: string) => JSON.parse(readFileSync(f, 'utf8'));
const ulaz: Partial<CmsPodaci> = {
  proizvodi: citaj(PUTANJE.proizvodi).proizvodi,
  kategorije: citaj(PUTANJE.kategorije).kategorije,
  boje: citaj(PUTANJE.boje).boje,
  podesavanja: (({ napomena, ...x }) => x)(citaj(PUTANJE.podesavanja)),
};
const L = ulaz.proizvodi as Product[];
const by = (slug: string) => { const p = L.find((x) => x.slug === slug); if (!p) throw new Error('Nema proizvoda ' + slug); return p; };
const has = (slug: string) => L.some((x) => x.slug === slug);

// ——— prave fotografije (scripts/prave-fotografije.json, redom iz mapa.json) ———
type Foto = { fajl: string; slug: string; src: string; og: string; w: number; h: number; alt: string; motiv?: string };
const FOTO: Foto[] = citaj('scripts/prave-fotografije.json');
// tacna: prvo fotografije tacne, pa dve iz kompleta
const redTacne = (f: Foto) => (f.slug !== 'rebrasta-ovalna-tacna' ? 0 : f.fajl.startsWith('tacna/') ? 0 : 1);
const prave = (slug: string): Img[] => FOTO.filter((f) => f.slug === slug)
  .map((f, i) => ({ f, i })).sort((a, b) => redTacne(a.f) - redTacne(b.f) || a.i - b.i)
  .map(({ f }) => ({ src: f.src, w: f.w, h: f.h, alt: f.alt, og: f.og, ...(f.motiv ? { motiv: f.motiv } : {}) }));
// postojeće fotografije ostaju iza novih (to su prave, obrađene fotografije, nisu AI); prva nova je slika za deljenje (og)
// opts.ai: samo za fotografije koje su zaista napravljene uz AI (dobijaju napomenu ispod)
function dodajPrave(p: Product, opts: { ai?: boolean; ukloni?: string[]; boja?: string } = {}) {
  const nove = prave(p.slug);
  if (opts.boja) nove.forEach((im) => (im.boja = opts.boja));
  const stare = p.images.filter((im) => !nove.some((n) => n.src === im.src) && !(opts.ukloni || []).includes(im.src))
    .map((im) => (opts.ai && im.src.startsWith('/img/p/') ? { ...im, ai: true } : im));
  // og samo na prvoj fotografiji, kao kad se fotografija otpremi iz admina
  p.images = [...nove, ...stare];
}

// ——— rebrasta ovalna tacna (DM-04), ranije NG-17 „Novogodišnja tacna za figure” ———
const STARA_TACNA = 'novogodisnja-tacna-za-figure';
const TACNA = 'rebrasta-ovalna-tacna';
if (has(STARA_TACNA)) {
  const t = by(STARA_TACNA);
  t.id = 'DM-04';
  t.slug = TACNA;
}
{
  const t = by(TACNA);
  Object.assign(t, {
    id: 'DM-04', title: 'Rebrasta ovalna tacna', category: 'dom-i-kuhinja', categories: ['dom-i-kuhinja', 'nova-godina'], tags: ['dekoracija', 'sto'],
    short: 'Ovalna tacna sa rebrastim obodom, kao keramika, a ne lomi se. Podloga za naše figure ili tacna za sitnice.',
    lead: 'Ovalna tacna sa rebrastim obodom koji izgleda kao keramika, a ne lomi se. Na njoj naše figure – irvas, Sneško, Deda Mraz i jelke – čine malu prazničnu scenu, a posle praznika drži ključeve, daljinski, nakit, sveće i sitnice.',
    about: [
      'Obod tacne je rebrast, pa izgleda kao keramika ili glazirana glina, a štampana je od PLA plastike i ne lomi se kad padne.',
      'Za praznike na nju poređajte irvasa, Sneška i Deda Mraza u pletenom izgledu, spiralnu jelku ili jelku sa postoljem: figure stoje zajedno i od njih nastaje mala scena na stolu, komodi ili prozoru.',
      'Posle praznika tacna ostaje u kući: na komodi ili u hodniku drži ključeve, daljinski, nakit, sveće i sitnice. Tacna je samo za dekoraciju i sitnice, nije za hranu.',
    ],
    specs: [['Oblik', 'ovalna tacna sa rebrastim obodom'], ['Materijal', 'PLA plastika'], ['Mere', 'javljamo na upit'], ['Boja', 'izbor boje kao kod ostalih proizvoda'], ['Namena', 'dekoracija i sitnice, nije za hranu']],
    faq: [
      { q: 'Može li tacna za hranu?', a: 'Ne. Tacna je samo za dekoraciju i sitnice (ključevi, daljinski, nakit, sveće), nije za hranu.' },
      { q: 'Da li se lomi?', a: 'Ne lomi se kao keramika. Štampana je od PLA plastike, pa podnosi i pad sa stola.' },
      { q: 'Ima li popusta ako uzmem i figure?', a: 'Ima. Za 2 različita proizvoda iz grupe (lutkice, spiralna jelka, jelka sa postoljem, tacna) popust je 5%, za 3 različita 10%, a za 4 i više 15%.' },
    ],
    seoTitle: 'Rebrasta ovalna tacna, izgled keramike | Bumbaru',
    meta: 'Ovalna tacna sa rebrastim obodom, kao keramika, a ne lomi se. Podloga za novogodišnje figure, posle praznika tacna za ključeve i sitnice. 590 din.',
    price: 590,
    colors: [{ id: 'boja', label: 'Boja', def: 'bela' }], // breskva/bež nije na stanju: najbliža je bela (krem)
    komplet: { uz: ['deda-mraz-lutkica-pleteni-izgled', 'snesko-lutkica-pleteni-izgled'], popust: 10 },
    related: ['deda-mraz-lutkica-pleteni-izgled', 'snesko-lutkica-pleteni-izgled', 'irvas-lutkica-pleteni-izgled', 'spiralna-jelka-sa-zvezdom', 'jelka-sa-postoljem'],
  });
  dodajPrave(t);
}

// ——— jelka sa postoljem (NG-18): cene po visini prelaze sa mini jelke ———
const mini = by('mini-jelka-za-sto');
if (!has('jelka-sa-postoljem')) {
  const i = L.indexOf(by('spiralna-jelka-sa-zvezdom'));
  L.splice(i + 1, 0, {
    id: 'NG-18', slug: 'jelka-sa-postoljem', title: 'Jelka sa postoljem', category: 'nova-godina', categories: ['nova-godina'], tags: ['sto'],
    short: 'Jelka na postolju za sto, policu ili prozor. 15, 10 ili 7 cm.',
    lead: 'Zelena jelka na postolju, sa vrhom na kraju. Stoji sama na stolu, polici ili prozoru, a lepa je i na tacni uz naše lutkice.',
    custom: false, price: 590, images: [],
    options: [{ id: 'velicina', label: 'Visina', values: ['15 cm', '10 cm', '7 cm'], cene: [590, 390, 290] }],
    colors: [{ id: 'boja', label: 'Boja jelke', def: 'zelena' }, { id: 'zvezda', label: 'Boja postolja i vrha', def: 'zlatna' }],
    about: [
      'Jelka ima tri dela: postolje, jelku i vrh. Stoji sama, pa je lepa na stolu, polici, prozoru ili radnom stolu.',
      'Birate visinu (15, 10 ili 7 cm) i boje, kao kod ostalih naših jelki. Više jelki različite visine zajedno izgleda kao mala šuma.',
      'Uz lutkice u pletenom izgledu i rebrastu tacnu čini malu prazničnu scenu, a za različite proizvode dobijate popust.',
    ],
    specs: [['Delovi', 'postolje, jelka i vrh'], ['Visina', '15, 10 ili 7 cm'], ['Boje', 'izbor boje kao kod ostalih jelki'], ['Materijal', 'PLA plastika']],
    details: JSON.parse(JSON.stringify(mini.details)),
    faq: [
      { q: 'Mogu li dve jelke različite visine?', a: 'Možete. Izaberite prvu visinu i dodajte u korpu, pa izaberite drugu i dodajte i nju.' },
      { q: 'Ima li popusta uz lutkice?', a: 'Ima. Za 2 različita proizvoda iz grupe (lutkice, spiralna jelka, jelka sa postoljem, tacna) popust je 5%, za 3 različita 10%, a za 4 i više 15%.' },
    ],
    related: [TACNA, 'spiralna-jelka-sa-zvezdom', 'irvas-lutkica-pleteni-izgled', 'snesko-lutkica-pleteni-izgled', 'deda-mraz-lutkica-pleteni-izgled'],
    seoTitle: 'Jelka sa postoljem za sto, 15, 10 ili 7 cm | Bumbaru',
    meta: 'Jelka na postolju za sto, policu ili prozor: postolje, jelka i vrh, tri visine (15, 10 i 7 cm). Od 290 din, plaćanje pouzećem.',
  } as Product);
}
dodajPrave(by('jelka-sa-postoljem'));

// ——— NG-07 mini jelka i NG-13 jelkica (pleteni izgled): na upit, van popusta ———
for (const slug of ['mini-jelka-za-sto', 'jelkica-pleteni-izgled']) {
  const p = by(slug);
  delete p.price; delete p.priceFrom; delete p.komplet; delete p.skriven;
  p.naUpit = true;
  p.options = (p.options || []).map((o) => { const { cene, stare, ...x } = o; return x; });
  p.related = p.related.filter((r) => r !== STARA_TACNA);
}
by('mini-jelka-za-sto').related = ['jelka-sa-postoljem', 'spiralna-jelka-sa-zvezdom', 'zlatna-jelka-za-sklapanje', 'patuljak-sa-led-svecicom'];

// ——— lutkice, spiralna jelka, jelkica za punjenje: prave fotografije ispred AI fotografija ———
for (const slug of ['deda-mraz-lutkica-pleteni-izgled', 'snesko-lutkica-pleteni-izgled', 'irvas-lutkica-pleteni-izgled', 'spiralna-jelka-sa-zvezdom']) {
  const p = by(slug);
  dodajPrave(p);
  p.related = [...new Set([TACNA, ...p.related.map((r) => (r === STARA_TACNA ? TACNA : r === 'mini-jelka-za-sto' ? 'jelka-sa-postoljem' : r))])].slice(0, 4);
}
{
  const p = by('jelkica-za-punjenje');
  const crvena = (p.colors || []).length > 0; // izbor boje nudi sve boje na stanju, među njima i crvenu
  dodajPrave(p, crvena ? { boja: 'crvena' } : {});
}

// ——— NG-01 ukras za jelku sa imenom: 4 motiva kao pravi ukrasi ———
const MOTIVI_TXT = 'Zvezdice i jelke, Mašnica, Kapica Deda Mraza, Velika pahulja';
{
  const p = by('ukras-za-jelku-sa-imenom');
  dodajPrave(p, { ukloni: ['/img/c/ukras-luka'] });
  // crteži Sofija i Mila na kraj galerije
  p.images = [...p.images.filter((im) => !im.src.startsWith('/img/c/')), ...p.images.filter((im) => im.src.startsWith('/img/c/'))];
  p.short = 'Ukras sa imenom koje upišete: 4 motiva, belo ime preko crvenog ukrasa, latinica ili ćirilica.';
  p.lead = 'Ukras za jelku sa imenom koje upišete, ispisanim pisanim slovima preko obruča. Ime je izdignuto preko podloge koja prati oblik slova, a ime i ukras su jedan komad, pa nema lepljenja i ništa ne otpada.';
  p.about = [
    'Birate jedan od četiri motiva: zvezdice i jelke, mašnicu, kapicu Deda Mraza ili veliku pahulju. Ukras je najlepši crven sa belim imenom, kao na fotografijama, a boje možete i da promenite. Ime pišete latinicom ili ćirilicom, do 12 slova, i odmah vidite pregled na strani.',
    p.about[1],
    p.about[2],
  ];
  p.specs = p.specs.map(([k, v]) => (k === 'Motivi' ? [k, MOTIVI_TXT] : k === 'Boje ukrasa' ? [k, 'crvena (kao na fotografijama) ili druga boja na stanju'] : k === 'Boja imena' ? [k, 'bela (kao na fotografijama) ili druga boja; ime je izdignuto preko podloge u boji ukrasa'] : [k, v]) as [string, string]);
  p.faq = [
    { q: 'Koji motivi postoje?', a: 'Četiri: zvezdice i jelke, mašnica, kapica Deda Mraza i velika pahulja. Kad izaberete motiv, galerija pokazuje fotografije baš tog ukrasa.' },
    ...p.faq,
  ];
  p.meta = 'Ukras za jelku sa imenom koje upišete, latinicom ili ćirilicom. 4 motiva: zvezdice i jelke, mašnica, kapica Deda Mraza, velika pahulja. Pregled uživo, plaćanje pouzećem.';
  p.related = ['porodicni-set-ukrasa-sa-imenima', 'rotirajuci-ukras-sa-imenom', 'privezak-deda-mraz', 'jelkica-za-punjenje'];
}

// ——— NG-02 porodični set: ista 4 motiva, isti za sve ili različit za svako ime ———
{
  const p = by('porodicni-set-ukrasa-sa-imenima');
  dodajPrave(p);
  p.short = '4 ili 5 ukrasa sa imenima, u jednoj kutiji. Isti motiv za sve ili različit za svako ime.';
  p.about[0] = 'Birate motiv: isti za sve ukrase ili različit za svako ime (zvezdice i jelke, mašnica, kapica Deda Mraza, velika pahulja). Boje birate: svi u istoj boji ili svaki ukras u drugoj. Imena pišete latinicom ili ćirilicom, do 12 slova po imenu, i vidite ceo set u pregledu pre slanja poruke.';
  p.specs = p.specs.map(([k, v]) => (k === 'Motiv' ? [k, `isti za sve ili različit za svako ime: ${MOTIVI_TXT}`] : [k, v]) as [string, string]);
  p.faq = p.faq.map((f) => (/drugi motiv/.test(f.q) ? { q: f.q, a: 'Može. Izaberite „Različit za svako ime”, pa pored svakog imena izaberite motiv.' } : f));
  p.meta = '4 ili 5 ukrasa za jelku sa imenima cele porodice, u jednoj kutiji. Isti motiv za sve ili različit za svako ime, pregled uživo. Izrada 3–5 radnih dana.';
  p.related = ['ukras-za-jelku-sa-imenom', 'rotirajuci-ukras-sa-imenom', 'porodicni-privezak-sa-imenima', 'patuljak-sa-led-svecicom'];
}

// ——— NG-17 rotirajući ukras sa imenom ———
if (!has('rotirajuci-ukras-sa-imenom')) {
  const n1 = by('ukras-za-jelku-sa-imenom');
  const i = L.indexOf(n1);
  L.splice(i + 1, 0, {
    id: 'NG-17', slug: 'rotirajuci-ukras-sa-imenom', title: 'Rotirajući ukras sa imenom', category: 'nova-godina', categories: ['nova-godina', 'pokloni-sa-imenom'], tags: [...(n1.tags || [])],
    short: 'Pahulja sa imenom i dva prstena koji se okreću. Jedan komad, ništa se ne lepi.',
    lead: 'Pahulja sa imenom u sredini i dva prstena oko nje. Delovi se okreću nezavisno, pa ukras na jelki stalno menja izgled.',
    custom: true, price: 590, personal: 'rotirajuci', images: [],
    about: [
      'Pahulja sa imenom je u sredini, a oko nje su dva prstena. Svaki deo se okreće sam, pa ukras na jelki stalno menja izgled, čim ga dotakne vazduh ili ruka.',
      'Ukras štampamo sklopljen, iz jednog komada: prstenovi se okreću odmah posle štampe i ništa se ne lepi. Ime upišete latinicom ili ćirilicom, do 10 slova, i birate boju ukrasa i boju imena.',
      'Izrada traje 3–5 radnih dana, zatim šaljemo kurirom širom Srbije, a plaćate pouzećem. Za Novu godinu porudžbine sa imenom primamo do 16. 12.',
    ],
    specs: [['Ime', 'do 10 slova, latinica ili ćirilica'], ['Boje', 'ukras i ime u bojama po izboru (početno crvena i bela)'], ['Izrada', 'štampa se sklopljen, iz jednog komada, delovi se okreću'], ['Materijal', 'PLA plastika'], ['Rok', n1.specs.find(([k]) => k === 'Rok')?.[1] || 'izrada 3–5 radnih dana'], ['Plaćanje', 'pouzećem, kuriru']],
    details: JSON.parse(JSON.stringify(n1.details)),
    faq: [
      { q: 'Da li se delovi stvarno okreću?', a: 'Okreću se. Pahulja i oba prstena se okreću nezavisno, a ukras je jedan komad, bez lepljenja.' },
      { q: 'Koliko slova može ime?', a: 'Do 10 slova, latinicom ili ćirilicom. Za duže ime pišite nam u Messenger-u.' },
      ...n1.faq.filter((f) => /Nove godine|poklon na drugu/.test(f.q)),
    ],
    related: ['ukras-za-jelku-sa-imenom', 'porodicni-set-ukrasa-sa-imenima', 'privezak-deda-mraz', 'jelkica-za-punjenje'],
    seoTitle: 'Rotirajući ukras za jelku sa imenom | Bumbaru',
    meta: 'Rotirajući ukras za jelku: pahulja sa imenom i dva prstena koji se okreću, iz jednog komada. Ime do 10 slova, boje po izboru, plaćanje pouzećem.',
  } as Product);
}
dodajPrave(by('rotirajuci-ukras-sa-imenom'));

// ——— veze: stara tacna i mini jelka u grupi ———
for (const p of L) {
  p.related = [...new Set(p.related.map((r) => (r === STARA_TACNA ? TACNA : r)))];
  if (p.komplet) p.komplet.uz = p.komplet.uz.map((r) => (r === STARA_TACNA ? TACNA : r));
}

// ——— akcija −10% samo za ukrase sa imenom ———
const pod = ulaz.podesavanja as any;
if (pod.akcija) pod.akcija.samo = ['NG-01', 'NG-17'];

const out = ocistiSve(ulaz);
// nijedna šifra ni adresa dva puta (model to proverava), ali i provera koja ostaje u izveštaju
const sifre = out.proizvodi.map((p) => p.id);
if (new Set(sifre).size !== sifre.length) throw new Error('Šifra dva puta!');
const f = fajlovi(out);
writeFileSync(PUTANJE.proizvodi, f.proizvodi);
writeFileSync(PUTANJE.podesavanja, f.podesavanja);
console.log('Proizvoda:', out.proizvodi.length, '| NG-17:', out.proizvodi.find((p) => p.id === 'NG-17')?.slug, '| NG-18:', out.proizvodi.find((p) => p.id === 'NG-18')?.slug, '| DM-04:', out.proizvodi.find((p) => p.id === 'DM-04')?.slug);
