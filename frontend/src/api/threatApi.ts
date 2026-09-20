import api from "./axios";
import type { Threat, ThreatInput, ThreatPage } from "../types/threat";

export async function getThreats(page: number, search: string, status: string, signal?: AbortSignal) {
    const response = await api.get<{ data: ThreatPage }>("/admin/threats", {
        params: { page, search, ...(status === "" ? {} : { is_active: status }) }, signal,
    });
    return response.data.data;
}

export async function saveThreat(input: ThreatInput, id?: number) {
    const response = id
        ? await api.put<{ data: Threat }>(`/admin/threats/${id}`, input)
        : await api.post<{ data: Threat }>("/admin/threats", input);
    return response.data.data;
}
