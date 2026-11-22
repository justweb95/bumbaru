/* Bumbaru 3D → Generator modela: strana u administraciji (porudžbine, imena, mere, pregled, izvoz). */
(function () {
  'use strict';
  var C = window.BUMBARU_GEN, G = window.BumbaruGen;
  var root = document.querySelector('[data-gen]');
  if (!C || !G || !root) return;
  var $ = function (s) { return root.querySelector(s); };
  var font = null, defs = null, items = [], src = null, timer = 0;

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
    r = r || { ime: '', motiv: C.motivi[0].id, boja: 'bela', bojaImena: 'crvena' };
    var mot = '<select class="bg-mot">' + C.motivi.map(function (m) { return '<option value="' + esc(m.id) + '"' + (m.id === r.motiv ? ' selected' : '') + '>' + esc(m.label) + '</option>'; }).join('') + '</select>';
    var tr = document.createElement('tr');
    tr.innerHTML = '<td class="bg-n"></td><td><input type="text" class="bg-ime" maxlength="12" value="' + esc(r.ime) + '" placeholder="npr. Mila" /></td><td>' + mot + '</td>'
      + '<td>' + colorSelect('bg-boja', r.boja, false) + '</td><td>' + colorSelect('bg-bi', r.bojaImena || 'ista', true) + '</td>'
      + '<td><button type="button" class="button-link bg-del" aria-label="Ukloni">✕</button></td>';
    tbody.appendChild(tr);
    number();
  }
  function number() { Array.prototype.forEach.call(tbody.rows, function (tr, i) { tr.cells[0].textContent = i + 1; }); }
  function rows() {
    return Array.prototype.map.call(tbody.rows, function (tr) {
      return { ime: tr.querySelector('.bg-ime').value.trim(), motiv: tr.querySelector('.bg-mot').value, boja: tr.querySelector('.bg-boja').value, bojaImena: tr.querySelector('.bg-bi').value };
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
    addRow(last ? { ime: '', motiv: last.querySelector('.bg-mot').value, boja: last.querySelector('.bg-boja').value, bojaImena: last.querySelector('.bg-bi').value } : null);
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
  function renderOrders() {
    var box = $('[data-gen-orders]');
    if (!C.orders.length) { box.innerHTML = '<p>Još nema porudžbina sa ukrasima.</p>'; return; }
    box.innerHTML = C.orders.map(function (o) {
      return '<div class="bg-order"><div class="bg-order-h"><strong>#' + esc(o.broj) + '</strong> ' + esc(o.kupac) + ' <span>' + esc(o.datum) + ' · ' + esc(o.status) + '</span></div>'
        + o.items.map(function (it) {
          var names = it.rows.map(function (r) { return r.ime; }).join(', ');
          return '<button type="button" class="bg-item" data-o="' + o.id + '" data-i="' + it.id + '"><b>' + esc(it.naziv) + (it.kom > 1 ? ' × ' + it.kom : '') + '</b><small>' + esc(names) + '</small></button>';
        }).join('')
        + (o.items.length > 1 || o.items[0].rows.length > 1 ? '<button type="button" class="button button-small bg-all" data-o="' + o.id + '">Cela porudžbina u jedan 3MF</button>' : '')
        + '</div>';
    }).join('');
  }
  function findOrder(id) { return C.orders.filter(function (o) { return o.id === id; })[0]; }
  function loadItem(oid, iid) {
    var o = findOrder(oid);
    if (!o) return false;
    var list = [];
    o.items.forEach(function (it) {
      if (iid && it.id !== iid) return;
      it.rows.forEach(function (r) { list.push(r); });
    });
    if (!list.length) return false;
    root.querySelectorAll('.bg-item,.bg-all').forEach(function (b) { b.classList.toggle('is-on', +b.getAttribute('data-o') === oid && (!iid || +b.getAttribute('data-i') === iid) && (!!iid === b.classList.contains('bg-item'))); });
    setRows(list, '#' + o.broj + (o.kupac ? ' (' + o.kupac + ')' : ''));
    src = { order: o.broj };
    return true;
  }
  $('[data-gen-orders]').addEventListener('click', function (e) {
    var b = e.target.closest('.bg-item,.bg-all');
    if (b) loadItem(+b.getAttribute('data-o'), b.classList.contains('bg-item') ? +b.getAttribute('data-i') : 0);
  });

  /* ——— Pravljenje modela ——— */
  function schedule() { clearTimeout(timer); timer = setTimeout(rebuild, 250); }
  function rebuild() {
    if (!font || !defs) return;
    var err = $('[data-gen-err]'), list = rows(), d = dims(), model = G.models[$('[data-gen-model]').value] || G.models.ukras;
    err.textContent = '';
    items = [];
    try {
      list.forEach(function (r) {
        var bi = r.bojaImena === 'ista' ? null : color(r.bojaImena);
        items.push(model.build({
          motiv: r.motiv, ime: r.ime, precnik: d.precnik, debljina: d.debljina, izdignuto: d.izdignuto, vrh: d.vrh,
          boja: color(r.boja), bojaImena: bi,
        }, { defs: defs, font: font, fitName: window.BumbaruOrnament.fitName }));
      });
      G.arrange(items, 6, d.ploca);
    } catch (ex) {
      err.textContent = 'Greška: ' + ex.message;
      items = [];
    }
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
  Promise.all([
    new Promise(function (ok, no) { window.opentype.load(C.font, function (e, f) { if (e) no(e); else ok(f); }); }),
    fetch(C.defs, { credentials: 'same-origin' }).then(function (r) { return r.text(); }),
  ]).then(function (r) {
    font = r[0]; defs = r[1];
    var sel = C.sel || {};
    if (sel.order && loadItem(sel.order, sel.item)) {
      if (sel.download === '3mf') export3MF();
    } else {
      rebuild();
    }
  }).catch(function (e) {
    $('[data-gen-err]').textContent = 'Font ili motivi nisu učitani: ' + (e && e.message ? e.message : e);
  });
})();
