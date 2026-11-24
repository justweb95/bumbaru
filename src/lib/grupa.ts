// Popust za različite proizvode (isto kao u WordPress pluginu, includes/grupa.php):
// lutkice u pletenom izgledu, spiralna jelka, jelka sa postoljem i rebrasta tacna.
// 2 različita −5%, 3 različita −10%, 4 i više −15%. Svaki proizvod se broji jednom (različite veličine = isti
// proizvod), popust važi za po jedan komad svakog različitog proizvoda (najskuplji izbor), dodatni komadi idu
// po punoj ceni. Više komada ISTOG proizvoda: 2 kom −5%, 4 i više −10% na sve komade tog proizvoda.
// Popusti se ne sabiraju (svaki komad dobija veći). Iznos se zaokružuje na 10 din. Akcija ne važi za ove proizvode.

export const GRUPA = [
  'irvas-lutkica-pleteni-izgled',
  'snesko-lutkica-pleteni-izgled',
  'deda-mraz-lutkica-pleteni-izgled',
  'spiralna-jelka-sa-zvezdom',
  'jelka-sa-postoljem',
  'rebrasta-ovalna-tacna',
];
export const GRUPA_POPUSTI = [{ od: 2, procenat: 5 }, { od: 3, procenat: 10 }, { od: 4, procenat: 15 }];
export const ISTI_POPUSTI = [{ od: 2, procenat: 5 }, { od: 4, procenat: 10 }];
export const istiPct = (n: number) => ISTI_POPUSTI.filter((t) => n >= t.od).reduce((a, t) => Math.max(a, t.procenat), 0);

export const uGrupi = (slug: string) => GRUPA.includes(slug);
export const grupaPct = (n: number) => GRUPA_POPUSTI.filter((t) => n >= t.od).reduce((a, t) => Math.max(a, t.procenat), 0);
export const razlicitih = (n: number) => {
  const a = n % 10, b = n % 100;
  return a >= 2 && a <= 4 && (b < 12 || b > 14) ? 'različita' : a === 1 && b !== 11 ? 'različit' : 'različitih';
};

export interface GrupaStavka { slug: string; cena: number; kom: number }
export function grupaRacun(stavke: GrupaStavka[]) {
  const kom = new Map<string, number[]>();
  let puna = 0;
  for (const s of stavke) {
    if (!uGrupi(s.slug)) continue;
    puna += s.cena * s.kom;
    for (let i = 0; i < s.kom; i++) kom.set(s.slug, [...(kom.get(s.slug) || []), s.cena]);
  }
  const n = kom.size, pct = grupaPct(n);
  let sir = 0, isti = 0;
  for (const cene of kom.values()) {
    cene.sort((a, b) => b - a);
    const ip = istiPct(cene.length);
    isti = Math.max(isti, ip);
    cene.forEach((c, i) => { sir += (c * Math.max(ip, i === 0 ? pct : 0)) / 100; });
  }
  const ukupno = sir > 0 ? Math.round((puna - sir) / 10) * 10 : puna;
  return { n, pct, isti, puna, popust: puna - ukupno, ukupno };
}
