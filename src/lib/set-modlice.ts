// Slavski set: kupac bira modlice za set (npr. 8), a medaljon sveca je deo proizvoda.
// Slike su isečci sa pravih fotografija (bez pozadine), za pregled seta „u krug” na strani proizvoda.
// Novi motiv: isečak u public/img/set/<id>.webp (PNG/WebP sa providnom pozadinom) i red u listi ispod.

export interface SetMotiv { id: string; naziv: string; slika: string; w: number; h: number }

export const SET_POZADINA = '/img/set/pozadina.webp';

export const SET_MODLICE: SetMotiv[] = [
  { id: 'krst', naziv: 'Krst', slika: '/img/set/krst.webp', w: 346, h: 380 },
  { id: 'golub-u-letu', naziv: 'Golub u letu', slika: '/img/set/golub-u-letu.webp', w: 380, h: 295 },
  { id: 'golub-sa-grancicom', naziv: 'Golub sa grančicom', slika: '/img/set/golub-sa-grancicom.webp', w: 380, h: 345 },
  { id: 'srecna-slava', naziv: 'Срећна слава', slika: '/img/set/srecna-slava.webp', w: 380, h: 288 },
  { id: 'pecat-krst', naziv: 'Pečat sa krstom', slika: '/img/set/pecat-krst.webp', w: 380, h: 358 },
  { id: 'pecat-okrugli', naziv: 'Pečat okrugli', slika: '/img/set/pecat-okrugli.webp', w: 380, h: 363 },
  { id: 'knjiga', naziv: 'Knjiga', slika: '/img/set/knjiga.webp', w: 368, h: 314 },
  { id: 'klas', naziv: 'Klas', slika: '/img/set/klas.webp', w: 270, h: 380 },
  { id: 'zito', naziv: 'Žito', slika: '/img/set/zito.webp', w: 360, h: 380 },
  { id: 'cvet', naziv: 'Cvet', slika: '/img/set/cvet.webp', w: 380, h: 355 },
  { id: 'grozdje', naziv: 'Grožđe sa listom', slika: '/img/set/grozdje.webp', w: 316, h: 380 },
  { id: 'bure', naziv: 'Bure', slika: '/img/set/bure.webp', w: 347, h: 380 },
  { id: 'pletenica', naziv: 'Pletenica', slika: '/img/set/pletenica.webp', w: 380, h: 123 },
];

export const SET_MEDALJONI: { id: string; naziv: string; slika: string }[] = [
  { id: 'sv-nikola', naziv: 'Sv. Nikola', slika: '/img/set/med-nikola.webp' },
  { id: 'sv-georgije', naziv: 'Sv. Georgije', slika: '/img/set/med-georgije.webp' },
  { id: 'arh-mihailo', naziv: 'Arh. Mihailo', slika: '/img/set/med-mihailo.webp' },
  { id: 'sv-jovan', naziv: 'Sv. Jovan', slika: '/img/set/med-jovan.webp' },
  { id: 'sv-dimitrije', naziv: 'Sv. Dimitrije', slika: '/img/set/med-dimitrije.webp' },
  { id: 'sv-sava', naziv: 'Sv. Sava', slika: '/img/set/med-sava.webp' },
];

// Početni izbor (kupac ga menja): najtraženiji motivi
export const SET_POCETNI = ['krst', 'golub-u-letu', 'pecat-krst', 'pecat-okrugli', 'srecna-slava', 'knjiga', 'klas', 'grozdje'];

export const setMotiv = (id: string) => SET_MODLICE.find((m) => m.id === id);
export const setMedaljon = (id: string) => SET_MEDALJONI.find((m) => m.id === id);
