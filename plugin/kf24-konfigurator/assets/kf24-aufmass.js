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
	var typeOf = {}; (A.terms || []).forEach(function (t) { typeOf[t.l] = t.t; });

	var st = store.get('am', null) || { sys: '', body: '', front: '', rows: [] };
	if (root.dataset.system && !st.rows.length) st.sys = root.dataset.system;
	var q = new URLSearchParams(location.search).get('system');
	if (q && /^(metod|faktum|andere)$/.test(q)) st.sys = q;
	if (!st.rows.length) st.rows = [blank('Tür')];

	function blank(n, note) { var t = typeOf[n] || 'tuer'; return { n: n || 'Tür', t: t, w: '', h: '', a: t === 'tuer' ? 'links' : t === 'klappe' ? 'oben' : 'keine', b: t === 'tuer' ? 2 : t === 'klappe' ? 2 : 0, q: 1, r: note || '' }; }
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
			'<td class="am-name" data-l="Bezeichnung"><select data-k="nsel" aria-label="Bezeichnung">' + to.html + '</select><input data-k="n" value="' + esc(r.n) + '" placeholder="eigene Bezeichnung"' + (to.known ? ' hidden' : '') + '><span class="am-print">' + esc(r.n) + '</span></td>' +
			'<td data-l="Art">' + sel('t', TYPES) + '<span class="am-print">' + TYPES[r.t] + '</span></td>' +
			'<td data-l="Breite mm"><input type="number" inputmode="numeric" data-k="w" value="' + esc(r.w) + '" placeholder="mm"' + (bad(r.w) ? ' aria-invalid="true"' : '') + '><span class="am-print">' + esc(r.w) + '</span></td>' +
			'<td data-l="Höhe mm"><input type="number" inputmode="numeric" data-k="h" value="' + esc(r.h) + '" placeholder="mm"' + (bad(r.h) ? ' aria-invalid="true"' : '') + '><span class="am-print">' + esc(r.h) + '</span></td>' +
			'<td data-l="Anschlag">' + sel('a', HINGE) + '<span class="am-print">' + esc(r.a) + '</span></td>' +
			'<td data-l="Topfbänder"><input type="number" min="0" max="5" inputmode="numeric" data-k="b" value="' + esc(r.b) + '"><span class="am-print">' + esc(r.b) + '</span></td>' +
			'<td data-l="Stück"><input type="number" min="1" inputmode="numeric" data-k="q" value="' + esc(r.q) + '"><span class="am-print">' + esc(r.q) + '</span></td>' +
			'<td data-l="Bemerkung"><input data-k="r" value="' + esc(r.r) + '" placeholder="z. B. Spüle links"><span class="am-print">' + esc(r.r) + '</span></td>' +
			'<td data-l="Preis" class="am-price kf24-noprint">' + rowPrice(r) + '</td>' +
			'<td class="kf24-noprint am-act"><button type="button" data-act="dup" title="Zeile kopieren" aria-label="Zeile kopieren">⧉</button><button type="button" data-act="del" title="Zeile löschen" aria-label="Zeile löschen">×</button></td></tr>';
	}

	function front() { return (T.products || []).filter(function (x) { return String(x.id) === String(st.front); })[0]; }
	function eur(v) { return v.toLocaleString('de-DE', { style: 'currency', currency: 'EUR' }); }
	function rowPrice(r) {
		var p = front(); if (!p || !(+r.w && +r.h)) return '–';
		if (Math.max(+r.w, +r.h) > A.max[0] || Math.min(+r.w, +r.h) > A.max[1]) return '<small>auf Anfrage</small>';
		return eur(Math.max(r.w * r.h / 1e6, 0) * p.p * Math.max(1, +r.q || 1));
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
		$('#am-body').value = st.body || ''; fsel.value = st.front || '';
		root.querySelector('.am-p-sys').textContent = { metod: 'IKEA Metod', faktum: 'IKEA Faktum', andere: 'andere' }[st.sys] || '';
		root.querySelector('.am-p-body').textContent = st.body || '';
		helper(); sum(); links();
	}
	function links() { root.querySelectorAll('#am-rows tr').forEach(function (tr) { var c = tr.querySelector('.am-price'); if (c) c.innerHTML = rowPrice(st.rows[+tr.dataset.i]); }); }
	function sum() {
		var n = 0, area = 0, miss = 0, big = 0, long = false, s = T.s || {}, p = front();
		st.rows.forEach(function (r) {
			var q = Math.max(1, +r.q || 1); n += q;
			if (!(+r.w && +r.h)) { miss++; return; }
			if (Math.max(+r.w, +r.h) > A.max[0] || Math.min(+r.w, +r.h) > A.max[1]) { big++; return; }
			area += r.w * r.h / 1e6 * q; if (Math.max(+r.w, +r.h) > s.len_l) long = true;
		});
		var html = '<b>' + n + ' Teile</b> · ' + area.toLocaleString('de-DE', { maximumFractionDigits: 2 }) + ' m²';
		if (p && area) {
			var goods = area * p.p, weight = area * p.kg, min = goods < s.min_t ? s.min_f : 0, len = long && weight < s.len_w ? s.len_f : 0;
			var ship = weight < 10 ? s.t1 : weight < 20 ? s.t2 : weight < 50 ? s.t3 : s.t4;
			html = '<table class="am-calc"><tr><td>' + n + ' Teile · ' + area.toLocaleString('de-DE', { maximumFractionDigits: 2 }) + ' m² × ' + eur(p.p) + '</td><td>' + eur(goods) + '</td></tr>' +
				(min ? '<tr><td>Mindermengenzuschlag (unter ' + eur(s.min_t) + ')</td><td>' + eur(min) + '</td></tr>' : '') +
				(len ? '<tr><td>Längenzuschlag</td><td>' + eur(len) + '</td></tr>' : '') +
				'<tr><td>Versand Deutschland, ca. ' + Math.round(weight) + ' kg</td><td>' + eur(ship) + '</td></tr>' +
				'<tr class="am-total"><td>Richtpreis gesamt</td><td>' + eur(goods + min + len + ship) + '</td></tr></table><p class="kf24-hint">inkl. MwSt. Topfbänder (' + eur(T.s.hp || 4) + ' je Stück), Grifffräsung und Dichtungen wählen Sie im nächsten Schritt.</p>';
		} else if (!p) html += ' · <span class="am-miss">Wählen Sie oben Ihre Front, dann sehen Sie den Preis.</span>';
		if (miss) html += '<p class="am-miss">' + miss + ' Zeile(n) noch ohne Maße.</p>';
		if (big) html += '<p class="am-miss">' + big + ' Teil(e) größer als ' + A.max[0] + ' × ' + A.max[1] + ' mm: nur auf Anfrage, nicht online bestellbar.</p>';
		$('#am-sum').innerHTML = html;
		var go = $('#am-go'); if (go) { go.disabled = !p; go.textContent = p ? 'Weiter: Material wählen & bestellen' : 'Erst oben die Front wählen'; }
	}
	function helper() {
		var box = root.querySelector('.kf24-am-helper'), parts = (A.systems || {})[st.sys];
		box.hidden = !parts;
		if (!parts) return;
		root.querySelector('.kf24-am-helper-t').textContent = st.sys === 'faktum' ? 'Faktum-Bestellhilfe:' : 'Metod-Bestellhilfe:';
		root.querySelector('.kf24-am-chips').innerHTML = parts.map(function (p, i) { return '<button type="button" data-part="' + i + '">+ ' + esc(p[0]) + ' ' + p[1] + '×' + p[2] + '</button>'; }).join('') +
			(st.sys === 'metod' ? '<p class="kf24-hint">Metod-Fronten in Standardmaßen finden Sie fertig im <a href="' + esc((window.location.origin) + '/produkt-kategorie/fronten-fuer-metod/') + '">Metod-Shop</a>. Die Tabelle brauchen Sie für Sondermaße.</p>' : '');
	}

	root.addEventListener('click', function (e) {
		var b = e.target.closest('button'); if (!b) return;
		if (b.dataset.part != null) {
			var p = A.systems[st.sys][+b.dataset.part];
			var r = blank(p[0], (st.sys === 'faktum' ? 'Faktum ' : 'Metod ') + p[1] + '×' + p[2] + ' cm');
			if (st.rows.length === 1 && !st.rows[0].w && !st.rows[0].h && !st.rows[0].r) st.rows = [];
			st.rows.push(r); save(); render();
			var last = root.querySelector('#am-rows tr:last-child input[data-k=w]'); if (last) last.focus();
			return;
		}
		var tr = b.closest('tr');
		if (b.dataset.act === 'del' && tr) { st.rows.splice(+tr.dataset.i, 1); if (!st.rows.length) st.rows.push(blank('Tür')); save(); render(); }
		if (b.dataset.act === 'dup' && tr) { st.rows.splice(+tr.dataset.i + 1, 0, Object.assign({}, st.rows[+tr.dataset.i])); save(); render(); }
	});
	root.addEventListener('change', function (e) {
		var t = e.target;
		if (t.name === 'am_sys') { st.sys = t.value; save(); render(); return; }
		if (t.id === 'am-body') { st.body = t.value; save(); render(); return; }
		if (t.id === 'am-front') { st.front = t.value; save(); sum(); links(); return; }
		var tr = t.closest('#am-rows tr'); if (!tr) return;
		var r = st.rows[+tr.dataset.i];
		if (t.dataset.k === 'nsel') {
			if (t.value === '__own') { r.n = ''; save(); render(); var own = root.querySelector('#am-rows tr[data-i="' + tr.dataset.i + '"] input[data-k=n]'); if (own) own.focus(); return; }
			r.n = t.value; r.t = typeOf[t.value] || r.t;
			r.a = r.t === 'tuer' ? (r.a === 'rechts' ? 'rechts' : 'links') : r.t === 'klappe' ? 'oben' : 'keine';
			r.b = r.t === 'tuer' || r.t === 'klappe' ? (+r.b || 2) : 0;
			save(); render(); return;
		}
		if (t.dataset.k === 't' || t.dataset.k === 'a') { r[t.dataset.k] = t.value; save(); render(); }
	});
	root.addEventListener('input', function (e) {
		var t = e.target, tr = t.closest('#am-rows tr'); if (!tr || !t.dataset.k || t.tagName === 'SELECT') return;
		var r = st.rows[+tr.dataset.i];
		r[t.dataset.k] = t.type === 'number' ? (t.value === '' ? '' : +t.value) : t.value;
		if (t.type === 'number' && (t.dataset.k === 'w' || t.dataset.k === 'h')) { var v = +t.value; t.setAttribute('aria-invalid', v && (v < A.max[2] || v > A.max[0]) ? 'true' : 'false'); }
		save(); sum(); links();
	});
	$('#am-add').addEventListener('click', function () { var last = st.rows[st.rows.length - 1]; st.rows.push(blank(last ? last.n : 'Tür')); save(); render(); });
	$('#am-reset').addEventListener('click', function () { st.rows = [blank('Tür')]; save(); render(); });
	$('#am-print').addEventListener('click', function () { document.body.classList.remove('kf24-am-blank'); window.print(); });
	$('#am-blank').addEventListener('click', function () { document.body.classList.add('kf24-am-blank'); window.print(); setTimeout(function () { document.body.classList.remove('kf24-am-blank'); }, 500); });
	$('#am-go').addEventListener('click', function () {
		var p = front(); if (!p) { fsel.focus(); return; }
		var rows = st.rows.filter(function (r) { return +r.w && +r.h && Math.max(+r.w, +r.h) <= A.max[0] && Math.min(+r.w, +r.h) <= A.max[1]; });
		if (!rows.length) { $('#am-sum').insertAdjacentHTML('beforeend', '<p class="am-miss">Bitte zuerst Maße eintragen.</p>'); return; }
		try { localStorage.setItem('kf24_handover', JSON.stringify(rows)); } catch (e) { /* ignore */ }
		location.href = p.u + '#kf24-konfigurator';
	});

	var form = $('#am-send');
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var msg = form.querySelector('.kf24-am-msg'), btn = form.querySelector('button[type=submit]');
		var fd = new FormData(form);
		fd.append('action', 'kf24_aufmass'); fd.append('nonce', A.nonce);
		fd.append('rows', JSON.stringify(st.rows.map(function (r) { return Object.assign({}, r, { t: TYPES[r.t] }); })));
		fd.append('sys', { metod: 'IKEA Metod', faktum: 'IKEA Faktum', andere: 'andere Küche' }[st.sys] || '');
		fd.append('body', st.body || 'unbekannt');
		fd.append('front', fsel.value ? fsel.options[fsel.selectedIndex].text : 'noch offen');
		btn.disabled = true; msg.textContent = 'Wird gesendet …';
		fetch(A.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); })
			.then(function (j) { msg.textContent = (j.data && j.data.msg) || (j.success ? 'Gesendet.' : 'Das hat nicht geklappt.'); msg.className = 'kf24-am-msg ' + (j.success ? 'is-ok' : 'is-bad'); if (j.success) form.reset(); })
			.catch(function () { msg.textContent = 'Keine Verbindung. Bitte später erneut versuchen.'; msg.className = 'kf24-am-msg is-bad'; })
			.then(function () { btn.disabled = false; });
	});
	render();
})();
