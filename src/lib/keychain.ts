// Privesci sa imenom: stilovi slova i crtanje pregleda uživo (SVG).
// Privezak se crta kao pravi 3D otisak, sloj po sloj: osnova sa ušicom, pa okvir (ako ga stil ima), pa slova.
// Svaki sloj ima i „bočnu stranu” (tamniju kopiju malo niže), pa pregled izgleda kao na fotografijama.

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
  osnovaW: number; // koliko osnova viri oko slova (deo veličine slova, ukupna debljina linije)
  okvirW: number; // isto za okvir (0 = nema okvira)
  ivica?: { boja: string; w: number }; // tanka tamna linija odmah oko slova (stil „kockice”)
  debljina?: number; // podebljanje slova (za tanja slova)
  join: 'round' | 'miter';
  usica: 'krug' | 'pilula';
  usicaR?: number; // poluprečnik okrugle ušice (deo veličine slova)
  karika?: string; // boja metalne karike, ako se crta
  srca?: boolean; // srce umesto tačke na i i j
  pukotine?: boolean; // kamene pukotine na slovima (pikselasta slova)
  sareno?: Sloj; // koji sloj menja boju kod „Šareno” (podrazumevano slova)
  primer: string;
}

export const STILOVI: KeyStyle[] = [
  { id: 'pisana', label: 'Pisana slova', font: "'Playball', 'Lobster', cursive", weight: 400, slojevi: ['slova', 'osnova'], def: { slova: 'roze', okvir: 'bela', osnova: 'bela' }, osnovaW: 0.34, okvirW: 0, debljina: 0.035, join: 'round', usica: 'krug', primer: 'Milica' },
  { id: 'bajka', label: 'Bajkovita slova', font: "'Mogra', 'Lobster', cursive", weight: 400, slojevi: ['slova', 'okvir', 'osnova'], def: { slova: 'bela', okvir: 'plava', osnova: 'crna' }, osnovaW: 0.40, okvirW: 0.17, debljina: 0.02, join: 'round', usica: 'pilula', srca: true, sareno: 'okvir', primer: 'Dijana' },
  { id: 'kockice', label: 'Slova kao od kockica', font: "'Nunito', system-ui, sans-serif", weight: 900, italic: true, slojevi: ['slova', 'okvir', 'osnova'], def: { slova: 'bela', okvir: 'zuta', osnova: 'crvena' }, osnovaW: 0.46, okvirW: 0.23, ivica: { boja: '#1d1a17', w: 0.12 }, debljina: 0.02, join: 'round', usica: 'krug', usicaR: 0.3, primer: 'Jovan' },
  { id: 'pikseli', label: 'Pikselasta slova', font: "'Bumbaru Piksel', monospace", weight: 900, caps: true, slojevi: ['slova', 'osnova'], def: { slova: 'svetlozelena', okvir: 'crna', osnova: 'crna' }, osnovaW: 0.2, okvirW: 0, join: 'miter', usica: 'krug', karika: '#2a2a2a', pukotine: true, primer: 'Miloš' },
  { id: 'stampana', label: 'Štampana slova', font: "'Montserrat', 'Geologica', system-ui, sans-serif", weight: 800, slojevi: ['slova', 'osnova'], def: { slova: 'crna', okvir: 'bela', osnova: 'bela' }, osnovaW: 0.36, okvirW: 0, join: 'round', usica: 'krug', karika: '#B9BEC4', primer: 'Milica' },
];
export const stil = (id: string) => STILOVI.find((s) => s.id === id) || STILOVI[0];
export const SLOJ_LABEL: Record<Sloj, string> = { slova: 'Boja slova', okvir: 'Boja okvira', osnova: 'Boja osnove' };
export const PRIMERI = ['Milica', 'Luka', 'Sara', 'Vuk', 'Ena', 'Mateo', 'Dunja', 'Pavle'];

export interface Crtez {
  imena: string[];
  stil: KeyStyle;
  boje: Record<Sloj, string>; // hex
  poImenu?: string[]; // hex boja za svako ime („Šareno”), za sloj stil.sareno
}

const NS = 'http://www.w3.org/2000/svg';
const FS = 100; // veličina slova u jedinicama crteža
const el = (tag: string, a: Record<string, string | number> = {}) => {
  const e = document.createElementNS(NS, tag);
  for (const k in a) e.setAttribute(k, String(a[k]));
  return e;
};
// tamnija (amt > 0) ili svetlija (amt < 0) nijansa
export function senka(hex: string, amt: number) {
  const n = parseInt(hex.slice(1), 16);
  const f = (v: number) => Math.round(Math.max(0, Math.min(255, amt >= 0 ? v * (1 - amt) : v + (255 - v) * -amt)));
  return '#' + [(n >> 16) & 255, (n >> 8) & 255, n & 255].map((v) => f(v).toString(16).padStart(2, '0')).join('');
}
const svetla = (hex: string) => {
  const n = parseInt(hex.slice(1), 16);
  return 0.299 * ((n >> 16) & 255) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255) > 200;
};
const zid = (hex: string) => senka(hex, svetla(hex) ? 0.17 : 0.32);

// ——— merenje na platnu (canvas): tačne ivice slova, mesto za ušicu i tačke na i/j ———
let platno: HTMLCanvasElement | null = null;
const ctx2d = () => {
  platno ||= document.createElement('canvas');
  return platno.getContext('2d', { willReadFrequently: true })!;
};
const fontCss = (s: KeyStyle) => `${s.italic ? 'italic ' : ''}${s.weight} ${FS}px ${s.font}`;

interface Mera { left: number; right: number; top: number; bottom: number; redovi: (number | null)[]; y0: number }
// crta tekst na platnu i za svaki red piksela pamti gde počinje mastilo (za spajanje ušice)
function izmeri(s: KeyStyle, tekst: string): Mera {
  const c = ctx2d();
  c.font = fontCss(s);
  const m = c.measureText(tekst);
  const pad = 20;
  const w = Math.ceil(m.actualBoundingBoxLeft + m.actualBoundingBoxRight) + pad * 2;
  const h = Math.ceil(m.actualBoundingBoxAscent + m.actualBoundingBoxDescent) + pad * 2;
  const cv = c.canvas;
  cv.width = Math.max(1, w); cv.height = Math.max(1, h);
  c.font = fontCss(s);
  c.fillStyle = '#000';
  const ox = pad + m.actualBoundingBoxLeft, oy = pad + m.actualBoundingBoxAscent;
  c.fillText(tekst, ox, oy);
  const d = c.getImageData(0, 0, cv.width, cv.height).data;
  const redovi: (number | null)[] = [];
  for (let y = 0; y < cv.height; y++) {
    let x0: number | null = null;
    for (let x = 0; x < cv.width; x++) if (d[(y * cv.width + x) * 4 + 3] > 100) { x0 = x - ox; break; }
    redovi.push(x0);
  }
  return { left: -m.actualBoundingBoxLeft, right: m.actualBoundingBoxRight, top: -m.actualBoundingBoxAscent, bottom: m.actualBoundingBoxDescent, redovi, y0: -oy };
}

// gde je tačka na „i” / „j” (u odnosu na početak slova i osnovnu liniju)
const tackeKes = new Map<string, { cx: number; cy: number; w: number; h: number } | null>();
function tacka(s: KeyStyle, slovo: string) {
  const key = s.font + s.weight + slovo;
  if (tackeKes.has(key)) return tackeKes.get(key)!;
  const c = ctx2d();
  const cv = c.canvas; cv.width = 200; cv.height = 220;
  c.font = fontCss(s); c.fillStyle = '#000';
  const ox = 60, oy = 160;
  c.fillText(slovo, ox, oy);
  const d = c.getImageData(0, 0, cv.width, cv.height).data;
  const ink = (y: number) => { let a = Infinity, b = -Infinity; for (let x = 0; x < cv.width; x++) if (d[(y * cv.width + x) * 4 + 3] > 100) { a = Math.min(a, x); b = Math.max(b, x); } return [a, b]; };
  let y = 0;
  while (y < cv.height && ink(y)[0] === Infinity) y++;
  const top = y; let l = Infinity, r = -Infinity;
  while (y < cv.height) { const [a, b] = ink(y); if (a === Infinity) break; l = Math.min(l, a); r = Math.max(r, b); y++; }
  const bot = y;
  let gap = 0; while (y < cv.height && ink(y)[0] === Infinity) { y++; gap++; }
  // tačka mora biti odvojena od ostatka slova
  const res = gap >= 2 && y < cv.height && bot - top < 40 ? { cx: (l + r + 1) / 2 - ox, cy: (top + bot) / 2 - oy, w: r - l + 1, h: bot - top } : null;
  tackeKes.set(key, res);
  return res;
}
const SRCE = 'M0 .92C-.22 .72-1 .28-1-.22-1-.68-.62-.96-.3-.96-.14-.96 0-.86 0-.68 0-.86.14-.96.3-.96.62-.96 1-.68 1-.22 1 .28.22 .72 0 .92Z';

// pseudo-slučajni brojevi (iste pukotine za isto ime)
const rnd = (seed: number) => () => ((seed = (seed * 16807) % 2147483647) - 1) / 2147483646;

let brojac = 0;
// Crta privezke jedan ispod drugog u <svg> i podešava viewBox.
export function nacrtaj(svg: SVGSVGElement, c: Crtez) {
  const s = c.stil;
  const root = el('g');
  const defs = el('defs');
  svg.replaceChildren(defs, root);
  const id = 'kc' + ++brojac;
  const rO = (s.osnovaW * FS) / 2;
  const gap = 0.42 * FS;
  let y = 0;
  let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;

  c.imena.forEach((ime, n) => {
    const tekst = s.caps ? ime.toLocaleUpperCase('sr') : ime;
    const sar = s.sareno || 'slova';
    const slova = (sar === 'slova' && c.poImenu?.[n]) || c.boje.slova;
    const okvir = (sar === 'okvir' && c.poImenu?.[n]) || c.boje.okvir;
    const osnova = c.boje.osnova;
    const M = izmeri(s, tekst);
    const visina = M.bottom - M.top;
    const oy = y - M.top; // osnovna linija ovog priveska
    const g = el('g', { transform: `translate(0 ${oy})` });
    root.appendChild(g);

    // Ušica: tamo gde slova levo najdalje sežu, u donjoj polovini
    let red = M.top + visina * 0.6, xNaRedu = M.left;
    {
      let best = Infinity;
      for (let i = 0; i < M.redovi.length; i++) {
        const yy = i + M.y0;
        if (yy < M.top + visina * 0.38 || yy > M.top + visina * 0.82) continue;
        const x = M.redovi[i];
        if (x !== null && x < best - 0.5) { best = x; red = yy; }
      }
      if (best < Infinity) xNaRedu = best;
    }
    const tab: SVGElement[] = [];
    let rupa = { x: 0, y: red, r: 0 };
    if (s.usica === 'pilula') {
      const h = 0.5 * FS, L = 0.62 * FS;
      const x1 = xNaRedu - rO - L, x2 = xNaRedu + 4;
      tab.push(el('rect', { x: x1, y: red - h / 2, width: x2 - x1, height: h, rx: h / 2 }));
      rupa = { x: x1 + h / 2, y: red, r: 0.14 * FS };
    } else {
      const R = (s.usicaR || 0.25) * FS;
      const cx = xNaRedu - rO - R * 0.55;
      tab.push(el('circle', { cx, cy: red, r: R }));
      tab.push(el('rect', { x: cx, y: red - R * 0.8, width: xNaRedu + 4 - cx, height: R * 1.6 }));
      rupa = { x: cx - R * 0.08, y: red, r: R * 0.42 };
    }
    const mask = el('mask', { id: `${id}-m${n}`, maskUnits: 'userSpaceOnUse', x: -2000, y: -2000, width: 6000, height: 4000 });
    mask.append(el('rect', { x: -2000, y: -2000, width: 6000, height: 4000, fill: '#fff' }), el('circle', { cx: rupa.x, cy: rupa.y, r: rupa.r, fill: '#000' }));
    defs.appendChild(mask);

    // Tekst kao sloj: obris debljine w u boji col
    const txt = (col: string, w: number, fill = col) => {
      const t = el('text', { x: 0, y: 0, 'font-size': FS, 'font-family': s.font, 'font-weight': s.weight, 'font-style': s.italic ? 'italic' : 'normal', fill, stroke: w ? col : 'none', 'stroke-width': w, 'stroke-linejoin': s.join, 'stroke-linecap': s.join === 'round' ? 'round' : 'square', 'stroke-miterlimit': 2, 'paint-order': 'stroke' });
      t.textContent = tekst;
      return t;
    };

    // Srca umesto tačaka na i/j
    const srca: { x: number; y: number; r: number; tw: number; th: number; tx: number; ty: number }[] = [];
    const pripremiSrca = (t: SVGTextElement) => {
      if (!s.srca) return;
      Array.from(tekst).forEach((ch, i) => {
        if (ch !== 'i' && ch !== 'j') return;
        const d = tacka(s, ch);
        if (!d) return;
        const p = t.getStartPositionOfChar(i);
        const r = Math.max(d.w, d.h) * 0.62;
        srca.push({ x: p.x + d.cx, y: d.cy - r * 0.05, r, tw: d.w, th: d.h, tx: p.x + d.cx, ty: d.cy });
      });
    };
    const srce = (col: string, w: number) => {
      const gg = el('g');
      srca.forEach((h) => gg.appendChild(el('path', { d: SRCE, transform: `translate(${h.x} ${h.y}) scale(${h.r})`, fill: col, stroke: w ? col : 'none', 'stroke-width': w / h.r, 'stroke-linejoin': 'round' })));
      return gg;
    };

    // slojevi odozdo nagore: [boja, debljina obrisa, visina sloja]
    const slojevi: { col: string; w: number; dz: number; vrsta: 'osnova' | 'sloj' | 'slova' }[] = [];
    slojevi.push({ col: osnova, w: s.osnovaW * FS, dz: 0.075 * FS, vrsta: 'osnova' });
    if (s.okvirW) slojevi.push({ col: okvir, w: s.okvirW * FS, dz: 0.035 * FS, vrsta: 'sloj' });
    if (s.ivica) slojevi.push({ col: s.ivica.boja, w: s.ivica.w * FS, dz: 0.02 * FS, vrsta: 'sloj' });
    slojevi.push({ col: slova, w: (s.debljina || 0) * FS, dz: 0.04 * FS, vrsta: 'slova' });

    let vrh = 0; // gornja površina prethodnog sloja (y pomeraj)
    let tekstSlova: SVGTextElement | null = null;
    slojevi.forEach((L, i) => {
      const crtaj = (col: string, dy: number) => {
        const gg = el('g', { transform: `translate(0 ${dy})` });
        if (L.vrsta === 'osnova') {
          gg.setAttribute('mask', `url(#${id}-m${n})`);
          tab.forEach((t) => { const k = t.cloneNode() as SVGElement; k.setAttribute('fill', col); gg.appendChild(k); });
        }
        const t = txt(col, L.w);
        gg.appendChild(t);
        return { gg, t: t as SVGTextElement };
      };
      const zidY = L.vrsta === 'osnova' ? L.dz : vrh;
      const gore = L.vrsta === 'osnova' ? 0 : vrh - L.dz;
      const a = crtaj(zid(L.col), zidY);
      g.appendChild(a.gg);
      const b = crtaj(L.col, gore);
      g.appendChild(b.gg);
      if (i === 0) pripremiSrca(b.t);
      if (s.srca && srca.length) {
        if (L.vrsta === 'slova') {
          // sakrij tačke i nacrtaj srca
          const mk = el('mask', { id: `${id}-t${n}`, maskUnits: 'userSpaceOnUse', x: -2000, y: -2000, width: 6000, height: 4000 });
          mk.appendChild(el('rect', { x: -2000, y: -2000, width: 6000, height: 4000, fill: '#fff' }));
          srca.forEach((h) => mk.appendChild(el('rect', { x: h.tx - h.tw * 0.75, y: h.ty - h.th * 0.75, width: h.tw * 1.5, height: h.th * 1.5, fill: '#000' })));
          defs.appendChild(mk);
          [a, b].forEach((o) => o.t.setAttribute('mask', `url(#${id}-t${n})`));
        }
        a.gg.appendChild(srce(zid(L.col), L.w));
        b.gg.appendChild(srce(L.col, L.w));
      }
      if (L.vrsta === 'slova') tekstSlova = b.t;
      vrh = gore;
    });

    // Kamene pukotine na pikselastim slovima
    if (s.pukotine && tekstSlova) {
      const t = tekstSlova as SVGTextElement;
      const clip = el('clipPath', { id: `${id}-c${n}` });
      const kt = t.cloneNode(true) as SVGTextElement;
      kt.setAttribute('transform', `translate(0 ${vrh})`);
      kt.removeAttribute('stroke');
      clip.appendChild(kt);
      defs.appendChild(clip);
      const pg = el('g', { 'clip-path': `url(#${id}-c${n})`, stroke: senka(slova, 0.45), 'stroke-width': 0.026 * FS, fill: 'none', 'stroke-linecap': 'square', 'stroke-linejoin': 'miter', opacity: 0.9 });
      let seed = 7; for (const ch of tekst) seed = (seed * 31 + ch.charCodeAt(0)) % 100000;
      const r = rnd(seed + 1);
      const korak = 0.12 * FS; // jedan „piksel”
      const sirina = M.right - M.left;
      const broj = Math.max(3, Math.round(sirina / (0.4 * FS)));
      for (let k = 0; k < broj; k++) {
        let px = M.left + r() * sirina, py = M.top + r() * visina + vrh;
        let d = `M${px.toFixed(1)} ${py.toFixed(1)}`;
        const duz = 2 + Math.floor(r() * 3);
        for (let j = 0; j < duz; j++) {
          if (r() < 0.5) px += (r() < 0.5 ? -1 : 1) * korak * (0.5 + r()); else py += (r() < 0.5 ? -1 : 1) * korak * (0.5 + r());
          d += ` L${px.toFixed(1)} ${py.toFixed(1)}`;
        }
        pg.appendChild(el('path', { d }));
      }
      g.appendChild(pg);
    }

    // Metalna karika
    let kL = Infinity;
    if (s.karika) {
      const kr = 0.3 * FS, kx = rupa.x - kr * 0.92;
      // iza priveska, pa se kroz rupu vidi kako prolazi
      g.insertBefore(el('circle', { cx: kx, cy: rupa.y - 1.5, r: kr, fill: 'none', stroke: senka(s.karika, -0.45), 'stroke-width': 0.022 * FS }), g.firstChild);
      g.insertBefore(el('circle', { cx: kx, cy: rupa.y, r: kr, fill: 'none', stroke: s.karika, 'stroke-width': 0.075 * FS }), g.firstChild);
      kL = kx - kr - 0.04 * FS;
    }

    // granice ovog priveska
    const levo = Math.min(kL, s.usica === 'pilula' ? rupa.x - 0.25 * FS : rupa.x - 0.27 * FS, M.left - rO);
    const sumaVisina = slojevi.slice(1).reduce((a, L) => a + L.dz, 0);
    minX = Math.min(minX, levo);
    maxX = Math.max(maxX, M.right + rO);
    minY = Math.min(minY, oy + M.top - rO - sumaVisina - (srca.length ? 0.1 * FS : 0));
    maxY = Math.max(maxY, oy + M.bottom + rO + 0.075 * FS);
    y = oy + M.bottom + rO + 0.075 * FS + gap + rO + sumaVisina;
  });

  const pad = 24;
  svg.setAttribute('viewBox', `${(minX - pad).toFixed(1)} ${(minY - pad).toFixed(1)} ${(maxX - minX + pad * 2).toFixed(1)} ${(maxY - minY + pad * 2).toFixed(1)}`);
}

// Fontovi moraju da budu učitani pre merenja teksta
export async function ucitajFont(s: KeyStyle, tekst: string) {
  if (!document.fonts) return;
  const fam = s.font.split(',')[0].trim();
  try { await document.fonts.load(`${s.italic ? 'italic ' : ''}${s.weight} ${FS}px ${fam}`, tekst || s.primer); } catch (e) { /* nastavi sa rezervnim fontom */ }
}
