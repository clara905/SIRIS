import axios from "axios";

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || "/api",
    timeout: 15000,
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
    },
});

api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem("token");

        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

export default api;

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (axios.isAxiosError(error) && error.response?.status === 401 && error.config?.url !== "/login") {
            localStorage.removeItem("token");
            window.location.replace("/login");
        }
        return Promise.reject(error);
    }
);

export function getApiError(error: unknown): string {
    if (axios.isAxiosError<{ message?: string }>(error)) {
        if (error.response?.status === 403) return "Akses ditolak. Gunakan akun admin yang aktif.";
        if (error.response?.status === 401) return error.response.data.message || "Sesi berakhir. Silakan login kembali.";
        if (!error.response) return "Backend tidak dapat dihubungi. Periksa koneksi dan pastikan server API berjalan.";
        return error.response.data.message || "Gagal mengambil data dari server.";
    }
    return error instanceof Error ? error.message : "Terjadi kesalahan. Silakan coba lagi.";
}
