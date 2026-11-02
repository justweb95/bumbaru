// Siluete proizvoda za izbor boja (npr. klipovi, privezak-stalak): crtež koji se oboji u svaku boju na stanju.
// Crtež je u boji `currentColor`, pa ga oboji `color` na <svg> elementu koji ga koristi (<use href="#sil-<id>">).
// Debljina (bočna strana) je ista putanja pomerena naniže i potamnjena, a odsjaj je beli prelaz preko lica.

export interface Silueta { id: string; label: string; viewBox: string; symbol: string }

// Zupci duž uspravne ivice: od y0 do y1 na x, vrh zupca pomeren za dx (znak određuje stranu)
function zupci(x: number, y0: number, y1: number, dx: number, korak = 5) {
  let d = '';
  for (let y = y0; y + korak <= y1 + 0.01; y += korak) d += ` L ${x} ${y} L ${x + dx} ${y + korak / 2}`;
  return d + ` L ${x} ${y1}`;
}
// Rebra (urezi) na spoljnoj ivici nožice
function rebra(x: number, y0: number, y1: number, dx: number, korak = 6) {
  let d = '';
  for (let y = y0; y + korak <= y1 + 0.01; y += korak) d += ` L ${x} ${y} L ${x + dx} ${y + 1} L ${x + dx} ${y + korak - 2} L ${x} ${y + korak - 1}`;
  return d + ` L ${x} ${y1}`;
}

// Klip za kesice: dve zaobljene ručice sa prorezima, nazubljena vilica u sredini i dve nožice sa rebrima
const klipPut = [
  'M 8 27 A 19 19 0 0 1 46 27',
  zupci(46, 27, 82, 3.2),
  'L 46 86 C 39 86 36 92 36 97 C 36 104 42 108 50 108 C 58 108 64 104 64 97 C 64 92 61 86 54 86 L 54 82',
  // desna strana vilice ide naviše
  (() => { let d = ''; const k = 5; for (let y = 82; y - k >= 27 - 0.01; y -= k) d += ` L 54 ${y} L ${54 - 3.2} ${y - k / 2}`; return d + ' L 54 27'; })(),
  'A 19 19 0 0 1 92 27 L 92 130',
  rebra(92, 130, 160, -3.4).replace(/^ L 92 130/, ''),
  'L 92 160 Q 92 164 88 164 L 68 164 Q 64 164 64 160 L 64 128 Q 64 122 58 122 L 42 122 Q 36 122 36 128 L 36 160 Q 36 164 32 164 L 12 164 Q 8 164 8 160',
  // leva nožica: rebra odozdo naviše
  (() => { let d = ''; const k = 6; for (let y = 160; y - k >= 130 - 0.01; y -= k) d += ` L 8 ${y} L ${8 + 3.4} ${y - 1} L ${8 + 3.4} ${y - k + 2} L 8 ${y - k + 1}`; return d; })(),
  'L 8 27 Z',
  // prorezi u ručicama
  'M 19 30 A 8 8 0 0 1 35 30 L 35 80 A 8 8 0 0 1 19 80 Z',
  'M 65 30 A 8 8 0 0 1 81 30 L 81 80 A 8 8 0 0 1 65 80 Z',
].join(' ');

// Privezak-stalak (sklopljen): telo sa rupom za kariku, ploča za telefon i šarka, metalna karika
const stalakTelo = 'M 26 44 H 74 A 12 12 0 0 1 86 56 V 152 A 12 12 0 0 1 74 164 H 26 A 12 12 0 0 1 14 152 V 56 A 12 12 0 0 1 26 44 Z M 50 50 A 5.5 5.5 0 1 0 50 61 A 5.5 5.5 0 1 0 50 50 Z';

const lice = (id: string, dy: number) => `
    <use href="#${id}-p" transform="translate(0 ${dy})" fill="currentColor" />
    <use href="#${id}-p" transform="translate(0 ${dy})" fill="#1d130c" fill-opacity=".38" />
    <use href="#${id}-p" fill="currentColor" />
    <use href="#${id}-p" fill="url(#sil-hl)" />
    <use href="#${id}-p" fill="none" stroke="#1d130c" stroke-opacity=".16" stroke-width="1" />`;

export const SILUETE: Silueta[] = [
  {
    id: 'klip',
    label: 'Klip za kesice',
    viewBox: '0 0 100 172',
    symbol: `<path id="sil-klip-p" fill-rule="evenodd" d="${klipPut}" />${lice('sil-klip', 6)}`,
  },
  {
    id: 'stalak',
    label: 'Privezak-stalak za telefon',
    viewBox: '0 0 100 172',
    symbol: `<path id="sil-stalak-p" fill-rule="evenodd" d="${stalakTelo}" />${lice('sil-stalak', 7)}
    <rect x="25" y="72" width="50" height="36" rx="5" fill="#1d130c" fill-opacity=".12" />
    <rect x="25" y="72" width="50" height="36" rx="5" fill="none" stroke="#fff" stroke-opacity=".22" stroke-width="1.2" />
    <path d="M 20 118 H 80" stroke="#1d130c" stroke-opacity=".35" stroke-width="2" />
    <path d="M 20 120.5 H 80" stroke="#fff" stroke-opacity=".25" stroke-width="1" />
    <rect x="24" y="126" width="52" height="30" rx="5" fill="#fff" fill-opacity=".08" />
    <rect x="11.5" y="113" width="5" height="12" rx="2.5" fill="#1d130c" fill-opacity=".3" />
    <rect x="83.5" y="113" width="5" height="12" rx="2.5" fill="#1d130c" fill-opacity=".3" />
    <path d="M 50 55.5 C 30 55 24 36 32 22 C 40 8 62 8 69 22 C 75 34 68 50 56 54" fill="none" stroke="url(#sil-metal)" stroke-width="4.2" stroke-linecap="round" />`,
  },
];

export const silueta = (id?: string) => SILUETE.find((s) => s.id === id);

// Zajednički prelazi (odsjaj i metal) za sve siluete
export const SILUETE_DEFS = `
  <linearGradient id="sil-hl" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".42" /><stop offset=".5" stop-color="#fff" stop-opacity=".06" /><stop offset="1" stop-color="#fff" stop-opacity="0" /></linearGradient>
  <linearGradient id="sil-metal" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#E6E8EA" /><stop offset=".5" stop-color="#9AA1A8" /><stop offset="1" stop-color="#5F666D" /></linearGradient>`;
