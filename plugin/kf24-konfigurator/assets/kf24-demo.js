/* Küchenfront24 – Testmodus: Begrüßung, Testliste, Feedback */
(function () {
	'use strict';
	var D = window.KF24DEMO;
	if (!D) return;
	var mem = {};
	var store = {
		get: function (k, d) { try { var v = localStorage.getItem('kf24demo_' + k); return v ? JSON.parse(v) : d; } catch (e) { return k in mem ? mem[k] : d; } },
		set: function (k, v) { mem[k] = v; try { localStorage.setItem('kf24demo_' + k, JSON.stringify(v)); } catch (e) { /* private mode */ } }
	};
	var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
	var el = function (html) { var t = document.createElement('template'); t.innerHTML = html.trim(); return t.content.firstChild; };
	var key = function (s) { return s[1]; };
	var who = D.name || 'Andreas';

	/* banner (front end only) */
	if (!D.admin) {
		var bar = el('<div class="kf24d-bar" role="note"><span><b>Testversion</b> – nichts wird wirklich bestellt, bezahlt oder verschickt.</span>' + (D.logged ? ' <a href="' + esc(D.shop) + '">Zur Verwaltung „Mein Shop“</a>' : '') + ' <button type="button" class="kf24d-open">Testliste &amp; Feedback</button></div>');
		document.body.insertBefore(bar, document.body.firstChild);
		document.documentElement.classList.add('kf24d-has-bar');
	}

	/* floating button + panel */
	var fab = el('<button type="button" class="kf24d-fab" aria-haspopup="dialog"><span class="kf24d-fab-ico" aria-hidden="true">✓</span> Testliste &amp; Feedback <span class="kf24d-fab-n"></span></button>');
	document.body.appendChild(fab);
	var panel = el('<aside class="kf24d-panel" role="dialog" aria-modal="false" aria-label="Testliste und Feedback" hidden>' +
		'<div class="kf24d-ph"><div class="kf24d-tabs" role="tablist"><button type="button" role="tab" data-t="list" aria-selected="true">Testliste</button><button type="button" role="tab" data-t="fb" aria-selected="false">Feedback <span class="kf24d-fbn"></span></button></div><button type="button" class="kf24d-x" aria-label="Schließen">×</button></div>' +
		'<div class="kf24d-body" data-p="list"><div class="kf24d-prog"><span></span></div><p class="kf24d-sm">Einfach von oben nach unten durchklicken. Haken setzt du selbst.</p><div class="kf24d-steps"></div></div>' +
		'<div class="kf24d-body" data-p="fb" hidden>' +
			'<label class="kf24d-lab" for="kf24d-txt">Was gefällt dir? Was soll anders werden?</label>' +
			'<textarea id="kf24d-txt" rows="4" placeholder="z. B. Die Startseite gefällt mir. Der Button sollte grün sein …"></textarea>' +
			'<label class="kf24d-chk"><input type="checkbox" id="kf24d-pg" checked> betrifft diese Seite: <i class="kf24d-pgname"></i></label>' +
			'<button type="button" class="kf24d-btn kf24d-save">Notiz speichern</button>' +
			'<ol class="kf24d-notes"></ol>' +
			'<div class="kf24d-send"><p><b>Fertig?</b> Schick alle Notizen an ' + esc(who) + ':</p><div class="kf24d-sendrow"><a class="kf24d-btn kf24d-wa" target="_blank" rel="noopener">Per WhatsApp</a><a class="kf24d-btn kf24d-ghost kf24d-mail" target="_blank" rel="noopener">Per E-Mail</a><button type="button" class="kf24d-btn kf24d-ghost kf24d-copy">Text kopieren</button></div><p class="kf24d-sm">Deine Notizen bleiben in diesem Browser gespeichert, auch wenn die Testseite neu startet.</p></div>' +
		'</div></aside>');
	document.body.appendChild(panel);
	var $ = function (s) { return panel.querySelector(s); };
	var pageName = (document.title || '').split(/[‹|–-]/)[0].trim() || location.pathname;
	$('.kf24d-pgname').textContent = pageName;

	function open(tab) { panel.hidden = false; fab.setAttribute('aria-expanded', 'true'); if (tab) showTab(tab); }
	function close() { panel.hidden = true; fab.setAttribute('aria-expanded', 'false'); }
	function showTab(t) {
		panel.querySelectorAll('[role=tab]').forEach(function (b) { b.setAttribute('aria-selected', String(b.dataset.t === t)); });
		panel.querySelectorAll('.kf24d-body').forEach(function (b) { b.hidden = b.dataset.p !== t; });
		store.set('tab', t);
	}
	fab.addEventListener('click', function () { if (panel.hidden) open(); else close(); });
	document.querySelectorAll('.kf24d-open').forEach(function (b) { b.addEventListener('click', function () { open('list'); }); });
	$('.kf24d-x').addEventListener('click', close);
	panel.addEventListener('click', function (e) { var b = e.target.closest('[role=tab]'); if (b) showTab(b.dataset.t); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) close(); });

	/* checklist */
	var done = store.get('done', {});
	function renderSteps() {
		var html = '', grp = '', n = 0;
		D.steps.forEach(function (s, i) {
			if (s[0] !== grp) { grp = s[0]; html += '<h3>' + esc(grp === 'Shop' ? 'Als Kunde im Shop' : 'Als Inhaber in der Verwaltung') + '</h3>'; }
			var on = !!done[key(s)]; if (on) n++;
			html += '<div class="kf24d-step' + (on ? ' is-done' : '') + '"><input type="checkbox" id="kf24d-s' + i + '" data-k="' + esc(key(s)) + '"' + (on ? ' checked' : '') + ' aria-label="erledigt"><div><a href="' + esc(s[2]) + '">' + (i + 1) + '. ' + esc(s[1]) + '</a>' + (s[3] ? '<small>' + esc(s[3]) + '</small>' : '') + '</div></div>';
		});
		$('.kf24d-steps').innerHTML = html;
		$('.kf24d-prog span').style.width = Math.round(n / D.steps.length * 100) + '%';
		fab.querySelector('.kf24d-fab-n').textContent = n + '/' + D.steps.length;
	}
	$('.kf24d-steps').addEventListener('change', function (e) {
		if (!e.target.dataset.k) return;
		if (e.target.checked) done[e.target.dataset.k] = 1; else delete done[e.target.dataset.k];
		store.set('done', done); renderSteps();
	});
	// clicking a step link keeps the panel open on the next page
	$('.kf24d-steps').addEventListener('click', function (e) { if (e.target.closest('a')) store.set('keepOpen', 1); });

	/* feedback */
	var notes = store.get('notes', []);
	function text() {
		return 'Feedback zur neuen Küchenfront24-Seite\n\n' + notes.map(function (n, i) { return (i + 1) + '. ' + (n.p ? '[' + n.p + '] ' : '') + n.t; }).join('\n\n');
	}
	function renderNotes() {
		$('.kf24d-notes').innerHTML = notes.map(function (n, i) {
			return '<li><div>' + (n.p ? '<b>' + esc(n.p) + ':</b> ' : '') + esc(n.t).replace(/\n/g, '<br>') + '</div><button type="button" data-i="' + i + '" aria-label="Notiz löschen">×</button></li>';
		}).join('');
		$('.kf24d-fbn').textContent = notes.length ? '(' + notes.length + ')' : '';
		$('.kf24d-send').hidden = !notes.length;
		var body = encodeURIComponent(text());
		$('.kf24d-wa').href = 'https://wa.me/' + (D.phone || '') + '?text=' + body;
		$('.kf24d-mail').href = 'mailto:' + encodeURIComponent(D.email || '') + '?subject=' + encodeURIComponent('Feedback neue Küchenfront24-Seite') + '&body=' + body;
	}
	$('.kf24d-save').addEventListener('click', function () {
		var t = $('#kf24d-txt').value.trim();
		if (!t) { $('#kf24d-txt').focus(); return; }
		notes.push({ t: t, p: $('#kf24d-pg').checked ? pageName : '', u: location.pathname, d: Date.now() });
		store.set('notes', notes); $('#kf24d-txt').value = ''; renderNotes();
		var b = $('.kf24d-save'); b.textContent = 'Gespeichert ✓'; setTimeout(function () { b.textContent = 'Notiz speichern'; }, 1600);
	});
	$('.kf24d-notes').addEventListener('click', function (e) {
		var b = e.target.closest('button[data-i]'); if (!b) return;
		notes.splice(+b.dataset.i, 1); store.set('notes', notes); renderNotes();
	});
	$('.kf24d-copy').addEventListener('click', function () {
		var b = this, t = text();
		var ok = function () { b.textContent = 'Kopiert ✓'; setTimeout(function () { b.textContent = 'Text kopieren'; }, 1800); };
		if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(t).then(ok, fallback); else fallback();
		function fallback() { var ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); ok(); } catch (e) { /* ignore */ } ta.remove(); }
	});

	renderSteps(); renderNotes(); showTab(store.get('tab', 'list'));
	if (store.get('keepOpen', 0)) { store.set('keepOpen', 0); open(); }

	/* welcome (once) */
	if (!store.get('welcomed', 0)) {
		var w = el('<div class="kf24d-welcome" role="dialog" aria-modal="true" aria-labelledby="kf24d-wt"><div class="kf24d-wbox">' +
			'<h2 id="kf24d-wt">Hallo Michi!</h2>' +
			'<p>Das ist die neue Küchenfront24 – Website, Shop und Verwaltung in einem. <b>Alles hier ist ein Testlauf:</b> Nichts wird bestellt, bezahlt oder verschickt. Du kannst nichts kaputt machen.</p>' +
			'<ol><li><b>Testliste</b> (Knopf „Testliste &amp; Feedback“ am unteren Rand): einfach von oben nach unten durchklicken.</li><li><b>Feedback:</b> Fällt dir etwas auf, schreib es direkt dort auf – das geht auf jeder Seite.</li><li>Am Ende: <b>„Per WhatsApp“</b> tippen und an ' + esc(who) + ' schicken.</li></ol>' +
			'<p class="kf24d-sm">Hinweis: Die Testseite läuft nur in diesem Browserfenster. Schließt du es, startet sie beim nächsten Klick auf den Link frisch. Deine Notizen bleiben gespeichert. Die Materialbilder sind vorerst Platzhalter.</p>' +
			'<div class="kf24d-wfoot"><button type="button" class="kf24d-btn kf24d-go">Los geht’s</button></div></div></div>');
		document.body.appendChild(w);
		w.querySelector('.kf24d-go').focus();
		w.querySelector('.kf24d-go').addEventListener('click', function () { store.set('welcomed', 1); w.remove(); open('list'); });
	}
})();
