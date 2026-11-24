"""Uskladi WordPress podatke (plugin bumbaru-3d/data/products.json i CSV) sa Astro proizvodima (src/data/proizvodi.json)
za proizvode iz novogodišnje ponude: tekstovi, slike (sa oznakama motiva i AI), cene, konfigurator i povezani.
Fotografije za WP uvoz se kopiraju u dodatak wordpress/plugins/bumbaru-3d-fotografije/import/p/<slug>/ (poseban zip, ne u gitu).
Pokretanje iz korena repoa: python3 scripts/wp-uskladi.py
"""
import csv, io, json, os, re, shutil

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__))) + '/'
WP = ROOT + 'wordpress/plugins/bumbaru-3d/data/products.json'
AS = ROOT + 'src/data/proizvodi.json'
CSVF = ROOT + 'wordpress/Bumbaru_proizvodi_i_cene.csv'
IMP = ROOT + 'wordpress/plugins/bumbaru-3d-fotografije/import/'  # dodatak „Bumbaru 3D – fotografije” (ne ide u git ni u glavni plugin)

# Astro motiv → WP motiv (includes/ornament.php)
MOT = {'zvezdice-i-jelke': 'zvezde', 'masnica': 'masna', 'kapica-deda-mraza': 'kapa', 'velika-pahulja': 'pahulja'}
UKRAS_MOTIVI = ['zvezde', 'masna', 'kapa', 'pahulja']
STARA_TACNA = 'novogodisnja-tacna-za-figure'
SLUGS = ['ukras-za-jelku-sa-imenom', 'rotirajuci-ukras-sa-imenom', 'porodicni-set-ukrasa-sa-imenima', 'rebrasta-ovalna-tacna',
         'jelka-sa-postoljem', 'mini-jelka-za-sto', 'jelkica-pleteni-izgled', 'irvas-lutkica-pleteni-izgled',
         'snesko-lutkica-pleteni-izgled', 'deda-mraz-lutkica-pleteni-izgled', 'spiralna-jelka-sa-zvezdom', 'jelkica-za-punjenje']


def wp_img(im):
    src = im['src'].lstrip('/').replace('img/', '', 1)  # /img/p/x/y → p/x/y
    full = src + '-lg.webp'
    card = src + ('-sq.webp' if src.startswith('p/') else '-lg.webp')
    o = {'full': full, 'card': card, 'w': im['w'], 'h': im['h'], 'alt': im['alt']}
    for k in ('vel',):
        if k in im:
            o[k] = im[k]
    if im.get('motiv') in MOT:
        o['motiv'] = MOT[im['motiv']]
    if im.get('boja'):
        o['boja'] = im['boja']
    if im.get('ai'):
        o['ai'] = True
    # fotografija za WP uvoz (lg, sq, og) iz public/
    if src.startswith('p/'):
        for suf in ('-lg.webp', '-sq.webp', '-og.jpg'):
            a, b = ROOT + 'public/img/' + src + suf, IMP + src + suf
            if os.path.exists(a) and not os.path.exists(b):
                os.makedirs(os.path.dirname(b), exist_ok=True)
                shutil.copyfile(a, b)
    return o


def options_txt(p):
    out = []
    for o in p.get('options') or []:
        vals = []
        for i, v in enumerate(o['values']):
            c = (o.get('cene') or [0] * len(o['values']))[i] if o.get('cene') else 0
            vals.append(f'{v} [{c}]' if c else v)
        out.append(o['label'] + ' = ' + ' | '.join(vals))
    return '\n'.join(out)


def run():
    w = json.load(open(WP, encoding='utf-8'))
    a = {p['slug']: p for p in json.load(open(AS, encoding='utf-8'))['proizvodi']}
    # stara tacna (NG-17 iz prethodne verzije) → rebrasta ovalna tacna DM-04
    for p in w:
        if p['slug'] == STARA_TACNA:
            p['slug'], p['id'] = 'rebrasta-ovalna-tacna', 'DM-04'
    by = {p['slug']: p for p in w}
    for slug in SLUGS:
        s = a[slug]
        p = by.get(slug)
        if not p:
            p = {'id': s['id'], 'slug': slug, 'config': {'type': '', 'options': ''}, 'homeOrder': 0, 'hidden': False, 'focus': ''}
            # novi proizvod posle srodnog
            posle = {'rotirajuci-ukras-sa-imenom': 'ukras-za-jelku-sa-imenom', 'jelka-sa-postoljem': 'spiralna-jelka-sa-zvezdom'}.get(slug)
            i = next((k for k, x in enumerate(w) if x['slug'] == posle), len(w) - 1)
            w.insert(i + 1, p)
            by[slug] = p
        for k in ('id', 'title', 'category', 'short', 'lead', 'about', 'specs', 'details', 'faq', 'seoTitle', 'meta', 'categories', 'related'):
            p[k] = s.get(k, p.get(k))
        p['tags'] = s.get('tags', [])
        p['custom'] = bool(s.get('custom'))
        p['price'] = s.get('price')
        p['priceFrom'] = bool(s.get('priceFrom'))
        p['hidden'] = bool(s.get('skriven'))
        p['images'] = [wp_img(im) for im in s['images']]
        og = next((im.get('og') for im in s['images'] if im.get('og')), '')
        p['og'] = og.lstrip('/').replace('img/', '', 1) if og else p.get('og', '')
        cfg = p['config']
        cfg['options'] = options_txt(s)
        if s.get('colors') and cfg.get('type') != 'jelka':  # jelka (mini jelka) bira boju zvezdice u svom konfiguratoru
            cfg['izbor_boja'] = [{'id': c['id'], 'label': c['label'], 'def': c['def']} for c in s['colors']]
        if s.get('komplet'):
            cfg['komplet'] = {'uz': s['komplet']['uz'], 'popust': s['komplet']['popust']}
        else:
            cfg.pop('komplet', None)
        if slug in ('ukras-za-jelku-sa-imenom', 'porodicni-set-ukrasa-sa-imenima'):
            cfg['motivi_ukras'] = UKRAS_MOTIVI
            cfg['name_color'] = 'bela'
        if slug == 'rotirajuci-ukras-sa-imenom':
            cfg.update({'type': 'ukras', 'name_color': 'bela', 'motivi_ukras': ['okretni'], 'ime_max': 10, 'kolicina': ''})
            p['focus'] = 'rotirajući ukras za jelku sa imenom'
        if slug == 'rebrasta-ovalna-tacna':
            p['focus'] = 'rebrasta tacna'
        if slug == 'jelka-sa-postoljem':
            p['focus'] = 'jelka sa postoljem'
    # na početnoj: rotirajući ukras odmah posle ukrasa sa imenom
    ng1 = by['ukras-za-jelku-sa-imenom']['homeOrder']
    if by['rotirajuci-ukras-sa-imenom']['homeOrder'] != ng1 + 1:
        for p in w:
            if p['homeOrder'] > ng1:
                p['homeOrder'] += 1
        by['rotirajuci-ukras-sa-imenom']['homeOrder'] = ng1 + 1
    # povezani svuda: stara tacna → nova
    for p in w:
        p['related'] = list(dict.fromkeys('rebrasta-ovalna-tacna' if r == STARA_TACNA else r for r in p.get('related', [])))
    ids = [p['id'] for p in w]
    assert len(ids) == len(set(ids)), 'šifra dva puta'
    txt = json.dumps(w, ensure_ascii=False, indent=1) + '\n'
    open(WP, 'w', encoding='utf-8').write(txt)
    return w


def update_csv(w):
    raw = open(CSVF, 'rb').read().decode('utf-8-sig')
    rows = list(csv.reader(io.StringIO(raw)))
    hi = next(i for i, r in enumerate(rows) if r and r[0] == 'Proizvod')
    col = {n: k for k, n in enumerate(rows[hi])}
    by = {p['slug']: p for p in w}
    def red(p):
        r = [''] * len(rows[hi])
        r[col['Proizvod']] = p['title']
        r[col['Šifra']] = p['id']
        return r
    rows = [r for r in rows if not (len(r) > 1 and r[1] in ('NG-17',) and 'tacna' in r[0].lower())]  # stara tacna
    sku = {r[1]: r for r in rows[hi + 1:] if len(r) > 1}
    kat = {'nova-godina': 'Nova godina', 'dom-i-kuhinja': 'Dom i kuhinja'}
    for slug in SLUGS:
        p = by[slug]
        r = sku.get(p['id'])
        if r is None:
            r = red(p)
            posle = {'NG-17': 'NG-01', 'NG-18': 'NG-16', 'DM-04': 'DM-03'}.get(p['id'], 'NG-16')
            i = next((k for k, x in enumerate(rows) if len(x) > 1 and x[1] == posle), len(rows) - 1)
            rows.insert(i + 1, r)
            sku[p['id']] = r
        r[col['Proizvod']] = p['title']
        r[col['Kategorija']] = kat.get(p['category'], r[col['Kategorija']])
        r[col['Cena sada, din']] = str(p['price']) if p.get('price') else ''
        r[col['Akcijska cena, din']] = {'NG-01': '350', 'NG-17': '350'}.get(p['id'], '')
        r[col['Fotografija']] = 'Ima' if p['images'] else 'Nema'
        opc = [p['config'].get('options', '').replace('\n', '; ')] if p['config'].get('options') else []
        opc += ['Izbor boje: ' + c['label'] for c in p['config'].get('izbor_boja', [])]
        if p['id'] in ('NG-01', 'NG-02'):
            opc = ['Ime, motiv (Zvezdice i jelke, Mašnica, Kapica Deda Mraza, Velika pahulja), boja ukrasa i imena'] + (['popust za više komada 2=10, 3=15, 4=20'] if p['id'] == 'NG-01' else ['4 imena 1.120 din ili 5 imena 1.400 din; motiv isti ili različit za svako ime'])
        if p['id'] == 'NG-17':
            opc = ['Ime do 10 slova, boja ukrasa i imena']
        if p['id'] in ('NG-07', 'NG-13'):
            opc.append('Cena na upit')
        r[col['Opcije']] = '; '.join(x for x in opc if x)
        if p.get('focus'):
            r[col['Ključna reč (Google)']] = p['focus']
        r[col['Status']] = 'Skriven' if p.get('hidden') else 'Objavljen'
    prods = [r for r in rows[hi + 1:] if len(r) > 1 and r[1]]
    for r in rows[:hi]:
        if r and r[0] == 'Proizvoda ukupno':
            r[1] = str(len(prods))
        if r and r[0].startswith('Bez cene'):
            r[1] = str(sum(1 for x in prods if not x[col['Cena sada, din']]))
        if r and r[0] == 'Bez fotografije':
            r[1] = str(sum(1 for x in prods if x[col['Fotografija']] != 'Ima'))
    out = io.StringIO()
    csv.writer(out, lineterminator='\r\n').writerows(rows)
    open(CSVF, 'wb').write(('﻿' + out.getvalue()).encode('utf-8'))


w = run()
update_csv(w)
print('ok', len(w))
