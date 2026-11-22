/* Bumbaru 3D: komplet po izboru (npr. lutkice: irvas + Sneško + Deda Mraz).
   Kupac štiklira šta ide u komplet; popust raste sa brojem proizvoda, a veličina glavnog proizvoda važi za sve. */
(function () {
  'use strict';
  var box = document.querySelector('[data-komplet2]');
  if (!box) return;
  var D = JSON.parse(box.getAttribute('data-komplet2'));
  var form = document.querySelector('[data-order-form]');
  var CFG = window.BUMBARU3D || {};
  var $ = function (s) { return box.querySelector(s); };
  var din = function (n) { return Math.round(n).toLocaleString('sr-RS').replace(/,/g, '.') + ' din'; };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var pop = function (el) { if (el) { el.classList.remove('pop'); void el.offsetWidth; el.classList.add('pop'); } };
  var lastPct = -1;

  function opcije() {
    var o = {};
    if (!form) return o;
    form.querySelectorAll('[name^="o-"]').forEach(function (el) {
      var i = el.name.slice(2);
      if (el.type === 'radio') { if (el.checked) o[i] = +el.value; } else o[i] = +el.value;
    });
    return o;
  }
  function velicina() {
    if (!form || !D.opcija) return '';
    var el = form.querySelector('[data-option="' + D.opcija.replace(/"/g, '\\"') + '"]:checked') || form.querySelector('select[data-option="' + D.opcija.replace(/"/g, '\\"') + '"]');
    if (!el) return '';
    if (el.tagName === 'SELECT') { var op = el.options[el.selectedIndex]; return op ? op.getAttribute('data-label') || '' : ''; }
    return el.getAttribute('data-label') || '';
  }
  function izabrani() {
    return Array.prototype.filter.call(box.querySelectorAll('[data-km-pick]'), function (c) { return c.checked; }).map(function (c) { return +c.value; });
  }
  function pct(n) { var p = 0; D.popusti.forEach(function (t) { if (n >= t.od) p = Math.max(p, t.procenat); }); return p; }

  function calc() {
    var vel = velicina(), sel = izabrani();
    var m = D.main[vel] || D.main[Object.keys(D.main)[0]];
    var puna = m.cena, zbir = m.eff;
    D.uz.forEach(function (x) {
      if (sel.indexOf(x.pid) < 0) return;
      var v = vel && x.v && x.v[vel] ? x.v[vel] : x;
      puna += v.cena; zbir += v.eff;
    });
    var n = 1 + sel.length, p = sel.length ? pct(n) : 0;
    return { n: n, sel: sel, pct: p, puna: puna, cena: Math.round(zbir * (1 - p / 100) / 10) * 10 };
  }

  function update() {
    var r = calc();
    box.querySelectorAll('.km-pick').forEach(function (l) { l.classList.toggle('on', l.querySelector('input').checked); });
    $('[data-km-n]').textContent = r.n + ' kom';
    var b = $('[data-km-pct]');
    b.hidden = !r.pct; b.textContent = 'Popust ' + r.pct + '%';
    if (r.pct > lastPct && lastPct >= 0) pop(b);
    lastPct = r.pct;
    var next = D.popusti.filter(function (t) { return t.od > r.n; })[0];
    var left = D.uz.length - r.sel.length;
    $('[data-km-text]').textContent = !r.sel.length ? 'Štiklirajte bar jedan proizvod za komplet.'
      : next && left > 0 ? 'Dodajte još ' + (next.od - r.n) + ' i popust je ' + next.procenat + '%.' : 'Imate najveći popust za komplet.';
    $('[data-km-puna]').textContent = r.pct ? din(r.puna) : '';
    $('[data-km-cena]').textContent = din(r.cena);
    $('[data-km2-add]').disabled = !r.sel.length;
  }

  box.addEventListener('change', update);
  if (form) form.addEventListener('change', update);

  $('[data-km2-add]').addEventListener('click', function () {
    var r = calc(), btn = this;
    if (!r.sel.length || !CFG.ajax) return;
    var nap = form && form.querySelector('[name="napomena"]');
    var data = { komplet: true, kompletUz: r.sel, opcije: opcije(), napomena: nap ? nap.value : '' };
    var body = new URLSearchParams();
    body.set('product_id', String(D.pid));
    body.set('quantity', '1');
    body.set('bu_json', JSON.stringify(data));
    btn.classList.add('busy'); btn.disabled = true;
    fetch(CFG.ajax.replace('%%endpoint%%', 'bumbaru_add'), { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
      .then(function (res) { return res.json(); })
      .then(function (j) {
        if (!j || !j.ok) {
          if (window.bumbaruToast) window.bumbaruToast('<span><strong>Nije dodato u korpu</strong><span>' + esc(j && j.msg || 'Pokušajte ponovo.') + '</span></span>', 'err');
          return;
        }
        if (window.bumbaruSetCartFragments) window.bumbaruSetCartFragments(j.fragments, j.count);
        if (window.bumbaruToast) window.bumbaruToast((D.slika ? '<img src="' + esc(D.slika) + '" alt="" width="48" height="48" />' : '') + '<span><strong>Dodato u korpu</strong><span>' + esc(j.naziv || D.naziv) + '</span></span><a class="btn btn-sm" href="' + esc(j.cart) + '">Korpa</a><a class="btn btn-sm btn-primary" href="' + esc(j.checkout) + '">Plaćanje</a>');
      })
      .catch(function () {
        if (window.bumbaruToast) window.bumbaruToast('<span><strong>Nije dodato u korpu</strong><span>Proverite internet i pokušajte ponovo.</span></span>', 'err');
      })
      .then(function () { btn.classList.remove('busy'); btn.disabled = false; });
  });
  update();
})();
