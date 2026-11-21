/* Küchenfront24 Konfigurator – Live-Zeichnung, Prüfung und Preis */
(function () {
	'use strict';
	var D = window.KF24CFG;
	var form = document.querySelector('.kf24-form');
	if (!D || !form) return;
	var P = D.product, S = D.settings;
	var $ = function (s) { return form.querySelector(s); };
	var $$ = function (s) { return Array.prototype.slice.call(form.querySelectorAll(s)); };
	var eur = function (v) { return v.toLocaleString('de-DE', { style: 'currency', currency: 'EUR' }); };
	var num = function (v) { var n = parseFloat(String(v).replace(',', '.')); return isNaN(n) ? 0 : n; };
	var fmt = function (v) { return String(Math.round(v * 10) / 10).replace('.', ','); };
	var hposDirty = false, gaDirty = false;

	function radio(name) { var r = form.querySelector('input[name="kf24[' + name + ']"]:checked'); return r ? r.value : ''; }
	function setRadio(name, v) { var r = form.querySelector('input[name="kf24[' + name + ']"][value="' + v + '"]'); if (r) r.checked = true; }

	function recommend(len) { return len <= 900 ? 2 : len <= 1600 ? 3 : len <= 2200 ? 4 : 5; }

	function defaultPositions(len, n) {
		if (n <= 0) return [];
		var e = Math.min(100, Math.max(70, len * 0.12));
		if (n === 1) return [Math.round(len / 2)];
		var out = [], step = (len - 2 * e) / (n - 1);
		for (var i = 0; i < n; i++) out.push(Math.round(e + i * step));
		return out;
	}

	function state() {
		var hinge = radio('hinge');
		var c = {
			type: radio('type'), decor: radio('decor') || '',
			w: num($('#kf24-w').value), h: num($('#kf24-h').value), grain: $('#kf24-grain').value,
			hinge: hinge, hinges: hinge === 'keine' ? 0 : parseInt($('#kf24-hinges').value, 10),
			deliver: $('input[name="kf24[deliver]"]').checked ? 'yes' : 'no',
			grip: radio('grip') || '0', gpos: $('#kf24-gpos').value,
			ga: num($('#kf24-ga').value), gb: num($('#kf24-gb').value), gd: num($('#kf24-gd').value),
			mill: radio('mill') || 'keine', seal: $('input[name="kf24[seal]"]').checked ? 'yes' : 'no',
			qty: Math.max(1, parseInt($('#kf24-qty').value, 10) || 1), hpos: []
		};
		$$('#kf24-hpos input').forEach(function (i) { c.hpos.push(num(i.value)); });
		return c;
	}

	function renderHpos(c, force) {
		var box = $('#kf24-hpos');
		var len = c.hinge === 'oben' ? c.w : c.h;
		var inputs = $$('#kf24-hpos input');
		if (!force && inputs.length === c.hinges && hposDirty) return;
		var pos = defaultPositions(len, c.hinges);
		if (hposDirty && inputs.length === c.hinges && !force) pos = inputs.map(function (i) { return num(i.value); });
		var ref = c.hinge === 'oben' ? 'von links' : 'von oben';
		box.innerHTML = pos.map(function (p, i) {
			return '<label>Topfband ' + (i + 1) + ' <span>mm ' + ref + '</span><input type="number" step="0.5" inputmode="decimal" name="kf24[hpos][' + i + ']" value="' + p + '"></label>';
		}).join('') + '<button type="button" class="kf24-link" id="kf24-hpos-reset">Standardpositionen</button>';
	}

	function gripHoles(c) {
		var n = parseInt(c.grip, 10); if (!n) return [];
		var w = c.w, h = c.h, a = c.ga, b = c.gb, d = n === 2 ? c.gd : 0;
		if (c.gpos === 'mitte') { var y0 = a || h / 2; return n === 2 ? [[w / 2 - d / 2, y0], [w / 2 + d / 2, y0]] : [[w / 2, y0]]; }
		var top = c.gpos.indexOf('oben') === 0, vert = /-v$/.test(c.gpos), fromR = c.hinge !== 'rechts';
		var x = fromR ? w - b : b, y = top ? a : h - a, out = [[x, y]];
		if (n === 2) out.push(vert ? [x, top ? y + d : y - d] : [fromR ? x - d : x + d, y]);
		return out;
	}
	function hingePts(c) {
		return c.hpos.map(function (p) {
			return c.hinge === 'links' ? [22.5, p] : c.hinge === 'rechts' ? [c.w - 22.5, p] : [p, 22.5];
		});
	}

	function check(c) {
		var err = [], warn = [];
		if (!c.decor) err.push('Bitte wählen Sie ' + P.field + '.');
		if (c.w < S.min_dim || c.h < S.min_dim) err.push('Breite und Höhe müssen mindestens ' + S.min_dim + ' mm betragen.');
		if (Math.max(c.w, c.h) > S.max_long || Math.min(c.w, c.h) > S.max_short) err.push('Maximalmaß ' + S.max_long + ' × ' + S.max_short + ' mm. Größere Fronten bitte anfragen.');
		var len = c.hinge === 'oben' ? c.w : c.h;
		if (c.hinges) {
			var rec = recommend(len);
			if (c.hinges < rec) warn.push('Für ' + fmt(len) + ' mm empfehlen wir ' + rec + ' Topfbänder.');
			if (rec > 4) warn.push('Sehr große Front: Bitte sprechen Sie Beschläge mit uns ab (' + S.phone + ').');
			if (c.hpos.some(function (p) { return p < 50 || p > len - 50; })) err.push('Topfbänder müssen mindestens 50 mm vom Rand entfernt sein.');
			var s = c.hpos.slice().sort(function (a, b) { return a - b; });
			for (var i = 1; i < s.length; i++) if (s[i] - s[i - 1] < 80) { err.push('Topfbänder brauchen mindestens 80 mm Abstand zueinander.'); break; }
			if (c.hinge !== 'oben' && c.w > 600) warn.push('Türen über 600 mm Breite werden schwer. Prüfen Sie zwei Türen oder einen Zwillingsanschlag.');
		}
		if (c.type === 'tuer' && c.hinge === 'keine') warn.push('Tür ohne Topfbandbohrung? Dann bohren Sie selbst.');
		gripHoles(c).forEach(function (p, i) {
			if (i === 0 && (p[0] < 15 || p[0] > c.w - 15 || p[1] < 15 || p[1] > c.h - 15)) err.push('Die Griffbohrung liegt zu nah am Rand oder außerhalb der Front.');
		});
		if (gripHoles(c).length === 2) { var q = gripHoles(c)[1]; if (q[0] < 15 || q[0] > c.w - 15 || q[1] < 15 || q[1] > c.h - 15) err.push('Die zweite Griffbohrung liegt außerhalb der Front. Lochabstand oder Position prüfen.'); }
		if (c.grip !== '0') hingePts(c).forEach(function (hp) { gripHoles(c).forEach(function (g) { if (Math.abs(hp[0] - g[0]) < 30 && Math.abs(hp[1] - g[1]) < 30) err.push('Griffbohrung kollidiert mit einem Topfband.'); }); });
		if (c.grain === 'quer' && /Massivholz|Furnier/.test(P.group) && c.h > c.w * 2) warn.push('Quer laufende Maserung bei hohen Fronten wirkt unruhig. Bewusst so gewählt?');
		return { err: err.filter(function (v, i, a) { return a.indexOf(v) === i; }), warn: warn };
	}

	function price(c) {
		var area = Math.max(c.w * c.h / 1e6, S.min_area);
		var base = area * P.m2;
		var mill = P.milling && c.mill !== 'keine' ? S.milling_price : 0;
		var hin = c.deliver === 'yes' ? c.hinges * S.hinge_price : 0;
		var seal = c.seal === 'yes' ? (c.w / 1000) * S.seal_price_lfm : 0;
		var unit = Math.round((base + mill + hin + seal) * 100) / 100;
		return { area: area, base: base, mill: mill, hin: hin, seal: seal, unit: unit, total: unit * c.qty };
	}

	function draw(c) {
		var box = $('#kf24-drawing');
		var W = box.clientWidth || 360, H = Math.max(300, Math.min(520, W * 1.2));
		var pad = 54;
		var w = Math.max(c.w, 1), h = Math.max(c.h, 1);
		var s = Math.min((W - pad * 2) / w, (H - pad * 2) / h);
		var fw = w * s, fh = h * s, ox = (W - fw) / 2 + 10, oy = (H - fh) / 2;
		var look = (c.decor && D.mats && D.mats[c.decor]) || { img: D.tex, color: D.color };
		var tex = look.img, fill = tex ? 'url(#kf24tex)' : look.color;
		var o = '<svg viewBox="0 0 ' + W + ' ' + H + '" width="' + W + '" height="' + H + '" xmlns="http://www.w3.org/2000/svg">';
		o += '<defs>' + (tex ? '<pattern id="kf24tex" patternUnits="userSpaceOnUse" width="520" height="520"' + (c.grain === 'quer' ? ' patternTransform="rotate(90)"' : '') + '><image href="' + tex + '" width="520" height="520" preserveAspectRatio="none"/></pattern>' : '') +
			'<marker id="kf24a" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="#5d554c"/></marker></defs>';
		o += '<rect x="' + (ox + 4) + '" y="' + (oy + 6) + '" width="' + fw + '" height="' + fh + '" fill="#000" opacity=".08" rx="2"/>';
		o += '<rect x="' + ox + '" y="' + oy + '" width="' + fw + '" height="' + fh + '" fill="' + fill + '" stroke="#3b2a1e" stroke-width="1.5" rx="2"/>';
		if (!tex) for (var i = 1; i < 8; i++) {
			if (c.grain === 'quer') o += '<line x1="' + (ox + 6) + '" x2="' + (ox + fw - 6) + '" y1="' + (oy + fh * i / 8) + '" y2="' + (oy + fh * i / 8) + '" stroke="#3b2a1e" stroke-opacity=".08"/>';
			else o += '<line y1="' + (oy + 6) + '" y2="' + (oy + fh - 6) + '" x1="' + (ox + fw * i / 8) + '" x2="' + (ox + fw * i / 8) + '" stroke="#3b2a1e" stroke-opacity=".08"/>';
		}
		if (c.mill !== 'keine' && P.milling) o += '<rect x="' + ox + '" y="' + (c.mill === 'oben' ? oy : oy + fh - Math.max(5, 25 * s)) + '" width="' + fw + '" height="' + Math.max(5, 25 * s) + '" fill="#2a2019" fill-opacity=".55"/>';
		// opening symbol (dashed lines towards hinge side)
		if (c.hinges) {
			var tip = c.hinge === 'links' ? [ox, oy + fh / 2] : c.hinge === 'rechts' ? [ox + fw, oy + fh / 2] : [ox + fw / 2, oy];
			var a1 = c.hinge === 'links' ? [ox + fw, oy] : c.hinge === 'rechts' ? [ox, oy] : [ox, oy + fh];
			var a2 = c.hinge === 'links' ? [ox + fw, oy + fh] : c.hinge === 'rechts' ? [ox, oy + fh] : [ox + fw, oy + fh];
			o += '<path d="M' + a1 + 'L' + tip + 'L' + a2 + '" fill="none" stroke="#fff" stroke-opacity=".75" stroke-width="1.2" stroke-dasharray="6 5"/>';
		}
		hingePts(c).forEach(function (p, idx) {
			var cx = ox + p[0] * s, cy = oy + p[1] * s, r = Math.max(4, 17.5 * s);
			o += '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="#fbfaf7" stroke="#0d5a5f" stroke-width="2"/>';
			var lx = c.hinge === 'rechts' ? ox + fw + 10 : c.hinge === 'links' ? ox - 10 : cx;
			if (c.hinge === 'oben') o += '<text x="' + cx + '" y="' + (oy - 10) + '" text-anchor="middle" class="kf24-t">' + fmt(c.hpos[idx]) + '</text>';
			else o += '<text x="' + lx + '" y="' + (cy + 4) + '" text-anchor="' + (c.hinge === 'rechts' ? 'start' : 'end') + '" class="kf24-t">' + fmt(c.hpos[idx]) + '</text>';
		});
		gripHoles(c).forEach(function (p) {
			o += '<circle cx="' + (ox + p[0] * s) + '" cy="' + (oy + p[1] * s) + '" r="3.5" fill="#b3261e" stroke="#fff" stroke-width="1"/>';
		});
		var gh = gripHoles(c);
		if (gh.length === 2) o += '<line x1="' + (ox + gh[0][0] * s) + '" y1="' + (oy + gh[0][1] * s) + '" x2="' + (ox + gh[1][0] * s) + '" y2="' + (oy + gh[1][1] * s) + '" stroke="#2a2019" stroke-width="5" stroke-linecap="round" opacity=".55"/>';
		// dimensions
		var by = oy + fh + 26, lx2 = ox - 34;
		o += '<line x1="' + ox + '" x2="' + (ox + fw) + '" y1="' + by + '" y2="' + by + '" stroke="#5d554c" marker-start="url(#kf24a)" marker-end="url(#kf24a)"/>';
		o += '<text x="' + (ox + fw / 2) + '" y="' + (by + 18) + '" text-anchor="middle" class="kf24-d">' + fmt(c.w) + ' mm</text>';
		o += '<line y1="' + oy + '" y2="' + (oy + fh) + '" x1="' + lx2 + '" x2="' + lx2 + '" stroke="#5d554c" marker-start="url(#kf24a)" marker-end="url(#kf24a)"/>';
		o += '<text x="' + (lx2 - 8) + '" y="' + (oy + fh / 2) + '" text-anchor="middle" class="kf24-d" transform="rotate(-90 ' + (lx2 - 8) + ' ' + (oy + fh / 2) + ')">' + fmt(c.h) + ' mm</text>';
		o += '</svg>';
		box.innerHTML = o;
		box.setAttribute('aria-label', 'Zeichnung: Front ' + fmt(c.w) + ' mal ' + fmt(c.h) + ' mm' + (c.hinges ? ', ' + c.hinges + ' Topfbänder ' + c.hinge : '') + (c.grip !== '0' ? ', Griffbohrung' : ''));
	}

	function sync() {
		var c = state();
		form.classList.toggle('kf24-no-hinge', c.hinge === 'keine');
		form.classList.toggle('kf24-no-grip', c.grip === '0');
		form.classList.toggle('kf24-grip1', c.grip === '1');
		form.classList.toggle('kf24-mitte', c.gpos === 'mitte');
		$('.kf24-ga-l').textContent = c.gpos === 'mitte' ? 'Höhe von oben (leer = Mitte)' : (c.gpos.indexOf('oben') === 0 ? 'Abstand von oben (a)' : 'Abstand von unten (a)');
		if (!gaDirty && c.gpos !== 'mitte' && !$('#kf24-ga').value) $('#kf24-ga').value = 40;
		var len = c.hinge === 'oben' ? c.w : c.h;
		if ($$('#kf24-hpos input').length !== c.hinges) renderHpos(c, false);
		c = state();
		var r = check(c), p = price(c);
		$('#kf24-msgs').innerHTML = r.err.map(function (m) { return '<li class="kf24-err">' + m + '</li>'; }).join('') + r.warn.map(function (m) { return '<li class="kf24-warn">' + m + '</li>'; }).join('');
		var rows = '<tr><td>' + p.area.toLocaleString('de-DE', { maximumFractionDigits: 3 }) + ' m² × ' + eur(P.m2) + '</td><td>' + eur(p.base) + '</td></tr>';
		if (p.mill) rows += '<tr><td>Grifffräsung</td><td>' + eur(p.mill) + '</td></tr>';
		if (p.hin) rows += '<tr><td>' + c.hinges + ' Topfbänder</td><td>' + eur(p.hin) + '</td></tr>';
		if (p.seal) rows += '<tr><td>Aufsteckdichtung' + (S.seal_note ? ' (' + S.seal_note + ')' : '') + '</td><td>' + eur(p.seal) + '</td></tr>';
		rows += '<tr class="kf24-unitrow"><td>Preis je Front</td><td>' + eur(p.unit) + '</td></tr>';
		if (c.qty > 1) rows += '<tr><td>× ' + c.qty + ' Stück</td><td></td></tr>';
		$('#kf24-breakdown').innerHTML = rows;
		$('#kf24-decor-name').textContent = c.decor || 'bitte wählen';
		$('#kf24-total').textContent = eur(p.total);
		$('#kf24-submit').disabled = r.err.length > 0;
		$('#kf24-msubmit').disabled = r.err.length > 0;
		$('#kf24-mtotal').textContent = eur(p.total);
		$('.kf24-hint-body').hidden = !c.hinges;
		var rec = recommend(len);
		$('#kf24-hinges').setAttribute('aria-description', 'Empfohlen: ' + rec);
		draw(c);
	}

	// presets per front type
	function preset(t) {
		if (t === 'tuer') { setRadio('hinge', 'links'); setRadio('grip', '0'); }
		if (t === 'schublade') { setRadio('hinge', 'keine'); $('#kf24-h').value = 197; $('#kf24-gpos').value = 'mitte'; }
		if (t === 'klappe') { setRadio('hinge', 'oben'); $('#kf24-h').value = 397; $('#kf24-hinges').value = 2; }
		if (t === 'blende') { setRadio('hinge', 'keine'); setRadio('grip', '0'); $('#kf24-h').value = 80; $('#kf24-w').value = 597; }
		$('.kf24-seal').hidden = t !== 'blende';
		hposDirty = false;
		var c = state(); var len = c.hinge === 'oben' ? c.w : c.h;
		if (c.hinge !== 'keine') $('#kf24-hinges').value = String(recommend(len) > 5 ? 5 : recommend(len));
		renderHpos(state(), true);
		sync();
	}

	form.addEventListener('input', function (e) {
		if (e.target.closest('#kf24-hpos')) hposDirty = true;
		if (e.target.id === 'kf24-ga') gaDirty = true;
		if (e.target.id === 'kf24-h' || e.target.id === 'kf24-w') {
			if (!hposDirty) { var c = state(); renderHpos(c, true); }
		}
		sync();
	});
	form.addEventListener('change', function (e) {
		var n = e.target.name || '';
		if (n === 'kf24[type]') return preset(e.target.value);
		if (n === 'kf24[hinge]' || e.target.id === 'kf24-hinges') {
			var c = state();
			if (n === 'kf24[hinge]' && c.hinge !== 'keine') { var len = c.hinge === 'oben' ? c.w : c.h; $('#kf24-hinges').value = String(Math.min(5, recommend(len))); }
			hposDirty = false; renderHpos(state(), true);
		}
		sync();
	});
	form.addEventListener('click', function (e) {
		if (e.target.id === 'kf24-hpos-reset') { hposDirty = false; renderHpos(state(), true); sync(); }
	});
	var filter = $('.kf24-filter');
	if (filter) filter.addEventListener('input', function () {
		var q = filter.value.toLowerCase();
		var any = false;
		$$('.kf24-tile').forEach(function (o) { var hit = !q || o.title.toLowerCase().indexOf(q) >= 0; o.hidden = !hit; any = any || hit; });
		$('.kf24-filter-empty').hidden = any;
	});
	form.addEventListener('submit', function (e) {
		var r = check(state());
		if (r.err.length) { e.preventDefault(); $('#kf24-msgs').scrollIntoView({ behavior: 'smooth', block: 'center' }); }
	});
	window.addEventListener('resize', function () { draw(state()); });
	$('.kf24-seal').hidden = true;
	renderHpos(state(), true);
	sync();
})();
