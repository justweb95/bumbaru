// Osnovna podešavanja sajta. Sve što se često menja je ovde.

export const SITE = {
  name: 'Bumbaru',
  url: 'https://bumbaru.sajt.net',
  lang: 'sr-Latn',
  description:
    'Ukrasi za jelku sa imenom, novogodišnji privesci i dekoracija, delovi za radionicu i auto. 3D štampa iz Leskovca, dostava kurirom širom Srbije.',
  brandSentence:
    'Pokloni sa imenom i delovi po meri. Sve štampamo, proveravamo i pakujemo sami, a šaljemo kurirom širom Srbije.',
  city: 'Leskovac, Srbija',

  // Facebook stranica i Messenger (korisničko ime stranice je bumbaru.rs)
  facebookUrl: 'https://www.facebook.com/bumbaru.rs',
  messengerPage: 'bumbaru.rs',
  // Instagram: upisati punu adresu kad nalog bude spreman, npr. 'https://www.instagram.com/bumbaru.rs/'
  instagramUrl: '',

  // Cene: dok nema firme i potvrđenih cena, cene se ne prikazuju.
  // Kad budu spremne, upišite cene u src/data/products.ts i ovde stavite true.
  showPrices: false,

  // Sezona na početnoj strani: 'nova-godina' ili 'stalna'
  season: 'nova-godina' as 'nova-godina' | 'stalna',
  ribbon: {
    'nova-godina': 'Sa imenom do 16. 12, ostale porudžbine do 20. 12.',
    stalna: 'Proizvodi na stanju šalju se isti dan.',
  },
};

export const messengerUrl = (text?: string, ref?: string) => {
  const base = `https://m.me/${SITE.messengerPage}`;
  const q: string[] = [];
  if (text) q.push('text=' + encodeURIComponent(text));
  if (ref) q.push('ref=' + encodeURIComponent(ref));
  return q.length ? `${base}?${q.join('&')}` : base;
};

export const din = (n: number) => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' din';
