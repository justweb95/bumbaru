// Prijava za admin panel boja (korisničko ime + lozinka).
// U kodu se ne čuvaju ni ime ni lozinka, samo scrypt otisak oba zajedno.
// Nova prijava: node scripts/admin-lozinka.mjs <korisnicko-ime> <lozinka>, pa ispisani red
// zameni ADMIN_HASH ispod (ili ga stavi u Vercel promenljivu ADMIN_HASH, koja ima prednost).
import { scrypt, timingSafeEqual } from 'node:crypto';

const ADMIN_HASH = 'scrypt$32768$8$1$FqKQE1/JVD7pOeOE1Ju/OQ==$rE7e49HfN7ZkiMAknEMM6ox4afBeyOvso7tzEp8BccY=';

const izracunaj = (ulaz: string, so: Buffer, duzina: number, N: number, r: number, p: number) =>
  new Promise<Buffer>((res, rej) =>
    scrypt(ulaz, so, duzina, { N, r, p, maxmem: 64 * 1024 * 1024 }, (e, k) => (e ? rej(e) : res(k))),
  );

export async function proveriPrijavu(korisnik: string, lozinka: string, hashIzOkruzenja?: string) {
  const delovi = (hashIzOkruzenja || ADMIN_HASH).trim().split('$');
  if (delovi.length !== 6 || delovi[0] !== 'scrypt') return false;
  const [, N, r, p, so, otisak] = delovi;
  const ocekivano = Buffer.from(otisak, 'base64');
  const dato = await izracunaj(`${korisnik}\u0000${lozinka}`, Buffer.from(so, 'base64'), ocekivano.length, +N, +r, +p);
  return dato.length === ocekivano.length && timingSafeEqual(dato, ocekivano);
}
