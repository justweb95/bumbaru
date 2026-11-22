/* Bumbaru 3D: administracija. Kalkulator cene (isti obračun kao includes/calculator.php),
   redovi koji se dodaju i brišu (boje, filamenti, dodaci), uvoz korak po korak. */
(function ($) {
  'use strict';
  var D = window.BUMBARU3D_ADMIN || {};

  var din = function (n) { return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' din'; };
  var num = function (v) { var n = parseFloat(String(v == null ? '' : v).replace(',', '.')); return isNaN(n) ? 0 : n; };

  function roundPrice(v, mode) {
    v = Math.max(0, v);
    switch (String(mode)) {
      case '10': return Math.ceil(v / 10) * 10;
      case '50': return Math.ceil(v / 50) * 50;
      case '100': return Math.ceil(v / 100) * 100;
      case 'end90': return v <= 90 ? 90 : Math.ceil((v + 10) / 100) * 100 - 10;
      default: return Math.round(v);
    }
  }

  // Isti obračun kao bumbaru_calc() u PHP-u.
  function calc(box) {
    var C = D.costs || {};
    var fil = {}; (D.filaments || []).forEach(function (f) { fil[f.id] = f; });
    var ext = {}; (D.extras || []).forEach(function (e) { ext[e.id] = e; });
    var pieces = Math.max(1, parseInt(box.find('[data-c="pieces"]').val(), 10) || 1);
    var grams = 0, material = 0;
    box.find('[data-row="fil"]').each(function () {
      var id = $(this).find('select').val(), g = num($(this).find('input').val());
      if (!id || !g || !fil[id]) return;
      grams += g; material += g / 1000 * num(fil[id].price_kg);
    });
    material *= 1 + num(C.waste_pct) / 100;
    var hours = num(box.find('[data-c="hours"]').val()) + num(box.find('[data-c="minutes"]').val()) / 60;
    var electricity = hours * num(C.power_w) / 1000 * num(C.kwh);
    var machine = hours * num(C.machine_h);
    material /= pieces; electricity /= pieces; machine /= pieces;
    var labor = num(box.find('[data-c="labor_min"]').val()) / 60 * num(C.labor_h);
    var extras = 0;
    box.find('[data-row="ext"]').each(function () {
      var id = $(this).find('select').val(), q = num($(this).find('input').val());
      if (!id || !ext[id]) return;
      extras += (q || 0) * num(ext[id].price);
    });
    var other = num(box.find('[data-c="other"]').val());
    var cost = material + electricity + machine + labor + extras + other;
    var mRaw = box.find('[data-c="margin"]').val();
    var margin = String(mRaw).trim() === '' ? num(C.margin_pct) : num(mRaw);
    var raw = cost * (1 + margin / 100) * (1 + num(C.vat_pct) / 100);
    var price = cost > 0 ? roundPrice(raw, C.rounding) : 0;
    var net = price / (1 + num(C.vat_pct) / 100);
    return { grams: grams / pieces, material: material, electricity: electricity, machine: machine, labor: labor, extras: extras, other: other, cost: cost, margin: margin, price: price, profit: net - cost };
  }

  function render(box) {
    var r = calc(box);
    var out = box.find('[data-calc-out]');
    var row = function (k, v) { return '<tr><th>' + k + '</th><td>' + din(v) + '</td></tr>'; };
    out.find('[data-breakdown]').html(
      row('Materijal' + (r.grams ? ' (' + (Math.round(r.grams * 10) / 10) + ' g, sa otpadom)' : ''), r.material) +
      row('Struja', r.electricity) + row('Štampač (amortizacija)', r.machine) + row('Rad', r.labor) +
      row('Dodaci', r.extras) + (r.other ? row('Ostalo', r.other) : '') +
      '<tr class="sum"><th>Trošak po komadu</th><td>' + din(r.cost) + '</td></tr>'
    );
    out.find('[data-price]').text(r.price ? din(r.price) : '—');
    out.find('[data-profit]').text(r.price ? 'Zarada po komadu: ' + din(r.profit) + ' (' + Math.round(r.margin) + '% na trošak)' : 'Upišite grame filamenta i vreme štampe.');
    box.data('price', r.price);
  }

  function addRow(box, kind) {
    var tpl = box.find('template[data-tpl="' + kind + '"]').html();
    if (!tpl) return;
    var list = box.find('[data-list="' + kind + '"]');
    var i = Date.now() % 100000;
    list.append(tpl.replace(/__i__/g, i));
  }

  $(function () {
    // Kalkulator (strana „Kalkulator” i proizvod)
    $('[data-calc]').each(function () {
      var box = $(this);
      box.on('input change', 'input, select', function () { render(box); });
      box.on('click', '[data-add]', function (e) { e.preventDefault(); addRow(box, $(this).data('add')); render(box); });
      box.on('click', '[data-del]', function (e) { e.preventDefault(); $(this).closest('[data-row]').remove(); render(box); });
      box.on('click', '[data-apply-price]', function (e) {
        e.preventDefault();
        var p = box.data('price');
        if (!p) return;
        var f = $('#_regular_price');
        if (f.length) { f.val(p).trigger('change'); $(this).text('Upisano: ' + din(p)); }
        var tgt = box.find('[data-c="apply_target"]'); if (tgt.length) tgt.val(p);
      });
      box.on('click', '[data-apply-sale]', function (e) {
        e.preventDefault();
        var pct = num(box.find('[data-c="sale_pct"]').val());
        var reg = num($('#_regular_price').val()) || box.data('price');
        if (!pct || !reg) return;
        var sale = Math.round(reg * (1 - pct / 100) / 10) * 10;
        $('#_sale_price').val(sale).trigger('change');
        $(this).text('Akcijska cena: ' + din(sale));
      });
      render(box);
    });

    // Redovi u tabelama (boje, filamenti, dodaci)
    $(document).on('click', '[data-table-add]', function (e) {
      e.preventDefault();
      var t = $($(this).data('table-add'));
      var tpl = t.find('template').html();
      var i = 'n' + Date.now();
      t.find('tbody').append(tpl.replace(/__i__/g, i));
    });
    $(document).on('click', '[data-table-del]', function (e) {
      e.preventDefault();
      $(this).closest('tr').remove();
    });
    // Boja: kružić se menja dok birate, a ivica se predlaže tamnija.
    $(document).on('input change', '.bu-color-face, .bu-color-edge', function () {
      var tr = $(this).closest('tr');
      var face = tr.find('.bu-color-face').val(), edge = tr.find('.bu-color-edge').val();
      if ($(this).hasClass('bu-color-face') && !tr.data('edge-touched')) {
        edge = darken(face, 0.32); tr.find('.bu-color-edge').val(edge);
      }
      if ($(this).hasClass('bu-color-edge')) tr.data('edge-touched', 1);
      tr.find('.bu-chip-preview').css({ background: 'linear-gradient(135deg,' + face + ' 55%,' + edge + ' 55%)' });
    });
    function darken(hex, a) {
      hex = String(hex).replace('#', '');
      if (hex.length !== 6) return '#000000';
      var o = '#';
      for (var i = 0; i < 3; i++) { var v = Math.round(parseInt(hex.substr(i * 2, 2), 16) * (1 - a)); o += ('0' + v.toString(16)).slice(-2); }
      return o.toUpperCase();
    }

    // Konfigurator na proizvodu: prikaži samo polja za izabrani tip.
    var typeSel = $('#bu_config_type');
    var syncType = function () {
      var t = typeSel.val();
      $('[data-for-type]').each(function () {
        var types = String($(this).data('for-type')).split(' ');
        $(this).toggle(types.indexOf(t) > -1);
      });
    };
    typeSel.on('change', syncType); syncType();

    // Uvoz korak po korak
    $('[data-import-start]').on('click', function (e) {
      e.preventDefault();
      var btn = $(this).prop('disabled', true);
      var log = $('[data-import-log]').empty().show();
      var bar = $('[data-import-bar]').show().find('span');
      var step = function (s) {
        $.post(ajaxurl, { action: 'bumbaru_import_step', step: s, _ajax_nonce: btn.data('nonce') })
          .done(function (r) {
            if (!r || !r.success) { log.append('<li class="err">' + ((r && r.data) || 'Greška.') + '</li>'); btn.prop('disabled', false); return; }
            log.append('<li>' + $('<div>').text(r.data.msg).html() + '</li>');
            log.scrollTop(log[0].scrollHeight);
            var done = r.data.next ? r.data.next : r.data.total;
            bar.css('width', Math.round(done / r.data.total * 100) + '%');
            if (r.data.next) step(r.data.next);
            else { btn.prop('disabled', false).text('Uvezi ponovo'); $('[data-import-done]').show(); }
          })
          .fail(function (x) { log.append('<li class="err">Greška na serveru (' + x.status + '). Pokušajte ponovo; već uvezeni proizvodi se neće duplirati.</li>'); btn.prop('disabled', false); });
      };
      step(0);
    });
  });
})(jQuery);
