/* Bumbaru 3D: popust za više komada (npr. ukrasi sa imenom).
   Pčelica na traci pokazuje koliko još komada do popusta: broji se količina u obrascu + ono što je već u korpi. */
(function () {
  'use strict';
  var box = document.querySelector('[data-kolicina]');
  if (!box) return;
  var D = JSON.parse(box.getAttribute('data-kolicina'));
  var form = box.closest('form') || document;
  var qtyIn = form.querySelector('input[name="quantity"]');
  var tiers = D.tiers, max = tiers[tiers.length - 1].od, korpa = D.korpa || 0, last = -1, lastPct = 0;
  var $ = function (s) { return box.querySelector(s); };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var pop = function (el) { if (el) { el.classList.remove('pop'); void el.offsetWidth; el.classList.add('pop'); } };
  var din = function (n) { return Math.round(n).toLocaleString('sr-RS').replace(/,/g, '.') + ' din'; };
  var snizi = function (n, p) { return p ? Math.round(n * (1 - p / 100) / 10) * 10 : n; };
  var rec = function (n) { var m10 = n % 10, m100 = n % 100; return m10 === 1 && m100 !== 11 ? D.rec[0] : D.rec[1]; };
  var pct = function (n) { var p = 0; tiers.forEach(function (t) { if (n >= t.od) p = Math.max(p, t.procenat); }); return p; };

  function qty() { return Math.max(1, Math.min(50, parseInt(qtyIn ? qtyIn.value : '1', 10) || 1)); }

  function update() {
    var q = qty(), n = korpa + q, p = pct(n), x = Math.min(n, max) / max;
    $('[data-mm-fill]').style.width = (x * 100) + '%';
    var bee = $('[data-mm-bee]');
    bee.style.left = (x * 100) + '%';
    if (n !== last && last >= 0) pop(bee);
    box.querySelectorAll('[data-mm-stop]').forEach(function (s) {
      var was = s.classList.contains('on'), on = n >= +s.getAttribute('data-mm-stop');
      s.classList.toggle('on', on);
      if (on && !was && last >= 0) pop(s);
    });
    $('[data-mm-count]').textContent = n + ' kom' + (korpa ? ' (u korpi ' + korpa + ')' : '');
    var badge = $('[data-mm-badge]');
    badge.hidden = !p;
    badge.textContent = 'Popust ' + p + '%';
    if (p > lastPct) pop(badge);
    var next = tiers.filter(function (t) { return t.od > n; })[0], txt = $('[data-mm-text]');
    if (next) {
      var k = next.od - n;
      txt.textContent = (p ? 'Još ' + k + ' ' + rec(k) + ' i popust je ' + next.procenat + '%.' : 'Još ' + k + ' ' + rec(k) + ' do popusta od ' + next.procenat + '%.');
    } else {
      txt.textContent = 'Imate najveći popust za količinu.';
    }
    var tot = $('[data-mm-total]');
    if (tot && D.cena) {
      tot.hidden = !p;
      tot.innerHTML = p ? 'Cena po ' + D.rec[0] + 'u sa popustom: <strong>' + din(Math.min(D.cena, snizi(D.lista || D.cena, p))) + '</strong><s>' + din(D.lista || D.cena) + '</s>' + (korpa ? ' <small>(važi i za ' + korpa + ' u korpi)</small>' : '') : '';
    }
    var setEl = $('[data-mm-set]');
    if (setEl && D.set) {
      // 2–4 ukrasa: porodični set od 4 imena je povoljniji po komadu.
      var showSet = n >= 2 && n <= 4 && D.cena && D.set.po < D.cena;
      setEl.hidden = !showSet;
      if (showSet) setEl.innerHTML = 'Za ' + D.set.kom + ' imena je povoljniji <a href="' + esc(D.set.url) + '">' + esc(D.set.naziv) + '</a>: ' + din(D.set.cena) + ' (' + din(D.set.po) + ' po ukrasu umesto ' + din(D.cena) + ').';
    }
    last = n; lastPct = p;
  }

  form.addEventListener('input', function (e) { if (e.target === qtyIn) update(); });
  form.addEventListener('change', function (e) { if (e.target === qtyIn) update(); });
  form.addEventListener('click', function (e) {
    if (e.target.closest('[data-inc],[data-dec]')) setTimeout(update, 0);
  });

  // Posle „Dodaj u korpu” (AJAX) broj u korpi se osveži iz odgovora servera.
  if (window.fetch) {
    var orig = window.fetch;
    window.fetch = function (url) {
      var r = orig.apply(this, arguments);
      if (String(url).indexOf('bumbaru_add') >= 0) {
        r.then(function (res) { return res.clone().json(); }).then(function (j) {
          if (j && j.ok && typeof j.u_korpi === 'number') {
            korpa = j.u_korpi;
            if (qtyIn) qtyIn.value = 1;
            update();
          }
        }).catch(function () {});
      }
      return r;
    };
  }
  update();
})();
