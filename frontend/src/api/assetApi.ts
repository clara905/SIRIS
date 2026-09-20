import api from "./axios";
import type { Asset, AssetInput, AssetPage } from "../types/asset";

export async function getAssets(params: { page: number; search: string; kategori: string }, signal?: AbortSignal) {
    const response = await api.get<{ data: AssetPage }>("/admin/assets", { params, signal });
    return response.data.data;
}

export async function saveAsset(input: AssetInput, id?: number) {
    const response = id
        ? await api.put<{ data: Asset }>(`/admin/assets/${id}`, input)
        : await api.post<{ data: Asset }>("/admin/assets", input);
    return response.data.data;
}
