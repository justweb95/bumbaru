// Kategorije kataloga. Adrese su iste kao u SEO planu (/katalog/<kategorija>/),
// da bi kasniji prelazak na WordPress + WooCommerce zadržao iste linkove.

import data from './kategorije.json';

export interface Faq { q: string; a: string }
export interface Category {
  slug: string;
  label: string; // kratko ime: meni, oznaka iznad naslova
  title: string; // H1
  text: string; // uvod ispod naslova
  seoTitle: string;
  meta: string;
  bg: string; // pozadina trake na vrhu kategorije
  bgPos?: string;
  icon: string; // putanja linijske ikonice 24×24
  filters?: { id: string; label: string }[];
  soon?: boolean;
  about: { h2: string; p: string[] };
  faq: Faq[];
}

// Podaci su u src/data/kategorije.json i menjaju se iz admin panela (redosled = redosled u meniju).
export const CATEGORIES = (data as unknown as { kategorije: Category[] }).kategorije;

export const categoryBySlug = (slug: string) => CATEGORIES.find((c) => c.slug === slug)!;
export const catUrl = (slug: string) => `/katalog/${slug}/`;
