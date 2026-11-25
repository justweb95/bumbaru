/* Bumbaru 3D: utisci kupaca (ocena, komentar, do 2 fotografije, odgovori). Šalje se AJAX-om, objavljuje posle odobrenja. */
(function () {
  'use strict';
  var box = document.querySelector('[data-rv]');
  if (!box) return;
  var $ = function (s, r) { return (r || box).querySelector(s); };
  var form = $('[data-rv-form]'), msg = $('[data-rv-msg]'), files = $('[data-rv-files]'), prev = $('[data-rv-prev]');
  var MAX = 2, MB = 8, izabrane = [];

  function otvori(parent, ime) {
    form.hidden = false;
    form.parent.value = parent || 0;
    var odg = !!parent;
    $('[data-rv-form-title]').textContent = odg ? 'Vaš odgovor' : 'Vaš utisak';
    var to = $('[data-rv-reply-to]');
    to.hidden = !odg;
    to.textContent = odg ? 'Odgovarate na utisak: ' + ime : '';
    $('[data-rv-rate]').hidden = odg;
    $('[data-rv-pics-field]').hidden = odg;
    $('[data-rv-mail]').hidden = odg;
    $('[data-rv-tlabel]').textContent = odg ? 'Odgovor' : 'Komentar';
    var tgt = odg ? box.querySelector('#utisak-' + parent) : null;
    if (tgt) tgt.appendChild(form); else $('.rv-list').appendChild(form);
    msg.textContent = '';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(function () { (odg ? form.tekst : form.ime).focus({ preventScroll: true }); }, 350);
  }

  box.addEventListener('click', function (e) {
    var t = e.target;
    if (t.closest('[data-rv-new]')) otvori(0);
    var r = t.closest('[data-rv-reply]');
    if (r) otvori(r.getAttribute('data-rv-reply'), r.getAttribute('data-rv-name'));
    if (t.closest('[data-rv-cancel]')) { form.hidden = true; }
    if (t.closest('[data-rv-all]')) {
      box.querySelectorAll('[data-rv-more]').forEach(function (a) { a.hidden = false; });
      t.closest('[data-rv-all]').remove();
    }
    var p = t.closest('[data-rv-full]'), dlg = $('[data-rv-dlg]');
    if (p && dlg && dlg.showModal) { $('[data-rv-dlg-img]').src = p.getAttribute('data-rv-full'); dlg.showModal(); }
    var x = t.closest('[data-rv-x]');
    if (x) { izabrane.splice(+x.getAttribute('data-rv-x'), 1); prikazi(); }
  });
  var dlg = $('[data-rv-dlg]');
  if (dlg) dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

  function prikazi() {
    prev.innerHTML = '';
    var dug = files.closest('.rv-file');
    if (dug) dug.hidden = izabrane.length >= MAX;
    izabrane.forEach(function (f, i) {
      var d = document.createElement('div');
      d.className = 'rv-th';
      var img = document.createElement('img');
      img.src = URL.createObjectURL(f);
      img.alt = '';
      d.appendChild(img);
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'icon-btn'; b.setAttribute('data-rv-x', i); b.setAttribute('aria-label', 'Ukloni fotografiju'); b.textContent = '×';
      d.appendChild(b);
      prev.appendChild(d);
    });
  }
  files.addEventListener('change', function () {
    var poruka = '';
    Array.prototype.forEach.call(files.files, function (f) {
      if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { poruka = 'Dozvoljene su samo fotografije (JPG, PNG, WebP).'; return; }
      if (f.size > MB * 1024 * 1024) { poruka = 'Fotografija je veća od ' + MB + ' MB.'; return; }
      if (izabrane.length >= MAX) { poruka = 'Najviše ' + MAX + ' fotografije.'; return; }
      izabrane.push(f);
    });
    files.value = '';
    msg.textContent = poruka;
    prikazi();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var odg = +form.parent.value > 0;
    if (!odg && !form.querySelector('input[name="ocena"]:checked')) { msg.textContent = 'Izaberite ocenu (zvezdice).'; return; }
    if (form.ime.value.trim().length < 2) { msg.textContent = 'Upišite ime.'; form.ime.focus(); return; }
    if (form.tekst.value.trim().length < (odg ? 2 : 10)) { msg.textContent = odg ? 'Upišite odgovor.' : 'Napišite bar rečenicu o proizvodu.'; form.tekst.focus(); return; }
    var fd = new FormData();
    fd.append('action', 'bumbaru_utisak');
    fd.append('t', 'bumbaru');
    fd.append('pid', box.getAttribute('data-rv'));
    ['parent', 'ime', 'email', 'tekst', 'web'].forEach(function (k) { fd.append(k, form[k].value); });
    var oc = form.querySelector('input[name="ocena"]:checked');
    if (oc && !odg) fd.append('ocena', oc.value);
    if (!odg) izabrane.forEach(function (f) { fd.append('slike[]', f, f.name); });
    var btn = form.querySelector('[type="submit"]');
    btn.disabled = true;
    msg.textContent = 'Šaljem…';
    fetch(box.getAttribute('data-rv-url'), { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        msg.textContent = j.msg || 'Slanje nije uspelo.';
        if (j.ok) {
          form.reset(); izabrane = []; prikazi();
          Array.prototype.forEach.call(form.querySelectorAll('.field, fieldset, .rv-actions'), function (el) { el.hidden = true; });
          var nova = document.createElement('button');
          nova.type = 'button'; nova.className = 'btn btn-paper'; nova.textContent = 'Zatvori';
          nova.addEventListener('click', function () {
            form.hidden = true; nova.remove();
            Array.prototype.forEach.call(form.querySelectorAll('.field, fieldset, .rv-actions'), function (el) { el.hidden = false; });
            msg.textContent = '';
          });
          form.appendChild(nova);
        }
      })
      .catch(function () { msg.textContent = 'Slanje nije uspelo. Proverite internet i pokušajte ponovo.'; })
      .then(function () { btn.disabled = false; });
  });
})();
