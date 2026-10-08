/**
 * Live invoice editor: dynamic line items with totals, discounts and taxes.
 */
export default (initialItems = [], taxRate = 0, symbol = '$', decimals = 2) => ({
    items: initialItems.length ? initialItems : [{ description: '', quantity: 1, unit_price: 0, discount: 0, product_id: '', plan_id: '' }],
    taxRate: Number(taxRate) || 0,
    symbol,
    decimals,

    add(item = {}) {
        this.items.push({ description: '', quantity: 1, unit_price: 0, discount: 0, product_id: '', plan_id: '', ...item });
    },

    remove(index) {
        this.items.splice(index, 1);
        if (!this.items.length) this.add();
    },

    fromPlan(index, event) {
        const option = event.target.selectedOptions[0];
        if (!option || !option.value) return;
        const item = this.items[index];
        item.plan_id = option.value;
        item.product_id = option.dataset.product || '';
        item.description = option.dataset.description || item.description;
        item.unit_price = Number(option.dataset.price || 0);
    },

    lineTotal(item) {
        const gross = Number(item.quantity || 0) * Number(item.unit_price || 0);
        return gross - gross * (Number(item.discount || 0) / 100);
    },

    get subtotal() {
        return this.items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0), 0);
    },

    get discount() {
        return this.items.reduce((sum, item) => {
            const gross = Number(item.quantity || 0) * Number(item.unit_price || 0);
            return sum + gross * (Number(item.discount || 0) / 100);
        }, 0);
    },

    get tax() {
        return (this.subtotal - this.discount) * (this.taxRate / 100);
    },

    get total() {
        return this.subtotal - this.discount + this.tax;
    },

    money(value) {
        return this.symbol + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: this.decimals, maximumFractionDigits: this.decimals });
    },
});
