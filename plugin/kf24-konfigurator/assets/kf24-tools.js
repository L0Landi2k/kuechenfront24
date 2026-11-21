/* Preisrechner und Frontarten-Filter */
(function () {
	'use strict';
	var T = window.KF24T || { products: [], s: {} };
	var eur = function (v) { return v.toLocaleString('de-DE', { style: 'currency', currency: 'EUR' }); };
	var store = { get: function (k, d) { try { var v = localStorage.getItem('kf24_' + k); return v ? JSON.parse(v) : d; } catch (e) { return d; } }, set: function (k, v) { try { localStorage.setItem('kf24_' + k, JSON.stringify(v)); } catch (e) {} } };

	// Frontarten filter
	document.querySelectorAll('.kf24-ft').forEach(function (ft) {
		ft.addEventListener('click', function (e) {
			var b = e.target.closest('[data-g]'); if (!b || b.tagName !== 'BUTTON') return;
			ft.querySelectorAll('.kf24-filter-row button').forEach(function (x) { x.classList.toggle('kf24-on', x === b); x.classList.toggle('kf24-ghost', x !== b); });
			ft.querySelectorAll('tbody tr').forEach(function (r) { r.hidden = b.dataset.g && r.dataset.g !== b.dataset.g; });
		});
	});

	var root = document.getElementById('kf24-calc');
	if (!root || !T.products.length) return;
	var $ = function (s) { return root.querySelector(s); };
	var sel = $('#kc-type');
	var groups = {};
	T.products.forEach(function (p) { (groups[p.g] = groups[p.g] || []).push(p); });
	sel.innerHTML = Object.keys(groups).map(function (g) {
		return '<optgroup label="' + g + '">' + groups[g].map(function (p) { return '<option value="' + p.id + '">' + p.n + ' · ' + eur(p.p) + '/m²</option>'; }).join('') + '</optgroup>';
	}).join('');
	var def = T.products.filter(function (p) { return /PG2/.test(p.n) && p.g === 'Massivholz'; })[0] || T.products[0];
	sel.value = store.get('type', def.id);
	if (!sel.value) sel.value = def.id;
	var parts = store.get('parts', [{ n: 'Tür', w: 597, h: 797, q: 2 }]);
	var PRESET = [{ n: 'Tür', w: 597, h: 797, q: 4 }, { n: 'Tür schmal', w: 397, h: 797, q: 2 }, { n: 'Schubladenfront', w: 597, h: 197, q: 3 }, { n: 'Schubladenfront', w: 597, h: 397, q: 3 }, { n: 'Geschirrspülerfront', w: 597, h: 797, q: 1 }, { n: 'Oberschrank-Tür', w: 397, h: 797, q: 4 }, { n: 'Hochschrank-Tür', w: 597, h: 1997, q: 2 }];
	function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
	function render() {
		$('#kc-parts').innerHTML = parts.length ? parts.map(function (p, i) {
			return '<div class="kf24-prow"><label class="kf24-pn">Bezeichnung<input data-i="' + i + '" data-k="n" value="' + esc(p.n) + '"></label><label>Breite mm<input type="number" inputmode="numeric" data-i="' + i + '" data-k="w" value="' + p.w + '"></label><label>Höhe mm<input type="number" inputmode="numeric" data-i="' + i + '" data-k="h" value="' + p.h + '"></label><label>Anzahl<input type="number" min="1" inputmode="numeric" data-i="' + i + '" data-k="q" value="' + p.q + '"></label><button type="button" class="kf24-x" data-del="' + i + '" aria-label="Teil entfernen">×</button></div>';
		}).join('') : '<p class="kf24-hint">Noch keine Frontteile. Fügen Sie ein Teil hinzu oder laden Sie die Beispielküche.</p>';
		calc();
	}
	function calc() {
		var p = T.products.filter(function (x) { return String(x.id) === String(sel.value); })[0] || def, s = T.s;
		store.set('type', p.id); store.set('parts', parts);
		var area = 0, cnt = 0, long = false;
		parts.forEach(function (t) { var w = +t.w || 0, h = +t.h || 0, q = Math.max(0, +t.q || 0); area += w * h / 1e6 * q; cnt += q; if (Math.max(w, h) > s.len_l && q) long = true; });
		var goods = area * p.p, weight = area * p.kg, min = goods > 0 && goods < s.min_t ? s.min_f : 0, len = long && weight < s.len_w ? s.len_f : 0;
		var ship = area === 0 ? 0 : weight < 10 ? s.t1 : weight < 20 ? s.t2 : weight < 50 ? s.t3 : s.t4;
		$('#kc-chip').style.cssText = p.tex ? 'background-image:url(' + p.tex + ')' : 'background:' + p.col;
		$('#kc-name').textContent = p.n;
		$('#kc-total').textContent = eur(goods + min + len + ship);
		$('#kc-lines').innerHTML = '<tr><td>' + cnt + ' Teile · ' + area.toLocaleString('de-DE', { maximumFractionDigits: 2 }) + ' m² × ' + eur(p.p) + '</td><td>' + eur(goods) + '</td></tr>' + (min ? '<tr><td>Mindermengenzuschlag</td><td>' + eur(min) + '</td></tr>' : '') + (len ? '<tr><td>Längenzuschlag</td><td>' + eur(len) + '</td></tr>' : '') + '<tr><td>Versand DE, ca. ' + Math.round(weight) + ' kg</td><td>' + eur(ship) + '</td></tr>';
		$('#kc-lz').textContent = 'Lieferzeit: ' + p.lz + ' ab Zahlungseingang';
		$('#kc-shop').href = p.u + '#kf24-konfigurator';
		var sm = $('#kc-sample'); sm.hidden = !p.sid; if (p.sid) { sm.href = p.sadd; sm.textContent = 'Muster (' + eur(p.sp) + ')'; }
	}
	$('#kc-parts').addEventListener('input', function (e) { var t = e.target; if (t.dataset.i == null) return; parts[+t.dataset.i][t.dataset.k] = t.dataset.k === 'n' ? t.value : +t.value; calc(); });
	$('#kc-parts').addEventListener('click', function (e) { var b = e.target.closest('[data-del]'); if (b) { parts.splice(+b.dataset.del, 1); render(); } });
	$('#kc-add').addEventListener('click', function () { parts.push({ n: 'Tür', w: 597, h: 797, q: 1 }); render(); });
	$('#kc-preset').addEventListener('click', function () { parts = PRESET.map(function (p) { return Object.assign({}, p); }); render(); });
	$('#kc-clear').addEventListener('click', function () { parts = []; render(); });
	sel.addEventListener('change', calc);
	render();
})();
