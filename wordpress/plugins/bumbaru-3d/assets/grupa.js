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
  var tiers = D.tiers, max = tiers[tiers.length - 1].od, korpa = D.korpa || [], last = -1, lastPct = 0;
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
  // Isti obračun kao bumbaru_grupa_racun() na serveru.
  function racun(stavke) {
    var jed = {}, puna = 0;
    stavke.forEach(function (s) {
      puna += s.cena * s.kom;
      if (s.kom && (!(s.slug in jed) || s.cena > jed[s.slug])) jed[s.slug] = s.cena;
    });
    var slugs = Object.keys(jed), n = slugs.length, p = pctZa(n), zbir = 0;
    slugs.forEach(function (k) { zbir += jed[k]; });
    var popust = p ? zbir - Math.round(zbir * (1 - p / 100) / 10) * 10 : 0;
    return { n: n, pct: p, puna: puna, popust: popust, ukupno: puna - popust, slugs: slugs };
  }
  function izbor() { return korpa.concat([{ slug: D.slug, cena: cena(), kom: qty(), sad: true }]); }

  function update() {
    var r = racun(izbor()), n = r.n, p = r.pct, x = Math.min(n, max) / max;
    $('[data-mm-fill]').style.width = (x * 100) + '%';
    var bee = $('[data-mm-bee]');
    bee.style.left = (x * 100) + '%';
    if (n !== last && last >= 0) pop(bee);
    box.querySelectorAll('[data-mm-stop]').forEach(function (s) {
      var was = s.classList.contains('on'), on = n >= +s.getAttribute('data-mm-stop');
      s.classList.toggle('on', on);
      if (on && !was && last >= 0) pop(s);
    });
    var uk = korpa.length ? korpa.reduce(function (a, s) { return a.indexOf(s.slug) < 0 ? a.concat([s.slug]) : a; }, []).length : 0;
    $('[data-mm-count]').textContent = broj(n) + (uk ? ' (u korpi ' + uk + ')' : '');
    var badge = $('[data-mm-badge]');
    badge.hidden = !p;
    badge.textContent = 'Popust ' + p + '%';
    if (p > lastPct) pop(badge);
    var next = tiers.filter(function (t) { return t.od > n; })[0];
    $('[data-mm-text]').textContent = next
      ? (next.od - n === 1 ? 'Dodajte još jedan različit proizvod za −' + next.procenat + '%.' : 'Dodajte još ' + (next.od - n) + ' različita proizvoda za −' + next.procenat + '%.')
      : 'Imate najveći popust za različite proizvode.';
    var tot = $('[data-mm-total]');
    tot.hidden = !p;
    tot.innerHTML = p ? 'Ukupno sa ' + (uk ? 'proizvodima iz korpe' : 'ovim izborom') + ': <strong>' + din(r.ukupno) + '</strong><s>' + din(r.puna) + '</s>' : '';
    var set = $('[data-mm-set]');
    if (set) {
      var nema = (D.lista || []).filter(function (l) { return r.slugs.indexOf(l.slug) < 0; });
      set.hidden = !nema.length;
      set.innerHTML = nema.length ? 'Može uz ovo: ' + nema.map(function (l) { return '<a href="' + esc(l.url) + '">' + esc(l.naziv) + '</a>'; }).join(', ') + '.' : '';
    }
    last = n; lastPct = p;
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
        if (r.pct) {
          var add = korpa.length ? ['', 'Uz to iz korpe:'].concat(korpa.map(function (s) {
            return '- ' + s.naziv + (s.izbor ? ' (' + s.izbor + ')' : '') + ': ' + din(s.cena) + (s.kom > 1 ? ' × ' + s.kom : '');
          })) : [];
          add.push('Popust za ' + r.n + ' ' + rec(r.n) + ' proizvoda: −' + r.pct + '% (−' + din(r.popust) + ')');
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
