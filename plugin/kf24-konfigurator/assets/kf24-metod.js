/* Zeichnung für Metod-Varianten */
jQuery(function ($) {
	var D = window.KF24MET || {}, box = document.getElementById('kf24-metod-draw');
	var $form = $('form.variations_form');
	if (!box || !$form.length) return;
	function val(re) {
		var v = '';
		$form.find('select[name^="attribute_"]').each(function () { if (re.test(this.name) || re.test($(this).closest('tr').find('label').text())) v = $(this).val() || ''; });
		return v;
	}
	function n(v) { return parseFloat(String(v).replace(/[^0-9.,]/g, '').replace(',', '.')) || 0; }
	function draw() {
		var w = n(val(/breite/i)), h = n(val(/h(oe|ö)he/i)), an = val(/anschlag/i).toLowerCase(), gf = val(/griff/i).toLowerCase(), grain = val(/maser/i).toLowerCase();
		if (!w || !h) { box.innerHTML = '<p class="kf24-hint">Wählen Sie Breite und Höhe, dann erscheint hier die Zeichnung.</p>'; return; }
		var W = box.clientWidth || 300, H = 300, s = Math.min((W - 90) / w, (H - 70) / h), fw = w * s, fh = h * s, ox = (W - fw) / 2 + 10, oy = 16;
		var fill = D.tex ? 'url(#kmt)' : D.color;
		var o = '<svg viewBox="0 0 ' + W + ' ' + H + '" width="' + W + '" height="' + H + '"><defs>' + (D.tex ? '<pattern id="kmt" patternUnits="userSpaceOnUse" width="240" height="240"' + (/quer|90/.test(grain) ? ' patternTransform="rotate(90)"' : '') + '><image href="' + D.tex + '" width="240" height="240" preserveAspectRatio="none"/></pattern>' : '') + '</defs>';
		o += '<rect x="' + ox + '" y="' + oy + '" width="' + fw + '" height="' + fh + '" fill="' + fill + '" stroke="#3b2a1e" stroke-width="1.5" rx="2"/>';
		if (/oben|unten/.test(gf)) o += '<rect x="' + ox + '" y="' + (/oben/.test(gf) ? oy : oy + fh - 7) + '" width="' + fw + '" height="7" fill="#2a2019" fill-opacity=".55"/>';
		if (/links|rechts/.test(an)) {
			var L = /links/.test(an), tip = L ? [ox, oy + fh / 2] : [ox + fw, oy + fh / 2], a = L ? [ox + fw, oy] : [ox, oy], b = L ? [ox + fw, oy + fh] : [ox, oy + fh];
			o += '<path d="M' + a + 'L' + tip + 'L' + b + '" fill="none" stroke="#fff" stroke-opacity=".8" stroke-dasharray="6 5"/>';
		}
		o += '<text x="' + (ox + fw / 2) + '" y="' + (oy + fh + 22) + '" text-anchor="middle" class="kf24-d">' + w + ' mm</text>';
		o += '<text x="' + (ox - 14) + '" y="' + (oy + fh / 2) + '" text-anchor="middle" class="kf24-d" transform="rotate(-90 ' + (ox - 14) + ' ' + (oy + fh / 2) + ')">' + h + ' mm</text></svg>';
		box.innerHTML = o;
	}
	$form.on('change', 'select', draw).on('found_variation reset_data', draw);
	draw();
});
