import api from "./axios";
import type { RiskInput, RiskPage, RiskRules } from "../types/riskAssessment";

export async function getRiskAssessments(page: number, search: string, level: string, signal?: AbortSignal) {
    const response = await api.get<{ data: RiskPage }>("/admin/risk-assessments", { params: { page, search, level }, signal });
    return response.data.data;
}
export async function getRiskRules(signal?: AbortSignal) {
    const response = await api.get<{ data: RiskRules }>("/admin/risk-assessments/rules", { signal });
    return response.data.data;
}
export async function saveRiskAssessment(input: RiskInput, id?: number) {
    if (id) await api.put(`/admin/risk-assessments/${id}`, input);
    else await api.post("/admin/risk-assessments", input);
}
