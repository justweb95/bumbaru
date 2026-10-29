// Boje filamenta za ceo sajt (izvor: src/data/boje.json, menja se iz admin panela).
import data from '../data/boje.json';

export interface Boja { id: string; naziv: string; hex: string; dostupna: boolean }

export const SVE_BOJE: Boja[] = (data.boje as Boja[]).filter((b) => /^#[0-9a-f]{6}$/i.test(b.hex));
export const BOJE_NA_STANJU: Boja[] = SVE_BOJE.filter((b) => b.dostupna);

// Tamnija nijansa za senku/ivicu na pregledu
export function darken(hex: string, amt = 0.32) {
  const n = parseInt(hex.slice(1), 16);
  // amt > 0 tamnije, amt < 0 svetlije
  const f = (v: number) => Math.min(255, Math.max(0, Math.round(amt >= 0 ? v * (1 - amt) : v + (255 - v) * -amt)));
  const r = f((n >> 16) & 255), g = f((n >> 8) & 255), b = f(n & 255);
  return '#' + ((1 << 24) | (r << 16) | (g << 8) | b).toString(16).slice(1);
}

// Svetla boja? (za izbor kontrastne ivice uzorka)
export function isLight(hex: string) {
  const n = parseInt(hex.slice(1), 16);
  const r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
  return 0.299 * r + 0.587 * g + 0.114 * b > 186;
}

export const bojaById = (id: string) => SVE_BOJE.find((b) => b.id === id);

// Podrazumevana boja: tražena ako je na stanju, inače prva na stanju
export function pick(id: string, fallbackIndex = 0): string {
  if (BOJE_NA_STANJU.some((b) => b.id === id)) return id;
  return (BOJE_NA_STANJU[fallbackIndex] || BOJE_NA_STANJU[0] || SVE_BOJE[0]).id;
}
