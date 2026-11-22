/* Bumbaru 3D: ukras sa imenom u pregledaču (isto kao lib/ornament.ts na Astro sajtu).
   Boje dolaze iz plugina (Bumbaru 3D → Boje). */
(function () {
  'use strict';
  var D = window.BUMBARU3D || { colors: {}, motivi: [] };

  var CH = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyzČĆŠŽĐčćšžđАБВГДЂЕЖЗИЈКЛЉМНЊОПРСТЋУФХЦЧЏШабвгдђежзијклљмнњопрстћуфхцчџш -'.";
  var W = [786,827,686,836,686,712,811,873,495,743,937,673,1232,884,816,805,816,880,693,697,890,763,1217,712,851,721,470,473,368,515,393,371,479,502,246,254,525,326,744,496,451,481,500,455,427,393,462,449,700,529,479,445,686,686,693,721,821,368,368,427,445,470,786,917,827,635,964,974,686,1234,725,890,743,937,861,1158,1133,873,1182,816,892,805,686,697,979,824,977,712,890,773,890,1272,470,473,473,380,479,514,393,717,435,462,254,465,549,794,734,475,751,451,496,481,368,744,502,479,721,529,501,555,600,693,265,403,290,257];
  var ADV = {};
  for (var i = 0; i < CH.length; i++) ADV[CH[i]] = W[i];

  // Ime ide od jedne do druge strane obruča i krajevima ulazi u njega, pa ga drži bez šinica.
  // Kraća imena dobiju veći font i blago razvučena slova (sx); šinice ostaju samo za imena od 1–2 slova.
  var REACH = 97, FS_MAX = 70, SX_MAX = 1.6;
  function fitName(raw, fallback) {
    var name = String(raw || '').trim().slice(0, 12) || fallback || 'Mila';
    var sum = 0;
    Array.from(name).forEach(function (ch) { sum += ADV[ch] !== undefined ? ADV[ch] : 520; });
    var em = sum / 1000 + 0.1;
    var CY = 182, RI = 94, yc = CY + 6;
    var fs = Math.max(22, Math.min(FS_MAX, 2 * REACH / em));
    var sx = Math.max(1, Math.min(SX_MAX, 2 * REACH / (em * fs)));
    var y = yc + 0.23 * fs;
    var w = em * fs * sx;
    var dx = 0.07 * fs * sx; // slova sa repom: crtež je malo desno od širine slova
    var rails = '';
    var r = function (v) { return v.toFixed(1); };
    var r3 = function (v) { return (Math.round(v * 1000) / 1000).toString(); };
    if (REACH - w / 2 > 3) {
      var yr = y - 0.05 * fs;
      var hr = Math.sqrt(RI * RI - (yr - CY) * (yr - CY));
      rails = 'M' + r(120 - hr - 4) + ' ' + r(yr) + 'L' + r(120 - w / 2 + 0.18 * fs) + ' ' + r(yr) +
        'M' + r(120 + w / 2 - 0.22 * fs) + ' ' + r(yr) + 'L' + r(120 + hr + 4) + ' ' + r(yr);
    }
    var tf = 'matrix(' + r3(sx) + ' 0 0 1 ' + r3(120 - 120 * sx - dx) + ' 0)';
    return { name: name, fs: r(fs), y: r(y), rails: rails, railW: r(Math.max(4.2, 0.085 * fs)), sx: r3(sx), dx: r3(dx), tf: tf };
  }

  var ids = Object.keys(D.colors);
  function boja(id) { return D.colors[id] || D.colors[ids[0]] || { face: '#F3EEE5', edge: '#B9AE9C', mid: '#D6CFC3', label: '' }; }
  function motiv(id) {
    for (var i = 0; i < D.motivi.length; i++) if (D.motivi[i].id === id) return D.motivi[i];
    return D.motivi[0] || { id: 'zvezde', hy: 64 };
  }

  function setName(g, f) {
    g.querySelectorAll('.orn-name').forEach(function (t) { t.textContent = f.name; t.setAttribute('font-size', f.fs); t.setAttribute('y', f.y); t.setAttribute('transform', f.tf); });
    g.querySelectorAll('.orn-rail').forEach(function (p) { p.setAttribute('d', f.rails); p.setAttribute('stroke-width', f.railW); });
  }

  // Oboji jedan ukras: motiv, boja ukrasa, boja imena i ime.
  function paint(g, o) {
    var M = motiv(o.motif), B = boja(o.color), N = o.nameColor === 'ista' ? B : boja(o.nameColor);
    var e = g.querySelector('.orn-edge'), fc = g.querySelector('.orn-face');
    [e, fc].forEach(function (u) { u.setAttribute('href', '#orn-' + M.id); });
    e.setAttribute('fill', B.edge); e.setAttribute('stroke', B.edge);
    fc.setAttribute('fill', B.face); fc.setAttribute('stroke', B.face);
    var ne = g.querySelector('.orn-ne'), nf = g.querySelector('.orn-nf');
    ne.setAttribute('fill', N.edge); ne.setAttribute('stroke', N.edge);
    nf.setAttribute('fill', N.face); nf.setAttribute('stroke', N.face);
    setName(g, fitName(o.name, o.fallback));
    return M;
  }

  window.BumbaruOrnament = { fitName: fitName, boja: boja, motiv: motiv, paint: paint, setName: setName };

  // Početna strana: ime upisano u polje odmah se vidi na tri ukrasa.
  var input = document.querySelector('[data-home-ime]');
  if (input) {
    var fallback = { a: 'Luka', b: 'Sofija', c: 'Mila' };
    input.addEventListener('input', function () {
      document.querySelectorAll('.ukr-stage [data-orn]').forEach(function (g) {
        setName(g, fitName(input.value, fallback[g.getAttribute('data-orn')] || 'Sofija'));
      });
    });
  }
})();
