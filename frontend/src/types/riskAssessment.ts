export interface RiskRules {
    scale_min: number;
    scale_max: number;
    levels: { key: string; label: string; min: number; max: number }[];
}
export interface RiskAssessment {
    vulnerabilities?: { id: number; nama_kerentanan: string }[];
    id: number;
    asset_id: number;
    threat_id: number;
    asset: { id: number; nama: string; snumber: string | null } | null;
    threats?: { id: number; nama_ancaman: string }[];
    threat: { id: number; nama_ancaman: string } | null;
    dinilai_oleh: { name: string } | null;
    likelihood: number;
    impact: number;
    skor: number;
    level: string;
    tanggal_penilaian: string;
    catatan: string | null;
}
export interface RiskInput {
    vulnerability_ids: number[];
    asset_id: number | null;
    threat_ids: number[];
    likelihood: number;
    impact: number;
    tanggal_penilaian: string;
    catatan: string;
}
export interface RiskPage {
    data: RiskAssessment[];
    total: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
}
