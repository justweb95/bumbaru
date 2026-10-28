import { defineConfig } from 'astro/config';

// Adresa sajta: menja se ovde kad se domen promeni (koristi se za kanonske linkove,
// sitemap, og:image i link proizvoda u Messenger poruci).
export default defineConfig({
  site: 'https://bumbaru.sajt.net',
  trailingSlash: 'always',
  build: { format: 'directory' },
  compressHTML: true,
});
