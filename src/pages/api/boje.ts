// Admin API za boje filamenta.
// GET  /api/boje/ (zaglavlja x-admin-user i x-admin-password) -> trenutni src/data/boje.json sa GitHub-a
// POST /api/boje/ (ista zaglavlja, telo { boje: [...] }) -> upisuje boje.json u repozitorijum,
//                 a Vercel posle toga sam ponovo objavi sajt (oko 1 minut).
// Prijava: src/lib/admin-auth.ts (otisak imena i lozinke; Vercel promenljiva ADMIN_HASH ima prednost).
// Potrebna promenljiva na Vercel-u: GITHUB_TOKEN (fine-grained, Contents: Read and write, samo ovaj repo).
import type { APIRoute } from 'astro';
import { proveriPrijavu } from '../../lib/admin-auth';

export const prerender = false;

const env = (k: string) => (typeof process !== 'undefined' ? process.env[k] : undefined) ?? (import.meta.env as Record<string, string | undefined>)[k];
const REPO = () => env('GITHUB_REPO') || 'justweb95/bumbaru';
const BRANCH = () => env('GITHUB_BRANCH') || 'main';
const API = () => env('GITHUB_API_URL') || 'https://api.github.com';
const PATH = 'src/data/boje.json';
const NAPOMENA = 'Boje filamenta. Menja se iz admin panela. dostupna: false = boja se ne nudi kupcima.';

const json = (data: unknown, status = 200) =>
  new Response(JSON.stringify(data), { status, headers: { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' } });
const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));
// zaglavlja stižu kodirana (encodeURIComponent), da rade i slova kao č, ć, š
const zaglavlje = (request: Request, ime: string) => {
  const v = request.headers.get(ime) || '';
  try { return decodeURIComponent(v); } catch { return v; }
};

async function proveri(request: Request) {
  const korisnik = zaglavlje(request, 'x-admin-user').trim();
  const lozinka = zaglavlje(request, 'x-admin-password');
  let ok = false;
  try { ok = !!korisnik && !!lozinka && (await proveriPrijavu(korisnik, lozinka, env('ADMIN_HASH'))); } catch { ok = false; }
  if (!ok) {
    await sleep(700); // usporava pogađanje lozinke
    return json({ greska: 'Pogrešno korisničko ime ili lozinka.' }, 401);
  }
  if (!env('GITHUB_TOKEN')) return json({ greska: 'GITHUB_TOKEN nije podešen na Vercel-u.' }, 503);
  return null;
}

const gh = (path: string, init: RequestInit = {}) =>
  fetch(`${API()}/repos/${REPO()}/${path}`, {
    ...init,
    headers: {
      Authorization: `Bearer ${env('GITHUB_TOKEN')}`,
      Accept: 'application/vnd.github+json',
      'X-GitHub-Api-Version': '2022-11-28',
      'User-Agent': 'bumbaru-admin',
      ...(init.body ? { 'Content-Type': 'application/json' } : {}),
    },
  });

async function procitaj() {
  const r = await gh(`contents/${PATH}?ref=${encodeURIComponent(BRANCH())}`, { cache: 'no-store' } as RequestInit);
  if (!r.ok) throw new Error(`GitHub ${r.status}: ${(await r.text()).slice(0, 200)}`);
  const f = (await r.json()) as { sha: string; content: string };
  return { sha: f.sha, data: JSON.parse(Buffer.from(f.content, 'base64').toString('utf8')) };
}

interface Boja { id: string; naziv: string; hex: string; dostupna: boolean }
function ocisti(ulaz: unknown): Boja[] | string {
  const lista = (ulaz as { boje?: unknown })?.boje;
  if (!Array.isArray(lista) || lista.length === 0) return 'Lista boja je prazna.';
  if (lista.length > 60) return 'Najviše 60 boja.';
  const ids = new Set<string>();
  const out: Boja[] = [];
  for (const b of lista) {
    const id = String(b?.id ?? '').trim();
    const naziv = String(b?.naziv ?? '').trim().replace(/\s+/g, ' ');
    const hex = String(b?.hex ?? '').trim().toUpperCase();
    if (!/^[a-z0-9-]{1,30}$/.test(id)) return `Neispravna oznaka boje: „${id}”.`;
    if (ids.has(id)) return `Boja „${naziv || id}” se ponavlja.`;
    if (!naziv || naziv.length > 30) return 'Svaka boja mora da ima naziv (do 30 znakova).';
    if (!/^#[0-9A-F]{6}$/.test(hex)) return `Neispravna boja za „${naziv}”.`;
    ids.add(id);
    out.push({ id, naziv, hex, dostupna: b?.dostupna !== false });
  }
  if (!out.some((b) => b.dostupna)) return 'Bar jedna boja mora biti na stanju.';
  return out;
}

// boje.json u istom formatu kao ručno pisan (jedna boja po redu)
const format = (boje: Boja[]) =>
  `{\n  "napomena": ${JSON.stringify(NAPOMENA)},\n  "boje": [\n${boje
    .map((b) => `    { "id": ${JSON.stringify(b.id)}, "naziv": ${JSON.stringify(b.naziv)}, "hex": "${b.hex}", "dostupna": ${b.dostupna} }`)
    .join(',\n')}\n  ]\n}\n`;

export const GET: APIRoute = async ({ request }) => {
  const stop = await proveri(request);
  if (stop) return stop;
  try {
    const { data } = await procitaj();
    return json({ boje: data.boje });
  } catch (e) {
    return json({ greska: 'Ne mogu da pročitam boje sa GitHub-a. ' + (e as Error).message }, 502);
  }
};

export const POST: APIRoute = async ({ request }) => {
  const stop = await proveri(request);
  if (stop) return stop;
  let telo: unknown;
  try { telo = await request.json(); } catch { return json({ greska: 'Neispravan zahtev.' }, 400); }
  const boje = ocisti(telo);
  if (typeof boje === 'string') return json({ greska: boje }, 400);
  try {
    const { sha } = await procitaj();
    const r = await gh(`contents/${PATH}`, {
      method: 'PUT',
      body: JSON.stringify({
        message: `Boje: izmena iz admin panela (${boje.filter((b) => b.dostupna).length} na stanju)`,
        content: Buffer.from(format(boje), 'utf8').toString('base64'),
        sha,
        branch: BRANCH(),
      }),
    });
    if (!r.ok) return json({ greska: `GitHub nije prihvatio izmenu (${r.status}). ${(await r.text()).slice(0, 200)}` }, 502);
    const res = (await r.json()) as { commit?: { sha?: string } };
    return json({ ok: true, commit: res.commit?.sha?.slice(0, 7) || '' });
  } catch (e) {
    return json({ greska: 'Greška pri čuvanju. ' + (e as Error).message }, 502);
  }
};
