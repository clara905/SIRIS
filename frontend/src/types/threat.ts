export interface Threat {
    id: number;
    kode_ancaman: string;
    nama_ancaman: string;
    deskripsi: string | null;
    asset_id: number | null;
    is_active: boolean;
    asset: { id: number; nama: string; snumber: string | null } | null;
    dibuat_oleh: { name: string } | null;
    vulnerabilities: { id: number; nama_kerentanan: string }[];
}

export interface ThreatInput {
    kode_ancaman: string;
    nama_ancaman: string;
    deskripsi: string;
    asset_id: number | null;
    is_active: boolean;
    vulnerability_ids: number[];
}

export interface ThreatPage {
    data: Threat[];
    total: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
}
