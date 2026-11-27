/* Bumbaru 3D: Counter-Strike 2 mapa u ramu (tip „csmapa”, includes/csmapa.php).
   BumbaruCS.svg(stanje, podaci, opcije) crta A4 podlogu (210 × 297 mm) kao SVG: isti crtež na strani proizvoda
   (u crnom ramu, sa slikom 3D mape) i na strani „Štampaj podlogu A4” u administraciji (bez rama i bez mape).
   Na strani proizvoda: izbor mape, modela, jezika, ime igrača i Steam link menjaju pregled odmah; dugmad strane
   (Dodaj u korpu, Poruči odmah, Messenger, traka na dnu) šalju izbor kao jednu stavku (bu_json). */
(function () {
  'use strict';
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var F_NASLOV = "Montserrat, Geologica, 'Arial Black', Arial, sans-serif";
  var F_KURZIV = "Nunito, Geologica, Arial, sans-serif";
  var F_TEKST = "Geologica, Montserrat, Arial, sans-serif";
  var uid = 0;

  // Opšti znak levo gore na MVP podlozi kad nema cs-znak.webp: nišan (krug, četiri crtice, tačka).
  function nisan(x, y, s) {
    var c = s / 2, r = s * 0.34;
    return '<g transform="translate(' + x + ' ' + y + ')" fill="none" stroke="#2E2E2E" stroke-width="' + (s * 0.07) + '" stroke-linecap="round">'
      + '<circle cx="' + c + '" cy="' + c + '" r="' + r + '"/>'
      + '<path d="M' + c + ' ' + (c - r - s * 0.12) + 'V' + (c - r * 0.45) + 'M' + c + ' ' + (c + r * 0.45) + 'V' + (c + r + s * 0.12) + 'M' + (c - r - s * 0.12) + ' ' + c + 'H' + (c - r * 0.45) + 'M' + (c + r * 0.45) + ' ' + c + 'H' + (c + r + s * 0.12) + '"/>'
      + '<circle cx="' + c + '" cy="' + c + '" r="' + (s * 0.05) + '" fill="#E59A2F" stroke="none"/></g>';
  }
  function slika(href, x, y, w, h, extra) {
    return href ? '<image href="' + esc(href) + '" x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" preserveAspectRatio="xMidYMid meet"' + (extra || '') + '/>' : '';
  }
  // Prostor za 3D mapu u pravoj veličini (mm na A4, najduža strana 160): slika mape (pregled),
  // isprekidan obris (štampa, po želji) ili ništa.
  function mapa(m, o, cx, cy) {
    var mm = m.mm || [160, 160], w = mm[0], h = mm[1], x = cx - w / 2, y = cy - h / 2;
    if (o.stampa) {
      return o.obris ? '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" rx="2" fill="none" stroke="#B9B9B9" stroke-width=".35" stroke-dasharray="2 1.6"/><text x="' + cx + '" y="' + (cy + 1.5) + '" text-anchor="middle" font-family="' + F_TEKST + '" font-size="4" fill="#B0B0B0">3D mapa ' + w + ' × ' + h + ' mm</text>' : '';
    }
    if (m.mapa) return '<g filter="url(#cs-sh' + uid + ')">' + slika(m.mapa, x, y, w, h) + '</g>';
    // slika 3D mape još ne postoji: bež pločica u veličini mape
    return '<g filter="url(#cs-sh' + uid + ')"><rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" rx="3" fill="#E6CFA6" stroke="#C9AE7F" stroke-width=".6"/>'
      + '<text x="' + cx + '" y="' + (cy + 2) + '" text-anchor="middle" font-family="' + F_TEKST + '" font-size="5" font-weight="700" fill="#8A6F45">3D mapa ' + esc(m.naziv) + '</text></g>';
  }
  // Avatar: učitana slika, siva pločica sa ikonicom ili ništa.
  function avatar(o, x, y, s) {
    if (o.avatar === 'none') return '';
    var clip = 'cs-av' + uid;
    if (o.avatar && o.avatar !== 'placeholder') {
      return '<clipPath id="' + clip + '"><rect x="' + x + '" y="' + y + '" width="' + s + '" height="' + s + '" rx="1.2"/></clipPath>'
        + '<image href="' + esc(o.avatar) + '" x="' + x + '" y="' + y + '" width="' + s + '" height="' + s + '" preserveAspectRatio="xMidYMid slice" clip-path="url(#' + clip + ')"/>';
    }
    var c = x + s / 2;
    return '<rect x="' + x + '" y="' + y + '" width="' + s + '" height="' + s + '" rx="1.2" fill="#D3D3D3"/>'
      + '<circle cx="' + c + '" cy="' + (y + s * 0.38) + '" r="' + (s * 0.18) + '" fill="#9E9E9E"/>'
      + '<path d="M' + (x + s * 0.18) + ' ' + (y + s * 0.88) + 'c0-' + (s * 0.22) + ' ' + (s * 0.14) + '-' + (s * 0.3) + ' ' + (s * 0.32) + '-' + (s * 0.3) + 's' + (s * 0.32) + ' ' + (s * 0.08) + ' ' + (s * 0.32) + ' ' + (s * 0.3) + 'z" fill="#9E9E9E"/>';
  }

  function mvp(m, T, st, o, D) {
    var ime = st.ime || (o.stampa ? '' : 'nick');
    var s = '';
    s += '<text x="105" y="31" text-anchor="middle" font-family="' + F_NASLOV + '" font-weight="800" font-size="21" letter-spacing=".6" fill="#2E2E2E" data-fit="176">' + esc(m.naziv.toUpperCase()) + '</text>';
    s += '<line x1="20" y1="36" x2="190" y2="36" stroke="#E59A2F" stroke-width=".8"/>';
    s += '<text x="105" y="47.5" text-anchor="middle" font-family="' + F_NASLOV + '" font-weight="800" font-size="9.6" letter-spacing=".4" fill="#1E1E1E">COUNTER-STRIKE <tspan fill="#E59A2F">2</tspan></text>';
    // znak levo i logo mape desno: isti kvadrat 26 mm, iznad mape (mapa počinje na y 77)
    s += D.znak ? slika(D.znak, 16, 50, 26, 26) : nisan(16, 50, 26);
    s += slika(m.logo, 168, 50, 26, 26);
    s += slika(m.siva, 20, 72, 170, 170, ' opacity=".9"');
    s += mapa(m, o, 105, 157);
    s += '<text x="105" y="252.5" text-anchor="middle" font-family="' + F_KURZIV + '" font-style="italic" font-weight="900" font-size="13.4" letter-spacing=".2" fill="#F0B416" data-fit="180">' + esc(T.mvp) + '</text>';
    var av = o.avatar !== 'none';
    s += '<g data-ime-g>' + avatar(o, 0, 257.4, 11) + '<text x="' + (av ? 13.5 : 0) + '" y="266.4" font-family="' + F_NASLOV + '" font-weight="800" font-size="10" fill="#2A2A2A" data-ime-t>' + esc(ime) + '</text></g>';
    if (st.ime) s += '<text x="105" y="285" text-anchor="middle" font-family="' + F_KURZIV + '" font-style="italic" font-weight="900" font-size="3.6" fill="#1E1E1E" data-fit="176">' + esc(T.posveta.replace('%s', st.ime)) + '</text>';
    return s;
  }

  function info(m, T, st, o, j) {
    var s = '', siva = '#8A8A8A', tam = '#202020';
    // tanak okvir sa uglovima
    s += '<rect x="10" y="10" width="190" height="277" fill="none" stroke="#2A2A2A" stroke-width=".35"/>';
    [[10, 10, 1, 1], [200, 10, -1, 1], [10, 287, 1, -1], [200, 287, -1, -1]].forEach(function (k) {
      s += '<path d="M' + (k[0] + 3 * k[2]) + ' ' + (k[1] + 9 * k[3]) + 'V' + (k[1] + 3 * k[3]) + 'H' + (k[0] + 9 * k[2]) + '" fill="none" stroke="#E59A2F" stroke-width=".7"/>';
    });
    s += '<text x="18" y="21" font-family="' + F_NASLOV + '" font-weight="800" font-size="3.6" letter-spacing="1.1" fill="' + tam + '">COUNTER-STRIKE <tspan fill="#E59A2F">2</tspan></text>';
    s += '<text x="192" y="21" text-anchor="end" font-family="ui-monospace, Menlo, Consolas, monospace" font-size="3.6" fill="' + siva + '">' + esc(m.kod) + ' · ' + esc(m.godina) + '</text>';
    s += '<line x1="18" y1="24.5" x2="192" y2="24.5" stroke="#D4D4D4" stroke-width=".3"/>';
    s += slika(m.logo, 95, 28.5, 20, 20);
    s += '<text x="105" y="64.5" text-anchor="middle" font-family="' + F_NASLOV + '" font-weight="800" font-size="20" letter-spacing="1" fill="' + tam + '" data-fit="170">' + esc(m.naziv.toUpperCase()) + '</text>';
    s += '<text x="105" y="71.5" text-anchor="middle" font-family="' + F_TEKST + '" font-weight="600" font-size="4.3" letter-spacing="1.6" fill="#6A6A6A">' + esc(m.pod[j].toUpperCase()) + '</text>';
    s += '<rect x="99" y="74.5" width="12" height=".8" fill="#E59A2F"/>';
    // kratki tekstovi levo (delovi mape) i desno (moto)
    m.levo[j].forEach(function (t, i) { s += '<text x="18" y="' + (34 + i * 5) + '" font-family="' + F_TEKST + '" font-weight="600" font-size="3.5" letter-spacing=".9" fill="' + siva + '">' + esc(t.toUpperCase()) + '</text>'; });
    s += '<rect x="18" y="46.5" width="8" height=".5" fill="' + siva + '"/>';
    T.moto.forEach(function (t, i) { s += '<text x="192" y="' + (34 + i * 5) + '" text-anchor="end" font-family="' + F_NASLOV + '" font-weight="800" font-size="3.6" letter-spacing=".9" fill="' + tam + '">' + esc(t.toUpperCase()) + '</text>'; });
    s += slika(m.siva, 30, 84, 150, 150, ' opacity=".85"');
    s += mapa(m, o, 105, 159.5);
    // traka sa podacima
    var cel = [
      [T.izdata, m.izdata[j], m.verzija],
      [T.autor, m.autor, T.cs2],
      [T.tip, T.tipv, ''],
      [T.igraci, T.igraciv, ''],
      [T.lok, m.lok[j], '']
    ], w = 174 / 5;
    s += '<rect x="18" y="243.5" width="174" height="23" rx="2.5" fill="#F6F4F0" stroke="#DEDAD2" stroke-width=".3"/>';
    cel.forEach(function (c, i) {
      var cx = 18 + w * i + w / 2;
      if (i) s += '<line x1="' + (18 + w * i) + '" y1="246.5" x2="' + (18 + w * i) + '" y2="263.5" stroke="#DEDAD2" stroke-width=".3"/>';
      s += '<text x="' + cx + '" y="248.4" text-anchor="middle" font-family="ui-monospace, Menlo, Consolas, monospace" font-size="2.4" fill="#E59A2F">0' + (i + 1) + '</text>';
      s += '<text x="' + cx + '" y="252.6" text-anchor="middle" font-family="' + F_TEKST + '" font-weight="600" font-size="2.6" letter-spacing=".6" fill="' + siva + '">' + esc(c[0].toUpperCase()) + '</text>';
      s += '<text x="' + cx + '" y="258.2" text-anchor="middle" font-family="' + F_NASLOV + '" font-weight="800" font-size="3.7" fill="' + tam + '" data-fit="' + (w - 3) + '">' + esc(c[1]) + '</text>';
      if (c[2]) s += '<text x="' + cx + '" y="262.4" text-anchor="middle" font-family="' + F_TEKST + '" font-size="2.5" fill="' + siva + '" data-fit="' + (w - 3) + '">' + esc(c[2]) + '</text>';
    });
    var dno = st.ime ? T.za.replace('%s', st.ime) : m.fakt[j];
    s += '<text x="105" y="278.5" text-anchor="middle" font-family="' + (st.ime ? F_KURZIV : F_TEKST) + '"' + (st.ime ? ' font-style="italic" font-weight="900" font-size="5"' : ' font-weight="600" font-size="3.4" letter-spacing=".8"') + ' fill="' + tam + '" data-fit="160">' + esc(st.ime ? dno : dno.toUpperCase()) + '</text>';
    return s;
  }

  /** Ceo SVG. st = { mapa, model, jezik, ime }, D = podaci iz PHP-a, o = { ram, stampa, obris, avatar }. */
  function svg(st, D, o) {
    o = o || {};
    uid++;
    var m = D.mape[st.mapa] || D.mape[Object.keys(D.mape)[0]];
    var j = st.jezik === 'sr' ? 'sr' : 'en', T = D.tekst[j];
    var vb = o.ram ? '-20 -18 250 333' : '0 0 210 297';
    var s = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' + vb + '"' + (o.stampa ? ' width="210mm" height="297mm"' : '') + ' role="img" aria-label="' + esc('Podloga ' + m.naziv + ', ' + (st.model === 'info' ? 'General Info' : 'MVP')) + '">';
    s += '<defs><filter id="cs-sh' + uid + '" x="-10%" y="-10%" width="120%" height="125%"><feDropShadow dx="1.2" dy="2.2" stdDeviation="1.6" flood-color="#3b2a12" flood-opacity=".35"/></filter>'
      + (o.ram ? '<filter id="cs-ram' + uid + '" x="-10%" y="-10%" width="120%" height="120%"><feDropShadow dx="0" dy="5" stdDeviation="5" flood-color="#000" flood-opacity=".35"/></filter><linearGradient id="cs-st' + uid + '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".16"/><stop offset=".45" stop-color="#fff" stop-opacity="0"/></linearGradient>' : '')
      + '</defs>';
    if (o.ram) s += '<rect x="-13" y="-13" width="236" height="323" rx="1.5" fill="#151515" filter="url(#cs-ram' + uid + ')"/><rect x="-9.5" y="-9.5" width="229" height="316" fill="none" stroke="#2C2C2C" stroke-width="1"/><rect x="-1.2" y="-1.2" width="212.4" height="299.4" fill="#0B0B0B"/>';
    s += '<rect width="210" height="297" fill="#FFFFFF"/>';
    s += st.model === 'info' ? info(m, T, st, o, j) : mvp(m, T, st, o, D);
    if (o.ram) s += '<path d="M0 0H140L0 120Z" fill="url(#cs-st' + uid + ')"/>';
    return s + '</svg>';
  }

  /** Posle ubacivanja u stranu: dugi tekstovi se smanje da stanu, ime i avatar idu na sredinu. */
  function poravnaj(root) {
    Array.prototype.forEach.call(root.querySelectorAll('[data-fit]'), function (t) {
      var max = +t.getAttribute('data-fit'), fs = +t.getAttribute('font-size');
      t.removeAttribute('textLength');
      var w = t.getComputedTextLength ? t.getComputedTextLength() : 0;
      if (w > max && w) t.setAttribute('font-size', (fs * max / w).toFixed(2));
    });
    var g = root.querySelector('[data-ime-g]');
    if (!g) return;
    var t = g.querySelector('[data-ime-t]'), fs = 10;
    t.setAttribute('font-size', fs);
    var w = t.getComputedTextLength(), av = g.querySelector('rect,image') ? 13.5 : 0;
    if (w + av > 150) { fs = fs * (150 - av) / w; t.setAttribute('font-size', fs.toFixed(2)); w = t.getComputedTextLength(); }
    g.setAttribute('transform', 'translate(' + (105 - (w + av) / 2).toFixed(2) + ' 0)');
  }

  window.BumbaruCS = { svg: svg, poravnaj: poravnaj };

  // ---------- strana proizvoda ----------
  var box = document.querySelector('[data-csmapa]');
  if (!box) return;
  var D = JSON.parse(box.getAttribute('data-csmapa'));
  var form = box.closest('form');
  var CFG = window.BUMBARU3D || {};
  var stage = document.querySelector('[data-cs-stage]');
  var $ = function (s, r) { return (r || box).querySelector(s); };
  var val = function (n) { var r = box.querySelector('input[name="' + n + '"]:checked'); return r ? r.value : ''; };
  var ime = $('[data-cs-ime]'), steam = $('[data-cs-steam]');
  var cnt = $('[data-cs-cnt]');
  var imeHint = $('[data-cs-err-ime]').textContent, steamHint = $('[data-cs-err-steam]').textContent;
  var DOZV = /[^\p{L}\p{N}\p{M} ._\-!?*#@$&+=~^|\[\](){}':;,\/]/gu;
  var steamOk = function (v) { return !v || /^(?:https?:\/\/)?(?:www\.)?steamcommunity\.com\/(id|profiles)\/[A-Za-z0-9_\-]{2,64}\/?(?:[?#].*)?$/i.test(v.trim()); };
  var stanje = function () { return { mapa: val('cs_mapa'), model: val('cs_model') || 'mvp', jezik: val('cs_jezik') || 'en', ime: ime.value.trim() }; };

  var raf = 0;
  function crtaj() {
    raf = 0;
    if (!stage) return;
    stage.innerHTML = svg(stanje(), D, { ram: true, avatar: 'placeholder' });
    poravnaj(stage);
  }
  var zakazi = function () { if (!raf) raf = requestAnimationFrame(crtaj); };
  function vidi() { var t = document.querySelector('[data-thumb="live"]'); if (t && t.getAttribute('aria-pressed') !== 'true') t.click(); }
  function opis() {
    var mvpM = val('cs_model') !== 'info';
    $('[data-cs-model-hint]').textContent = mvpM ? 'MVP: „Most Valuable Player”, slika i ime igrača.' : 'General Info: podaci o mapi (izdata, autor, tip, igrači, lokacija). Ime nije obavezno, štampa se kao posveta u dnu.';
    $('[data-cs-ime-l]').textContent = mvpM ? 'Ime igrača (nick)' : 'Ime za posvetu (nije obavezno)';
    $('[data-cs-steam-f]').hidden = !mvpM;
  }
  box.addEventListener('change', function () { opis(); zakazi(); vidi(); });
  ime.addEventListener('input', function () {
    var c = ime.value.replace(DOZV, '');
    if (c !== ime.value) { var p = ime.selectionStart - (ime.value.length - c.length); ime.value = c; try { ime.setSelectionRange(p, p); } catch (e) { /* */ } }
    cnt.textContent = ime.value.length + '/16';
    $('[data-cs-err-ime]').textContent = imeHint;
    ime.classList.remove('is-err');
    zakazi();
  });
  ime.addEventListener('focus', vidi);
  steam.addEventListener('input', function () { $('[data-cs-err-steam]').textContent = steamHint; steam.classList.remove('is-err'); });
  steam.addEventListener('blur', function () { if (!steamOk(steam.value)) { $('[data-cs-err-steam]').textContent = 'Link nije ispravan. Primer: https://steamcommunity.com/id/vasnick'; steam.classList.add('is-err'); } });
  if (document.fonts && document.fonts.load) {
    Promise.all([document.fonts.load('800 10px Montserrat'), document.fonts.load('italic 900 10px Nunito')]).then(zakazi, zakazi);
  }

  function greska(el, msg, polje) {
    $(polje).textContent = msg;
    el.classList.add('is-err');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    try { el.focus({ preventScroll: true }); } catch (e) { el.focus(); }
  }
  function proveri() {
    var st = stanje();
    if (st.model === 'mvp' && !st.ime) { greska(ime, 'Upišite ime igrača (nick) koje štampamo na podlozi.', '[data-cs-err-ime]'); return null; }
    if (st.model === 'mvp' && !steamOk(steam.value)) { greska(steam, 'Link nije ispravan. Primer: https://steamcommunity.com/id/vasnick (ili ostavite prazno).', '[data-cs-err-steam]'); return null; }
    st.steam = st.model === 'mvp' ? steam.value.trim() : '';
    return st;
  }
  function linije(st) {
    var m = D.mape[st.mapa];
    var out = ['Mapa: ' + m.naziv, 'Model podloge: ' + (st.model === 'info' ? 'General Info' : 'MVP'), 'Jezik na podlozi: ' + st.jezik.toUpperCase()];
    if (st.ime) out.push((st.model === 'info' ? 'Posveta za: ' : 'Ime igrača: ') + st.ime);
    if (st.model === 'mvp') out.push(st.steam ? 'Steam profil: ' + st.steam : 'Slika igrača: šaljem posle porudžbine (ili bez slike)');
    var nap = form && form.querySelector('[name="napomena"]');
    if (nap && nap.value.trim()) out.push('Napomena: ' + nap.value.trim());
    return out;
  }
  var radi = false;
  function posalji(mod, btn) {
    var st = proveri();
    if (!st) return;
    var bu = form ? JSON.parse(form.getAttribute('data-bu') || '{}') : {};
    var q = form && form.querySelector('[name="quantity"]');
    var kom = Math.max(1, Math.min(50, parseInt(q && q.value, 10) || 1));
    if (mod === 'msg') {
      if (window.bumbaruOrder) window.bumbaruOrder(bu.id, linije(st), kom, { t: bu.naslov, u: location.href.split('#')[0], i: bu.slika });
      return;
    }
    if (radi || !CFG.ajax) return;
    radi = true;
    if (btn) btn.classList.add('busy');
    var nap = form && form.querySelector('[name="napomena"]');
    var body = new URLSearchParams();
    body.set('product_id', String(bu.pid));
    body.set('quantity', String(kom));
    body.set('bu_json', JSON.stringify({ mapa: st.mapa, model: st.model, jezik: st.jezik, ime: st.ime, steam: st.steam, napomena: nap ? nap.value : '' }));
    fetch(CFG.ajax.replace('%%endpoint%%', 'bumbaru_add'), { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || !j.ok) {
          if (window.bumbaruToast) window.bumbaruToast('<span><strong>Nije dodato u korpu</strong><span>' + esc(j && j.msg || 'Pokušajte ponovo.') + '</span></span>', 'err');
          return;
        }
        if (mod === 'checkout' && j.checkout) { location.href = j.checkout; return; }
        if (mod === 'cart' && j.cart) { location.href = j.cart; return; }
        if (window.bumbaruSetCartFragments) window.bumbaruSetCartFragments(j.fragments, j.count);
        if (window.bumbaruToast) window.bumbaruToast((bu.slika ? '<img src="' + esc(bu.slika) + '" alt="" width="48" height="48" />' : '') + '<span><strong>Dodato u korpu</strong><span>' + esc(j.naziv || bu.naslov) + ' · ' + esc(D.mape[st.mapa].naziv) + '</span></span><a class="btn btn-sm" href="' + esc(j.cart) + '">Korpa</a><a class="btn btn-sm btn-primary" href="' + esc(j.checkout) + '">Plaćanje</a>');
      })
      .catch(function () {
        if (window.bumbaruToast) window.bumbaruToast('<span><strong>Nije dodato u korpu</strong><span>Proverite internet i pokušajte ponovo.</span></span>', 'err');
      })
      .then(function () { radi = false; if (btn) btn.classList.remove('busy'); });
  }
  // Hvata dugmad pre product.js (faza hvatanja), pa u korpu ide izbor sa ove strane.
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest && ev.target.closest('[data-add], [data-checkout], [data-messenger-order], [data-sbar-add], [data-sbar-buy]');
    if (!b || (b.matches('[data-add]') && b.tagName !== 'BUTTON')) return;
    ev.preventDefault(); ev.stopPropagation();
    posalji(b.matches('[data-messenger-order]') ? 'msg' : b.matches('[data-checkout]') ? 'checkout' : b.matches('[data-sbar-buy]') ? 'cart' : 'add', b);
  }, true);
  document.addEventListener('submit', function (ev) {
    if (ev.target !== form) return;
    ev.preventDefault(); ev.stopPropagation();
    posalji('checkout', ev.submitter);
  }, true);

  opis();
  crtaj();
})();
