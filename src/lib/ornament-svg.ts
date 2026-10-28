import { fitName, boja, motiv } from './ornament';

export interface OrnOpts {
  motif?: string;
  color?: string; // id boje ukrasa
  nameColor?: string; // 'ista' ili id boje
  name?: string;
  fallback?: string;
  key?: string; // oznaka za pregled uživo (data-orn="…")
}

// Jedan ukras u lokalnim koordinatama (kutija 240 × 290, kukica na y = motiv.hy).
export function ornamentG(o: OrnOpts) {
  const M = motiv(o.motif || 'zvezde');
  const B = boja(o.color || 'bela');
  const N = !o.nameColor || o.nameColor === 'ista' ? B : boja(o.nameColor);
  const f = fitName(o.name || '', o.fallback || 'Mila');
  const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
  const nameLayer = (cls: string, col: string, tf = '') =>
    `<g class="${cls}"${tf} fill="${col}" stroke="${col}"><path class="orn-rail" d="${f.rails}" fill="none" stroke-width="${f.railW}" stroke-linecap="round"></path><text class="orn-name" x="120" y="${f.y}" text-anchor="middle" font-family="Pacifico, cursive" font-size="${f.fs}" stroke-width="1.2" stroke-linejoin="round">${esc(f.name)}</text></g>`;
  return `<g class="orn"${o.key ? ` data-orn="${o.key}"` : ''} data-hy="${M.hy}">` +
    `<use class="orn-edge" href="#orn-${M.id}" transform="translate(1.4 3.2)" fill="${B.edge}" stroke="${B.edge}"></use>` +
    `<use class="orn-face" href="#orn-${M.id}" fill="${B.face}" stroke="${B.face}"></use>` +
    nameLayer('orn-ne', N.edge, ' transform="translate(1.1 2.6)"') +
    nameLayer('orn-nf', N.face) +
    `</g>`;
}
