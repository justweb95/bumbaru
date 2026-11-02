// Kviz „Nađi poklon”: pitanja i za koga/povod/stil je koji proizvod.
// Proizvodi koji nisu u spisku dobijaju podrazumevane oznake po kategoriji (pa i novi proizvodi iz admina ulaze u kviz).
import type { Product } from '../data/products';

export interface Odgovor { id: string; label: string; slika?: string; ikonica: string }
export interface Pitanje { id: 'za' | 'povod' | 'stil' | 'budzet'; naslov: string; odgovori: Odgovor[] }

// slika = adresa proizvoda čija se fotografija prikazuje na pločici
export const PITANJA: Pitanje[] = [
  { id: 'za', naslov: 'Za koga je poklon?', odgovori: [
    { id: 'dete', label: 'Za dete', slika: 'pikselasti-privezak-sa-imenom', ikonica: 'star' },
    { id: 'ona', label: 'Za nju', slika: 'rebrasta-vaza-sa-tacnom-i-svecnjakom', ikonica: 'sparkle' },
    { id: 'on', label: 'Za njega', slika: 'privezak-i-stalak-za-telefon', ikonica: 'tool' },
    { id: 'porodica', label: 'Za celu porodicu', slika: 'porodicni-set-ukrasa-sa-imenima', ikonica: 'gift' },
    { id: 'kolege', label: 'Za kolege ili firmu', slika: 'mini-jelka-za-sto', ikonica: 'box' },
    { id: 'dom', label: 'Za sebe i dom', slika: 'podmetaci-monstera-sa-saksijom', ikonica: 'bulb' },
  ] },
  { id: 'povod', naslov: 'Koji je povod?', odgovori: [
    { id: 'nova-godina', label: 'Nova godina', slika: 'novogodisnja-kucica-sa-led-svecicom', ikonica: 'star' },
    { id: 'rodjendan', label: 'Rođendan', slika: 'privezak-sa-imenom', ikonica: 'gift' },
    { id: 'useljenje', label: 'Useljenje, novi stan', slika: 'rebrasta-vaza-sa-tacnom-i-svecnjakom', ikonica: 'bulb' },
    { id: 'paznja', label: 'Mali znak pažnje', slika: 'irvas-lutkica-pleteni-izgled', ikonica: 'sparkle' },
  ] },
  { id: 'stil', naslov: 'Šta bi ga najviše obradovalo?', odgovori: [
    { id: 'ime', label: 'Nešto sa imenom', slika: 'ukras-za-jelku-sa-imenom', ikonica: 'pen' },
    { id: 'korisno', label: 'Nešto korisno', slika: 'klipovi-za-kesice', ikonica: 'tool' },
    { id: 'lepo', label: 'Lepa dekoracija', slika: 'spiralna-jelka-sa-zvezdom', ikonica: 'sparkle' },
    { id: 'zabavno', label: 'Nešto šareno i zabavno', slika: 'snesko-lutkica-pleteni-izgled', ikonica: 'palette' },
  ] },
  { id: 'budzet', naslov: 'Koliko želite da potrošite?', odgovori: [
    { id: 'do-500', label: 'Do 500 din', ikonica: 'cash' },
    { id: 'do-1500', label: '500 do 1.500 din', ikonica: 'cash' },
    { id: 'preko-1500', label: 'Preko 1.500 din', ikonica: 'gift' },
    { id: 'svejedno', label: 'Svejedno', ikonica: 'sparkle' },
  ] },
];

type Profil = { za: string[]; povod: string[]; stil: string[] };
const SVI_ZA = ['dete', 'ona', 'on', 'porodica', 'kolege', 'dom'];
const SVI_POVODI = ['nova-godina', 'rodjendan', 'useljenje', 'paznja'];
const PO_KATEGORIJI: Record<string, Profil> = {
  'nova-godina': { za: SVI_ZA, povod: ['nova-godina', 'paznja'], stil: ['lepo'] },
  'pokloni-sa-imenom': { za: SVI_ZA, povod: SVI_POVODI, stil: ['ime'] },
  privesci: { za: ['dete', 'ona', 'on', 'kolege'], povod: ['rodjendan', 'paznja', 'nova-godina'], stil: ['zabavno', 'korisno'] },
  'dom-i-kuhinja': { za: ['ona', 'dom', 'porodica'], povod: ['useljenje', 'rodjendan', 'paznja'], stil: ['lepo', 'korisno'] },
  radionica: { za: ['on', 'dom'], povod: ['rodjendan', 'useljenje', 'paznja'], stil: ['korisno'] },
  'auto-i-moto': { za: ['on'], povod: ['rodjendan', 'paznja'], stil: ['korisno'] },
};
const P = (za: string[], povod: string[], stil: string[]): Profil => ({ za, povod, stil });
const LUTKICA = P(['dete', 'ona', 'porodica', 'kolege'], ['nova-godina', 'paznja', 'rodjendan'], ['zabavno', 'lepo']);
const IME = P(['dete', 'ona', 'on', 'kolege'], ['rodjendan', 'paznja', 'nova-godina'], ['ime', 'zabavno']);
const PROFILI: Record<string, Profil> = {
  'ukras-za-jelku-sa-imenom': P(SVI_ZA, ['nova-godina'], ['ime', 'lepo']),
  'porodicni-set-ukrasa-sa-imenima': P(['porodica', 'ona', 'dom'], ['nova-godina'], ['ime', 'lepo']),
  'privezak-deda-mraz': P(['dete', 'kolege', 'ona', 'on'], ['nova-godina', 'paznja'], ['zabavno']),
  'privezak-irvas': P(['dete', 'kolege', 'ona', 'on'], ['nova-godina', 'paznja'], ['zabavno']),
  'patuljak-sa-led-svecicom': P(['ona', 'porodica', 'dom', 'kolege'], ['nova-godina', 'paznja'], ['lepo']),
  'prstenovi-za-salvete-set-od-6': P(['dom', 'porodica', 'ona'], ['nova-godina'], ['lepo', 'korisno']),
  'mini-jelka-za-sto': P(['kolege', 'dom', 'ona', 'on'], ['nova-godina', 'paznja'], ['lepo']),
  'zlatna-jelka-za-sklapanje': P(['kolege', 'ona', 'dom'], ['nova-godina', 'paznja'], ['lepo', 'zabavno']),
  'jelkica-za-punjenje': P(['dete', 'porodica'], ['nova-godina'], ['zabavno']),
  'novogodisnje-modlice-za-medenjake': P(['porodica', 'ona', 'dete', 'dom'], ['nova-godina'], ['korisno', 'zabavno']),
  'irvas-lutkica-pleteni-izgled': LUTKICA,
  'snesko-lutkica-pleteni-izgled': LUTKICA,
  'jelkica-pleteni-izgled': LUTKICA,
  'deda-mraz-lutkica-pleteni-izgled': LUTKICA,
  'novogodisnja-kucica-sa-led-svecicom': P(['porodica', 'dete', 'ona', 'dom'], ['nova-godina'], ['lepo', 'zabavno']),
  'spiralna-jelka-sa-zvezdom': P(['dom', 'ona', 'kolege'], ['nova-godina', 'useljenje'], ['lepo']),
  'porodicni-privezak-sa-imenima': P(['porodica'], SVI_POVODI, ['ime']),
  'privezak-sa-imenom': IME,
  'privezak-sa-imenom-bajkovita-slova': P(['dete', 'ona'], ['rodjendan', 'paznja', 'nova-godina'], ['ime', 'zabavno']),
  'privezak-sa-imenom-kockice': P(['dete', 'on'], ['rodjendan', 'paznja', 'nova-godina'], ['ime', 'zabavno']),
  'pikselasti-privezak-sa-imenom': P(['dete'], ['rodjendan', 'paznja', 'nova-godina'], ['ime', 'zabavno']),
  'privezak-i-stalak-za-telefon': P(['on', 'ona', 'kolege'], ['paznja', 'rodjendan', 'nova-godina'], ['korisno', 'zabavno']),
  'klipovi-za-kesice': P(['dom', 'porodica', 'kolege'], ['useljenje', 'paznja'], ['korisno', 'zabavno']),
  'rebrasta-vaza-sa-tacnom-i-svecnjakom': P(['ona', 'dom'], ['useljenje', 'rodjendan'], ['lepo']),
  'podmetaci-monstera-sa-saksijom': P(['ona', 'dom', 'kolege'], ['useljenje', 'rodjendan', 'paznja'], ['lepo', 'korisno']),
};
// Grupe sličnih proizvoda: u predlozima najviše dva iz iste grupe, da izbor bude raznovrstan
const GRUPE: Record<string, string> = {
  'irvas-lutkica-pleteni-izgled': 'lutkice', 'snesko-lutkica-pleteni-izgled': 'lutkice', 'jelkica-pleteni-izgled': 'lutkice', 'deda-mraz-lutkica-pleteni-izgled': 'lutkice',
  'privezak-sa-imenom': 'ime', 'privezak-sa-imenom-bajkovita-slova': 'ime', 'privezak-sa-imenom-kockice': 'ime', 'pikselasti-privezak-sa-imenom': 'ime',
  'privezak-deda-mraz': 'ng-privesci', 'privezak-irvas': 'ng-privesci',
};
export const grupa = (p: Product) => GRUPE[p.slug] || p.slug;
export const profil = (p: Product): Profil => PROFILI[p.slug] || PO_KATEGORIJI[p.category] || { za: SVI_ZA, povod: SVI_POVODI, stil: [] };
