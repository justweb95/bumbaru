// Korpa u pregledaču (localStorage, bez naloga). Porudžbina ide istim putem kao i do sada:
// jedna poruka na Messenger sa svim proizvodima iz korpe.

export interface Stavka {
  kljuc: string; // isti proizvod sa istim izborom se sabira
  id: string; // šifra
  slug: string;
  naziv: string;
  slika: string;
  url: string;
  linije: string[]; // izbor kupca (boje, motivi, imena…)
  kol: number;
  cena: number; // ukupno za stavku u din (0 = cenu javljamo u odgovoru)
  jed?: number; // cena po komadu: kad postoji, kupac u korpi menja količinu
  od?: boolean; // „od … din”
}

const K = 'bumbaru-korpa';
export const citaj = (): Stavka[] => {
  try { const l = JSON.parse(localStorage.getItem(K) || '[]'); return Array.isArray(l) ? l : []; } catch { return []; }
};
export const sacuvaj = (l: Stavka[]) => {
  try { localStorage.setItem(K, JSON.stringify(l)); } catch { /* privatni režim: korpa radi do zatvaranja strane */ }
  window.dispatchEvent(new CustomEvent('korpa', { detail: l }));
};
export const dodaj = (s: Omit<Stavka, 'kljuc'>) => {
  const kljuc = s.id + '|' + s.linije.join('|');
  const l = citaj();
  const isti = l.find((x) => x.kljuc === kljuc);
  if (isti) { isti.kol += s.kol; isti.cena += s.cena; } else l.push({ ...s, kljuc });
  sacuvaj(l);
  window.dispatchEvent(new CustomEvent('korpa-dodato', { detail: s }));
};
export const ukupnoKomada = (l = citaj()) => l.reduce((a, x) => a + x.kol, 0);
export const din = (n: number) => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' din';
