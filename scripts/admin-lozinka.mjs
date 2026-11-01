// Pravi otisak (hash) korisničkog imena i lozinke za admin panel boja.
// Upotreba:  node scripts/admin-lozinka.mjs <korisnicko-ime> <lozinka>
// Ispisani red se stavi u src/lib/admin-auth.ts (ADMIN_HASH) ili u Vercel promenljivu ADMIN_HASH.
// Ni ime ni lozinka se ne čuvaju u kodu, samo ovaj otisak.
import { randomBytes, scryptSync } from 'node:crypto';

const [korisnik, lozinka] = process.argv.slice(2);
if (!korisnik || !lozinka) {
  console.error('Upotreba: node scripts/admin-lozinka.mjs <korisnicko-ime> <lozinka>');
  process.exit(1);
}

const N = 32768, r = 8, p = 1;
const so = randomBytes(16);
const otisak = scryptSync(`${korisnik}\u0000${lozinka}`, so, 32, { N, r, p, maxmem: 64 * 1024 * 1024 });
console.log(`scrypt$${N}$${r}$${p}$${so.toString('base64')}$${otisak.toString('base64')}`);
