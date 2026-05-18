/* ============================================================
   SATU SEDUH — Main JavaScript
   (Cart & Order sistem ada di js/order-system.js)
   ============================================================ */

/* ── 01. CURSOR ── */
const cur  = document.getElementById('cur');
const curR = document.getElementById('curR');
const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
if (isTouchDevice) {
  if(cur) cur.style.display = 'none';
  if(curR) curR.style.display = 'none';
  document.body.style.cursor = 'auto';
} else {
  let mx = -100, my = -100;
  let rx = -100, ry = -100;
  const ease = 0.12; // lower = smoother/slower ring, 0.12 is lightweight

  // Move dot immediately via transform (no layout thrash)
  document.addEventListener('mousemove', e => {
    mx = e.clientX;
    my = e.clientY;
    if(cur) {
      cur.style.left = mx + 'px';
      cur.style.top  = my + 'px';
    }
  });

  // Animate ring with eased RAF — much lighter than setTimeout
  function animRing() {
    rx += (mx - rx) * ease;
    ry += (my - ry) * ease;
    if(curR) {
      curR.style.left = rx + 'px';
      curR.style.top  = ry + 'px';
    }
    requestAnimationFrame(animRing);
  }
  requestAnimationFrame(animRing);

  document.addEventListener('mouseleave', () => {
    if(cur) cur.style.opacity='0';
    if(curR) curR.style.opacity='0';
  });
  document.addEventListener('mouseenter', () => {
    if(cur) cur.style.opacity='1';
    if(curR) curR.style.opacity='1';
  });
}


/* ── 02. NAVBAR SCROLL ── */
const navbar   = document.getElementById('navbar');
const ham      = document.getElementById('ham');
const mobNav   = document.getElementById('mobNav');
const backdrop = document.getElementById('backdrop');

if(navbar) window.addEventListener('scroll', () => navbar.classList.toggle('scrolled', window.scrollY > 60));
if(ham) ham.addEventListener('click', () => { mobNav.classList.toggle('open'); backdrop.classList.toggle('on'); });
if(backdrop) backdrop.addEventListener('click', () => {
  if(mobNav) mobNav.classList.remove('open');
  const cartSb = document.getElementById('cartSb');
  if(cartSb) cartSb.classList.remove('open');
  backdrop.classList.remove('on');
});


/* ── 03. SCROLL REVEAL ── */
const revObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('vis'); });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal,.reveal-left,.reveal-right').forEach(el => revObs.observe(el));


/* ── 04. MENU TABS (F&B) ── */
window.showTab = function(id, btn) {
  document.querySelectorAll('.fnb-section').forEach(s => s.classList.remove('show'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  const sec = document.getElementById('tab-' + id);
  if(sec){ sec.classList.add('show'); }
  btn.classList.add('active');
  setTimeout(() => {
    if(sec) sec.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
    feather.replace();
  }, 50);
};


/* ── 05. ROOM SELECTOR ── */
window.selRoom = function(el) {
  document.querySelectorAll('.room-opt').forEach(r => r.classList.remove('sel'));
  el.classList.add('sel');
};


/* ── 06. REVIEW SLIDER ── */
const revTrack = document.getElementById('revTrack');
if(revTrack){
  const revCards = revTrack.querySelectorAll('.rev-card');
  let revIdx = 0;
  function getCardW(){ return revCards[0] ? revCards[0].offsetWidth + 24 : 0; }
  function buildDots(){
    const d = document.getElementById('revDots'); if(!d) return;
    d.innerHTML = '';
    revCards.forEach((_,i)=>{
      const dot = document.createElement('div');
      dot.className = 'rev-dot'+(i===0?' act':'');
      dot.addEventListener('click', ()=>goRev(i));
      d.appendChild(dot);
    });
  }
  function goRev(i){
    revIdx = Math.max(0, Math.min(i, revCards.length-1));
    revTrack.style.transform = `translateX(-${revIdx*getCardW()}px)`;
    document.querySelectorAll('.rev-dot').forEach((d,j)=>d.classList.toggle('act',j===revIdx));
  }
  buildDots();
  const rp = document.getElementById('revPrev');
  const rn = document.getElementById('revNext');
  if(rp) rp.addEventListener('click', ()=>goRev(revIdx-1));
  if(rn) rn.addEventListener('click', ()=>goRev((revIdx+1)%revCards.length));
  setInterval(()=>goRev((revIdx+1)%revCards.length), 4500);
}


/* ── 07. TOAST ── */
const toastEl  = document.getElementById('toast');
const toastMsg = document.getElementById('toastMsg');
let toastTimer;
window.showToast = function(msg){
  if(!toastEl || !toastMsg) return;
  toastMsg.textContent = msg;
  toastEl.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=>toastEl.classList.remove('show'), 2800);
};


/* ── 08. PRODUCT MODAL (detail produk biji kopi) ── */
const prodModalEl = document.getElementById('prodModal');
if(prodModalEl){
  window.openModal = function(data){
    document.getElementById('modalImg').src           = data.img;
    document.getElementById('modalBadge').textContent = data.badge;
    document.getElementById('modalStars').textContent = data.stars;
    document.getElementById('modalName').innerHTML    = data.name;
    document.getElementById('modalDesc').textContent  = data.desc;
    document.getElementById('modalChips').innerHTML   = (data.chips||[]).map(c=>`<span class="prod-modal-chip">${c}</span>`).join('');
    document.getElementById('modalPrice').innerHTML   = `${data.price} <s>${data.oldPrice}</s>`;
    document.getElementById('modalAddBtn').onclick    = ()=>{ addFoodToCart(data.name, parseInt(data.price.replace(/\D/g,'')), data.img); closeModal(); };
    prodModalEl.classList.add('open');
  };
  window.closeModal = function(){ prodModalEl.classList.remove('open'); };
  prodModalEl.addEventListener('click', e=>{ if(e.target===prodModalEl) closeModal(); });
}


/* ── 09. FORM KONTAK ── */
window.kirimKomentar = function(e){
  const btn = e.target;
  btn.textContent = '✓ Komentar Terkirim!';
  btn.style.background = '#2d6a4f';
  setTimeout(()=>{ btn.textContent='Kirim Komentar →'; btn.style.background=''; }, 3000);
};


/* ── INIT ── */
feather.replace();


/* ── PRODUK: baca dari data-* attribute (no inline JS error) ── */
window.openProdModal = function(el) {
  const card = el.closest('.prod-card');
  const img  = card.querySelector('img').src;
  const d    = card.dataset;
  const chips = d.chips ? d.chips.split(',') : [];
  const price = parseInt(d.price);
  const priceStr = 'IDR ' + (price/1000) + 'K';

  document.getElementById('modalImg').src           = img;
  document.getElementById('modalBadge').textContent = d.badge || '';
  document.getElementById('modalStars').textContent = d.stars || '';
  document.getElementById('modalName').textContent  = d.name  || '';
  document.getElementById('modalDesc').textContent  = d.desc  || '';
  document.getElementById('modalChips').innerHTML   = chips.map(c => `<span class="prod-modal-chip">${c}</span>`).join('');
  document.getElementById('modalPrice').innerHTML   = `${priceStr} <s>${d.old || ''}</s>`;
  document.getElementById('modalAddBtn').onclick    = () => {
    window.addFoodToCart(d.name, price, img);
    window.closeModal();
  };
  document.getElementById('prodModal').classList.add('open');
};

window.addProdToCart = function(el) {
  const card  = el.closest('.prod-card');
  const img   = card.querySelector('img').src;
  const d     = card.dataset;
  window.addFoodToCart(d.name, parseInt(d.price), img);
};

window.closeModal = function() {
  const m = document.getElementById('prodModal');
  if(m) m.classList.remove('open');
};
