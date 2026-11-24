// Pregled ukrasa sa imenom: boje, motivi i raspored imena preko obruča.
// Isti kod se koristi pri izradi strane (ornament-svg.ts) i u pregledu uživo u pregledaču (paint).
// Uklapanje imena je isto kao u WordPress pluginu (assets/ornament.js): ime ide od jedne do druge strane obruča,
// a na pravom ukrasu je izdignuto preko podloge u boji ukrasa koja prati oblik slova.

import { SVE_BOJE, darken } from './colors';
import SIR from '../data/pacifico-sirine.json';

// Boje ukrasa dolaze iz liste boja filamenta (admin panel); ivica je tamnija nijansa.
const IVICE: Record<string, string> = { bela: '#B9AE9C', crvena: '#7E1F1A', zelena: '#1C3A25', zlatna: '#A0741F' };
export const BOJE = SVE_BOJE.map((b) => ({ id: b.id, label: b.naziv, face: b.hex, edge: IVICE[b.id] || darken(b.hex, b.id === 'crna' ? -0.35 : 0.32) }));

// ime: ri = unutrašnji poluprečnik obruča gde ime ulazi u njega; traka = ime leži na traci (širina w, visina y, najveći font fs)
// podloga: false = bez podloge ispod imena (ime je na traci koja je deo ukrasa)
export interface Motiv { id: string; label: string; hy: number; ime?: { ri?: number; traka?: boolean; w?: number; y?: number; fs?: number }; podloga?: boolean }
// Motivi koje kupac bira (ukras sa imenom i porodični set), redom kao na sajtu
export const MOTIVI: Motiv[] = [
  { id: 'zvezdice-i-jelke', label: 'Zvezdice i jelke', hy: 64 },
  { id: 'masnica', label: 'Mašnica', hy: 71 },
  { id: 'kapica-deda-mraza', label: 'Kapica Deda Mraza', hy: 55, ime: { ri: 92.5 } },
  { id: 'velika-pahulja', label: 'Velika pahulja', hy: 72, podloga: false, ime: { traka: true, w: 80, y: 189, fs: 46 } },
];
// Rotirajući ukras: pahulja sa imenom u sredini i dva prstena oko nje (ime manje, na traci)
export const OKRETNI: Motiv = { id: 'okretni', label: 'Rotirajući', hy: 69, podloga: false, ime: { traka: true, w: 56, y: 189, fs: 34 } };
const SVI = [...MOTIVI, OKRETNI];

// Širine slova fonta Pacifico (1/1000 em) i gde crtež slova počinje i završava (repovi slova).
const ADV: Record<string, number> = {}, INK: Record<string, [number, number]> = {};
Array.from(SIR.chars).forEach((ch, i) => { ADV[ch] = SIR.w[i]; INK[ch] = [SIR.l[i], SIR.r[i]]; });
const REACH = 97, FS_MAX = 70, SX_MAX = 1.6, SW = 1.2;
const PODLOGA = 5.5; // podloga ispod imena: koliko je deblja od slova (jedinice crteža; 80 mm ukras ≈ 2 mm)
const r1 = (v: number) => v.toFixed(1);
const r3 = (v: number) => String(Math.round(v * 1000) / 1000);

export function fitName(raw: string, fallback = 'Mila', M: Motiv = MOTIVI[0], max = 12) {
  const cfg = M.ime || {};
  const name = Array.from(String(raw || '').trim()).slice(0, max).join('') || fallback;
  const chars = Array.from(name);
  let sum = 0;
  for (const ch of chars) sum += ADV[ch] !== undefined ? ADV[ch] : 520;
  const first = INK[chars[0]] || [0, 0], last = INK[chars[chars.length - 1]] || [0, 0];
  const inkEm = Math.max(0.2, (sum - first[0] - last[1]) / 1000), c0 = (first[0] - last[1]) / 2000;
  const traka = !!cfg.traka;
  const reach = traka ? cfg.w! : REACH;
  const W = 2 * reach - SW;
  const CY = 182, RI = cfg.ri || 94, yc = cfg.y || CY + 6;
  const fs = traka ? Math.max(16, Math.min(cfg.fs!, W / inkEm)) : Math.max(22, Math.min(FS_MAX, W / inkEm));
  const sx = traka ? 1 : Math.max(1, Math.min(SX_MAX, W / (inkEm * fs)));
  const y = yc + SIR.yk * fs;
  const w = inkEm * fs * sx + SW;
  const dx = sx * c0 * fs;
  let rails = '';
  if (!traka && 2 * reach - w > 6) {
    const yr = y - 0.05 * fs;
    const hr = Math.sqrt(RI * RI - (yr - CY) * (yr - CY));
    rails = 'M' + r1(120 - hr - 4) + ' ' + r1(yr) + 'L' + r1(120 - w / 2 + 0.18 * fs) + ' ' + r1(yr) +
      'M' + r1(120 + w / 2 - 0.22 * fs) + ' ' + r1(yr) + 'L' + r1(120 + hr + 4) + ' ' + r1(yr);
  }
  return {
    name, fs: r1(fs), y: r1(y), rails, railW: r1(Math.max(4.2, 0.085 * fs)),
    tf: `matrix(${r3(sx)} 0 0 1 ${r3(120 - 120 * sx - dx)} 0)`,
    bk: traka || M.podloga === false ? 0 : PODLOGA,
  };
}

export const boja = (id: string) => BOJE.find((b) => b.id === id) || BOJE[0];
export const motiv = (id: string) => SVI.find((m) => m.id === id) || MOTIVI[0];

// Pregled uživo: oboji jedan ukras (<g class="orn">) i upiše ime. Vraća motiv (zbog visine kačenja hy).
export function paint(g: Element, o: { motif: string; color: string; nameColor: string; name: string; fallback: string; max?: number }) {
  const M = motiv(o.motif), B = boja(o.color), N = o.nameColor === 'ista' ? B : boja(o.nameColor);
  const f = fitName(o.name, o.fallback, M, o.max);
  const boji = (el: Element | null, c: string) => { if (el) { el.setAttribute('fill', c); el.setAttribute('stroke', c); } };
  g.querySelectorAll('.orn-edge, .orn-face').forEach((u) => u.setAttribute('href', '#orn-' + M.id));
  boji(g.querySelector('.orn-edge'), B.edge); boji(g.querySelector('.orn-face'), B.face);
  boji(g.querySelector('.orn-be'), B.edge); boji(g.querySelector('.orn-bf'), B.face);
  boji(g.querySelector('.orn-ne'), N.edge); boji(g.querySelector('.orn-nf'), N.face);
  g.querySelectorAll('.orn-name, .orn-bkt').forEach((t) => {
    t.textContent = f.name; t.setAttribute('font-size', f.fs); t.setAttribute('y', f.y); t.setAttribute('transform', f.tf);
    if (t.classList.contains('orn-bkt')) t.setAttribute('stroke-width', String(2 * f.bk + SW));
  });
  g.querySelectorAll('.orn-rail').forEach((p) => { p.setAttribute('d', f.rails); p.setAttribute('stroke-width', f.railW); });
  g.querySelectorAll('.orn-bkr').forEach((p) => { p.setAttribute('d', f.rails); p.setAttribute('stroke-width', String(parseFloat(f.railW) + 2 * f.bk)); });
  g.querySelectorAll<SVGElement>('.orn-be, .orn-bf').forEach((b) => { b.style.display = f.bk > 0 ? '' : 'none'; });
  g.setAttribute('data-motif', M.id); g.setAttribute('data-hy', String(M.hy));
  return M;
}
