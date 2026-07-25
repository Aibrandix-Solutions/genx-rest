export function lineSubtotal(item) {
    const price = Number(item?.price || 0);
    const qty = Number(item?.quantity || 1);
    return price * qty;
}

export function computeItemDiscountAmount(item) {
    const type = item?.discount_type;
    const value = Number(item?.discount_value || 0);
    if (!type || value <= 0) {
        return 0;
    }

    const sub = lineSubtotal(item);
    if (type === "percent") {
        return Math.min(sub, (sub * Math.min(value, 100)) / 100);
    }
    if (type === "fixed") {
        return Math.min(sub, value);
    }

    return 0;
}

export function lineTotalAmount(item) {
    return Math.max(0, lineSubtotal(item) - computeItemDiscountAmount(item));
}

export function hasItemDiscount(item) {
    return computeItemDiscountAmount(item) > 0;
}

export function normalizeItemDiscountFields(item) {
    const amount = computeItemDiscountAmount(item);
    if (amount <= 0) {
        item.discount_type = null;
        item.discount_value = null;
        item.item_discount_amount = null;
        return;
    }

    item.item_discount_amount = amount;
}
