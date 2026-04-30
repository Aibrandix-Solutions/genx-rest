import Swal from "sweetalert2";

export const showPosAlert = (type = "info", message = "", options = {}) => {
    const text = String(message || "").trim();
    if (!text) {
        return;
    }

    const normalizedType = String(type || "info").toLowerCase();
    const icon = ["success", "error", "warning", "info", "question"].includes(normalizedType)
        ? normalizedType
        : "info";

    try {
        if (typeof window !== "undefined" && typeof window.showToast === "function") {
            window.showToast(icon, text);
            return;
        }

        Swal.fire({
            icon,
            text,
            toast: true,
            position: options.position || "top-end",
            showConfirmButton: false,
            timer: options.timer ?? 3500,
            timerProgressBar: true,
        });
    } catch (error) {
        // Native fallback if SweetAlert is unavailable.
        alert(text);
    }
};

export const showPosConfirm = async (message = "", options = {}) => {
    const text = String(message || "").trim();
    if (!text) {
        return false;
    }

    try {
        const result = await Swal.fire({
            icon: options.icon || "warning",
            text,
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || "Yes",
            cancelButtonText: options.cancelButtonText || "Cancel",
            reverseButtons: true,
            allowOutsideClick: options.allowOutsideClick ?? true,
        });

        return !!result.isConfirmed;
    } catch (error) {
        return confirm(text);
    }
};
