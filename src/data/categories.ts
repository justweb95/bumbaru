// Kategorije kataloga. Adrese su iste kao u SEO planu (/katalog/<kategorija>/),
// da bi kasniji prelazak na WordPress + WooCommerce zadržao iste linkove.

export interface Faq { q: string; a: string }
export interface Category {
  slug: string;
  label: string; // kratko ime: meni, oznaka iznad naslova
  title: string; // H1
  text: string; // uvod ispod naslova
  seoTitle: string;
  meta: string;
  bg: string; // pozadina trake na vrhu kategorije
  bgPos?: string;
  icon: string; // putanja linijske ikonice 24×24
  filters?: { id: string; label: string }[];
  soon?: boolean;
  about: { h2: string; p: string[] };
  faq: Faq[];
}

export const CATEGORIES: Category[] = [
  {
    slug: 'nova-godina',
    label: 'Nova godina',
    title: 'Novogodišnji ukrasi i pokloni',
    text: 'Ukrasi za jelku sa imenom, porodični set, privesci i dekoracija za praznični sto. Porudžbine sa imenom primamo do 16. 12, a ostale do 20. 12.',
    seoTitle: 'Novogodišnji ukrasi i pokloni sa imenom | Bumbaru',
    meta: 'Ukrasi za jelku sa imenom, porodični set, novogodišnji privesci, patuljak sa LED svećicom i dekoracija za praznični sto. Poručivanje preko Messengera.',
    bg: '/img/bg/hero-nova-godina.webp',
    bgPos: 'right top',
    icon: 'M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z',
    filters: [
      { id: 'sve', label: 'Sve' },
      { id: 'ime', label: 'Sa imenom' },
      { id: 'privesci', label: 'Privesci' },
      { id: 'sto', label: 'Za sto i policu' },
      { id: 'jelka', label: 'Za jelku' },
    ],
    about: {
      h2: 'Ukrasi za jelku sa imenom, za celu porodicu',
      p: [
        'Svaki ukras je kugla sa imenom ispisanim preko obruča, od PLA plastike, u jednom komadu. Birate jedan od četiri motiva i boju, a ime pišete latinicom ili ćirilicom, do 12 slova.',
        'Za 4 ili 5 imena uzmite porodični set ukrasa sa imenima: svi ukrasi u istom motivu, u jednoj kutiji. Izrada traje 3–5 radnih dana, plaćate pouzećem, a porudžbine sa imenom primamo do 16. 12.',
      ],
    },
    faq: [
      { q: 'Mogu li ime ćirilicom?', a: 'Možete. Štampamo tačno kako upišete, latinicom ili ćirilicom.' },
      { q: 'Stiže li do Nove godine?', a: 'Stiže ako poručite do 16. 12. Izrada traje 3–5 radnih dana, a kurir nosi 1–2 radna dana.' },
      { q: 'Mogu li da pošaljem poklon na drugu adresu?', a: 'Možete, ali paket plaća onaj ko ga prima, jer je plaćanje pouzećem. Poruku za karticu u paketu napišite nam u Messenger-u, do 120 znakova.' },
    ],
  },
  {
    slug: 'radionica',
    label: 'Radionica',
    title: 'Držači za aku alat i pribor za radionicu',
    text: 'Zidni držači za Parkside X20 V, držači za baterije drugih brendova, police za aku alat i distanceri za montažu kuhinje.',
    seoTitle: 'Držači za aku alat i baterije, pribor za radionicu | Bumbaru',
    meta: 'Zidni držači za Parkside X20 V alat i baterije, držači za baterije drugih brendova i distanceri za kuhinjske frontove. PETG, poručivanje preko Messengera.',
    bg: '/img/bg/radionica.webp',
    icon: 'M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z',
    about: {
      h2: 'Držači za Parkside X20 V i alat drugih brendova',
      p: [
        'Set držača za Parkside X20 V ima četiri komada: dva za alat i dva za baterije. Kače se na zid, dasku ili ploču sa rupama, pa alat visi sa baterijom, a rezervne baterije stoje pored njega. Štampamo ih od PETG plastike, koja ne puca od udaraca i podnosi toplotu u garaži.',
        'Za Einhell, Bosch, Makita i drugi alat pravimo držač po meri: pošaljite fotografiju baterije u Messenger-u.',
      ],
    },
    faq: [
      { q: 'Imam Einhell, Bosch ili Makita alat.', a: 'Pošaljite nam fotografiju baterije u Messenger-u, pa javljamo cenu i rok.' },
      { q: 'Imate li distancere drugih debljina?', a: 'Pravimo po meri; pošaljite upit.' },
      { q: 'Kada stiže?', a: 'Proizvode na stanju šaljemo odmah po potvrdi porudžbine, a kurir nosi 1–2 radna dana.' },
    ],
  },
  {
    slug: 'auto-i-moto',
    label: 'Auto i moto',
    title: 'Držač tablice, nosač nalepnice i oprema za motor',
    text: 'Držač tablice bez okvira, nosač registracione nalepnice, nastavak za podmazivanje lanca i sitnice za auto i garažu.',
    seoTitle: 'Držač tablice bez okvira, nosač nalepnice za motor | Bumbaru',
    meta: 'Držač tablice bez okvira, nosač registracione nalepnice za motor i nastavak za podmazivanje lanca. 3D štampa, poručivanje preko Messengera.',
    bg: '/img/bg/auto-moto.webp',
    icon: 'M5 16h14v-4l-2-5H7l-2 5zM8 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM16 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
    about: {
      h2: 'Držač tablice bez okvira: kako se postavlja',
      p: [
        'Držač tablice bez okvira dolazi u paru. Oba držača idu na postojeće rupe za tablicu, bez bušenja, a tablica se utisne u njih i drži čvrsto. Rama oko tablice nema, pa se vide sva slova i brojevi. Odgovara za standardnu tablicu 520 × 110 mm.',
        'Za motor, skuter i kvad imamo nosač registracione nalepnice, okrugli ili pravougaoni, u crnoj ili beloj boji.',
      ],
    },
    faq: [
      { q: 'Vidi li se cela tablica?', a: 'Vidi. Držač ne prekriva nijedno slovo ni broj.' },
      { q: 'Odgovara li za moj auto?', a: 'Odgovara za standardnu tablicu 520 × 110 mm sa dve rupe za šrafove. Ako niste sigurni, pošaljite fotografiju mesta za tablicu.' },
      { q: 'Može li za motor?', a: 'Držač tablice je za auto. Za motor, skuter i kvad imamo nosač registracione nalepnice.' },
    ],
  },
  {
    slug: 'pokloni-sa-imenom',
    label: 'Pokloni sa imenom',
    title: 'Pokloni sa imenom',
    text: 'Ukrasi, porodični set i privesci sa imenima, latinicom ili ćirilicom. Izrada 3–5 radnih dana, a pre štampe proveravamo svako ime.',
    seoTitle: 'Pokloni sa imenom, latinicom ili ćirilicom | Bumbaru',
    meta: 'Ukras za jelku sa imenom, porodični set ukrasa, privezak sa imenom i porodični privezak. Ime latinicom ili ćirilicom, izrada 3–5 radnih dana.',
    bg: '/img/bg/kraft.webp',
    icon: 'M3 9h18v12H3zM12 9v12M3 13h18M12 9c-2-4-6-4-6-1.5S10 9 12 9c2 0 6 0 6-1.5S14 5 12 9z',
    about: {
      h2: 'Personalizovani pokloni sa imenom, latinicom ili ćirilicom',
      p: [
        'Svaki poklon sa imenom štampamo posebno, tačno kako upišete ime, latinicom ili ćirilicom. Pre štampe proveravamo svako ime, a ako nešto nije jasno, javljamo vam se.',
        'Ukras za jelku sa imenom je mali poklon za dete, kumče ili par, a porodični set ima 4 ili 5 imena u jednoj kutiji. Porodični privezak nosi 2–4 imena i ide uz ključeve cele godine. Izrada traje 3–5 radnih dana, a plaćate pouzećem.',
      ],
    },
    faq: [
      { q: 'Mogu li ime ćirilicom?', a: 'Možete. Štampamo tačno kako upišete, latinicom ili ćirilicom.' },
      { q: 'Koliko traje izrada?', a: '3–5 radnih dana, a kurir nosi 1–2 radna dana. Za Novu godinu porudžbine sa imenom primamo do 16. 12.' },
      { q: 'Mogu li da vratim poklon sa imenom?', a: 'Ne zbog predomišljanja, jer ga pravimo samo za vas. Ako stigne oštećen ili sa našom greškom, šaljemo novi bez troška.' },
    ],
  },
  {
    slug: 'privesci',
    label: 'Privesci',
    title: 'Privesci sa imenom i privesci koji nešto rade',
    text: 'Novogodišnji privesci, privezak sa imenom, pikselasti privezak za decu i privezak koji se rasklopi u stalak za telefon. Mali poklon koji se nosi svaki dan.',
    seoTitle: 'Privesci sa imenom i novogodišnji privesci | Bumbaru',
    meta: 'Privezak Deda Mraz, privezak irvas, privezak sa imenom, pikselasti privezak za decu i privezak-stalak za telefon. Poručivanje preko Messengera.',
    bg: '/img/bg/kraft.webp',
    icon: 'M8 3a5 5 0 1 0 0 10 5 5 0 0 0 0-10zM11.5 11.5L20 20M16 16l2.5-2.5M18.5 18.5l2.5-2.5',
    about: {
      h2: 'Privesci sa imenom, za decu i odrasle',
      p: [
        'Privezak je najmanji poklon koji se nosi svaki dan: na ključevima, rancu ili torbi. Ime štampamo latinicom ili ćirilicom, u dve boje.',
        'Za decu imamo pikselasti privezak sa slovima od kockica, a za celu porodicu porodični privezak sa 2–4 imena. Za praznike tu su novogodišnji privesci Deda Mraz i irvas, spakovani kao mali poklon.',
      ],
    },
    faq: [
      { q: 'Mogu li ime ćirilicom?', a: 'Možete. Štampamo tačno kako upišete, latinicom ili ćirilicom.' },
      { q: 'Da li je privezak igračka?', a: 'Nije. Sitni delovi nisu za decu mlađu od 3 godine.' },
      { q: 'Mogu li da vratim privezak sa imenom?', a: 'Ne zbog predomišljanja, jer ga pravimo samo za vas. Ako stigne oštećen ili sa našom greškom, šaljemo novi bez troška.' },
    ],
  },
  {
    slug: 'igracke-za-decu',
    label: 'Igračke za decu',
    title: 'Igračke za decu',
    text: 'Prve igračke od 3D štampe stižu uskoro. Igračke stavljamo u prodaju tek kad prođu proveru bezbednosti.',
    seoTitle: 'Igračke za decu, uskoro | Bumbaru',
    meta: 'Igračke od 3D štampe stižu uskoro. Pišite nam na Messenger-u šta vaše dete voli, pa vam javljamo kad krenu.',
    bg: '/img/bg/kraft.webp',
    icon: 'M4 14h7v7H4zM13 14h7v7h-7zM8.5 4.5h7v7h-7z',
    soon: true,
    about: { h2: '', p: [] },
    faq: [],
  },
];

export const categoryBySlug = (slug: string) => CATEGORIES.find((c) => c.slug === slug)!;
export const catUrl = (slug: string) => `/katalog/${slug}/`;
