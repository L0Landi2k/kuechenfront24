/* Küchenfront24 Theme – Menü, Cookie-Einwilligung, Küchen-Illustrationen */
(function () {
'use strict';
var K = window.KF24SITE || {};
var IMG = K.img || '';
var $ = function (s) { return document.querySelector(s); }, $$ = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };
var store = { get: function (k, d) { try { var v = localStorage.getItem('kf24_' + k); return v ? JSON.parse(v) : d; } catch (e) { return d; } }, set: function (k, v) { try { localStorage.setItem('kf24_' + k, JSON.stringify(v)); } catch (e) {} } };

/* menu drawer */
var menu = $('#menu'), mb = $('#menuBtn');
if (menu && mb) {
	mb.addEventListener('click', function () { menu.classList.add('open'); mb.setAttribute('aria-expanded', 'true'); });
	$$('#menu [data-close]').forEach(function (el) { el.addEventListener('click', function () { menu.classList.remove('open'); mb.setAttribute('aria-expanded', 'false'); }); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') menu.classList.remove('open'); });
}

/* consent + GA4 (only loaded after opt-in) */
function loadGA() {
	if (!K.ga || window.__kf24ga) return; window.__kf24ga = 1;
	var s = document.createElement('script'); s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(K.ga); document.head.appendChild(s);
	window.dataLayer = window.dataLayer || []; window.gtag = function () { dataLayer.push(arguments); };
	gtag('js', new Date()); gtag('config', K.ga, { anonymize_ip: true });
}
var ck = $('#cookie'), consent = store.get('consent', null);
if (consent && consent.stats) loadGA();
if (ck) {
	if (!consent && K.ga) ck.classList.add('open');
	var setC = function (v) { store.set('consent', v); ck.classList.remove('open'); if (v.stats) loadGA(); else if (window.__kf24ga) location.reload(); };
	$('#ckNec').addEventListener('click', function () { setC({ stats: false }); });
	$('#ckAll').addEventListener('click', function () { setC({ stats: true }); });
	$('#ckSet').addEventListener('click', function () { var m = $('#ckMore'); if (m.hidden) { m.hidden = false; this.textContent = 'Auswahl speichern'; $('#ckStats').checked = !!(store.get('consent', {}) || {}).stats; } else setC({ stats: $('#ckStats').checked }); });
	var cl = $('#cookieLink'); if (cl) cl.addEventListener('click', function (e) { e.preventDefault(); ck.classList.add('open'); });
}

if (!document.querySelector('[data-scene], #heroScene')) return;
var defs = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
defs.setAttribute('width', '0'); defs.setAttribute('height', '0'); defs.setAttribute('aria-hidden', 'true'); defs.style.position = 'absolute';
defs.innerHTML = '<defs id="gdefs"></defs>'; document.body.appendChild(defs);
/* materials for illustrations */
const TEX=['eiche','eiche-wild','eiche-rustikal','nussbaum','kirsche','esche','ahorn','buche','kernbuche','birke','erle','furnier-eiche','beton'];
const COLOR={'weiss':'#f2f0eb','uni-grau':'#8d8f8d','salbei':'#a0aa92','taubenblau':'#6c8594','fenix':'#2d2d2e','schwarz':'#1f1e1d','hochglanz':'#f6f6f4','kaschmir':'#d8cdbd'};
const fill=m=>TEX.includes(m)?`url(#t-${m})`:(COLOR[m]||m);
const bg=m=>TEX.includes(m)?`background-image:url(${IMG}${m}.jpg)`:`background:${COLOR[m]||m}`;
document.getElementById('gdefs').innerHTML=TEX.map(t=>`<pattern id="t-${t}" patternUnits="userSpaceOnUse" width="420" height="420"><image href="${IMG}${t}.jpg" width="420" height="420" preserveAspectRatio="none"/></pattern>`).join('')+
 `<linearGradient id="sheen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".22"/><stop offset=".55" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".10"/></linearGradient>
 <linearGradient id="shadeDown" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3a2a1a" stop-opacity=".28"/><stop offset="1" stop-color="#3a2a1a" stop-opacity="0"/></linearGradient>
 <linearGradient id="wallG" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#efe9df"/><stop offset="1" stop-color="#e4dccf"/></linearGradient>
 <linearGradient id="floorG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c9b9a4"/><stop offset="1" stop-color="#b9a891"/></linearGradient>
 <linearGradient id="glass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3b3d40"/><stop offset=".5" stop-color="#1d1e20"/><stop offset="1" stop-color="#2c2e31"/></linearGradient>`;

/* front primitive: x,y,w,h, handle: 'v-l','v-r','h','g'(griffmulde),'' */
function front(x,y,w,h,hd,mat,hc){
 let s=`<rect x="${x+1.5}" y="${y+1.5}" width="${w-3}" height="${h-3}" rx="1.5" fill="${fill(mat)}"/><rect x="${x+1.5}" y="${y+1.5}" width="${w-3}" height="${h-3}" rx="1.5" fill="url(#sheen)" stroke="rgba(40,25,10,.35)" stroke-width="1"/>`;
 const c=hc||'#2b2b2b';
 if(hd==='h')s+=`<rect x="${x+w/2-Math.min(40,w*.3)}" y="${y+12}" width="${Math.min(80,w*.6)}" height="5" rx="2.5" fill="${c}"/>`;
 if(hd==='v-l')s+=`<rect x="${x+12}" y="${y+h-(h>200?h*.5:h-12)-0}" width="5" height="${Math.min(70,h*.35)}" rx="2.5" fill="${c}"/>`;
 if(hd==='v-r')s+=`<rect x="${x+w-17}" y="${y+h-(h>200?h*.5:h-12)}" width="5" height="${Math.min(70,h*.35)}" rx="2.5" fill="${c}"/>`;
 if(hd==='vb-l')s+=`<rect x="${x+12}" y="${y+h-12-Math.min(70,h*.35)}" width="5" height="${Math.min(70,h*.35)}" rx="2.5" fill="${c}"/>`;
 if(hd==='vb-r')s+=`<rect x="${x+w-17}" y="${y+h-12-Math.min(70,h*.35)}" width="5" height="${Math.min(70,h*.35)}" rx="2.5" fill="${c}"/>`;
 if(hd==='k'){s+=`<circle cx="${x+w/2}" cy="${y+(h>120?h*.5:16)}" r="6" fill="#8a7a66"/><circle cx="${x+w/2-1.5}" cy="${y+(h>120?h*.5:16)-1.5}" r="2" fill="#fff" opacity=".5"/>`}
 if(hd==='g')s+=`<rect x="${x+1.5}" y="${y+1.5}" width="${w-3}" height="9" fill="rgba(25,15,8,.55)"/>`;
 return s;
}
/* layouts: arrays of [x,y,w,h,handle] */
const L={
 hero:{vb:'0 0 1200 600',fronts:[
  [120,23,144,192,'vb-r'],[120,215,144,336,'v-r'],
  [264,23,144,144,'vb-l'],[264,311,144,120,'h'],[264,431,144,120,'h'],
  [408,359,144,38,'h'],[408,397,144,77,'h'],[408,474,144,77,'h'],
  [552,359,144,192,'h'],
  [696,359,96,192,'v-r'],[792,359,96,192,'v-l'],
  [888,359,144,96,'h'],[888,455,144,96,'h'],
  [1032,359,96,192,'v-l'],
  [552,37,72,192,'vb-r'],[624,37,72,192,'vb-l'],[696,37,96,192,'vb-r'],[792,37,96,192,'vb-l'],[1032,37,96,192,'vb-l']]},
};
function heroBase(){
 return `<rect width="1200" height="600" fill="url(#wallG)"/><rect y="570" width="1200" height="30" fill="url(#floorG)"/>
 <rect x="408" y="229" width="720" height="120" fill="#f3eee6"/>
 ${Array.from({length:12},(_,i)=>`<line x1="${408+i*60}" y1="229" x2="${408+i*60}" y2="349" stroke="#ddd3c4" stroke-width="1"/>`).join('')}
 <line x1="408" y1="289" x2="1128" y2="289" stroke="#ddd3c4"/>
 <rect x="408" y="229" width="720" height="30" fill="url(#shadeDown)"/>
 <rect x="930" y="0" width="60" height="200" fill="#d9d2c7"/><path d="M896 200h128l-8 29H904z" fill="#cfc7ba"/><rect x="904" y="225" width="112" height="4" fill="#b8ae9f"/>
 <rect x="416" y="160" width="128" height="8" fill="url(#t-eiche)"/><rect x="416" y="168" width="128" height="14" fill="url(#shadeDown)" opacity=".7"/>
 <path d="M440 160c-2-14 4-24 14-26 10 2 16 12 14 26z" fill="#8a9a78"/><path d="M447 140c-6-12-2-26 7-30 3 10 2 22-7 30zm14 0c6-12 2-26-7-30-3 10-2 22 7 30z" fill="#6f8a62"/>
 <rect x="490" y="128" width="16" height="32" rx="3" fill="#e7e2da" stroke="#cfc6b9"/><rect x="512" y="136" width="16" height="24" rx="3" fill="#e7e2da" stroke="#cfc6b9"/>
 <rect x="264" y="167" width="144" height="144" fill="url(#glass)"/><rect x="276" y="179" width="120" height="92" rx="4" fill="#111" opacity=".5"/><rect x="276" y="282" width="120" height="6" rx="3" fill="#8a8c8f"/>
 <rect x="402" y="349" width="732" height="10" fill="#d8d2c9"/><rect x="402" y="359" width="732" height="3" fill="rgba(0,0,0,.12)"/>
 <path d="M790 349v-36q0-10 10-10h14v4h-12q-6 0-6 6v36z" fill="#9a9a9a"/>
 <path d="M462 349v-26q0-7 8-8h20q8 1 8 8v26z" fill="#2f2f2f"/><rect x="497" y="326" width="6" height="12" rx="3" fill="#2f2f2f"/>
 <rect x="600" y="296" width="70" height="53" rx="6" fill="url(#t-nussbaum)" transform="rotate(8 635 322)"/>
 <rect x="120" y="551" width="1008" height="19" fill="#3b3734"/>
 <rect x="0" y="570" width="1200" height="8" fill="rgba(40,25,10,.18)"/>`;
}
function heroScene(mat){
 const fr=L.hero.fronts.map(f=>front(...f,mat)).join('');
 return fr;
}
/* smaller illustrative scenes */
function mini(kind,mat,mat2){
 const W=640,H=400;let s=`<svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Illustration">`;
 s+=`<rect width="${W}" height="${H}" fill="url(#wallG)"/><rect y="360" width="${W}" height="40" fill="url(#floorG)"/>`;
 if(kind==='sideboard'){
  s+=`<rect x="60" y="80" width="200" height="140" fill="#d9cfc0"/><rect x="66" y="86" width="188" height="128" fill="#b9c3b6"/><path d="M66 214l60-70 40 40 30-30 58 60z" fill="#8fa087"/>`;
  s+=`<rect x="44" y="236" width="552" height="12" fill="${fill('eiche-wild')}"/>`;
  for(let i=0;i<6;i++)s+=front(48+i*91.3,248,91.3,86,'g',mat);
  s+=`<rect x="48" y="334" width="548" height="22" fill="url(#shadeDown)"/>`;
  s+=`<path d="M470 236v-34q0-10 12-12h16q12 2 12 12v34z" fill="#c9bfae"/><path d="M490 190c-4-26 0-44 8-54 4 14 6 34 0 54z" fill="#6f8a62"/><circle cx="380" cy="226" r="10" fill="#3b3734"/><rect x="376" y="160" width="8" height="66" fill="#3b3734"/><path d="M356 160h48l-12-26h-24z" fill="#ece5d8" stroke="#3b3734" stroke-width="2"/>`;
 }else if(kind==='griff'){
  s+=`<rect x="40" y="70" width="560" height="10" fill="${fill('eiche')}"/><rect x="40" y="80" width="560" height="14" fill="url(#shadeDown)"/>`;
  s+=`<path d="M70 70c0-16 8-24 18-24s18 8 18 24z" fill="#c9bfae"/><rect x="140" y="44" width="40" height="26" fill="#e9e3da" stroke="#cfc6b9"/><rect x="460" y="48" width="14" height="22" fill="#e9e3da" stroke="#cfc6b9"/><rect x="478" y="40" width="14" height="30" fill="#e9e3da" stroke="#cfc6b9"/>`;
  s+=`<rect x="30" y="196" width="580" height="16" fill="${fill('eiche')}"/>`;
  const xs=[[36,96],[132,96],[228,144],[372,120],[492,112]];
  xs.forEach(([x,w],i)=>{if(i===2){s+=front(x,212,w,46,'g',mat)+front(x,258,w,46,'g',mat)+front(x,304,w,46,'g',mat)}else s+=front(x,212,w,138,'g',mat)});
  s+=`<rect x="36" y="350" width="568" height="10" fill="#3b3734"/><path d="M300 196v-30q0-8 8-8h12v4h-10q-4 0-4 5v29z" fill="#9a9a9a"/>`;
 }else if(kind==='faktum'||kind==='metod'||kind==='indiv'){
  const hd=kind==='faktum'?['k','k','k']:kind==='metod'?['h','v-r','v-l']:['g','g','g'];
  s+=`<rect x="70" y="190" width="520" height="80" fill="#f3eee6"/><rect x="70" y="190" width="520" height="22" fill="url(#shadeDown)"/>`;
  for(let i=0;i<4;i++)s+=front(70+i*130,40,130,150,kind==='faktum'?'k':i%2?'vb-l':'vb-r',mat);
  s+=`<rect x="64" y="268" width="532" height="9" fill="${kind==='indiv'?fill('beton'):'#d8d2c9'}"/>`;
  s+=front(70,277,130,28,hd[0],mat)+front(70,305,130,47,hd[0],mat)+front(200,277,130,75,hd[1],mat)+front(330,277,130,75,hd[2],mat)+front(460,277,130,37,hd[0],mat)+front(460,314,130,38,hd[0],mat);
  s+=`<rect x="70" y="352" width="520" height="8" fill="#3b3734"/><path d="M392 268v-26q0-8 8-8h12v4h-10q-4 0-4 5v25z" fill="#9a9a9a"/><path d="M110 268v-18q0-6 6-6h18q6 0 6 6v18z" fill="#2f2f2f"/>`;
 }else if(kind==='wand'){
  s+=front(60,40,130,320,'',mat)+front(190,40,130,150,'',mat2||'eiche')+`<rect x="190" y="190" width="130" height="70" fill="#2a2725"/><rect x="196" y="196" width="118" height="58" fill="#111"/>`+front(190,260,130,100,'',mat)+front(320,40,130,320,'',mat)+front(450,40,130,150,'',mat)+front(450,190,130,170,'',mat2||'eiche');
  s+=`<rect x="196" y="196" width="118" height="58" fill="#1a1c1f"/>`;
 }
 return s+'</svg>';
}

$$('[data-scene]').forEach(function (el) { var k = el.dataset.scene; el.innerHTML = mini(k, el.dataset.mat || (k === 'faktum' ? 'furnier-eiche' : k === 'indiv' ? 'salbei' : 'eiche-wild'), el.dataset.mat2); });

/* hero */
var SW = K.swatches || [];
var hs = $('#heroScene');
if (hs && SW.length) {
	var cur = 0, top = 0;
	hs.innerHTML = '<svg class="scene" viewBox="0 0 1200 600" role="img" aria-label="Illustration einer Küche mit neuen Fronten">' + heroBase() + '<g class="layer" id="lA">' + heroScene(SW[0].m) + '</g><g class="layer" id="lB" style="opacity:0"></g><rect x="0" y="0" width="1200" height="600" fill="url(#sheen)" opacity=".5" pointer-events="none"/></svg>';
	$('#swRow').innerHTML = SW.map(function (s, i) { return '<button class="sw" style="' + bg(s.m) + '" aria-pressed="' + (i === 0) + '" aria-label="' + s.n + '" data-i="' + i + '" title="' + s.n + '"></button>'; }).join('');
	var pick = function (i) {
		if (i === cur) return; cur = i;
		var A = $('#lA'), B = $('#lB'), show = top ? A : B, hide = top ? B : A;
		show.innerHTML = heroScene(SW[i].m); show.style.opacity = 1; hide.style.opacity = 0; top = 1 - top;
		$$('.sw').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.i == i); });
		$('#swName').textContent = SW[i].n; $('#swPrice').textContent = SW[i].p;
	};
	$('#swRow').addEventListener('click', function (e) { var b = e.target.closest('.sw'); if (b) pick(+b.dataset.i); });
	$('#swName').textContent = SW[0].n; $('#swPrice').textContent = SW[0].p;
}
})();

/* FAQ search */
(function () {
	var q = document.querySelector('.kf24-faq-search');
	if (!q) return;
	q.addEventListener('input', function () {
		var v = q.value.toLowerCase().trim(), any = false;
		document.querySelectorAll('.kf24-faq-sec').forEach(function (sec) {
			var hits = 0;
			sec.querySelectorAll('details.kf24-faq-q').forEach(function (d) {
				var hit = !v || d.textContent.toLowerCase().indexOf(v) >= 0;
				d.hidden = !hit; d.classList.toggle('kf24-faq-hit', !!v && hit);
				if (v && hit && d.querySelector('summary').textContent.toLowerCase().indexOf(v) < 0) d.open = true;
				if (hit) hits++;
			});
			sec.hidden = !hits; any = any || hits > 0;
		});
		document.querySelector('.kf24-faq-none').hidden = any;
	});
})();
