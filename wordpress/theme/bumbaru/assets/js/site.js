/* Bumbaru tema (nova verzija): meni i padajući „Katalog”, zaglavlje koje se na računaru krije pri skrolu,
   mali zalepljeni pregled proizvoda na telefonu dok se bira motiv/ime/boja,
   animacije pri pojavljivanju, pitanja koja se lagano otvaraju, odbrojavanje akcije,
   „Nedavno ste gledali”, filter kategorije, pretraga, kviz „Nađi poklon”, upit „Po meri”,
   brzo dodavanje u korpu i prozor za porudžbinu preko Messengera. */
(function () {
  'use strict';
  var CFG = window.BUMBARU || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var html = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var din = function (n) { return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' din'; };
  var plural = function (n) { return n % 10 === 1 && n % 100 !== 11 ? 'proizvod' : 'proizvoda'; };
  var norm = function (s) { return String(s || '').toLowerCase().replace(/đ/g, 'dj').normalize('NFD').replace(/[̀-ͯ]/g, ''); };
  var mUrl = function (text, ref) {
    var q = [];
    if (text) q.push('text=' + encodeURIComponent(text));
    if (ref) q.push('ref=' + encodeURIComponent(ref));
    return 'https://m.me/' + (CFG.messengerPage || '') + (q.length ? '?' + q.join('&') : '');
  };
  var PROD = (function () { try { var el = document.getElementById('bu-products'); return el ? JSON.parse(el.textContent || '{}') : {}; } catch (e) { return {}; } })();

  // ——— Meni na telefonu ———
  var menu = $('[data-menu]');
  var openBtn = $('[data-menu-open]');
  var closeMenu = function () {
    if (!menu) return;
    menu.classList.remove('open');
    if (openBtn) { openBtn.setAttribute('aria-expanded', 'false'); openBtn.focus(); }
    document.body.style.overflow = '';
  };
  if (openBtn && menu) {
    openBtn.addEventListener('click', function () {
      menu.classList.add('open'); openBtn.setAttribute('aria-expanded', 'true'); document.body.style.overflow = 'hidden';
      var c = $('[data-menu-close]', menu); if (c) c.focus();
    });
    var mc = $('[data-menu-close]', menu); if (mc) mc.addEventListener('click', closeMenu);
    menu.addEventListener('click', function (e) { if (e.target === menu) closeMenu(); });
  }

  // ——— Padajući meni „Katalog”: mišem se otvara prelaskom (klik vodi na ceo katalog),
  //     na ekranu na dodir dodirom, a sa tastature strelicom nadole ili razmakom ———
  var mBtn = $('[data-mega-btn]'), mega = $('[data-mega]'), mBg = $('[data-mega-bg]');
  var mt = 0;
  var otvoren = function () { return !!mBtn && mBtn.getAttribute('aria-expanded') === 'true'; };
  var postavi = function (v) {
    if (!mBtn || !mega || v === otvoren()) return;
    mBtn.setAttribute('aria-expanded', String(v));
    mega.hidden = !v; if (mBg) mBg.hidden = !v;
    if (v) { mega.classList.remove('in'); void mega.offsetWidth; mega.classList.add('in'); }
  };
  var mis = window.matchMedia('(hover: hover) and (pointer: fine)');
  if (mBtn && mega) {
    mBtn.addEventListener('click', function (e) {
      if (mis.matches || e.detail === 0) return; // miš i Enter: obična veza na katalog
      e.preventDefault(); clearTimeout(mt); postavi(!otvoren());
    });
    mBtn.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowDown' && e.key !== ' ') return;
      e.preventDefault(); clearTimeout(mt); postavi(true);
      var a = $('a', mega); if (a) a.focus();
    });
    mBtn.addEventListener('mouseenter', function () { if (!mis.matches) return; clearTimeout(mt); mt = setTimeout(function () { postavi(true); }, 110); });
    [mBtn, mega].forEach(function (el) { el.addEventListener('mouseleave', function () { if (!mis.matches) return; clearTimeout(mt); mt = setTimeout(function () { postavi(false); }, 260); }); });
    mega.addEventListener('mouseenter', function () { clearTimeout(mt); });
    mega.addEventListener('focusout', function (e) { var n = e.relatedTarget; if (n && !mega.contains(n) && n !== mBtn) postavi(false); });
  }
  if (mBg) mBg.addEventListener('click', function () { postavi(false); });

  // ——— Zaglavlje: senka posle skrola; skrolom nadole se sakrije, nagore vrati (i na telefonu) ———
  var header = $('[data-header]') || $('.header');
  var dodir = window.matchMedia('(hover: none), (pointer: coarse)');
  // Na telefonu dok se kuca (otvorena tastatura) zaglavlje miruje: tastatura sama pomera stranu.
  var kuca = function () { var a = document.activeElement; return dodir.matches && !!a && (a.tagName === 'TEXTAREA' || (a.tagName === 'INPUT' && !/^(checkbox|radio|button|submit|range|color|file)$/i.test(a.type))); };
  var poslednji = window.scrollY;
  var sakrij = function (v) { if (header) header.classList.toggle('h-skriven', v); html.classList.toggle('hdr-skriven', v); };
  var onScroll = function () {
    var y = window.scrollY;
    if (header) header.classList.toggle('scrolled', y > 40);
    if (y < 160 || otvoren() || (header && header.querySelector(':focus-visible'))) { sakrij(false); poslednji = y; return; }
    if (kuca()) { poslednji = y; return; }
    var d = y - poslednji;
    if (Math.abs(d) < (dodir.matches ? 12 : 8)) return;
    sakrij(d > 0);
    poslednji = y;
  };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  if (header) header.addEventListener('focusin', function (e) { try { if (e.target.matches(':focus-visible')) sakrij(false); } catch (x) { sakrij(false); } });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (otvoren()) { postavi(false); if (mBtn) mBtn.focus(); }
    else if (menu && menu.classList.contains('open')) closeMenu();
  });

  // ——— Telefon: dok kupac bira motiv, ime ili boju, pregled proizvoda ostaje gore u traci (oko 25% ekrana) ———
  (function () {
    var g = $('[data-gallery][data-mini]');
    var st = g && $('.stage', g);
    var info = $('.tpl-product .info');
    if (!st || !info) return;
    var mob = window.matchMedia('(max-width: 960px)');
    var ph = document.createElement('div');
    ph.className = 'mini-ph'; ph.hidden = true;
    st.parentNode.insertBefore(ph, st);
    var band = document.createElement('div');
    band.className = 'mini-band'; band.hidden = true; band.setAttribute('aria-hidden', 'true');
    band.innerHTML = '<button type="button" class="mini-x" aria-label="Sakrij pregled" tabindex="-1">×</button><button type="button" class="mini-up" tabindex="-1">Veći pregled ↑</button>';
    document.body.appendChild(band);
    var on = false, ugasen = false, raf = 0, visina = 0, vracam = 0;
    var postavi = function (v) {
      if (v === on) return;
      on = v;
      if (v) { ph.style.height = st.offsetHeight + 'px'; ph.hidden = false; }
      else { ph.hidden = true; ph.style.height = ''; }
      g.classList.toggle('is-mini', v); band.hidden = !v; html.classList.toggle('ima-mini', v);
    };
    var proveri = function () {
      raf = 0;
      if (!mob.matches || ugasen || vracam) { postavi(false); return; }
      // Vrh trake prati stanje zaglavlja (ne trenutni položaj dok klizi), pa traka klizi zajedno sa njim.
      var hb = header && !header.classList.contains('h-skriven') ? header.offsetHeight : 0;
      // Visina ekrana bez tastature: pamti se najveća, da traka ne skače kad se tastatura otvori.
      var vv = window.visualViewport ? window.visualViewport.height : window.innerHeight;
      visina = Math.max(visina, window.innerHeight);
      var tast = kuca() && vv < visina * 0.8;
      var vis = tast ? Math.round(Math.max(110, Math.min(vv * 0.3, 180))) : Math.round(Math.min(visina * 0.25, 260));
      html.style.setProperty('--mini-top', hb + 'px');
      html.style.setProperty('--mini-h', vis + 'px');
      var a = (on ? ph : st).getBoundingClientRect();
      var ib = info.getBoundingClientRect().bottom;
      postavi(a.bottom < hb + 40 && ib > hb + vis + 140);
    };
    var zakazi = function () { if (!raf) raf = requestAnimationFrame(proveri); };
    window.addEventListener('scroll', zakazi, { passive: true });
    window.addEventListener('resize', zakazi);
    if (window.visualViewport) window.visualViewport.addEventListener('resize', zakazi);
    document.addEventListener('focusin', zakazi); document.addEventListener('focusout', function () { setTimeout(zakazi, 60); });
    // Zaglavlje klizi bez skrola (posle 8 px) – osveži položaj trake i kad se ono pomeri.
    if (header && window.MutationObserver) new MutationObserver(zakazi).observe(header, { attributes: true, attributeFilter: ['class'] });
    if (mob.addEventListener) mob.addEventListener('change', zakazi);
    $('.mini-x', band).addEventListener('click', function () { ugasen = true; postavi(false); });
    // ↑ vraća na veliki pregled; dok strana klizi gore traka se ne pali ponovo (inače trepne).
    var gore = function () {
      var y = g.getBoundingClientRect().top + window.scrollY - (header ? header.offsetHeight : 0) - 12;
      clearTimeout(vracam); vracam = setTimeout(function () { vracam = 0; zakazi(); }, reduce.matches ? 50 : 1200);
      postavi(false);
      window.scrollTo({ top: Math.max(0, y), behavior: reduce.matches ? 'auto' : 'smooth' });
    };
    $('.mini-up', band).addEventListener('click', gore);
    // Dodir na sličicu u traci ne radi ništa (kupac ostaje kod izbora).
    st.addEventListener('click', function (e) { if (on) { e.preventDefault(); e.stopPropagation(); } }, true);
    zakazi();
  })();

  // ——— Pojavljivanje pri skrolu; beskonačne animacije rade samo dok su na ekranu ———
  (function () {
    var els = $$('.reveal, .fx-reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    els.forEach(function (e) { io.observe(e); });
    var io2 = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { en.target.classList.toggle('anim-off', !en.isIntersecting); });
    }, { rootMargin: '120px 0px' });
    $$('.sway, .fx-float').forEach(function (e) { io2.observe(e); });
  })();

  // ——— Česta pitanja: lagano otvaranje i zatvaranje (bez JavaScript-a radi kao obično <details>) ———
  (function () {
    var EASE = 'cubic-bezier(.22,.8,.24,1)';
    var trajanje = function (px) { return Math.round(Math.min(460, Math.max(240, 200 + Math.abs(px) * 0.7))); };
    document.addEventListener('click', function (e) {
      var sum = e.target.closest && e.target.closest('.acc summary');
      if (!sum || reduce.matches) return;
      var d = sum.parentElement;
      var wrap = null;
      for (var i = 0; i < d.children.length; i++) { if (d.children[i].classList.contains('ans-wrap')) wrap = d.children[i]; }
      if (!wrap || typeof wrap.animate !== 'function') return;
      e.preventDefault();
      var closing = d.classList.contains('closing');
      var start = d.open ? wrap.getBoundingClientRect().height : 0;
      if (wrap._anim) wrap._anim.cancel();
      wrap._anim = null;
      if (!d.open || closing) {
        d.classList.remove('closing');
        d.open = true;
        var end = wrap.scrollHeight;
        var a = wrap.animate([{ height: start + 'px', opacity: start ? 1 : 0 }, { height: end + 'px', opacity: 1 }], { duration: trajanje(end - start), easing: EASE });
        var ans = wrap.querySelector('.ans');
        if (ans) ans.animate([{ transform: 'translateY(-8px)' }, { transform: 'none' }], { duration: trajanje(end - start), easing: EASE });
        a.onfinish = function () { wrap._anim = null; };
        wrap._anim = a;
      } else {
        d.classList.add('closing');
        var b = wrap.animate([{ height: start + 'px', opacity: 1 }, { height: '0px', opacity: 0 }], { duration: trajanje(start), easing: EASE, fill: 'forwards' });
        b.onfinish = function () { d.open = false; d.classList.remove('closing'); b.cancel(); wrap._anim = null; };
        wrap._anim = b;
      }
    });
  })();

  // ——— Odbrojavanje do kraja akcije (posle kraja <html> dobija klasu akcija-kraj) ———
  (function () {
    var odb = $$('[data-odb]');
    if (!odb.length) return;
    var tik = function () {
      odb.forEach(function (el) {
        var ms = Date.parse(el.getAttribute('data-odb') || '') - Date.now();
        if (!(ms > 0)) { html.classList.add('akcija-kraj'); return; }
        var d = Math.floor(ms / 864e5), h = Math.floor((ms % 864e5) / 36e5), m = Math.floor((ms % 36e5) / 6e4);
        var set = function (s, v, w) { var b = el.querySelector(s); if (b) b.textContent = (w && v < 10 ? '0' : '') + v; };
        set('[data-d]', d, false); set('[data-h]', h, true); set('[data-m]', m, true);
      });
    };
    tik(); setInterval(tik, 20000);
  })();

  // ——— Poruka za Messenger (proizvodi bez cene, upit „Po meri”, upit za firmu) ———
  var dlg = $('#order-dialog');
  if (dlg) {
    var d$ = function (s) { return dlg.querySelector(s); };
    var ta = d$('[data-od-text]'), go = d$('[data-od-go]'), status = d$('[data-od-status]');
    var current = '';
    var sync = function () { go.href = mUrl(ta.value, current); };
    var copy = function () {
      var done = function () { status.textContent = 'Poruka je kopirana.'; };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        return navigator.clipboard.writeText(ta.value).then(done, function () { ta.select(); try { document.execCommand('copy'); } catch (e) {} done(); });
      }
      ta.select(); try { document.execCommand('copy'); } catch (e) {}
      done();
      return Promise.resolve();
    };
    var open = function () { if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.setAttribute('open', ''); };
    var slika = function (src) {
      var img = d$('[data-od-img]'), ph = d$('[data-od-ph]');
      if (src) { img.src = src; img.hidden = false; ph.hidden = true; } else { img.hidden = true; ph.hidden = false; }
    };
    // p = { t: naziv, u: link, i: slika }; bez p podaci se uzimaju iz spiska proizvoda
    window.bumbaruOrder = function (id, lines, qty, p) {
      p = p || PROD[id] || {};
      current = id;
      ta.value = ['Zdravo, želim da poručim:', (p.t || '') + ' (šifra ' + id + ')']
        .concat(lines || [])
        .concat(['Količina: ' + (qty || 1), '', p.u || location.href]).join('\n');
      d$('[data-od-name]').textContent = p.t || '';
      d$('[data-od-id]').textContent = id;
      slika(p.i);
      status.textContent = '';
      sync(); open();
    };
    window.bumbaruMessage = function (text, ref, opts) {
      opts = opts || {};
      current = ref || '';
      ta.value = text;
      d$('[data-od-name]').textContent = opts.naslov || 'Upit za izradu po meri';
      d$('[data-od-id]').textContent = opts.sifra || ref || 'po meri';
      slika(opts.slika);
      status.textContent = '';
      sync(); open();
    };
    ta.addEventListener('input', sync);
    go.addEventListener('click', function () { copy(); status.textContent = 'Poruka je kopirana. Otvaramo Messenger…'; });
    d$('[data-od-copy]').addEventListener('click', copy);
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    document.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-order]');
      if (b && !b.closest('form[data-order-form]')) {
        e.preventDefault();
        window.bumbaruOrder(b.getAttribute('data-order'), [], 1, { t: b.getAttribute('data-order-title'), u: b.getAttribute('data-order-url'), i: b.getAttribute('data-order-img') });
      }
    });
  }

  // ——— Kratko obaveštenje (dodato u korpu, greška) ———
  var toast = $('[data-toast]'), toastT;
  window.bumbaruToast = function (h, kind) {
    if (!toast) return;
    toast.innerHTML = h; toast.hidden = false;
    toast.classList.toggle('err', kind === 'err');
    toast.classList.remove('out');
    clearTimeout(toastT);
    toastT = setTimeout(function () { toast.classList.add('out'); setTimeout(function () { toast.hidden = true; }, 300); }, kind === 'err' ? 6000 : 4600);
  };

  // ——— Broj u korpi (WooCommerce fragmenti) ———
  var setCount = function (frag, count) {
    if (frag) {
      Object.keys(frag).forEach(function (sel) {
        if (sel.indexOf('cart-count') < 0 && sel.indexOf('korpa-n') < 0) return;
        $$(sel).forEach(function (el) {
          var t = document.createElement('div'); t.innerHTML = frag[sel];
          var n = t.firstElementChild; if (n) el.replaceWith(n);
        });
      });
    }
    if (typeof count === 'number') {
      $$('[data-cart-count]').forEach(function (el) { el.textContent = String(count); el.hidden = count === 0; });
      $$('.korpa-btn').forEach(function (b) { b.setAttribute('aria-label', count ? 'Korpa, ' + count + ' kom' : 'Korpa'); });
    }
    $$('.korpa-btn').forEach(function (b) { b.classList.remove('pop'); void b.offsetWidth; b.classList.add('pop'); });
  };
  window.bumbaruSetCartFragments = setCount;
  // WooCommerce sam javlja promenu korpe (npr. na strani korpe)
  if (window.jQuery) window.jQuery(document.body).on('added_to_cart', function (e, frag) { setCount(frag); });

  // ——— Brzo dodavanje u korpu sa kartice ———
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('[data-add-to-cart]');
    if (!a || !CFG.ajaxAddToCart || !window.fetch) return;
    e.preventDefault();
    if (a.classList.contains('busy')) return;
    a.classList.add('busy');
    var body = new URLSearchParams();
    body.set('product_id', a.getAttribute('data-add-to-cart'));
    body.set('quantity', '1');
    fetch(CFG.ajaxAddToCart.replace('%%endpoint%%', 'add_to_cart'), { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        a.classList.remove('busy');
        if (!res || res.error) { if (res && res.product_url) location.href = res.product_url; return; }
        setCount(res.fragments);
        var card = a.closest('.card');
        var t = card ? (card.querySelector('h3') || {}).textContent : '';
        var img = card ? card.querySelector('.media img') : null;
        window.bumbaruToast((img ? '<img src="' + esc(img.currentSrc || img.src) + '" alt="" width="48" height="48" />' : '') + '<span><strong>Dodato u korpu</strong><span>' + esc(t) + '</span></span><a class="btn btn-sm" href="' + esc(CFG.cartUrl) + '">Korpa</a>' + (CFG.checkoutUrl ? '<a class="btn btn-sm btn-primary" href="' + esc(CFG.checkoutUrl) + '">Plaćanje</a>' : ''));
      })
      .catch(function () { a.classList.remove('busy'); location.href = a.href; });
  });

  // ——— Količina na strani proizvoda ———
  $$('[data-order-form]').forEach(function (form) {
    var kol = form.querySelector('input[name="quantity"][type="number"]');
    if (!kol) return;
    var max = Number(kol.getAttribute('max')) || 50;
    var clamp = function (v) { return Math.max(1, Math.min(max, v || 1)); };
    var inc = form.querySelector('[data-inc]'), dec = form.querySelector('[data-dec]');
    var fire = function () { kol.dispatchEvent(new Event('input', { bubbles: true })); kol.dispatchEvent(new Event('change', { bubbles: true })); };
    if (inc) inc.addEventListener('click', function () { kol.value = String(clamp(+kol.value + 1)); fire(); });
    if (dec) dec.addEventListener('click', function () { kol.value = String(clamp(+kol.value - 1)); fire(); });
  });

  // ——— „Nedavno ste gledali” (pamti se u pregledaču, bez naloga) ———
  (function () {
    var K = 'bumbaru-gledano';
    var citaj = function () { try { var l = JSON.parse(localStorage.getItem(K) || '[]'); return Array.isArray(l) ? l : []; } catch (e) { return []; } };
    $$('[data-ned]').forEach(function (sec) {
      var ovaj = sec.getAttribute('data-ovaj') || '';
      var l = citaj().filter(function (id) { return PROD[id]; });
      if (ovaj && PROD[ovaj]) {
        l = [ovaj].concat(l.filter(function (x) { return x !== ovaj; })).slice(0, 12);
        try { localStorage.setItem(K, JSON.stringify(l)); } catch (e) {}
      }
      var prikaz = l.filter(function (x) { return x !== ovaj; }).slice(0, 8);
      if (!prikaz.length) return;
      var kraj = html.classList.contains('akcija-kraj');
      sec.querySelector('[data-ned-list]').innerHTML = prikaz.map(function (id) {
        var p = PROD[id];
        var a = kraj ? 0 : p.a;
        var e = a ? p.e : p.c;
        var cena = p.c ? (p.o ? 'od ' : '') + (e < p.c ? '<s>' + din(p.c) + '</s> <b class="ak-n">' + din(e) + '</b>' : '<b>' + din(p.c) + '</b>') + (p.k ? ' / kom' : '') : '';
        return '<a class="ned-card" href="' + esc(p.u) + '"><span class="ned-img">' + (p.i ? '<img src="' + esc(p.i) + '" alt="" width="160" height="160" loading="lazy" />' : '<img src="' + esc(CFG.bee || '') + '" alt="" width="160" height="160" class="ned-ph" />') + (a ? '<i class="ned-ak">−' + a + '%</i>' : '') + '</span><span class="ned-t">' + esc(p.t) + '</span>' + (cena ? '<span class="ned-c">' + cena + '</span>' : '') + '</a>';
      }).join('');
      sec.hidden = false;
      var clr = sec.querySelector('[data-ned-clear]');
      if (clr) clr.addEventListener('click', function () { try { localStorage.setItem(K, JSON.stringify(ovaj ? [ovaj] : [])); } catch (e) {} sec.hidden = true; });
    });
  })();

  // ——— Filter u kategoriji (?filter=sv-nikola) ———
  (function () {
    var chips = $$('[data-filter]');
    if (!chips.length) return;
    var cards = $$('[data-grid] .card');
    var count = $('[data-count]');
    var apply = function (id) {
      var n = 0;
      cards.forEach(function (c) { var ok = id === 'sve' || (c.getAttribute('data-tags') || '').split(' ').indexOf(id) > -1; c.hidden = !ok; if (ok) n++; });
      chips.forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-filter') === id)); });
      if (count) count.textContent = n + ' ' + plural(n);
    };
    chips.forEach(function (b) {
      b.addEventListener('click', function () {
        var f = b.getAttribute('data-filter') || 'sve';
        apply(f);
        var u = new URL(location.href);
        if (f === 'sve') u.searchParams.delete('filter'); else u.searchParams.set('filter', f);
        history.replaceState(null, '', u);
      });
    });
    var qf = new URLSearchParams(location.search).get('filter');
    if (qf && chips.some(function (c) { return c.getAttribute('data-filter') === qf; })) apply(qf);
  })();

  // ——— Pretraga (po korenu reči: privesci → priv…, jelke → jelk…) ———
  (function () {
    var input = $('[data-q]'), box = $('[data-results]');
    var items = $$('.s-item');
    if (!input || !box || !items.length) return;
    var koren = function (w) { return w.length > 4 ? w.slice(0, Math.max(4, w.length - 2)) : w; };
    var clear = $('[data-clear]'), title = $('[data-title]'), info = $('[data-info]'), empty = $('[data-empty]');
    var catBtns = $$('[data-cat]');
    var params = new URLSearchParams(location.search);
    var kat = params.get('kategorija') || '';
    if (!catBtns.some(function (b) { return b.getAttribute('data-cat') === kat; })) kat = '';
    input.value = params.get('q') || '';
    var ima = function (el, c) { return (el.getAttribute('data-cats') || '').split(' ').indexOf(c) > -1; };
    var primeni = function (azuriraj) {
      var q = input.value.trim();
      var words = norm(q).split(/\s+/).filter(Boolean).map(koren);
      var nadjeni = items.filter(function (el) { var h = el.getAttribute('data-hay') || ''; return words.every(function (w) { return h.indexOf(w) > -1; }); });
      catBtns.forEach(function (b) {
        var c = b.getAttribute('data-cat') || '';
        var n = c ? nadjeni.filter(function (el) { return ima(el, c); }).length : nadjeni.length;
        var cnt = b.querySelector('[data-cnt]'); if (cnt) cnt.textContent = String(n);
        b.classList.toggle('zero', n === 0 && c !== kat);
        b.setAttribute('aria-pressed', String(c === kat));
      });
      var vidljivi = nadjeni.filter(function (el) { return !kat || ima(el, kat); });
      var skor = function (el) { var t = el.getAttribute('data-title') || ''; return words.length && words.every(function (w) { return t.indexOf(w) > -1; }) ? 0 : 1; };
      items.forEach(function (el) { el.hidden = vidljivi.indexOf(el) < 0; });
      vidljivi.slice().sort(function (a, b) { return skor(a) - skor(b) || Number(a.getAttribute('data-i')) - Number(b.getAttribute('data-i')); }).forEach(function (el) { box.appendChild(el); });
      var n = vidljivi.length;
      if (title) title.textContent = q ? 'Pretraga: ' + q : 'Šta tražite?';
      if (info) {
        info.innerHTML = '';
        if (n) { var b = document.createElement('b'); b.textContent = n + ' ' + plural(n); info.appendChild(b); info.appendChild(document.createTextNode(q || kat ? ' odgovara pretrazi' : ' u katalogu')); }
      }
      if (clear) clear.hidden = !q;
      if (empty) empty.hidden = n > 0;
      box.hidden = n === 0;
      if (!n && empty) {
        var t = empty.querySelector('[data-empty-title]'); if (t) t.textContent = q ? 'Nismo našli „' + q + '”' : 'Nema proizvoda u ovoj kategoriji';
        var ask = empty.querySelector('[data-ask]'); if (ask) ask.href = mUrl('Zdravo, tražim: ' + (q || 'proizvod') + '. Da li to možete da napravite?', 'pretraga');
      }
      if (azuriraj !== false) {
        var u = new URL(location.href);
        if (q) u.searchParams.set('q', q); else u.searchParams.delete('q');
        if (kat) u.searchParams.set('kategorija', kat); else u.searchParams.delete('kategorija');
        history.replaceState(null, '', u);
      }
    };
    var st = 0;
    input.addEventListener('input', function () { clearTimeout(st); st = setTimeout(primeni, 120); });
    var f = $('[data-form]'); if (f) f.addEventListener('submit', function (e) { e.preventDefault(); primeni(); input.blur(); });
    if (clear) clear.addEventListener('click', function () { input.value = ''; primeni(); input.focus(); });
    catBtns.forEach(function (b) { b.addEventListener('click', function () { kat = b.getAttribute('data-cat') || ''; primeni(); }); });
    $$('[data-pop]').forEach(function (b) {
      b.addEventListener('click', function () {
        input.value = b.getAttribute('data-pop') || ''; kat = ''; primeni();
        var bar = $('.s-bar'); if (bar) bar.scrollIntoView({ behavior: reduce.matches ? 'auto' : 'smooth', block: 'start' });
      });
    });
    primeni(false);
  })();

  // ——— Kviz „Nađi poklon” ———
  (function () {
    var root = $('[data-kviz]');
    if (!root) return;
    var qs = $$('[data-q]', root);
    var res = $('[data-res]', root), back = $('[data-back]', root), step = $('[data-step]', root), fill = $('[data-fill]', root), bee = $('[data-bee]', root);
    var odg = {}, k = 0;
    var lab = function (qid) { var q = $('[data-q="' + qid + '"]', root); var b = q && odg[qid] ? $('[data-a="' + odg[qid] + '"]', q) : null; return b ? b.getAttribute('data-label') : ''; };
    var rezultat = function () {
      var za = odg.za, povod = odg.povod, stil = odg.stil, bu = odg.budzet;
      var items = $$('.kv-item', res);
      var ima = function (el, a, v) { return (el.getAttribute('data-' + a) || '').split(' ').indexOf(v) > -1; };
      var skor = function (el) {
        var s = 0;
        s += ima(el, 'za', za) ? 3 : -2;
        s += ima(el, 'povod', povod) ? 2 : -2;
        if (ima(el, 'stil', stil)) s += 2;
        var c = Number(el.getAttribute('data-cena') || 0);
        if (c && bu !== 'svejedno') {
          var ok = bu === 'do-500' ? c <= 550 : bu === 'do-1500' ? c >= 400 && c <= 1600 : c >= 1300;
          s += ok ? 2 : -3;
        } else if (!c) s -= 0.5;
        s += el.getAttribute('data-foto') === '1' ? 1 : -2;
        return s;
      };
      var rang = items.map(function (el) { return { el: el, s: skor(el) }; }).sort(function (a, b) { return b.s - a.s || Number(a.el.getAttribute('data-i')) - Number(b.el.getAttribute('data-i')); });
      var brojG = {};
      var raznovrsno = rang.filter(function (x) { var g = x.el.getAttribute('data-g') || ''; brojG[g] = (brojG[g] || 0) + 1; return brojG[g] <= 2; });
      var izbor = raznovrsno.filter(function (x) { return x.s >= 5; }).slice(0, 6);
      if (izbor.length < 3) izbor = raznovrsno.slice(0, 3);
      items.forEach(function (el) { el.hidden = true; });
      var grid = $('[data-grid]', res);
      izbor.forEach(function (x, j) { x.el.hidden = false; x.el.style.setProperty('--d', String(j)); x.el.classList.remove('in'); void x.el.offsetWidth; x.el.classList.add('in'); grid.appendChild(x.el); });
      $('[data-res-sub]', res).textContent = [lab('za'), lab('povod'), lab('stil'), lab('budzet')].filter(Boolean).join(' · ');
      var a = $('[data-ask]', res);
      if (a) a.href = mUrl('Zdravo! Tražim poklon: ' + [lab('za'), lab('povod'), lab('stil'), 'budžet ' + lab('budzet').toLowerCase()].join(', ') + '. Šta biste mi predložili?', 'kviz');
    };
    var prikazi = function (i) {
      k = i;
      var kraj = i >= qs.length;
      qs.forEach(function (q, j) { q.hidden = j !== i; if (j === i) { q.classList.remove('in'); void q.offsetWidth; q.classList.add('in'); } });
      res.hidden = !kraj;
      back.hidden = i === 0;
      step.textContent = kraj ? 'Gotovo!' : 'Pitanje ' + (i + 1) + ' od ' + qs.length;
      var x = Math.min(i, qs.length) / qs.length;
      fill.style.width = (x * 100) + '%';
      bee.style.left = (x * 100) + '%';
      bee.classList.remove('hop'); void bee.offsetWidth; bee.classList.add('hop');
      if (kraj) rezultat();
      if (i > 0) root.scrollIntoView({ behavior: reduce.matches ? 'auto' : 'smooth', block: 'start' });
    };
    root.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-a]');
      if (!b) return;
      var q = b.closest('[data-q]');
      odg[q.getAttribute('data-q')] = b.getAttribute('data-a');
      $$('[data-a]', q).forEach(function (x) { x.classList.toggle('on', x === b); });
      setTimeout(function () { prikazi(k + 1); }, 260);
    });
    back.addEventListener('click', function () { prikazi(Math.max(0, k - 1)); });
    $('[data-again]', root).addEventListener('click', function () { odg = {}; $$('.kv-opt.on', root).forEach(function (x) { x.classList.remove('on'); }); prikazi(0); });
    prikazi(0);
  })();

  // ——— Upit „Po meri” ———
  var upit = $('[data-upit]');
  if (upit) {
    upit.addEventListener('submit', function (e) {
      e.preventDefault();
      var d = new FormData(upit);
      var sta = String(d.get('sta') || '').trim();
      var err = upit.querySelector('[data-err]');
      var t = upit.querySelector('#u-sta');
      if (!sta) { t.focus(); t.setAttribute('aria-invalid', 'true'); err.textContent = 'Opišite ukratko šta treba da napravimo.'; err.style.color = 'var(--berry)'; return; }
      t.removeAttribute('aria-invalid');
      var lines = ['Zdravo, imam upit za izradu po meri:', sta];
      var add = function (k, label) { var v = String(d.get(k) || '').trim(); if (v) lines.push(label + ': ' + v); };
      add('mere', 'Mere'); add('materijal', 'Materijal'); add('kol', 'Količina'); add('rok', 'Rok');
      lines.push('', 'Fotografiju ili skicu šaljem u sledećoj poruci.');
      if (window.bumbaruMessage) window.bumbaruMessage(lines.join('\n'), 'po-meri');
    });
  }
})();
