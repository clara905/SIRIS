import api from "../axios";
export interface Person { id: number; name: string; email: string; role: { name: string }; bidang_id: number | null; sub_bidang_id: number | null; satker_id: number | null; bidang: { nama_bidang: string } | null; sub_bidang: { nama_sub_bidang: string } | null; satker: { nama_satker: string } | null }
export interface Vulnerability { id: number; nama_kerentanan: string; deskripsi?: string }
export interface Threat { id: number; nama_ancaman: string; vulnerabilities: Vulnerability[] }
export async function saveThreat(data: { asset_id: number; nama_ancaman: string; deskripsi: string }): Promise<Threat> {
    const response = await api.post<{ data: Threat }>("/satker/threats", data);
    return response.data.data;
}
export interface Treatment { id: number; status: string; strategi: string; catatan: string; pic: number | null; target_selesai: string | null; approved_by: number | null; approved_at: string | null }
export interface Assessment { id: number; asset: Asset; threat?: Threat; threats: Threat[]; vulnerabilities: Vulnerability[]; likelihood: number; impact: number; skor: number; level: string; dinilai_oleh: { name: string } | null; tanggal_penilaian: string; treatments: Treatment[]; catatan: string | null }
export interface SatkerAssetInput { nama_aset: string; kategori: string; kondisi: string; deskripsi: string; idx: number | null; jumlah: number; merk: string; snumber: string; pengadaan: string; nilai_kekritisan: number | null }
export interface Asset { idx: number | null; jumlah: number; merk: string | null; snumber: string | null; pengadaan: string | null; nilai_kekritisan: number | null; lokasi: string | null; satker_id: number | null; id: number; kode_aset: string; nama_aset: string; kategori: string; kondisi: string; status: string; deskripsi: string | null; created_by: number | null; creator: { id: number; name: string } | null; permissions: { can_view: boolean; can_edit: boolean }; sub_bidang: { nama_sub_bidang: string } | null; satker: { nama_satker: string } | null; has_active_risk: boolean; risk_level: string | null; threats: Threat[]; risk_assessments: Assessment[] }
export interface NewVulnerability { nama: string; deskripsi: string; threat_ids: number[] }
export interface Submission { created_by: number; satker: { nama_satker: string } | null; id: number; asset_id: number; asset: Asset; status: string; threats: Threat[]; vulnerabilities: Vulnerability[]; new_vulnerabilities: NewVulnerability[]; catatan: string; reviewer_note: string | null; created_at: string; updated_at: string; timeline: { status: string; label: string; at: string }[]; assessment: Assessment | null; creator?: { name: string } }
export interface SubmissionInput { asset_id: number; threat_ids: number[]; vulnerability_ids: number[]; new_vulnerabilities: NewVulnerability[]; catatan: string; action: "draft" | "submit" }
export interface Page<T> { data: T[]; current_page: number; last_page: number; total: number }
export interface PortalNotification { id: number; title: string; message: string; url: string; read_at: string | null; created_at: string }
export interface Dashboard { attention_treatments: (Treatment & { risk_assessment: Assessment })[]; summary: { total_assets: number; active_submissions: number; needs_revision: number; problem_assets: number }; attention: Submission[]; recent: Submission[]; problem_assets: Asset[] }
export async function getPortal<T>(path: string, signal?: AbortSignal): Promise<T> { const response = await api.get<{ data: T }>(`/satker/${path}`, { signal }); return response.data.data; }
export async function saveAsset(data: SatkerAssetInput, id?: number) { const response = id ? await api.put<{ data: Asset }>(`/satker/assets/${id}`, data) : await api.post<{ data: Asset }>("/satker/assets", data); return response.data.data; }
export async function saveSubmission(data: SubmissionInput, id?: number) { const response = id ? await api.put<{ data: Submission }>(`/satker/pengajuan/${id}`, data) : await api.post<{ data: Submission }>("/satker/pengajuan", data); return response.data.data; }
export const dateText = (value?: string | null) => value ? new Intl.DateTimeFormat("id-ID", { dateStyle: "medium" }).format(new Date(value)) : "Belum tersedia";
