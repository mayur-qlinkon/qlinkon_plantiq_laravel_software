/* ═══════════════════════════════════════════════════════
           BIZALERT
═══════════════════════════════════════════════════════ */
const BizAlert = {
    baseConfig: {
        buttonsStyling: false,
        customClass: {
            popup: "rounded-xl border border-gray-100 shadow-2xl bg-white",
            title: "text-xl font-bold text-[#212538] tracking-tight mt-2",
            htmlContainer: "text-gray-500 text-sm font-medium mt-2",
            confirmButton:
                "bg-[{{ $primary }}] hover:bg-[{{ $hover }}] text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm ml-3",
            cancelButton:
                "bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm mr-3",
            input: "w-full border border-gray-200 rounded-lg px-4 py-3 text-sm text-gray-700 focus:ring-2 focus:ring-[{{ $primary }}]/20 focus:border-[{{ $primary }}] outline-none mt-4 transition-all",
        },
    },

    confirm(
        title = "Are you sure?",
        text = "You won't be able to revert this!",
        confirmText = "Yes, confirm!",
        icon = "warning",
    ) {
        // Start with the default primary color
        let currentConfirmClass = this.baseConfig.customClass.confirmButton;

        // Base layout classes that every button shares (padding, rounded corners, text size)
        const baseBtnLayout = "text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm ml-3";

        // Dynamic color mapping based on the icon type
        const colorMap = {
            warning: `bg-[#ef4444] hover:bg-red-600 ${baseBtnLayout}`, // Kept red for backward compatibility with Delete actions
            error: `bg-[#ef4444] hover:bg-red-600 ${baseBtnLayout}`,
            info: `bg-blue-600 hover:bg-blue-700 ${baseBtnLayout}`,
            success: `bg-emerald-600 hover:bg-emerald-700 ${baseBtnLayout}`,
        };

        // If the passed icon matches our map, override the default primary color
        if (colorMap[icon]) {
            currentConfirmClass = colorMap[icon];
        }

        return Swal.fire({
            ...this.baseConfig,
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: "Cancel",
            reverseButtons: true,
            customClass: {
                ...this.baseConfig.customClass,
                confirmButton: currentConfirmClass,
            },
        });
    },

    toast(title, icon = "success") {
        return Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: {
                popup: "rounded-lg border border-gray-100 shadow-lg mt-16 mr-4 bg-white",
                title: "text-sm font-bold text-gray-700 ml-2",
            },
        }).fire({
            icon,
            title,
        });
    },

    loading(title = "Processing...") {
        return Swal.fire({
            ...this.baseConfig,
            title,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });
    },

    input(title, text = "", inputType = "text", placeholder = "") {
        return Swal.fire({
            ...this.baseConfig,
            title,
            text,
            input: inputType,
            inputPlaceholder: placeholder,
            showCancelButton: true,
            confirmButtonText: "Submit",
            reverseButtons: true,
        });
    },

    close() {
        Swal.close();
    },
};
