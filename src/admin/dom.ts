// Mali pomoćnici za pravljenje elemenata u admin panelu (bez biblioteka).
type Dete = Node | string | number | null | undefined | false | Dete[];
type Attrs = Record<string, unknown> & { class?: string; style?: string };

export function h<K extends keyof HTMLElementTagNameMap>(tag: K, attrs: Attrs | null = null, ...deca: Dete[]): HTMLElementTagNameMap[K] {
  const el = document.createElement(tag);
  if (attrs) {
    for (const [k, v] of Object.entries(attrs)) {
      if (v === undefined || v === null || v === false) continue;
      if (k.startsWith('on') && typeof v === 'function') el.addEventListener(k.slice(2), v as EventListener);
      else if (k === 'value' && 'value' in el) (el as HTMLInputElement).value = String(v);
      else if (k === 'checked' && 'checked' in el) (el as HTMLInputElement).checked = !!v;
      else if (k === 'selected' && 'selected' in el) (el as HTMLOptionElement).selected = !!v;
      else if (k === 'disabled' && 'disabled' in el) (el as HTMLButtonElement).disabled = !!v;
      else if (k === 'hidden') el.hidden = !!v;
      else if (k === 'dataset' && typeof v === 'object') Object.assign(el.dataset, v);
      else el.setAttribute(k, v === true ? '' : String(v));
    }
  }
  dodaj(el, deca);
  return el;
}

function dodaj(el: Node, deca: Dete[]) {
  for (const d of deca) {
    if (d === null || d === undefined || d === false) continue;
    if (Array.isArray(d)) dodaj(el, d);
    else el.appendChild(typeof d === 'string' || typeof d === 'number' ? document.createTextNode(String(d)) : d);
  }
}

// Linijske ikonice (iste kao na sajtu)
const IKONE: Record<string, string> = {
  plus: '<path d="M12 5v14M5 12h14"/>',
  close: '<path d="M6 6l12 12M18 6L6 18"/>',
  up: '<path d="M6 15l6-6 6 6"/>',
  down: '<path d="M6 9l6 6 6-6"/>',
  left: '<path d="M15 6l-6 6 6 6"/>',
  right: '<path d="M9 6l6 6-6 6"/>',
  check: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
  pen: '<path d="M4 20l4-1L19 8a2.1 2.1 0 0 0-3-3L5 16zM14 7l3 3"/>',
  copy: '<path d="M8 8h12v12H8z"/><path d="M16 8V4H4v12h4"/>',
  camera: '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
  search: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
  eye: '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
  eyeoff: '<path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.3 6.3C3.7 8 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.7-1.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
  ext: '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
  trash: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
  box: '<path d="M3 7.5L12 3l9 4.5v9L12 21l-9-4.5zM3 7.5l9 4.5 9-4.5M12 12v9"/>',
};
export function ikona(ime: keyof typeof IKONE | string, size = 18) {
  const s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  s.setAttribute('width', String(size)); s.setAttribute('height', String(size)); s.setAttribute('viewBox', '0 0 24 24');
  s.setAttribute('fill', 'none'); s.setAttribute('stroke', 'currentColor'); s.setAttribute('stroke-width', '2');
  s.setAttribute('stroke-linecap', 'round'); s.setAttribute('stroke-linejoin', 'round'); s.setAttribute('aria-hidden', 'true');
  s.innerHTML = IKONE[ime] || '';
  return s;
}

export const dugmeIkona = (ime: string, label: string, onclick: (e: MouseEvent) => void, extra: Attrs = {}) => {
  const { class: cls, ...ostalo } = extra;
  return h('button', { type: 'button', 'aria-label': label, title: label, ...ostalo, class: `icon-btn sm${cls ? ' ' + cls : ''}`, onclick }, ikona(ime));
};

export const din = (n: number) => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' din';
