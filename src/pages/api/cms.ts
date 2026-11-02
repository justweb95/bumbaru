// Admin API (proizvodi, kategorije, boje, podešavanja). Sve izmene idu u GitHub repozitorijum,
// a Vercel posle svakog čuvanja sam ponovo objavi sajt (oko 1 minut). Ako objava ne uspe, ostaje prethodna verzija.
//
// GET  /api/cms/                  -> trenutni podaci sa GitHub-a
// GET  /api/cms/?status=<commit>  -> stanje objave na Vercel-u (ako token sme da čita „Commit statuses”)
// POST /api/cms/ { akcija: 'slika', slug, lg, sm, sq, og, w, h } -> otpremi jednu fotografiju (4 verzije), vrati putanju
// POST /api/cms/ { akcija: 'sacuvaj', podaci, slike: [{ path, sha }] } -> sve izmene u jednom commit-u
//
// Zaglavlja: x-admin-user i x-admin-password (encodeURIComponent). Prijava: src/lib/admin-auth.ts.
// Potrebna promenljiva na Vercel-u: GITHUB_TOKEN (fine-grained, samo ovaj repo, Contents: Read and write).
import type { APIRoute } from 'astro';
import { randomBytes } from 'node:crypto';
import { proveriPrijavu } from '../../lib/admin-auth';
import { ocistiSve, fajlovi, fajloviSlike, fotografije, PUTANJE, ADMIN_SLIKA, CmsGreska, type CmsPodaci } from '../../lib/cms-model';

export const prerender = false;

const env = (k: string) => (typeof process !== 'undefined' ? process.env[k] : undefined) ?? (import.meta.env as Record<string, string | undefined>)[k];
const REPO = () => env('GITHUB_REPO') || 'justweb95/bumbaru';
const BRANCH = () => env('GITHUB_BRANCH') || 'main';
const API = () => env('GITHUB_API_URL') || 'https://api.github.com';

const json = (data: unknown, status = 200) =>
  new Response(JSON.stringify(data), { status, headers: { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' } });
const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));
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
  if (!env('GITHUB_TOKEN')) return json({ greska: 'GITHUB_TOKEN nije podešen na Vercel-u.', bezTokena: true }, 503);
  return null;
}

class GhGreska extends Error { constructor(public status: number, msg: string) { super(msg); } }
async function gh<T = any>(path: string, init: RequestInit = {}): Promise<T> {
  const r = await fetch(`${API()}/repos/${REPO()}/${path}`, {
    ...init,
    cache: 'no-store',
    headers: {
      Authorization: `Bearer ${env('GITHUB_TOKEN')}`,
      Accept: 'application/vnd.github+json',
      'X-GitHub-Api-Version': '2022-11-28',
      'User-Agent': 'bumbaru-admin',
      ...(init.body ? { 'Content-Type': 'application/json' } : {}),
    },
  } as RequestInit);
  if (!r.ok) {
    const t = (await r.text()).slice(0, 300);
    const zasto = r.status === 401 ? ' (token je neispravan ili je istekao)' : r.status === 403 || r.status === 404 ? ' (token nema dozvolu za ovaj repozitorijum ili za Contents: Read and write)' : '';
    throw new GhGreska(r.status, `GitHub ${r.status}${zasto}: ${t}`);
  }
  return r.json() as Promise<T>;
}

async function procitajSve(): Promise<{ podaci: CmsPodaci; commit: string }> {
  const ref = await gh<{ object: { sha: string } }>(`git/ref/heads/${encodeURIComponent(BRANCH())}`);
  const commit = ref.object.sha;
  const citaj = async (path: string) => {
    const f = await gh<{ content: string }>(`contents/${path}?ref=${commit}`);
    return JSON.parse(Buffer.from(f.content, 'base64').toString('utf8'));
  };
  const [p, k, b, s] = await Promise.all([citaj(PUTANJE.proizvodi), citaj(PUTANJE.kategorije), citaj(PUTANJE.boje), citaj(PUTANJE.podesavanja)]);
  return { commit, podaci: { proizvodi: p.proizvodi, kategorije: k.kategorije, boje: b.boje, podesavanja: { prikaziCene: s.prikaziCene === true, ...(s.akcija ? { akcija: s.akcija } : {}) } } };
}

export const GET: APIRoute = async ({ request, url }) => {
  const stop = await proveri(request);
  if (stop) return stop;
  const status = url.searchParams.get('status');
  if (status) {
    if (!/^[0-9a-f]{40}$/.test(status)) return json({ greska: 'Neispravan commit.' }, 400);
    try {
      const s = await gh<{ state: string; statuses: { context: string; state: string; target_url?: string }[] }>(`commits/${status}/status`);
      const v = s.statuses.find((x) => /vercel/i.test(x.context));
      return json({ stanje: v ? v.state : 'ceka' });
    } catch (e) {
      return json({ stanje: 'nepoznato', greska: (e as Error).message });
    }
  }
  try {
    return json(await procitajSve());
  } catch (e) {
    return json({ greska: 'Ne mogu da pročitam podatke sa GitHub-a. ' + (e as Error).message }, 502);
  }
};

// ——— fotografije ———
const MAGIJA: Record<string, (b: Buffer) => boolean> = {
  webp: (b) => b.length > 12 && b.toString('ascii', 0, 4) === 'RIFF' && b.toString('ascii', 8, 12) === 'WEBP',
  jpg: (b) => b.length > 3 && b[0] === 0xff && b[1] === 0xd8 && b[2] === 0xff,
};

async function otpremiSliku(telo: any) {
  const slug = String(telo?.slug ?? '');
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug) || slug.length > 80) return json({ greska: 'Proizvod prvo mora da ima adresu (npr. mini-jelka).' }, 400);
  const w = Number(telo?.w), h = Number(telo?.h);
  if (!Number.isInteger(w) || !Number.isInteger(h) || w < 1 || h < 1 || w > 4000 || h > 4000) return json({ greska: 'Neispravne mere slike.' }, 400);
  const id = 'u' + randomBytes(5).toString('hex').slice(0, 9);
  const src = `/img/p/${slug}/${id}`;
  const og = `${src}-og.jpg`;
  const delovi: [string, string, 'webp' | 'jpg'][] = [['lg', `public${src}-lg.webp`, 'webp'], ['sm', `public${src}-sm.webp`, 'webp'], ['sq', `public${src}-sq.webp`, 'webp'], ['og', `public${og}`, 'jpg']];
  const fajlovi = delovi.map(([k, path, tip]) => {
    const s = String(telo?.[k] ?? '');
    const buf = Buffer.from(s, 'base64');
    if (!MAGIJA[tip](buf)) throw new CmsGreska(`Verzija „${k}” nije ispravna ${tip === 'webp' ? 'WebP' : 'JPEG'} slika. Probajte u Chrome-u ili Edge-u.`);
    if (buf.length > 3_000_000) throw new CmsGreska('Slika je prevelika.');
    return { path, content: buf.toString('base64') };
  });
  const blobovi = await Promise.all(fajlovi.map(async (f) => {
    const r = await gh<{ sha: string }>('git/blobs', { method: 'POST', body: JSON.stringify({ content: f.content, encoding: 'base64' }) });
    return { path: f.path, sha: r.sha };
  }));
  return json({ src, og, w, h, slike: blobovi });
}

// ——— čuvanje ———
interface Stavka { path: string; mode: '100644'; type: 'blob'; sha?: string | null; content?: string }

async function sacuvaj(telo: any) {
  const podaci = ocistiSve(telo?.podaci || {});
  const tekstovi = fajlovi(podaci);

  // fotografije koje podaci koriste
  const koristiSrc = new Set(podaci.proizvodi.flatMap((p) => fotografije(p).map((im) => im.src)));
  const NOVA = /^public(\/img\/p\/[a-z0-9-]+\/u[0-9a-f]{9})-(lg|sm|sq|og)\.(webp|jpg)$/;

  const nove = new Map<string, string>(); // path -> sha (tek otpremljene, samo one koje se koriste)
  for (const s of Array.isArray(telo?.slike) ? telo.slike : []) {
    const path = String(s?.path ?? ''), sha = String(s?.sha ?? '');
    const m = path.match(NOVA);
    if (m && (m[3] === 'jpg') === (m[2] === 'og') && koristiSrc.has(m[1]) && /^[0-9a-f]{40}$/.test(sha)) nove.set(path, sha);
  }

  for (let pokusaj = 0; pokusaj < 2; pokusaj++) {
    const ref = await gh<{ object: { sha: string } }>(`git/ref/heads/${encodeURIComponent(BRANCH())}`);
    const glava = ref.object.sha;
    const commit = await gh<{ tree: { sha: string } }>(`git/commits/${glava}`);
    const stablo = await gh<{ tree: { path: string; type: string }[]; truncated: boolean }>(`git/trees/${commit.tree.sha}?recursive=1`);
    const postoji = new Set(stablo.tree.filter((t) => t.type === 'blob').map((t) => t.path));

    // svaka fotografija iz podataka mora da postoji (u repozitorijumu ili među tek otpremljenim)
    if (!stablo.truncated) {
      for (const p of podaci.proizvodi) for (const im of fotografije(p)) {
        const nema = fajloviSlike(im.src, im.og).filter((f) => !postoji.has(f) && !nove.has(f));
        if (nema.length) throw new CmsGreska(`„${p.title}”: fotografija ${im.src} nije otpremljena do kraja. Obrišite je i dodajte ponovo.`);
      }
    }

    const stavke: Stavka[] = (Object.keys(PUTANJE) as (keyof typeof PUTANJE)[]).map((k) => ({ path: PUTANJE[k], mode: '100644', type: 'blob', content: tekstovi[k] }));
    for (const [path, sha] of nove) stavke.push({ path, mode: '100644', type: 'blob', sha });
    // brišu se samo fotografije otpremljene iz admina koje više nijedan proizvod ne koristi (stare fotografije ostaju)
    let obrisano = 0;
    if (!stablo.truncated) {
      for (const path of postoji) {
        const m = path.match(NOVA);
        if (m && ADMIN_SLIKA.test(m[1]) && !koristiSrc.has(m[1]) && !nove.has(path)) { stavke.push({ path, mode: '100644', type: 'blob', sha: null }); if (m[2] === 'lg') obrisano++; }
      }
    }

    const novoStablo = await gh<{ sha: string }>('git/trees', { method: 'POST', body: JSON.stringify({ base_tree: commit.tree.sha, tree: stavke }) });
    if (novoStablo.sha === commit.tree.sha) return json({ ok: true, bezIzmena: true });

    const vidljivih = podaci.proizvodi.filter((p) => !p.skriven).length;
    const novih = [...nove.keys()].filter((x) => x.endsWith('-lg.webp')).length;
    const poruka = `Admin: izmene sajta (${vidljivih} proizvoda na sajtu${novih ? `, novih fotografija: ${novih}` : ''}${obrisano ? `, obrisanih fotografija: ${obrisano}` : ''})`;
    const novi = await gh<{ sha: string }>('git/commits', { method: 'POST', body: JSON.stringify({ message: poruka, tree: novoStablo.sha, parents: [glava] }) });
    try {
      await gh(`git/refs/heads/${encodeURIComponent(BRANCH())}`, { method: 'PATCH', body: JSON.stringify({ sha: novi.sha, force: false }) });
      return json({ ok: true, commit: novi.sha });
    } catch (e) {
      // neko je u međuvremenu promenio granu (npr. izmena koda): pokušaj ponovo na novoj verziji
      if (e instanceof GhGreska && e.status === 422 && pokusaj === 0) continue;
      throw e;
    }
  }
  throw new CmsGreska('Grana se menjala za vreme čuvanja. Pokušajte ponovo.');
}

export const POST: APIRoute = async ({ request }) => {
  const stop = await proveri(request);
  if (stop) return stop;
  let telo: any;
  try { telo = await request.json(); } catch { return json({ greska: 'Neispravan zahtev (možda je slika prevelika).' }, 400); }
  try {
    if (telo?.akcija === 'slika') return await otpremiSliku(telo);
    if (telo?.akcija === 'sacuvaj') return await sacuvaj(telo);
    return json({ greska: 'Nepoznata akcija.' }, 400);
  } catch (e) {
    if (e instanceof CmsGreska) return json({ greska: e.message }, 400);
    return json({ greska: 'Greška pri čuvanju. ' + (e as Error).message }, 502);
  }
};
