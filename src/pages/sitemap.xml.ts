import type { APIRoute } from 'astro';
import { SITE } from '../data/site';
import { CATEGORIES, catUrl } from '../data/categories';
import { PRODUCTS, productUrl } from '../data/products';

export const GET: APIRoute = () => {
  const urls = ['/', '/katalog/', ...CATEGORIES.map((c) => catUrl(c.slug)), ...PRODUCTS.map((p) => productUrl(p.slug)), '/po-meri/', '/pomoc/', '/politika-privatnosti/'];
  const body = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls.map((u) => `  <url><loc>${SITE.url}${u}</loc></url>`).join('\n')}\n</urlset>\n`;
  return new Response(body, { headers: { 'Content-Type': 'application/xml; charset=utf-8' } });
};
