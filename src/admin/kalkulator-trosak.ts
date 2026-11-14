// Kalkulator troška iz slicera (admin → Kalkulator, drugi deo): za bilo koji proizvod.
// Unese se Total filament (g) i vreme štampe iz slicera, broj komada na ploči, pakovanje i rad po komadu;
// računa trošak po komadu i cene ×2 / ×2,5 / ×3. Ulazne cene su iz claude/kalkulacija-troskova.md
// (filament 2.300 din/kg, struja ~7,2 din/h, habanje i rizik 4–8 din/h, rad 600 din/h, pakovanje po komadu)
// i mogu da se promene ovde; promene se pamte u ovom pregledaču.
import { h, din } from './dom';

interface Ulazne { filament: number; struja: number; habanje: number; satnica: number }
const PODRAZUMEVANO: Ulazne = { filament: 2300, struja: 7.2, habanje: 8, satnica: 600 };
const PAKOVANJA: [string, number][] = [
  ['Bez pakovanja', 0],
  ['Kesa + kartica (procena)', 10],
  ['Mala kutija', 24],
  ['Mala kutija + karika', 29],
  ['Velika kutija', 64],
  ['Velika kutija + karika', 69],
  ['Drugo (upišite iznos)', -1],
];
const KLJUC = 'bumbaru-kalk-trosak';

const st = { grami: '', cost: '', vreme: '', komada: '1', pak: 0, pakDrugo: '', rad: '3', cena: '' };
let ul: Ulazne = (() => { try { return { ...PODRAZUMEVANO, ...JSON.parse(localStorage.getItem(KLJUC) || '{}') }; } catch { return { ...PODRAZUMEVANO }; } })();

const broj = (s: string) => { const n = Number(String(s).trim().replace(/\s/g, '').replace(',', '.')); return Number.isFinite(n) && n > 0 ? n : 0; };
// „2h49m”, „35m42s”, „1:03”, „1:03:20”, „2,5” (sati) ili „45” (minuti) → minuti
export function minuti(s: string): number {
  const t = s.trim().toLowerCase().replace(',', '.');
  if (!t) return 0;
  const hms = t.match(/^(?:(\d+(?:\.\d+)?)\s*(?:h|č|sat[ai]?))?\s*(?:(\d+(?:\.\d+)?)\s*(?:m|min))?\s*(?:(\d+(?:\.\d+)?)\s*s)?$/);
  if (hms && (hms[1] || hms[2] || hms[3])) return (Number(hms[1] || 0) * 60) + Number(hms[2] || 0) + Number(hms[3] || 0) / 60;
  const kol = t.match(/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/);
  if (kol) return Number(kol[1]) * 60 + Number(kol[2]) + Number(kol[3] || 0) / 60;
  const n = Number(t);
  if (Number.isFinite(n) && n > 0) return t.includes('.') ? n * 60 : n; // decimalni broj = sati, ceo = minuti
  return 0;
}
const zaokruzi = (n: number) => Math.ceil(n / 10) * 10;
const d1 = (n: number) => (Math.round(n * 10) / 10).toLocaleString('sr-RS', { maximumFractionDigits: 1 });

export function panelTrosak(): HTMLElement[] {
  const rez = h('div', { class: 'stack', 'aria-live': 'polite' });
  const vremeInfo = h('p', { class: 'hint' });
  const polje = (label: string, input: HTMLElement, pomoc?: string) => h('div', { class: 'field' }, h('label', null, label, input), pomoc ? h('p', { class: 'hint' }, pomoc) : null);
  const tekst = (kljuc: keyof typeof st, attrs: Record<string, unknown>) => h('input', { class: 'input', value: String(st[kljuc]), ...attrs, oninput: (e: Event) => { (st as Record<string, unknown>)[kljuc] = (e.target as HTMLInputElement).value; crtaj(); } });
  const pakDrugo = tekst('pakDrugo', { inputmode: 'decimal', placeholder: 'din po komadu', style: 'max-width:160px' });
  const pakSel = h('select', { class: 'select', onchange: (e: Event) => { st.pak = Number((e.target as HTMLSelectElement).value); crtaj(); } },
    ...PAKOVANJA.map(([t, v], i) => h('option', { value: String(i), selected: i === st.pak }, v >= 0 ? `${t} – ${v} din` : t)));
  const ulazPolje = (label: string, k: keyof Ulazne, jed: string) => h('label', { class: 'field' }, `${label} (${jed})`,
    h('input', { class: 'input', inputmode: 'decimal', value: String(ul[k]).replace('.', ','), oninput: (e: Event) => { const n = broj((e.target as HTMLInputElement).value); if (n) { ul = { ...ul, [k]: n }; try { localStorage.setItem(KLJUC, JSON.stringify(ul)); } catch { /* bez pamćenja */ } crtaj(); } } }));
  const ulazneBox = h('div', { class: 'stack' });
  const crtajUlazne = () => ulazneBox.replaceChildren(
    h('div', { style: 'display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px' },
      ulazPolje('Filament', 'filament', 'din/kg'), ulazPolje('Struja po satu štampe', 'struja', 'din/h'),
      ulazPolje('Habanje i rizik', 'habanje', 'din/h'), ulazPolje('Tvoj rad', 'satnica', 'din/h')),
    h('p', { class: 'hint' }, 'Iz kalkulacije troškova: filament 2.300 din/kg, struja ~7,2 din/h (najskuplja EPS tarifa, Kobra 3 V2 ~180 W), habanje i rizik 4–8 din/h (ovde gornja granica), rad 600 din/h.'),
    h('button', { type: 'button', class: 'btn btn-sm', onclick: () => { ul = { ...PODRAZUMEVANO }; try { localStorage.removeItem(KLJUC); } catch { /* */ } crtajUlazne(); crtaj(); } }, 'Vrati podrazumevane'));
  crtajUlazne();

  function crtaj() {
    const g = broj(st.grami), costSl = broj(st.cost), min = minuti(st.vreme), n = Math.max(1, Math.round(broj(st.komada)) || 1);
    const pakVr = PAKOVANJA[st.pak]?.[1] ?? 0;
    const pak = pakVr >= 0 ? pakVr : broj(st.pakDrugo);
    pakDrugo.style.display = pakVr < 0 ? '' : 'none';
    const radMin = broj(st.rad);
    vremeInfo.textContent = st.vreme.trim() ? (min ? `= ${Math.floor(min / 60)} h ${Math.round(min % 60)} min` : 'Ne razumem vreme – upišite npr. 2h49m ili 35m42s.') : 'npr. 2h49m, 35m42s ili 1:03';
    if (!(g || costSl) || !min) { rez.replaceChildren(h('p', { class: 'hint' }, 'Upišite Total filament (ili Cost iz slicera) i vreme štampe.')); return; }
    const sati = min / 60;
    const plastika = costSl || (g * ul.filament) / 1000;
    const struja = sati * ul.struja, habanje = sati * ul.habanje;
    const stampaPloca = plastika + struja + habanje;
    const stampa = stampaPloca / n, rad = (radMin * ul.satnica) / 60;
    const trosak = stampa + pak + rad;
    const red = (t: string, v: string, jak = false) => h('div', { style: `display:flex;justify-content:space-between;gap:16px;${jak ? 'font-weight:800' : ''}` }, h('span', null, t), h('span', { style: 'white-space:nowrap' }, v));
    const cena = broj(st.cena);
    const marze: [string, number][] = [['×2 (minimum, 50% marže)', 2], ['×2,5 (60% marže)', 2.5], ['×3 (cilj, 67% marže)', 3]];
    rez.replaceChildren(
      h('div', { class: 'stack', style: 'gap:6px' },
        h('p', { class: 'hint' }, n > 1 ? `Ploča (${n} kom):` : 'Štampa:'),
        red(costSl ? 'Plastika (Cost iz slicera)' : `Plastika (${d1(g)} g × ${d1(ul.filament / 1000)} din)`, `${d1(plastika)} din`),
        red(`Struja (${d1(sati)} h)`, `${d1(struja)} din`),
        red('Habanje i rizik', `${d1(habanje)} din`),
        red(n > 1 ? 'Štampa cele ploče' : 'Štampa ukupno', `${d1(stampaPloca)} din`, true)),
      h('div', { class: 'stack', style: 'gap:6px;border-top:2px solid var(--line);padding-top:12px' },
        h('p', { class: 'hint' }, 'Po komadu:'),
        red('Štampa', `${d1(stampa)} din`), red('Pakovanje', `${d1(pak)} din`), red(`Rad (${d1(radMin)} min)`, `${d1(rad)} din`),
        red('Pravi trošak po komadu', `${d1(trosak)} din`, true),
        red('Bez rada i pakovanja (samo štampa)', `${d1(stampa)} din`)),
      h('div', { style: 'display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px' },
        ...marze.map(([t, f]) => { const c = zaokruzi(trosak * f); return h('div', { style: 'background:var(--honey-soft);border-radius:16px;padding:14px 16px' },
          h('div', { class: 'hint' }, t), h('div', { style: 'font-weight:800;font-size:26px' }, din(c)), h('div', { class: 'hint' }, `zarada ${din(Math.round(c - trosak))} po komadu`)); })),
      ...(cena ? [h('p', { class: 'hint warn' }, `Na ${din(cena)}: ${d1(cena / trosak)}× pravi trošak, zarada ${din(Math.round(cena - trosak))} po komadu, ${din(Math.round(((cena - trosak) * n) / sati))} po satu štampe${cena < trosak * 2 ? ' – ispod minimuma ×2' : ''}.`)] : []));
  }

  crtaj();
  return [
    h('div', { class: 'card-box sec' },
      h('h3', { class: 'h3' }, 'Kalkulator troška iz slicera – bilo koji proizvod'),
      h('div', { style: 'display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px' },
        polje('Total filament (g)', tekst('grami', { inputmode: 'decimal', placeholder: 'npr. 65,09' }), 'Uvek „Total filament”, ne „Model filament” (kod više boja uključuje flush).'),
        polje('ili Cost iz slicera (din)', tekst('cost', { inputmode: 'decimal', placeholder: 'samo ako je u sliceru 2300/kg' }), 'Ako je upisano, koristi se umesto grama.'),
        polje('Vreme štampe (Total time)', tekst('vreme', { placeholder: '2h49m' }), vremeInfo),
        polje('Komada na ploči', tekst('komada', { inputmode: 'numeric', placeholder: '1' }), 'Trošak ploče se deli na komade.'),
        polje('Pakovanje po komadu', h('div', { class: 'stack', style: 'gap:8px' }, pakSel, pakDrugo), 'Kutija: mala 6,5 cm ili velika 12,5 × 10,5 cm, sa punjenjem, 2 nalepnice i karticom.'),
        polje('Tvoj rad po komadu (min)', tekst('rad', { inputmode: 'decimal', placeholder: '3' }), 'Skidanje, čišćenje, pakovanje. 10 din po minuti.'),
        polje('Cena koju razmatraš (din, nije obavezno)', tekst('cena', { inputmode: 'decimal', placeholder: 'npr. 490' }), 'Pokazuje faktor i zaradu za tu cenu.'))),
    h('div', { class: 'card-box sec' }, h('h3', { class: 'h3' }, 'Trošak i cene'), rez),
    h('details', { class: 'card-box sec' }, h('summary', { style: 'cursor:pointer;font-weight:700' }, 'Ulazne cene (filament, struja, habanje, rad)'), ulazneBox),
  ];
}
