/* Bumbaru 3D: popust za različite proizvode (lutkice, spiralna i mini jelka, tacna).
   Pčelica na traci pokazuje broj različitih proizvoda: ono što je već u korpi + ovaj proizvod.
   Popust je na po jedan komad svakog različitog proizvoda (najskuplji izbor), dodatni komadi idu po punoj ceni.
   Isti obračun radi server (grupa.php), ovde je samo prikaz i Messenger poruka. */
(function () {
  'use strict';
  var box = document.querySelector('[data-grupa]');
  if (!box) return;
  var D = JSON.parse(box.getAttribute('data-grupa'));
  var form = box.closest('form') || document;
  var qtyIn = form.querySelector('input[name="quantity"]');
  var tiers = D.tiers, max = tiers[tiers.length - 1].od, korpa = D.korpa || [], last = -1, lastPct = 0; // last: poslednje mesto pčelice
  var $ = function (s) { return box.querySelector(s); };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var pop = function (el) { if (el) { el.classList.remove('pop'); void el.offsetWidth; el.classList.add('pop'); } };
  var din = function (n) { return Math.round(n).toLocaleString('sr-RS').replace(/,/g, '.') + ' din'; };
  var pctZa = function (n) { var p = 0; tiers.forEach(function (t) { if (n >= t.od) p = Math.max(p, t.procenat); }); return p; };
  var rec = function (n) { var a = n % 10, b = n % 100; return a >= 2 && a <= 4 && (b < 12 || b > 14) ? 'različita' : (a === 1 && b !== 11 ? 'različit' : 'različitih'); };
  var broj = function (n) { return n === 1 ? '1 proizvod' : n + ' ' + rec(n); };

  function qty() { return Math.max(1, Math.min(50, parseInt(qtyIn ? qtyIn.value : '1', 10) || 1)); }
  // Cena izabrane opcije (npr. veličina), inače osnovna cena proizvoda.
  function cena() {
    var r = form.querySelector('input[data-cena]:checked');
    if (r) return Number(r.dataset.cena) || D.cena;
    var s = Array.prototype.find.call(form.querySelectorAll('select[data-option]'), function (x) { var o = x.selectedOptions[0]; return o && o.dataset.cena; });
    return s ? Number(s.selectedOptions[0].dataset.cena) || D.cena : D.cena;
  }
  // Isti obračun kao bumbaru_grupa_racun() na serveru: različiti proizvodi (po jedan komad svakog) i
  // više komada istog proizvoda (D.isti); svaki komad dobija veći popust, ukupno zaokruženo na 10 din.
  var istiZa = function (n) { var p = 0; (D.isti || []).forEach(function (t) { if (n >= t.od) p = Math.max(p, t.procenat); }); return p; };
  function racun(stavke) {
    var kom = {}, puna = 0;
    stavke.forEach(function (s) {
      for (var i = 0; i < s.kom; i++) (kom[s.slug] = kom[s.slug] || []).push(s.cena);
      puna += s.cena * s.kom;
    });
    var slugs = Object.keys(kom), n = slugs.length, p = pctZa(n), sir = 0, isti = 0;
    slugs.forEach(function (k) {
      var c = kom[k].sort(function (a, b) { return b - a; }), ip = istiZa(c.length);
      isti = Math.max(isti, ip);
      c.forEach(function (x, i) { sir += x * Math.max(ip, i === 0 ? p : 0) / 100; });
    });
    var ukupno = sir > 0 ? Math.round((puna - sir) / 10) * 10 : puna;
    return { n: n, pct: p, isti: isti, puna: puna, popust: puna - ukupno, ukupno: ukupno, slugs: slugs };
  }
  function izbor() { return korpa.concat([{ slug: D.slug, cena: cena(), kom: qty(), sad: true }]); }

  function update() {
    var r = racun(izbor()), n = r.n, p = r.pct, e = Math.max(p, r.isti);
    // Pčelica stoji na stepenici sa dostignutim procentom (za različite proizvode ili za više istih).
    var nivo = Math.min(n, 1);
    tiers.forEach(function (t) { if (e >= t.procenat) nivo = t.od; });
    var x = nivo / max, kljuc = nivo + '|' + e;
    $('[data-mm-fill]').style.width = (x * 100) + '%';
    var bee = $('[data-mm-bee]');
    bee.style.left = (x * 100) + '%';
    if (kljuc !== last && last !== -1) pop(bee);
    box.querySelectorAll('[data-mm-stop]').forEach(function (s) {
      var was = s.classList.contains('on'), on = e >= +s.getAttribute('data-mm-pct');
      s.classList.toggle('on', on);
      if (on && !was && last !== -1) pop(s);
    });
    // komada ovog proizvoda (u korpi + izbor)
    var ovaj = korpa.filter(function (s) { return s.slug === D.slug; }).reduce(function (a, s) { return a + s.kom; }, 0) + qty();
    var uk = korpa.length ? korpa.reduce(function (a, s) { return a.indexOf(s.slug) < 0 ? a.concat([s.slug]) : a; }, []).length : 0;
    $('[data-mm-count]').textContent = broj(n) + (ovaj > 1 ? ' · ' + ovaj + ' kom ovog' : '') + (uk ? ' (u korpi ' + uk + ')' : '');
    var badge = $('[data-mm-badge]');
    var bp = e;
    badge.hidden = !bp;
    badge.textContent = 'Popust ' + bp + '%';
    if (bp > lastPct) pop(badge);
    var next = tiers.filter(function (t) { return t.od > n && t.procenat > e; })[0];
    var sledIsti = (D.isti || []).filter(function (t) { return t.od > ovaj && t.procenat > e; })[0];
    var txt = next
      ? (next.od - n === 1 ? 'Dodajte još jedan različit proizvod za −' + next.procenat + '%' : 'Dodajte još ' + (next.od - n) + ' različita proizvoda za −' + next.procenat + '%')
      : '';
    if (sledIsti) txt += (txt ? ', ili još ' : 'Još ') + (sledIsti.od - ovaj) + ' kom ovog proizvoda za −' + sledIsti.procenat + '%';
    $('[data-mm-text]').textContent = txt ? txt + '.' : 'Imate najveći popust.';
    var tot = $('[data-mm-total]');
    tot.hidden = !r.popust;
    tot.innerHTML = r.popust ? 'Ukupno sa ' + (uk ? 'proizvodima iz korpe' : 'ovim izborom') + ': <strong>' + din(r.ukupno) + '</strong><s>' + din(r.puna) + '</s>' : '';
    var set = $('[data-mm-set]');
    if (set) {
      var nema = (D.lista || []).filter(function (l) { return r.slugs.indexOf(l.slug) < 0; });
      set.hidden = !nema.length;
      set.innerHTML = nema.length ? 'Može uz ovo: ' + nema.map(function (l) { return '<a href="' + esc(l.url) + '">' + esc(l.naziv) + '</a>'; }).join(', ') + '.' : '';
    }
    last = kljuc; lastPct = bp;
  }

  form.addEventListener('change', function () { setTimeout(update, 0); });
  form.addEventListener('input', function (e) { if (e.target === qtyIn) update(); });
  form.addEventListener('click', function (e) { if (e.target.closest('[data-inc],[data-dec]')) setTimeout(update, 0); });

  // Posle „Dodaj u korpu” (AJAX) ovaj izbor je u korpi.
  if (window.fetch) {
    var orig = window.fetch;
    window.fetch = function (url) {
      var sad = { slug: D.slug, cena: cena(), kom: qty() };
      var r = orig.apply(this, arguments);
      if (String(url).indexOf('bumbaru_add') >= 0) {
        r.then(function (res) { return res.clone().json(); }).then(function (j) {
          if (j && j.ok) { korpa.push(sad); update(); }
        }).catch(function () {});
      }
      return r;
    };
  }

  // Messenger poruka: proizvodi iz korpe, popust i ukupna cena (kad ima popusta).
  function wrap() {
    var orig = window.bumbaruOrder;
    if (typeof orig !== 'function' || orig.buGrupa) return;
    var w = function (id, lines, q) {
      var args = Array.prototype.slice.call(arguments);
      if (id === D.sku) {
        var st = korpa.concat([{ slug: D.slug, cena: cena(), kom: q || 1 }]), r = racun(st);
        if (r.popust) {
          var add = korpa.length ? ['', 'Uz to iz korpe:'].concat(korpa.map(function (s) {
            return '- ' + s.naziv + (s.izbor ? ' (' + s.izbor + ')' : '') + ': ' + din(s.cena) + (s.kom > 1 ? ' × ' + s.kom : '');
          })) : [];
          add.push('Popust' + (r.pct ? ' za ' + r.n + ' ' + rec(r.n) + ' proizvoda −' + r.pct + '%' : '') + (r.isti ? (r.pct ? ', ' : ' za ') + 'više istih −' + r.isti + '%' : '') + ' (−' + din(r.popust) + ')');
          add.push('Ukupno: ' + din(r.ukupno) + ' (umesto ' + din(r.puna) + ')');
          args[1] = (lines || []).concat(add);
        }
      }
      return orig.apply(this, args);
    };
    w.buGrupa = true;
    window.bumbaruOrder = w;
  }
  wrap();
  window.addEventListener('load', wrap);
  update();
})();
