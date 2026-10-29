// Privesci sa imenom: stilovi slova i crtanje pregleda uživo (SVG).
// Privezak se crta u tri sloja, kao pravi 3D otisak: osnova (široka kontura), okvir i slova.

export type Sloj = 'slova' | 'okvir' | 'osnova';
export interface KeyStyle {
  id: string;
  label: string; // naziv stila u poruci i na pločici
  font: string; // CSS font-family
  weight: number;
  italic?: boolean;
  caps?: boolean;
  slojevi: Sloj[]; // koje boje kupac bira
  def: Record<Sloj, string>; // podrazumevane boje (id iz liste boja)
  okvirW: number; // debljina okvira (deo veličine slova)
  osnovaW: number; // debljina osnove
  join: 'round' | 'miter';
  tankaIvica?: string; // tanka tamna linija oko slova (stil „Kockice”)
  primer: string;
}

export const STILOVI: KeyStyle[] = [
  { id: 'pisana', label: 'Pisana slova', font: "'Lobster', 'Pacifico', cursive", weight: 400, slojevi: ['slova', 'okvir', 'osnova'], def: { slova: 'roze', okvir: 'bela', osnova: 'crna' }, okvirW: 0.15, osnovaW: 0.36, join: 'round', primer: 'Milica' },
  { id: 'stampana', label: 'Štampana slova', font: "'Geologica', system-ui, sans-serif", weight: 800, slojevi: ['slova', 'osnova'], def: { slova: 'crna', okvir: 'bela', osnova: 'bela' }, okvirW: 0, osnovaW: 0.34, join: 'round', primer: 'Milica' },
  { id: 'bajka', label: 'Bajkovita slova', font: "'Grandstander', 'Lobster', cursive", weight: 800, slojevi: ['slova', 'okvir', 'osnova'], def: { slova: 'plava', okvir: 'bela', osnova: 'crna' }, okvirW: 0.15, osnovaW: 0.36, join: 'round', primer: 'Dijana' },
  { id: 'kockice', label: 'Slova kao od kockica', font: "'Rubik', system-ui, sans-serif", weight: 900, italic: true, slojevi: ['slova', 'okvir', 'osnova'], def: { slova: 'bela', okvir: 'zuta', osnova: 'crvena' }, okvirW: 0.17, osnovaW: 0.36, join: 'round', tankaIvica: '#1d1a17', primer: 'Jovan' },
  { id: 'pikseli', label: 'Pikselasta slova', font: "'Pixelify Sans', monospace", weight: 700, caps: true, slojevi: ['slova', 'osnova'], def: { slova: 'svetlozelena', okvir: 'crna', osnova: 'crna' }, okvirW: 0, osnovaW: 0.26, join: 'miter', primer: 'Miloš' },
];
export const stil = (id: string) => STILOVI.find((s) => s.id === id) || STILOVI[0];
export const SLOJ_LABEL: Record<Sloj, string> = { slova: 'Boja slova', okvir: 'Boja okvira', osnova: 'Boja osnove' };
export const PRIMERI = ['Milica', 'Luka', 'Sara', 'Vuk', 'Ena', 'Mateo', 'Dunja', 'Pavle'];

export interface Crtez {
  imena: string[];
  stil: KeyStyle;
  boje: Record<Sloj, string>; // hex
  slovaPoImenu?: string[]; // hex boja slova za svako ime („Šareno”)
}

const NS = 'http://www.w3.org/2000/svg';
const el = (tag: string, a: Record<string, string | number>) => {
  const e = document.createElementNS(NS, tag);
  for (const k in a) e.setAttribute(k, String(a[k]));
  return e;
};

// Crta privezke jedan ispod drugog u <svg> i podešava viewBox.
export function nacrtaj(svg: SVGSVGElement, c: Crtez) {
  const fs = 100, gap = 70;
  const root = el('g', {});
  svg.replaceChildren(root);
  let y = 0;
  c.imena.forEach((ime, i) => {
    const s = c.stil;
    const tekst = s.caps ? ime.toLocaleUpperCase('sr') : ime;
    const slova = c.slovaPoImenu?.[i] || c.boje.slova;
    const okvir = s.okvirW ? c.boje.okvir : c.boje.osnova;
    const osnova = c.boje.osnova;
    const g = el('g', {});
    root.appendChild(g);
    const t = (stroke: string, w: number, fill: string) => {
      const n = el('text', { x: 0, y, 'font-size': fs, 'font-family': s.font, 'font-weight': s.weight, 'font-style': s.italic ? 'italic' : 'normal', fill, stroke, 'stroke-width': w, 'stroke-linejoin': s.join, 'stroke-linecap': s.join === 'round' ? 'round' : 'square', 'paint-order': 'stroke' });
      n.textContent = tekst;
      return n;
    };
    const baza = t(osnova, s.osnovaW * fs, osnova);
    g.appendChild(baza);
    const bb = baza.getBBox();
    // ušica za kariku, spojena sa osnovom
    const R = 0.3 * fs, tx = bb.x - R * 0.55, cy = bb.y + bb.height * 0.5;
    g.insertBefore(el('circle', { cx: tx, cy, r: R, fill: osnova }), baza);
    if (s.okvirW) g.appendChild(t(okvir, s.okvirW * fs, okvir));
    g.appendChild(t(s.tankaIvica || 'none', s.tankaIvica ? 0.045 * fs : 0, slova));
    g.appendChild(el('circle', { cx: tx, cy, r: R * 0.38, fill: '#E9E2D7' }));
    // metalna karika
    g.appendChild(el('circle', { cx: tx - R * 0.95, cy, r: R * 0.95, fill: 'none', stroke: '#8E949B', 'stroke-width': 0.075 * fs }));
    g.appendChild(el('circle', { cx: tx - R * 0.95, cy: cy - 1.5, r: R * 0.95, fill: 'none', stroke: '#D5D9DE', 'stroke-width': 0.025 * fs }));
    y += bb.height + gap;
  });
  const box = root.getBBox();
  const pad = 30;
  svg.setAttribute('viewBox', `${box.x - pad} ${box.y - pad} ${box.width + pad * 2} ${box.height + pad * 2}`);
}

// Fontovi moraju da budu učitani pre merenja teksta
export async function ucitajFont(s: KeyStyle, tekst: string) {
  if (!document.fonts) return;
  const fam = s.font.split(',')[0].trim();
  try { await document.fonts.load(`${s.italic ? 'italic ' : ''}${s.weight} 100px ${fam}`, tekst || s.primer); } catch (e) { /* nastavi sa rezervnim fontom */ }
}
