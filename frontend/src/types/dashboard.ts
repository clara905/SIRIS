export interface DashboardSummary {
    total_assets: number;
    total_threats: number;
    total_vulnerabilities: number;
    total_risks: number;
    high_risks: number;
    extreme_risks: number;
}

export interface RiskLevels {
    rendah: number;
    sedang: number;
    tinggi: number;
    sangat_tinggi: number;
    ekstrem: number;
}

export interface TreatmentStatus {
    belum: number;
    proses: number;
    selesai: number;
}

export interface BidangMonitoring {
    id: number;
    nama_bidang: string;
    total_asset: number;
    total_user: number;
    total_risiko: number;
}

export interface DashboardAsset {
    id: number;
    snumber: string | null;
    nama: string;
}

export interface DashboardThreat {
    id: number;
    kode_ancaman: string;
    nama_ancaman: string;
}

export interface DashboardUser {
    id: number;
    name: string;
}

export interface DashboardTreatment {
    id: number;
    risk_assessment_id: number;
    status: string;
}

export interface RecentRisk {
    id: number;
    asset: DashboardAsset | null;
    threat: DashboardThreat | null;
    likelihood: number;
    impact: number;
    skor: number;
    level: string;
    dinilai_oleh: DashboardUser | null;
    tanggal_penilaian: string;
    treatments: DashboardTreatment[];
}

export interface DashboardData {
    summary: DashboardSummary;
    risk_levels: RiskLevels;
    treatment_status: TreatmentStatus;
    bidang_monitoring: BidangMonitoring[];
    recent_risks: RecentRisk[];
}

export interface DashboardResponse {
    success: boolean;
    message: string;
    data: DashboardData;
}