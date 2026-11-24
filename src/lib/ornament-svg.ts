import { fitName, boja, motiv } from './ornament';

export interface OrnOpts {
  motif?: string;
  color?: string; // id boje ukrasa
  nameColor?: string; // 'ista' ili id boje
  name?: string;
  fallback?: string;
  key?: string; // oznaka za pregled uživo (data-orn="…")
  max?: number; // najviše slova imena
}

// Jedan ukras u lokalnim koordinatama (kutija 240 × 290, kukica na y = motiv.hy).
// Slojevi: senka ukrasa, podloga imena (senka i lice, u boji ukrasa), ukras, pa ime (senka i lice, u boji imena).
export function ornamentG(o: OrnOpts) {
  const M = motiv(o.motif || 'zvezdice-i-jelke');
  const B = boja(o.color || 'crvena');
  const N = !o.nameColor || o.nameColor === 'ista' ? B : boja(o.nameColor);
  const f = fitName(o.name || '', o.fallback || 'Mila', M, o.max);
  const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
  const txt = (cls: string, sw: number) => `<text class="${cls}" x="120" y="${f.y}" transform="${f.tf}" text-anchor="middle" font-family="Pacifico, cursive" font-size="${f.fs}" stroke-width="${sw}" stroke-linejoin="round">${esc(f.name)}</text>`;
  const nameLayer = (cls: string, col: string, tf = '') =>
    `<g class="${cls}"${tf} clip-path="url(#orn-cn)" fill="${col}" stroke="${col}"><path class="orn-rail" d="${f.rails}" fill="none" stroke-width="${f.railW}" stroke-linecap="round"></path>${txt('orn-name', 1.2)}</g>`;
  const podloga = (cls: string, col: string, tf = '') =>
    `<g class="${cls}"${tf} clip-path="url(#orn-cn)" fill="${col}" stroke="${col}"${f.bk > 0 ? '' : ' style="display:none"'}><path class="orn-bkr" d="${f.rails}" fill="none" stroke-width="${parseFloat(f.railW) + 2 * f.bk}" stroke-linecap="round"></path>${txt('orn-bkt', 2 * f.bk + 1.2)}</g>`;
  return `<g class="orn"${o.key ? ` data-orn="${o.key}"` : ''} data-hy="${M.hy}" data-motif="${M.id}">` +
    `<use class="orn-edge" href="#orn-${M.id}" transform="translate(1.4 3.2)" fill="${B.edge}" stroke="${B.edge}"></use>` +
    podloga('orn-be', B.edge, ' transform="translate(1.4 3.2)"') +
    `<use class="orn-face" href="#orn-${M.id}" fill="${B.face}" stroke="${B.face}"></use>` +
    podloga('orn-bf', B.face) +
    nameLayer('orn-ne', N.edge, ' transform="translate(1.1 2.6)"') +
    nameLayer('orn-nf', N.face) +
    `</g>`;
}
