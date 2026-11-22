/* Bumbaru 3D – generator modela za štampu (3MF i STL).
 *
 * Od 2D crteža (SVG putanje, slova fonta) pravi 3D delove: svaki deo je jedna boja (jedan filament).
 * Modeli se dodaju u BumbaruGen.models (vidi „ukras” na dnu): model dobija parametre i vraća listu
 * predmeta, a svaki predmet listu delova { naziv, boja, hex, oblik (2D), z0, z1 }.
 *
 * Radi u pregledaču (administracija) i u Node-u (provera). Potrebni su ClipperLib, earcut i opentype.
 */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory(require('clipper-lib'), require('earcut'));
  } else {
    root.BumbaruGen = factory(root.ClipperLib, root.earcut);
  }
})(typeof self !== 'undefined' ? self : this, function (ClipperLib, earcut) {
  'use strict';

  var SC = 1000; // Clipper radi sa celim brojevima: 1 jedinica crteža = 1000
  var TOL = 0.12; // tačnost krivih (jedinice crteža)

  /* ——— SVG putanja → izlomljene linije ——— */

  function parsePath(d) {
    var toks = String(d).match(/[a-zA-Z]|[-+]?(?:\d*\.\d+|\d+\.?)(?:[eE][-+]?\d+)?/g) || [];
    var i = 0, cmd = '', x = 0, y = 0, sx = 0, sy = 0, cx = 0, cy = 0, qx = 0, qy = 0, prev = '';
    var subs = [], cur = null;
    function num() { return parseFloat(toks[i++]); }
    function isNum() { return i < toks.length && !/^[a-zA-Z]$/.test(toks[i]); }
    function start(px, py) { cur = { pts: [[px, py]], closed: false }; subs.push(cur); }
    function line(px, py) { if (!cur) start(x, y); cur.pts.push([px, py]); }
    function cubic(x1, y1, x2, y2, x3, y3) {
      var len = Math.hypot(x1 - x, y1 - y) + Math.hypot(x2 - x1, y2 - y1) + Math.hypot(x3 - x2, y3 - y2);
      var n = Math.max(2, Math.ceil(Math.sqrt(len / TOL) * 0.9));
      for (var k = 1; k <= n; k++) {
        var t = k / n, u = 1 - t;
        line(u * u * u * x + 3 * u * u * t * x1 + 3 * u * t * t * x2 + t * t * t * x3,
          u * u * u * y + 3 * u * u * t * y1 + 3 * u * t * t * y2 + t * t * t * y3);
      }
    }
    function quad(x1, y1, x2, y2) {
      var len = Math.hypot(x1 - x, y1 - y) + Math.hypot(x2 - x1, y2 - y1);
      var n = Math.max(2, Math.ceil(Math.sqrt(len / TOL) * 0.8));
      for (var k = 1; k <= n; k++) {
        var t = k / n, u = 1 - t;
        line(u * u * x + 2 * u * t * x1 + t * t * x2, u * u * y + 2 * u * t * y1 + t * t * y2);
      }
    }
    function arc(rx, ry, phi, fa, fs, x2, y2) {
      if (rx === 0 || ry === 0) { line(x2, y2); return; }
      rx = Math.abs(rx); ry = Math.abs(ry);
      var p = phi * Math.PI / 180, cp = Math.cos(p), sp = Math.sin(p);
      var dx = (x - x2) / 2, dy = (y - y2) / 2;
      var x1p = cp * dx + sp * dy, y1p = -sp * dx + cp * dy;
      var lam = (x1p * x1p) / (rx * rx) + (y1p * y1p) / (ry * ry);
      if (lam > 1) { rx *= Math.sqrt(lam); ry *= Math.sqrt(lam); }
      var num2 = rx * rx * ry * ry - rx * rx * y1p * y1p - ry * ry * x1p * x1p;
      var den = rx * rx * y1p * y1p + ry * ry * x1p * x1p;
      var co = Math.sqrt(Math.max(0, num2 / den)) * (fa === fs ? -1 : 1);
      var cxp = co * rx * y1p / ry, cyp = -co * ry * x1p / rx;
      var ccx = cp * cxp - sp * cyp + (x + x2) / 2, ccy = sp * cxp + cp * cyp + (y + y2) / 2;
      var ang = function (ux, uy, vx, vy) {
        var a = Math.atan2(ux * vy - uy * vx, ux * vx + uy * vy);
        return a;
      };
      var t1 = ang(1, 0, (x1p - cxp) / rx, (y1p - cyp) / ry);
      var dt = ang((x1p - cxp) / rx, (y1p - cyp) / ry, (-x1p - cxp) / rx, (-y1p - cyp) / ry);
      if (!fs && dt > 0) dt -= 2 * Math.PI;
      if (fs && dt < 0) dt += 2 * Math.PI;
      var r = Math.max(rx, ry);
      var n = Math.max(4, Math.ceil(Math.abs(dt) / (2 * Math.acos(Math.max(-1, 1 - TOL / r)))));
      for (var k = 1; k <= n; k++) {
        var t = t1 + dt * k / n;
        line(ccx + rx * Math.cos(t) * cp - ry * Math.sin(t) * sp, ccy + rx * Math.cos(t) * sp + ry * Math.sin(t) * cp);
      }
    }
    while (i < toks.length) {
      if (/^[a-zA-Z]$/.test(toks[i])) cmd = toks[i++];
      var rel = cmd === cmd.toLowerCase(), C = cmd.toUpperCase();
      var ox = rel ? x : 0, oy = rel ? y : 0;
      if (C === 'Z') {
        if (cur) { cur.closed = true; x = sx; y = sy; cur = null; }
        prev = 'Z';
        continue;
      }
      if (!isNum()) { i++; continue; }
      if (C === 'M') {
        x = ox + num(); y = oy + num(); sx = x; sy = y; start(x, y);
        cmd = rel ? 'l' : 'L';
      } else if (C === 'L') { x = ox + num(); y = oy + num(); line(x, y); }
      else if (C === 'H') { x = ox + num(); line(x, y); }
      else if (C === 'V') { y = (rel ? y : 0) + num(); line(x, y); }
      else if (C === 'C') {
        var a1 = ox + num(), b1 = oy + num(), a2 = ox + num(), b2 = oy + num(), a3 = ox + num(), b3 = oy + num();
        cubic(a1, b1, a2, b2, a3, b3); cx = a2; cy = b2; x = a3; y = b3;
      } else if (C === 'S') {
        var r1 = /[CS]/.test(prev) ? 2 * x - cx : x, s1 = /[CS]/.test(prev) ? 2 * y - cy : y;
        var a2s = ox + num(), b2s = oy + num(), a3s = ox + num(), b3s = oy + num();
        cubic(r1, s1, a2s, b2s, a3s, b3s); cx = a2s; cy = b2s; x = a3s; y = b3s;
      } else if (C === 'Q') {
        var q1 = ox + num(), w1 = oy + num(), q2 = ox + num(), w2 = oy + num();
        quad(q1, w1, q2, w2); qx = q1; qy = w1; x = q2; y = w2;
      } else if (C === 'T') {
        var t1x = /[QT]/.test(prev) ? 2 * x - qx : x, t1y = /[QT]/.test(prev) ? 2 * y - qy : y;
        var t2x = ox + num(), t2y = oy + num();
        quad(t1x, t1y, t2x, t2y); qx = t1x; qy = t1y; x = t2x; y = t2y;
      } else if (C === 'A') {
        var rx = num(), ry = num(), ph = num(), fa = num(), fs = num(), ex = ox + num(), ey = oy + num();
        arc(rx, ry, ph, fa ? 1 : 0, fs ? 1 : 0, ex, ey); x = ex; y = ey;
      } else { i++; }
      prev = C;
    }
    return subs;
  }

  /** opentype.js Path → izlomljene linije (iste kao parsePath). */
  function fontPathToSubs(path) {
    var d = '';
    path.commands.forEach(function (c) {
      if (c.type === 'M' || c.type === 'L') d += c.type + c.x + ' ' + c.y + ' ';
      else if (c.type === 'C') d += 'C' + c.x1 + ' ' + c.y1 + ' ' + c.x2 + ' ' + c.y2 + ' ' + c.x + ' ' + c.y + ' ';
      else if (c.type === 'Q') d += 'Q' + c.x1 + ' ' + c.y1 + ' ' + c.x + ' ' + c.y + ' ';
      else if (c.type === 'Z') d += 'Z ';
    });
    return parsePath(d);
  }

  /* ——— 2D oblici (Clipper) ——— */

  var CL = ClipperLib;
  function toPath(pts) { return pts.map(function (p) { return { X: Math.round(p[0] * SC), Y: Math.round(p[1] * SC) }; }); }
  function bool(type, subj, clip, rule) {
    var c = new CL.Clipper();
    c.AddPaths(subj, CL.PolyType.ptSubject, true);
    if (clip && clip.length) c.AddPaths(clip, CL.PolyType.ptClip, true);
    var out = new CL.Paths();
    var fr = rule === 'evenodd' ? CL.PolyFillType.pftEvenOdd : CL.PolyFillType.pftNonZero;
    c.Execute(type, out, fr, CL.PolyFillType.pftNonZero);
    return out;
  }
  /** Popunjena površina putanje (fill-rule nonzero ili evenodd). */
  function fill(subs, rule) {
    var paths = subs.filter(function (s) { return s.pts.length > 2; }).map(function (s) { return toPath(s.pts); });
    return bool(CL.ClipType.ctUnion, paths, null, rule || 'nonzero');
  }
  function union(a, b) { return bool(CL.ClipType.ctUnion, a.concat(b || []), null, 'nonzero'); }
  function diff(a, b) { return bool(CL.ClipType.ctDifference, a, b); }
  function inter(a, b) { return bool(CL.ClipType.ctIntersection, a, b); }
  /** Proširi oblik za r (kao debljina linije oko slova). */
  function grow(paths, r) {
    if (!r) return paths;
    var o = new CL.ClipperOffset(2, TOL * SC / 2);
    o.AddPaths(paths, CL.JoinType.jtRound, CL.EndType.etClosedPolygon);
    var out = new CL.Paths();
    o.Execute(out, r * SC);
    return out;
  }
  /** Linija debljine w sa okruglim krajevima (stroke). */
  function stroke(subs, w) {
    var o = new CL.ClipperOffset(2, TOL * SC / 2);
    subs.forEach(function (s) {
      if (s.pts.length < 2) return;
      o.AddPath(toPath(s.pts), CL.JoinType.jtRound, s.closed ? CL.EndType.etClosedLine : CL.EndType.etOpenRound);
    });
    var out = new CL.Paths();
    o.Execute(out, w / 2 * SC);
    return union(out);
  }
  function circle(cx, cy, r) {
    var n = Math.max(48, Math.ceil(2 * Math.PI * r / 0.6)), pts = [];
    for (var k = 0; k < n; k++) pts.push([cx + r * Math.cos(2 * Math.PI * k / n), cy + r * Math.sin(2 * Math.PI * k / n)]);
    return [toPath(pts)];
  }

  /** Clipper putanje → poligoni sa rupama [{ outer:[[x,y]], holes:[[[x,y]]] }], u jedinicama crteža. */
  function exPolys(paths) {
    var c = new CL.Clipper();
    c.AddPaths(CL.Clipper.CleanPolygons(paths, SC * 0.01), CL.PolyType.ptSubject, true);
    var tree = new CL.PolyTree();
    c.Execute(CL.ClipType.ctUnion, tree, CL.PolyFillType.pftNonZero, CL.PolyFillType.pftNonZero);
    var res = [];
    var conv = function (p) { return p.map(function (q) { return [q.X / SC, q.Y / SC]; }); };
    (function walk(node) {
      node.Childs().forEach(function (outer) {
        var e = { outer: conv(outer.Contour()), holes: [] };
        outer.Childs().forEach(function (hole) {
          e.holes.push(conv(hole.Contour()));
          walk(hole);
        });
        if (e.outer.length > 2) res.push(e);
      });
    })(tree);
    return res;
  }

  function area(pts) {
    var a = 0;
    for (var k = 0, n = pts.length; k < n; k++) {
      var p = pts[k], q = pts[(k + 1) % n];
      a += p[0] * q[1] - q[0] * p[1];
    }
    return a / 2;
  }

  /* ——— 3D: izvlačenje poligona u telo ——— */

  /**
   * Poligoni (u mm, y nagore) → zatvorena mreža trouglova od z0 do z1.
   * mesh = { v: [x,y,z,...], t: [a,b,c,...] }
   */
  function extrude(polys, z0, z1, mesh) {
    mesh = mesh || { v: [], t: [] };
    polys.forEach(function (e) {
      var rings = [e.outer].concat(e.holes).map(function (r, ri) {
        var a = area(r);
        // spoljna kontura u smeru suprotnom od kazaljke (CCW), rupe u smeru kazaljke
        return (ri === 0 ? a < 0 : a > 0) ? r.slice().reverse() : r;
      });
      var flat = [], holeIdx = [], base = mesh.v.length / 3, count = 0;
      rings.forEach(function (r, ri) {
        if (ri > 0) holeIdx.push(count);
        r.forEach(function (p) { flat.push(p[0], p[1]); count++; });
      });
      for (var k = 0; k < count; k++) mesh.v.push(flat[2 * k], flat[2 * k + 1], z0);
      for (k = 0; k < count; k++) mesh.v.push(flat[2 * k], flat[2 * k + 1], z1);
      var tri = earcut(flat, holeIdx, 2);
      for (k = 0; k < tri.length; k += 3) {
        var a = tri[k], b = tri[k + 1], c = tri[k + 2];
        var s = (flat[2 * b] - flat[2 * a]) * (flat[2 * c + 1] - flat[2 * a + 1]) - (flat[2 * c] - flat[2 * a]) * (flat[2 * b + 1] - flat[2 * a + 1]);
        if (s < 0) { var tmp = b; b = c; c = tmp; }
        mesh.t.push(base + count + a, base + count + b, base + count + c); // gore
        mesh.t.push(base + a, base + c, base + b); // dole
      }
      var off = 0;
      rings.forEach(function (r) {
        var n = r.length;
        for (var j = 0; j < n; j++) {
          var i0 = base + off + j, i1 = base + off + (j + 1) % n;
          mesh.t.push(i0, i1, i1 + count, i0, i1 + count, i0 + count);
        }
        off += n;
      });
    });
    return mesh;
  }

  /* ——— Zapis fajlova ——— */

  var CRC = (function () {
    var t = new Uint32Array(256);
    for (var n = 0; n < 256; n++) {
      var c = n;
      for (var k = 0; k < 8; k++) c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1;
      t[n] = c >>> 0;
    }
    return t;
  })();
  function crc32(u8) {
    var c = 0xFFFFFFFF;
    for (var i = 0; i < u8.length; i++) c = CRC[(c ^ u8[i]) & 0xFF] ^ (c >>> 8);
    return (c ^ 0xFFFFFFFF) >>> 0;
  }
  function utf8(s) {
    if (typeof TextEncoder !== 'undefined') return new TextEncoder().encode(s);
    return Uint8Array.from(Buffer.from(s, 'utf8'));
  }
  /** ZIP bez kompresije: [{ name, data: Uint8Array|string }] → Uint8Array. */
  function zip(files) {
    var parts = [], central = [], offset = 0;
    files.forEach(function (f) {
      var name = utf8(f.name), data = typeof f.data === 'string' ? utf8(f.data) : f.data, crc = crc32(data);
      var h = new DataView(new ArrayBuffer(30));
      h.setUint32(0, 0x04034b50, true); h.setUint16(4, 20, true); h.setUint16(6, 0x0800, true); h.setUint16(8, 0, true);
      h.setUint16(10, 0, true); h.setUint16(12, 0x21, true); h.setUint32(14, crc, true);
      h.setUint32(18, data.length, true); h.setUint32(22, data.length, true); h.setUint16(26, name.length, true); h.setUint16(28, 0, true);
      parts.push(new Uint8Array(h.buffer), name, data);
      var c = new DataView(new ArrayBuffer(46));
      c.setUint32(0, 0x02014b50, true); c.setUint16(4, 20, true); c.setUint16(6, 20, true); c.setUint16(8, 0x0800, true);
      c.setUint16(10, 0, true); c.setUint16(12, 0, true); c.setUint16(14, 0x21, true); c.setUint32(16, crc, true);
      c.setUint32(20, data.length, true); c.setUint32(24, data.length, true); c.setUint16(28, name.length, true);
      c.setUint32(42, offset, true);
      central.push(new Uint8Array(c.buffer), name);
      offset += 30 + name.length + data.length;
    });
    var csize = central.reduce(function (s, p) { return s + p.length; }, 0);
    var e = new DataView(new ArrayBuffer(22));
    e.setUint32(0, 0x06054b50, true); e.setUint16(8, files.length, true); e.setUint16(10, files.length, true);
    e.setUint32(12, csize, true); e.setUint32(16, offset, true);
    var all = parts.concat(central, [new Uint8Array(e.buffer)]);
    var out = new Uint8Array(all.reduce(function (s, p) { return s + p.length; }, 0)), o = 0;
    all.forEach(function (p) { out.set(p, o); o += p.length; });
    return out;
  }

  function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function f4(n) { return (Math.round(n * 10000) / 10000).toString(); }

  /**
   * 3MF: svaki predmet je jedan objekat sa delovima (komponente), svaki deo ima svoju boju.
   * PrusaSlicer, Bambu Studio, OrcaSlicer i Anycubic Slicer ga otvaraju kao predmet sa više delova.
   * items: [{ naziv, x, y, delovi: [{ naziv, boja, hex, mesh }] }]
   */
  function to3MF(items, meta) {
    var mats = [], matIdx = {};
    items.forEach(function (it) {
      it.delovi.forEach(function (d) {
        if (!(d.boja in matIdx)) { matIdx[d.boja] = mats.length; mats.push(d); }
      });
    });
    var id = 2, res = '', build = '';
    res += '<basematerials id="1">' + mats.map(function (m) { return '<base name="' + esc(m.bojaNaziv || m.boja) + '" displaycolor="' + esc((m.hex || '#CCCCCC').toUpperCase()) + 'FF"/>'; }).join('') + '</basematerials>';
    items.forEach(function (it) {
      var comps = [];
      it.delovi.forEach(function (d) {
        var v = d.mesh.v, t = d.mesh.t, s = '<object id="' + id + '" type="model" name="' + esc(d.naziv) + '" pid="1" pindex="' + matIdx[d.boja] + '"><mesh><vertices>';
        for (var k = 0; k < v.length; k += 3) s += '<vertex x="' + f4(v[k]) + '" y="' + f4(v[k + 1]) + '" z="' + f4(v[k + 2]) + '"/>';
        s += '</vertices><triangles>';
        for (k = 0; k < t.length; k += 3) s += '<triangle v1="' + t[k] + '" v2="' + t[k + 1] + '" v3="' + t[k + 2] + '"/>';
        s += '</triangles></mesh></object>';
        res += s;
        comps.push(id++);
      });
      res += '<object id="' + id + '" type="model" name="' + esc(it.naziv) + '"><components>' + comps.map(function (c) { return '<component objectid="' + c + '"/>'; }).join('') + '</components></object>';
      build += '<item objectid="' + id + '" transform="1 0 0 0 1 0 0 0 1 ' + f4(it.x) + ' ' + f4(it.y) + ' 0"/>';
      id++;
    });
    var model = '<?xml version="1.0" encoding="UTF-8"?>\n<model unit="millimeter" xml:lang="sr-Latn" xmlns="http://schemas.microsoft.com/3dmanufacturing/core/2015/02">'
      + '<metadata name="Title">' + esc(meta && meta.naslov || 'Bumbaru') + '</metadata><metadata name="Designer">Bumbaru</metadata>'
      + '<metadata name="Application">Bumbaru 3D generator</metadata>'
      + '<resources>' + res + '</resources><build>' + build + '</build></model>';
    return zip([
      { name: '[Content_Types].xml', data: '<?xml version="1.0" encoding="UTF-8"?>\n<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="model" ContentType="application/vnd.ms-package.3dmanufacturing-3dmodel+xml"/></Types>' },
      { name: '_rels/.rels', data: '<?xml version="1.0" encoding="UTF-8"?>\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Target="/3D/3dmodel.model" Id="rel0" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/></Relationships>' },
      { name: '3D/3dmodel.model', data: model },
    ]);
  }

  /** Binarni STL jedne mreže (pomerene za dx, dy). */
  function toSTL(meshes, dx, dy) {
    var nt = meshes.reduce(function (s, m) { return s + m.t.length / 3; }, 0);
    var buf = new ArrayBuffer(84 + nt * 50), dv = new DataView(buf), o = 84;
    var head = utf8('Bumbaru 3D generator');
    new Uint8Array(buf).set(head.slice(0, 80), 0);
    dv.setUint32(80, nt, true);
    meshes.forEach(function (m) {
      var v = m.v, t = m.t;
      for (var k = 0; k < t.length; k += 3) {
        var a = t[k] * 3, b = t[k + 1] * 3, c = t[k + 2] * 3;
        var ux = v[b] - v[a], uy = v[b + 1] - v[a + 1], uz = v[b + 2] - v[a + 2];
        var wx = v[c] - v[a], wy = v[c + 1] - v[a + 1], wz = v[c + 2] - v[a + 2];
        var nx = uy * wz - uz * wy, ny = uz * wx - ux * wz, nz = ux * wy - uy * wx, l = Math.hypot(nx, ny, nz) || 1;
        dv.setFloat32(o, nx / l, true); dv.setFloat32(o + 4, ny / l, true); dv.setFloat32(o + 8, nz / l, true); o += 12;
        [a, b, c].forEach(function (q) {
          dv.setFloat32(o, v[q] + (dx || 0), true); dv.setFloat32(o + 4, v[q + 1] + (dy || 0), true); dv.setFloat32(o + 8, v[q + 2], true); o += 12;
        });
        dv.setUint16(o, 0, true); o += 2;
      }
    });
    return new Uint8Array(buf);
  }

  /* ——— SVG crteži motiva (data/ornament-defs.svg) ——— */

  function attr(s, n) {
    var m = new RegExp('\\s' + n + '="([^"]*)"').exec(s);
    return m ? m[1] : null;
  }
  /** Delovi jedne grupe <g id="…">: [{ d, fill, rule, sw, clip }] (clip = da li je unutar clip-path grupe). */
  function readGroup(svg, id) {
    var start = svg.indexOf('<g id="' + id + '"');
    if (start < 0) return null;
    var re = /<(\/?)(g|path)\b([^>]*?)(\/?)>/g, depth = 0, clipDepth = -1, out = [];
    re.lastIndex = start;
    var m;
    while ((m = re.exec(svg))) {
      if (m[2] === 'g') {
        if (m[1]) { depth--; if (depth === clipDepth) clipDepth = -1; if (depth === 0) break; }
        else if (!m[4]) { depth++; if (attr(m[3], 'clip-path')) clipDepth = depth - 1; }
      } else if (!m[1]) {
        out.push({ d: attr(m[3], 'd') || '', fill: attr(m[3], 'fill') !== 'none', rule: attr(m[3], 'fill-rule') || 'nonzero', sw: parseFloat(attr(m[3], 'stroke-width') || '0'), clip: clipDepth >= 0 });
      }
    }
    return out;
  }

  /**
   * Delovi imena koji ne dodiruju ostatak (tačka na i, kvačica, odvojeno slovo) dobiju mostić širine w
   * do najbližeg dela ukrasa ili imena. Vraća ime sa mostićima.
   */
  function connectIslands(body, name, w) {
    for (var guard = 0; guard < 16; guard++) {
      var comps = exPolys(union(body, name));
      if (comps.length <= 1) break;
      comps.forEach(function (c) { c.a = Math.abs(area(c.outer)); });
      comps.sort(function (a, b) { return b.a - a.a; });
      var island = null;
      for (var i = 1; i < comps.length && !island; i++) {
        if (inter([toPath(comps[i].outer)], name).length) island = comps[i];
      }
      if (!island) break;
      var best = Infinity, P = null, Q = null;
      comps.forEach(function (c) {
        if (c === island) return;
        island.outer.forEach(function (p) {
          c.outer.forEach(function (q) {
            var d = (p[0] - q[0]) * (p[0] - q[0]) + (p[1] - q[1]) * (p[1] - q[1]);
            if (d < best) { best = d; P = p; Q = q; }
          });
        });
      });
      if (!P) break;
      var L = Math.sqrt(best) || 1, ux = (Q[0] - P[0]) / L, uy = (Q[1] - P[1]) / L, e = w * 0.6;
      name = union(name, stroke([{ pts: [[P[0] - ux * e, P[1] - uy * e], [Q[0] + ux * e, Q[1] + uy * e]], closed: false }], w));
    }
    return name;
  }

  /* ——— Modeli ——— */

  var models = {};

  /**
   * Ukras sa imenom (isti crtež kao pregled na sajtu: obruč 208 jedinica, kukica gore, motiv u krugu, ime preko sredine).
   * p: { motiv, ime, precnik (mm), debljina (mm), izdignuto (mm), vrh (mm: druga boja samo u gornjih vrh mm; 0 = cela debljina),
   *      boja: {id, naziv, hex}, bojaImena: {id, naziv, hex} ili null (= kao ukras) }
   * ctx: { defs (SVG tekst), font (opentype.Font), fitName (funkcija sa sajta) }
   */
  models.ukras = {
    naziv: 'Ukras sa imenom',
    build: function (p, ctx) {
      var CX = 120, CY = 182;
      var parts = readGroup(ctx.defs, 'orn-' + p.motiv);
      if (!parts) throw new Error('Nema motiva: ' + p.motiv);
      var free = [], clipped = [];
      parts.forEach(function (q) {
        var subs = parsePath(q.d), shape;
        if (q.fill) shape = grow(fill(subs, q.rule), 0.5); // na sajtu ivica = boja (stroke 1)
        else shape = stroke(subs, q.sw || 1);
        (q.clip ? clipped : free).push(shape);
      });
      var body = union([].concat.apply([], free));
      if (clipped.length) body = union(body, inter(union([].concat.apply([], clipped)), circle(CX, CY, 96.5)));

      // Ime: slova fonta Pacifico (stroke 1.2 kao na sajtu) i „šinice” koje ga spajaju sa obručem.
      var f = ctx.fitName(p.ime);
      var fs = parseFloat(f.fs), y = parseFloat(f.y);
      var adv = ctx.font.getAdvanceWidth(f.name, fs, { kerning: true });
      var fpath = ctx.font.getPath(f.name, CX - adv / 2, y, fs, { kerning: true });
      var glyphs = fontPathToSubs(fpath);
      var sx = parseFloat(f.sx || '1'), dx = parseFloat(f.dx || '0');
      var tx;
      if (!f.rails) {
        // Bez šinica: slova tačno od -REACH do +REACH (po crtežu slova), da krajevi sigurno uđu u obruč.
        var bb = fpath.getBoundingBox(), REACH = 97.5;
        var k = (2 * REACH - 1.2) / (bb.x2 - bb.x1), cx0 = (bb.x1 + bb.x2) / 2;
        tx = function (x) { return CX + (x - cx0) * k; };
      } else {
        tx = function (x) { return sx * (x - CX) + CX - dx; };
      }
      glyphs.forEach(function (sub) { sub.pts.forEach(function (q) { q[0] = tx(q[0]); }); });
      var name = grow(fill(glyphs, 'nonzero'), 0.6);
      if (f.rails) name = union(name, stroke(parsePath(f.rails), parseFloat(f.railW)));

      var s = p.precnik / 208;
      var mm = function (polys) {
        return polys.map(function (e) {
          var t = function (r) { return r.map(function (q) { return [(q[0] - CX) * s, (CY - q[1]) * s]; }); };
          return { outer: t(e.outer), holes: e.holes.map(t) };
        });
      };
      var T = p.debljina, up = Math.max(0, p.izdignuto || 0), top = Math.max(0, Math.min(T, p.vrh || 0));
      var B = p.boja, N = p.bojaImena;
      var same = !N || N.id === B.id;
      var delovi = [];
      if (same) {
        var all = mm(exPolys(union(body, name)));
        var m1 = extrude(all, 0, T);
        if (up) extrude(mm(exPolys(name)), T, T + up, m1);
        delovi.push({ naziv: 'Ukras (' + B.naziv + ')', boja: B.id, bojaNaziv: B.naziv, hex: B.hex, mesh: m1, oblik: all });
      } else {
        var bodyOnly = mm(exPolys(diff(body, name)));
        var nm = mm(exPolys(name));
        var mb = extrude(bodyOnly, 0, T);
        var zName = top ? T - top : 0;
        if (zName > 0) extrude(nm, 0, zName, mb);
        delovi.push({ naziv: 'Telo (' + B.naziv + ')', boja: B.id, bojaNaziv: B.naziv, hex: B.hex, mesh: mb, oblik: bodyOnly });
        delovi.push({ naziv: 'Ime (' + N.naziv + ')', boja: N.id, bojaNaziv: N.naziv, hex: N.hex, mesh: extrude(nm, zName, T + up), oblik: nm });
      }
      // Tačke na i/j, kvačice (č, ć, š, ž) i slova koja se ne dodiruju: spoje se mostićem sa najbližim delom.
      name = connectIslands(body, name, 2.4);
      // Provera: da li je ime spojeno sa ukrasom (slovo koje ne dodiruje ništa bi otpalo posle štampe).
      var upozorenje = '';
      var nBody = exPolys(body).length, nAll = exPolys(union(body, name)).length;
      if (nAll > nBody) upozorenje = f.name + ': deo imena nije spojen sa ukrasom (' + (nAll - nBody) + ' odvojen' + (nAll - nBody === 1 ? ' deo' : 'a dela') + ').';
      return { naziv: 'Ukras ' + f.name, ime: f.name, upozorenje: upozorenje, sirina: 208 * s, visina: (CY + 104 - 49) * s, delovi: delovi };
    },
  };

  /**
   * Raspored predmeta na ploči štampača (ploca = širina ploče u mm, 0 = bez ograničenja).
   * Koordinate su od ugla ploče, predmeti su na sredini ploče; ako ne staju, slajser ih rasporedi sam (Arrange).
   */
  function arrange(items, gap, ploca) {
    gap = gap || 6;
    var w = Math.max.apply(null, items.map(function (i) { return i.sirina; })) + gap;
    var h = Math.max.apply(null, items.map(function (i) { return i.visina; })) + gap;
    var cols = ploca ? Math.max(1, Math.floor((ploca + gap) / w)) : (items.length <= 4 ? 2 : 3);
    cols = Math.min(cols, Math.max(1, Math.ceil(Math.sqrt(items.length * h / w))), items.length);
    var rows = Math.ceil(items.length / cols), c0 = ploca ? ploca / 2 : 0;
    items.forEach(function (it, k) {
      var c = k % cols, r = Math.floor(k / cols);
      var nc = Math.min(cols, items.length - r * cols);
      it.x = c0 + (c - (nc - 1) / 2) * w;
      it.y = c0 + ((rows - 1) / 2 - r) * h;
    });
    return items;
  }

  /** Mali SVG pregled delova odozgo (u bojama), za stranu generatora. */
  function previewSVG(items) {
    var minx = Infinity, miny = Infinity, maxx = -Infinity, maxy = -Infinity, body = '';
    items.forEach(function (it) {
      it.delovi.forEach(function (d) {
        var path = '';
        d.oblik.forEach(function (e) {
          [e.outer].concat(e.holes).forEach(function (r) {
            path += 'M' + r.map(function (q) {
              var x = q[0] + (it.x || 0), y = -(q[1] + (it.y || 0));
              if (x < minx) minx = x; if (x > maxx) maxx = x; if (y < miny) miny = y; if (y > maxy) maxy = y;
              return x.toFixed(2) + ' ' + y.toFixed(2);
            }).join('L') + 'Z';
          });
        });
        body += '<path d="' + path + '" fill="' + esc(d.hex || '#ccc') + '" fill-rule="evenodd" stroke="#3A2A1C" stroke-width="0.25"/>';
      });
    });
    var pad = 3;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' + [minx - pad, miny - pad, maxx - minx + 2 * pad, maxy - miny + 2 * pad].map(function (n) { return n.toFixed(1); }).join(' ') + '">' + body + '</svg>';
  }

  function stats(items) {
    var t = 0, v = 0;
    items.forEach(function (it) { it.delovi.forEach(function (d) { t += d.mesh.t.length / 3; v += d.mesh.v.length / 3; }); });
    return { trouglova: t, tacaka: v };
  }

  return {
    models: models,
    parsePath: parsePath,
    fontPathToSubs: fontPathToSubs,
    fill: fill, union: union, diff: diff, inter: inter, grow: grow, stroke: stroke, circle: circle,
    exPolys: exPolys, extrude: extrude, arrange: arrange,
    to3MF: to3MF, toSTL: toSTL, zip: zip, previewSVG: previewSVG, stats: stats, readGroup: readGroup,
  };
});
