import AdminDeleteButton from "../../components/AdminDeleteButton";
import { useEffect, useRef, useState, type FormEvent } from "react";
import { Archive, Bug, ChevronLeft, ChevronRight, Eye, Link2, Pencil, Plus, RefreshCw, Search, ShieldAlert, X } from "lucide-react";
import { getVulnerabilities, getVulnerability, saveVulnerability } from "../../api/vulnerabilityApi";
import { getApiError } from "../../api/axios";
import type { Vulnerability, VulnerabilityDetail, VulnerabilityInput, VulnerabilityPage } from "../../types/vulnerability";
import RelationPicker, { type RelationOption } from "../../components/RelationPicker";
import "./Dashboard.css";
import "./Assets.css";
import "./Threats.css";
import "./Vulnerabilities.css";

const filters = [
    { value: "", title: "Semua kerentanan", description: "Seluruh catatan kerentanan", icon: Bug },
    { value: "1", title: "Kerentanan aktif", description: "Digunakan dalam pemetaan ancaman", icon: ShieldAlert },
    { value: "0", title: "Tidak aktif", description: "Catatan yang dinonaktifkan", icon: Archive },
];

export default function Vulnerabilities() {
    const [data, setData] = useState<VulnerabilityPage | null>(null);
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");
    const [editor, setEditor] = useState<{ item?: Vulnerability } | null>(null);
    const [detailId, setDetailId] = useState<number | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true); setError("");
        getVulnerabilities(page, query, status, controller.signal)
            .then((result) => { if (!controller.signal.aborted) setData(result); })
            .catch((err: unknown) => { if (!controller.signal.aborted) setError(getApiError(err)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [page, query, status, revision]);

    function reset() { setSearch(""); setQuery(""); setStatus(""); setPage(1); }

    return <div className="dashboard assets-page vulnerabilities-page">
        <div className="page-header"><div><span className="dashboard-eyebrow">PEMETAAN KELEMAHAN KEAMANAN</span><h1>Manajemen Kerentanan</h1><p>Dokumentasikan kelemahan dan keterkaitannya dengan ancaman keamanan informasi.</p></div><button className="asset-primary" onClick={() => { setNotice(""); setEditor({}); }}><Plus size={17} />Tambah kerentanan</button></div>
        <section className="asset-intro vulnerability-intro" aria-label="Ringkasan kerentanan"><div className="asset-intro-icon"><Bug size={32} /></div><div className="asset-intro-copy"><span className="asset-hero-eyebrow">VULNERABILITY REGISTER</span><h2>Petakan kelemahan.<br />Bangun pertahanan.</h2><p>Kenali celah pada aset, proses, dan sistem untuk mendukung penilaian risiko yang lebih menyeluruh.</p></div><div className="asset-hero-metric"><span>Kerentanan sesuai filter</span><strong>{loading || error || !data ? "—" : data.total.toLocaleString("id-ID")}</strong><small>{query ? `Pencarian: ${query}` : "Catatan kerentanan terdaftar"}</small></div></section>
        <div className="asset-category-cards threat-status-cards" role="group" aria-label="Filter status kerentanan">{filters.map(({ value, title, description, icon: Icon }) => <button key={value} className={`asset-category-card ${status === value ? "is-active" : ""}`} aria-pressed={status === value} onClick={() => { setStatus(value); setPage(1); }}><span className="asset-card-icon"><Icon size={22} /></span><strong>{title}</strong><small>{description}</small><span className="asset-card-indicator" aria-hidden="true" /></button>)}</div>
        {notice && <p className="asset-notice" role="status">{notice}</p>}
        <section className="dashboard-panel"><div className="panel-header asset-panel-heading"><div><h3>Daftar Kerentanan</h3><p>Identitas, status, dan jumlah ancaman yang terhubung.</p></div><button className="refresh-button" disabled={loading} onClick={() => setRevision((v) => v + 1)}><RefreshCw size={15} className={loading ? "dashboard-spinning" : ""} />Perbarui</button></div>
            <div className="asset-filters"><div className="asset-filter-caption"><span className="asset-filter-dot" />{filters.find((item) => item.value === status)?.title}{(query || status) && <button onClick={reset}>Reset filter <X size={12} /></button>}</div><form className="asset-search" onSubmit={(event) => { event.preventDefault(); setQuery(search.trim()); setPage(1); }}><Search size={17} aria-hidden="true" /><input aria-label="Cari nama kerentanan" placeholder="Cari nama kerentanan..." value={search} onChange={(e) => setSearch(e.target.value)} /><button>Cari</button></form></div>
            {loading ? <div className="asset-empty" role="status"><RefreshCw className="dashboard-spinning" size={28} /><p>Memuat data kerentanan...</p></div> : error ? <div className="asset-empty" role="alert"><Bug size={30} /><h3>Data belum dapat dimuat</h3><p>{error}</p><button className="refresh-button" onClick={() => setRevision((v) => v + 1)}>Coba lagi</button></div> : data?.data.length ? <>
                <div className="table-wrapper"><table className="dashboard-table vulnerability-table"><caption className="asset-sr-only">Daftar kerentanan keamanan informasi</caption><thead><tr>{["Kerentanan", "Ancaman terkait", "Status", "Aksi"].map((label) => <th key={label} scope="col">{label}</th>)}</tr></thead><tbody>{data.data.map((item) => <tr key={item.id}>
                    <td><div className="asset-name"><span className="asset-type-icon vulnerability-symbol"><Bug size={20} /></span><div><strong>{item.nama_kerentanan}</strong><p className="asset-description">{item.deskripsi || "Belum ada deskripsi."}</p></div></div></td>
                    <td><button className="vulnerability-count" aria-label={`Lihat ${item.threats_count} ancaman terkait ${item.nama_kerentanan}`} onClick={() => setDetailId(item.id)}><Link2 size={14} />{item.threats_count.toLocaleString("id-ID")} ancaman</button></td>
                    <td><span className={`threat-status ${item.is_active ? "active" : "inactive"}`}><i />{item.is_active ? "Aktif" : "Tidak aktif"}</span></td>
                    <td><div className="vulnerability-actions"><button className="asset-edit" aria-label={`Detail ${item.nama_kerentanan}`} onClick={() => setDetailId(item.id)}><Eye size={15} />Detail</button><button className="asset-edit" aria-label={`Edit ${item.nama_kerentanan}`} onClick={() => { setNotice(""); setEditor({ item }); }}><Pencil size={15} />Edit</button><AdminDeleteButton endpoint={`/admin/vulnerabilities/${item.id}`} label={item.nama_kerentanan} onDeleted={() => { setNotice("Data berhasil dihapus."); setPage(1); setRevision((v) => v + 1); } } /></div></td>
                </tr>)}</tbody></table></div><div className="asset-pagination"><span>Menampilkan {data.from}–{data.to} dari {data.total} kerentanan</span><div><button aria-label="Halaman sebelumnya" disabled={page <= 1} onClick={() => setPage(page - 1)}><ChevronLeft size={17} /></button><span>Halaman {data.current_page} dari {data.last_page}</span><button aria-label="Halaman berikutnya" disabled={page >= data.last_page} onClick={() => setPage(page + 1)}><ChevronRight size={17} /></button></div></div>
            </> : <div className="asset-empty"><Bug size={36} /><h3>{query || status ? "Kerentanan tidak ditemukan" : "Belum ada kerentanan terdaftar"}</h3><p>{query || status ? "Coba kata kunci lain atau tampilkan semua status." : "Tambahkan kerentanan pertama untuk melengkapi pemetaan keamanan."}</p><button className="asset-primary" onClick={query || status ? reset : () => setEditor({})}>{query || status ? "Reset filter" : "Tambah kerentanan"}</button></div>}
        </section><p className="asset-footnote">Status aktif menunjukkan penggunaan catatan kerentanan, bukan tingkat keparahan risiko.</p>
        {editor && <VulnerabilityEditor item={editor.item} onClose={() => setEditor(null)} onSaved={() => { setNotice(editor.item ? "Perubahan kerentanan berhasil disimpan." : "Kerentanan baru berhasil ditambahkan."); setEditor(null); setRevision((v) => v + 1); }} />}
        {detailId !== null && <VulnerabilityDetails id={detailId} onClose={() => setDetailId(null)} />}
    </div>;
}

function VulnerabilityEditor({ item, onClose, onSaved }: { item?: Vulnerability; onClose: () => void; onSaved: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [form, setForm] = useState<VulnerabilityInput>({ threat_ids: item?.threats?.map((threat) => threat.id) || [], nama_kerentanan: item?.nama_kerentanan || "", deskripsi: item?.deskripsi || "", is_active: item?.is_active ?? true });
    const [selectedThreats, setSelectedThreats] = useState<RelationOption[]>(item?.threats?.map((threat) => ({ id: threat.id, label: threat.nama_ancaman })) || []);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState("");
    useEffect(() => { const element = dialog.current; element?.showModal(); return () => element?.close(); }, []);
    async function submit(event: FormEvent) {
        event.preventDefault(); if (saving) return;
        setSaving(true); setError("");
        try { await saveVulnerability(form, item?.id); onSaved(); } catch (err) { setError(getApiError(err)); } finally { setSaving(false); }
    }
    return <dialog ref={dialog} className="asset-dialog" aria-labelledby="vulnerability-editor-title" onCancel={(event) => { event.preventDefault(); if (!saving) onClose(); }}><form onSubmit={submit}>
        <header><div><span className="dashboard-eyebrow">IDENTIFIKASI KERENTANAN</span><h2 id="vulnerability-editor-title">{item ? "Edit kerentanan" : "Tambah kerentanan"}</h2></div><button type="button" className="asset-edit" aria-label="Tutup formulir" disabled={saving} onClick={onClose}><X size={20} /></button></header>
        <div className="asset-form-fields"><p>Lengkapi informasi kerentanan. Kolom bertanda * wajib diisi.</p><label>Nama kerentanan *<input autoFocus required maxLength={255} placeholder="Contoh: Password mudah ditebak" value={form.nama_kerentanan} onChange={(e) => setForm({ ...form, nama_kerentanan: e.target.value })} /></label><label>Deskripsi<textarea rows={5} placeholder="Jelaskan kelemahan dan kondisi yang dapat dimanfaatkan oleh ancaman" value={form.deskripsi} onChange={(e) => setForm({ ...form, deskripsi: e.target.value })} /></label><label>Status<select value={form.is_active ? "1" : "0"} onChange={(e) => setForm({ ...form, is_active: e.target.value === "1" })}><option value="1">Aktif</option><option value="0">Tidak aktif</option></select></label><RelationPicker kind="threats" selected={selectedThreats} onChange={(items) => { setSelectedThreats(items); setForm({ ...form, threat_ids: items.map((value) => value.id) }); }} />{error && <p className="asset-form-error" role="alert">{error}</p>}</div>
        <footer><button type="button" className="asset-edit" disabled={saving} onClick={onClose}>Batal</button><button type="submit" className="asset-primary" disabled={saving}>{saving ? "Menyimpan..." : "Simpan kerentanan"}</button></footer>
    </form></dialog>;
}

function VulnerabilityDetails({ id, onClose }: { id: number; onClose: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [data, setData] = useState<VulnerabilityDetail | null>(null);
    const [error, setError] = useState("");
    const [revision, setRevision] = useState(0);
    useEffect(() => { const element = dialog.current; element?.showModal(); return () => element?.close(); }, []);
    useEffect(() => {
        const controller = new AbortController();
        setError(""); setData(null);
        getVulnerability(id, controller.signal).then((result) => { if (!controller.signal.aborted) setData(result); }).catch((err: unknown) => { if (!controller.signal.aborted) setError(getApiError(err)); });
        return () => controller.abort();
    }, [id, revision]);
    return <dialog ref={dialog} className="asset-dialog vulnerability-detail" aria-labelledby="vulnerability-detail-title" onCancel={(event) => { event.preventDefault(); onClose(); }}>
        <header><div><span className="dashboard-eyebrow">PEMETAAN KERENTANAN</span><h2 id="vulnerability-detail-title">Detail kerentanan</h2></div><button className="asset-edit" aria-label="Tutup detail" onClick={onClose}><X size={20} /></button></header>
        <div className="asset-form-fields">{error ? <div role="alert"><p className="asset-form-error">{error}</p><button className="asset-edit" onClick={() => setRevision((v) => v + 1)}>Coba lagi</button></div> : !data ? <p role="status">Memuat detail kerentanan...</p> : <>
            <div className="vulnerability-detail-title"><span className="asset-type-icon vulnerability-symbol"><Bug size={24} /></span><h3>{data.nama_kerentanan}</h3></div><span className={`threat-status ${data.is_active ? "active" : "inactive"}`}><i />{data.is_active ? "Aktif" : "Tidak aktif"}</span><p className="vulnerability-detail-description">{data.deskripsi || "Belum ada deskripsi."}</p>
            <h3 className="vulnerability-linked-title"><Link2 size={16} />Ancaman terkait ({data.threats.length})</h3>{data.threats.length ? <ul className="vulnerability-linked-list">{data.threats.map((threat) => <li key={threat.id}><ShieldAlert size={18} /><div><strong>{threat.nama_ancaman}</strong><small>{threat.kode_ancaman}</small></div><span className={`threat-status ${threat.is_active ? "active" : "inactive"}`}>{threat.is_active ? "Aktif" : "Tidak aktif"}</span></li>)}</ul> : <p>Kerentanan ini belum dikaitkan dengan ancaman.</p>}
        </>}</div><footer><button className="asset-edit" onClick={onClose}>Tutup</button></footer>
    </dialog>;
}
