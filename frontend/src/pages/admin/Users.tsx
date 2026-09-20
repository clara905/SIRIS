import { useEffect, useRef, useState, type FormEvent } from "react";
import { Users as UsersIcon, Plus, Pencil, Search, RefreshCw, X, ChevronLeft, ChevronRight, ShieldCheck, UserCheck, UserX } from "lucide-react";
import api, { getApiError } from "../../api/axios";
import OrganizationFields from "../../components/OrganizationFields";
import "./Dashboard.css";
import "./Assets.css";
import "./Threats.css";
import "./Users.css";

interface UserRecord { bidang_id: number | null; sub_bidang_id: number | null; satker_id: number | null; sub_bidang?: { nama_sub_bidang: string } | null; id: number; name: string; email: string; role_id: number; is_active: boolean; role: { name: string } | null; bidang?: { nama_bidang: string } | null; satker?: { nama_satker: string } | null }
interface UserPage { data: UserRecord[]; total: number; current_page: number; last_page: number; from: number | null; to: number | null }
interface Role { id: number; name: string }
const filters = [{ value: "", label: "Semua pengguna", description: "Seluruh akun terdaftar", icon: UsersIcon }, { value: "1", label: "Pengguna aktif", description: "Akun yang dapat mengakses sistem", icon: UserCheck }, { value: "0", label: "Tidak aktif", description: "Akun yang dinonaktifkan", icon: UserX }];

export default function Users() {
    const [data, setData] = useState<UserPage | null>(null);
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");
    const [editor, setEditor] = useState<{ user?: UserRecord } | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        setLoading(true); setError("");
        api.get<{ data: UserPage }>("/admin/users", { params: { page, search: query, is_active: status || undefined }, signal: controller.signal })
            .then((response) => { if (!controller.signal.aborted) setData(response.data.data); })
            .catch((err) => { if (!controller.signal.aborted) setError(getApiError(err)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [page, query, status, revision]);
    function reset() { setSearch(""); setQuery(""); setStatus(""); setPage(1); }
    function open(user?: UserRecord) { setNotice(""); setEditor({ user }); }
    return <div className="dashboard assets-page users-page">
        <div className="page-header"><div><span className="dashboard-eyebrow">AKSES & PENGGUNA</span><h1>Manajemen Pengguna</h1><p>Kelola identitas, peran, dan status akun pengguna SIRIS.</p></div><button className="asset-primary" onClick={() => open()}><Plus size={17} />Tambah pengguna</button></div>
        <section className="asset-intro"><div className="asset-intro-icon"><UsersIcon size={30} /></div><div className="asset-intro-copy"><span className="asset-hero-eyebrow">USER MANAGEMENT</span><h2>Kelola akses.<br />Bangun kolaborasi.</h2><p>Pastikan setiap pengguna memiliki peran dan akses yang sesuai.</p></div><div className="asset-hero-metric"><span>Pengguna sesuai filter</span><strong>{loading || error ? "?" : data?.total.toLocaleString("id-ID") ?? "0"}</strong><small>Akun terdaftar di sistem</small></div></section>
        <div className="asset-category-cards users-filters" role="group" aria-label="Filter status pengguna">{filters.map(({ value, label, description, icon: Icon }) => <button key={value} className={`asset-category-card ${status === value ? "is-active" : ""}`} aria-pressed={status === value} onClick={() => { setStatus(value); setPage(1); }}><span className="asset-card-icon"><Icon size={22} /></span><strong>{label}</strong><small>{description}</small><span className="asset-card-indicator" aria-hidden="true" /></button>)}</div>
        {notice && <p className="asset-notice" role="status">{notice}</p>}
        <section className="dashboard-panel"><div className="panel-header asset-panel-heading"><div><h3>Daftar Pengguna</h3><p>Informasi akun dan hak akses pengguna.</p></div><button className="refresh-button" disabled={loading} onClick={() => setRevision((v) => v + 1)}><RefreshCw size={15} className={loading ? "dashboard-spinning" : ""} />Perbarui</button></div>
            <div className="asset-filters"><div className="asset-filter-caption"><span className="asset-filter-dot" />{filters.find((item) => item.value === status)?.label}{(query || status) && <button onClick={reset}>Reset filter <X size={12} /></button>}</div><form className="asset-search" onSubmit={(event) => { event.preventDefault(); setQuery(search.trim()); setPage(1); }}><Search size={17} /><input aria-label="Cari nama atau email" placeholder="Cari nama atau email..." maxLength={255} value={search} onChange={(event) => setSearch(event.target.value)} /><button>Cari</button></form></div>
            {loading ? <div className="asset-empty" role="status"><RefreshCw className="dashboard-spinning" /><p>Memuat pengguna...</p></div> : error ? <div className="asset-empty" role="alert"><ShieldCheck /><h3>Data belum dapat dimuat</h3><p>{error}</p><button className="asset-edit" onClick={() => setRevision((v) => v + 1)}>Coba lagi</button></div> : data?.data.length ? <><div className="table-wrapper"><table className="dashboard-table asset-table users-table"><caption className="asset-sr-only">Daftar akun pengguna</caption><thead><tr>{["Pengguna", "Peran", "Organisasi", "Status", "Aksi"].map((label) => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>{data.data.map((user) => <tr key={user.id}><td><div className="asset-name"><span className="user-avatar">{user.name.trim().slice(0, 2).toUpperCase()}</span><div><strong>{user.name}</strong><small className="table-subtext">{user.email}</small></div></div></td><td><span className="user-role"><ShieldCheck size={13} />{user.role?.name || "Tanpa peran"}</span></td><td>{user.bidang?.nama_bidang || "Belum ditentukan"}<small className="table-subtext">{[user.sub_bidang?.nama_sub_bidang, user.satker?.nama_satker].filter(Boolean).join(" / ")}</small></td><td><span className={`threat-status ${user.is_active ? "active" : "inactive"}`}><i />{user.is_active ? "Aktif" : "Tidak aktif"}</span></td><td><button className="asset-edit" aria-label={`Edit ${user.name}`} onClick={() => open(user)}><Pencil size={15} />Edit</button></td></tr>)}</tbody></table></div><div className="asset-pagination"><span>Menampilkan {data.from}?{data.to} dari {data.total} pengguna</span><div><button aria-label="Halaman sebelumnya" disabled={page <= 1} onClick={() => setPage(page - 1)}><ChevronLeft size={17} /></button><span>Halaman {page} dari {data.last_page}</span><button aria-label="Halaman berikutnya" disabled={page >= data.last_page} onClick={() => setPage(page + 1)}><ChevronRight size={17} /></button></div></div></> : <div className="asset-empty"><UsersIcon size={32} /><h3>Belum ada pengguna sesuai filter</h3><p>Sesuaikan pencarian atau tambahkan pengguna baru.</p><button className="asset-primary" onClick={query || status ? reset : () => open()}>{query || status ? "Reset filter" : "Tambah pengguna"}</button></div>}
        </section>
        {editor && <UserEditor user={editor.user} onClose={() => setEditor(null)} onSaved={() => { setEditor(null); setNotice("Data pengguna berhasil disimpan."); setRevision((v) => v + 1); }} />}
    </div>;
}

function UserEditor({ user, onClose, onSaved }: { user?: UserRecord; onClose: () => void; onSaved: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [form, setForm] = useState({ bidang_id: user?.bidang_id ?? null, sub_bidang_id: user?.sub_bidang_id ?? null, satker_id: user?.satker_id ?? null, name: user?.name || "", email: user?.email || "", password: "", role_id: user?.role_id ? String(user.role_id) : "", is_active: user?.is_active ?? true });
    const [roles, setRoles] = useState<Role[]>([]);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState("");
    const [error, setError] = useState("");
    const [revision, setRevision] = useState(0);
    const [saving, setSaving] = useState(false);
    useEffect(() => { const element = dialog.current; element?.showModal(); return () => element?.close(); }, []);
    useEffect(() => { const controller = new AbortController(); setLoading(true); setLoadError(""); api.get<{ data: Role[] }>("/admin/users/roles", { signal: controller.signal }).then((response) => { if (!controller.signal.aborted) setRoles(response.data.data); }).catch((err) => { if (!controller.signal.aborted) setLoadError(getApiError(err)); }).finally(() => { if (!controller.signal.aborted) setLoading(false); }); return () => controller.abort(); }, [revision]);
    async function submit(event: FormEvent) { event.preventDefault(); if (saving || loading || loadError) return; setSaving(true); setError(""); try { const payload = { ...form, role_id: Number(form.role_id), password: form.password || undefined }; if (user) await api.put(`/admin/users/${user.id}`, payload); else await api.post("/admin/users", payload); onSaved(); } catch (err) { setError(getApiError(err)); } finally { setSaving(false); } }
    return <dialog ref={dialog} className="asset-dialog" aria-labelledby="user-editor-title" onCancel={(event) => { event.preventDefault(); if (!saving) onClose(); }}><form onSubmit={submit}><header><div><span className="dashboard-eyebrow">IDENTITAS & AKSES</span><h2 id="user-editor-title">{user ? "Edit pengguna" : "Tambah pengguna"}</h2></div><button type="button" className="asset-edit" aria-label="Tutup formulir" disabled={saving} onClick={onClose}><X size={20} /></button></header><div className="asset-form-fields"><p>Lengkapi informasi akun. Kolom bertanda * wajib diisi.</p><label>Nama lengkap *<input autoFocus required maxLength={255} autoComplete="name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></label><label>Email *<input type="email" required maxLength={255} autoComplete="off" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></label><label>{user ? "Password baru (opsional)" : "Password *"}<input type="password" required={!user} minLength={8} autoComplete="new-password" placeholder={user ? "Kosongkan untuk mempertahankan password" : "Minimal 8 karakter"} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} /></label><div className="asset-form-row"><label>Peran *<select required disabled={loading || !!loadError} value={form.role_id} onChange={(e) => setForm({ ...form, role_id: e.target.value })}><option value="">{loading ? "Memuat peran..." : "Pilih peran"}</option>{roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}</select></label><label>Status<select value={form.is_active ? "1" : "0"} onChange={(e) => setForm({ ...form, is_active: e.target.value === "1" })}><option value="1">Aktif</option><option value="0">Tidak aktif</option></select></label></div><OrganizationFields value={form} onChange={(organization) => setForm({ ...form, ...organization })} />{loadError && <div role="alert"><p>{loadError}</p><button className="asset-edit" type="button" onClick={() => setRevision((v) => v + 1)}>Muat ulang peran</button></div>}{error && <p className="asset-form-error" role="alert">{error}</p>}</div><footer><button type="button" className="asset-edit" disabled={saving} onClick={onClose}>Batal</button><button className="asset-primary" disabled={saving || loading || !!loadError || !form.role_id}>{saving ? "Menyimpan..." : "Simpan pengguna"}</button></footer></form></dialog>;
}
