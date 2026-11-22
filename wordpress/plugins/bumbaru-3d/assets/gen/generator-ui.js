/* Bumbaru 3D → Generator modela: strana u administraciji (porudžbine, imena, mere, pregled, izvoz). */
(function () {
  'use strict';
  var C = window.BUMBARU_GEN, G = window.BumbaruGen;
  var root = document.querySelector('[data-gen]');
  if (!C || !G || !root) return;
  var $ = function (s) { return root.querySelector(s); };
  var font = null, defs = null, okretni = null, items = [], src = null, timer = 0;

  var colors = {};
  C.colors.forEach(function (c) { colors[c.id] = c; });
  function color(id) { return colors[id] || C.colors[0]; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function slug(s) {
    var m = { č: 'c', ć: 'c', š: 's', ž: 'z', đ: 'dj', Č: 'C', Ć: 'C', Š: 'S', Ž: 'Z', Đ: 'Dj' };
    var cyr = 'абвгдђежзијклљмнњопрстћуфхцчџш', lat = ['a', 'b', 'v', 'g', 'd', 'dj', 'e', 'z', 'z', 'i', 'j', 'k', 'l', 'lj', 'm', 'n', 'nj', 'o', 'p', 'r', 's', 't', 'c', 'u', 'f', 'h', 'c', 'c', 'dz', 's'];
    return String(s).toLowerCase().split('').map(function (ch) {
      var k = cyr.indexOf(ch);
      return k >= 0 ? lat[k] : (m[ch] || ch);
    }).join('').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'ukras';
  }

  /* ——— Redovi (jedan red = jedan ukras) ——— */
  var tbody = $('[data-gen-rows]');
  function colorSelect(cls, val, withSame) {
    var o = '<select class="' + cls + '">' + (withSame ? '<option value="ista"' + (val === 'ista' ? ' selected' : '') + '>Kao ukras</option>' : '');
    C.colors.forEach(function (c) { o += '<option value="' + esc(c.id) + '"' + (c.id === val ? ' selected' : '') + '>' + esc(c.naziv) + '</option>'; });
    return o + '</select>';
  }
  function addRow(r) {
    r = r || { ime: '', motiv: C.motivi[0].id, boja: 'bela', bojaImena: 'crvena', font: 'pacifico' };
    var fon = '<select class="bg-font">' + (C.fontovi || []).map(function (f) { return '<option value="' + esc(f.id) + '"' + (f.id === (r.font || 'pacifico') ? ' selected' : '') + '>' + esc(f.label) + '</option>'; }).join('') + '</select>';
    var mot = '<select class="bg-mot">' + C.motivi.map(function (m) { return '<option value="' + esc(m.id) + '"' + (m.id === r.motiv ? ' selected' : '') + '>' + esc(m.label) + '</option>'; }).join('') + '</select>';
    var tr = document.createElement('tr');
    tr.innerHTML = '<td class="bg-n"></td><td><input type="text" class="bg-ime" maxlength="12" value="' + esc(r.ime) + '" placeholder="npr. Mila" /></td><td>' + mot + '</td><td>' + fon + '</td>'
      + '<td>' + colorSelect('bg-boja', r.boja, false) + '</td><td>' + colorSelect('bg-bi', r.bojaImena || 'ista', true) + '</td>'
      + '<td><button type="button" class="button-link bg-del" aria-label="Ukloni">✕</button></td>';
    tbody.appendChild(tr);
    number();
  }
  function number() { Array.prototype.forEach.call(tbody.rows, function (tr, i) { tr.cells[0].textContent = i + 1; }); }
  function rows() {
    return Array.prototype.map.call(tbody.rows, function (tr) {
      return { ime: tr.querySelector('.bg-ime').value.trim(), motiv: tr.querySelector('.bg-mot').value, font: tr.querySelector('.bg-font').value, boja: tr.querySelector('.bg-boja').value, bojaImena: tr.querySelector('.bg-bi').value };
    }).filter(function (r) { return r.ime; });
  }
  function setRows(list, label) {
    tbody.innerHTML = '';
    list.forEach(addRow);
    if (!list.length) addRow();
    src = label || null;
    $('[data-gen-src]').textContent = label ? 'Iz porudžbine: ' + label : '';
    rebuild();
  }
  tbody.addEventListener('input', schedule);
  tbody.addEventListener('change', schedule);
  tbody.addEventListener('click', function (e) {
    if (e.target.classList.contains('bg-del')) {
      e.target.closest('tr').remove();
      if (!tbody.rows.length) addRow();
      number(); schedule();
    }
  });
  $('[data-gen-add]').addEventListener('click', function () {
    var last = tbody.rows[tbody.rows.length - 1];
    addRow(last ? { ime: '', motiv: last.querySelector('.bg-mot').value, font: last.querySelector('.bg-font').value, boja: last.querySelector('.bg-boja').value, bojaImena: last.querySelector('.bg-bi').value } : null);
    tbody.rows[tbody.rows.length - 1].querySelector('.bg-ime').focus();
  });
  root.addEventListener('change', function (e) { if (e.target.matches('[data-gen-dim],[data-gen-model]')) schedule(); });
  root.addEventListener('input', function (e) { if (e.target.matches('[data-gen-dim]')) schedule(); });

  function dims() {
    var d = {};
    root.querySelectorAll('[data-gen-dim]').forEach(function (i) { d[i.getAttribute('data-gen-dim')] = parseFloat(String(i.value).replace(',', '.')) || 0; });
    return d;
  }

  /* ——— Porudžbine ——— */
  var tab = 'nove';
  function pendingRows(o) {
    var n = 0;
    o.items.forEach(function (it) { if (!it.skinuto) n += it.rows.length; });
    return n;
  }
  function visibleOrders() {
    return C.orders.filter(function (o) { return tab === 'sve' || (o.aktivna && pendingRows(o)); });
  }
  function renderOrders() {
    var box = $('[data-gen-orders]'), list = visibleOrders();
    var nove = 0, noveItems = 0;
    C.orders.forEach(function (o) { if (o.aktivna) { var k = pendingRows(o); nove += k; if (k) noveItems++; } });
    $('[data-gen-ceka]').textContent = nove ? '(' + nove + ')' : '';
    var all = $('[data-gen-allnew]');
    all.hidden = !(tab === 'nove' && nove > 1 && noveItems > 0);
    $('[data-gen-skini-sve]').textContent = 'Skini sve nove u jedan 3MF (' + nove + ' ' + (nove === 1 ? 'ukras' : 'ukrasa') + ')';
    if (!list.length) {
      box.innerHTML = '<p class="bg-empty">' + (tab === 'nove' ? 'Nema novih ukrasa za štampu. 🐝' : 'Još nema porudžbina sa ukrasima.') + '</p>';
      return;
    }
    box.innerHTML = list.map(function (o) {
      var items = o.items.filter(function (it) { return tab === 'sve' || !it.skinuto; });
      return '<div class="bg-order"><div class="bg-order-h"><strong>#' + esc(o.broj) + '</strong> ' + esc(o.kupac) + ' <span>' + esc(o.datum) + ' · ' + esc(o.status) + '</span></div>'
        + items.map(function (it) {
          var names = it.rows.map(function (r) { return r.ime; }).join(', ');
          return '<div class="bg-item' + (it.skinuto ? ' is-done' : '') + '" data-o="' + o.id + '" data-i="' + it.id + '">'
            + '<div class="bg-item-t"><b>' + esc(it.naziv) + (it.kom > 1 ? ' × ' + it.kom : '') + '</b>'
            + (it.skinuto ? '<span class="bg-badge bg-badge-done">Skinuto ' + esc(it.skinuto) + '</span>' : '<span class="bg-badge bg-badge-new">Novo</span>') + '</div>'
            + '<small>' + esc(names) + '</small>'
            + '<div class="bg-item-a"><button type="button" class="button button-small button-primary" data-skini>' + (it.skinuto ? 'Skini ponovo' : 'Skini 3MF') + '</button>'
            + '<button type="button" class="button button-small" data-otvori>Otvori</button></div></div>';
        }).join('')
        + (items.length > 1 ? '<button type="button" class="button button-small bg-all" data-o="' + o.id + '" data-skini-sve-o>Skini celu porudžbinu u jedan 3MF</button>' : '')
        + '</div>';
    }).join('');
  }
  function findOrder(id) { return C.orders.filter(function (o) { return o.id === id; })[0]; }
  /** Ukrasi i oznake stavki za porudžbinu (iid = jedna stavka, 0 = sve; samoNove = samo neskinute). */
  function collect(o, iid, samoNove) {
    var list = [], keys = [];
    o.items.forEach(function (it) {
      if (iid && it.id !== iid) return;
      if (samoNove && it.skinuto) return;
      it.rows.forEach(function (r) { list.push(r); });
      keys.push(o.id + ':' + it.id);
    });
    return { rows: list, keys: keys };
  }
  function loadItem(oid, iid) {
    var o = findOrder(oid);
    if (!o) return false;
    var c = collect(o, iid, false);
    if (!c.rows.length) return false;
    setRows(c.rows, '#' + o.broj + (o.kupac ? ' (' + o.kupac + ')' : ''));
    src = { order: o.broj };
    return true;
  }
  // Oznaka „Skinuto” na serveru i u listi.
  function mark(keys) {
    if (!keys.length) return;
    var body = new FormData();
    body.append('action', 'bumbaru_gen_mark');
    body.append('_ajax_nonce', C.nonce);
    keys.forEach(function (k) { body.append('stavke[]', k); });
    fetch(C.ajax, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j || !j.success) return;
      j.data.stavke.forEach(function (k) {
        var p = k.split(':'), o = findOrder(+p[0]);
        if (o) o.items.forEach(function (it) { if (it.id === +p[1]) it.skinuto = j.data.skinuto; });
      });
      document.querySelectorAll('.bu-gen-count').forEach(function (el) {
        el.textContent = j.data.ceka;
        if (!j.data.ceka) el.parentNode.style.display = 'none';
      });
      renderOrders();
    });
  }
  function quick(c, base) {
    if (!font || !defs) { $('[data-gen-err]').textContent = 'Font i motivi se još učitavaju, sačekajte sekund.'; return; }
    try {
      var built = buildItems(c.rows);
      save(G.to3MF(built, { naslov: base }), base + '.3mf', 'model/3mf');
      mark(c.keys);
    } catch (ex) {
      $('[data-gen-err]').textContent = 'Greška: ' + ex.message;
    }
  }
  function nameFor(o, c) {
    return 'porudzbina-' + o.broj + '-' + (c.rows.length === 1 ? 'ukras-' + slug(c.rows[0].ime) + '-' + c.rows[0].motiv : c.rows.length + '-ukrasa');
  }
  $('[data-gen-orders]').addEventListener('click', function (e) {
    var card = e.target.closest('[data-o]');
    if (!card) return;
    var o = findOrder(+card.getAttribute('data-o')), iid = +card.getAttribute('data-i') || 0;
    if (!o) return;
    if (e.target.closest('[data-skini]') || e.target.closest('[data-skini-sve-o]')) {
      var c = collect(o, iid, false);
      quick(c, nameFor(o, c));
    } else if (e.target.closest('[data-otvori]')) {
      loadItem(o.id, iid);
      root.querySelectorAll('.bg-item').forEach(function (b) { b.classList.toggle('is-on', b === card); });
      $('[data-gen-rows]').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
  $('[data-gen-skini-sve]').addEventListener('click', function () {
    var rowsAll = [], keys = [];
    C.orders.forEach(function (o) {
      if (!o.aktivna) return;
      var c = collect(o, 0, true);
      rowsAll = rowsAll.concat(c.rows); keys = keys.concat(c.keys);
    });
    if (!rowsAll.length) return;
    var d = new Date(), dd = d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    quick({ rows: rowsAll, keys: keys }, 'za-stampu-' + dd + '-' + rowsAll.length + '-ukrasa');
  });
  root.querySelectorAll('[data-gen-tab]').forEach(function (t) {
    t.addEventListener('click', function () {
      tab = t.getAttribute('data-gen-tab');
      root.querySelectorAll('[data-gen-tab]').forEach(function (x) { x.classList.toggle('is-on', x === t); });
      renderOrders();
    });
  });

  /* ——— Pravljenje modela ——— */
  function schedule() { clearTimeout(timer); timer = setTimeout(rebuild, 250); }
  /** Ukrasi (redovi) → gotovi 3D predmeti raspoređeni po ploči. */
  function buildItems(list) {
    var d = dims(), model = G.models[$('[data-gen-model]').value] || G.models.ukras, out = [];
    list.forEach(function (r) {
      var bi = r.bojaImena === 'ista' ? null : color(r.bojaImena);
      out.push(model.build({
        motiv: r.motiv, font: r.font || 'pacifico', ime: r.ime, precnik: d.precnik, debljina: d.debljina, izdignuto: d.izdignuto, vrh: d.vrh,
        boja: color(r.boja), bojaImena: bi,
      }, { defs: defs, okretni: okretni, font: font.pacifico, fonts: font, fitName: window.BumbaruOrnament.fitName, motiv: window.BumbaruOrnament.motiv }));
    });
    return G.arrange(out, 6, d.ploca);
  }
  function rebuild() {
    if (!font || !defs) return;
    var err = $('[data-gen-err]'), d = dims();
    err.textContent = '';
    try {
      items = buildItems(rows());
    } catch (ex) {
      err.textContent = 'Greška: ' + ex.message;
      items = [];
    }
    var warn = items.map(function (i) { return i.upozorenje; }).filter(Boolean);
    var nap = items.map(function (i) { return i.napomena; }).filter(function (x, k, a) { return x && a.indexOf(x) === k; });
    if (warn.length) err.textContent = 'Pažnja: ' + warn.join(' ');
    var napEl = $('[data-gen-nap]');
    if (napEl) napEl.textContent = nap.join(' ');
    var ok = items.length > 0;
    $('[data-gen-3mf]').disabled = !ok;
    $('[data-gen-stl]').disabled = !ok;
    $('[data-gen-preview]').innerHTML = ok ? G.previewSVG(items) : '<p>Upišite ime.</p>';
    if (ok) {
      var s = G.stats(items), it = items[0];
      $('[data-gen-stats]').textContent = items.length + (items.length === 1 ? ' ukras' : ' ukrasa') + ' · ' + it.sirina.toFixed(1) + ' × ' + it.visina.toFixed(1) + ' × ' + (d.debljina + d.izdignuto).toFixed(1) + ' mm · delovi: '
        + it.delovi.map(function (p) { return p.naziv; }).join(', ') + ' · ' + s.trouglova.toLocaleString('sr-RS') + ' trouglova';
    } else {
      $('[data-gen-stats]').textContent = '';
    }
  }

  function fileBase() {
    var m = items.length === 1 ? rows()[0].motiv : 'mix';
    var who = items.length === 1 ? slug(items[0].ime) : items.length + '-ukrasa';
    return (src && src.order ? 'porudzbina-' + src.order + '-' : '') + 'ukras-' + who + '-' + m;
  }
  function save(data, name, type) {
    var url = URL.createObjectURL(new Blob([data], { type: type }));
    var a = document.createElement('a');
    a.href = url; a.download = name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
  }
  function export3MF() {
    if (!items.length) return;
    save(G.to3MF(items, { naslov: fileBase() }), fileBase() + '.3mf', 'model/3mf');
  }
  $('[data-gen-3mf]').addEventListener('click', export3MF);
  $('[data-gen-stl]').addEventListener('click', function () {
    if (!items.length) return;
    var files = [];
    items.forEach(function (it, k) {
      it.delovi.forEach(function (p) {
        files.push({ name: (items.length > 1 ? (k + 1) + '-' : '') + slug(it.ime) + '-' + slug(p.naziv) + '.stl', data: G.toSTL([p.mesh], it.x, it.y) });
      });
    });
    if (files.length === 1) save(files[0].data, fileBase() + '.stl', 'model/stl');
    else save(G.zip(files), fileBase() + '-stl.zip', 'application/zip');
  });

  $('[data-gen-save]').addEventListener('click', function () {
    var body = new FormData(), d = dims();
    body.append('action', 'bumbaru_gen_defaults');
    body.append('_ajax_nonce', C.nonce);
    Object.keys(d).forEach(function (k) { body.append(k, d[k]); });
    fetch(C.ajax, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      $('[data-gen-saved]').textContent = j && j.success ? 'Zapamćeno.' : 'Nije sačuvano.';
    });
  });

  /* ——— Početak ——— */
  renderOrders();
  addRow({ ime: 'Mila', motiv: C.motivi[0].id, boja: 'bela', bojaImena: 'crvena' });
  // Svi fontovi imena se učitaju odmah (model se pravi bez čekanja kad se promeni font u redu).
  var FURL = C.fonts || { pacifico: C.font };
  var fids = Object.keys(FURL);
  Promise.all(fids.map(function (id) {
    return new Promise(function (ok, no) { window.opentype.load(FURL[id], function (e, f) { if (e) no(e); else ok(f); }); });
  }).concat([
    fetch(C.defs, { credentials: 'same-origin' }).then(function (r) { return r.text(); }),
    C.okretni ? fetch(C.okretni, { credentials: 'same-origin' }).then(function (x) { return x.arrayBuffer(); }).then(G.readOkretni) : null,
  ])).then(function (r) {
    okretni = r[fids.length + 1];
    font = {}; fids.forEach(function (id, i) { font[id] = r[i]; }); defs = r[fids.length];
    var sel = C.sel || {};
    if (sel.order && loadItem(sel.order, sel.item)) {
      if (sel.download === '3mf') {
        export3MF();
        var so = findOrder(sel.order);
        if (so) mark(collect(so, sel.item, false).keys);
      }
    } else {
      rebuild();
    }
  }).catch(function (e) {
    $('[data-gen-err]').textContent = 'Font ili motivi nisu učitani: ' + (e && e.message ? e.message : e);
  });
})();
