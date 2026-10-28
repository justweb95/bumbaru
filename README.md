# Bumbaru: sajt-katalog (prva verzija)

Statički sajt u [Astro](https://astro.build) okviru, po dizajnu „Bumbaru prodavnica”. Nema korpe ni plaćanja na sajtu:
dugme **„Poruči preko Messengera”** složi poruku (naziv, šifra, izbor kupca i link proizvoda), kopira je i otvara
Messenger stranice **facebook.com/bumbaru.rs**. Link proizvoda u poruci Messenger prikazuje sa slikom proizvoda (og:image).

Adrese su iste kao u SEO planu (`/katalog/<kategorija>/`, `/proizvod/<naziv>/`, `/pomoc/`, `/po-meri/`), pa kasniji
prelazak na WordPress + WooCommerce zadržava iste linkove.

## Pokretanje na računaru

```bash
npm install
npm run dev      # http://localhost:4321
npm run build    # gotov sajt u folderu dist/
```

Potreban je Node.js 20 ili noviji.

## Objavljivanje (deploy)

Sajt je čist statički sajt (folder `dist/`), pa radi na bilo kom statičkom hostingu.

**Vercel**
1. vercel.com → Add New → Project → izaberite GitHub repozitorijum `justweb95/bumbaru` → Import.
2. Framework: Astro (prepozna sam). Build: `npm run build`, Output: `dist`. → Deploy.
3. Settings → Domains → dodajte `bumbaru.sajt.net`; Vercel prikaže DNS zapis (obično CNAME na `cname.vercel-dns.com`).

> Napomena: besplatni Vercel Hobby plan je po pravilima Vercel-a samo za nekomercijalnu upotrebu, a sajt koji
> oglašava proizvode za prodaju se računa kao komercijalni. Za besplatan hosting koji to dozvoljava koristite
> Cloudflare Pages (isti koraci), ili Vercel Pro.

**Cloudflare Pages** (besplatno, dozvoljena komercijalna upotreba)
1. dash.cloudflare.com → Workers & Pages → Create → Pages → Connect to Git → `justweb95/bumbaru`.
2. Framework preset: Astro. Build command: `npm run build`. Output directory: `dist`. → Save and Deploy.
3. Custom domains → `bumbaru.sajt.net` → Cloudflare prikaže CNAME (na `<projekat>.pages.dev`).

**Domen bumbaru.sajt.net**: u panelu sajt.net dodajte DNS zapis koji prikaže Vercel ili Cloudflare
(CNAME `bumbaru` → adresa iz koraka 3). Ako se domen promeni, promenite i `site` u `astro.config.mjs`
i `url` u `src/data/site.ts`.

Svaki `git push` na granu `main` posle toga sam objavljuje novu verziju.

## Gde se šta menja

| Šta | Fajl |
| --- | --- |
| Messenger stranica, Facebook, Instagram, adresa sajta, traka na vrhu, sezona | `src/data/site.ts` |
| Prikaz cena (sada isključen) | `src/data/site.ts` → `showPrices: true` |
| Proizvodi: nazivi, tekstovi, šifre, cene, opcije, slike | `src/data/products.ts` |
| Kategorije i njihovi tekstovi i pitanja | `src/data/categories.ts` |
| Početna strana | `src/pages/index.astro` |
| Pomoć, Po meri, Politika privatnosti | `src/pages/*.astro` |
| Boje, slova, dugmad | `src/styles/global.css` |

### Novi proizvod
1. Slike u `public/img/p/<naziv-proizvoda>/`: `1-lg.webp` (cela fotografija, do 1200 px), `1-sq.webp` (kvadrat
   900 × 900 za kartice), po želji `2-lg.webp`, `2-sq.webp`… Prva slika je sa žigom.
2. Slika za deljenje linka `public/img/og/<naziv-proizvoda>.jpg` (1200 × 630).
3. Novi objekat u `src/data/products.ts` (kopirajte postojeći proizvod i promenite polja).

### Slike i žig
- Kartice i prva fotografija proizvoda: verzija sa logom (vidljiva mesta).
- Ostale fotografije u galeriji: verzija bez loga.
- Proizvodi bez fotografije prikazuju „Fotografija uskoro”.
- Modlice za medenjake nisu na sajtu (nema deklaracije za kontakt sa hranom). Zbirna fotografija privezaka
  koristi se samo isečena, bez zaštićenog lika.

## Šta je namerno izostavljeno u prvoj verziji
- Korpa, plaćanje i nalog (porudžbine idu preko Messengera).
- Uslovi kupovine i podaci o prodavcu (PIB, matični broj): dodaju se kad se otvori firma.
- Cene: upisane su za proizvode gde postoje, ali se ne prikazuju dok `showPrices` nije `true`.
