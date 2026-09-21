import AdminDeleteButton from "../../components/AdminDeleteButton";
import { useEffect, useRef, useState, type FormEvent } from "react";
import { ClipboardCheck, Plus, RefreshCw, Search, Pencil, X, ChevronLeft, ChevronRight, ShieldAlert } from "lucide-react";
import api, { getApiError } from "../../api/axios";
import { getAssets } from "../../api/assetApi";
import { getRiskAssessments, getRiskRules, saveRiskAssessment } from "../../api/riskAssessmentApi";
import type { RiskAssessment, RiskInput, RiskPage, RiskRules } from "../../types/riskAssessment";
import RelationPicker, { type RelationOption } from "../../components/RelationPicker";
import "./Dashboard.css";
import "./Assets.css";
import "./RiskAssessments.css";

function levelKey(value: string) { return ["sangat_tinggi", "ekstrem"].includes(value) ? "tinggi" : value; }
function today() { const date = new Date(); return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`; }
function riskDate(value: string) { return new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "short", year: "numeric" }).format(new Date(value)); }

export default function RiskAssessments() {
    const [data, setData] = useState<RiskPage | null>(null);
    const [rules, setRules] = useState<RiskRules | null>(null);
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [level, setLevel] = useState("");
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");
    const [editor, setEditor] = useState<{ risk?: RiskAssessment } | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        setLoading(true); setError("");
        Promise.all([getRiskAssessments(page, query, level, controller.signal), getRiskRules(controller.signal)])
            .then(([result, policy]) => { if (!controller.signal.aborted) { setData(result); setRules(policy); } })
            .catch((err: unknown) => { if (!controller.signal.aborted) setError(getApiError(err)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [page, query, level, revision]);
    function reset() { setSearch(""); setQuery(""); setLevel(""); setPage(1); }
    return <div className="dashboard assets-page risk-page">
        <div className="page-header"><div><span className="dashboard-eyebrow">ANALISIS KEAMANAN INFORMASI</span><h1>Risk Assessment</h1><p>Nilai kemungkinan dan dampak ancaman untuk menentukan prioritas risiko.</p></div><button className="asset-primary" disabled={!rules} onClick={() => setEditor({})}><Plus size={17} />Tambah penilaian</button></div>
        <section className="asset-intro" aria-label="Ringkasan penilaian">
            <div className="asset-intro-icon"><ClipboardCheck size={32} /></div>
            <div className="asset-intro-copy"><span className="asset-hero-eyebrow">PENILAIAN RISIKO</span><h2>Ukur dampaknya.<br />Tentukan prioritasnya.</h2><p>Petakan ancaman dan kerentanan untuk menentukan tingkat risiko setiap aset.</p></div>
            <div className="asset-hero-metric"><span>Penilaian sesuai filter</span><strong>{loading || error || !data ? "—" : data.total.toLocaleString("id-ID")}</strong><small>Skor = kemungkinan × dampak</small></div>
        </section>
        {rules && <details className="risk-help"><summary><span>Panduan & matriks risiko</span><ChevronRight size={16} aria-hidden="true" /></summary><RiskGuide rules={rules} /></details>}
        {rules && <div className="asset-category-cards risk-policy-grid" role="group" aria-label="Filter tingkat risiko">{rules.levels.map((band) => <button key={band.key} className={`asset-category-card ${level === band.key ? "is-active" : ""}`} aria-pressed={level === band.key} onClick={() => { setLevel(level === band.key ? "" : band.key); setPage(1); }}><span className="asset-card-icon"><ShieldAlert size={22} /></span><strong>Risiko {band.label.toLowerCase()}</strong><small>Skor {band.min}–{band.max}</small><span className="asset-card-indicator" aria-hidden="true" /></button>)}</div>}
        {notice && <p className="asset-notice" role="status">{notice}</p>}
        <section className="dashboard-panel"><div className="panel-header asset-panel-heading"><div><h3>Daftar Penilaian Risiko</h3><p>Hasil penilaian aset, ancaman, dan tingkat risiko.</p></div><button className="refresh-button" disabled={loading} onClick={() => setRevision((v) => v + 1)}><RefreshCw size={15} className={loading ? "dashboard-spinning" : ""} />Perbarui</button></div>
            <div className="asset-filters"><div className="asset-filter-caption"><span className="asset-filter-dot" />{level ? `Risiko ${level}` : "Semua tingkat risiko"}{(query || level) && <button onClick={reset}>Reset filter <X size={12} /></button>}</div><form className="asset-search" onSubmit={(event) => { event.preventDefault(); setQuery(search.trim()); setPage(1); }}><Search size={17} /><input aria-label="Cari aset, serial number, atau ancaman" placeholder="Cari aset atau ancaman..." value={search} maxLength={255} onChange={(e) => setSearch(e.target.value)} /><button>Cari</button></form></div>
            {loading ? <div className="asset-empty" role="status"><RefreshCw className="dashboard-spinning" size={28} /><p>Memuat penilaian risiko...</p></div> : error ? <div className="asset-empty" role="alert"><ShieldAlert size={30} /><h3>Data belum dapat dimuat</h3><p>{error}</p><button className="refresh-button" onClick={() => setRevision((v) => v + 1)}>Coba lagi</button></div> : data?.data.length ? <>
                <div className="table-wrapper"><table className="dashboard-table risk-table"><caption className="asset-sr-only">Hasil penilaian risiko</caption><thead><tr>{["Aset / ancaman", "Kemungkinan", "Dampak", "Skor / tingkat", "Penilaian", "Aksi"].map((label) => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>{data.data.map((risk) => <tr key={risk.id}>
                    <td><strong>{risk.asset?.nama || "Aset tidak tersedia"}</strong><small className="table-subtext">{risk.asset?.snumber || "Tanpa serial number"}</small><p className="risk-threat-name">{(risk.threats?.length ? risk.threats : risk.threat ? [risk.threat] : []).map((item) => item.nama_ancaman).join(", ") || "Ancaman tidak tersedia"}</p>{!!risk.vulnerabilities?.length && <p className="asset-description">Kerentanan: {risk.vulnerabilities.map((item) => item.nama_kerentanan).join(", ")}</p>}{risk.catatan && <p className="asset-description">{risk.catatan}</p>}</td><td>{risk.likelihood} / 5</td><td>{risk.impact} / 5</td><td><div className="risk-score-cell"><strong>{risk.skor}</strong><span className={`risk-badge risk-${levelKey(risk.level)}`}>{levelKey(risk.level)}</span></div></td><td>{riskDate(risk.tanggal_penilaian)}<small className="table-subtext">{risk.dinilai_oleh?.name || "Penilai tidak tersedia"}</small></td><td><button className="asset-edit" onClick={() => setEditor({ risk })} aria-label={`Edit penilaian ${risk.asset?.nama || risk.id}`}><Pencil size={15} />Edit</button><AdminDeleteButton endpoint={`/admin/risk-assessments/${risk.id}`} label={`Penilaian #${risk.id}`} onDeleted={() => { setNotice("Data berhasil dihapus."); setPage(1); setRevision((v) => v + 1); } } /></td>
                </tr>)}</tbody></table></div><div className="asset-pagination"><span>Menampilkan {data.from}–{data.to} dari {data.total} penilaian</span><div><button aria-label="Halaman sebelumnya" disabled={page <= 1} onClick={() => setPage(page - 1)}><ChevronLeft size={17} /></button><span>Halaman {data.current_page} dari {data.last_page}</span><button aria-label="Halaman berikutnya" disabled={page >= data.last_page} onClick={() => setPage(page + 1)}><ChevronRight size={17} /></button></div></div>
            </> : <div className="asset-empty"><ClipboardCheck size={36} /><h3>{query || level ? "Penilaian tidak ditemukan" : "Belum ada penilaian risiko"}</h3><p>{query || level ? "Sesuaikan pencarian atau reset filter." : "Mulai dengan memilih aset dan ancaman yang akan dinilai."}</p><button className="asset-primary" disabled={!rules} onClick={query || level ? reset : () => setEditor({})}>{query || level ? "Reset filter" : "Tambah penilaian"}</button></div>}
        </section><p className="asset-footnote">Skala kemungkinan dan dampak: 1–5. Batas kategori mengikuti konfigurasi penilaian sistem; data lama sangat tinggi dan ekstrem ditampilkan sebagai tinggi.</p>
        {editor && rules && <RiskEditor risk={editor.risk} rules={rules} onClose={() => setEditor(null)} onSaved={() => { setNotice("Penilaian risiko berhasil disimpan."); setEditor(null); setRevision((v) => v + 1); }} />}
    </div>;
}

function RiskGuide({ rules }: { rules: RiskRules }) {
    const scale = Array.from({ length: rules.scale_max - rules.scale_min + 1 }, (_, index) => rules.scale_min + index);
    return <section className="risk-guide" aria-labelledby="risk-guide-title">
        <div className="risk-guide-copy">
            <span className="dashboard-eyebrow">PANDUAN PENILAIAN</span>
            <h2 id="risk-guide-title">Dari identifikasi ke prioritas.</h2>
            <p>Gunakan kemungkinan dan dampak untuk menentukan tingkat risiko setiap aset.</p>
            <ol className="risk-guide-steps">
                <li><span>01</span><div><h3>Identifikasi aset & ancaman</h3><p>Pilih aset dan ancaman aktif yang relevan.</p></div></li>
                <li><span>02</span><div><h3>Nilai kemungkinan & dampak</h3><p>Gunakan skala {rules.scale_min} sampai {rules.scale_max}. Semakin besar nilai, semakin besar risikonya.</p></div></li>
                <li><span>03</span><div><h3>Catat dasar penilaian</h3><p>Simpan tanggal dan alasan penilaian untuk membantu evaluasi berikutnya.</p></div></li>
            </ol>
        </div>
        <div className="risk-reference">
            <div className="risk-reference-heading"><h3>Matriks risiko</h3><span>Kemungkinan × dampak</span></div>
            <table className="risk-reference-table">
                <caption>Baris: kemungkinan · Kolom: dampak</caption>
                <thead><tr><th scope="col"><abbr title="Kemungkinan / Dampak">K / D</abbr></th>{scale.map((value) => <th scope="col" key={value}>{value}</th>)}</tr></thead>
                <tbody>{[...scale].reverse().map((likelihood) => <tr key={likelihood}>
                    <th scope="row">{likelihood}</th>
                    {scale.map((impact) => {
                        const score = likelihood * impact;
                        const band = rules.levels.find((item) => score >= item.min && score <= item.max);
                        return <td key={impact} className={`risk-${band?.key}`}><span aria-label={`Skor ${score}, ${band?.label ?? "Belum dikategorikan"}`}>{score}</span></td>;
                    })}
                </tr>)}</tbody>
            </table>
            <div className="risk-reference-legend">{rules.levels.map((band) => <span className={`risk-${band.key}`} key={band.key}>{band.label} <b>{band.min}–{band.max}</b></span>)}</div>
            <p>Referensi skor berdasarkan aturan penilaian sistem.</p>
        </div>
    </section>;
}

interface Option { id: number; label: string }
function RiskLookup({ kind, assetId, selected, onSelect }: { kind: "asset" | "threat"; assetId?: number | null; selected: Option | null; onSelect: (value: Option | null) => void }) {
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [last, setLast] = useState(1);
    const [options, setOptions] = useState<Option[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [revision, setRevision] = useState(0);
    const label = kind === "asset" ? "aset" : "ancaman";
    useEffect(() => {
        const controller = new AbortController();
        setLoading(true); setError("");
        const timer = window.setTimeout(async () => {
            try {
                if (kind === "asset") {
                    const result = await getAssets({ page, search, kategori: "" }, controller.signal);
                    if (!controller.signal.aborted) { setOptions(result.data.map((item) => ({ id: item.id, label: `${item.nama}${item.snumber ? ` · ${item.snumber}` : ""}` }))); setLast(result.last_page); }
                } else if (assetId) {
                    const response = await api.get<{ data: { data: { id: number; nama_ancaman: string }[]; last_page: number } }>("/admin/risk-assessments/threat-options", { params: { asset_id: assetId, search, page }, signal: controller.signal });
                    if (!controller.signal.aborted) { setOptions(response.data.data.data.map((item) => ({ id: item.id, label: item.nama_ancaman }))); setLast(response.data.data.last_page); }
                } else { setOptions([]); }
            } catch (err) { if (!controller.signal.aborted) setError(getApiError(err)); }
            finally { if (!controller.signal.aborted) setLoading(false); }
        }, 250);
        return () => { window.clearTimeout(timer); controller.abort(); };
    }, [kind, assetId, page, search, revision]);
    const disabled = kind === "threat" && !assetId;
    return <div className="risk-lookup"><label>Cari {label}<input type="search" disabled={disabled} maxLength={255} placeholder={`Cari ${label}...`} value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} /></label><label>Pilih {label} *<select required={kind === "asset"} value={selected?.id ?? ""} disabled={disabled || loading || !!error} onChange={(e) => onSelect(options.find((item) => item.id === Number(e.target.value)) || null)}><option value="">{disabled ? "Pilih aset terlebih dahulu" : `Pilih ${label}`}</option>{selected && !options.some((item) => item.id === selected.id) && <option value={selected.id}>{selected.label}</option>}{options.map((item) => <option key={item.id} value={item.id}>{item.label}</option>)}</select></label>{error ? <div role="alert"><small>{error}</small><button type="button" className="asset-edit" onClick={() => setRevision((v) => v + 1)}>Coba lagi</button></div> : loading ? <small role="status">Memuat pilihan...</small> : !disabled && <div className="risk-lookup-pages"><button type="button" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</button><small>{options.length ? `Halaman ${page} / ${last}` : `Tidak ada ${label} sesuai pencarian`}</small><button type="button" disabled={page >= last} onClick={() => setPage(page + 1)}>Berikutnya</button></div>}</div>;
}

function RiskEditor({ risk, rules, onClose, onSaved }: { risk?: RiskAssessment; rules: RiskRules; onClose: () => void; onSaved: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [form, setForm] = useState<RiskInput>({ vulnerability_ids: risk?.vulnerabilities?.map((item) => item.id) || [], asset_id: risk?.asset_id ?? null, threat_ids: risk?.threats?.length ? risk.threats.map((item) => item.id) : risk?.threat_id ? [risk.threat_id] : [], likelihood: risk?.likelihood ?? 1, impact: risk?.impact ?? 1, tanggal_penilaian: risk?.tanggal_penilaian.slice(0, 10) || today(), catatan: risk?.catatan || "" });
    const [asset, setAsset] = useState<Option | null>(risk?.asset ? { id: risk.asset.id, label: risk.asset.nama } : null);
    const [threats, setThreats] = useState<Option[]>((risk?.threats?.length ? risk.threats : risk?.threat ? [risk.threat] : []).map((item) => ({ id: item.id, label: item.nama_ancaman })));
    const [linkNotice, setLinkNotice] = useState("");
    const [vulnerabilities, setVulnerabilities] = useState<RelationOption[]>(risk?.vulnerabilities?.map((item) => ({ id: item.id, label: item.nama_kerentanan })) || []);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState("");
    const score = form.likelihood * form.impact;
    const band = rules.levels.find((item) => score >= item.min && score <= item.max);
    const scale = Array.from({ length: rules.scale_max - rules.scale_min + 1 }, (_, i) => rules.scale_min + i);
    useEffect(() => { const element = dialog.current; element?.showModal(); return () => element?.close(); }, []);
    function selectVulnerabilities(items: RelationOption[]) {
        const added = items.filter((item) => !form.vulnerability_ids.includes(item.id));
        const linked = added.flatMap((item) => item.threats || []);
        const eligible = linked.filter((item) => item.is_active && (item.asset_id === null || item.asset_id === form.asset_id));
        const merged = new Map(threats.map((item) => [item.id, item]));
        eligible.forEach((item) => merged.set(item.id, { id: item.id, label: item.nama_ancaman }));
        const nextThreats = [...merged.values()];
        setVulnerabilities(items);
        setThreats(nextThreats);
        setForm({ ...form, vulnerability_ids: items.map((item) => item.id), threat_ids: nextThreats.map((item) => item.id) });
        setLinkNotice(linked.some((item) => !eligible.includes(item))
            ? "Ancaman yang aktif dan sesuai aset otomatis dipilih. Ancaman tidak aktif atau milik aset lain tidak disertakan."
            : eligible.length ? "Ancaman terkait kerentanan otomatis ditambahkan ke pilihan." : "");
    }
    async function submit(event: FormEvent) {
        event.preventDefault(); if (saving || !form.asset_id || !form.threat_ids.length) return;
        setSaving(true); setError("");
        try { await saveRiskAssessment(form, risk?.id); onSaved(); } catch (err) { setError(getApiError(err)); } finally { setSaving(false); }
    }
    return <dialog ref={dialog} className="asset-dialog risk-dialog" aria-labelledby="risk-editor-title" onCancel={(event) => { event.preventDefault(); if (!saving) onClose(); }}><form onSubmit={submit}><header><div><span className="dashboard-eyebrow">ANALISIS RISIKO</span><h2 id="risk-editor-title">{risk ? "Edit penilaian" : "Penilaian risiko baru"}</h2></div><button type="button" className="asset-edit" disabled={saving} aria-label="Tutup formulir" onClick={onClose}><X size={20} /></button></header>
        <div className="asset-form-fields risk-editor-body"><p className="risk-editor-intro">Lengkapi objek penilaian, tentukan skor, lalu simpan hasil evaluasi. Kolom bertanda * wajib diisi.</p>
        <section className="risk-form-section" aria-labelledby="risk-identification-title"><div className="risk-section-heading"><span>01</span><div><h3 id="risk-identification-title">Identifikasi risiko</h3><p>Tentukan aset serta ancaman dan kerentanan yang terkait.</p></div></div><div className="asset-form-row"><RiskLookup kind="asset" selected={asset} onSelect={(value) => { setAsset(value); setThreats([]); setVulnerabilities([]); setLinkNotice(""); setForm({ ...form, asset_id: value?.id ?? null, threat_ids: [], vulnerability_ids: [] }); }} /><RiskLookup key={form.asset_id ?? "none"} kind="threat" assetId={form.asset_id} selected={null} onSelect={(value) => { if (value && !form.threat_ids.includes(value.id)) { setThreats([...threats, value]); setForm({ ...form, threat_ids: [...form.threat_ids, value.id] }); } }} /></div>
            <div className="risk-selected-threats"><p>Pilih satu atau beberapa ancaman. Skor berikut berlaku untuk seluruh ancaman terpilih.</p>{threats.length ? <ul>{threats.map((item) => <li key={item.id}><span>{item.label}</span><button type="button" aria-label={`Hapus pilihan ${item.label}`} onClick={() => { setThreats(threats.filter((value) => value.id !== item.id)); setForm({ ...form, threat_ids: form.threat_ids.filter((id) => id !== item.id) }); }}><X size={14} /></button></li>)}</ul> : <p>Belum ada ancaman dipilih.</p>}</div>
            {form.asset_id ? <RelationPicker kind="vulnerabilities" selected={vulnerabilities} onChange={selectVulnerabilities} /> : <p className="risk-link-hint">Pilih aset terlebih dahulu untuk mengaitkan kerentanan dan ancamannya.</p>}
            <p className="risk-link-hint">Ancaman terkait kerentanan akan otomatis dipilih. Menghapus kerentanan tidak menghapus pilihan ancaman; Anda dapat menghapusnya secara terpisah.</p>
            {linkNotice && <p className="risk-link-hint" role="status">{linkNotice}</p>}
        </section>
        <section className="risk-form-section" aria-labelledby="risk-scoring-title"><div className="risk-section-heading"><span>02</span><div><h3 id="risk-scoring-title">Penilaian & tingkat risiko</h3><p>Atur skala atau pilih langsung pada matriks. Skor dihitung otomatis.</p></div></div>
            <div className="asset-form-row"><label>Kemungkinan (likelihood) *<select value={form.likelihood} onChange={(e) => setForm({ ...form, likelihood: Number(e.target.value) })}>{scale.map((value) => <option key={value} value={value}>{value} / {rules.scale_max}</option>)}</select></label><label>Dampak (impact) *<select value={form.impact} onChange={(e) => setForm({ ...form, impact: Number(e.target.value) })}>{scale.map((value) => <option key={value} value={value}>{value} / {rules.scale_max}</option>)}</select></label></div>
            <div className="risk-matrix-section"><div><h3>Matriks risiko 5 × 5</h3><p>Klik sel untuk memilih kemungkinan dan dampak.</p><div className="risk-matrix" role="group" aria-label="Matriks kemungkinan baris dan dampak kolom"><span>K / D</span>{scale.map((i) => <span key={`head-${i}`}>{i}</span>)}{[...scale].reverse().map((l) => <div className="risk-matrix-row" key={l}><span>{l}</span>{scale.map((i) => { const cellBand = rules.levels.find((b) => l * i >= b.min && l * i <= b.max); return <button type="button" key={i} className={`risk-${cellBand?.key} ${l === form.likelihood && i === form.impact ? "selected" : ""}`} aria-pressed={l === form.likelihood && i === form.impact} aria-label={`Kemungkinan ${l}, dampak ${i}, skor ${l * i}, ${cellBand?.label}`} onClick={() => setForm({ ...form, likelihood: l, impact: i })}>{l * i}</button>; })}</div>)}</div></div><div className={`risk-score-preview risk-${band?.key}`} role="status"><span>Skor penilaian</span><strong>{score}</strong><b>{band?.label}</b><small>{form.likelihood} kemungkinan × {form.impact} dampak</small></div></div>
            <p>{rules.levels.map((item) => `${item.label}: ${item.min}–${item.max}`).join(" · ")}</p><label>Tanggal penilaian *<input type="date" required min="1000-01-01" max={today()} value={form.tanggal_penilaian} onChange={(e) => setForm({ ...form, tanggal_penilaian: e.target.value })} /></label><label>Catatan penilaian<textarea rows={3} maxLength={10000} placeholder="Dasar penilaian kemungkinan dan dampak..." value={form.catatan} onChange={(e) => setForm({ ...form, catatan: e.target.value })} /></label></section>{error && <p className="asset-form-error" role="alert">{error}</p>}
        </div><footer><button type="button" className="asset-edit" disabled={saving} onClick={onClose}>Batal</button><button className="asset-primary" type="submit" disabled={saving || !form.asset_id || !form.threat_ids.length}>{saving ? "Menyimpan..." : "Simpan penilaian"}</button></footer></form></dialog>;
}
