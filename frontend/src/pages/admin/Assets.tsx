import AdminDeleteButton from "../../components/AdminDeleteButton";
import { useEffect, useRef, useState, type FormEvent } from "react";
import { Boxes, Search, Plus, RefreshCw, Pencil, X, ChevronLeft, ChevronRight, Monitor, Database, Package } from "lucide-react";
import { getAssets, saveAsset } from "../../api/assetApi";
import { getApiError } from "../../api/axios";
import type { Asset, AssetCategory, AssetInput, AssetPage } from "../../types/asset";
import OrganizationFields from "../../components/OrganizationFields";
import "./Dashboard.css";
import "./Assets.css";
import SirisWordmark from "../../components/SirisWordmark";

const categories = { physical: "Fisik", software: "Perangkat lunak", digital: "Digital" };
const categoryIcons = { physical: Package, software: Monitor, digital: Database };
const categoryDescriptions = { physical: "Perangkat & infrastruktur", software: "Aplikasi & sistem", digital: "Data & informasi" };
const blank: AssetInput = { bidang_id: null, sub_bidang_id: null, satker_id: null, idx: null, kondisi: "", merk: "", snumber: "", pengadaan: "", lokasi: "", stock: 1, nama: "", kategori: "physical", keterangan: "", nilai_kekritisan: null };

export default function Assets() {
    const [result, setResult] = useState<AssetPage | null>(null);
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [category, setCategory] = useState("");
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");
    const [editor, setEditor] = useState<{ asset?: Asset } | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError("");
        getAssets({ page, search: query, kategori: category }, controller.signal)
            .then((data) => { if (!controller.signal.aborted) setResult(data); })
            .catch((err: unknown) => { if (!controller.signal.aborted) setError(getApiError(err)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [page, query, category, revision]);

    function searchAssets(event: FormEvent) {
        event.preventDefault();
        setPage(1);
        setQuery(search.trim());
    }

    return (
        <div className="dashboard assets-page">
            <div className="page-header">
                <div><span className="dashboard-eyebrow">INVENTARIS KEAMANAN INFORMASI</span><h1>Manajemen Aset</h1><p>Kelola inventaris, kepemilikan, dan tingkat kekritisan aset.</p></div>
                <button className="asset-primary" onClick={() => { setNotice(""); setEditor({}); }}><Plus size={17} />Tambah aset</button>
            </div>
            <section className="asset-intro" aria-label="Tentang inventaris aset">
                <div className="asset-intro-icon"><Boxes size={28} /></div>
                <div className="asset-intro-copy"><span className="asset-hero-eyebrow">PENGELOLAAN ASET TERPUSAT</span><h2>Kenali aset.<br />Lindungi yang bernilai.</h2><p>Petakan aset fisik, perangkat lunak, dan digital untuk mendukung pengelolaan risiko yang terarah.</p></div>
                <div className="asset-hero-metric"><span>Aset sesuai filter</span><strong>{loading || error || !result ? "—" : result.total.toLocaleString("id-ID")}</strong><small>{query ? `Pencarian: ${query}` : "Inventaris terdaftar"}</small></div>
            </section>
            <div className="asset-category-cards" role="group" aria-label="Kategori aset">
                <button className={`asset-category-card ${category === "" ? "is-active" : ""}`} aria-pressed={category === ""} onClick={() => { setCategory(""); setPage(1); }}><span className="asset-card-icon"><Boxes size={22} /></span><strong>Semua aset</strong><small>Seluruh kategori inventaris</small><span className="asset-card-indicator" aria-hidden="true" /></button>
                {(Object.keys(categories) as AssetCategory[]).map((key) => { const Icon = categoryIcons[key]; return <button key={key} className={`asset-category-card ${category === key ? "is-active" : ""}`} aria-pressed={category === key} onClick={() => { setCategory(key); setPage(1); }}><span className={`asset-card-icon asset-${key}`}><Icon size={22} /></span><strong>{categories[key]}</strong><small>{categoryDescriptions[key]}</small><span className="asset-card-indicator" aria-hidden="true" /></button>; })}
            </div>
            {notice && <p className="asset-notice" role="status">{notice}</p>}
            <section className="dashboard-panel">
                <div className="panel-header asset-panel-heading"><div><h3>Daftar Aset</h3><p>{!loading && !error && result ? `${result.total.toLocaleString("id-ID")} aset sesuai filter` : <>Data inventaris dari sistem <SirisWordmark /></>}</p></div><button className="refresh-button" disabled={loading} onClick={() => setRevision((value) => value + 1)}><RefreshCw size={15} className={loading ? "dashboard-spinning" : ""} />Perbarui</button></div>
                <div className="asset-filters">
                    <div className="asset-filter-caption"><span className="asset-filter-dot" />{category ? categories[category as AssetCategory] : "Seluruh kategori"}{(category || query) && <button type="button" onClick={() => { setCategory(""); setSearch(""); setQuery(""); setPage(1); }}>Reset filter <X size={12} /></button>}</div>
                    <form className="asset-search" onSubmit={searchAssets}><Search size={17} aria-hidden="true" /><input aria-label="Cari nama, merk, serial, lokasi" placeholder="Cari nama, merk, serial, lokasi..." value={search} onChange={(event) => setSearch(event.target.value)} /><button type="submit">Cari</button></form>
                </div>
                {error ? <div className="asset-empty" role="alert"><Boxes size={30} /><h3>Data aset belum dapat dimuat</h3><p>{error}</p><button className="refresh-button" onClick={() => setRevision((value) => value + 1)}>Coba lagi</button></div> : loading ? <div className="asset-empty" role="status"><RefreshCw className="dashboard-spinning" size={26} /><p>Memuat inventaris aset...</p></div> : result?.data.length ? <>
                    <div className="table-wrapper"><table className="dashboard-table asset-table"><caption className="asset-sr-only">Daftar inventaris aset berdasarkan pencarian dan kategori yang dipilih</caption><thead><tr><th scope="col">Aset</th><th scope="col">Kategori</th><th scope="col">Stok</th><th scope="col">Detail inventaris</th><th scope="col">Organisasi</th><th scope="col">Kekritisan</th><th scope="col">Aksi</th></tr></thead><tbody>
                        {result.data.map((asset) => { const Icon = categoryIcons[asset.kategori] || Boxes; return <tr key={asset.id}>
                            <td><div className="asset-name"><span className={`asset-type-icon asset-${asset.kategori}`}><Icon size={19} /></span><div><strong>{asset.nama}</strong>{asset.keterangan && <p className="asset-description">{asset.keterangan}</p>}</div></div></td>
                            <td><span className={`asset-category-badge asset-${asset.kategori}`}>{categories[asset.kategori] || asset.kategori}</span></td>
                            <td>{asset.stock.toLocaleString("id-ID")} unit</td><td><dl className="asset-inventory-details"><div><dt>IDX</dt><dd>{asset.idx ?? "—"}</dd></div><div><dt>Kondisi</dt><dd>{asset.kondisi || "—"}</dd></div><div><dt>Merk</dt><dd>{asset.merk || "—"}</dd></div><div><dt>Serial</dt><dd>{asset.snumber || "—"}</dd></div><div><dt>Pengadaan</dt><dd>{asset.pengadaan || "—"}</dd></div><div><dt>Lokasi</dt><dd>{asset.lokasi || "—"}</dd></div></dl></td><td>{asset.bidang?.nama_bidang || "Belum ditetapkan"}<small className="table-subtext">{[asset.sub_bidang?.nama_sub_bidang, asset.satker?.nama_satker].filter(Boolean).join(" · ") || "—"}</small></td>
                            <td><div className="asset-criticality" aria-label={asset.nilai_kekritisan ? `Kekritisan ${asset.nilai_kekritisan} dari 5` : "Kekritisan belum dinilai"}><div aria-hidden="true">{[1, 2, 3, 4, 5].map((level) => <i key={level} className={level <= (asset.nilai_kekritisan || 0) ? "filled" : ""} />)}</div><small>{asset.nilai_kekritisan ? `${asset.nilai_kekritisan} / 5` : "Belum dinilai"}</small></div></td>
                            <td><div className="asset-actions"><button className="asset-edit" aria-label={`Edit aset ${asset.nama}`} onClick={() => { setNotice(""); setEditor({ asset }); }}><Pencil size={15} />Edit</button><AdminDeleteButton endpoint={`/admin/assets/${asset.id}`} label={asset.nama} onDeleted={() => { setNotice("Data berhasil dihapus."); setPage(1); setRevision((v) => v + 1); } } /></div></td>
                        </tr>; })}
                    </tbody></table></div>
                    <div className="asset-pagination"><span>Menampilkan {result.from}–{result.to} dari {result.total} aset</span><div><button aria-label="Halaman sebelumnya" disabled={page <= 1} onClick={() => setPage(page - 1)}><ChevronLeft size={17} /></button><span>Halaman {result.current_page} dari {result.last_page}</span><button aria-label="Halaman berikutnya" disabled={page >= result.last_page} onClick={() => setPage(page + 1)}><ChevronRight size={17} /></button></div></div>
                </> : <div className="asset-empty"><Boxes size={36} /><h3>{query || category ? "Aset tidak ditemukan" : "Belum ada aset terdaftar"}</h3><p>{query || category ? "Coba kata kunci lain atau tampilkan seluruh kategori." : "Tambahkan aset pertama untuk mulai menyusun inventaris."}</p>{query || category ? <button className="refresh-button" onClick={() => { setQuery(""); setSearch(""); setCategory(""); setPage(1); }}>Reset filter</button> : <button className="asset-primary" onClick={() => setEditor({})}><Plus size={16} />Tambah aset</button>}</div>}
            </section>
            <p className="asset-footnote">Nilai kekritisan menggunakan skala 1–5. Semakin tinggi nilai, semakin kritis aset tersebut.</p>
            {editor && <AssetEditor asset={editor.asset} onClose={() => setEditor(null)} onSaved={() => { setEditor(null); setNotice(editor.asset ? "Perubahan aset berhasil disimpan." : "Aset baru berhasil ditambahkan."); setRevision((value) => value + 1); }} />}
        </div>
    );
}

function AssetEditor({ asset, onClose, onSaved }: { asset?: Asset; onClose: () => void; onSaved: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [form, setForm] = useState<AssetInput>(asset ? { bidang_id: asset.bidang_id ?? null, sub_bidang_id: asset.sub_bidang_id ?? null, satker_id: asset.satker_id ?? null, idx: asset.idx, kondisi: asset.kondisi || "", merk: asset.merk || "", snumber: asset.snumber || "", pengadaan: asset.pengadaan || "", lokasi: asset.lokasi || "", stock: asset.stock, nama: asset.nama, kategori: asset.kategori, keterangan: asset.keterangan || "", nilai_kekritisan: asset.nilai_kekritisan } : { ...blank });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState("");
    useEffect(() => { const element = dialog.current; element?.showModal(); return () => element?.close(); }, []);
    async function submit(event: FormEvent) {
        event.preventDefault();
        if (saving) return;
        setSaving(true); setError("");
        try { await saveAsset(form, asset?.id); onSaved(); }
        catch (err) { setError(getApiError(err)); }
        finally { setSaving(false); }
    }
    return <dialog ref={dialog} className="asset-dialog" aria-labelledby="asset-editor-title" onCancel={(event) => { event.preventDefault(); if (!saving) onClose(); }}>
        <form onSubmit={submit}><header><div><span className="dashboard-eyebrow">INVENTARIS <SirisWordmark /></span><h2 id="asset-editor-title">{asset ? "Edit aset" : "Tambah aset baru"}</h2></div><button type="button" className="asset-edit" aria-label="Tutup formulir" disabled={saving} onClick={onClose}><X size={20} /></button></header>
        <div className="asset-form-fields"><p>Lengkapi identitas aset. Kolom bertanda * wajib diisi.</p>
            <label>ID inventaris eksternal (idx)<input type="number" min={1} max={2147483647} step={1} placeholder="Opsional, contoh: 257" value={form.idx ?? ""} onChange={(event) => setForm({ ...form, idx: event.target.value === "" ? null : event.target.valueAsNumber })} /></label>
            <label>Stok / jumlah aset *<input type="number" required min={1} max={2147483647} step={1} value={Number.isNaN(form.stock) ? "" : form.stock} onChange={(event) => setForm({ ...form, stock: event.target.valueAsNumber })} /><small>Jumlah unit aset, minimal 1.</small></label><label>Nama aset *<input autoFocus required maxLength={255} placeholder="Nama aset" value={form.nama} onChange={(event) => setForm({ ...form, nama: event.target.value })} /></label>
            <div className="asset-form-row"><label>Kategori *<select value={form.kategori} onChange={(event) => setForm({ ...form, kategori: event.target.value as AssetCategory })}>{Object.entries(categories).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
            <label>Nilai kekritisan<select value={form.nilai_kekritisan ?? ""} onChange={(event) => setForm({ ...form, nilai_kekritisan: event.target.value ? Number(event.target.value) : null })}><option value="">Belum dinilai</option>{[1, 2, 3, 4, 5].map((value) => <option key={value} value={value}>{value} / 5</option>)}</select></label></div>
            <div className="asset-form-row"><label>Kondisi<input maxLength={100} placeholder="Contoh: Baik" value={form.kondisi || ""} onChange={(event) => setForm({ ...form, kondisi: event.target.value })} /></label><label>Merk<input maxLength={255} value={form.merk || ""} onChange={(event) => setForm({ ...form, merk: event.target.value })} /></label></div>
            <label>Serial number (snumber)<input maxLength={255} value={form.snumber || ""} onChange={(event) => setForm({ ...form, snumber: event.target.value })} /></label>
            <OrganizationFields value={form} onChange={(organization, location) => setForm({ ...form, ...organization, lokasi: location })} />
            <div className="asset-form-row"><label>Tanggal pengadaan<input type="date" min="1000-01-01" max="9999-12-31" value={form.pengadaan || ""} onChange={(event) => setForm({ ...form, pengadaan: event.target.value })} /></label><label>Lokasi organisasi<input readOnly value={form.lokasi || ""} placeholder="Pilih organisasi di bawah" /></label></div>
            <label>Keterangan<textarea rows={3} placeholder="Informasi tambahan mengenai aset" value={form.keterangan || ""} onChange={(event) => setForm({ ...form, keterangan: event.target.value })} /></label>
            {error && <p className="asset-form-error" role="alert">{error}</p>}
        </div><footer><button type="button" className="asset-edit" disabled={saving} onClick={onClose}>Batal</button><button className="asset-primary" type="submit" disabled={saving}>{saving ? "Menyimpan..." : "Simpan aset"}</button></footer></form>
    </dialog>;
}
