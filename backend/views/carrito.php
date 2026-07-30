<?php
// Asume que el número de WhatsApp está en config/config.php
require_once __DIR__ . '/../../config/config.php';
?>
<?php include __DIR__ . '/layouts/header.php'; ?>

<main class="page-content">
<section class="container" style="max-width: 980px; margin: 0 auto 40px;">
  <h1 style="font-size: 28px; margin-bottom: 16px;">TU CARRITO</h1>
  <p style="color:#666; margin-bottom: 24px;">Revisa tus productos y envía un único pedido por WhatsApp.</p>

  <div id="cart-empty" style="display:none; padding:24px; border:1px dashed #ddd; border-radius:10px; text-align:center;">
    Tu carrito está vacío. <a href="/productos" style="color:#2E7D32; text-decoration:underline;">Ver productos</a>
  </div>

  <div id="cart-wrapper" style="display:none;">
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="text-align:left; border-bottom:1px solid #eee;">
            <th style="padding:12px;">Productos</th>
            <th style="padding:12px;">Precio</th>
            <th style="padding:12px;">Cantidad</th>
            <th style="padding:12px;">Subtotal</th>
            <th style="padding:12px;">Acciones</th>
          </tr>
        </thead>
        <tbody id="cart-body"></tbody>
      </table>
    </div>
    
    <div id="cart-total" style="font-weight:700; font-size:18px; margin-top:16px; text-align:right;">Total: $0</div>
    <div style="margin-top:10px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:flex-end;">
      <a href="/productos" style="display:inline-flex; align-items:center; gap:8px; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; color:#111; text-decoration:none; background:#fff; font-weight:600;">
        Seguir comprando
      </a>
      <button id="btn-checkout-pay" style="display:inline-flex; align-items:center; gap:8px; background:#d9534f; color:#ffffff; border:none; border-radius:10px; padding:10px 18px; text-decoration:none; font-weight:700; box-shadow:0 2px 8px rgba(217,83,79,0.3); cursor:pointer;">
        <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
        Proceder al Pago / Checkout
      </button>
      <a id="cart-wa-btn" href="#" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; gap:8px; background:#25D366; color:#ffffff; border-radius:10px; padding:10px 14px; text-decoration:none; font-weight:700; box-shadow:0 2px 8px rgba(37,211,102,0.25);">
        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
        Enviar pedido por WhatsApp
      </a>
    </div>
    <div class="cart-notes" style="margin-top:10px; color:#6b7280; font-size:13px; text-align:right;">
      Impuesto incluido. Los gastos de envío se calculan de acuerdo a la ubicación.
    </div>
  </div>
</section>

<script>
  (function() {
    const STORAGE_KEY = 'ft_cart';
    const body = document.getElementById('cart-body');
    const empty = document.getElementById('cart-empty');
    const wrap = document.getElementById('cart-wrapper');
    const totalEl = document.getElementById('cart-total');
    const waBtn = document.getElementById('cart-wa-btn');

    function fmtCurrency(n){
      try { return new Intl.NumberFormat('es-CO', { style:'currency', currency:'COP' }).format(n); } catch(e) { return '$' + (n||0); }
    }

    function readCart(){
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : [];
      } catch(e) { return []; }
    }
    function writeCart(items){
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
      // Actualiza contador en navbar si existe
      const badge = document.querySelector('[data-cart-count]');
      if (badge) badge.textContent = items.reduce((a,i)=>a + Number(i.qty||0), 0);
    }

    // Reinicio manual del contador global del código de pedido (FT-YYYYMMDD-001)
    const ORDER_SEQ_KEY = 'ft_order_seq_global';
    function getQueryParam(name){
      try { return new URL(location.href).searchParams.get(name); } catch(_) { return null; }
    }
    function resetOrderSeqToZero(){
      try { localStorage.setItem(ORDER_SEQ_KEY, '0'); } catch(_){}
    }
    (function(){
      const val = (getQueryParam('reset_seq') || '').toLowerCase();
      if (val === '1' || val === 'true' || val === 'yes') {
        resetOrderSeqToZero();
        try { history.replaceState({}, document.title, location.pathname); } catch(_){}
      }
    })();

    // Código de pedido corto y secuencial por día: FT-YYYYMMDD-001
    function getDateKey(){
      const d = new Date();
      const pad = (n) => String(n).padStart(2,'0');
      return `${d.getFullYear()}${pad(d.getMonth()+1)}${pad(d.getDate())}`;
    }
    // Sufijo dinámico: mínimo 3 dígitos, aumenta según el contador
    function padMin3(n){
      const s = String(n);
      return s.length >= 3 ? s : s.padStart(3, '0');
    }
    function makeOrderCode(dateKey, seq){ return `FT-${dateKey}-${padMin3(seq)}`; }
    function peekOrderCode(){
      const dk = getDateKey();
      const key = 'ft_order_seq_global';
      const cur = parseInt(localStorage.getItem(key) || '0', 10);
      return makeOrderCode(dk, cur + 1);
    }
    function nextOrderCode(){
      const dk = getDateKey();
      const key = 'ft_order_seq_global';
      const cur = parseInt(localStorage.getItem(key) || '0', 10) + 1;
      localStorage.setItem(key, String(cur));
      return makeOrderCode(dk, cur);
    }

    // Helpers de imagen para la miniatura en el carrito (unificados con catálogo/detalle)
    function imageCandidates(slug){
      const clean = String(slug || '').trim();
      const lc = clean.toLowerCase();
      // Normalizaciones adicionales: espacios a underscore/hyphen
      const u = lc.replace(/[\s-]+/g, '_');
      const h = lc.replace(/[\s_]+/g, '-');
      const exts = ['png','jpg','jpeg','svg'];
      const cands = [];
      exts.forEach(ext => {
        // Básicos por slug
        cands.push(`assets/img/products/${lc}.${ext}`);
        cands.push(`assets/img/products/${u}.${ext}`);
        cands.push(`assets/img/products/${h}.${ext}`);
        // Variante 'frontal' como principal
        cands.push(`assets/img/products/${lc}_frontal.${ext}`);
        cands.push(`assets/img/products/${u}_frontal.${ext}`);
        cands.push(`assets/img/products/${h}_frontal.${ext}`);
        // Variantes 'back' y 'trasera' como alternativas
        cands.push(`assets/img/products/${lc}_back.${ext}`);
        cands.push(`assets/img/products/${u}_back.${ext}`);
        cands.push(`assets/img/products/${h}_back.${ext}`);
        cands.push(`assets/img/products/${lc}-back.${ext}`);
        cands.push(`assets/img/products/${h}-back.${ext}`);
        cands.push(`assets/img/products/${lc}_trasera.${ext}`);
        cands.push(`assets/img/products/${u}_trasera.${ext}`);
        cands.push(`assets/img/products/${h}_trasera.${ext}`);
        cands.push(`assets/img/products/${lc}-trasera.${ext}`);
        cands.push(`assets/img/products/${h}-trasera.${ext}`);
      });
      // Fallback histórico para typo "ofical"
      if (lc === 'camiseta_oficial_femtribe') {
        exts.forEach(ext => cands.push(`assets/img/products/camiseta_ofical_femtribe.${ext}`));
      }
      // Fallback genérico esqueleto: cualquier slug que contenga 'esqueleto'
      const isSkeleton = /esqueletos?/i.test(lc);
      if (isSkeleton) {
        // Priorizar imagen frontal existente en el proyecto
        exts.forEach(ext => cands.unshift(`assets/img/products/esqueleto_femtribe.${ext}`));
        exts.forEach(ext => cands.unshift(`assets/img/products/esqueletos_femtribe.${ext}`));
        // Alternativas de trasera si existieran
        exts.forEach(ext => cands.push(`assets/img/products/esqueleto_femtribe_back.${ext}`));
        exts.forEach(ext => cands.push(`assets/img/products/esqueletos_femtribe_back.${ext}`));
      }
      return cands;
    }

    function buildImageForSlug(slug, name){
      const img = document.createElement('img');
      img.alt = `Imagen de ${name || slug}`;
      img.style.width = '88px';
      img.style.height = '88px';
      img.style.objectFit = 'contain';
      img.style.border = '1px solid #eee';
      img.style.borderRadius = '12px';
      img.style.background = '#fff';
      img.style.visibility = 'hidden';
      img.style.opacity = '0';
      img.style.transition = 'opacity 0.18s ease';
      img.loading = 'lazy';
      img.decoding = 'async';

      const cands = imageCandidates(slug);
      let idx = 0;
      function tryNext(){
        if (idx < cands.length) {
          const url = '/' + cands[idx++];
          const test = new Image();
          test.onload = function(){
            img.src = url;
            img.style.visibility = 'visible';
            img.style.opacity = '1';
            test.onload = null; test.onerror = null;
          };
          test.onerror = function(){
            test.onload = null; test.onerror = null;
            tryNext();
          };
          test.src = url;
        } else {
          img.src = '/assets/img/logoverde.png';
          img.style.visibility = 'visible';
          img.style.opacity = '1';
        }
      }
      tryNext();
      return img;
    }

    // Nota: se eliminan duplicados de helpers para evitar redefiniciones y errores.

    function render(){
      const items = readCart();
      if (!items.length){
        empty.style.display = 'block';
        wrap.style.display = 'none';
        writeCart(items);
        return;
      }
      empty.style.display = 'none';
      wrap.style.display = 'block';
      body.innerHTML = '';
      let total = 0;
      items.forEach((it, idx) => {
        const name = it.name || it.slug;
        const price = Number(it.price || 0);
        const qty = Number(it.qty || 1);
        const subtotal = price * qty;
        total += subtotal;
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f2f2f2';
        tr.innerHTML = `
          <td style="padding:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
              <div data-img-cell style="width:88px; height:88px; flex:0 0 auto;"></div>
              <div>
                <div style="font-weight:600;">${name}</div>
                <div style="color:#555; font-size:13px;">Color: ${it.color || '-'} | Género: ${it.gender || '-'} | Talla: ${it.size || '-'}
                </div>
              </div>
            </div>
          </td>
          <td style="padding:12px;">${fmtCurrency(price)}</td>
          <td style="padding:12px;">
            <div style="display:flex; align-items:center; gap:6px;">
              <button aria-label="Restar" data-act="dec" data-idx="${idx}" class="qty-btn" style="width:28px; height:28px; border:1px solid #ddd; border-radius:6px; background:#fff;">-</button>
              <span style="min-width:24px; text-align:center;">${qty}</span>
              <button aria-label="Sumar" data-act="inc" data-idx="${idx}" class="qty-btn" style="width:28px; height:28px; border:1px solid #ddd; border-radius:6px; background:#fff;">+</button>
            </div>
          </td>
          <td style="padding:12px; font-weight:600;">${fmtCurrency(subtotal)}</td>
          <td style="padding:12px;">
            <button data-act="rm" data-idx="${idx}" style="border:none; background:#ffefef; color:#c62828; padding:8px 10px; border-radius:6px; cursor:pointer;">Eliminar</button>
          </td>
        `;
        body.appendChild(tr);
        const imgCell = tr.querySelector('[data-img-cell]');
        if (imgCell) imgCell.appendChild(buildImageForSlug(it.slug, it.name));
      });
      totalEl.textContent = `Total: ${fmtCurrency(total)}`;
      waBtn.href = buildWhatsAppUrl(items, total, peekOrderCode());
      writeCart(items);
    }

    function buildWhatsAppUrl(items, total, orderCode){
      const phone = '<?php echo preg_replace('/[^0-9]/', '', WHATSAPP_BUSINESS_NUMBER); ?>';
      const now = new Date();
      const fecha = now.toLocaleString('es-CO', { hour12: false });

      let lines = [];
      // Encabezado
      lines.push('FEMTRIBE – Solicitud de pedido');
      lines.push(`Código de pedido: ${orderCode}`);
      lines.push(`Fecha: ${fecha}`);
      lines.push('');
      lines.push(`Resumen: ${items.length} artículo(s) | Total: ${fmtCurrency(total)}`);
      lines.push('');

      // Detalle por producto (bloques legibles)
      items.forEach((it, i) => {
        const name = it.name || it.slug;
        const qty = Number(it.qty || 1);
        const price = Number(it.price || 0);
        const subtotal = price * qty;
        lines.push(`— Producto ${i+1}`);
        lines.push(`  • Nombre: ${name}`);
        lines.push(`  • Color: ${it.color || '-'}`);
        lines.push(`  • Género: ${it.gender || '-'}`);
        lines.push(`  • Talla: ${it.size || '-'}`);
        lines.push(`  • Cantidad: ${qty}`);
        lines.push(`  • Precio unitario: ${fmtCurrency(price)}`);
        lines.push(`  • Subtotal: ${fmtCurrency(subtotal)}`);
        lines.push('');
      });

      // Pie
      lines.push(`Total a pagar: ${fmtCurrency(total)}`);
      lines.push('Impuesto incluido. El envío se calcula según la ubicación.');
      lines.push('¿Me confirman disponibilidad y método de pago?');

      const msg = encodeURIComponent(lines.join('\n'));
      return `https://wa.me/${phone}?text=${msg}`;
    }

    document.addEventListener('click', (e) => {
      const t = e.target;
      const act = t.getAttribute('data-act');
      const idx = Number(t.getAttribute('data-idx'));
      if (!act) return;
      const items = readCart();
      if (act === 'inc') {
        items[idx].qty = Number(items[idx].qty || 1) + 1;
      } else if (act === 'dec') {
        items[idx].qty = Math.max(1, Number(items[idx].qty || 1) - 1);
      } else if (act === 'rm') {
        items.splice(idx, 1);
      }
      writeCart(items);
      render();
    });

    // Limpiar carrito tras enviar por WhatsApp usando código secuencial
    waBtn.addEventListener('click', function(ev){
      ev.preventDefault();
      const items = readCart();
      if (!items.length) return;
      let total = 0; items.forEach(i => { total += Number(i.price||0) * Number(i.qty||1); });
      const code = nextOrderCode();
      const url = buildWhatsAppUrl(items, total, code);
      try { window.open(url, '_blank'); } catch(_){ location.href = url; }
      writeCart([]);
      render();
    });

    const btnCheckoutPay = document.getElementById('btn-checkout-pay');
    if (btnCheckoutPay) {
      btnCheckoutPay.addEventListener('click', function(ev) {
        ev.preventDefault();
        const items = readCart();
        if (!items.length) {
          alert('Tu carrito está vacío.');
          return;
        }

        if (!window.isUserLoggedIn) {
          window.showAuthModal('/checkout');
        } else {
          window.location.href = '/checkout';
        }
      });
    }

    render();
  })();
</script>

</section>
</main>

<?php include __DIR__ . '/layouts/footer.php'; ?>