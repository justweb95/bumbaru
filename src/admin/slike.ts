// Obrada fotografije u pregledaču pre otpremanja: 4 verzije, na svakoj logo dole levo.
//   lg  – cela fotografija, duža strana najviše 1200 px (galerija na strani proizvoda)
//   sm  – ista, 640 px širine
//   sq  – kvadrat 900 × 900, isečen iz sredine (kartice u katalogu i sličice)
//   og  – 1200 × 630 JPEG za deljenje (Messenger, Facebook): kvadrat na sredini, zamagljena pozadina
// Logo: tamni na svetlom delu fotografije, svetli na tamnom (meri se svetlina ugla gde ide logo).

export interface Obradjena { lg: Blob; sm: Blob; sq: Blob; og: Blob; w: number; h: number; pregled: string }

let logoi: Promise<{ tamni: HTMLImageElement; svetli: HTMLImageElement }> | null = null;
const ucitaj = (src: string) => new Promise<HTMLImageElement>((res, rej) => { const i = new Image(); i.onload = () => res(i); i.onerror = () => rej(new Error('Logo nije učitan: ' + src)); i.src = src; });
const logo = () => (logoi ||= Promise.all([ucitaj('/img/brand/logo-tamni.png'), ucitaj('/img/brand/logo-svetli.png')]).then(([tamni, svetli]) => ({ tamni, svetli })));

async function dekodiraj(file: File): Promise<ImageBitmap | HTMLImageElement> {
  try {
    return await createImageBitmap(file, { imageOrientation: 'from-image' } as ImageBitmapOptions);
  } catch {
    const url = URL.createObjectURL(file);
    try { return await ucitaj(url); } finally { setTimeout(() => URL.revokeObjectURL(url), 5000); }
  }
}

const platno = (w: number, h: number) => {
  const c = document.createElement('canvas');
  c.width = w; c.height = h;
  const ctx = c.getContext('2d')!;
  ctx.imageSmoothingEnabled = true; ctx.imageSmoothingQuality = 'high';
  return { c, ctx };
};

function zig(ctx: CanvasRenderingContext2D, w: number, h: number, l: { tamni: HTMLImageElement; svetli: HTMLImageElement }) {
  const m = Math.min(w, h);
  const lw = Math.round(m * 0.2);
  const lh = Math.round((lw * l.tamni.naturalHeight) / l.tamni.naturalWidth);
  const x = Math.round(m * 0.028), y = Math.round(h - m * 0.028 - lh);
  // svetlina dela slike ispod loga (0 = crno, 1 = belo)
  const d = ctx.getImageData(x, y, lw, lh).data;
  let s = 0;
  for (let i = 0; i < d.length; i += 16) s += (0.2126 * d[i] + 0.7152 * d[i + 1] + 0.0722 * d[i + 2]) / 255;
  const svetlina = s / (d.length / 16);
  ctx.drawImage(svetlina < 0.5 ? l.svetli : l.tamni, x, y, lw, lh);
}

const kodiraj = (c: HTMLCanvasElement, tip: 'image/webp' | 'image/jpeg', q: number) =>
  new Promise<Blob>((res, rej) => c.toBlob((b) => {
    if (!b) return rej(new Error('Slika nije mogla da se sačuva.'));
    if (b.type !== tip) return rej(new Error('Ovaj pregledač ne ume da pravi WebP slike. Dodajte fotografije iz Chrome-a ili Edge-a.'));
    res(b);
  }, tip, q));

export async function obradi(file: File): Promise<Obradjena> {
  if (!/^image\//.test(file.type) && !/\.(jpe?g|png|webp|heic|heif)$/i.test(file.name)) throw new Error(`„${file.name}” nije fotografija.`);
  const [img, l] = await Promise.all([dekodiraj(file), logo()]);
  const W = 'naturalWidth' in img ? img.naturalWidth : img.width;
  const H = 'naturalHeight' in img ? img.naturalHeight : img.height;
  if (!W || !H) throw new Error(`„${file.name}” ne može da se otvori. Ako je HEIC sa iPhone-a, sačuvajte je kao JPG.`);
  if (Math.min(W, H) < 400) throw new Error(`„${file.name}” je premala (${W} × ${H}). Potrebno je bar 400 px po kraćoj strani.`);

  // lg
  const k = Math.min(1, 1200 / Math.max(W, H));
  const w = Math.round(W * k), hh = Math.round(H * k);
  const lg = platno(w, hh);
  lg.ctx.drawImage(img, 0, 0, w, hh);
  zig(lg.ctx, w, hh, l);

  // sm (iz lg, sa istim logom)
  const sw = Math.min(640, w), sh = Math.round((hh * sw) / w);
  const sm = platno(sw, sh);
  sm.ctx.drawImage(lg.c, 0, 0, sw, sh);

  // sq: isečak iz sredine originala
  const S = 900, m = Math.min(W, H);
  const sq = platno(S, S);
  sq.ctx.drawImage(img, (W - m) / 2, (H - m) / 2, m, m, 0, 0, S, S);
  zig(sq.ctx, S, S, l);

  // og: zamagljena pozadina + kvadrat na sredini
  const og = platno(1200, 630);
  og.ctx.filter = 'blur(28px)';
  og.ctx.drawImage(sq.c, 0, (S - S * 630 / 1200) / 2, S, (S * 630) / 1200, -60, -40, 1320, 710); // srednja traka kvadrata, malo veća od platna da se ne vide ivice zamućenja
  og.ctx.filter = 'none';
  og.ctx.fillStyle = 'rgba(255,255,255,.12)';
  og.ctx.fillRect(0, 0, 1200, 630);
  og.ctx.drawImage(sq.c, 285, 0, 630, 630);

  if ('close' in img) img.close();
  let lgB = await kodiraj(lg.c, 'image/webp', 0.86);
  if (lgB.size > 1_400_000) lgB = await kodiraj(lg.c, 'image/webp', 0.74);
  const [smB, sqB, ogB] = await Promise.all([kodiraj(sm.c, 'image/webp', 0.84), kodiraj(sq.c, 'image/webp', 0.84), kodiraj(og.c, 'image/jpeg', 0.84)]);
  return { lg: lgB, sm: smB, sq: sqB, og: ogB, w, h: hh, pregled: URL.createObjectURL(sqB) };
}

export const uBase64 = (b: Blob) =>
  new Promise<string>((res, rej) => {
    const r = new FileReader();
    r.onload = () => res(String(r.result).replace(/^data:[^,]*,/, ''));
    r.onerror = () => rej(new Error('Slika nije pročitana.'));
    r.readAsDataURL(b);
  });
