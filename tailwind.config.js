/** @type {import('tailwindcss').Config} */
module.exports = {
    theme: {
        extend: {
            fontFamily: {
                sans: ["Poppins", "sans-serif"],
            },
            colors: {
                // Every layout supplies its own values via :root.
                // Fallbacks keep things sane if a layout forgets one.
                brand: {
                    50: "var(--color-brand-50, #f6fceb)",
                    100: "var(--color-brand-100, #eaf8d4)",
                    200: "var(--color-brand-200, #d5f0ae)",
                    300: "var(--color-brand-300, #b5e67e)",
                    400: "var(--color-brand-400, #98da5e)",
                    500: "var(--brand-500, #88d14f)",
                    600: "var(--brand-600, #82cd47)",
                    700: "var(--brand-700, #72b93c)",
                    800: "var(--color-brand-800, #5d9732)",
                    900: "var(--color-brand-900, #4a7828)",
                },
            },
        },
    },
    plugins: [],
};
