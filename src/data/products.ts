// Proizvodi. Novi proizvod = novi objekat u nizu PRODUCTS.
// Slike: /public/img/p/<slug>/<n>-lg.webp (cela) i <n>-sq.webp (kvadrat za kartice); prva slika je sa žigom.
// Boje: colors: [{ id, label, def }] nudi boje iz admin panela (src/data/boje.json).
// Cene: upisuje se broj (npr. price: 390). Prikazuju se tek kad je SITE.showPrices = true.

import type { Faq } from './categories';

export interface Img { src: string; w: number; h: number; alt: string }
export interface Option { id: string; label: string; values: string[] }
export type Personal = 'ukras' | 'set' | 'privezak';
// Izbor boje iz liste boja filamenta (admin panel); def = id podrazumevane boje
export interface ColorChoice { id: string; label: string; def: string }
// Privezak sa imenom: stilovi slova (src/lib/keychain.ts) i broj imena (svako ime je jedan privezak)
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
  about: string[];
  specs: [string, string][];
  details: Faq[];
  faq: Faq[];
  related: string[];
  seoTitle: string;
  meta: string;
}

const p = (slug: string, n: number, w: number, h: number, alt: string): Img => ({ src: `/img/p/${slug}/${n}`, w, h, alt });
const c = (name: string, w: number, h: number, alt: string): Img => ({ src: `/img/c/${name}`, w, h, alt });

const ROK_IME = 'Izrada 3–5 radnih dana, zatim kurir za 1–2 radna dana. Za Novu godinu porudžbine sa imenom primamo do 16. 12.';
const ROK = 'Rok slanja javljamo u odgovoru na vašu poruku. Kurir nosi 1–2 radna dana, a plaćate pouzećem, kuriru kad paket stigne.';
const PLA = 'Brišite suvom krpom. Ne ostavljajte ga pored grejanja ni na suncu iza stakla: PLA omekšava na oko 55–60 °C.';
const POVRACAJ_IME = 'Proizvod sa imenom pravimo samo za vas, pa ga ne primamo nazad zbog predomišljanja. Ako stigne oštećen ili sa našom greškom, šaljemo novi bez troška za vas.';
const POVRACAJ = 'Proizvod možete vratiti u roku od 14 dana od prijema, bez navođenja razloga. Ako stigne oštećen, pošaljite nam fotografiju u Messenger-u i šaljemo novi.';
const PRIVEZAK_FAQ: Faq[] = [
  { q: 'Da li je privezak igračka?', a: 'Nije. Sitni delovi i karika nisu za decu mlađu od 3 godine.' },
  { q: 'Mogu li više komada?', a: 'Možete. Upišite količinu pre slanja poruke ili nam je napišite u Messenger-u.' },
];
const IME_FAQ: Faq[] = [
  { q: 'Mogu li ime ćirilicom?', a: 'Možete. Štampamo tačno kako upišete, latinicom ili ćirilicom.' },
  { q: 'Mogu li više privezaka sa različitim imenima?', a: 'Možete. Kliknite „Dodajte još jedno ime”: svako ime je poseban privezak, a sva stižu u istom paketu.' },
  { q: 'Mogu li da biram boje?', a: 'Možete. Birate boju slova i osnove (a kod stilova sa okvirom i boju okvira) od boja koje trenutno imamo.' },
  { q: 'Da li je privezak igračka?', a: 'Nije. Sitni delovi i karika nisu za decu mlađu od 3 godine.' },
];
const PRIVEZAK_SPECS_BEZ_OKVIRA: [string, string][] = [
  ['Ime', 'do 12 slova, latinica ili ćirilica'],
  ['Boje', 'slova i osnova, po izboru'],
  ['Karika', 'metalna karika za ključeve'],
  ['Materijal', 'PLA plastika'],
  ['Rok', 'izrada 3–5 radnih dana'],
];
const PRIVEZAK_IME_SPECS: [string, string][] = [
  ['Ime', 'do 12 slova, latinica ili ćirilica'],
  ['Boje', 'slova, okvir i osnova, po izboru'],
  ['Karika', 'metalna karika za ključeve'],
  ['Materijal', 'PLA plastika'],
  ['Rok', 'izrada 3–5 radnih dana'],
];
const PRIVEZAK_IME_DETAILS: Faq[] = [
  { q: 'Rok izrade i dostava', a: ROK_IME },
  { q: 'Povraćaj i reklamacije', a: POVRACAJ_IME },
  { q: 'Održavanje', a: PLA },
];

export const PRODUCTS: Product[] = [
  // ——— NOVA GODINA: proizvodi sa imenom ———
  {
    id: 'NG-01',
    slug: 'ukras-za-jelku-sa-imenom',
    title: 'Ukras za jelku sa imenom',
    category: 'nova-godina',
    categories: ['nova-godina', 'pokloni-sa-imenom'],
    tags: ['ime', 'jelka'],
    short: 'Kugla sa imenom koje upišete. 4 motiva, 4 boje, latinica ili ćirilica.',
    lead: 'Kugla sa imenom koje upišete, ispisanim pisanim slovima preko obruča. Ime i ukras su jedan komad, pa nema lepljenja i ništa ne otpada.',
    custom: true,
    price: 390,
    images: [
      c('ukras-sofija', 1200, 1200, 'Beli ukras za jelku sa imenom Sofija i crvenom mašnom'),
      c('ukras-luka', 1200, 1200, 'Zeleni ukras za jelku sa planinama, šumom i imenom Luka'),
      c('ukras-mila', 1200, 1200, 'Beli ukras za jelku sa jelkama i crvenim imenom Mila'),
    ],
    personal: 'ukras',
    about: [
      'Birate jedan od četiri motiva i boju ukrasa, a ime može biti u istoj ili drugoj boji. Ime pišete latinicom ili ćirilicom, do 12 slova, i odmah vidite pregled na strani.',
      'Izrada traje 3–5 radnih dana, zatim šaljemo kurirom širom Srbije, a plaćate pouzećem. Za Novu godinu porudžbine sa imenom primamo do 16. 12.',
      'Ukras stiže u maloj kutiji sa podlogom i karticom, spreman za poklon. Lagan je i ne lomi se kao staklena kugla. Pre štampe proveravamo svako ime, a ako nešto nije jasno, pišemo vam.',
    ],
    specs: [
      ['Motivi', 'Zvezde i jelke, Planine i šuma, Mašna sa cvetom, Grančica sa bobicama'],
      ['Boje ukrasa', 'bela, crvena, zelena, zlatna'],
      ['Boja imena', 'kao ukras ili jedna od ostale tri boje, u istom komadu'],
      ['Ime', 'do 12 slova, latinica ili ćirilica, sa slovima č, ć, đ, š, ž'],
      ['Materijal', 'PLA plastika'],
      ['Pakovanje', 'mala kutija sa podlogom, nalepnicom i karticom'],
      ['Rok', 'izrada 3–5 radnih dana; za Novu godinu poslednja porudžbina 16. 12.'],
      ['Plaćanje', 'pouzećem, kuriru'],
    ],
    details: [
      { q: 'Pakovanje', a: 'Stiže u maloj kutiji sa podlogom, nalepnicom i karticom, spremno za poklon. Poruku za karticu napišite nam u Messenger-u, do 120 znakova.' },
      { q: 'Rok izrade i dostava', a: ROK_IME },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ_IME },
      { q: 'Održavanje', a: PLA },
    ],
    faq: [
      { q: 'Mogu li ime da napišem ćirilicom?', a: 'Možete. Štampamo tačno kako upišete, latinicom ili ćirilicom.' },
      { q: 'Šta ako ime ima više od 12 slova?', a: 'Pišite nam u Messenger-u, pa proveravamo da li staje.' },
      { q: 'Stiže li do Nove godine?', a: 'Stiže ako poručite do 16. 12. Izrada traje 3–5 radnih dana, a kurir nosi 1–2 radna dana.' },
      { q: 'Mogu li da ga pošaljem kao poklon na drugu adresu?', a: 'Možete, ali paket plaća onaj ko ga prima, jer je plaćanje pouzećem. Ako to ne želite, pošaljite paket na svoju adresu.' },
      { q: 'Kako da poručim više imena?', a: 'Za 4 ili 5 imena uzmite porodični set. Za drugi broj imena napišite sva imena u istoj poruci.' },
    ],
    related: ['porodicni-set-ukrasa-sa-imenima', 'privezak-deda-mraz', 'patuljak-sa-led-svecicom', 'jelkica-za-punjenje'],
    seoTitle: 'Ukras za jelku sa imenom, kugla po meri | Bumbaru',
    meta: 'Ukras za jelku sa imenom koje upišete, latinicom ili ćirilicom. 4 motiva i 4 boje, pregled uživo. Izrada 3–5 radnih dana, plaćanje pouzećem.',
  },
  {
    id: 'NG-02',
    slug: 'porodicni-set-ukrasa-sa-imenima',
    title: 'Porodični set ukrasa sa imenima',
    category: 'nova-godina',
    categories: ['nova-godina', 'pokloni-sa-imenom'],
    tags: ['ime', 'jelka'],
    short: '4 ili 5 ukrasa sa imenima, u jednoj kutiji. Isti motiv, boje po izboru.',
    lead: '4 ili 5 ukrasa za jelku, svaki sa jednim imenom, u jednoj kutiji. Poklon za celu porodicu, za kumove ili za baku i deku koji žele imena unučadi na jelki.',
    custom: true,
    price: 1590,
    priceFrom: true,
    images: [c('ukras-set', 1200, 1200, 'Porodični set od četiri ukrasa sa imenima Marko, Jelena, Luka i Mila')],
    personal: 'set',
    about: [
      'Svi ukrasi imaju isti motiv, a boje birate: svaki ukras u drugoj boji ili svi u istoj. Imena pišete latinicom ili ćirilicom, do 12 slova po imenu, i vidite ceo set u pregledu pre slanja poruke.',
      'Izrada traje 3–5 radnih dana, a plaćate pouzećem. Za Novu godinu porudžbine sa imenom primamo do 16. 12.',
      'Pre štampe proveravamo sva imena, a ako nešto nije jasno, pišemo vam. Svako ime je spojeno sa obručem, pa je svaki ukras jedan komad.',
    ],
    specs: [
      ['U setu', '4 ili 5 ukrasa sa imenima, u kutiji sa podlogom, nalepnicom i karticom'],
      ['Motiv', 'jedan za ceo set, od četiri ponuđena'],
      ['Boje', 'Šareno (svaki ukras u drugoj boji) ili jedna boja za sve; ime u boji ukrasa ili u drugoj'],
      ['Imena', 'do 12 slova po imenu, latinica ili ćirilica, mogu se mešati'],
      ['Materijal', 'PLA plastika'],
      ['Rok', 'izrada 3–5 radnih dana; za Novu godinu do 16. 12.'],
    ],
    details: [
      { q: 'Pakovanje', a: 'Svi ukrasi stižu u jednoj kutiji sa podlogom, nalepnicom i karticom. Poruku za karticu napišite nam u Messenger-u, do 120 znakova.' },
      { q: 'Rok izrade i dostava', a: ROK_IME },
      { q: 'Povraćaj i reklamacije', a: 'Set pravimo samo za vas, pa ga ne primamo nazad zbog predomišljanja. Ako neki ukras stigne oštećen ili sa našom greškom, šaljemo novi bez troška.' },
      { q: 'Održavanje', a: 'Brišite suvom krpom. Ne ostavljajte ukrase pored grejanja ni na suncu iza stakla: PLA omekšava na oko 55–60 °C.' },
    ],
    faq: [
      { q: 'Može li svaki ukras da ima drugi motiv?', a: 'U setu svi imaju isti motiv. Za različite motive poručite pojedinačne ukrase.' },
      { q: 'Imamo 6 ili više imena. Šta da uradimo?', a: 'Uzmite set od 5 i u istoj poruci dopišite ostala imena, u istom motivu i boji.' },
      { q: 'Mogu li neka imena biti ćirilicom, a neka latinicom?', a: 'Mogu. Svako ime štampamo tačno kako ga upišete.' },
      { q: 'Da li je set dobar poklon za kumove?', a: 'Jeste: na kartici u paketu napišemo vašu poruku, do 120 znakova.' },
    ],
    related: ['ukras-za-jelku-sa-imenom', 'porodicni-privezak-sa-imenima', 'patuljak-sa-led-svecicom', 'mini-jelka-za-sto'],
    seoTitle: 'Porodični set ukrasa za jelku sa imenima | Bumbaru',
    meta: '4 ili 5 ukrasa za jelku sa imenima cele porodice, u jednoj kutiji. Isti motiv, boje po izboru, pregled uživo. Izrada 3–5 radnih dana.',
  },

  // ——— NOVA GODINA: proizvodi sa fotografijama ———
  {
    id: 'NG-03',
    slug: 'privezak-deda-mraz',
    title: 'Privezak Deda Mraz',
    category: 'nova-godina',
    categories: ['nova-godina', 'privesci'],
    tags: ['privesci'],
    short: 'Mali Deda Mraz za ključeve. Poklon uz čestitku ili u paketiću.',
    lead: 'Mali Deda Mraz sa karikom za ključeve. Lep je uz čestitku, u novogodišnjem paketiću ili kao sitnica za drugare i kolege.',
    images: [
      p('privezak-deda-mraz', 1, 1074, 976, 'Privezak Deda Mraz sa karikom, crveno-beli, izbliza'),
      p('privezak-deda-mraz', 2, 1086, 992, 'Privezak Deda Mraz u crnoj poklon kutiji sa karticom'),
      p('privezak-deda-mraz', 3, 1191, 896, 'Gomila privezaka Deda Mraz na drvenom stolu sa šišarkama'),
    ],
    about: [
      'Deda Mraz je štampan u više boja, sa karikom za ključeve, rančić ili torbu. Lagan je, a boje su u samoj plastici, pa se ne ljušte.',
      'Za paketiće u vrtiću, školi ili firmi poručite više komada u jednoj poruci. Uz privezak lepo ide i privezak irvas.',
    ],
    specs: [
      ['Boje', 'crvena, bela i crna, kao na fotografiji'],
      ['Karika', 'metalna karika za ključeve'],
      ['Materijal', 'PLA plastika'],
      ['Pakovanje', 'poklon pakovanje sa karticom'],
    ],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
      { q: 'Održavanje', a: PLA },
    ],
    faq: PRIVEZAK_FAQ,
    related: ['privezak-irvas', 'jelkica-za-punjenje', 'zlatna-jelka-za-sklapanje', 'ukras-za-jelku-sa-imenom'],
    seoTitle: 'Privezak Deda Mraz, novogodišnji poklon | Bumbaru',
    meta: 'Privezak Deda Mraz sa karikom za ključeve, u poklon pakovanju. Mali novogodišnji poklon uz čestitku ili za paketiće. Poručivanje preko Messengera.',
  },
  {
    id: 'NG-04',
    slug: 'privezak-irvas',
    title: 'Privezak irvas',
    category: 'nova-godina',
    categories: ['nova-godina', 'privesci'],
    tags: ['privesci'],
    short: 'Irvas sa crvenim nosom, za ključeve. Mali poklon uz čestitku.',
    lead: 'Mali irvas sa crvenim nosom i karikom za ključeve. Sitan poklon koji se nosi cele zime, a ne samo za praznike.',
    images: [
      p('privezak-irvas', 1, 1172, 896, 'Privezak irvas sa crvenim nosom i karikom za ključeve'),
      p('privezak-irvas', 2, 1172, 896, 'Privezak irvas u crnoj poklon kutiji sa karticom'),
      p('privezak-irvas', 3, 1172, 896, 'Više privezaka irvas na drvenom stolu'),
    ],
    about: [
      'Irvas je štampan u više boja, sa karikom za ključeve, rančić ili torbu. Boje su u samoj plastici, pa se ne ljušte.',
      'Za paketiće u vrtiću, školi ili firmi poručite više komada u jednoj poruci. Uz njega lepo ide i privezak Deda Mraz.',
    ],
    specs: [
      ['Boje', 'braon, bež, crvena i crna, kao na fotografiji'],
      ['Karika', 'metalna karika za ključeve'],
      ['Materijal', 'PLA plastika'],
      ['Pakovanje', 'poklon pakovanje sa karticom'],
    ],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
      { q: 'Održavanje', a: PLA },
    ],
    faq: PRIVEZAK_FAQ,
    related: ['privezak-deda-mraz', 'jelkica-za-punjenje', 'mini-jelka-za-sto', 'ukras-za-jelku-sa-imenom'],
    seoTitle: 'Privezak irvas, novogodišnji poklon | Bumbaru',
    meta: 'Privezak irvas sa crvenim nosom i karikom za ključeve, u poklon pakovanju. Mali novogodišnji poklon. Poručivanje preko Messengera.',
  },
  {
    id: 'NG-05',
    slug: 'patuljak-sa-led-svecicom',
    title: 'Patuljak sa LED svećicom',
    category: 'nova-godina',
    categories: ['nova-godina'],
    tags: ['sto'],
    short: 'Patuljak koji svetli kroz zvezdice. LED svećica u kompletu.',
    lead: 'Novogodišnji patuljak za sto, policu ili prozor. U otvor ispod brade stavlja se LED svećica, pa patuljak svetli kroz zvezdice na kapi. Svećica stiže u kompletu.',
    images: [
      p('patuljak-sa-led-svecicom', 1, 1000, 1200, 'Beli patuljak sa zvezdicama na kapi i upaljenom LED svećicom, pored jelke'),
      p('patuljak-sa-led-svecicom', 2, 1200, 900, 'Tri bela patuljka sa LED svećicama na prazničnom stolu'),
      p('patuljak-sa-led-svecicom', 3, 1200, 900, 'Tri motiva patuljka: sa fenjerom, sa zvezdicama i sa zvezdom'),
    ],
    options: [{ id: 'motiv', label: 'Motiv', values: ['Sa zvezdicama na kapi', 'Sa fenjerom', 'Sa zvezdom u ruci', 'Set od 3 motiva'] }],
    colors: [{ id: 'boja', label: 'Boja', def: 'bela' }],
    about: [
      'Patuljak je štampan od bele plastike, sa zvezdicama kroz koje prolazi svetlo. Imamo tri motiva: sa zvezdicama na kapi, sa fenjerom i sa zvezdom u ruci, a možete uzeti i sva tri.',
      'Patuljak ide samo sa LED svećicom, koju dobijate u kompletu. Pravu sveću ne stavljajte: plastika omekšava na toploti.',
    ],
    specs: [
      ['Motivi', 'sa zvezdicama na kapi, sa fenjerom, sa zvezdom u ruci'],
      ['Svetlo', 'LED svećica u kompletu'],
      ['Boja', 'bela, ili druga boja po izboru'],
      ['Materijal', 'PLA plastika'],
    ],
    details: [
      { q: 'Samo LED svećica', a: 'Patuljak je od plastike koja omekšava na oko 55–60 °C. Zato ide samo sa LED svećicom, nikad sa pravom svećom.' },
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
    ],
    faq: [
      { q: 'Može li prava sveća?', a: 'Ne. Prava sveća greje plastiku, pa patuljak ide samo sa LED svećicom, koja je u kompletu.' },
      { q: 'Mogu li sva tri motiva?', a: 'Možete. Izaberite „Set od 3 motiva” pre slanja poruke.' },
    ],
    related: ['mini-jelka-za-sto', 'prstenovi-za-salvete-set-od-6', 'zlatna-jelka-za-sklapanje', 'ukras-za-jelku-sa-imenom'],
    seoTitle: 'Patuljak sa LED svećicom, novogodišnja dekoracija | Bumbaru',
    meta: 'Novogodišnji patuljak koji svetli kroz zvezdice, LED svećica u kompletu. Tri motiva, za sto, policu ili prozor. Poručivanje preko Messengera.',
  },
  {
    id: 'NG-06',
    slug: 'prstenovi-za-salvete-set-od-6',
    title: 'Prstenovi za salvete, set od 6',
    category: 'nova-godina',
    categories: ['nova-godina'],
    tags: ['sto'],
    short: 'Šest novogodišnjih motiva, svako za stolom dobija svoj.',
    lead: 'Set od šest prstenova za salvete: sneško, zvezda, Deda Mraz, poklon, jelen i irvas. Salveta se provuče kroz prsten i sto je spreman za praznik.',
    images: [
      p('prstenovi-za-salvete-set-od-6', 1, 896, 1195, 'Crveni prstenovi za salvete sa novogodišnjim motivima na prazničnom stolu'),
      p('prstenovi-za-salvete-set-od-6', 2, 896, 1195, 'Salvete sa crvenim prstenovima: zvezda, jelen i Deda Mraz'),
      p('prstenovi-za-salvete-set-od-6', 3, 896, 1195, 'Šest motiva prstenova za salvete: sneško, zvezda, Deda Mraz, poklon, jelen i irvas'),
    ],
    colors: [{ id: 'boja', label: 'Boja', def: 'crvena' }],
    about: [
      'Svaki prsten ima drugi motiv, pa gosti lako zapamte svoje mesto za stolom. Ravni su i laki, a posle praznika staju u kovertu do sledeće godine.',
      'Set ima šest komada u jednoj boji, koju birate sami. Za veći sto poručite dva seta.',
    ],
    specs: [
      ['U setu', '6 prstenova: sneško, zvezda, Deda Mraz, poklon, jelen, irvas'],
      ['Boje', 'po izboru, od boja koje imamo'],
      ['Materijal', 'PLA plastika'],
    ],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
      { q: 'Održavanje', a: 'Brišite suvom ili blago vlažnom krpom. Ne perite u mašini za sudove.' },
    ],
    faq: [
      { q: 'Mogu li dva seta u različitim bojama?', a: 'Možete. Izaberite jednu boju, a drugu napišite u napomeni.' },
      { q: 'Odgovaraju li za platnene i papirne salvete?', a: 'Odgovaraju za obe vrste, salveta se samo provuče kroz prsten.' },
    ],
    related: ['patuljak-sa-led-svecicom', 'mini-jelka-za-sto', 'zlatna-jelka-za-sklapanje', 'porodicni-set-ukrasa-sa-imenima'],
    seoTitle: 'Prstenovi za salvete, novogodišnji set od 6 | Bumbaru',
    meta: 'Šest prstenova za salvete sa novogodišnjim motivima: sneško, zvezda, Deda Mraz, poklon, jelen i irvas. Crvena ili tirkizna boja.',
  },
  {
    id: 'NG-07',
    slug: 'mini-jelka-za-sto',
    title: 'Mini jelka za sto',
    category: 'nova-godina',
    categories: ['nova-godina'],
    tags: ['sto'],
    short: 'Mala jelka sa zvezdom za sto, policu ili radni sto.',
    lead: 'Mala zelena jelka sa zvezdom na vrhu, za praznični sto, policu ili radni sto. Ne treba joj ni voda ni struja, a svake godine izgleda isto.',
    images: [
      p('mini-jelka-za-sto', 1, 896, 1195, 'Zelena mini jelka sa zvezdom na prazničnom stolu, pored sveća'),
      p('mini-jelka-za-sto', 2, 896, 1195, 'Mini jelka za sto sa kuglama i svećama'),
    ],
    colors: [{ id: 'boja', label: 'Boja', def: 'zelena' }],
    about: [
      'Jelka ima slojevite grane, braon stablo i zvezdu na vrhu. Stoji sama, bez postolja, pa je lepa na stolu, polici ili prozoru.',
      'Dobar je mali poklon za kolege: ne zauzima mesto, a radni sto odmah izgleda praznično.',
    ],
    specs: [
      ['Boje', 'zelena jelka, braon stablo, zvezda'],
      ['Materijal', 'PLA plastika'],
    ],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
      { q: 'Održavanje', a: PLA },
    ],
    faq: [
      { q: 'Može li jelka u drugoj boji?', a: 'Može. Izaberite boju pre slanja poruke, od boja koje trenutno imamo.' },
    ],
    related: ['patuljak-sa-led-svecicom', 'zlatna-jelka-za-sklapanje', 'prstenovi-za-salvete-set-od-6', 'jelkica-za-punjenje'],
    seoTitle: 'Mini jelka za sto, novogodišnja dekoracija | Bumbaru',
    meta: 'Mala zelena jelka sa zvezdom za praznični sto, policu ili radni sto. Novogodišnja dekoracija i mali poklon za kolege.',
  },
  {
    id: 'NG-08',
    slug: 'zlatna-jelka-za-sklapanje',
    title: 'Zlatna jelka za sklapanje',
    category: 'nova-godina',
    categories: ['nova-godina'],
    tags: ['sto'],
    short: 'Stiže ravna, sklapate je sami. Staje u kovertu uz čestitku.',
    lead: 'Zlatna jelka koja stiže kao ravna ploča. Delove izvadite i uklopite, pa dobijete jelku koja stoji. Ravna je, pa staje u kovertu uz čestitku.',
    images: [
      p('zlatna-jelka-za-sklapanje', 1, 896, 1194, 'Sklopljena zlatna jelka i zlatna spirala na stolu'),
      p('zlatna-jelka-za-sklapanje', 2, 896, 1194, 'Ravni delovi zlatne jelke i spirala pre sklapanja'),
    ],
    colors: [{ id: 'boja', label: 'Boja', def: 'zlatna' }],
    about: [
      'Jelka se sastoji od dva ravna dela sa šarama koji se uklope jedan u drugi. Sklapa se za par sekundi, bez lepka i alata, a posle praznika se rasklopi i vrati u kovertu.',
      'Uz jelku ide i zlatna spirala za ukras. Lep je poklon koji se šalje u čestitki, umesto još jedne kartice.',
    ],
    specs: [
      ['U kompletu', 'jelka od dva dela i zlatna spirala'],
      ['Boja', 'zlatna'],
      ['Sklapanje', 'bez lepka i alata'],
      ['Materijal', 'PLA plastika'],
    ],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
      { q: 'Održavanje', a: PLA },
    ],
    faq: [
      { q: 'Da li je teško za sklapanje?', a: 'Nije. Dva dela se uklope jedan u drugi, bez lepka i alata.' },
      { q: 'Mogu li da je pošaljem u čestitki?', a: 'Možete, ravna je i staje u kovertu.' },
    ],
    related: ['mini-jelka-za-sto', 'privezak-deda-mraz', 'prstenovi-za-salvete-set-od-6', 'ukras-za-jelku-sa-imenom'],
    seoTitle: 'Zlatna jelka za sklapanje, poklon u koverti | Bumbaru',
    meta: 'Zlatna jelka koja stiže ravna i sklapa se bez lepka i alata. Staje u kovertu uz čestitku. Uz jelku ide zlatna spirala.',
  },
  {
    id: 'NG-09',
    slug: 'jelkica-za-punjenje',
    title: 'Jelkica za punjenje',
    category: 'nova-godina',
    categories: ['nova-godina'],
    tags: ['jelka'],
    short: 'Ukras za jelku koji punite sitnim poklonima ili umotanim bombonama.',
    lead: 'Jelkica od mrežaste plastike koja se kači na jelku ili stoji na stolu. Napunite je umotanim bombonama, sitnim poklonima ili porukama, pa deca imaju šta da otkriju za praznik.',
    images: [
      p('jelkica-za-punjenje', 1, 928, 1152, 'Tirkizna jelkica za punjenje napunjena bombonama, pored jelke'),
      p('jelkica-za-punjenje', 2, 928, 1152, 'Tirkizna jelkica za punjenje okačena na jelku'),
      p('jelkica-za-punjenje', 3, 928, 1152, 'Crvena jelkica za punjenje okačena na jelku'),
    ],
    colors: [{ id: 'boja', label: 'Boja', def: 'tirkizna' }],
    about: [
      'Jelkica ima mrežaste zidove, pa se vidi šta je unutra, i kukicu za kačenje na jelku. Može i da stoji na stolu ili polici.',
      'Bombone stavljajte umotane. Jelkica je ukras, nije posuda za hranu.',
    ],
    specs: [
      ['Boje', 'po izboru, od boja koje imamo'],
      ['Kačenje', 'kukica na vrhu'],
      ['Materijal', 'PLA plastika'],
    ],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
      { q: 'Održavanje', a: PLA },
    ],
    faq: [
      { q: 'Mogu li da stavim bombone?', a: 'Možete, ali samo umotane. Jelkica je ukras, nije posuda za hranu.' },
      { q: 'Može li više komada u različitim bojama?', a: 'Može. Napišite nam u poruci koliko komada i koje boje želite.' },
    ],
    related: ['privezak-deda-mraz', 'privezak-irvas', 'ukras-za-jelku-sa-imenom', 'mini-jelka-za-sto'],
    seoTitle: 'Jelkica za punjenje, ukras za jelku | Bumbaru',
    meta: 'Ukras za jelku u obliku jelkice koji punite sitnim poklonima ili umotanim bombonama. Tirkizna ili crvena. Poručivanje preko Messengera.',
  },

  // ——— POKLONI SA IMENOM I PRIVESCI ———
  {
    id: 'PK-01',
    slug: 'porodicni-privezak-sa-imenima',
    title: 'Porodični set privezaka sa imenima',
    category: 'pokloni-sa-imenom',
    categories: ['pokloni-sa-imenom', 'privesci', 'nova-godina'],
    tags: ['ime', 'privesci'],
    short: 'Privezak sa imenom za svakog u porodici. Birate stil slova i boje.',
    lead: 'Za svakog člana porodice po jedan privezak sa imenom, u istom stilu slova. Birate jedan od pet stilova, a boje slova mogu biti iste ili svaka druga.',
    custom: true,
    images: [
      p('porodicni-privezak-sa-imenima', 1, 1200, 1200, 'Privesci sa imenima Dijana, Mateo, Luka, Sofija, Ema, Noa, Olivia i Gabrijel u bajkovitim slovima'),
      p('porodicni-privezak-sa-imenima', 2, 1024, 1024, 'Privesci sa imenima pisanim slovima u više boja: Milica, Tijana, Đurđa, Dunja, Sofija, Anđa'),
      p('porodicni-privezak-sa-imenima', 3, 1184, 1184, 'Privesci sa imenima Marija, Petar, Jovan i Milica ćirilicom, slova kao od kockica'),
      p('porodicni-privezak-sa-imenima', 4, 1024, 1024, 'Pikselasti privesci sa imenima Branko, Željko, Miloš i Vukašin'),
    ],
    personal: 'privezak',
    keychain: { stilovi: ['pisana', 'bajka', 'kockice', 'pikseli', 'stampana'], startImena: 3, minImena: 2, maxImena: 8, sareno: true, slike: { bajka: 0, pisana: 1, kockice: 2, pikseli: 3 } },
    about: [
      'Svako ime je poseban privezak, a svi stižu zajedno u jednom paketu. Stil slova je isti za ceo set, a boje slova mogu biti šarene, svako ime u drugoj boji.',
      'Imena pišete latinicom ili ćirilicom i štampamo ih tačno kako ih upišete. Izrada traje 3–5 radnih dana, a plaćate pouzećem.',
    ],
    specs: [
      ['U setu', '2–8 privezaka, svaki sa jednim imenom'],
      ['Stil slova', 'pisana, bajkovita, kao od kockica, pikselasta ili štampana'],
      ['Boje', 'slova, osnova i okvir (kod stilova sa okvirom); svako ime može u drugoj boji'],
      ['Karika', 'metalna karika za svaki privezak'],
      ['Materijal', 'PLA plastika'],
      ['Rok', 'izrada 3–5 radnih dana'],
    ],
    details: PRIVEZAK_IME_DETAILS,
    faq: IME_FAQ,
    related: ['privezak-sa-imenom', 'privezak-sa-imenom-bajkovita-slova', 'privezak-sa-imenom-kockice', 'pikselasti-privezak-sa-imenom'],
    seoTitle: 'Porodični set privezaka sa imenima | Bumbaru',
    meta: 'Privezak sa imenom za svakog u porodici, u istom stilu slova i bojama po izboru. Latinica ili ćirilica, izrada 3–5 radnih dana.',
  },
  {
    id: 'PR-01',
    slug: 'privezak-sa-imenom',
    title: 'Privezak sa imenom',
    category: 'privesci',
    categories: ['privesci', 'pokloni-sa-imenom'],
    tags: ['ime', 'privesci'],
    short: 'Pisana ili štampana slova, boje birate sami.',
    lead: 'Privezak sa imenom, pisanim ili štampanim slovima. Slova i osnova su u bojama koje izaberete.',
    custom: true,
    images: [
      p('privezak-sa-imenom', 1, 1024, 1024, 'Privesci sa imenima pisanim slovima u više boja: Milica, Tijana, Đurđa, Dunja, Sofija, Anđa, Mia, Slađa, Maša, Nikolina'),
      p('privezak-sa-imenom', 2, 1024, 1024, 'Beli privezak sa imenom štampanim crnim slovima, pored privezaka sa pisanim slovima'),
    ],
    personal: 'privezak',
    keychain: { stilovi: ['pisana', 'stampana'], startImena: 1, minImena: 1, maxImena: 6, sareno: true, slike: { pisana: 0, stampana: 1 } },
    about: [
      'Birate pisana ili štampana slova i boje za slova i osnovu. Ime pišete latinicom ili ćirilicom i štampamo ga tačno kako ga upišete.',
      'Za više imena dodajte još redova: svako ime je poseban privezak. Izrada traje 3–5 radnih dana, a plaćate pouzećem.',
    ],
    specs: PRIVEZAK_SPECS_BEZ_OKVIRA,
    details: PRIVEZAK_IME_DETAILS,
    faq: IME_FAQ,
    related: ['privezak-sa-imenom-bajkovita-slova', 'privezak-sa-imenom-kockice', 'pikselasti-privezak-sa-imenom', 'porodicni-privezak-sa-imenima'],
    seoTitle: 'Privezak sa imenom, boje po izboru | Bumbaru',
    meta: 'Privezak za ključeve sa imenom, pisanim ili štampanim slovima. Boje slova i osnove birate sami. Izrada 3–5 radnih dana.',
  },
  {
    id: 'PR-04',
    slug: 'privezak-sa-imenom-bajkovita-slova',
    title: 'Privezak sa imenom, bajkovita slova',
    category: 'privesci',
    categories: ['privesci', 'pokloni-sa-imenom'],
    tags: ['ime', 'privesci'],
    short: 'Razigrana slova kao iz crtanog filma. Boje birate sami.',
    lead: 'Privezak sa imenom ispisanim razigranim slovima, kao sa naslovne strane slikovnice: bela slova, okvir u jarkoj boji i crna osnova, a tačke na i i j su mala srca.',
    custom: true,
    images: [p('privezak-sa-imenom-bajkovita-slova', 1, 1200, 1200, 'Privesci sa imenima Dijana, Mateo, Luka, Sofija, Ema, Noa, Olivia i Gabrijel u bajkovitim slovima')],
    personal: 'privezak',
    keychain: { stilovi: ['bajka'], startImena: 1, minImena: 1, maxImena: 6, sareno: true },
    about: [
      'Slova su razigrana i debela, sa okvirom u drugoj boji, pa se ime vidi i izdaleka. Deca ga kače na ranac, a odrasli na ključeve.',
      'Za više imena dodajte još redova: svako ime je poseban privezak. Izrada traje 3–5 radnih dana, a plaćate pouzećem.',
    ],
    specs: PRIVEZAK_IME_SPECS,
    details: PRIVEZAK_IME_DETAILS,
    faq: IME_FAQ,
    related: ['privezak-sa-imenom', 'privezak-sa-imenom-kockice', 'pikselasti-privezak-sa-imenom', 'porodicni-privezak-sa-imenima'],
    seoTitle: 'Privezak sa imenom, bajkovita slova | Bumbaru',
    meta: 'Privezak za ključeve sa imenom u razigranim slovima kao iz crtanog filma. Boje po izboru. Izrada 3–5 radnih dana.',
  },
  {
    id: 'PR-05',
    slug: 'privezak-sa-imenom-kockice',
    title: 'Privezak sa imenom, slova kao od kockica',
    category: 'privesci',
    categories: ['privesci', 'pokloni-sa-imenom'],
    tags: ['ime', 'privesci'],
    short: 'Debela slova sa duplim okvirom, za male graditelje.',
    lead: 'Privezak sa imenom u debelim, kosim slovima sa duplim okvirom, kao na kutijama sa kockicama. Pravi poklon za male graditelje.',
    custom: true,
    images: [p('privezak-sa-imenom-kockice', 1, 1184, 1184, 'Privesci sa imenima Marija, Petar, Jovan i Milica ćirilicom, bela slova sa žutim i crvenim okvirom')],
    personal: 'privezak',
    keychain: { stilovi: ['kockice'], startImena: 1, minImena: 1, maxImena: 6, sareno: true },
    about: [
      'Bela slova sa žutim okvirom i crvenom osnovom su najpoznatija kombinacija, ali boje birate sami. Ime može latinicom ili ćirilicom.',
      'Za više imena dodajte još redova: svako ime je poseban privezak. Izrada traje 3–5 radnih dana, a plaćate pouzećem.',
    ],
    specs: PRIVEZAK_IME_SPECS,
    details: PRIVEZAK_IME_DETAILS,
    faq: IME_FAQ,
    related: ['privezak-sa-imenom-bajkovita-slova', 'pikselasti-privezak-sa-imenom', 'privezak-sa-imenom', 'porodicni-privezak-sa-imenima'],
    seoTitle: 'Privezak sa imenom, slova kao od kockica | Bumbaru',
    meta: 'Privezak za ključeve sa imenom u debelim slovima sa duplim okvirom, za ljubitelje kockica. Boje po izboru, latinica ili ćirilica.',
  },
  {
    id: 'PR-02',
    slug: 'pikselasti-privezak-sa-imenom',
    title: 'Pikselasti privezak sa imenom',
    category: 'privesci',
    categories: ['privesci', 'pokloni-sa-imenom'],
    tags: ['ime', 'privesci'],
    short: 'Pikselasta slova kao u video igrici, deca ga vole.',
    lead: 'Privezak sa imenom u pikselastim slovima, kao u video igrici. Deca ga kače na ranac, a odrasli na ključeve.',
    custom: true,
    images: [p('pikselasti-privezak-sa-imenom', 1, 1024, 1024, 'Pikselasti privesci sa imenima Branko, Željko, Miloš i Vukašin, zelena slova na crnoj osnovi')],
    personal: 'privezak',
    keychain: { stilovi: ['pikseli'], startImena: 1, minImena: 1, maxImena: 6, sareno: true },
    about: [
      'Ime štampamo velikim pikselastim slovima, u dve boje: slova i osnova. Najlepše izgleda sa kraćim imenima, do 8 slova.',
      'Za više imena dodajte još redova: svako ime je poseban privezak. Izrada traje 3–5 radnih dana, a plaćate pouzećem.',
    ],
    specs: [
      ['Ime', 'do 12 slova, najlepše do 8; latinica ili ćirilica'],
      ['Boje', 'slova i osnova, po izboru'],
      ['Karika', 'metalna karika za ključeve'],
      ['Materijal', 'PLA plastika'],
      ['Rok', 'izrada 3–5 radnih dana'],
    ],
    details: PRIVEZAK_IME_DETAILS,
    faq: IME_FAQ,
    related: ['privezak-sa-imenom-kockice', 'privezak-sa-imenom-bajkovita-slova', 'privezak-sa-imenom', 'porodicni-privezak-sa-imenima'],
    seoTitle: 'Pikselasti privezak sa imenom za decu | Bumbaru',
    meta: 'Privezak sa imenom u pikselastim slovima kao u video igrici. Boje po izboru. Za ranac ili ključeve, izrada 3–5 radnih dana.',
  },
  {
    id: 'PR-03',
    slug: 'privezak-i-stalak-za-telefon',
    title: 'Privezak i stalak za telefon',
    category: 'privesci',
    categories: ['privesci'],
    tags: ['privesci'],
    short: 'Rasklopi se i drži telefon na stolu.',
    lead: 'Privezak za ključeve koji se rasklopi u stalak za telefon. Uvek je pri ruci kad gledate video ili imate video poziv.',
    images: [],
    colors: [{ id: 'boja', label: 'Boja', def: 'plava' }],
    about: ['Sklopljen je mali privezak na ključevima, a rasklopljen drži telefon uspravno ili položeno na stolu.'],
    specs: [['Materijal', 'PLA plastika']],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
    ],
    faq: PRIVEZAK_FAQ,
    related: ['privezak-sa-imenom', 'pikselasti-privezak-sa-imenom', 'privezak-deda-mraz', 'privezak-irvas'],
    seoTitle: 'Privezak i stalak za telefon | Bumbaru',
    meta: 'Privezak za ključeve koji se rasklopi u stalak za telefon. 3D štampa, poručivanje preko Messengera.',
  },

  // ——— RADIONICA ———
  {
    id: 'RA-01',
    slug: 'drzac-za-aku-alat-parkside-x20v',
    title: 'Set držača za Parkside X20 V alat i baterije',
    category: 'radionica',
    categories: ['radionica'],
    short: '4 držača za zid: 2 za alat, 2 za baterije. PETG.',
    lead: 'Set od četiri zidna držača za Parkside X20 V: dva za alat sa baterijom i dva za rezervne baterije. Alat visi na zidu spreman za rad, a baterije stoje pored njega.',
    price: 1390,
    images: [],
    colors: [{ id: 'boja', label: 'Boja', def: 'crna' }],
    about: [
      'Držači su pravljeni po meri baterije Parkside X20 V. Štampamo ih od PETG plastike, koja ne puca od udaraca i podnosi toplotu u garaži.',
      'Imate alat drugog brenda? Pošaljite meru ili fotografiju baterije u Messenger-u, pa pravimo držač za vaš model.',
    ],
    specs: [
      ['Za', 'alat i baterije sistema Parkside X20 V'],
      ['U setu', '4 držača: 2 za alat, 2 za baterije'],
      ['Materijal', 'PETG'],
      ['Montaža', 'na zid, dasku ili ploču sa rupama, šrafovima'],
    ],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
    ],
    faq: [
      { q: 'Imam Einhell, Bosch ili Makita alat.', a: 'Pošaljite nam fotografiju baterije u Messenger-u, pa javljamo cenu i rok za držač po meri.' },
      { q: 'Mogu li da dokupim samo držač za bateriju?', a: 'Možete. Napišite nam u poruci koliko držača i za šta vam treba.' },
    ],
    related: ['zidni-drzac-za-bateriju', 'polica-i-drzac-za-aku-busilicu', 'distanceri-za-kuhinjske-frontove', 'drzac-tablice-bez-okvira'],
    seoTitle: 'Držač za Parkside X20 V alat i baterije, set | Bumbaru',
    meta: 'Zidni držači za Parkside X20 V: dva za alat, dva za baterije. PETG. Poručivanje preko Messengera, plaćanje pouzećem.',
  },
  {
    id: 'RA-02',
    slug: 'zidni-drzac-za-bateriju',
    title: 'Zidni držač za bateriju',
    category: 'radionica',
    categories: ['radionica'],
    short: 'Za vaš model baterije, i za druge brendove.',
    lead: 'Zidni držač za bateriju aku alata. Pravimo ga po modelu vaše baterije, pa baterija sedi čvrsto i ne pada.',
    custom: true,
    images: [],
    options: [{ id: 'model', label: 'Brend baterije', values: ['Parkside X20 V', 'Einhell', 'Bosch', 'Makita', 'DeWalt', 'Drugi (napišite u poruci)'] }],
    colors: [{ id: 'boja', label: 'Boja', def: 'crna' }],
    about: ['Napišite nam brend i model baterije, a ako niste sigurni, pošaljite fotografiju u Messenger-u. Štampamo od PETG plastike.'],
    specs: [['Materijal', 'PETG'], ['Montaža', 'na zid ili dasku, šrafovima']],
    details: [
      { q: 'Rok i dostava', a: 'Rok javljamo kad potvrdimo model baterije. Kurir nosi 1–2 radna dana, a plaćate pouzećem.' },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ_IME.replace('sa imenom', 'po meri') },
    ],
    faq: [{ q: 'Nemam model baterije na spisku.', a: 'Izaberite „Drugi” i pošaljite fotografiju baterije u Messenger-u.' }],
    related: ['drzac-za-aku-alat-parkside-x20v', 'polica-i-drzac-za-aku-busilicu', 'distanceri-za-kuhinjske-frontove', 'nastavak-za-podmazivanje-lanca'],
    seoTitle: 'Zidni držač za bateriju aku alata, po modelu | Bumbaru',
    meta: 'Zidni držač za bateriju aku alata, po modelu: Parkside, Einhell, Bosch, Makita i drugi. PETG, poručivanje preko Messengera.',
  },
  {
    id: 'RA-03',
    slug: 'polica-i-drzac-za-aku-busilicu',
    title: 'Polica i držač za aku bušilicu',
    category: 'radionica',
    categories: ['radionica'],
    short: 'Bušilica visi, a gore stoje bitovi i baterija.',
    lead: 'Zidna polica sa držačem za aku bušilicu. Bušilica visi ispod police, a gore stoje bitovi, burgije i rezervna baterija.',
    images: [],
    options: [{ id: 'model', label: 'Brend bušilice', values: ['Parkside X20 V', 'Einhell', 'Bosch', 'Makita', 'Drugi (napišite u poruci)'] }],
    colors: [{ id: 'boja', label: 'Boja', def: 'crna' }],
    about: ['Polica se kači na zid šrafovima. Štampamo je od PETG plastike, koja podnosi težinu alata i toplotu u garaži.'],
    specs: [['Materijal', 'PETG'], ['Montaža', 'na zid, šrafovima']],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
    ],
    faq: [{ q: 'Odgovara li za moju bušilicu?', a: 'Napišite nam brend i model, ili pošaljite fotografiju u Messenger-u.' }],
    related: ['drzac-za-aku-alat-parkside-x20v', 'zidni-drzac-za-bateriju', 'distanceri-za-kuhinjske-frontove', 'drzac-tablice-bez-okvira'],
    seoTitle: 'Polica i držač za aku bušilicu | Bumbaru',
    meta: 'Zidna polica sa držačem za aku bušilicu, mesto za bitove i bateriju. PETG, poručivanje preko Messengera.',
  },
  {
    id: 'RA-04',
    slug: 'distanceri-za-kuhinjske-frontove',
    title: 'Distanceri za kuhinjske frontove',
    category: 'radionica',
    categories: ['radionica'],
    short: 'Set 1–5 mm, debljina utisnuta na svakom komadu.',
    lead: 'Set distancera za montažu kuhinjskih frontova i vrata. Debljina je utisnuta na svakom komadu, pa se ne mešaju.',
    price: 990,
    images: [],
    colors: [{ id: 'boja', label: 'Boja', def: 'crna' }],
    about: ['Distanceri drže jednak razmak između frontova dok ih pritežete. Set ima debljine od 1 do 5 mm.'],
    specs: [['Debljine', '1–5 mm, utisnuto na svakom komadu'], ['Materijal', 'PETG']],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
    ],
    faq: [{ q: 'Imate li distancere drugih debljina?', a: 'Pravimo po meri; napišite nam u poruci koje debljine vam trebaju.' }],
    related: ['drzac-za-aku-alat-parkside-x20v', 'zidni-drzac-za-bateriju', 'polica-i-drzac-za-aku-busilicu', 'drzac-tablice-bez-okvira'],
    seoTitle: 'Distanceri za kuhinjske frontove, set 1–5 mm | Bumbaru',
    meta: 'Distanceri za montažu kuhinjskih frontova, set 1–5 mm sa utisnutom debljinom. PETG, poručivanje preko Messengera.',
  },

  // ——— AUTO I MOTO ———
  {
    id: 'AM-01',
    slug: 'drzac-tablice-bez-okvira',
    title: 'Držač tablice bez okvira, par',
    category: 'auto-i-moto',
    categories: ['auto-i-moto'],
    short: 'Par, crna boja. Tablica bez rama, postavlja se bez bušenja.',
    lead: 'Par držača za registarsku tablicu bez okvira. Idu na postojeće rupe, bez bušenja, a tablica se utisne u njih i drži čvrsto.',
    price: 790,
    images: [],
    colors: [{ id: 'boja', label: 'Boja', def: 'crna' }],
    about: [
      'Rama oko tablice nema, pa se vide sva slova i brojevi. Odgovara za standardnu tablicu 520 × 110 mm sa dve rupe za šrafove.',
      'Delove koji idu napolje štampamo od plastike koja podnosi sunce i kišu.',
    ],
    specs: [['U pakovanju', '2 držača'], ['Boja', 'crna, ili druga po izboru'], ['Za tablicu', '520 × 110 mm'], ['Montaža', 'na postojeće rupe, bez bušenja']],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
    ],
    faq: [
      { q: 'Vidi li se cela tablica?', a: 'Vidi. Držač ne prekriva nijedno slovo ni broj.' },
      { q: 'Odgovara li za moj auto?', a: 'Odgovara za standardnu tablicu sa dve rupe za šrafove. Ako niste sigurni, pošaljite fotografiju mesta za tablicu.' },
    ],
    related: ['nosac-registracione-nalepnice-za-motor', 'nastavak-za-podmazivanje-lanca', 'drzac-za-aku-alat-parkside-x20v', 'distanceri-za-kuhinjske-frontove'],
    seoTitle: 'Držač tablice bez okvira, par | Bumbaru',
    meta: 'Držač registarske tablice bez okvira, par, crna boja. Postavlja se na postojeće rupe bez bušenja, vide se sva slova.',
  },
  {
    id: 'AM-02',
    slug: 'nosac-registracione-nalepnice-za-motor',
    title: 'Nosač registracione nalepnice za motor',
    category: 'auto-i-moto',
    categories: ['auto-i-moto'],
    short: 'Za motor, skuter i kvad. Nalepnica ostaje vidljiva i na mestu.',
    lead: 'Nosač registracione nalepnice za motor, skuter i kvad. Nalepnica ostaje vidljiva, zaštićena i na mestu.',
    price: 690,
    images: [],
    options: [{ id: 'oblik', label: 'Oblik', values: ['Okrugli', 'Pravougaoni'] }],
    colors: [{ id: 'boja', label: 'Boja', def: 'crna' }],
    about: ['Delove koji idu napolje štampamo od plastike koja podnosi sunce i kišu. Ako niste sigurni gde ide na vašem motoru, pošaljite fotografiju u Messenger-u.'],
    specs: [['Oblik', 'okrugli ili pravougaoni'], ['Boja', 'po izboru, od boja koje imamo']],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
    ],
    faq: [{ q: 'Odgovara li za skuter?', a: 'Odgovara za motor, skuter i kvad. Ako niste sigurni, pošaljite fotografiju u Messenger-u.' }],
    related: ['drzac-tablice-bez-okvira', 'nastavak-za-podmazivanje-lanca', 'drzac-za-aku-alat-parkside-x20v', 'zidni-drzac-za-bateriju'],
    seoTitle: 'Nosač registracione nalepnice za motor | Bumbaru',
    meta: 'Nosač registracione nalepnice za motor, skuter i kvad, okrugli ili pravougaoni, crni ili beli. Poručivanje preko Messengera.',
  },
  {
    id: 'AM-03',
    slug: 'nastavak-za-podmazivanje-lanca',
    title: 'Nastavak za podmazivanje lanca',
    category: 'auto-i-moto',
    categories: ['auto-i-moto'],
    short: 'Nasadi se na sprej, a mazivo ide pravo na lanac.',
    lead: 'Nastavak koji se nasadi na sprej za lanac. Mazivo ide pravo na lanac, a ne na felnu i gumu.',
    images: [],
    colors: [{ id: 'boja', label: 'Boja', def: 'crna' }],
    about: ['Mazivo ide tamo gde treba, pa se manje rasipa i ne prlja felnu. Pre porudžbine nam napišite koji sprej koristite.'],
    specs: [['Za', 'sprej za lanac motora']],
    details: [
      { q: 'Rok i dostava', a: ROK },
      { q: 'Povraćaj i reklamacije', a: POVRACAJ },
    ],
    faq: [{ q: 'Odgovara li za moj sprej?', a: 'Napišite nam koji sprej koristite ili pošaljite fotografiju u Messenger-u.' }],
    related: ['nosac-registracione-nalepnice-za-motor', 'drzac-tablice-bez-okvira', 'drzac-za-aku-alat-parkside-x20v', 'zidni-drzac-za-bateriju'],
    seoTitle: 'Nastavak za podmazivanje lanca motora | Bumbaru',
    meta: 'Nastavak za sprej za lanac: mazivo ide pravo na lanac motora. 3D štampa, poručivanje preko Messengera.',
  },
];

export const productBySlug = (slug: string) => PRODUCTS.find((x) => x.slug === slug);
export const productUrl = (slug: string) => `/proizvod/${slug}/`;
export const inCategory = (cat: string) => PRODUCTS.filter((x) => x.categories.includes(cat));
export const hasPhoto = (x: Product) => x.images.length > 0;
// Kvadratna slika za kartice (fotografije imaju posebnu kvadratnu verziju sa žigom, crteži su već kvadratni)
export const cardImg = (i: Img) => (i.src.startsWith('/img/p/') ? `${i.src}-sq.webp` : `${i.src}-sm.webp`);
