import { defineConfig } from 'astro/config';
import vercel from '@astrojs/vercel';

// Adresa sajta: menja se ovde kad se domen promeni (koristi se za kanonske linkove,
// sitemap, og:image i link proizvoda u Messenger poruci).
export default defineConfig({
  site: 'https://bumbaru.rs',
  trailingSlash: 'always',
  build: { format: 'directory' },
  compressHTML: true,
  // Sajt je statičan; na serveru (Vercel funkcija) radi samo /api/boje za admin panel.
  output: 'static',
  // Stara adresa tacne (preimenovana u „Rebrasta ovalna tacna”)
  redirects: { '/proizvod/novogodisnja-tacna-za-figure/': '/proizvod/rebrasta-ovalna-tacna/' },
  adapter: vercel(),
});
