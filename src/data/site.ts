// Osnovna podešavanja sajta. Sve što se često menja je ovde.
// Prikaz cena se uključuje u admin panelu (src/data/podesavanja.json).
import podesavanja from './podesavanja.json';

// Akcija iz admina (Podešavanja): važi do kraja izabranog dana. Posle tog trenutka sajt sam sakrije akcijske cene
// (u pregledaču, i bez nove objave), a pri sledećoj objavi akcija nestaje i iz koda strane.
interface AkcijaPod { ukljucena: boolean; naziv: string; procenat: number; kategorija: string; do: string }
const ak = (podesavanja as { akcija?: AkcijaPod }).akcija;
const akKraj = ak ? `${ak.do}T23:59:59+01:00` : '';
const AKCIJA = ak && ak.ukljucena && Date.now() <= Date.parse(akKraj)
  ? { naziv: ak.naziv, procenat: ak.procenat, kategorija: ak.kategorija, do: ak.do, kraj: akKraj }
  : null;

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

  // Cene: dok nema firme i potvrđenih cena, cene se ne prikazuju (prekidač je u admin panelu → Podešavanja).
  showPrices: podesavanja.prikaziCene === true,
  akcija: podesavanja.prikaziCene === true ? AKCIJA : null,

  // Sezona na početnoj strani: 'nova-godina' ili 'stalna'
  season: 'nova-godina' as 'nova-godina' | 'stalna',
  ribbon: {
    'nova-godina': 'Isporuka širom Srbije za 2–3 radna dana',
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

// „31. 12.” iz „2026-12-31”
export const datumKratko = (iso: string) => { const [, m, d] = iso.split('-').map(Number); return `${d}. ${m}.`; };
