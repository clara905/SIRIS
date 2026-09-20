import { useEffect, useRef, useState, type FormEvent } from "react";
import { ShieldAlert, ShieldCheck, Archive, Plus, Search, RefreshCw, Pencil, X, ChevronLeft, ChevronRight, Boxes, Bug } from "lucide-react";
import { getThreats, saveThreat } from "../../api/threatApi";
import { getAssets } from "../../api/assetApi";
import { getApiError } from "../../api/axios";
import type { Threat, ThreatInput, ThreatPage } from "../../types/threat";
import type { AssetPage } from "../../types/asset";
import "./Dashboard.css";
import "./Assets.css";
import "./Threats.css";

const statuses = [
    { value: "", label: "Semua ancaman", text: "Seluruh catatan ancaman", icon: ShieldAlert },
    { value: "1", label: "Ancaman aktif", text: "Ancaman berstatus aktif", icon: ShieldCheck },
    { value: "0", label: "Tidak aktif", text: "Catatan yang dinonaktifkan", icon: Archive },
];

export default function Threats() {
    const [data, setData] = useState<ThreatPage | null>(null);
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");
    const [editor, setEditor] = useState<{ threat?: Threat } | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        setLoading(true); setError("");
        getThreats(page, query, status, controller.signal)
            .then((result) => { if (!controller.signal.aborted) setData(result); })
            .catch((err: unknown) => { if (!controller.signal.aborted) setError(getApiError(err)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [page, query, status, revision]);
    function reset() { setSearch(""); setQuery(""); setStatus(""); setPage(1); }
    return <div className="dashboard assets-page threats-page">
        <div className="page-header"><div><span className="dashboard-eyebrow">IDENTIFIKASI KEAMANAN INFORMASI</span><h1>Manajemen Ancaman</h1><p>Identifikasi ancaman dan kaitannya dengan aset serta kerentanan.</p></div><button className="asset-primary" onClick={() => setEditor({})}><Plus size={17} />Tambah ancaman</button></div>
        <section className="asset-intro threat-intro"><div className="asset-intro-icon"><ShieldAlert size={32} /></div><div className="asset-intro-copy"><span className="asset-hero-eyebrow">THREAT REGISTER</span><h2>Kenali ancaman.<br />Perkuat perlindungan.</h2><p>Dokumentasikan sumber ancaman sebagai dasar penilaian dan penanganan risiko keamanan informasi.</p></div><div className="asset-hero-metric"><span>Ancaman sesuai filter</span><strong>{loading || error || !data ? "—" : data.total.toLocaleString("id-ID")}</strong><small>{query ? `Pencarian: ${query}` : "Catatan ancaman terdaftar"}</small></div></section>
        <div className="asset-category-cards threat-status-cards" role="group" aria-label="Filter status ancaman">{statuses.map(({ value, label, text, icon: Icon }) => <button key={value} className={`asset-category-card ${status === value ? "is-active" : ""}`} aria-pressed={status === value} onClick={() => { setStatus(value); setPage(1); }}><span className="asset-card-icon"><Icon size={22} /></span><strong>{label}</strong><small>{text}</small><span className="asset-card-indicator" aria-hidden="true" /></button>)}</div>
        {notice && <p className="asset-notice" role="status">{notice}</p>}
        <section className="dashboard-panel"><div className="panel-header asset-panel-heading"><div><h3>Daftar Ancaman</h3><p>Pemetaan ancaman, aset terkait, dan kerentanan.</p></div><button className="refresh-button" disabled={loading} onClick={() => setRevision((v) => v + 1)}><RefreshCw size={15} className={loading ? "dashboard-spinning" : ""} />Perbarui</button></div>
            <div className="asset-filters"><div className="asset-filter-caption"><span className="asset-filter-dot" />{statuses.find((item) => item.value === status)?.label}{(query || status) && <button onClick={reset}>Reset filter <X size={12} /></button>}</div><form className="asset-search" onSubmit={(event) => { event.preventDefault(); setPage(1); setQuery(search.trim()); }}><Search size={17} /><input aria-label="Cari kode atau nama ancaman" placeholder="Cari kode atau nama ancaman..." value={search} onChange={(event) => setSearch(event.target.value)} /><button>Cari</button></form></div>
            {loading ? <div className="asset-empty" role="status"><RefreshCw className="dashboard-spinning" size={28} /><p>Memuat data ancaman...</p></div> : error ? <div className="asset-empty" role="alert"><ShieldAlert size={30} /><h3>Data belum dapat dimuat</h3><p>{error}</p><button className="refresh-button" onClick={() => setRevision((v) => v + 1)}>Coba lagi</button></div> : data?.data.length ? <>
                <div className="table-wrapper"><table className="dashboard-table asset-table"><caption className="asset-sr-only">Daftar ancaman keamanan informasi</caption><thead><tr>{["Ancaman", "Aset terkait", "Kerentanan", "Status", "Aksi"].map((label) => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>{data.data.map((threat) => <tr key={threat.id}>
                    <td><div className="asset-name"><span className="asset-type-icon threat-symbol"><ShieldAlert size={20} /></span><div><strong>{threat.nama_ancaman}</strong><small className="table-subtext">{threat.kode_ancaman}</small>{threat.deskripsi && <p className="asset-description">{threat.deskripsi}</p>}</div></div></td>
                    <td><div className="threat-asset"><Boxes size={15} /><span>{threat.asset?.nama || "Belum dikaitkan"}<small className="table-subtext">{threat.asset?.snumber || "—"}</small></span></div></td>
                    <td><div className="threat-vulnerabilities">{threat.vulnerabilities.length ? threat.vulnerabilities.map((item) => <span key={item.id}><Bug size={12} />{item.nama_kerentanan}</span>) : <small>Belum ada kerentanan terkait</small>}</div></td>
                    <td><span className={`threat-status ${threat.is_active ? "active" : "inactive"}`}><i />{threat.is_active ? "Aktif" : "Tidak aktif"}</span></td>
                    <td><button className="asset-edit" aria-label={`Edit ${threat.nama_ancaman}`} onClick={() => setEditor({ threat })}><Pencil size={15} />Edit</button></td>
                </tr>)}</tbody></table></div><div className="asset-pagination"><span>Menampilkan {data.from}–{data.to} dari {data.total} ancaman</span><div><button aria-label="Halaman sebelumnya" disabled={page <= 1} onClick={() => setPage(page - 1)}><ChevronLeft size={17} /></button><span>Halaman {data.current_page} dari {data.last_page}</span><button aria-label="Halaman berikutnya" disabled={page >= data.last_page} onClick={() => setPage(page + 1)}><ChevronRight size={17} /></button></div></div>
            </> : <div className="asset-empty"><ShieldAlert size={36} /><h3>{query || status ? "Ancaman tidak ditemukan" : "Belum ada ancaman terdaftar"}</h3><p>{query || status ? "Sesuaikan pencarian atau reset filter untuk melihat data lainnya." : "Mulai dokumentasikan ancaman untuk melengkapi pemetaan risiko."}</p><button className="asset-primary" onClick={query || status ? reset : () => setEditor({})}>{query || status ? "Reset filter" : "Tambah ancaman"}</button></div>}
        </section><p className="asset-footnote">Status aktif menunjukkan penggunaan catatan ancaman, bukan tingkat keparahan risiko.</p>
        {editor && <ThreatEditor threat={editor.threat} onClose={() => setEditor(null)} onSaved={() => { setNotice(editor.threat ? "Perubahan ancaman berhasil disimpan." : "Ancaman baru berhasil ditambahkan."); setEditor(null); setRevision((v) => v + 1); }} />}
    </div>;
}

function ThreatEditor({ threat, onClose, onSaved }: { threat?: Threat; onClose: () => void; onSaved: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [form, setForm] = useState<ThreatInput>({ kode_ancaman: threat?.kode_ancaman || "", nama_ancaman: threat?.nama_ancaman || "", deskripsi: threat?.deskripsi || "", asset_id: threat?.asset_id ?? null, is_active: threat?.is_active ?? true, vulnerability_ids: threat?.vulnerabilities.map((item) => item.id) || [] });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState("");
    const [assets, setAssets] = useState<AssetPage | null>(null);
    const [assetQuery, setAssetQuery] = useState("");
    const [assetPage, setAssetPage] = useState(1);
    const [assetLoading, setAssetLoading] = useState(true);
    const [assetError, setAssetError] = useState("");
    const [assetRevision, setAssetRevision] = useState(0);
    const [selectedAsset, setSelectedAsset] = useState(threat?.asset ?? null);
    useEffect(() => { const element = dialog.current; element?.showModal(); return () => element?.close(); }, []);
    useEffect(() => {
        const controller = new AbortController();
        setAssetLoading(true); setAssetError("");
        const timer = window.setTimeout(() => {
            getAssets({ page: assetPage, search: assetQuery, kategori: "" }, controller.signal)
                .then((result) => { if (!controller.signal.aborted) setAssets(result); })
                .catch((err: unknown) => { if (!controller.signal.aborted) setAssetError(getApiError(err)); })
                .finally(() => { if (!controller.signal.aborted) setAssetLoading(false); });
        }, 300);
        return () => { window.clearTimeout(timer); controller.abort(); };
    }, [assetPage, assetQuery, assetRevision]);
    async function submit(event: FormEvent) {
        event.preventDefault(); if (saving) return;
        setSaving(true); setError("");
        try { await saveThreat(form, threat?.id); onSaved(); } catch (err) { setError(getApiError(err)); } finally { setSaving(false); }
    }
    return <dialog ref={dialog} className="asset-dialog threat-dialog" aria-labelledby="threat-editor-title" onCancel={(event) => { event.preventDefault(); if (!saving) onClose(); }}><form onSubmit={submit}>
        <header><div><span className="dashboard-eyebrow">IDENTIFIKASI ANCAMAN</span><h2 id="threat-editor-title">{threat ? "Edit ancaman" : "Tambah ancaman baru"}</h2></div><button type="button" className="asset-edit" disabled={saving} aria-label="Tutup formulir" onClick={onClose}><X size={20} /></button></header>
        <div className="asset-form-fields"><p>Lengkapi identitas ancaman. Kolom bertanda * wajib diisi.</p>
            <label>Kode ancaman *<input autoFocus required maxLength={100} placeholder="Contoh: THR-001" value={form.kode_ancaman} onChange={(e) => setForm({ ...form, kode_ancaman: e.target.value })} /></label>
            <label>Nama ancaman *<input required maxLength={255} placeholder="Contoh: Akses tidak sah" value={form.nama_ancaman} onChange={(e) => setForm({ ...form, nama_ancaman: e.target.value })} /></label>
            <label>Deskripsi<textarea rows={3} placeholder="Jelaskan ancaman dan potensi dampaknya" value={form.deskripsi} onChange={(e) => setForm({ ...form, deskripsi: e.target.value })} /></label>
            <div className="threat-asset-picker"><label>Cari aset terkait<input type="search" placeholder="Cari nama atau serial number..." value={assetQuery} onChange={(e) => { setAssetQuery(e.target.value); setAssetPage(1); }} /></label>
            <label>Aset terkait (opsional)<select disabled={assetLoading || !!assetError} value={form.asset_id ?? ""} onChange={(e) => { const id = Number(e.target.value) || null; setForm({ ...form, asset_id: id }); setSelectedAsset(assets?.data.find((item) => item.id === id) ?? null); }}><option value="">Tanpa aset terkait</option>{selectedAsset && !assets?.data.some((item) => item.id === selectedAsset.id) && <option value={selectedAsset.id}>{selectedAsset.nama}{selectedAsset.snumber ? ` (SN: ${selectedAsset.snumber})` : ""}</option>}{assets?.data.map((asset) => <option key={asset.id} value={asset.id}>{asset.nama}{asset.snumber ? ` (SN: ${asset.snumber})` : ""}</option>)}</select></label>
            <label>Serial number aset<input readOnly value={selectedAsset?.snumber || ""} placeholder={selectedAsset ? "Aset ini belum memiliki serial number" : "Pilih aset terkait terlebih dahulu"} aria-describedby="threat-serial-help" /></label><small id="threat-serial-help">Terisi otomatis dari aset yang dipilih. Untuk mengubah serial number, edit data pada halaman Aset.</small>
            {assetLoading && <small role="status">Memuat pilihan aset...</small>}{assetError && <div role="alert"><p>{assetError}</p><button type="button" className="asset-edit" onClick={() => setAssetRevision((v) => v + 1)}>Muat ulang aset</button></div>}
            {!assetLoading && !assetError && assets && <div className="threat-picker-pages"><button type="button" disabled={assetPage <= 1} onClick={() => setAssetPage(assetPage - 1)}>Sebelumnya</button><small>{assets.total ? `Halaman ${assets.current_page} / ${assets.last_page}` : "Tidak ada aset sesuai pencarian"}</small><button type="button" disabled={assetPage >= assets.last_page} onClick={() => setAssetPage(assetPage + 1)}>Berikutnya</button></div>}</div>
            <label>Status<select value={form.is_active ? "1" : "0"} onChange={(e) => setForm({ ...form, is_active: e.target.value === "1" })}><option value="1">Aktif</option><option value="0">Tidak aktif</option></select></label>
            {!!threat?.vulnerabilities.length && <div className="threat-linked-note"><Bug size={16} /><span>{threat.vulnerabilities.length} kerentanan terkait tetap dipertahankan saat disimpan.</span></div>}
            {error && <p className="asset-form-error" role="alert">{error}</p>}
        </div><footer><button type="button" className="asset-edit" disabled={saving} onClick={onClose}>Batal</button><button type="submit" className="asset-primary" disabled={saving}>{saving ? "Menyimpan..." : "Simpan ancaman"}</button></footer>
    </form></dialog>;
}
