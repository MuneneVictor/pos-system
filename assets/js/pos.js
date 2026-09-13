(function () {
    'use strict';

    const root = document.querySelector('[data-pos-root]');
    if (!root || !window.POS_CONFIG) return;

    const cfg = window.POS_CONFIG;
    const cart = [];
    let selectedCustomer = null;
    let searchTimer = null;

    const searchInput = document.getElementById('pos-search');
    const results = document.querySelector('[data-product-results]');
    const searchStatus = document.querySelector('[data-search-status]');
    const cartLines = document.querySelector('[data-cart-lines]');
    const emptyCart = document.querySelector('[data-empty-cart]');
    const subtotalEl = document.querySelector('[data-subtotal]');
    const discountEl = document.querySelector('[data-discount]');
    const totalEl = document.querySelector('[data-total]');
    const payButton = document.querySelector('[data-open-payment]');
    const paymentModal = document.querySelector('[data-payment-modal]');
    const paymentRows = document.querySelector('[data-payment-rows]');
    const paymentDue = document.querySelector('[data-payment-due]');
    const paymentAllocated = document.querySelector('[data-payment-allocated]');
    const paymentRemaining = document.querySelector('[data-payment-remaining]');
    const errorBox = document.querySelector('[data-pos-error]');
    const customerSearch = document.getElementById('customer-search');
    const customerResults = document.querySelector('[data-customer-results]');
    const selectedCustomerEl = document.querySelector('[data-selected-customer]');

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
    }

    function money(value) {
        return `${cfg.currency} ${new Intl.NumberFormat('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2}).format(Number(value || 0))}`;
    }

    function roundMoney(value) {
        return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
    }

    function qtyStep(item) {
        return Number(item.allows_decimal) === 1 ? '0.001' : '1';
    }

    function imageUrl(path) {
        if (!path) return '';
        const base = String(cfg.appBase || '/').replace(/\/$/, '');
        return `${base}/${String(path).replace(/^\//, '')}`;
    }

    function normalizeProduct(p) {
        return {
            id: Number(p.id),
            name: String(p.name),
            sku: String(p.sku || ''),
            barcode: p.barcode ? String(p.barcode) : '',
            short_name: String(p.short_name || p.unit_snapshot || ''),
            allows_decimal: Number(p.allows_decimal || 0),
            track_stock: Number(p.track_stock ?? 1),
            current_stock: Number(p.current_stock || 0),
            image_path: p.image_path || null,
            quantity: Number(p.quantity || 1),
            unit_price: Number(p.unit_price ?? p.selling_price ?? 0),
            discount_amount: Number(p.discount_amount || 0)
        };
    }

    function addProduct(raw) {
        const p = normalizeProduct(raw);
        const existing = cart.find(item => item.id === p.id);
        const increment = Number(p.allows_decimal) === 1 ? 1 : 1;

        if (existing) {
            existing.quantity = roundQuantity(existing.quantity + increment);
        } else {
            p.quantity = p.quantity > 0 ? p.quantity : increment;
            cart.push(p);
        }

        renderCart();
        searchInput.value = '';
        searchInput.focus();
        searchStatus.textContent = `${p.name} added`;
    }

    function roundQuantity(value) {
        return Math.round(Number(value) * 1000) / 1000;
    }

    function lineSubtotal(item) {
        return roundMoney(item.quantity * item.unit_price);
    }

    function lineTotal(item) {
        return Math.max(0, roundMoney(lineSubtotal(item) - Number(item.discount_amount || 0)));
    }

    function totals() {
        let subtotal = 0;
        let discount = 0;
        let total = 0;
        cart.forEach(item => {
            subtotal += lineSubtotal(item);
            discount += Number(item.discount_amount || 0);
            total += lineTotal(item);
        });
        return { subtotal: roundMoney(subtotal), discount: roundMoney(discount), total: roundMoney(total) };
    }

    function renderCart() {
        cartLines.querySelectorAll('.pos-cart-line').forEach(el => el.remove());
        emptyCart.hidden = cart.length > 0;

        cart.forEach((item, index) => {
            const line = document.createElement('article');
            line.className = 'pos-cart-line';
            line.innerHTML = `
                <div class="pos-cart-line__top">
                    <div><strong>${escapeHtml(item.name)}</strong><span>${escapeHtml(item.sku)} · ${escapeHtml(item.short_name)}</span></div>
                    <button type="button" class="cart-remove" aria-label="Remove">×</button>
                </div>
                <div class="pos-cart-line__controls">
                    <div class="cart-qty-control">
                        <button type="button" data-minus>−</button>
                        <input type="number" min="${qtyStep(item)}" step="${qtyStep(item)}" value="${item.quantity}" data-qty>
                        <button type="button" data-plus>+</button>
                    </div>
                    <label class="cart-money-field"><span>Price</span><input type="number" min="0" step="0.01" value="${item.unit_price.toFixed(2)}" data-price ${cfg.canPriceOverride ? '' : 'readonly'}></label>
                    <label class="cart-money-field"><span>Discount</span><input type="number" min="0" step="0.01" value="${Number(item.discount_amount).toFixed(2)}" data-discount ${cfg.canDiscount ? '' : 'readonly'}></label>
                    <div class="cart-line-total"><span>Line total</span><strong>${money(lineTotal(item))}</strong></div>
                </div>`;

            line.querySelector('.cart-remove').addEventListener('click', () => { cart.splice(index, 1); renderCart(); });
            line.querySelector('[data-minus]').addEventListener('click', () => {
                const step = Number(item.allows_decimal) === 1 ? 0.5 : 1;
                item.quantity = Math.max(Number(item.allows_decimal) === 1 ? 0.001 : 1, roundQuantity(item.quantity - step));
                renderCart();
            });
            line.querySelector('[data-plus]').addEventListener('click', () => {
                const step = Number(item.allows_decimal) === 1 ? 0.5 : 1;
                item.quantity = roundQuantity(item.quantity + step);
                renderCart();
            });
            line.querySelector('[data-qty]').addEventListener('change', e => {
                let val = Number(e.target.value || 0);
                if (Number(item.allows_decimal) !== 1) val = Math.round(val);
                item.quantity = Math.max(Number(item.allows_decimal) === 1 ? 0.001 : 1, roundQuantity(val));
                renderCart();
            });
            line.querySelector('[data-price]').addEventListener('change', e => {
                if (!cfg.canPriceOverride) return;
                item.unit_price = Math.max(0, roundMoney(e.target.value));
                renderCart();
            });
            line.querySelector('[data-discount]').addEventListener('change', e => {
                if (!cfg.canDiscount) return;
                item.discount_amount = Math.min(lineSubtotal(item), Math.max(0, roundMoney(e.target.value)));
                renderCart();
            });
            cartLines.appendChild(line);
        });

        const t = totals();
        subtotalEl.textContent = money(t.subtotal);
        discountEl.textContent = money(t.discount);
        totalEl.textContent = money(t.total);
        payButton.disabled = cart.length === 0 || t.total < 0;
    }

    function renderProducts(products) {
        results.innerHTML = '';
        if (!products.length) {
            results.innerHTML = '<div class="pos-no-results"><strong>No products found</strong><span>Try a different product name, SKU or barcode.</span></div>';
            return;
        }
        products.forEach(raw => {
            const p = normalizeProduct(raw);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pos-product-card';
            button.innerHTML = `
                <div class="pos-product-image">${p.image_path ? `<img src="${escapeHtml(imageUrl(p.image_path))}" alt="">` : `<span>${escapeHtml((p.name.split(/\s+/).slice(0,2).map(v=>v[0]||'').join('')).toUpperCase())}</span>`}</div>
                <div class="pos-product-copy"><strong>${escapeHtml(p.name)}</strong><span>${escapeHtml(p.sku)}</span><b>${money(p.unit_price)}/${escapeHtml(p.short_name)}</b><small>${p.track_stock ? `Stock ${p.current_stock} ${escapeHtml(p.short_name)}` : 'Stock not tracked'}</small></div>`;
            button.addEventListener('click', () => addProduct(raw));
            results.appendChild(button);
        });
    }

    async function searchProducts(query) {
        searchStatus.textContent = 'Searching…';
        try {
            const response = await fetch(`${cfg.searchUrl}?q=${encodeURIComponent(query)}`, {credentials:'same-origin'});
            const data = await response.json();
            if (!data.ok) throw new Error(data.message || 'Search failed');
            if (data.exact && data.products.length === 1) {
                addProduct(data.products[0]);
                return;
            }
            renderProducts(data.products || []);
            searchStatus.textContent = `${(data.products || []).length} result(s)`;
        } catch (error) {
            searchStatus.textContent = error.message || 'Search failed';
        }
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = searchInput.value.trim();
        if (!q) return;
        searchTimer = setTimeout(() => searchProducts(q), 180);
    });
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const q = searchInput.value.trim();
            if (q) searchProducts(q);
        }
    });

    results.querySelectorAll('[data-product]').forEach(button => {
        button.addEventListener('click', () => {
            try { addProduct(JSON.parse(button.dataset.product)); } catch (_) {}
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') { e.preventDefault(); searchInput.focus(); searchInput.select(); }
        if (e.key === 'F9' && cart.length) { e.preventDefault(); openPayment(); }
    });

    document.querySelector('[data-clear-cart]')?.addEventListener('click', function () {
        if (cart.length && !window.confirm('Clear the current cart?')) return;
        cart.length = 0;
        selectedCustomer = null;
        renderSelectedCustomer();
        renderCart();
    });

    // Customers
    let customerTimer = null;
    customerSearch.addEventListener('input', function () {
        clearTimeout(customerTimer);
        const q = customerSearch.value.trim();
        customerResults.innerHTML = '';
        if (!q) return;
        customerTimer = setTimeout(async () => {
            try {
                const r = await fetch(`${cfg.customerSearchUrl}?q=${encodeURIComponent(q)}`, {credentials:'same-origin'});
                const data = await r.json();
                customerResults.innerHTML = '';
                (data.customers || []).forEach(c => {
                    const b = document.createElement('button'); b.type='button'; b.className='customer-result';
                    b.innerHTML = `<strong>${escapeHtml(c.name)}</strong><span>${escapeHtml(c.phone || c.email || '')} · Balance ${money(c.account_balance)}</span>`;
                    b.addEventListener('click', () => { selectedCustomer = c; customerSearch.value=''; customerResults.innerHTML=''; renderSelectedCustomer(); });
                    customerResults.appendChild(b);
                });
            } catch (_) { customerResults.innerHTML = ''; }
        }, 180);
    });

    function renderSelectedCustomer() {
        if (!selectedCustomer) { selectedCustomerEl.hidden = true; selectedCustomerEl.innerHTML=''; return; }
        selectedCustomerEl.hidden = false;
        selectedCustomerEl.innerHTML = `<div><strong>${escapeHtml(selectedCustomer.name)}</strong><span>${escapeHtml(selectedCustomer.phone || selectedCustomer.email || '')}</span></div><button type="button">×</button>`;
        selectedCustomerEl.querySelector('button').addEventListener('click', () => { selectedCustomer=null; renderSelectedCustomer(); });
    }

    // Payment modal
    function paymentMethodOptions() {
        return cfg.paymentMethods.map(m => `<option value="${m.id}" data-type="${escapeHtml(m.method_type)}" data-ref="${Number(m.requires_reference)}">${escapeHtml(m.name)}</option>`).join('');
    }

    function addPaymentRow(defaultAmount) {
        const row = document.createElement('div'); row.className='payment-row';
        row.innerHTML = `<select data-payment-method>${paymentMethodOptions()}</select><input type="number" min="0" step="0.01" value="${Number(defaultAmount || 0).toFixed(2)}" data-payment-amount><input type="text" maxlength="120" placeholder="Reference" data-payment-reference><button type="button" class="icon-danger" data-remove-payment>×</button>`;
        row.querySelectorAll('select,input').forEach(el => el.addEventListener('input', recalcPayments));
        row.querySelector('[data-remove-payment]').addEventListener('click', () => { if (paymentRows.children.length > 1) row.remove(); recalcPayments(); });
        paymentRows.appendChild(row); recalcPayments();
    }

    function openPayment() {
        if (!cart.length) return;
        const t = totals();
        paymentRows.innerHTML='';
        addPaymentRow(t.total);
        paymentDue.textContent=money(t.total);
        errorBox.hidden=true;
        paymentModal.hidden=false;
        document.body.classList.add('modal-open');
    }

    function closePayment() { paymentModal.hidden=true; document.body.classList.remove('modal-open'); }
    payButton.addEventListener('click', openPayment);
    document.querySelectorAll('[data-close-payment]').forEach(b=>b.addEventListener('click',closePayment));
    document.querySelector('[data-add-payment]')?.addEventListener('click',()=>addPaymentRow(0));

    function recalcPayments() {
        const due=totals().total;
        let allocated=0;
        paymentRows.querySelectorAll('[data-payment-amount]').forEach(i=>allocated+=Number(i.value||0));
        allocated=roundMoney(allocated);
        paymentAllocated.textContent=money(allocated);
        paymentRemaining.textContent=money(roundMoney(due-allocated));
    }

    function paymentPayload() {
        return Array.from(paymentRows.querySelectorAll('.payment-row')).map(row => ({
            payment_method_id: Number(row.querySelector('[data-payment-method]').value),
            amount: row.querySelector('[data-payment-amount]').value,
            reference_no: row.querySelector('[data-payment-reference]').value.trim()
        })).filter(p=>Number(p.amount)>0);
    }

    function itemPayload() {
        return cart.map(i => ({product_id:i.id, quantity:String(i.quantity), unit_price:i.unit_price.toFixed(2), discount_amount:Number(i.discount_amount).toFixed(2)}));
    }

    async function completeSale() {
        errorBox.hidden=true;
        const button=document.querySelector('[data-complete-sale]'); button.disabled=true; button.textContent='Processing…';
        try {
            const response=await fetch(cfg.processUrl,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},credentials:'same-origin',body:JSON.stringify({_csrf:cfg.csrf,held_sale_id:cfg.heldSale?Number(cfg.heldSale.id):0,customer_id:selectedCustomer?Number(selectedCustomer.id):0,notes:document.querySelector('[data-sale-notes]').value.trim(),items:itemPayload(),payments:paymentPayload()})});
            const data=await response.json();
            if(!response.ok||!data.ok)throw new Error(data.message||'Sale could not be completed.');
            window.location.href=cfg.receiptBase+encodeURIComponent(data.sale_id);
        }catch(error){errorBox.textContent=error.message||'Sale could not be completed.';errorBox.hidden=false;button.disabled=false;button.textContent='Complete sale';}
    }
    document.querySelector('[data-complete-sale]')?.addEventListener('click',completeSale);

    async function holdSale() {
        if(!cart.length)return;
        const button=document.querySelector('[data-hold-sale]');button.disabled=true;button.textContent='Holding…';
        try{const response=await fetch(cfg.holdUrl,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},credentials:'same-origin',body:JSON.stringify({_csrf:cfg.csrf,held_sale_id:cfg.heldSale?Number(cfg.heldSale.id):0,customer_id:selectedCustomer?Number(selectedCustomer.id):0,notes:document.querySelector('[data-sale-notes]')?.value.trim()||'',items:itemPayload()})});const data=await response.json();if(!response.ok||!data.ok)throw new Error(data.message||'Sale could not be held.');window.location.href=cfg.heldListUrl;}catch(error){alert(error.message||'Sale could not be held.');button.disabled=false;button.textContent='Hold sale';}}
    document.querySelector('[data-hold-sale]')?.addEventListener('click',holdSale);

    // Resume held sale
    if (cfg.heldSale) {
        (cfg.heldSale.items || []).forEach(raw => cart.push(normalizeProduct(raw)));
        if (cfg.heldSale.customer_id) {
            selectedCustomer = {id:Number(cfg.heldSale.customer_id),name:cfg.heldSale.customer_name,phone:cfg.heldSale.customer_phone||'',account_balance:'0.00'};
            renderSelectedCustomer();
        }
        const notes=document.querySelector('[data-sale-notes]'); if(notes) notes.value=cfg.heldSale.notes||'';
        renderCart();
    }
})();
