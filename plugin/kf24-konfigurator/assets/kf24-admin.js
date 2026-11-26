/* Küchenfront24 – Mein Shop (Fronten, Materialien, Drag & Drop) */
(function () {
	'use strict';
	var A = window.KF24ADM || {};
	var $ = function (s, r) { return (r || document).querySelector(s); };
	var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
	var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };

	function post(action, data, file) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', A.nonce);
		Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
		if (file) fd.append('file', file);
		return fetch(A.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { throw new Error('Server-Antwort unlesbar (' + r.status + ')'); }); })
			.then(function (j) { if (!j || !j.success) throw new Error((j && j.data && j.data.msg) || 'Das hat nicht geklappt.'); return j.data; });
	}

	var toastBox;
	function toast(msg, bad) {
		if (!toastBox) { toastBox = document.createElement('div'); toastBox.className = 'kf24a-toasts'; toastBox.setAttribute('role', 'status'); document.body.appendChild(toastBox); }
		var t = document.createElement('div');
		t.className = 'kf24a-toast' + (bad ? ' is-bad' : '');
		t.textContent = msg;
		toastBox.appendChild(t);
		setTimeout(function () { t.classList.add('is-out'); }, 3200);
		setTimeout(function () { t.remove(); }, 3800);
	}

	/** Make an element a drop zone; cb(files). Click opens the file dialog. */
	function dropzone(el, cb, multiple) {
		var input = $(multiple ? '.kf24a-files' : '.kf24a-file') || (function () { var i = document.createElement('input'); i.type = 'file'; i.accept = 'image/*'; i.hidden = true; if (multiple) i.multiple = true; document.body.appendChild(i); return i; })();
		['dragenter', 'dragover'].forEach(function (ev) { el.addEventListener(ev, function (e) { e.preventDefault(); el.classList.add('is-over'); }); });
		['dragleave', 'drop'].forEach(function (ev) { el.addEventListener(ev, function (e) { e.preventDefault(); el.classList.remove('is-over'); }); });
		el.addEventListener('drop', function (e) { var f = Array.prototype.slice.call(e.dataTransfer.files || []).filter(isImg); if (f.length) cb(multiple ? f : [f[0]]); else toast('Bitte ein Bild (JPG, PNG, WebP) hineinziehen.', true); });
		function pick() { input.value = ''; input.onchange = function () { var f = Array.prototype.slice.call(input.files || []).filter(isImg); if (f.length) cb(multiple ? f : [f[0]]); }; input.click(); }
		el.addEventListener('click', function (e) { if (!e.target.closest('a,button,input')) pick(); });
		el.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(); } });
	}
	function isImg(f) { return /^image\//.test(f.type); }

	// stop the browser from opening files dropped next to a zone
	window.addEventListener('dragover', function (e) { if (e.target.closest && !e.target.closest('.kf24a-drop,.kf24a-mat')) e.preventDefault(); });
	window.addEventListener('drop', function (e) { if (e.target.closest && !e.target.closest('.kf24a-drop,.kf24a-mat')) e.preventDefault(); });

	/* ---------------- Fronts list ---------------- */
	$$('.kf24a-search').forEach(function (s) {
		s.addEventListener('input', function () {
			var q = s.value.toLowerCase(), any = false;
			$$(s.dataset.filter).forEach(function (el) { var hit = !q || (el.dataset.name || '').toLowerCase().indexOf(q) >= 0; el.hidden = !hit; any = any || hit; });
			var e = $('.kf24a-empty'); if (e) e.hidden = any;
		});
	});
	$$('.kf24a-front').forEach(function (row) {
		var id = row.dataset.id;
		var sw = $('.kf24a-status', row);
		sw.addEventListener('change', function () {
			post('kf24_front_status', { id: id, on: sw.checked ? 1 : '' }).then(function () {
				row.classList.toggle('is-off', !sw.checked);
				$('.kf24a-switch em', row).textContent = sw.checked ? 'online' : 'offline';
				toast(sw.checked ? 'Front ist jetzt im Shop sichtbar.' : 'Front ist jetzt offline.');
			}).catch(function (e) { sw.checked = !sw.checked; toast(e.message, true); });
		});
		$('.kf24a-dup', row).addEventListener('click', function () { post('kf24_front_duplicate', { id: id }).then(function (d) { location.href = d.redirect; }).catch(function (e) { toast(e.message, true); }); });
		$('.kf24a-del', row).addEventListener('click', function () { confirmDelete(id, row.dataset.name); });
	});
	var delOne = $('.kf24a-del-one');
	if (delOne) delOne.addEventListener('click', function () { confirmDelete(delOne.dataset.id, $('[name=name]').value); });

	function confirmDelete(id, name) {
		ask('„' + name + '“ löschen?', 'Die Front verschwindet aus dem Shop und landet im Papierkorb. Bestehende Bestellungen bleiben erhalten.', 'Ja, löschen').then(function (ok) {
			if (ok) post('kf24_front_delete', { id: id }).then(function (d) { location.href = d.redirect; }).catch(function (e) { toast(e.message, true); });
		});
	}

	/** Small promise-based confirm dialog (no browser alerts). */
	function ask(title, text, yes) {
		return new Promise(function (res) {
			var d = document.createElement('dialog');
			d.className = 'kf24a-dialog kf24a-ask';
			d.innerHTML = '<h2>' + esc(title) + '</h2><p>' + esc(text) + '</p><div class="kf24a-dlg-foot"><span class="kf24a-spacer"></span><button type="button" class="button" value="0">Abbrechen</button><button type="button" class="button button-primary kf24a-danger" value="1">' + esc(yes) + '</button></div>';
			document.body.appendChild(d);
			$$('button', d).forEach(function (b) { b.addEventListener('click', function () { d.close(); d.remove(); res(b.value === '1'); }); });
			d.addEventListener('cancel', function () { d.remove(); res(false); });
			d.showModal();
		});
	}

	/* ---------------- Front editor ---------------- */
	var form = $('#kf24a-front-form');
	if (form) {
		var fieldNames = { Dekor: 'Dekor', Furnier: 'Holzart', Massivholz: 'Holzart', Lack: 'Lackfarbe', Fenix: 'Fenix-Dekor' };
		var grp = $('#kf24a-group'), fld = $('#kf24a-field');
		grp.addEventListener('change', function () {
			var known = Object.keys(fieldNames).map(function (k) { return fieldNames[k]; });
			if (!fld.value || known.indexOf(fld.value) >= 0) fld.value = fieldNames[grp.value] || fld.value;
		});

		// product image
		var pz = $('.kf24a-drop-product'), pic = $('.kf24a-drop-pic', pz), hid = $('[name=image_id]', form), rm = $('.kf24a-img-remove');
		dropzone(pz, function (files) {
			pz.classList.add('is-busy');
			post('kf24_upload', {}, files[0]).then(function (d) { hid.value = d.id; pic.style.backgroundImage = 'url(' + d.url + ')'; rm.hidden = false; toast('Bild hochgeladen – jetzt noch speichern.'); })
				.catch(function (e) { toast(e.message, true); }).then(function () { pz.classList.remove('is-busy'); });
		});
		rm.addEventListener('click', function () { hid.value = 0; pic.style.backgroundImage = ''; rm.hidden = true; });

		// material picker
		var pk = $('.kf24a-picker'), mats = JSON.parse(pk.dataset.mats), sel = JSON.parse(pk.dataset.selected);
		var grid = $('.kf24a-pick-grid', pk), chips = $('.kf24a-chips', pk), q = $('.kf24a-pick-search', pk), only = $('.kf24a-pick-only', pk), ta = $('#kf24a-options');
		var groups = A.groups.filter(function (g) { return mats.some(function (m) { return m.g === g; }); });
		var active = '';
		// pre-select the group that holds most selected materials
		var best = 0; groups.forEach(function (g) { var n = mats.filter(function (m) { return m.g === g && sel.indexOf(m.n) >= 0; }).length; if (n > best) { best = n; active = g; } });
		function renderChips() {
			chips.innerHTML = '<button type="button" data-g=""' + (active ? '' : ' class="is-on"') + '>Alle</button>' + groups.map(function (g) {
				var n = mats.filter(function (m) { return m.g === g && sel.indexOf(m.n) >= 0; }).length;
				return '<button type="button" data-g="' + esc(g) + '"' + (active === g ? ' class="is-on"' : '') + '>' + esc(g) + (n ? ' <b>' + n + '</b>' : '') + '</button>';
			}).join('');
		}
		function visible() {
			var s = q.value.toLowerCase();
			return mats.filter(function (m) { return (!active || m.g === active) && (!s || m.n.toLowerCase().indexOf(s) >= 0) && (!only.checked || sel.indexOf(m.n) >= 0); });
		}
		function render() {
			renderChips();
			grid.innerHTML = visible().map(function (m) {
				var on = sel.indexOf(m.n) >= 0;
				return '<label class="kf24a-pick' + (on ? ' is-on' : '') + '" title="' + esc(m.n) + '"><input type="checkbox" value="' + esc(m.n) + '"' + (on ? ' checked' : '') + '><span class="kf24a-pick-img" style="background-color:' + esc(m.c) + (m.i ? ';background-image:url(' + esc(m.i) + ')' : '') + '"></span><span class="kf24a-pick-n">' + esc(m.n) + '</span></label>';
			}).join('') || '<p class="kf24a-hint">Nichts gefunden.</p>';
			ta.value = sel.join('\n');
			$('#kf24a-count').textContent = sel.length;
		}
		grid.addEventListener('change', function (e) {
			var v = e.target.value, i = sel.indexOf(v);
			if (e.target.checked && i < 0) sel.push(v);
			if (!e.target.checked && i >= 0) sel.splice(i, 1);
			e.target.closest('.kf24a-pick').classList.toggle('is-on', e.target.checked);
			ta.value = sel.join('\n'); $('#kf24a-count').textContent = sel.length; renderChips();
		});
		chips.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) { active = b.dataset.g; render(); } });
		q.addEventListener('input', render); only.addEventListener('change', render);
		$('.kf24a-pick-all', pk).addEventListener('click', function () { visible().forEach(function (m) { if (sel.indexOf(m.n) < 0) sel.push(m.n); }); render(); });
		$('.kf24a-pick-none', pk).addEventListener('click', function () { var v = visible().map(function (m) { return m.n; }); sel = sel.filter(function (n) { return v.indexOf(n) < 0; }); render(); });
		render();

		var dirty = false;
		form.addEventListener('input', function () { dirty = true; });
		form.addEventListener('change', function () { dirty = true; });
		form.addEventListener('submit', function (e) {
			if (!sel.length) { e.preventDefault(); toast('Bitte mindestens ein Material auswählen.', true); pk.scrollIntoView({ behavior: 'smooth' }); return; }
			dirty = false;
		});
		window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
	}

	/* ---------------- Material library ---------------- */
	var wrap = $('.kf24a-mats');
	if (wrap) {
		var all = JSON.parse(wrap.dataset.mats), mg = '', ms = $('.kf24a-mat-search'), ph = $('.kf24a-mat-ph'), gbox = $('.kf24a-mat-groups');
		var dlg = $('#kf24a-mat-dialog'), cur = null, pending = null;

		function tile(m) {
			return '<div class="kf24a-mat" data-id="' + m.id + '" tabindex="0" role="button" aria-label="' + esc(m.name) + ' bearbeiten">' +
				'<span class="kf24a-mat-img" style="background-color:' + esc(m.color) + (m.img ? ';background-image:url(' + esc(m.img) + ')' : '') + '"></span>' +
				(m.placeholder ? '<span class="kf24a-tag">Platzhalter</span>' : '') + (m.photo ? '<span class="kf24a-tag is-photo">Foto</span>' : '') +
				'<span class="kf24a-mat-n">' + esc(m.name) + '</span><span class="kf24a-mat-u">' + (m.used.length ? 'in ' + m.used.length + ' Front' + (m.used.length > 1 ? 'en' : '') : 'nicht verwendet') + '</span>' +
				'<span class="kf24a-mat-drop">Foto loslassen zum Ersetzen</span></div>';
		}
		function filtered() {
			var s = ms.value.toLowerCase();
			return all.filter(function (m) { return (!mg || m.group === mg) && (!s || (m.name + ' ' + m.code).toLowerCase().indexOf(s) >= 0) && (!ph.checked || m.placeholder); });
		}
		function renderGroups() {
			gbox.innerHTML = '<button type="button" data-g=""' + (mg ? '' : ' class="is-on"') + '>Alle <small>' + all.length + '</small></button>' + A.groups.map(function (g) {
				var n = all.filter(function (m) { return m.group === g; }).length;
				return n ? '<button type="button" data-g="' + esc(g) + '"' + (mg === g ? ' class="is-on"' : '') + '>' + esc(g) + ' <small>' + n + '</small></button>' : '';
			}).join('');
		}
		function renderMats() {
			renderGroups();
			var f = filtered();
			wrap.innerHTML = f.map(tile).join('');
			$('.kf24a-empty').hidden = f.length > 0;
			$$('.kf24a-mat', wrap).forEach(bindTile);
		}
		function find(id) { return all.filter(function (m) { return String(m.id) === String(id); })[0]; }
		function update(d) {
			var i = all.findIndex(function (m) { return String(m.id) === String(d.id); });
			if (i >= 0) all[i] = Object.assign({}, all[i], d); else all.unshift(Object.assign({ used: [] }, d));
		}
		function bindTile(el) {
			var m = find(el.dataset.id);
			el.addEventListener('click', function () { openDlg(m); });
			el.addEventListener('keydown', function (e) { if (e.key === 'Enter') openDlg(m); });
			['dragenter', 'dragover'].forEach(function (ev) { el.addEventListener(ev, function (e) { e.preventDefault(); el.classList.add('is-over'); }); });
			['dragleave', 'drop'].forEach(function (ev) { el.addEventListener(ev, function (e) { e.preventDefault(); el.classList.remove('is-over'); }); });
			el.addEventListener('drop', function (e) {
				var f = Array.prototype.slice.call(e.dataTransfer.files || []).filter(isImg)[0];
				if (!f) return toast('Bitte ein Bild hineinziehen.', true);
				el.classList.add('is-busy');
				post('kf24_mat_photo', { id: m.id }, f).then(function (d) { update(d); renderMats(); toast('Foto für „' + d.name + '“ übernommen. Im Shop sofort sichtbar.'); })
					.catch(function (er) { el.classList.remove('is-busy'); toast(er.message, true); });
			});
		}
		gbox.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) { mg = b.dataset.g; renderMats(); } });
		ms.addEventListener('input', renderMats); ph.addEventListener('change', renderMats);

		// dialog
		var fm = $('.kf24a-dlg-form', dlg), E = fm.elements, dz = $('.kf24a-drop-mat', dlg), dpic = $('.kf24a-drop-pic', dz);
		function openDlg(m) {
			cur = m || null; pending = null;
			$('.kf24a-dlg-title', dlg).textContent = m ? 'Material bearbeiten' : 'Neues Material';
			E['name'].value = m ? m.name : '';
			E['code'].value = m ? m.code : '';
			E['group'].value = m ? m.group : (mg || A.groups[0]);
			E['color'].value = m && /^#[0-9a-f]{6}$/i.test(m.color) ? m.color : '#cccccc';
			dpic.style.backgroundImage = m && m.img ? 'url(' + m.img + ')' : '';
			dpic.style.backgroundColor = E['color'].value;
			$('.kf24a-photo-remove', dlg).hidden = !(m && m.photo);
			$('.kf24a-mat-delete', dlg).hidden = !m;
			$('.kf24a-used', dlg).innerHTML = m ? (m.used.length ? 'Verwendet in: ' + m.used.map(esc).join(', ') : 'Wird noch in keiner Front verwendet.') + (m.placeholder ? '<br><b>Tipp:</b> Das Bild ist ein Platzhalter. Zieh ein echtes Foto deines Musters links hinein.' : '') : '';
			dlg.showModal();
			E['name'].focus();
		}
		E['color'].addEventListener('input', function () { dpic.style.backgroundColor = E['color'].value; });
		dropzone(dz, function (files) { pending = files[0]; dpic.style.backgroundImage = 'url(' + URL.createObjectURL(pending) + ')'; });
		$('.kf24a-mat-new').addEventListener('click', function () { openDlg(null); });
		$('.kf24a-dlg-close', dlg).addEventListener('click', function () { dlg.close(); });
		$('.kf24a-mat-save', dlg).addEventListener('click', function () {
			if (!E['name'].value.trim()) { E['name'].focus(); return toast('Bitte einen Namen eingeben.', true); }
			var btn = this; btn.disabled = true;
			post('kf24_mat_save', { id: cur ? cur.id : 0, name: E['name'].value, code: E['code'].value, group: E['group'].value, color: E['color'].value }, pending)
				.then(function (d) { update(d); dlg.close(); renderMats(); toast('Gespeichert.'); })
				.catch(function (e) { toast(e.message, true); }).then(function () { btn.disabled = false; });
		});
		$('.kf24a-photo-remove', dlg).addEventListener('click', function () {
			post('kf24_mat_photo_remove', { id: cur.id }).then(function (d) { update(d); dlg.close(); renderMats(); toast('Eigenes Foto entfernt.'); }).catch(function (e) { toast(e.message, true); });
		});
		$('.kf24a-mat-delete', dlg).addEventListener('click', function () {
			var m = cur; dlg.close();
			ask('„' + m.name + '“ löschen?', m.used.length ? 'Es wird auch aus ' + m.used.length + ' Front(en) entfernt: ' + m.used.join(', ') + '.' : 'Das Material wird in keiner Front verwendet.', 'Ja, löschen').then(function (ok) {
				if (!ok) return;
				post('kf24_mat_delete', { id: m.id }).then(function () { all = all.filter(function (x) { return x.id !== m.id; }); renderMats(); toast('Gelöscht.'); }).catch(function (e) { toast(e.message, true); });
			});
		});

		// bulk
		var prog = $('.kf24a-progress');
		dropzone($('.kf24a-drop-bulk'), function (files) {
			var g = $('.kf24a-bulk-group').value, done = 0, okN = 0;
			prog.hidden = false; prog.textContent = 'Lade 0 von ' + files.length + ' …';
			files.reduce(function (p, f) {
				return p.then(function () {
					return post('kf24_mat_bulk', { group: g }, f).then(function (d) { update(d); okN++; }).catch(function (e) { toast(f.name + ': ' + e.message, true); })
						.then(function () { done++; prog.textContent = 'Lade ' + done + ' von ' + files.length + ' …'; });
				});
			}, Promise.resolve()).then(function () { prog.textContent = okN + ' Foto(s) übernommen.'; renderMats(); setTimeout(function () { prog.hidden = true; }, 4000); });
		}, true);

		var startQ = new URLSearchParams(location.search).get('q');
		if (startQ) ms.value = startQ;
		renderMats();
	}
	/* ---------------- Reference editor ---------------- */
	var rf = $('#kf24a-ref-form');
	if (rf) {
		var layout = function () {
			var v = (rf.querySelector('[name=layout]:checked') || {}).value;
			$('.kf24a-ref-ba', rf).hidden = v !== 'vorher-nachher';
			$('.kf24a-ref-cover', rf).hidden = v === 'vorher-nachher';
			$('.kf24a-ref-gal', rf).hidden = v === 'einzelbild';
		};
		rf.addEventListener('change', function (e) { if (e.target.name === 'layout') layout(); });
		layout();
		$$('.kf24a-drop-one', rf).forEach(function (z) {
			var w = z.parentNode, hid = $('input[type=hidden]', w), pic = $('.kf24a-drop-pic', z), rm = $('.kf24a-one-remove', w);
			dropzone(z, function (files) {
				z.classList.add('is-busy');
				post('kf24_upload', {}, files[0]).then(function (d) { hid.value = d.id; pic.style.backgroundImage = 'url(' + d.url + ')'; rm.hidden = false; })
					.catch(function (e) { toast(e.message, true); }).then(function () { z.classList.remove('is-busy'); });
			});
			rm.addEventListener('click', function () { hid.value = 0; pic.style.backgroundImage = ''; rm.hidden = true; });
		});
		var gbox = $('.kf24a-gal', rf), gin = $('[name=gallery]', rf), items = JSON.parse(gbox.dataset.items || '[]');
		var renderGal = function () {
			gin.value = items.map(function (i) { return i.id; }).join(',');
			gbox.innerHTML = items.map(function (it, i) {
				return '<div class="kf24a-gal-i" style="background-image:url(' + esc(it.url) + ')"><button type="button" data-mv="-1" data-i="' + i + '" aria-label="nach vorne">←</button><button type="button" data-mv="1" data-i="' + i + '" aria-label="nach hinten">→</button><button type="button" data-rm="' + i + '" aria-label="entfernen">×</button></div>';
			}).join('');
		};
		gbox.addEventListener('click', function (e) {
			var b = e.target.closest('button'); if (!b) return;
			if (b.dataset.rm != null) items.splice(+b.dataset.rm, 1);
			if (b.dataset.mv) { var i = +b.dataset.i, j = i + (+b.dataset.mv); if (j >= 0 && j < items.length) { var t = items[i]; items[i] = items[j]; items[j] = t; } }
			renderGal();
		});
		var many = $('.kf24a-drop-many', rf);
		dropzone(many, function (files) {
			many.classList.add('is-busy');
			files.reduce(function (p, f) { return p.then(function () { return post('kf24_upload', {}, f).then(function (d) { items.push({ id: d.id, url: d.url }); renderGal(); }).catch(function (e) { toast(f.name + ': ' + e.message, true); }); }); }, Promise.resolve())
				.then(function () { many.classList.remove('is-busy'); });
		}, true);
		renderGal();
		rf.addEventListener('submit', function (e) {
			var v = rf.querySelector('[name=layout]:checked').value;
			if (v === 'vorher-nachher' && (!+rf.elements['before'].value || !+rf.elements['after'].value)) { e.preventDefault(); toast('Für Vorher / Nachher bitte beide Fotos hineinziehen.', true); }
		});
		var rdel = $('.kf24a-ref-del', rf);
		if (rdel) rdel.addEventListener('click', function () {
			ask('Referenz löschen?', 'Sie verschwindet von der Website und landet im Papierkorb.', 'Ja, löschen').then(function (ok) {
				if (ok) post('kf24_ref_delete', { id: rdel.dataset.id }).then(function (d) { location.href = d.redirect; }).catch(function (e) { toast(e.message, true); });
			});
		});
	}
	/* ---------------- Metod & Faktum: prices, formula, parts ---------------- */
	var dec = function (v) { var n = parseFloat(String(v).replace(/\./g, '').replace(',', '.')); return isNaN(n) ? 0 : n; };
	var pf = $('#kf24a-prices');
	if (pf) {
		var changed = {};
		pf.addEventListener('input', function (e) {
			var t = e.target; if (!t.dataset.f) return;
			(changed[t.dataset.f] = changed[t.dataset.f] || {})[t.dataset.p] = t.value.trim();
			t.classList.toggle('is-set', t.value.trim() !== '');
			var n = Object.keys(changed).reduce(function (a, k) { return a + Object.keys(changed[k]).length; }, 0);
			$('.kf24a-changed').textContent = n + ' Änderung' + (n === 1 ? '' : 'en') + ' noch nicht gespeichert';
		});
		$$('input[data-f]', pf).forEach(function (i) { i.classList.toggle('is-set', i.value.trim() !== ''); });
		pf.addEventListener('submit', function (e) {
			e.preventDefault();
			post('kf24_save_prices', { data: JSON.stringify(changed) }).then(function () { changed = {}; $('.kf24a-changed').textContent = ''; toast('Preise gespeichert. Im Shop sofort aktiv.'); }).catch(function (er) { toast(er.message, true); });
		});
	}
	var ff = $('#kf24a-formula');
	if (ff) {
		var ex = function (tr) {
			var num = function (v) { var n = parseFloat(String(v).replace(',', '.')); return isNaN(n) ? 0 : n; }, g = function (n) { var i = $('[name=' + n + ']', tr); return i ? num(i.value) : 0; };
			var eu = function (v) { return v.toLocaleString('de-DE', { style: 'currency', currency: 'EUR' }); };
			var fx = g('factor') > 0 ? g('factor') : 1, oilIn = $('[name=oil_m2]', tr);
			var w = 597, h = 797, area = w * h / 1e6, raw = area * g('m2') + 2 * (w + h) / 1000 * g('edge') + g('piece'), calc = raw * fx, v = Math.max(calc, g('minprice'));
			var html = '<b>' + eu(v) + '</b><small> = ' + (fx !== 1 ? '(' : '') + area.toFixed(3).replace('.', ',') + ' m² × ' + g('m2') + (g('edge') ? ' + 2,79 lfm × ' + g('edge') : '') + (g('piece') ? ' + ' + g('piece') : '') + (fx !== 1 ? ') × ' + String(fx).replace('.', ',') : '') + (g('minprice') > calc ? ' → Mindestpreis' : '') + '</small>';
			if (oilIn) {
				var om = oilIn.value.trim() === '' ? +ff.dataset.oil : num(oilIn.value), oil = Math.max(+ff.dataset.oilmin || 0, area * om);
				html += '<br><small>geölt: <b>' + eu(v + oil) + '</b> (+ ' + eu(oil) + ')</small>';
			}
			$('.kf24a-ex', tr).innerHTML = html;
		};
		$$('tbody tr', ff).forEach(ex);
		ff.addEventListener('input', function (e) { ex(e.target.closest('tr')); });
		ff.addEventListener('submit', function (e) {
			e.preventDefault();
			var data = {};
			$$('tbody tr', ff).forEach(function (tr) { var o = {}; $$('input', tr).forEach(function (i) { o[i.name] = i.value; }); data[tr.dataset.id] = o; });
			post('kf24_save_formula', { data: JSON.stringify(data) }).then(function () { toast('Formeln gespeichert. Die Preise im Shop rechnen ab sofort damit.'); }).catch(function (er) { toast(er.message, true); });
		});
	}
	var pt = $('#kf24a-parts');
	if (pt) {
		var rows = JSON.parse($('#kf24a-parts-data').textContent || '[]');
		var TY = { tuer: 'Tür', schublade: 'Schublade', klappe: 'Klappe', blende: 'Blende', sonstig: 'Sonstiges' }, AN = { links: 'links', rechts: 'rechts', oben: 'oben', keine: 'keine' };
		var opt = function (map, v) { return Object.keys(map).map(function (k) { return '<option value="' + k + '"' + (k === v ? ' selected' : '') + '>' + map[k] + '</option>'; }).join(''); };
		var rowHtml = function (r) {
			return '<tr data-id="' + esc(r.id || '') + '"><td><input name="group" value="' + esc(r.group || '') + '"></td><td><input name="name" value="' + esc(r.name || '') + '"></td><td><input name="w" inputmode="decimal" value="' + esc(r.w || '') + '"></td><td><input name="h" inputmode="decimal" value="' + esc(r.h || '') + '"></td><td><select name="type">' + opt(TY, r.type || 'tuer') + '</select></td><td><select name="hinge">' + opt(AN, r.hinge || 'keine') + '</select></td><td><input name="hinges" type="number" min="0" max="5" value="' + esc(r.hinges || 0) + '"></td><td><input name="hraw" value="' + esc((r.hraw || []).join(', ')) + '" placeholder="z. B. 59, 59"></td><td><input name="hlast" type="checkbox"' + (r.hlast === 'ende' ? ' checked' : '') + '></td><td><input name="note" value="' + esc(r.note || '') + '"></td><td><button type="button" class="button-link kf24a-part-del" aria-label="Teil löschen">×</button></td></tr>';
		};
		var tb = $('tbody', pt);
		tb.innerHTML = rows.map(rowHtml).join('');
		$('.kf24a-part-add', pt).addEventListener('click', function () { tb.insertAdjacentHTML('beforeend', rowHtml({ group: 'Türen', type: 'tuer', hinge: 'links', hinges: 2 })); tb.lastElementChild.querySelector('[name=name]').focus(); });
		tb.addEventListener('click', function (e) { var b = e.target.closest('.kf24a-part-del'); if (b) b.closest('tr').remove(); });
		pt.addEventListener('submit', function (e) {
			e.preventDefault();
			var data = $$('tr', tb).map(function (tr) { var o = { id: tr.dataset.id }; $$('input,select', tr).forEach(function (i) { o[i.name] = i.type === 'checkbox' ? i.checked : i.value; }); return o; });
			post('kf24_save_parts', { sys: pt.dataset.sys, data: JSON.stringify(data) }).then(function () { toast('Teileliste gespeichert.'); }).catch(function (er) { toast(er.message, true); });
		});
	}
})();
