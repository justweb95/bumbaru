// Akcijske cene (npr. novogodišnja akcija iz admina → Podešavanja).
import { SITE } from '../data/site';
import type { Product } from '../data/products';

export const AKCIJA = SITE.akcija;
// Procenat akcije za proizvod (0 ako akcija ne važi za njega)
// samo: šifre proizvoda (npr. ['NG-01']); kad je upisano, akcija važi samo za njih, a ne za celu kategoriju
export const akcijaPct = (p: Product) =>
  AKCIJA && (AKCIJA.samo.length ? AKCIJA.samo.includes(p.id) : p.categories.includes(AKCIJA.kategorija)) ? AKCIJA.procenat : 0;
// Cena posle popusta, zaokružena na 10 din
export const snizi = (n: number, pct: number) => (pct ? Math.round((n * (1 - pct / 100)) / 10) * 10 : n);
