import { defineConfig } from 'astro/config';
import vercel from '@astrojs/vercel';

// Adresa sajta: menja se ovde kad se domen promeni (koristi se za kanonske linkove,
// sitemap, og:image i link proizvoda u Messenger poruci).
export default defineConfig({
  site: 'https://bumbaru.sajt.net',
  trailingSlash: 'always',
  build: { format: 'directory' },
  compressHTML: true,
  // Sajt je statičan; na serveru (Vercel funkcija) radi samo /api/boje za admin panel.
  output: 'static',
  adapter: vercel(),
});
