export type AssetCategory = "physical" | "software" | "digital";

export interface Asset {
    id: number;
    bidang_id: number | null;
    sub_bidang_id: number | null;
    satker_id: number | null;
    idx: number | null;
    kondisi: string | null;
    merk: string | null;
    snumber: string | null;
    pengadaan: string | null;
    lokasi: string | null;
    nama: string;
    stock: number;
    kategori: AssetCategory;
    keterangan: string | null;
    nilai_kekritisan: number | null;
    bidang: { nama_bidang: string } | null;
    sub_bidang: { nama_sub_bidang: string } | null;
    satker: { nama_satker: string } | null;
}

export type AssetInput = Pick<Asset, "bidang_id" | "sub_bidang_id" | "satker_id" | "idx" | "kondisi" | "merk" | "snumber" | "pengadaan" | "lokasi" | "nama" | "stock" | "kategori" | "keterangan" | "nilai_kekritisan">;

export interface AssetPage {
    data: Asset[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}
