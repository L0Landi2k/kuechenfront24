/* Küchenfront24 – Online-Aufmaßtabelle mit Bestellhilfe für Metod und Faktum */
(function () {
	'use strict';
	var root = document.getElementById('kf24-am');
	if (!root) return;
	var A = window.KF24A || {}, T = window.KF24T || { products: [] };
	var $ = function (s) { return root.querySelector(s); };
	var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
	var store = { get: function (k, d) { try { var v = localStorage.getItem('kf24_' + k); return v ? JSON.parse(v) : d; } catch (e) { return d; } }, set: function (k, v) { try { localStorage.setItem('kf24_' + k, JSON.stringify(v)); } catch (e) { /* ignore */ } } };
	var TYPES = { tuer: 'Tür', schublade: 'Schublade', klappe: 'Klappe', blende: 'Blende', sonstig: 'Sonstiges' };
	var HINGE = { links: 'links', rechts: 'rechts', oben: 'oben', keine: 'keine' };
	var GRAIN = { aufrecht: '↑ aufrecht', quer: '→ quer' };
	var GRIP = { '0': 'kein Griff', '1': '1 Bohrung', '2': '2 Bohrungen' };
	var GPOS = { mitte: 'mittig horizontal', 'mitte-v': 'mittig vertikal', 'oben-h': 'oben horizontal', 'unten-h': 'unten horizontal', 'oben-v': 'oben vertikal', 'unten-v': 'unten vertikal' };
	var MILL = { keine: 'keine', oben: 'oben', unten: 'unten' };
	var FIN = { '': 'Standard (Klarlack matt)', lack: 'Klarlack matt', oel: 'geölt' };
	function def(r) { // fill new detail fields for rows saved by older versions
		if (r.g == null) r.g = r.t === 'blende' ? 'laengs' : 'aufrecht';
		if (r.hp == null) r.hp = ''; if (r.gr == null) r.gr = '0'; if (r.gd == null) r.gd = '';
		if (r.gp == null) r.gp = 'mitte'; if (r.mf == null) r.mf = 'keine'; if (r.sl == null) r.sl = false;
		return r;
	}
	function det(r) { // one readable line with everything beyond size/hinge
		var o = [r.t === 'blende' ? 'Maserung längs' : 'Maserung ' + (r.g === 'quer' ? 'quer' : 'aufrecht')];
		if (r.a !== 'keine' && +r.b && String(r.hp).trim()) o.push('Bänder ' + String(r.hp).trim() + ' mm (erste v. oben, letzte v. unten)');
		if (r.gr !== '0') o.push('Griff ' + GRIP[r.gr] + (r.gr === '2' && r.gd ? ', ' + r.gd + ' mm' : '') + ', ' + GPOS[r.gp]);
		if (r.mf !== 'keine') o.push('Grifffräsung ' + r.mf);
		if (r.sl && r.t === 'blende') o.push('mit Dichtung');
		return o.join(' · ');
	}
	var typeOf = {}; (A.terms || []).forEach(function (t) { typeOf[t.l] = t.t; });

	var st = store.get('am', null) || { sys: '', body: '', front: '', fin: '', rows: [] };
	st.rows.forEach(def); if (st.fin == null) st.fin = '';
	if (root.dataset.system && !st.rows.length) st.sys = root.dataset.system;
	var q = new URLSearchParams(location.search).get('system');
	if (q && /^(metod|faktum|andere)$/.test(q)) st.sys = q;
	if (!st.rows.length) st.rows = [blank('Tür')];

	function blank(n, note) { var t = typeOf[n] || 'tuer'; return def({ n: n || 'Tür', t: t, w: '', h: '', a: t === 'tuer' ? 'links' : t === 'klappe' ? 'oben' : 'keine', b: t === 'tuer' ? 2 : t === 'klappe' ? 2 : 0, q: 1, r: note || '' }); }
	function save() { store.set('am', st); }

	// front select
	var fsel = $('#am-front');
	var groups = {};
	(T.products || []).forEach(function (p) { (groups[p.g] = groups[p.g] || []).push(p); });
	fsel.innerHTML += Object.keys(groups).map(function (g) { return '<optgroup label="' + esc(g) + '">' + groups[g].map(function (p) { return '<option value="' + p.id + '">' + esc(p.n) + '</option>'; }).join('') + '</optgroup>'; }).join('');

	function termOptions(cur) {
		var html = '', g = '', known = false;
		(A.terms || []).forEach(function (t) {
			if (t.g !== g) { if (g) html += '</optgroup>'; g = t.g; html += '<optgroup label="' + esc(g) + '">'; }
			if (t.l === cur) known = true;
			html += '<option' + (t.l === cur ? ' selected' : '') + '>' + esc(t.l) + '</option>';
		});
		html += '</optgroup><option value="__own"' + (!known ? ' selected' : '') + '>Andere Bezeichnung …</option>';
		return { html: html, known: known };
	}

	function rowHtml(r, i) {
		var to = termOptions(r.n);
		var sel = function (k, map) { return '<select data-k="' + k + '" aria-label="' + k + '">' + Object.keys(map).map(function (v) { return '<option value="' + v + '"' + (r[k] === v ? ' selected' : '') + '>' + map[v] + '</option>'; }).join('') + '</select>'; };
		var bad = function (v) { v = +v; return v && (v < A.max[2] || v > A.max[0]); };
		return '<tr data-i="' + i + '">' +
			'<td class="am-n">' + (i + 1) + '</td>' +
			'<td class="am-name" data-l="Bezeichnung">' + (r.pid ? '<b class="am-sys">' + esc(r.n) + '</b><small class="am-sys-t">' + (r.pid.indexOf('faktum') === 0 ? 'IKEA Faktum' : 'IKEA Metod') + '</small>' : '<select data-k="nsel" aria-label="Bezeichnung">' + to.html + '</select><input data-k="n" value="' + esc(r.n) + '" placeholder="eigene Bezeichnung"' + (to.known ? ' hidden' : '') + '>') + '<span class="am-print">' + esc(r.n) + '</span></td>' +
			'<td data-l="Art">' + sel('t', TYPES) + '<span class="am-print">' + TYPES[r.t] + '</span></td>' +
			'<td data-l="Breite mm"><input type="number" inputmode="numeric" data-k="w" value="' + esc(r.w) + '" placeholder="mm"' + (bad(r.w) ? ' aria-invalid="true"' : '') + '><span class="am-print">' + esc(r.w) + '</span></td>' +
			'<td data-l="Höhe mm"><input type="number" inputmode="numeric" data-k="h" value="' + esc(r.h) + '" placeholder="mm"' + (bad(r.h) ? ' aria-invalid="true"' : '') + '><span class="am-print">' + esc(r.h) + '</span></td>' +
			'<td data-l="Anschlag">' + sel('a', HINGE) + '<span class="am-print">' + esc(r.a) + '</span></td>' +
			'<td data-l="Topfbänder"><input type="number" min="0" max="5" inputmode="numeric" data-k="b" value="' + esc(r.b) + '"><span class="am-print">' + esc(r.b) + '</span></td>' +
			'<td data-l="Stück"><input type="number" min="1" inputmode="numeric" data-k="q" value="' + esc(r.q) + '"><span class="am-print">' + esc(r.q) + '</span></td>' +
			'<td data-l="Bemerkung"><input data-k="r" value="' + esc(r.r) + '" placeholder="z. B. Spüle links"><span class="am-print">' + esc(r.r) + '</span></td>' +
			'<td data-l="Preis" class="am-price kf24-noprint">' + rowPrice(r) + '</td>' +
			'<td class="kf24-noprint am-act"><button type="button" data-act="det" class="am-det-b' + (r._o ? ' is-on' : '') + '" aria-expanded="' + (r._o ? 'true' : 'false') + '">Details</button><button type="button" data-act="dup" title="Zeile kopieren" aria-label="Zeile kopieren">⧉</button><button type="button" data-act="del" title="Zeile löschen" aria-label="Zeile löschen">×</button></td></tr>' +
			'<tr class="am-det-sum" data-i="' + i + '"' + (r._o ? ' hidden' : '') + '><td></td><td colspan="10"><small>' + esc(det(r)) + '</small></td></tr>' +
			'<tr class="am-det" data-i="' + i + '"' + (r._o ? '' : ' hidden') + '><td></td><td colspan="10"><div class="am-det-g">' +
				(r.t === 'blende' ? '<label>Maserung<span class="am-fix">längs (fest)</span></label>' : '<label>Maserung' + sel('g', GRAIN) + '</label>') +
				(r.a !== 'keine' && +r.b ? '<label>Topfband-Abstände <small>mm, erste von oben, letzte von unten</small><input data-k="hp" value="' + esc(r.hp) + '" placeholder="leer = Standard, z. B. 100 / 100"></label>' : '') +
				'<label>Griffbohrung' + sel('gr', GRIP) + '</label>' +
				(r.gr !== '0' ? '<label>Griffposition' + sel('gp', GPOS) + '</label>' : '') +
				(r.gr === '2' ? '<label>Lochabstand <small>mm, Mitte–Mitte</small><input type="number" inputmode="numeric" data-k="gd" value="' + esc(r.gd) + '" placeholder="z. B. 128"></label>' : '') +
				'<label>Grifffräsung' + sel('mf', MILL) + '</label>' +
				(r.t === 'blende' ? '<label class="am-chk"><input type="checkbox" data-k="sl"' + (r.sl ? ' checked' : '') + '> Aufsteckdichtung</label>' : '') +
			'</div></td></tr>';
	}

	function front() { return (T.products || []).filter(function (x) { return String(x.id) === String(st.front); })[0]; }
	function eur(v) { return v.toLocaleString('de-DE', { style: 'currency', currency: 'EUR' }); }
	function unit(r, p) { // same formula as the server: max(Mindestpreis, m² × Preis + Umfang × Kante + Pauschale) or fixed price
		if (r.pid && p.ov && p.ov[r.pid] > 0) return +p.ov[r.pid];
		var area = Math.max(r.w * r.h / 1e6, (T.s && T.s.ma) || 0), lfm = 2 * (+r.w + +r.h) / 1000;
		return Math.round(Math.max((area * p.p + lfm * (p.e || 0) + (p.pc || 0)) * (p.fx > 0 ? p.fx : 1), p.mp || 0) * 100) / 100;
	}
	function rowPrice(r) {
		var p = front(); if (!p || !(+r.w && +r.h)) return '–';
		if (Math.max(+r.w, +r.h) > A.max[0] || Math.min(+r.w, +r.h) > A.max[1]) return '<small>auf Anfrage</small>';
		return eur(unit(r, p) * Math.max(1, +r.q || 1));
	}
	function cfgUrl(r) {
		var p = (T.products || []).filter(function (x) { return String(x.id) === String(st.front); })[0];
		var base = p ? p.u : (T.products[0] || {}).u;
		if (!base) return '#';
		var u = new URL(base, location.href);
		u.searchParams.set('kf24_type', r.t); if (r.w) u.searchParams.set('kf24_w', r.w); if (r.h) u.searchParams.set('kf24_h', r.h);
		u.searchParams.set('kf24_hinge', r.a); if (+r.b) u.searchParams.set('kf24_hn', r.b); u.searchParams.set('kf24_qty', r.q || 1);
		u.searchParams.set('kf24_label', (r.r ? r.r + ' · ' : '') + r.n);
		u.hash = 'kf24-konfigurator';
		return u.toString();
	}

	function render() {
		$('#am-rows').innerHTML = st.rows.map(rowHtml).join('');
		root.querySelectorAll('input[name=am_sys]').forEach(function (r) { r.checked = r.value === (st.sys || 'andere'); });
		$('#am-body').value = st.body || ''; fsel.value = st.front || ''; $('#am-fin').value = st.fin || '';
		helper(); sum(); links();
		if (typeof sheetEl !== 'undefined' && sheetEl && sheetEl.innerHTML) sheet(false);
	}
	function links() { root.querySelectorAll('#am-rows tr').forEach(function (tr) { var c = tr.querySelector('.am-price'); if (c) c.innerHTML = rowPrice(st.rows[+tr.dataset.i]); }); }
	function sum() {
		var n = 0, area = 0, miss = 0, big = 0, long = false, s = T.s || {}, p = front(), goods = 0;
		st.rows.forEach(function (r) {
			var q = Math.max(1, +r.q || 1); n += q;
			if (!(+r.w && +r.h)) { miss++; return; }
			if (Math.max(+r.w, +r.h) > A.max[0] || Math.min(+r.w, +r.h) > A.max[1]) { big++; return; }
			area += r.w * r.h / 1e6 * q; if (p) goods += unit(r, p) * q; if (Math.max(+r.w, +r.h) > s.len_l) long = true;
		});
		var html = '';
		if (p && area) {
			var weight = area * p.kg, min = goods < s.min_t ? s.min_f : 0, len = long && weight < s.len_w ? s.len_f : 0;
			var ship = weight < 10 ? s.t1 : weight < 20 ? s.t2 : weight < 50 ? s.t3 : s.t4;
			html = '<table class="am-calc"><tr><td>' + n + (n === 1 ? ' Front' : ' Fronten') + ' · ' + esc(p.n.replace(/\s+–\s+individuell konfigurierbar$/, '')) + '</td><td>' + eur(goods) + '</td></tr>' +
				(min ? '<tr><td>Mindermengenzuschlag (unter ' + eur(s.min_t) + ')</td><td>' + eur(min) + '</td></tr>' : '') +
				(len ? '<tr><td>Längenzuschlag</td><td>' + eur(len) + '</td></tr>' : '') +
				'<tr><td>Versand Deutschland, ca. ' + Math.round(weight) + ' kg</td><td>' + eur(ship) + '</td></tr>' +
				'<tr class="am-total"><td>Richtpreis gesamt</td><td>' + eur(goods + min + len + ship) + '</td></tr></table><p class="kf24-hint">inkl. MwSt. Topfbänder (' + eur(T.s.hp || 4) + ' je Stück), Grifffräsung, Dichtung und Oberfläche rechnen wir im nächsten Schritt dazu.</p>';
		} else if (!p) html = '<span class="kf24-hint">Wählen Sie oben Ihre Front, dann sehen Sie hier den Richtpreis inklusive Versand.</span>';
		if (miss) html += '<p class="am-miss">' + miss + ' Zeile(n) noch ohne Maße.</p>';
		if (big) html += '<p class="am-miss">' + big + ' Teil(e) größer als ' + A.max[0] + ' × ' + A.max[1] + ' mm: nur auf Anfrage, nicht online bestellbar.</p>';
		$('#am-sum').innerHTML = html;
		var go = $('#am-go'); if (go) { go.disabled = !p; go.textContent = p ? 'Weiter: Material wählen & bestellen' : 'Erst oben die Front wählen'; }
	}
	function catParts(sys) { return (A.catalog || []).filter(function (p) { return p.s === sys; }); }
	function helper() {
		var box = root.querySelector('.kf24-am-helper'), parts = catParts(st.sys);
		box.hidden = !parts.length;
		if (!parts.length) return;
		root.querySelector('.kf24-am-helper-t').textContent = st.sys === 'faktum' ? 'Faktum-Bestellhilfe:' : 'Metod-Bestellhilfe:';
		var g = '', html = '';
		parts.forEach(function (p) {
			if (p.g !== g) { g = p.g; html += '<h4>' + esc(g) + '</h4>'; }
			html += '<button type="button" data-part="' + esc(p.id) + '" title="' + esc(p.w + ' × ' + p.h + ' mm' + (p.x ? ' – ' + p.x : '')) + '">+ ' + esc(p.n) + '</button>';
		});
		root.querySelector('.kf24-am-chips').innerHTML = html + '<p class="kf24-hint">Die Maße sind schon eingetragen (' + (st.sys === 'faktum' ? 'so wie Faktum-Fronten tatsächlich gefertigt werden' : 'Metod-Rastermaß minus 3 mm') + '). Bitte trotzdem kurz an der alten Front nachmessen.</p>';
	}

	root.addEventListener('click', function (e) {
		var b = e.target.closest('button'); if (!b) return;
		if (b.dataset.part != null) {
			var p = (A.catalog || []).filter(function (x) { return x.id === b.dataset.part; })[0]; if (!p) return;
			var r = def({ n: p.n, t: p.t, w: p.w, h: p.h, a: p.a, b: p.b, q: 1, r: '', pid: p.id, hp: p.hr && p.hr.length === p.b ? p.hr.join(' / ') : '' });
			if (st.rows.length === 1 && !st.rows[0].w && !st.rows[0].h && !st.rows[0].r) st.rows = [];
			st.rows.push(r); save(); render();
			var last = root.querySelector('#am-rows tr:last-child input[data-k=q]'); if (last) last.focus();
			return;
		}
		var tr = b.closest('tr');
		if (b.dataset.act === 'del' && tr) { st.rows.splice(+tr.dataset.i, 1); if (!st.rows.length) st.rows.push(blank('Tür')); save(); render(); }
		if (b.dataset.act === 'det' && tr) { var rr = st.rows[+tr.dataset.i]; rr._o = !rr._o; save(); render(); return; }
		if (b.dataset.act === 'dup' && tr) { st.rows.splice(+tr.dataset.i + 1, 0, Object.assign({}, st.rows[+tr.dataset.i], { _o: false })); save(); render(); }
	});
	root.addEventListener('change', function (e) {
		var t = e.target;
		if (t.name === 'am_sys') { st.sys = t.value; save(); render(); return; }
		if (t.id === 'am-body') { st.body = t.value; save(); render(); return; }
		if (t.id === 'am-front') { st.front = t.value; save(); sum(); links(); return; }
		if (t.id === 'am-fin') { st.fin = t.value; save(); render(); return; }
		var tr = t.closest('#am-rows tr'); if (!tr) return;
		var r = st.rows[+tr.dataset.i];
		if (t.dataset.k === 'nsel') {
			if (t.value === '__own') { r.n = ''; save(); render(); var own = root.querySelector('#am-rows tr[data-i="' + tr.dataset.i + '"] input[data-k=n]'); if (own) own.focus(); return; }
			r.n = t.value; r.t = typeOf[t.value] || r.t;
			r.a = r.t === 'tuer' ? (r.a === 'rechts' ? 'rechts' : 'links') : r.t === 'klappe' ? 'oben' : 'keine';
			r.b = r.t === 'tuer' || r.t === 'klappe' ? (+r.b || 2) : 0;
			save(); render(); return;
		}
		if (t.dataset.k === 't') { r.t = t.value; r.g = r.t === 'blende' ? 'laengs' : (r.g === 'quer' ? 'quer' : 'aufrecht'); save(); render(); return; }
		if (['a', 'g', 'gr', 'gp', 'mf'].indexOf(t.dataset.k) >= 0) { r[t.dataset.k] = t.value; save(); render(); return; }
		if (t.dataset.k === 'sl') { r.sl = t.checked; save(); render(); }
	});
	root.addEventListener('input', function (e) {
		var t = e.target, tr = t.closest('#am-rows tr'); if (!tr || !t.dataset.k || t.tagName === 'SELECT' || t.type === 'checkbox') return;
		var r = st.rows[+tr.dataset.i];
		r[t.dataset.k] = t.type === 'number' ? (t.value === '' ? '' : +t.value) : t.value;
		if (r.pid && (t.dataset.k === 'w' || t.dataset.k === 'h')) { var cp = (A.catalog || []).filter(function (x) { return x.id === r.pid; })[0]; if (cp && (+r.w !== cp.w || +r.h !== cp.h)) { r.pid = ''; } }
		if (t.type === 'number' && (t.dataset.k === 'w' || t.dataset.k === 'h')) { var v = +t.value; t.setAttribute('aria-invalid', v && (v < A.max[2] || v > A.max[0]) ? 'true' : 'false'); }
		save(); sum(); links();
	});
	$('#am-add').addEventListener('click', function () { var last = st.rows[st.rows.length - 1]; st.rows.push(blank(last ? last.n : 'Tür')); save(); render(); });
	$('#am-reset').addEventListener('click', function () { st.rows = [blank('Tür')]; save(); render(); });
	/* ---------- Druckblatt: eigenes, sauberes A4-Querformat ---------- */
	var sheetEl = document.getElementById('am-sheet'); document.body.appendChild(sheetEl);
	function box(on, t) { return '<span class="ps-box' + (on ? ' on' : '') + '"></span>' + t; }
	function sheet(blank) {
		var p = front(), sys = st.sys || 'andere', rows = blank ? [] : st.rows.filter(function (r) { return r.w || r.h || r.r || r.pid; });
		var line = function (v) { return v ? '<b>' + esc(v) + '</b>' : '<span class="ps-line"></span>'; };
		var h = '<div class="ps-head"><div><b class="ps-t">Aufmaß Küchenfronten</b><span>Küchenfront24 · Schreinerei Senft · Tel. ' + esc(A.phone || '') + ' · kuechenfront24.de</span></div><div class="ps-meta">' +
			'<span>Name ' + line('') + '</span><span>Telefon / E-Mail ' + line('') + '</span><span>Datum ' + line('') + '</span></div></div>' +
			'<div class="ps-opts"><span><i>Küche</i> ' + box(!blank && sys === 'metod', 'IKEA Metod') + box(!blank && sys === 'faktum', 'IKEA Faktum') + box(!blank && sys === 'andere', 'andere') + '</span>' +
			'<span><i>Korpus-Wandstärke</i> ' + box(!blank && st.body === '16 mm', '16') + box(!blank && st.body === '18 mm', '18') + box(!blank && st.body === '19 mm', '19 mm') + '</span>' +
			'<span><i>Oberfläche (Holz)</i> ' + box(!blank && st.fin !== 'oel' && !!st.fin, 'Klarlack matt') + box(!blank && st.fin === 'oel', 'geölt') + '</span>' +
			'<span class="ps-wide"><i>Front / Material</i> ' + line(!blank && p ? p.n.replace(/\s+–\s+individuell konfigurierbar$/, '') : '') + ' <i>Dekor / Holzart / Farbe</i> ' + line('') + '</span></div>' +
			'<table class="ps-t"><thead><tr><th>Pos.</th><th>Bezeichnung / Raum</th><th>Art</th><th>Breite<br>mm</th><th>Höhe<br>mm</th><th>Stück</th><th>Maserung<br>↑ / →</th><th>Anschlag<br>L / R / O / –</th><th>Topf&shy;bänder<br>Anzahl</th><th>Topfband-Abstände mm<br><small>erste v. oben, letzte v. unten</small></th><th>Griff<br><small>Bohrungen, Lochabstand, Lage</small></th><th>Griff&shy;fräsung</th><th>Bemerkung</th></tr></thead><tbody>';
		var n = blank ? 14 : rows.length + 4;
		for (var i = 0; i < n; i++) {
			var r = rows[i];
			if (!r) { h += '<tr><td>' + (i + 1) + '</td>' + new Array(13).join('<td></td>') + '</tr>'; continue; }
			var hinge = r.a === 'keine' || !+r.b ? '–' : { links: 'L', rechts: 'R', oben: 'O' }[r.a];
			h += '<tr><td>' + (i + 1) + '</td><td>' + esc(r.n) + (r.pid ? ' <small>(' + (r.pid.indexOf('faktum') === 0 ? 'Faktum' : 'Metod') + ')</small>' : '') + '</td><td>' + TYPES[r.t] + '</td><td class="ps-num">' + esc(r.w) + '</td><td class="ps-num">' + esc(r.h) + '</td><td class="ps-num">' + esc(r.q || 1) + '</td>' +
				'<td>' + (r.t === 'blende' ? 'längs' : r.g === 'quer' ? '→ quer' : '↑ aufrecht') + '</td><td>' + hinge + '</td><td class="ps-num">' + (hinge === '–' ? '–' : esc(r.b)) + '</td><td>' + (hinge === '–' ? '–' : esc(r.hp || 'Standard')) + '</td>' +
				'<td>' + (r.gr === '0' ? '–' : GRIP[r.gr] + (r.gr === '2' && r.gd ? ', ' + esc(r.gd) + ' mm' : '') + ', ' + GPOS[r.gp]) + '</td><td>' + (r.mf === 'keine' ? '–' : r.mf) + '</td><td>' + esc(r.r) + (r.sl && r.t === 'blende' ? (r.r ? ' · ' : '') + 'Dichtung' : '') + '</td></tr>';
		}
		h += '</tbody></table><div class="ps-foot"><p><b>So messen Sie:</b> Breite × Höhe der alten Front in mm (nicht den Korpus). Maserung ↑ = Holzfasern senkrecht, → = waagrecht. Anschlag: L = Bänder links, R = rechts, O = oben (Klappe), – = keine Bohrung. Topfband-Abstand = Mitte Bohrung ab Oberkante, das letzte Band ab Unterkante.</p>' +
			'<p>Online bestellbar bis ' + A.max[0] + ' × ' + A.max[1] + ' mm, größer auf Anfrage. Maßanfertigung – bitte alle Angaben sorgfältig prüfen.</p></div>';
		sheetEl.innerHTML = h; sheetEl.classList.toggle('is-blank', !!blank);
	}
	var blankMode = false;
	$('#am-print').addEventListener('click', function () { blankMode = false; window.print(); });
	$('#am-blank').addEventListener('click', function () { blankMode = true; sheet(true); window.print(); setTimeout(function () { blankMode = false; sheet(false); }, 800); });
	window.addEventListener('beforeprint', function () { sheet(blankMode); });
	$('#am-go').addEventListener('click', function () {
		var p = front(); if (!p) { fsel.focus(); return; }
		var rows = st.rows.filter(function (r) { return +r.w && +r.h && Math.max(+r.w, +r.h) <= A.max[0] && Math.min(+r.w, +r.h) <= A.max[1]; });
		if (!rows.length) { $('#am-sum').insertAdjacentHTML('beforeend', '<p class="am-miss">Bitte zuerst Maße eintragen.</p>'); return; }
		try { localStorage.setItem('kf24_handover', JSON.stringify(rows.map(function (r) { return Object.assign({}, r, { body: parseInt(st.body, 10) || '', fin: st.fin }); }))); } catch (e) { /* ignore */ }
		location.href = p.u + '#kf24-konfigurator';
	});

	var form = $('#am-send');
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var msg = form.querySelector('.kf24-am-msg'), btn = form.querySelector('button[type=submit]');
		var fd = new FormData(form);
		fd.append('action', 'kf24_aufmass'); fd.append('nonce', A.nonce);
		fd.append('rows', JSON.stringify(st.rows.map(function (r) { return Object.assign({}, r, { t: TYPES[r.t], d: det(r) }); })));
		fd.append('fin', FIN[st.fin || '']);
		fd.append('sys', { metod: 'IKEA Metod', faktum: 'IKEA Faktum', andere: 'andere Küche' }[st.sys] || '');
		fd.append('body', st.body || 'unbekannt');
		fd.append('front', fsel.value ? fsel.options[fsel.selectedIndex].text : 'noch offen');
		btn.disabled = true; msg.textContent = 'Wird gesendet …';
		fetch(A.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); })
			.then(function (j) { msg.textContent = (j.data && j.data.msg) || (j.success ? 'Gesendet.' : 'Das hat nicht geklappt.'); msg.className = 'kf24-am-msg ' + (j.success ? 'is-ok' : 'is-bad'); if (j.success) form.reset(); })
			.catch(function () { msg.textContent = 'Keine Verbindung. Bitte später erneut versuchen.'; msg.className = 'kf24-am-msg is-bad'; })
			.then(function () { btn.disabled = false; });
	});
	render(); sheet(false);
})();
