// Pregled ukrasa sa imenom: boje, motivi i raspored imena preko obruča.
// Isti kod se koristi pri izradi strane i u pregledu uživo u pregledaču.

export const BOJE = [
  { id: 'bela', label: 'Bela', face: '#F3EEE5', edge: '#B9AE9C' },
  { id: 'crvena', label: 'Crvena', face: '#B3312A', edge: '#7E1F1A' },
  { id: 'zelena', label: 'Zelena', face: '#2F5B3B', edge: '#1C3A25' },
  { id: 'zlatna', label: 'Zlatna', face: '#D6A23E', edge: '#A0741F' },
];

export const MOTIVI = [
  { id: 'zvezde', label: 'Zvezde i jelke', hy: 64 },
  { id: 'planine', label: 'Planine i šuma', hy: 64 },
  { id: 'masna', label: 'Mašna i cvet', hy: 71 },
  { id: 'grancica', label: 'Grančica', hy: 64 },
];

// Širine slova fonta Pacifico (1/1000 em), da ime uvek ispuni obruč.
const CH = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyzČĆŠŽĐčćšžđАБВГДЂЕЖЗИЈКЛЉМНЊОПРСТЋУФХЦЧЏШабвгдђежзијклљмнњопрстћуфхцчџш -'.";
const W = [786,827,686,836,686,712,811,873,495,743,937,673,1232,884,816,805,816,880,693,697,890,763,1217,712,851,721,470,473,368,515,393,371,479,502,246,254,525,326,744,496,451,481,500,455,427,393,462,449,700,529,479,445,686,686,693,721,821,368,368,427,445,470,786,917,827,635,964,974,686,1234,725,890,743,937,861,1158,1133,873,1182,816,892,805,686,697,979,824,977,712,890,773,890,1272,470,473,473,380,479,514,393,717,435,462,254,465,549,794,734,475,751,451,496,481,368,744,502,479,721,529,501,555,600,693,265,403,290,257];
const ADV: Record<string, number> = {};
for (let i = 0; i < CH.length; i++) ADV[CH[i]] = W[i];

export function fitName(raw: string, fallback = 'Mila') {
  const name = String(raw || '').trim().slice(0, 12) || fallback;
  let sum = 0;
  for (const ch of name) sum += ADV[ch] !== undefined ? ADV[ch] : 520;
  const em = sum / 1000 + 0.1;
  const CY = 182, RI = 94, yc = CY + 6;
  const half = Math.sqrt(RI * RI - 36);
  const fs = Math.max(22, Math.min(60, (2 * half + 6) / em));
  const y = yc + 0.23 * fs;
  const w = em * fs;
  let rails = '';
  if (half - w / 2 > 3) {
    const yr = y - 0.05 * fs;
    const hr = Math.sqrt(RI * RI - (yr - CY) * (yr - CY));
    const r = (v: number) => v.toFixed(1);
    rails = 'M' + r(120 - hr - 4) + ' ' + r(yr) + 'L' + r(120 - w / 2 + 0.18 * fs) + ' ' + r(yr) +
      'M' + r(120 + w / 2 - 0.22 * fs) + ' ' + r(yr) + 'L' + r(120 + hr + 4) + ' ' + r(yr);
  }
  return { name, fs: fs.toFixed(1), y: y.toFixed(1), rails, railW: Math.max(4.2, 0.085 * fs).toFixed(1) };
}

export const boja = (id: string) => BOJE.find((b) => b.id === id) || BOJE[0];
export const motiv = (id: string) => MOTIVI.find((m) => m.id === id) || MOTIVI[0];
