// Kalkulator porudžbine slavskih modlica (admin → Kalkulator).
// Cene se uzimaju iz proizvoda, pa kalkulator uvek računa isto kao sajt:
//   osnovne = Krst (SM-01), medaljoni = Sv. Nikola (SM-13), setovi = Slavski set Sv. Nikola (SS-01),
//   popust na količinu = „Sastavite svoj set” (SK-01). Akcijske = polje cena, regularne = polje „redovna”.
// Pravila: set = 8 osnovnih iste veličine + medaljon veličinu veći (mali set: srednji medaljon, srednji i veliki: veliki).
// Kalkulator sam bira koliko setova se isplati kupcu; ostale modlice idu pojedinačno sa popustom na količinu.
import { h, din } from './dom';
import type { CmsPodaci } from '../lib/cms-model';

type Vel = 'V' | 'S' | 'M';
interface Cenovnik { osn: Record<Vel, number>; med: { V: number; S: number }; set: Record<Vel, number>; popusti: { od: number; procenat: number }[] }
interface Rezultat { setovi: Record<Vel, number>; ostalo: { osn: Record<Vel, number>; med: { V: number; S: number } }; zbirOstalo: number; komOstalo: number; pct: number; ostaloPosle: number; ukupno: number; bezPopusta: number }

const kol = { osnV: 0, osnS: 0, osnM: 0, medV: 0, medS: 0 };
let akcijske = true;
const r10 = (n: number) => Math.round(n / 10) * 10;

function cenovnik(P: CmsPodaci, akc: boolean): Cenovnik {
  const opc = (id: string) => P.proizvodi.find((p) => p.id === id)?.options?.find((o) => o.cene?.some((c) => c > 0));
  const niz = (id: string, rez: number[]) => {
    const o = opc(id);
    const c = (o?.cene || []).map((v, i) => (akc ? v : o?.stare?.[i] || v));
    return rez.map((r, i) => c[i] || r);
  };
  const [oV, oS, oM] = niz('SM-01', akc ? [300, 200, 150] : [350, 300, 250]);
  const [mV, mS] = niz('SM-13', akc ? [500, 400] : [690, 590]);
  const [sV, sS, sM] = niz('SS-01', akc ? [2400, 1600, 1300] : [3490, 3090, 2590]);
  const popusti = [...(P.proizvodi.find((p) => p.id === 'SK-01')?.motivi?.popusti || [])].sort((a, b) => a.od - b.od);
  return { osn: { V: oV, S: oS, M: oM }, med: { V: mV, S: mS }, set: { V: sV, S: sS, M: sM }, popusti };
}

function izracunaj(c: Cenovnik): Rezultat {
  let naj: Rezultat | null = null;
  // veliki i srednji set troše veliki medaljon, mali set srednji
  for (let sv = 0; sv <= Math.min(Math.floor(kol.osnV / 8), kol.medV); sv++)
    for (let ss = 0; ss <= Math.min(Math.floor(kol.osnS / 8), kol.medV - sv); ss++)
      for (let sm = 0; sm <= Math.min(Math.floor(kol.osnM / 8), kol.medS); sm++) {
        const osn = { V: kol.osnV - 8 * sv, S: kol.osnS - 8 * ss, M: kol.osnM - 8 * sm };
        const med = { V: kol.medV - sv - ss, S: kol.medS - sm };
        const kom = osn.V + osn.S + osn.M + med.V + med.S;
        const zbir = osn.V * c.osn.V + osn.S * c.osn.S + osn.M * c.osn.M + med.V * c.med.V + med.S * c.med.S;
        const pct = c.popusti.filter((p) => kom >= p.od).reduce((m, p) => Math.max(m, p.procenat), 0);
        const posle = pct ? r10(zbir * (1 - pct / 100)) : zbir;
        const ukupno = sv * c.set.V + ss * c.set.S + sm * c.set.M + posle;
        if (!naj || ukupno < naj.ukupno)
          naj = { setovi: { V: sv, S: ss, M: sm }, ostalo: { osn, med }, zbirOstalo: zbir, komOstalo: kom, pct, ostaloPosle: posle, ukupno, bezPopusta: 0 };
      }
  const r = naj!;
  r.bezPopusta = kol.osnV * c.osn.V + kol.osnS * c.osn.S + kol.osnM * c.osn.M + kol.medV * c.med.V + kol.medS * c.med.S;
  return r;
}

const NAZ_SET: Record<Vel, string> = { V: 'Veliki set (8 velikih + veliki medaljon)', S: 'Srednji set (8 srednjih + veliki medaljon)', M: 'Mali set (8 malih + srednji medaljon)' };
const NAZ_OSN: Record<Vel, string> = { V: 'velika', S: 'srednja', M: 'mala' };

export function panelKalkulator(P: CmsPodaci): HTMLElement {
  const rez = h('div', { class: 'stack', 'aria-live': 'polite' });
  const red = (label: string, kljuc: keyof typeof kol, cena: () => number) => {
    const broj = h('input', { class: 'input', type: 'number', min: 0, max: 999, inputmode: 'numeric', value: String(kol[kljuc]), style: 'width:76px;text-align:center', 'aria-label': `Broj: ${label}`,
      oninput: (e: Event) => { kol[kljuc] = Math.max(0, Math.min(999, Math.round(Number((e.target as HTMLInputElement).value) || 0))); crtaj(); } });
    const pomeri = (d: number) => { kol[kljuc] = Math.max(0, kol[kljuc] + d); broj.value = String(kol[kljuc]); crtaj(); };
    const cenaEl = h('span', { class: 'hint', 'data-k': kljuc });
    return { el: h('div', { style: 'display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:6px 0' },
      h('span', { style: 'flex:1 1 140px;font-weight:700' }, label, ' ', cenaEl),
      h('button', { type: 'button', class: 'btn btn-sm', style: 'min-width:44px', 'aria-label': `Manje: ${label}`, onclick: () => pomeri(-1) }, '−'),
      broj,
      h('button', { type: 'button', class: 'btn btn-sm', style: 'min-width:44px', 'aria-label': `Više: ${label}`, onclick: () => pomeri(1) }, '+')),
      osvezi: () => { cenaEl.textContent = `· ${din(cena())}`; }, broj };
  };
  let c = cenovnik(P, akcijske);
  const redovi = [
    red('Osnovna velika', 'osnV', () => c.osn.V), red('Osnovna srednja', 'osnS', () => c.osn.S), red('Osnovna mala', 'osnM', () => c.osn.M),
    red('Medaljon veliki (Ø 9,5)', 'medV', () => c.med.V), red('Medaljon srednji (Ø 7,6)', 'medS', () => c.med.S),
  ];
  const mod = h('label', { class: 'tog' },
    h('input', { type: 'checkbox', checked: akcijske, onchange: (e: Event) => { akcijske = (e.target as HTMLInputElement).checked; crtaj(); } }),
    h('span', { class: 'tog-ui', 'aria-hidden': 'true' }), h('span', { class: 'tog-t' }, 'Akcijske cene'));
  const modOpis = h('p', { class: 'hint' });
  let tekstPoruke = '';
  const kopiraj = h('button', { type: 'button', class: 'btn btn-sm', onclick: async () => {
    try { await navigator.clipboard.writeText(tekstPoruke); kopiraj.textContent = 'Kopirano ✓'; } catch { kopiraj.textContent = 'Kopiranje nije uspelo'; }
    setTimeout(() => (kopiraj.textContent = 'Kopiraj za poruku'), 2000);
  } }, 'Kopiraj za poruku');
  const ponisti = h('button', { type: 'button', class: 'btn btn-sm', onclick: () => { (Object.keys(kol) as (keyof typeof kol)[]).forEach((k) => (kol[k] = 0)); redovi.forEach((r) => (r.broj.value = '0')); crtaj(); } }, 'Poništi');

  function crtaj() {
    c = cenovnik(P, akcijske);
    redovi.forEach((r) => r.osvezi());
    modOpis.textContent = akcijske ? 'Računa sa akcijskim cenama (cena u proizvodima).' : 'Računa sa regularnim cenama (polje „redovna” u proizvodima).';
    const ukupnoKom = Object.values(kol).reduce((a, b) => a + b, 0);
    if (!ukupnoKom) { rez.replaceChildren(h('p', { class: 'hint' }, 'Upišite koliko modlica kupac uzima.')); tekstPoruke = ''; return; }
    const r = izracunaj(c);
    const stavke: [string, number][] = [];
    const linije: string[] = [];
    (['V', 'S', 'M'] as Vel[]).forEach((v) => { if (r.setovi[v]) { stavke.push([`${NAZ_SET[v]} × ${r.setovi[v]}`, r.setovi[v] * c.set[v]]); linije.push(`${NAZ_SET[v]} × ${r.setovi[v]}: ${din(r.setovi[v] * c.set[v])}`); } });
    const poj: string[] = [];
    (['V', 'S', 'M'] as Vel[]).forEach((v) => { if (r.ostalo.osn[v]) poj.push(`${r.ostalo.osn[v]} × osnovna ${NAZ_OSN[v]} (${din(c.osn[v])})`); });
    if (r.ostalo.med.V) poj.push(`${r.ostalo.med.V} × medaljon veliki (${din(c.med.V)})`);
    if (r.ostalo.med.S) poj.push(`${r.ostalo.med.S} × medaljon srednji (${din(c.med.S)})`);
    if (poj.length) {
      stavke.push([`Pojedinačno: ${poj.join(', ')}`, r.zbirOstalo]);
      linije.push(`Pojedinačno: ${poj.join(', ')} = ${din(r.zbirOstalo)}`);
      if (r.pct) { stavke.push([`Popust na količinu ${r.pct}% (${r.komOstalo} kom)`, r.ostaloPosle - r.zbirOstalo]); linije.push(`Popust ${r.pct}% (${r.komOstalo} kom): −${din(r.zbirOstalo - r.ostaloPosle)}`); }
    }
    const usteda = r.bezPopusta - r.ukupno;
    const regularno = cenovnik(P, false);
    const bezAkcije = kol.osnV * regularno.osn.V + kol.osnS * regularno.osn.S + kol.osnM * regularno.osn.M + kol.medV * regularno.med.V + kol.medS * regularno.med.S;
    linije.push(`Ukupno: ${din(r.ukupno)}`);
    if (akcijske && bezAkcije > r.ukupno) linije.push(`Bez akcije bi bilo ${din(bezAkcije)}, ušteda ${din(bezAkcije - r.ukupno)}.`);
    else if (usteda > 0) linije.push(`Ušteda ${din(usteda)}.`);
    tekstPoruke = linije.join('\n');
    rez.replaceChildren(
      h('div', { class: 'stack', style: 'gap:6px' }, ...stavke.map(([t, n]) => h('div', { style: 'display:flex;justify-content:space-between;gap:16px' }, h('span', null, t), h('span', { style: 'white-space:nowrap;font-weight:700' }, n < 0 ? `−${din(-n)}` : din(n))))),
      h('div', { style: 'display:flex;justify-content:space-between;align-items:baseline;gap:16px;border-top:2px solid var(--line);padding-top:12px' },
        h('span', { style: 'font-weight:800;font-size:20px' }, `Za naplatu (${ukupnoKom} kom)`),
        h('span', { style: 'font-weight:800;font-size:28px' }, din(r.ukupno))),
      akcijske && bezAkcije > r.ukupno
        ? h('p', { class: 'hint' }, `Bez akcije (regularne cene, bez popusta) bi bilo ${din(bezAkcije)}, kupac štedi ${din(bezAkcije - r.ukupno)}.`)
        : usteda > 0 ? h('p', { class: 'hint' }, `Bez seta i popusta bi bilo ${din(r.bezPopusta)}, kupac štedi ${din(usteda)}.`) : null,
      h('div', { style: 'display:flex;gap:10px;flex-wrap:wrap' }, kopiraj, ponisti));
  }
  crtaj();
  const popustiTxt = c.popusti.length ? c.popusti.map((p) => `od ${p.od} kom ${p.procenat}%`).join(', ') : 'nema';
  return h('div', { class: 'stack' },
    h('div', { class: 'card-box sec' },
      h('h3', { class: 'h3' }, 'Kalkulator porudžbine – slavske modlice'),
      mod, modOpis,
      h('div', { class: 'stack', style: 'gap:0' }, ...redovi.map((r) => r.el)),
      h('p', { class: 'hint' }, `Uvek ista pravila kao na sajtu: 8 osnovnih iste veličine + medaljon veličinu veći računa se kao set (mali set ide sa srednjim medaljonom), kad je to jeftinije za kupca. Ostale modlice idu pojedinačno sa popustom na količinu iz „Sastavite svoj set” (${popustiTxt}). Cene se menjaju u proizvodima Krst, Sveti Nikola i Slavski set Sveti Nikola.`)),
    h('div', { class: 'card-box sec' }, h('h3', { class: 'h3' }, 'Račun'), rez));
}
