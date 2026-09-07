// 1. Prevent typing of minus, plus, and 'e' (exponential) characters globally
window.preventInvalidChars = function(event) {
    if (['-', '+', 'e', 'E'].includes(event.key)) {
        event.preventDefault();
    }
};

// 2. Fallback: Sanitize copy-pasted values (converts negatives to positive)
// window.sanitizePositiveNumber = function(value) {
//     if (value === '' || value === null) return value; // Allow clearing the input
//     let num = parseFloat(value);
//     return num < 0 ? Math.abs(num) : value;
// };
/**
 * Sanitizes a numeric value to a non-negative float.
 * Returns 0 for anything invalid, negative, or empty.
 * Attach via: @input="field = window.sanitizePositiveNumber(field)"
 */
window.sanitizePositiveNumber = function(value) {
    const parsed = parseFloat(value);
    if (isNaN(parsed) || parsed < 0) return 0;
    return parsed;
};

/**
 * Integer-only input for Indian billing (rupee amounts and item counts).
 * Decimals are stripped outright, not rounded — there are no paise here.
 *
 * Leaves the box EMPTY while the user is mid-edit so the leading 0 can
 * actually be deleted, and strips leading zeros so "0299" becomes "299".
 * The minimum is applied on blur by commitInteger(), never while typing,
 * otherwise a min of 1 would fight the user on every keystroke.
 *
 * Attach via: @input="item.qty = window.sanitizeInteger($event, { max: 100 })"
 */
window.sanitizeInteger = function(event, options = {}) {
    const { max = null } = options;
    const el = event.target;

    // Strip everything that is not a digit — this kills '.', ',', '-' and 'e'
    let digits = String(el.value).replace(/\D/g, '');

    // "0299" -> "299", but keep a lone "0" intact
    digits = digits.replace(/^0+(?=\d)/, '');

    if (digits === '') {
        if (el.value !== '') el.value = '';
        return '';
    }

    let num = parseInt(digits, 10);
    if (max !== null && num > max) num = max;

    // Only write back when the text actually changed, so the caret does not
    // jump to the end of the field on every normal keystroke.
    if (el.value !== String(num)) el.value = String(num);

    return num;
};

/**
 * Settles a sanitizeInteger() field on blur — an empty box falls back to min.
 * Attach via: @blur="item.qty = window.commitInteger(item.qty, 1)"
 */
window.commitInteger = function(value, min = 0) {
    const parsed = parseInt(String(value).replace(/\D/g, ''), 10);
    return isNaN(parsed) ? min : Math.max(min, parsed);
};

/**
 * Integer-only guard, applied by markup instead of per-field wiring.
 *
 * type="number" cannot block a decimal point: step="1" is validation-only,
 * so "1.11111111" types in happily and x-model.number stores it. The fix is
 * type="text" inputmode="numeric" plus the data-int attribute:
 *
 *   <input type="text" inputmode="numeric" data-int data-int-min="1" ... >
 *
 * Registered on document in the CAPTURE phase, which runs before listeners
 * bound to the element itself — so Alpine's x-model reads the sanitised
 * value rather than what was typed. No @input or @blur is needed.
 *
 * data-int      presence enables the guard
 * data-int-max  clamp applied while typing
 * data-int-min  floor applied on blur only, so a min of 1 does not fight
 *               the user on the first keystroke
 */
(function () {
    function cleanIntegerField(el) {
        // Strips '.', ',', '-' and 'e' in one pass.
        let digits = String(el.value).replace(/\D/g, '');

        // "0299" -> "299", but a lone "0" survives.
        digits = digits.replace(/^0+(?=\d)/, '');

        if (digits === '') {
            if (el.value !== '') el.value = '';
            return;
        }

        let num = parseInt(digits, 10);

        const max = el.dataset.intMax;
        if (max !== undefined && max !== '' && num > Number(max)) {
            num = Number(max);
        }

        // Only write back on an actual change, or the caret jumps to the end
        // of the field on every keystroke.
        if (el.value !== String(num)) el.value = String(num);
    }

    document.addEventListener('input', function (event) {
        const el = event.target;
        if (!el || el.dataset === undefined || el.dataset.int === undefined) return;
        cleanIntegerField(el);
    }, true);

    document.addEventListener('blur', function (event) {
        const el = event.target;
        if (!el || el.dataset === undefined || el.dataset.int === undefined) return;

        cleanIntegerField(el);

        const min = el.dataset.intMin;
        if (min !== undefined && min !== '' && (el.value === '' || Number(el.value) < Number(min))) {
            el.value = String(Number(min));
        }

        // Blur fires no input event, so Alpine would keep the pre-commit
        // value without this.
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }, true);
})();

window.sanitizePhone = function(el) {
    // remove everything except digits
    let value = el.value.replace(/\D/g, '');

    // limit to 10 digits
    value = value.slice(0, 10);

    // update input
    el.value = value;
}

/**
 * Sanitizes qty — must be positive integer or decimal, minimum 1.
 * Attach via: @input="item.qty = window.sanitizeQty(item.qty)"
 */
window.sanitizeQty = function(value, min = 1) {
    const parsed = parseFloat(value);
    if (isNaN(parsed) || parsed < min) return min;
    return parsed;
};
