// Guards for <input type="number"> across the admin panel.
//
// Attached once by delegation rather than wired onto each field, because the
// fields that were missed were exactly the ones nobody thought about — a minus
// sign typed into Cost Price was accepted all the way to the database.
//
// Opt out on a field that genuinely accepts negatives with data-allow-negative.

(function () {
    const isGuardedNumberInput = (el) =>
        el &&
        el.tagName === "INPUT" &&
        el.type === "number" &&
        !el.hasAttribute("data-allow-negative");

    // A focused number input changes its value on mouse wheel, so scrolling the
    // page over one silently edits it. A GST rate entered as 15 was saved as
    // 14.99 this way. Blurring lets the page scroll and leaves the value alone.
    document.addEventListener(
        "wheel",
        function (e) {
            const el = document.activeElement;
            if (isGuardedNumberInput(el) && el === e.target) {
                el.blur();
            }
        },
        { passive: true },
    );

    // The browser accepts "-", "+", "e" and "E" in a number field because the
    // HTML spec allows scientific notation. Quantities, prices and percentages
    // never need it.
    document.addEventListener("keydown", function (e) {
        if (!isGuardedNumberInput(e.target)) return;

        if (["-", "+", "e", "E"].includes(e.key)) {
            e.preventDefault();
        }
    });

    // Typing is only one way in. Pasting "-500" or "1e5" bypasses keydown
    // entirely, and a browser reports an unparseable number field as an empty
    // string — so the bad value arrives as a silent blank rather than an error.
    document.addEventListener("paste", function (e) {
        if (!isGuardedNumberInput(e.target)) return;

        const pasted = (e.clipboardData || window.clipboardData).getData("text");
        const cleaned = pasted.replace(/[^0-9.]/g, "");

        if (cleaned !== pasted) {
            e.preventDefault();
            if (cleaned !== "") {
                document.execCommand("insertText", false, cleaned);
            }
        }
    });

    // Last line of defence: the spinner arrows and programmatic changes do not
    // pass through keydown or paste.
    document.addEventListener("change", function (e) {
        if (!isGuardedNumberInput(e.target)) return;

        const value = parseFloat(e.target.value);

        if (!isNaN(value) && value < 0) {
            e.target.value = Math.abs(value);
            // Alpine and other bindings listen for input, not change.
            e.target.dispatchEvent(new Event("input", { bubbles: true }));
        }
    });
})();