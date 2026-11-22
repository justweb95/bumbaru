/* Bumbaru 3D: ukras sa imenom u pregledaču (isto kao lib/ornament.ts na Astro sajtu).
   Boje dolaze iz plugina (Bumbaru 3D → Boje). */
(function () {
  'use strict';
  var D = window.BUMBARU3D || { colors: {}, motivi: [] };

  // Fontovi i širine slova (data/font-widths.json, 1/1000 em); Pacifico je osnovni.
  var FM = D.fm || null, FONTS = D.fontovi || [{ id: 'pacifico', css: 'Pacifico, cursive', cyr: true, sw: 1.2 }];
  var ADVS = {};
  function adv(id) {
    if (ADVS[id]) return ADVS[id];
    var f = FM && (FM.fonts[id] || FM.fonts.pacifico), m = {};
    if (f) Array.from(FM.chars).forEach(function (ch, i) { if (f.w[i] !== null) m[ch] = f.w[i]; });
    return (ADVS[id] = m);
  }
  function font(id) { for (var i = 0; i < FONTS.length; i++) if (FONTS[i].id === id) return FONTS[i]; return FONTS[0]; }
  // Font bez ćirilice + ime ćirilicom = Pacifico (isto kao bumbaru_name_font u PHP-u).
  function nameFont(id, name) { var f = font(id || 'pacifico'); return !f.cyr && /[\u0400-\u04FF]/.test(name) ? font('pacifico') : f; }

  // Obruč: ime ide od jedne do druge strane obruča i krajevima ulazi u njega, pa ga drži bez šinica.
  // Kraća imena dobiju veći font i blago razvučena slova (sx); šinice ostaju samo za imena od 1–2 slova.
  // Traka/pločica (motiv.ime.traka): ime leži na delu ukrasa, pa se samo smesti u širinu w i visinu fs.
  var REACH = 97, FS_MAX = 70, SX_MAX = 1.6;
  function ink(id) {
    var f = FM && (FM.fonts[id] || FM.fonts.pacifico), m = {};
    if (f && f.l) Array.from(FM.chars).forEach(function (ch, i) { if (f.w[i] !== null) m[ch] = [f.l[i], f.r[i]]; });
    return m;
  }
  var INKS = {};
  function fitName(raw, fallback, M, fontId) {
    var cfg = (M && M.ime) || {};
    var name = String(raw || '').trim().slice(0, 12) || fallback || 'Mila';
    var F = nameFont(fontId, name), ADV = adv(F.id), INK = INKS[F.id] || (INKS[F.id] = ink(F.id));
    var fm = (FM && FM.fonts[F.id]) || { yk: 0.23 };
    var chars = Array.from(name), sum = 0;
    chars.forEach(function (ch) { sum += ADV[ch] !== undefined ? ADV[ch] : 520; });
    // Stvarni crtež: od početka crteža prvog slova do kraja crteža poslednjeg (repovi slova prelaze širinu slova).
    var first = INK[chars[0]] || [0, 0], last = INK[chars[chars.length - 1]] || [0, 0];
    var inkEm = Math.max(0.2, (sum - first[0] - last[1]) / 1000), c0 = (first[0] - last[1]) / 2000;
    var sw = +F.sw || 1.2;
    var traka = !!cfg.traka;
    var reach = traka ? +cfg.w : (cfg.reach || REACH);
    var W = 2 * reach - sw; // širina crteža slova (linija oko slova dodaje sw/2 sa svake strane)
    var CY = 182, RI = cfg.ri || 94, yc = cfg.y || CY + 6;
    var fs = traka ? Math.max(16, Math.min(+cfg.fs, W / inkEm)) : Math.max(22, Math.min(FS_MAX, W / inkEm));
    var sx = traka ? 1 : Math.max(1, Math.min(SX_MAX, W / (inkEm * fs)));
    var y = yc + fm.yk * fs;
    var w = inkEm * fs * sx + sw;
    var dx = sx * c0 * fs; // pomeri crtež tako da mu je sredina tačno na sredini ukrasa
    var rails = '';
    var r = function (v) { return v.toFixed(1); };
    var r3 = function (v) { return (Math.round(v * 1000) / 1000).toString(); };
    if (!traka && 2 * reach - w > 6) {
      var yr = y - 0.05 * fs;
      var hr = Math.sqrt(RI * RI - (yr - CY) * (yr - CY));
      rails = 'M' + r(120 - hr - 4) + ' ' + r(yr) + 'L' + r(120 - w / 2 + 0.18 * fs) + ' ' + r(yr) +
        'M' + r(120 + w / 2 - 0.22 * fs) + ' ' + r(yr) + 'L' + r(120 + hr + 4) + ' ' + r(yr);
    }
    var tf = 'matrix(' + r3(sx) + ' 0 0 1 ' + r3(120 - 120 * sx - dx) + ' 0)';
    return { name: name, fs: r(fs), y: r(y), rails: rails, railW: r(Math.max(4.2, 0.085 * fs)), sx: r3(sx), dx: r3(dx), tf: tf, mode: traka ? 'traka' : 'obruc', reach: r(reach), font: F.id, css: F.css, sw: String(F.sw) };
  }

  var ids = Object.keys(D.colors);
  function boja(id) { return D.colors[id] || D.colors[ids[0]] || { face: '#F3EEE5', edge: '#B9AE9C', mid: '#D6CFC3', label: '' }; }
  function motiv(id) {
    for (var i = 0; i < D.motivi.length; i++) if (D.motivi[i].id === id) return D.motivi[i];
    return D.motivi[0] || { id: 'zvezde', hy: 64 };
  }

  function setName(g, f) {
    g.querySelectorAll('.orn-name').forEach(function (t) {
      t.textContent = f.name; t.setAttribute('font-size', f.fs); t.setAttribute('y', f.y); t.setAttribute('transform', f.tf);
      if (f.css) { t.setAttribute('font-family', f.css); t.setAttribute('stroke-width', f.sw); }
    });
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
    g.setAttribute('data-motif', M.id); g.setAttribute('data-hy', M.hy);
    var fid = o.font;
    if (!fid) { var fi = document.querySelector('[data-order-form] input[name="font"]:checked'); fid = fi ? fi.value : 'pacifico'; }
    setName(g, fitName(o.name, o.fallback, M, fid));
    return M;
  }

  window.BumbaruOrnament = { fitName: fitName, boja: boja, motiv: motiv, paint: paint, setName: setName, font: font, nameFont: nameFont };

  // Strana proizvoda: promena fonta osveži pregled (product.js crta pregled kad se promeni ime).
  document.addEventListener('change', function (e) {
    if (!e.target.matches || !e.target.matches('[data-order-form] input[name="font"]')) return;
    var n = document.querySelector('[data-order-form] [data-ime], [data-order-form] [data-set-name]');
    if (n) n.dispatchEvent(new Event('input', { bubbles: true }));
  });

  // Početna strana: ime upisano u polje odmah se vidi na tri ukrasa.
  var input = document.querySelector('[data-home-ime]');
  if (input) {
    var fallback = { a: 'Luka', b: 'Sofija', c: 'Mila' };
    input.addEventListener('input', function () {
      document.querySelectorAll('.ukr-stage [data-orn]').forEach(function (g) {
        setName(g, fitName(input.value, fallback[g.getAttribute('data-orn')] || 'Sofija', motiv(g.getAttribute('data-motif'))));
      });
    });
  }
})();
