import api from "./axios";
import type { DashboardResponse } from "../types/dashboard";

export const getAdminDashboard =
    async (): Promise<DashboardResponse> => {
        const response =
            await api.get<DashboardResponse>(
                "/admin/dashboard"
            );

        const result = response.data;
        if (!result?.success || !result.data?.summary || !result.data?.risk_levels ||
            !result.data?.treatment_status || !Array.isArray(result.data?.recent_risks)) {
            throw new Error("Format respons dashboard tidak sesuai. Periksa alamat API dan respons backend.");
        }
        return result;
    };
