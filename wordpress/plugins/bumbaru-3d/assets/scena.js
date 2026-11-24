/* Bumbaru 3D: Napravite svoj set (tip „scena”, includes/scena.php).
   Kupac dodaje i skida tacnu i figure (do D.max komada, može i više istih). Pregled u galeriji: isečene figure
   na tacni (ili na stolu, bez tacne), u izabranim bojama. Cena i pčelica: isti obračun kao bumbaru_grupa_racun().
   Dugmad strane (Dodaj u korpu, Poruči odmah, Messenger, traka na dnu) šalju ceo set kao jednu stavku. */
(function () {
  'use strict';
  var box = document.querySelector('[data-scena]');
  if (!box) return;
  var D = JSON.parse(box.getAttribute('data-scena'));
  var form = box.closest('form');
  var CFG = window.BUMBARU3D || {};
  var scene = document.querySelector('[data-sc-scene]');
  var $ = function (s, r) { return (r || box).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || box).querySelectorAll(s)); };
  var din = function (n) { return Math.round(n).toLocaleString('sr-RS').replace(/,/g, '.') + ' din'; };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var pop = function (el) { if (el) { el.classList.remove('pop'); void el.offsetWidth; el.classList.add('pop'); } };
  var po = {}; D.stavke.forEach(function (s) { po[s.slug] = s; });
  var pick = {};
  var max = D.max;
  var tiers = D.popusti, tmax = tiers[tiers.length - 1].od;

  // ---------- obračun (isto kao bumbaru_grupa_racun) ----------
  var pctZa = function (t, n) { var p = 0; t.forEach(function (x) { if (n >= x.od) p = Math.max(p, x.procenat); }); return p; };
  function racun() {
    var puna = 0, sir = 0, n = 0, isti = 0, kom = 0;
    Object.keys(pick).forEach(function (k) { if (pick[k] > 0) n++; });
    var p = pctZa(tiers, n);
    Object.keys(pick).forEach(function (k) {
      var q = pick[k]; if (!q) return;
      var c = po[k].cena, ip = pctZa(D.isti, q);
      isti = Math.max(isti, ip); kom += q; puna += c * q;
      for (var i = 0; i < q; i++) sir += c * Math.max(ip, i === 0 ? p : 0) / 100;
    });
    var ukupno = sir > 0 ? Math.round((puna - sir) / 10) * 10 : puna;
    return { n: n, kom: kom, pct: p, isti: isti, puna: puna, popust: puna - ukupno, ukupno: ukupno };
  }
  var ukupnoKom = function () { return Object.keys(pick).reduce(function (a, k) { return a + pick[k]; }, 0); };

  // ---------- boje ----------
  function boje() {
    var o = {};
    $$('[data-sc-color]').forEach(function (fs) {
      var a = fs.getAttribute('data-sc-color').split('|'), r = fs.querySelector('input:checked');
      if (!r) return;
      (o[a[0]] = o[a[0]] || {})[a[1]] = r.value;
    });
    return o;
  }

  // ---------- pregled ----------
  var slike = {}, platna = {};
  function ucitaj(s) {
    if (slike[s.slug]) return slike[s.slug];
    var im = new Image();
    im.decoding = 'async';
    im.src = s.img;
    slike[s.slug] = im;
    im.addEventListener('load', function () { crtaj(); });
    return im;
  }
  var rgb = function (hex) { var h = String(hex || '#ffffff').replace('#', ''); if (h.length === 3) h = h.replace(/./g, '$&$&'); var n = parseInt(h, 16); return [n >> 16 & 255, n >> 8 & 255, n & 255]; };
  // Proizvod sa izborom boja: senka (R) × izabrana boja dela (G = 255 prvi deo, 0 drugi deo).
  function obojeno(s, bj) {
    var im = slike[s.slug];
    if (!im || !im.complete || !im.naturalWidth) return null;
    var c1 = s.boje[0] ? D.boje[bj[s.boje[0].id] || s.boje[0].def] : null;
    var c2 = s.boje[1] ? D.boje[bj[s.boje[1].id] || s.boje[1].def] : c1;
    var a = rgb(c1 && c1.hex), b = rgb(c2 && c2.hex), key = s.slug + a.join() + b.join();
    if (platna[key]) return platna[key];
    var cv = document.createElement('canvas');
    cv.width = im.naturalWidth; cv.height = im.naturalHeight;
    var cx = cv.getContext('2d');
    cx.drawImage(im, 0, 0);
    var d = cx.getImageData(0, 0, cv.width, cv.height), p = d.data;
    for (var i = 0; i < p.length; i += 4) {
      if (!p[i + 3]) continue;
      var l = p[i] / 255, c = p[i + 1] > 127 ? a : b;
      // tamne boje dobiju malo svetla da se vide rebra i senke
      p[i] = Math.min(255, c[0] * l + 26 * l * l); p[i + 1] = Math.min(255, c[1] * l + 26 * l * l); p[i + 2] = Math.min(255, c[2] * l + 26 * l * l);
    }
    cx.putImageData(d, 0, 0);
    platna[key] = cv.toDataURL('image/webp', 0.9);
    return platna[key];
  }
  // Raspored: jelke na krajevima, lutkice u sredini; na tacni do 3, bez tacne do 4 u redu.
  var MESTA = { 1: [50], 2: [37, 63], 3: [27, 50, 73], 4: [17, 39, 61, 83], 5: [13, 31, 50, 69, 87], 6: [10, 26, 42, 58, 74, 90] };
  var bilo = {};
  function crtaj() {
    if (!scene) return;
    var bj = boje(), tacna = null, fig = [], jelke = [], lutke = [];
    D.stavke.forEach(function (s) {
      for (var i = 0; i < (pick[s.slug] || 0); i++) {
        if (s.tacna) tacna = s;
        else (s.h >= 40 ? jelke : lutke).push({ s: s, k: s.slug + '#' + i });
      }
    });
    fig = (jelke.length ? [jelke[0]] : []).concat(lutke, jelke.slice(1)); // prva jelka levo, ostale desno
    var el = [], sad = {};
    if (tacna) el.push({ k: tacna.slug + '#0', s: tacna, x: 50, y: 6, w: tacna.w, z: 1 });
    var xs = MESTA[Math.min(fig.length, 6)] || [];
    var naT = !!tacna;
    fig.forEach(function (f, i) {
      var x = xs[i] !== undefined ? xs[i] : 50;
      // na tacni: figure malo uže (tacna je ispod), noge u tacni
      if (naT) x = 50 + (x - 50) * 0.86;
      // bez tacne, sa više figura u redu: malo manje, da ne stoje jedna na drugoj
      var k = naT ? 1 : fig.length >= 4 ? 0.82 : fig.length === 3 ? 0.92 : 1;
      el.push({ k: f.k, s: f.s, x: x, y: naT ? 15 : 11, h: f.s.h * k, z: 2 + i % 2 });
    });
    var stari = {};
    $$('[data-sc-k]', scene).forEach(function (n) { stari[n.getAttribute('data-sc-k')] = n; });
    el.forEach(function (e) {
      var n = stari[e.k], src = e.s.senka ? obojeno(e.s, bj[e.s.slug] || {}) : e.s.img;
      if (!src) { ucitaj(e.s); return; }
      if (!n) {
        n = document.createElement('img');
        n.className = 'sc-f' + (e.s.tacna ? ' sc-tacna' : '');
        n.alt = e.s.naziv;
        n.setAttribute('data-sc-k', e.k);
        n.style.left = e.x + '%';
        scene.appendChild(n);
        if (bilo.init) pop(n);
      }
      if (n.getAttribute('src') !== src) n.src = src;
      n.style.left = e.x + '%';
      n.style.bottom = e.y + '%';
      n.style.zIndex = e.z;
      if (e.w) { n.style.width = e.w + '%'; n.style.height = 'auto'; } else { n.style.height = e.h + '%'; n.style.width = 'auto'; }
      sad[e.k] = 1;
    });
    Object.keys(stari).forEach(function (k) { if (!sad[k]) stari[k].remove(); });
    var prazno = $('[data-sc-empty]', document);
    if (prazno) prazno.hidden = !!el.length;
    bilo.init = true;
  }

  // ---------- konfigurator ----------
  var lastPct = 0, lastKom = -1;
  function update(posle) {
    var r = racun(), kom = r.kom, pun = kom >= max;
    $$('[data-sc]').forEach(function (t) {
      var s = po[t.getAttribute('data-sc')], n = pick[s.slug] || 0;
      t.classList.toggle('on', n > 0);
      t.classList.toggle('off', !n && pun);
      $('[data-sc-pick]', t).setAttribute('aria-pressed', String(n > 0));
      $('[data-sc-step]', t).hidden = !n;
      $('[data-sc-n]', t).textContent = n;
      var inc = $('[data-sc-inc]', t);
      if (inc) inc.disabled = pun;
    });
    $$('[data-sc-boje]').forEach(function (b) { b.hidden = !pick[b.getAttribute('data-sc-boje')]; });
    $('[data-sc-cnt]').textContent = kom + ' / ' + max + ' kom';
    // pčelica: svaki komad jedan korak
    var e = Math.max(r.pct, r.isti), x = Math.min(Math.max(kom, 1), tmax) / tmax;
    $('[data-mm-fill]').style.width = (x * 100) + '%';
    var bee = $('[data-mm-bee]');
    bee.style.left = (x * 100) + '%';
    if (kom !== lastKom && lastKom >= 0) pop(bee);
    $$('[data-mm-stop]').forEach(function (s) {
      var was = s.classList.contains('on'), on = e >= +s.getAttribute('data-mm-pct');
      s.classList.toggle('on', on);
      if (on && !was && lastKom >= 0) pop(s);
    });
    $('[data-mm-count]').textContent = kom ? r.n + (r.n === 1 ? ' proizvod' : r.n < 5 ? ' različita' : ' različitih') + ' · ' + kom + ' kom' : 'Set je prazan';
    var badge = $('[data-mm-badge]');
    badge.hidden = !e;
    badge.textContent = 'Popust ' + e + '%';
    if (e > lastPct) pop(badge);
    var txt;
    if (!kom) txt = 'Dodajte tacnu ili figure.';
    else if (pun) txt = 'Set je pun (' + max + ' kom).' + (e ? '' : ' Skinite nešto da dodate drugo.');
    else {
      var next = tiers.filter(function (t) { return t.od > r.n && t.procenat > e; })[0];
      txt = next ? (next.od - r.n === 1 ? 'Dodajte još jedan različit proizvod za −' + next.procenat + '%.' : 'Dodajte još ' + (next.od - r.n) + ' različita proizvoda za −' + next.procenat + '%.') : 'Imate najveći popust.';
      if (next && next.od - r.n > max - kom) txt = 'Još ' + (max - kom) + ' kom do punog seta.';
    }
    $('[data-mm-text]').textContent = txt;
    $('[data-sc-total]').innerHTML = kom ? 'Cena seta: <strong>' + din(r.ukupno) + '</strong>' + (r.popust ? '<s>' + din(r.puna) + '</s>' : '') : '';
    cena(r);
    lastPct = e; lastKom = kom;
    $('[data-sc-err]').textContent = '';
    crtaj();
    if (posle) vidi();
  }
  // Cena pored naslova (product.js je menja kad se nešto promeni u obrascu, pa se postavlja posle njega).
  function cena(r) {
    r = r || racun();
    setTimeout(function () {
      var p = document.querySelector('[data-price-el]');
      if (!p) return;
      p.innerHTML = r.kom ? (r.popust ? '<s>' + din(r.puna) + '</s> ' : '') + '<span class="amount">' + din(r.ukupno) + '</span>' : '<span class="amount">od ' + din(Math.min.apply(null, D.stavke.map(function (s) { return s.cena; }))) + '</span>';
    }, 0);
  }
  // Galerija pokaže pregled seta.
  function vidi() {
    var t = document.querySelector('[data-thumb="scena"]');
    if (t && t.getAttribute('aria-pressed') !== 'true') t.click();
  }
  function greska(msg) {
    var e = $('[data-sc-err]');
    e.textContent = msg;
    pop($('.sc-grid'));
  }

  box.addEventListener('click', function (ev) {
    var b = ev.target.closest('button');
    var t = b && b.closest('[data-sc]');
    if (!t) return;
    var slug = t.getAttribute('data-sc'), s = po[slug], n = pick[slug] || 0, kom = ukupnoKom();
    if (b.matches('[data-sc-pick]')) {
      if (n) pick[slug] = 0;
      else if (kom >= max) return greska('Set je pun (' + max + ' kom). Skinite nešto pa dodajte ' + s.naziv + '.');
      else pick[slug] = 1;
    } else if (b.matches('[data-sc-inc]')) {
      if (s.tacna) return;
      if (kom >= max) return greska('Set je pun (' + max + ' kom).');
      pick[slug] = n + 1;
    } else if (b.matches('[data-sc-dec]')) {
      pick[slug] = Math.max(0, n - 1);
    } else return;
    update(true);
  });
  box.addEventListener('change', function (ev) {
    if (ev.target.closest('[data-sc-color]')) { crtaj(); vidi(); cena(); }
  });
  if (form) form.addEventListener('change', function () { cena(); });
  $('[data-sc-reset]').addEventListener('click', function () { pick = {}; D.poc.forEach(function (s) { if (po[s]) pick[s] = (pick[s] || 0) + 1; }); update(true); });
  $('[data-sc-clear]').addEventListener('click', function () { pick = {}; update(true); });

  // ---------- korpa, plaćanje, Messenger ----------
  function linije() {
    var bj = boje(), r = racun(), out = ['Set:'];
    D.stavke.forEach(function (s) {
      var n = pick[s.slug]; if (!n) return;
      var izb = [];
      if (s.vel) izb.push(s.vel);
      s.boje.forEach(function (b) { var v = (bj[s.slug] || {})[b.id] || b.def; izb.push(b.label + ': ' + (D.boje[v] ? D.boje[v].label : v)); });
      out.push('- ' + (n > 1 ? n + ' × ' : '') + s.naziv + (s.sku ? ' (šifra ' + s.sku + ')' : '') + (izb.length ? ': ' + izb.join(', ') : ''));
    });
    if (r.popust) out.push('Popust: −' + din(r.popust) + ' (umesto ' + din(r.puna) + ')');
    out.push('Cena seta: ' + din(r.ukupno));
    var nap = form && form.querySelector('[name="napomena"]');
    if (nap && nap.value.trim()) out.push('Napomena: ' + nap.value.trim());
    return out;
  }
  var radi = false;
  function posalji(mod, btn) {
    if (!ukupnoKom()) { greska('Dodajte bar jedan proizvod u set.'); box.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
    if (mod === 'msg') {
      if (window.bumbaruOrder) window.bumbaruOrder(D.sku, linije(), 1, { t: D.naziv, u: location.href.split('#')[0], i: D.slika });
      return;
    }
    if (radi || !CFG.ajax) return;
    radi = true;
    if (btn) btn.classList.add('busy');
    var nap = form && form.querySelector('[name="napomena"]');
    var body = new URLSearchParams();
    body.set('product_id', String(D.pid));
    body.set('quantity', '1');
    body.set('bu_json', JSON.stringify({ scena: pick, boje: boje(), napomena: nap ? nap.value : '' }));
    fetch(CFG.ajax.replace('%%endpoint%%', 'bumbaru_add'), { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
      .then(function (res) { return res.json(); })
      .then(function (j) {
        if (!j || !j.ok) {
          if (window.bumbaruToast) window.bumbaruToast('<span><strong>Nije dodato u korpu</strong><span>' + esc(j && j.msg || 'Pokušajte ponovo.') + '</span></span>', 'err');
          return;
        }
        if (mod === 'checkout' && j.checkout) { location.href = j.checkout; return; }
        if (mod === 'cart' && j.cart) { location.href = j.cart; return; }
        if (window.bumbaruSetCartFragments) window.bumbaruSetCartFragments(j.fragments, j.count);
        if (window.bumbaruToast) window.bumbaruToast((D.slika ? '<img src="' + esc(D.slika) + '" alt="" width="48" height="48" />' : '') + '<span><strong>Set je u korpi</strong><span>' + esc(j.naziv || D.naziv) + '</span></span><a class="btn btn-sm" href="' + esc(j.cart) + '">Korpa</a><a class="btn btn-sm btn-primary" href="' + esc(j.checkout) + '">Plaćanje</a>');
      })
      .catch(function () {
        if (window.bumbaruToast) window.bumbaruToast('<span><strong>Nije dodato u korpu</strong><span>Proverite internet i pokušajte ponovo.</span></span>', 'err');
      })
      .then(function () { radi = false; if (btn) btn.classList.remove('busy'); });
  }
  // Hvata dugmad pre product.js (faza hvatanja na dokumentu), pa obična korpa ne dobija sam proizvod.
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest && ev.target.closest('[data-add], [data-checkout], [data-messenger-order], [data-sbar-add], [data-sbar-buy]');
    if (!b) return;
    ev.preventDefault(); ev.stopPropagation();
    posalji(b.matches('[data-messenger-order]') ? 'msg' : b.matches('[data-checkout]') ? 'checkout' : b.matches('[data-sbar-buy]') ? 'cart' : 'add', b);
  }, true);
  document.addEventListener('submit', function (ev) {
    if (ev.target !== form) return;
    ev.preventDefault(); ev.stopPropagation();
    posalji('checkout', ev.submitter);
  }, true);

  // početni set
  D.stavke.forEach(ucitaj);
  D.poc.forEach(function (s) { if (po[s]) pick[s] = (pick[s] || 0) + 1; });
  update(false);
})();
