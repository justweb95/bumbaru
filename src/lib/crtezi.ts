// Crteži proizvoda u bojama koje kupac izabere (npr. vaza, spiralna jelka, podmetači Monstera).
// Delovi crteža se boje redom izbora boja proizvoda: prvi izbor boje = --c1, drugi = --c2.
// Senke i odsjaji su crni i beli slojevi preko boje, pa crtež lepo izgleda u svakoj boji.

export interface Crtez { id: string; label: string; delovi: string[]; viewBox: string; svg: string }

const f = (n: number) => +n.toFixed(1);
const SENKA = 'fill:#1d130c';
const SVETLO = 'fill:#fff';

// Vertikalna rebra (tamna traka + svetla ivica) u okviru isečka
function rebra(clip: string, x0: number, x1: number, y0: number, y1: number, korak: number, sirina: number) {
  let s = `<g clip-path="url(#${clip})">`;
  for (let x = x0; x <= x1; x += korak) {
    s += `<rect x="${f(x)}" y="${y0}" width="${sirina}" height="${y1 - y0}" style="${SENKA};opacity:.13" />`;
    s += `<rect x="${f(x + sirina)}" y="${y0}" width="1.6" height="${y1 - y0}" style="${SVETLO};opacity:.22" />`;
  }
  return s + '</g>';
}
// Senčenje sleva nadesno (svetlo levo, senka desno)
const valjak = (id: string) => `<linearGradient id="${id}" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity=".28" /><stop offset=".38" stop-color="#fff" stop-opacity=".04" /><stop offset=".75" stop-color="#000" stop-opacity=".1" /><stop offset="1" stop-color="#000" stop-opacity=".32" /></linearGradient>`;

// ——— Vaza, tacna i svećnjak ———
const vazaTelo = 'M140 98 C 140 122 84 124 82 170 L 84 298 Q 85 312 100 312 L 220 312 Q 235 312 236 298 L 238 170 C 236 124 180 122 180 98 Z';
const vaza = `
  <defs>${valjak('cr-v-g')}<clipPath id="cr-v-telo"><path d="${vazaTelo}" /></clipPath><clipPath id="cr-v-sv"><rect x="262" y="252" width="84" height="62" rx="7" /></clipPath>
  <radialGradient id="cr-v-plamen"><stop offset="0" stop-color="#FFF6D6" /><stop offset=".5" stop-color="#FFD66B" /><stop offset="1" stop-color="#FFB13B" stop-opacity="0" /></radialGradient></defs>
  <ellipse cx="200" cy="352" rx="172" ry="14" style="${SENKA};opacity:.14" />
  <rect x="34" y="308" width="332" height="38" rx="19" style="fill:var(--c1)" />
  <rect x="34" y="308" width="332" height="38" rx="19" style="${SENKA};opacity:.28" />
  <rect x="34" y="304" width="332" height="30" rx="15" style="fill:var(--c1)" />
  <rect x="46" y="306" width="308" height="18" rx="9" style="${SENKA};opacity:.08" />
  <rect x="34" y="304" width="332" height="30" rx="15" style="fill:url(#cr-v-g)" />
  <path d="${vazaTelo}" style="fill:var(--c1)" />
  ${rebra('cr-v-telo', 86, 232, 96, 314, 9.5, 4)}
  <path d="${vazaTelo}" style="fill:url(#cr-v-g)" />
  <rect x="141" y="72" width="38" height="30" style="fill:var(--c1)" /><rect x="141" y="72" width="38" height="30" style="fill:url(#cr-v-g)" />
  <rect x="133" y="62" width="54" height="16" rx="6" style="fill:var(--c1)" /><rect x="133" y="62" width="54" height="16" rx="6" style="fill:url(#cr-v-g)" />
  <ellipse cx="160" cy="64" rx="19" ry="3.5" style="${SENKA};opacity:.35" />
  <rect x="262" y="252" width="84" height="62" rx="7" style="fill:var(--c1)" />
  ${rebra('cr-v-sv', 264, 344, 252, 314, 8, 3.4)}
  <rect x="262" y="252" width="84" height="62" rx="7" style="fill:url(#cr-v-g)" />
  <rect x="268" y="248" width="72" height="12" rx="6" style="fill:var(--c1)" /><rect x="268" y="248" width="72" height="12" rx="6" style="${SENKA};opacity:.3" />
  <rect x="286" y="244" width="36" height="10" rx="4" style="fill:#F4EFE6" /><rect x="286" y="244" width="36" height="10" rx="4" style="${SENKA};opacity:.08" />
  <circle cx="304" cy="226" r="26" style="fill:url(#cr-v-plamen);opacity:.9" />
  <path d="M304 210 C 298 220 297 230 304 236 C 311 230 310 220 304 210 Z" style="fill:#FFC34A" />
  <path d="M304 218 C 301 224 301 230 304 233 C 307 230 307 224 304 218 Z" style="fill:#FFF3C4" />
  <rect x="303" y="234" width="2" height="8" style="fill:#4E3E33" />`;

// ——— Spiralna jelka sa zvezdom (velika i mala) ———
function jelkaG(cx: number, vrh: number, dno: number, pola: number, id: string) {
  const kupa = `M${cx} ${vrh} L ${cx + pola} ${dno - 6} Q ${cx} ${dno + 10} ${cx - pola} ${dno - 6} Z`;
  const n = 12, h = (dno - vrh) / n;
  let trake = `<g clip-path="url(#${id})">`;
  for (let i = 0; i < n + 3; i++) {
    const yl = vrh + i * h + h * 0.9, yr = yl - h * 1.4;
    const y2l = yl + h, y2r = yr + h;
    trake += `<path d="M${cx - pola - 4} ${f(yl)} Q ${cx} ${f((yl + yr) / 2 + h * 0.45)} ${cx + pola + 4} ${f(yr)} L ${cx + pola + 4} ${f(y2r)} Q ${cx} ${f((y2l + y2r) / 2 + h * 0.45)} ${cx - pola - 4} ${f(y2l)} Z" style="${SENKA};opacity:${i % 2 ? '.06' : '.17'}" />`;
    trake += `<path d="M${cx - pola - 4} ${f(yl)} Q ${cx} ${f((yl + yr) / 2 + h * 0.45)} ${cx + pola + 4} ${f(yr)}" style="fill:none;stroke:#fff;stroke-opacity:.28;stroke-width:1.6" />`;
  }
  trake += '</g>';
  const zr = pola * 0.27, zy = vrh - zr * 0.55;
  let z = '';
  for (let k = 0; k < 10; k++) { const a = -Math.PI / 2 + (k * Math.PI) / 5, r = k % 2 ? zr * 0.45 : zr; z += `${k ? 'L' : 'M'}${f(cx + r * Math.cos(a))} ${f(zy + r * Math.sin(a))} `; }
  return `<clipPath id="${id}"><path d="${kupa}" /></clipPath>
    <ellipse cx="${cx}" cy="${dno + 6}" rx="${f(pola * 1.05)}" ry="${f(pola * 0.12)}" style="${SENKA};opacity:.16" />
    <path d="${kupa}" style="fill:var(--c1)" />${trake}<path d="${kupa}" style="fill:url(#cr-j-g)" />
    <rect x="${f(cx - pola * 0.05)}" y="${f(zy + zr * 0.3)}" width="${f(pola * 0.1)}" height="${f(zr * 0.9)}" style="fill:var(--c2)" />
    <path d="${z}Z" style="fill:var(--c2);stroke:var(--c2);stroke-width:3;stroke-linejoin:round" />
    <path d="${z}Z" style="fill:url(#cr-j-z);stroke:#1d130c;stroke-opacity:.22;stroke-width:1.2;stroke-linejoin:round" />`;
}
const jelka = `<defs>${valjak('cr-j-g')}<linearGradient id="cr-j-z" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".45" /><stop offset=".55" stop-color="#fff" stop-opacity="0" /><stop offset="1" stop-color="#000" stop-opacity=".3" /></linearGradient></defs>
  ${jelkaG(158, 82, 356, 104, 'cr-j-1')}${jelkaG(304, 196, 356, 62, 'cr-j-2')}`;

// ——— Monstera: listovi-podmetači u saksiji ———
// List u sopstvenim koordinatama: peteljka u (0,0), vrh na (0,-100); prorezi su rupe (evenodd)
const listPut = 'M0 0 C -42 -4 -58 -40 -47 -70 C -39 -93 -15 -105 0 -100 C 15 -105 39 -93 47 -70 C 58 -40 42 -4 0 0 Z '
  + 'M-44 -58 L -14 -52 L -14 -49 L -46 -50 Z M-46 -34 L -12 -34 L -12 -31 L -44 -26 Z M-36 -14 L -9 -20 L -9 -17 L -30 -8 Z '
  + 'M44 -62 L 14 -55 L 14 -52 L 46 -54 Z M47 -38 L 12 -37 L 12 -34 L 45 -30 Z M38 -17 L 9 -22 L 9 -19 L 32 -10 Z '
  + 'M-20 -78 a4 7 -20 1 0 0.1 0 Z M22 -80 a4 7 20 1 0 0.1 0 Z';
const list = (x: number, y: number, ugao: number, s: number, sy = 1) => `
  <g transform="translate(${x} ${y}) scale(1 ${sy}) rotate(${ugao}) scale(${s})">
    <path d="${listPut}" fill-rule="evenodd" style="fill:var(--c1)" />
    <path d="${listPut}" fill-rule="evenodd" style="fill:url(#cr-m-l)" />
    <path d="M0 -3 L 0 -96" style="fill:none;stroke:#1d130c;stroke-opacity:.22;stroke-width:2.2;stroke-linecap:round" />
    <path d="M0 -60 L -30 -78 M0 -45 L -38 -45 M0 -28 L -30 -22 M0 -62 L 30 -80 M0 -46 L 38 -47 M0 -29 L 30 -24" style="fill:none;stroke:#fff;stroke-opacity:.14;stroke-width:1.2;stroke-linecap:round" />
  </g>`;
const peteljka = (x1: number, y1: number, x2: number, y2: number) => `<path d="M${x1} ${y1} Q ${f((x1 + x2) / 2 + (x2 - x1) * 0.1)} ${f((y1 + y2) / 2 + 18)} ${x2} ${y2}" style="fill:none;stroke:var(--c1);stroke-width:4.5;stroke-linecap:round" /><path d="M${x1} ${y1} Q ${f((x1 + x2) / 2 + (x2 - x1) * 0.1)} ${f((y1 + y2) / 2 + 18)} ${x2} ${y2}" style="fill:none;stroke:#1d130c;stroke-opacity:.25;stroke-width:4.5;stroke-linecap:round" />`;
const saksija = 'M128 258 L 272 258 C 276 300 268 340 236 356 L 164 356 C 132 340 124 300 128 258 Z';
let spirala = '<g clip-path="url(#cr-m-s)">';
for (let i = -8; i < 14; i++) spirala += `<path d="M${120 + i * 14} 360 C ${150 + i * 14} 320 ${150 + i * 14} 300 ${200 + i * 14} 250" style="fill:none;stroke:#1d130c;stroke-opacity:.13;stroke-width:4" /><path d="M${124 + i * 14} 360 C ${154 + i * 14} 320 ${154 + i * 14} 300 ${204 + i * 14} 250" style="fill:none;stroke:#fff;stroke-opacity:.18;stroke-width:1.4" />`;
spirala += '</g>';
const listovi: [number, number, number, number][] = [[96, 236, -78, 0.66], [304, 232, 78, 0.66], [116, 196, -52, 0.78], [286, 192, 52, 0.78], [150, 158, -24, 0.84], [252, 156, 24, 0.84], [200, 142, 0, 0.88]];
const monstera = `<defs>${valjak('cr-m-g')}<clipPath id="cr-m-s"><path d="${saksija}" /></clipPath>
  <linearGradient id="cr-m-l" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".25" /><stop offset=".6" stop-color="#fff" stop-opacity="0" /><stop offset="1" stop-color="#000" stop-opacity=".18" /></linearGradient></defs>
  <ellipse cx="200" cy="360" rx="160" ry="12" style="${SENKA};opacity:.14" />
  ${listovi.map(([x, y, u]) => peteljka(200, 262, f(x + Math.sin((u * Math.PI) / 180) * 2), y)).join('')}
  ${listovi.map(([x, y, u, s]) => list(x, y, u, s)).join('')}
  <path d="${saksija}" style="fill:var(--c2)" />${spirala}<path d="${saksija}" style="fill:url(#cr-m-g)" />
  <rect x="122" y="250" width="156" height="16" rx="8" style="fill:var(--c2)" /><rect x="122" y="250" width="156" height="16" rx="8" style="fill:url(#cr-m-g)" />
  <ellipse cx="200" cy="254" rx="70" ry="4" style="${SENKA};opacity:.35" />
  ${list(118, 366, -100, 0.6, 0.42)}${list(282, 366, 100, 0.6, 0.42)}`;

export const CRTEZI: Crtez[] = [
  { id: 'vaza', label: 'Vaza sa tacnom i svećnjakom', delovi: ['vaza, tacna i svećnjak'], viewBox: '0 0 400 400', svg: vaza },
  { id: 'jelka', label: 'Spiralna jelka sa zvezdom', delovi: ['jelka', 'zvezda'], viewBox: '0 0 400 400', svg: jelka },
  { id: 'monstera', label: 'Monstera u saksiji (podmetači)', delovi: ['listovi', 'saksija'], viewBox: '0 0 400 400', svg: monstera },
];
export const crtez = (id?: string) => CRTEZI.find((c) => c.id === id);
