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
		var hinge = radio('hinge'), type = radio('type');
		var c = {
			type: type, decor: radio('decor') || '',
			w: num($('#kf24-w').value), h: num($('#kf24-h').value), grain: type === 'blende' ? 'laengs' : $('#kf24-grain').value,
			label: $('#kf24-label').value.trim(), note: (form.querySelector('[name="kf24[note]"]') || {}).value || '',
			body: (form.querySelector('[name="kf24[body]"]') || {}).value || '19',
			hlast: radio('hlast') === 'ende' ? 'ende' : 'start', hraw: [], part: $('#kf24-part-id').value,
			hinge: hinge, hinges: hinge === 'keine' ? 0 : parseInt($('#kf24-hinges').value, 10),
			deliver: $('input[name="kf24[deliver]"]').checked ? 'yes' : 'no',
			grip: radio('grip') || '0', gpos: $('#kf24-gpos').value,
			ga: num($('#kf24-ga').value), gb: num($('#kf24-gb').value), gd: num($('#kf24-gd').value),
			finish: P.finish ? (radio('finish') || 'lack') : '',
			mill: radio('mill') || 'keine', seal: $('input[name="kf24[seal]"]').checked ? 'yes' : 'no',
			qty: Math.max(1, parseInt($('#kf24-qty').value, 10) || 1), hpos: []
		};
		$$('#kf24-hpos input[type=number]').forEach(function (i) { c.hraw.push(num(i.value)); });
		var len = hinge === 'oben' ? c.w : c.h;
		c.hpos = c.hraw.map(function (v, i) { return c.hlast === 'ende' && i === c.hraw.length - 1 && c.hraw.length >= 2 ? Math.round((len - v) * 10) / 10 : v; });
		if (c.hraw.length < 2) c.hlast = 'start';
		return c;
	}

	function renderHpos(c, force, raw, hlast) {
		var box = $('#kf24-hpos');
		var len = c.hinge === 'oben' ? c.w : c.h;
		var inputs = $$('#kf24-hpos input[type=number]');
		if (!force && !raw && inputs.length === c.hinges && hposDirty) return;
		var last = hlast || (radio('hlast') === 'ende' ? 'ende' : 'start');
		var pos = raw || defaultPositions(len, c.hinges);
		if (!raw && last === 'ende' && pos.length >= 2) pos[pos.length - 1] = Math.round((len - pos[pos.length - 1]) * 10) / 10;
		var a = c.hinge === 'oben' ? 'von links' : 'von oben', b = c.hinge === 'oben' ? 'von rechts' : 'von unten';
		box.innerHTML = pos.map(function (p, i) {
			var isLast = i === pos.length - 1 && pos.length >= 2;
			var ref = isLast && last === 'ende' ? b : a;
			return '<label>Topfband ' + (i + 1) + ' <span class="kf24-ref">mm ' + ref + '</span><input type="number" step="0.5" inputmode="decimal" name="kf24[hpos][' + i + ']" value="' + p + '">' +
				(isLast ? '<span class="kf24-mini" role="radiogroup" aria-label="Topfband ' + (i + 1) + ' bemaßen"><label><input type="radio" name="kf24[hlast]" value="start"' + (last !== 'ende' ? ' checked' : '') + '><span>' + a + '</span></label><label><input type="radio" name="kf24[hlast]" value="ende"' + (last === 'ende' ? ' checked' : '') + '><span>' + b + '</span></label></span>' : '') + '</label>';
		}).join('') + '<button type="button" class="kf24-link" id="kf24-hpos-reset">Standardpositionen</button>';
	}

	function gripHoles(c) {
		var n = parseInt(c.grip, 10); if (!n) return [];
		var w = c.w, h = c.h, a = c.ga, b = c.gb, d = n === 2 ? c.gd : 0;
		if (c.gpos === 'mitte') { var y0 = a || h / 2; return n === 2 ? [[w / 2 - d / 2, y0], [w / 2 + d / 2, y0]] : [[w / 2, y0]]; }
		if (c.gpos === 'mitte-v') { var x0 = c.hinge !== 'rechts' ? w - b : b, y1 = a || h / 2; return n === 2 ? [[x0, y1 - d / 2], [x0, y1 + d / 2]] : [[x0, y1]]; }
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
		if (Math.max(c.w, c.h) > S.max_long || Math.min(c.w, c.h) > S.max_short) err.push('Online bestellbar bis ' + fmt(S.max_long) + ' × ' + fmt(S.max_short) + ' mm. Größere Teile fertigen wir <a href="' + D.contact + '">auf Anfrage</a>.');
		if (c.type === 'sonstig' && !c.label) err.push('Bitte geben Sie dem sonstigen Bauteil eine Bezeichnung.');
		if (c.grip === '2' && c.gd < 16) err.push('Bitte den Lochabstand des Griffs eintragen (Mitte bis Mitte).');
		var len = c.hinge === 'oben' ? c.w : c.h;
		if (c.hinges) {
			var rec = recommend(len);
			if (c.hinges < rec) warn.push('Für ' + fmt(len) + ' mm empfehlen wir ' + rec + ' Topfbänder.');
			if (rec > 4) warn.push('Sehr große Front: Bitte sprechen Sie Beschläge mit uns ab (' + S.phone + ').');
			if (c.hpos.some(function (p) { return p < 50 || p > len - 50; })) err.push('Topfbänder müssen mindestens 50 mm vom Rand entfernt sein.');
			var s = c.hpos.slice().sort(function (a, b) { return a - b; });
			for (var i = 1; i < s.length; i++) if (s[i] - s[i - 1] < 80) { err.push('Topfbänder brauchen mindestens 80 mm Abstand zueinander.'); break; }
			if (c.hinge !== 'oben' && c.w > 600) warn.push('Türen über 600 mm Breite werden schwer. Prüfen Sie, ob zwei Türen besser sind.');
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

	function catPart(id) { return (D.catalog || []).filter(function (p) { return p.id === id; })[0]; }
	function basePrice(c, area) { // identical to kf24_base_price() on the server
		var cp = c.part && catPart(c.part), ov = P.overrides || {};
		if (cp && ov[c.part] > 0 && Math.abs(cp.w - c.w) < 0.6 && Math.abs(cp.h - c.h) < 0.6) return { v: +ov[c.part], how: 'fest' };
		var calc = area * P.m2 + 2 * (c.w + c.h) / 1000 * (P.edge || 0) + (P.piece || 0);
		return { v: Math.round(Math.max(calc, P.minprice || 0) * 100) / 100, how: (P.minprice || 0) > calc ? 'mindest' : 'formel' };
	}
	function price(c) {
		var area = Math.max(c.w * c.h / 1e6, S.min_area);
		var bp = basePrice(c, area), base = bp.v;
		var mill = P.milling && c.mill !== 'keine' ? S.milling_price : 0;
		var hin = c.deliver === 'yes' ? c.hinges * S.hinge_price : 0;
		var seal = c.seal === 'yes' ? (c.w / 1000) * S.seal_price_lfm : 0;
		var ff = P.finish && c.finish && S.finishes && S.finishes[c.finish], fin = ff ? Math.round(Math.max(ff[2], area * ff[1]) * 100) / 100 : 0;
		var unit = Math.round((base + mill + hin + seal + fin) * 100) / 100;
		return { area: area, base: base, how: bp.how, mill: mill, hin: hin, seal: seal, fin: fin, unit: unit, total: unit * c.qty };
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
		o += '<defs>' + (tex ? '<pattern id="kf24tex" patternUnits="userSpaceOnUse" width="520" height="520"' + (c.grain === 'quer' || (c.grain === 'laengs' && c.w > c.h) ? ' patternTransform="rotate(90)"' : '') + '><image href="' + tex + '" width="520" height="520" preserveAspectRatio="none"/></pattern>' : '') +
			'<marker id="kf24a" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="#5d554c"/></marker></defs>';
		o += '<rect x="' + (ox + 4) + '" y="' + (oy + 6) + '" width="' + fw + '" height="' + fh + '" fill="#000" opacity=".08" rx="2"/>';
		o += '<rect x="' + ox + '" y="' + oy + '" width="' + fw + '" height="' + fh + '" fill="' + fill + '" stroke="#3b2a1e" stroke-width="1.5" rx="2"/>';
		if (!tex) for (var i = 1; i < 8; i++) {
			if (c.grain === 'quer' || (c.grain === 'laengs' && c.w > c.h)) o += '<line x1="' + (ox + 6) + '" x2="' + (ox + fw - 6) + '" y1="' + (oy + fh * i / 8) + '" y2="' + (oy + fh * i / 8) + '" stroke="#3b2a1e" stroke-opacity=".08"/>';
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
			var fromEnd = c.hlast === 'ende' && idx === c.hpos.length - 1;
			var lab = fromEnd ? fmt(c.hraw[idx]) + (c.hinge === 'oben' ? ' v. r.' : ' v. u.') : fmt(c.hpos[idx]);
			if (c.hinge === 'oben') o += '<text x="' + cx + '" y="' + (oy - 10) + '" text-anchor="middle" class="kf24-t">' + lab + '</text>';
			else o += '<text x="' + lx + '" y="' + (cy + 4) + '" text-anchor="' + (c.hinge === 'rechts' ? 'start' : 'end') + '" class="kf24-t">' + lab + '</text>';
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
		$('.kf24-ga-l').textContent = c.gpos === 'mitte' ? 'Höhe von oben (leer = Mitte)' : c.gpos === 'mitte-v' ? 'Griffmitte von oben (leer = Mitte)' : (c.gpos.indexOf('oben') === 0 ? 'Abstand von oben (a)' : 'Abstand von unten (a)');
		if (!gaDirty && c.gpos !== 'mitte' && c.gpos !== 'mitte-v' && !$('#kf24-ga').value) $('#kf24-ga').value = 40;
		var len = c.hinge === 'oben' ? c.w : c.h;
		if ($$('#kf24-hpos input[type=number]').length !== c.hinges) renderHpos(c, false);
		c = state();
		var r = check(c), p = price(c);
		form.classList.toggle('kf24-is-blende', c.type === 'blende');
		var fixedGrip = c.type === 'schublade' || c.type === 'klappe';
		if (fixedGrip && $('#kf24-gpos').value !== 'mitte') { $('#kf24-gpos').value = 'mitte'; }
		$('#kf24-gpos').closest('label').hidden = fixedGrip;
		if (fixedGrip) $('.kf24-ga-l').textContent = 'Höhe der Griffmitte von oben (A, leer = Mitte)';
		$('.kf24-grain-l').hidden = c.type === 'blende'; $('.kf24-grain-fix').hidden = c.type !== 'blende';
		$('.kf24-label-t').innerHTML = c.type === 'sonstig' ? 'Bezeichnung <small>(Pflicht, z. B. „Regalboden“ oder „Wandpaneel“)</small>' : 'Eigene Bezeichnung <small>(optional, z. B. „Spüle links“)</small>';
		var shown = !c.w && !c.h ? ['<li class="kf24-info">Bitte Breite und Höhe in mm eintragen.</li>'] : r.err.map(function (m) { return '<li class="kf24-err">' + m + '</li>'; });
		$('#kf24-msgs').innerHTML = shown.join('') + r.warn.map(function (m) { return '<li class="kf24-warn">' + m + '</li>'; }).join('');
		var cpn = c.part && catPart(c.part);
		var rows = '<tr><td>' + (p.how === 'fest' ? 'Festpreis ' + cpn.n : p.how === 'mindest' ? 'Mindestpreis je Teil' : p.area.toLocaleString('de-DE', { maximumFractionDigits: 3 }) + ' m² × ' + eur(P.m2) + ((P.edge || P.piece) ? ' + Kante/Pauschale' : '')) + '</td><td>' + eur(p.base) + '</td></tr>';
		if (p.fin) rows += '<tr><td>Oberfläche ' + S.finishes[c.finish][0] + '</td><td>' + eur(p.fin) + '</td></tr>';
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
		if (t === 'schublade') { setRadio('hinge', 'keine'); if (!ownDims) $('#kf24-h').value = 197; $('#kf24-gpos').value = 'mitte'; }
		if (t === 'klappe') { setRadio('hinge', 'oben'); if (!ownDims) $('#kf24-h').value = 397; $('#kf24-hinges').value = 2; }
		if (t === 'blende') { setRadio('hinge', 'keine'); setRadio('grip', '0'); if (!ownDims) { $('#kf24-h').value = 80; $('#kf24-w').value = 597; } }
		if (t === 'sonstig') { setRadio('hinge', 'keine'); setRadio('grip', '0'); setTimeout(function () { $('#kf24-label').focus(); }, 50); }
		$('.kf24-seal').hidden = t !== 'blende';
		hposDirty = false;
		var c = state(); var len = c.hinge === 'oben' ? c.w : c.h;
		if (c.hinge !== 'keine') $('#kf24-hinges').value = String(recommend(len) > 5 ? 5 : recommend(len));
		renderHpos(state(), true);
		sync();
	}

	form.addEventListener('input', function (e) {
		if (e.target.closest('#kf24-hpos') && e.target.type === 'number') hposDirty = true;
		if (e.target.id === 'kf24-ga') gaDirty = true;
		if (e.target.id === 'kf24-h' || e.target.id === 'kf24-w') {
			if (!hposDirty) { var c = state(); renderHpos(c, true); }
		}
		sync();
	});
	form.addEventListener('change', function (e) {
		var n = e.target.name || '';
		if (n === 'kf24[type]') return preset(e.target.value);
		if (n === 'kf24[hlast]') { // switch reference of the last hinge and convert the value
			var cc = state(), L = cc.hinge === 'oben' ? cc.w : cc.h, inp = $$('#kf24-hpos input[type=number]').pop();
			if (inp) inp.value = Math.round((L - num(inp.value)) * 10) / 10;
			renderHpos(state(), false, $$('#kf24-hpos input[type=number]').map(function (i) { return num(i.value); }), e.target.value);
			hposDirty = true; sync(); return;
		}
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
	// values handed over from the Aufmaß table (?kf24_w=…&kf24_h=…)
	var qs = new URLSearchParams(location.search), took = false;
	if (/^(tuer|schublade|klappe|blende|sonstig)$/.test(qs.get('kf24_type') || '')) { setRadio('type', qs.get('kf24_type')); preset(qs.get('kf24_type')); took = true; }
	['w', 'h'].forEach(function (k) { var v = parseInt(qs.get('kf24_' + k), 10); if (v > 0) { $('#kf24-' + k).value = v; took = true; } });
	if (/^(links|rechts|oben|keine)$/.test(qs.get('kf24_hinge') || '')) setRadio('hinge', qs.get('kf24_hinge'));
	var hn = parseInt(qs.get('kf24_hn'), 10); if (hn >= 1 && hn <= 5) $('#kf24-hinges').value = String(hn);
	if (qs.get('kf24_label')) form.querySelector('[name="kf24[label]"]').value = qs.get('kf24_label').slice(0, 60);
	var qq = parseInt(qs.get('kf24_qty'), 10); if (qq > 0 && $('#kf24-qty')) $('#kf24-qty').value = qq;
	if (took) { var note = document.createElement('p'); note.className = 'kf24-took'; note.textContent = 'Maße aus Ihrer Aufmaßtabelle übernommen. Bitte noch Material und Details wählen.'; form.insertBefore(note, form.firstChild); }
	renderHpos(state(), true);
	sync();

	/* ---------- Teil aus Küchensystem (Metod / Faktum) ---------- */
	var sysSel = $('#kf24-sys'), partSel = $('#kf24-part'), partId = $('#kf24-part-id');
	function fillParts(sys, cur) {
		var g = '', h = '<option value="">– Teil wählen –</option>';
		(D.catalog || []).filter(function (p) { return p.s === sys; }).forEach(function (p) {
			if (p.g !== g) { h += (g ? '</optgroup>' : '') + '<optgroup label="' + p.g + '">'; g = p.g; }
			h += '<option value="' + p.id + '"' + (p.id === cur ? ' selected' : '') + '>' + p.n + ' – ' + fmt(p.w) + ' × ' + fmt(p.h) + '</option>';
		});
		partSel.innerHTML = h + (g ? '</optgroup>' : '');
		partSel.closest('label').hidden = !sys;
		renderChips(sys);
	}
	function renderChips(sys) {
		var box = document.getElementById('kf24-chips'); if (!box) return;
		box.hidden = !sys; if (!sys) return;
		var g = '', h = '', rk = function (x) { return /^t(ü|u)r/i.test(x) ? 0 : /schubl/i.test(x) ? 1 : /klapp/i.test(x) ? 2 : /geschirr/i.test(x) ? 3 : 4; };
		(D.catalog || []).map(function (p, i) { return [p, i]; }).filter(function (a) { return a[0].s === sys; })
			.sort(function (a, b) { return (rk(a[0].g) - rk(b[0].g)) || (a[0].g < b[0].g ? -1 : a[0].g > b[0].g ? 1 : 0) || (a[1] - b[1]); })
			.map(function (a) { return a[0]; }).forEach(function (p) {
			if (p.g !== g) { h += (g ? '</div>' : '') + '<div class="kf24-chipg"><span>' + p.g + '</span>'; g = p.g; }
			var n = (typeof list !== 'undefined' ? list : []).reduce(function (a, c) { return a + (c && c.part === p.id ? (+c.qty || 1) : 0); }, 0);
			h += '<button type="button" class="kf24-chip' + (n ? ' is-in' : '') + '" data-part="' + p.id + '" title="' + fmt(p.w) + ' × ' + fmt(p.h) + ' mm">' + p.n.replace(/</g, '&lt;') + (n ? ' <i>' + n + '×</i>' : '') + '</button>';
		});
		document.getElementById('kf24-chips-in').innerHTML = h + (g ? '</div>' : '');
	}
	function applyPart(cp) {
		ownDims = false; setRadio('type', cp.t); preset(cp.t);
		$('#kf24-w').value = cp.w; $('#kf24-h').value = cp.h;
		setRadio('hinge', cp.a); if (cp.b) $('#kf24-hinges').value = String(cp.b);
		var note = form.querySelector('[name="kf24[note]"]'); if (note && (!note.value || note.dataset.auto)) { note.value = cp.x || ''; note.dataset.auto = '1'; }
		if (!$('#kf24-label').value || $('#kf24-label').dataset.auto) { $('#kf24-label').value = cp.n; $('#kf24-label').dataset.auto = '1'; }
		partId.value = cp.id; partSel.value = cp.id;
		hposDirty = cp.hr && cp.hr.length === cp.b;
		renderHpos(state(), true, hposDirty ? cp.hr.slice() : null, cp.hl);
		sync();
	}
	sysSel.addEventListener('change', function () { fillParts(sysSel.value, ''); if (!sysSel.value) partId.value = ''; try { localStorage.setItem('kf24_kueche', sysSel.value || 'andere'); } catch (e) {} sync(); });
	partSel.addEventListener('change', function () {
		var cp = catPart(partSel.value); if (cp) applyPart(cp); else { partId.value = ''; sync(); }
		if (active >= 0 && list[active]) { upd(); renderList(); }
	});
	form.addEventListener('input', function (e) {
		if ((e.target.id === 'kf24-w' || e.target.id === 'kf24-h') && partId.value) {
			var cp = catPart(partId.value);
			if (cp && (num($('#kf24-w').value) !== cp.w || num($('#kf24-h').value) !== cp.h)) { partId.value = ''; partSel.value = ''; sync(); }
		}
		if (e.target.id === 'kf24-label') delete e.target.dataset.auto;
		if (e.target.name === 'kf24[note]') delete e.target.dataset.auto;
	}, true);
	var qsys = new URLSearchParams(location.search).get('system');
	if (!qsys) { try { qsys = localStorage.getItem('kf24_kueche'); } catch (e) { qsys = ''; } }
	if (qsys && /^(metod|faktum)$/.test(qsys)) { sysSel.value = qsys; fillParts(qsys, ''); }

	/* ---------- Frontenliste: mehrere Fronten planen, ein Klick in den Warenkorb ---------- */
	var LKEY = 'kf24_list_' + P.id, list = [], active = -1;
	var TYPES = { tuer: 'Tür', schublade: 'Schublade', klappe: 'Klappe', blende: 'Blende', sonstig: 'Sonstiges' };
	try { list = JSON.parse(localStorage.getItem(LKEY)) || []; } catch (e) { list = []; }
	(function () { // items saved by older versions: fill missing fields so nothing breaks
		var d = state();
		list = (Array.isArray(list) ? list : []).filter(function (c) { return c && typeof c === 'object'; }).map(function (c) {
			Object.keys(d).forEach(function (k) { if (c[k] === undefined) c[k] = d[k]; });
			if (!Array.isArray(c.hpos)) c.hpos = []; if (!Array.isArray(c.hraw)) c.hraw = [];
			return c;
		});
	})();
	function save() { try { localStorage.setItem(LKEY, JSON.stringify(list)); } catch (e) { /* private mode */ } }
	function setVal(sel, v) { var el = form.querySelector(sel); if (el && v != null) el.value = v; }
	function toForm(c) {
		setRadio('type', c.type || 'tuer');
		form.querySelectorAll('input[name="kf24[decor]"]').forEach(function (r) { r.checked = r.value === c.decor; });
		setVal('#kf24-w', c.w || ''); setVal('#kf24-h', c.h || '');
		if (c.grain && c.grain !== 'laengs') setVal('#kf24-grain', c.grain);
		setVal('#kf24-label', c.label || ''); setVal('[name="kf24[note]"]', c.note || ''); setVal('[name="kf24[body]"]', c.body || '19');
		setRadio('hinge', c.hinge || 'keine'); setVal('#kf24-hinges', String(c.hinges || 2));
		$('input[name="kf24[deliver]"]').checked = c.deliver === 'yes';
		setRadio('grip', c.grip || '0'); setVal('#kf24-gpos', c.gpos || 'mitte');
		setVal('#kf24-ga', c.ga || ''); setVal('#kf24-gb', c.gb || 40); setVal('#kf24-gd', c.gd || 128);
		setRadio('mill', c.mill || 'keine'); if (P.finish) setRadio('finish', c.finish || 'lack'); var se = $('input[name="kf24[seal]"]'); if (se) se.checked = c.seal === 'yes';
		setVal('#kf24-qty', c.qty || 1);
		var cpp = c.part && catPart(c.part); partId.value = cpp ? c.part : '';
		if (cpp) { sysSel.value = cpp.s; fillParts(cpp.s, cpp.id); } else if (partSel.value) { partSel.value = ''; }
		var hasRaw = c.hraw && c.hraw.length === (c.hinge === 'keine' ? 0 : c.hinges) && c.hraw.length > 0;
		hposDirty = !!hasRaw; gaDirty = !!c.ga;
		renderHpos(state(), true, hasRaw ? c.hraw.slice() : null, c.hlast);
		$('.kf24-seal').hidden = (c.type || 'tuer') !== 'blende';
		sync();
	}
	function short(n) { return n.length > 22 ? n.slice(0, 21) + '…' : n; }
	function upd() { var ok = list[active].ok; list[active] = state(); if (ok === false) list[active].ok = false; save(); }
	function grainTxt(c) { return c.type === 'blende' || c.grain === 'laengs' ? 'Maserung längs' : c.grain === 'quer' ? 'Maserung ↔ quer' : 'Maserung ↕ aufrecht'; }
	function hingeTxt(c) {
		if (c.hinge === 'keine' || !c.hinges) return c.type === 'tuer' ? 'ohne Topfbandbohrung' : 'ohne Bohrung';
		return (c.hinge === 'oben' ? 'Bänder oben' : 'Anschlag ' + c.hinge) + ', ' + c.hinges + ' Bänder';
	}
	function thumb(c) { // mini drawing: proportion, grain direction, hinge holes
		if (!c.w || !c.h) return '<span class="kf24-th"></span>';
		var s = 30 / Math.max(c.w, c.h), w = c.w * s, h = c.h * s, x = (34 - w) / 2, y = (34 - h) / 2, o = '';
		var across = c.grain === 'quer' || ((c.grain === 'laengs' || c.type === 'blende') && c.w > c.h);
		for (var i = 1; i < 5; i++) o += across ? '<line x1="' + (x + 2) + '" x2="' + (x + w - 2) + '" y1="' + (y + h * i / 5) + '" y2="' + (y + h * i / 5) + '"/>' : '<line y1="' + (y + 2) + '" y2="' + (y + h - 2) + '" x1="' + (x + w * i / 5) + '" x2="' + (x + w * i / 5) + '"/>';
		var dots = c.hinge === 'keine' ? [] : hingePts(c).map(function (p) { return '<circle cx="' + (x + p[0] * s) + '" cy="' + (y + p[1] * s) + '" r="2.2"/>'; }).join('');
		return '<svg class="kf24-th" viewBox="0 0 34 34" aria-hidden="true"><rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" rx="1"/><g>' + o + '</g>' + dots + '</svg>';
	}
	function nextOpen(from) { for (var k = 1; k <= list.length; k++) { var j = (from + k) % list.length; if (list[j].ok === false) return j; } return -1; }
	function renderList() {
		var box = document.getElementById('kf24-list');
		box.hidden = list.length === 0;
		form.classList.toggle('kf24-has-list', list.length > 0);
		if (!list.length) {
			renderChips(sysSel.value); document.getElementById('kf24-check').hidden = true;
			$('#kf24-submit').hidden = false; $('#kf24-tolist').hidden = false; return;
		}
		var total = 0, bad = 0, chk = 0;
		document.getElementById('kf24-list-items').innerHTML = list.map(function (c, i) {
			var r = check(c), p = price(c); total += p.total; if (r.err.length) bad++;
			var open = c.ok === false; if (open && !r.err.length) chk++;
			return '<li class="' + (i === active ? 'is-active ' : '') + (r.err.length ? 'has-err' : open ? 'is-open' : 'is-ok') + '"><button type="button" class="kf24-li" data-i="' + i + '">' + thumb(c) + '<b>' + (i + 1) + '. ' + (c.label ? c.label.replace(/</g, '&lt;') : TYPES[c.type] || 'Front') + '</b><small>' + fmt(c.w) + ' × ' + fmt(c.h) + ' mm · ' + (c.decor ? short(c.decor).replace(/</g, '&lt;') : '<em>Material fehlt</em>') + (P.finish && c.finish === 'oel' ? ' · ' + S.finishes.oel[0].toLowerCase() : '') + '</small><small class="kf24-li-d">' + grainTxt(c) + ' · ' + hingeTxt(c) + '</small><span class="kf24-li-p">' + (r.err.length ? '<span class="kf24-li-x">' + r.err.length + ' offen</span>' : open ? '<span class="kf24-li-o">prüfen</span>' : eur(p.total)) + '</span></button><span class="kf24-li-q"><button type="button" data-q="' + i + '" data-d="-1" aria-label="Eins weniger">−</button><b>' + (c.qty || 1) + '</b><button type="button" data-q="' + i + '" data-d="1" aria-label="Eins mehr">+</button></span><button type="button" class="kf24-li-del" data-del="' + i + '" aria-label="Position ' + (i + 1) + ' entfernen">×</button></li>';
		}).join('');
		renderChips(sysSel.value);
		document.getElementById('kf24-list-n').textContent = list.length + (list.length === 1 ? ' Position' : ' Positionen');
		document.getElementById('kf24-list-total').textContent = eur(total);
		var btn = document.getElementById('kf24-list-cart');
		btn.disabled = bad > 0;
		btn.dataset.open = chk;
		btn.textContent = bad ? bad + ' Position' + (bad > 1 ? 'en' : '') + ' noch unvollständig' : chk ? 'Noch ' + chk + ' Position' + (chk > 1 ? 'en' : '') + ' prüfen' : 'Alle ' + list.length + ' in den Warenkorb';
		renderMode(chk);
		$('#kf24-submit').hidden = true; $('#kf24-tolist').hidden = true;
		$('#kf24-mtotal').textContent = eur(total);
		$('#kf24-msubmit').textContent = bad || chk ? 'Liste prüfen' : 'Alle ' + list.length + ' in den Warenkorb';
		$('#kf24-msubmit').disabled = false;
	}
	$('#kf24-msubmit').addEventListener('click', function (e) {
		if (!list.length) return;
		e.preventDefault();
		var b = document.getElementById('kf24-list-cart');
		if (b.disabled) document.getElementById('kf24-list').scrollIntoView({ behavior: 'smooth' }); else b.click();
	});
	/* Was bearbeite ich gerade? Ein Kasten über der Zeichnung sagt es – und hat den passenden Knopf. */
	function renderMode(chk) {
		var cb = document.getElementById('kf24-check'), t = document.getElementById('kf24-check-t'), ok = document.getElementById('kf24-check-ok'), ac = list[active];
		cb.hidden = !list.length; if (cb.hidden) return;
		var name = ac ? (ac.label || TYPES[ac.type] || 'Front').replace(/</g, '&lt;') : '';
		if (ac && ac.ok === false) {
			cb.className = 'kf24-check is-open';
			t.innerHTML = '<b class="kf24-check-h">Position ' + (active + 1) + ' prüfen: ' + name + '</b>' + grainTxt(ac) + ' · ' + hingeTxt(ac) + '.<br>Stimmt die Zeichnung? Sonst links ändern' + (ac.type === 'tuer' ? ' – vor allem den Anschlag' : '') + '. Änderungen gelten für diese Position.';
			ok.dataset.act = 'ok'; ok.textContent = '✓ Passt – in Frontenliste übernehmen';
		} else if (ac) {
			cb.className = 'kf24-check is-edit';
			t.innerHTML = '<b class="kf24-check-h">Sie bearbeiten Position ' + (active + 1) + ': ' + name + '</b>Jede Änderung links wird sofort in die Liste übernommen.';
			ok.dataset.act = 'done'; ok.textContent = 'Fertig – neue Front planen';
		} else {
			cb.className = 'kf24-check is-new';
			t.innerHTML = '<b class="kf24-check-h">Neue Front – noch nicht in der Liste</b>Links Maße und Bohrungen eingeben' + (sysSel.value ? ' oder oben ein Teil antippen' : '') + ', dann übernehmen.';
			ok.dataset.act = 'add'; ok.textContent = '+ In die Frontenliste übernehmen';
		}
		if (chk > 0 && ok.dataset.act === 'ok') ok.textContent += chk > 1 ? ' (noch ' + chk + ')' : '';
	}
	function freshForm() { // keep material, type and system – clear what belongs to one front
		var n = blankFrom(state()); n.w = 0; n.h = 0; n.part = ''; n.hraw = []; n.hlast = 'start'; delete n.ok;
		toForm(n); var l = $('#kf24-label'); l.dataset.auto = '1'; ownDims = false;
	}
	function newMode() { active = -1; freshForm(); renderList(); }
	function select(i) { active = i; toForm(list[i]); renderList(); form.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
	function blankFrom(c) { var n = JSON.parse(JSON.stringify(c)); n.label = ''; n.note = ''; n.qty = 1; return n; }

	// edits in the form update the active position
	['input', 'change'].forEach(function (ev) {
		form.addEventListener(ev, function () { if (active >= 0 && list[active]) { upd(); renderList(); } });
	});
	document.getElementById('kf24-tolist').addEventListener('click', function () {
		var c = state();
		if (c.w && c.h) { list.push(c); save(); }
		newMode();
	});
	document.getElementById('kf24-list').addEventListener('click', function (e) {
		var b = e.target.closest('button'); if (!b) return;
		if (b.dataset.q != null) {
			var qi = +b.dataset.q, nq = Math.max(1, Math.min(99, (+list[qi].qty || 1) + (+b.dataset.d)));
			list[qi].qty = nq; save(); if (qi === active) setVal('#kf24-qty', nq); renderList(); return;
		}
		if (b.dataset.i != null) return select(+b.dataset.i);
		if (b.dataset.del != null) {
			var di = +b.dataset.del;
			list.splice(di, 1); save();
			if (di === active || !list.length) { newMode(); } else { if (di < active) active--; renderList(); }
			return;
		}
		if (b.id === 'kf24-list-add') { newMode(); form.scrollIntoView({ behavior: 'smooth', block: 'start' }); return; }
		if (b.id === 'kf24-list-decor') { var d = radio('decor'); if (!d) return; var fi = radio('finish'); list.forEach(function (c) { c.decor = d; if (fi) c.finish = fi; }); save(); renderList(); return; }
		if (b.id === 'kf24-list-clear') {
			if (!b.dataset.sure) { b.dataset.sure = 1; b.textContent = 'Wirklich leeren?'; setTimeout(function () { delete b.dataset.sure; b.textContent = 'Liste leeren'; }, 3000); return; }
			list = []; save(); newMode(); return;
		}
		if (b.id === 'kf24-list-cart' && +b.dataset.open > 0) { var o = nextOpen(-1); if (o >= 0) { active = o; toForm(list[o]); renderList(); document.getElementById('kf24-check').scrollIntoView({ behavior: 'smooth', block: 'center' }); } return; }
		if (b.id === 'kf24-list-cart') {
			b.disabled = true; b.textContent = 'Wird in den Warenkorb gelegt …';
			var fd = new FormData(); fd.append('product_id', P.id); fd.append('items', JSON.stringify(list));
			fetch(D.bulk, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
				if (j && j.success) { list = []; save(); location.href = D.cart; return; }
				var errs = (j && j.data && j.data.errors) || {};
				var first = Object.keys(errs)[0];
				$('#kf24-msgs').innerHTML = Object.keys(errs).map(function (k) { return '<li class="kf24-err">Position ' + (+k + 1) + ': ' + errs[k].join(' ') + '</li>'; }).join('') || '<li class="kf24-err">Das hat nicht geklappt. Bitte erneut versuchen.</li>';
				if (first != null) select(+first);
				renderList();
			}).catch(function () { $('#kf24-msgs').innerHTML = '<li class="kf24-err">Keine Verbindung. Ihre Liste ist gespeichert – bitte erneut versuchen.</li>'; renderList(); });
		}
	});

	document.getElementById('kf24-check-ok').addEventListener('click', function () {
		var act = this.dataset.act;
		if (act === 'add') {
			var c = state(), r = check(c);
			if (r.err.length) { sync(); $('#kf24-msgs').scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
			delete c.ok; list.push(c); save(); newMode(); flash(list.length - 1); return;
		}
		if (act === 'ok' && list[active]) {
			var r2 = check(list[active]); if (r2.err.length) { sync(); return; }
			delete list[active].ok; save(); flash(active);
		}
		var o = nextOpen(active);
		if (o >= 0) { active = o; toForm(list[o]); renderList(); } else newMode();
	});
	function flash(i) { // short highlight so the customer sees where the front went
		var li = document.querySelectorAll('#kf24-list-items li')[i]; if (!li) return;
		li.classList.add('is-flash'); setTimeout(function () { li.classList.remove('is-flash'); }, 1200);
	}

	// Teile-Knöpfe: jedes angetippte Teil wird eine Position der Frontenliste
	var ownDims = false;
	form.addEventListener('input', function (e) { if (e.isTrusted && (e.target.id === 'kf24-w' || e.target.id === 'kf24-h')) ownDims = true; });
	document.getElementById('kf24-chips').addEventListener('click', function (e) {
		var b = e.target.closest('.kf24-chip'); if (!b) return;
		var cp = catPart(b.dataset.part); if (!cp) return;
		var same = -1;
		list.forEach(function (c, i) { if (c.part === cp.id && same < 0) same = i; });
		if (same >= 0) {
			list[same].qty = (+list[same].qty || 1) + 1; save();
			if (same === active) setVal('#kf24-qty', list[same].qty);
		} else {
			var cur = state();
			if (!list.length && ownDims && cur.w && cur.h && !cur.part) { list.push(cur); }
			var lbl = $('#kf24-label'); lbl.value = ''; lbl.dataset.auto = '1';
			var nt = form.querySelector('[name="kf24[note]"]'); if (nt) { nt.value = ''; nt.dataset.auto = '1'; }
			setVal('#kf24-qty', 1);
			applyPart(cp);
			var np = state(); np.ok = false; list.push(np); active = list.length - 1; save();
		}
		renderList(); renderChips(sysSel.value);
	});

	// list handed over from the Aufmaß table
	try {
		var hand = JSON.parse(localStorage.getItem('kf24_handover') || 'null');
		if (hand && hand.length) {
			localStorage.removeItem('kf24_handover');
			var base = state();
			list = hand.map(function (r) {
				var c = JSON.parse(JSON.stringify(base));
				c.type = r.t || 'tuer'; c.w = +r.w || 0; c.h = +r.h || 0; c.qty = Math.max(1, +r.q || 1);
				c.label = ((r.n || '') + (r.r ? ' – ' + r.r : '')).slice(0, 60);
				c.hinge = r.a || 'keine'; c.hinges = c.hinge === 'keine' ? 0 : Math.max(1, Math.min(5, +r.b || 2));
				c.grain = c.type === 'blende' ? 'laengs' : 'aufrecht'; c.hraw = []; c.hlast = 'start'; c.hpos = [];
				c.grip = '0'; c.mill = 'keine'; c.seal = 'no'; c.note = ''; c.decor = base.decor || '';
				var len = c.hinge === 'oben' ? c.w : c.h; c.hraw = defaultPositions(len, c.hinges); c.hpos = c.hraw.slice(); c.part = '';
				var cp = r.pid && catPart(r.pid);
				if (cp && cp.w === c.w && cp.h === c.h) {
					c.part = cp.id; c.note = cp.x || '';
					if (cp.hr && cp.hr.length === c.hinges) { c.hraw = cp.hr.slice(); c.hlast = cp.hl || 'start'; c.hpos = c.hraw.map(function (v, i) { return c.hlast === 'ende' && i === c.hraw.length - 1 ? len - v : v; }); }
				}
				if (c.type !== 'blende' && r.g === 'quer') c.grain = 'quer';
				var hp = String(r.hp || '').split(/[^0-9.,]+/).filter(Boolean).map(function (v) { return num(v); });
				if (c.hinges && hp.length === c.hinges) { c.hraw = hp; c.hlast = hp.length > 1 ? 'ende' : 'start'; c.hpos = hp.map(function (v, i) { return c.hlast === 'ende' && i === hp.length - 1 ? len - v : v; }); }
				if (r.gr === '1' || r.gr === '2') { c.grip = r.gr; c.gpos = r.gp || 'mitte'; if (+r.gd) c.gd = +r.gd; }
				if (r.mf === 'oben' || r.mf === 'unten') c.mill = r.mf;
				if (r.sl && c.type === 'blende') c.seal = 'yes';
				if (r.body === 16 || r.body === 19) c.body = String(r.body);
				if (P.finish && (r.fin === 'oel' || r.fin === 'lack')) c.finish = r.fin;
				c.ok = false;
				return c;
			});
			save();
		}
	} catch (e) { /* ignore */ }
	if (list.length) { var fo = nextOpen(-1); if (fo >= 0) { active = fo; toForm(list[fo]); renderList(); } else newMode(); }
	else renderList();
})();
