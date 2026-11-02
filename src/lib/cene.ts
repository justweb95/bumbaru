// Akcijske cene (npr. novogodišnja akcija iz admina → Podešavanja).
import { SITE } from '../data/site';
import type { Product } from '../data/products';

export const AKCIJA = SITE.akcija;
// Procenat akcije za proizvod (0 ako akcija ne važi za njega)
export const akcijaPct = (p: Product) => (AKCIJA && p.categories.includes(AKCIJA.kategorija) ? AKCIJA.procenat : 0);
// Cena posle popusta, zaokružena na 10 din
export const snizi = (n: number, pct: number) => (pct ? Math.round((n * (1 - pct / 100)) / 10) * 10 : n);
