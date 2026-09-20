import { useEffect, useState } from "react";
import { X } from "lucide-react";
import api, { getApiError } from "../api/axios";
import "./RelationPicker.css";
export interface LinkedThreat { id: number; nama_ancaman: string; asset_id: number | null; is_active: boolean }
export interface RelationOption { id: number; label: string; threats?: LinkedThreat[] }
export default function RelationPicker({ kind, selected, onChange }: { kind: "threats" | "vulnerabilities"; selected: RelationOption[]; onChange: (items: RelationOption[]) => void }) {
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [last, setLast] = useState(1);
    const [options, setOptions] = useState<RelationOption[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [revision, setRevision] = useState(0);
    const label = kind === "threats" ? "ancaman" : "kerentanan";
    useEffect(() => { const controller = new AbortController(); setLoading(true); setError(""); const timer = window.setTimeout(() => {
        api.get<{ data: { data: { id: number; nama_ancaman?: string; nama_kerentanan?: string; threats?: LinkedThreat[] }[]; last_page: number } }>(`/admin/${kind}`, { params: { search, page }, signal: controller.signal }).then((response) => { if (!controller.signal.aborted) { setOptions(response.data.data.data.map((item) => ({ id: item.id, label: item.nama_ancaman || item.nama_kerentanan || String(item.id), threats: item.threats }))); setLast(response.data.data.last_page); } }).catch((err) => { if (!controller.signal.aborted) setError(getApiError(err)); }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
    }, 250); return () => { controller.abort(); window.clearTimeout(timer); }; }, [kind, search, page, revision]);
    return <section className="relation-picker" aria-label={`Pilihan ${label}`}><label>Cari {label}<input type="search" maxLength={255} placeholder={`Cari ${label} yang sudah terdaftar...`} value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></label><p>Pilih beberapa {label} untuk dikaitkan ({selected.length} dipilih).</p><div className="relation-options">{loading ? <p role="status">Memuat pilihan...</p> : error ? <div role="alert"><p>{error}</p><button type="button" className="asset-edit" onClick={() => setRevision((v) => v + 1)}>Coba lagi</button></div> : options.length ? options.map((item) => <label key={item.id}><input type="checkbox" checked={selected.some((value) => value.id === item.id)} onChange={(event) => onChange(event.target.checked ? [...selected, item] : selected.filter((value) => value.id !== item.id))} />{item.label}</label>) : <p>Tidak ada {label} sesuai pencarian.</p>}</div><div className="relation-pages"><button type="button" className="asset-edit" disabled={loading || page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</button><small>Halaman {page} / {last}</small><button type="button" className="asset-edit" disabled={loading || page >= last} onClick={() => setPage(page + 1)}>Berikutnya</button></div>{selected.length > 0 && <ul className="relation-selected">{selected.map((item) => <li key={item.id}>{item.label}<button type="button" aria-label={`Hapus pilihan ${item.label}`} onClick={() => onChange(selected.filter((value) => value.id !== item.id))}><X size={14} /></button></li>)}</ul>}</section>;
}
