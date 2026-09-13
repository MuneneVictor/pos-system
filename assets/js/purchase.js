(function () {
    'use strict';

    const root = document.querySelector('[data-purchase-builder]');
    if (!root) return;

    const products = Array.isArray(window.PURCHASE_PRODUCTS) ? window.PURCHASE_PRODUCTS : [];
    const rows = root.querySelector('[data-purchase-rows]');
    const addButton = root.querySelector('[data-add-purchase-row]');
    const totalEl = root.querySelector('[data-purchase-total]');
    const discountInput = root.querySelector('[name="discount_amount"]');

    function money(value) {
        return new Intl.NumberFormat('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0);
    }

    function productOptions() {
        return '<option value="">Select product</option>' + products.map(p =>
            `<option value="${p.id}" data-cost="${p.buying_price}" data-unit="${p.short_name}">${escapeHtml(p.name)} (${escapeHtml(p.sku)})</option>`
        ).join('');
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
    }

    function createRow() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><select name="product_id[]" class="purchase-product" required>${productOptions()}</select></td>
            <td><div class="qty-with-unit"><input type="number" name="quantity[]" min="0.001" step="0.001" value="1" required><span data-unit>—</span></div></td>
            <td><input type="number" name="unit_cost[]" min="0" step="0.0001" value="0.0000" required></td>
            <td class="purchase-line-total">0.00</td>
            <td><button type="button" class="icon-danger" data-remove-row aria-label="Remove">×</button></td>`;
        rows.appendChild(tr);
        bindRow(tr);
        recalc();
    }

    function bindRow(tr) {
        const product = tr.querySelector('.purchase-product');
        const qty = tr.querySelector('[name="quantity[]"]');
        const cost = tr.querySelector('[name="unit_cost[]"]');
        const unit = tr.querySelector('[data-unit]');

        product.addEventListener('change', function () {
            const option = product.selectedOptions[0];
            if (option) {
                cost.value = Number(option.dataset.cost || 0).toFixed(4);
                unit.textContent = option.dataset.unit || '—';
            }
            recalc();
        });

        qty.addEventListener('input', recalc);
        cost.addEventListener('input', recalc);
        tr.querySelector('[data-remove-row]').addEventListener('click', function () {
            if (rows.querySelectorAll('tr').length > 1) tr.remove();
            recalc();
        });
    }

    function recalc() {
        let subtotal = 0;
        rows.querySelectorAll('tr').forEach(tr => {
            const qty = Number(tr.querySelector('[name="quantity[]"]').value || 0);
            const cost = Number(tr.querySelector('[name="unit_cost[]"]').value || 0);
            const line = qty * cost;
            subtotal += line;
            tr.querySelector('.purchase-line-total').textContent = money(line);
        });
        const discount = Number(discountInput?.value || 0);
        totalEl.textContent = money(Math.max(0, subtotal - discount));
    }

    rows.querySelectorAll('tr').forEach(bindRow);
    addButton?.addEventListener('click', createRow);
    discountInput?.addEventListener('input', recalc);
    if (!rows.querySelector('tr')) createRow();
    recalc();
})();
